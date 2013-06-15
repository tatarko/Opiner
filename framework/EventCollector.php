<?php

namespace Opiner;
use Opiner\Interfaces\Event as IEvent;
use Opiner\Exception\Event as EEvent;

/**
 * @author Tomas Tatarko <tomas@tatarko.sk>
 * @link https://github.com/tatarko/Opiner
 * @copyright Copyright &copy; 2012-2013 Tomas Tatarko
 * @license GPL 3
 * @since 0.1
 */
class EventCollector extends Object {

	/**
	 * @var \Opiner\Interfaces\Event[][] 
	 */
	protected $events = [];

	/**
	 * @var \Opiner\Object Instance of object which handles events
	 */
	protected $parent;

	/**
	 * Sets object that handles events
	 * @param \Opiner\Object $object
	 */
	public function __construct(Object $object) {
		parent::__construct();

		$this->parent = $object;
	}

	/**
	 * Add event to stack
	 * @param string $type
	 * @param \Opiner\Interfaces\Event $event
	 */
	public function add($type, IEvent $event) {

		if(!is_string($type)) {

			throw new Exception('Type must me string variable', 112);
		}

		$this->events[strtolower($type)][] = $event;
	}

	/**
	 * Run all events
	 * @param string $type
	 * @return boolean
	 */
	public function run($type) {

		if(!is_string($type)) {

			throw new Exception('Type must me string variable', 112);
		}

		$type = strtolower($type);

		if(!isset($this->events[$type])) {

			return true;
		}

		try {

			foreach($this->events[$type] as $event) {

				$event->run($this->parent, $type);
			}

			return true;
		}
		catch(EEvent $e) {

			return false;
		}
	}
}
