<?php
if(!class_exists('DukaGate_Shipping_API')) {
	class DukaGate_Shipping_API{
		//private shipping method name. Lowercase alpha (a-z) and dashes (-) only please!
		var $plugin_name = '';
		
		//public name of your method, for lists and such.
		var $public_name = '';
		
		 //set to true if you need to use the shipping_metabox() method to add per-product shipping options
		var $use_metabox = false;
		
		//set to true to show before payment form
		var $before_payment = false;
		
		var $shipping_info = '';
		
		//Do not overide
		function __construct() {
			global $dukagate;
			//run plugin construct
			$this->on_create();
			if ($this->use_metabox) {
				
			}
			$dg_sp_gw = $dukagate->dg_get_shipping_gateway($this->plugin_slug);
			if(empty($dg_sp_gw)){
				$this->register();
			}
			$this->register();
			$this->add_column();
		}
		
		private function add_column(){
			$table_update = get_option('shipping_db_install');
			if(!isset($table_update) || empty($table_update)){
				global $wpdb;
				$databases = DukaGate::db_names();
				$table_name = $databases['transactions'];
				$alter_sql = "ALTER TABLE `$table_name` ADD `shipping_info` LONGTEXT NULL;";
				$wpdb->query($alter_sql);
				$alter_sql = "ALTER TABLE `$table_name` ADD `shipping` FLOAT NOT NULL;";
				$wpdb->query($alter_sql);
				update_option('shipping_db_install', 'shipping_install');
			}
		}
		
		/**
		 * Runs when your class is instantiated. Use to setup your plugin instead of __construct()
		 */
		function on_create() {
		
		}
		
		//Register Plugin
		function register(){
			
		}
		
		/**
		 * Echo shipping form to show to user
		 */
		function shipping_form() {

		}
		
		/**
		 * Admin options
		 */
		function set_up_options($plugin_slug){
		
		}
		
		
		//Save Shipping rates
		function dg_save_shipping_settings(){
			
		}
			
		/**
		 * Echo a settings meta box with whatever settings you need for you shipping module.
		 *  Form field names should be prefixed with mp[shipping][plugin_name], like "mp[shipping][plugin_name][mysetting]".
		 *  You can access saved settings via $settings array.
		 */
		function shipping_settings_box($settings) {

		}
		

		/**
		 * Save any per-product shipping fields from the shipping metabox using update_post_meta
		 *
		 * @param array|string $shipping_meta, save anything from the $_POST global
		 * return array|string $shipping_meta
		 */
		function save_shipping_metabox($shipping_meta) {
			
		  return $shipping_meta;
		}
		
		/**
		 * Use this function to return your calculated price as an integer or float
		 *
		 * @param int $price, always 0. Modify this and return
		 * @param float $total, cart total after any coupons and before tax
		 * @param array $cart, the contents of the shopping cart for advanced calculations
		 * @param string $address1
		 * @param string $address2
		 * @param string $city
		 * @param string $state, state/province/region
		 * @param string $zip, postal code
		 * @param string $country, ISO 3166-1 alpha-2 country code
		 *
		 * return float $price
		 */
		function calculate_shipping() {
		  //it is required to override this method
		  wp_die( __("You must override the calculate_shipping() method in your {$this->public_name} shipping plugin!", 'mp') );
		}
	}
}


/**
 * Use this function to register your gateway plugin class
 *
 * @param string $class_name - the case sensitive name of your plugin class
 * @param string $plugin_name - the sanitized private name for your plugin
 * @param string $plugin_slug - slug name of the plugin
 * @param string $shipping_info - Shipping information
 * @param bool $active optional - whether the gateway is active
 */
function dg_register_shipping_plugin($class_name, $plugin_name, $plugin_slug, $shipping_info, $active = true) {
	global $dukagate;
	if (class_exists($class_name)) {
		$dukagate->dg_save_shipping_gateway($plugin_name, $plugin_slug, $class_name, $shipping_info, $active);
	} else {
		return false;
	}
}
?>