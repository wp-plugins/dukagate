<?php

/**
 * DukaGate Payment Gateway
 * KopoKopo Gateway Plugin
 * http://kopokopo.com/
 */
class DukaGate_GateWay_KopoKopo extends DukaGate_GateWay_API{
	
	//private gateway slug. Lowercase alpha (a-z) and dashes (-) only please!
	var $plugin_name;
	
	//shortname of plugin
	var $plugin_slug;
	
	//Payment gateway required fields
	var $required_fields;
	
	var $script_url = 'https://app.kopokopo.com/javascripts/k2_ecomwidgetcors.js';
	
	//Determine if to use form submit or other method
	var $form_submit = false;
	
	//Currencies
	var $currencies;
	
	//Default method called on create
	function on_create(){
		
		
		$this->plugin_name = __('KopoKopo (Beta)');
		$this->plugin_slug = __('kopokopo');
		$this->required_fields = array(
										'subdomain' => '',
										'authkey' => '');
										
		add_action( 'wp_ajax_nopriv_dg_kopokopo', array(&$this, 'process_ipn_return'));
		add_action( 'wp_ajax_dg_kopokopo', array(&$this, 'process_ipn_return'));
								
	}
	
	//Register Plugin
	function register(){
		//Register Plugin
		dg_register_gateway_plugin('DukaGate_GateWay_KopoKopo', $this->plugin_name, $this->plugin_slug, $this->required_fields, $this->currencies, false);
	}
	
	/**
	 * Set Up Payment gateway options
	 */
	function set_up_options($plugin_slug){
		global $dukagate;
		if(@$_POST[$plugin_slug]){
			$required_fields = array(
									'subdomain' => '',
									'authkey' => '');
			$required_fields['subdomain'] = $_POST[$plugin_slug.'_subdomain'];
			$required_fields['authkey'] = $_POST[$plugin_slug.'_authkey'];
			$enabled = (@$_POST[$plugin_slug.'_enable'] == 'checked') ? 1 : 0;
			$dukagate->dg_save_gateway_options($plugin_slug ,DukaGate::array_to_json($required_fields), $enabled);
		}
		if(@$_GET['k_test']){
			$test_result = DukaGate::call_class_function('DukaGate_GateWay_KopoKopo', 'connection_test', '');
			if($test_result){
				?>
				<div id="message" class="updated fade">
					<h3><?php _e('Successfully connected to KopoKopo','dukagate'); ?></h3>
				</div>
				<?php
			}else{
				?>
				<div id="message" class="error fade">
					<h3><?php _e('There was an error connecting to KopoKopo. Please check your credentials and try again','dukagate'); ?></h3>
				</div>
				<?php
			}
		}
		$options = DukaGate::json_to_array($dukagate->dg_get_gateway_options($plugin_slug));
		$enabled = $dukagate->dg_get_enabled_status($plugin_slug);
		?>
		<form method="POST" action="">
			<table class="form-table">
				<tr>
				    <th scope="row"><?php _e('KopoKopo Checkout','dukagate') ?></th>
				    <td>
						<p>
							<?php echo sprintf( __('The KopoKopo API Credentials are available on your <a href="%1$s" target="_blank">Merchant account</a> . After you have configured and saved your settings, use the <a href="%2$s" class="inboxact_test">test</a> to make sure the Merchant account works'), 'https://app.kopokopo.com/', admin_url('edit.php?post_type=dg_product&page=dukagate-settings&tab=payment&k_test=t')); ?> 
						</p>
							
						<p>
							<label><?php _e('Test Settings','dukagate') ?><br />
							  
							</label>
						</p>
				    </td>
				</tr>
				<tr>
				    <th scope="row"><?php _e('Credentials','dukagate'); ?></th>
				    <td>
						<p>
							<label><?php _e('Sub Domain','dukagate'); ?><br />
							  <input value="<?php echo $options['subdomain']; ?>" size="30" name="<?php echo $plugin_slug; ?>_subdomain" type="text" />
							</label>
						</p>
						
						<p>
							<label><?php _e('Auth Key','dukagate') ?><br />
							  <input value="<?php echo $options['authkey']; ?>" size="30" name="<?php echo $plugin_slug; ?>_authkey" type="text" />
							</label>
						</p>
						
				    </td>
				</tr>
				<tr>
				    <th scope="row"><?php _e('Enable','dukagate') ?></th>
				    <td>
						<p>
							<label><?php _e('Select To enable or disable','dukagate') ?><br />
							  <input value="checked" name="<?php echo $plugin_slug; ?>_enable" type="checkbox" <?php echo (intval($enabled) == 1) ? "checked='checked'": ""; ?> />
							</label>
						</p>
						<p>
							<input type="submit" name="<?php echo $plugin_slug; ?>" value="<?php _e('Save Settings','dukagate') ?>" />
						</p>
				    </td>
				</tr>
			</table>
		</form>
		<?php
	}
	
	/**
	 * We test and make sure the connection is fine
	 *
	 */
	function connection_test(){
		global $dukagate;
		$options = DukaGate::json_to_array($dukagate->dg_get_gateway_options('kopokopo'));
		$subdomain = $options['subdomain'];
		$authkey = $options['authkey'];
		$url = 'https://app.kopokopo.com/javascripts/k2_ecomwidgetcors.js?subdomain='.$subdomain.'&authkey='.$authkey;
		$hCURL = curl_init();
		$response = "";
		if($hCURL){
			curl_setopt( $hCURL, CURLOPT_HEADER, false);
			curl_setopt( $hCURL, CURLOPT_RETURNTRANSFER, true);
			curl_setopt( $hCURL, CURLOPT_TIMEOUT, 30 );
			curl_setopt( $hCURL, CURLOPT_URL, $url);
			curl_setopt( $hCURL, CURLOPT_SSL_VERIFYPEER, false);
			$response = curl_exec($hCURL);
			curl_close($hCURL);
		}
		if (!empty($response)) {
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * Process Payment
	 */
	function process_payment_form($content){
		global $dukagate;
		$invoice_id = $_SESSION['dg_invoice'];
		$dg_cart = $_SESSION['dg_cart'];
		$dg_shop_settings = get_option('dukagate_shop_settings');
		$shop_currency = $dg_shop_settings['currency'];
		$options = DukaGate::json_to_array($dukagate->dg_get_gateway_options($this->plugin_slug));
		
		$return_path = get_page_link($dg_shop_settings['thankyou_page']);
        $check_return_path = explode('?', $return_path);
        if (count($check_return_path) > 1) {
            $return_path .= '&id=' . $invoice_id;
        } else {
            $return_path .= '?id=' . $invoice_id;
        }
		$amount = 0.0;
		foreach ($dg_cart as $cart_items => $cart) {	
			$amount += $cart['total'];
		}
		
		$total_shipping = 0.00;
		$dg_shipping = $_SESSION['dg_shipping_total'];
		if(is_array($dg_shipping)){
			foreach ($dg_shipping as $shipping) {
				$total_shipping += $shipping;
			}
		}
		$amount = $amount + $total_shipping;
		if(isset($_SESSION['dg_discount_value'])){
			$total_discount = $_SESSION['dg_discount_value'];
			$total_discount = floatval(($total_discount * $amount)/100);
			$amount = $amount - $total_discount;
		}
		
		$subdomain = $options['subdomain'];
		$authkey = $options['authkey'];
		
		$output = '<script src="'.DG_GATEWAYS_URL.'/libraries/kopokopo.js"></script>';
		$output .= '<script type="text/javascript">';
		$output .= 'function transactionCheckCallback(r){';
		$output .= 'dg_kopokopo.process(r, "'.esc_url($return_path).'", "'.$invoice_id.'");';
		$output .= '}';
		$output .= '</script>';
		$output .= '<script src="'.$this->script_url.'?subdomain='.$subdomain.'&authkey='.$authkey.'"></script>';
		return $output;
	}
	
	/**
	 * Process IPN
	 */
	function process_ipn_return() {
		$status = $_POST['status'];
		$invoice = $_POST['invoice'];
		switch ($status) {
			case '01':
				$updated_status = 'Paid';
				break;
			default:
				$updated_status = 'Canceled';
				break;
		}
		$dukagate->dg_update_order_log($invoice, $updated_status);
	}
}
?>