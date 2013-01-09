<?php 

namespace Opiner\Module;

class Database extends \Opiner\Module
{

	const
		all = true,
		whole = '*',
		comma = ', ',
		wrap = '`~`';
	
	protected
		$connection,
		$segments = array (),
		$disable = null,
		$prefix = '';



	/* Startovanie modulu ako volanie z aplikacie
	 * @return object self */

	public function startup ()
	{
		if (!isset ($this -> _settings ['server'], $this -> _settings ['username'], $this -> _settings ['password'], $this -> _settings ['database']))
		throw new \Opiner\Exception (null, 220);
		$this -> connect ($this -> _settings ['server'], $this -> _settings ['username'], $this -> _settings ['password'], $this -> _settings ['database']);

		if ($this -> _settings ['prefix'])
		$this -> setPrefix ($this -> _settings ['prefix']);

		if (is_array ($this -> _settings ['settings']) and count ($this -> _settings ['settings']) == 3)
		{
			$data = $this -> select ($this -> _settings ['settings'] [1], $this -> _settings ['settings'] [2]) -> table ($this -> _settings ['settings'] [0]) -> fetch ();
			if ($data)
			foreach ($data as $row)
			\Opiner\Application::config ($row [$this -> _settings ['settings'] [1]], $row [$this -> _settings ['settings'] [2]], false);
		}

		if ($this -> _settings ['relations'] === true)
		$this -> mapRelations ();

		unset ($this -> _settings);
		return $this;
	}



	/* Funkcia na pripojenie k mysql servru a databaze
	 * @param string $server: Adresa MySQL servra ku ktoremu sa chceme pripojit
	 * @param string $username: Meno pouzivatela, pod ktorym sa chceme prihlasit
	 * @param string $password: Heslo pouzivatela, pod ktorym sa chceme prihlasit
	 * @param string $database: Databaza, z ktorej budeme cerpat udaje
	 * @return object boolean */

	public function connect ($server, $username, $password, $database = null)
	{
		if (false === ($this -> connection = @mysql_pconnect ($server, $username, $password)))
		throw new \Opiner\Exception ($server, 221);
		if ($database !== null and !@mysql_select_db ($database, $this -> connection))
		throw new \Opiner\Exception ($database, 222);
		$this -> query ('SET NAMES `utf8` COLLATE `utf8_general_ci`');
		return true;
	}



	/* Mapovanie vztahov medzi tabulkami v databaze
	 * @return self */

	public function mapRelations ()
	{
		$sql = mysql_query ('SHOW TABLES' . (($this -> prefix == '' ? '': ' LIKE "' . $this -> prefix . '%"')), $this -> connection);
		while ($table = mysql_fetch_row ($sql))
		{
			$query = mysql_query ('DESCRIBE `'. $table [0] . '`');
			while ($data = mysql_fetch_row ($query))
			{
				if (count (explode ('.', $data [0])) == 2)
				$this -> relations [current (explode ('.', $data [0]))] [] = array (
					'table'	=> $table [0],
					'field'	=> $data [0],
				);
			}
		}
		return $this;
	}



	/* Odosielanie SQL prikazu
	 * @param string $string: Samotna SQL quera volana na db
	 * @return boolean/array */

	protected function query ($string)
	{
		\Opiner\Application::$log ['database'] [] = $string;
	        if ($this -> disable !== null)
		{
			self::error ($this -> disable . ' | Full Syntax: ' . $string, \Opiner\toLog);
			return false;
		}
		if (false === ($result = mysql_query ($string, $this -> connection)))
		{
			self::error (mysql_error(), \Opiner\toLog);
			$this -> segments = array ();
			return false;
		}
		$this -> segments = array ();
		$this -> disable = null;
		return $result;
	}



	/* Pri opakovanom vybere dat v ramci jeden query, vracia jednotlive riadky
	 * @param pointer $query: Odkaz na query, z ktorej tahame riadky
	 * @return boolean/array */

	protected function result ($query)
	{
		return mysql_fetch_assoc ($query);
	}



	/* Generovanie SELECT statement-u pri SQL prikazoch
	 * @param mixed $first: Prvy zo stlpcov, ktore sa maju tahat
	 * @return self */

	public function select ($first = self::all)
	{
		if (self::all !== $first)
		{
			if (!is_array ($first))
			$first = func_get_args ();
			foreach ($first as $name) $segments[] = $this -> getWrap ($name);
			$this -> segments [] = 'SELECT ' . implode (self::comma, $segments);
		}
		else $this -> segments [] = 'SELECT ' . self::whole;
		return $this;
	}



	/* Funkcia na pripojenie k mysql servru a databaze
	 * @param string server: Adresa MySQL servra ku ktoremu sa chceme pripojit
	 * @param string username: Meno pouzivatela, pod ktorym sa chceme prihlasit
	 * @param string password: Heslo pouzivatela, pod ktorym sa chceme prihlasit
	 * @param string database: Databaza, z ktorej budeme cerpat udaje
	 * @return object boolean */

	public function table ()
	{
		$tables = func_get_args();
		$tables = is_array ($tables [0]) ? $tables [0] : $tables;
		if (empty ($tables))
		return $this -> nocomplete ('Empty table list');
		$i = 96;
		foreach ($tables as $name)
		{
			$segments[] = count ($tables) > 1 ? $this -> getWrap ($this -> prefix . $name) . ' as ' . $this -> getWrap ('table_' . chr(++$i)) : str_replace ('~', $this -> prefix . $name, self::wrap);
			$this -> tablelog [] = $name;
		}
		$this -> segments [] = 'FROM ' . implode (self::comma, $segments);
		return $this;
	}

	// Výber tabuľky
	public function send ($result = false)
	{
		if ($result === true)
		return $this -> query (implode (' ', $this -> segments));
		$this -> result = $this -> query (implode (' ', $this -> segments));
		return $this;
	}


	// Tvorenie WHERE podmienok
	public function where ($where = '')
	{
		if (!is_array ($where))
		{
			$array = func_get_args();
			$where = array ();
			foreach ($array as $index => $value)
			{
				if ($index % 2 == 0)
				$where[$value] = $array [++$index];
			}
		}
		
		foreach ($where as $index => $value)
		{
			list ($index, $type, $mark) = array_merge (explode ('#', $index), array ('=', '='));
			switch ($type)
			{

				case 'valsql':
					$segments[] = implode (' ', array ($this -> getWrap ($index), $mark, $value));
				    break;

				case 'int':
					$segments[] = implode (' ', array ($this -> getWrap ($index), $mark, intval ($value)));
					break;

				case 'value':
					$segments[] = implode (' ', array ($this -> getWrap ($index), $mark, $this -> getWrap ($value)));
					break;

				case 'like':
					$segments[] = implode (' ', array ($this -> getWrap ($index), 'LIKE', "'" . mysql_real_escape_string ($value) . "'"));
					break;

				case 'in':
					$segments[] = '(' . $this -> getWrap ($index) . ' IN (' . implode (', ', array_unique($value)) . '))';
					break;

				case 'sql':
					$segments[] = implode (' ', array ($index, $mark, "'" . mysql_real_escape_string ($value) . "'"));
					break;

				default:
					$segments[] = implode (' ', array ($this -> getWrap ($index), $mark, "'" . mysql_real_escape_string ($value) . "'"));
					break;
			}
		}
		if (isset ($segments))
		$this -> segments [] = 'WHERE ' . implode (' AND ', $segments);
		return $this;
	}


	// Vkladanie novych zaznamov
	public function insert ($table, $rows)
	{
		if (!is_array ($rows)) return false;
		foreach ($rows as $index => $value)
		{
			list ($index, $type) = array_merge (explode ('#', $index), array ('string'));
			$keys[$index] = $this -> getWrap ($index);
			switch ($type)
			{
				case 'int':
					$values[$index] = intval ($value);
					break;

				case 'sql':
					$values[$index] = $value;
					break;

				default:
					$values[$index] = $value == '' ? 'NULL' : "'" . mysql_real_escape_string ($value) . "'";
					break;
			}
		}
		$this -> segments [] = 'INSERT INTO ' . $this -> getWrap ($this -> prefix . $table) . ' (' . implode (', ', $keys) . ') VALUES (' . implode (', ', $values) . ');';
		return $this;
	}


	// Aktualizovanie zaznamov
	public function update ($table, $rows)
	{
		if (!is_array ($rows)) return false;
		foreach ($rows as $index => $value)
		{
			list ($index, $type) = array_merge (explode ('#', $index), array ('string'));
			$keys[$index] = $this -> getWrap ($index);
			switch ($type)
			{
				case 'int':
					$values[$index] = intval ($value);
					break;

				case 'sql':
					$values[$index] = $value;
					break;

				default:
					$values[$index] = $value == '' ? 'NULL' : "'" . mysql_real_escape_string ($value) . "'";
					break;
			}
		}
		foreach ($values as $index => $value) $segments [] = $this -> getWrap ($index) . ' = ' . $value;
		$this -> segments [] = 'UPDATE ' . $this -> getWrap ($this -> prefix . $table) . ' SET ' . implode (', ', $segments);
		return $this;
	}


	// Zoradzovanie vysledkov
	public function order ($order)
	{
		foreach (func_get_args() as $value)
		{
			$index = explode ('#', $value);
			if (count ($index) == 1) $segments [] = $this -> getWrap ($index [0]) . ' ASC';
			else switch ($index [1])
			{
				case 'asc': $segments [] = $this -> getWrap ($index [0]) . ' ASC'; break;
				case 'desc': $segments [] = $this -> getWrap ($index [0]) . ' DESC'; break;
				case 'sql': $segments [] = $index [0]; break;
			}
		}
		$this -> segments [] = 'ORDER BY ' . implode (', ', $segments);
		return $this;
	}


	// Obmedzenie poctu vysledkov
	public function limit ($limit = 1)
	{
		$array = count (func_get_args()) == 0 ? array (1) : func_get_args();
		if (count ($array) == 2)
		$this -> segments [] = 'LIMIT ' . max (0, intval ($array[1])) . ' OFFSET ' . max (0, intval ($array[0]));
		else $this -> segments [] = 'LIMIT ' . max (1, intval ($array[0]));
		return $this;
	}

	// Výber zaznamov
	public function fetch ()
	{
		$this -> send ();
		if (false === $this -> result) return false;
		$result = array ();
		while ($data = $this -> result ($this -> result))
		$result[] = $data;
		return $result;
	}

	// Výber jednej hodnoty
	public function fetchValue ()
	{
		$this -> limit (1) -> send ();
		if (false === $this -> result) return false;
		$data = $this -> result ($this -> result);
		return is_array ($data) ? current($data) : $data;
	}

	// Výber jedného riadka
	public function fetchRow ()
	{
		$this -> limit (1) -> send ();
		if (false === $this -> result) return false;
		$data = $this -> result ($this -> result);
		return $data;
	}

	// Obalenie indexov
	protected function getWrap ($index)
	{
		$index = explode ('/', $index);
		if (count ($index) == 2)
		{
			if (strlen ($index[0]) == 1)
			$index [0] = 'table_' . chr (ord (intval ($index[0])) + 48);
			return str_replace ('~', $index [0], self::wrap) . '.' . $this -> parseValue ($index [1]);
		}
		return $this -> parseValue ($index [0]);
	}

	// Premenovanie vyberaneho pola
	protected function parseValue ($index)
	{
		$index = explode ('#', $index);
		if (count ($index) == 3 and $index[1] == 'sql') return $index[0] . ' as ' . str_replace ('~', $index[2], self::wrap);
		if (count ($index) == 2 and $index[1] == 'sql') return $index[0];
		return $this -> rename ($index [0]);
	}

	// Pridanie indexu tabuliek
	protected function rename ($index)
	{
		if (preg_match('#([a-z]+)\[([a-z]*?)\]#', $index, $match))
		return str_replace ('~', $match[1], self::wrap) . ' as ' . str_replace ('~', $match[2], self::wrap);
		return str_replace ('~', $index, self::wrap);
	}

	// Pridanie prefixu
	public function setPrefix ($prefix)
	{
		$this -> prefix = $prefix == '' ? '' : $prefix . '__';
	}

	// Ziska kluce
	protected function getKeys ($array, $key)
	{
		$result = array ();
		foreach ($array as $value)
		if (isset ($value [$key]))
		$result [] = $value [$key];
		return $result;
	}

	// Pridaj nove data do uz ziskanych vysledkov
	protected function addData ($result, $index, $field, $value, $data, $remove = false)
	{
		foreach ($result as $i => $array)
		if (isset ($array [$field])
		and $array [$field] == $value)
		{
			if ($remove) unset ($data [$remove]);
			$result [$i] [$index] [] = $data;
			break;
		}
		return $result;
	}

	// Inteligentny vyber dat z tabulky
	public function multiFetch ()
	{
		// Spracovanie zakladneho query
		$this -> send ();
		if (false === $this -> result) return false;
		$tablename = end ($this -> tablelog);
		$result = array ();
		while ($data = $this -> result ($this -> result))
		{
			$result[] = $data;
			
			// Najdem nejaky relationship?
			foreach ($data as $index => $value)
			if (count (explode ('.', $index)) == 2)
			$indexes [$index] [] = $data [$index];
		}
		
		// Ziskavam data na zaklade priameho prelinkovania
		if (isset ($indexes))
		foreach ($indexes as $index => $keys)
		{
			list ($table, $field) = explode('.', $index);
			if (array_search($table, $this -> tablelog) === false)	// nebral som z tej tabulky uz nieco?
			{
				foreach ($this -> select () -> table ($table) -> where ($field . '#in', $keys) -> multiFetch () as $data)
				$array [$data [$field]] = $data;
				foreach ($keys as $i => $key)
				{
					$result [$i] [$table] = $array [$key];
					unset($result [$i] [$index]);
				}
			}
		}
		
		// Nacitavanie spatnych prelinkovani
		if (isset ($this -> relations [$tablename]) and isset ($indexes))
		foreach ($this -> relations [$tablename] as $value)
		{
			list ($table, $field) = explode ('.', $value ['field']);
			if (isset ($result [0] [$field])
			and array_search ($value ['table'], $this -> tablelog) === false)
			foreach ($this -> select () -> table ($value ['table']) -> where ($value ['field'] . '#in', $this -> getKeys ($result, $field)) -> multiFetch () as $data)
			$result = $this -> addData ($result, $value ['table'], $field, $data [$value ['field']], $data, $value ['field']);
		}
		return $result;
	}



	/* Po skonceni kompilovania stranky sa pekne krasne odpojime
	 * @return self */

	public function afterCompilation ()
	{
		mysql_close ($this -> connection);
	}



	/* Ziskaj hodnotu AUTO_INCREMENT z naposledy pridaneho zaznamu
	 * @return int */

	public function getAutoIncrementValue ()
	{
		return mysql_insert_id ($this -> connection);
	}
}

?>