<?php

namespace Opiner;



/**
 * Rozhranie na tvorbu a spracovanie formularov
 *
 * Pomocou tejto triedy je mozne velmi jednoduchou
 * formou vytvatat nove formulare a nasledne ich
 * aj spracovavat.
 *
 * @author Tomas Tatarko
 * @since 0.6
 */

class Form extends Object
{



	protected
		$action,
		$method,
		$inputs,
		$last,
		$fieldsets,
		$active;

	/**
	 * Vytvorenie noveho formulara
	 *
	 * Vytvori novy formular, ktory moze smerovat
	 * na urcitu stranku (odkaz predany ako prvy
	 * argument tejto metody). Okrem toho je mozne
	 * v tomto kroku nastavit metodu, akou sa ma formular
	 * odoslat (defaultne je to metoda POST).
	 *
	 * @param string Adresa, kam sa formular odosle
	 * @param string Metoda odosielania dat
	 * @return object
	 */

	public function __construct ($action = null, $method = 'post')
	{
		$this -> action    = $action;
		$this -> method    = $method;
		$this -> inputs    = [];
		$this -> fieldsets = [];

		// Nacitanie abstraktnej triedy inputov
		if (!class_exists ('\\Opiner\\Input'))
		self::getFile (root . 'class/input.php');
		return $this;
	}



	/**
	 * Pridanie noveho inputu
	 *
	 * Tato funkcia v prvom kroku odkontroluje, ci
	 * hladany typ vstupneho policka existuje. Ak ano,
	 * tak sa nacita zdrojovy kod triedy tohto vstupneho
	 * pola a nasledna sa vola vytvorenie noveho objektu,
	 * ktory sa ulozi do pola $inputs.
	 *
	 * @param string O aky typ policka ide?
	 * @param string Aky html nazov ma mat?
	 * @param string Aky nazov pre pouzivatela ma mat?
	 * @param mixed Jeho zakladna hodnota
	 * @param string Strucny popisok tohto policka
	 * @param string Do ktoreho fieldsetu ho umiestnit
	 * @return object
	 */

	public function add ($type, $name, $label = '', $default = null, $description = '', $fieldset = null)
	{
		$class = '\\Opiner\\Input\\' . ucfirst ($type);
		if (!class_exists ($class))
		{
			self::getFile (root . 'input/' . $type . '.php');
			if (!class_exists ($class))
			throw new Exception ($type, 400);
		}
		$this -> inputs [$name] = new $class ($this, $name, $label, $default, $description);
		$this -> last = $name;
		if ($fieldset) $this -> fieldsets [$fieldset] ['inputs'] [] = $name;
		elseif ($this -> active) $this -> fieldsets [$this -> active] ['inputs'] [] = $name;
		else $this -> fieldsets ['__empty__'] ['inputs'] [] = $name;
		return $this;
	}



	/**
	 * Pridanie noveho fieldsetu
	 *
	 * Tato metoda zapricini pridanie noveho
	 * fieldsetu do formulara. Ak je volana s prazdnymi
	 * argumentami, nastavi sa aktualny fieldset na prazdnu hodnotu.
	 *
	 * @param string Unikatny html nazov (ID)
	 * @param string Aky nazov pre pouzivatela ma mat
	 * @return object
	 */

	public function fieldset ($name = null, $title)
	{
		if (empty ($name))
		{
			$this -> active = null;
			return $this;
		}
		
		$title = empty ($title) ? ucwords (str_replace (['.', '-', '_'], ' ', $name)) : $title;
		$this -> fieldsets [$name] = [
			'name'	=> $name,
			'title' => $title,
		];
		$this -> active = $name;
		return $this;
	}



	/**
	 * Pridavanie validatorov
	 *
	 * Pre naposledny pridane vstupne policko
	 * pridane zoznam validatorov, ktorymi
	 * musi dane policko prejst, aby mohla byt
	 * hodnota povazovana za spravne vyplnenu.
	 * Tieto validatory mozu byt metody objektu
	 * samotneho inputu alebo funkcie z namespacu
	 * \Opiner\validator. Pocet vstupnych
	 * argumentov tejto metody je neobmedzeny
	 *
	 * @param string Ktory validator aplikovat
	 * @return object
	 */

	public function validator ($validator)
	{
		if (!$this -> last) return $this;
		$validator = is_array ($validator) ? $validator : func_get_args ();
		$validator = count ($validator) == 1 ? explode (',', $validator [0]) : $validator;
		
		foreach ($validator as $name)
		$this -> inputs [$this -> last] -> addValidator (trim ($name));
		return $this;
	}



	/**
	 * Je formular spravne odoslany?
	 *
	 * Tato metoda skontroluje, ci je formular odoslany
	 * a aj ci vsetky jeho vstupne polia prejdu svojimi
	 * validatormi v poriadku. Ak ano, metoda vrati true,
	 * inac false.
	 *
	 * @return boolean
	 */

	public function posted ()
	{
		$ok = true;
		foreach ($this -> inputs as $input)
		{
			if (!$input -> posted ()) $ok = false;
			elseif (!$input -> validate ()) $ok = false;
		}
		return $ok;
	}



	/**
	 * Nahodi premenne formularu do templatu
	 *
	 * Pripravi vsetky potrebne premenne pre spravne
	 * formulara a vlozi ich do templatu.
	 *
	 * @param string Materska premenna templatu
	 * @param Opiner\Module\Template Odkaz na template, do ktoreho sa ma form vlozit
	 * @return object
	 */

	public function putIntoTemplate ($name, $template = null)
	{
		$template === null ? Opiner::module('template') : $template;
		$array = [
			'action'	=> $this -> action,
			'actionString'	=> ' action="' . htmlspecialchars ((string)$this -> action, ENT_NOQUOTES) . '"',
			'method'	=> $this -> method,
			'methodString'	=> ' method="' . htmlspecialchars ((string)$this -> method, ENT_NOQUOTES) . '"',
		];
		$i = -1;
		
		foreach ($this -> fieldsets as $index => $values)
		{
			$array ['fieldsets'] [++$i] = [
				'name'	=> $values ['name'],
				'title'	=> $values ['title'],
			];
			foreach ($values ['inputs'] as $input)
			$array ['fieldsets'] [$i] ['inputs'] [] = $this -> inputs [$input] -> getTemplateValues ();
			if ($index == '__empty__') $emptyLocation = $i;
		}
		
		if (isset ($emptyLocation))
		{
			$array ['inputs'] = $array ['fieldsets'] [$emptyLocation] ['inputs'];
			unset ($array ['fieldsets'] [$emptyLocation] ['inputs']);
		}
		$template -> addData ();
		var_dump($array);
		return $this;
	}
}
?>