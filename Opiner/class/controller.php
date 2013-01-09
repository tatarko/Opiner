<?php

namespace Opiner;


abstract class Controller
{

	use Behavior;
	
	protected
		$db,
		$temp,
		$menu,
		$cache;



	/* Nahodi odkazy na systemove moduly do prislusnych premennych
	 * @return object self */

	public function __construct ()
	{
		$this -> db = Application::module ('database');
		$this -> temp = Application::module ('template');
		$this -> menu = Application::module ('menu');
		$this -> cache = Application::module ('cache');
		return $this;
	}



	/* Vrati pozadovany modul
	 * @return object Opiner\Module\* */

	protected static function module ($localName)
	{
		return Application::module ($localName);
	}



	/* Preklada jazykove frazy
	 * @return string */

	protected static function t ($key)
	{
		return Application::module ('language') -> translate (func_get_args ());
	}



	/* Preklada jazykove frazy
	 * @return string */

	protected static function l ()
	{
		return Application::module ('router') -> route (func_get_args ());
	}



	/* Pridanie noveho boxu do menu
	 * @param string $name: Unikatny nazov menu boxu
	 * @param string $title: Popisok menu boxu
	 * @param string $into: Do ktoreho stacku vlozit toto menu 
	 * @return string */

	protected static function addBox ($name, $title = null, $into = null)
	{
		return Application::module ('menu') -> addBox ([
			'name'	=> $name,
			'title'	=> $title,
			'into'	=> $into
		]);
	}



	/* Pridanie noveho boxu do menu
	 * @return string */

	protected static function addLink ($title, $url)
	{
		return Application::module ('menu') -> addLink (func_get_args ());
	}


}
?>