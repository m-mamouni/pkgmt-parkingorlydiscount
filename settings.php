<?php
defined('_PKMGMT') or die('Restricted access');

require_once PKMGMT_PLUGIN_DIR . DS . "includes"	. DS . "pkmgmt.php";
require_once PKMGMT_PLUGIN_DIR . DS . "includes" 	. DS . "pkmgmt_site.php";

if ( is_admin() ) {
	require_once PKMGMT_PLUGIN_DIR . DS . "admin"  . DS . "admin.php";
	require_once PKMGMT_PLUGIN_DIR . DS . "includes"	. DS . "pkmgmt-list-table.php";
	new pkmgmt_admin();
}
else {
		require_once PKMGMT_PLUGIN_DIR . DS . "includes" . DS . "controller.php";
}
