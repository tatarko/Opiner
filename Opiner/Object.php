<?php

namespace Opiner;

/**
 * Matersky objekt
 * 
 * Tato trieda je zakladnym predpisom pre vsetky objekty
 * tohto frameworku. Nastavuje akesi zakladne pravidla
 * spravania vsetkych objektov, ktore tento
 * framework vyprodukuje.
 * 
 * @author Tomas Tatarko
 * @since 0.6
 */

abstract class Object {

	/**
	 * Nasadenie novych hodnot premennych
	 * 
	 * V zadkla
	 * 
	 * @param string Index premennej, ktoru cheme nastavit
	 * @param mixed Jej nova hodnota
	 * @return mixed Novo nastavenu hodnotu
	 * @throws Opiner\Exception Ak premenna neexistuje
	 */

	public function __set($name, $value) {

		$publicFields = function($obj) { return get_object_vars($obj); };
		if(array_search($name, $publicFields($this)) !== false)
			return $this->$name = $value;
		elseif(class_exists('Exception', false))
			throw new Exception($name, 100);
		else die('Variable "' . $name . '" does not exist!');
	}



	/**
	 * Ziskavanie hodnot premennych
	 * 
	 * @param string Index premennej, ktoru chceme ziskat
	 * @return mixed Jej aktualnu hodnotu
	 * @throws Opiner\Exception Ak premenna neexistuje v objekte
	 */

	public function __get($name) {

		$publicFields = function($obj) { return get_object_vars($obj); };
		if(array_search($name, $publicFields($this)) !== false)
			return $this->$name;
		elseif(class_exists('Exception', false))
			throw new Exception($name, 100);
		else die('Variable "' . $name . '" does not exist!');
	}



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
	 * @throws Opiner\Exception V pripade, ze je volany level ERROR_DIE
	 */

	public static function error($string, $level = ERROR_DIE) {
		
		switch($level) {

			case ERROR_LOG:
				Opiner::$log['errors'][] = $string;
				return true;
				break;

			case ERROR_RETURN: return false;
				
			default: throw new Exception($string);
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

	public static function isFile($file, $level = ERROR_RETURN) {

		if(file_exists($file))
			return true;
			else return static::error('File "' . $file . '" has not been found!', $level);
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

	public static function getFile($file) {
		
		if(!self::isFile($file))
			throw new Exception($file, 101);
		
		return require_once($file);
	}
}
?>
