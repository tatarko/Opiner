<?php

namespace Opiner;
use Opiner\Interfaces\Component as IComponent;

/**
 * Default application class
 *
 * @author Tomas Tatarko <tomas@tatarko.sk>
 * @link https://github.com/tatarko/Opiner
 * @copyright Copyright &copy; 2012-2013 Tomas Tatarko
 * @license GPL 3
 * @since 0.1
 * @abstract
 */
abstract class Application extends Object {

	/**
	 * Return NULL if component is not found
	 */
	const MISSING_COMPONENT_NULL = 1;

	/**
	 * Throw error/exception if component is not found
	 */
	const MISSING_COMPONENT_THROW = 2;

	/**
	 * @var \Opiner\Component[] Application components
	 */
	protected $components;

	/**
	 * @var mixed[] Application configuration 
	 */
	protected $config;

	/**
	 * @var string Path to the application folder
	 */
	protected $applicationPath;

	/**
	 * @var string Path to the storage folder
	 */
	protected $storagePath;

	/**
	 * @var bool Flag marking if application has already been initialized
	 */
	protected $isInitialized = false;

	/**
	 * Constructor of application
	 * 
	 * In this step, configuration will be loaded from file
	 * 
	 * @param string $config
	 * @return \Opiner\Application
	 */
	public function __construct($config = null) {

		if(empty($config) || !file_exists($config)) {

			$this->applicationPath	= dirname(__DIR__) . '/application/';
			$config					= $this->applicationPath . 'config/default.php';
		}
		else {

			$this->applicationPath = dirname(dirname($config)) . DIRECTORY_SEPARATOR;
		}

		if(!file_exists($config)) {

			trigger_error('Config file not found (' . $config . ')', E_USER_ERROR);
		}

		$this->storagePath = dirname($this->applicationPath) . '/storage/';
		$this->config = require_once $config;

		return $this;
	}

	/**
	 * Initiate application
	 * 
	 * All of application components will be loaded
	 * 
	 * @return \Opiner\Application
	 */
	public function init() {

		$this->isInitialized = true;

		if(!isset($this->config['components'])) {
			
			return;
		}
		
		if(!is_array($this->config['components'])) {
			
			trigger_error('Definition of application components have to be an array', E_USER_WARNING);
		}
		
		foreach($this->config['components'] as $index => $config) {
			
			$className		= ucfirst(@$config['class'] ?: $index);
			$realClassName	= strpos($className, '.') === false && strpos($className, '\\') === false ? Opiner::getClassByAlias('component', $className) : $className;

			$component = new $realClassName;

			if(!$component instanceof IComponent) {

				$this->throwError('"' . $realClassName . '" is not an instance of Component', 101);
				continue;
			}

			unset($config['class']);
			$component->init($config);
		}
	}

	/**
	 * Reporting error to user
	 * 
	 * If application has been already initiated,
	 * an exception will be thrown. Otherwise error
	 * wil be triggered.
	 * 
	 * @param string $message
	 * @param int $code
	 * @throws Exception
	 */
	public function throwError($message, $code = 100) {

		if($this->isInitialized) {

			throw new Exception($message, $code);
		}
		else {

			switch(subtr($code, 0, 1)) {

				case 2:
					trigger_error($message, E_USER_WARNING);
					break;

				case 3:
					trigger_error($message, E_USER_NOTICE);
					break;

				case 4:
					trigger_error($message, E_USER_DEPRECATED);
					break;

				default:
					trigger_error($message, E_USER_ERROR);
					break;
			}
		}
	}

	/**
	 * Run application
	 */
	abstract public function run();

	/**
	 * Get application component by type
	 * @param string $name Name of the (parent) class
	 * @param int $missing Flag: what to do if component will not be found
	 * @return Opiner\Component
	 * @throws Exception If $missing flag is set to throw and component is not found
	 */
	public function getComponentByType($name, $missing = self::MISSING_COMPONENT_NULL) {

		foreach($this->components as $component) {

			if($component instanceof $name) {

				return $component;
			}
		}

		switch($missing) {

			case self::MISSING_COMPONENT_NULL:
				return null;
				break;

			case self::MISSING_COMPONENT_THROW:
				$this->throwError('Application does not have component of type "' . $name . '"');
				break;
		}
	}

	/**
	 * Returns path to application folder
	 * @return string
	 */
	public function getApplicationPath() {

		return $this->applicationPath;
	}

	/**
	 * Returns path to storage folder
	 * @return string
	 */
	public function getStoragePath() {

		return $this->storagePath;
	}

	/**
	 * Sets path to storage folder
	 * @param string $path Folder path
	 */
	public function setStoragePath($path) {

		if(is_dir($path)) {

			$this->storagePath = $path;
		}
		else {

			$this->throwError('Storage path is not valid directory', 211);
		}
	}

	/**
	 * Returns path to framework folder
	 * @return string
	 */
	public function getFrameworkPath() {

		return Opiner::getFrameworkPath();
	}

	/**
	 * Checks if application has been initiated yet
	 * @return bool
	 */
	public function getIsInitialized() {

		return $this->isInitialized;
	}
}
?>