<?php

namespace Opiner\Module;



/**
 * Generovanie a praca s menu stranky
 *
 * Tato trieda ponuka rozhranie na jednoduchu
 * tvorbu a spracovanie roznych menu v ramci stranky.
 * Jednotlive menu mozu byt v troch roznych druhoch:
 * - box (obsahuje odkazy pripadne pevny html obsah)
 * - stack (zoskupenie viacerych boxov)
 * - breadcrumbs (odkazy na urcenie aktualnej polohy
 *   v ramci webu)
 *
 * @author Tomas Tatarko
 * @since 0.4
 */

class Menu extends \Opiner\Module
{
	protected
		$breadcrumbs = false,
		$activeBox = null,
		$activeStack = null,
		$links = [],
		$boxes = [],
		$stacks = [],
		$boxPositionCounter = 15,
		$linkPositionCounter = 50;



	/**
	 * Spustenie modulu ako volanie aplikacie
	 *
	 * V tomto kroku sa pripravi vnutorna struktura vsetkych
	 * jednotlivych menu na zaklade parametrov predanych
	 * v konfiguracii frameworku.
	 *
	 * @return object
	 */

	public function startup ()
	{
		$this -> prepare ($this -> _settings);
		unset ($this -> _settings);
		return $this;
	}



	/**
	 * Pripravi vnutornu strukturu vsetkych menu
	 *
	 * Postupne prechadza vsetkymi menu, ktore su
	 * spomenute v nastaveniach predanych ako prvy
	 * argument tejto metody a podla tejto konfiguracie
	 * pridava boxy, stacky, breadcrumby.
	 * 
	 * @param array Pole nastaveni
	 * @return object
	 */

	public function prepare ($settings)
	{
		if (!is_array ($settings))
		return $this;
		
		foreach ($settings as $name => $params)
		{
			if ($name == 'breadcrumbs')
			{
				$this -> setBreadcrumbsState ($params);
				continue;
			}
			
			$type = (isset ($params ['type']) and $params ['type'] == 'stack') ? 'stack' : 'box';
			$methodName = 'add' . ucfirst ($type);
			$this	-> $methodName (array_merge (['name' => $name], $params))
				-> setActiveStack ();
		}
		return $this -> setActiveBox ();
	}



	/**
	 * Pridanie noveho boxu
	 *
	 * @param array Parametre daneho boxu
	 * @param string Do ktoreho stacku ho vlozit
	 * @return object
	 */

	public function addBox ($params, $into = null)
	{
		if (!is_array ($params))
		return $this;
		
		if ($into !== null) $params ['into'] = $into;
		elseif ($this -> activeStack) $params ['into'] = $this -> activeStack;

		$params ['type'] = 'box';

		if (!isset ($params ['name']) or empty ($params ['name']))
		{
			$inc = 0;
			do { ++$inc; } while (isset ($this -> boxes [$params ['type'] . $inc]));
			$params ['name'] = $params ['type'] . $inc;
		}
		if (!isset ($params ['title']) or empty ($params ['title']))
		$params ['title'] = ucwords (str_replace (['.', '-', '_'], ' ', $params ['name']));

		if (!isset ($params ['position']) or !is_int ($params ['position']))
		$params ['position'] = $this -> boxPositionCounter++;
		
		$this -> boxes [$params ['name']] = $params;
		$this -> setActiveBox ($params ['name']);
		return $this;
	}



	/**
	 * Pridanie noveho stacku
	 *
	 * @param array Parametre daneho stacku
	 * @return object
	 */

	public function addStack ($params)
	{
		if (!is_array ($params))
		return $this;
		
		$params ['type'] = 'stack';
		if (!isset ($params ['name']))
		{
			$inc = 0;
			do { ++$inc; } while (isset ($this -> boxes [$params ['type'] . $inc]));
			$params ['name'] = $params ['type'] . $inc;
		}
		if (!isset ($params ['title']))
		$params ['title'] = ucwords (str_replace (['.', '-', '_'], ' ', $params ['name']));
		
		$this -> stacks [$params ['name']] = $params;
		$this -> setActiveStack ($params ['name']);
		return $this;
	}



	/**
	 * Prida novy odkaz
	 *
	 * @param string Text odkazu
	 * @param string Adresa odkazu
	 * @param string Popisok odkazu
	 * @param int Pozicia odkazu medzi ostatnymi
	 * @param string Do ktoreho boxu odkaz patri?
	 * @return object
	 */

	public function addLink ($title, $url = null, $description = null, $position = null, $into = null)
	{
		if (is_array ($title))
		list ($title, $url, $position, $description, $into) = array_merge ($title, [null, null, null, null]);

		$params ['title'] = $title;
		$params ['url'] = (is_array ($url) or $url === null) ? \Opiner\Application::module ('router') -> route ($url) : $url;
		$params ['position'] = is_int ($position) ? $position : $this -> linkPositionCounter++;

		if ($description !== null) $params ['description'] = $description;
		if ($into !== null) $params ['into'] = $into;
		elseif ($this -> activeBox !== null) $params ['into'] = $this -> activeBox;

		$this -> links [] = $params;
		return $this;
	}



	/**
	 * Prida novu omrvinku
	 *
	 * @param string Text odkazu
	 * @param string Adresa, kam smeruje odkaz
	 * @param string Popisok odkazu
	 * @return object
	 */

	public function addBreadcrumb ($title, $url = null, $description = null)
	{
		if (is_array ($title))
		list ($title, $url, $position) = array_merge ($title, [null, null]);

		$params ['title'] = $title;
		$params ['url'] = is_array ($url) ? \Opiner\Application::module ('router') -> route ($url) : $url;
		if ($description !== null) $params ['description'] = $description;

		$this -> breadcrumbs [] = $params;
		return $this;
	}



	/**
	 * Nastavi, ci sa ma riesit breadcrumbs menu
	 *
	 * @param boolean Ano, ci nie?
	 * @return object
	 */

	public function setBreadcrumbsState ($state = false)
	{
		if ($state and is_array ($this -> breadcrumbs)) return $this;
		$this -> breadcrumbs = $state ? [] : false;
		return $this;
	}



	/**
	 * Nastavi aktivny box
	 *
	 * Do tohto boxu sa budu nasledne vkladat
	 * vsetky dalsie odkazy, az kym nebude tato metoda zavolana
	 * s prazdnym prvym argumentom
	 *
	 * @param string Unikatny nazov aktivneho boxu
	 * @return object
	 */

	public function setActiveBox ($name = null)
	{
		$this -> activeBox = isset ($this -> boxes [$name]) ? $name : null;
		return $this;
	}



	/**
	 * Nastavi aktivny stack
	 *
	 * Do tohto stacku budu pridavane vsetky nasledujuce boxy,
	 * az kym nebude tato metoda zavolana znova, no uz bez
	 * prveho argumentu.
	 *
	 * @param string Unikatny nazov aktivneho stacku
	 * @return object
	 */

	public function setActiveStack ($name = null)
	{
		$this -> activeStack = isset ($this -> stacks [$name]) ? $name : null;
		return $this;
	}



	/**
	 * Nahodenie menu do templatu
	 *
	 * V dobe, ked stranka prechadza do fazy kompilovania,
	 * tak vsetky boxy, stacky a breadcrumby sa pekne krasne
	 * vlozia do aktivneho templatu. Odkazy, ktore sa nenachadzaju
	 * v ziadnom z boxov budu zahodene.
	 *
	 * @return object
	 */

	public function compile ()
	{
		// Nahadzovanie odkazov do boxov
		foreach ($this -> links as $index => $link)
		if (isset ($link ['into'], $this -> boxes [$link ['into']]))
		$this -> boxes [$link ['into']] ['links'] [] = $link;
		unset ($this -> links);

		// Nahadzovanie boxov do prislusnych stackov
		foreach ($this -> boxes as $name => $box)
		if (isset ($box ['into'], $this -> stacks [$box ['into']]))
		{
			$this -> stacks [$box ['into']] ['boxes'] [] = $box;
			unset ($this -> boxes [$name]);
		}

		// Pridanie omrviniek do templatu
		if ($this -> breadcrumbs)
		foreach ($this -> breadcrumbs as $breadcrumb)
		\Opiner\Application::module ('template') -> value ('menu/breadcrumbs', $breadcrumb);

		// Pridanie boxov do templatu
		foreach ($this -> boxes as $box){}
		\Opiner\Application::module ('template') -> addMenu ($box ['name'], $box);

		// Pridanie stackov do templatu
		foreach ($this -> stacks as $stack)
		\Opiner\Application::module ('template') -> addMenu ($stack ['name'], $stack);

		return $this;
	}
}
?>