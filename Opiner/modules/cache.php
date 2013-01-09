<?php

namespace Opiner\Module;

class Cache extends \Opiner\Module
{
	
	protected
		$cacheFolder,
		$cacheFile,
		$imageCacheData,
		$values = [];



	/* Trieda na zaobstaranie cachovania premennych
	 * @param string $cacheFile: Do ktoreho suboru sa maju ulozit cache premennych
	 * @param string $cacheFolder: Priecinok, do ktoreho sa maju ukladat ostatne cache
	 * @return object self */

	public function startup ()
	{
		$this -> run () -> chechOldValues ();
		unset ($this -> _settings);
		return $this;
	}



	/* Trieda na zaobstaranie cachovania premennych
	 * @param string $cacheFile: Do ktoreho suboru sa maju ulozit cache premennych
	 * @param string $cacheFolder: Priecinok, do ktoreho sa maju ukladat ostatne cache
	 * @return object self */

	public function run ($cacheFile = null, $cacheFolder = null)
	{
		$this -> cacheFile = $cacheFile === null ? 'values.php' : $cacheFile;
		$this -> cacheFolder = $cacheFolder === null ? \Opiner\scripts . 'cache/' : $cacheFolder;
		
		// Kontrola existencie priecinka
		if (!is_dir ($this -> cacheFolder))
		throw new \Opiner\Exception ($this -> cacheFolder, 250);
		
		// Nacitanie cache hodnot
		if ($this -> isFile ($this -> cacheFolder . $this -> cacheFile))
		$this -> values = require ($this -> cacheFolder . $this -> cacheFile);
		
		return $this;
	}



	/* Odstrani cache hodnoty, ktore su uz po dobe spotreby
	 * @return object self */

	protected function chechOldValues ()
	{
		$time = time ();
		foreach ($this -> values  as $index => $value)
		if ($value ['valid'] < $time)
		unset ($this -> values [$index]);
		return $this;
	}



	/* Po skompilovani stranky ulozime hodnoty pekne krasne do suboru
	 * @return object self */

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



	/* Ukladanie a ziskavania cache hodnot
	 * @param string $key: Kluc k zacachovanej hodnote
	 * @param mixed $value: Nova hodnota cachovanej hodnoty
	 * @param int $valid: Dokedy ma byt cache aktivna?
	 * @return mixed: Hodnotu cache pripadne object:self */

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



	/* Zisti, kedy cachovanie hodnoty skonci
	 * @param string $key: Kluc k zacachovanej hodnote
	 * @return int: Timestamp casu */

	public function getExpirationTime ($key)
	{
		return isset ($this -> values [$key] ['valid']) ? $this -> values [$key] ['valid'] : false;
	}



	/* Zisti, kedy cachovanie hodnoty zacalo
	 * @param string $key: Kluc k zacachovanej hodnote
	 * @return int: Timestamp casu */

	public function getCreateTime ($key)
	{
		return isset ($this -> values [$key] ['created']) ? $this -> values [$key] ['created'] : false;
	}



	/* Zisti, kedy bolo cachovanie hodnoty naposledy zmenene
	 * @param string $key: Kluc k zacachovanej hodnote
	 * @return int: Timestamp casu */

	public function getModifyTime ($key)
	{
		return isset ($this -> values [$key] ['updated']) ? $this -> values [$key] ['updated'] : false;
	}



	/* Zisti, ako dlho cache existuje
	 * @param string $key: Kluc k zacachovanej hodnote
	 * @return int: Timestamp casu */

	public function getCacheAge ($key)
	{
		return isset ($this -> values [$key] ['created']) ? time () - $this -> values [$key] ['created'] : false;
	}



	/* Zisti, ako dlho cache nebola menena
	 * @param string $key: Kluc k zacachovanej hodnote
	 * @return int: Timestamp casu */

	public function getCacheModifiedAge ($key)
	{
		return isset ($this -> values [$key] ['updated']) ? time () - $this -> values [$key] ['updated'] : false;
	}



	/* Odstrani cache hodnotu z inventara
	 * @param string $key: Kluc k zacachovanej hodnote
	 * @return object self */

	public function dropCache ($key)
	{
		unset ($this -> values [$key]);
		return $this;
	}
}