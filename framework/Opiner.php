<?php

namespace Opiner;

const NAME = 'Opiner';
const VERSION = '0.6';
const URL = 'http://tatarko.github.com/Opiner/';

const AUTHOR = 'Tomáš Tatarko';
const AUTHOR_URL = 'http://tatarko.sk/';

const DEFAULT_CONTENT_TYPE = 'text/html';
const DEFAULT_CHARSET = 'UTF-8';

const CLASS_FILE_EXTENSION = '.php';

/**
 * Description of Opiner
 *
 * @author Tomas Tatarko <tomas@tatarko.sk>
 * @link https://github.com/tatarko/Opiner
 * @copyright Copyright &copy; 2013 Tomas Tatarko
 * @license GPL 3
 * @since 0.1
 */

class Opiner {

	/**
	 * @var Opiner\Interfaces\Application Currently active application
	 */
	public static $application;

	/**
	 * 
	 * @return Opiner\Interfaces\Application Current initatied application
	 */
	public static function app() {

		if(static::$application) {
			
			return $this->application;
		}
		else {
			
			trigger_error('Application has not been initiated yet', E_USER_ERROR);
		}
	}

	public static function __callStatic($name, $params) {

		if(strtolower(substr($name, 0, 6)) == 'create') {
			
			$name		= ucfirst(strtolower(str_replace(['create', 'application'], '', $name)));
			$className	= static::getClassByAlias('application', $name);
			$configFile	= isset($params[1]) ? $params[1] : null;
			
			if(class_exists($className)) {
				
				static::$application = (new $className($configFile))->init();
				return static::$application;
			}
			else {
				
				trigger_error('Unable to find "' . $name . '" application type', E_USER_ERROR);
			}
		}
		elseif(static::$application) {
			
			return static::$application->$name;
		}
	}
	
	/**
	 * Get full name of class (with namespaces) according to alias
	 * @param string... $alias
	 * @return string
	 */
	public static function getClassByAlias($alias) {

		foreach(func_get_args() as $param) {

			foreach(explode('.', $param) as $fragment) {
				
				$fragments[] = $fragment;
			}
		}

		array_walk($fragments, function($data){

			return ucfirst($data);
		});

		return '\\' . implode('\\', $fragments);
	}
	
	public static function getPathOfAlias($alias) {
		
		for($i = 0; $i < count($fragments) - 1; ++$i) {

			$fragments[$i] = strtolower($fragments[0]);
		}

		if($fragments[0] == 'opiner') {

			$location = dirname(__FILE__);
		}
		else {

			if(static::$application) {

				$location = static::$application->getApplicationPath();
			}
			else {
				
			}
		}
		
		for($i = 0; $i < count($fragments) - 1; ++$i) {

			$fragments[$i] = strtolower($fragments[0]);
		}
	}
	
	public static function loadClass($className) {

		$fragments = array_filter(explode('\\', $className), function($data) {

			return !empty($data);
		});

		$class		= end($fragments);
		$classPath	= static::getPathOfAlias(substr(implode('.', $fragments), 0, -strlen($class)));

		return $classPath . DIRECTORY_SEPARATOR . $class . CLASS_FILE_EXTENSION;
	}
}

/**
 * Autoloading of class files
 */
spl_autoload_register(function($class) {

	require_once Opiner::loadClass($class, PATH_ALIAS_FILE);
});
?>