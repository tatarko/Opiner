<?php

namespace Opiner\Module;

class Language extends \Opiner\Module
{

	protected
		$translations = [],	// Tabulka plna prekladovych fraz
		$language;		// Aky jazyk je aktivny?



	/* Startovanie modulu ako volanie z aplikacie
	 * @return object self */

	public function startup ()
	{
		$this -> language = is_array ($this -> _settings) ? $this -> _settings [0] : $this -> _settings;
		$this -> load ($this -> language);
		unset ($this -> _settings);
		return $this;
	}



	/* Startovanie modulu ako volanie z aplikacie
	 * @return object self */

	public function load ($language)
	{
		if (self::isFile (\Opiner\root . 'languages/' . $language . '.php'))
		$this -> translations = array_merge ($this -> translations, require (\Opiner\root . 'languages/' . $language . '.php'));
		if (self::isFile (\Opiner\scripts . 'languages/' . $language . '.php'))
		$this -> translations = array_merge ($this -> translations, require (\Opiner\scripts . 'languages/' . $language . '.php'));
		return $this;
	}



	/* Prelozi pozadovanu frazu na zaklade prekladacej tabulky
	 * @param string $key: Kluc frazy, ktoru chceme prelozit
	 * @return string */

	public function translate ($key)
	{
		$params = is_array ($key) ? $key : func_get_args ();
		$key = current ($params);

		if (!isset ($this -> translations [$key])) return '[translation missing: '. $key . ']';
		$string = $this -> translations [$key];
		foreach ($params as $index => $value)
		$string = str_replace ('$' . $index, $value, $string);
		return $string;
	}



	/* Odtestuje, ci existuje prekladova fraza
	 * @param string $key: Kluc frazy, ktoru chceme prelozit
	 * @return booelan */

	public function test ($key)
	{
		return isset ($this -> translations [$key]);
	}
}

?>