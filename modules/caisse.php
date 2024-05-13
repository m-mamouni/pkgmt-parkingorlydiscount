<?php
defined('_PKMGMT') or die('Restricted accesss');

Class caisse extends pkmgmt_site
{

	public function __construct($post = null)
	{
		parent::__construct($post);
	}
	
	public function dateCaisse()
	{
		$return = array();
		try
		{
			$this->connect();
			$post = array_merge($_POST,$_GET);
			$reservation = $this->database['table_reservation'];
			$query = "
SELECT
  DISTINCT DATE_FORMAT(`date_retour`,'%Y-%m-%d') AS date_facture
FROM `$reservation`
WHERE
  `date_retour` BETWEEN DATE_SUB(NOW(), INTERVAL 10 DAY) AND (DATE_ADD(NOW(), INTERVAL 10 DAY)) AND
   (`status` = 3 OR `status` = 2)
ORDER BY date_facture DESC
LIMIT 0 , 30
";
			$result = $this->db->get_results( $query);
			$return['Result'] = $result;
			print @json_encode( $return );

		}
		catch ( Exception $ex)
		{
			$return['Result'] = array();
			$return['Message'] = $ex->getMessage;
			print @json_encode( $return );
		}
	}

	public function totalCaisse()
	{
		$return = array();
		try
		{
			$this->connect();
			$post = array_merge($_POST,$_GET);
			$seek = $post['seek'];
			$reservation = $this->database['table_reservation'];

			$query = "
SELECT
  COUNT(*) AS nbr_resa,
  SUM(`prix_resa`) AS total,
	  `paiement`,
  DATE_FORMAT(`date_retour`,'%Y-%m-%d') AS date_facture
FROM `$reservation`
WHERE
  `date_retour` LIKE '{$seek}%' AND
   (`status` = 3 OR `status` = 2)
GROUP BY date_facture,`paiement`
ORDER BY date_facture DESC
";
			$result = $this->db->get_results( $query );
			//if ( ! $result )
			$return['Result'] = $result;
			print @json_encode( $return );
		}
		catch (Exception $ex)
		{
			$return['Result'] = array();
			$return['Message'] = $ex->getMessage;
			print @json_encode( $return );		
		}
	}	
}
