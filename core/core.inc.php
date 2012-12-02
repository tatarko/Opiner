<?php 

// Ak je spustený priamo súbor s jadrom
if (false !== strpos ($_SERVER['PHP_SELF'], '.inc.php'))
die (header ('HTTP/1.1 403 Forbidden') . 'Unauthorized Access!');

// Prepočítanie aktuálnej polohy jadra (privátnej)
if (!defined ('_root'))
define ('_root', substr (str_replace ('\\', '/', dirname (__FILE__)), 0, strrpos (str_replace ('\\', '/', dirname (__FILE__)), '/')) . '/');

// Prepočítanie adresy servra
if (!defined ('_remote'))
define ('_remote', 'http://' . $_SERVER['HTTP_HOST'] . substr ($_SERVER['SCRIPT_NAME'], 0, strrpos ($_SERVER['SCRIPT_NAME'], '/') + 1));


class Opiner {

	// Informácie o jadre
	const	name = 'Opiner',
		version = '0.1',
		root = _root,
		rootCore = 'core/',
		rootController = 'core/controller/',
		rootClass = 'core/class/',
		rootLibrary = 'core/library/',
		rootView = 'core/view/',
		rootTemplate = 'template/',
		rootRemote = 'remote/',
		rootRemoteCss = 'remote/css/',
		rootRemoteJs = 'remote/js/',
		routerDefault = '{$app:index$.php}{?$view:string:default${=$primary${&comments=$secondary:int:5$}}}',
		routerFolders = '{$app:index$/}{$view:string:default$/{$primary$/{$secondary:int:5$/}}}',
		toDie = 1,
		toLog = 2,
		toReturn = 3;

	public static
		$template = null,
		$database = null,
		$router = null,
		$user = null,
		$headerType = 'text/html',
		$charSet = 'UTF-8',
		$remote = _remote,
		$debug = false,
		$log = array ();

	protected static
		$settings = array (),
		$starttime;

	// Načítanie konfigurácie
	public static function load ($configFile = null)
	{

		// Osetrenie debuggingu
		if (substr(_remote, 0, 17) == 'http://localhost/')
		Opiner::$debug = true;
		Opiner::$starttime = microtime (true);

		// Práca s hlavičkami
		unset($_SESSION);
		session_start();
		Header ('Content-Type: ' . Opiner::$headerType . '; charset=' . Opiner::$charSet);
		mb_internal_encoding (Opiner::$charSet);
		mb_regex_encoding (Opiner::$charSet);
		echo '';

		// Načítanie konfiguračného súboru
		if ($configFile !== null)
		{
			Opiner::isFile (Opiner::root . Opiner::rootCore . $configFile, Opiner::toDie);
			require_once (Opiner::root . Opiner::rootCore . $configFile);
		}
	}

	// Pripojenie k MySQL databáze
	public static function connect ($settings = false, $map = true)
	{
		if (Opiner::config ('mysqlServer')
		and Opiner::config ('mysqlUsername')
		and Opiner::config ('mysqlPassword') !== false
		and Opiner::config ('mysqlDatabase')
		and Opiner::config ('mysqlPrefix') !== false)
		{
			Opiner::$database = Opiner::getObject ('database');
			if (!Opiner::$database -> connect (Opiner::config ('mysqlServer'), Opiner::config ('mysqlUsername'), Opiner::config ('mysqlPassword'), Opiner::config ('mysqlDatabase')))
			Opiner::error ('Connecting to MySQL server or database has failed!');
			if (Opiner::config ('mysqlPrefix')) Opiner::$database -> setPrefix (Opiner::config ('mysql_prefix'));
			if ($settings === true) Opiner::$settings = Opiner::$database -> config ();
			if ($settings === true) Opiner::$database -> mapRelations ();
		}
		else Opiner::error('Settings does not match enough data for connecting to database!');
	}

	// Zavolanie obejktu databazy
	public static function call ()
	{
		Opiner::$database -> tablelog = array ();
		return Opiner::$database;
	}



	// Získa nejakú hodnotu nastavení
	public static function config ($key, $level = Opiner::toReturn)
	{
		if (defined ('opiner_' . $key))
		return constant ('opiner_' . $key);
		else if (isset (Opiner::$settings [$key]))
		return Opiner::$settings [$key];
		else return Opiner::error ('Config value "' . $key . '" does not exists!', $level);
	}



	// Obstaranie chybovych hlasok
	public static function error ($string, $level = Opiner::toDie)
	{
		switch ($level)
		{

			// Ulozi chybu do logu
		        case Opiner::toLog:
		        	Opiner::$log [] = $string;
		        	return true;
		        break;

		        // Vratenie prazdnej hodnoty
		        case Opiner::toReturn:
		        	return false;
		        break;

		        // Ukoncenie behu aplikacie
		        default:
		        	die('<p><strong>Error:</strong> ' . $string . '</p>');
		        break;
		}
	}

	// Overenie existencie suboru
	public static function isFile ($file, $level = Opiner::toLog)
	{
		if (file_exists ($file)) return true;
		else return Opiner::error ('File "' . $file . '" has not been found!', $level);
	}

	// Načíta triedu
	public static function getClass ($name)
	{
		if (class_exists ($name)) return true;
		Opiner::isFile (Opiner::root . Opiner::rootClass . $name . '.class.inc.php', Opiner::toDie);
		require_once (Opiner::root . Opiner::rootClass . $name . '.class.inc.php');
		if (class_exists ($name)) return true;
		else Opiner::error ('Class "' . $name . '" has not been found!');
	}

	// Načíta objekt zvolenej triedy
	public static function getObject ($name, $values = null)
	{
		Opiner::getClass ($name);
		if (is_array ($values))
		{
			foreach ($value as $index => $val)
			$value [$index] = var_export ($val, true);
			eval ('$obj = new ' . $name . ' (' . implode (', ', $value) . ');');
			return $obj;
		}
		eval ('$obj = new ' . $name . ' (' . var_export ($values, true) . ');');
		return $obj;
	}

	// Načíta kniznicu funkcii
	public static function getLibrary ($name)
	{
		if (array_search (Opiner::root . Opiner::rootLibrary . $name . '.library.inc.php', get_included_files()) !== false) return true;
		Opiner::isFile (Opiner::root . Opiner::rootLibrary . $name . '.library.inc.php', Opiner::toDie);
		require_once (Opiner::root . Opiner::rootLibrary . $name . '.library.inc.php');
		return true;
	}

	// Načítanie controllera
	public static function controller ($name)
	{
		Opiner::getClass ('controller');
		$class = 'controller_' . $name;
		if (class_exists ($class))
		{
			$obj = new $class;
			if (method_exists ($obj, 'startup'))
			$obj -> startup ();
			return $obj;
		}
		Opiner::isFile (Opiner::root . Opiner::rootController . $name . '.controller.inc.php', Opiner::toDie);
		require_once (Opiner::root . Opiner::rootController . $name . '.controller.inc.php');
		if (!class_exists ($class))
		Opiner::error ('Controller "' . $name . '" has not been found!');
		$obj = new $class;
		if (method_exists ($obj, 'startup'))
		$obj -> startup ();
		return $obj;
	}

	// Načítanie motívu
	public static function template ($name)
	{
		Opiner::$template = Opiner::getObject ('template', $name);
		return Opiner::$template;
	}

	// Spustenie routra
	public static function router ($name)
	{
		Opiner::$router = Opiner::getObject ('router', $name) -> completeUrl (true);
	}

	// Načítanie uzivatela
	public static function user ($id = null)
	{
	        if ($id === null)
	        {
			Opiner::$user = Opiner::getObject ('user');
			return Opiner::$user;
		}
		else return Opiner::getObject ('user', intval ($id));
	}

	// Samotne skompilva
	public static function compile ()
	{

		if (Opiner::$router)
		Opiner::$router -> getDirections () -> loadView ();

		if (Opiner::$template)
		Opiner::$template -> compile ();
		
		if (Opiner::$debug)
		{

			foreach (get_defined_vars() as $index => $value)
			if ($index != 'GLOBALS' and substr($index, 0, 1) != '_' and substr($index, 0, 5) != 'HTTP_')$fc[] = '$' . $index . ' = ' . var_export ($value, true) . ';';
			foreach(get_defined_constants() as $index => $value)
			if(substr($index,0,1)=='_') $vars[] = $index . ' = ' . var_export($value, true) . ';';
			$funcs = get_defined_functions();
			$pole = get_declared_classes();
			for($index = array_search('Opiner', $pole); $index < count ($pole); ++$index)
			$classes[] = $pole[$index];
			
		
echo '

<div style="display:block;position:fixed;bottom:0;right:0;width:300px;height:21px;background:#333 -webkit-gradient(linear, left top, left bottom, from(#383838), to(#222));color:#eee;text-shadow:1px 1px 0 #000;padding:0 10px;font:normal 11px Calibri;line-height:21px;text-align:center;-webkit-border-radius:4px 0 0 0;">
' . round ((microtime (true) - Opiner::$starttime) * 1000) . 'ms / ' . count(get_included_files()) . ' files / ' . count($classes) . ' classes / ' . count($funcs['user']) . ' functions / ' . count($vars) . ' constants / ' . count(Opiner::$log) . ' queries
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
' . implode ("\n", Opiner::$log) . '

-->';
		}
	}
}
?>