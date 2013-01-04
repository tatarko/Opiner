<?php

namespace Opiner;

class Module {

	protected $_settings = [];
	
	public function __construct ($settings)
	{
		if ($settings === null) return $this;
		$this -> _settings = $settings;
		return $this;
	}

	public function startup ()
	{
		return $this;
	}	
}

?>