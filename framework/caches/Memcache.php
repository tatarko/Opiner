<?php

namespace Opiner\Cache;
use Opiner\Cache;

/**
 * @author Tomas Tatarko <tomas@tatarko.sk>
 * @link https://github.com/tatarko/Opiner
 * @copyright Copyright &copy; 2012-2013 Tomas Tatarko
 * @license GPL 3
 * @since 0.1
 * 
 * @property int $compressed Correct flag for setting/getting values from/to memcache
 */
class Memcache extends Cache {

	/**
	 * Default hostname of server to connect
	 */
	const DEFAULT_SERVER = 'locahost';

	/**
	 * Default port to connect
	 */
	const DEFAULT_PORT = 11211;

	/**
	 * @var \Memcache Memcache connection 
	 */
	protected $memcache;

	/**
	 * Set new variable (do nothing on existence)
	 * @param string $key
	 * @param mixed $value
	 * @param int $time
	 * @return bool
	 */
	public function add($key, $value, $time = null) {

		return $this->memcache->add($key, $value, $this->compressed, $this->adjustTime($time));
	}

	/**
	 * Connect to cache mechanism (or check something)
	 * @param mixed[] $settings Stack of settings (given by app configuration)
	 * @return bool
	 */
	public function connect($settings) {

		$this->memcache = new \Memcache;
		return $this->memcache->connect(
				@$settings['server'] ?: 'locahost',
				@$settings['server'] ?: 'locahost'
		);
	}

	/**
	 * Delete variable from cache
	 * @param string $key
	 * @return bool
	 */
	public function delete($key) {

		return $this->memcache->delete($key);
	}

	/**
	 * Delete all cached values
	 * @return bool
	 */
	public function flush() {

		return $this->memcache->flush();
	}

	/**
	 * Get variable from cache
	 * @param string $key
	 * @return mixed
	 */
	public function get($key) {

		return $this->memcache->get($key, $this->compressed);
	}

	/**
	 * Set new variable (replace on existence)
	 * @param string $key
	 * @param mixed $value
	 * @param int $time
	 * @return bool
	 */
	public function set($key, $value, $time = null) {

		return $this->memcache->set($key, $value, $this->compressed, $this->adjustTime($time));
	}

	/**
	 * Gettings correct flag for setting/getting values from/to memcache
	 * @return type
	 */
	public function getCompressed() {

		return $this->compress ? 0 : 2;
	}

	/**
	 * Disconnect from memcache
	 */
	public function disconnect() {

		$this->memcache->close();
	}
}
?>