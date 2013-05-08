<?php

namespace Opiner\Interfaces;

/**
 * Basic commands for Application components
 *
 * @author Tomas Tatarko <tomas@tatarko.sk>
 * @link https://github.com/tatarko/Opiner
 * @copyright Copyright &copy; 2012-2013 Tomas Tatarko
 * @license GPL 3
 * @since 0.1
 */
interface Component {

	/**
	 * Component initialization
	 */
	public function init($settings = null);

	/**
	 * Checks if component has already been initialized
	 * @return bool
	 */
	public function getIsInitialized();
}

?>