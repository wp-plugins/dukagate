<?php
/*
Plugin Name: DukaGate Shopping Cart
Description: DukaGate Shopping Cart
Version: 3.7.4.1
Author: rixeo
Author URI: http://www.shumipress.com/
Plugin URI: http://dukagate.info/
Domain Path: /dukagate/lang/
*/

define('DG_PLUGIN_BASENAME',plugin_basename(__FILE__));

define('DG_PLUGIN_URL', WP_PLUGIN_URL.'/'.dirname(plugin_basename(__FILE__)));
define('DG_PLUGIN_DIR', WP_PLUGIN_DIR.'/'.dirname(plugin_basename(__FILE__)));


define('DG_DOWNLOAD_FILES_DIR', WP_CONTENT_DIR. '/uploads/dg_download_files/' );
define('DG_DOWNLOAD_FILES_DIR_TEMP', WP_CONTENT_DIR. '/uploads/dg_temp_download_files/' );

define('DG_DUKAGATE_URL', DG_PLUGIN_URL.'/dukagate');
define('DG_DUKAGATE_DIR', DG_PLUGIN_DIR.'/dukagate');

$dg_content_url = content_url();

define('DG_DUKAGATE_CONTENT_URL', content_url().'/dukagate');
define('DG_DUKAGATE_CONTENT_DIR', WP_CONTENT_DIR.'/dukagate');

define('DG_DUKAGATE_INVOICE_URL', DG_DUKAGATE_CONTENT_URL.'/invoice');
define('DG_DUKAGATE_INVOICE_DIR', DG_DUKAGATE_CONTENT_DIR.'/invoice');

define('DG_DUKAGATE_WIDGET_DIR', DG_DUKAGATE_DIR.'/widgets/');

define('DG_GATEWAYS', DG_DUKAGATE_DIR.'/plugins-gateway/');
define('DG_GATEWAYS_URL', DG_DUKAGATE_URL.'/plugins-gateway/');
define('DG_SHIPPING', DG_DUKAGATE_DIR.'/plugins-shipping/');
define('DG_SHIPPING_URL', DG_DUKAGATE_URL.'/plugins-shipping/');
define('DG_ACTIVE_MERCHANT', DG_GATEWAYS.'libraries/aktive_merchant/');

define('DG_DUKAGATE_PDF_TEMPLATE_DIR', DG_DUKAGATE_DIR.'/pdf-templates/');

require_once(DG_DUKAGATE_DIR.'/dukagate-init.php');

session_start();
?>