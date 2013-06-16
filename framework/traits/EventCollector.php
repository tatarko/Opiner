<?php

namespace Opiner\Traits;
use Opiner\Interfaces\Event as IEvent;
use Opiner\Exception;
use Opiner\Exception\Event as EventException;

/**
 * Traits for creating, managing and executing events
 *
 * @author Tomas Tatarko <tomas@tatarko.sk>
 * @link https://github.com/tatarko/Opiner
 * @copyright Copyright &copy; 2012-2013 Tomas Tatarko
 * @license GPL 3
 * @since 0.1
 */
trait EventCollector {

	/**
	 * @var \Opiner\Interfaces\Event[eventName][]
	 */
	protected $_events = [];

	/**
	 * Registers new event for specific action
	 * @param string $eventName Specific name of action (event)
	 * @param \Opiner\Interfaces\Event $event Instance of \Opiner\Interfaces\Event or callable variable
	 */
	public function addEvent($eventName, $event) {

		if(!is_string($eventName))
			throw new Exception('Type must me string variable', 112);

		$eventName = strtolower($eventName);

		if($event instanceof IEvent || is_callable($event))
			$this->_events[$eventName] = $event;
			else throw new Exception('Not valid event', 113);
	}

	/**
	 * Execute all actions assigned to event
	 * @param string $type
	 * @return boolean
	 */
	protected function invokeEvent($eventName) {

		if(!is_string($eventName))
			throw new Exception('Type must me string variable', 112);

		$eventName = strtolower($eventName);

		if(!isset($this->_events[$eventName]) || empty($this->_events[$eventName]))
			return true;

		try {

			foreach($this->events[$eventName] as $event) {

				if($event instanceof IEvent)
					$event->run($this, $eventName);
					else call_user_func_array ($event, [$this, $eventName]);
			}

			return true;
		}
		catch(EventException $e) {

			return false;
		}
	}
}