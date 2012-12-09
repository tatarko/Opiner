<?php 

namespace opiner\module;


class database extends \opiner\module {

	const all = true;

	// Premenné
	protected	$all = '*',
			$comma = ', ',
			$wrap = '`~`',
			$set = '(~)',
			$connection,
			$segments = array (),
			$disable = null,
			$prefix = '';


	public function startup ()
	{
		if (isset ($this -> _settings ['server'], $this -> _settings ['username'], $this -> _settings ['password'], $this -> _settings ['database']))
		{
			if (!$this -> connect ($this -> _settings ['server'], $this -> _settings ['username'], $this -> _settings ['password'], $this -> _settings ['database']))
			\opiner\application::error ('Connecting to MySQL server or database has failed!');

			if ($this -> _settings ['prefix']) $this -> setPrefix ($this -> _settings ['prefix']);

/*
			if (is_array (self::$settings ['db'] ['settings']) and count (self::$settings ['db'] ['settings']))
			foreach ($this -> moduls ['database'] -> select (self::$settings ['db'] ['settings'] [1], self::$settings ['db'] ['settings'] [2]) -> table (self::$settings ['db'] ['settings'] [0]) -> fetch () as $row)
			$this -> _settings [$row [self::$settings ['db'] ['settings'] [1]]] = $row [self::$settings ['db'] ['settings'] [2]];
*/

			if ($this -> _settings ['relations'] === true) $this -> mapRelations ();
		}
		else \opiner\application::error('Settings does not match enough data for connecting to database!');
		return $this;
	}





	// Načítanie konfigurácie
	public function connect ($server, $username, $password, $database = null)
	{
		if (false === ($this -> connection = mysql_pconnect ($server, $username, $password)))
		\opiner\application::error ('Connection to MySQL server "' . $server . '" has failed!');
		if ($database !== null and !mysql_select_db ($database, $this -> connection))
		\opiner\application::error ('Connection to database "' . $database . '" has failed!');
		$this -> query ('SET NAMES `utf8` COLLATE `utf8_general_ci`');
		return true;
	}


	// Zmapuje suvislosti medzi tabulkami
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


	// Posielanie Query na databázu
	protected function query ($string)
	{
	        if ($this -> disable !== null)
		{
			\opiner\application::error ($this -> disable . ' | Full Syntax: ' . $string, \opiner\application::toLog);
			return false;
		}
		if (false === ($result = mysql_query ($string, $this -> connection)))
		{
			\opiner\application::error (mysql_error() . ' | Full Syntax: ' . $string, \opiner\application::toLog);
			return false;
		}
		$this -> queryLog [] = $string;
		$this -> segments = array ();
		$this -> disable = null;
		return $result;
	}


	// Posielanie Query na databázu
	protected function result ($query)
	{
		return mysql_fetch_assoc ($query);
	}

	// Príkaz SELECT
	public function select ($first = database::all)
	{
		if (database::all !== $first)
		{
			if (!is_array ($first))
			$first = func_get_args ();
			foreach ($first as $name) $segments[] = $this -> getWrap ($name);
			$this -> segments [] = 'SELECT ' . implode ($this -> comma, $segments);
		}
		else $this -> segments [] = 'SELECT ' . $this -> all;
		return $this;
	}

	// Výber tabuľky
	public function table ()
	{
		$tables = func_get_args();
		$tables = is_array ($tables [0]) ? $tables [0] : $tables;
		if (empty ($tables))
		return $this -> nocomplete ('Empty table list');
		$i = 96;
		foreach ($tables as $name)
		{
			$segments[] = count ($tables) > 1 ? $this -> getWrap ($this -> prefix . $name) . ' as ' . $this -> getWrap ('table_' . chr(++$i)) : str_replace ('~', $this -> prefix . $name, $this -> wrap);
			$this -> tablelog [] = $name;
		}
		$this -> segments [] = 'FROM ' . implode ($this -> comma, $segments);
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


	// Načítanie konfigurácie
	public function config ()
	{
		if (false === ($query = $this -> select ('key', 'value', 'owner') -> table ('settings') -> send (true)))
		if (mysql_errno ($this -> connection) == 1146 and $this -> query ('CREATE TABLE `settings` (`key` tinytext NOT NULL, `value` text, `owner` tinytext) ENGINE=MyISAM DEFAULT CHARSET=utf8;'))
		$query = $this -> select ('key', 'value', 'owner') -> table ('settings') -> send (true);
		else \opiner\application::error ('Config table can not be created!');
		while ($row = $this -> result ($query))
		{
			if ($row['owner'] == 'php')
			ini_set ($row['key'], $row['value']);
			else $return[$row['key']] = $row['value'];
		}
		return isset ($return) ? $return : array();
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
		$this -> segments [] = 'INSERT INTO ' . $this -> getWrap ($table) . ' (' . implode (', ', $keys) . ') VALUES (' . implode (', ', $values) . ');';
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
	                return str_replace ('~', $index [0], $this -> wrap) . '.' . $this -> parseValue ($index [1]);
		}
		return $this -> parseValue ($index [0]);
	}

	// Premenovanie vyberaneho pola
	protected function parseValue ($index)
	{
		$index = explode ('#', $index);
	        if (count ($index) == 3 and $index[1] == 'sql') return $index[0] . ' as ' . str_replace ('~', $index[2], $this -> wrap);
	        if (count ($index) == 2 and $index[1] == 'sql') return $index[0];
	        return $this -> rename ($index [0]);
	}

	// Pridanie indexu tabuliek
	protected function rename ($index)
	{
		if (preg_match('#([a-z]+)\[([a-z]*?)\]#', $index, $match))
		{
			return str_replace ('~', $match[1], $this -> wrap) . ' as ' . str_replace ('~', $match[2], $this -> wrap);
		}
		else return str_replace ('~', $index, $this -> wrap);
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
}

?>