<?php

Class parkineo
{
  private PDO $conn;
  private array $data = array();
  private int $site = 1;
  private int $commande_id = 0;

  public function __construct()
  {
    $mysql = 'db.parkingorly.fr';
    $port = 35506;
    $dbname = 'parkineo';
    $this->conn = new PDO('mysql:host='.$mysql.';port='.$port.';dbname='.$dbname, 'parkineo', 'Mamouni123');
    $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
  }

  public function record($data = array(), $site = 1): void
  {
    try
    {
      $this->data = $data;
      $this->site = match (strtolower($site)) {
        "roissy" => 2,
        "zaventem" => 3,
        default => 1,
      };
      $member_id = $this->create_member();
      $commande_id = $this->create_commande($member_id, $site);
      $this->commande_id = $commande_id;
      $this->create_vehicule($commande_id);
    }
    catch(Exception $e)
    {
    }
  }

  public function getCommandeID()
  {
    return $this->commande_id;
  }

  public function updateStatus($status, $resauuid, $site, $paye, $datePaiement, $arr_status = 2, $paiement_id = 2)
  {
    try
    {
      if ($status != "Completed")
        throw new Exception("Parkineo not Updated, Status different de Completed");
      if ( !isset($resauuid) || $resauuid == '' )
        throw new Exception("Parkineo Not Updated");
      //$resa = array();
      $facture_id = $this->getBillID($site, $resauuid);
      //$resa['status'] = $arr_status;
      //$resa['paye'] = $paye;
      $date = date('Y-m-d H:i:s');
      $query = "UPDATE `tbl_commande` SET `facture_id` = '{$facture_id}', `paye` = '{$paye}', `date_paiement` = '{$datePaiement}', `paiement_id` = {$paiement_id}, `status` = '{$arr_status}', `date` = '{$date}' WHERE `resauuid` = '{$resauuid}'";
      if(! $this->conn->query($query) )
        throw new Exception("Problème lors de la mise a jour d'une commande");
      print("Parkineo UPDATED\n");
    }
    catch(Exception $e)
    {
      print("Update Error : " . $e->getMessage() . "\n");
    }

  }

  private function getBillID($site, $resauuid)
  {
    $query = "SELECT `facture_id` FROM `tbl_commande` WHERE `resauuid` = :resauuid";
    $req = $this->conn->prepare($query);
    $req->execute(array('resauuid' => $resauuid));
    if ( $row = $req->fetch(PDO::FETCH_ASSOC) )
      return $row['facture_id'];

    $query = "SELECT max(`facture_id`) AS facture_id FROM `tbl_commande` WHERE `facture_id` LIKE '{$site}%'";
    $req = $this->conn->prepare($query);
    $req->execute();
    $row = $req->fetch(PDO::FETCH_ASSOC);
    return (${site} . str_pad(substr($row['facture_id'], 3)+1, 9, 0, STR_PAD_LEFT));
  }

  private function simplification($chaineNonValide, $separator='-')
  {

    $chaineNonValide = strip_tags(html_entity_decode($chaineNonValide));

    $accented = array('&','À','Á','Â','Ã','Ä','Å','Æ','Ă','Ą','Ç','Ć','Č','Œ','Ď','Đ','à','á','â','ã','ä','å','æ','ă','ą','ç','ć','č','œ','ď','đ','È','É','Ê','Ë','Ę','Ě','Ğ','Ì','Í','Î','Ï','İ','Ĺ','Ľ','Ł','è','é','ê','ë','ę','ě','ğ','ì','í','î','ï','ı','ĺ','ľ','ł','Ñ','Ń','Ň','Ò','Ó','Ô','Õ','Ö','Ø','Ő','Ŕ','Ř','Ś','Ş','Š','ñ','ń','ň','ò','ó','ô','ö','ø','ő','ŕ','ř','ś','ş','š','$','Ţ','Ť','Ù','Ú','Û','Ų','Ü','Ů','Ű','Ý','ß','Ź','Ż','Ž','ţ','ť','ù','ú','û','ų','ü','ů','ű','ý','ÿ','ź','ż','ž','А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','М','Н','О','П','Р','а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','р','С','Т','У','Ф','Х','Ц','Ч','Ш','Щ','Ъ','Ы','Ь','Э','Ю','Я','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я');
    $replace = array('et','A','A','A','A','A','A','AE','A','A','C','C','C','OE','D','D','a','a','a','a','a','a','ae','a','a','c','c','c','oe','d','d','E','E','E','E','E','E','G','I','I','I','I','I','L','L','L','e','e','e','e','e','e','g','i','i','i','i','i','l','l','l','N','N','N','O','O','O','O','O','O','O','R','R','S','S','S','n','n','n','o','o','o','o','o','o','r','r','s','s','s','s','T','T','U','U','U','U','U','U','U','Y','Y','Z','Z','Z','t','t','u','u','u','u','u','u','u','y','y','z','z','z','A','B','B','r','A','E','E','X','3','N','N','K','N','M','H','O','N','P','a','b','b','r','a','e','e','x','3','n','n','k','n','m','h','o','p','C','T','Y','O','X','U','u','W','W','b','b','b','E','O','R','c','t','y','o','x','u','u','w','w','b','b','b','e','o','r ');
    $chaineValide = str_replace($accented, $replace, $chaineNonValide);

    $search = array ('@[ ]@i','@[^a-zA-Z0-9_-]@');
    $replace = array ($separator, '');
    $chaineValide = preg_replace('/(?:(['.$separator.'])\1)\1*/', '$1', trim(strtolower(preg_replace($search, $replace, $chaineValide)), $separator));

    return $chaineValide;

  }

  private function create_member()
  {
    $resa = array();
    $resa['status'] = 6;
    $resa['date'] = date('Y-m-d');
    $resa['email'] = strtolower($this->getData('email'));
    $resa['password'] = strrev(md5('parkineo'));
    $resa['reseau_id'] = 0;
    $resa['societe'] = '';
    $resa['nom'] = ucwords(utf8_decode($this->getData('nom')));
    $resa['prenom'] = ucwords(utf8_decode($this->getData('prenom')));
    $resa['adresse'] = $this->getData('adresse');
    $resa['code_postal'] = ucwords($this->getData('cp'));
    $resa['ville'] = ucwords(utf8_decode($this->getData('ville')));
    $resa['pays'] = strstr($this->getData('mobile'), '+33') ? 1 : 2;
    $resa['langue'] = 1;
    $resa['tel_fixe'] = 0;
    $resa['tel_port'] = $this->getData('mobile');
    $resa['tva'] = '';
    $resa['url'] = $this->simplification(implode(" ", array($resa['prenom'],$resa['nom'], $resa['ville'])));
    $resa['afficher'] = 1;
    $resa['parking_id'] = 0;
    $resa['site_id'] = 0;
    $resa['lier'] = serialize(array());

    $query = "SELECT `id_membre` FROM `tbl_membre` WHERE `email` LiKE ? ORDER BY `id_membre` ASC";
    $req = $this->conn->prepare($query);
    if(!$req->execute(array($resa['email'])))
      throw new Exception("Probleme lors de la creation d'un membre");

    if($req->rowCount() > 0) {

      $row = $req->fetch(PDO::FETCH_ASSOC);
      $id = $row['id_membre'];

    } else {

      $query = "
    INSERT INTO `tbl_membre`
    (
        `id_membre` , `status` , `date` , `email` , `password`
        , `reseau_id` , `societe` , `nom` , `prenom` , `adresse`
        , `code_postal` , `ville` , `pays` , `langue` , `tel_fixe` , `tel_port`
        , `tva` , `url` , `afficher` , `lier`
        , `parking_id`, `site_id`
    )
    VALUES (
        NULL , :status , :date , :email , :password
        , :reseau_id , :societe , :nom , :prenom , :adresse
        , :code_postal , :ville , :pays , :langue , :tel_fixe , :tel_port
        , :tva , :url , :afficher , :lier
        , :parking_id, :site_id
    )";
      $req = $this->conn->prepare($query);
      if(!$req->execute($resa))
        throw new Exception("Probleme lors de la creation d'un membre");

      $id = $this->conn->lastInsertId();

    }

    return $id;
  }

  private function isexist($resauuid)
  {
    $ret = 0;
    if ( $resauuid != '' and $resauuid != '0000-0000-000000' )
    {
      $data = array(
        'resauuid' => $resauuid
      );
      $query = "SELECT `id_commande` FROM tbl_commande WHERE `resauuid` = :resauuid";
      $req = $this->conn->prepare($query);
      if ($req->execute($data)) {
        $row = $req->fetch(PDO::FETCH_ASSOC);
        $ret = $row['id_commande'];
      }
    }
    return $ret;
  }
  private function create_commande($membre_id = 0, $site = "Orly")
  {
    $id_commande = $this->isexist($this->getData('resauuid'));
    if ( $id_commande != 0 )
      return $id_commande;
    $query = "SELECT `grille_tarifaire` FROM `tbl_remplissage` WHERE `date` = ?";
    $req = $this->conn->prepare($query);
    $req->execute(array(substr($this->getData('navette'), 0, 10)));
    $row = $req->fetch(PDO::FETCH_ASSOC);

    $unserialize = unserialize($row['grille_tarifaire']);

    $grille_tarifiare = $unserialize[$this->site];

    if (!$membre_id)
      throw new Exception("Probleme lors de la creation d'une commande");
    $facturation = implode(" ",array(ucwords($this->getData('nom')),ucwords($this->getData('prenom'))))."\n";
    $facturation .=
      $this->getData('adresse')."\n".
      implode(" ", array(ucwords($this->getData('cp')), ucwords($this->getData('ville'))) );
    $debut = new DateTime(substr($this->getData('navette'), 0, 10));
    $fin = new DateTime(substr($this->getData('date_retour'), 0, 10));
    $interval = $debut->diff($fin);
    $aTerminal = array('S' => 1, 'W' => 2, '1' => 3, '2A' => 5, '2B' => 6, '2C' => 7, '2D' => 8, '2E' => 9, '2F' => 10, '2G' => 11, '3' => 4);
    $recherche = implode(" ",
      array(ucwords($this->getData('prenom')), ucwords($this->getData('nom')), ucwords($this->getData('email')),
      str_replace('+', '00', $this->getData('mobile'))
      )
    );
    date_default_timezone_set('Europe/Paris');
    $resa = array(
      'resauuid'          => $this->getData('resauuid')
      ,'facture_id'       => ''
      ,'site_id' => $this->site
      ,'parking_id'       => $this->site
      ,'date'             => date('Y-m-d H:i:s')
      ,'membre_id'        => $membre_id ,'telephone' => str_replace('+', '00', $this->getData('mobile'))
      ,'facturation'      => utf8_decode($facturation) ,'depart' => substr($this->getData('navette'), 0, 10)
      ,'depart_heure'     => substr($this->getData('navette'), 11), 'arrivee' => substr($this->getData('date_retour'), 0, 10)
      ,'arrivee_heure'    => substr($this->getData('date_retour'), 11), 'nb_jour' => $interval->days+1
      ,'nb_jour_offert'   => 0
      ,'nb_personne'      => serialize(array(
        'aller'   => $this->getData('nbr_retour')
        ,'retour' => $this->getData('nbr_retour')
      ))
      ,'compagnie_id'     => 0
      ,'destination_id'   => 0
      ,'terminal'         => serialize(array(
        'depart'   => $aTerminal[$this->getData('terminal_aller')]
        ,'arrivee' => $aTerminal[$this->getData('terminal_retour')]
      ))
      ,'num_vol'          => serialize(array(
        'depart'   => $this->getData('terminal_aller')
        ,'arrivee' => $this->getData('terminal_retour')
      ))
      ,'total'            => $this->getData('prix_resa')
      ,'grille_tarifaire' => $grille_tarifiare
      ,'tva'              => 20
      ,'tva_transport'    => 10
      ,'coupon_id'        => 0
      ,'recherche'        => utf8_decode($recherche)
      ,'remarque'         => "Commande Parking ".$site." / Destination : " . utf8_decode($this->getData('destination'))." / Reference : ".$this->getData('id')
      ,'status'           => 1
      ,'nb_retard'        => 0
    );
    $query = "
INSERT INTO `tbl_commande`
(
    `resauuid`, `facture_id` , `site_id` , `parking_id` ,  `date` , `membre_id`
    , `telephone` , `facturation` , `depart` , `depart_heure` , `arrivee`
    , `arrivee_heure` , `nb_jour` , `nb_jour_offert` , `nb_personne` , `compagnie_id`
    , `destination_id` , `terminal` , `num_vol` , `total` , `grille_tarifaire` , `tva`
    , `tva_transport` , `coupon_id` , `recherche` , `remarque` , `status`
    , `nb_retard`
)
VALUES
(
    :resauuid, :facture_id , :site_id , :parking_id , :date , :membre_id
    , :telephone , :facturation , :depart , :depart_heure , :arrivee
    , :arrivee_heure , :nb_jour , :nb_jour_offert , :nb_personne , :compagnie_id
    , :destination_id , :terminal , :num_vol , :total , :grille_tarifaire, :tva
    , :tva_transport , :coupon_id , :recherche , :remarque , :status
    , :nb_retard
)";
    $req = $this->conn->prepare($query);
    if(!$req->execute($resa))
      throw new Exception("Probleme lors de la creation d'une commande");
    return $this->conn->lastInsertId();
  }

  private function create_vehicule($commande_id = 0)
  {
    if(!$commande_id)
      throw new Exception("Probleme lors de la creation d'un vehicule");
    $resa = array(
      'commande_id'      => $commande_id
      ,'type_id'         => 1
      ,'parking_type'    => ($this->getData('type') == 'ext') ? 0 : 1
      ,'options'         => serialize(array())
      ,'marque'          => 'Inconnue'
      ,'modele'          => $this->getData('modele')
      ,'immatriculation' => $this->getData('immatriculation')
      ,'nb_personne'     => serialize(array(
        'aller'   => $this->getData('nbr_retour')
        ,'retour' => $this->getData('nbr_retour')
      ))
      ,'tarif'           => $this->getData('prix_resa')
      ,'commentaire'     => ''
      ,'status'          => serialize(array(
        0  => array('encours' => '00:00', 'fait' => '00:00')
        ,1 => array('encours' => '00:00', 'fait' => '00:00')
      ))
    );
    $query = "
INSERT INTO `tbl_vehicule`
(
    `commande_id`, `type_id`, `parking_type`, `options`, `marque`
    , `modele`, `immatriculation`, `nb_personne`, `tarif`, `status`, `commentaire`
)
VALUES
(
    :commande_id, :type_id, :parking_type, :options, :marque
    , :modele, :immatriculation, :nb_personne, :tarif, :status, :commentaire
)
";
    $req = $this->conn->prepare($query);
    if($req->execute($resa))
      throw new Exception("Probleme lors de la creation d'un vehicule");
  }

  private function getData($field = null)
  {
    if ( is_null($field) || ! array_key_exists($field, $this->data))
      return '';
    return $this->data[$field];
  }
}
?>
