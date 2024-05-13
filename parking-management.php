<?php
/**
 * Parking management
 *
 * @package PKMGTPackage
 * @author David ALEXANDRE
 * @license GPL2 Licence
 *
 * @wordpress-plugin
 * Plugin Name: Parking management
 * Description: Plugin to manage park booking
 * Text Domain: pkmgmt
 * Domain Path: /languages
 * Version: 2.6.1
 * Author: David ALEXANDRE
 * License: GPL2 license
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( '_PKMGMT' ) )
{
	define( '_PKMGMT', 1 );
	define( 'PKMGMT_VERSION', '2.6.1' );
	define( 'PKMGMT_REQUIRED_WP_VERSION', '5' );
	define( 'DS', DIRECTORY_SEPARATOR );
	define( 'PKMGMT_PLUGIN', __FILE__ );
	define( 'PKMGMT_PLUGIN_BASENAME', plugin_basename( PKMGMT_PLUGIN ) );
	define( 'PKMGMT_PLUGIN_NAME', trim( dirname( PKMGMT_PLUGIN_BASENAME ), DS ) );
	define( 'PKMGMT_PLUGIN_DIR', untrailingslashit( dirname( PKMGMT_PLUGIN ) ) );
	define( 'PKMGMT_PLUGIN_URL', untrailingslashit( plugins_url( '', PKMGMT_PLUGIN ) ) );
	define( 'PKMGMT_PLUGIN_MODULES_DIR', PKMGMT_PLUGIN_DIR . DS . 'modules' );
	define( 'PKMGMT_PLUGIN_TCPDF_DIR', PKMGMT_PLUGIN_MODULES_DIR.DS."tcpdf");
	define( 'PKMGMT_PLUGIN_INCLUDES_DIR', PKMGMT_PLUGIN_DIR . DS . 'includes' );
	define( 'PKMGMT_LANGUAGES_DIR', PKMGMT_PLUGIN_DIR . DS . 'languages' );
	define( 'PKMGMT_PLUGIN_TEMPLATES', PKMGMT_PLUGIN_DIR.DS."templates");
	define( 'PKMGMT_LOAD_JS', true );
	define( 'PKMGMT_LOAD_CSS', true );
	define( 'PKMGMT_USE_PIPE', true );
	define( 'PKMGMT_ADMIN_READ_CAPABILITY', 'edit_dashboard' );
	define( 'PKMGMT_ADMIN_READ_WRITE_CAPABILITY', 'remove_users' );
	define( 'PKMGMT_ADMIN_REMOVE_USERS', 'remove_users');
	define( 'PKMGMT_VERIFY_NONCE', true );
}
require_once( PKMGMT_PLUGIN_DIR . DS . "settings.php" );

