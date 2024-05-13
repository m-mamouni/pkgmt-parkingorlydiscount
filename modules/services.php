<?php
defined('_PKMGMT') or die('Restricted accesss');

Class services extends pkmgmt_site
{

	public function __construct($post = null)
	{
		parent::__construct($post);
	}
	
	public function listServices()
	{
		$return = array();
		try
		{
			$this->connect();
			$post = array_merge($_POST,$_GET);
			$where = $this->createSeekWhere($this->database['table_services']);
			$query = "SELECT COUNT(*) AS RecordCount FROM %s WHERE 1 %s";
			$query = sprintf($query, $this->database['table_services'], $where);
			$RecordCount = $this->db->get_var( $query );
			$return['TotalRecordCount'] = $RecordCount;
			$query = "SELECT * FROM %s WHERE 1 %s ORDER BY %s LIMIT %d, %d";
			$query = sprintf($query, "`".$this->database['table_services']."`", $where,
				$post['jtSorting'], $post['jtStartIndex'], $post['jtPageSize']);
			
			$result = $this->db->get_results($query);
			foreach ( $result as &$elem)
			{
				$this->utf8_array($elem);
			}
			$return['Result'] = "OK";
			$return['Records'] = $result;
			print @json_encode( $return );
		}
		catch (Exception $ex)
		{
			$return['Result'] = "ERROR";
			$return['Message'] = $ex->getMessage();
			print @json_encode( $return );		
		}
	}
	
	public function createService()
	{
		$return = array();
		try
		{
			$resa = $this->record();
			$return['Result'] = "OK";
			$return['Record'] = $resa;
			
			print @json_encode( $return );	
		}
		catch ( Exception $ex )
		{
			$return['Result'] = "ERROR";
			$return['Message'] = $ex->getMessage();
			print @json_encode($return);
		}
	}

	public function updateService()
	{
		$return = array();
		try
		{
			$resa = $this->record();
			$return['Result'] = "OK";
			print @json_encode( $return );	
		}
		catch ( Exception $ex )
		{
			$return['Result'] = "ERROR";
			$return['Message'] = $ex->getMessage();
			print @json_encode($return);
		}
	}


	public function record()
	{
		$return = array();
		$post = array_merge($_POST,$_GET);
		$post_id = $post['post_id'];
		unset($post['submit']);
		if ( array_key_exists( "action", $post ) )
			unset($post["action"]);
		if ( array_key_exists( "post_id", $post ) )
			unset($post["post_id"]);
		$resa = array();
		$data = $post;
		foreach ( $data as $key => $value )
		{
			$resa[$key] = trim($value);
		}
		$this->utf8_array($resa);
		$this->connect();
		if ( ! $this->db->replace( $this->database['table_services'], $resa ) )
			throw new Exception("Erreur d'enregistrement en base ");
		return ($resa);
	}
	
	
	function deleteServices()
	{
		$return = array();
		try
		{
			$post = array_merge($_POST,$_GET);
			$this->connect();
			if (  current_user_can('pkmgmt_admin') )
			{
				$this->db->query(
					$this->db->prepare(
						"DELETE FROM `". $this->database['table_services']."` WHERE `id` = %d",
						$post["id"]
						)
				 );
			}
			$return['Result'] = "OK";
			print @json_encode( $return );
		}
		catch (Exception $ex)
		{
			$return['Result'] = "ERROR";
			$return['Message'] = $ex->getMessage();
			print @json_encode( $return );
		}
	}

	
}