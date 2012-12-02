<?php 

// Prihlasovacie údaje k databáze
define ('opiner_mysqlServer', 'localhost');
define ('opiner_mysqlUsername', 'root');
define ('opiner_mysqlPassword', 'root');
define ('opiner_mysqlDatabase', 'test');
define ('opiner_mysqlPrefix', '');

// PHP konfigurácia
ini_set ('error_log', Opiner::root . '/cache/erros.log');
ini_set ('error_reporting', E_ALL);
ini_set ('display_errors', E_ALL);
ini_set ('allow_url_open', 1);
ini_set ('mbstring.internal_encoding', 'UTF-8');
ini_set ('mbstring.language', 'Slovak');

?>