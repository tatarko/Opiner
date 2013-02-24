<?php

namespace Opiner;

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
 * @url https://github.com/tatarko/Opiner
 * @package tatarko\Opiner
 * @version 0.6
 * @since 0.2
 */

class Framework extends Object {
	
	/**
	 * Aky typ dokumentu ma byt vygenerovany
	 */
	public static $headerType = DEFAULT_CONTENT_TYPE;
	
	/**
	 * Kodovanie vygenerovaneho dokumentu
	 */
	public static $charSet = DEFAULT_CHARSET;
	
	/**
	 * Log aplikacie 
	 */
	public static $log;
	
	/**
	 * Nastavenia aktualne spustenej instancie frameworku
	 */
	protected static $settings;
	
	/**
	 * Fyzicka adresa k suborom webovej stranky
	 */
	protected static $webroot;
	
	/**
	 * Fyzicka adresa k skriptovacim suborom stranky
	 */
	protected static $scripts;
	
	/**
	 * URL adresa webovej stranky 
	 */
	protected static $remote;
	
	/**
	 * Pole nacitanych modulov
	 */
	protected static $modules;
	
	/**
	 * Objekt debuggera
	 */
	protected static $debugger;
	
	
	/**
	 * Zoznam nacitanych modulov
	 */
	protected static $moduleIndexes;
	
	
	/**
	 * Zoznam nacitanych modulov
	 */
	protected static $prepared = false;

	
	
	/**
	 * Nacitanie statickych premennych Frameworku
	 * 
	 * Tato metoda nastavi vsetky pozadovane
	 * premenne pre chod frameworku na ich defaultne
	 * hdonoty.
	 * 
	 * @since 0.6
	 */

	public static function loadStaticValues() {
		
		self::$prepared			= true;
		self::$headerType		= DEFAULT_CONTENT_TYPE;
		self::$charSet			= DEFAULT_CHARSET;
		self::$log				= array();
		self::$modules			= array();
		self::$debugger			= new Debugger;
		self::$moduleIndexes		= array('cache', 'database', 'language', 'router', 'menu', 'template');
		self::$settings			= array(
			'template'	=> 'default',
			'language'	=> 'english',
			'router'	=> '{$controller:string:site$/{$action:string:default$/}}',
			'modules'	=> array(),
		);
	}


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

	public static function compile($configFile = null, $return = false, $webroot = null) {
		
		if(!self::$prepared)
			self::loadStaticValues();

		try {

			// Nasadenie zakladnych premennych objektu
			if ($webroot === null)
				$webroot = dirname(debug_backtrace()[0]['file']) . '/';
			self::$webroot	= $webroot;
			self::$scripts	= self::getLocation(LOCATION_PRIVATE_DIRECOTY);
			self::$remote	= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 'https://' : 'http://')
							. $_SERVER['HTTP_HOST'] . substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], DIRECTORY_SEPARATOR) + 1);

			// Práca s hlavičkami, kodovanim
			if(!$return)
				Header('Content-Type: ' . self::$headerType . '; charset=' . self::$charSet);
			mb_internal_encoding (self::$charSet);
			mb_regex_encoding (self::$charSet);
	
			// Načítanie konfiguracie zo súboru
			if ($configFile !== null) {
				$file = self::getLocation(LOCATION_CONFIG, $configFile);
				if(!self::isFile($file))
					throw new Exception($configFile, 102); 
				self::$settings = array_merge (self::$settings, require ($file));
			}
			
			$content = self::loadModules ();
			echo self::$debugger;
			if ($return === true)
				return $content;
				else echo $content;

		} catch (Exception $exception) {
			die($exception);
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

	protected static function loadModules() {

		// Predpriprava externych modulov
		foreach (self::$settings['modules'] as $index => $type) {
			$indexes[] = $index;
			self::$settings[$index]['type'] = $type;
		}
		
		// Pridanie indexov
		if(isset($indexes))
			self::$moduleIndexes = array_merge($indexes, self::$moduleIndexes);

		// Nacitavanie modulov na poziadanie
		foreach(self::$moduleIndexes as $module) {
			
			$type	= isset(self::$settings[$module]['type']) ? self::$settings[$module]['type'] : $module;
			$name	= '\\Opiner\\Module\\' . ucfirst($type);
			$config = isset(self::$settings[$module]) ? self::$settings[$module] : null;

			if(class_exists($name))
				self::$modules[$module] = new $name($config);
				else throw new Exception($type, 110);
		}

		// Postupne spustanie vsetkych ocakavanych metod
		ob_start ();
		$methods = array('startup', 'prepareCompilation', 'compile', 'afterCompilation');

		foreach($methods as $method) {
			foreach (self::$modules as $module) {
				if(method_exists($module, $method))
				$module->$method ();
			}
		}

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
	 * @param string Hodnotea
	 * @since 0.6
	 */

	public static function getPrivateLocation() {
		
		return self::$scripts;
	}



	/**
	 * Vrati adresu k zakladnemu priecinku stranky
	 * @return string
	 * @since 0.6
	 */

	public static function getWebLocation () {

		return self::$webroot;
	}



	/**
	 * Vrati cestu k pozadovanemu priecinku
	 * 
	 * Tato metoda nahradi v stringu predanom v prvom
	 * argumente hodnoty oznacene v [::] ohraniceni
	 * za staticke hodnoty tejto triedy.
	 * 
	 * @since 0.6
	 * @return string Adresa priecinka
	 * @param string $location Adresa priecinku s premennymi
	 * @param... string Premenne na nahradenie
	 */

	public static function getLocation($location) {
		
		$params = func_get_args();
		unset($params[0]);
		foreach($params as $index => $value)
			$location = str_replace('[:param' . $index . ':]', $value, $location);
		
		while(preg_match('#\[\:([a-zA-Z]+)\:\]#ius', $location, $match))
			$location = str_replace($match[0], isset(self::${$match[1]}) ? self::${$match[1]} : '', $location);
			
		return $location;
	}
}

?>
