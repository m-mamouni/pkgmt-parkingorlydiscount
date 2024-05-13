<?php
defined('_PKMGMT') or die('Restricted access');
require_once PKMGMT_PLUGIN_DIR . DS . "modules"	. DS . "feries.php";
require_once PKMGMT_PLUGIN_DIR . DS . "modules"	. DS . "vacances.php";

class Calcultarif
{
	var $_id			= 0;
	var $ref			= null;
	
	var $_db				= null;
	var $_table_reservation = null;
	var $_tableresatarif	= null;
	var $_tabletarif		= null;
	var $_tableservices		= null;
	var $_resa				= null;
	var $_dates				= null;
	var $service			= null;
	var $_vacances			= false;
	
	var $ofrais	= array('dimanche'=>0,'nuit' => 0,'ferie' => 0);
	var $tva = array('tva1'=> 10,'tva2'=> 20);
	
	var $tarif_fields = array('base', 
		'ferie_aller', 
		'ferie_retour', 
		'nuit_aller', 
		'nuit_retour', 
		'nuit_status', 
		'dimanche_aller', 
		'dimanche_retour', 
		'pers_supp', 
		'nbr_jour', 
		'nbr_jour_supp', 
		'etat_des_lieux', 
		'remorque', 
		'oubli', 
		'premium', 
		'lavage', 
		'categorie', 
		'notification', 
		'smssend');
	
	var $codepromo		= null;
	var $base			= 0;
	var $base_orig		= 0;
	var $promo			= 0;
	var $supplement		= 0;
	var $supp_spe		= 0;
	var $supp_lav		= 0;
	var $supp_nuit		= 0;
	var $nbr_jour		= 0;
	var $nbr_jour_promo	= 0;
	var $nbr_jour_supp	= 0;
	var $ferie_aller 	= 0;
	var $ferie_retour 	= 0;
	var $nuit_aller 	= 0;
	var $nuit_retour 	= 0;
	var $nuit_status 	= 0;
	var $dimanche_aller = 0;
	var $dimanche_retour= 0;
	var $pers_supp		= 0;
	var $tarif_pers		= 0;
	var $etat_des_lieux = 0;
	var $lavage			= 0;
	var $remorque		= 0;
	var $oubli			= 0;
	var $premium    	= 0;
	var $notification	= 0;
	var $smssend		= 0;
	var $categorie		= "A";
	var $_tarif			= 0;
	var $nbr_jour_frais = 0;
	
	function __construct()
	{
	}
	
	function init($database, $resa, $frais, $tva)
	{
		$this->db = new wpdb($database['dbuser'],
				 $database['dbpassword'],
				 $database['dbname'],
				 $database['dbhost'].':'.$database['dbport']);

		$this->ofrais = $frais;
		$this->tva = $tva;
		$this->_table_reservation = $database['table_reservation'];
		$this->_tabletarif = $database['table_tarifs_ext'];
		if ( $resa['type'] == 'pre' )
			$this->premium = 1;
		$this->_tableservices = $database['table_services'];
		$this->_resa = $resa;
		$this->_dates = new stdClass();
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
		$vacances = Vacances::getInstance();
		$vacances->setDate($this->_dates->navette->format("Y-m-d H:i:s"), "Y-m-d H:i:s");
		if ( $vacances->isVacances(true) )
			$this->_vacances = true;
		$vacances->setDate($this->_dates->date_retour->format("Y-m-d H:i:s"), "Y-m-d H:i:s");
		if ( $vacances->isVacances(true) )
			$this->_vacances = true;
		if ( $resa['type'] == 'int' || $resa['type'] == 'pre' )
			$this->_tabletarif = $database['table_tarifs_int'];
		$navette = clone $this->_dates->navette;
		$navette->setTime(0,0);
		$retour = clone $this->_dates->date_retour;
		$retour->setTime(0,0);
		$this->_dates->date_diff = date_diff($navette, $retour, true);
		if ( ! array_key_exists('prix_resa', $resa) || $resa['prix_resa'] < 1 )
			$this->calcul();
	}
	
	public function calcul()
	{
		$this->_getServices();
		$this->setBaseTarif();
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
		return( $this->_tarif );
	}
		
	function getArrayTarif()
	{
		$return = array();
		$return["base"] = $this->base;
		$return["supplement"] = $this->supplement;
		$return["supp_spe"] = $this->supp_spe;
		$return["nbr_jour"] = $this->nbr_jour;
		$return["nbr_jour_promo"] = $this->nbr_jour_promo;
		$return["nbr_jour_supp"] = $this->nbr_jour_supp;
		$return["ferie_aller"]  = $this->ferie_aller;
		$return["ferie_retour"]  = $this->ferie_retour;
		$return["nuit_aller"]  = $this->nuit_aller;
		$return["nuit_retour"]  = $this->nuit_retour;
		$return["nuit_status"]  = $this->nuit_status;
		$return["pers_supp"] = $this->pers_supp;
		$return["tarif_pers"] = $this->tarif_pers;
		$return["etat_des_lieux"]  = $this->etat_des_lieux;
		$return["lavage"] = $this->lavage;
		$return["remorque"] = $this->remorque;
		$return["oubli"] = $this->oubli;
		$return["categorie"] = $this->categorie;
		$return["prix_resa"] = $this->_tarif;
		$return["premium"] = $this->premium;
		$return["notification"] = $this->notification;
		$return["smssend"] = $this->smssend;
		return $return;
	}

	function getTarifDetail($field, $desc, $prix, $tva)
	{
		$ret = array();
		$ret['field'] = $field;
		$ret['tva'] = $tva;
		$ret['desc'] = $desc;
		$ret['prix'] = $prix;
		$ret['prixht'] = $this->HT($prix, $this->tva[$tva]);
		$ret['prixtva'] = $prix - $ret['prixht'];
		return $ret;
	}
	
	function getArrayDetail($style = true)
	{
		$return = array();

		$return[] = $this->getTarifDetail('nbr_jour',"Tarif pour " . $this->nbr_jour . " jour(s)", 0,'tva2');
		$return[] = $this->getTarifDetail("navette","Transfert le " . $this->_dates->navette->format("Y-m-d"), $this->base / 2, 'tva2');
		$return[] = $this->getTarifDetail("date_retour", "Transfert le " . $this->_dates->date_retour->format("Y-m-d"),$this->base / 2,'tva2');
		if ( $this->ferie_aller )
			$return[] = $this->getTarifDetail("ferier_aller","Frais Jour férié à l'aller", $this->service["ferie"]->valeur,'tva2');
		if ( $this->ferie_retour )
			$return[] = $this->getTarifDetail("ferier_retour","Frais Jour férié au retour", $this->service["ferie"]->valeur,'tva2');

	if ( $this->supp_spe )
		{
			$desc = "Frais supp pour : ";
			if ( $this->nuit_aller )
				$desc .= "<br> départ nocture";
			if ( $this->nuit_retour )
				$desc .= "<br>retour nocture";
			if ( $this->dimanche_aller )
				$desc .= "<br>départ dimanche";
			if ( $this->dimanche_retour )
				$desc .= "<br>retour dimanche";
			$return[] = $this->getTarifDetail($desc, $this->service["supp_spe"]->valeur,'tva2' );
		}
		if ($this->promo)
			$return[] = $this->getTarifDetail("promo","promo", -1 * $this->promo,'tva2' );
		if ($this->pers_supp)
			$return[] = $this->getTarifDetail("pers_supp","Frais personnes supplémentaire (".$this->pers_supp.")", ( $this->pers_supp  * $this->service["pers_supp"]->valeur),'tva2');
		if ( $this->etat_des_lieux )
			$return[]= $this->getTarifDetail("etat_des_lieux","Etat des lieux", $this->service["etat_des_lieux"]->valeur, 'tva1');
		if ( $this->remorque)
			$return[]= $this->getTarifDetail("remorque","Suppl. remorque", $this->service["remorque"]->valeur, 'tva1');
		if ( $this->oubli )
			$return[]= $this->getTarifDetail("oubli","Suppl. Oublie", $this->service["oubli"]->valeur, 'tva1');
		if ( $this->premium )
			$return[]= $this->getTarifDetail("premium","Suppl. Premium", $this->service["premium"]->valeur, 'tva1');
		if ($this->supp_lav)
			$return[]= $this->getTarifDetail("supp_lavage","Lavage", $this->supp_lav, 'tva1');	
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

		$return .= $this->TRBODY("Tarif pour " . $this->nbr_jour . " jour(s)", $this->base_orig);
		if ( $this->ferie_aller )
			$return .= $this->TRBODY("Frais Jour férié à l'aller", $this->service["ferie"]->valeur . " €" );
		if ( $this->ferie_retour )
			$return .= $this->TRBODY("Frais Jour férié au retour", $this->service["ferie"]->valeur . " €" );	

	if ( $this->supp_spe )
		{
			$desc = "Frais supp pour : ";
			if ( $this->nuit_aller )
				$desc .= "<br> départ nocture";
			if ( $this->nuit_retour )
				$desc .= "<br>retour nocture";
			if ( $this->dimanche_aller )
				$desc .= "<br>départ dimanche";
			if ( $this->dimanche_retour )
				$desc .= "<br>retour dimanche";
			$return .= $this->TRBODY($desc, $this->service["supp_spe"]->valeur . " €" );
		}
		if ($this->promo)
			$return .= $this->TRBODY("promo", "-". $this->promo . " €" );
		if ($this->pers_supp)
			$return .= $this->TRBODY("Frais personnes supplémentaire (".$this->pers_supp.")", 
				( $this->pers_supp  * $this->service["pers_supp"]->valeur) . " €" );
				
		if ( $this->etat_des_lieux )
			$return .= $this->TRBODY("Etat des lieux",$this->service["etat_des_lieux"]->valeur . " €");
		if ( $this->remorque)
			$return .= $this->TRBODY("Suppl. remorque", $this->service["remorque"]->valeur . " €");
		if ( $this->oubli )
			$return .= $this->TRBODY("Suppl. Oublie", $this->service["oubli"]->valeur . " €");
		if ( $this->premium )
			$return .= $this->TRBODY("Suppl. Premium", $this->service["premium"]->valeur . " €");
		if ($this->supp_lav)
			$return .= $this->TRBODY("Lavage", $this->supp_lav . " €");	
		$return .= "</tbody>";
		$return .= "<tfoot>";
		$return .= $this->TR("TOTAL", $this->base + $this->supplement . " €");
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
	
	private function promotion()
	{
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
				case ($this->nbr_jour >= 7 && $this->nbr_jour <= 7):
					return 3;
				case ($this->nbr_jour >= 8 && $this->nbr_jour <= 9):
					return 4;
				case ($this->nbr_jour >= 10 && $this->nbr_jour <= 13):
					return 5;
				case ($this->nbr_jour >= 14 && $this->nbr_jour <= 21):
					return 6;
				case ($this->nbr_jour >= 22 && $this->nbr_jour <= 28):
					return 10;
				case ($this->nbr_jour > 28):
					return 14;
			}
		}
		return 0;
		
	}
	
	function setBaseTarif()
	{
		$this->nbr_jour = $this->_dates->date_diff->format('%a') + 1;
		$this->nbr_jour_promo = $this->nbr_jour - $this->promotion();
		
		if ( $this->nbr_jour_promo > $this->service["max_jours"]->valeur )
			$this->nbr_jour_supp = $this->nbr_jour_promo - $this->service["max_jours"]->valeur;
		$where = " WHERE `nbr_jours`=" . ($this->nbr_jour_promo - $this->nbr_jour_supp);
		$query = "SELECT `prix` FROM `{$this->_tabletarif}` $where" ;

		$this->base = $this->db->get_var( $query );
		$this->base_orig = $this->base;
		$this->frais();
		$this->base += ( $this->ferie_aller   * $this->service["ferie"]->valeur );
		$this->base += ( $this->ferie_retour  * $this->service["ferie"]->valeur );
		$this->base += ( $this->supp_spe * $this->service["supp_spe"]->valeur );
		$this->base += ( $this->nbr_jour_supp * $this->service["jour_supp"]->valeur );
		$this->setCodePromo();
	}
	
	private function setCodePromo()
	{
		if ( array_key_exists("codepromo", $this->service)
			&& ($this->codepromo == $this->service["codepromo"]->valeur) 
			&& !$this->_vacances 
			&& $this->nbr_jour > $this->nbr_jour_frais
			&& array_key_exists("promo", $this->service)) {
			$this->promo = $this->calculPromo($this->base,$this->service["promo"]->valeur );
			$this->base -= $this->promo; 
		  }
		 if ( array_key_exists("codepromojour", $this->service)
			&& ($this->codepromo == $this->service["codepromojour"]->valeur) 
			&& $this->nbr_jour > $this->nbr_jour_frais
			&& array_key_exists("promojour", $this->service)) {
			$this->promo = $this->calculPromo($this->base,$this->service["promojour"]->valeur );
			$this->base -= $this->promo; 
		  }
		if ( array_key_exists("codepromoadmin", $this->service)
			&& substr($this->codepromo, 0, 8) == $this->service["codepromoadmin"]->valeur ) {
			$this->promo = $this->calculPromo($this->base, substr($this->codepromo,-2) . "%");
			$this->base -= $this->promo; 
		}
		if ( array_key_exists("codeagence", $this->service)
			&& substr($this->codepromo, 0, 6) == $this->service["codeagence"]->valeur
			&& array_key_exists("agence", $this->service) ) {
			$this->promo = $this->calculPromo($this->base, $this->service["agence"]->valeur); 
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
			)
			{
			$this->supp_spe = 1; $this->supp_nuit = 1; $this->nuit_retour = 1;
			}
		if ($this->ofrais['dimanche'] == 1 && $this->est_dimanche((int)$this->_dates->navette->format("w"))
			&& $this->nbr_jour > $this->nbr_jour_frais){
			$this->supp_spe = 1; $this->dimanche_aller = 1;}
		if ($this->ofrais['dimanche'] == 1 && $this->est_dimanche((int)$this->_dates->date_retour->format("w"))
			&& $this->nbr_jour > $this->nbr_jour_frais) {
			$this->supp_spe = 1; $this->dimanche_retour = 1;}
	}
	
	function calculPromo($valeur, $baisse)
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
		$fmtsql = "SELECT `id`, ".implode(", ",$tarif_fields)." FROM `" . $this->_table_reservation . "` WHERE `id` = '". $id."'";
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
?>
