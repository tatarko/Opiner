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
	 * @since 0.6
	 */
	protected $primaryKeys = array();
	
	/**
	 * @var bool Je tento zaznam novy?
	 */
	protected $isNew = true;
	
	/**
	 * @var bool|null Boli hodnoty zaznamu zvalidovane?
	 * @since 0.6
	 */
	protected $validated = null;
	
	/**
	 * @var array Povodne hodnoty zmenenych poli
	 * @since 0.6
	 */
	protected $backupStorage = array();
	
	/**
	 * @var array Povodne hodnoty zmenenych poli
	 * @since 0.6
	 */
	protected $errors = array();
	
	/**
	 * @var Opiner\Model Model pre dany tok dat
	 * @since 0.6
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
	 * @param Opiner\Model Data aktualneho riadka v tabulke
	 * @return Opiner\ActiveRecord
	 */

	public function __construct(Model $model = null) {
		
		if($model !== null && $model instanceof Model) {

			$this->model		= $model;
			$this->isNew		= false;
			$this->validated	= true;
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
	 * @throws Opiner\Exception Ak field v tabulke neexistuje
	 */

	public function __set($field, $value) {

		// Je takyto field v danej tabulke?
		if(!key_exists($field, $this->storage))
		throw new Exception($field, 300);

		// Ako bude vyzerat nova hodnota, bude nutny update DB?		
		if($value !== $this->storage[$field]) {
			
			$this->backupStorage[$field] = $value;
			$this->validated = null;
		}
		
		return $this->storage[$field] = $value;
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
	 * @throws Opiner\Exception Ak field v tabulke neexistuje
	 */

	public function __get($field) {
		
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
	 * @return bool
	 */

	public function __isset($field) {
		
		return key_exists($field, $this->storage) ? true : false;
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
	 * @throws Opiner\Exception Field z tabulky nie je mozne zmazat
	 */

	public function __unset($field) {
		
		throw new Exception($field, 301);
	}

	
	
	/**
	 * Vratenie vsetkuch zmien
	 * 
	 * Vsetky pozmenene hodnoty v jednotlivych bunkach
	 * tabulky vrati spat na ich povodne hodnoty.
	 * @return \Opiner\ActiveRecord
	 * @since 0.6
	 */
	public function reset() {
		
		$this->validated		= null;
		$this->storage			= array_merge($this->storage, $this->backupStorage);
		$this->backupStorage	= array();
		return $this;
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

	public function save() {
		
		// Kontrola validovania
		if($this->validated === null)
			$this->validate();
		
		if($this->validated !== true) {
			$this->validated = null;
			return false;
		}
		
		// Ak ide o novy zaznam
		if($this->isNew) {
			
			$query = Opiner::module('database')->insert(static::getTableName(), $this->storage)->send();
			
			if(!$query)
				return false;
			
			$this->isNew			= false;
			$this->backupStorage	= array();
			
			if($field = $this->model->getAutoIncrementField())
				$this->storage[$field] = Opiner::module('database')->getAutoIncrementValue();

			return true;
		}
		else {
			
			$query = Opiner::module('database')->update(static::getTableName(), $this->storage);
			$this->updateFilteringQuery($query);
			
			if(!$query->send())
				return false;

			$this->backupStorage = array();
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

	public function delete() {
		
		$query = Opiner::module('database')->delete(static::getTableName());
		$this->updateFilteringQuery($query);

		if($query->send()) {
			
			unset($this);
			return true;
		}
		else return false;
	}
	
	
	
	/**
	 * Vyfiltrovanie iba tych spravnych vysledkov pri aktualizovani
	 * a mazani zaznamov z tabulky
	 * @param \Opiner\Module\Database $query
	 * @return \Opiner\ActiveRecord
	 */
	protected function updateFilteringQuery(Module\Database $query) {
					
		// Ak mozeme vyhladavat na zaklade primarnych klucov
		if(!empty($this->primaryKeys)) {
			foreach($this->primaryKeys as $pk)
				$where[$pk] = isset($this->backupStorage[$pk]) ? $this->backupStorage[$pk] : $this->storage[$pk];
			$query->where($where);
		}
			
		// inak vyhladavane podla povodnych premennych
		else {
			foreach($this->storage as $field => $value)
				$where[$field] = isset($this->backupStorage[$field]) ? $this->backupStorage[$field] : $value;
			$query->where($where)->limit(1);
		}
		return $this;
	}



	/**
	 * Aktualny nazov tabulky, ktorej zaznamy spracuvavame?
	 *
	 * Tato metoda ziska nazov aktualnej triedy a na zaklade toho
	 * odvodi nazov tabulky, s ktorou pracuje dany model.
	 * @return string
	 */

	public static function getTableName() {
		
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
	 * @return Opiner\Model
	 */

	public static function model() {
		
		$class = get_called_class();
		return new Model(new $class);
	}



	/**
	 * Validovanie hodnot ActiveRecordu
	 * @return bool
	 */
	public function validate() {
		
		$this->validated = true;
		
		foreach($this->storage as $field => $value) {
			$result = $this->model->validate($field, $value);
			if(is_array($result)) {
				$this->validated = false;
				$this->errors = array_merge($this->errors, $result);
			}
		}
		return $this->validated;
	}
}

?>