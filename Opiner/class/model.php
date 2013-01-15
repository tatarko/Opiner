<?php

namespace Opiner;

/**
 * Zakladny predpis pre vsetky databazove modely
 *
 * Tato abstraktna trieda sluzi ako zaklad pre vsetky modely,
 * ktore budu od nej odvodene. Uz vo svojom zaklade ponuka
 * vsetko potrebne, a preto odvodene triedy prakticky
 * nepotrebuju nic definovat (i ked tuto moznost nestracaju).
 *
 * Model sam ako taky je z podstaty MVC nastroj sluziaci
 * na vytahovanie dat z databazy bez potreby pisania
 * SQL prikazov. Naopak, s modelom sa pracuje na urovni
 * ORM / Active Record architektury.
 *
 * @author Tomas Tatarko
 * @since 0.5
 */

abstract class Model
{

	protected
		$storage = [],
		$activePrimaryKey = false,
		$isNew = false,
		$executeUpdate = false,
		$originalData = [];

	protected static
		$primaryKey,
		$fields = [],
		$fieldData = [];



	/**
	 * Vytvorenie noveho objektu daneho modelu
	 *
	 * Vytvoreny objekt vzdy predstavuje jeden riadok tabulky
	 * v databaze. Pricom ak sa vytvorenie objektu vola bez argument,
	 * tak dochadza k vytvoreniu noveho zaznamu v tabulke.
	 * Pokial vstupnym argumentom funkcie je pole s dostatocnym
	 * poctom argumentov, tak objekt bude predstatovat
	 * existujuci zaznam v tabulke.
	 *
	 * @param array Data aktualneho riadka v tabulke
	 * @return object
	 */

	public final function __construct ($data = null)
	{
		$this -> prepareStorage ();
		
		if (is_array ($data) and count ($data) == count (static::$fields))
		{
			foreach ($data as $index => $value)
			$this -> $index = $value;
			$this -> executeUpdate = false;
			$this -> originalData = $this -> storage;

			if (static::$primaryKey)
			{
				$fieldName = static::$primaryKey;
				$this -> activePrimaryKey = $this -> $fieldName;
			}
		}
		else $this -> isNew = true;
		return $this;
	}



	/**
	 * Nastavenie aktualnej hodnoty bunky
	 *
	 * Zmeni hodnotu pozadovanej bunky daneho riadku
	 * na novu hodnotu. V tomto kroku sa tato hodnota
	 * nijako nekontroluje. Ku kontrole tychto hodnot
	 * dochadza az volanim metody validate(), ktore
	 * nastava pocas behu metody save(). Ak sa vsak clovek
	 * pokusi zmenit hodnotu bunky, ktore sa v tabulke
	 * nenachadza, tak dochadza k vyhodeniu vynimky
	 * a tym padom ku koncu behu frameworku.
	 *
	 * @param string Ktoru hodnotu chceme menit
	 * @param mixed Nova hodnota tejto premennej
	 * @return mixed Aktualna hodnota premennej
	 */

	public  function __set ($field, $value)
	{
		// Je takyto field v danej tabulke?
		if (array_search ($field, static::$fields) === false)
		throw new Exception ($field, 300);

		// Ako bude vyzerat nova hodnota, bude nutny update DB?		
		if ($value !== $this -> storage [$field]) $this -> executeUpdate = true;
		$this -> storage [$field] = $value;
		return $value;
	}



	/**
	 * Vrati aktualnu hodnotu bunky
	 *
	 * Ak sa clovek pokusi citat hodnotu neexistujucej bunky,
	 * tak dochadza k vyhodeniu vynimky a tym padom ku
	 * koncu kompilovania celeho frameworku.
	 *
	 * @param string Ktoru hodnotu chceme dostat
	 * @return mixed Aktualna hodnota premennej
	 */

	public final function __get ($field)
	{
		if (array_search ($field, static::$fields) === false)
		throw new Exception ($field, 300);
		return $this -> storage [$field];
	}



	/**
	 * Kontrola existencie niektorej z buniek
	 *
	 * Tato metoda vracia true/false podla toho, ci
	 * bunka s predanym nazvom v tabulke realne existuje
	 *
	 * @param string Nazov bunky, ktorej eixstenciu chceme overit
	 * @return boolean
	 */

	public final function __isset ($field)
	{
		return array_search ($field, static::$fields) === false ? false : true;
	}



	/**
	 * Pokus o zmazanie niektorej z buniek
	 *
	 * Jednotlive bunky z lubovolneho riadku nie je mozne mazat,
	 * a preto pri kazdom volani tejto magickej metody
	 * dochadza k vyhodeniu vynimky a teda ku koncu
	 * kompilovania frameworku.
	 *
	 * @param string Kam ide pokus
	 */

	public final function __unset ($field)
	{
		throw new Exception ($field, 301);
	}



	/**
	 * Pripravi vnutorny ulozny priestor
	 *
	 * Ak nie su dane, zisti informacie o tabulke, parametroch
	 * fieldov a podobne. Dalej pripravi vnutorny storage priestor,
	 * ponastavuje vsetky potrebne premenne pre spravny beh celeho modelu
	 *
	 * @return object
	 */

	protected final function prepareStorage ()
	{
		if (empty (static::$fields))
		static::prepareMeta ();

		foreach (static::$fields as $field)
		$this -> storage [$field] = static::$fieldData [$field] ['default'];
	}



	/**
	 * Zisti detaily o tabulke
	 *
	 * Callback metoda pre prepareStorage. Ak je potrebne, tak
	 * tato metoda si vytiahne z databazy informacie o vsetkych
	 * fieldoch danej tabulky a na zaklade ziskanych hodnot
	 * vytvori vnutornu staticku stukturu modelu.
	 *
	 * @return object
	 */

	protected static final function prepareMeta ()
	{
		foreach (Framework::module ('database') -> getFieldList (static::tableName ()) as $field)
		{
			static::$fields [] = $field ['Field'];
			static::$fieldData [$field ['Field']] = [
				'label'		=> ucwords (str_replace (['.', '-', '_'], ' ', $field ['Field'])),
				'default'	=> $field ['Default'],
				'rules'		=> [],
				'type'		=> $field ['Type'],
				'format'	=> static::getSimpleFieldTypeName ($field ['Type']),
			];
			if ($field ['Key'] == 'PRI') static::$primaryKey = $field ['Field'];
			if ($field ['Null'] == 'NO' and strpos ($field ['Extra'], 'auto_increment') === false) static::$fieldData [$field ['Field']] ['rules'] [] = 'required';
			if (strpos ($field ['Type'], 'unsigned') !== false) static::$fieldData [$field ['Field']] ['rules'] [] = 'unsigned';
		}
	}



	/**
	 * Vrati typovy nazov pre field
	 *
	 * Vstupom do tejto funkcie je typ niektoreho zo stlpcov tabulky
	 * tak, ako je deklarovany v samotnej mysql databaze. Vystupom
	 * funkcie je potom uz len jednoduche urcenie typu premennej
	 * bez ohladu na jej parametre (string, int, ...)
	 *
	 * @param string Typ podla MySQL
	 * @return string
	 */

	protected static final function getSimpleFieldTypeName ($type)
	{
		$type = strpos ($type, '(') !== false ? substr ($type, 0, strpos ($type, '(')) : $type;
		switch ($type)
		{
			case 'tinyint':
			case 'smallint':
			case 'mediumint':
			case 'int':
			case 'bigint': return 'integer';
			case 'decimal':
			case 'float':
			case 'double': return 'float';
			case 'timestamp':
			case 'datetime':
			case 'time':
			case 'year':
			case 'date': return 'date';
			case 'enum':
			case 'set': return 'oneof';
			default: return 'string';
		}
	}



	/**
	 * Ukladanie riadku do tabulky
	 *
	 * Ak ide o novy riadok tabulky, tak vykonava insert
	 * prikaz, inac sa vykonava update prikaz. Ako sucast
	 * behu tejto metody sa vola metoda validate(), ktora
	 * kontroluje, ci kazda bunka riadka je v takom formate
	 * a tvare, aky ocakavaju databaza a programator
	 *
	 * @return boolean Vykonala sa query na DB spravne?
	 */

	public final function save ()
	{
		if ($this -> isNew)
		{
			if (!Framework::module('database') -> insert (static::tableName (), $this -> storage) -> send ())
			return false;
			
			$this -> isNew = false;
			$this -> executeUpdate = false;
			if ($this -> primaryKey)
			$this -> activePrimaryKey = Framework::module('database') -> getAutoIncrementValue ();
			return true;
		}
		else
		{
			if (!Framework::module('database') -> update (static::tableName (), $this -> storage) -> where (static::$primaryKey, $this -> activePrimaryKey) -> send ())
			return false;

			$this -> executeUpdate = false;
			return true;
		}
	}



	/**
	 * Zmazanie riadku z databazy
	 *
	 * Volanie tejto metody zapricini nenavratne zmazanie
	 * daneho riadku z databazy. Tento stav sa uz nebude
	 * dat vratit spat.
	 *
	 * @return boolean Vykonala sa query na DB spravne?
	 * @since 0.6
	 */

	public final function delete ()
	{
		$query = Framework::module('database') -> delete (static::tableName ());
		if (static::$primaryKey)
			$query -> where (static::$primaryKey, $this -> activePrimaryKey);
			else
		if ($query -> send ())
		{
			unset ($this);
			return true;
		}
		else return false;
	}



	/**
	 * Aktualny nazov tabulky, ktorej zaznamy spracuvavame?
	 *
	 * Tato metoda ziska nazov aktualnej triedy a na zaklade toho
	 * odvodi nazov tabulky, s ktorou pracuje dany model.
	 *
	 * @return string
	 */

	public final static function tableName ()
	{
		return substr (get_called_class (), strrpos (get_called_class (), '\\') + 1);
	}



	/**
	 * Vrati novu instaciu ModelHandler-a
	 *
	 * Tato metoda vytvori a vrati novy objekt triedy ModelHandler,
	 * ktora zabezpecuje vyber riadkov tabulky na zaklade predanych
	 * pravidiel. Ak nie je predany argument model, tak 
	 *
	 * @return object ModelHandler
	 */

	public final static function model ()
	{
		if (empty (static::$fieldData))
		{
			$classname = '\\Opiner\\Model\\' . static::tableName ();
			$obj = new $classname;
			unset ($classname, $obj);
		}
		return new ModelHandler (static::tableName (), static::$fieldData, static::$primaryKey);
	}
}

?>