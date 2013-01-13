<?php 

// Definovanie mennej triedy
namespace Opiner;

// Zakladne konstanty
const
	name = 'Opiner',
	version = '0.5',
	url = 'http://tatarko.github.com/Opiner/',
	author = 'Tomáš Tatarko',
	authorUrl = 'http://tatarko.sk/',
	toDie = 1,
	toLog = 2,
	toReturn = 4;

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
 * Opiner\Application::compile (__FILE__, 'default');
 *
 * ?>
 * </pre>
 *
 * @author Tomas Tatarko
 * @since 0.2
 */

class Application {

	use Behavior;
	
	const
		locationConfig		= '[scripts]config/$1.php',
		locationController	= '[scripts]controllers/$1.php',
		locationModules		= '[scripts]modules/$1.php';

	public static
		$headerType = 'text/html',	// Aky mimetype ma byt poslany do hlaviciek
		$charSet = 'UTF-8',		// Ake kodovanie pouzivame
		$log = [];			// Co budeme logovat

	protected static
		$settings = ['modules' => []],	// Pole nastaveni aplikacie
		$modules = [],			// Nacitane moduly
		$debug,				// Debug objekt
		$moduleIndexes = ['cache', 'database', 'language', 'router', 'menu', 'template'];
						// Ake moduly automaticky nacitavat?



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

	public static function compile ($webRoot, $configFile = null)
	{
		try
		{

			// Nasadenie zakladnych premennych objektu
			define ('Opiner\\web', substr (str_replace ('\\', '/', $webRoot), 0, strrpos (str_replace ('\\', '/', $webRoot), '/')) . '/');
			define ('Opiner\\scripts', substr (str_replace ('\\', '/', $webRoot), 0, strrpos (str_replace ('\\', '/', $webRoot), '/')) . '/private/');
			define ('Opiner\\remote', 'http://' . $_SERVER['HTTP_HOST'] . substr ($_SERVER['SCRIPT_NAME'], 0, strrpos ($_SERVER['SCRIPT_NAME'], '/') + 1));

			// Práca s hlavičkami, kodovanim
			Header ('Content-Type: ' . self::$headerType . '; charset=' . self::$charSet);
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
			$dir = opendir (scripts . 'models');
			while ($file = readdir ($dir))
			if (substr ($file, -4) == '.php')
			self::getFile (scripts . 'models/' . $file);
			
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
			$methods = ['startup', 'prepareCompilation', 'compile', 'afterCompilation'];
			foreach ($methods as $method)
			foreach (self::$modules as $module)
			if (method_exists ($module, $method))
			$module -> $method ();

			echo self::$debug;
		} catch (Exception $exception) {
			die ($exception);
		}
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
		if (!defined ('Opiner\\Application::location' . ucfirst ($type)))
		throw new Exception ($type, 103);
		
		$route = constant ('Opiner\\Application::location' . ucfirst ($type));
		foreach (func_get_args () as $index => $value)
		$route = str_replace ('$' . $index, $value, $route);
		while (preg_match ('#\[([a-z]+)\]#ius', $route, $match))
		$route = str_replace ($match [0], defined ('Opiner\\' . $match [1]) ? constant ('Opiner\\' . $match [1]) : '', $route);
		
		return $route;
	}
}
?>