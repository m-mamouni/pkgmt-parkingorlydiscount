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
		$pdf->Ln();$this->th();$pdf->SetFont('', 'B', 9);
		for ( $i = 0; $i < 2; $i++)
		{
			$this->th();
			$pdf->Cell(40, 8, 'Nom et prénom :', 1, 0, 'L', 1 );
			$this->td();
			$pdf->Cell(103, 8, strtoupper($data['nom']).' '.strtoupper($data['prenom']), 1, 0, 'L', 1 );
		}
		$pdf->Ln();
		for ( $i = 0; $i < 2; $i++)
		{
			$this->th();
			$pdf->Cell(40, 8, 'Adresse :', 1, 0, 'L', 1 );
			$this->td();
			$pdf->Cell(103, 8, strtoupper($data['adresse']), 1, 0, 'L', 1 );
		}
		$pdf->Ln();
	}
	function info($hl,$left, $hr,$right) {
		$pdf=&$this->_pdf;
		$data=$this->_data;
		for ( $i = 0; $i < 2; $i++)
		{
			$this->th();
			$pdf->Cell(40, 8, $hl.' :', 1, 0, 'L', 1 );
			$this->td();
			$pdf->Cell(35, 8, $left, 1, 0, 'L', 1 );
			$this->th();
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
		$this->info('Mobile', $data['mobile'], 'prix', $data['prix_resa'].' €');
		$this->info('Immatriculation', $data['immatriculation'], 'Modele', substr($data['modele'],0,20));
		$this->info('Déposé le', $data['_navette'], 'Retour le', $data['_date_retour']);
		$this->td();
		$pdf->Cell(143, 8, '', 1, 0, 'C', 1 );
		$pdf->Cell(143, 8, '', 1, 0, 'C', 1 );
		$pdf->Ln();
		$this->info('Compagnie', $data['compagnie'], 'Destination', substr($data['destination'],0,20));
		$this->th();
		$pdf->Cell(143,8, 'Fiche Navette Aller', 1, 0, 'L', 1);
		$pdf->Cell(143,8, 'Fiche Navette Aller', 1, 0, 'L', 1);
		$pdf->Ln();
		$this->info('Depot le', $data['_navette'],'Heure dépot', $data['_heure_navette'] );
		$this->info('nbr de personne', $data['nbr_aller'],'Terminal', $data['terminal_aller'] );
		$this->th();
		$pdf->Cell(143,8, 'Fiche Navette Retour', 1, 0, 'L', 1);
		$pdf->Cell(143,8, 'Fiche Navette Retour', 1, 0, 'L', 1);
		$pdf->Ln();
		$pdf->SetFont('', 'B', 17);
		$this->info('Retour le', $data['_date_retour'],'Heure', $data['_heure_retour']);
		$pdf->SetFont('', 'B', 9);
		$this->info('Nbr de personne', $data['nbr_retour'], 'Terminal', $data['terminal_retour']);
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
        $pdf->setXY(148,$y);
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
	function td() {
		$pdf = &$this->_pdf;
		$pdf->SetFillColor(255, 255, 255);
		$pdf->SetTextColor(0);
	}
}
?>
