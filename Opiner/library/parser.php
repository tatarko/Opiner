<?php

namespace Opiner\Parser;



/**
 * Volanie ostatnych parserov
 *
 * @param string Ktory parser chceme zavolat
 * @param string Premenna, ktoru chceme zformatovat
 * @param string Dotatocne informacie pre parser
 * @return string
 * @author Tomas Tatarko
 * @since 0.3
 */

function call ($parser, $value, $format = null)
{
	if ($parser == 'call') return $value;
	if (function_exists ('\\opiner\\parser\\' . $parser))
	return $parser ($value, $format);
	return $value;
}



/**
 * Zformatuje premennu do podoby, ktora moze ist do url adries
 *
 * @param string Premenna, ktoru chceme zformatovat
 * @return string
 * @author Tomas Tatarko
 * @since 0.3
 */

function webalize ($value)
{

}



/**
 * Datum vo formate
 *
 * Z predaneho timestampu alebo formatovaneho datumu urobi
 * vystup v pozadovanej forme
 *
 * @param int Datum a cas v jeho primarnych formach
 * @param string Ako ho chceme vypisat?
 * @return string Napriklad 13.09.2013
 * @author Tomas Tatarko
 * @since 0.3
 */

function date ($date, $format = '%d.%m.%Y')
{
	return strftime ($format, $date);
}



/**
 * Z predaneho URL linku urobi html odkaz
 *
 * @param string Datum a cas v jeho primarnych formach
 * @param string Ako ho chceme vypisat?
 * @return string
 * @author Tomas Tatarko
 * @since 0.3
 */

function url ($value)
{
	return '<a href="' . $value . '">' . $value . '</a>';
}

?>