<?php

namespace Opiner;

trait Behavior {
	


	/* Osetrenie chybovych hlasok systemu
	 * @param string $string: Text hlasky, ktora sa ma vypisat
	 * @param int $level: Ako sa ma chybova hlaska spracovat?
	 * @return boolean */

	public static function error ($string, $level = toDie)
	{
		switch ($level)
		{
			case toLog:
				Application::$log ['errors'] [] = $string;
				return true;
				break;

			case toReturn: return false;
			default: throw new Exception ($string);
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

	public static function getFile ($file)
	{
		if (!self::isFile ($file))
		throw new Exception ($file, 101);
		return require_once ($file);
	}



	/* Ziska pole indexov z viacurovnoveho pola
	 * @param array $data: Vstupne pole
	 * @return array */

	public static function getIndexes ($data)
	{
		$indexes = [];
		foreach ($data as $index => $value)
		$indexes [] = $index;
		return $indexes;
	}
}