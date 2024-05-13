<?php
defined('_PKMGMT') or die('Restricted access');


Class pkmgmt_admin extends pkmgmt
{

	public $id;
	public $name;
	public $title;

	public function __construct()
	{
		$this->pkmgmt_menu();
		$this->ajax_resa_function();
		$this->ajax_calendar_function();
		$this->ajax_services_function();
		$this->ajax_clients_function();
		$this->ajax_tarifs_function();
		$this->ajax_caisse_function();
		$this->ajax_graph_function();
	}

	function pkmgmt_menu()
	{
        add_action( 'plugins_loaded', array( &$this, 'admin_load_textdomain' ) );
		add_action( 'init', array( &$this,'pkmgmt_init' ), 2);
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_action( 'admin_menu', array( &$this, 'pkmgmt_admin_menu' ));
		add_filter( 'set-screen-option', array( &$this, 'pkmgmt_set_screen_options' ), 10, 3 );

		add_filter( 'admin_head', array( &$this,'pkmgmt_show_tiny_mce' ), 10, 4 );
		add_action( 'admin_enqueue_scripts', array( &$this, 'pkmgmt_admin_enqueue_scripts' ) );
		add_action( 'pkmgmt_admin_notices', array( &$this, 'pkmgmt_admin_updated_message' ) );

		add_filter( 'map_meta_cap', array( &$this,'pkmgmt_map_meta_cap' ), 10, 4 );
		add_action( 'activate_' . PKMGMT_PLUGIN_BASENAME, array( &$this,'admin_install' ) );
	}

	function ajax_resa_function()
	{
		add_action( 'wp_ajax_listResaAction', array($this,'listResaAction_callback') );
		add_action( 'wp_ajax_deleteResaAction', array($this,'deleteResaAction_callback') );
		add_action( 'wp_ajax_createResaAction', array($this,'createResaAction_callback') );
		add_action( 'wp_ajax_updateResaAction', array($this,'updateResaAction_callback') );

		add_action( 'wp_ajax_validResaAction', array($this,'validResaAction_callback') );
		add_action( 'wp_ajax_invoiceResaAction', array($this,'invoiceResaAction_callback') );
		add_action( 'wp_ajax_exitsplitResaAction', array($this,'exitsplitResaAction_callback') );
		add_action( 'wp_ajax_deliveryResaAction', array($this,'deliveryResaAction_callback') );
		add_action( 'wp_ajax_serviceResaTarif', array($this,'serviceResaTarif_callback') );
		add_action( 'wp_ajax_updateResaDB', array($this,'updateResaDBAction_callback') );
		add_action( 'wp_ajax_getResaTarif', array($this,'getResaTarif_callback') );

		add_action( 'wp_ajax_nopriv_getTarifByDate', array($this, 'getTarifByDate_callback'));
		add_action( 'wp_ajax_getTarifByDate', array($this, 'getTarifByDate_callback'));

		add_action( 'wp_ajax_statusResaAction', array($this, 'statusResaAction_callback') );

		add_action( 'wp_ajax_printResaAction', array($this,'printResaAction_callback') );

	}

	function ajax_calendar_function()
	{
		add_action('wp_ajax_getCalendarAction', array($this, 'getCalendarAction_callback'));

	}

	function ajax_services_function()
	{
		add_action( 'wp_ajax_listServicesAction', array($this,'listServicesAction_callback') );
		add_action( 'wp_ajax_deleteServicesAction', array($this,'deleteServicesAction_callback') );
		add_action( 'wp_ajax_createServiceAction', array($this,'createServiceAction_callback') );
		add_action( 'wp_ajax_updateServicesAction', array($this,'updateServicesAction_callback') );
	}

	function ajax_clients_function()
	{
		add_action( 'wp_ajax_listClientsAction', array($this,'listClientsAction_callback') );
		add_action( 'wp_ajax_deleteClientAction', array($this,'deleteClientAction_callback') );
		add_action( 'wp_ajax_createClientAction', array($this,'createClientAction_callback') );
		add_action( 'wp_ajax_updateClientAction', array($this,'updateClientAction_callback') );
	}

	function ajax_tarifs_function()
	{
		add_action( 'wp_ajax_listTarifsAction', array($this,'listTarifsAction_callback') );
		add_action( 'wp_ajax_deleteTarifsAction', array($this,'deleteTarifsAction_callback') );
		add_action( 'wp_ajax_createTarifAction', array($this,'createTarifAction_callback') );
		add_action( 'wp_ajax_updateTarifsAction', array($this,'updateTarifsAction_callback') );
	}

	function ajax_caisse_function()
	{
		add_action( 'wp_ajax_caisseAction', array($this,'caisseAction_callback') );
		add_action( 'wp_ajax_datecaisseAction', array($this,'datecaisseAction_callback') );
	}

	function ajax_graph_function()
	{
		add_action( 'wp_ajax_graph_resa_dayAction', array($this, 'graph_resa_dayAction_callback'));
	}

	function pkmgmt_show_tiny_mce()
	{
	// conditions here
		wp_enqueue_script( 'common' );
		wp_enqueue_script( 'jquery-color' );
		wp_print_scripts('editor');
		if (function_exists('add_thickbox')) add_thickbox();
		wp_print_scripts('media-upload');
		//if (function_exists('wp_editor')) wp_editor();
		wp_admin_css();
		wp_enqueue_script('utils');
		do_action("admin_print_styles-post-php");
		do_action('admin_print_styles');
	}

	public function admin_load_textdomain($locale = null)
	{
		global $l10n;

		$domain = "parking-management";
		if ( is_textdomain_loaded( $domain ) )
				return true;
		else
		{
			$mofile = $domain . "-" . get_locale() . ".mo";
			if ( file_exists(PKMGMT_LANGUAGES_DIR . DS . $mofile ) )
				return load_textdomain( $domain, PKMGMT_LANGUAGES_DIR . DS . $mofile );
			else
				return load_textdomain( $domain, PKMGMT_LANGUAGES_DIR . DS . $domain . "-fr_FR.mo" );
		}
		return false;
	}

	public function pkmgmt_admin_menu()
	{
		global $_wp_last_object_menu;

		$_wp_last_object_menu++;

		$obj = add_menu_page(
			__("Parking Management", 'parking-management'),
			__("Car park", 'parking-management'),
			"pkmgmt_manager_cap",
            'pkmgmt',
			array( &$this, 'pkmgmt_render_admin' ),
            $this->pkmgmt_plugin_url('images/p.png'),
			$_wp_last_object_menu
        );

		$edit = add_submenu_page( 'pkmgmt',
					__( 'Edit Site', 'parking-management' ),
					__( 'Edit Site', 'parking-management' ),
					"pkmgmt_manager_cap", "pkmgmt",
					array( &$this, 'pkmgmt_render_admin' ) );

		add_action( "load-" . $edit, array( &$this, "pkmgmt_load_admin") );

		$addnew = add_submenu_page( 'pkmgmt',
					__( 'Add new site', 'parking-management' ),
					__( 'Add new site', 'parking-management' ),
					"pkmgmt_manager_cap", "pkmgmt-new",
					array( &$this, "pkmgmt_admin_add_new_page") );
		add_action( 'load-' . $addnew, array( &$this, "pkmgmt_load_admin") );

		$this->submenu_by_site();
	}

	private function submenu_by_site()
	{
		$items = pkmgmt_site::find();
		$total_items = pkmgmt_site::count();

		foreach( $items as $item )
		{
			add_submenu_page( 'pkmgmt',
					$item->name,
					$item->name,
					"pkmgmt_read_cap", "pkmgmt-" . $item->id,
					array( &$this, "pkmgmt_admin_site_page") );
		}
	}

	function pkmgmt_load_admin()
	{
		global $l10n;
		$action = $this->pkmgmt_current_action();
		switch ($action)
		{
			case "save":
				$this->pkmgmt_load_save();
				break;
			case "copy":
				$this->pkmgmt_load_copy();
				break;
			case "delete":
				$this->pkmgmt_load_delete();
				break;
			default:
				$this->pkmgmt_load_default();
				break;
		}
	}

	function pkmgmt_load_save()
	{
		//print_log($_POST,false);
		$id = $_POST['post_ID'];

		if ( ! current_user_can( 'pkmgmt_manager_cap' ) )
			wp_die( __( 'You are not allowed to edit this item.', 'parking-management' ) );

		if ( ! $site = $this->pkmgmt_site($id) )
		{
			$site = new pkmgmt_site();
			$site->initial = true;
		}
		$site->title = trim( $_POST['pkmgmt-title'] );
		$site->name = trim( $_POST['pkmgmt-name'] );
		$site->locale = trim( $_POST['pkmgmt-locale'] );

		if ( $site->initial )
			$database = array();
		else
			$database = $site->database;
		if (array_key_exists('pkmgmt-database-dbname', $_POST))
		$database = array(
			'dbname' => trim( $_POST['pkmgmt-database-dbname'] ),
			'dbhost' => trim( $_POST['pkmgmt-database-dbhost'] ),
			'dbuser' => trim( $_POST['pkmgmt-database-dbuser'] ),
			'dbpassword' => trim( $_POST['pkmgmt-database-dbpassword'] ),
			'dbport' => trim( $_POST['pkmgmt-database-dbport'] ),
			'table_reservation' => trim( $_POST['pkmgmt-database-table_reservation'] ),
			'table_services' => trim( $_POST['pkmgmt-database-table_services'] ),
			'table_tarifs_basse' => trim( $_POST['pkmgmt-database-table_tarifs_basse'] ),
			'table_tarif_resa' => trim( $_POST['pkmgmt-database-table_tarif_resa'] ),
			'table_tarifs_int' => trim( $_POST['pkmgmt-database-table_tarifs_int'] ),
			'table_tarifs_ext' => trim( $_POST['pkmgmt-database-table_tarifs_ext'] ),
			'table_tarifs_eco' => trim( $_POST['pkmgmt-database-table_tarifs_eco'] ),
            'start_date_price_save' => trim( $_POST['pkmgmt-database-start_date_price_save'] ),
            'end_date_price_save' => trim( $_POST['pkmgmt-database-end_date_price_save'] ),
			'table_users' => trim( $_POST['pkmgmt-database-table_users'] )
		);

		$terminal = (!empty($_POST['pkmgmt-info-terminal'])) ? $_POST['pkmgmt-info-terminal']:$site->info['terminal'];

		$indicatif = (!empty($_POST['pkmgmt-info-indicatif'])) ? $_POST['pkmgmt-info-indicatif']:$site->info['indicatif'];

		$dimanche = (array_key_exists( 'dimanche', $_POST['pkmgmt-info-frais'] ) && !empty($_POST['pkmgmt-info-frais']['dimanche']) ) ? $_POST['pkmgmt-info-frais']['dimanche'] : 0;
		$nuit = (array_key_exists( 'nuit', $_POST['pkmgmt-info-frais'] ) && !empty($_POST['pkmgmt-info-frais']['nuit']) ) ? $_POST['pkmgmt-info-frais']['nuit'] : 0;

		$cg = (!empty($_POST['pkmgmt-info-cg']) ) ? $_POST['pkmgmt-info-cg'] : 0;

		$ferie = (array_key_exists( 'ferie', $_POST['pkmgmt-info-frais'] ) && !empty($_POST['pkmgmt-info-frais']['ferie']) ) ? $_POST['pkmgmt-info-frais']['ferie'] : 0;

		$cb = (array_key_exists( 'CB', $_POST['pkmgmt-info-paiement'] ) && !empty($_POST['pkmgmt-info-paiement']['CB']) ) ? $_POST['pkmgmt-info-paiement']['CB'] : 0;
		$espece = (array_key_exists( 'Espece', $_POST['pkmgmt-info-paiement'] ) && !empty($_POST['pkmgmt-info-paiement']['Espece']) ) ? $_POST['pkmgmt-info-paiement']['Espece'] : 0;
		$cheque = (array_key_exists( 'Cheque', $_POST['pkmgmt-info-paiement'] ) && !empty($_POST['pkmgmt-info-paiement']['Cheque']) ) ? $_POST['pkmgmt-info-paiement']['Cheque'] : 0;

		$ext = (array_key_exists( 'ext', $_POST['pkmgmt-info-type'] ) && !empty($_POST['pkmgmt-info-type']['ext']) ) ? $_POST['pkmgmt-info-type']['ext'] : 0;
		$int = (array_key_exists( 'int', $_POST['pkmgmt-info-type'] ) && !empty($_POST['pkmgmt-info-type']['int']) ) ? $_POST['pkmgmt-info-type']['int'] : 0;

		$autovalid = (array_key_exists( 'autovalid', $_POST['pkmgmt-info-gestion'] ) && !empty($_POST['pkmgmt-info-gestion']['autovalid']) ) ? $_POST['pkmgmt-info-gestion']['autovalid'] : 0;
		$e_mail = (array_key_exists( 'mail', $_POST['pkmgmt-info-gestion'] ) && !empty($_POST['pkmgmt-info-gestion']['mail']) ) ? $_POST['pkmgmt-info-gestion']['mail'] : 0;
		$base = (array_key_exists( 'base', $_POST['pkmgmt-info-gestion'] ) && !empty($_POST['pkmgmt-info-gestion']['base']) ) ? $_POST['pkmgmt-info-gestion']['base'] : 0;

		$paypal_actif = (!empty($_POST['pkmgmt-paypal-actif']) ) ? $_POST['pkmgmt-paypal-actif'] : 0;
		$paypal_commun = (!empty($_POST['pkmgmt-paypal-commun']) ) ? $_POST['pkmgmt-paypal-commun'] : 0;

		if ( $site->initial )
			$info = array();
		else
			$info = $site->info;
		if (array_key_exists('pkmgmt-info-adresse', $_POST))
		$info = array(
			'adresse' => trim( $_POST['pkmgmt-info-adresse'] ),
			'telephone' => trim( $_POST['pkmgmt-info-telephone'] ),
			'RCS' => trim( $_POST['pkmgmt-info-RCS']),
			'email' => trim( $_POST['pkmgmt-info-email'] ),
			'time' => array(
				'start' => trim( $_POST['pkmgmt-info-time-start'] ),
				'end'   => trim( $_POST['pkmgmt-info-time-end'] )),
			'deliverymessage' => trim( $_POST['pkmgmt-info-deliverymessage'] ),
			'logo' => trim( $_POST['pkmgmt-info-logo'] ),
			'tva1' => trim( $_POST['pkmgmt-info-tva1'] ),
			'tva2' => trim( $_POST['pkmgmt-info-tva2'] ),
			'terminal' => $terminal,
			'indicatif' => $indicatif,
			'cg' => $cg,
			'frais' => array(
					'dimanche' => $dimanche,
					'nuit' => $nuit,
					'ferie' => $ferie
						),
			'paiement' => array(
					'CB' => $cb,
					'Espece' => $espece,
					'Cheque' => $cheque
						),
			'type' => array(
					'ext' => $ext,
					'int' => $int
						),
			'gestion' => array(
					'autovalid' => $autovalid,
					'mail' => $e_mail,
					'base' => $base
				)
		);

		if ( $site->initial )
				$paypal = array();
		else
				$paypal = $site->paypal;
		if (array_key_exists('pkmgmt-paypal-actif', $_POST))
			$paypal['actif'] = $paypal_actif;
		else
			$paypal['actif'] = 0;
		if (array_key_exists('pkmgmt-paypal-commun', $_POST))
			$paypal['commun'] = $paypal_commun;
		else
			$paypal['commun'] = 0;

		if ( $site->initial )
			$divers = array();
		else
			$divers = $site->divers;
		if (array_key_exists('pkmgmt-divers-vacances', $_POST))
		$divers = array(
			'vacances' 	=> trim( $_POST['pkmgmt-divers-vacances'] ),
			'expire'	=> trim( $_POST['pkmgmt-divers-expire'] ),
			'smsuser' => trim( $_POST['pkmgmt-divers-smsuser'] ),
			'smstype' => trim( $_POST['pkmgmt-divers-smstype'] ),
			'smspasswd' => trim( $_POST['pkmgmt-divers-smspasswd'] ),
			'smsaccount'=> trim( $_POST['pkmgmt-divers-smsaccount'] ),
			'smssender' => trim( $_POST['pkmgmt-divers-smssender'] ),
			'smsmessage' => trim( $_POST['pkmgmt-divers-smsmessage'] )
		);

		if ( $site->initial )
			$response = array();
		else
			$response = $site->response;
		if (array_key_exists('pkmgmt-response', $_POST))
		$response = trim( $_POST['pkmgmt-response']);

		$props = apply_filters( 'pkmgmt_admin_posted_properties',
			compact( 'database', 'info', 'paypal', 'divers', 'response' ) );

		foreach ( (array) $props as $key => $prop )
		{
			if (!empty($prop))
				$site->{$key} = $prop;

		}
		//print_log($site, false);
		$query = array();
		$query['message'] = ( $site->initial ) ? 'created' : 'saved';
		$site->save();
		pkmgmt_site::set_current($site);
		$query['post'] = $site->id;
		$redirect_to = add_query_arg( $query, menu_page_url( 'pkmgmt', false ) );
		wp_safe_redirect( $redirect_to );
	}

	function pkmgmt_load_copy()
	{

		$id = empty( $_POST['post_ID'] )
			? absint( $_REQUEST['post'] )
			: absint( $_POST['post_ID'] );

		check_admin_referer( 'pkmgmt-copy-site_' . $id );

		if ( ! current_user_can( 'pkmgmt_edit', $id ) )
			wp_die( __( 'You are not allowed to edit this item.', 'parking-management' ) );
		$query = array();

		if ( $site = $this->pkmgmt_site( $id ) )
		{
			$new_site = $site->copy();
			$new_site->save();

			$query['post'] = $new_site->id;
			$query['message'] = 'created';
		}

		$redirect_to = add_query_arg( $query, menu_page_url( 'pkmgmt', false ) );

		wp_safe_redirect( $redirect_to );
	}

	function pkmgmt_load_delete()
	{

		if ( ! empty( $_POST['post_ID'] ) )
			check_admin_referer( 'pkmgmt-delete-site_' . $_POST['post_ID'] );
		elseif ( ! is_array( $_REQUEST['post'] ) )
			check_admin_referer( 'pkmgmt-delete-site_' . $_REQUEST['post'] );
		else
			check_admin_referer( 'bulk-posts' );

		$posts = empty( $_POST['post_ID'] )
			? (array) $_REQUEST['post']
			: (array) $_POST['post_ID'];

		$deleted = 0;

		foreach ( $posts as $post ) {
			$post = new pkmgmt_site( $post );

			if ( empty( $post ) )
				continue;

			if ( ! current_user_can( 'pkmgmt_delete', $post->id ) )
				wp_die( __( 'You are not allowed to delete this item.', 'parking-management' ) );

			if ( ! $post->delete() )
				wp_die( __( 'Error in deleting.', 'parking-management' ) );

			$deleted += 1;
		}

		$query = array();

		if ( ! empty( $deleted ) )
			$query['message'] = 'deleted';

		$redirect_to = add_query_arg( $query, menu_page_url( 'pkmgmt', false ) );

		wp_safe_redirect( $redirect_to );
	}

	function pkmgmt_site( $id )
	{
		$site = new pkmgmt_site( $id );

		if ( $site->initial )
			return false;

		return $site;
	}

	function pkmgmt_load_default()
	{
		global $plugin_page;

		$_GET['post'] = isset( $_GET['post'] ) ? $_GET['post'] : '';

		$post = null;

		if ( 'pkmgmt-new' == $plugin_page )
			$post = $this->generate_default_package( array(	'locale' => 'fr_FR' ) );
		elseif ( ! empty( $_GET['post'] ) )
			$post = $this->pkmgmt_site( $_GET['post'] );

		if ( $post && current_user_can( 'pkmgmt_read_cap' ) )
			$this->pkmgmt_add_meta_boxes( $post->id );
		else
		{
			$current_screen = get_current_screen();

			add_filter( 'manage_' . $current_screen->id . '_columns',
				array('PKMGMT_Parking_List_Table', 'define_columns') );

			add_screen_option( 'per_page', array(
				'label' => __( 'Parking Management', 'parking-management' ),
				'default' => 20,
				'option' => 'pkmgmt_parking_per_page' ) );
		}
		if ( $post )
			pkmgmt_site::set_current( $post );
	}

	public function pkmgmt_set_screen_options( $result, $option, $value )
	{
		$pkmgmt_screens = array(
			'pkmgmt_parking_per_page' );

		if ( in_array( $option, $pkmgmt_screens ) )
			$result = $value;

		return $result;
	}

	public function pkmgmt_admin_add_new_page()
	{
		if ( $post = pkmgmt_site::get_current() )
		{
			$this->title = $post->title;
			$this->pkmgmt_admin_edit($post->id);
		}
		else
			$this->pkmgmt_admin_add(-1);

	}

	public function pkmgmt_admin_edit($post_id)
	{
		global $plugin_page;
		?>
	<div class="wrap">
		<h2><?php
		if ( "pkmgmt-new" == $plugin_page ) :
			echo esc_html( __( 'Add a site', "parking-management" ) );
		else :
			echo esc_html( __( 'Edit a site', "parking-management" ) );
		endif;
		?></h2>

		<?php
			do_action( 'pkmgmt_admin_notices' );
			$this->form($post_id);
		?>
	</div>
	<?php
	}

	public function pkmgmt_admin_add($post_id)
	{
			?>
	<div class="wrap">
		<h2><?php
		echo esc_html( __( 'Add a site', "parking-management" ) );
		?></h2>

		<?php
			do_action( 'pkmgmt_admin_notices' );
			$this->form($post_id);
		?>
	</div>
	<?php
	}

	public function form($post_id)
	{
		global $plugin_page;
		if ( $post_id == -1 )
			$post = new pkmgmt_site();
		else
			$post = new pkmgmt_site($post_id);
	?>
		<br class="clear" />
		<?php
		if ( current_user_can( 'pkmgmt_manager_cap', $post_id ) )
			$disabled = '';
		else
			$disabled = ' disabled="disabled"';
		?>
<form action="" method="post">
	<?php if ( current_user_can( 'pkmgmt_admin', $post_id ) )
			wp_nonce_field( 'pkmgmt-save_' . $post_id ); ?>
<input type="hidden" name="page" value="<?php echo $plugin_page; ?>" />
<input type="hidden" id="hiddenaction" name="action" value="save" />
<input type="hidden" id="pkmgmt-locale" name="pkmgmt-locale" value="fr_FR">
<input type="hidden" id="post_ID" name="post_ID" value="<?php echo (int) $post_id; ?>" />

<div id="poststuff" class="metabox-holder">
	<div id="titlediv">
		<input type="text" class="wide" id="pkmgmt-title" placeholder="<?php echo esc_html(__("Title", 'parking-management'))?>"
			name="pkmgmt-title" size="80" value="<?php echo esc_attr( $post->title ); ?>"<?php echo $disabled; ?> />

		<p class="tagcode">
			<?php echo esc_html( __( "Name", 'parking-management' ) ); ?><br />

			<input type="text" class="wide" id="pkmgmt-name" name="pkmgmt-name" size="80" value="<?php echo esc_attr( $post->name ); ?>"<?php echo $disabled; ?> />
		</p>
		<?php if ( ! $post->initial ) : ?>
		<p class="tagcode">
			<?php echo esc_html( __( "Copy and paste this code into your home page to include pre booking form.", 'parking-management' ) ); ?><br />

			<input type="text" id="pkmgmt-home-form-anchor-text" value='[parking-management-home-form id="<?php echo $post_id; ?>" title="<?php echo esc_attr( $post->name ); ?>"]' onfocus="this.select();" readonly class="wide wp-ui-text-highlight code" />
		</p>
		<p class="tagcode">
			<?php echo esc_html( __( "Copy this code and paste it into your post, page or text widget content.", 'parking-management' ) ); ?><br />

			<input type="text" id="pkmgmt-anchor-text" onfocus="this.select();" readonly class="wide wp-ui-text-highlight code" />
		</p>
		<p class="tagcode">
			<?php echo esc_html( __( "Copy this code and paste it into your post, page or text widget content to add payplug code.", 'parking-management' ) ); ?><br />

			<input type="text" id="pkmgmt-payplug-anchor-text" value='[parking-management-payplug  id="<?php echo $post_id; ?>" title="<?php echo esc_attr( $post->name ); ?>"]' onfocus="this.select();" readonly class="wide wp-ui-text-highlight code" />
		</p>
		<p class="tagcode">
			<?php echo esc_html( __( "Copy this code and paste it into your post, page or text widget content to add paypal code.", 'parking-management' ) ); ?><br />

			<input type="text" id="pkmgmt-paypal-anchor-text" value='[parking-management-paypal  id="<?php echo $post_id; ?>" title="<?php echo esc_attr( $post->name ); ?>"]' onfocus="this.select();" readonly class="wide wp-ui-text-highlight code" />
		</p>
		<?php endif; ?>
	<?php if ( current_user_can( 'pkmgmt_admin_cap', $post_id ) ) : ?>
		<div class="save-pkmgmt">
			<input type="submit" class="button-primary" name="pkmgmt-save" value="<?php echo esc_attr( __( 'Save', 'parking-management' ) ); ?>" />
		</div>
	<?php endif; ?>

	<?php if ( current_user_can( 'pkmgmt_super_admin_cap', $post_id ) && ! $post->initial ) : ?>
		<div class="actions-link">
			<?php $copy_nonce = wp_create_nonce( 'pkmgmt-copy_' . $post_id ); ?>
			<input type="submit" name="pkmgmt-copy" class="copy" value="<?php echo esc_attr( __( 'Duplicate', 'parking-management' ) ); ?>"
			<?php echo "onclick=\"this.form._wpnonce.value = '$copy_nonce'; this.form.action.value = 'copy'; return true;\""; ?> />
			|

			<?php $delete_nonce = wp_create_nonce( 'pkmgmt-delete_' . $post_id ); ?>
			<input type="submit" name="pkmgmt-delete" class="delete" value="<?php echo esc_attr( __( 'Delete', 'parking-management' ) ); ?>"
			<?php echo "onclick=\"if (confirm('" .
				esc_js( __( "You are about to delete this site.\n  'Cancel' to stop, 'OK' to delete.", 'parking-management' ) ) .
				"')) {this.form._wpnonce.value = '$delete_nonce'; this.form.action.value = 'delete'; return true;} return false;\""; ?> />
		</div>
	<?php endif; ?>

	</div>
	<?php

		do_action_ref_array( 'pkmgmt_admin_after_general_settings', array( &$post ) );

		do_meta_boxes( null, 'info', $post );



		if ( current_user_can( 'pkmgmt_super_admin_cap' ) )
        {
			do_meta_boxes( null, 'paypal', $post );
			do_meta_boxes( null, 'database', $post );
		}


		if ( current_user_can( 'pkmgmt_manager_cap' ) )
			do_meta_boxes( null, 'divers', $post );



		if ( current_user_can( 'pkmgmt_admin_cap' ) )
			do_meta_boxes( null, 'response', $post );

		wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
		wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );

	?>
</div>
</form>
		<?php
	}

	function pkmgmt_database_meta_box( $post, $box )
	{
		$defaults = array( 'id' => 'pkmgmt-database', 'name' => 'database', 'use' => null );

		if ( ! isset( $box['args'] ) || ! is_array( $box['args'] ) )
			$args = array();
		else
			$args = $box['args'];
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

		$id = esc_attr( $id );
		$database = $post->{$name};
		if ( ! empty( $use ) ) :
	?>
	<?php endif; ?>

	<div class="database-fields">
		<div class="half-left">
			<div class="database-field">
			<label for="<?php echo $id; ?>-dbhost"><?php echo esc_html( __( 'Database Host', 'parking-management' ) ); ?></label><br />
			<input type="text" id="<?php echo $id; ?>-dbhost" name="<?php echo $id; ?>-dbhost" class="wide" size="70" value="<?php echo esc_attr( $database['dbhost'] ); ?>" />
			</div>

			<div class="database-field">
			<label for="<?php echo $id; ?>-dbuser"><?php echo esc_html( __( 'Database User', 'parking-management' ) ); ?></label><br />
			<input type="text" id="<?php echo $id; ?>-dbuser" name="<?php echo $id; ?>-dbuser" class="wide" size="70" value="<?php echo esc_attr( $database['dbuser'] ); ?>" />
			</div>

			<div class="database-field">
			<label for="<?php echo $id; ?>-dbpassword"><?php echo esc_html( __( 'Database Password', 'parking-management' ) ); ?></label><br />
			<input type="text" id="<?php echo $id; ?>-dbpassword" name="<?php echo $id; ?>-dbpassword" class="wide" size="70" value="<?php echo esc_attr( $database['dbpassword'] ); ?>" />
			</div>

			<div class="database-field">
			<label for="<?php echo $id; ?>-dbport"><?php echo esc_html( __( 'Database Port', 'parking-management' ) ); ?></label><br />
			<input type="text" id="<?php echo $id; ?>-dbport" name="<?php echo $id; ?>-dbport" class="wide" size="70" value="<?php echo esc_attr( $database['dbport'] ); ?>" />
			</div>

			<div class="database-field">
			<label for="<?php echo $id; ?>-dbname"><?php echo esc_html( __( 'Database Name', 'parking-management' ) ); ?></label><br />
			<input type="text" id="<?php echo $id; ?>-dbname" name="<?php echo $id; ?>-dbname" class="wide" size="70" value="<?php echo esc_attr( $database['dbname'] ); ?>" />
			</div>

			<div class="database-field">
			<label for="<?php echo $id; ?>-table_reservation"><?php echo esc_html( __( 'Bookings table', 'parking-management' ) ); ?></label><br />
			<input type="text" id="<?php echo $id; ?>-table_reservation" name="<?php echo $id; ?>-table_reservation" class="wide" size="70" value="<?php echo esc_attr( $database['table_reservation'] ); ?>" />
			</div>

			<div class="database-field">
			<label for="<?php echo $id; ?>-table_users"><?php echo esc_html( __( 'Users table', 'parking-management' ) ); ?></label><br />
			<input type="text" id="<?php echo $id; ?>-table_users" name="<?php echo $id; ?>-table_users" class="wide" size="70" value="<?php echo esc_attr( $database['table_users'] ); ?>" />
			</div>

		</div>

		<div class="half-right">
			<div class="database-field">
			<label for="<?php echo $id; ?>-table_services"><?php echo esc_html( __( 'Services table', 'parking-management' ) ); ?></label><br />
			<input type="text" id="<?php echo $id; ?>-table_services" name="<?php echo $id; ?>-table_services" class="wide" size="70" value="<?php echo esc_attr( $database['table_services'] ); ?>" />
			</div>

			<div class="database-field">
			<label for="<?php echo $id; ?>-table_tarifs_ext"><?php echo esc_html( __( 'Area price table', 'parking-management' ) ); ?></label><br />
			<input type="text" id="<?php echo $id; ?>-table_tarifs_ext" name="<?php echo $id; ?>-table_tarifs_ext" class="wide" size="70" value="<?php echo esc_attr( $database['table_tarifs_ext'] ); ?>" />
			</div>

			<div class="database-field">
			<label for="<?php echo $id; ?>-table_tarifs_int"><?php echo esc_html( __( 'Cover price table', 'parking-management' ) ); ?></label><br />
			<input type="text" id="<?php echo $id; ?>-table_tarifs_int" name="<?php echo $id; ?>-table_tarifs_int" class="wide" size="70" value="<?php echo esc_attr( $database['table_tarifs_int'] ); ?>" />
			</div>
            		<div class="database-field">
            		<label for="<?php echo $id; ?>-start_date_price_save"><?php echo esc_html( __( 'Begin save Date', 'parking-management' ) ); ?></label><br />
            		<input type="date" id="<?php echo $id; ?>-start_date_price_save" name="<?php echo $id; ?>-start_date_price_save" class="wide" size="70" value="<?php echo esc_attr( $database['start_date_price_save'] ); ?>" />
            		</div>

            		<div class="database-field">
            		<label for="<?php echo $id; ?>-end_date_price_save"><?php echo esc_html( __( 'End save Date', 'parking-management' ) ); ?></label><br />
            		<input type="date" id="<?php echo $id; ?>-end_date_price_save" name="<?php echo $id; ?>-end_date_price_save" class="wide" size="70" value="<?php echo esc_attr( $database['end_date_price_save'] ); ?>" />
            		</div>



			<div class="database-field">
			<label for="<?php echo $id; ?>-table_tarifs_eco"><?php echo esc_html( __( 'Save price table', 'parking-management' ) ); ?></label><br />
			<input type="text" id="<?php echo $id; ?>-table_tarifs_eco" name="<?php echo $id; ?>-table_tarifs_eco" class="wide" size="70" value="<?php echo esc_attr( $database['table_tarifs_eco'] ); ?>" />
			</div>

			<div class="database-field">
			<label for="<?php echo $id; ?>-table_tarifs_basse"><?php echo esc_html( __( 'Low price table', 'parking-management' ) ); ?></label><br />
			<input type="text" id="<?php echo $id; ?>-table_tarifs_basse" name="<?php echo $id; ?>-table_tarifs_basse" class="wide" size="70" value="<?php echo esc_attr( $database['table_tarifs_basse'] ); ?>" />
			</div>

			<div class="database-field">
			<label for="<?php echo $id; ?>-table_tarif_resa"><?php echo esc_html( __( 'Booking price table', 'parking-management' ) ); ?></label><br />
			<input type="text" id="<?php echo $id; ?>-table_tarif_resa" name="<?php echo $id; ?>-table_tarif_resa" class="wide" size="70" value="<?php echo esc_attr( $database['table_tarif_resa'] ); ?>" />
			</div>

		</div>

	<br class="clear" />
	</div>
	<?php
	}

	function pkmgmt_info_meta_box( $post, $box )
	{
		$defaults = array( 'id' => 'pkmgmt-info', 'name' => 'info', 'use' => null );

		if ( ! isset( $box['args'] ) || ! is_array( $box['args'] ) )
			$args = array();
		else
			$args = $box['args'];
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

		$id = esc_attr( $id );
		$info = $post->{$name};

		if (!array_key_exists('indicatif', $info) || !isset($info['indicatif']))
			$info['indicatif'] = 'fr';

		if (!array_key_exists('time', $info))
			$info['time'] = array();
		if (!array_key_exists('start', $info['time']))
			$info['time']['start'] = '00:00';
		if (!array_key_exists('end', $info['time']))
			$info['time']['end'] = '23:59';
		if (!array_key_exists('time_end', $info) || !isset($info['time_end']))
			$info['time_end'] = '23:59';

		if (!array_key_exists('cg', $info))
			$info['cg'] = 0;

		if (!array_key_exists('dimanche',$info['frais']))
			$info['frais']['dimanche'] = 0;
		if (!array_key_exists('nuit',$info['frais']))
			$info['frais']['nuit'] = 0;
		if (!array_key_exists('ferie',$info['frais']))
			$info['frais']['ferie'] = 0;

		if (!array_key_exists('ext', $info['type']))
			$info['type']['ext'] = 0;
		if (!array_key_exists('int', $info['type']))
			$info['type']['int'] = 0;

		if (!array_key_exists('CB',$info['paiement']))
			$info['paiement']['CB'] = 0;
		if (!array_key_exists('Espece',$info['paiement']))
			$info['paiement']['Espece'] = 0;
		if (!array_key_exists('Cheque',$info['paiement']))
			$info['paiement']['Cheque'] = 0;

		if (!array_key_exists('autovalid', $info['gestion']))
			$info['gestion']['autovalid'] = 0;
		if (!array_key_exists('mail', $info['gestion']))
			$info['gestion']['mail'] = 0;
		if (!array_key_exists('base', $info['gestion']))
			$info['gestion']['base'] = 0;

		if ( ! empty( $use ) ) :
	?>
	<?php endif; ?>

	<div class="info-fields">
	<div class="half-left">
		<div class="info-field">
		<label for="<?php echo $id; ?>-adresse"><?php echo esc_html( __( 'Address', 'parking-management' ) ); ?></label><br />
		<input type="text" id="<?php echo $id; ?>-adresse" name="<?php echo $id; ?>-adresse" class="wide" size="70" value="<?php echo esc_attr( $info['adresse'] ); ?>" />
		</div>

		<div class="info-field">
		<label for="<?php echo $id; ?>-telephone"><?php echo esc_html( __( 'Phone', 'parking-management' ) ); ?></label><br />
		<input type="text" id="<?php echo $id; ?>-telephone" name="<?php echo $id; ?>-telephone" class="wide" size="70" value="<?php echo esc_attr( $info['telephone'] ); ?>" />
		</div>

		<div class="info-field">
		<label for="<?php echo $id; ?>-email"><?php echo esc_html( __( 'Email', 'parking-management' ) ); ?></label><br />
		<input type="text" id="<?php echo $id; ?>-email" name="<?php echo $id; ?>-email" class="wide" size="70" value="<?php echo esc_attr( $info['email'] ); ?>" />
		</div>

		<div class="info-field">
		<label for="<?php echo $id; ?>-RCS"><?php echo esc_html( __( 'RCS', 'parking-management' ) ); ?></label><br />
		<input type="text" id="<?php echo $id; ?>-RCS" name="<?php echo $id; ?>-RCS" class="wide" size="70" value="<?php echo esc_attr( $info['RCS'] ); ?>" />
		</div>

		<div class="info-field">
		<label for="<?php echo $id; ?>-deliverymessage"><?php echo esc_html( __( 'Delivery Message', 'parking-management' ) ); ?></label><br />
		<textarea rows="5" cols="70" id="<?php echo $id; ?>-deliverymessage" name="<?php echo $id; ?>-deliverymessage" ><?php echo esc_attr( $info['deliverymessage'] ); ?></textarea>
		</div>
		<div class="info-field">
		<p><label>Type de Paiment accept&eacute;</label></p>
		<input type="checkbox" id="<?php echo $id; ?>-paiement_cb" name="<?php echo $id; ?>-paiement[CB]"
			value="<?php echo $info['paiement']['CB']; ?>"
			<?php echo ($info['paiement']['CB'] == 1) ? "checked=\"checked\"" : ""; ?>
		/>
		<label for="<?php echo $id; ?>-paiement_cb"><?php echo esc_html( __( 'CB payment', 'parking-management' ) ); ?></label><br />
		<input type="checkbox" id="<?php echo $id; ?>-paiement_espece" name="<?php echo $id; ?>-paiement[Espece]"
			value="<?php echo $info['paiement']['Espece']; ?>"
			<?php echo ($info['paiement']['Espece'] == 1) ? "checked=\"checked\"" : ""; ?>
		/>
		<label for="<?php echo $id; ?>-paiement_espece"><?php echo esc_html( __( 'Cash payment', 'parking-management' ) ); ?></label><br />
		<input type="checkbox" id="<?php echo $id; ?>-paiement_cheque" name="<?php echo $id; ?>-paiement[Cheque]"
			value="<?php echo $info['paiement']['Cheque']; ?>"
			<?php echo ($info['paiement']['Cheque'] == 1) ? "checked=\"checked\"" : ""; ?>
		/>
		<label for="<?php echo $id; ?>-paiement_cheque"><?php echo esc_html( __( 'Cheque payment', 'parking-management' ) ); ?></label><br />
		</div>

		<div class="info-field">
		<p><label>Mode de garde</label></p>
		<input type="checkbox" id="<?php echo $id; ?>-type_ext" name="<?php echo $id; ?>-type[ext]"
			value="<?php echo $info['type']['ext']; ?>"
			<?php echo ($info['type']['ext'] == 1) ? "checked=\"checked\"" : ""; ?>
		/>
		<label for="<?php echo $id; ?>-type_ext"><?php echo esc_html( __( 'Outdoor', 'parking-management' ) ); ?></label><br />
		<input type="checkbox" id="<?php echo $id; ?>-type_int" name="<?php echo $id; ?>-type[int]"
			value="<?php echo $info['type']['int']; ?>"
			<?php echo ($info['type']['int'] == 1) ? "checked=\"checked\"" : ""; ?>
		/>
		<label for="<?php echo $id; ?>-type_int"><?php echo esc_html( __( 'Indoor', 'parking-management' ) ); ?></label><br />
		</div>

		<div class="info-field">
		<p><label>Indicatif pays par défaut</label></p>
			<input type="radio" id="<?php echo $id; ?>-indicatif_fr" name="<?php echo $id; ?>-indicatif" value="fr"
			<?php echo ($info['indicatif'] == 'fr') ? "checked=\"checked\"" : ""; ?> />
			<label for="<?php echo $id; ?>-indicatif_fr">France</label><br />
			<input type="radio" id="<?php echo $id; ?>-indicatif_be" name="<?php echo $id; ?>-indicatif" value="be"
			<?php echo ($info['indicatif'] == 'be') ? "checked=\"checked\"" : ""; ?> />
			<label for="<?php echo $id; ?>-indicatif_be">Belgique</label><br />
			<input type="radio" id="<?php echo $id; ?>-indicatif_ch" name="<?php echo $id; ?>-indicatif" value="ch"
			<?php echo ($info['indicatif'] == 'ch') ? "checked=\"checked\"" : ""; ?> />
			<label for="<?php echo $id; ?>-indicatif_ch">Suisse</label><br />
			<input type="radio" id="<?php echo $id; ?>-indicatif_de" name="<?php echo $id; ?>-indicatif" value="de"
			<?php echo ($info['indicatif'] == 'de') ? "checked=\"checked\"" : ""; ?> />
			<label for="<?php echo $id; ?>-indicatif_de">Allemagne</label><br />
			<input type="radio" id="<?php echo $id; ?>-indicatif_nl" name="<?php echo $id; ?>-indicatif" value="nl"
			<?php echo ($info['indicatif'] == 'nl') ? "checked=\"checked\"" : ""; ?> />
			<label for="<?php echo $id; ?>-indicatif_nl">Pays-bas</label><br />
		</div>

	</div>
	<div class="half-right">
		<div class="info-field">
		<label for="<?php echo $id; ?>-tva1"><?php echo esc_html( __( 'VAT #1', 'parking-management' ) ); ?></label><br />
		<input type="text" id="<?php echo $id; ?>-tva1" name="<?php echo $id; ?>-tva1" class="wide" size="70" value="<?php echo esc_attr( $info['tva1'] ); ?>" />
		</div>

		<div class="info-field">
		<label for="<?php echo $id; ?>-tva2"><?php echo esc_html( __( 'VAT #2', 'parking-management' ) ); ?></label><br />
		<input type="text" id="<?php echo $id; ?>-tva2" name="<?php echo $id; ?>-tva2" class="wide" size="70" value="<?php echo esc_attr( $info['tva2'] ); ?>" />
		</div>

		<div class="info-field">
		<label for="<?php echo $id; ?>-time"><?php echo esc_html( __( 'Time', 'parking-management' ) ); ?></label><br />
		<input type="time" id="<?php echo $id; ?>-time-start" name="<?php echo $id; ?>-time-start" class="wide" size="70" value="<?php echo esc_attr( $info['time']['start'] ); ?>" />
		<input type="time" id="<?php echo $id; ?>-time-end"   name="<?php echo $id; ?>-time-end"   class="wide" size="70" value="<?php echo esc_attr( $info['time']['end'] ); ?>" />
		</div>
		<div class="info-field">
		<label for="<?php echo $id; ?>-logo"><?php echo esc_html( __( 'Print Logo', 'parking-management' ) ); ?></label><br />
		<input type="text" id="<?php echo $id; ?>-logo" name="<?php echo $id; ?>-logo" class="wide" size="70" value="<?php echo esc_attr( $info['logo'] ); ?>" />
		<input type="button" class="button-primary" name="<?php echo $id; ?>-logo_button" id="<?php echo $id; ?>-logo_button" value="<?php echo esc_html(__('Choose Image','parking-management')); ?>" />
		</div>

		<br>
		<div class="info-field">
		<p><label>Options diverses</label></p>
		<input type="checkbox" id="<?php echo $id; ?>-cg" name="<?php echo $id; ?>-cg"
			value="<?php echo $info['cg']; ?>"
			<?php echo ($info['cg'] == 1) ? "checked=\"checked\"" : ""; ?>
		/>
		<label for="<?php echo $id; ?>-cg"><?php echo esc_html( __( 'Legal Information', 'parking-management' ) ); ?></label><br />
		</div>

		<br>
		<div class="info-field">
		<p><label>Options suppl&eacute;ments</label></p>
		<input type="checkbox" id="<?php echo $id; ?>-frais_dimanche" name="<?php echo $id; ?>-frais[dimanche]"
			value="<?php echo $info['frais']['dimanche']; ?>"
			<?php echo ($info['frais']['dimanche'] == 1) ? "checked=\"checked\"" : ""; ?>
		/>
		<label for="<?php echo $id; ?>-frais_dimanche"><?php echo esc_html( __( 'Taxe on sunday', 'parking-management' ) ); ?></label><br />
		<input type="checkbox" id="<?php echo $id; ?>-frais_nuit" name="<?php echo $id; ?>-frais[nuit]"
			value="<?php echo $info['frais']['nuit']; ?>"
			<?php echo ($info['frais']['nuit'] == 1) ? "checked=\"checked\"" : ""; ?>
		/>
		<label for="<?php echo $id; ?>-frais_nuit"><?php echo esc_html( __( 'Taxe on night', 'parking-management' ) ); ?></label><br />
		<input type="checkbox" id="<?php echo $id; ?>-frais_ferie" name="<?php echo $id; ?>-frais[ferie]"
			value="<?php echo $info['frais']['ferie']; ?>"
			<?php echo ($info['frais']['ferie'] == 1) ? "checked=\"checked\"" : ""; ?>
		/>
		<label for="<?php echo $id; ?>-frais_ferie"><?php echo esc_html( __( 'Taxe on days off', 'parking-management' ) ); ?></label><br />

		</div>
		<br>
		<div class="info-field">
		<p><label>Terminals</label></p>
		<?php
	?>
			<input type="radio" id="<?php echo $id; ?>-terminal_orly" name="<?php echo $id; ?>-terminal" value="Orly"
<?php echo ($info['terminal'] == 'Orly') ? "checked=\"checked\"" : ""; ?> />
			<label for="<?php echo $id; ?>-terminal_orly"><?php echo esc_html( __( 'Orly', 'parking-management' ) ); ?></label><br />
			<input type="radio" id="<?php echo $id; ?>-terminal_roissy" name="<?php echo $id; ?>-terminal" value="Roissy"
<?php echo ($info['terminal'] == 'Roissy') ? "checked=\"checked\"" : ""; ?> />
			<label for="<?php echo $id; ?>-terminal_roissy"><?php echo esc_html( __( 'Roissy', 'parking-management' ) ); ?></label><br />
			<input type="radio" id="<?php echo $id; ?>-terminal_zaventem" name="<?php echo $id; ?>-terminal" value="Zaventem"
<?php echo ($info['terminal'] == 'Zaventem') ? "checked=\"checked\"" : ""; ?> />
			<label for="<?php echo $id; ?>-terminal_zaventem"><?php echo esc_html( __( 'Zaventem', 'parking-management' ) ); ?></label><br />
		</div>

		<div class="info-field">
		<p><label>Gestions</label></p>
		<?php
	?>
			<input type="checkbox" id="<?php echo $id; ?>-gestion_autovalid" name="<?php echo $id; ?>-gestion[autovalid]"
			value="<?php echo $info['gestion']['autovalid']; ?>"
			<?php echo ($info['gestion']['autovalid'] == 1) ? "checked=\"checked\"" : ""; ?>
			/>
			<label for="<?php echo $id; ?>-gestion_autovalid"><?php echo esc_html( __( 'Auto Valid', 'parking-management' ) ); ?></label><br />
			<input type="checkbox" id="<?php echo $id; ?>-gestion_mail" name="<?php echo $id; ?>-gestion[mail]"
			value="<?php echo $info['gestion']['mail']; ?>"
			<?php echo ($info['gestion']['mail'] == 1) ? "checked=\"checked\"" : ""; ?>
			/>
			<label for="<?php echo $id; ?>-gestion_mail"><?php echo esc_html( __( 'E-MAIL', 'parking-management' ) ); ?></label><br />
			<input type="checkbox" id="<?php echo $id; ?>-gestion_base" name="<?php echo $id; ?>-gestion[base]"
			value="<?php echo $info['gestion']['base']; ?>"
			<?php echo ($info['gestion']['base'] == 1) ? "checked=\"checked\"" : ""; ?>
			/>
			<label for="<?php echo $id; ?>-gestion_base"><?php echo esc_html( __( 'Database', 'parking-management' ) ); ?></label><br />
		</div>


	</div>

	<br class="clear" />
	</div>
	<?php
	}

    function pkmgmt_paypal_meta_box( $post, $box )
    {
        $defaults = array( 'id' => 'pkmgmt-paypal', 'name' => 'paypal', 'use' => null );

        if ( ! isset( $box['args'] ) || ! is_array( $box['args'] ) )
            $args = array();
        else
            $args = $box['args'];
        extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

        $id = esc_attr( $id );
        $paypal = $post->{$name};

				if (!array_key_exists('actif', $paypal))
					$paypal['actif'] = 0;
				if (!array_key_exists('commun', $paypal))
					$paypal['commun'] = 0;

        ?>
        <div class="paypal-fields">
            <div class="half-left">
		        <div class="paypal-field">
		            <input
									type="checkbox"
									id="<?php echo $id; ?>-actif"
									name="<?php echo $id; ?>-actif"
									value="<?php echo $paypal['actif']; ?>"
									<?php echo ($paypal['actif'] == 1) ? "checked=\"checked\"" : ""; ?>
		            />
		            <label for="<?php echo $id; ?>-actif"><?php echo esc_html( __( 'Enable', 'parking-management' ) ); ?></label><br />
		            <input
									type="checkbox"
									id="<?php echo $id; ?>-commun"
									name="<?php echo $id; ?>-commun"
		            	value="<?php echo $paypal['commun']; ?>"
									<?php echo ($paypal['commun'] == 1) ? "checked=\"checked\"" : ""; ?>
		            />
		            <label for="<?php echo $id; ?>-commun"><?php echo esc_html( __( 'Common', 'parking-management' ) ); ?></label><br />
            </div>
            </div>
        <br class="clear" />
		</div>

        <?php
    }

	function pkmgmt_divers_meta_box( $post, $box )
	{
		$defaults = array( 'id' => 'pkmgmt-divers', 'name' => 'divers', 'use' => null );

		if ( ! isset( $box['args'] ) || ! is_array( $box['args'] ) )
			$args = array();
		else
			$args = $box['args'];
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

		$id = esc_attr( $id );
		$divers = $post->{$name};
		if ( ! empty( $use ) ) :
	?>
	<?php endif; ?>

	<div class="divers-fields">
	<div class="half-left">
		<div class="divers-field">
		<label for="<?php echo $id; ?>-smstype"><?php echo esc_html( __( 'SMS Type', 'parking-management' ) ); ?></label><br />
		<select id="<?php echo $id; ?>-smstype" name="<?php echo $id; ?>-smstype" >
		  <option value="ovh" <?php echo ($divers['smstype'] == "OVH") ? "selected":""; ?> >OVH</option>
		  <option value="smsenvoie" <?php echo ($divers['smstype'] == "smsenvoie") ? "selected":""; ?> >SMS Envoie</option>
		  <option value="smsbox" <?php echo ($divers['smstype'] == "smsbox") ? "selected":""; ?> >SMS Box</option>
		</select>
		</div>

		<div class="divers-field">
		<label for="<?php echo $id; ?>-smsaccount"><?php echo esc_html( __( 'SMS Account', 'parking-management' ) ); ?></label><br />
		<input type="text" id="<?php echo $id; ?>-smsaccount" name="<?php echo $id; ?>-smsaccount" class="wide" size="70" value="<?php echo esc_attr( $divers['smsaccount'] ); ?>" />
		</div>

		<div class="divers-field">
		<label for="<?php echo $id; ?>-smsuser"><?php echo esc_html( __( 'SMS User', 'parking-management' ) ); ?></label><br />
		<input type="text" id="<?php echo $id; ?>-smsuser" name="<?php echo $id; ?>-smsuser" class="wide" size="70" value="<?php echo esc_attr( $divers['smsuser'] ); ?>" />
		</div>

		<div class="divers-field">
		<label for="<?php echo $id; ?>-smspasswd"><?php echo esc_html( __( 'SMS Password', 'parking-management' ) ); ?></label><br />
		<input type="text" id="<?php echo $id; ?>-smspasswd" name="<?php echo $id; ?>-smspasswd" class="wide" size="70"
			value="<?php echo esc_attr( $divers['smspasswd'] ); ?>" />
		</div>

		<div class="divers-field">
		<label for="<?php echo $id; ?>-expire"><?php echo esc_html( __( 'Timeout session', 'parking-management' ) ); ?></label><br />
		<input type="text" id="<?php echo $id; ?>-expire" name="<?php echo $id; ?>-expire" class="wide" size="70" value="<?php echo esc_attr( $divers['expire'] ); ?>" />
		</div>

		<div class="divers-field">
		<label for="<?php echo $id; ?>-vacances"><?php echo esc_html( __( 'URL holiday', 'parking-management' ) ); ?></label><br />
		<input type="text" id="<?php echo $id; ?>-vacances" name="<?php echo $id; ?>-vacances" class="wide" size="70" value="<?php echo esc_attr( $divers['vacances'] ); ?>" />
		</div>
	</div>

	<div class="half-right">
		<div class="divers-field">
		<label for="<?php echo $id; ?>-smssender"><?php echo esc_html( __( 'SMS Sender', 'parking-management' ) ); ?></label><br />
		<input type="text" id="<?php echo $id; ?>-smssender" name="<?php echo $id; ?>-smssender" class="wide" size="70"
			value="<?php echo esc_attr( $divers['smssender'] ); ?>" />
		</div>

		<div class="divers-field">
		<label for="<?php echo $id; ?>-smsmessage"><?php echo esc_html( __( 'SMS Message', 'parking-management' ) ); ?></label><br />
		<textarea  id="<?php echo $id; ?>-smsmessage" name="<?php echo $id; ?>-smsmessage" cols="100" rows="9"><?php echo esc_textarea( $divers['smsmessage'] ); ?> </textarea>
		</div>
	</div>

	<br class="clear" />
	</div>
	<?php
	}

	function pkmgmt_response_meta_box( $post, $box )
	{
		$defaults = array( 'id' => 'pkmgmt-response', 'name' => 'response', 'use' => null );

		if ( ! isset( $box['args'] ) || ! is_array( $box['args'] ) )
			$args = array();
		else
			$args = $box['args'];
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

		$id = esc_attr( $id );
		$response = $post->{$name};
?>

	<div class="response-field">
<?php
		wp_editor($response, $id, array('textarea_rows'=> 20));
?>

	<br class="clear" />
	</div>
	<?php
	}

	public function pkmgmt_render_admin()
	{
		if ( $post = pkmgmt_site::get_current() )
		{
			$this->title = $post->title;
			$this->pkmgmt_admin_edit($post->id);
			return;
		}

		$list_table = new PKMGMT_Parking_List_Table();
		$list_table->prepare_items();

		?>
		<div class="wrap theme-options-page">
			<h1><?php
			echo esc_html( __( 'Parking Management', 'parking-management') );
			echo ' <a href="' . esc_url( menu_page_url( 'pkmgmt-new', false ) ) . '" class="add-new-h2">' . esc_html( __( 'Add New Site', 'parking-management' ) ) . '</a>';
			?></h1>

	<?php do_action( 'pkmgmt_admin_notices' ); ?>

	<form method="post" action="options.php">
		<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
		<?php $list_table->display(); ?>
	</form>
		</div>
		<?php
	}

	public function pkmgmt_map_meta_cap( $caps, $cap, $user_id, $args )
	{
		$meta_caps = array(
			'pkmgmt_edit'   => PKMGMT_ADMIN_READ_WRITE_CAPABILITY,
			'pkmgmt_read'   => PKMGMT_ADMIN_READ_CAPABILITY,
			'pkmgmt_delete' => PKMGMT_ADMIN_READ_WRITE_CAPABILITY,
			'pkmgmt_admin' => PKMGMT_ADMIN_REMOVE_USERS );

		$meta_caps = apply_filters( 'pkmgmt_map_meta_cap', $meta_caps );

		$caps = array_diff( $caps, array_keys( $meta_caps ) );

		if ( isset( $meta_caps[$cap] ) )
			$caps[] = $meta_caps[$cap];

		return $caps;
	}

	public function generate_default_package( $args = '' )
	{
		global $l10n;

		$defaults = array( 'locale' => 'fr_FR', 'title' => '', 'name' => '' );
		$args = wp_parse_args( $args, $defaults );
		$locale = $args['locale'];
		$title = $args['title'];
		$name = $args['name'];

		if ( $locale ) {
			$mo_orig = $l10n['parking-management'];
			self::admin_load_textdomain( $locale );
		}

		$site = new pkmgmt_site();
		$site->initial = true;
		$site->title = ( $title ? $title : __( 'Untitled', 'parking-management' ) );
		$site->name = ( $name ? $name : __( 'Untitled', 'parking-management' ) );
		$site->locale = ( $locale ? $locale : get_locale() );

		if ( isset( $mo_orig ) ) {
			$l10n['parking-management'] = $mo_orig;
		}
		return $site;
	}

	public function admin_install()
	{
			$this->admin_install_roles();
			$current_user = wp_get_current_user();
			$current_user->add_role( 'pkmgmt_super_admin_role' );
			if ( $opt = get_option( 'pkmgmt' ) )
					return;
			$this->admin_load_textdomain();
			$this->register_post_types();
			if ( get_posts( array( 'post_type' => 'pkmgmt' ) ) )
					return;
			$site = $this->generate_default_package(
					array( 'title' => sprintf( __( 'Parking %d', 'parking-management' ), 1 ),
				'name' => sprintf( __( 'Parking %d', 'parking-management' ), 1 ) ) );
			$site->save();
	}

		private function admin_install_roles()
		{
				if (get_role('pkmgmt_read_role') !== null)
				remove_role('pkmgmt_read_role');
				add_role(
						'pkmgmt_read_role',
						__('PK Read', 'parking-management'),
						array(
								'read' => true,
								'read_private_pages' => true,
								'pkmgmt_read_cap' => true
						)
				);
				if (get_role('pkmgmt_read_write_role') !== null)
						remove_role('pkmgmt_read_write_role');
				add_role(
						'pkmgmt_read_write_role',
						__('PK Manager', 'parking-management'),
						array(
								'pkmgmt_read_cap' => true,
								'pkmgmt_manager_cap' => true,
								'read' => true,
								'read_private_pages' => true,
								'edit_pages' => true,
								'edit_themes' => true,
								'edit_theme_options' => true,
								'edit_published_pages' => true,
								'edit_private_pages' => true,
								'edit_others_posts' => true,
								'edit_others_pages' => true,
								'update_themes' => true,
								'upload_files' => true,
								'publish_pages' => true,
								'manage_links' => true,
								'install_themes' => true,
								'delete_published_pages' => true,
								'delete_private_pages' => true,
								'delete_pages' => true,
								'delete_others_pages' => true
						)
				);
				if (get_role('pkmgmt_admin_role') !== null)
						remove_role('pkmgmt_admin_role');
				add_role(
						'pkmgmt_admin_role',
						__('PK Admin', 'parking-management'),
						array(
								'pkmgmt_read_cap' => true,
								'pkmgmt_manager_cap' => true,
								'pkmgmt_admin_cap' => true,
								'read' => true,
								'read_private_pages' => true,
								'edit_pages' => true,
								'edit_themes' => true,
								'edit_theme_options' => true,
								'edit_published_pages' => true,
								'edit_private_pages' => true,
								'edit_others_posts' => true,
								'edit_others_pages' => true,
								'edit_users' => true,
								'update_themes' => true,
								'upload_files' => true,
								'publish_pages' => true,
								'manage_links' => true,
								'install_themes' => true,
								'delete_published_pages' => true,
								'delete_private_pages' => true,
								'delete_pages' => true,
								'delete_others_pages' => true,
								'remove_users' => true,
								'list_users' => true,
								'create_users' => true
						)
				);
				if (get_role('pkmgmt__super_admin_role') !== null)
						remove_role('pkmgmt_super_admin_role');
				add_role(
						'pkmgmt_super_admin_role',
						__('PK Super Admin', 'parking-management'),
						array(
								'pkmgmt_read_cap' => true,
								'pkmgmt_manager_cap' => true,
								'pkmgmt_admin_cap' => true,
								'pkmgmt_super_admin_cap' => true,
								'read' => true,
								'read_private_pages' => true,
								'edit_pages' => true,
								'edit_themes' => true,
								'edit_theme_options' => true,
								'edit_published_pages' => true,
								'edit_private_pages' => true,
								'edit_others_posts' => true,
								'edit_others_pages' => true,
								'edit_users' => true,
								'update_themes' => true,
								'upload_files' => true,
								'publish_pages' => true,
								'manage_links' => true,
								'install_themes' => true,
								'delete_published_pages' => true,
								'delete_private_pages' => true,
								'delete_pages' => true,
								'delete_others_pages' => true,
								'remove_users' => true,
								'list_users' => true,
								'create_users' => true
						)
				);
		}

	function register_post_types()
	{
		register_post_type( self::post_type, array(
			'labels' => array(
				'name' => __( 'Parking Management', 'parking-management' ),
				'singular_name' => __( 'Parking Management', 'parking-management' ) ),
			'rewrite' => false,
			'query_var' => false ) );
	}

	public function admin_init()
	{
		$opt = get_option( 'pkmgmt' );
		if ( ! is_array( $opt ) )
				$opt = array();

		$old_ver = isset( $opt['version'] ) ? (string) $opt['version'] : '0';
		$new_ver = PKMGMT_VERSION;

		if ( $old_ver == $new_ver )
				return;

		$opt['version'] = $new_ver;
		update_option( 'pkmgmt', $opt );

	}

	public function pkmgmt_init()
	{
		$this->get_request_uri();
		self::register_post_type();
	}

	public static function register_post_type()
	{
		register_post_type( self::post_type, array(
			'labels' => array(
				'name' => __( 'Parking Management', 'parking-management' ),
				'singular_name' => __( 'Parking Management', 'parking-management' ) ),
			'rewrite' => false,
			'query_var' => false ) );
	}

	public function get_request_uri(): string
	{
		static $request_uri = '';

		if ( empty( $request_uri ) )
			$request_uri = add_query_arg( array() );
		return esc_url_raw( $request_uri );
	}

	function pkmgmt_admin_updated_message()
	{
		if ( empty( $_REQUEST['message'] ) )
			return;

		if ( 'created' == $_REQUEST['message'] )
			$updated_message = esc_html( __( 'Site created.', 'parking-management' ) );
		elseif ( 'saved' == $_REQUEST['message'] )
			$updated_message = esc_html( __( 'Site saved.', 'parking-management' ) );
		elseif ( 'deleted' == $_REQUEST['message'] )
			$updated_message = esc_html( __( 'Site deleted.', 'parking-management' ) );

		if ( empty( $updated_message ) )
			return;

		?>
		<div id="message" class="updated"><p><?php echo $updated_message; ?></p></div>
		<?php
	}

	function pkmgmt_add_meta_boxes( $post_id )
	{

		add_meta_box( 'databasediv', __( 'Database', 'parking-management' ),
			array( &$this, 'pkmgmt_database_meta_box'), null, 'database', 'core' );

		add_meta_box( 'infodiv', __( 'Information', 'parking-management' ),
			array( &$this, 'pkmgmt_info_meta_box'), null, 'info', 'core' );

		add_meta_box( 'paypaldiv', __( 'Paypal', 'parking-management' ),
			array( &$this, 'pkmgmt_paypal_meta_box'), null, 'paypal', 'core' );

		add_meta_box( 'diversdiv', __( 'Divers', 'parking-management' ),
			array( &$this, 'pkmgmt_divers_meta_box'), null, 'divers', 'core' );

		add_meta_box( 'responsediv', __( 'Response', 'parking-management' ),
			array( &$this, 'pkmgmt_response_meta_box'), null, 'response', 'core' );

		do_action( 'pkmgmt_add_meta_boxes', $post_id );
	}

	function pkmgmt_admin_enqueue_scripts( $hook_suffix )
	{
		if ( false === strpos( $hook_suffix, 'pkmgmt' ) )
		return;

		wp_enqueue_style( 'parking-management-admin',
			$this->pkmgmt_plugin_url( 'admin/css/styles.css' ),
			array(), PKMGMT_VERSION, 'all' );
		if ( $this->pkmgmt_is_rtl() )
		{
			wp_enqueue_style( 'parking-management-admin-rtl',
				$this->pkmgmt_plugin_url( 'admin/css/styles-rtl.css' ),
				array(), PKMGMT_VERSION, 'all' );
		}
		wp_enqueue_style('thickbox');
		wp_enqueue_script('media-upload');
		wp_enqueue_script('thickbox');
		wp_enqueue_script( 'pkmgmt-admin',
			$this->pkmgmt_plugin_url( 'admin/js/scripts.js' ),
			array( 'jquery','postbox','media-upload','thickbox' ),
			PKMGMT_VERSION, true );


		$current_screen = get_current_screen();

		wp_localize_script( 'pkmgmt-admin', '_pkmgmt', array(
			'siteurl'=> get_option("siteurl").DS,
			'base'=> ABSPATH,
			'screenId' => $current_screen->id,
			'pluginUrl' => $this->pkmgmt_plugin_url()
			) );

	}

	function pkmgmt_admin_site_page()
	{
		global $plugin_page;
		$post_id = (int) substr( $plugin_page, 7 );
		$site = $this->pkmgmt_site($post_id);

		wp_enqueue_style( 'pkmgmt-jtable-css',
			$this->pkmgmt_plugin_url( 'admin/css/jtable.min.css' ));

		wp_enqueue_style( 'pkmgmt-jtable-ui-css',
			$this->pkmgmt_plugin_url( 'admin/css/jquery-ui.css' ));

		wp_enqueue_style( 'pkmgmt-calendar-css',
			$this->pkmgmt_plugin_url( 'admin/css/calendar.css' ));

		wp_enqueue_script( 'pkmgmt-datetime', $this->pkmgmt_plugin_url( 'admin/js/jquery-ui-timepicker-addon.js' ),
			array( 'jquery', 'jquery-ui-core', 'jquery-ui-datepicker' ) );

		wp_enqueue_script( 'highcharts', $this->pkmgmt_plugin_url( 'admin/js/highcharts.js' ),
			array( 'jquery', 'jquery-ui-core' ) );
		wp_enqueue_script( 'exporting', $this->pkmgmt_plugin_url('admin/js/exporting.js'),
			array('highcharts') );
		wp_enqueue_script( 'pkmgmt-datetime-fr', $this->pkmgmt_plugin_url( 'admin/js/jquery-ui-timepicker-fr.js' ),
			array( 'pkmgmt-datetime' ) );
		wp_enqueue_script('jquery-ui-dialogw', $this->pkmgmt_plugin_url('admin/js/jquery.dialogw.js'),
			array( 'jquery', 'jquery-ui-dialog', 'jquery-ui-core', 'jquery-ui-widget'));
		wp_enqueue_script( 'pkmgmt-datetime-fr', $this->pkmgmt_plugin_url('admin/js/jquery-ui-timepicker-fr.js'),
			array( 'pkmgmt-datetime' ) );
		wp_enqueue_script( 'pkmgmt-jtable-custom', $this->pkmgmt_plugin_url( 'admin/js/jquery.jtable.custom.js' ),
			array( 'pkmgmt-datetime', 'jquery-ui-dialog', 'jquery-ui-dialogw' ) );

		wp_enqueue_script( 'pkmgmt-calendario', $this->pkmgmt_plugin_url( 'admin/js/jquery.calendario.js' ),
			array( 'pkmgmt-jtable-custom' ) );
		wp_enqueue_script( 'pkmgmt-calendarier', $this->pkmgmt_plugin_url('admin/js/calendrier1.js'),
			array( 'pkmgmt-calendario'));

		wp_enqueue_script( 'pkmgmt-site', $this->pkmgmt_plugin_url( 'admin/js/admin.js' ),
			array( 'pkmgmt-calendarier' ) );

		$options_aerogare= array('W' => 'Ouest', 'S' => 'Sud');

		if ( $site->info['terminal'] == 'Roissy' )
			$options_aerogare= array('1'=> 'Terminal 1','2A'=> 'Terminal 2A','2B'=> 'Terminal 2B','2C'=> 'Terminal 2C','2D'=> 'Terminal 2D','2E'=> 'Terminal 2E','2F'=> 'Terminal 2F','2G'=> 'Terminal 2G','3'=> 'Terminal 3');
		$aerogare_retour = array(
				'title' => 'Terminal aller',
				'type' => 'select',
				'options' => $options_aerogare,
				'defaultValue' => 'S',
				'list'=> false);
		$aerogare_aller = array(
						'title' => 'Terminal aller',
						'type' => 'select',
						'options' => $options_aerogare,
						'defaultValue' => 'S',
						'list'=> false);
        $paypal   = $site->paypal;
		$database = $site->database;
		$paiement = array();
		foreach ( $site->info['paiement'] as $mode => $paytype)
		{
			if ( $paytype == 1 )
				$paiement[] = $mode;
		}

		$type = array();
		foreach ( $site->info['type'] as $mode => $typestatus)
		{
			if ( $typestatus == 1 )
				$type[] = $mode;
		}

		$gestion = array();
		foreach ( $site->info['gestion'] as $mode => $gestionstatus)
		{
			if ( $gestionstatus == 1 )
				$gestion[] = $mode;
		}

		$database = json_encode($database);
		wp_localize_script( 'pkmgmt-site', 'ajax_object',
			array( 'ajax_url' => admin_url( 'admin-ajax.php' ),
				'postid'=>  $post_id,
				'superadmin'=> current_user_can('pkmgmt_super_admin_cap') ? "1":"0",
				'admin' =>  current_user_can('pkmgmt_admin_cap') ?  "1" : "0" ,
				'manager' => current_user_can('pkmgmt_manager_cap') ? "1" : "0",
				'read' => current_user_can('pkmgmt_read_cap') ? "1": "0",
				'plugin_url' => $this->pkmgmt_plugin_url(),
				'calendrier' => $this->pkmgmt_plugin_url( 'admin/js/calendrier.js' ),
				'calendario' => $this->pkmgmt_plugin_url( 'admin/js/jquery.calendario.js' ),
				'aerogare_aller' => $aerogare_aller,
				'aerogare_retour' =>  $aerogare_retour,
				'paiement' => $paiement,
				'type' => $type,
				'gestion' => $gestion,
				'terminal' => strtolower($site->info['terminal']),
				'database' => $database)
					);
		?>
	<div class="wrap">
		<h2><?php echo esc_html( $site->title ); ?></h2>
		<div id="select_pkmgmt"></div>
		<div>
			<div id="HeaderContainer"></div>
			<div id="JTableContainer"></div>
		</div>
		<div id="button_pkmgmt"></div>
	</div>
		<?php
	}

	/**********************************************************************************/
	/*						   Reservation CallBack								 */
	/**********************************************************************************/

	function listResaAction_callback()
	{
		if ( ! class_exists("reservation") )
			require_once PKMGMT_PLUGIN_MODULES_DIR. DS . "reservation.php";
		$post_id = (int) $_REQUEST['post_id'];
		$site = new reservation( $post_id );
		$site->listReservations();
		exit(1);
	}

	function deleteResaAction_callback()
	{
		if ( ! class_exists("reservation") )
			require_once PKMGMT_PLUGIN_MODULES_DIR. DS . "reservation.php";
		$post_id = (int) $_REQUEST['post_id'];
		$site = new reservation( $post_id );
		$site->deleteReservations();
		exit(1);
	}

	function getResaTarif_callback()
	{
		if ( ! class_exists("reservation") )
			require_once PKMGMT_PLUGIN_MODULES_DIR. DS . "reservation.php";
		$post_id = (int) $_REQUEST['post_id'];
		$resa = new reservation( $post_id );
		$resa->getTarif();
		exit(1);
	}

	function getTarifByDate_callback()
	{
		if ( ! class_exists("reservation") )
			require_once PKMGMT_PLUGIN_MODULES_DIR. DS . "reservation.php";
		$post_id = (int) $_REQUEST['post_id'];
		$resa = new reservation( $post_id );
		$resa->getHomeTarifs();
		exit(1);
	}

	function createResaAction_callback()
	{
		if ( ! class_exists("reservation") )
			require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "reservation.php";
		$post_id = (int) $_REQUEST['post_id'];
		$resa = new reservation( $post_id );
		$resa->createReservation();
		exit(1);
	}

	function updateResaAction_callback()
	{
		if ( ! class_exists("reservation") )
			require_once PKMGMT_PLUGIN_MODULES_DIR. DS . "reservation.php";
		$post_id = (int) $_REQUEST['post_id'];
		$resa = new reservation( $post_id );
		$resa->updateReservation();
		exit(1);
	}

	function statusResaAction_callback()
	{
		if ( ! class_exists("reservation") )
			require_once PKMGMT_PLUGIN_MODULES_DIR. DS . "reservation.php";
		$post_id = (int) $_REQUEST['post_id'];
		$resa = new reservation( $post_id );
		$resa->statusResa();
		exit(1);
	}

	function updateResaDBAction_callback()
	{
		if ( ! class_exists("reservation") )
			require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "reservation.php";
		$post_id = (int) $_REQUEST['post_id'];
		$resa = new reservation( $post_id );
		$resa->updateDB();
		exit(1);
	}

	function validResaAction_callback()
	{
		if ( ! class_exists("reservation") )
			require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "reservation.php";
		$post_id = (int) $_REQUEST['post_id'];
		$resa = new reservation( $post_id );
		$resa->validReservation();
		exit(1);
	}

  /**********************************************************************************/
  /*                                     PDF                                        */
  /**********************************************************************************/

	function invoiceResaAction_callback()
	{
		if (! class_exists("pkmgmt_pdf") )
			require_once PKMGMT_PLUGIN_MODULES_DIR.DS."pkmgmt_pdf.php";
		$post_id = (int) $_REQUEST['post_id'];
		$pkmgmtpdf = new pkmgmt_pdf( $post_id );
		if ( $_REQUEST['out'] == 'print' )
			$pkmgmtpdf->invoicePrint();
		if ( $_REQUEST['out'] == 'send' )
			$pkmgmtpdf->invoiceSend();
		exit(1);
	}

	function exitsplitResaAction_callback()
	{
		if (! class_exists("pkmgmt_pdf") )
			require_once PKMGMT_PLUGIN_MODULES_DIR.DS."pkmgmt_pdf.php";
		$post_id = (int) $_REQUEST['post_id'];
		$pkmgmtpdf = new pkmgmt_pdf( $post_id );
		$pkmgmtpdf->exitsplitPrint();
		exit(1);
	}

	function deliveryResaAction_callback()
	{
		if (! class_exists("pkmgmt_pdf") )
			require_once PKMGMT_PLUGIN_MODULES_DIR.DS."pkmgmt_pdf.php";
		$post_id = (int) $_REQUEST['post_id'];
		$pkmgmtpdf = new pkmgmt_pdf( $post_id );
		$pkmgmtpdf->deliveryPrint();
		exit(1);
	}

	function printResaAction_callback()
	{
		if (! class_exists("pkmgmt_pdf") )
			require_once PKMGMT_PLUGIN_MODULES_DIR.DS."pkmgmt_pdf.php";
		$post_id = (int) $_REQUEST['post_id'];
		$pkmgmtpdf = new pkmgmt_pdf( $post_id );
		$pkmgmtpdf->pkmgmtPrint();
		exit(1);
	}

	/**********************************************************************************/
	/*								Calendrier									  */
	/**********************************************************************************/

	function getCalendarAction_callback()
	{
		if (!class_exists("pkmgmt_calendar"))
			require_once PKMGMT_PLUGIN_MODULES_DIR.DS."pkmgmt_calendar.php";
		$post_id = (int) $_REQUEST['post_id'];
		$pkmgmtcal = new pkmgmt_calendar( $post_id );
		$pkmgmtcal->getCalendar();
		exit(1);
	}

	/**********************************************************************************/
	/*							 Service CallBack								   */
	/**********************************************************************************/
	function listServicesAction_callback()
	{
		if ( ! class_exists("services") )
			require_once PKMGMT_PLUGIN_MODULES_DIR. DS . "services.php";
		$post_id = (int) $_REQUEST['post_id'];
		$site = new services( $post_id );
		$site->listServices();
		exit(1);
	}

	function deleteServicesAction_callback()
	{
		if ( ! class_exists("services") )
			require_once PKMGMT_PLUGIN_MODULES_DIR. DS . "services.php";
		$post_id = (int) $_REQUEST['post_id'];
		$site = new services( $post_id );
		$site->deleteServices();
		exit(1);
	}

	function createServiceAction_callback()
	{
		if ( ! class_exists("services") )
			require_once PKMGMT_PLUGIN_MODULES_DIR. DS . "services.php";
		$post_id = (int) $_REQUEST['post_id'];
		$resa = new services( $post_id );
		$resa->createService();
		exit(1);
	}

	function updateServicesAction_callback()
	{
		if ( ! class_exists("services") )
			require_once PKMGMT_PLUGIN_MODULES_DIR. DS . "services.php";
		$post_id = (int) $_REQUEST['post_id'];
		$resa = new services( $post_id );
		$resa->updateService();
		exit(1);
	}

	/**********************************************************************************/
	/*							 Tarifs  CallBack								   */
	/**********************************************************************************/
	function listTarifsAction_callback()
	{
		if ( ! class_exists("tarifs") )
			require_once PKMGMT_PLUGIN_MODULES_DIR. DS . "tarifs.php";
		$post_id = (int) $_REQUEST['post_id'];
		$site = new tarifs( $post_id );
		$site->listTarifs();
		exit(1);
	}

	function deleteTarifsAction_callback()
	{
		if ( ! class_exists("tarifs") )
			require_once PKMGMT_PLUGIN_MODULES_DIR. DS . "tarifs.php";
		$post_id = (int) $_REQUEST['post_id'];
		$site = new tarifs( $post_id );
		$site->deleteTarifs();
		exit(1);
	}

	function createTarifAction_callback()
	{
		if ( ! class_exists("tarifs") )
			require_once PKMGMT_PLUGIN_MODULES_DIR. DS . "tarifs.php";
		$post_id = (int) $_REQUEST['post_id'];
		$resa = new tarifs( $post_id );
		$resa->createTarif();
		exit(1);
	}

	function updateTarifsAction_callback()
	{
		if ( ! class_exists("tarifs") )
			require_once PKMGMT_PLUGIN_MODULES_DIR. DS . "tarifs.php";
		$post_id = (int) $_REQUEST['post_id'];
		$resa = new tarifs( $post_id );
		$resa->updateTarifs();
		exit(1);
	}

	/**********************************************************************************/
	/*							 Client CallBack								   */
	/**********************************************************************************/
	function listClientsAction_callback()
	{
		if ( ! class_exists("clients") )
			require_once PKMGMT_PLUGIN_MODULES_DIR. DS . "clients.php";
		$post_id = (int) $_REQUEST['post_id'];
		$site = new clients( $post_id );
		$site->listClients();
		exit(1);
	}

	function deleteClientAction_callback()
	{
		if ( ! class_exists("clients") )
			require_once PKMGMT_PLUGIN_MODULES_DIR. DS . "clients.php";
		$post_id = (int) $_REQUEST['post_id'];
		$site = new clients( $post_id );
		$site->deleteClient();
		exit(1);
	}

	function createClientAction_callback()
	{
		if ( ! class_exists("clients") )
			require_once PKMGMT_PLUGIN_MODULES_DIR. DS . "clients.php";
		$post_id = (int) $_REQUEST['post_id'];
		$resa = new clients( $post_id );
		$resa->createClient();
		exit(1);
	}

	function updateClientAction_callback()
	{
		if ( ! class_exists("clients") )
			require_once PKMGMT_PLUGIN_MODULES_DIR. DS . "clients.php";
		$post_id = (int) $_REQUEST['post_id'];
		$resa = new clients( $post_id );
		$resa->updateClient();
		exit(1);
	}

	/**********************************************************************************/
	/*							 Caisse  CallBack								   */
	/**********************************************************************************/
	function caisseAction_callback()
	{
		if ( ! class_exists("caisse") )
			require_once PKMGMT_PLUGIN_MODULES_DIR. DS . "caisse.php";
		$post_id = (int) $_REQUEST['post_id'];
		$site = new caisse( $post_id );
		$site->totalCaisse();
		exit(1);
	}

	function datecaisseAction_callback()
	{
		if ( ! class_exists("caisse") )
			require_once PKMGMT_PLUGIN_MODULES_DIR. DS . "caisse.php";
		$post_id = (int) $_REQUEST['post_id'];
		$site = new caisse( $post_id );
		$site->dateCaisse();
		exit(1);
	}

	/**********************************************************************************/
	/*							 Graph CallBack								   */
	/**********************************************************************************/

	function graph_resa_dayAction_callback()
	{
		if ( ! class_exists("graph") )
			require_once PKMGMT_PLUGIN_MODULES_DIR. DS . "graph.php";
		$post_id = (int) $_REQUEST['post_id'];
		$site = new graph( $post_id );
		$site->nbr_resa_day();
		exit(1);

	}
}
?>
