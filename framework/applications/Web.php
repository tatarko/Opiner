<?php

namespace Opiner\Application;
use Opiner\Opiner;

/**
 * Basic web application
 *
 * @author Tomas Tatarko <tomas@tatarko.sk>
 * @link https://github.com/tatarko/Opiner
 * @copyright Copyright &copy; 2012-2013 Tomas Tatarko
 * @license GPL 3
 * @since 0.1
 */
class Web extends \Opiner\Application {

	public function run() {

		$this->getComponentByType(Opiner::getClassByAlias('component.router'), self::MISSING_COMPONENT_THROW);
	}
}
?>