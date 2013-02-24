<?php 

/**
 * Matersky package celeho frameworku
 * 
 * @author Tomas Tatarko
 * @url https://github.com/tatarko/Opiner
 * @package tatarko/Opiner
 * @version 0.6
 * @since 0.2
 */
namespace Opiner;

/**
 * Nazov celeho frameworku
 */
const NAME = 'Opiner';

/**
 * Verzia frameworku
 */
const VERSION = '0.6';

/**
 * Verejna url adresa projektu
 */
const URL = 'http://tatarko.github.com/Opiner/';

/**
 * Meno autora projektu
 */
const AUTHOR = 'Tomáš Tatarko';

/**
 * Url adresa na web autora
 */
const AUTHOR_URL = 'http://tatarko.sk/';

/**
 * Adresa k priecinku s konfiguracnymi subormi
 */
const LOCATION_CONFIG = '[:scripts:]config/[:param1:].php';

/**
 * Adresa k prekladovym suborom aplikacie
 */
const LOCATION_LANGUAGE = '[:scripts:]language/[:param1:].php';

/**
 * Zakladny typ generovaneho dokumentu
 */
const LOCATION_PRIVATE_DIRECOTY = '[:webroot:]private/';

/**
 * V pripade chybu ju iba logovat
 */
const ERROR_LOG = 1;

/**
 * V pripade chybu ukoncit beh aplikacie
 */
const ERROR_DIE = 2;

/**
 * V pripade chybu len vratit false
 */
const ERROR_RETURN = 4;

/**
 * Zakladny typ generovaneho dokumentu
 */
const DEFAULT_CONTENT_TYPE = 'text/html';

/**
 * Zakladny typ generovaneho dokumentu
 */
const DEFAULT_CHARSET = 'UTF-8';

/**
 * Pripona php suborov s triedami (autoloading)
 */
const CLASS_FILE_EXTENSION = '.php';

/**
 * @const Opiner\ROOT Adresa k suborom frameworku
 */
define(__NAMESPACE__ . '\ROOT', dirname(__FILE__) . DIRECTORY_SEPARATOR);



// Automaticke nacitavanie tried

spl_autoload_register(function($class) {
	
	$location = explode('\\', $class);
	
	// Triedy z denenej struktury
	if(count($location) > 1) {
		
		$folder = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
		foreach($location as $loc)
			$folder .= $loc . DIRECTORY_SEPARATOR;
		$filename = dirname($folder) . DIRECTORY_SEPARATOR . end($location) . CLASS_FILE_EXTENSION;
		if(file_exists($filename))
			require_once $filename;
		elseif(class_exists('Opiner\\Framework', false)) {
			$private = Framework::getPrivateLocation();
			$filename = $private . substr($filename, strrpos($filename, reset($location) . DIRECTORY_SEPARATOR) + strlen(current($location)) + 1);
			if(file_exists($filename))
				require_once $filename;
		}
	}
	
	// Zakladne triedy + Modely
	else {
		$location	= class_exists('Opiner\\Framework', false)
					? Framework::getLocation(LOCATION_MODEL, $class)
					: dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
		$filename = $location . $class . CLASS_FILE_EXTENSION;
		if(file_exists($filename))
			require_once $filename;
	}
});
?>