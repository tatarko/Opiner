<?php

// Načítanie triedy jadra
require_once ('core/core.inc.php');

// Načítanie jadra + konfigurácie
Opiner::load ('config.inc.php');

// Pripojenie k databáze
Opiner::connect (true);

// Načítanie routra
Opiner::router (Opiner::routerDefault);

// Načítanie motívu
Opiner::template ('default')
	-> meta ('subject', 'Opiner Framework')
	-> value ('siteTitle', 'Opiner Framework')
	-> title ('Opiner Framework');

// Vygenerovania kompletnej stránky
Opiner::compile();
?>