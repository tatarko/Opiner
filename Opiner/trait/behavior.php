<?php

namespace Opiner;



/**
 * Zakladne spravanie niektorych tried
 *
 * Tento trait poskytuje triedam ziskavat
 * informacie o existencii suborov, ich
 * nacitavanie alebo s tym suvisiace osetrenie
 * chyb.
 *
 * @author Tomas Tatarko
 * @since 0.2
 */

trait Behavior {
	


	/**
	 * Osetrenie chybovych hlasok systemu
	 *
	 * Tato metoda osetri chybovu hlasku predanu ako prvy
	 * argument a to v podobe, aku ocakava programator
	 * (zadanim druheho) argumentu. Moze vratit boolean
	 * hodnotu false, chybu ulozit do logu alebo ukoncit
	 * kompilovanie frameworku vyhodenim vynimky.
	 * 
	 * @param string Text hlasky, ktora sa ma vypisat
	 * @param int Ako sa ma chybova hlaska spracovat?
	 * @return boolean
	 */

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



	/**
	 * Overenie existencie suboru
	 *
	 * Tato metoda overi, ci adrea suboru predana ako argument
	 * tejto metody je adresou na skutocne existujuci subor
	 * a vysledok vrati v podobe boolean hodnoty. Tejto metode je vsak mozne
	 * nastavit aj ine mody spracovania - vyhodit chybu, ulozit do logu.
	 *
	 * @param string Adresa suboru, ktoreho existencia ma byt overena
	 * @param string Ako sa ma spracovat vysledok
	 * @return boolean
	 */

	public static function isFile ($file, $level = toReturn)
	{
		if (file_exists ($file)) return true;
		else return self::error ('File "' . $file . '" has not been found!', $level);
	}



	/**
	 * Vkladanie suborov s osetrenym opakovanim
	 *
	 * Tato metoda najprv zavola metodu isFile() ako overenie,
	 * ci hladany subor existuje. Ak nie, tak vyhodi vynimku
	 * a tym ukonci kompilovanie celeho frameworku. Ak vsak
	 * subor existuje, tak ho nacita a vysledok vrati
	 *
	 * @param string Adresa suboru, ktory ma byt nacitany
	 * @return mixed Vysledok vrateny samotnym suborom
	 */

	public static function getFile ($file)
	{
		if (!self::isFile ($file))
		throw new Exception ($file, 101);
		return require_once ($file);
	}



	/**
	 * Ziska pole indexov z pola
	 *
	 * Tato metoda vrati pole, ktore predstavuje
	 * zoznam indexov asociativneho pola predaneho
	 * ako argument tejto metody.
	 *
	 * @param array Vstupne pole
	 * @return array
	 * @since 0.3
	 */

	public static function getIndexes ($data)
	{
		$indexes = [];
		foreach ($data as $index => $value)
		$indexes [] = $index;
		return $indexes;
	}
}