<?php

namespace Opiner;

/**
 * Framework name
 */
const NAME = 'Opiner';

/**
 * Version of framework
 */
const VERSION = '0.1';

/**
 * Link to official website
 */
const URL = 'http://tatarko.github.com/Opiner/';

/**
 * Name of author
 */
const AUTHOR = 'Tomáš Tatarko';

/**
 * Link to author's website
 */
const AUTHOR_URL = 'http://tatarko.sk/';

/**
 * Default content type of generated websites
 */
const DEFAULT_CONTENT_TYPE = 'text/html';

/**
 * Default charset to be used
 */
const DEFAULT_CHARSET = 'UTF-8';

/**
 * Suffix used for autoloading class file
 */
const CLASS_FILE_SUFFIX = '.php';

/**
 * Starter class for doing basic stuff
 * such as creating application and
 * unmasking different aliases.
 *
 * @author Tomas Tatarko <tomas@tatarko.sk>
 * @link https://github.com/tatarko/Opiner
 * @copyright Copyright &copy; 2013 Tomas Tatarko
 * @license GPL 3
 * @since 0.1
 */
class Opiner {
	
	/**
	 * Unmask as a file name
	 */
	const PATH_ALIAS_FILE = 1;
	
	/**
	 * Unmask as a directory name
	 */
	const PATH_ALIAS_DIRECTORY = 2;
	
	/**
	 * Unmask all types
	 */
	const PATH_ALIAS_ALL = 3;

	/**
	 * @var Opiner\Interfaces\Application Currently active application
	 */
	public static $application;

	/**
	 * Getter for current active application
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

	/**
	 * Overriding magic method for calling static methods
	 * 
	 * If called method's name starts with "create", new application
	 * instance will be created with type definied after create keyword
	 * (word "application" will be dismissed). In other cases it will be checked
	 * if exists some application and if yes, the application's compomenent named
	 * as a called method will be returned.
	 * 
	 * @param string $name Name of component to be returned or type of application to be created
	 * @param mixed[] $params
	 * @return Opiner\Component
	 */
	public static function __callStatic($name, $params) {

		if(strtolower(substr($name, 0, 6)) == 'create') {
			
			$name		= ucfirst(str_replace(['create', 'application'], '', strtolower($name)));
			$className	= static::getClassByAlias('application', $name);
			$configFile	= isset($params[1]) ? $params[1] : null;
			
			if(class_exists($className)) {
				
				static::$application = new $className($configFile);
				static::$application->init();
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
	 * Get full name of class (with namespace) according to alias
	 * @param string... $alias
	 * @return string
	 */
	public static function getClassByAlias($alias) {

		foreach(func_get_args() as $param) {

			foreach(explode('.', $param) as $fragment) {

				$fragments[] = $fragment;
			}
		}

		array_walk($fragments, function(&$data){

			$data = ucfirst($data);
		});
		
		if($fragments[0] != 'Opiner') {

			$secondary = array_merge(['Opiner'], $fragments);

			if(!class_exists('\\' . implode('\\', $fragments))) {

				return '\\' . implode('\\', $secondary);
			}
		}

		return '\\' . implode('\\', $fragments);
	}
	
	/**
	 * Get real path according to given alias
	 * @param string $alias Alias of class/directory
	 * @param int $type One or more types to be unmasking (according to class constants starting with PATH_ALIAS_)
	 * @return string Unmasked path
	 */
	public static function getPathOfAlias($alias, $type = self::PATH_ALIAS_ALL) {

		$return		= array();
		$alias		= str_replace('.', '\\', $alias);
		$fragments	= array_filter(explode('\\', $alias));

		if(empty($fragments)) {

			return null;
		}

		array_walk($fragments, function(&$var){

			$var = strtolower($var);

			if(!in_array($var, ['opiner', 'interfaces'])) {

				$var .= substr($var, -1) == 'y' ? substr($var, -1) . 'ies' : 's';
			}
		});

		$inFramework = $fragments[0] == 'opiner';

		// Getting path to directory
		if($inFramework || !static::$application) {

			if($inFramework) {

				unset($fragments[0]);
			}
			$return['directory'] = static::getFrameworkPath()
				. implode(DIRECTORY_SEPARATOR, $fragments)
				. DIRECTORY_SEPARATOR;
		}
		else {

			$return['directory'] = static::$application->getApplicationPath()
				. implode(DIRECTORY_SEPARATOR, $fragments)
				. DIRECTORY_SEPARATOR;
		}

		// Getting path to class
		if($type & self::PATH_ALIAS_FILE) {

			$return['file']				= explode(DIRECTORY_SEPARATOR, $return['directory'], -1);
			$lastKey					= count($return['file']) - 1;
			$return['file'][$lastKey]	= ucfirst($return['file'][$lastKey]);
			$return['file'][$lastKey]	= substr($return['file'][$lastKey], 0, substr($return['file'][$lastKey], -3) == 'ies' ? -3 : -1);
			$return['file']				= implode(DIRECTORY_SEPARATOR, $return['file']) . CLASS_FILE_SUFFIX;
			
			if(static::$application && !$inFramework && !file_exists($return['file'])) {
				
				$return['file'] = static::getPathOfAlias(implode('.', array_merge(['opiner'], $fragments)), static::PATH_ALIAS_FILE);
			}
		}

		// Unsetting path to directory if it is not requested
		if(($type & self::PATH_ALIAS_DIRECTORY) == 0) {

			unset($return['directory']);
		}

		// Returning data
		switch(count($return)) {

			case 0: return null; break;
			case 1: return current($return); break;
			default: return $return; break;
		}
	}
	
	/**
	 * Getting path to framework files
	 * @return string
	 */
	public static function getFrameworkPath() {

		return dirname(__FILE__) . DIRECTORY_SEPARATOR;
	}
}

/**
 * Autoloading of class files
 */
spl_autoload_register(function($class) {

	$filename = Opiner::getPathOfAlias($class, Opiner::PATH_ALIAS_FILE);

	if(file_exists($filename)) {

		require_once $filename;
	}
});
?>