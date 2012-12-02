<?php

// Kontrola existencie jadra
if (!defined ('_root') or false !== strpos ($_SERVER['PHP_SELF'], '.inc.php'))
die (header ('HTTP/1.1 403 Forbidden') . 'Unauthorized Access!');



class router
{

	protected
		$route, // Špecifický tvar routovania parametrov
		$complete_url = false, // Generovať adresy vrátanie doménového smerovania
		$app, // Spustená aplikácia (ktorý súbor volal jadro)
		$view = 'default', // Ktorý view má byť spustený
		$indexes = array (), // Aké indexy má router hľadať
		$active_table = array (), // Ktoré adresy majú byť vyhodnotené ako aktívne
		$route_table = array (); // Samotná routovacia tabuľka



	/*
	 *	Konštruktor objektu, generovanie routovacej tabuľky,
	 *	hľadanie paremetrických indexov, načítanie aktívne
	 *	načítanej adresy stránky
	 *	@param string route Štruktúta, podľa ktorej má byť web routovaný
	 *	@param boolean directions Májú byť získané aj aktuálne hodnoty routovania?
	 *	@return object self
	 */

	public function __construct ($route, $directions = true)
	{
		$this -> route = $route;
		$pos = 0;
		while (preg_match ('#\{(.*?)\$([a-z0-9]+)\:?([a-z]*?)\:?([a-z0-9-_]*?)\$(.*?)\}#m', $this -> route, $match, PREG_OFFSET_CAPTURE, $pos))
		{
			$this -> route_table [$match [2] [0]] = array (
				'wrap-begin' => $match [1] [0],
				'wrap-end'   => strpos ($match [5] [0], '{') !== false ? substr ($match [5] [0], 0, strpos ($match [5] [0], '{')) : $match [5] [0],
				'controller' => $match [3] [0] == '' ? 'controller_encode' : 'controller_' . $match [3] [0],
				'default'    => $match [4] [0] == '' ? 'index' : $match [4] [0],
				'has-child'  => strpos ($match [5] [0], '{') !== false ? true : false,
			);
			$this -> $match [2] [0] = $match [4] [0];
			if ($match [2] [0] != 'app' and $match [2] [0] != 'view')
			$this -> indexes [] = $match [2] [0];
                        $pos = $match[0][1] + 1;
		}
		$this -> app = substr ($_SERVER['SCRIPT_FILENAME'], strrpos ($_SERVER['SCRIPT_FILENAME'], '/') + 1);
		$this -> app = substr ($this -> app, 0, strrpos ($this -> app, '.'));
		if ($directions === true) $this -> getDirections ();
		return $this;
	}



	/**
	 *	Načíta aktuálne hodnoty routovania
	 *	@return object self
	 */

	public function getDirections ()
	{
		$route = substr($_SERVER['REQUEST_URI'], strlen(substr ($_SERVER['SCRIPT_NAME'], 0, strrpos ($_SERVER['SCRIPT_NAME'], '/') + 1)));
		foreach ($this -> route_table as $index => $table)
		{
			$pattern = '#' . $this -> escape ($table ['wrap-begin']) . '(?P<' . $index . '>[a-zA-Z0-9-_]+)' . $this -> escape ($table['wrap-end']) . '#';
			preg_match ($pattern, $route, $match, PREG_OFFSET_CAPTURE);
			foreach ($match as $index => $value) {
				if (is_string ($index) and $value[0] != '' and $value[0] != $this -> route_table [$index] ['default'])
				{
					$this -> $index = $value[0];
					$route = substr ($route, 0, $match [0] [1]) . substr ($route, $match [0] [1] + strlen ($match [0] [0]));
				}
			};
		}
		foreach ($this -> route_table as $index => $value)
		if ($index != 'app') $params[] = $this -> $index;
		$this -> active_table [] = $this -> route ($params);
		return $this;
	}



	/**
	 *	Prepočítanie adresy odkazu na základe predaných parametrov
	 *	@return string
	 */

	public function route ()
	{
		$route = $this -> complete_url ? array ('url' => Opiner::$remote) : array ('url' => '');
		if (isset ($this -> route_table ['app']) and $this -> route_table ['app'] ['default'] != $this -> app)
		$route ['app'] = $this -> route_table ['app'] ['wrap-begin'] . $this -> app . $this -> route_table ['app'] ['wrap-end'];
		$int = 0;
		$args = func_get_args();
		if (count ($args) == 1 and is_array ($args [0])) $args = $args [0];
		foreach ($args as $index => $value)
		{
		        $value = is_array ($value) ? $this -> webalize ($value) : $value;
			$index = $index == 0 ? 'view' : $this -> indexes [$int++];
			if (isset ($this -> route_table [$index])) {
			        $table = $this -> route_table [$index];
			        $controller = method_exists ($this, $table ['controller']) ? $table ['controller'] : 'controller_encode';
			        $compare = is_array ($value) ? current ($value) : $value;
			        if ($table ['default'] != $compare and $compare != '')
			        {
					if (isset ($last)) $route [] = $last;
					$route [$index] = $table ['wrap-begin'] . $this -> $controller ($value) . $table ['wrap-end'];
				}
				elseif ($table ['has-child']) $last = $table ['wrap-begin'] . $this -> $controller ($value) . $table ['wrap-end'];
				else unset ($last);
			} else break;
		}
		return implode ('', $route);
	}



	/**
	 *	Je predaný odkaz aktívne routovaným?
	 *	@return boolean
	 */

	public function isActive ()
	{
		$link = $this -> route (func_get_args());
		if (array_search ($link, $this -> active_table) !== false)
		return true;
		else return false;
	}



	/**
	 *	Nastavenie, či majú byť odkazy generované vrátane domény
	 *	@param boolean complete Generovať skutočný url?
	 *	@return object self
	 */

	public function completeUrl ($complete = false)
	{
		$this -> complete_url = $complete === true ? true : false;
		$this -> active_table = array ();
		$this -> getDirections ();
		return $this;
	}



	/**
	 *	Načítanie aktuálneho view modelu
	 *	@return object self
	 */

	public function loadView ()
	{
		// Načítanie súborovej štruktúry pre View
	        Opiner::isFile(Opiner::root . Opiner::rootView . $this -> view . '.view.inc.php', Opiner::toDie);
	        Opiner::getClass ('view');
	        require_once (Opiner::root . Opiner::rootView . $this -> view . '.view.inc.php');
	        if (!class_exists ('view_' . $this -> view))
	        Opiner::error('Router has not found "' .  $this -> view . '" view model!');

		// Načítanie triedy daného viewu
		$class = 'view_' . $this -> view;
		$this -> viewObject = new $class ();
		foreach (array ('router', 'template') as $object)
		if (isset (Opiner::$$object) and Opiner::$$object) $this -> viewObject -> $object = Opiner::$$object;

		// Postupné načítania potrebných metód
		foreach (array ('startup', 'startup_' . $this -> app, 'check', 'check_' . $this -> app, 'prepare', 'prepare_' . $this -> app, 'render', 'render_' . $this -> app) as $method)
		if (method_exists ($this -> viewObject, $method))
		{
			if (false === $this -> viewObject -> $method () and Opiner::$template)
			{
				Opiner::$template -> value ('title', 'Error');
				Opiner::$template -> value ('content', '<p class="error">View "' . $this -> app . '" shouted error in "' . $method . '" method!</p>');
				return $this;
			}
		}
		return $this;
	}



	/**
	 *	Ošetrenie vstupných parametrov pre router
	 *	@param string value Premenná na ošetrenie
	 *	@return string
	 */

	protected function controller_encode ($value)
	{
		return urlencode($value);
	}



	/**
	 *      Ošetrenie premennej pre vstup do regexpov
	 *	@param string value Premenná na ošetrenie
	 *	@return string
	 */

	protected function escape ($value)
	{
		return str_replace (array ('?', '&'), array ('\\?', '\\&'), $value);
	}



	protected function webalize ($value)
	{
		if (is_array ($value))
		{
			foreach ($value as $index => $key)
			$value[$index] = $this -> webalize ($key);
			return implode ('-', $value);
		}
		else
		{
			$value = iconv ('UTF-8', 'WINDOWS-1250//IGNORE', $value);
			$value = strtr ($value, "\xa5\xa3\xbc\x8c\xa7\x8a\xaa\x8d\x8f\x8e\xaf\xb9\xb3\xbe\x9c\x9a\xba\x9d\x9f\x9e\xbf"
			. "\xc0\xc1\xc2\xc3\xc4\xc5\xc6\xc7\xc8\xc9\xca\xcb\xcc\xcd\xce\xcf\xd0\xd1\xd2\xd3\xd4\xd5\xd6\xd7"
			. "\xd8\xd9\xda\xdb\xdc\xdd\xde\xdf\xe0\xe1\xe2\xe3\xe4\xe5\xe6\xe7\xe8\xe9\xea\xeb\xec\xed\xee\xef"
			. "\xf0\xf1\xf2\xf3\xf4\xf5\xf6\xf8\xf9\xfa\xfb\xfc\xfd\xfe", 'ALLSSSSTZZZallssstzzzRAAAALCCCEEEEII'
			. 'DDNNOOOOxRUUUUYTsraaaalccceeeeiiddnnooooruuuuyt');
			$value = strtolower ($value);
			$value = preg_replace ('#[^a-z0-9]+#', '-', $value);
			$value = trim ($value, '-');
			return $value;
		}
	}



	public function goHome ()
	{
		Header ('Location: ./');
	}
}
?>