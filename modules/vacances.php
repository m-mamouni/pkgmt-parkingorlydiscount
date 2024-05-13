<?php
defined('_PKMGMT') or die('Restricted access');

class Vacances extends reservation
{
	var $annee = null;
	private $vacancesdata = null;
	var $vacancesurl = null;
	var $date = null;
	var $formatdate = null;
	var $vacances = false;
	
	function __construct()
	{
		$this->vacancesurl = PKMGMT_PLUGIN_DIR . DS ."media".DS."vacances.xml";
		static $vacance;
		if (!isset($vacance))
		{
			$this->vacancesdata = simplexml_load_file($this->vacancesurl);
			$vacance = "done";
		}
	
	}
	
	function setDate( $date, $format )
	{
		$this->date = DateTime::createFromFormat($format, $date);
		$this->formatdate = $format;
	}
	
	public static function &getInstance($force = false)
	{
			static $instance;

			if (!isset($instance) || empty($instance) || $force )
			{
					$vacance = new Vacances();
					$instance = $vacance;
			}
			return $instance;
	}
	function proceed()
	{
		$result = $this->vacancesdata->xpath('//root/calendrier');
		foreach ( $result as $index => $elem )
		{
			foreach ( $elem->zone as $name => $zone )
			{
				$libelle = $zone->attributes();
				$libelle = $libelle['libelle'];
				if ( ! preg_match("/^A$|^B$|^C$/", $libelle) )
					continue;
				foreach( $elem->zone->vacances as $vacance )
				{
					$value = $vacance->attributes()->debut . " 00:00:00";
					$date_debut = DateTime::createFromFormat("Y/m/d H:i:s", $value);
					$date_debut->sub(new DateInterval("P2D"));
					$value = $vacance->attributes()->fin . " 23:59:59";
					$date_fin = DateTime::createFromFormat("Y/m/d H:i:s", $value);
					$date_fin->add(new DateInterval("P2D"));
					if ( $this->date >= $date_debut && $this->date <= $date_fin )
					{
						$this->vacances = true;
						return;
					}
				}
			}
		}
	}
	
	function isVacances($force = false)
	{
		if ( is_null( $this->vacances ) || $force)
			$this->proceed();
		return $this->vacances;
	}
}
?>