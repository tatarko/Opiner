<?php 

return [

	// Prihlasovacie údaje k databáze
	'database' => [
		'server'	=> 'localhost',
		'username'	=> 'root',
		'password'	=> 'root',
		'database'	=> 'test',
		'prefix'	=> 'prefix',
		'settings'	=> ['settings', 'key', 'value'],
		'relations'	=> true,
	],

	// Vsetko ostatne
	'template' => [
		'default',
		'meta'		=> [
			'title'		=> 'Opiner Framework',
			'description'	=> 'Open Source PHP Framework for Everybody',
			'keywords'	=> 'open, source, php, framework',
		],
		'links'		=> ['remote/css/default.css'],
	],

	'language' => 'slovak',

	'router'	=> '{$controller:string:site$/{$action:string:default$/}}{$primary:int:0$/{$secondary:int:0$/}}',

/*
	'modules'	=> [
		['mailtemp', 'template', 'mail'],
		// Pod akym menom bude modul dostupny, O aky druh modulu ide, konfiguracia modulu
	],
*/
];

?>