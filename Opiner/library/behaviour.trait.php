<?php

namespace Opiner;

trait Behaviour {
	


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

			case toReturn:
				return false;
				break;

			default:
				die('<p><strong>Error:</strong> ' . $string . '</p>');
				break;
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
		if (array_search ($file, Application::$log ['requiredFiles']) !== false) return true;
		Application::$log ['requiredFiles'] [] = $file;
		return require_once ($file);
	}



	/* Vrati adresu sukromnych suborov instancie webu
	 * @return string */

	public static function getWebRoot ()
	{
		return Application::$webRoot;
	}



	/* Vrati adresu verejnych suborov instancie webu
	 * @return string */

	public static function getWebRemote ()
	{
		return Application::$remote;
	}
}