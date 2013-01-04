<?php

namespace Opiner\Controller;



// Trieda template
class Site extends \Opiner\Controller
{
	public function actionDefault ()
	{
		$this -> module ('template')
			-> value ('title', 'Hello world!')
			-> value ('content', 'Blahoželáme, Vaša stránka beží správne :)');
	}
}
?>