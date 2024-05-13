<?php
add_action('rest_api_init', 'pkmgmt_add_endpoint', 0);
add_action('init', 'pkmgmt_request', 0);
add_action('init', 'pkmgmt_control_init', 11);
add_action('init', 'pkmgmt_load_textdomain');

function pkmgmt_control_init()
{
	global $wp;
	if (!isset($_SERVER['REQUEST_METHOD'])) {
		return;
	}
	if ('GET' == $_SERVER['REQUEST_METHOD']) {
		if (!empty($_GET) && isset($_GET['_pkmgmt'])) {
			pkmgmt_ajax();
		}
	}

	if ('POST' == $_SERVER['REQUEST_METHOD']) {
		if (!empty($_POST) && isset($_POST['_pkmgmt'])) {
			pkmgmt_ajax();
		}
		//exit(0);
	}
}

function pkmgmt_add_endpoint()
{
	//add_rewrite_endpoint( 'Parking_Management', EP_ALL );
	register_rest_route('payplug', '/ipn', array(
		'methods' => 'POST',
		'callback' => 'pkmgmt_payplug_notification',
	));
}

if (!class_exists("paypal"))
	require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "paypal.php";

if (!class_exists("payplug"))
	require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "payplug.php";
function pkmgmt_request()
{

	if (isset($_GET['action']) && $_GET['action'] == 'IPNAction') {
		$wp->query_vars['Parking_Management'] = $_GET['action'];
	}
	if (isset($_GET['type'])) {
		$wp->query_vars['type'] = $_GET['type'];
	}
	if (isset($_GET['post_id'])) {
		print_r($wp);
//        $wp->query_vars['post_id'] = $_GET['post_id'];
	}
	if (!empty($wp->query_vars['Parking_Management']) && !empty($wp->query_vars['resaid']) && !empty($wp->query_vars['type']) && $wp->query_vars['type'] == 'payplug') {
		$payplug = new payplug($wp->query_vars['post_id']);
		$payplug->ipn();
		die(1);
	}
	if (!empty($wp->query_vars['Parking_Management']) && !empty($wp->query_vars['post_id']) && !empty($wp->query_vars['type']) && $wp->query_vars['type'] == 'paypal') {
		$paypal = new paypal($wp->query_vars['post_id']);
		$paypal->ipn();
		die(1);
	}
}

function pkmgmt_payplug_notification()
{
	$post = array_merge($_POST, $_GET);

	if (!empty($post['post_id'])) {
		$payplug = new payplug($post['post_id']);
		$payplug->ipn();
		die(1);
	}
}

function pkmgmt_load_textdomain($locale = null)
{
	global $l10n;

	$domain = "parking-management";
	$mofile = $domain . "-" . get_locale() . ".mo";
	if (file_exists(PKMGMT_LANGUAGES_DIR . DS . $mofile))
		return load_textdomain($domain, PKMGMT_LANGUAGES_DIR . DS . $mofile);
	else
		return load_textdomain($domain, PKMGMT_LANGUAGES_DIR . DS . $domain . "-fr_FR.mo");
}

function pkmgmt_ajax()
{
	$post = array_merge($_POST, $_GET);
	$post_id = 0;
	if (isset($post['post_id']))
		$post_id = (int)$post['post_id'];
	if (isset($post['post_ID']))
		$post_id = (int)$post['post_ID'];
	if (!isset($post_id) || $post_id == 0)
		return;
	$resa = new reservation($post_id);
	if ($resa->createReservation(false)) {
		$resa->notifyReservation();
		$resa->notifySMSUser();
//		if ($resa->getEmail() == 'david@zdm.fr') print_log("after sms");
		$resa->notifyMailUser();
		$query_string = sprintf("?resaid=%s", $resa->getID());
		$page = get_page_by_title("Validation");
		if (is_null($page))
			wp_redirect(home_url());
		else
			wp_redirect(get_permalink($page->ID) . $query_string);
	} else {
		print_log("Erreur : ", false);
		print_log($resa->errorMessage, true);
	}
	exit(0);
}

/* Shortcodes */

if (!class_exists("reservation"))
	require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "reservation.php";

add_action('plugins_loaded', 'pkmgmt_add_shortcodes');

function pkmgmt_add_shortcodes()
{
	add_shortcode('parking-management', 'pkmgmt_management_form_tag_func');
	add_shortcode('parking-management-paypal', 'pkmgmt_management_paypal_form_tag_func');
	add_shortcode('parking-management-payplug', 'pkmgmt_management_payplug_func');
	add_shortcode('parking-management-payplug-payment', 'pkmgmt_management_payplug_func');

	add_shortcode('parking-management-home-form', 'pkmgmt_management_home_form_tag_func');
}

function pkmgmt_management_payplug_func($atts, $content = null, $code = '')
{

	if (is_feed())
		return '[parking-management-payplug]';
	$post = array_merge($_POST, $_GET);

	$atts = shortcode_atts(array(
		'id' => 0,
		'title' => '',
		'html_id' => '',
		'html_name' => '',
		'html_class' => '',
		'output' => 'form'), $atts);
	$id = (int)$atts['id'];

	$title = trim($atts['title']);

	if (!$reservation = new reservation($id))
		$reservation = pkmgmt_get_booking_form_by_title($title);
	if (!$reservation) return '[' . $code . ' 404 "Not Found"]';

	if ($code == 'parking-management-payplug') {
//		return $reservation->form_mypos(array(), $post['resaid']);
		return $reservation->form_payplug(array(), $post['resaid']);
	}
//	if ($code == 'parking-management-payplug-payment') {
//		return $reservation->mypos_payment($post['resaid']);
//	}
	return 'paiement sur place';
}

function pkmgmt_management_paypal_form_tag_func($atts, $content = null, $code = '')
{
	if (is_feed())
		return '[parking-management-paypal]';

	if ($code == 'parking-management-paypal') {
		$atts = shortcode_atts(array(
			'id' => 0,
			'title' => '',
			'html_id' => '',
			'html_name' => '',
			'html_class' => '',
			'output' => 'form'), $atts);
		$id = (int)$atts['id'];
		$pk = new pkmgmt();
		$indicatif = "fr";
		if ($site = pkmgmt_site($id)) {
			if (array_key_exists('indicatif', $site->info) && isset($site->info['indicatif']))
				$indicatif = $site->info['indicatif'];
		}
		$post = array_merge($_POST, $_GET);
		$title = trim($atts['title']);

		if (!$reservation = new reservation($id))
			$reservation = pkmgmt_get_booking_form_by_title($title);

	} else {
		if (is_string($atts))
			$atts = explode(' ', $atts, 2);

		$id = (int)array_shift($atts);
		$reservation = new reservation($id);
	}

	if (!$reservation) return '[parking-management-paypal 404 "Not Found"]';
	if (!array_key_exists('resaid', $post))
		return '';
	if ($site->paypal['actif'] == 0)
		return '';
	return $reservation->form_paypal(array(), $post['resaid'], $id);
}

function pkmgmt_management_home_form_tag_func($atts, $content = null, $code = '')
{
	if (is_feed())
		return '[parking-management-home-form]';

	if ('parking-management-home-form' == $code) {
		$atts = shortcode_atts(array(
			'id' => 0,
			'title' => '',
			'html_id' => '',
			'html_name' => '',
			'html_class' => '',
			'output' => 'form'), $atts);

		$id = (int)$atts['id'];
		$pk = new pkmgmt();
		wp_enqueue_style('pkmgmt-home-reservation-styles',
			$pk->pkmgmt_plugin_url('includes/css/home_reservation.css'));

		wp_enqueue_script('pkmgmt-home-form-script',
			$pk->pkmgmt_plugin_url('includes/js/reservation.js'),
			array('pkmgmt-utils-script'),
			null, true);
		$autovalid = "0";
		$indicatif = "fr";
		if ($site = pkmgmt_site($id)) {
			if ($site->info['gestion']['autovalid'] == 1) $autovalid = "1";
			if (array_key_exists('indicatif', $site->info) && isset($site->info['indicatif']))
				$indicatif = $site->info['indicatif'];
		}
		wp_localize_script('pkmgmt-home-form-script', 'ajax_object',
			array('ajax_url' => admin_url('admin-ajax.php'),
				'postid' => $id,
				'pkmgmt_includes_dir' => $pk->pkmgmt_plugin_url('includes'),
				'autovalid' => $autovalid,
				'indicatif' => $indicatif
			));
		$title = trim($atts['title']);
		if (!$reservation = new reservation($id))
			$reservation = pkmgmt_get_booking_form_by_title($title);

	} else {
		if (is_string($atts))
			$atts = explode(' ', $atts, 2);

		$id = (int)array_shift($atts);
		$reservation = new reservation($id);
	}

	if (!$reservation) return '[parking-management-home-form 404 "Not Found"]';
	return $reservation->form_home_html($atts);
}

function pkmgmt_management_form_tag_func($atts, $content = null, $code = '')
{
	if (is_feed())
		return '[parking-management]';


	if ('parking-management' == $code) {
		$atts = shortcode_atts(array(
			'id' => 0,
			'title' => '',
			'html_id' => '',
			'html_name' => '',
			'html_class' => '',
			'output' => 'form'), $atts);

		$id = (int)$atts['id'];
		$pk = new pkmgmt();
		wp_enqueue_script('pkmgmt-reservation-script',
			$pk->pkmgmt_plugin_url('includes/js/reservation.js'),
			array('pkmgmt-utils-script'),
			null, true);
		$autovalid = "0";
		$indicatif = "fr";
		if ($site = pkmgmt_site($id)) {
			if ($site->info['gestion']['autovalid'] == 1)
				$autovalid = "1";
			if (array_key_exists('indicatif', $site->info) && isset($site->info['indicatif']))
				$indicatif = $site->info['indicatif'];
		}
		wp_localize_script('pkmgmt-reservation-script', 'ajax_object',
			array('ajax_url' => admin_url('admin-ajax.php'),
				'pkmgmt_includes_dir' => $pk->pkmgmt_plugin_url('includes'),
				'autovalid' => $autovalid,
				'indicatif' => $indicatif
			));
		$title = trim($atts['title']);

		if (!$reservation = new reservation($id))
			$reservation = pkmgmt_get_booking_form_by_title($title);

	} else {
		if (is_string($atts))
			$atts = explode(' ', $atts, 2);

		$id = (int)array_shift($atts);
		$reservation = new reservation($id);
	}

	if (!$reservation) return '[parking-management 404 "Not Found"]';
	return $reservation->form_html($atts);
}

add_action('wp_enqueue_scripts', 'pkmgmt_do_enqueue_scripts');

function pkmgmt_do_enqueue_scripts()
{
	if (pkmgmt_load_js()) {
		pkmgmt_enqueue_scripts();
	}

	if (pkmgmt_load_css()) {
		pkmgmt_enqueue_styles();
	}
}

function pkmgmt_enqueue_scripts()
{

	$pk = new pkmgmt();
	//wp_enqueue_script( 'pkmgmt-jquery',
	//	$pk->pkmgmt_plugin_url( 'includes/js/jquery.min.js' ),array(),null,true);
	wp_enqueue_script('pkmgmt-jquery-ui',
		$pk->pkmgmt_plugin_url('includes/js/jquery-ui-1.13.2/jquery-ui.min.js'),
		array('jquery'), null, true);
	wp_enqueue_script('pkmgmt-timepicker-script',
		$pk->pkmgmt_plugin_url('includes/js/jquery-datetimepicker/jquery.datetimepicker.min.js'),
		array('pkmgmt-jquery-ui'), null, true);
	wp_enqueue_script('pkmgmt-timepicker-fr-script',
		$pk->pkmgmt_plugin_url('includes/js/jquery-ui-timepicker-fr.min.js'),
		array('pkmgmt-timepicker-script'), null, true);
	wp_enqueue_script('pkmgmt-validation-script',
		$pk->pkmgmt_plugin_url('includes/js/validate/jquery.validate.min.js'),
		array('pkmgmt-timepicker-fr-script'), null, true);
	wp_enqueue_script('pkmgmt-add-validation-script',
		$pk->pkmgmt_plugin_url('includes/js/validate/additional-methods.min.js'),
		array('pkmgmt-validation-script'), null, true);
	wp_enqueue_script('pkmgmt-moment-script',
		$pk->pkmgmt_plugin_url('includes/js/moment.min.js'),
		array('jquery'), null, true);
	wp_enqueue_script('pkmgmt-intltel-script',
		$pk->pkmgmt_plugin_url('includes/js/intlTelInput.min.js'),
		array('pkmgmt-validation-script'), null, true);
	wp_enqueue_script('pkmgmt-utils-script',
		$pk->pkmgmt_plugin_url('includes/js/utils.min.js'),
		array('pkmgmt-intltel-script'), null, true);
}

function pkmgmt_enqueue_styles()
{
	$pk = new pkmgmt();
	wp_enqueue_style('pkmgmt-home-reservation-styles',
		$pk->pkmgmt_plugin_url('includes/css/home_reservation.css'));
	wp_enqueue_style('pkmgmt-reservation-styles',
		$pk->pkmgmt_plugin_url('includes/css/styles.css'));
	wp_enqueue_style('pkmgmt-reservation-ui',
		$pk->pkmgmt_plugin_url('includes/js/jquery-ui-1.13.2/jquery-ui.min.css'));
	wp_enqueue_style('pkmgmt-reservation-ui-theme',
		$pk->pkmgmt_plugin_url('includes/js/jquery-ui-1.13.2/jquery-ui.theme.min.css'));
	wp_enqueue_style('pkmgmt-reservation-ui-structure',
		$pk->pkmgmt_plugin_url('includes/js/jquery-ui-1.13.2/jquery-ui.structure.min.css'));
	wp_enqueue_style('pkmgmt-reservation-timepicker',
		$pk->pkmgmt_plugin_url('includes/js/jquery-datetimepicker/jquery.datetimepicker.min.css'));
	wp_enqueue_style('pkmgmt-intltelinput-style',
		$pk->pkmgmt_plugin_url('includes/css/intlTelInput.css'));

}

function pkmgmt_get_booking_form_by_title($title)
{
	$page = get_page_by_title($title, OBJECT, pkmgmt_site::post_type);

	if ($page)
		return new pkmgmt_site($page->ID);

	return null;
}

if (!function_exists("print_log")) :
	function print_log($object, $out = true)
	{
		print "<pre>";
		print_r($object);
		print "</pre>";
		if ($out)
			exit(1);
	}
endif;


function pkmgmt_load_js()
{
	return apply_filters('pkmgmt_load_js', PKMGMT_LOAD_JS);
}

function pkmgmt_load_css()
{
	return apply_filters('pkmgmt_load_css', PKMGMT_LOAD_CSS);
}

function pkmgmt_site($id)
{
	$site = new pkmgmt_site($id);
	if ($site->initial)
		return false;

	return $site;
}

