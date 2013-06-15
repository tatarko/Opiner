<?php

namespace Opiner;
use Opiner\Interfaces\Cache as ICache;

/**
 * Basic methods for cache systems
 *
 * @author Tomas Tatarko <tomas@tatarko.sk>
 * @link https://github.com/tatarko/Opiner
 * @copyright Copyright &copy; 2012-2013 Tomas Tatarko
 * @license GPL 3
 * @since 0.1
 * @abstract
 */
abstract class Cache extends Object implements ICache {

	/**
	 * @var bool Compress data
	 */
	protected $compress = true;

	/**
	 * @var int Maximum allowed age of cache
	 */
	protected $maxAge = self::MONTH;

	/**
	 * @var int Default expiration time
	 */
	protected $defaultAge = self::HOUR;

	/**
	 * Get corrected time value for caching values
	 * @param int $time
	 * @return int
	 */
	public function adjustTime($time) {

		$time = $time === null ? $this->defaultAge : $time;
		return min($this->maxAge, max(0, (int)$time));
	}

	/**
	 * Checks if value can be cached
	 * @param mixed $var
	 * @return bool
	 */
	public function canCache($var) {

		switch(gettype($var)) {

			case 'resource':
			case '':
				return false;
				break;

			default:
				return true;
				break;
		}
	}

	/**
	 * Get max allowed age for cached values
	 * @return int
	 */
	public function getMaxAge() {
		
		return $this->maxAge;
	}

	/**
	 * Set max allowed age for cached values
	 * @param int $time Max allowed time in seconds
	 */
	public function setMaxAge($time) {

		$this->maxAge = $this->adjustTime($time);
	}

	/**
	 * Does current cache engine compress stored data?
	 * @return bool
	 */
	public function getCompress() {

		return $this->compress;
	}

	/**
	 * Sets default expiration time
	 * @param int $time
	 */
	public function setDefaultAge($time) {

		$this->defaultAge = $this->adjustTime($time);
	}

	/**
	 * Get default expiration time
	 * @return int
	 */
	public function getDefaultAge() {

		return $this->defaultAge;
	}
}

?>