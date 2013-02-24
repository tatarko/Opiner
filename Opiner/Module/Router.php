<?php

namespace Opiner\Module;



/**
 * Routovanie aplikacie
 *
 * Tato trieda na zaklade adresy, z ktorej bola
 * volana stranka vyzisti, ktory controller
 * ma byt volany, ktora akcia spustena a taktiez
 * sa pokusi najst zmienky o doplnkovych premennych,
 * ktore moze stranka obsahovat.
 *
 * @author Tomas Tatarko
 * @since 0.3
 */

class Router extends \Opiner\Module
{

	protected
		$route,
		$complete_url = false,
		$indexes = array(),
		$active_table = array(),
		$route_table = array(),
		$data = array(
			'controller'	=> 'default',
			'action'		=> 'default'
		),
		$view = 'default',
		$controller;



	/**
	 * Spustenie modulu volanim z aplikacie
	 *
	 * V tejto metode sa z nastaveni tohto modulu nacita routovaci
	 * tvar a zavola metoda run(), ktora uz rozbehne samotne routovanie
	 *
	 * @return object
	 */

	public function startup()
	{
		$route = is_array($this->settings) ? $this->settings[0] : $this->settings; 
		$this->run($route);
		unset($this->settings);
		return $this;
	}



	/**
	 * Spustenie routra
	 *
	 * Generovanie routovacej tabuľky, hladanie paremetrickych
	 * indexov, lustenie aktivne nacitanej adresy stranky
	 *
	 * @param string Predpis, podla ktoreho sa bude routovat stranka
	 * @param boolean directions Májú byť získané aj aktuálne hodnoty routovania?
	 * @return object
	 */

	public function run($route, $directions = true)
	{
		$this->route = $route;
		$pos = 0;
		while(preg_match('#\{(.*?)\$([a-z0-9]+)\:?([a-z]*?)\:?([a-z0-9-_]*?)\$(.*?)\}#m', $route, $match, PREG_OFFSET_CAPTURE, $pos))
		{
			$this->route_table[$match[2][0]] = array(
				'wrap-begin' => $match[1][0],
				'wrap-end'   => strpos($match[5][0], '{') !== false ? substr($match[5][0], 0, strpos($match[5][0], '{')) : $match[5][0],
				'controller' => $match[3][0] == '' ? 'controller_encode' : 'controller_' . $match[3][0],
				'default'    => $match[4][0] == '' ? 'index' : $match[4][0],
				'has-child'  => strpos($match[5][0], '{') !== false ? true : false,
			);
			$this->data[$match[2][0]] = $match[4][0];
			$this->indexes[] = $match[2][0];
                        $pos = $this->route_table[$match[2][0]]['has-child'] ? $match[5][1] : $match[0][1] + 1;
		}
		if($directions === true) $this->getDirections();
		return $this;
	}



	/**
	 * Načíta aktuálne hodnoty routovania
	 *
	 * @return object
	 */

	public function getDirections()
	{
		$route = substr($_SERVER['REQUEST_URI'], strlen(substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/') + 1)));
		foreach($this->route_table as $index => $table)
		{
			$pattern = '#' . $this->escape($table['wrap-begin']) . '([a-zA-Z0-9-_]+)' . $this->escape($table['wrap-end']) . '#';
			if(preg_match($pattern, $route, $match, PREG_OFFSET_CAPTURE) and $match[1] != $this->route_table[$index]['default'])
			{
				$this->data[$index] = $match[1][0];
				$route = substr($route, 0, $match[0][1]) . substr($route, $match[0][1] + strlen($match[0][0]));
			}
		}
		foreach($this->route_table as $index => $value)
		$params[] = $this->data[$index];
		$this->active_table[] = $this->route($params);
		return $this;
	}



	/**
	 * Prepočítanie adresy odkazu na základe predaných parametrov
	 *
	 * @return string
	 */

	public function route()
	{
		$route[] = $this->complete_url ? self::getWebRemote() : '';
		$params = func_get_args();
		if(empty($params)) return $route[0];
		if(is_array($params[0])) $params = $params[0];

		foreach($params as $index => $value)
		{
			$value = is_array($value) ? $this->webalize($value) : $value;
			$index = is_int($index) ? $this->indexes[$index] : $index;

			if(isset($this->route_table[$index]))
			{
				$table = $this->route_table[$index];
				$controller = method_exists($this, $table['controller']) ? $table['controller'] : 'controller_encode';
				
				if($table['default'] != $value and $value != '')
				{
					if(isset($last)) $route[] = $last;
					$route[$index] = $table['wrap-begin'] . $this->$controller($value) . $table['wrap-end'];
				}
				elseif($table['has-child']) $last = $table['wrap-begin'] . $this->$controller($value) . $table['wrap-end'];
				else unset($last);
			} else break;
		}
		return implode('', $route);
	}



	/**
	 * Je predany odkaz aktivne nacitanym?
	 *
	 * @return boolean
	 */

	public function isActive()
	{
		$link = $this->route(func_get_args());
		if(array_search($link, $this->active_table) !== false)
		return true;
		else return false;
	}



	/**
	 * Nastavit, ci maju byt odkazy generovane vratane domeny
	 *
	 * @param boolean Ano, ci nie?
	 * @return object
	 */

	public function completeUrl($complete = false)
	{
		$this->complete_url = $complete === true ? true : false;
		$this->active_table =[];
		$this->getDirections();
		return $this;
	}



	/**
	 * Metoda volana pri kompilovani stranky
	 *
	 * V prvej faze sa kontroluje, ci je mozne nacitat
	 * samotny subor controllera. Ak ano, kontroluje sa,
	 * ci nad tymto controllerom moze byt zavolana pozadovana
	 * akcia. Ak ano, tak sa zavola a nakoniec sa do templatu
	 * nastavi view, ktory si zvolila akcia controllera.
	 *
	 * @return object
	 */

	public function compile()
	{

		// Nacitanie controllera		
		$controllerName = '\\Opiner\\Controller\\' . ucfirst($this->data['controller']);
		if(!class_exists($controllerName))
			throw new \Opiner\Exception($this->data['controller'], 210);
		$this->controller = new $controllerName;
		
		// Spustenie pozadovanej akcie
		$actionName = 'action' . ucfirst($this->data['action']);
		if(!method_exists($this->controller, $actionName))
		throw new \Opiner\Exception($this->data['controller'] . '|' . $this->data['action'], 212);
		$this->controller->$actionName();

		// Ukoncenie celeho procesu
		\Opiner\Framework::module('template')->setView($this->view);
		return $this;
	}



	/**
	 * Ošetrenie vstupných parametrov pre router
	 *
	 * @param string Premenná na ošetrenie
	 * @return string
	 */

	protected function controller_encode($value)
	{
		return urlencode($value);
	}



	/**
	 * Ošetrenie premennej pre vstup do regexpov
	 *
	 * @param string Premenná na ošetrenie
	 * @return string
	 */

	protected function escape($value)
	{
		return str_replace(array('?', '&'), array('\\?', '\\&'), $value);
	}



	/**
	 * Ziskanie SEO-friendly tvaru parametra
	 *
	 * @param mixed Parameter, ktory chceme osetrit
	 * @return string
	 */

	protected function webalize($value)
	{
		if(is_array($value))
		{
			foreach($value as $index => $key)
			$value[$index] = $this->webalize($key);
			return implode('-', $value);
		}
		else
		{
			$value = iconv('UTF-8', 'WINDOWS-1250//IGNORE', $value);
			$value = strtr($value, "\xa5\xa3\xbc\x8c\xa7\x8a\xaa\x8d\x8f\x8e\xaf\xb9\xb3\xbe\x9c\x9a\xba\x9d\x9f\x9e\xbf"
			. "\xc0\xc1\xc2\xc3\xc4\xc5\xc6\xc7\xc8\xc9\xca\xcb\xcc\xcd\xce\xcf\xd0\xd1\xd2\xd3\xd4\xd5\xd6\xd7"
			. "\xd8\xd9\xda\xdb\xdc\xdd\xde\xdf\xe0\xe1\xe2\xe3\xe4\xe5\xe6\xe7\xe8\xe9\xea\xeb\xec\xed\xee\xef"
			. "\xf0\xf1\xf2\xf3\xf4\xf5\xf6\xf8\xf9\xfa\xfb\xfc\xfd\xfe", 'ALLSSSSTZZZallssstzzzRAAAALCCCEEEEII'
			. 'DDNNOOOOxRUUUUYTsraaaalccceeeeiiddnnooooruuuuyt');
			$value = strtolower($value);
			$value = preg_replace('#[^a-z0-9]+#', '-', $value);
			$value = trim($value, '-');
			return $value;
		}
	}
}
?>