<?php

// Kontrola jadra
if (!defined ('_root') or false !== strpos ($_SERVER['PHP_SELF'], '.inc.php'))
die (header ('HTTP/1.1 403 Forbidden') . 'Unauthorized Access!');



// Trieda template
class template
{

	// Základné premmenné motívu
	protected
		$name,
		$fname,
		$root,
		$view = 'default',
		$values = array (),
		$template = '',
		$meta = array (),
		$links = array (),
		$css = array (),
		$scripts = array (),
		$tohead = array (),
		$title = array ();
	public $remote;



	/**
	 *	Vytvorenie objektu, určenie základných premenných
	 *	@param string name Fyzický názov súboru
	 *	@return object self
	 */

	public function __construct ($name)
	{
		$this -> fname = $name;
		$this -> root = Opiner::root . Opiner::rootTemplate . $name . '/';
		$this -> remote = Opiner::rootTemplate . $name . '/';
		if (!Opiner::isFile ($this -> root . 'config.inc.php'))
		Opiner::error('Template "' . $this -> fname . '" does not contain its configuration!');
		if (!Opiner::isFile ($this -> root . $this -> view . '.tpl'))
		Opiner::error('Template "' . $this -> fname . '" does not contain "' . $this -> view . '" view model!');
		require_once ($this -> root . 'config.inc.php');
		$this	-> meta ('generator', Opiner::name . ' ' . Opiner::version)
			-> meta ('robots', 'index, follow')
			-> value ('basehref', Opiner::$remote)
			-> value ('template', $this -> remote)
			-> value ('template_name', $this -> name)
			-> value ('remote', Opiner::$remote . $this -> remote);
		return $this;
	}



	/**
	 *	Samotné kompilovanie motívu
	 *	@param boolean return Spôsob, akým sa má vrátiť výsledok
	 *	@return object self Ak return === false, motív sa vypíše pomocou echo
	 *	@return string Ak return !== false, vráti priamo skompilovaný motív
	 */

	public function compile ($return = false)
	{
		$this -> template = file_get_contents ($this -> root . $this -> view . '.tpl');
		$this -> compileheaders ();
		$this -> template = $this -> parseCycles ($this -> template, $this -> values);
		$this -> template = $this -> parseValues ($this -> template, $this -> values, true);
		$this -> template = preg_replace ('#([\n\r]*[ \t]*?)+[\n\r]+#', "\n", $this -> template);
		if ($return !== true)
		{
			echo $this -> template;
			return $this;
		}
		else $this -> template;
	}



	/**
	 *	Kompilovanie html hlavičky do premennej TEMPLATE_HEADER
	 *	@return object self
	 */

	protected function compileheaders ()
	{
		$lines = array (
			'<base href="' . $this -> value ('basehref') . '" />',
			'<meta http-equiv="content-type" content="' . Opiner::$headerType . ';charset=utf-8" />'
		);
		foreach ($this -> meta as $index => $value)
		$lines[] = '<meta name="' . $index . '" content="' . htmlspecialchars ($value, ENT_COMPAT) . '" />';
		foreach (array_unique (array_filter ($this -> css)) as $value)
		$lines[] = '<link rel="stylesheet" href="' . $value . '" type="text/css" />';
		foreach ($this -> links as $type => $array)
		{
		        $link = '<link rel="' . $type . '"';
			foreach ($array as $key => $value) $link .= ' ' . $key . '="' . $value . '"';
			$lines [] = $link . ' />';
		}
		foreach (array_unique (array_filter ($this -> scripts)) as $value)
		$lines[] = '<script src="' . $value . '" type="text/javascript" charset="utf-8"></script>';
		$lines[] = '<title>' . implode (' &raquo; ', $this -> title) . '</title>';
		$lines = array_merge ($lines, $this -> tohead);
		$lines = array_unique (array_filter ($lines));
		return $this -> value ('headers', ' ' . implode ("\n ", $lines));
	}



	/**
	 *	Parsovanie cyklov v rámci predaného stringu
	 *	@return object self
	 */

	protected function parseCycles ($string, $values)
	{
		while (preg_match ('#<!-- (begin|if) ([a-z_]+)([a-zA-Z0-9_\# ]*?) -->(.+?)<!-- end \\2 -->#ism', $string, $match, PREG_OFFSET_CAPTURE))
		{
		        $index = 0;
		        $mode = $match[1][0];
		        $value = $match[2][0];
		        $content = $match[4][0];
		        $start = $match[0][1];
		        $end = $start + strlen ($match[0][0]);
			switch ($mode)
			{



				// Cykly
			        case 'begin':
			                $boxes = array ();
			                if (isset ($values [$value]) and is_array ($values [$value]) and !empty ($values [$value]))
			                {
			                        $count = count ($values [$value]);
						foreach ($values [$value] as $array)
						{
							$array = array_merge ($array, array (
								'this_position' => ++$index,
								'this_first' => $index == 1 ? true : false,
								'this_last' => $index == $count ? true : false,
								'this_count' => $count,
								'this_even' => ($index % 2 == 0) ? true : false,
								'this_odd' => ($index % 2 == 1) ? true : false,
								'this_pair_class' => ($index % 2 == 0) ? 'even' : 'odd',
							));
							$box = $this -> parseCycles ($content, $array);
							$box = $this -> parseValues ($box, $array);
							$boxes[] = $box;
						}
						$string = substr ($string, 0, $start) . implode ('', $boxes) . substr ($string, $end);
					}
					else $string = substr ($string, 0, $start) . substr ($string, $end);
				break;



				// Podmienky
				case 'if':
				        $start = $match[0][1];
				        $end = $start + strlen ($match[0][0]);
					$array = array_merge (array ('elseif', $match[2][0] . $match[3][0]), preg_split ('#<!-- (elseif|else) ' . $value . ' ([a-zA-Z0-9_\# ]*?)-->#ism', $content, -1, PREG_SPLIT_DELIM_CAPTURE));
					for ($i = 1; $i < count ($array); $i += 3)
					{
						if ($array[($i-1)] == 'elseif' and $this -> parseCond ($array[$i], $values))
						{
							$string = substr ($string, 0, $start) . $array[($i+1)] . substr ($string, $end);
							break 2;
						}
						else if ($array[($i-1)] == 'else')
						{
							$string = substr ($string, 0, $start) . $array[($i+1)] . substr ($string, $end);
							break 2;
						}
					}
					$string = substr ($string, 0, $start) . substr ($string, $end);
				break;

				default: $string = substr ($string, 0, $start) . substr ($string, $end); break;
			};
		}
		return $string;
	}



	/**
	 *	Parsovanie cyklov v rámci predaného stringu
	 *	@return object self
	 */

	protected function parseCond ($string, $values)
	{
		$words = explode (' ', trim ($string, ' '));
		if (!isset ($values [$words[0]]) or empty ($values [$words[0]])) return false;
		if (count ($words) == 1) return true;
		if (count ($words) == 3)
		{
		        if (substr ($words[2], 0, 1) == '#' and !isset ($values [substr ($words[2], 1)])) return false;
		        $compare = substr ($words[2], 0, 1) == '#' ? $values [substr ($words[2], 1)] : $words[2];
			if ($words[1] == 'equals' and $values [$words[0]] == $compare) return true;
			else if ($words[1] == 'over' and $values [$words[0]] > $compare) return true;
			else if ($words[1] == 'under' and $values [$words[0]] < $compare) return true;
			else return false;
		}
		return false;
	}



	/**
	 *	Parsovanie premenných
	 *	@return object self
	 */

	protected function parseValues ($string, $values, $clear = false)
	{
		$pos = 0;
		while (preg_match ('#{([a-z_/]+)(\:([a-z_]+))?(\:(.[^{}]+))?}#ism', $string, $match, PREG_OFFSET_CAPTURE, $pos))
		{
		        $start = $match[0][1];
		        $end = $start + strlen ($match[0][0]);
		        $pos = $start + 1;
		        if ($match[1][0] == 'include')
		        {
				$replace = Opiner::isFile ($this -> root  . $match[3][0] . '.html') ? file_get_contents ($this -> root  . $match[3][0] . '.html') : '';
				if ($replace) $replace = $this -> parseCycles ($replace, $values);
				$string = str_replace ($match[0][0], $replace, $string);
			}
		        elseif (isset ($values [current (explode ('/', $match[1][0]))]))
		        {
		        	$value = $values;
				foreach (explode ('/', $match[1][0]) as $yeah)
				$value = $value [$yeah];
				if (count ($match) == 2)
				$string = str_replace ($match[0][0], htmlspecialchars($value) , $string);
				else if ($match [3] [0] == 'html')
				$string = str_replace ($match[0][0], $value, $string);
				else if (method_exists ($this, 'parser_' . $match[3][0]))
				{
				        $method = 'parser_' . $match[3][0];
					$string = str_replace ($match[0][0], $this -> $method ($value, (isset ($match[5][0]) ? $match[5][0] : null)), $string);
				}
				else $string = str_replace ($match[0][0], '', $string);
			}
			elseif ($clear)
			$string = str_replace ($match[0][0], '', $string);
		}
		return $string;
	}



	/**
	 *	Nastavenie premennej motívu
	 *	@param string key Index (názov) premennej
	 *	@param mixed value Hodnota premennej
	 *	@return object self
	 */

	public function value ($key, $value = null)
	{
	        if ($value === null and isset ($this -> values [$key]) and !is_array ($this -> values [$key])) return $this -> values [$key];
	        else if ($value === null) return '';
	        else if (is_array ($value)) $this -> values [$key] [] = $value;
		else $this -> values [$key] = $value;
		return $this;
	}



	/**
	 *      Načíta iný ako default View model
	 *      Ak model neexistuje, vyhodí sa kritická chyba
	 *      @param string view Aký view model požadujeme na načítanie
	 *      @return object self Ak sa podarí načítať view
	 */

	public function setView ($view)
	{
		if (!Opiner::isFile ($this -> root . $view . '.tpl'))
		Opiner::error('Template "' . $this -> fname . '" does not contain "' . $view . '" view model!');
		$this -> view = $view;
		return $this;
	}



	/**
	 *      Načíta iný ako default View model
	 *      Ak model neexistuje, vyhodí sa kritická chyba
	 *      @param string view Aký view model požadujeme na načítanie
	 *      @return object self Ak sa podarí načítať view
	 */

	public function getScript ($source)
	{
		$this -> scripts [] = $source;
		return $this;
	}



	/**
	 *      Načíta iný ako default View model
	 *      Ak model neexistuje, vyhodí sa kritická chyba
	 *      @param string view Aký view model požadujeme na načítanie
	 *      @return object self Ak sa podarí načítať view
	 */

	public function linkStyle ($source)
	{
		$this -> css [] = $source;
		return $this;
	}



	/**
	 *      Načíta iný ako default View model
	 *      Ak model neexistuje, vyhodí sa kritická chyba
	 *      @param string view Aký view model požadujeme na načítanie
	 *      @return object self Ak sa podarí načítať view
	 */

	public function title ($title, $clear = false)
	{
	        if ($clear === true) $this -> title = array ($title);
		else $this -> title [] = $title;
		return $this;
	}



	/**
	 *	Nastavenie dát pre meta hlavičky
	 *	Ak hodnota $value === null, tak
	 *	dochádza k odstráneniu informácií s meta hlavičiek
	 *	@param string index Názov/Typ údaja
	 *	@param string value Hodnota premennej
	 *	@return object self
	 */

	public function meta ($index, $value = null)
	{
		if ($value === null)
		{
			unset ($this -> meta [$index]);
		}
		else $this -> meta [$index] = $value;
		return $this;
	}



	/**
	 *	Pridávanie segmentov hlavičky
	 *	@param string segment Samotný segment
	 *	@return object self
	 */

	public function addsegment ($segment)
	{
		$this -> tohead [] = $segment;
		return $this;
	}



	/**
	 *	Pridávanie segmentov hlavičky
	 *	@param string segment Samotný segment
	 *	@return object self
	 */

	public function addLink ($rel, $href)
	{
	        $keys = array ('rel', 'href', 'type', 'title');
	        foreach (func_get_args() as $key => $value)
	        if ($key > 0) $array [$keys [$key]] = $value;
		$this -> links [$rel] = $array;
		return $this;
	}



	/**
	 *	Pridávanie segmentov hlavičky
	 *	@param string segment Samotný segment
	 *	@return object self
	 */

	public function parser_date ($value, $format = '%d.%m.%Y')
	{
		return strftime ($format, $value);
	}



	/**
	 *	Pridávanie segmentov hlavičky
	 *	@param string segment Samotný segment
	 *	@return object self
	 */

	public function parser_url ($value)
	{
		return '<a href="' . $value . '">' . $value . '</a>';
	}
}
?>