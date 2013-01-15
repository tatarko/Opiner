<?php

namespace Opiner\Module;



/**
 * Prekladanie stranok do inych jazykov
 *
 * Tato jednoducha trieda skvele posluzi, ak
 * programator potrebuje stranku prelozit do
 * viacerych jazykov. Zoskupuje prelozene frazy
 * pristupne pod unikatnym klucom a ponuka
 * moznost pouzivat v ramci prelozenych fraz
 * aj premenne prisptupne pod indexmi $1,
 * $2 a podobne.
 *
 * @author Tomas Tatarko
 * @since 0.3
 */

class Language extends \Opiner\Module
{

	protected
		$translations = [];	// Tabulka plna prekladovych fraz



	/**
	 * Startovanie modulu ako volanie z aplikacie
	 *
	 * Jedinym cielom tejto metody je nacitat prelozene frazy
	 * jazyka, ktory sa nachadza v nastaveniach tohto modulu.
	 *
	 * @return object
	 */

	public function startup ()
	{
		$language = is_array ($this -> _settings) ? $this -> _settings [0] : $this -> _settings;
		$this -> load ($language);
		unset ($this -> _settings);
		return $this;
	}



	/**
	 * Nacita prekladove subory
	 *
	 * @return object
	 */

	public function load ($language)
	{
		if (self::isFile (\Opiner\root . 'languages/' . $language . '.php'))
		$this -> translations = array_merge ($this -> translations, require (\Opiner\root . 'languages/' . $language . '.php'));
		if (self::isFile (\Opiner\Framework::location ('language', $language)))
		$this -> translations = array_merge ($this -> translations, require (\Opiner\Framework::location ('language', $language)));
		return $this;
	}



	/**
	 * Prelozi pozadovanu frazu
	 *
	 * Tato metoda vyhlada v prekladovej tabulke frazu
	 * s klucom predanym v prvom argumente. Ak sa najde,
	 * tak do nej nahadze premenne predane ako dalsie argumenty
	 * tejto funkcie.
	 * 
	 * @param string Kluc frazy, ktoru chceme prelozit
	 * @return string
	 */

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



	/**
	 * Odtestuje, ci existuje prekladova fraza
	 *
	 * @param string Kluc frazy, ktoru chceme prelozit
	 * @return booelan
	 */

	public function test ($key)
	{
		return isset ($this -> translations [$key]);
	}
}
?>