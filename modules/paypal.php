<?php
defined('_PKMGMT') or die('Restricted accesss');

Class paypal extends pkmgmt_site
{

  public $LOGG;

	public function __construct($post = null)
	{
		parent::__construct($post);
    $this->LOGG = dirname(dirname(__FILE__)) . DS . preg_replace('/\\.[^.\\s]{3,4}$/', '', basename(__FILE__)) . ".log";
	}

  public function updateParkineo($resauuid, $site, $paye)
  {

    $date = (new DateTime('NOW'))->format("y:m:d h:i:s");
    if ( ! class_exists(parkineo) )
      require_once PKMGMT_PLUGIN_INCLUDES_DIR . DS . "parkineo.php";

    $park = new parkineo();
    $query = "SELECT `id`, `resauuid`,`payment_status` FROM `{$this->database['table_reservation']}` WHERE `resauuid` = '${resauuid}' ";
		$rows = $this->db->get_results($query);
    foreach ($rows as $row)
    {
      $park->updateStatus($row->payment_status, $row->resauuid, $site, $paye);
      error_log("${date}\n",3, $this->LOGG);
      error_log("Parkineo Update : " . print_r($row,true)."\n",3, $this->LOGG);
    }
  }

	public function ipn()
	{
    $date = (new DateTime('NOW'))->format("y:m:d h:i:s");
		$return = array();
    $this->connect();
		foreach ($_POST as $key => $value) {
			$post[$key] = html_entity_decode(htmlentities($value, ENT_QUOTES, $_POST['charset']));
		}
    $payment_date = date('Y-m-d H:i:s',strtotime($post['payment_date']));
    $post['payment_date'] = $payment_date;
    $post['resauuid'] = $post['option_selection1'];
    unset($post['option_selection1']);
    if ($this->db->replace('paypal',$post))
    {
      print("Insert OK\n");
      error_log("${date}\n",3, $this->LOGG);
      error_log("Insert/Replace : " . print_r($post,true)."\n",3, $this->LOGG);
    }
    else
    {
      print("[ERROR] - Insert KO\n");
      error_log("${date}\n",3, $this->LOGG);
      error_log("Query : " . print_r($this->db->last_query,true)."\n",3, $this->LOGG);
      error_log("Error : ". print_r($this->db->last_error,true)."\n",3, $this->LOGG);
      die(1);
    }
    if ( isset($post['resauuid'] ) )
    {
      $rec['paiement'] = 'Paypal';
      $rec['payment_status'] = $post['payment_status'];
      $site = 'CDG';
      if ($post['business'] == 'parkingorly94@yahoo.com')
        $site = 'ORY';
      if ($post['business'] == 'emiliomosta@yahoo.fr')
        $site = 'ORY';
      $this->db->update($this->database['table_reservation'],$rec,array('resauuid' => $post['resauuid']));
      $this->updateParkineo($post['resauuid'], $site, $post['mc_gross']);
    }
	}
}
