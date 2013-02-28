<?php

namespace Opiner\Validator;

/**
 * Validaovanie webovej adresy
 *
 * @author Tomas Tatarko <tomas@tatarko.sk>
 * @link https://github.com/tatarko/Opiner
 * @copyright Copyright &copy; 2012-2013 Tomas Tatarko
 * @license GPL 3
 * @since 0.6
 */
class Url extends \Opiner\Validator {

	/**
	 * @var string Prefiltrovana premenna obsahujuca (ne)naplatnu emailovu adresu
	 */
	private $validAddress;


	/**
	 * Skontroluje hodnotu
	 * @return bool
	 */
	public function validate() {
		
		$this->validAddress = filter_var($this->value, FILTER_VALIDATE_URL);
		
		if(!$this->validAddress)
			$this->addError('Value is not a valid web address');
			else $this->isCorrect = true;
			
		return $this->isCorrect;
	}



	/**
	 * Vrati spravne naformatovanu hodnotu
	 * @return string
	 */
	public function getFilteredValue() {
	
		return (string)$this->validAddress;
	}
}

?>