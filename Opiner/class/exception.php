<?php

namespace Opiner;


// Trieda template
class Exception extends \Exception
{

	public function __toString ()
	{
		switch ($this -> getCode ())
		{
			case 101:
				$message = $this -> getLocalMessage ('errors.fileNotFound');
				$required = $this -> getMessage ();
				break;

			case 102:
				$message = $this -> getLocalMessage ('errors.missingConfig', ucfirst ($this -> getMessage ()));
				$required = Application::location ('config', $this -> getMessage ());
				break;

			case 103:
				$message = $this -> getLocalMessage ('errors.missingLocationConstant', ucfirst ($this -> getMessage ()));
				break;

			case 110:
				$message = $this -> getLocalMessage ('errors.missingModuleFile', ucfirst ($this -> getMessage ()));
				$required = Application::location ('modules', $this -> getMessage ());
				break;

			case 111:
				$message = $this -> getLocalMessage ('errors.missingModule', ucfirst ($this -> getMessage ()));
				$required = Application::location ('modules', $this -> getMessage ());
				break;

			case 200:
				list ($template, $file) = explode ('|', $this -> getMessage ());
				$message = $this -> getLocalMessage ('errors.modules.template.config', ucfirst ($template));
				$required = $file;
				break;

			case 201:
				list ($view, $template, $file) = explode ('|', $this -> getMessage ());
				$message = $this -> getLocalMessage ('errors.modules.template.view', ucfirst ($template), ucfirst ($view));
				$required = $file;
				break;

			case 210:
				$message = $this -> getLocalMessage ('errors.modules.router.controllerFile', ucfirst ($this -> getMessage ()));
				$required = Application::location ('controller', $this -> getMessage ());
				break;

			case 220:
				$message = $this -> getLocalMessage ('errors.modules.db.settings');
				break;

			case 211:
				$message = $this -> getLocalMessage ('errors.modules.router.controller', ucfirst ($this -> getMessage ()));
				$required = Application::location ('controller', $this -> getMessage ());
				break;

			case 212:
				list ($controller, $action) = explode ('|', $this -> getMessage ());
				$message = $this -> getLocalMessage ('errors.modules.router.action', ucfirst ($controller), ucfirst ($action));
				$required = Application::location ('controller', $controller);
				break;

			case 221:
				$message = $this -> getLocalMessage ('errors.modules.db.server', $this -> getMessage ());
				break;

			case 222:
				$message = $this -> getLocalMessage ('errors.modules.db.database', $this -> getMessage ());
				break;

			case 250:
				$message = $this -> getLocalMessage ('errors.modules.cache.folder', $this -> getMessage ());
				break;
			
			default:
				$message = $this -> getMessage ();
				break;
		}
		
		$message = '<div style="width:960px;margin:15px auto;color:#777;"><h1 style="color:#555;">' . $this -> getLocalMessage ('error') . '</h1>
<p style="background:#ffacaa;color:white;padding:8px;text-shadow:1px 0 0 #c4554e;-webkit-border-radius:4px;">' . $message . '</p>' . PHP_EOL;
		
		if (isset ($required)) $message .= '<pre>' . $required . '</pre>' . PHP_EOL;
		
		$message .= '<h2 style="color:#555;">Backtrace</h2>
<p><small>' . $this -> getFile () . '(' . $this -> getLine () . ')</small></p>' . PHP_EOL
		. $this -> getCodeFromFile ($this -> getFile (), $this -> getLine ());
		
		
		foreach ($this -> getTrace () as $trace)
		$message .= '<p><small>' . $trace ['file'] . '(' . $trace ['line'] . ')</small></p>' . PHP_EOL
		. $this -> getCodeFromFile ($trace ['file'], $trace ['line']);
		
		
		return $message . '</div>';
		
	}



	protected static function getLocalMessage ($message)
	{
		if (Application::module ('language')
		and Application::module ('language') -> test ($message))
		return Application::module ('language') -> translate (func_get_args());

		list ($code, $param, $other) = array_merge (func_get_args(), ['', '']);
		switch ($code)
		{
			case 'error': return 'Error';
			case 'errors.fileNotFound': return 'File was not found!';
			case 'errors.missingConfig': return 'Configuration "' . $param . '" was not found!';
			case 'errors.missingLocationConstant': return 'Location constant "' . $param . '" was not declared!';
			case 'errors.missingModule': return 'Module "' . $param . '" was not declared in file!';
			case 'errors.missingModuleFile': return 'File for module "' . $param . '" was not found!';
			case 'errors.modules.cache.folder': return 'Cache folder "' . $param . '" does not exists!';
			case 'errors.modules.router.controllerFile': return 'Router could not find "' . $param . '" controller!';
			case 'errors.modules.router.controller': return 'Controller "' . $param . '" was not found in file!';
			case 'errors.modules.router.action': return 'Controller "' . $param . '" does not contain "' . $other . '" action!';
			case 'errors.modules.template.config': return 'Template "' . $param . '" does not have its own configuration file!';
			case 'errors.modules.template.view': return 'Template "' . $param . '" does not contain "' . $other . '" view file!';
			case 'errors.modules.db.settings': return 'Settings do not contain enough data!';
			case 'errors.modules.db.server': return 'Could not connect to "' . $param . '" MySQL server!';
			case 'errors.modules.db.database': return 'Could not connect to "' . $param . '" database!';
		}
	}



	protected static function getCodeFromFile ($file, $line)
	{
		$lines = explode (PHP_EOL, file_get_contents ($file));
		$start = max (0, $line - 6);
		$end = min (count ($lines) - 1, $line + 4);
		
		$return = '<pre style="background:#fafafa;border:1px solid #eee;border-left-width:4px;padding:8px;color:#444;-webkit-border-radius:4px;">';
		for ($i = $start; $i <= $end; ++$i)
		$return .= ($i + 1) . ':  ' . $lines [$i] . PHP_EOL;
		return $return .= '</pre>';
	}

}
?>