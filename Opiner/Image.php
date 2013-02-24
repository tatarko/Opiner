<?php

namespace Opiner;



/**
 * Spracovanie obrazkov
 *
 * Tato jednoducha trieda sluzi na pracu s obrazkami.
 * V aktualnej verzii ponuka moznost zmensit obrazok
 * na presne zadany rozmer a to bez deformovania pomeru
 * vysky a sirky (vdaka orezavaniu). Dalsou funkciou je
 * ulozenie obrazka do suboru alebo vystup do prehliadaca.
 *
 * @author Tomas Tatarko
 * @since 0.4
 */

class Image extends Object
{

	protected
		$filename,
		$width,
		$height,
		$type,
		$suffix,
		$palette;
	
	public static
		$defaultJpegQuality = 90;

	const
		white = 16777215,
		black = 0;



	/**
	 * Vytvorenie objektu, určenie základných premenných
	 *
	 * Pri vytvarani obrazka sa automaticky zistia informacie
	 * o tomto obrazku - ako napriklad rozmery, koncovka, MIME
	 * type. Zaroven sa trieda automaticky pokusi
	 * otvorit obrazok a ulozit ho do lokalnej palety.
	 *
	 * @param string Fyzická adresa obrázka
	 * @return object
	 */

	public function __construct ($filename)
	{
		$this -> filename = $filename;
		if (false === ($info = getimagesize ($this -> filename)))
		return false;
		$this -> width = $info [0];
		$this -> height = $info [1];
		$this -> type = $info ['mime'];
		$this -> suffix = substr ($this -> filename, strrpos ($this -> filename, '.') + 1);
		if ($this -> suffix == 'tmp' or strlen ($this -> suffix) != 3)
		{
			switch ($this -> type)
			{
				case 'image/jpeg': $this -> suffix = 'jpg'; break;
				case 'image/png': $this -> suffix = 'png'; break;
				case 'image/gif': $this -> suffix = 'gif'; break;
			}
		}
		switch ($this -> suffix)
		{
			case 'jpg': $this -> palette = imagecreatefromjpeg ($this -> filename); break;
			case 'png': $this -> palette = imagecreatefrompng ($this -> filename); break;
			case 'gif': $this -> palette = imagecreatefromgif ($this -> filename); break;
		}
		return $this;
	}



	/**
	 * Zmenšenie obrazku
	 *
	 * Inteligentne zmensenie obrazku na presne zadane rozmery.
	 * Pri tomto zmenseni nedochadza k roztiahnutiu obrazu sposobene
	 * rozlicnym pomerom sirky a vysky. Ak je tento pomer iny ako original
	 * obrazku, tak sa radsej zrezu okraje.
	 *
	 * @param int Šírka zmenšeného obrázka
	 * @param int Výška zmenšeného obrázka
	 * @param string Kam uložiť zmenšený obrázok
	 * @return object
	 */

	public function resize ($width, $height, $filename = false)
	{
		$ratio_orig = $this -> width / $this -> height;
		$ratio_new = $width / $height;
		if ($ratio_orig > $ratio_new)
		{
			$help = ceil ($this -> width / ($this -> height / $height));
			$helppalette = imagecreatetruecolor ($help, $height);
			imagefilledrectangle ($helppalette, 0, 0, $help, $height, self::white);
			imagecopyresampled ($helppalette, $this -> palette, 0, 0, 0, 0, $help, $height, $this -> width, $this -> height);
			$palette = imagecreatetruecolor ($width, $height);
			imagecopy ($palette, $helppalette, 0, 0, round (($help - $width)/2), 0, $width, $height);
		}
		else if ($ratio_orig < $ratio_new)
		{
			$help = ceil ($this -> height / ($this -> width / $width));
			$helppalette = imagecreatetruecolor ($width, $help);
			imagefilledrectangle ($helppalette, 0, 0, $width, $help, self::white);
			imagecopyresampled ($helppalette, $this -> palette, 0, 0, 0, 0, $width, $help, $this -> width, $this -> height);
			$palette = imagecreatetruecolor ($width, $height);
			imagecopy ($palette, $helppalette, 0, 0, 0, round (($help - $height)/2), $width, $height);
		}
		else if ($ratio_orig == $ratio_new)
		{
			$palette = imagecreatetruecolor ($width, $height);
			imagecopyresampled ($palette, $this -> palette, 0, 0, 0, 0, $width, $height, $this -> width, $this -> height);
		}
		if (empty ($filename))
		{
			$this -> palette = $palette;
			$this -> width = $width;
			$this -> height = $height;
			return $this;
		}
		else return $this -> output ($filename);
	}



	/**
	 * Vygenerovanie obrázka z kreslacieho plátna
	 *
	 * Tato funkcia ma jeden jediny argument, ktory
	 * ak je vynechany, tak dochadza k odoslaniu
	 * obrazku do prehliadaca uzivatela.
	 *
	 * @param string Kam uložiť obrázok
	 * @return object
	 */

	public function output ($filename = false)
	{
		if ($filename === false) Header ('Content-type: ' . $this -> type);
		else if (false === strpos ($filename, '.' . $this -> suffix)) $filename .= '.' . $this -> suffix;
		switch ($this -> suffix)
		{
			case 'jpg': imagejpeg ($this -> palette, $filename, self::$defaultJpegQuality);
			case 'png': imagepng ($this -> palette, $filename);
			case 'gif': imagegif ($this -> palette, $filename);
		}
		return $this;
	}



	/**
	 * Vrati aktualnu sirku obrazka
	 * @return int
	 * @since 0.5
	 */

	public function getWidth ()
	{
		return $this -> width;
	}



	/**
	 * Vrati aktualnu vysku obrazka
	 * @return int
	 * @since 0.5
	 */

	public function getHeight ()
	{
		return $this -> height;
	}



	/**
	 * Vrati koncovku suboru obrazka
	 * @return string
	 * @since 0.5
	 */

	public function getSuffix ()
	{
		return $this -> suffix;
	}



	/**
	 * Vrati mime typ obrazku
	 * @return string
	 * @since 0.5
	 */

	public function getType ()
	{
		return $this -> type;
	}
}
?>