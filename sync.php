<?php

require_once "./includes/parkineo.php";

Class sync
{
  private $site = "orly";
  private $data = array();
  private $table = 'reservations_new1';
  private $conn;
  private $from_date = '2018-07-14';
  private $park;

  public function __construct()
  {
    print("init\n");
    $mysql = 'sqlprive-ad31076-001.privatesql';
    $port = '3306';
    $dbname = 'parkingorly';
    $login = 'parkingorly';
    $passwd = 'Zba9EKUd8RP5N8Qh';
    $this->conn = new PDO('mysql:host='.$mysql.';port='.$port.';dbname='.$dbname, $login, $passwd);
    $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
    $this->park = new parkineo();
  }

  private function record($data, $site)
  {
    print("recording [".$data['id']."]");
    $park = $this->park;
    $park->record($data, $site);
    try
    {
      $park->updateStatus($data['payment_status'],$data['resauuid'], $this->site, $data['prix_resa']);
    }
    catch(Exception $e)
    {
      print("update not done");
    }
    print("migrate done for ". $data['id']."\n");
  }

  public function getLists()
  {
    $table = $this->table;
    $query = "SELECT * FROM  `${table}` WHERE `commande_id` = 0 AND `date_create` > :from_date";
    print("query=" . ${query}."\n");
    $req = $this->conn->prepare($query);
    print_r($req);
    if ( ! $req->execute(array('from_date' => $this->from_date)) )
    {
      throw new Exception("Probleme lors du SELECT FROM ${table}");
    }
    while ( $row = $req->fetch(PDO::FETCH_ASSOC) )
    {
      print_r($row);
      $this->record($row, $this->site);
    }
  }

}


$s = new sync();
$s->getLists();
print("ok\n");
?>
