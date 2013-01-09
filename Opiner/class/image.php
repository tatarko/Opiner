<?php

namespace Opiner;

class Image
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
	 *	Vytvorenie objektu, určenie základných premenných
	 *	@param string name Fyzická adresa obrázka
	 *	@return object self
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
	 *      Zmenšenie na presné zadané rozmery, pokiaľ pôvodný
	 *      obrázok nemá taký pomer šírky a výšky, bude orezaný
	 *      @param int width Šírka zmenšeného obrázka
	 *      @param int height Výška zmenšeného obrázka
	 *      @param string filename Kam uložiť zmenšený obrázok
	 *      @param boolean filename Default hodnota false, obrázok sa zmenší iba v rámci objektu
	 *      @return object self
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
	 *      Výsledné vygenerovanie obrázka z kreslacieho plátna
	 *      @param string filaname Kam uložiť obrázok
	 *      @param boolean false filename Ak má byť obrázok vykreslený do prehliadača
	 *      @return object self
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
}
?>