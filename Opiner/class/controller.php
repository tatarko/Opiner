<?php

namespace Opiner;



/**
 * Vzorova trieda pre vsetky controllery, ktore moze stranka obsahovat
 * 
 * Tato abstraktna trieda definuje akesi zakladne rozhranie, ktore
 * mozu pouzivat vsetky controllery existujuce na stranke.
 *
 * @author Tomas Tatarko
 * @since 0.3
 * @abstract
 */

abstract class Controller
{

	use Behavior;
	
	protected
		$db,
		$temp,
		$menu,
		$cache;



	/**
	 * Vznik objektu
	 *
	 * Ako zaklad sa do premennych tohto controllera
	 * nahodia odkazy na systemove moduly (template, database, menu,
	 * cache).
	 *
	 * @return object
	 */

	public function __construct ()
	{
		$this -> db = Application::module ('database');
		$this -> temp = Application::module ('template');
		$this -> menu = Application::module ('menu');
		$this -> cache = Application::module ('cache');
		return $this;
	}



	/**
	 * Vrati pozadovany modul
	 *
	 * Tato metoda je viac menej odkazom na rovnomennu metodu
	 * triedy Application
	 * 
	 * @param string Unikatny nazov modulu, ktory chceme nacitat
	 * @return object
	 */

	protected static function module ($localName)
	{
		return Application::module ($localName);
	}



	/**
	 * Preklad jazykovej frazy
	 *
	 * Tato metoda je odkazom na metodu translate modulu language,
	 * ktora sa stara o ziskavanie prekladovych fraz. Povinny vstupny
	 * parameter je unikatny kluc, pod ktorym sa nachadza prelozana fraza,
	 * no tato metoda moze byt volana aj dalsimi argumenty, ktore budu
	 * vlozene do prelozenej frazy na vopred urcene miesto.
	 *
	 * @param string Kluc prekladovej frazy
	 * @return string
	 * @since 0.4
	 */

	protected static function t ($key)
	{
		return Application::module ('language') -> translate (func_get_args ());
	}



	/**
	 * Tvorenie odkazov
	 *
	 * Tato metoda na odkazom na metodu route systemoveho
	 * routra. Tato metoda nema ziaden povinny ani ocakavany argument,
	 * no napriek tomu vsetky predane argumenty spracuvava podla routovacej
	 * tabulky.
	 *
	 * @return string
	 * @since 0.4
	 */

	protected static function l ()
	{
		return Application::module ('router') -> route (func_get_args ());
	}



	/**
	 * Pridanie noveho boxu do menu
	 *
	 * @param string Unikatny nazov menu boxu
	 * @param string Popisok menu boxu
	 * @param string Do ktoreho stacku vlozit toto menu 
	 * @return object
	 * @since 0.4
	 */

	protected static function addBox ($name, $title = null, $into = null)
	{
		return Application::module ('menu') -> addBox ([
			'name'	=> $name,
			'title'	=> $title,
			'into'	=> $into
		]);
	}



	/**
	 * Pridanie noveho odkazu do menu
	 *
	 * @param string Text odkazu
	 * @param string Adresa, kam ma smerovat odkaz
	 * @return object
	 * @since 0.4
	 */

	protected static function addLink ($title, $url)
	{
		return Application::module ('menu') -> addLink (func_get_args ());
	}
}
?>