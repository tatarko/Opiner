<?php

// Kontrola jadra
if (!defined ('_root') or false !== strpos ($_SERVER['PHP_SELF'], '.inc.php'))
die (header ('HTTP/1.1 403 Forbidden') . 'Unauthorized Access!');

$this -> name = 'Default';
$this -> linkStyle ($this -> remote . 'css/bootstrap.css');
?>