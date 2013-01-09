<?php

namespace Opiner;

abstract class Module {

	use Behavior;

	protected $_settings = [];
	
	public function __construct ($settings = null)
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