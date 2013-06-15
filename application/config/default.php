<?php

return [

	'title'		=> 'Opiner Framework',
	
	'components' => [
		
		'db' => [
		
			'class'			=> 'database',
			'connection'	=> 'sqlite:' . $this->storagePath . 'database/default.sqlite',
		],
		
		'cache' => true,
	]
];

?>
