<?php

// Kontrola jadra
if (!defined ('_root') or false !== strpos ($_SERVER['PHP_SELF'], '.inc.php'))
die (header ('HTTP/1.1 403 Forbidden') . 'Unauthorized Access!');



// Trieda template
class controller_posts extends controller
{


	public function startup ()
	{
		# prepare query array, call important classes, etc
	}


	public function render ($into)
	{
		# do what controller is meant to
	}
}
?>