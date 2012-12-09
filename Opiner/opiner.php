<?php 

// Definovanie mennej triedy
namespace opiner;

// Zakladne konstanty
const	name = 'Opiner',
	version = '0.1',
	author = 'Tomáš Tatarko',
	toDie = 1,
	toLog = 2,
	toReturn = 4;
define ('opiner\root', substr (str_replace ('\\', '/', __FILE__), 0, strrpos (str_replace ('\\', '/', __FILE__), '/')) . '/');


// Samotna trieda aplikacie
class application {

	public static
		$headerType = 'text/html',	// Aky mimetype ma byt poslany do hlaviciek
		$charSet = 'UTF-8',		// Ake kodovanie pouzivame
		$debug = false,			// Maju byt vykreslene debug informacie?
		$log = [],			// Co vsetko sme zalogovali
		$remote;			// Adresa web-servera

	protected static
		$settings = [],			// Pole nastaveni aplikacie
		$modules = [],			// Nacitane moduly
		$starttime = 0,			// Kedy sa aplikacia zacala generovat
		$webRoot,			// Fyzicka adresa k suborom instancie aplkacie
		$systemModules = ['database', 'template', 'router', 'language'];
						// Ake moduly automaticky nacitavat?



	/* Vytvorenie novej instancie Aplikacie
	 * @param string $webRoot: Adresa suboru, cez ktory bol zavolany Opiner (__FILE__)
	 * @param string $configFile: Nazov konfiguracneho suboru z /private/config, ktory sa ma nacitat
	 * @return object Opiner\Application */

	public static function load ($webRoot, $configFile = null)
	{

		// Nasadenie zakladnych premennych objektu
		self::$webRoot = substr (str_replace ('\\', '/', $webRoot), 0, strrpos (str_replace ('\\', '/', $webRoot), '/')) . '/';
		self::$remote = 'http://' . $_SERVER['HTTP_HOST'] . substr ($_SERVER['SCRIPT_NAME'], 0, strrpos ($_SERVER['SCRIPT_NAME'], '/') + 1);

		// Osetrenie debuggingu
		if (substr (self::$remote, 0, 17) == 'http://localhost/')
		{
			self::$debug = true;
			self::$starttime = microtime (true);
		}

		// Práca s hlavičkami, kodovanim
		Header ('Content-Type: ' . self::$headerType . '; charset=' . self::$charSet);
		mb_internal_encoding (self::$charSet);
		mb_regex_encoding (self::$charSet);

		// Načítanie konfiguracie zo súboru
		if ($configFile !== null)
		{
			self::isFile (self::$webRoot . 'private/config/' .$configFile . '.php', toDie);
			self::$settings = require (self::$webRoot . 'private/config/' .$configFile . '.php');
		}
		self::loadModules ();
	}



	/* Nacita predvolene moduly, pokial je v konfigu zmienka o nich
	 * @return self */

	protected static function loadModules ()
	{
		// Nacitanie vzoroveho modulu
		self::isFile (root . 'library/module.php', toDie);
		require_once (root . 'library/module.php');

		// Nacitavanie postupne kazdeho jedneho modulu
		foreach (self::$systemModules as $module)
		if (isset (self::$settings [$module]))
		{
			$name = '\\opiner\\module\\' . $module;
			self::isFile (root . 'modules/' . $module . '.php', toDie);
			require (root . 'modules/' . $module . '.php');
			self::$modules [$module] = (new $name (self::$settings [$module])) -> startup ();
		}
	}



	/* Vytvorenie novej instancie Aplikacie
	 * @param string $webRoot: Adresa suboru, cez ktory bol zavolany Opiner (__FILE__)
	 * @param string $configFile: Nazov konfiguracneho suboru z /private/config, ktory sa ma nacitat
	 * @return object Opiner\Application */

	public static function config ($key, $level = toReturn)
	{
		if (defined ('opiner_' . $key))
		return constant ('opiner_' . $key);
		else if (isset (self::$settings [$key]))
		return self::$settings [$key];
		else return self::error ('Config value "' . $key . '" does not exists!', $level);
	}



	/* Osetrenie chybovych hlasok systemu
	 * @param string $string: Text hlasky, ktora sa ma vypisat
	 * @param int $level: Ako sa ma chybova hlaska spracovat?
	 * @return boolean */

	public static function error ($string, $level = toDie)
	{
		switch ($level)
		{
			case toLog: self::$log ['errors'] [] = $string; return true; break;
			case toReturn: return false; break;
			default: die('<p><strong>Error:</strong> ' . $string . '</p>'); break;
		}
	}



	/* Overenie existencie suboru
	 * @param string $file: Adresa suboru, ktoreho existencia ma byt overena
	 * @param string $level: Ako sa ma spracovat vysledok
	 * @return boolean */

	public static function isFile ($file, $level = toReturn)
	{
		if (file_exists ($file)) return true;
		else return self::error ('File "' . $file . '" has not been found!', $level);
	}



	/* Vkladanie suborov s osetrenym opakovanim
	 * @param string $file: Adresa suboru, ktory ma byt nacitany */

	public static function requireOnce ($file)
	{
		self::isFile ($file, toDie);
		if (array_search ($file, self::$log ['reuqired.files']) !== false) return true;
		self::$log ['required.files'] = $file;
		return require_once ($file);
	}



	/* Vrati adresu sukromnych suborov frameworku
	 * @return string */

	public static function getWebRoot ()
	{
		return self::$webRoot;
	}



	/* Samotne kompilovanie stranky
	 * @return self */

	public static function compile ()
	{
		$methods = ['prepareCompilation', 'compile', 'afterCompilation'];
		foreach ($methods as $method)
		foreach (self::$modules as $name => $module)
		if (method_exists ($module, $method))
		$module -> $method ();
		#if (self::$debug) self::debug ();
	}



	/* Vystup debuggera
	 * @return self */

	protected static function debug ()
	{
		foreach (get_defined_vars() as $index => $value)
		if ($index != 'GLOBALS' and substr($index, 0, 1) != '_' and substr($index, 0, 5) != 'HTTP_')$fc[] = '$' . $index . ' = ' . var_export ($value, true) . ';';
		foreach(get_defined_constants() as $index => $value)
		if(substr($index,0,1)=='_') $vars[] = $index . ' = ' . var_export($value, true) . ';';
		$funcs = get_defined_functions();
/*
		$pole = get_declared_classes();
		for($index = array_search('Opiner', $pole); $index < count ($pole); ++$index)
		$classes[] = $pole[$index];
*/
			
		
echo '

<div style="display:block;position:fixed;bottom:0;right:0;width:300px;height:21px;background:#333 -webkit-gradient(linear, left top, left bottom, from(#383838), to(#222));color:#eee;text-shadow:1px 1px 0 #000;padding:0 10px;font:normal 11px Calibri;line-height:21px;text-align:center;-webkit-border-radius:4px 0 0 0;">
' . round ((microtime (true) - self::$starttime) * 1000) . 'ms / ' . count(get_included_files()) . ' files / ' . count($classes) . ' classes / ' . count($funcs['user']) . ' functions / ' . count($vars) . ' constants / ' . count(self::$log) . ' queries
</div>

<!--

Files:
' . implode ("\n", get_included_files()) . '

Classes:
' . implode ("\n", $classes) . '

Functions:
' . implode ("\n", $funcs['user']) . '

Constants:
' . implode ("\n", $vars) . '

Queries:
' . implode ("\n", self::$log) . '

-->';
	}
}
?>