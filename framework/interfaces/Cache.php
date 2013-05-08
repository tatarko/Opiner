<?php

namespace Opiner\Interfaces;

/**
 * Basic commands for Cache engines
 *
 * @author Tomas Tatarko <tomas@tatarko.sk>
 * @link https://github.com/tatarko/Opiner
 * @copyright Copyright &copy; 2012-2013 Tomas Tatarko
 * @license GPL 3
 * @since 0.1
 */
interface Cache {

	/**
	 * One minute
	 */
	const MINUTE = 60;

	/**
	 * One hour
	 */
	const HOUR = 3600;

	/**
	 * One day
	 */
	const DAY = 86400;

	/**
	 * One week
	 */
	const WEEK = 604800;

	/**
	 * One month
	 */
	const MONTH = 2592000;

	/**
	 * Get variable from cache
	 * @param string $key
	 * @return mixed
	 */
	public function get($key);

	/**
	 * Delete variable from cache
	 * @param string $key
	 * @return bool
	 */
	public function delete($key);

	/**
	 * Set new variable (replace on existence)
	 * @param string $key
	 * @param mixed $value
	 * @param int $time
	 * @return bool
	 */
	public function set($key, $value, $time = null);

	/**
	 * Set new variable (do nothing on existence)
	 * @param string $key
	 * @param mixed $value
	 * @param int $time
	 * @return bool
	 */
	public function add($key, $value, $time = null);

	/**
	 * Delete all cached values
	 * @return bool
	 */
	public function flush();
	
	/**
	 * Connect to cache mechanism (or check something)
	 * @return bool
	 */
	public function connect();
}

?>