<?php
defined('_PKMGMT') or die('Restricted access');

Class pkmgmt_site extends pkmgmt
{
	private static $found_items = 0;
	private static $current = null;
	private static $submission = array();
	private static $dbname;

	public $locale = false;
	public $initial = false;
	public $id;
	public $debug = false;
	public $name;
	public $title;
	public $unit_tag;
	public $responses_count = 0;
	public $scanned_form_tags;
	public $posted_data;
	public $response;
	public $uploaded_files = array();
	public $_data = array();
	public $db = null;
	public $string_attachments  = array();

	public static $conf_fields = array(
		'database', 'info', 'paypal', 'template',
		'name','divers', 'response'
		);

  	public $paypal = array(
        'actif' => 1,
        'commun' => 0
    );
	public $info = array(
		'adresse' => '',
		'telephone' => '',
		'RCS' => '',
		'email' => '',
		'horaires' => '',
		'time' => array(
			'start' => '00:00',
			'end' => '23:59'),
		'terminal' => 'Orly',
		'paiement' =>
			array('CB' => 1,
			'Espece' => 1,
			'Cheque' => 0),
		'type' => array('ext' => 1,
			'int' => 0),
		'gestion' => array('autovalid'=> 0,
			'mail' => 0,
			'base' => 0),
		'logo' => '',
		'tva1' => '10',
		'tva2' => '20',
		'cg' => 0,
		'frais' => array (
					'dimanche' => 1,
					'nuit' => 1,
					'ferie' => 1
					)
		);

	public $template = array();

	public $divers = array(
		'vacances' 	=> 'http://telechargement.index-education.com/vacances.xml',
		'expire'	=> 3600,
		'smsuser' => '', 'smspasswd' => '', 'smstype' => '',
		'smsaccount'=> '', 'smssender' => '', 'smsmessage' => ''
	);

	public $database = array(
		'dbname' => "", 'dbhost' => "",
		'dbuser' => "", 'dbpassword' => "",
		'dbport' => "", 'table_reservation' => "",
		'table_services' => "", 'table_tarifs_basse' => "",
		'table_tarif_resa' => "", 'table_tarifs_int' => "",
		'table_tarifs_ext' => "", 'table_tarifs_eco' => "",
		'start_date_price_save' => "", 'end_date_price_save' => "",
		'table_users' => ""
		);

	public function __construct($post = null)
	{
		add_action( 'phpmailer_init', array($this,'addStringAttachments') );
		$this->initial = true;
		$post = get_post( $post );
		if ( $post && self::post_type == get_post_type( $post ) ) {

			$this->initial = false;
			$this->id = $post->ID;
			$this->name = $post->post_name;
			$this->title = $post->post_title;
			$this->locale = get_post_meta( $post->ID, '_locale', true );

			$props = $this->get_properties();

			foreach ( $props as $prop => $value ) {
				if ( metadata_exists( 'post', $post->ID, '_' . $prop ) )
					$this->{$prop} = get_post_meta( $post->ID, '_' . $prop, true );
				else
					$this->{$prop} = $value;
			}
		}

	}

	public static function get_fields()
	{
		return self::$conf_fields;
	}

	public static function set_current( self $obj )
	{
		self::$current = $obj;
	}

	public static function get_current()
	{
		return self::$current;
	}

	public static function reset_current()
	{
		self::$current = null;
	}

	public static function count()
	{
		return self::$found_items;
	}

	public static function find( $args = '' )
	{
		$defaults = array(
			'post_status' => 'any',
			'posts_per_page' => -1,
			'offset' => 0,
			'orderby' => 'ID',
			'order' => 'ASC' );

		$args = wp_parse_args( $args, $defaults );
		$args['post_type'] = self::post_type;

		$q = new WP_Query();
		$posts = $q->query( $args );
		self::$found_items = $q->found_posts;
		$objs = array();
		foreach ( (array) $posts as $post )
			$objs[] = new self( $post );

		return $objs;
	}

	public function get_properties()
	{
		$properties = array();

		foreach ( self::$conf_fields as $prop_name )
			$properties[$prop_name] = isset( $this->{$prop_name} ) ? $this->{$prop_name} : '';

		return apply_filters( 'pkmgmt_properties', $properties, $this );
	}

	public function save()
	{
		$props = $this->get_properties();

		$post_content = implode( "\n", $this->pkmgmt_array_flatten( $props ) );
		if ( $this->initial ) {
			$post_id = wp_insert_post( array(
				'post_type' => self::post_type,
				'post_status' => 'publish',
				'post_title' => $this->title,
				'post_content' => trim( $post_content ) ) );
		} else {
			$post_id = wp_update_post( array(
				'ID' => (int) $this->id,
				'post_status' => 'publish',
				'post_title' => $this->title,
				'post_content' => trim( $post_content ) ) );
		}

		if ( $post_id ) {
			foreach ( $props as $prop => $value )
				update_post_meta( $post_id, '_' . $prop, $this->pkmgmt_normalize_newline_deep( $value ) );

			if ( ! empty( $this->locale ) )
				update_post_meta( $post_id, '_locale', $this->locale );

			if ( $this->initial ) {
				$this->initial = false;
				$this->id = $post_id;
				do_action_ref_array( 'pkmgmt_after_create', array( &$this ) );
			} else {
				do_action_ref_array( 'pkmgmt_after_update', array( &$this ) );
			}

			do_action_ref_array( 'pkmgmt_after_save', array( &$this ) );
		}

		return $post_id;
	}

	public function copy()
	{
		$new = new self;
		$new->initial = true;
		$new->title = $this->title . '_copy';
		$new->locale = $this->locale;

		$props = $this->get_properties();

		foreach ( $props as $prop => $value )
			$new->{$prop} = $value;

		$new = apply_filters_ref_array( 'pkmgmt_copy', array( &$new, &$this ) );

		return $new;
	}

	public function delete(): bool
	{
		if ( $this->initial )
			return false;

		if ( wp_delete_post( $this->id, true ) ) {
			$this->initial = true;
			$this->id = null;
			return true;
		}

		return false;
	}

	public function connect($force = false)
	{
		static $dbname;
		if (isset($dbname) && !$force)
			return;
		$dbname =  $this->database['dbname'];
		$this->db = new wpdb($this->database['dbuser'],
						 $this->database['dbpassword'],
						 $this->database['dbname'],
						 $this->database['dbhost'].':'.$this->database['dbport']);
	}

	public function disconnect()
	{
		if( $this->db )
			$this->db->close();
	}

	public	function createSeekWhere($table = null): string
	{
		if ( is_null($table) )
			return "";
		$post = array_merge($_POST,$_GET);
		$seek = "'%'";
			if ( ! empty( $post['seek'] ) )
			{
				if (preg_match("/([0-9]{2})\/([0-9]{2})\/([0-9]{4})(.*)/", $post['seek'], $matches))
					$seek = "$matches[3]-$matches[2]-$matches[1]$matches[4]";
				else
					$seek = $post['seek'];
				$seek = "'%" . $seek . "%'";
			}
		$query = "SHOW columns FROM `$table`";
		$fields = $this->db->get_results($query);
		$where = array();
		foreach ( $fields as $field)
		{
			$where[] = "`" . $field->Field . "`" . " LIKE $seek";
		}
		if ( !empty($where) )
			$where = implode(" OR ", $where);
		else
			$where = "";
		$ret = "AND ( $where )";
		if ( array_key_exists('type', $post) )
		{
			$field = "";
			if ( $post['type'] == 'TodayOut' )
				$field = 'date_retour';
			if ( $post['type'] == 'TodayIn' )
				$field = 'navette';
			if ( ! empty($field) )
			{
				$ret .= " AND SUBSTRING(`$field`,1,10) = CURDATE()";
			}
		}

		return $ret;
	}

	public function utf8_array(&$array)
	{
		if (!is_array($array))
			return;
		foreach ($array as $k => $v)
		{
			if (is_string($v))
			{
				$array[$k] = utf8_encode($v);
			}
		}
	}

	public function notification($to, $subject, $message, $headers = array(), $attachment = null)
	{
		add_filter( 'wp_mail_content_type', array($this, 'set_html_content_type') );
		if ( ! is_null($attachment) )
			$result = wp_mail($to, $subject, $message, $headers, $attachment);
		else
			$result = wp_mail($to, $subject, $message, $headers);
		remove_filter('wp_mail_content_type', array($this, 'set_html_content_type'));
		return $result;
	}
	public function addStringAttachment($attachment, $filename)
	{
		$this->string_attachments[] = compact('attachment', 'filename');
	}

	public function addStringAttachments($phpmailer)
	{
		foreach ( $this->string_attachments as $str_attachment )
		{
			try {
				$phpmailer->AddStringAttachment($str_attachment['attachment'], $str_attachment['filename']);
			} catch (phpmailerException $e) {
				continue;
			}
		}
	}

	public function set_html_content_type()
	{
		return 'text/html; charset=UTF-8';
	}

	public function sendSMS($to, $message)
	{
		$from = $this->divers['smssender'];
		$smscompte = $this->divers['smsaccount'];
		$login = $this->divers['smsuser'];
		$password = $this->divers['smspasswd'];
		try
		{
			$soap = new SoapClient("https://www.ovh.com/soapi/soapi-re-1.61.wsdl");
			$result = $soap->telephonySmsUserSend("$login", "$password", "$smscompte", "$from",
							"$to", "$message", "", "1", "", "2", "", "", true);
		}
		catch(SoapFault $fault)
		{
			throw new Exception($fault);
		}

	}

	public function replaceFromData($matches)
	{
		$match = $matches[0];
		$orig = $match;
		$match = substr($match, 1, strlen($match) - 2);
		if ( array_key_exists($match, $this->_data))
			return $this->_data[$match];
		return $orig;
	}

	public function orderByDate($ids): array
	{
		$query = "SELECT `id` FROM `{$this->database['table_reservation']}` WHERE `id` IN (%s) ORDER BY `navette` ASC";
		$query = sprintf($query, implode( ", ", $ids ));
		$this->connect();
		$res = $this->db->get_results($query, ARRAY_A);
		$ret = array();
		foreach ($res as $elem)
		{
			$ret[] = $elem['id'];
		}
		return($ret);
	}


}

