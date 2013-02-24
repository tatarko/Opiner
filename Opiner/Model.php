<?php

namespace Opiner;

/**
 * Spravca modelu, tvorca queries
 *
 * Tato trieda je funkcnym rozsirenym logiky modelovej
 * spoluprace s databazou. Kym objekty abstraktnej
 * triedy Model predstavuju vzdy iba jeden riadok tabulky,
 * tak objekty tejto triedy sluzia na tvorenie
 * querov na databazu a vytahovanie jednotlivych
 * riadkov tabulky uz ako objetov dcerskych tried
 * abstraktnej triedy Model.
 *
 * Cela logika tejto triedy sa da rozdelit na dve casti.
 * Konkretne na volenie podmienok vyberu riadkov a ich
 * naslednom vytahovani z databazy.
 *
 * @author Tomas Tatarko
 * @since 0.5
 */

class Model extends Object {

	/**
	 * @var string Nazov tabulky v databaze
	 */
	protected $tableName;

	/**
	 * @var string Nazov triedy samotneho Active Record-u
	 */
	protected $className;

	/**
	 * @var array Informacie o bunkach tabulky
	 */
	protected static $fields = array();

	/**
	 * @var array Primarne kluce tabulky
	 */
	protected static $primaryKeys = array();

	/**
	 * @var array Podmienky vyberu dat z tabulky
	 */
	protected $conditions = array();

	/**
	 * @var array Sposob zoradenia vysledkov
	 */
	protected $order = array();

	/**
	 * @var int Maximalny pocet vratenych vysledkov
	 */
	protected $limit = 1000;

	/**
	 * @var int Pocet preskocenych vysledkov
	 */
	protected $offset = 0;

	/**
	 * @var array Pomenovanie rozhrania/scenare
	 */
	protected $scopes = array();

	/**
	 * @var bool|array Docasne skladisko dat pre ActiveRecord
	 */
	private $metaData = false;



	/**
	 * Vytvorenie noveho objektu
	 *
	 * V prvom kroku sa novemu objektu nastavia jeho
	 * premenne na zaklade vstupnych argumentov. Nasledne
	 * sa kontroluje, ci hladany model vobec existuje a ak nie,
	 * tak sa vyhodi vynimka, a teda konci kompilovanie frameworku.
	 * Nasledne sa pokracuje nacitavanim deklarovanych scopes,
	 * co je zhrnutie viacerych podmienok pod jeden nazov.
	 * Na zaver sa samotny novo vytvoreny objekt vrati.
	 *
	 * @param Opiner\ActiveRecord Ukazkovy objekt
	 * @return Opiner\Model
	 * @throws Opiner\Exception Ak vstupny argument nie je instaciou ActiveRecord triedy
	 */

	public function __construct(ActiveRecord $activeRecord) {
		
		if(!$activeRecord instanceof ActiveRecord)
			throw new Exception(get_class($activeRecord), 302);
		
		$this->tableName	= $activeRecord->getTableName();
		$this->className	= get_class($activeRecord);

		if(!isset(self::$fields[$this->tableName])) {
		
			self::$fields[$this->tableName]			= array();
			self::$primaryKeys[$this->tableName]		= array();
			
			foreach(Framework::module('database')->getFieldList($this->tableName) as $field) {
				
				self::$fields[$this->tableName][$field['Field']] = new TableField($field);
				
				if(self::$fields[$this->tableName][$field['Field']]->isPrimaryKey())
					self::$primaryKeys[$this->tableName][] = $field['Field'];
			}
		}
		
		foreach(get_class_methods($this->className) as $method)
		if(substr($method, 0, 5) == 'scope' and $method !== 'scope')
		$this->scopes[strtolower(substr($method, 5))] = $method;
		
		return $this;
	}



	/**
	 * Volanie jednotlivych scopes
	 *
	 * Tato metoda zisti, ci volany scope vobec existuje. Ak nie,
	 * tak vyhodi vynimku, co zapricini ukoncenie kompilovania
	 * celeho frameworku. Ak scope existuje, tak ho zavola
	 * a nasledne podla navratovej hodnoty aj nastavi podmienky,
	 * urci limit, zoradzovanie atd...
	 *
	 * @param string Ktory scope bude volany
	 * @param array Parametre volaneho scopu
	 * @return Opiner\Model
	 */

	public function __call($method, $params) {
		
		$method = strtolower($method);
		if(!isset($this->scopes[$method]))
		throw new Exception($method . '|' . $this->tableName, 303);
		
		foreach($params as $index => $value)
		$params[$index] = var_export($value, true);
		eval('$this->params = ' . $this->className . '::' . $this->scopes[$method] . '(' . implode(', ', $params) . ');');

		return $this;
	}



	/**
	 * Prida nove podmienky vyberu
	 *
	 * Aktualne pole podmienok vyberu dat z databazy zluci
	 * s polom predanym v argumente tejto metody. Ak sa
	 * programator pokusi urobit podmienku na neexistujucu
	 * bunku tabulky, dochadza k odoslaniu vynimky, a tym
	 * padom k ukonceniu kompilovania frameworku.
	 *
	 * @param array Pole podmienok
	 * @return Opiner\Model
	 */

	public function conditions($argument) {
		
		if(!is_array($argument)) return $this;
		foreach($argument as $index => $value) {
			
			$field = current(explode('#', $index));
			if(!isset(self::$fields[$this->tableName][$field]))
			throw new Exception($index, 300);
			$this->conditions[$index] = $value;
		}
		return $this;
	}



	/**
	 * Prida jednu samotnu podmienku
	 *
	 * Tato metoda vlastne len zavola metodu conditions()
	 * s ocakavanym argumentom typu array.
	 *
	 * @param string Nazov bunky
	 * @param mixed Ocakavana hodnota tej bunky
	 * @return Opiner\Model
	 */

	public function condition($field, $value) {
		
		return $this->conditions(array($field => $value));
	}



	/**
	 * Premaze pole podmienok
	 *
	 * Ak potrebuje programator premazat pole podmienok skor
	 * nastavenych, tak mu na to postaci jednoduche volanie
	 * tejto metody bez argumentov
	 *
	 * @return Opiner\Model
	 */

	public function clearConditions() {

		$this->conditions = array();
		return $this;
	}



	/**
	 * Nastavi pole podmienok bez ohladu na predchadzuje podmienky
	 *
	 * Ak programator potrebuje nastavit podmienky odznova, tak
	 * mu staci zavolat tuto metodu. Ta najprv zavola metodu
	 * clearConditions(), co sposobi premazanie aktualne
	 * zvolenych podmienok a potom nasledne zavola metodu
	 * conditions(), ktora prida nove podmienky predane v argumente
	 * tejto metody.
	 *
	 * @param array Ake podmienky maju byt nastavene?
	 * @return Opiner\Model
	 */

	public function setConditions($conditions) {
		
		$this->clearConditions()->conditions($conditions);
	}



	/**
	 * Nastavi MySQL limit podla predanej strany
	 *
	 * Pri strankovanom vybere riadkov tabulky
	 * staci programatorovi zavolat tuto funkciu,
	 * do argumentov predat aktualnu stranu
	 * a pocet poloziek na jednu stranu. Tato metoda
	 * nasledne nastavi pri tvoreni poziadavky
	 * na databazu spravny limit aj offset
	 *
	 * @param int Aktualna strana
	 * @param int Limit poloziek na jednu stranu
	 * @return Opiner\Model
	 */

	public function setLimitByPage($page, $limit) {
		
		$this->limit	= max(intval($limit), 1);
		$this->offset	= (max(intval($page), 1) - 1) * $this->limit;
		return $this;
	}



	/**
	 * Nastavi limit poloziek
	 *
	 * Pri ziskavani poloziek z databazy sa nacita
	 * zvoleny maximalny pocet riadkov
	 *
	 * @param int Ocakavany pocet poloziek
	 * @return Opiner\Model
	 */

	public function limit($limit) {
		
		$this->limit = max(intval($limit), 1);
		return $this;
	}



	/**
	 * Nastavi offset poloziek
	 *
	 * Pri ziskavani poloziek z databazy sa nacitaju
	 * polozky od i-tej pozicie. To znamena ze pocet
	 * poloziek predany ako argument tejto metody sa
	 * pri vypise poloziek vynecha.
	 *
	 * @param int Ocakavany offset poloziek
	 * @return Opiner\Model
	 */

	public function offset($offset) {
		
		$this->offset = max(intval($offset), 0);
		return $this;
	}



	/**
	 * Urci podla coho sa budu zoradzovat vysledky
	 *
	 * Tato metoda vlastne vytvori ORDER BY klauzulu
	 * poziadavky posielanej na databazu
	 * 
	 * @param string Podla ktorej bunky zoradit vysledky
	 * @param string V akom poradi?
	 * @return object
	 */

	public function order($field, $by = 'asc') {
		
		if(!isset($this->fields[$field]))
		throw new Exception($index, 305, $this->tableName);
		$this->order = $field . '#' . $by;
		return $this;
	}

	

	/**
	 * Vrati defaultne hodnoty pre riadok tabulky.
	 * 
	 * Defaultne hodnoty mozu byt zmene na aktualne
	 * v pripade, ze sa vykonava vyber riadkov z modela.
	 * @return array Pole hodnot
	 * @internal Callback pre __construct v ActiveRecord
	 */
	
	public function getValues() {
		
		foreach(self::$fields[$this->tableName] as $index => $field)
			$return[$index] = $field->getDefaultValue();
		
		if($this->metaData) {
			foreach($this->metaData as $index => $value)
				$return[$index] = $value;
		}

		return $return;
	}

	

	/**
	 * Vrati zoznam primarnych klucov
	 * @return array Pole primarnych klucov
	 */
	
	public function getPrimaryKeys() {
		
		foreach(self::$fields[$this->tableName] as $index => $field)
			if($field->isPrimaryKey())
				$return[] = $index;

		return isset($return) ? $return : array();
	}

	

	/**
	 * Vrati nazov bunky, ktora funguje ako auto_increment
	 * @return string|bool
	 */
	
	public function getAutoIncrementField() {
		
		foreach(self::$fields[$this->tableName] as $index => $field)
			if($field->isAutoIncrement())
				return $index;
		
		return false;
	}


	/**
	 * Vrati jeden konkretny zaznam s unikatnym ID(primary key)
	 *
	 * Tato metoda narozdiel od find() vrati vzdy iba jeden jediny
	 * zaznam. Na vyhladanie tohto zaznamu sa pri tom pouziju
	 * vsetky podmienky, scopy(a podobne), ktore boli uz skor nastavene.
	 *
	 * @param int Aktualna hodnota unikatne PRIMARY KEY 
	 * @return bool|Opiner\ActiveRecord Podla uspesnosti najdenia riadku
	 */

	public function findByPk($id) {
		
		if(count(self::$primaryKeys[$this->tableName]) == 1)
			$this->condition(current(self::$primaryKeys[$this->tableName]), (int)$id);
		else foreach(self::$primaryKeys[$this->tableName] as $pk)
			if(isset($id[$pk]))
				$this->condition($pk, (int)$id[$pk]);
		
		$this->metaData = Framework::module('database')
				->select()
				->table($this->tableName)
				->where($this->conditions)
				->fetchRow();

		if(!$this->metaData)
			return false;
			
		$record = new $this->className($this);
		$this->metaData = false;
		return $record;
	}



	/**
	 * Vrati vsetky zaznamy
	 *
	 * Tato metoda vrati pole objektov daneho modelu,
	 * ktore vyvohuju zvolenym podmienkam. Ak sa ziaden
	 * takyto riadok nenajde, vrati sa aj napriek tomu
	 * prazdne pole! Nenastava situacia, aby sa vratila
	 * false hodnota, vyhodila vynimka, ci nieco podobne.
	 *
	 * @return array Pole objetov daneho modelu
	 */

	public function find()
	{
		$return =[];
		foreach(Framework::module('database')
			-> select()
			-> table($this->tableName)
			-> where($this->conditions)
			-> order($this->order)
			-> limit($this->limit, $this->offset)
			-> fetch() as $row)
		$return[] = new $this->className($row);
		return $return;
	}



	/**
	 * Vrati vsetky zaznamy ako JSON
	 *
	 * Tato metoda zoberie vsetky mozne vysledne riadky
	 * a zakoduje ich do JSON kodu, ktory moze byt dalej pouzity.
	 *
	 * @return string
	 * @since 0.6
	 */

	public function getAsJson()
	{
		$query = Framework::module('database')
			-> select()
			-> table($this->tableName);
		if(!empty($this->conditions)) $query->where($this->conditions);
		if(!empty($this->order)) $query->order($this->order);
		return $query->limit($this->limit, $this->offset)->fetchAsJson();
	}



	/**
	 * Ulozi vsetky zaznamy do json suboru
	 *
	 * Do suboru, ktoreho adresa je predana v prvom
	 * argumente tejto metody sa ulozi JSON so vsetkymi
	 * riadkami, ktore vyhovuju navolenych podmienkam
	 *
	 * @param string Adresa suboru, do ktoreho sa maju zapisat data
	 * @return object
	 * @since 0.6
	 */

	public function getIntoJsonFile($file)
	{
		file_put_contents($file, $this->getAsJson());
		return $this;
	}



	/**
	 * Vrati vsetky zaznamy ako CSV
	 *
	 * Tato metoda zoberie vsetky mozne vysledne riadky
	 * a zakoduje ich do CSV kodu, ktory moze byt dalej pouzity.
	 *
	 * @param string Retazec oddelujuci bunky v ramci riadku
	 * @return string
	 * @since 0.6
	 */

	public function getAsCsv($delimiter = ';')
	{
		$query = Framework::module('database')
			-> select()
			-> table($this->tableName);
		if(!empty($this->conditions)) $query->where($this->conditions);
		if(!empty($this->order)) $query->order($this->order);
		return $query->limit($this->limit, $this->offset)->fetchAsCsv($delimiter);
	}



	/**
	 * Ulozi vsetky zaznamy do csv suboru
	 *
	 * Do suboru, ktoreho adresa je predana v prvom
	 * argumente tejto metody sa ulozi struktura so vsetkymi
	 * riadkami, ktore vyhovuju navolenych podmienkam
	 *
	 * @param string Adresa suboru, do ktoreho sa maju zapisat data
	 * @param string Retazec oddelujuci jednotlive bunky v ramci riadku
	 * @return object
	 * @since 0.6
	 */

	public function getIntoCsvFile($file, $delimiter = ';')
	{
		file_put_contents($file, $this->getAsCsv($delimiter));
		return $this;
	}	
}
?>