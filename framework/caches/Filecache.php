<?php

namespace Opiner\Cache;
use	Opiner\Cache,
	Opiner\Opiner,
	Exception;

/**
 * Caching values to files
 *
 * @author Tomas Tatarko <tomas@tatarko.sk>
 * @link https://github.com/tatarko/Opiner
 * @copyright Copyright &copy; 2012-2013 Tomas Tatarko
 * @license GPL 3
 * @since 0.1
 */
class Filecache extends Cache {

	/**
	 * Level of compression for variables
	 */
	const GZCOMPRESS_LEVEL = 3;

	/**
	 * Filename of file containing meta data and indexes of cached values
	 */
	const INDEX_TABLE_FILENAME = '_indexes';

	/**
	 * Value to be returned on missing item
	 */
	const MISSING_ITEM = false;

	/**
	 * Maximum value length to store just in index table (if larger, goes into file)
	 */
	const MAX_INDEX_LENGTH = 1024;

	/**
	 * Extension of cache files
	 */
	const CACHE_FILE_EXTENSION = '.bin';

	/**
	 * @var string Path to directory prepared for storing cache values
	 */
	protected $storageFolder;

	/**
	 * @var mixed[key][dateon] Table containing meta data about cached values
	 */
	protected $meta = [];

	/**
	 * Set new variable (do nothing on existence)
	 * @param string $key
	 * @param mixed $value
	 * @param int $time
	 * @return bool
	 */
	public function add($key, $value, $time = null) {

		$hash = md5($key);

		if(isset($this->meta[$hash])) {

			return true;
		}

		$this->meta[$hash] = $this->putIntoCache($hash, $value, $this->adjustTime($time));

		return true;
	}

	/**
	 * Connect to cache mechanism (or check something)
	 * @param mixed[] $settings Stack of settings (given by app configuration)
	 * @return bool
	 */
	public function connect($settings) {

		$this->storageFolder = isset($settings['path'])
				? $settings['path']
				: Opiner::app()->getStoragePath() . 'cache/';

		if(!is_dir($this->storageFolder) || !is_writable($this->storageFolder)) {

			throw new Exception('Given cache path is not valid or script is not allowed to write into it', 104);
			return false;
		}

		if(substr($this->storageFolder, -1) != DIRECTORY_SEPARATOR) {

			$this->storageFolder .= DIRECTORY_SEPARATOR;
		}

		if(self::MISSING_ITEM !== ($meta = $this->fromFile(self::INDEX_TABLE_FILENAME))) {

			$this->meta = $meta;
		}
		return true;
	}

	/**
	 * Save index table to file
	 */
	public function disconnect() {

		if(!empty($this->meta)) {

			file_put_contents($this->storageFolder . self::INDEX_TABLE_FILENAME . self::CACHE_FILE_EXTENSION, $this->parseValue($this->meta));
		}
		elseif(file_exists($this->storageFolder . self::INDEX_TABLE_FILENAME . self::CACHE_FILE_EXTENSION)) {
			
			unlink($this->storageFolder . self::INDEX_TABLE_FILENAME . self::CACHE_FILE_EXTENSION);
		}
	}

	/**
	 * Delete variable from cache
	 * @param string $key
	 * @return bool
	 */
	public function delete($key) {
		
		$this->deleteByHash(md5($key));
		return true;
	}

	/**
	 * Definitely delete all data about cached value
	 * @param string $hash
	 */
	protected function deleteByHash($hash) {

		if(isset($this->meta[$hash])) {

			unset($this->meta[$hash]);
		}

		if(file_exists($this->storageFolder . $hash . self::CACHE_FILE_EXTENSION)) {

			unlink($this->storageFolder . $hash . self::CACHE_FILE_EXTENSION);
		}
	}

	/**
	 * Delete all cached values
	 * @return bool
	 */
	public function flush() {

		$return		= true;
		$directory	= opendir($this->storageFolder);

		while($filename = readdir($directory)) {

			if(!in_array($filename, ['.', '..', '.gitignore']) && !is_dir($this->storageFolder . $filename)) {

				$return = unlink($this->storageFolder . $filename) && $return;
			}
		}

		closedir($directory);
		$this->meta = [];

		return $return;
	}

	/**
	 * Get variable from cache
	 * @param string $key
	 * @return mixed
	 */
	public function get($key) {

		$hash = md5($key);

		if(!isset($this->meta[$hash])) {

			return self::MISSING_ITEM;
		}

		if($this->meta[$hash]['expire'] < time()) {

			$this->delete($key);
			return self::MISSING_ITEM;
		}

		return isset($this->meta[$hash]['value'])
				? $this->unparseValue($this->meta[$hash]['value'])
				: $this->fromFile($hash);
	}

	/**
	 * Set new variable (replace on existence)
	 * @param string $key
	 * @param mixed $value
	 * @param int $time
	 * @return bool
	 */
	public function set($key, $value, $time = null) {

		$hash				= md5($key);
		$this->meta[$hash]	= $this->putIntoCache($hash, $value, $this->adjustTime($time));
		return true;
	}

	/**
	 * Compress value into storeable format
	 * @param mixed $value
	 * @return string
	 */
	protected function parseValue($value) {

		if(!$this->canCache($value)) {

			throw new Exception('Unable to store variable to cache', 105);
		}

		$value = serialize($value);

		if($this->compress) {

			$value = gzcompress($value, self::GZCOMPRESS_LEVEL);
		}

		return $value;
	}

	/**
	 * Uncompress value from storable format
	 * @param string $value
	 * @return mixed
	 */
	protected function unparseValue($value) {

		if($this->compress) {

			$value = gzuncompress($value);
		}

		$value = unserialize($value);

		return $value;
	}

	/**
	 * Get value from file
	 * @param type $filename
	 * @return mixed
	 */
	protected function fromFile($filename) {

		if(!file_exists($this->storageFolder . $filename . self::CACHE_FILE_EXTENSION)) {

			$this->deleteByHash($filename);
			return self::MISSING_ITEM;
		}

		return $this->unparseValue(file_get_contents($this->storageFolder . $filename . self::CACHE_FILE_EXTENSION));
	}

	/**
	 * Put value into cache mechanism
	 * @param string $hash
	 * @param mixed $value
	 * @param int $expire
	 * @return mixed[]
	 */
	protected function putIntoCache($hash, $value, $expire) {

		$parsedValue	= $this->parseValue($value);
		$meta			= [
			'expire'	=> time() + $expire
		];

		if(strlen($parsedValue) > self::MAX_INDEX_LENGTH) {

			file_put_contents($this->storageFolder . $hash . self::CACHE_FILE_EXTENSION, $parsedValue);
		}
		else {

			$meta['value'] = $parsedValue;
		}

		return $meta;
	}
}

?>