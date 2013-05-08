<?php

namespace Opiner;

/**
 * Basic class for all objects used in framework
 * 
 * Defines basic magic methods such as __construct,
 * __set, __get, __isset.
 *
 * @author Tomas Tatarko <tomas@tatarko.sk>
 * @link https://github.com/tatarko/Opiner
 * @copyright Copyright &copy; 2012-2013 Tomas Tatarko
 * @license GPL 3
 * @since 0.1
 */
class Object {

	/**
	 * @var mixed[] Stack of values that can be changed publicly
	 */
	protected $stack = array();

	/**
	 * @var string[] List of public properties
	 */
	protected $_publicProperties = array();

	/**
	 * Constructor
	 * 
	 * Loads all public properties of class into prepared array
	 * @return self
	 */
	public function __construct() {

		$callback = function($object) {

			return get_object_vars($object);
		};

		$this->_publicProperties = $callback($this);

		return $this;
	}
	
	/**
	 * Setting variable of object
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value) {

		if(method_exists($this, 'set' . ucfirst($name))) {

			$this->{'set' . ucfirst($name)}($value);
		}
		elseif(in_array($name, $this->_publicProperties)) {

			$this->$name = $value;
		}
		elseif(in_array($name, $this->stack)) {

			$this->stack[$name]	= $value;
		}
		else {

			trigger_error('Property "' . $name . '" is not defined', E_USER_WARNING);
		}
	}

	/**
	 * Getting variable of object
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name) {

		if(method_exists($this, 'get' . ucfirst($name))) {

			return $this->{'get' . ucfirst($name)}();
		}
		elseif(in_array($name, $this->_publicProperties)) {

			return $this->$name;
		}
		elseif(in_array($name, $this->stack)) {

			return $this->stack[$name];
		}
		else {

			trigger_error('Property "' . $name . '" is not defined', E_USER_WARNING);
		}
	}

	/**
	 * Checking if variable of object exists
	 * @param string $name
	 * @return bool
	 */
	public function __isset($name) {

		if(method_exists($this, 'get' . ucfirst($name))
		|| in_array($name, $this->_publicProperties)
		|| in_array($name, $this->stack)) {

			return true;
		}
		else {

			return false;
		}
	}
}
?>