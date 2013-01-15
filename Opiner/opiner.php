<?php 

// Definovanie mennej triedy
namespace Opiner;

// Zakladne konstanty
const
	name = 'Opiner',
	version = '0.6',
	url = 'http://tatarko.github.com/Opiner/',
	author = 'Tomáš Tatarko',
	authorUrl = 'http://tatarko.sk/';

// Definovanie cesty k frameworku
define ('Opiner\root', substr (str_replace ('\\', '/', __FILE__), 0, strrpos (str_replace ('\\', '/', __FILE__), '/')) . '/');

// Nacitanie toho najzakladnejsieho
require_once (root . 'class/exception.php');
require_once (root . 'trait/behavior.php');





/**
 * Korenova trieda celeho frameworku Opiner
 * 
 * Cely tento framework je pripraveny tak, aby na vygenerovanie celej
 * stacilo zavolanie jedinej statickej metody v povodnom (index.php)
 * subore stranky. Tato metoda sluzi dalej ako rozcestnik dalsich
 * procesov pre vygenerovanie stranky. Typicky priklad zakladneho,
 * korenoveho suboru suboru index.php.
 *
 * <pre>
 * <?php
 * 
 * // Načítanie triedy jadra
 * include ('Opiner/opiner.php');
 * 
 * // Skompilovanie vystupu
 * Opiner\Framework::compile (__FILE__, 'default');
 *
 * ?>
 * </pre>
 *
 * @author Tomas Tatarko
 * @since 0.2
 */

class Framework {

	use Behavior;
	
	const
		locationConfig		= '[getPrivateLocation]config/$1.php',
		locationController	= '[getPrivateLocation]controllers/$1.php',
		locationLanguage	= '[getPrivateLocation]languages/$1.php',
		locationModel		= '[getPrivateLocation]models/$1.php',
		locationModule		= '[getPrivateLocation]modules/$1.php',
		errorToLog		= 1,
		errorToDie		= 2,
		errorToReturn		= 3;



	public static
		$headerType = 'text/html',
		$charSet = 'UTF-8',
		$log = [],
		$defaultPrivateFolder = '[webroot]private/';
						
		

	protected static
		$settings = [
			'template'	=> 'default',
			'language'	=> 'english',
			'router'	=> '{$controller:string:site$/{$action:string:default$/}}',
			'modules'	=> [],
		],
		$webroot = null,
		$scripts = null,
		$remote = null,
		$modules = [],
		$debug,
		$moduleIndexes = ['cache', 'database', 'language', 'router', 'menu', 'template'];



	/**
	 * Skompilovanie samotnej stranky, rozcestnik na vsetko
	 * 
	 * V prvom stadiu sa definuju konstanty ciest k jednotlivym
	 * priecinkom v ramci systemu. Nasledne sa nastavi kodovanie stranky
	 * a zacinaju sa nacitavat subory potrebne pre spravny chod systemu.
	 * Ak zadany parameter $configFile, pokracuje sa nacitanim konfiguracie
	 * stranky z daneho suboru. Ako dalsie sa zacinaju nacitavat databazove
	 * modely a ako posledne sa nacitavaju moduly. Po uspesnom prejdeni tymito
	 * vsetkymi procesmi sa volaju metody modulov, ktore vlastne zabezpecia
	 * samotne vygenerovanie a vyexportovanie webovej stranky.
	 * 
	 * @param string Adresa suboru, cez ktory bol zavolany framework Opiner (__FILE__)
	 * @param string Nazov konfiguracneho suboru z /private/config, ktory sa ma nacitat
	 */

	public static function compile ($configFile = null, $return = false, $webroot = null)
	{
		try
		{
			// Nasadenie zakladnych premennych objektu
			if ($webroot === null) $webroot = debug_backtrace() [0] ['file'];
			self::$webroot = substr (str_replace ('\\', '/', $webroot), 0, strrpos (str_replace ('\\', '/', $webroot), '/')) . '/';
			self::$scripts = str_replace ('[webroot]', self::$webroot, self::$defaultPrivateFolder);
			self::$remote = 'http://' . $_SERVER['HTTP_HOST'] . substr ($_SERVER['SCRIPT_NAME'], 0, strrpos ($_SERVER['SCRIPT_NAME'], '/') + 1);

			// Práca s hlavičkami, kodovanim
			if (!$return) Header ('Content-Type: ' . self::$headerType . '; charset=' . self::$charSet);
			mb_internal_encoding (self::$charSet);
			mb_regex_encoding (self::$charSet);
		
			// Nacitanie suborov, priprava debugu
			self::getFile (root . 'class/debug.php');
			self::getFile (root . 'class/module.php');
			self::getFile (root . 'class/model.php');
			self::getFile (root . 'class/modelhandler.php');
			self::$debug = new Debug ();
	
			// Načítanie konfiguracie zo súboru
			if ($configFile !== null)
			{
				$file = self::location ('config', $configFile);
				if (!self::isFile ($file))
				throw new Exception ($configFile, 102); 
				self::$settings = array_merge (self::$settings, require ($file));
			}
			
			// Nacitanie modelov #TODO
			$modelLocation = substr (self::location ('model'), 0, strrpos (self::location ('model'), '/') + 1);
			$dir = opendir ($modelLocation);
			while ($file = readdir ($dir))
			if (substr ($file, -4) == '.php')
			self::getFile ($modelLocation . $file);
			
			$content = self::loadModules ();
			echo self::$debug;
			if ($return === true)
				return $content;
				else echo $content;

		} catch (Exception $exception) {
			die ($exception);
		}
	}



	/**
	 * Nacita moduly
	 *
	 * Backend metody compile(). Nacita vsetky pozadove
	 * ktore boli uvedene v konfiguracii.
	 *
	 * @since 0.6
	 */

	protected static function loadModules ()
	{
		// Predpriprava externych modulov
		$indexes = [];
		foreach (self::$settings ['modules'] as $index => $type)
		{
			$indexes [] = $index;
			self::$settings [$index] ['type'] = $type;
		}
		self::$moduleIndexes = array_merge ($indexes, self::$moduleIndexes);

		// Nacitavanie modulov na poziadanie
		foreach (self::$moduleIndexes as $module)
		{
			$type = isset (self::$settings [$module] ['type']) ? self::$settings [$module] ['type'] : $module;
			$name = '\\Opiner\\Module\\' . ucfirst ($type);
			$config = isset (self::$settings [$module]) ? self::$settings [$module] : null;

			// Z internych modulov
			if (self::isFile (root . 'modules/' . $type . '.php'))
			{
				self::getFile (root . 'modules/' . $type . '.php');
				self::$modules [$module] = new $name ($config);
			}
			
			// Externe moduly
			elseif (self::isFile (self::location ('modules', $type)))
			{
				self::getFile (self::location ('modules', $type));
				if (!class_exists ($name))
				throw new Exception ($type, 111);
				self::$modules [$module [0]] = new $name ($config);
			}
			else throw new Exception ($type, 110);
		}

		// Postupne spustanie vsetkych ocakavanych metod
		ob_start ();
		$methods = ['startup', 'prepareCompilation', 'compile', 'afterCompilation'];
		foreach ($methods as $method)
		foreach (self::$modules as $module)
		if (method_exists ($module, $method))
		$module -> $method ();
		$return = ob_get_contents();
		ob_end_clean();
		return $return;
		
	}



	/**
	 * Ziskavanie alebo nastavovanie konfiguracnych hodnot stranky
	 *
	 * Ak tejto funkcii predany iba prvy parameter, framework vrati hodnotu
	 * konfiguracnej hodnoty. Ak je tejto funkcii predany aj druhy parameter,
	 * tak dochadza k zmenej konfiguracie. Od tretieho parametra zavisi,
	 * ci bude tato zmena ulozena aj do konfiguracnej tabulky v databaze.
	 *
	 * @param string Unikatny nazov konfiguracnej premennej
	 * @param mixed Hodnota konfiguracnej hodnoty
	 * @param boolean Ulozit novu konfiguracnu hodnotu aj do databazy?
	 */

	public static function config ($key, $value = null, $updateDb = true)
	{
		if ($value === null)
		return isset (self::$settings [$key]) ? self::$settings [$key] : null;

		self::$settings [$key] = $value;
		return $updateDb ? self::module ('database') -> updateConfigValue ($key, $value) : true;
	}



	/**
	 * Callback vracajuci pozadovany modul na zaklade predaneho nazvu
	 *
	 * @param string Unikatny nazov pozadovaneho modulu
	 * @return object
	 */

	public static function module ($localname)
	{
		return isset (self::$modules [$localname]) ? self::$modules [$localname] : null;
	}



	/**
	 * Prekladanie klucovych slov na adresy k pozadovanych suborom
	 *
	 * Nakolko cesty k niektorym zo systemovych suborov sa mozu casom
	 * menit, su definovane oddelene v konstantach. Tato metoda poskytuje
	 * moznost ziskat adresu priecinka, z ktoreho ma byt pozadovany
	 * subor nacitany
	 *
	 * @param string type Typ, co potrebujeme nacitat
	 * @return string
	 */

	public static function location ($type)
	{
		if (!defined ('Opiner\\Framework::location' . ucfirst ($type)))
		throw new Exception ($type, 103);
		
		$route = constant ('Opiner\\Framework::location' . ucfirst ($type));
		foreach (func_get_args () as $index => $value)
		$route = str_replace ('$' . $index, $value, $route);
		while (preg_match ('#\[([a-z]+)\]#ius', $route, $match))
		$route = str_replace ($match [0], method_exists ('\\Opiner\\Framework', $match [1]) ? Framework::$match[1] () : '', $route);
		
		return $route;
	}



	/**
	 * Vrati adresu stranky (verejne dostupnu
	 * @return string
	 * @since 0.6
	 */

	public static function getRemoteLocation ()
	{
		return self::$remote;
	}



	/**
	 * Vrati adresu k sukromnym/tajnym suborom stranky
	 * @return string
	 * @since 0.6
	 */

	public static function getPrivateLocation ()
	{
		return self::$scripts;
	}



	/**
	 * Vrati adresu k zakladnemu priecinku stranky
	 * @return string
	 * @since 0.6
	 */

	public static function getWebLocation ()
	{
		return self::$webroot;
	}
}
?>