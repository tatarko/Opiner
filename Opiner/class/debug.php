<?php

namespace Opiner;

class Debug
{



	public function __construct ()
	{
		$this -> start = microtime (true);
		$this -> localhost = substr (remote, 0, 17) == 'http://localhost/' ? true : false;
	}



	/* Vystup debuggera
	 * @return self */

	public function __toString ()
	{
		foreach (get_defined_constants () as $index => $value)
		if (substr ($index, 0, 6) == 'Opiner') $vars[] = $index . ' = ' . var_export($value, true) . ';';
		$funcs = get_defined_functions ();
		foreach (get_declared_classes() as $trieda)
		if (substr ($trieda, 0, 6) == 'Opiner') $classes[] = $trieda;
			
		
return '

<div style="display:block;position:fixed;bottom:0;right:0;width:300px;height:21px;background:#333 -webkit-gradient(linear, left top, left bottom, from(#383838), to(#222));color:#eee;text-shadow:1px 1px 0 #000;padding:0 10px;font:normal 11px Calibri;line-height:21px;text-align:center;-webkit-border-radius:4px 0 0 0;">
' . round ((microtime (true) - $this -> start) * 1000) . 'ms / ' . count(get_included_files()) . ' files / ' . count($classes) . ' classes / ' . count($funcs['user']) . ' functions / ' . count($vars) . ' constants / ' . count(Application::$log) . ' queries
</div>

<!--

Files:
' . implode ("\n", get_included_files()) . '

Classes:
' . implode ("\n", $classes) . '

Functions:
' . implode ("\n", $funcs['user']) . '

Constants:
' . implode ("\n", $vars) . '

Log:
' . var_export (Application::$log, true) . '

-->';
	}
}

?>