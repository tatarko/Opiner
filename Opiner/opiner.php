<?php 

// Definovanie mennej triedy
namespace Opiner;

// Zakladne konstanty
const	name = 'Opiner',
	version = '0.2.1',
	url = 'http://tatarko.github.com/Opiner/',
	author = 'Tomáš Tatarko',
	authorUrl = 'http://tatarko.sk/',
	toDie = 1,
	toLog = 2,
	toReturn = 4;

// Definovanie cesty k frameworku
define ('Opiner\root', substr (str_replace ('\\', '/', __FILE__), 0, strrpos (str_replace ('\\', '/', __FILE__), '/')) . '/');



// Nacitanie zakladneho traitu
require_once (root . 'library/behaviour.trait.php');



// Samotna trieda aplikacie
class Application {

	use Behaviour;

	public static
		$headerType = 'text/html',	// Aky mimetype ma byt poslany do hlaviciek
		$charSet = 'UTF-8',		// Ake kodovanie pouzivame
		$remote,			// Adresa web-servera
		$webRoot,			// Fyzicka adresa k suborom instancie aplkacie
		$log = ['errors' => [], 'requiredFiles' => [], 'database' => []];
						// Co vsetko sme zalogovali

	protected static
		$settings = [],			// Pole nastaveni aplikacie
		$modules = [],			// Nacitane moduly
		$starttime = 0,			// Kedy sa aplikacia zacala generovat
		$debug = false,			// Maju byt vykreslene debug informacie?
		$systemModules = ['database', 'template', 'router', 'language'];
						// Ake moduly automaticky nacitavat?



	/* Vytvorenie novej instancie Aplikacie
	 * @param string $webRoot: Adresa suboru, cez ktory bol zavolany Opiner (__FILE__)
	 * @param string $configFile: Nazov konfiguracneho suboru z /private/config, ktory sa ma nacitat */

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
			self::isFile (self::$webRoot . 'private/config/' . $configFile . '.php', toDie);
			self::$settings = require (self::$webRoot . 'private/config/' . $configFile . '.php');
		}
		self::loadModules ();
	}



	/* Nacita predvolene moduly, pokial je v konfigu zmienka o nich */

	protected static function loadModules ()
	{
		// Nacitanie vzoroveho modulu
		self::isFile (root . 'library/module.class.php', toDie);
		self::requireOnce (root . 'library/module.class.php');

		// Nacitavanie postupne kazdeho jedneho modulu
		foreach (self::$systemModules as $module)
		if (isset (self::$settings [$module]))
		{
			$name = '\\Opiner\\Module\\' . $module;
			$config = is_array (self::$settings [$module]) ? self::$settings [$module] : [self::$settings [$module]];

			self::isFile (root . 'modules/' . $module . '.php', toDie);
			self::requireOnce (root . 'modules/' . $module . '.php');
			self::$modules [$module] = (new $name ($config)) -> startup ();
		}

		// Nacitavanie modulov na poziadanie
		if (isset (self::$settings ['modules']))
		foreach (self::$settings ['modules'] as $module)
		if (is_array ($module) and count ($module) == 3)
		{
			$name = '\\Opiner\\Module\\' . $module [1];
			$config = is_array ($module [2]) ? $module [2] : [$module [2]];

			// Z internych modulov
			if (self::isFile (root . 'modules/' . $module [1] . '.php', toReturn))
			{
				self::requireOnce (root . 'modules/' . $module [1] . '.php');
				self::$modules [$module [0]] = (new $name ($config)) -> startup ();
			}
			
			// Externe moduly
			elseif (self::isFile (self::getWebRoot . 'private/modules/' . $module [1] . '.php', toReturn))
			{
				self::requireOnce (root . 'private/modules/' . $module [1] . '.php');
				self::$modules [$module [0]] = (new $name ($config)) -> startup ();
			};
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



	/* Samotne kompilovanie stranky
	 * @return self */

	public static function compile ()
	{
		$methods = ['prepareCompilation', 'compile', 'afterCompilation'];
		foreach ($methods as $method)
		foreach (self::$modules as $module)
		if (method_exists ($module, $method))
		$module -> $method ();
		if (self::$debug) self::debug ();
	}



	/* Vystup debuggera
	 * @return self */

	protected static function debug ()
	{
		foreach (get_defined_constants () as $index => $value)
		if (substr ($index, 0, 6) == 'Opiner') $vars[] = $index . ' = ' . var_export($value, true) . ';';
		$funcs = get_defined_functions ();
		foreach (get_declared_classes() as $trieda)
		if (substr ($trieda, 0, 6) == 'Opiner') $classes[] = $trieda;
			
		
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

Log:
' . var_export (self::$log, true) . '

-->';
	}
}
?>