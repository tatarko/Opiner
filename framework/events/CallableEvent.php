<?php

namespace Opiner\Event;
use Opiner\Event;
use Opiner\Exception;

/**
 * Description of CallableEvent
 *
 * @author Tomas Tatarko <tomas@tatarko.sk>
 * @link https://github.com/tatarko/Opiner
 * @copyright Copyright &copy; 2012-2013 Tomas Tatarko
 * @license GPL 3
 * @since 0.1
 */

class CallableEvent extends Event {

	/**
	 * @var callble function/method to be called 
	 */
	protected $callable;

	/**
	 * Creates an instance
	 * @param callable $callable
	 * @throws \Opiner\Exception
	 */
	public function __construct($callable) {
		parent::__construct();

		if(!is_callable($callable)) {

			throw new Exception('Variable must be callable', 114);
		}

		$this->callable = $callable;
	}

	/**
	 * Run requestd action
	 * @param \Opiner\Object $object Object that makes an event
	 * @param string $type Type of event
	 * @return mixed
	 */
	public function run($object, $type) {

		return call_user_func_array($this->callable, [$object, $type]);
	}
}

?>