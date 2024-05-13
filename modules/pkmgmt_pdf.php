<?php
defined('_PKMGMT') or die('Restricted accesss');
require_once PKMGMT_PLUGIN_MODULES_DIR.DS."reservation.php";
require_once PKMGMT_PLUGIN_MODULES_DIR.DS."tcpdf".DS."tcpdf.php";

Class pkmgmt_pdf extends reservation
{
	protected $_pdf = null;
	protected $post_id = 0;
	protected $format = 'A4';
	protected $tva = array('tva1' => 0, 'tva2' => 0);
	private $_style = array();
	private $_content = "";
	private $double = true;

	public function __construct($post = null)
	{
		$this->_pdf = new TCPDF();
		$pdf =& $this->_pdf;
		$pdf->SetMargins(5, 5, 5, 5);
		$pdf->SetHeaderMargin(1);
		$pdf->SetFooterMargin(0);
		$pdf->SetLineWidth(0.1);
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);

		$pdf->SetCreator(PDF_AUTHOR);
		$pdf->SetTitle("Impression");
		$pdf->SetSubject("Impression");
		$pdf->SetKeywords("Bon de prise en charge, facture, bon de sortie");

		$pdf->setHeaderFont(Array('helvetica', '', 10));

		$pdf->setRTL(false);

		$this->post_id = $post;
		if ( ! is_null($post) )
			parent::__construct($post);
	}
	public function getIDS($field = 'navette')
	{
		$post = array_merge($_POST,$_GET);

		if (array_key_exists('ids',$post) && isset($post['ids']))
		{
			$ids = $post['ids'];
			if ( ! is_array($ids) && strpos( $ids, ",") !== false )
				$ids = explode(",", $ids );
			if ( ! is_array($ids) )
				$ids = array( $ids );
			return $ids;
		}
		if (array_key_exists('date',$post) && isset($post['date']))
		{
			$reservation = $this->database['table_reservation'];
			$query = "SELECT id ";
			$query .= "FROM `$reservation` WHERE `$field` BETWEEN '%s 00:00:00' AND '%s 23:59:59' AND `status` <3";
			$query .= " ORDER BY $field";
			$query = sprintf( $query, $post['date'], $post['date'] );
			$this->connect();
			$result = $this->db->get_results($query, ARRAY_A);
			$ids = array();
			foreach ( $result as $value)
			{
				$ids[] = $value['id'];
			}
			return $ids;
		}
		return array();
	}

	private function planning()
	{
		$post = array_merge($_POST,$_GET);
		$reservation = $this->database['table_reservation'];
		$this->connect();
		$query = "SELECT type AS Type, UPPER(`nom`) AS Nom, `mobile`, SUBSTRING(`navette`, -8, 5) AS heure,".
            " `terminal_aller` AS Terminal,";
		$query .= " UPPER(LEFT(`destination`,20)) AS Destination, `nbr_aller` AS 'N Pers', CONCAT(`prix_resa`, ' &euro;') AS PX ";
		$query .= "FROM `$reservation` WHERE `status` < 3 AND `navette` BETWEEN '%s 00:00:00' AND '%s 23:59:59'";
		$query .= " ORDER BY heure";
		$query = sprintf( $query, $post['date'], $post['date'] );
		$result = $this->db->get_results($query, ARRAY_A);

		$nbr_in = count($result);
		$in = $this->lplanning( $result );
		$query = "SELECT type AS Type, UPPER(`nom`) as Nom, `mobile`, DATE_FORMAT(`date_retour`, '%H:%i') AS heure,".
	            " `terminal_retour` AS Terminal, ";
		$query .= " UPPER(LEFT(`destination`, 20)) AS 'provenance', `nbr_retour` AS 'N Pers', CONCAT(`prix_resa`, ' &euro;') AS PX ";
		$query .= sprintf( "FROM `$reservation` WHERE `status` < 3 AND `date_retour` BETWEEN '%s 00:00:00' AND '%s 23:59:59'", $post['date'], $post['date'] );
		$query .= " ORDER BY heure";
		$result	= $this->db->get_results($query, ARRAY_A);
		$nbr_out = count($result);
		$out = $this->lplanning( $result );
		$tpl_planning  = file_get_contents( PKMGMT_PLUGIN_TEMPLATES.DS."plannings".DS."1.html");
		$return = $tpl_planning;
		$return = str_replace( "__PLANNING_IN__", $in, $return );
		$return = str_replace( "__NBR_IN__", $nbr_in, $return );
		$return = str_replace( "__PLANNING_OUT__", $out, $return );
		$return = str_replace( "__NBR_OUT__", $nbr_out, $return );
		$return = str_replace( "__DATE__", $post['date'], $return );
		print $return;
	}
	function lplanning($line)
	{
        $return = "<tr>";
        if (empty($line))
        	return;
        $titres = array_keys( $line[0] );
        foreach ( $titres as $titre )
        {
            $return .= "<th>$titre</th>";
        }
        $return .= "</tr>";
        foreach ( $line as $elem )
        {
            $return .= "<tr>";
            foreach ( $elem as $key => $value )
            {
                $return .= "<td>$value</td>";
            }
            $return .= "</tr>";
        }
        return $return;
    }
	public function pkmgmtPrint()
	{
		$post = array_merge($_POST,$_GET);

		if ( array_key_exists('out', $post) && isset($post['out']) && $post['out'] == 'planning')
		{
			$this->planning();
			return;
		}
		$ids = $this->getIDS('navette');
		if (empty($ids))
			return;
		$cxs = $post['cx'];
		if ( ! is_array($cxs) && strpos( $cxs, ",") !== false )
			$cxs = explode(",", $cxs );
		if ( ! is_array($cxs) )
			$cxs = array( $cxs );
		$method_cx = array('fc' => 'invoice', 'bp' => 'delivery', 'bs' => 'exitsplit');
		$ids = $this->orderByDate($ids);

		foreach( $ids as $id )
		{
			foreach ( $cxs as $cx )
			{
				if (array_key_exists($cx, $method_cx))
				{
					$func = $method_cx[$cx];
					$this->$func($id);
				}
			}
		}
		$this->writeHTML();
		$this->pdfout();
	}

	function writeHTML()
	{
		$pdf =& $this->_pdf;
		$content = implode("\n", $this->_style);
		$content .= $this->_content;
		$pdf->writeHTML($content);
	}

	function invoicePrint()
	{
		$post = array_merge($_POST,$_GET);
		$ids = $post['ids'];
		$this->invoice($ids);
		$this->double = true;
		$this->pdfout("Facture");
	}

	function exitsplitPrint()
	{
		$post = array_merge($_POST,$_GET);
		$ids = $post['ids'];
		$this->exitsplit($ids);
		$this->pdfout("Bon_de_sortie");
	}

	function deliveryPrint()
	{
		$post = array_merge($_POST,$_GET);
		$ids = $post['ids'];
		$this->delivery($ids);
		$this->pdfout("Bon_de_prise_en_charge");
	}

	function invoiceSend()
	{
		try {
		$this->format = 'A5';
		$post = array_merge($_POST,$_GET);
		$id = $post['ids'];
		$this->double = false;
		$this->invoice($id);
		$facture = $this->pdfout("facture", true);
		$filename = "facture_" . "FPOS-". $this->_data['numresa'] . "-" . $id.".pdf";
		$this->addStringAttachment($facture, $filename);
		global $phpmailer;
		if ( !is_object( $phpmailer ) || !is_a( $phpmailer, 'PHPMailer' ) ) {
			require_once ABSPATH . WPINC . '/class-phpmailer.php';
			require_once ABSPATH . WPINC . '/class-smtp.php';
			$phpmailer = new PHPMailer( true );
		}
		$phpmailer->AddAddress($this->_data['email']);
		$phpmailer->FromName = $this->name;
		$phpmailer->From = $this->info['email'];
		$phpmailer->Subject = "Facture ". $this->name;
		$phpmailer->isHTML(true);
		$phpmailer->CharSet = "UTF-8";
		$phpmailer->MsgHTML("Facture ". $this->name);
		$this->addStringAttachments($phpmailer);

		$result = $phpmailer->Send();
		if ( ! $result )
			throw_log(__("Sending Error", 'parking-management'));
		$return = array();
		$return['Result'] = "OK";
		print @json_encode( $return );
		}
		catch (phpmailerException $e)
		{
			$return = array();
			$return['Result'] = "ERROR";
			$return['Message'] = $ex->errorMessage();
			print @json_encode( $return );
		}
		catch (Exception $ex)
		{
			$return = array();
			$return['Result'] = "ERROR";
			$return['Message'] = $ex->getMessage();
			print @json_encode( $return );
		}
	}

	function invoice($id = 0)
	{
		$this->_data = $this->getResa($id);
		$pdf =& $this->_pdf;
		if ( ! class_exists("Invoice") )
			require_once(PKMGMT_PLUGIN_TEMPLATES.DS."invoices".DS."1.php");
		try
		{
			global $wpdb;
			$this->_data['logo'] = $this->info['logo'];
			$this->_data['_invoice'] = __('INVOICE', 'parking-management');
			$this->_data['_invoicenum'] = "FPOS-". $this->_data['numresa'] . "-" . $id;
			$this->_data['_name'] = $this->title;
			$this->_data['_address'] = $this->info['adresse'];
			$this->_data['_phone'] = $this->info['telephone'];
			$this->_data['_RCS'] = $this->info['RCS'];
			static $post_id;
			$post_id = $this->post_id;
			$tarif = Tarif::getInstance();
			$tarif->init($this->database, $this->_data,$this->info['terminal']);
			$tarif->setSuppTarif( $id );
			$tarif_details = $tarif->getArrayDetail();
			$this->_data['details'] = $tarif_details;
			$this->_data['HT'] = $this->totaux($tarif_details, 'prixht');
			$this->_data['TVA1'] = $this->getTVA($tarif_details, 'tva1');
			$this->_data['TVA2'] = $this->getTVA($tarif_details, 'tva2');
			$this->_data['TVA']['TVA1'] = $this->info['tva1'];
			$this->_data['TVA']['TVA2'] = $this->info['tva2'];
			$this->_data['TTC'] = $this->totaux($tarif_details, 'prix');
			$format = $this->format;
			$orientation = 'L';
			if ( $format == 'A5')
				$orientation = 'P';
			$inv = new Invoice($pdf,$this->_data,$this->double, $format, $orientation);
			$inv->draw();
		}
		catch (Exception $ex)
		{
			die($ex->getMessage());
		}
	}

	function exitsplit($id = 0)
	{
		$this->_data = $this->getResa($id);
		$pdf =& $this->_pdf;
		if ( ! class_exists("exitSplit") )
			require_once(PKMGMT_PLUGIN_TEMPLATES.DS."exitsplit".DS."1.php");
		try
		{
			global $wpdb;
			$retour = DateTime::createFromFormat("Y-m-d H:i:s", $this->_data['date_retour']);
			$this->_data['_date_retour'] = $retour->format("d/m/Y");
			$this->_data['_heure_retour'] = $retour->format("H:i");
			$ext = new Exitsplit($pdf,$this->_data);
			$ext->draw();
		}
		catch (Exception $ex)
		{
			die($ex->getMessage());
		}
	}

	function delivery($id = 0)
	{
		$this->_data = $this->getResa($id);
		$pdf =& $this->_pdf;
		if ( ! class_exists("delivery") )
			require_once(PKMGMT_PLUGIN_TEMPLATES.DS."delivery".DS."2.php");
		try
		{
			global $wpdb;
			$this->_data['logo'] = $this->info['logo'];
			$this->_data['_delivery'] = __('DELIVERY', 'parking-management');
			$this->_data['_name'] = $this->title;
			$this->_data['_email'] = $this->info['email'];
			$this->_data['_time'] = $this->info['time']['start'] . " - " . $this->info['time']['end'];
			$this->_data['_address'] = $this->info['adresse'];
			$this->_data['_phone'] = $this->info['telephone'];
			$this->_data['_RCS'] = $this->info['RCS'];
			$retour = DateTime::createFromFormat("Y-m-d H:i:s", $this->_data['date_retour']);
			$this->_data['_date_retour'] = $retour->format("d/m/Y");
			$this->_data['_heure_retour'] = $retour->format("H:i");

			$navette = DateTime::createFromFormat("Y-m-d H:i:s", $this->_data['navette']);
			$this->_data['_navette'] = $navette->format("d/m/Y");
			$this->_data['_heure_navette'] = $navette->format("H:i");
			$this->_data['deliverymessage'] = $this->info['deliverymessage'];
			$del = new delivery($pdf,$this->_data, true);
			$del->draw();
		}
		catch (Exception $ex)
		{
			die($ex->getMessage());
		}
	}

	private function totaux($details, $type)
	{
		$ret = 0;
		foreach ($details as $detail)
		{
			$ret += $detail[$type];
		}
		return $ret;
	}

	private function getTVA($details, $tva)
	{
		$ret = 0;
		foreach ($details as $detail)
		{
			if ($detail['tva'] == $tva)
				$ret += $detail['prixtva'];
		}
		return $ret;
	}

	private function createTableInvoiceDetail($details)
	{
		$ret = array();
		foreach ($details as $elem)
		{
			$ret[]=$this->inTR($this->inTD($elem['desc']) . $this->inTD($elem['prixht']));
		}
		for ($i = count($ret); $i<7;$i++)
		{
			$ret[] = $this->inTR($this->inTD("&nbsp;").$this->inTD("&nbsp;"));
		}
		return implode("\n", $ret);
	}

	private function createArrayInvoiceDetail($details)
	{
		$ret = array();
		foreach ($details as $elem)
		{
			$ret[]=$this->inTR($this->inTD($elem['desc']) . $this->inTD($elem['prixht']));
		}
		for ($i = count($ret); $i<7;$i++)
		{
			$ret[] = $this->inTR($this->inTD("&nbsp;").$this->inTD("&nbsp;"));
		}
		return implode("\n", $ret);
	}

	private function inTD($html)
	{
		return "<td>$html</td>";
	}

	private function inTR($html)
	{
		return "<tr>$html</tr>";
	}

	private function replaceData($content)
	{
		return preg_replace_callback("/{[^}\s]+}/i", array($this, 'replaceFromData'), $content);
	}

	private function createPage($contents, $format=null, $type = null)
	{
		if ( is_null($type) || is_null($format) )
			throw new Exception(__("Error during PDF creating contact your administrator", 'parking-management'));

		$ret = <<< EOF
<page format="$format" backtop="20mm" backbottom="10mm" backleft="3mm" backright="3mm" style="font-size: 12pt">
EOF;
		$ret .= file_get_contents($contents['HEADER']);
		$ret .= $this->tableFormat(file_get_contents($contents['HTML']), $format, $type);
		$ret .= "<page_footer><table><tr><td>";
		$ret .= file_get_contents($contents['FOOTER']);
		if ( $format == 'A4' && $this->double)
			$ret .= "</td><td>" . file_get_contents($contents['FOOTER']);
		$ret .= "</td></tr></table></page_footer>";
		$ret .= <<< EOF
</page>
EOF;
	return $ret;
	}
	private function tableFormat($content, $format = 'A4', $type = null)
	{
		if ( is_null($type) )
			throw new Exception(__("Error during PDF creating contact your administrator", 'parking-management'));
		$ret = <<< EOF
<table id="$type">
  <tr>
    <td>
EOF;
		$ret .= $content;
		if ( $format ==  'A4' )
			$ret .= "</td><td>" . $content;
		$ret .= <<< EOF
    </td>
  </tr>
</table>
EOF;
		return $ret;
	}

	function pixelsToMillimeters($px)
	{
		return $px * 25.4 / 72;
	}

	function pdfout($name = "impression", $get = false)
	{
		$pdf =& $this->_pdf;
		$_type		= "pdf";
		$_mime		= 'application/pdf';
		$_charset	= 'utf-8';

		$data = $pdf->Output('', 'S');
		if ( $get )
			return $data;
		header("Content-Type:".$_mime."; charset=".$_charset);
		header('Content-Disposition:inline; filename="'.$name.'.pdf"');
		echo $data;
	}
}
?>
