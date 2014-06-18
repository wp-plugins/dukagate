<?php
	
/**
 * DukaGate Payment Gateway
 * Cash On Delivery Gateway Plugin
 */
class DukaGate_GateWay_CashOnDelivery extends DukaGate_GateWay_API{

	//private gateway slug. Lowercase alpha (a-z) and dashes (-) only please!
	var $plugin_name;
	
	//shortname of plugin
	var $plugin_slug;
	
	//always contains the url to send payment notifications to if needed by your gateway. Populated by the parent class
	var $ipn_url;
	
	//Payment gateway required fields
	var $required_fields;
	
	//Currencies
	var $currencies;
	
	//Determine if to use form submit or other method
	var $form_submit = false;


	
	//Default method called on create
	function on_create(){
		$this->plugin_name = __('Cash On Delivery');
		$this->plugin_slug = __('cash_on_delivery');
		$this->required_fields = array('none' =>'');
		$this->currencies = array();
	}
	
	//Register Plugin
	function register(){
		//Register Plugin
		dg_register_gateway_plugin('DukaGate_GateWay_CashOnDelivery', $this->plugin_name, $this->plugin_slug, $this->required_fields, $this->currencies);
	}
	
	/**
	 * Process IPN
	 */
	function process_ipn_return() {
		global $dukagate;
		$dg_shop_settings = get_option('dukagate_shop_settings');
		$invoice = $_REQUEST['invoice'];
		$dukagate->dg_update_order_log($invoice, 'Pending');
		
		$return_path = get_page_link($dg_shop_settings['thankyou_page']);
		$check_return_path = explode('?', $return_path);
		if (count($check_return_path) > 1) {
			$return_path .= '&id=' . $invoice;
		} else {
			$return_path .= '?id=' . $invoice;
		}
		header("Location: $return_path");
	}
	
	/**
	 * Set Up Payment gateway options
	 */
	function set_up_options($plugin_slug, $settings){
		global $dukagate;
		$editor_id = 'dg['.$plugin_slug.'][instructions]';
		?>
		<table class="form-table">
			<tr>
				<th scope="row"><?php _e('Instructions','dukagate'); ?></th>
				<td>
					<p>
						<?php wp_editor( $settings[$plugin_slug]['instructions'], $editor_id , array( 'media_buttons' => false )); ?>
					</p>
				</td>
			</tr>
			
		</table>
		
		<?php
	}
	
	
	/**
	 * Process Payment
	 */
	function process_payment_form($cart){
		global $dukagate;
		
		$settings = get_option('dukagate_gateway_settings');
		
		$dg_shop_settings = get_option('dukagate_shop_settings');
		$invoice = $_SESSION['dg_invoice'];
		$dukagate->dg_update_order_log($invoice, 'Pending');
		
		$return_path = get_page_link($dg_shop_settings['thankyou_page']);
		$check_return_path = explode('?', $return_path);
		if (count($check_return_path) > 1) {
			$return_path .= '&id=' . $invoice;
		} else {
			$return_path .= '?id=' . $invoice;
		}
		
		$output = $settings[$this->plugin_slug]['instructions'];
		$output .= '<br/>';
		$output .= '<button class="bank_continue_btn" onclick="cash_continue();">Continue</button>';
		$output .= '<script type="text/javascript">';
        $output .= 'function cash_continue(){
					window.location.href="'.$return_path.'"
					};
                     </script>';
		return $output;
	}
}

?>