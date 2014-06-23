<?php

if(!class_exists('DukaGate_GateWay_API')) {
	class DukaGate_GateWay_API{
	
		//private gateway slug. Lowercase alpha (a-z) and dashes (-) only please!
		var $plugin_name = '';
		
		//shortname of plugin
		var $plugin_slug = '';
		
		//always contains the url to send payment notifications to if needed by your gateway. Populated by the parent class
		var $ipn_url;
		
		//Payment gateway required fields
		var $required_fields ='';
		
		//Determine if to use form submit or other method
		var $form_submit = true;
		
		//Do not overide
		function __construct() {
			global $dukagate;
			//run plugin construct
			$this->on_create();
			
			//check required vars
			if (empty($this->plugin_name) || empty($this->plugin_slug) || empty($this->required_fields))
				wp_die( __("You must override all required vars in your {$this->plugin_name} payment gateway plugin!") );
				
			$this->set_up_ipn_url();
			add_action( 'dg_payment_submit_' . $this->plugin_slug, array(&$this, 'process_payment_form'), 10, 2 ); //Payment process
			
			add_action( 'wp_ajax_nopriv_dg_handle_payment_return_'. $this->plugin_slug, array(&$this, 'process_ipn_return') );
			add_action( 'wp_ajax_dg_handle_payment_return_'. $this->plugin_slug, array(&$this, 'process_ipn_return') );
			
			add_action( 'dg_order_log_'. $this->plugin_slug , array(&$this, 'order_form_action'), 10, 1 );
			add_action( 'dg_gateway_option_'. $this->plugin_slug , array(&$this, 'set_up_options'), 10, 2 );
			$this->register();
		}
		
		
		//Default method called on create
		function on_create(){
		}
		
		//Register Plugin
		function register(){
			
		}
		
		//Sets Up IPN URL
		//Do not overide
		function set_up_ipn_url(){
			$this->ipn_url = get_option('siteurl').'/?dg_handle_payment_return_'.$this->plugin_slug.'=true';
		}
		
		//Get Plugin slug
		function get_plugin_slug(){
			return $this->plugin_slug;
		}
		/**
		 * Process IPN
		 */
		function process_ipn_return() {
			
		}
		
		/**
		 * Set Up Payment gateway options
		 */
		function set_up_options($plugin_slug, $settings){
		}

		
		/**
		 * Process Payment
		 */
		function process_payment_form($content, $invoice){
		}
		
		/**
		 * Action to be done on the order form
		 */
		function order_form_action($invoice){
		}
	}
}

/**
 * Use this function to register your gateway plugin class
 *
 * @param string $class_name - the case sensitive name of your plugin class
 * @param string $plugin_name - the sanitized private name for your plugin
 * @param string $plugin_slug - slug name of the plugin
 * @param bool $active optional - whether the gateway is active
 */
function dg_register_gateway_plugin($class_name, $plugin_name, $plugin_slug, $gateway_options, $currencies, $active = true) {
	global $dukagate;
	if (class_exists($class_name)) {
		$dukagate->dg_save_payment_gateway($plugin_name, $plugin_slug, $class_name, DukaGate::array_to_json($gateway_options), DukaGate::array_to_json($currencies),  $active);
	} else {
		return false;
	}
}
?>