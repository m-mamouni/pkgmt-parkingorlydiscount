<?php
defined('_PKMGMT') or die('Restricted access');

class Feries extends reservation
{
	var $annee = null;

	function __construct( )
	{
	}

	function dimanche_paques()
    {
        return date("Y-m-d", easter_date($this->annee));
    }
    function vendredi_saint()
    {
        $dimanche_paques = $this->dimanche_paques();
        return date("Y-m-d", strtotime("$dimanche_paques -2 day"));
    }
    function lundi_paques()
    {
        $dimanche_paques = $this->dimanche_paques();
        return date("Y-m-d", strtotime("$dimanche_paques +1 day"));
    }
    function jeudi_ascension()
    {
        $dimanche_paques = $this->dimanche_paques();
        return date("Y-m-d", strtotime("$dimanche_paques +39 day"));
    }
    function lundi_pentecote()
    {
        $dimanche_paques = $this->dimanche_paques();
        return date("Y-m-d", strtotime("$dimanche_paques +50 day"));
    }


    function jours_feries()
    {
		    $annee = $this->annee;
        $jours_feries = array
        (    $this->dimanche_paques()
        ,    $this->lundi_paques()
        ,    $this->jeudi_ascension()
        ,    $this->lundi_pentecote()

        ,    "$annee-01-01"        //    Nouvel an
        ,    "$annee-05-01"        //    Fête du travail
        ,    "$annee-05-08"        //    Armistice 1945
        ,    "$annee-07-14"        //    Fête nationale
        ,    "$annee-08-15"        //    Assomption
        ,    "$annee-11-11"        //    Armistice 1918
        ,    "$annee-11-01"        //    Toussaint
        ,    "$annee-12-25"        //    Noël
        );
        sort($jours_feries);
        return $jours_feries;
    }
    function est_ferie($jour)
    {
        //$jour = date("Y-m-d", strtotime($jour));

        $this->annee = substr($jour, 0, 4);
        return in_array($jour, $this->jours_feries());
    }
}
?>
