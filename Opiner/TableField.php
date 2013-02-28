<?php

namespace Opiner;

/**
 * Meta informacie o bunke tabulky
 *
 * @author Tomas Tatarko
 * @since 0.6
 */

class TableField extends Object {

	/**
	 * @var string Presny nazov bunky v tabulke
	 */
	protected $field;

	/**
	 * @var string Nazov zobrazovany na stranke
	 */
	protected $label;

	/**
	 * @var string Zakladna hodnota pre tuto bunku
	 */
	protected $defaultValue;

	/**
	 * @var bool Je to auto_increment bunka?
	 */
	protected $autoIncrement;

	/**
	 * @var bool Moze byt bunka prazdna?
	 */
	protected $required;

	/**
	 * @var bool Moze byt bunka prazdna?
	 */
	protected $primaryKey;

	/**
	 * @var array Validatory pre tuto bunku
	 */
	protected $validators;

	public function __construct($meta) {
		
		$this->field			= $meta['Field'];
		$this->label			= ucwords(str_replace(array('.', '-', '_'), ' ', $meta['Field']));
		$this->defaultValue		= $meta['Default'];
		$this->autoIncrement	= strpos($meta['Extra'], 'auto_increment') === false ? false : true;
		$this->required			= $meta['Null'] == 'NO' ? true : false;
		$this->validators		= self::getValidatorList($meta['Type']);
		$this->primaryKey		= $meta['Key'] == 'PRI' ? true : false;
	}



	/**
	 * Vrati typovy nazov pre field
	 *
	 * Vstupom do tejto funkcie je typ niektoreho zo stlpcov tabulky
	 * tak, ako je deklarovany v samotnej mysql databaze. Vystupom
	 * funkcie je potom uz len jednoduche urcenie typu premennej
	 * bez ohladu na jej parametre(string, int, ...)
	 *
	 * @param string Typ podla MySQL
	 * @return string
	 */

	private static final function getValidatorList($type) {
		
		$validators = array();
		
		if(preg_match('#[a-z]*int\(([0-9]?)\)#ius', $type, $match)) {
			$validators['Integer']['min'] = -pow(2*8, $match[1] - 1);
			$validators['Integer']['max'] = pow(2*8, $match[1] - 1) - 1;
		}
		
		if(strpos($type, 'unsigned') !== false) {
			$validators['Integer']['max'] += $validators['Integer']['max'] + 1;
			$validators['Integer']['min'] = 0;
		}
		
		if(preg_match('#[a-z]*text#ius', $type, $match)) {
			$validators['String'] = array();
		}
		
		foreach($validators as $index => $params) {
			
			$name = '\\' . __NAMESPACE__ . '\\Validator\\' . $index;
			$validators[$index] = new $name($params);
		}
		
		return $validators;
		$type = strpos($type, '(') !== false ? substr($type, 0, strpos($type, '(')) : $type;
		switch($type)
		{
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
		}
	}
	
	/**
	 * Vrati nazov stlpca tabulky
	 * @return string
	 */
	public function getField() {
		
		return $this->field;
	}
	
	/**
	 * Vrati nazov bunky
	 * @return string
	 */
	public function getLabel() {
		
		return $this->label;
	}
	
	/**
	 * Vrati zakladnu hodnotu pre zaznamy tabulky
	 * @return string
	 */
	public function getDefaultValue() {
		
		return $this->defaultValue;
	}
	
	/**
	 * Je to primarny index?
	 * @return bool
	 */
	public function isPrimaryKey() {
		
		return $this->primaryKey;
	}
	
	/**
	 * Je to povinny field?
	 * @return bool
	 */
	public function isRequired() {
		
		return $this->required;
	}
	
	/**
	 * Je to auto_increment hodnota?
	 * @return bool
	 */
	public function isAutoIncrement() {
		
		return $this->autoIncrement;
	}
}
?>