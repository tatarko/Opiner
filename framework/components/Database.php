<?php

namespace Opiner\Component;
use Opiner\Exception;

/**
 * Component respresenting connection do database
 *
 * @author Tomas Tatarko <tomas@tatarko.sk>
 * @link https://github.com/tatarko/Opiner
 * @copyright Copyright &copy; 2012-2013 Tomas Tatarko
 * @license GPL 3
 * @since 0.1
 */
class Database extends \Opiner\Component {

	/**
	 * @var \PDO Instance of database connection
	 */
	protected $connection;

	/**
	 * 
	 * @param mixed[] $settings
	 * @throws Exception If connection attempt fails
	 */
	public function init($settings = null) {
		parent::init($settings);
		
		try {

			$this->connection = new \PDO(
					$this->fetchConfig('connection',	'mysql:localhost'),
					$this->fetchConfig('username',		null),
					$this->fetchConfig('password',		null),
					$this->fetchConfig('driver',		null)
			);
		}
		catch(PDOException $e) {

			$this->isInitialized = false;
			throw new Exception('Could not connect to database', 121, $e);
		}
	}

	/**
	 * Calling methods on internal PDO object
	 * @param string $name
	 * @param mixed[] $arguments
	 * @return mixed
	 * @throws Exception If PDO does not contain requested method
	 */
	public function __call($name, $arguments) {

		$this->initCheck();

		if(!method_exists($this->connection, $name)) {

			throw new Exception('Compoment does not contain method "' . $name . '"', 122);
		}

		return call_user_func_array([$this->connection, $name], $arguments);
	}
}

?>