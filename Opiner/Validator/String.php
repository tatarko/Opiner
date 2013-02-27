<?php

namespace Opiner\Validator;

/**
 * Validovanie retazcov
 *
 * @author Tomas Tatarko <tomas@tatarko.sk>
 * @link https://github.com/tatarko/Opiner
 * @copyright Copyright &copy; 2012-2013 Tomas Tatarko
 * @license GPL 3
 * @since 0.6
 */
class String extends \Opiner\Validator {
	
	public function validate() {
		
		return true;
	}
	
	public function getFilteredValue() {
	
		return (string)$this->value;
	}
}

?>