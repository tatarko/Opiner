<?php

return array(

	'title'		=> 'Opiner Framework',
	
	'components' => array(
		
		'db' => array(
		
			'class'			=> 'database',
			'connection'	=> 'sqlite:' . $this->storagePath . 'default.db',
		)
	)
);

?>