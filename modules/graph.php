<?php
defined('_PKMGMT') or die('Restricted accesss');

Class graph extends pkmgmt_site
{

	public function __construct($post = null)
	{
		parent::__construct($post);
	}
	
	public function nbr_resa_day()
	{
		$return = array();
		try
		{
			$this->connect();
			$post = array_merge($_POST,$_GET);
			$query = "
SELECT
  COUNT(*) AS nbr_resa,
  UNIX_TIMESTAMP(DATE_FORMAT(`date_create`,'%Y-%m-%d 12:00:00')) as date_resa
FROM `reservations`
WHERE
  `date_create` BETWEEN DATE_SUB(NOW(), INTERVAL 240 DAY) AND NOW() 
GROUP BY date_resa
ORDER BY date_resa ASC
			";
			$firstdate = NULL;
			$result = $this->db->get_results( $query );
			$data = array();
			foreach ($result as $value)
			{
				$data[] = (int)$value->nbr_resa;
				if( is_null($firstdate) )
					$firstdate = (int)$value->date_resa;
			}
			$return['data'] = $data;
			$return['firstdate'] = $firstdate;
			$query = "
SELECT 
  COUNT( * ) AS nbr_resa, 
  UNIX_TIMESTAMP( DATE_FORMAT(  `date_create` ,  '%Y-%m-%d 12:00:00' ) ) AS date_resa
FROM  `reservations` 
WHERE  `date_create` 
BETWEEN DATE_SUB( DATE_SUB( NOW( ) , INTERVAL 240 DAY ) , INTERVAL 1 YEAR ) 
AND DATE_SUB( NOW( ) , INTERVAL 1 YEAR ) 
GROUP BY date_resa
ORDER BY date_resa ASC
			";
			$firstdate = NULL;
			$result = $this->db->get_results( $query );
			$data = array();
			foreach ($result as $value)
			{
				$data[] = (int)$value->nbr_resa;
				if( is_null($firstdate) )
					$firstdate = (int)$value->date_resa;
			}
			$return['dataly'] = $data;
			$return['firstdately'] = $firstdate;
			print @json_encode( $return );

		}
		catch ( Exception $ex)
		{
			$return['Result'] = array();
			$return['Message'] = $ex->getMessage;
			print @json_encode( $return );
		}
	}
}