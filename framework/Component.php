<?php

namespace Opiner;

/**
 * Basic component class
 *
 * @author Tomas Tatarko <tomas@tatarko.sk>
 * @link https://github.com/tatarko/Opiner
 * @copyright Copyright &copy; 2012-2013 Tomas Tatarko
 * @license GPL 3
 * @since 0.6
 */
abstract class Component extends Object implements \Opiner\Interfaces\Component {

	/**
	 * @var bool Flag that marks if component has already been initialized
	 */
	protected $isInitialized = false;

	/**
	 * @var mixed[] Array of components settings
	 */
	protected $settings = array();

	/**
	 * Basic initialization
	 * @param mixed[] $settings Component settings
	 * @return \Opiner\Component Self
	 */
	public function init($settings = null) {

		$this->settings			= (array)$settings;
		$this->isInitialized	= true;
		return $this;
	}

	/**
	 * Checks if component has already been initialized
	 * @return bool
	 */
	public function getIsInitialized() {

		return $this->isInitialized;
	}

	/**
	 * Checks if component has been initialized and throw expcetion if not
	 * @throws Exception
	 */
	public function initCheck() {

		if(!$this->isInitialized) {

			throw new Exception('Component has not been initialized yet', 102);
		}
	}

	/**
	 * Fetching value form component configuration
	 * @param string $key Index of configuration value
	 * @param mixed $default Value to be return if configuration value does not exists
	 * @return mixed
	 */
	public function fetchConfig($key, $default = null) {

		return @$this->settings[$key] ?: $default;
	}
}

?>