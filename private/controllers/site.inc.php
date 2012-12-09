<?php

// Kontrola jadra
if (!defined ('_root') or false !== strpos ($_SERVER['PHP_SELF'], '.inc.php'))
die (header ('HTTP/1.1 403 Forbidden') . 'Unauthorized Access!');



// Trieda template
class view_default extends view
{

	public function startup ()
	{
		# Building query array, etc
	}

	public function check ()
	{
		# Checkig if view can run correctly
	}

	public function prepare ()
	{
		# Calling controllers, etc
	}

	public function render ()
	{
		$this -> template	-> value ('title', 'Hello world!')
					-> value ('content', 'Blahoželáme, Vaša stránka beží správne :)');
	}
}
?>