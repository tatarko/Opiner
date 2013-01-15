<?php

namespace Opiner\Module;



/**
 * Cachovanie hodnot
 *
 * Pomocou tohto modulu moze programator velmi jednoduchou
 * formou cachovat urcite premenne. Cachovat pri tom
 * znamena ulozit vysledok nejakej funkcie na urcitu
 * dobu. Pri najblizsom spusteni stranky tak tato
 * funkcia nemusi byt spustana, a to jednak odlahsi
 * zataz hardwaru a prispeje to aj k rychlejsiemu
 * nacitaniu webovej stranky.
 *
 * @author Tomas Tatarko
 * @since 0.4
 */

class Cache extends \Opiner\Module
{
	
	protected
		$cacheFolder,
		$cacheFile,
		$values = [];



	/**
	 * Spustanie modulu volanim z compile() metody aplikacie
	 *
	 * Tato metoda sposobi samotne spustenie cache metody
	 * a taktiez aj odstrani uz presluhujuce premenne, ktorym
	 * vyprsala expiracia.
	 *
	 * @return object
	 */

	public function startup ()
	{
		$this -> run () -> chechOldValues ();
		unset ($this -> _settings);
		return $this;
	}



	/**
	 * Spustenie cache modulu
	 *
	 * V prvej faze tejto metody sa nastavia premmne a odkontroluje
	 * sa, ci cache priecinok existuje. Ak nie, tak sa vyhodi vynimka,
	 * co sposobi ukoncenie kompilovania frameworku. Nakoniec aj existuje
	 * aj cache subor, tak sa z neho nacitaju premmne.
	 *
	 * @param string Do ktoreho suboru sa maju ulozit cache premenne
	 * @param string Priecinok, do ktoreho sa maju ukladat ostatne cache
	 * @return object
	 */

	public function run ($cacheFile = null, $cacheFolder = null)
	{
		$this -> cacheFile = $cacheFile === null ? 'values.php' : $cacheFile;
		$this -> cacheFolder = $cacheFolder === null ? \Opiner\Framework::getPrivateLocation () . 'cache/' : $cacheFolder;
		
		// Kontrola existencie priecinka
		if (!is_dir ($this -> cacheFolder))
		throw new \Opiner\Exception ($this -> cacheFolder, 250);
		
		// Nacitanie cache hodnot
		if ($this -> isFile ($this -> cacheFolder . $this -> cacheFile))
		$this -> values = require ($this -> cacheFolder . $this -> cacheFile);
		
		return $this;
	}



	/**
	 * Odstrani stare cache premenne
	 *
	 * Tato metoda prebehne vsetkymi cache premennymi,
	 * skontroluje, ktorym vyprasala expiracia a tie
	 * vymaze.
	 *
	 * @return object
	 */

	protected function chechOldValues ()
	{
		$time = time ();
		foreach ($this -> values  as $index => $value)
		if ($value ['valid'] < $time)
		unset ($this -> values [$index]);
		return $this;
	}



	/**
	 * Po skompilovani stranky
	 *
	 * Ak je uz cela stranka skompilovana, ulozia sa
	 * cache hodnoty tohto modulu do suboru.
	 *
	 * @return object
	 */

	public function afterCompilation ()
	{
		if (empty ($this -> values))
		{
			if ($this -> isFile ($this -> cacheFolder . $this -> cacheFile))
			unlink ($this -> cacheFolder . $this -> cacheFile);
			return $this;
		}
		
		file_put_contents ($this -> cacheFolder . $this -> cacheFile, '<?php return ' . var_export ($this -> values, true) . '; ?>');
		return $this;
	}



	/**
	 * Ziskavanie/ukladanie cache hodnoty
	 *
	 * Podla toho, kolko argumentov je predanych tejto metode
	 * sposobuje bud vyhladanie a vratenie cachovanej premennej
	 * alebo prida hodnotu do cache pamete.
	 *
	 * Ak je predany len jeden argument, tak premenna s takymto
	 * klucom sa bude vyhladavat a pripadne sa vrati (ak nie je
	 * cachovana, vrati null).
	 *
	 * Ak je zadany aj druhy argument, tak cache hodnota sa ulozi
	 * do pamete a bude pristupna pod unikatnym nazvom zvolenym
	 * ako prvy argument tejto metody. Treti volitelny parameter
	 * moze nastavit presny cas, kedy ma skoncit platnost tejto cache
	 * hodnoty.
	 *
	 * @param string Kluc k zacachovanej hodnote
	 * @param mixed Nova hodnota cachovanej hodnoty
	 * @param int Dokedy ma byt cache aktivna (timestamp)
	 * @return mixed Hodnotu cache alebo object:self
	 */

	public function cache ($key, $value = null, $valid = 0)
	{
		if ($value === null)
		return isset ($this -> values [$key], $this -> values [$key] ['value']) ? $this -> values [$key] ['value'] : null;
		else if (is_object ($value))
		return $this -> error ('Cannot cache value "' . $key . '" beacuse it is an object!', \Opiner\toLog);
		else
		{
			if (!isset ($this -> values [$key]))
			$this -> values [$key] = [
				'created'	=> time (),
				'updated'	=> time (),
				'valid'		=> intval ($valid),
				'value'		=> $value,
			];
			else
			{
				$this -> values [$key] ['updated'] = time ();
				$this -> values [$key] ['valid'] = intval ($valid);
				$this -> values [$key] ['value'] = $value;
			}
		}
		return $this;
	}



	/**
	 * Zisti, kedy cachovanie hodnoty skonci
	 *
	 * @param string Kluc k zacachovanej hodnote
	 * @return int Timestamp casu
	 */

	public function getExpirationTime ($key)
	{
		return isset ($this -> values [$key] ['valid']) ? $this -> values [$key] ['valid'] : false;
	}



	/**
	 * Zisti, kedy cachovanie hodnoty zacalo
	 *
	 * @param string Kluc k zacachovanej hodnote
	 * @return int Timestamp casu
	 */

	public function getCreateTime ($key)
	{
		return isset ($this -> values [$key] ['created']) ? $this -> values [$key] ['created'] : false;
	}



	/**
	 * Zisti, kedy bolo cachovanie hodnoty naposledy zmenene
	 *
	 * @param string Kluc k zacachovanej hodnote
	 * @return int Timestamp casu
	 */

	public function getModifyTime ($key)
	{
		return isset ($this -> values [$key] ['updated']) ? $this -> values [$key] ['updated'] : false;
	}



	/**
	 * Zisti, ako dlho cache existuje
	 *
	 * @param string Kluc k zacachovanej hodnote
	 * @return int Timestamp casu
	 */

	public function getCacheAge ($key)
	{
		return isset ($this -> values [$key] ['created']) ? time () - $this -> values [$key] ['created'] : false;
	}



	/**
	 * Zisti, ako dlho cache nebola menena
	 *
	 * @param string Kluc k zacachovanej hodnote
	 * @return int Timestamp casu
	 */

	public function getCacheModifiedAge ($key)
	{
		return isset ($this -> values [$key] ['updated']) ? time () - $this -> values [$key] ['updated'] : false;
	}



	/**
	 * Odstrani cache hodnotu z inventara
	 *
	 * @param string Kluc k zacachovanej hodnote
	 * @return object
	 */

	public function dropCache ($key)
	{
		unset ($this -> values [$key]);
		return $this;
	}
}
?>