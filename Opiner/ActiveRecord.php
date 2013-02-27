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

abstract class ActiveRecord extends Object {

	/**
	 * @var array Pole s hodnotami jednotlivych fieldov
	 */
	protected $storage = array();
	
	/**
	 * @var array Hodnoty primarnych klucov pre dany row
	 */
	protected $primaryKeys = array();
	
	/**
	 * @var bool Je tento zaznam novy?
	 */
	protected $isNew = false;
	
	/**
	 * @var bool Hodnoty primarnych klucov pre dany row
	 */
	protected $executeUpdate = false;
	
	/**
	 * @var Opiner\Model Model pre dany tok dat
	 */
	protected $model;



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
	 * @return Opiner\ActiveRecord
	 */

	public final function __construct(Model $model = null) {
		
		if($model !== null && $model instanceof Model) {
			$this->model = $model;
			$this->isNew = false;
		}
		else $this->model = new Model($this);
		
		$this->storage		= $this->model->getValues();
		$this->primaryKeys	= $this->model->getPrimaryKeys();

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

	public  function __set($field, $value)
	{
		// Je takyto field v danej tabulke?
		if(!key_exists($field, $this->storage))
		throw new Exception($field, 300);

		// Ako bude vyzerat nova hodnota, bude nutny update DB?		
		if($value !== $this->storage[$field]) $this->executeUpdate = true;
		$this->storage[$field] = $value;
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

	public final function __get($field)
	{
		if(!key_exists($field, $this->storage))
		throw new Exception($field, 300);
		return $this->storage[$field];
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

	public final function __isset($field)
	{
		return array_search($field, static::$fields) === false ? false : true;
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

	public final function __unset($field)
	{
		throw new Exception($field, 301);
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

	protected final function prepareStorage()
	{
		if(empty(static::$fields))
		static::prepareMeta();

		foreach(static::$fields as $field)
		$this->storage[$field] = static::$fieldData[$field]['default'];
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

	public final function save() {
		
		// Ak ide o novy zaznam
		if($this->isNew) {
			
			$query = Opiner::module('database')
					->insert(static::getTableName(), $this->storage)
					->send();
			
			if(!$query)
				return false;
			
			$this->isNew			= false;
			$this->executeUpdate	= false;
			
			if($field = $this->model->getAutoIncrementField())
				$this->storage[$field] = Opiner::module('database')->getAutoIncrementValue();

			return true;
		}
		else {
			
			$query = Opiner::module('database')->update(static::getTableName(), $this->storage);
			
			if(!empty($this->primaryKeys)) {
				foreach($this->primaryKeys as $pk)
					$where[$pk] = $this->storage[$pk];
				$query->where($where);
			}
			
			if(!$query->send())
				return false;

			$this->executeUpdate = false;
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

	public final function delete()
	{
		$query = Opiner::module('database')->delete(static::getTableName());
		if(static::$primaryKey)
			$query->where(static::$primaryKey, $this->activePrimaryKey);
			else
		if($query->send())
		{
			unset($this);
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

	public final static function getTableName() {
		
		if(strpos(get_called_class(), '\\') !== false)
			return substr(get_called_class(), strrpos(get_called_class(), '\\') + 1);
			else return get_called_class();
	}



	/**
	 * Vrati novu instaciu ModelHandler-a
	 *
	 * Tato metoda vytvori a vrati novy objekt triedy ModelHandler,
	 * ktora zabezpecuje vyber riadkov tabulky na zaklade predanych
	 * pravidiel. Ak nie je predany argument model, tak 
	 *
	 * @return Opiner/Model
	 */

	public final static function model() {
		
		$class = get_called_class();
		return new Model(new $class);
	}
}

?>