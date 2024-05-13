<?php
Class Exitsplit
{
	private $_pdf = null;
	private $_data = null;
	private $_double = true;
	
	function __construct(&$pdf, $data) {
		$this->_pdf = &$pdf;
		$this->_data = $data;
	}
	function Cell($w,$h,$size,$desc, $double = false) {
		$pdf = &$this->_pdf;
		$data = $this->_data;
		$pdf->SetFont('', '', $size);
		$pdf->Cell($w, $h, $desc, 1, 0, 'C', 1);
		if ($double) $pdf->Cell($w, $h, $desc, 1, 0, 'C', 1);
		$pdf->Ln();
	}
	function MultiCell($w,$h,$size,$desc) {
		$pdf = &$this->_pdf;
		$data = $this->_data;
		$pdf->SetFont('', '', $size);
		$pdf->MultiCell($w, $h, $desc, 1, 'C');
	}

	function draw() {
		$pdf = &$this->_pdf;
		$data = $this->_data;
		$pdf->AddPage( "P" );
		$pdf->SetDrawColor(0,0,0);
		$this->td();
		$this->Cell(100, 30, 52, $data['_date_retour'],true);
		$this->Cell(100, 25, 52, $data['_heure_retour'],true);
		$this->Cell(100, 15, 25, $data['nom'],true);
		$this->MultiCell(100, 50, 40, substr($data['modele'],0,20) . "\n" . $data['immatriculation']);
		$pdf->setY(75);
		$pdf->setX(105);
		$this->MultiCell(100, 50, 40, substr($data['modele'],0,20) . "\n" . $data['immatriculation'], 1, 'C');
		$this->Cell(100, 10, 15, 'Déposé le : ' . $data['navette'] .' ('.$data['type'].')', true);
		$this->Cell(200, 40, 95, $data['_date_retour']);
		$this->Cell(200, 30, 75, $data['_heure_retour']);
		$this->MultiCell(200, 25, 42, substr($data['modele'],0,20) . "\n" . $data['immatriculation']);
		$this->Cell(200, 15,20, 'Déposé le : ' . $data['navette'] .' ('.$data['type'].')');
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