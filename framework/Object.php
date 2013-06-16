<?php

namespace Opiner;

/**
 * Get public properties for an object
 * @param object $obj
 * @return string[]
 */
function getPublicProperties($obj) {

	return is_object($obj) ? array_keys(get_object_vars($obj)) : [];
}

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
	 * @var string[] List of public properties
	 */
	protected $_publicProperties = [];

	/**
	 * Constructor
	 * 
	 * Loads all public properties of class into prepared array
	 * @return self
	 */
	public function __construct() {

		$this->_publicProperties = getPublicProperties($this);
		return $this;
	}

	/**
	 * Setting variable of object
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value) {

		if(method_exists($this, 'set' . ucfirst($name)))
			call_user_func_array([$this, 'set' . ucfirst($name)], []);
		elseif(in_array($name, $this->_publicProperties))
			$this->$name = $value;
			else throw new Exception('Property "' . $name . '" is not defined', 116);
	}

	/**
	 * Getting variable of object
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name) {

		if(method_exists($this, 'get' . ucfirst($name)))
			return call_user_func_array([$this, 'get' . ucfirst($name)], []);
		elseif(in_array($name, $this->_publicProperties))
			return $this->$name;
			else throw new Exception('Property "' . $name . '" is not defined', 116);
	}

	/**
	 * Checking if variable of object exists
	 * @param string $name
	 * @return bool
	 */
	public function __isset($name) {

		if(in_array($name, $this->_publicProperties)
		|| (method_exists($this, 'get' . ucfirst($name)) && method_exists($this, 'set' . ucfirst($name))))
			return true;
			else return false;
	}
}
?>