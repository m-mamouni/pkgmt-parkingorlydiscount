<?php
defined('_PKMGMT') or die('Restricted accesss');
require_once PKMGMT_PLUGIN_MODULES_DIR.DS."reservation.php";

Class pkmgmt_calendar extends reservation
{
	
	
	public function __construct($post_id)
	{
		parent::__construct($post_id);
	}
	
	public function getCalendar()
	{
		$this->connect();
		$db = &$this->db;
		$date = new DateTime();
		$resa = $this->database['table_reservation'];
		$where = "WHERE `navette` >= '" . $date->format("Y-m") . "' AND `status` < 3";
		$query = "SELECT LEFT(navette, 10) AS 'date', count(*) AS 'in' FROM `$resa` $where GROUP BY date ORDER BY navette";
		
		$result = $db->get_results($query);
		$resultIn = array();
		foreach ( $result as $value )
		{
			$key = $value->date;
			$nbr = $value->in;
			$resultIn[$key] = $nbr;
		}
		$where = "WHERE date_retour >= '" . $date->format("Y-m") . "' AND `status` < 3";
		$query = "SELECT LEFT(date_retour, 10) AS 'date', count(*) AS 'out' FROM `$resa` $where GROUP BY date ORDER BY date_retour";
		$result = $db->get_results($query);
		$resultOut = array();
		foreach ( $result as $value )
		{
			$key = $value->date;
			$nbr = $value->out;
			$resultOut[$key] = $nbr;
		}
		$query = "SELECT DISTINCT(LEFT(`navette`, 10)) AS 'date' FROM `$resa` WHERE `navette` >= '%s 00:00:00' AND `status` < 3";
		$query = sprintf( $query, $date->format("Y-m-d") );
		$dates = $db->get_results($query, ARRAY_A);
		$query = "SELECT DISTINCT(LEFT(`date_retour`, 10)) AS 'date' FROM `$resa` WHERE `date_retour` >= '%s 23:59:59' AND `status` < 3";
		$query = sprintf( $query, $date->format("Y-m-d") );
		$dates = array_merge($dates,$db->get_results($query, ARRAY_A));
		foreach( $dates as $value )
		{
			$arr[] = $value['date'];
		}
		sort( $arr );
		$arr = array_unique($arr);
		$query = " SELECT '%s' as 'date', COUNT(*) AS parc FROM `$resa` WHERE `date_retour` > '%s 23:59:59' AND `navette` < '%s 00:00:00' AND `status` < 3";
		$resultParc = array();
		$return = array();
		foreach ( $arr as $where )
		{
			$fmtsql = sprintf( $query, $where, $where, $where );
			$result = $db->get_results($fmtsql, ARRAY_A);
			$index = substr($where, 5, 2) . "-" . substr( $where, 8, 2 ) . "-" . substr( $where, 0, 4 );
			$return[$index] = 'in : '.@$resultIn[$where].'<br>out : '.@$resultOut[$where].'<br>parc : '.$result[0]['parc'];
		}
		echo json_encode($return);
	}
}
?>