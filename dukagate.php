<?php
/*
Plugin Name: DukaGate Shopping Cart
Description: DukaGate Shopping Cart
Version: 3.4
Author: TheBunch
Author URI: http://dukagate.info/
Plugin URI: http://dukagate.info/
*/

define('DG_PLUGIN_URL', WP_PLUGIN_URL.'/'.dirname(plugin_basename(__FILE__)));
define('DG_PLUGIN_DIR', WP_PLUGIN_DIR.'/'.dirname(plugin_basename(__FILE__)));

define('DG_DOWNLOAD_FILES_DIR', WP_CONTENT_DIR. '/uploads/dg_download_files/' );
define('DG_DOWNLOAD_FILES_DIR_TEMP', WP_CONTENT_DIR. '/uploads/dg_temp_download_files/' );

define('DG_DUKAGATE_URL', DG_PLUGIN_URL.'/dukagate');
define('DG_DUKAGATE_DIR', DG_PLUGIN_DIR.'/dukagate');

define('DG_DUKAGATE_WIDGET_DIR', DG_DUKAGATE_DIR.'/widgets/');

define('DG_GATEWAYS', DG_DUKAGATE_DIR.'/plugins-gateway/');
define('DG_GATEWAYS_URL', DG_DUKAGATE_URL.'/plugins-gateway/');
define('DG_SHIPPING', DG_DUKAGATE_DIR.'/plugins-shipping/');
define('DG_SHIPPING_URL', DG_DUKAGATE_URL.'/plugins-shipping/');
define('DG_ACTIVE_MERCHANT', DG_GATEWAYS.'libraries/aktive_merchant/');


require_once(DG_DUKAGATE_DIR.'/dukagate-init.php');


//Initialise Dukagate
global $dukagate;
if(!isset($dukagate)){
	$dukagate = new DukaGate();
}

//Set up File action
add_action('dg_daily_file_event', array($dukagate, 'delete_files_daily'));




register_activation_hook(__FILE__,array($dukagate,'init'));
register_deactivation_hook(__FILE__,array($dukagate,'destroy'));

$dukagate->set_up_directories_and_file_info();
//Set up shop
$dukagate->set_up();


/**
 * This function adds links to the plugin page
 */
add_filter('plugin_row_meta', 'dg_register_links',10,2);
function dg_register_links($links, $file) {
   $base = plugin_basename(__FILE__);
   if ($file == $base) {
		$links[] = '<a href="options-general.php?page=dukagate-settings">'.__('Settings').'</a>';
		$links[] = '<a href="http://dukagate.info/faq/" target="_blank">'.__('FAQ').'</a>';
		$links[] = '<a href="http://dukagate.info/documentation/" target="_blank">'.__('Documentation').'</a>';
   }
   return $links;
}


#session_set_cookie_params(8*60*60); 
#session_save_path(DG_PLUGIN_DIR.'/temp'); 
#ini_set('session.gc_maxlifetime', '28800');
session_start();
?>