<?php 

// PHP konfigurácia
#ini_set ('error_log', Opiner::root . '/cache/erros.log');
#ini_set ('error_reporting', E_ALL);
#ini_set ('display_errors', E_ALL);

return array (

	// Prihlasovacie údaje k databáze
	'database' => array (
		'server'	=> 'localhost',
		'username'	=> 'root',
		'password'	=> 'root',
		'database'	=> 'test',
		'prefix'	=> 'prefix',
		'settings'	=> array ('settings', 'key', 'value'),
		'relations'	=> true,
	),

	// Vsetko ostatne
	'template'	=> array (
		'name'		=> 'default',
		'meta'		=> array (
			'title'		=> 'Opiner Framework',
			'description'	=> 'Open Source PHP Framework for Everybody',
			'keywords'	=> 'open, source, php, framework',
		),
		'links'		=> array ('remote/css/default.css'),
	),
	'language'	=> 'slovak',
	'router'	=> '{?$controller:string:site${=$action:string:default${&$param:int:5$}}}',
//	'router'	=> '{$app:index$/}{$view:string:default$/{$primary$/{$secondary:int:5$/}}}',

);
?>