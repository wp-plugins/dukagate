<?php

/**
 * DukaGate Payment Gateway
 * Authorize.Net Gateway Plugin
 */
class DukaGate_GateWay_AuthorizeNet extends DukaGate_GateWay_API{

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
	var $form_submit = true;
	
	var $sandbox_url = 'https://test.authorize.net/gateway/transact.dll';
	var $post_url = 'https://secure.authorize.net/gateway/transact.dll';
	
	//Default method called on create
	function on_create(){
		$this->plugin_name = __('Authorize.net');
		$this->plugin_slug = __('authorizenet');
		$this->required_fields = array(
										'sandbox' => '',
										'authorize_api' => '',
										'authorize_transaction_key' => '',
										'currency' => '');
		$this->currencies = array(
	              'USD' => 'USD - U.S. Dollar'
	          );
	}
	
	//Register Plugin
	function register(){
		//Register Plugin
		dg_register_gateway_plugin('DukaGate_GateWay_AuthorizeNet', $this->plugin_name, $this->plugin_slug, $this->required_fields, $this->currencies);
	}
	
	/**
	 * Process IPN
	 */
	function process_ipn_return() {
		global $wpdb;
		global $dukagate;
		$payment_status = intval($_POST['x_response_code']);
		$invoice = $_POST['x_invoice_num'];
		$payer_email = $_POST['x_email'];
		
		switch ($payment_status) {
			case 1:
				$updated_status = 'Paid';
				break;
			case 2:
				$updated_status = 'Canceled';
				break;
			case 3:
				$updated_status = 'Canceled';
				break;
			case 4:
				$updated_status = 'Pending';
				break;
			default:
				$updated_status = 'Canceled';
				break;
		}
	
		$dg_shop_settings = get_option('dukagate_shop_settings');

		$dukagate->dg_update_order_log($invoice, $updated_status);
		
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
		$currencies = DukaGate::json_to_array($dukagate->dg_get_gateway_currencies($plugin_slug));
		?>
		<table class="form-table">
			<tr>
				<th scope="row"><?php _e('Authorize.Net Settings', 'dukagate') ?></th>
				<td>
					<p>
						<label><?php _e('Use Test Server'); ?><br />
						  <input value="checked" name="dg[<?php echo $plugin_slug; ?>][sandbox]" type="checkbox" <?php echo ($settings[$plugin_slug]['sandbox'] == 'checked') ? "checked='checked'": ""; ?> />
						</label>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e('Authorize.Net Credentials', 'dukagate'); ?></th>
				<td>
					<p>
						<label><?php _e('API Login', 'dukagate'); ?><br />
						  <input value="<?php echo $settings[$plugin_slug]['authorize_api']; ?>" size="30" name="dg[<?php echo $plugin_slug; ?>][authorize_api]" type="text" />
						</label>
					</p>
					<p>
						<label><?php _e('Transaction Key', 'dukagate'); ?><br />
						  <input value="<?php echo $settings[$plugin_slug]['authorize_transaction_key']; ?>" size="30" name="dg[<?php echo $plugin_slug; ?>][authorize_transaction_key]" type="text" />
						</label>
					</p>
					<p>
						<label><?php _e('Currency', 'dukagate'); ?><br />
							<select name="dg[<?php echo $plugin_slug; ?>][currency]">
								<?php
								$sel_currency = $settings[$plugin_slug]['currency'];
								foreach ($currencies as $k => $v) {
									echo '<option value="' . $k . '"' . ($k == $sel_currency ? ' selected' : '') . '>' . wp_specialchars($v, true) . '</option>' . "\n";
								}
								?>
							</select>
						</label>
					</p>
				</td>
			</tr>
			
		</table>
		<?php
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
		
		$settings = get_option('dukagate_gateway_settings');
		
		
		
		$return_path = get_page_link($dg_shop_settings['thankyou_page']);
        $check_return_path = explode('?', $return_path);
        if (count($check_return_path) > 1) {
            $return_path .= '&id=' . $invoice_id;
        } else {
            $return_path .= '?id=' . $invoice_id;
        }
		$conversion_rate = 1;
		$currency = $settings[$this->plugin_slug]['currency'];
        if ($shop_currency != $currency) {
			$curr = new DG_CURRENCYCONVERTER();
            $conversion_rate = $curr->convert(1, $currency, $shop_currency);
		}
		
		//Set up return url
		$action_url = $this->post_url;
		if($settings[$this->plugin_slug]['sandbox'] == 'checked'){
			$action_url = $this->sandbox_url;
		}
		$sequence = rand(1, 1000);
        $timeStamp = time();
		$dg_total = 0;
		foreach ($dg_cart as $cart_items => $cart) {
			$price = number_format($conversion_rate * $cart['price'], 2);
			if(!empty($_SESSION['dg_discount_value'])){
				$price - $_SESSION['dg_discount_value'];
			}else{
				if($cart['discount'] > 0){	
					$price - $cart['discount'];
				}
			}
			$dg_total +=  $price * $cart['quantity'];
		}
		if (phpversion() >= '5.1.2') {
            $fingerprint = hash_hmac("md5", $settings[$this->plugin_slug]['authorize_api'] . "^" . $sequence . "^" . $timeStamp . "^" . $dg_total . "^", $settings[$this->plugin_slug]['authorize_transaction_key']);
        } else {
            $fingerprint = bin2hex(mhash(MHASH_MD5, $settings[$this->plugin_slug]['authorize_api'] . "^" . $sequence . "^" . $timeStamp . "^" . $dg_total . "^", $settings[$this->plugin_slug]['authorize_transaction_key']));
        }
		if(!empty($dg_shop_settings['tax_rate'])){
			$tax_rate = $dg_shop_settings['tax'];
            $total_tax = $dg_total * $tax_rate / 100;
			$dg_total = $dg_total + $total_tax;
		}
		$output .= '<form name="dg_authorize_form" id="dg_payment_form" action="' . $action_url . '" method="post">';
        $output .= '<input type="hidden" name="x_login" value="' . $settings[$this->plugin_slug]['authorize_api'] . '" />';
        $output .= '<input type="hidden" name="x_version" value="3.1" />';
        $output .= '<input type="hidden" name="x_method" value="CC" />';
        $output .= '<input type="hidden" name="x_type" value="AUTH_CAPTURE" />';
        $output .= '<input type="hidden" name="x_amount" value="' . $dg_total . '" />';
        $output .= '<input type="hidden" name="x_description" value="Your Order No.: ' . $invoice_id . '" />';
        $output .= '<input type="hidden" name="x_invoice_num" value="' . $invoice_id . '" />';
        $output .= '<input type="hidden" name="x_fp_sequence" value="' . $sequence . '" />';
        $output .= '<input type="hidden" name="x_fp_timestamp" value="' . $timeStamp . '" />';
        $output .= '<input type="hidden" name="x_fp_hash" value="' . $fingerprint . '" />';
        $output .= '<input type="hidden" name="x_show_form" value="PAYMENT_FORM" />';
        $output .= '<input type="hidden" name="x_relay_response" value="TRUE" />';
        $output .= '<input type="hidden" name="x_receipt_link_method" value="LINK" />';
        $output .= '<input type="hidden" name="x_receipt_link_text" value="Back to Shop" />';
        $output .= '<input type="hidden" name="x_receipt_link_URL" value="' . $return_path . '" />';
        $output .= '</form>';
		
		return $output;
	}
}
?>