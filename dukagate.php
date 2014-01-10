<?php
/*
Plugin Name: DukaGate Shopping Cart
Description: DukaGate Shopping Cart
Version: 3.5.2
Author: rixeo
Author URI: http://dukagate.info/
Plugin URI: http://dukagate.info/
*/

define('DG_PLUGIN_URL', WP_PLUGIN_URL.'/'.dirname(plugin_basename(__FILE__)));
define('DG_PLUGIN_DIR', WP_PLUGIN_DIR.'/'.dirname(plugin_basename(__FILE__)));

define('DG_DOWNLOAD_FILES_DIR', WP_CONTENT_DIR. '/uploads/dg_download_files/' );
define('DG_DOWNLOAD_FILES_DIR_TEMP', WP_CONTENT_DIR. '/uploads/dg_temp_download_files/' );

define('DG_DUKAGATE_URL', DG_PLUGIN_URL.'/dukagate');
define('DG_DUKAGATE_DIR', DG_PLUGIN_DIR.'/dukagate');


define('DG_DUKAGATE_INVOICE_URL', DG_PLUGIN_URL.'/invoice');
define('DG_DUKAGATE_INVOICE_DIR', DG_PLUGIN_DIR.'/invoice');

define('DG_DUKAGATE_WIDGET_DIR', DG_DUKAGATE_DIR.'/widgets/');

define('DG_GATEWAYS', DG_DUKAGATE_DIR.'/plugins-gateway/');
define('DG_GATEWAYS_URL', DG_DUKAGATE_URL.'/plugins-gateway/');
define('DG_SHIPPING', DG_DUKAGATE_DIR.'/plugins-shipping/');
define('DG_SHIPPING_URL', DG_DUKAGATE_URL.'/plugins-shipping/');
define('DG_ACTIVE_MERCHANT', DG_GATEWAYS.'libraries/aktive_merchant/');

define('DG_DUKAGATE_PDF_TEMPLATE_DIR', DG_DUKAGATE_DIR.'/pdf-templates/');

require_once(DG_DUKAGATE_DIR.'/dukagate-init.php');


//Initialise Dukagate
global $dukagate;
if(!isset($dukagate)){
	$dukagate = new DukaGate();
}

$dukagate->init();

//Set up File action
add_action('dg_daily_file_event', array($dukagate, 'delete_files_daily'));




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
		$links[] = '<a href="http://dukagate.info/forums/forum/bugs/" target="_blank">'.__('Bugs').'</a>';
		$links[] = '<a href="http://dukagate.info/contact/" target="_blank">'.__('Contact').'</a>';
   }
   return $links;
}

/**
 * This function shows Transaction Widget on Dashboard
 */
add_action('wp_dashboard_setup', 'dg_show_revenue_graph', 1);
function dg_show_revenue_graph() {
    wp_add_dashboard_widget( 'dg_revenue_widget_admin', __( 'Dukagate Revenue Graph' ), 'dg_revenue_graph' );
}

/**
 * Show revenue widget
 */
function dg_revenue_graph(){
	global $dukagate;
	$dg_shop_settings = get_option('dukagate_shop_settings');
	printf(__("Total %d orders sold with total amount of %s %d"),$dukagate->total_sales(),$dg_shop_settings['currency_symbol'],number_format($dukagate->total_revenue(),2));
	$payment_status = array('Pending', 'Paid', 'Canceled');
	$days = array();
	?>
	<canvas id="revenuedata"></canvas>
	<script type="text/javascript">
		var width = jQuery('#dg_revenue_widget_admin').width() - 20;
		var height = jQuery('#dg_revenue_widget_admin').width() - 200;
		var g = new Bluff.Line('revenuedata', width+'x'+height);
		g.title = 'Transactions Revenue';
		g.tooltips = true;
		g.theme_37signals();
		g.labels = {};
		<?php
		foreach($payment_status as $status){
			$results = $dukagate->sales_summary($status);
			if(!empty($results)){
				?>
				g.data("<?php echo $status; ?>", <?php echo str_replace('"', "", json_encode($results['total'])); ?>);
				<?php
				array_push($days,$results['days']);
			}
		}
		$days = array_unique($days);
		$i=0;
		foreach($days as $day){
			foreach($day as $d){
				?>
				g.labels[<?php echo $i; ?>] = '<?php echo $d; ?>';
				<?php
				$i++;
			}
		}
		?>
		g.draw();
	</script>
	<?php
	
}

#session_set_cookie_params(8*60*60); 
#session_save_path(DG_PLUGIN_DIR.'/temp'); 
#ini_set('session.gc_maxlifetime', '28800');
session_start();
?>