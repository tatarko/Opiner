<?php

namespace Opiner;
use Exception as _Exception;

/**
 * Description of Exception
 *
 * @author Tomas Tatarko <tomas@tatarko.sk>
 * @link https://github.com/tatarko/Opiner
 * @copyright Copyright &copy; 2012-2013 Tomas Tatarko
 * @license GPL 3
 * @since 0.1
 */
class Exception extends _Exception {

	public function __toString() {
		
		return sprintf('<p><strong>[%d] %s</strong> on line %d in file %s', $this->getCode(), $this->getMessage(), $this->getLine(), $this->getFile());
	}
}

?>