<?php

namespace opiner\parser;

function call ($parser, $value, $format)
{
	if ($parser == 'call') return $value;
	if (function_exists ('\\opiner\\parser\\' . $parser))
	return $parser ($value, $format);
	return $value;
}

function webalize ($value, $format)
{

}