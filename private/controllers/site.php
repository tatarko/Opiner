<?php

namespace Opiner\Controller;



/**
 * Ukazkovy controller
 *
 * @author Tomas Tatarko
 * @since 0.3
 */

class Site extends \Opiner\Controller
{



	/**
	 * Zakladna akcia
	 * @return object
	 */

	public function actionDefault ()
	{
		$this -> temp
			-> title ($this -> t('site.title'))
			-> value ('title', $this -> t('site.title'))
			-> value ('content', $this -> t('site.congratulations'));
	}
}
?>