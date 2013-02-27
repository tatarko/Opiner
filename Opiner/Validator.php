<?php

namespace Opiner;

/**
 * Predpis metod, ktore musia implementovat vsetky validatory
 *
 * @author Tomas Tatarko <tomas@tatarko.sk>
 * @link https://github.com/tatarko/Opiner
 * @copyright Copyright &copy; 2012-2013 Tomas Tatarko
 * @license GPL 3
 * @since 0.6
 */
interface ValidatorInterface {

	/**
	 * Zistenie, ci hodnota zodpoveda validatoru
	 * @return bool
	 */
	public function validate();
	
	/**
	 * Ziskanie korektnej hodnoty, v spravnom formatovani
	 * @return mixed
	 */
	public function getFilteredValue();
	
	/**
	 * Nastavenie lubovolnej hodnoty do validatora
	 * @return Opiner\Validator
	 */
	public function setValue($value);
	
	/**
	 * Vrati zoznam vsetkych chybovych hlasok
	 * @return array
	 */
	public function getErrors();
	
	/**
	 * Ziska korektnu hodnotu
	 * @return mixed
	 * @throws Opiner\Exception Ak sa hodnota pokusi ziskat bez validovania
	 */
	public function getCorrectValue();
}



/**
 * Zakladna abstraktna trieda pre validatory hodnot
 *
 * @author Tomas Tatarko <tomas@tatarko.sk>
 * @link https://github.com/tatarko/Opiner
 * @copyright Copyright &copy; 2012-2013 Tomas Tatarko
 * @license GPL 3
 * @since 0.6
 */

abstract class Validator extends Object implements ValidatorInterface {

	/**
	 * @var mixed Hodnota, ktora bude validovana
	 */
	protected $value;

	/**
	 * @var bool Je hodnota validna?
	 */
	protected $isCorrect;

	/**
	 * @var array Zoznam veci, na zaklade ktorych hodnota nie je validna
	 */
	protected $errors;

	
	
	/**
	 * Vytvorenie objektu, nastavenie vnutornych premennych
	 * @param array $params Parametre objektu, ktore budu nastavene
	 * @return \Opiner\Validator
	 */

	public function __construct($params = null) {

		if(is_array($params) && !empty($params)) {
			
			foreach($params as $name => $param)
				$this->$name = $param;
		}

		$this->isCorrect	= false;
		$this->errors		= array();
		return $this;
	}



	/**
	 * Nastavi hodnotu na validovanie
	 * @param mixed $value
	 * @return \Opiner\Validator
	 */
	public function setValue($value) {
		
		$this->value		= $value;
		$this->isCorrect	= false;
		$this->errors		= array();
		return $this;
	}



	/**
	 * Prida novu chybovu hlasku
	 * @param string $error Hlaska o chybe
	 * @return \Opiner\Validator
	 */
	protected function addError($error) {
		
		$this->errors[] = $error;
		return $this;
	}



	/**
	 * Vrati zoznam chybovych hlasiek
	 * @return array
	 */
	public function getErrors() {
		
		return $this->errors;
	}



	/**
	 * Ziska korektnu hodnotu
	 * @return mixed
	 * @throws Opiner\Exception Ak sa hodnota pokusi ziskat bez validovania
	 */
	public function getCorrectValue() {
		
		if(!$this->isCorrect) {
			
			throw new Exception('', 304);
			return;
		}
		
		return $this->getFilteredValue();
	}
}

?>