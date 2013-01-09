<?php

namespace Opiner;

abstract class Model
{

	protected
		$storage = [],
		$activePrimaryKey = 0,
		$isNew = false,
		$executeUpdate = false;

	protected static
		$primaryKey,
		$fields = [],
		$labels = [],
		$rules = [],
		$conditios = [],
		$scenarios = [];

	abstract public static function tableName ();


	/* Vytvorenie noveho zaznamu v Databaze
	 * return object self */

	public final function __construct ($data = null)
	{
		$this -> prepareStorage ();
		$this -> isNew = is_array ($data) ? true : false;
		
		if (is_array ($data))
		foreach ($data as $index => $value)
		$this -> $index = $value;

		return $this;
	}



	/* Nastavenie hodnoty niektoreho z fieldov
	 * @param string $field: Ktoru hodnotu chceme menit
	 * @param mixed $value: Nova hodnota tejto premennej
	 * @return mixed: Aktualna hodnota premennej */

	public  function __set ($field, $value)
	{
		// Je takyto field v danej tabulke?
		if (array_search ($field, static::$fields) === false)
		throw new Exception ($field, 300);

		// Ako bude vyzerat nova hodnota, bude nutny update DB?		
		$newValue = self::parseValue ($field, $value);
		if ($newValue !== $this -> storage [$field]) $this -> executeUpdate = true;
		$this -> storage [$field] = $newValue;
		return $newValue;
	}



	/* Ziskanie aktualnej hodnoty niektoreho z fieldov
	 * @param string $field: Ktoru hodnotu chceme dostat
	 * @return mixed: Aktualna hodnota premennej */

	public final function __get ($field)
	{
		if (array_search ($field, static::$fields) === false)
		throw new Exception ($field, 301);
		return $this -> storage [$field];
	}



	/* Existuje takyto field?
	 * @param string $field: Ktoru hodnotu chceme overit
	 * @return boolean */

	public final function __isset ($field)
	{
		return array_search ($field, static::$fields) === false ? false : true;
	}



	/* Pokus o zmazanie niektoreho z fieldov
	 * @param string $field: Kam ide pokus */

	public final function __unset ($field)
	{
		throw new Exception ($field, 302);
	}



	/* Ak nie su dane, zistit informacie o tabulke, parametroch
	 * fieldov a podobne. Dalej pripravi vnutorny storage priestor,
	 * ponastavuje vsetky potrebne premenne pre spravny beh celeho modelu
	 * @return object self */

	protected final function prepareStorage ()
	{
		if (empty ($this -> fields))
		$this -> prepareMeta ();

		foreach (static::$fields as $field)
		$this -> storage [$field] = 0;
	}



	/* Zisti detaily o tabulke
	 * @return object self */

	protected final function prepareMeta ()
	{
		#mysql_s
	}



	/* Osetri hodnotu podla pravidiel prisluchajucich pre dany field
	 * @param string $field: Pravidla ktoreho fieldu aplikovat?
	 * @param mixed $value: Na ake vstupne data do aplikovat
	 * @return mixed: Osetrena hodnota */

	protected static final function parseValue ($field, $value)
	{
		return $value;
	}



	/* Prida novy zaznam do DB, pripadne aktualizuje existujuci
	 * @return boolean: Podarilo sa vykonat akciu? */

	public final function save ()
	{
		if ($this -> isNew)
		{
			if (!Application::module('database') -> insert (static::tableName (), $this -> storage) -> send ())
			return false;
			
			$this -> isNew = false;
			$this -> executeUpdate = false;
			if ($this -> primaryKey)
			$this -> activePrimaryKey = Application::module('database') -> getAutoIncrementValue ();
			return true;
		}
		else
		{
			if (!Application::module('database') -> update (static::tableName (), $this -> storage) -> send ())
			return false;

			$this -> executeUpdate = false;
			return true;
		}
	}



	/* Vrat vsetky mozne zaznamy
	 * return array */

	public static final function findAll ()
	{
		$return = [];
		foreach (Application::module('database') -> select () -> table (static::tableName ()) -> fetch () as $data)
		eval ('$return [] = new \\Opiner\\Model\\' . static::tableName () . ' ($data);');
		return $return;
	}
}

?>