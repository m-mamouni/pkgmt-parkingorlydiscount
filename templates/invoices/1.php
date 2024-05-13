<?php
Class Invoice
{
	private $_pdf = null;
	private $_data = null;
	private $_double = true;
	private $_details = array();
	private $format = 'A4';
	private $orientation = 'L';
	
	function __construct(&$pdf, $data, $double = true, $format ='A4', $orientation = 'L' ) {
		$this->_pdf = &$pdf;
		$this->_data = $data;
		$this->_double = $double;
		$this->_details = $data['details'];
		$this->format = $format;
		$this->orientation = $orientation;
	}
	function addlogo($pos) {
		$pdf = &$this->_pdf;
		$data = $this->_data;
		$pdf->Image( $data['logo'],
				$pos, 10, $this->pixelsToMillimeters(135), $this->pixelsToMillimeters(54), 'JPG' );
	}
	function addTitle() {
		$pdf = &$this->_pdf;
		$data = $this->_data;
		$double = $this->_double;
		$width = 69;
		if ($double )
			$width *= 2;
		$pdf->SetFont('', 'B', 20);
		$pdf->SetFillColor(255, 255, 255);
		$pdf->Cell($width, 12, $data['_invoice'], 1, 0, 'C', 1 );
		if ($double ) $pdf->Cell($width, 12, $data['_invoice'], 1, 0, 'C', 1 );
		$pdf->SetFont('', '', 8);
		$pdf->Ln(9);
		$pdf->Cell($width, 5, $data['_invoicenum'], 1, 0, 'C', 1 );
		if ($double ) $pdf->Cell($width, 5, $data['_invoicenum'], 1, 0, 'C', 1 );
	}
	function addSociete($xpos) {
		$pdf=&$this->_pdf;
		$data=$this->_data;
		$pdf->SetFont('','B',16);
		$pdf->Text($xpos,50,$data['_name']);
		$pdf->SetFont('','B',12);
		$pdf->Text($xpos,56,'SARL '.$data['_name']);
		$pdf->SetFont('','',8);
		$pdf->Ln();
		$pdf->MultiCell(50,2, $data['_address'], 0, '', 0, 1, $xpos, 62	, true);
		$pdf->Text($xpos,68, 'Téléphone : '.$data['_phone']);
		$pdf->Text($xpos,71, $data['_RCS']);
	}
	function client($xpos) {
		$pdf=&$this->_pdf;
		$data=$this->_data;
		$pdf->SetFont('', '', 10);
		$pdf->Text($xpos,50, $data['nom'].' '. $data['prenom']);
		$pdf->Ln();
		$pdf->MultiCell(60,2, $data['adresse'], 0, '', 0, 1, $xpos, 55, true);
		$pdf->Text($xpos,65, $data['cp'].' '. $data['ville']);
	}
	function thead() {
		$pdf=&$this->_pdf;
		$double=$this->_double;
		$n=($double=='A4')?2:1;
		$this->th();
		$pdf->SetDrawColor(0,0,0);
		$pdf->SetXY(5,80);
		for ( $i = 0; $i < $n; $i++){
			$pdf->Cell(98,6, 'DESCRIPTION', 1, 0, 'L', 1 );
			$pdf->Cell(40,6, 'MONTANT', 1, 0, 'C', 1 );
		}
		$pdf->Ln();
	}
	function TableLine($desc,$prix) {
		$pdf = &$this->_pdf;
		$double = $this->_double;
		$n = ($double )? 2:1;
		for ($i=0;$i<$n;$i++){
			$pdf->Cell(98,6,$desc,1,0,'L',1);
			$pdf->Cell(40,6,$prix,1,0,'C',1);
		}
		$pdf->Ln();
	}
	function tableDetails(){
		$details=$this->_details;
		$nbrline=0;
		$maxline=10;
		$this->td();
		foreach ($details as $elem){
			$this->TableLine(str_replace("<br>", " ", $elem['desc']),$elem['prixht'].'€');
			$nbrline++;
		}
		for ($i=$nbrline;$i<$maxline;$i++){
			$this->TableLine('', '');}
	}
	function TotauxLine($desc,$prix) {
		$pdf=&$this->_pdf;
		$double=$this->_double;
		$n=($double=='A4')?2:1;
		for ( $i = 0; $i < $n; $i++) {
			$pdf->SetDrawColor(255,255,255);
			$pdf->Cell(98,6,$desc,1,0,'R',1);
			$pdf->SetDrawColor(0,0,0);
			$this->th();
			$pdf->Cell(40,6,$prix,1,0,'C',1 );
			$this->td();
		}
		$pdf->Ln();
	}
	function tableTotaux() {
		$data=$this->_data;
		$this->TotauxLine('HT',$data['HT'].'€');
		$this->TotauxLine('TVA '.$data['TVA']['TVA1'].'%',$data['TVA1'].'€');
		$this->TotauxLine('TVA '.$data['TVA']['TVA2'].'%',$data['TVA2'].'€');
		$this->TotauxLine('TTC',$data['TTC'].'€');
	}
	function draw() {
		$pdf = &$this->_pdf;
		$data = $this->_data;
		$double = $this->_double;
		$pdf->AddPage( $this->orientation, $this->format );
		$this->addlogo(10);
		if ( $double  ) $this->addlogo(150);
		$pdf->SetDrawColor(255,255,255);
		$pdf->setY(32);
		$this->addTitle();
		$this->addSociete(10);
		if ( $double  ) $this->addSociete(150);
		$this->client(80);
		if ($double  ) $this->client(218);
		$this->thead();
		$this->tableDetails();
		$this->tableTotaux();
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