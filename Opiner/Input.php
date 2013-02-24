<?php

namespace Opiner;



/**
 * Materska trieda pre vsetky vstupne polia formularov
 *
 * Tato trieda definuje akesi zakladne rozhranie
 * a pravidla pre vsetky mozne vstupne polia
 * formularov.
 *
 * @abstract
 * @author Tomas Tatarko
 * @since 0.6
 */

abstract class Input extends Object
{



	protected
		$form,		// Odkaz na formular, ku ktoremu input patri
		$name,		// Unikatny html nazov inputu
		$label,		// Nazov inputu zobrazovany pouzivatelovi
		$value,		// Aktualna hodnota v policku
		$default,	// Zakladna, vychodiskova hodnota v policku
		$description,	// Strucny popisok policka
		$validators = [];	// Zoznam validatorov



	/**
	 * Vytvorenie noveho inputu
	 *
	 * Tato metoda je volana samotnym objektom Form,
	 * a to presne specifikovanym poradim argumentov.
	 * Z toho dovodu je tato metoda final a nie je mozne ju
	 * prepisovat v zdedenych triedach. Ak je potrebne po vytvoreni
	 * noveho objektu dcerskej triedy vykonat nejake operacie,
	 * staci vytvorit novu metodu afterConstruct(), ktora bude
	 * zavolana.
	 *
	 * @param object/Form Odkaz na formular, ktoremu tento input patri
	 * @param string Unikatny html nazov vstupne policka
	 * @param string Nazov policka zobrazovany pouzivatelovi
	 * @param mixed Zakladna hodnota
	 * @param string Strucny popis tohto policka
	 * @return object
	 */

	public final function __construct ($form, $name, $label = '', $default = null, $description = '')
	{
		$this -> form		= $form;
		$this -> name		= $name;
		$this -> label		= empty ($label) ? ucwords (str_replace (['.', '_', '-'], ' ', $name)) : $label;
		$this -> value		= $default;
		$this -> default	= $default;
		$this -> description	= $description;
		
		if (method_exists ($this, 'afterConstruct'))
		$this -> afterConstruct ();
		return $this;
	}



	/**
	 * Pridaj novy validator
	 *
	 * Do zoznamu validatorov, ktorymi musi prejst
	 * hodnota vstupneho policka, aby bola povazovana
	 * za spravne odoslanu, prida novy zaznam (validator).
	 *
	 * @param string Nazov validatoru
	 * @return object
	 */

	public final function addValidator ($name)
	{
		$this -> validators [] = $name;
		$this -> validators = array_unique ($this -> validators);
		return $this;
	}



	/**
	 * Odober validator
	 *
	 * Zo zoznamu validatorov, ktorymi musi prejst
	 * hodnota vstupneho policka, aby bola povazovana
	 * za spravne odoslanu, odoberie pozadovany validator.
	 *
	 * @param string Nazov validatoru
	 * @return object
	 */

	public final function removeValidator ($name)
	{
		if (false !== $index = array_search ($name, $this -> validators))
		unset ($this -> validators [$index]);
		return $this;
	}



	/**
	 * Je policko odoslane?
	 *
	 * Skontroluje, ci je policko spravne odoslane,
	 * to znamena, ci sa nachadza hodnota jeho premennej
	 * medzi odoslanymi datami.
	 *
	 * @return boolean
	 */

	public function posted ()
	{
		if (isset ($_REQUEST [$this -> name]))
		{
			$this -> value = $_REQUEST [$this -> name];
			return true;
		}
		else return false;
	}



	/**
	 * Je policko validne?
	 *
	 * Tato metoda prechadza polom validatorov
	 * urcenych pre toto vstupne policko a postupne
	 * vpusta hodnotu tohto vstupneho policka
	 * na zvalidovanie. Ak pri hocktorom z validarov
	 * hodnota neobstoji, tato metoda vrati false.
	 * Ak sa validator nepodari najst, metoda vyhodi vynimku,
	 * a tym padom konci kopmilovanie frameworku.
	 *
	 * @return boolean
	 */

	public final function validate ()
	{
		$status = true;
		foreach ($this -> validators as $validator)
		{
			$name = 'validator' . ucfirst ($validator);
			$namespace = '\\Opiner\\Valitor\\' . $validator;

			if (method_exists ($this, $name))
			{
				if (!$this -> $name ())
				$status = false;
			}
			elseif (function_exists ($namespace))
			{
				if (!$namespace ($this -> value))
				$status = false;
			}
			else throw new Exception ($validator, 402);
		}
		return $status;
	}



	/**
	 * Je odoslana hodnota NIE prazdna?
	 *
	 * Jeden zo zakladnych validatorov. Kontroluje,
	 * ci odoslana hodnota nie je prazdna. Inak povedane,
	 * nemoze to byt prazdny string, ci nieco podobne.
	 *
	 * @return boolean
	 */

	public function validatorRequired ()
	{
		return empty ($this -> value) ? false : true;
	}



	/**
	 * Vrati premenne fieldu pre template
	 *
	 * Tato metoda vrati zoznam premennych, ktore
	 * sa vlozia do templatu
	 *
	 * @return array
	 */

	abstract public function getTemplateValues ();
}
?>