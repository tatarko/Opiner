<?php

namespace Opiner\Parser;



/* Volanie ostatnych parserov
 * @param string $parser: Ktory parser chceme zavolat
 * @param string $value: Premenna, ktoru chceme zformatovat
 * @param string $format: Dotacne informacie pre parser
 * @return string */

function call ($parser, $value, $format = null)
{
	if ($parser == 'call') return $value;
	if (function_exists ('\\opiner\\parser\\' . $parser))
	return $parser ($value, $format);
	return $value;
}



/* Zformatuje premennu do podoby, ktora moze ist do url adries
 * @param string $value: Premenna, ktoru chceme zformatovat
 * @return string */

function webalize ($value)
{

}



/* Z predaneho timestampu alebo formatovaneho datumu urobi
 * vystup v pozadovanej forme
 * @param int/string $date: Datum a cas v jeho primarnych formach
 * @param string $format: Ako ho chceme vypisat?
 * @return string */

function date ($date, $format = '%d.%m.%Y')
{
	return strftime ($format, $date);
}



/* Z predaneho URL linku urobi html odkaz
 * @param string $url: Datum a cas v jeho primarnych formach
 * @param string $format: Ako ho chceme vypisat?
 * @return string */

function url ($value)
{
	return '<a href="' . $value . '">' . $value . '</a>';
}

?>