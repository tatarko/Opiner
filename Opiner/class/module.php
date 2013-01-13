<?php

namespace Opiner;



/**
 * Rodicovska trieda pre vsetky moduly
 *
 * Tato trieda akymsi zakladnym predpisom
 * pre vsetky moduly frameworku a stranky
 * ako takej. Konstruktor tejto triedy nastavi
 * do svojej protected premennej nastavenia tohto
 * modulu ziskane z config suboru (pripadne z databazy)
 *
 * @abstract
 * @author Tomas Tatarko
 * @since 0.3
 */

abstract class Module {

	use Behavior;

	protected $_settings = [];



	/**
	 * Vznik noveho modulu
	 *
	 * Pokial je vznik modulu volany spolu s nastaveniami,
	 * tak sa tieto nastavenia ulozia do premennej na to
	 * urcunej. Vstupny argument moze byt ako pole, tak aj
	 * niektory z jednoduchych typov premennych (string, int, ...)
	 *
	 * @param mixed Nastavenia modulu
	 * @return object
	 */

	public function __construct ($settings = null)
	{
		if ($settings === null) return $this;
		$this -> _settings = $settings;
		return $this;
	}



	/**
	 * Prebudzanie modulu
	 *
	 * Prva metod, ktora je volana frameworkom
	 * pri kompilovani stranky. Mala by sposobit
	 * nacitanie vsetkeho potrebneho pre chod
	 * modulu.
	 */

	abstract public function startup ();
}

?>