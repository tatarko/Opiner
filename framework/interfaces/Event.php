<?php

namespace Opiner\Interfaces;

/**
 * Description of Event
 *
 * @author Tomas Tatarko <tomas@tatarko.sk>
 * @link https://github.com/tatarko/Opiner
 * @copyright Copyright &copy; 2012-2013 Tomas Tatarko
 * @license GPL 3
 * @since 0.1
 */
interface Event {

	public function run($object, $type);
}

?>
