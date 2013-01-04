<?php

namespace Opiner\Module;

class Language extends \Opiner\Module
{

	use \Opiner\Behaviour;
	protected $translations = [];		// Tabulka plna prekladovych fraz



	/* Startovanie modulu ako volanie z aplikacie
	 * @return object self */

	public function startup ()
	{
		$this -> load ($this -> _settings [0]);
	}



	/* Startovanie modulu ako volanie z aplikacie
	 * @return object self */

	public function load ($language)
	{
		if (self::isFile (\Opiner\root . 'languages/' . $language . '.php'))
		$this -> translations = array_merge ($this -> translations, require (\Opiner\root . 'languages/' . $language . '.php'));
		if (self::isFile (self::getWebRoot () . 'private/languages/' . $language . '.php'))
		$this -> translations = array_merge ($this -> translations, require (self::getWebRoot () . 'private/languages/' . $language . '.php'));
		return $this;
	}



	/* Prelozi pozadovanu frazu na zaklade prekladacej tabulky
	 * @param string $key: Kluc frazy, ktoru chceme prelozit
	 * @return string */

	public function translate ($key)
	{
		if (!isset ($this -> translations [$key])) return '[translation missing: '. $key . ']';
		$string = $this -> translations [$key];
		foreach (func_get_args() as $index => $value)
		$string = str_replace ('$' . $index, $value, $string);
		return $string;
	}
}

?>