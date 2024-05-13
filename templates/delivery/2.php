<?php
Class Delivery
{
	private $_pdf = null;
	private $_data = null;
	private $_double = true;
	
	function __construct(&$pdf, $data, $double = true ) {
		$this->_pdf = &$pdf;
		$this->_data = $data;
		$this->_double = $double;
	}
	function addlogo($pos) {
		$pdf = &$this->_pdf;
		$data = $this->_data;
		$pdf->Image( $data['logo'],
				$pos, 10, $this->pixelsToMillimeters(135), $this->pixelsToMillimeters(54), 'JPG' );
	}
	function addSociete($xpos) {
		$pdf=&$this->_pdf;
		$data=$this->_data;
		$pdf->SetFont('','B',10);
		$pdf->MultiCell(50,2, $data['_address'], 0, '', 0, 1, $xpos, 5, true);
		$pdf->Text($xpos,15, 'Téléphone : '.$data['_phone']);
		$pdf->Text($xpos,20, 'Horaire : '.$data['_time']);
		$pdf->Text($xpos,25, 'Email : '.$data['_email']);
		$pdf->Text($xpos,30, 'SARL '.$data['_name']. ' - '.$data['_RCS']);
	}
	function head() {
		$pdf=&$this->_pdf;
		$data=$this->_data;
		$pdf->SetFont('', '');
		$pdf->setY(35);
		$this->td();
		$pdf->Cell(143,8, $data['_delivery'], 1, 0, 'C', 1);
		$pdf->Cell(143,8, $data['_delivery'], 1, 0, 'C', 1);
		$pdf->Ln();$this->td();$pdf->SetFont('', 'B', 9);
		$this->info('Nom et prénom', strtoupper($data['nom']).' '.strtoupper($data['prenom']));
		$this->info('Adresse', strtoupper($data['adresse'] ." " . $data['cp']." " . $data['ville']));
	}
	function info($hl, $content)
	{
		$pdf=&$this->_pdf;
		$data=$this->_data;
		for ( $i = 0; $i < 2; $i++)
		{
			$pdf->Cell(40, 8, $hl . ' :', 1, 0, 'L', 1 );
			$pdf->Cell(103, 8, $content, 1, 0, 'L', 1 );
		}
		$pdf->Ln();
	}
	function trip($hl,$left, $hr,$right) {
		$pdf=&$this->_pdf;
		$data=$this->_data;
		for ( $i = 0; $i < 2; $i++)
		{
			$this->th();
			$pdf->Cell(40, 8, $hl.' :', 1, 0, 'L', 1 );
			$this->td();
			$pdf->Cell(35, 8, $left, 1, 0, 'L', 1 );
			$this->th2();
			$pdf->Cell(33, 8, $hr. ' :', 1, 0, 'L', 1 );
			$this->td();
			$pdf->Cell(35, 8, $right, 1, 0, 'L', 1 );
		}
		$pdf->Ln();
	}
	function draw() {
		$pdf = &$this->_pdf;
		$data = $this->_data;
		$double = $this->_double;
		$pdf->AddPage( "L" );
		$this->addlogo(10);
		if ( $double  ) $this->addlogo(150);
		$pdf->SetDrawColor(0,0,0);
		$this->addSociete(60);
		if ( $double  ) $this->addSociete(200);
		$this->head();
		$this->info('Mobile', $data['mobile'] );
		$this->info('Modele', substr($data['modele'],0,20));
		$this->info('Immatriculation', $data['immatriculation']);
		$this->th2();
		$this->info('prix', $data['prix_resa'].' €');
		$this->td();
		$this->info('Destination', substr($data['destination'],0,20));
		$this->info('Compagnie', $data['compagnie']);
		if ( array_key_exists('nbr_aller', $data) )
			$nbr_pers = $data['nbr_aller'];
		else
			$nbr_pers = $data['nbr_retour'];
		if ( array_key_exists('nbr_aller', $data) && array_key_exists('nbr_retour', $data))
			$nbr_pers = max($data['nbr_aller'], $data['nbr_retour']);
		$this->info('nbr personne', $nbr_pers);
		$this->th();
		$pdf->Cell(75,8, 'Fiche Navette Aller', 1, 0, 'L', 1);
		$this->th2();
		$pdf->Cell(68,8, 'Fiche Navette Retour', 1, 0, 'L', 1);
		$this->th();
		$pdf->Cell(75,8, 'Fiche Navette Aller', 1, 0, 'L', 1);
		$this->th2();
		$pdf->Cell(68,8, 'Fiche Navette Retour', 1, 0, 'L', 1);
		$pdf->Ln();
	//	$this->trip('Depot le', $data['_navette'],'Heure dépot', $data['_heure_navette'] );
		$pdf->SetFont('', 'B', 17);
		$this->trip('Depot le', $data['_navette'],'Retour le', $data['_date_retour'] );
		$this->trip('Heure dépot', $data['_heure_navette'],'Heure', $data['_heure_retour'] );
		$pdf->SetFont('', 'B', 9);
		$this->trip('Terminal aller', $data['terminal_aller'], 'Terminal retour', $data['terminal_retour'] );
		$pdf->Ln(1);
		$pdf->SetFont('', '', 10);
		$y = $pdf->getY();
		$pdf->MultiCell(143,5, $data['deliverymessage'], 0, '', 0, 1, '', '', true);
		$pdf->setXY(148,$y);
		$pdf->MultiCell(143,5, $data['deliverymessage'], 0, '', 0, 1, '', '', true);
		$pdf->Ln();
		$texte = "J'accepte les conditions générales
Signature du client
Bon pour mandat
";
		$pdf->SetFont('', '', 9);
		$y = $pdf->getY();
		$pdf->MultiCell(60,3, $texte, 0, '', 0, 1, '', '177	', true);
		$pdf->MultiCell(60,3, $texte, 0, '', 0, 1, '148', '177', true);
		$pdf->SetDrawColor(0,0,0);
		$pdf->MultiCell(80,3, " \n \n \n", 'LRTB', '', 0, 1, '60', '177	', true);
		$pdf->MultiCell(80,3, " \n \n \n", 'LRTB', '', 0, 1, '210', '177	', true);

	}
	function pixelsToMillimeters($px) {
		return $px * 25.4 / 72;
	}
	function th() {
		$pdf = &$this->_pdf;
		$pdf->SetFillColor(80, 80, 80);
		$pdf->SetTextColor(255,255,255);
	}
	function th2() {
		$pdf = &$this->_pdf;
		$pdf->SetFillColor(120, 120, 120);
		$pdf->SetTextColor(255,255,255);
	}
	function td() {
		$pdf = &$this->_pdf;
		$pdf->SetFillColor(255, 255, 255);
		$pdf->SetTextColor(0);
	}
}
?>