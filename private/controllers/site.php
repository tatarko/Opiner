<?php

namespace Opiner\Controller;

class Site extends \Opiner\Controller
{



	public function actionDefault ()
	{
		$this -> temp
			-> value ('title', $this -> t('site.title'))
			-> value ('content', $this -> t('site.congratulations'));

		#var_dump (\Opiner\Model\Kantor::findAll ());
	}
}
?>