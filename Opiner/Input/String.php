<?php

namespace Opiner\Input;



/**
 * Vstupne pole formularov: kratke retazce
 *
 * @author Tomas Tatarko
 * @since 0.6
 */

class String extends \Opiner\Input
{



	/**
	 * Vrati premenne fieldu pre template
	 *
	 * Tato metoda vrati zoznam premennych, ktore
	 * sa vlozia do templatu.
	 *
	 * @return array
	 */

	public function getTemplateValues ()
	{
		return [
			'type'		=> 'string',
			'inputType'	=> 'text',
			'name'		=> $this -> name,
			'value'		=> $this -> value,
			'label'		=> $this -> label,
			'description'	=> $this -> description,
		];
	}
}
?>