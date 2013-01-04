<?php

// Načítanie triedy jadra
include ('Opiner/Opiner.php');

// Spustenie frameworku
Opiner\Application::load (__FILE__, 'default');

// Skompilovanie vystupu
Opiner\Application::compile ();

?>