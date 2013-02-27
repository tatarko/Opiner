<?php

namespace Opiner\Validator;

/**
 * Validaovanie ciselnych hodnot
 *
 * @author Tomas Tatarko <tomas@tatarko.sk>
 * @link https://github.com/tatarko/Opiner
 * @copyright Copyright &copy; 2012-2013 Tomas Tatarko
 * @license GPL 3
 * @since 0.6
 */
class Integer extends \Opiner\Validator {

	/**
	 * @var int Minimalna mozna hodnota
	 */
	protected $min;

	/**
	 * @var int Maximalna mozna hodnota
	 */
	protected $max;



	/**
	 * Skontroluje hodnotu
	 * 
	 * Skript na zaklade filtrovania hodnoty zisti,
	 * ci kontrolovana hodnota je cislom. Ak ano, tak
	 * kontroluje aj, ci je v rozmedzi minimalnej
	 * a maximalnej pripustnej hodnoty (ak su zadane).
	 * @return boolean
	 */
	public function validate() {
		
		$status	= true;
		$int	= filter_var($this->value, FILTER_VALIDATE_INT) == $this->value ? true : false;
		
		if($int != $this->value) {
			$this->addError('Value is not an integer!');
			return false;
		}
		
		if($this->max !== null && $int > $this->max) {
			$this->addError('Value is higher than allowed!');
			$status = false;
		}
		
		if($this->min !== null && $int < $this->min) {
			$this->addError('Value is lower than allowed!');
			$status = false;
		}
		
		return $this->isCorrect = $status;
	}
	
	/**
	 * Vrati spravne naformatovanu hodnotu
	 * @return int
	 * @throws Opiner\Exception Ak hodnota neprebehla validaciou (vobec alebo uspesne)
	 */
	public function getFilteredValue() {
	
		return (int)filter_var($this->value, FILTER_VALIDATE_INT);
	}
}

?>