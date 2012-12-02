<?php

// Kontrola jadra
if (!defined ('_root') or false !== strpos ($_SERVER['PHP_SELF'], '.inc.php'))
die (header ('HTTP/1.1 403 Forbidden') . 'Unauthorized Access!');



// Trieda template
class view
{

	// Základné premmenné motívu
	public $router = null;
	public $template = null;



	/**
	 *	Vytvorenie objektu, určenie základných premenných
	 *	@param string name Fyzický názov súboru
	 *	@return object self
	 */

	public function __construct ()
	{
		return $this;
	}
}
?>