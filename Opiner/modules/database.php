<?php 

namespace Opiner\Module;



/**
 * Komunikacia s MySQL databazou
 *
 * Pomocou tohto modulu moze programator velmi jednoduchou
 * formou cachovat urcite premenne. Cachovat pri tom
 * znamena ulozit vysledok nejakej funkcie na urcitu
 * dobu. Pri najblizsom spusteni stranky tak tato
 * funkcia nemusi byt spustana, a to jednak odlahsi
 * zataz hardwaru a prispeje to aj k rychlejsiemu
 * nacitaniu webovej stranky.
 *
 * @author Tomas Tatarko
 * @since 0.3
 */

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



	/**
	 * Startovanie modulu ako volanie z aplikacie
	 *
	 * V prvom kroku tejto metody sa overi, ci existuju vsetky
	 * potrebne premenne pre spravne pripojenie k databaze.
	 * Ak nie, vyhodi sa vynimka a skonci tak kompilovanie
	 * frameworku. Ak existuju, modul sa pokusi pripojit
	 * k ziadanemu MySQL servru a databaze prostrednictvom metody
	 * connect(). Tu tiez moze dojst k vyhodeniu vynimky.
	 *
	 * Ak pole nastaveni tohto modulu obsahuje zmienku aj
	 * o moznej konfiguracii v databaze, tak sa framework
	 * pokusi tuto konfiguraciu ziskat. Okrem toho sa este
	 * podla nastaveni modulu nastavuje prefix tabuliek alebo
	 * mapuju relacie medzi tabulkami.
	 *
	 * @return object
	 */

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

		/*if ($this -> _settings ['relations'] === true)
		$this -> mapRelations ();*/

		unset ($this -> _settings);
		return $this;
	}



	/**
	 * Pripojenie k servru/databaze
	 *
	 * Na zaklade argumentov predanych tejto metode
	 * sa pokusi pripojit k databaze (volitelne). Ak sa
	 * nieco z toho nepodari, metoda vrati vynimku a dochadza
	 * tak k ukonceniu kompilovania frameworku.
	 *
	 * @param string Adresa MySQL servra ku ktoremu sa chceme pripojit
	 * @param string Meno pouzivatela, pod ktorym sa chceme prihlasit
	 * @param string Heslo pouzivatela, pod ktorym sa chceme prihlasit
	 * @param string Databaza, z ktorej budeme cerpat udaje
	 * @return boolean
	 */

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
	 * @return self /

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
	}*/



	/**
	 * Odosielanie SQL prikazu
	 *
	 * @param string Samotna SQL quera volana na db
	 * @return boolean Pripadne resourceid
	 */

	protected function query ($string)
	{
		\Opiner\Application::$log ['database'] [] = $string;
	        if ($this -> disable !== null)
		{
			self::error ($this -> disable . ' | Full Syntax: ' . $string, \Opiner\toLog);
			return false;
		}
		if (false === $result = mysql_query ($string, $this -> connection))
		{
			self::error (mysql_error(), \Opiner\toLog);
			$this -> segments = array ();
			return false;
		}
		$this -> segments = array ();
		$this -> disable = null;
		return $result;
	}



	/**
	 * Vrati jeden riadok
	 *
	 * Pri vytahovani viacerych vysledkov sa tato
	 * funkcia vola stale dookola, az kym nevrati false
	 *
	 * @param pointer Odkaz na query, z ktorej tahame riadky
	 * @return boolean/array
	 */

	protected function result ($query)
	{
		return mysql_fetch_assoc ($query);
	}



	/**
	 * Generovanie SELECT statement-u pri SQL prikazoch
	 *
	 * Tato metoda sluzi na urcenie toho, ake data chceme
	 * dostat pri vytahovani vysledkov z databazy. Tato
	 * metoda ma premenlivy pocet argumentov. Kazdy argument
	 * je vlastne dalsie a dalsie policko, ktore chceme dostat.
	 * Ak je tato metoda volana bez argumentov, tak dochadza
	 * k vyberu vsetkych dat danej tabulky. Okrem toho este moze
	 * byt prvym argumentom aj pole a vtedy sa nacitavaju
	 * elementy tohto pola ako jednotlive bunky.
	 *
	 * @param string Co chceme nacitat
	 * @return object
	 */

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



	/**
	 * Definovanie, z ktorych tabuliek tahat data
	 *
	 * Tato metoda ma premenlivy pocet argumentov, pricom
	 * kazdy jeden predstavuje nazov dalsej a dalsej tabulky,
	 * z ktorej chceme ziskavat udaje. Zoznam tabuliek moze byt
	 * predany aj ako pole v prvom argumente.
	 *
	 * @param string Nazov tabulku.
	 * @return object
	 */

	public function table ($table)
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



	/**
	 * Odosielanie prikazu
	 *
	 * Ak programator uz po ciastkach vyskladal prikaz,
	 * ktory chce v databaze vykovat, moze ho pomocou
	 * tejto metody odoslat. Podla hodnoty prveho argumentu
	 * sa nasledne odosiela vysledok tejto akcie alebo uklada
	 * lokalne pre neskorsie citanie.
	 *
	 * @param boolean Odoslat vysledok akcie?
	 * @return boolean Pripadne object
	 */

	public function send ($result = true)
	{
		if ($result === true)
		return $this -> query (implode (' ', $this -> segments));
		$this -> result = $this -> query (implode (' ', $this -> segments));
		return $this;
	}



	/**
	 * Tvorenie podmienok
	 *
	 * Tato prida do skladaneho SQL prikazy novy segment
	 * s WHERE klauzulou. Prakticky ide o definovanie
	 * podmienok vyberu dat. Tieto podmienky sa predavaju
	 * ako jednotlive argumenty tejto metody a vzdy v paroch.
	 * To znamena, ze neparny argument predstavuje bunku
	 * tabulku, ktoru chceme porovnat a parny argument
	 * zase hodnotou, z ktorou ju chceme porovnat.
	 *
	 * Tieto podmienky mozu byt predane aj ako pole, vtedy
	 * plati, ze index zaznamu pola urcuje bunku, ktoru
	 * chceme porovnat a jeho hodnota urcuje to, s cim chceme
	 * hladany zaznam porovnat.
	 *
	 * @param string Co chceme porovnat
	 * @param string S cim to chceme porovnat
	 * @return object
	 */

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



	/**
	 * Pridanie noveho zaznamu
	 *
	 * Do tabulky (nazov predany ako prvy argument)
	 * prida novy zaznam s hodnotami, ktore su predane
	 * ako pole v druhom argumente. Tato metoda len vytvori
	 * tvar SQL prikazu. Pre skutocne ulozenie tohto zaznamu
	 * je potrebne nad objektom este zavolat metodu send().
	 *
	 * @param string Nazov tabulky, do ktorej pridat zaznam
	 * @param array S akymi hodnotami
	 * @return object
	 */

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



	/**
	 * Aktualizovanie zaznamov
	 *
	 * V tabulke predanej ako prvy paramter upravi hodnoty
	 * jednotlivych buniek na nove hodnoty predane ako pole
	 * v druhom argumente. Tato metoda len vytvori
	 * tvar SQL prikazu. Pre skutocne ulozenie tohto zaznamu
	 * je potrebne nad objektom este zavolat metodu send().
	 *
	 * @param string Nazov tabulky, v ktorej chceme menit obsah
	 * @param array Nove, aktualizovane hodnoty
	 * @return object
	 */

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



	/**
	 * Ako zoradit vysledky
	 *
	 * Urci, podla ktorych buniek zoradit vysledky
	 * pri vypise dat z databazy pripadne podla coho
	 * sa orientovat pri mazani / aktualizovani zaznamov.
	 *
	 * @param string Nazov bunky
	 * @return object
	 */

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



	/**
	 * Obmedzenie poctu vysledkov
	 *
	 * Urci maximalny mozny pocet riadkov, ktore
	 * sa maju vratit, pripadne byt zmazane,
	 * aktualizovane. Tato metoda moze mat aj druhy
	 * argument a ten urci, kolko riadkov na zaciatku
	 * vynechat a pokracovat teda az od i-teho riadku.
	 *
	 * @param int Maximalny pocet riadkov
	 * @param int Kolko riadkov vynechat
	 * @return object
	 */

	public function limit ($limit = 1, $offset = 0)
	{
		$limit = max (intval ($limit), 1);
		$offset = max (intval ($offset), 0);
		$this -> segments [] = 'LIMIT ' . $limit . ' OFFSET ' . $offset;
		return $this;
	}



	/**
	 * Výber zaznamov
	 *
	 * Tato metoda vrati jednotlive zaznamy podla
	 * vyskladaneho prikazu. Vysledok je vrateny ako
	 * dvojrozmerne pole, kde na prvej urovni su jednotlive
	 * riadky a na druhej urovni jednotlive bunky toho
	 * riadka.
	 *
	 * @return array Dvojrozmerne pole vysledkov
	 */

	public function fetch ()
	{
		$this -> send (false);
		if (false === $this -> result) return false;
		$result = array ();
		while ($data = $this -> result ($this -> result))
		$result[] = $data;
		return $result;
	}



	/**
	 * Výber zaznamu
	 *
	 * Tato metoda vrati zaznam podla
	 * vyskladaneho prikazu. Jednoducha premenna,
	 * pricom ide vzdy o prvu bunku prveho riadku.
	 *
	 * @return mixed
	 */

	public function fetchValue ()
	{
		$this -> limit (1) -> send (false);
		if (false === $this -> result) return false;
		$data = $this -> result ($this -> result);
		return is_array ($data) ? current($data) : $data;
	}



	/**
	 * Výber zaznamov
	 *
	 * Tato metoda vrati jednotlive zaznamy podla
	 * vyskladaneho prikazu. Vysledok je vrateny ako
	 * jednoduche pole, pricom ide vzdy o prvy
	 * mozny riadok.
	 *
	 * @return array Pole vysledkov
	 */

	public function fetchRow ()
	{
		$this -> limit (1) -> send (false);
		if (false === $this -> result) return false;
		$data = $this -> result ($this -> result);
		return $data;
	}



	/**
	 * Obalenie nazvu tabulky
	 *
	 * @param string Nazov tabulky
	 * @return string
	 */

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



	/**
	 * Osetrenie indexu
	 *
	 * @param string Co chceme osetrit
	 * @return string
	 */

	protected function parseValue ($index)
	{
		$index = explode ('#', $index);
		if (count ($index) == 3 and $index[1] == 'sql') return $index[0] . ' as ' . str_replace ('~', $index[2], self::wrap);
		if (count ($index) == 2 and $index[1] == 'sql') return $index[0];
		return $this -> rename ($index [0]);
	}

	/**
	 * Dynamicke premenovanie policka
	 *
	 * @param string Predpis policka
	 * @return string
	 */

	protected function rename ($index)
	{
		if (preg_match('#([a-z]+)\[([a-z]*?)\]#', $index, $match))
		return str_replace ('~', $match[1], self::wrap) . ' as ' . str_replace ('~', $match[2], self::wrap);
		return str_replace ('~', $index, self::wrap);
	}



	/**
	 * Nastavenie prefixu tabuliek
	 *
	 * @param string Aky prefix sa bude pouzivat
	 * @return object
	 */

	public function setPrefix ($prefix)
	{
		$this -> prefix = $prefix == '' ? '' : $prefix . '__';
		return $this;
	}

	/* Ziska kluce
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
	}*/



	/**
	 * Po skompilovani stranky
	 *
	 * Ak je uz cela stranka skompilovana, nie je potrebne
	 * udrzovat pripojenie k MySQL servru aktivne, a takk
	 * dojde k bezpecnemu odpojeniu.
	 *
	 * @return object
	 */

	public function afterCompilation ()
	{
		mysql_close ($this -> connection);
		return $this;
	}



	/**
	 * Ziskaj hodnotu primarneho kluca
	 *
	 * Z naposledy pridaneho riadku zisti hodnotu
	 * primarneho kluca/policka s AUTO_INCREMENT
	 * parametrom.
	 *
	 * @return int
	 * @since 0.4
	 */

	public function getAutoIncrementValue ()
	{
		return mysql_insert_id ($this -> connection);
	}



	/**
	 * Ziska zoznam fieldov z tabulky
	 *
	 * V nazvu tabulky predaneho v argumente tejto
	 * metody ziska zoznam vsetkych fieldov spolu
	 * s informaciami o tychto fieldov. Tato funkcia
	 * vrati dvojrozmerne pole.
	 *
	 * @param string Nazov tabulky
	 * @return array
	 * @since 0.5
	 */

	public function getFieldList ($table)
	{
		$query = $this -> query ('SHOW COLUMNS FROM ' . $this -> getWrap ($this -> prefix . $table));
		$result = [];
		while ($data = mysql_fetch_assoc ($query))
		$result [] = $data;
		return $result;
	}
}
?>