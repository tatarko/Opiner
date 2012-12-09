<?php

// Kontrola jadra
if (!defined ('_root') or false !== strpos ($_SERVER['PHP_SELF'], '.inc.php'))
die (header ('HTTP/1.1 403 Forbidden') . 'Unauthorized Access!');



// Trieda template
class controller
{

	public function __construct ()
	{
		$this -> template = Opiner::$template;
		$this -> router = Opiner::$router;
	}

	public function render ()
	{
		return $this;
	}
}
?>