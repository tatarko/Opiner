<?php

namespace Opiner\Traits;
use Opiner\Interfaces\Event as Event;
use Opiner\EventCollector as EventCollectorClass;
use Opiner\Event\CallableEvent;
use Opiner\Exception;

/**
 * Description of EventCollector
 *
 * @author Tomas Tatarko <tomas@tatarko.sk>
 * @link https://github.com/tatarko/Opiner
 * @copyright Copyright &copy; 2012-2013 Tomas Tatarko
 * @license GPL 3
 * @since 0.1
 */
trait EventCollector {

	/**
	 * @var \Opiner\EventCollector 
	 */
	protected $_eventCollector;

	/**
	 * Creates instance of event collector
	 */
	protected function _loadEventCollector() {

		if(!$this->_eventCollector instanceof EventCollectorClass) {

			$this->_eventCollector = new EventCollectorClass($this);
		}
		
		return $this->_eventCollector;
	}

	/**
	 * Registers new event for specific action
	 * @param string $type
	 * @param \Opiner\Interfaces\Event $event
	 */
	public function registerEvent($type, $event) {

		if(!is_string($type)) {

			throw new Exception('Type must me string variable', 112);
		}

		$this->_loadEventCollector();

		if($event instanceof Event) {

			$this->_eventCollector->add($type, $this->_eventCollector);
		}
		elseif(is_callable($event)) {

			$this->_eventCollector->add($type, new CallableEvent($event));
		}
		else {

			throw new Exception('Not valid event', 113);
		}
	}

	/**
	 * Run events asociated to requested action
	 * @param string $type
	 * @return boolean
	 */
	protected function invokeEvent($type) {

		if(!is_string($type)) {

			throw new Exception('Type must me string variable', 112);
		}

		$this->_loadEventCollector();

		return $this->_eventCollector->run($type);
	}
}