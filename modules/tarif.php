<?php
defined('_PKMGMT') or die('Restricted access');
require_once PKMGMT_PLUGIN_DIR . DS . "modules"	. DS . "feries.php";
require_once PKMGMT_PLUGIN_DIR . DS . "modules"	. DS . "vacances.php";

class Tarif
{
    var $_id                = 0;
    var $ref                = null;
    var $_db                = null;
    var $_table_reservation = null;
    var $_tableresatarif    = null;
    var $_tabletarif        = null;
    var $_tableservices     = null;
    var $_site              = "orly";
    var $_type              = "int";
    var $_resa              = null;
    var $_dates             = null;
    var $service            = null;
    var $_vacances          = false;
    var $ofrais             = array('dimanche'=>0,'nuit' => 0,'ferie' => 0);
    var $tva                = array('tva1'=> 10,'tva2'=> 20);
    var $codepromo          = null;
    var $base               = 0;
    var $base_orig          = 0;
    var $promo              = 0;
    var $supplement         = 0;
    var $supp_spe           = 0;
    var $supp_lav           = 0;
    var $supp_nuit          = 0;
    var $nbr_jour           = 0;
    var $nbr_jour_supp      = 0;
    var $ferie_aller        = 0;
    var $ferie_retour       = 0;
    var $nuit_aller         = 0;
    var $nuit_retour        = 0;
    var $nuit_status        = 0;
    var $dimanche_aller     = 0;
    var $dimanche_retour    = 0;
    var $pers_supp          = 0;
    var $tarif_pers         = 0;
    var $etat_des_lieux     = 0;
    var $lavage             = 0;
    var $remorque           = 0;
    var $oubli              = 0;
    var $premium            = 0;
    var $notification       = 0;
    var $smssend            = 0;
    var $categorie          = "A";
    var $_tarif             = 0;
    var $nbr_jour_frais     = 0;
    var $start_date_promo   = "01/11/2016";
    var $end_date_promo     = "15/12/2016";


    function __construct()
    {
    }

    function init($database, $resa, $site="orly")
    {
        $this->_site = $site;
        $this->db = new wpdb($database['dbuser'],
                 $database['dbpassword'],
                 $database['dbname'],
                 $database['dbhost'].':'.$database['dbport']);

		$this->tva = $tva;
		$this->_table_reservation = $database['table_reservation'];
		if ( $resa['type'] == 'pre' )
			$this->premium = 1;
		$this->_resa = $resa;
		$this->_type = $resa["type"];
		$this->_dates = new stdClass();
		if ( array_key_exists('codepromo', $resa))
			$this->codepromo = $resa['codepromo'];
		$date_pattern = "/(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/[0-9]{4} (2[0-3]|1[0-9]|0?[0-9]):([0-5][0-9])/";
		if ( !preg_match( $date_pattern, $resa['navette'] ) )
			$this->_dates->navette = new DateTime($resa['navette']);
		else
			$this->_dates->navette = DateTime::createFromFormat("d/m/Y H:i", $resa['navette']);
		if ( !preg_match( $date_pattern, $resa['date_retour'] ) )
			$this->_dates->date_retour = new DateTime($resa['date_retour']);
		else
			$this->_dates->date_retour = DateTime::createFromFormat("d/m/Y H:i", $resa['date_retour']);
		$navette = clone $this->_dates->navette;
		$navette->setTime(0,0);
		$retour = clone $this->_dates->date_retour;
		$retour->setTime(0,0);
		$this->_dates->date_diff = date_diff($navette, $retour, true);
		$this->setBaseTarif();
	}

	function get_home_tarifs($database, $date_aller, $date_retour, $site="orly") {
	    $this->_site = $site;

		$ret = array();
		try {
			$this->_dates = new stdClass();
			$date_pattern = "/(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/[0-9]{4}/";
			if ( !preg_match( $date_pattern, $date_aller ) )
				$this->_dates->navette = new DateTime($date_aller);
			else
				$this->_dates->navette = DateTime::createFromFormat("d/m/Y H:i", $date_aller);
			if ( !preg_match( $date_pattern, $date_retour ) )
				$this->_dates->date_retour = new DateTime($date_retour);
			else
				$this->_dates->date_retour = DateTime::createFromFormat("d/m/Y H:i", $date_retour);
            $this->_type = 'ext';
			$ret['ext'] = $this->getBaseTarif();
			$this->_tabletarif = $database['table_tarifs_int'];
            $this->_type = 'int';
			$ret['int'] = $this->getBaseTarif();
			$ret['nbr'] = $this->nbr_jour;
			return $ret;
		} catch (Exception $e ){
			return array("ext" => "Error", "int" => "Error", "nbr" => "Error");
		}
	}

	public static function &getInstance($force = false )
	{
			static $instance;

			if (!isset($instance) || empty($instance) || $force )
			{
					$tarif = new Tarif();
					$instance = $tarif;
			}
			return $instance;
	}


	function getTarif()
	{
		return( $this->base );
	}

    function getExternalFees() {
        $opts = array(
          'http' => array(
            'method'  => 'GET',
            'header'  => "Content-Type: application/json\r\n",
            'timeout' => 60,
          ),
          'ssl' => array(
            'verify_peer'=>false,
            'verify_peer_name'=>false
          )
        );
        $context  = stream_context_create($opts);

        switch (strtolower($this->_site)) {
          case "roissy":
            $aeroport = "2";
            break;
          case "zaventem":
            $aeroport = "3";
            break;
          default :
            $aeroport = "1";
        }
        $parking_type = "0";
        if ($this->_type ==  'int') {
            $parking_type = "1";
        }
        $url = 'https://www.parkineo.com/include/ajax/calculatePrix2.php';
        $depart = $this->_dates->navette->format("d/m/Y");
        $retour = $this->_dates->date_retour->format("d/m/Y");
        $nb_pax = max($this->_resa['nbr_aller'], $this->_resa['nbr_retour']);
        $params = sprintf("depart=%s&retour=%s&aeroport=%s&type_id=1&parking_type=%s&nb_pax=%d", $depart, $retour, $aeroport, $parking_type, $nb_pax);
        $result = file_get_contents(sprintf("%s?%s",$url, $params), false, $context);
        return json_decode($result);
    }

	function getArrayTarif()
	{
		$return                   = array();
		$return["base"]           = $this->base;
		$return["supplement"]     = $this->supplement;
		$return["supp_spe"]       = $this->supp_spe;
		$return["nbr_jour"]       = $this->nbr_jour;
		$return["nbr_jour_supp"]  = $this->nbr_jour_supp;
		$return["ferie_aller"]    = $this->ferie_aller;
		$return["ferie_retour"]   = $this->ferie_retour;
		$return["nuit_aller"]     = $this->nuit_aller;
		$return["nuit_retour"]    = $this->nuit_retour;
		$return["nuit_status"]    = $this->nuit_status;
		$return["pers_supp"]      = $this->pers_supp;
		$return["tarif_pers"]     = $this->tarif_pers;
		$return["etat_des_lieux"] = $this->etat_des_lieux;
		$return["lavage"]         = $this->lavage;
		$return["remorque"]       = $this->remorque;
		$return["oubli"]          = $this->oubli;
		$return["categorie"]      = $this->categorie;
		$return["tarif"]          = $this->_tarif;
		$return["premium"]        = $this->premium;
		$return["notification"]   = $this->notification;
		$return["smssend"]        = $this->smssend;
		return $return;
	}

	function getTarifDetail($desc, $prix, $tva)
	{
		$ret            = array();
		$ret['tva']     = $tva;
		$ret['desc']    = $desc;
		$ret['prix']    = $prix;
		$ret['prixht']  = $this->HT($prix, $this->tva[$tva]);
		$ret['prixtva'] = $prix - $ret['prixht'];
		return $ret;
	}

	function getArrayDetail($style = true)
	{
		$return = array();

		$return[] = $this->getTarifDetail("Tarif pour " . $this->nbr_jour . " jour(s)", 0,'tva2');
		$return[] = $this->getTarifDetail("Transfert le " . $this->_dates->navette->format("Y-m-d"), $this->base / 2, 'tva2');
		$return[] = $this->getTarifDetail("Transfert le " . $this->_dates->date_retour->format("Y-m-d"),$this->base / 2,'tva2');

		return $return;
	}

	function getTableHTMLDetail($style = true)
	{
		$return = "";
		$return .= "<table align=center style='font-family: \"Lucida Sans Unicode\", \"Lucida Grande\", Sans-Serif; font-size: 12px;'>";
		$return .= "<thead>";
		$return .= '<tr><th style="background: #b9c9fe; padding: 8px;" colspan=2 >D&eacute;tails</th></tr>';
		$return .= "</thead>";
		$return .= "<tbody>";

		$return .= $this->TRBODY("Tarif pour " . $this->nbr_jour . " jour(s)", $this->base);

		$return .= "</tbody>";
		$return .= "<tfoot>";
		$return .= $this->TR("TOTAL", $this->base . " €");
		$return .= "</tfoot>";
		$return .= "</table>";
		return $return;
	}

	private function TRBODY($desc, $prix)
	{
		return "<tr><td style='border-bottom: 1px solid #fff; color: #669;border-top: 1px solid #fff;background: #e8edff;padding: 8px;'>$desc</td><td style='border-bottom: 1px solid #fff; color: #669;border-top: 1px solid #fff;background: #e8edff;padding: 8px;'>$prix</td></tr>\n";
	}

	private function TR($desc, $prix)
	{
		return "<tr><td style='border-bottom: 1px solid #fff; color: #669;border-top: 1px solid #fff;background: #e8edff;padding: 8px;'>$desc</td><td style='border-bottom: 1px solid #fff; color: #669;border-top: 1px solid #fff;background: #e8edff;padding: 8px;'>$prix</td></tr>\n";
	}

    private function isPromoPeriode()
    {
        $start_date_promo = $this->start_date_promo . " 00:00";
        $end_date_promo = $this->end_date_promo . " 23:59";
//      if ( $this->_resa['mobile'] = "+33638380854" )
//      {
//          print_log($start_date_promo, false);
//          print_log($end_date_promo, true);
//      }
        $promo_start = DateTime::createFromFormat("Y-m-d H:i", $start_date_promo);
        $promo_end = DateTime::createFromFormat("Y-m-d H:i", $end_date_promo);

//      $promo_start = DateTime::createFromFormat("d/m/Y H:i", $start_date_promo);
//      $promo_end = DateTime::createFromFormat("d/m/Y H:i", $end_date_promo);
        $start = $this->_dates->navette;
        //$end = $this->_dates->date_retour;

//      if ( $this->_resa['mobile'] = "+33638380854" )
//      {
//          print_log("in", false);
//          print_log("vacances", false);
//          if ($this->_vacances)
//              print_log("oui", false);
//          else
//              print_log("non", false);
//          print_log("promo", false);
//          print_log($start, false);
//          print_log($promo_start, false);
//          print_log($promo_end, false);
//          print_log($promo_end2, false);
//      }

        if ( !$this->_vacances && ( $start >= $promo_start && $start <= $promo_end ) )
        {
//          if ( $this->_resa['mobile'] = "+33638380854" )
//          {
//              print_log("return true", false);
//          }
            return true;
        }
        return false;
    }

	private function promotion()
	{
		return;
		$promo_start = DateTime::createFromFormat("d/m/Y H:i", "01/11/2014 00:00");
		$promo_end = DateTime::createFromFormat("d/m/Y H:i", "15/12/2015 23:59");
		$navette = $this->_dates->navette;

		if ( ( $navette >= $promo_start ) && ( $navette <= $promo_end ) )
		{
			switch (true)
			{
				case ($this->nbr_jour < 4):
					return 0;
				case ($this->nbr_jour >= 4 && $this->nbr_jour <= 5):
					return 1;
				case ($this->nbr_jour >= 6 && $this->nbr_jour <= 6):
					return 2;
				case ($this->nbr_jour == 7):
					return 3;
				case ($this->nbr_jour >= 8 && $this->nbr_jour <= 9):
					return 4;
				case ($this->nbr_jour >= 10 && $this->nbr_jour <= 13):
					return 5;
				case ($this->nbr_jour >= 14 && $this->nbr_jour <= 21):
					return 6;
				case ($this->nbr_jour >= 22 && $this->nbr_jour <= 28):
					return 10;
				case ($this->nbr_jour >= 29):
					return 14;
			}
		}
		return 0;

	}

	function setBaseTarif()
	{
        $eQuote = $this->getExternalFees();
        $this->nbr_jour = $eQuote->nb_jour_reel;
        $this->base_orig = $eQuote->total;
        $this->base = $eQuote->total;
	}

	function getBaseTarif()
	{
        $eQuote = $this->getExternalFees();
        $this->nbr_jour = $eQuote->nb_jour_reel;
        $this->base_orig = $eQuote->total;
        $this->base = $eQuote->total;
		return $this->base;
	}

	private function setPromo()
	{
		if ( array_key_exists("codepromo", $this->service)
			&& ($this->codepromo == $this->service["codepromo"]->valeur)
			&& !$this->_vacances
			&& $this->nbr_jour > $this->nbr_jour_frais
			&& array_key_exists("promo", $this->service)) {
			$this->promo = $this->calcul($this->base,$this->service["promo"]->valeur );
			$this->base -= $this->promo;
		  }
		 if ( array_key_exists("codepromojour", $this->service)
			&& ($this->codepromo == $this->service["codepromojour"]->valeur)
			&& $this->nbr_jour > $this->nbr_jour_frais
			&& array_key_exists("promojour", $this->service)) {
			$this->promo = $this->calcul($this->base,$this->service["promojour"]->valeur );
			$this->base -= $this->promo;
		  }
		if ( array_key_exists("codepromoadmin", $this->service)
			&& substr($this->codepromo, 0, 8) == $this->service["codepromoadmin"]->valeur ) {
			$this->promo = $this->calcul($this->base, substr($this->codepromo,-2) . "%");
			$this->base -= $this->promo;
		}
		if ( array_key_exists("codeagence", $this->service)
			&& substr($this->codepromo, 0, 6) == $this->service["codeagence"]->valeur
			&& array_key_exists("agence", $this->service) ) {
			$this->promo = $this->calcul($this->base, $this->service["agence"]->valeur);
			$this->base -= $this->promo;
		}
	}

	private function frais()
	{
		$this->nbr_jour_frais = 0;

		if ( array_key_exists('nbr_jour_frais', $this->service) )
			$this->nbr_jour_frais = $this->service['nbr_jour_frais']->valeur;
		$ferie = new Feries();
		if ($this->ofrais['ferie'] == 1 && $ferie->est_ferie($this->_dates->navette->format("Y-m-d"))
			&& $this->nbr_jour > $this->nbr_jour_frais)
			$this->ferie_aller = 1;
		if ($this->ofrais['ferie'] == 1 && $ferie->est_ferie($this->_dates->date_retour->format("Y-m-d"))
			&& $this->nbr_jour > $this->nbr_jour_frais)
			$this->ferie_retour = 1;
		if ($this->ofrais['nuit'] == 1
			&& $this->est_nuit((int)$this->_dates->navette->format("H"))
			&& $this->nbr_jour > $this->nbr_jour_frais
			) {
			$this->supp_spe = 1; $this->supp_nuit = 1; $this->nuit_aller = 1;
			}
		if ($this->ofrais['nuit'] == 1
			&& $this->est_nuit((int)$this->_dates->date_retour->format("H"))
			&& $this->nbr_jour > $this->nbr_jour_frais
			){
			$this->supp_spe = 1; $this->supp_nuit = 1; $this->nuit_retour = 1;

			}
		if ($this->ofrais['dimanche'] == 1 && $this->est_dimanche((int)$this->_dates->navette->format("w"))
			&& $this->nbr_jour > $this->nbr_jour_frais){
			$this->supp_spe = 1; $this->dimanche_aller = 1;}
		if ($this->ofrais['dimanche'] == 1 && $this->est_dimanche((int)$this->_dates->date_retour->format("w"))
			&& $this->nbr_jour > $this->nbr_jour_frais) {
			$this->supp_spe = 1; $this->dimanche_retour = 1;}
	}

	function calcul($valeur, $baisse)
	{
	     if ( preg_match('/%$/', $baisse) )
	     {
	     	$baisse = (int)substr($baisse, 0, -1);
	     	$baisse = $valeur * ($baisse/100);
	     }
	     return ($baisse);
	}

	function est_nuit( $heure )
	{
		if ( $heure >= $this->service["nuit_debut"]->valeur )
		{
			$this->nuit_status = 1;
			return true;
		}
		if ( $heure < $this->service["nuit_fin"]->valeur )
		{
			$this->nuit_status = 1;
			return true;
		}
		return false;
	}

	function est_dimanche( $w )
	{
		if ($w == 7 || $w == 0 )
			return true;
		return false;
	}

	function setTarif( $prix )
	{
		$this->_tarif = $prix;
	}

	function setSuppTarif($id)
	{
		$this->loadDbTarif($id);

		$n_user_max = $this->service["n_user_max"]->valeur;
		if (!array_key_exists('nbr_aller', $this->_resa) || ! isset( $this->_resa['nbr_aller'] ) )
			$this->_resa['nbr_aller'] = $this->_resa['nbr_retour'];
		if ( isset( $this->_resa['nbr_aller'] ) && isset( $this->_resa['nbr_retour']) )
		{
			$max = max($this->_resa['nbr_aller'], $this->_resa['nbr_retour']);
			if ( $max > $n_user_max )
				$this->pers_supp = $max - $n_user_max;
		}
		$this->supplement = ( $this->pers_supp  * $this->service["pers_supp"]->valeur);
		$this->supplement += ( $this->etat_des_lieux * $this->service["etat_des_lieux"]->valeur);
		$this->supplement += ( $this->remorque * $this->nbr_jour * $this->service["remorque"]->valeur);
		$this->supplement += ( $this->oubli * $this->service["oubli"]->valeur);
			if ( array_key_exists('premium', $this->service ) )
		$this->supplement += ( $this->premium * $this->service["premium"]->valeur);
		if ( $this->lavage)
		{
			$key = "lav_" . $this->lavage;
			$this->supp_lav = $this->service[$key]->valeur;
			$key = "lav_cat" . $this->categorie;
			$this->supp_lav += $this->service[$key]->valeur;
			$this->supplement +=  $this->supp_lav;
		}
		$this->setTarif($this->base + $this->supplement);
	}

	function loadDbTarif($id = 0)
	{
		if (!$id)
			return;
		$fmtsql = "SELECT * FROM `" . $this->_tableresatarif . "` WHERE `resaid` = '". $id."'";
		$result = $this->db->get_row($fmtsql);
		if ( !$result )
		{
			$this->setDbTarif($id);
			return;
		}
		$this->pers_supp = $result->pers_supp;
		$this->etat_des_lieux = $result->etat_des_lieux;
		$this->remorque = $result->remorque;
		$this->oubli = $result->oubli;
		if ( isset($result->premium) )
			$this->premium = $result->premium;
		$this->lavage = $result->lavage;
		$this->categorie = $result->categorie;
		$this->notification = $result->notification;
		$this->smssend = $result->smssend;
	}

	function setDbTarif($id)
	{
		$data = array();
		$data['resaid'] = $id;
		$data['base'] = $this->base;
		$data['ferie_aller'] = $this->ferie_aller;
		$data['ferie_retour'] = $this->ferie_retour;
		$data['nuit_aller'] = $this->nuit_aller;
		$data['nuit_retour'] = $this->nuit_retour;
		$data['nuit_status'] = $this->nuit_status;
		$data['dimanche_aller'] = $this->dimanche_aller;
		$data['dimanche_retour'] = $this->dimanche_retour;
		$data['pers_supp'] = $this->pers_supp;
		$data['nbr_jour'] = $this->nbr_jour;
		$data['nbr_jour_supp'] = $this->nbr_jour_supp;
		$data['etat_des_lieux'] = $this->etat_des_lieux;
		$data['remorque'] = $this->remorque;
		$data['oubli'] = $this->oubli;
		$data['premium'] = $this->premium;
		$data['lavage'] = $this->lavage;
		$data['categorie'] = $this->categorie;
		$data['notification'] = $this->notification;
		$data['smssend'] = $this->smssend;
		$this->db->insert($this->_tableresatarif, $data);
	}

	function _getServices()
	{
		$fmtsql = "SELECT `name`, `valeur` FROM `{$this->_tableservices}`" ;
		$this->service = $this->db->get_results( $fmtsql, OBJECT_K);
	}

	function update_tarif($id,$elem, $valeur)
	{
		$config = new Config();
		$obj = array();
		$obj[$elem] = $valeur;
		$this->db->update($this->_tableresatarif, $obj , array('resaid' => $id));
		$prix_resa = $this->_tarif;
		$this->db->update( $this->_tablereservation, array('prix_resa' => $prix_resa), array( 'id' => $id));
	}

	private function HT($prix, $tva)
	{
		$ht = round($prix / ( 1 + ( $tva / 100 ) ), 2);
		return $ht;
	}

}
