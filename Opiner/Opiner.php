<?php 

/**
 * Matersky package celeho frameworku
 * 
 * @author Tomas Tatarko
 * @url https://github.com/tatarko/Opiner
 * @version 0.6
 * @since 0.1
 */
namespace Opiner;

/**
 * Nazov celeho frameworku
 */
const NAME = 'Opiner';

/**
 * Verzia frameworku
 */
const VERSION = '0.6';

/**
 * Verejna url adresa projektu
 */
const URL = 'http://tatarko.github.com/Opiner/';

/**
 * Meno autora projektu
 */
const AUTHOR = 'Tomáš Tatarko';

/**
 * Url adresa na web autora
 */
const AUTHOR_URL = 'http://tatarko.sk/';

/**
 * Adresa k priecinku s konfiguracnymi subormi
 */
const LOCATION_CONFIG = '[:scripts:]config/[:param1:].php';

/**
 * Adresa k prekladovym suborom aplikacie
 */
const LOCATION_LANGUAGE = '[:scripts:]language/[:param1:].php';

/**
 * Adresa k modulom
 */
const LOCATION_MODULE = '[:scripts:]Module/[:param1:].php';

/**
 * Zakladny typ generovaneho dokumentu
 */
const LOCATION_PRIVATE_DIRECOTY = '[:webroot:]private/';

/**
 * Adresa k modelom
 */
const LOCATION_MODEL = '[:scripts:]Model/';

/**
 * V pripade chybu ju iba logovat
 */
const ERROR_LOG = 1;

/**
 * V pripade chybu ukoncit beh aplikacie
 */
const ERROR_DIE = 2;

/**
 * V pripade chybu len vratit false
 */
const ERROR_RETURN = 4;

/**
 * Zakladny typ generovaneho dokumentu
 */
const DEFAULT_CONTENT_TYPE = 'text/html';

/**
 * Zakladny typ generovaneho dokumentu
 */
const DEFAULT_CHARSET = 'UTF-8';

/**
 * Pripona php suborov s triedami(autoloading)
 */
const CLASS_FILE_EXTENSION = '.php';

/**
 * @const Opiner\ROOT Adresa k suborom frameworku
 */
define(__NAMESPACE__ . '\ROOT', dirname(__FILE__) . DIRECTORY_SEPARATOR);



// Automaticke nacitavanie tried
spl_autoload_register(function($class) {
	
	$location = explode('\\', $class);
	
	// Triedy z denenej struktury
	if(count($location) > 1) {
		
		$folder = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
		foreach($location as $loc)
			$folder .= $loc . DIRECTORY_SEPARATOR;
		$filename = dirname($folder) . DIRECTORY_SEPARATOR . end($location) . CLASS_FILE_EXTENSION;
		if(file_exists($filename))
			require_once $filename;
		elseif(class_exists('Opiner\\Opiner', false)) {
			$private = Opiner::getPrivateLocation();
			$filename = $private . substr($filename, strrpos($filename, reset($location) . DIRECTORY_SEPARATOR) + strlen(current($location)) + 1);
			if(file_exists($filename))
				require_once $filename;
		}
	}
	
	// Zakladne triedy + Modely
	else {
		$location	= class_exists('Opiner\\Opiner', false)
					? Opiner::getLocation(LOCATION_MODEL, $class)
					: dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
		$filename = $location . $class . CLASS_FILE_EXTENSION;
		if(file_exists($filename))
			require_once $filename;
	}
});


/**
 * Korenova trieda celeho frameworku Opiner
 * 
 * Cely tento framework je pripraveny tak, aby na vygenerovanie celej
 * stacilo zavolanie jedinej statickej metody v povodnom(index.php)
 * subore stranky. Tato metoda sluzi dalej ako rozcestnik dalsich
 * procesov pre vygenerovanie stranky. Typicky priklad zakladneho,
 * korenoveho suboru suboru index.php.
 *
 * <pre>
 * <?php
 * 
 * // Načítanie triedy jadra
 * include('Opiner/Opiner.php');
 * 
 * // Skompilovanie vystupu
 * Opiner\Opiner::compile('default');
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

class Opiner extends Object {
	
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
	 * @param string $configFile Adresa konfiguracneho suboru
	 * @param bool $return Vratit vysledok ako text(true) alebo ho odoslat do vypisat(false)
	 * @param string $webroot Adresa webovej stranky
	 * @return string|bool
	 */

	public static function compile($configFile = null, $return = false, $webroot = null) {
		
		if(!self::$prepared)
			self::loadStaticValues();

		try {

			// Nasadenie zakladnych premennych objektu
			if($webroot === null)
				$webroot = dirname(debug_backtrace()[0]['file']) . '/';
			self::$webroot	= $webroot;
			self::$scripts	= self::getLocation(LOCATION_PRIVATE_DIRECOTY);
			self::$remote	=(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 'https://' : 'http://')
							. $_SERVER['HTTP_HOST'] . substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], DIRECTORY_SEPARATOR) + 1);

			// Práca s hlavičkami, kodovanim
			if(!$return)
				Header('Content-Type: ' . self::$headerType . '; charset=' . self::$charSet);
			mb_internal_encoding(self::$charSet);
			mb_regex_encoding(self::$charSet);
	
			// Načítanie konfiguracie zo súboru
			if($configFile !== null) {
				$file = self::getLocation(LOCATION_CONFIG, $configFile);
				if(!self::isFile($file))
					throw new Exception($configFile, 102); 
				self::$settings = array_merge(self::$settings, require($file));
			}
			
			$content		= self::loadModules();
			self::$prepared	= false;
			
			if($return === true)
				return $content;
			
			echo $content;
			echo self::$debugger;
			return true;

		} catch(Exception $exception) {
			
			die($exception);
		}
	}



	/**
	 * Nacita moduly
	 *
	 * Backend metody compile(). Nacita vsetky pozadove
	 * ktore boli uvedene v konfiguracii.
	 * @since 0.6
	 * @return string Buffer vygenerovaneho vystupu
	 */

	protected static function loadModules() {

		// Predpriprava externych modulov
		foreach(self::$settings['modules'] as $index => $type) {
			
			$indexes[] = $index;
			self::$settings[$index]['type'] = $type;
		}
		
		// Pridanie indexov
		if(isset($indexes))
			self::$moduleIndexes = array_merge($indexes, self::$moduleIndexes);
		
		// Nacitavanie modulov na poziadanie
		foreach(self::$moduleIndexes as $module) {
			
			$type	= isset(self::$settings[$module]['type']) ? self::$settings[$module]['type'] : $module;
			$name	= '\\' . __NAMESPACE__ . '\\Module\\' . ucfirst($type);
			$config = isset(self::$settings[$module]) ? self::$settings[$module] : null;

			if($config && class_exists($name))
				self::$modules[$module] = new $name($config);
		}

		// Postupne spustanie vsetkych ocakavanych metod
		ob_start();
		$methods = array('startup', 'prepareCompilation', 'compile', 'afterCompilation');

		foreach($methods as $method) {
			foreach(self::$modules as $module) {
				if(method_exists($module, $method))
					$module->$method();
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
	 * @return string|null|bool
	 */

	public static function config($key, $value = null, $updateDb = true) {
		
		if($value === null)
			return isset(self::$settings[$key]) ? self::$settings[$key] : null;

		self::$settings[$key] = $value;
		return $updateDb ? self::module('database')->updateConfigValue($key, $value) : true;
	}



	/**
	 * Callback vracajuci pozadovany modul na zaklade predaneho nazvu
	 *
	 * @param string Unikatny nazov pozadovaneho modulu
	 * @return Opiner\Module
	 */

	public static function module($localname) {
		
		return isset(self::$modules[$localname]) ? self::$modules[$localname] : null;
	}



	/**
	 * Vrati adresu stranky(verejne dostupnu
	 * @return string
	 * @since 0.6
	 */

	public static function getRemoteLocation() {
		
		return self::$remote;
	}



	/**
	 * Vrati adresu k sukromnym/tajnym suborom stranky
	 * @param string
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

	public static function getWebLocation() {

		return self::$webroot;
	}



	/**
	 * Vrati cestu k pozadovanemu priecinku
	 * 
	 * Tato metoda nahradi v stringu predanom v prvom
	 * argumente hodnoty oznacene v[::] ohraniceni
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