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

abstract class Controller extends Object
{
	
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
		$this -> db = Opiner::module ('database');
		$this -> temp = Opiner::module ('template');
		$this -> menu = Opiner::module ('menu');
		$this -> cache = Opiner::module ('cache');
		return $this;
	}



	/**
	 * Vrati pozadovany modul
	 *
	 * Tato metoda je viac menej odkazom na rovnomennu metodu
	 * triedy materskej Opiner
	 * 
	 * @param string Unikatny nazov modulu, ktory chceme nacitat
	 * @return Opiner\Module
	 */

	protected static function module($localName) {
		
		return Opiner::module($localName);
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
		return Opiner::module ('language') -> translate (func_get_args ());
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
		return Opiner::module ('router') -> route (func_get_args ());
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
		return Opiner::module ('menu') -> addBox ([
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
		return Opiner::module ('menu') -> addLink (func_get_args ());
	}



	/**
	 * Volanie modelu
	 *
	 * Tato metoda sa pokusi zavolat model, ktoreho
	 * nazov je predany ako prvy argument. Ak sa tento
	 * model nenajde, vyhodi sa vynimka a ukonci sa
	 * kompilovania stranky.
	 *
	 * @param string Aky model chcem
	 * @return object
	 * @since 0.6
	 */

	protected static function model ($model)
	{
		$name = '\\Opiner\\Model\\' . $model;
		if (!class_exists ($name))
		throw new Exception ($model, 302);
		return $name::model ();
	}



	/**
	 * Vytvorenie noveho formulara
	 *
	 * Tato napomocna trieda vyvola nacitanie toho
	 * spravneho suboru potrebneho na tvorbu
	 * formularov a zaroven vytvori novy
	 * objekt triedy Form.
	 *
	 * @param string Kam ma formular smerovat
	 * @param string akou metodou sa maju odosielat data
	 * @return object/Form
	 * @since 0.6
	 */

	protected function createForm ($action = null, $method = 'post')
	{
		if (!class_exists ('\\Opiner\\Form'))
		self::getFile (root . 'class/form.php');
		
		return new Form ($action, $method);
	}
}
?>