<?php

defined('_PKMGMT') or die('Restricted accesss');
require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "tarif.php";
require_once PKMGMT_PLUGIN_INCLUDES_DIR . DS . "parkineo.php";

require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "payplug-php" . DS . "lib" . DS . "init.php";

require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "vendor" . DS . "autoload.php";

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_WARNING & ~E_NOTICE);

use \Ovh\Api;
use Aws\Sns\SnsClient;
use Aws\Exception\AwsException;
use Payplug\Payment;
use Mypos\IPC\Cart;
use Mypos\IPC\Config;
use Mypos\IPC\Customer;
use Mypos\IPC\IPC_Exception;
use Mypos\IPC\Purchase;

class reservation extends pkmgmt_site
{
	private $tarifResaFields = array('id', 'base', 'nbr_jour', 'nbr_jour_supp', 'ferie_aller',
		'ferie_retour', 'dimanche_aller', 'dimanche_retour',
		'nuit_status', 'nuit_aller', 'nuit_retour', 'premium',
		'pers_supp', 'etat_des_lieux', 'remorque', 'oubli',
		'lavage', 'categorie', 'notification', 'smssend');
	private $resaFields = array('id', 'resauuid', 'numresa', 'date_create', 'civilite',
		'nom', 'prenom', 'cp',
		'mobile', 'email', 'date_retour',
		'navette',
		'modele', 'immatriculation', 'nbr_retour',
		'prix_resa', 'destination', 'zone', 'status',
		'terminal_retour', 'terminal_aller', 'heure_aller', 'paiement',
		'aerogare_retour', 'aerogare_aller', 'type', 'codepromo');
	private $resa;
	public $errorMessage = "";
	private $SMSdescription = array('OK' => 'Message envoyé avec succès',
		'ERROR 01' => 'Paramètres manquants',
		'ERROR 02' => 'Identifiant ou mot de passe incorrect',
		'ERROR 03' => 'Crédit insuffisant',
		'ERROR 04' => 'Numéro invalide',
		'ERROR 05' => 'Erreur d\'éxécution SMSBOX');

	public function __construct($post = null)
	{
		parent::__construct($post);
	}

	public function getID()
	{
		return $this->resa['id'];
	}

	public function getEmail()
	{
		return $this->resa['email'];
	}

	public function listReservations()
	{
		$return = array();
		try {
			$this->connect();
			$post = array_merge($_POST, $_GET);
			$status = $post['status'];
			$query = "SELECT COUNT(*) AS RecordCount FROM %s WHERE status=%d %s ";
			$query = sprintf($query, $this->database['table_reservation'], (int)$status,
				$this->createSeekWhere($this->database['table_reservation']));

			$RecordCount = $this->db->get_var($query);
			$return['TotalRecordCount'] = $RecordCount;
			$query = "SELECT * FROM %s WHERE status=%d %s ";
			$query .= " ORDER BY %s LIMIT %d, %d";
			$query = sprintf($query, "`" . $this->database['table_reservation'] . "`", $status, $this->createSeekWhere($this->database['table_reservation']), $post['jtSorting'], $post['jtStartIndex'], $post['jtPageSize']);
			$result = $this->db->get_results($query);
			foreach ($result as &$elem) {
				$this->utf8_array($elem);
			}
			$return['Result'] = "OK";
			$return['Records'] = $result;
			print @json_encode($return);
		} catch (Exception $ex) {
			$return['Result'] = "ERROR";
			$return['Message'] = $ex->getMessage();
			print @json_encode($return);
		}
	}

	public function createReservation($ajax = true)
	{
		$return = array();
		try {
			$this->resa = $this->record();
			$return['Record'] = $this->resa;
			$return['Result'] = "OK";
			$this->errorMessage = "";
			if ($ajax)
				print @json_encode($return);
			else
				return true;
		} catch (Exception $ex) {
			$this->errorMessage = $ex->getMessage();
			$return['Result'] = "ERROR";
			$return['Message'] = $this->errorMessage;
			if ($ajax)
				print @json_encode($return);
			else
				return false;
		}
	}

	public function notifyReservation()
	{
		if ($this->info['gestion']['mail'] == 0)
			return;

		$headers[] = 'From: ' . $this->name . " <" . $this->info['email'] . ">";
		$to = $this->info['email'];
		$subject = "Nouvelle reservation N: " . $this->resa['id'] . " de " . $this->resa['nom'] . " " . $this->resa['prenom'];
		$message = "Information de reservation :\n";
		$message .= "<table>\n";
		$message .= "<tbody>\n";
		foreach ($this->resa as $key => $value) {
			$message .= "<tr><td>$key</td><td>$value</td></tr>\n";
		}
		$message .= "</tbody>\n";
		$message .= "</table>\n";
		$this->notification($to, $subject, $message, $headers);
	}

	public function notifyMailUser()
	{
		//if ($this->info['gestion']['autovalid'] == 0)
		//    return;
		$this->_data = $this->resa;
		$this->_data['name'] = $this->name;
		$this->_data['navette'] = date("d/m/Y H:i", strtotime($this->_data['navette']));
		$this->_data['date_retour'] = date("d/m/Y H:i", strtotime($this->_data['date_retour']));
		$this->_data['detail'] = $this->_data['prix_resa'];
		$this->_data['heure_navette'] = substr($this->_data['navette'], 11);
		setlocale(LC_TIME, 'fr_FR');
		$this->_data['date'] = strftime("%A %e %B %Y");

		$headers[] = 'From: ' . $this->name . " <" . $this->info['email'] . ">";
		$message = preg_replace_callback("/{[^}\s]+}/i", array($this, 'replaceFromData'), $this->response);
		$this->notification($this->resa['email'], "Confirmation de réservation", $message, $headers);

//		if ($sendResult) {
//			$rec = array();
//			$rec['notification'] = 1;
//			$this->db->update($this->database['table_tarif_resa'], $rec, array('resaid' => $this->resa['id']));
//			$rec = array();
//			$resa = array('status' => '1');
//			$this->db->update($this->database['table_reservation'], $resa, array('id' => $this->resa['id']));
//		} else
//			throw new Exception("Erreur lors de l'envoie de l'email a " . $this->resa['email'] . " - reservation=" . $this->resa['id']);
	}

	public function notifySMSUser()
	{
		//if ($this->info['gestion']['autovalid'] == 0)
		//    return;
		$this->_data = $this->resa;
		$this->_data['name'] = $this->name;
		$message = preg_replace_callback("/{[^}\s]+}/i", array($this, 'replaceFromData'), $this->divers['smsmessage']);
		$this->sendSMSAWS($this->resa['mobile'], $message);
	}

	public function statusResa()
	{
		$post = array_merge($_POST, $_GET);
		$return = array();
		try {
			$this->connect();
			$resa = array('status' => '2');
			if ($this->db->update($this->database['table_reservation'], $resa, array('id' => $post['id'])) === false)
				throw new Exception("Erreur d'enregistrement en base ");
			$return['Record'] = $resa;
			$return['Result'] = "OK";
			print @json_encode($return);
		} catch (Exception $ex) {
			$return['Result'] = "ERROR";
			$return['Message'] = $ex->getMessage();
			print @json_encode($return);
		}
	}

	public function updateReservation()
	{
		$return = array();
		try {
			$resa = $this->record();
			$return['Record'] = $resa;
			$return['Result'] = "OK";
			print @json_encode($return);
		} catch (Exception $ex) {
			$return['Result'] = "ERROR";
			$return['Message'] = $ex->getMessage();
			print @json_encode($return);
		}
	}

	private function createFromFormat($date)
	{
		$date_pattern = "/[0-9]{4}\/(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1]) (2[0-3]|1[0-9]|0?[0-9]):([0-5][0-9])/";
		if (preg_match($date_pattern, $date)) {
			return DateTime::createFromFormat("Y/m/d H:i", $date);
		}
		$date_pattern = "/[0-9]{4}\-(0[1-9]|1[0-2])\-(0[1-9]|[1-2][0-9]|3[0-1]) (2[0-3]|1[0-9]|0?[0-9]):([0-5][0-9])/";
		if (preg_match($date_pattern, $date)) {
			return DateTime::createFromFormat("Y-m-d H:i", $date);
		}
		$date_pattern = "/(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/20[0-9]{2} (2[0-3]|1[0-9]|0?[0-9]):([0-5][0-9])/";
		if (preg_match($date_pattern, $date)) {
			return DateTime::createFromFormat("d/m/Y H:i", $date);
		}
		return null;
	}

	public function record()
	{
		$fullMsg = "Parking Complet \njusqu'au 3 mai 2024 inclus appelez désistement possible";
		$fullDateStart = DateTime::createFromFormat("d/m/Y H:i", "19/04/2024 00:00");
		$fullDateEnd = DateTime::createFromFormat("d/m/Y H:i", "03/05/2024 23:59");
		$return = array();
		$post = array_merge($_POST, $_GET);

		if (isset($post['post_id']))
			$post_id = $post['post_id'];
		$resa = array();
		//$data = $post;
		foreach ($this->resaFields as $key) {
			if (isset($post[$key]))
				$resa[$key] = trim($post[$key]);
		}
		$local_navette = $this->createFromFormat($resa['navette']);
		if ($local_navette == null)
			throw new Exception("Le format de l'heure d'arriv&eacute;e au parking est incorrect : " . $resa['navette']);
		if (($local_navette >= $fullDateStart) && ($local_navette <= $fullDateEnd))
			throw new Exception($fullMsg);
		$local_date_retour = $this->createFromFormat($resa['date_retour']);
		if ($local_date_retour == null)
			throw new Exception("Le format de l'heure d'arriv&eacute;e au parking est incorrect : " . $resa['navette']);
		if ($local_date_retour <= $local_navette)
			throw new Exception("Veuillez entrer un date retour correct");
		$resa['navette'] = $local_navette->format("Y-m-d H:i:s");
		$resa['date_retour'] = $local_date_retour->format("Y-m-d H:i:s");
		if ($this->info['cg'] == 1 && !array_key_exists('conditiongenerale', $post))
			throw new Exception("Vous devez valider les conditions générales");
		if (!$this->validate($resa))
			throw new Exception("Erreur de validation");
		if (!count($resa))
			return array();
		if (!isset($resa['status']))
			$resa['status'] = 0;
		$resa['resauuid'] = uniqid();
		$fees = $this->getFees($resa);
		$resa['prix_resa'] = $fees->total;
		$this->utf8_array($resa);
		$auth = base64_encode($this->database['dbuser'] . ":" .
			$this->database['dbpassword']);
		try {
			$url = 'https://www.parkineo.com/api/record.api';
			$options = array(
				'http' => array(
					'header' => "Content-Type: application/json\r\nAuthorization: Basic $auth\r\n",
					'method' => 'POST',
					'content' => json_encode($resa)
				),
				'ssl' => array(
					'verify_peer' => false,
					'verify_peer_name' => false
				)
			);
			$context = stream_context_create($options);

			$result_raw = file_get_contents($url, false, $context);
			$result = json_decode($result_raw, true);
			if (array_key_exists('id', $result) && !empty($result['id'])) {
				$resa['id'] = $result['id'];
				$resa['member_id'] = $result['member_id'];
			}
		} catch (Exception $e) {
			if ($resa['email'] == 'david@zdm.fr') print_log($e->getMessage(), true);

		}
		return ($resa);
	}

	function getFees($resa)
	{
		$opts = array(
			'http' => array(
				'method' => 'GET',
				'header' => "Content-Type: application/json\r\n",
				'timeout' => 60,
			),
			'ssl' => array(
				'verify_peer' => false,
				'verify_peer_name' => false
			)
		);
		$context = stream_context_create($opts);
		$airport = "1";
		$parking_type = "0";
		$url = 'https://www.parkineo.com/include/ajax/calculatePrix2.php';
		$dates = new stdClass();
		$dates->navette = new DateTime($resa['navette']);
		$dates->date_retour = new DateTime($resa['date_retour']);
		$depart = $dates->navette->format("d/m/Y");
		$retour = $dates->date_retour->format("d/m/Y");
		$nb_personne = max($resa['nbr_aller'], $resa['nbr_retour']);
		$params = sprintf("nb_vehicule=1&depart=%s&retour=%s&aeroport_id=%s&type_id=1&type[0]=%s&nb_pax=%d&parking[0]=0", $depart, $retour, $airport, $parking_type, $nb_personne);
		$result = file_get_contents(sprintf("%s?%s", $url, $params), false, $context);
//		return $result;
		return json_decode($result);
	}

	function getTarif()
	{
		$post = array_merge($_POST, $_GET);
		if (isset($post['post_id']))
			$post_id = $post['post_id'];
		$resa = $this->getResa($post["id"]);
		if (!$resa) {
			print @json_encode('{}');
		}
		$tarif = Tarif::getInstance();
		$tarif->init($this->database, $resa, $this->info['terminal']);
		$return = $tarif->getArrayTarif();
		print @json_encode($return);
	}

	function getHomeTarifs()
	{
		$post = array_merge($_POST, $_GET);
		if (isset($post['post_id']))
			$post_id = $post['post_id'];
		$tarif = Tarif::getInstance();
		$return = $tarif->get_home_tarifs($this->database, $post["navette"], $post["date_retour"], $this->info['terminal']);
		print @json_encode($return);
	}

	public function getResa($id = 0)
	{

		if (!$id)
			return $id;
		$url = 'https://www.parkineo.com/api/book.api';
		$data = array();
		$data['id'] = $id;
		$auth = base64_encode($this->database['dbuser'] . ":" .
			$this->database['dbpassword']);
		$options = array(
			'http' => array(
				'header' => "Content-Type: application/json\r\nAuthorization: Basic $auth\r\n",
				'method' => 'POST',
				'content' => json_encode($data)
			),
			'ssl' => array(
				'verify_peer' => false,
				'verify_peer_name' => false
			)
		);
		$context = stream_context_create($options);
		$result_raw = file_get_contents($url, false, $context);
		return json_decode($result_raw, true);
	}

	private function getFullResa($id = 0)
	{
		if (!$id)
			return $id;
		$this->connect();
//         $rtf = $this->tarifResaFields;
//         array_shift($rtf);
//         $rf = $this->resaFields;
//         array_shift($rf);
//         $fields = array_merge($rf, $rtf);
		$fields = implode(", ", $this->resaFields);
		$query = "
        SELECT $fields FROM `{$this->database['table_reservation']}`
        WHERE id = %d
        ";
		$result = $this->db->get_row($this->db->prepare($query, $id), ARRAY_A);
		if (!$result)
			throw new Exception(__("Error RES007", 'parking-management'));
		return $result;
	}

	private function formatMobile($mobile)
	{
		$patterns = '/[^\+\d]/';
		$replacements = '';
		$mobile = preg_replace($patterns, $replacements, $mobile);
		$patterns = '/^00/';
		$replacements = '+';
		$mobile = preg_replace($patterns, $replacements, $mobile);
		$patterns = '/^0/';
		$replacements = '+33';
		$mobile = preg_replace($patterns, $replacements, $mobile);
		$patterns = '/^\+330/';
		$replacements = '+33';
		$mobile = preg_replace($patterns, $replacements, $mobile);
		$patterns = '/^\+/';
		if (!preg_match($patterns, $mobile))
			$mobile = '+' . $mobile;
		return ($mobile);
	}

	function validate(&$resa)
	{
		try {
			foreach ($resa as $key => $value) {
				switch ($key) {
					case 'nom':
					case 'prenom':
					case 'modele':
					case 'immatriculation':
					case 'destination':
					case 'nbr_retour':
						$this->checkSTDField($value, "Le champ $key doit être renseigné !");
						break;
					case 'cp':
						$this->checkCPField($value, "Le code postal est incorrect");
						break;
					case 'mobile':
						$value = $this->formatMobile($value);
						$resa['mobile'] = $value;
						$this->checkMOBILEField($value, "Entrez correctement votre mobile !");
						break;
					case 'email':
						$this->checkEMAILField($value, "l'adresse email est incorrect");
						break;
					case 'navette':
					case 'date_retour':
						$this->checkDateField($value, "date incorrect");
						break;
				}
			}
			return true;
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}

	private function checkSTDField($value, $message)
	{
		if (empty($value))
			throw new Exception($message);
	}

	private function checkCPField($value, $message)
	{
		if (empty($value) || !preg_match("/[\d]{4}|[\d]{5}/", $value))
			throw new Exception($message);
	}

	private function checkEMAILField($value, $message)
	{
		if (!preg_match("/^[_a-zA-Z0-9-]+(.[_a-zA-Z0-9-]+)*@([a-zA-Z0-9-]+.)+[a-zA-Z]{2,4}$/", $value))
			throw new Exception($message);
	}

	private function checkMOBILEField($value, $message)
	{
		$mobile = "/^((\(\+[\d]{2}\)[\d]( [\d]{2}){4})|(0[67]( [\d]{2}){4})|((\+)?[\d]{2}[\d]{9}(\d{1,3})?)|(0[\d]{9})|(00[\d]{2}[\d]{9}(\d{1,3})?))$/";
		$mobile = "/^\+[\d]{10}|\+[\d]{11}|\+[\d]{12}$/";
		if (!preg_match($mobile, $value))
			throw new Exception($message);
	}

	private function checkDateField($value, $message)
	{
		$value = substr($value, 0, 16);
		$lowlimitdate = DateTime::createFromFormat("Y-m-d H:i", "2012-01-01 00:00");
		$datetime = DateTime::createFromFormat("Y-m-d H:i", $value);

		if (is_null($datetime) || empty($datetime)) {
			$datetime = DateTime::createFromFormat("d/m/Y H:i", $value);
			if (is_null($datetime) || empty($datetime))
				throw new Exception("date is null");
		}
		if (empty($value) || is_null($datetime) || ($datetime < $lowlimitdate))
			throw new Exception($message . " " . print_r($datetime, true));
	}

	public function updateDb()
	{
		try {
			$post = array_merge($_POST, $_GET);
			list($table, $data, $where) = array($post['table'], $post['data'], $post['where']);
			$this->validate($data);
			$this->connect();
			if (($result = $this->db->update($table, $data, $where)) === false && !empty($this->db->last_error))
				throw new Exception("Erreur lors de la mise a jour de champ " . $this->db->last_error);
			$return['Result'] = "OK";
			print @json_encode($return);
		} catch (Exception $ex) {
			$return['Result'] = "ERROR";
			$return['Message'] = $ex->getMessage();
			print @json_encode($return);
		}
	}

	function deleteReservations()
	{
		$return = array();
		try {

			$post = array_merge($_POST, $_GET);
			$this->connect();
			if (current_user_can('pkmgmt_super_admin_cap')) {
				$this->db->query(
					$this->db->prepare(
						"DELETE FROM `" . $this->database['table_reservation'] . "` WHERE `id` = %d",
						$post["id"]
					)
				);
			} else {
				$query = "UPDATE `" . $this->database['table_reservation'] . "` SET `status` = 9 WHERE `id` = $post[id];";
				$this->db->query($query);
			}
			$return['Result'] = "OK";
			print @json_encode($return);
		} catch (Exception $ex) {
			$return['Result'] = "ERROR";
			$return['Message'] = $ex->getMessage();
			print @json_encode($return);
		}
	}

	function validReservation()
	{
		$return = array();
		try {
			$post = array_merge($_POST, $_GET);
			$this->connect();
			$data = array();
			$data['prix_resa'] = $post['prix_resa'];
			$data['mobile'] = $post['mobile'];
			if (!is_numeric($post['status']))
				$post['status'] = intval($post['status'][0]);
			if (!array_key_exists('status', $post) || !$post['status'])
				$data['status'] = 1;
			$this->validate($data);
			$this->db->update($this->database['table_reservation'], $data, array('id' => $post['resaid']));
			$this->_data = $this->getFullResa($post['resaid']);
			$this->_data['name'] = $this->name;
			$this->_data['navette'] = date("d/m/Y H:i", strtotime($this->_data['navette']));
			$this->_data['date_retour'] = date("d/m/Y H:i", strtotime($this->_data['date_retour']));
			$tarif = Tarif::getInstance();
			$tarif->init($this->database, $this->_data, $this->info['terminal']);
			$this->_data['detail'] = $tarif->getTableHTMLDetail();
			$this->_data['heure_navette'] = substr($this->_data['navette'], 11);
			setlocale(LC_TIME, 'fr_FR');
			$this->_data['date'] = strftime("%A %e %B %Y");
			if ((!array_key_exists('smssend', $post) || !$post['smssend']) && $data['mobile'] != "+33638380854") {
				$message = preg_replace_callback("/{[^}\s]+}/i", array($this, 'replaceFromData'), $this->divers['smsmessage']);
				$this->sendSMSAWS($data['mobile'], $message);
				$rec = array();
				$rec['smssend'] = 1;
				$this->db->update($this->database['table_tarif_resa'], $rec, array('resaid' => $post['resaid']));
			}
			if (!array_key_exists('notification', $post) || !$post['notification']) {
				$headers[] = 'From: ' . $this->name . " <" . $this->info['email'] . ">";
				$message = preg_replace_callback("/{[^}\s]+}/i", array($this, 'replaceFromData'), $this->response);
				$sendResult = $this->notification($post['email'], "Confirmation de réservation", $message, $headers);
				if ($sendResult) {
					$rec = array();
					$rec['notification'] = 1;
					$this->db->update($this->database['table_tarif_resa'], $rec, array('resaid' => $post['resaid']));
				} else
					throw new Exception("Erreur lors de l'envoie de l'email a " . $post['email'] . " - reservation=" . $post['resaid']);
			}
			$return['Result'] = "OK";
			print @json_encode($return);
		} catch (Exception $ex) {
			$return['Result'] = "ERROR";
			$return['Message'] = $ex->getMessage();
			print @json_encode($return);
		}
	}

	function sendSMS($to, $message, $mode = 'Expert', $id = false)
	{
		$from = $this->divers['smssender'];
		$smsuser = $this->divers['smsuser'];
		$smspasswd = $this->divers['smspasswd'];
		$fgc = true;
		$api_type = 'php'; // Ne pas changer
		$api_path = 'https://api.smsbox.fr/api.' . $api_type; // Ne pas changer
		$query = $api_path . '?login=' . rawurlencode($smsuser);
		$query .= '&pass=' . rawurlencode($smspasswd);
		$query .= '&dest=' . rawurlencode($to);
		$query .= '&mode=' . rawurlencode($mode);
		//$query .= '&origine='.rawurlencode($from);
		$query .= '&msg=' . rawurlencode($message);
		$query .= '&udh=1';
		if ($id) $query .= '&id=1';
		if ($fgc && strlen($query) > 1024) $fgc = false;
		if ($fgc) $buffer = @file_get_contents($query);
		else $buffer = $this->use_socket($query);
		return $buffer;
	}

	function sendSMSOVH($to, $message)
	{
		$from = $this->divers['smssender'];
		$login = $this->divers['smsuser'];
		$password = $this->divers['smspasswd'];
		$smscompte = $this->divers['smsaccount'];
		try {
			$soap = new SoapClient("https://www.ovh.com/soapi/soapi-re-1.61.wsdl");
			$result = $soap->telephonySmsUserSend("$login", "$password", "$smscompte", "$from",
				"$to", "$message", "", "1", "", "2", "", "", true);
		} catch (SoapFault $fault) {

		}
	}

	function sendSMSAWS($phone, $message)
	{
		$aws_cred = array(
			'credentials' => array(
				'key' => $this->divers['smsuser'],
				'secret' => $this->divers['smspasswd'],
			),
			'region' => 'eu-west-3',
			'version' => 'latest'
		);

		try {
			$SnSclient = new SnsClient($aws_cred);
			$SnSclient->publish([
				'Message' => $message,
				'PhoneNumber' => $phone,
				'MessageAttributes' => [
					'AWS.SNS.SMS.SenderID' => [
						'DataType' => 'String',
						'StringValue' => $this->divers['smssender']
					],
					'AWS.SNS.SMS.SMSType' => [
						'DataType' => 'String',
						'StringValue' => 'Transactional'
					],
				]
			]);
		} catch (AwsException $e) {
			if ($this->resa['email'] == 'david@zdm.fr') print_log($e->getMessage());
			// output error message if fails
			error_log($e->getMessage());
		}
	}

	function sendSMSAPIOVH($to, $message)
	{
		try {
			$applicationKey = "9yedEYOBXbXEakrA";
			$applicationSecret = "bUHb2CaXGU2m4fTDKEL8ox9OnimrIvCT";
			$consumer_key = "Bp9DUxWmO4uuDdJH5tKsRe6hTEMCq8Xs";

			$endpoint = 'ovh-eu';
			$conn = new Api(
				$applicationKey,
				$applicationSecret,
				$endpoint,
				$consumer_key);
			$smsServices = $conn->get('/sms/');
			$from = $this->divers['smssender'];
			$content = (object)array(
				"charset" => "UTF-8",
				"class" => "phoneDisplay",
				"coding" => "7bit",
				"message" => $message,
				"noStopClause" => false,
				"priority" => "high",
				"receivers" => ["$to"],
				"sender" => $from,
				"validityPeriod" => 2880
			);
			$resultPostJob = $conn->post('/sms/' . $smsServices[0] . '/jobs/', $content);
			$smsJobs = $conn->get('/sms/' . $smsServices[0] . '/jobs/');
		} catch (Exception $e) {

		}
	}

	function sendEnvoiSMS($to, $message, $mode = 'Expert', $id = false)
	{
		define('SMSENVOI_EMAIL', $this->divers['smsuser']);
		define('SMSENVOI_APIKEY', $this->divers['smspasswd']);
		$sender = $this->divers['smssender'];
		require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "smsenvoi.php";
		$smsenvoi = new smsenvoi();
		//$smsenvoi->debug = true;
		$result = $smsenvoi->sendSMS($to, $message, $mode, $sender);
		if (!$result)
			throw_log("SMS Erreur : " . $smsenvoi->result->message);
	}

	function use_socket($uri, $port = 80, $timeout = 10)
	{
		$r_uri = parse_url($uri);
		$sock = fsockopen($r_uri['host'], $port, $errno, $errstr, $timeout);
		if ($sock) {
			fputs($sock, "POST {$r_uri['path']} HTTP/1.1\r\n");
			fputs($sock, "Host: {$r_uri['host']}\r\n");
			fputs($sock, "Content-Type: application/x-www-form-urlencoded\r\n");
			fputs($sock, "Content-Length: " . strlen($r_uri['query']) . "\r\n");
			fputs($sock, "Content-Encoding: ISO-8859-15\r\n");
			fputs($sock, "Connection: Close\r\n\r\n");
			fputs($sock, $r_uri['query']);
			$body = false;
			$buffer = null;
			while ($ligne = fgets($sock, 1024)) {
				if ($body) $buffer .= $ligne;
				if (!$body && trim($ligne) === '') $body = true;
				if (trim($ligne) == '0') break;
			}
			return $buffer;
		} else
			return false;
	}

	public function mypos_payment($resaid): string
	{
		$resa = $this->getResa($resaid);

		if (!$resa) return '';
		try {
			$cancel_url = home_url();
			$cancel_page = get_page_by_title("Annulation");
			if (!is_null($cancel_page))
				$cancel_url = get_permalink($cancel_page->ID) . "?resaid=" . $resa['id'];
			$confirmation_url = home_url();
			$confirmation_page = get_page_by_title("Confirmation");
			if (!is_null($confirmation_page))
				$confirmation_url = get_permalink($confirmation_page->ID) . "?resaid=" . $resa['id'];

			$cnf = new Config();
			if ($_GET['debug'] == "1") {
				print_log($cancel_url, false);
				print_log($confirmation_url, false);
				print_log("");
			}
			$cnf->setIpcURL('https://mypos.com/vmp/checkout');
			$cnf->setLang('en');
			$cnf->setVersion('1.4');
//			if ( home_url() == 'https://parkineo.com' || home_url() == 'https://www.parkineo.com') {
				$cnf->loadConfigurationPackage('eyJzaWQiOiI3NjUwMDgiLCJjbiI6IjQwNzAyMTAwNTE4IiwicGsiOiItLS0tLUJFR0lOIFJTQSBQUklWQVRFIEtFWS0tLS0tXG5NSUlDWEFJQkFBS0JnUURMRzNaS0g5RzhEQWVpUklVdGdpQjdoN09tQ2VQbzhUazlSYmV1Q1MyYjlEVVdkWGtvXG5HeTRwcU01VEswSWJTZDVkWXlCVkM2bmJnRWp6RUNWYlhENnZrMGtGYkljd2JxWlFcL291N1wvMXhoQU1RY0xlVHhcbitZcmxLM1VwQjRPM2FrSkNkaWRzdHJ4QTZndlhSWXRkWVo3cVFnbVliUFd5blpqQkdMR0Y1UkF5MVFJREFRQUJcbkFvR0JBS2RJMVNLMEZuREZqN3dNcDR3VjgxOExJK0lMbjFXSzZOUVlpZktqeUpiOGlvVVcrRlVhSGVsZUNhSGlcbnJTNEFwMDlQaDVcL0VYWUJXcHI3YmU0bEY3ckhcL3dpQWJvMmJIbVB4S3orOUd5WkhMRG1hdG1LN2Z4U2taeFwvVE9cbm1nWm96MWQwOTY1ZVlWUlJxV1JcL1JKY1wvWHNyNU1OazNrTzZYcG1VOGhIbU9NTDRCQWtFQVwvbkVXN3BBZ1dyd2FcbmYwWGhMcnZsM1VJVFdSaXRrdjBRa1R4OUpER0pSNWhuUU5OTlJoNk4weDhSSXJ6T0lXbURZTzNlaUE5QnUyUXBcbjVSWmVpMzlcL3dRSkJBTXhaNUJaMm5EcUFuUENhU3ZuS0RYSjdaV0U0MVlcL3JZVnhKV25hTzMwVXcrN2FQb0tZa1xuckRlcmNDckowdTNQS0Y3NFgxRU5KbCtiUStwNXRVSEl1QlVDUURoNFB0K3BheFRZSklWVXFcL3BrUjBySXpHUjhcbnZxRWR1eHlRc1RGa01SU0x0WGJFcnFTK1NUbXU3bTJvTzVOY3lJU3RwakxLT0F0djJvQjJhM3RJUzhFQ1FHbXlcbmpTRjVWREdHV0theWg5bFB3MGpWdm9oZEIwbWJyQTQ1K3NxYjk2d29PK29mdkM1emhZaDk2bFNYYzd5QmpCWWpcblRkbjBmVWF5WHB5bk96TzlpRWtDUUQxUUphcjlsbENXVVNFUTNyeVhhdUlcLzFBblY4dVFpUHdJdjFhNjkrQ0lwXG5tb3lUYTIxWVF3SW9DSkNYZ2FJOTRMcldXQ3BtSm1mWjEra0VcL2dLYktWUT1cbi0tLS0tRU5EIFJTQSBQUklWQVRFIEtFWS0tLS0tXG4iLCJwYyI6Ii0tLS0tQkVHSU4gQ0VSVElGSUNBVEUtLS0tLVxuTUlJQ0RUQ0NBWGFnQXdJQkFnSUVKUWlVeERBTkJna3Foa2lHOXcwQkFRc0ZBREFkTVFzd0NRWURWUVFHRXdKQ1xuUnpFT01Bd0dBMVVFQ2hNRmJYbFFUMU13SGhjTk1qUXdORE13TVRnME5URXlXaGNOTXpRd05ESTRNVGcwTlRFeVxuV2pBZE1Rc3dDUVlEVlFRR0V3SkNSekVPTUF3R0ExVUVDaE1GYlhsUVQxTXdnWjh3RFFZSktvWklodmNOQVFFQlxuQlFBRGdZMEFNSUdKQW9HQkFMWU5EUU1OdU1YY3Exb1FPNHpJenNFbDJPTzhJelVkZlJuXC93TVhwcW5QaW4yRnlcbmpkUXlDditjakN5N2NQMXg2dG1JSmtiTHkxQytHYXM5cjVPOHFMRmpqa3lDSmtQTTQ0XC9EZHQyZkc3WDJoTkZ1XG5Sd3JjQlV2U0dxMVRnYzF0cHlpdEF3c0FZUzVocWxYQ29IUUVCdnRaRG9OcEpkVzljMlpIeDFaZWxDOEZBZ01CXG5BQUdqV2pCWU1CMEdBMVVkRGdRV0JCUUVXNUpFaUQwTldFbG9ZcFdDdVFXbW5LOUZkREFmQmdOVkhTTUVHREFXXG5nQlFFVzVKRWlEME5XRWxvWXBXQ3VRV21uSzlGZERBSkJnTlZIUk1FQWpBQU1Bc0dBMVVkRHdRRUF3SUU4REFOXG5CZ2txaGtpRzl3MEJBUXNGQUFPQmdRQWUxWHBYdGRUSWY0emJkK1duc0JmR21QNG1JQlBNMlZvRHpMVm1zV3RsXG5HY2RBdzRSbk10R3o5dkVZUEZJQTY3REtPa2x5NThjcmoxRHM4Z1BTdWhZa1hkREFWTlwvc2Q5eTlMQkY3VytsUVxuS1FFN0twWVZPOUhCcUJha3hwbjJZWDZVUng2dTZ2dWVocmx6MU0zbzkwbEtSQllsWUlhdmREWEN6akVKdm5rXC9cbjRBPT1cbi0tLS0tRU5EIENFUlRJRklDQVRFLS0tLS1cbiIsImlkeCI6IjEifQ==');
//			} else {
//				$cnf->loadConfigurationPackage('eyJzaWQiOiI3NjUxODQiLCJjbiI6IjQwNzAyMTAwNTE4IiwicGsiOiItLS0tLUJFR0lOIFJTQSBQUklWQVRFIEtFWS0tLS0tXG5NSUlDWEFJQkFBS0JnUURjTURsY1JEWEc1ZjRHK0ZuZlJ4WmkyQThWSkJHZWo4bCtwbmx3R2p5VXFCTTVsRE9UXG5BSnhMenlXY3dyMGFKa1ZZNzVzUzVNemFGZURvYkVNTFBBSFZ5TU5UNUdLTWdFb2hlU0F1QkJxZnhoVEdCdGM5XG5jMGxRcXdiMytsaFNiU25sbXk0aEgrd1JZS2xrQXAzUXY3T1JcL1FXU3BWOGVSS1NyVTIwOE9MaW5pd0lEQVFBQlxuQW9HQkFMNVFQRFBnak82bU9hTkNvME5XU1NZVUF5MU5xS0Q1UExwb0gweGlrUHdZTGMyMXA4SW1ZXC9FdVVSOG1cblBwWk1mK0gzNzN3c0lGZGJVN0x3YUI1Y3RtTzdwTnpDSjhKelpoTElHemlJMEFVdHZLOU9wMzdqSnlRY0xvOHdcbmlUNVFtYlJiOXR3bFBIWTNXWjdVc1JUYXBua2lPcTZxbjBCanFqc2N3bkNYK0k1SkFrRUErVGRSRFJ3TlhDbENcbmxZRjVHYW1CRTBaWXkwSHZaQkV5R2EzVEdcL1VNWlFOOGRwTjB3bklmb2FMOUh4ajNPVmJZY1pnXC9xNSs2S1p4RFxuc3MyMUY4U0NIUUpCQU9JdW9CeE9kaWtRMGxDXC8wVGh4bTZrSUZJVTVDaGdhcTRNV0tTSnlseVJLeHo4MWNYMmxcbm85NkIrV1wvbWNYSGlLUW41a3dsTnZnVFpVOVVtUERvdUg4Y0NRRUwwS0pOVUVRYk1rdlhscGVwT0RyNWw3TjhXXG5wTUVHbWFZcWZBZ0x5cnVzdHhpSVB4c3FXXC9NcFwvY1VJQ1F0Zm1LRFVIVzczWjZWY05SZnBpaGlMazhVQ1FBYlNcbnN6XC9IV2ljbys3ODEyaURpeEhoWDV3NlJ4dTB0T01sT2pOVWVNZE1GY25kRXJIVEczMmVoOGgxZkRVTUxPSU8xXG5oS3IrTG1KRkVCTlpBTzRjWUJjQ1FINVhWZExPNlhIMzJYeHZKVFpqSG9XQndsOWpWQldIOWYwczhmNHhYUzZwXG5KdUFHQWtXd0RqUEZreDM0Z3RqOWtJR3ZcL0pJREg0N01GSUpxS2QzTmRrVT1cbi0tLS0tRU5EIFJTQSBQUklWQVRFIEtFWS0tLS0tXG4iLCJwYyI6Ii0tLS0tQkVHSU4gQ0VSVElGSUNBVEUtLS0tLVxuTUlJQ0RUQ0NBWGFnQXdJQkFnSUVGTDlGOERBTkJna3Foa2lHOXcwQkFRc0ZBREFkTVFzd0NRWURWUVFHRXdKQ1xuUnpFT01Bd0dBMVVFQ2hNRmJYbFFUMU13SGhjTk1qUXdOREk0TVRZMU9USXlXaGNOTXpRd05ESTJNVFkxT1RJeVxuV2pBZE1Rc3dDUVlEVlFRR0V3SkNSekVPTUF3R0ExVUVDaE1GYlhsUVQxTXdnWjh3RFFZSktvWklodmNOQVFFQlxuQlFBRGdZMEFNSUdKQW9HQkFNdGVRakhDVlVBdmhZb3d6QkJEeHpFXC9BVmhBTnp5UTZLRUE0WnBaeWZnMHo2VkRcblBlRHhRK2dscmR2NDJNR2c4WnFqQThsMENwd0FoR291bGs4NHFqQmpES0I3blI2ZHB5clpQa1VvczgzNjRtWm9cbmg5Q2NwVWFqa2N3ZmlLUXVXWjk2blZMTUE0a3ZtNkhjaHgybVZsRkFQNFwvZkFXSGFadWxzTnIxSU5mcVwvQWdNQlxuQUFHaldqQllNQjBHQTFVZERnUVdCQlRWdXBmajZkb0tocFgzZjF0clNRODhPc2dzN2pBZkJnTlZIU01FR0RBV1xuZ0JUVnVwZmo2ZG9LaHBYM2YxdHJTUTg4T3NnczdqQUpCZ05WSFJNRUFqQUFNQXNHQTFVZER3UUVBd0lFOERBTlxuQmdrcWhraUc5dzBCQVFzRkFBT0JnUUI5c1Z6Rk9ybW9kejNBY01ncUlvNEVSaVBNZ0VlRGNPaEV1S1hYU2JtRVxuN1F5VXRkb0lRMFpRTTFzM28wUWpCd3dUVkNYblVQRVU5MWdaYm9oR3JWQ3JsMkNoRkZvQklvQmNOUjFQWUR3N1xuYmpwOEV0YnpQTWNndnFQbHF4QTQrMzdQRjFQdjNuRWFYVzZIZ0FLT2FXeWpTWVlvc0tyd29PQjdydEZLU2NtNFxuaUE9PVxuLS0tLS1FTkQgQ0VSVElGSUNBVEUtLS0tLVxuIiwiaWR4IjoiMSJ9');
//			}
			$customer = new Customer();
			$customer->setFirstName($resa['prenom']);
			$customer->setLastName($resa['nom']);
			$customer->setEmail($resa['email']);
			$customer->setPhone($resa['mobile']);
			$customer->setCountry('FRA');
			$customer->setZip($resa['cp']);

			$cart = new Cart;
			$cart->add("Reservation du {$resa['navette']} au {$resa['date_retour']}"  , 1, $resa['prix_resa']);

			$purchase = new Purchase($cnf);
			$purchase->setUrlCancel($cancel_url);
			$purchase->setUrlOk($confirmation_url);
//			if ( home_url() == 'https://parkingorly.fr' || home_url() == 'https://www.parkingorly.fr') {
				$purchase->setUrlNotify('https://www.parkineo.com/orly/ipn/mypos.html');
//			} else {
//				$purchase->setUrlNotify('https://www.parkineo.com/roissy/ipn/mypos.html');
//			}
			$purchase->setOrderID("{$resa['id']}");
			$purchase->setCurrency('EUR');
			$purchase->setCustomer($customer);
			$purchase->setCart($cart);


			$purchase->setCardTokenRequest(Purchase::CARD_TOKEN_REQUEST_PAY_AND_STORE);
			$purchase->setPaymentParametersRequired(Purchase::PURCHASE_TYPE_FULL);
			$purchase->setPaymentMethod(Purchase::PAYMENT_METHOD_BOTH);

			$purchase->process();
		} catch (HttpException|IPC_Exception $e) {
			if ($_GET['debug'] == "1") {
				print_log($resa, false);
				print_log($e, false);
				print_log($e->getMessage(), false);
			}
		}
		return '';
	}

	public function form_payplug($atts, $resaid): string
	{
		$atts = wp_parse_args($atts, array(
			'html_id' => '',
			'html_name' => '',
			'html_class' => '',
			'output' => 'form'));
		if ('raw_form' == $atts['output']) {
			return '<pre class="pkbkg-raw-form"><code>'
				. esc_html($this->prop('form')) . '</code></pre>';
		}

		$this->unit_tag = self::get_unit_tag($this->id);
		$attr = array(
			'class' => 'pkbkg',
			'id' => $this->unit_tag,
			(get_option('html_type') == 'text/html') ? 'lang' : 'xml:lang' => str_replace('_', '-', $this->locale),
			'dir' => $this->pkmgmt_is_rtl() ? 'rtl' : 'ltr'
		);

		$resa = $this->getResa($resaid);
		if (!$resa) return '';
		try {
			if (empty($resa['adresse'])) $resa['adresse'] = "n/c";
			if (empty($resa['cp'])) $resa['cp'] = "n/c";
			if (empty($resa['ville'])) $resa['ville'] = "n/c";
			$secret_key = 'sk_live_5SNvYIWHANZ82sK936bLGT';
			if ($_POST['debug'] == '1') {
				print_log($resa);
			}
			\Payplug\Payplug::init(array(
				'secretKey' => $secret_key
			));

			$success_url = home_url();
			$success_page = get_page_by_title("Success");
			if (!is_null($success_page))
				$success_url = get_permalink($success_page->ID);
			$cancel_url = home_url();
			$cancel_page = get_page_by_title("Validation");
			if (!is_null($cancel_page))
				$cancel_url = get_permalink($cancel_page->ID) . "?resaid=" . $resa['id'];
			$notify_url = "https://www.parkineo.com/orly/ipn/payplug.html";
			$civilite = array(1 => "mme", 2 => "mr");
			$payment = Payment::create(array(
				'amount' => ($resa['prix_resa'] * 100),
				'currency' => 'EUR',
				'billing' => array(
					'title' => $civilite[$resa['civilite']],
					'first_name' => $resa['prenom'],
					'last_name' => $resa['nom'],
					'email' => $resa['email'],
					'address1' => $resa['adresse'],
					'postcode' => $resa['cp'],
					'city' => $resa['ville'],
					'country' => 'FR',
					'language' => "fr"
				),
				'shipping' => array(
					'title' => $civilite[$resa['civilite']],
					'first_name' => $resa['prenom'],
					'last_name' => $resa['nom'],
					'email' => $resa['email'],
					'address1' => $resa['adresse'],
					'postcode' => $resa['cp'],
					'city' => $resa['ville'],
					'country' => 'FR',
					'language' => "fr",
					'delivery_type' => 'BILLING'
				),
				'hosted_payment' => array(
					'return_url' => $success_url,
					'cancel_url' => $cancel_url
				),
				'notification_url' => $notify_url,
				'metadata' => array(
					'resauuid' => $resa['resauuid'],
					'id_commande' => $resa['id']
				)
			));

			$payment_url = $payment->hosted_payment->payment_url;
			if ($resa['prix_resa'] < 30) return '';
		} catch (Exception $e) {
			if ($_GET['debug'] == "1") {
				print_log($resa, false);
				print_log($e, false);
				print_log($e->getMessage(), false);
			}
			if ($resa['email'] == 'david@zdm.fr') {
				print_log($e, false);
			}
			return '';
		}
		$ret = <<< EOF
<script type="text/javascript" src="https://api.payplug.com/js/1/form.latest.js"></script>
<script type="text/javascript">
  document.addEventListener('DOMContentLoaded', function() {
    [].forEach.call(document.querySelectorAll("#pkmgmt-payplug"), function(el) {
      el.addEventListener('submit', function(event) {
        var payplug_url = '%s';
        Payplug.showPayment(payplug_url);
        event.preventDefault();
      })
    })
  })
</script>
<div %s>
  <div class="container">
    <div class="row">
      <div class='col-md-12'>
        <h1 style="text-align: center;"><i class="fa fa-flask"></i> Dernière étape: paiement </h1>
        <p style="text-align: center;">
          Montant de la réservation : %s €
        </p>
        <div class='col-md-4'>
          <form name="pkmgmt-payplug" id="pkmgmt-payplug" class="validation reservation" action="" method="post" target="_top" novalidate>
            <p style="text-align: center;">
              <button type="submit" class="btn btn-default">Paiement</button>
            </p>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
EOF;
		$html = sprintf($ret
			, $payment_url
			, $this->pkmgmt_format_atts($attr)
			, $resa['prix_resa']
		);
		return $html;
	}

	public function form_mypos($atts, $resaid): string
	{
		$atts = wp_parse_args($atts, array(
			'html_id' => '',
			'html_name' => '',
			'html_class' => '',
			'output' => 'form'));
		if ('raw_form' == $atts['output']) {
			return '<pre class="pkbkg-raw-form"><code>'
				. esc_html($this->prop('form')) . '</code></pre>';
		}

		$this->unit_tag = self::get_unit_tag($this->id);
		$attr = array(
			'class' => 'pkbkg',
			'id' => $this->unit_tag,
			(get_option('html_type') == 'text/html') ? 'lang' : 'xml:lang' => str_replace('_', '-', $this->locale),
			'dir' => $this->pkmgmt_is_rtl() ? 'rtl' : 'ltr'
		);

		$resa = $this->getResa($resaid);
		if (!$resa) return '';
		$payment_page = get_page_by_title("Paiement");
		if (is_null($payment_page)) {
			return '';
		}
		$payment_url = get_permalink($payment_page->ID);

		$ret = <<< EOF
<div %s>
  <div class="container">
    <div class="row">
      <div class='col-md-12'>
        <h1 style="text-align: center;"><i class="fa fa-flask"></i> Dernière étape: paiement </h1>
        <p style="text-align: center;">
          Montant de la réservation : %s €
        </p>
        <div class='col-md-4'>
          <form name="pkmgmt-payplug" id="pkmgmt-payplug" class="validation reservation" action="%s" method="get" target="_top" novalidate>
            <p style="text-align: center;">
      			<input type="hidden" name="resaid" value="%s" />
              	<button type="submit" class="btn btn-default">Paiement</button>
            </p>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
EOF;
		$html = sprintf($ret
			, $this->pkmgmt_format_atts($atts)
			, $resa['prix_resa']
			, $payment_url
			, $resa['id']
		);
		return $html;
	}

	public function form_paypal($atts = array(), $resaid, $post_id)
	{

		$atts = wp_parse_args($atts, array(
			'html_id' => '',
			'html_name' => '',
			'html_class' => '',
			'output' => 'form'));
		if ('raw_form' == $atts['output']) {
			return '<pre class="pkbkg-raw-form"><code>'
				. esc_html($this->prop('form')) . '</code></pre>';
		}

		$this->unit_tag = self::get_unit_tag($this->id);
		$attr = array(
			'class' => 'pkbkg',
			'id' => $this->unit_tag,
			(get_option('html_type') == 'text/html') ? 'lang' : 'xml:lang' => str_replace('_', '-', $this->locale),
			'dir' => $this->pkmgmt_is_rtl() ? 'rtl' : 'ltr'
		);
		$url = 'https://www.paypal.com/cgi-bin/webscr';
		$resa = $this->getResa($resaid);
		if (!$resa) return '';
		$email = 'parkingorly94@yahoo.com';
		if (strtolower($this->info['terminal']) == 'roissy')
			$email = 'parkingroissy77@yahoo.fr';
		if ($this->paypal['commun'] == 1)
			$email = 'emiliomosta@yahoo.fr';
		$notify_url = get_home_url(null, '', 'https') . "?Parking_Management&action=IPNAction&type=paypal&post_id=" . $post_id;
		$navette = DateTime::createFromFormat("Y-m-d H:i:s", $resa['navette']);
		$retour = DateTime::createFromFormat("Y-m-d H:i:s", $resa['date_retour']);
		$url_retour = get_home_url(null, '', 'https');
		if ($resa['prix_resa'] < 30) return '';
		$ret = <<< EOF
<div %s>
            <fieldset class="formulaire">
                <legend title="Paiement">Paiement</legend>
    <form name="pkmgmt-reservation" id="pkmgmt-reservation" class="validation reservation" action="%s" method="post" target="_top">
        <input type="hidden" name="cmd" value="_xclick">
        <input type="hidden" name="business" value="%s">
        <input type="hidden" name="item_name" value="Reservation du %s au %s">
        <input type="hidden" name="on0" value="Reference">
        <input type="hidden" name="os0" value="%s">
        <input type="hidden" name="amount" value="%s">
        <input type="hidden" name="currency_code" value="EUR">
    <input type="hidden" name="notify_url" value="%s">
    <input type="hidden" name="return" value="%s">

        <div>
                <table>
                    <tr>
                        <td>
                          Montant de la r&eacute;servation : %s€
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="image" src="/wp-content/uploads/2017/10/btn_paynow.png" border="0" name="submit" alt="PayPal, le réflexe sécurité pour payer en ligne">
                        </td>
                    </tr>
                </table>
        </div>
        <img alt="" border="0" src="https://www.paypalobjects.com/fr_FR/i/scr/pixel.gif" width="1" height="1" />
    </form>


    <form name="pkmgmt-reservation" id="pkmgmt-reservation" class="validation reservation" action="/" method="post" target="_top">
        <div>
                <table>
                    <tr>
                        <td>
                          Vous avez la possibilit&eacute; de payer une fois sur place
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="image" src="/wp-content/uploads/2017/10/btn_paylater.png" border="0" name="submit" alt="PayPal, le réflexe sécurité pour payer en ligne">
                        </td>
                    </tr>
                </table>
        </div>
    </form>

            </fieldset>
</div>
EOF;
		$html = sprintf($ret
			, $this->pkmgmt_format_atts($attr)
			, $url
			, $email
			, $navette->format("d/m/Y H:i:s")
			, $retour->format("d/m/Y H:i:s")
			, $resa['resauuid']
			, $resa['prix_resa']
			, $notify_url
			, $url_retour
			, $resa['prix_resa']
		);
		return $html;
	}

	public function form_home_html($atts = array())
	{
		$atts = wp_parse_args($atts, array(
			'html_id' => '',
			'html_name' => '',
			'html_class' => '',
			'output' => 'form'));
		if ('raw_form' == $atts['output']) {
			return '<pre class="pkbkg-raw-home-form"><code>'
				. esc_html($this->prop('form')) . '</code></pre>';
		}
		$this->unit_tag = self::get_unit_tag($this->id);
		$attr = array(
			'class' => 'pkbkg',
			'id' => $this->unit_tag,
			(get_option('html_type') == 'text/html') ? 'lang' : 'xml:lang' => str_replace('_', '-', $this->locale),
			'dir' => $this->pkmgmt_is_rtl() ? 'rtl' : 'ltr'
		);
		$html = sprintf('<div %s>', $this->pkmgmt_format_atts($attr)) . "\n";
		$url = $this->get_request_uri();
		$html .= sprintf('<form %s>',
				$this->pkmgmt_format_atts(array(
					'id' => 'pkmgmt-home-reservation',
					'name' => 'pkmgmt-home-reservation',
					'class' => 'home-reservation'))) . "\n";
		$html .= $this->form_home_elements();
		$html .= $this->form_hidden_fields();
		$html .= '</form>';
		$html .= '<div id="home_form_estimation" class="home_form_estimation"></div>';
		$html .= '</div>';
		return $html;
	}

	private function form_home_elements()
	{
		$now = new DateTime();
		$now->setTimezone(new DateTimeZone('Europe/Paris'));
		$nowText = $now->format('d/m/Y H:i');
		$later = new DateTime();
		$later->setTimezone(new DateTimeZone('Europe/Paris'));
		$later->modify('+4 day');
		$laterText = $later->format('d/m/Y H:i');

		$ret = <<< EOF
         <div class="home_form_navette">
          <fieldset class="fs_navette">
            <legend title="Aller">%s</legend>
            <table>
            <tr>
              <td><input id="homenavette" name="navette" autocomplete="off"
                type="text" size="20" class="txtinput" maxlength="20" tabindex="19"
                value="$nowText">
              </td>
            </tr>
            </table>
          </fieldset>
         </div>
         <div class="home_form_retour">
          <fieldset class="fs_retour">
            <legend title="retour">%s</legend>
            <table>
            <tr>
              <td><input id="homeresadateretour" name="date_retour"
                type="text" size="20" class="txtinput" maxlength="20" tabindex="19"
                value=""
                placeholder="date de retour?" >
              </td>
            </tr>
            </table>
          </fieldset>
         </div>

EOF;
		$ret = sprintf($ret
			, __('Date of deposite ', 'parking-management')
			, __('Date of pick up', 'parking-management')
		);

		return $ret;
	}

	public function form_html($atts = array())
	{
		$atts = wp_parse_args($atts, array(
			'html_id' => '',
			'html_name' => '',
			'html_class' => '',
			'output' => 'form'));
		if ('raw_form' == $atts['output']) {
			return '<pre class="pkbkg-raw-form"><code>'
				. esc_html($this->prop('form')) . '</code></pre>';
		}

		$this->unit_tag = self::get_unit_tag($this->id);
		$attr = array(
			'class' => 'pkbkg',
			'id' => $this->unit_tag,
			(get_option('html_type') == 'text/html') ? 'lang' : 'xml:lang' => str_replace('_', '-', $this->locale),
			'dir' => $this->pkmgmt_is_rtl() ? 'rtl' : 'ltr'
		);
		$html = sprintf('<div %s>', $this->pkmgmt_format_atts($attr)) . "\n";
		$url = $this->get_request_uri();
		$html .= sprintf('<form %s>',
				$this->pkmgmt_format_atts(array(
					'action' => esc_url($url),
					'method' => 'post',
					'id' => 'pkmgmt-reservation',
					'name' => 'pkmgmt-reservation',
					'class' => 'reservation'))) . "\n";

		$html .= $this->form_hidden_fields();
		$html .= $this->form_elements();
		$html .= '</form>';
		$html .= '</div>';
		return $html;
	}

	private function form_hidden_fields()
	{
		$hidden_fields = array(
			'_pkmgmt' => $this->id,
			'post_ID' => $this->id,
			'conditiongenerale_enable' => $this->info['cg'],
			'_pkmgmt_version' => PKMGMT_VERSION,
			'_pkmgmt_locale' => $this->locale,
			'_pkmgmt_unit_tag' => $this->unit_tag);
		if (PKMGMT_VERIFY_NONCE)
			$hidden_fields['_wpnonce'] = wp_create_nonce('pkmgmt-form_' . $this->id);
		$content = '';
		foreach ($hidden_fields as $name => $value) {
			$content .= '<input type="hidden"'
				. ' name="' . esc_attr($name) . '"'
				. ' value="' . esc_attr($value) . '" />' . "\n";
		}
		return '<div style="display: none;">' . "\n" . $content . '</div>' . "\n";
	}

	public function form_elements()
	{
		return apply_filters('pkmgmt_form_elements', $this->form_do_shortcode());
	}

	private function form_do_shortcode()
	{
		$form = $this->form_info();
		$form .= $this->form_trip();
		$form .= $this->form_aller();
		$form .= $this->form_retour();
		if ($this->info['cg'] == 1)
			$form .= $this->form_condition();
		$form .= $this->form_bouton();
		$form .= $this->form_dialog();
		return $form;
	}

	function form_info()
	{
		$ret = <<< EOF
        <div>
        <fieldset class="formulaire">
        <legend title="information">%s</legend>
        <table>
        <tr>
        <td class="label">%s</td>
        <td><select name="civilite" tabindex="1">
        <option value="0" {$this->get_form_selected_value("civilite", "0", "2")} data-html-text="%s">%s</option>
        <option value="1" {$this->get_form_selected_value("civilite", "1", "2")} data-html-text="%s">%s</option>
        <option value="2" {$this->get_form_selected_value("civilite", "2", "2")} data-html-text="%s">%s</option>
        </select>
        </td>
        <td>
        </td>
        <tr>
        <tr>
        <td class="label">%s</td>
        <td><input type="text" name="nom" id="nom" tabindex="2" size="40" value="{$this->get_form_value('nom')}" class="txtinput"></td>
        <td>
        </td>
        </tr>
        <tr>
        <td class="label">%s</td>
        <td><input type="text" name="prenom" id="prenom" tabindex="3" size="40" value="{$this->get_form_value('prenom')}" class="txtinput"></td>
        <td>
        </td>
        </tr>
        <tr>
        <td class="label">%s</td>
        <td><input name="cp" type="number" class="txtinput" id="cp" tabindex="5" value="{$this->get_form_value('cp')}" size="10"></td>
        <td>
        </td>
        </tr>
        <tr>
        <td class="label">%s</td>
        <td><input name="mobile" type="tel" class="txtinput" id="mobile" tabindex="7" value="{$this->get_form_value('mobile')}" size="15"></td>
        <td>
        </td>
        </tr>
        <tr>
        <td class="label">%s</td>
        <td><input name="email" type="email" class="txtinput email" id="email" autofocus  tabindex="8" value="{$this->get_form_value('email')}"></td>
        <td>
        </td>
        </tr>
        <tr>
        <td class="label">%s</td>
        <td><input name="modele" type="text" class="txtinput" id="modele" tabindex="9" value="{$this->get_form_value('modele')}"></td>
        <td>
        </td>
        </tr>
        <tr>
        <td class="label">%s</td>
        <td><input name="immatriculation" type="text" class="txtinput" id="immatriculation" tabindex="10" value="{$this->get_form_value('immatriculation')}"></td>
        <td>
        </td>
        </tr>
EOF;
		if ($this->info['type']['ext'] != 0 || $this->info['type']['int'] != 0) {
			$ret .= <<< EOF
            <tr>
            <td class="label">%s</td>
            <td>
            {$this->get_type_select("type", "12")}
            </td>
            <td>
            </td>
            </tr>
EOF;
		} else {
			$ret .= <<< EOF
            <input name="type" type="hidden" value="ext" />
EOF;
		}
		$ret .= <<< EOF
        </table>
        </fieldset>
        </div>
EOF;
		$ret = sprintf($ret
			, __('Information', 'parking-management')
			, __('Civility', 'parking-management')
			, __('Miss', 'parking-management')
			, __('Miss', 'parking-management')
			, __('Madame', 'parking-management')
			, __('Madame', 'parking-management')
			, __('Mister', 'parking-management')
			, __('Mister', 'parking-management')
			, __('Name', 'parking-management')
			, __('Surname', 'parking-management')
			, __('Zip Code', 'parking-management')
			, __('Mobile', 'parking-management')
			, __('Email', 'parking-management')
			, __('Vehicle Model', 'parking-management')
			, __('Matriculation', 'parking-management')
			, __('Service', 'parking-management')
		);

		return $ret;
	}

	private function form_trip()
	{
		$ret = <<< EOF
        <div>
        <fieldset class="formulaire">
        <legend title="voyage">%s</legend>
        <table>
        <tr>
        <td class="label">%s</td>
        <td><input name="destination" type="text" class="txtinput" id="destination" tabindex="13" value="{$this->get_form_value('destination')}"></td>
        <td>
        </td>
        </tr>
        </table>
        </fieldset>
        </div>
EOF;
		$ret = sprintf($ret
			, __('Trip', 'parking-management')
			, __('Destination', 'parking-management')
		);
		return $ret;
	}

	private function form_aller()
	{
		$ret = <<< EOF
        <div>
        <fieldset class="formulaire">
        <legend title="depart">%s</legend>
        <table>
EOF;
		if ($this->info['terminal'] != 'Zaventem') {
			$ret .= <<< EOF
            <tr>
            <td class="label">Terminal</div></td>
            <td>
            {$this->get_terminal_select("terminal_aller", "15")}
            </td>
            <td>
            </td>
            <tr>
EOF;
		}
		$ret .= <<< EOF
        <tr>
        <td class="label">%s</td>
        <td><input
        id="navette"
		autocomplete="off"
        name="navette"
        type="text"
        size="20"
        class="txtinput date_navette navette"
        maxlength="20"
        tabindex="16"
        style="position:relative; z-index:50;"
        value="{$this->get_form_value('navette')}"
        >
        </td>
        <td>
        </td>
        </tr>
        </table>
        </fieldset>
        </div>
EOF;
		$ret = sprintf($ret
			, __('Departure', 'parking-management')
			, __('Date and time of deposit of the vehicle to the parking', 'parking-management')
		);

		return $ret;
	}

	private function form_retour()
	{
		$ret = <<< EOF
      <div>
      <fieldset class="formulaire">
      <legend title="retour">%s</legend>
      <table>
EOF;
		if ($this->info['terminal'] != 'Zaventem') {
			$ret .= <<< EOF
          <tr>
          <td class="label">Terminal</div></td>
          <td>
          {$this->get_terminal_select("terminal_retour", "18")}
          </td>
          <td>
          </td>
          <tr>
EOF;
		}
		$ret .= <<< EOF
      <tr>
      <td class="label">%s</td>
      <td><input
      id="date_retour"
      autocomplete="off"
      name="date_retour"
      type="text"
      size="20"
      class="txtinput date_retour"
      maxlength="20"
      tabindex="19"
      style="position:relative; z-index:50;"
      value="{$this->get_form_value('date_retour')}"
      >
      </td>
      <td>
      </td>
      </tr>
      <tr>
      <td class="label">%s</td>
      <td>
        <select name="nbr_retour" id="nbr_retour" class="" tabindex="20" aria-invalid="false">
            <option value="0" selected="selected">0</option>
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3">3</option>
            <option value="4">4</option>
            <option value="5">5 (+7€)</option>
            <option value="6">6 (+14€)</option>
            <option value="7">7 (+21€)</option>
            <option value="8">8 (+28€)</option>
        </select>
      <td>
      <td>
      </td>
      </tr>
      </table>
      </fieldset>
      </div>
EOF;
		$ret = sprintf($ret
			, __('Return', 'parking-management')
			, __('Date and time of landing return', 'parking-management')
			, __('Number of people', 'parking-management')
		);

		return $ret;
	}

	private function form_condition(): string
	{
		$cg = get_page_by_path('conditions-generales');
		if (!$cg)
			return "";
		$ret = <<< EOF
        <div>
        <fieldset class="formulaire">
        <legend title="condition">%s</legend>
        <input type="checkbox" name="conditiongenerale" id="conditiongenerale"/> <a class="lbp-inline-link-1 cboxElement" target="_blank" href="%s">%s</a>
        </fieldset>
        </div>
EOF;
		return sprintf($ret, __('Terms and conditions', 'parking-management'), $cg->post_name, __('Please read and accept the terms and conditions', 'parking-management'));
	}

	function form_bouton(): string
	{
		$ret = <<< EOF
        <fieldset class="formulaire buttons">
        <section id="buttons">
        <button type="submit" id="submitbtn" tabindex="31" value="Envoyer">%s</button>
        </section>
        </fieldset>
EOF;
		return sprintf($ret, __('Validate', 'parking-management'));
	}

	function form_dialog(): string
	{
		return <<< EOF
<div id="dialogForm" name="dialogForm" class="dialogForm" style="display: none; z-index:300;" title="Confirmation de réservation" >
	<form name="pkmgmt-reservation2" id="pkmgmt-reservation2">
		<table id="table_validation" class="validation">
			<tbody>
				<tr><td><label for="email2">email</label></td><td><input type="email" id="email2" name="email" class="email"></td></tr>
				<tr><td><label for="navette2">Date d'aller</label></td><td><input type="text" id="navette2" name="navette" class="navette" /></td></tr>
				<tr><td><label for="date_retour2">Date de retour</label></td><td><input type="text" id="date_retour2" name="date_retour" class="date_retour"></td></tr>
			</tbody>
		</table>
	</form>
</div>
EOF;
	}

	private function get_terminal_select($name, $tabindex): string
	{
		$options_aerogare = match ($this->info['terminal']) {
			"Roissy" => array('1' => 'Terminal 1', '2A' => 'Terminal 2A', '2B' => 'Terminal 2B', '2C' => 'Terminal 2C',
				'2D' => 'Terminal 2D', '2E' => 'Terminal 2E', '2F' => 'Terminal 2F',
				'2G' => 'Terminal 2G', '3' => 'Terminal 3'),
			"Zaventem" => array('A' => 'Terminal A', 'B' => 'Terminal B', 'T' => 'Terminal T'),
			default => array('W' => 'Orly 1, Orly 2, Orly 3', 'S' => 'Orly 4'),
		};
		$default = array_keys($options_aerogare);
		$default = array_shift($default);
		$ret = "<div class=\"pkmgmt-radio\">";
		foreach ($options_aerogare as $value => $label) :
			$ret .= "<input type=\"radio\" name=\"{$name}\" id=\"${name}-terminal-{$value}\" class=\"pkmgmt-radio-input\" value=\"{$value}\" {$this->get_form_checked_value($name,$value,$default)}>";
			$ret .= "<label class=\"pkmgmt-radio-label\" for=\"${name}-terminal-{$value}\">$label</label>";
		endforeach;
		$ret .= "</div>\n";
		return $ret;
	}

	private function get_type_select($name, $tabindex): string
	{
		$options_type = array();
		foreach ($this->info['type'] as $typename => $value) {
			if ($value == 1)
				$options_type[$typename] = ($typename == 'int') ? __('Indoor', 'parking-management') : __('Outdoor', 'parking-management');
		}
		$default = array_keys($options_type);
		$default = array_shift($default);
		## TMP
		$default = 'int';
		##
		$ret = <<< EOF
        <select name="{$name}" id="type" class="select" tabindex="$tabindex">
EOF;
		foreach ($options_type as $value => $label) :
			$ret .= "<option value=\"{$value}\" {$this->get_form_selected_value($name,$value,$default)} >$label</option>\n";

		endforeach;
		$ret .= <<< EOF
        </select>
EOF;
		return $ret;
	}

	private function get_form_value($name, $default = "")
	{
		$post = array_merge($_POST, $_GET);
		return (is_null($post) || !isset($post[$name])) ? $default : $post[$name];
	}

	private function get_form_checked_value($name, $value = 0, $default_value = 0)
	{
		$default = 'checked="checked"';
		$post = array_merge($_POST, $_GET);
		if (is_null($post) || !isset($post[$name]))
			return ($value == $default_value ? $default : "");
		else
			return ($value == $post[$name] ? $default : "");
	}

	private function get_form_selected_value($name, $value = 0, $default_value = 0)
	{
		$default = 'selected="selected"';
		$post = array_merge($_POST, $_GET);
		if (is_null($post) || !isset($post[$name]))
			return ($value == $default_value ? $default : "");
		else
			return ($value == $post[$name] ? $default : "");
	}

	private static function get_unit_tag($id = 0)
	{
		static $global_count = 0;
		$global_count += 1;
		if (in_the_loop()) {
			$unit_tag = sprintf('pkmgmt-f%1$d-p%2$d-o%3$d',
				absint($id), get_the_ID(), $global_count);
		} else {
			$unit_tag = sprintf('pkmgmt-f%1$d-o%2$d',
				absint($id), $global_count);
		}
		return $unit_tag;
	}

	private function pkmgmt_format_atts($atts)
	{
		$html = '';

		$prioritized_atts = array('type', 'name', 'value');

		foreach ($prioritized_atts as $att) {
			if (isset($atts[$att])) {
				$value = trim($atts[$att]);
				$html .= sprintf(' %s="%s"', $att, esc_attr($value));
				unset($atts[$att]);
			}
		}

		foreach ($atts as $key => $value) {
			$value = trim($value);

			if ('' !== $value) {
				$html .= sprintf(' %s="%s"', $key, esc_attr($value));
			}
		}

		$html = trim($html);

		return $html;
	}
}
