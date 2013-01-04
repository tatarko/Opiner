<?php

namespace Opiner;


// Trieda template
class Controller
{

	public function __construct ()
	{
	}

	protected static function module ($localName)
	{
		return Application::module ($localName);
	}
}
?>