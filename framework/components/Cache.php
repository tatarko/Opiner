<?php

namespace Opiner\Component;
use Opiner\Interfaces\Cache as ICache;
use Opiner\Opiner;
use \Exception;

/**
 * Cache component
 *
 * @author Tomas Tatarko <tomas@tatarko.sk>
 * @link https://github.com/tatarko/Opiner
 * @copyright Copyright &copy; 2012-2013 Tomas Tatarko
 * @license GPL 3
 * @since 0.1
 */
class Cache extends \Opiner\Component implements ICache {

	/**
	 * @var \Opiner\Cache
	 */
	protected $cache;

	/**
	 * Cache initialization
	 * @param mixed[] $settings
	 * @throws Exception
	 */
	public function init($settings = null) {
		parent::init($settings);

		$interface		= Opiner::getClassByAlias('opiner.interfaces.cache');
		$className		= Opiner::getClassByAlias('cache', $this->fetchConfig('mechanism', 'filecache'));
		$this->cache	= new $className;

		if(!$this->cache instanceof $interface) {

			$this->isInitialized = false;
			throw new Exception('Given class name is not valid instance of Cache interface', 106);
		}
		elseif(!$this->cache->connect($this->settings)) {

			$this->isInitialized = false;
			throw new Exception('Could not connect to Cache mechanism', 107);
		}
	}

	/**
	 * Set new variable (do nothing on existence)
	 * @param string $key
	 * @param mixed $value
	 * @param int $time
	 * @return bool
	 */
	public function add($key, $value, $time = null) {

		$this->initCheck();
		return $this->cache->add($key, $value, $time);
	}

	/**
	 * Delete variable from cache
	 * @param string $key
	 * @return bool
	 */
	public function delete($key) {

		$this->initCheck();
		return $this->cache->delete($key);
	}

	/**
	 * Delete all cached variables
	 * @return bool
	 */
	public function flush() {

		$this->initCheck();
		return $this->cache->flush();
	}

	/**
	 * Get variable from cache
	 * @param string $key
	 * @return mixed
	 */
	public function get($key) {

		$this->initCheck();
		return $this->cache->get($key);
	}

	/**
	 * Set new variable (replace on existence)
	 * @param string $key
	 * @param mixed $value
	 * @param int $time
	 * @return bool
	 */
	public function set($key, $value, $time = null) {

		$this->initCheck();
		return $this->cache->set($key, $value, $time);
	}

	/**
	 * Overriding php magic method for setting object's properties
	 * @param string $key
	 * @param mixed $value
	 */
	public function __set($key, $value) {

		if(!$this->set($key, $value)) {

			throw new Exception('Unable to store value to cache', 108);
		}
	}

	/**
	 * Overriding php magic method for getting object's properties
	 * @param string $key
	 * @return mixed
	 */
	public function __get($key) {

		return $this->get($key);
	}

	/**
	 * Gets value from cache and if it doesn't exist, call
	 * required function with given parameters and store
	 * result in cache.
	 * 
	 * @param string $function
	 * @param mixed[] $args
	 * @param int $expire
	 * @return mixed
	 */
	public function byFunctionCall($function, $args = null, $expire = null) {

		$key = md5('function:' . (string)$function . serialize($args));

		if(($return = $this->get($key)) !== false) {

			return $return;
		}

		$return = call_user_func_array($function, (array)$args);
		$this->set($key, $return, $expire);
		return $return;
	}

	/**
	 * Gets value from cache and if it doesn't exist, call
	 * required method at given object with given parameters
	 * and store result in cache.
	 * 
	 * @param object $object
	 * @param string $method
	 * @param mixed[] $args
	 * @param int $expire
	 * @return mixed
	 */
	public function byMethodCall($object, $method, $args = null, $expire = null) {

		$key = md5('method:' . serialize($object) . $method . serialize($args));

		if(($return = $this->get($key)) !== false) {

			return $return;
		}

		$return = call_user_func_array([$object, $method], (array)$args);
		$this->set($key, $return, $expire);
		return $return;
	}

	/**
	 * Gets value from cache and if it doesn't exist, call given
	 * function and store result in cache.
	 * 
	 * @param string $key
	 * @param function() $function
	 * @param int $expire
	 * @return mixed
	 */
	public function byFunction($key, $function, $expire = null) {

		if(($return = $this->get($key)) !== false) {

			return $return;
		}

		$return = $function();
		$this->set($key, $return, $expire);
		return $return;
	}

	/**
	 * Connect to cache ({@link:init()} will be called)
	 * @return bool
	 */
	public function connect($settings) {

		if(!$this->isInitialized) {

			$this->init();
		}

		return $this->isInitialized;
	}

	/**
	 * Disconnect cache mechanism
	 */
	public function disconnect() {

		$this->cache->disconnect();
	}
}

?>