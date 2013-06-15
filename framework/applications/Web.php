<?php

namespace Opiner\Application;
use Opiner\Application;
use Opiner\Traits\EventCollector;

/**
 * Basic web application
 *
 * @author Tomas Tatarko <tomas@tatarko.sk>
 * @link https://github.com/tatarko/Opiner
 * @copyright Copyright &copy; 2012-2013 Tomas Tatarko
 * @license GPL 3
 * @since 0.1
 */
class Web extends Application {

	use EventCollector;

	public function run() {

		$this->registerEvent('hello', function() {
			echo 'Hello World!';
		});
		$this->invokeEvent('hello');
	}
}
?>