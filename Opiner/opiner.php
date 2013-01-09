<?php 

// Definovanie mennej triedy
namespace Opiner;

// Zakladne konstanty
const
	name = 'Opiner',
	version = '0.2.2',
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
try {
	require_once (root . 'trait/behavior.php');
} catch (Exception $e) {
	die ($e);
}


// Samotna trieda aplikacie
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



	/* Vytvorenie novej instancie Aplikacie
	 * @param string $webRoot: Adresa suboru, cez ktory bol zavolany Opiner (__FILE__)
	 * @param string $configFile: Nazov konfiguracneho suboru z /private/config, ktory sa ma nacitat */

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
			//self::getFile (root . 'class/model.php'); TODO
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
/*
			$dir = opendir (scripts . 'models');
			while ($file = readdir ($dir))
			if (substr ($file, -4) == '.php')
			self::getFile (scripts . 'models/' . $file);
*/
			
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



	/* Citanie/Zapis konfiguracie aplikacie
	 * @param string $key: Nazov reprezentujuci konfiguracny hodnotu
	 * @param string $value: Ak je zadane, nahodi sa nova hodnota konfiguracie
	 * @param boolean $updateDb: Ma sa vykonat aj ulozenie konfiguracnej hodnoty do databazy
	 * @return string/boolean */

	public static function config ($key, $value = null, $updateDb = true)
	{
		if ($value === null)
		return isset (self::$settings [$key]) ? self::$settings [$key] : null;

		self::$settings [$key] = $value;
		return $updateDb ? self::module ('database') -> updateConfigValue ($key, $value) : true;
	}



	/* Vrati object reprezentujuci pozadovany modul
	 * @param string $localname: Ktory modul ma byt vrateny */

	public static function module ($localname)
	{
		return isset (self::$modules [$localname]) ? self::$modules [$localname] : null;
	}



	/* Vrati object reprezentujuci pozadovany modul
	 * @param string $localname: Ktory modul ma byt vrateny */

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