<?php

namespace Opiner;



/**
 * Trieda pre vynimky systemu
 *
 * Ide o triedu odvodenu od zakladnej PHP triedy Exception.
 * Oproti nej ma obmenenu metodu __toString a to na taku formu,
 * aby programatorovi poskytla detailnejsi opis chyby. Podla
 * moznosti vyuziva aj nacitany modul prekladu a chybove hlasky
 * prelozi do lokalnej reci. Ak je framework spusteny na lokalnom
 * servri, tak prida aj backtrace pre rychlejsie identifikovanie,
 * kde k chybe vlastne doslo.
 *
 * @author Tomas Tatarko
 * @since 0.4
 */

class Exception extends \Exception {



	/**
	 * Vystup do textovej verzie.
	 *
	 * Kedze v pripade, ze dojde k odoslaniu vynimky,
	 * tak system ju spracuje sposobom die($exception)
	 * a preto je potrebne vystup tejto vynimky
	 * odoslat ako string.
	 *
	 * @return string
	 */
	
	public function __toString() {
		switch($this->getCode()) {
			case 100:
				$message = $this->getLocalMessage('errors.variableNotDefined', $this->getMessage());
				break;

			case 101:
				$message = $this->getLocalMessage('errors.fileNotFound');
				$required = $this->getMessage();
				break;

			case 102:
				$message = $this->getLocalMessage('errors.missingConfig', ucfirst($this->getMessage()));
				$required = Framework::getLocation(LOCATION_CONFIG, $this->getMessage());
				break;

			case 103:
				$message = $this->getLocalMessage('errors.missingLocationConstant', ucfirst($this->getMessage()));
				break;

			case 110:
				$message = $this->getLocalMessage('errors.missingModuleFile', ucfirst($this->getMessage()));
				$required = Framework::getLocation(LOCATION_MODULE, $this->getMessage());
				break;

			case 111:
				$message = $this->getLocalMessage('errors.missingModule', ucfirst($this->getMessage()));
				$required = Framework::getLocation(LOCATION_MODULE, $this->getMessage());
				break;

			case 200:
				list($template, $file) = explode('|', $this->getMessage());
				$message = $this->getLocalMessage('errors.modules.template.config', ucfirst($template));
				$required = $file;
				break;

			case 201:
				list($view, $template, $file) = explode('|', $this->getMessage());
				$message = $this->getLocalMessage('errors.modules.template.view', ucfirst($template), ucfirst($view));
				$required = $file;
				break;

			case 210:
				$message = $this->getLocalMessage('errors.modules.router.controllerFile', ucfirst($this->getMessage()));
				$required = Framework::getLocation(LOCATION_CONTROLLER, $this->getMessage());
				break;

			case 220:
				$message = $this->getLocalMessage('errors.modules.db.settings');
				break;

			case 211:
				$message = $this->getLocalMessage('errors.modules.router.controller', ucfirst($this->getMessage()));
				$required = Framework::getLocation(LOCATION_CONTROLLER, $this->getMessage());
				break;

			case 212:
				list($controller, $action) = explode('|', $this->getMessage());
				$message = $this->getLocalMessage('errors.modules.router.action', ucfirst($controller), ucfirst($action));
				$required = Framework::location('controller', $controller);
				break;

			case 221:
				$message = $this->getLocalMessage('errors.modules.db.server', $this->getMessage());
				break;

			case 222:
				$message = $this->getLocalMessage('errors.modules.db.database', $this->getMessage());
				break;

			case 250:
				$message = $this->getLocalMessage('errors.modules.cache.folder', $this->getMessage());
				break;

			case 300:
				$message = $this->getLocalMessage('errors.model.missingField', $this->getMessage());
				break;

			case 301:
				$message = $this->getLocalMessage('errors.model.cannotUnset', $this->getMessage());
				break;

			case 302:
				$message = $this->getLocalMessage('errors.model.missingClass', $this->getMessage());
				break;

			case 303:
				list($scope, $model) = explode('|', $this->getMessage());
				$message = $this->getLocalMessage('errors.model.missingScope', $this->getMessage());
				break;
			
			default:
				$message = $this->getMessage();
				break;
		}
		
		$message = '<div style="width:960px;margin:15px auto;color:#777;"><h1 style="color:#555;">' . $this->getLocalMessage('error') . '</h1>
<p style="background:#ffacaa;color:white;padding:8px;text-shadow:1px 0 0 #c4554e;-webkit-border-radius:4px;">' . $message . '</p>' . PHP_EOL;
		
		if(isset($required)) $message .= '<pre>' . $required . '</pre>' . PHP_EOL;
		
		$message .= '<h2 style="color:#555;">Backtrace</h2>
<p><small>' . $this->getFile() . '(' . $this->getLine() . ')</small></p>' . PHP_EOL
		. $this->getCodeFromFile($this->getFile(), $this->getLine());
		
		
		foreach($this->getTrace() as $trace)
		$message .= '<p><small>' . $trace ['file'] . '(' . $trace ['line'] . ')</small></p>' . PHP_EOL
		. $this->getCodeFromFile($trace ['file'], $trace ['line']);
		
		
		return $message . '</div>';
		
	}



	/**
	 * Ziskaj lokalizovanu frazu
	 *
	 * Ak uz bol nacitany modul prekladu stranok a aj obsahuje
	 * hladanu frazu, tak tato metoda vrati prelozeny text.
	 * V opacnom pripade vrati pevne stavoveny text(v anglickom
	 * jazyku).
	 *
	 * @param string Kluc prekladovej frazy
	 * @return string Prelozena fraza
	 */

	protected static function getLocalMessage($message)
	{
		if(Framework::module('language')
		and Framework::module('language')->test($message))
		return Framework::module('language')->translate(func_get_args());

		list($code, $param, $other) = array_merge(func_get_args(), ['', '']);
		switch($code)
		{
			case 'error': return 'Error';
			case 'errors.variableNotDefined': return 'Variable "' . $param . '" was not defined in object!';
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
			case 'errors.model.missingField': return 'Field "' . $param . '" does not exists!';
			case 'errors.model.cannotUnset': return 'Field "' . $param . '" can not be deleted!';
			case 'errors.model.missingClass': return 'Model "' . $param . '" does not exists!';
			case 'errors.model.missingScope': return 'Scope "' . $param . '" was not defined in model "' . $other . '"!';
		}
	}



	/**
	 * Nacitaj blok kodu zo suboru
	 *
	 * Zo suboru predaneho ako argument tejto metody nacita blok
	 * maximalne desiatich riadkov z okolia riadku, na ktorom
	 * bol volany prikaz v ramci backtrace. Tento zdrojovy
	 * kod nasledne obali do <code>&lt;pre&gt;</code> tagu.
	 *
	 * @param string Adresa suboru
	 * @param int Riadok, z ktoreho okolia nacitat zdrojovy kod
	 * @return string
	 */

	protected static function getCodeFromFile($file, $line) {
		$lines	= explode(PHP_EOL, file_get_contents($file));
		$start	= max(0, $line - 6);
		$end	= min(count($lines) - 1, $line + 4);
		
		$return = '<pre style="background:#fafafa;border:1px solid #eee;border-left-width:4px;padding:8px;color:#444;-webkit-border-radius:4px;">';
		for($i = $start; $i <= $end; ++$i)
			$return .=($i + 1) . ':  ' . $lines [$i] . PHP_EOL;
		return $return .= '</pre>';
	}
}
?>