<?php

namespace Opiner;

/**
 * Spravca modelu, tvorca queries
 *
 * Tato trieda je funkcnym rozsirenym logiky modelovej
 * spoluprace s databazou. Kym objekty abstraktnej
 * triedy Model predstavuju vzdy iba jeden riadok tabulky,
 * tak objekty tejto triedy sluzia na tvorenie
 * querov na databazu a vytahovanie jednotlivych
 * riadkov tabulky uz ako objetov dcerskych tried
 * abstraktnej triedy Model.
 *
 * Cela logika tejto triedy sa da rozdelit na dve casti.
 * Konkretne na volenie podmienok vyberu riadkov a ich
 * naslednom vytahovani z databazy.
 *
 * @author Tomas Tatarko
 * @since 0.5
 */

class ModelHandler
{

	protected
		$tableName,
		$className,
		$fields = [],
		$primaryKey,
		$conditions = [],
		$order,
		$limit = 1000,
		$offset = 0,
		$scopes = [];



	/**
	 * Vytvorenie noveho objektu
	 *
	 * V prvom kroku sa novemu objektu nastavia jeho
	 * premenne na zaklade vstupnych argumentov. Nasledne
	 * sa kontroluje, ci hladany model vobec existuje a ak nie,
	 * tak sa vyhodi vynimka, a teda konci kompilovanie frameworku.
	 * Nasledne sa pokracuje nacitavanim deklarovanych scopes,
	 * co je zhrnutie viacerych podmienok pod jeden nazov.
	 * Na zaver sa samotny novo vytvoreny objekt vrati.
	 *
	 * @param string Aky model spravujeme
	 * @param array Informacie o jednotlivych bunkach tohto modelu
	 * @param string Ktora bunka predstavuje primarny kluc?
	 * @return object
	 */

	public function __construct ($model, $data, $primaryKey = null)
	{
		$this -> tableName = $model;
		$this -> className = '\\Opiner\\Model\\' . $model;
		$this -> fields = $data;
		$this -> primaryKey = $primaryKey;
		
		if (!class_exists ($this -> className))
		throw new Exception ($this -> tableName, 302);
		
		foreach (get_class_methods ($this -> className) as $method)
		if (substr ($method, 0, 5) == 'scope' and $method !== 'scope')
		$this -> scopes [strtolower (substr ($method, 5))] = $method;
		
		return $this;
	}



	/**
	 * Volanie jednotlivych scopes
	 *
	 * Tato metoda zisti, ci volany scope vobec existuje. Ak nie,
	 * tak vyhodi vynimku, co zapricini ukoncenie kompilovania
	 * celeho frameworku. Ak scope existuje, tak ho zavola
	 * a nasledne podla navratovej hodnoty aj nastavi podmienky,
	 * urci limit, zoradzovanie atd...
	 *
	 * @param string Ktory scope bude volany
	 * @param array Parametre volaneho scopu
	 * @return object
	 */

	public function __call ($method, $params)
	{
		$method = strtolower ($method);
		if (!isset ($this -> scopes [$method]))
		throw new Exception ($method . '|' . $this -> tableName, 303);
		
		foreach ($params as $index => $value)
		$params [$index] = var_export ($value, true);
		eval ('$params = ' . $this -> className . '::' . $this -> scopes [$method] . '(' . implode (', ', $params) . ');');
		
		foreach ($params as $index => $value)
		$this -> $index ($value);

		return $this;
	}



	/**
	 * Prida nove podmienky vyberu
	 *
	 * Aktualne pole podmienok vyberu dat z databazy zluci
	 * s polom predanym v argumente tejto metody. Ak sa
	 * programator pokusi urobit podmienku na neexistujucu
	 * bunku tabulky, dochadza k odoslaniu vynimky, a tym
	 * padom k ukonceniu kompilovania frameworku.
	 *
	 * @param array Pole podmienok
	 * @return object
	 */

	public function conditions ($argument)
	{
		if (!is_array ($argument)) return $this;
		foreach ($argument as $index => $value)
		{
			$field = substr ($index, 0, strpos ($index, '#'));
			if (!isset ($this -> fields [$index]))
			throw new Exception ($index, 300);
			$this -> conditions [] = [$index, $value];
		}
		return $this;
	}



	/**
	 * Prida jednu samotnu podmienku
	 *
	 * Tato metoda vlastne len zavola metodu conditions()
	 * s ocakavanym argumentom typu array.
	 *
	 * @param string Nazov bunky
	 * @param mixed Ocakavana hodnota tej bunky
	 * @return object
	 */

	public function condition ($fied, $value)
	{
		return $this -> conditions ([$field => $value]);
	}



	/**
	 * Premaze pole podmienok
	 *
	 * Ak potrebuje programator premazat pole podmienok skor
	 * nastavenych, tak mu na to postaci jednoduche volanie
	 * tejto metody bez argumentov
	 *
	 * @return object
	 */

	public function clearConditions ()
	{
		$this -> conditions = [];
		return $this;
	}



	/**
	 * Nastavi pole podmienok bez ohladu na predchadzuje podmienky
	 *
	 * Ak programator potrebuje nastavit podmienky odznova, tak
	 * mu staci zavolat tuto metodu. Ta najprv zavola metodu
	 * clearConditions(), co sposobi premazanie aktualne
	 * zvolenych podmienok a potom nasledne zavola metodu
	 * conditions(), ktora prida nove podmienky predane v argumente
	 * tejto metody.
	 *
	 * @param array Ake podmienky maju byt nastavene?
	 * @return object
	 */

	public function setConditions ($conditions)
	{
		$this	-> clearConditions ()
			-> conditions ($conditions);
	}



	/**
	 * Nastavi MySQL limit podla predanej strany
	 *
	 * Pri strankovanom vybere riadkov tabulky
	 * staci programatorovi zavolat tuto funkciu,
	 * do argumentov predat aktualnu stranu
	 * a pocet poloziek na jednu stranu. Tato metoda
	 * nasledne nastavi pri tvoreni poziadavky
	 * na databazu spravny limit aj offset
	 *
	 * @param int Aktualna strana
	 * @param int Limit poloziek na jednu stranu
	 * @return object
	 */

	public function setLimitByPage ($page, $limit)
	{
		$this -> limit = max (intval ($limit), 1);
		$this -> offset = (max (intval ($page), 1) - 1) * $this -> limit;
		return $this;
	}



	/**
	 * Nastavi limit poloziek
	 *
	 * Pri ziskavani poloziek z databazy sa nacita
	 * zvoleny maximalny pocet riadkov
	 *
	 * @param int Ocakavany pocet poloziek
	 * @return object
	 */

	public function limit ($limit)
	{
		$this -> limit = max (intval ($limit), 1);
		return $this;
	}



	/**
	 * Nastavi offset poloziek
	 *
	 * Pri ziskavani poloziek z databazy sa nacitaju
	 * polozky od i-tej pozicie. To znamena ze pocet
	 * poloziek predany ako argument tejto metody sa
	 * pri vypise poloziek vynecha.
	 *
	 * @param int Ocakavany offset poloziek
	 * @return object
	 */

	public function offset ($offset)
	{
		$this -> offset = max (intval ($offset), 0);
		return $this;
	}



	/**
	 * Urci podla coho sa budu zoradzovat vysledky
	 *
	 * Tato metoda vlastne vytvori ORDER BY klauzulu
	 * poziadavky posielanej na databazu
	 * 
	 * @param string Podla ktorej bunky zoradit vysledky
	 * @param string V akom poradi?
	 * @return object
	 */

	public function order ($field, $by = 'asc')
	{
		if (!isset ($this -> fields [$field]))
		throw new Exception ($index, 305, $this -> tableName);
		$this -> order = $field . '#' . $by;
		return $this;
	}



	/**
	 * Vrati jeden konkretny zaznam s unikatnym ID (primary key)
	 *
	 * Tato metoda narozdiel od find() vrati vzdy iba jeden jediny
	 * zaznam. Na vyhladanie tohto zaznamu sa pri tom pouziju
	 * vsetky podmienky, scopy (a podobne), ktore boli uz skor nastavene.
	 *
	 * @param int Aktualna hodnota unikatne PRIMARY KEY 
	 * @return object Podla aktivneho modelu
	 */

	public function findByPk ($id)
	{
		if(!$data = Framework::module ('database')
			-> select ()
			-> table ($this -> tableName)
			-> where (array_merge ($this -> conditions, [$this -> primaryKey => $id]))
			-> order ($this -> primaryKey)
			-> fetchRow ())
		return false;
		return new $this -> className ($data);
	}



	/**
	 * Vrati vsetky zaznamy
	 *
	 * Tato metoda vrati pole objektov daneho modelu,
	 * ktore vyvohuju zvolenym podmienkam. Ak sa ziaden
	 * takyto riadok nenajde, vrati sa aj napriek tomu
	 * prazdne pole! Nenastava situacia, aby sa vratila
	 * false hodnota, vyhodila vynimka, ci nieco podobne.
	 *
	 * @return array Pole objetov daneho modelu
	 */

	public function find ()
	{
		$return = [];
		foreach (Framework::module ('database')
			-> select ()
			-> table ($this -> tableName)
			-> where ($this -> conditions)
			-> order ($this -> order)
			-> limit ($this -> limit, $this -> offset)
			-> fetch () as $row)
		$return [] = new $this -> className ($row);
		return $return;
	}



	/**
	 * Vrati vsetky zaznamy ako JSON
	 *
	 * Tato metoda zoberie vsetky mozne vysledne riadky
	 * a zakoduje ich do JSON kodu, ktory moze byt dalej pouzity.
	 *
	 * @return string
	 * @since 0.6
	 */

	public function getAsJson ()
	{
		$query = Framework::module ('database')
			-> select ()
			-> table ($this -> tableName);
		if (!empty ($this -> conditions)) $query -> where ($this -> conditions);
		if (!empty ($this -> order)) $query -> order ($this -> order);
		return $query -> limit ($this -> limit, $this -> offset) -> fetchAsJson ();
	}



	/**
	 * Ulozi vsetky zaznamy do json suboru
	 *
	 * Do suboru, ktoreho adresa je predana v prvom
	 * argumente tejto metody sa ulozi JSON so vsetkymi
	 * riadkami, ktore vyhovuju navolenych podmienkam
	 *
	 * @param string Adresa suboru, do ktoreho sa maju zapisat data
	 * @return object
	 * @since 0.6
	 */

	public function getIntoJsonFile ($file)
	{
		file_put_contents ($file, $this -> getAsJson ());
		return $this;
	}



	/**
	 * Vrati vsetky zaznamy ako CSV
	 *
	 * Tato metoda zoberie vsetky mozne vysledne riadky
	 * a zakoduje ich do CSV kodu, ktory moze byt dalej pouzity.
	 *
	 * @param string Retazec oddelujuci bunky v ramci riadku
	 * @return string
	 * @since 0.6
	 */

	public function getAsCsv ($delimiter = ';')
	{
		$query = Framework::module ('database')
			-> select ()
			-> table ($this -> tableName);
		if (!empty ($this -> conditions)) $query -> where ($this -> conditions);
		if (!empty ($this -> order)) $query -> order ($this -> order);
		return $query -> limit ($this -> limit, $this -> offset) -> fetchAsCsv ($delimiter);
	}



	/**
	 * Ulozi vsetky zaznamy do csv suboru
	 *
	 * Do suboru, ktoreho adresa je predana v prvom
	 * argumente tejto metody sa ulozi struktura so vsetkymi
	 * riadkami, ktore vyhovuju navolenych podmienkam
	 *
	 * @param string Adresa suboru, do ktoreho sa maju zapisat data
	 * @param string Retazec oddelujuci jednotlive bunky v ramci riadku
	 * @return object
	 * @since 0.6
	 */

	public function getIntoCsvFile ($file, $delimiter = ';')
	{
		file_put_contents ($file, $this -> getAsCsv ($delimiter));
		return $this;
	}	
}
?>