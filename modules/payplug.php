<?php

use Payplug\Notification;
use Payplug\Resource\Payment;

defined('_PKMGMT') or die('Restricted access');
require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "payplug-php" . DS . "lib" . DS . "init.php";
require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "vendor" . DS . "autoload.php";
require_once PKMGMT_PLUGIN_INCLUDES_DIR. DS . "parkineo.php";

class payplug extends pkmgmt_site
{

	public string $LOGG;

	public function __construct($post = null)
	{
		parent::__construct($post);
		$this->LOGG = dirname(__FILE__, 2) . DS . preg_replace('/\\.[^.\\s]{3,4}$/', '', basename(__FILE__)) . ".log";
	}

	public function updateParkineo($resauuid, $site, $paye, $resource, $payment_status = "Completed"): void
	{
		$input = file_get_contents('php://input');
		$post = json_decode($input, true);
		if ($post['email'] != 'david@zdm.fr') return;

		$date = (new DateTime('NOW'))->format("y:m:d h:i:s");
		if (!class_exists('parkineo'))
			require_once PKMGMT_PLUGIN_INCLUDES_DIR . DS . "parkineo.php";

//		$query = "SELECT `id`, `resauuid`,`payment_status` FROM `{$this->database['table_reservation']}` WHERE `resauuid` = '${resauuid}' ";
//		$rows = $this->db->get_results($query);
		$datePaiement = date('Y-m-d H:i:s', $resource->hosted_payment->paid_at);
		if ($post['email'] == 'david@zdm.fr') print_log($resauuid,false);
		if ($post['email'] == 'david@zdm.fr') print_log($site,false);
		if ($post['email'] == 'david@zdm.fr') print_log($paye,false);
		if ($post['email'] == 'david@zdm.fr') print_log($datePaiement,false);
		try {
			$park = new parkineo();
		} catch (Exception $e) {
			if ($post['email'] == 'david@zdm.fr') print_log($e->getMessage());
			return;
		}
		if ($post['email'] == 'david@zdm.fr') print_log($payment_status);
//		foreach ($rows as $row) {
			$park->updateStatus($payment_status, $resauuid, $site, $paye, $datePaiement, 2, 3);
			error_log("${date}\n", 3, $this->LOGG);
//			error_log("Parkineo Update : " . print_r($row, true) . "\n", 3, $this->LOGG);
//		}
	}

	public function ipn(): void
	{
		$input = file_get_contents('php://input');
		$post = json_decode($input, true);
		$secret_key = 'sk_live_5SNvYIWHANZ82sK936bLGT';
//		if ($post['email'] == 'david@zdm.fr') $secret_key = 'sk_test_6ROshkshtnDLZhMDuDUwPO';

		try {
			Payplug\Payplug::init(array(
				'secretKey' => $secret_key
			));
			$this->connect();
			$resource = Notification::treat($input);
			$site = "ORY";
			if ($this->info['terminal'] == "Roissy") {
				$site = "CDG";
			}
//			$payment = array();
			if ($resource instanceof Payment
				&& $resource->is_paid) {
//				$payment['id'] = $resource->id;
//				$payment['state'] = $resource->is_paid;
//				$payment['date'] = date('Y-m-d H:i:s', $resource->hosted_payment->paid_at);
//				$payment['amount'] = $resource->amount;
//				$payment['data'] = $resource->metadata['resauuid'];
//				$rec = array();
//				$rec['paiement'] = 'Payplug';
//				$rec['payment_status'] = "Completed";
//				$this->db->update($this->database['table_reservation'], $rec, array('resauuid' => $resource->metadata['resauuid']));

//			if ($post['email'] == 'david@zdm.fr') print_log($resource);
				$this->updateParkineo($resource->metadata['resauuid'], $site, ($resource->amount / 100), $resource);
			}
		} catch (\Payplug\Exception\PayplugException $exception) {
			if ($post['email'] == 'david@zdm.fr')
				print_r($exception->getMessage());
			else
				echo htmlentities($exception);
		}

	}
}

//if ( ! function_exists( "print_log") ) :
//  function print_log($object, $out = true)
//  {
//    print "<pre>";
//    print_r($object);
//    print "</pre>";
//    if ($out)
//      exit(1);
//  }
//endif;

