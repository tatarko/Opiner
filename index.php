<?php

// Načítanie triedy jadra
include ('opiner/opiner.php');

// Spustenie frameworku
opiner\application::load (__FILE__, 'default');

// Skompilovanie vystupu
opiner\application::compile ();

?>