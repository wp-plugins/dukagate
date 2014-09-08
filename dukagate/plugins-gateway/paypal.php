<?php
	
/**
 * DukaGate Payment Gateway
 * PayPal Gateway Plugin
 */
class DukaGate_GateWay_PayPal extends DukaGate_GateWay_API{

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
	
	var $sandbox_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
	var $post_url = 'https://www.paypal.com/cgi-bin/webscr';

	
	//Default method called on create
	function on_create(){
		$this->plugin_name = __('PayPal');
		$this->plugin_slug = __('paypal');
		$this->required_fields = array(
										'sandbox' => '',
										'paypal_id' => '',
										'currency' => '');
		$this->currencies = array(
	              'AUD' => 'AUD - Australian Dollar',
	              'BRL' => 'BRL - Brazilian Real',
	              'CAD' => 'CAD - Canadian Dollar',
	              'CHF' => 'CHF - Swiss Franc',
	              'CZK' => 'CZK - Czech Koruna',
	              'DKK' => 'DKK - Danish Krone',
	              'EUR' => 'EUR - Euro',
	              'GBP' => 'GBP - Pound Sterling',
	              'ILS' => 'ILS - Israeli Shekel',
	              'HKD' => 'HKD - Hong Kong Dollar',
	              'HUF' => 'HUF - Hungarian Forint',
	              'JPY' => 'JPY - Japanese Yen',
	              'MYR' => 'MYR - Malaysian Ringgits',
	              'MXN' => 'MXN - Mexican Peso',
	              'NOK' => 'NOK - Norwegian Krone',
	              'NZD' => 'NZD - New Zealand Dollar',
	              'PHP' => 'PHP - Philippine Pesos',
	              'PLN' => 'PLN - Polish Zloty',
	              'SEK' => 'SEK - Swedish Krona',
	              'SGD' => 'SGD - Singapore Dollar',
	              'TWD' => 'TWD - Taiwan New Dollars',
	              'THB' => 'THB - Thai Baht',
	              'USD' => 'USD - U.S. Dollar'
	          );
	}
	
	//Register Plugin
	function register(){
		//Register Plugin
		dg_register_gateway_plugin('DukaGate_GateWay_PayPal', $this->plugin_name, $this->plugin_slug, $this->required_fields, $this->currencies);
	}
	
	/**
	 * Process IPN
	 */
	function process_ipn_return() {
		global $wpdb;
		global $dukagate;
		$dg_shop_settings = get_option('dukagate_shop_settings');
		$req = 'cmd=_notify-validate';
		foreach ($_POST as $key => $value) {
			$value = urlencode(stripslashes($value));
			$req .= "&$key=$value";
		}
		$settings = get_option('dukagate_gateway_settings');
		//Set up return url
		$dg_form_action = $this->post_url;
		if($settings[$this->plugin_slug]['sandbox'] == 'checked'){
			$dg_form_action = $this->sandbox_url;
		}
		$ch = curl_init($dg_form_action);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
		$result = curl_exec($ch);
		curl_close($ch);
		
		if (strcmp($result, "VERIFIED") == 0) {
			global $dukagate;
			$invoice = $_REQUEST['invoice'];
			$tx_id = $_REQUEST['txn_id'];
			$payer_email = $_REQUEST['payer_email'];
			$payment_status = $_REQUEST['payment_status'];
			$updated_status = '';
			switch ($payment_status) {
				case 'Processed':
					$updated_status = 'Paid';
					break;
				case 'Completed':
					$updated_status = 'Paid';
					break;
				case 'Pending':
					$updated_status = 'Pending';
					break;
				default:
					$updated_status = 'Canceled';
					break;
			}
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
				    <th scope="row"><?php _e('PayPal Settings') ?></th>
				    <td>
						<p>
							<label><?php _e('Use PayPal Sandbox') ?><br />
							  <input value="checked" name="dg[<?php echo $plugin_slug; ?>][sandbox]" type="checkbox" <?php echo ($settings[$plugin_slug]['sandbox'] == 'checked') ? "checked='checked'": ""; ?> />
							</label>
						</p>
				    </td>
				</tr>
				
				<tr>
				    <th scope="row"><?php _e('PayPal Credentials') ?></th>
				    <td>
						<p>
							<label><?php _e('Custom Checkout Name'); ?><br />
							  <input value="<?php echo $settings[$plugin_slug]['custom_name']; ?>" size="30" name="dg[<?php echo $plugin_slug; ?>][custom_name]" type="text" />
							</label>
						</p>
						<p>
							<label><?php _e('PayPal ID') ?><br />
							  <input value="<?php echo $settings[$plugin_slug]['paypal_id']; ?>" size="30" name="dg[<?php echo $plugin_slug; ?>][paypal_id]" type="text" />
							</label>
						</p>
						<p>
							<label><?php _e('PayPal Currency') ?><br />
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
	function process_payment_form($cart){
		global $dukagate;
		$invoice_id = $_SESSION['dg_invoice'];
		$dg_cart = $_SESSION['dg_cart'];
		$dg_shop_settings = get_option('dukagate_shop_settings');
		$shop_currency = $dg_shop_settings['currency'];
		
		$settings = get_option('dukagate_gateway_settings');
		
		$return_path = get_page_link($dg_shop_settings['thankyou_page']);
        $check_return_path = explode('?', $cancel_path);
        if (count($check_return_path) > 1) {
            $cancel_path .= '&id=' . $invoice_id;
        } else {
            $cancel_path .= '?id=' . $invoice_id;
        }
		$conversion_rate = 1;
        if ($shop_currency != $settings[$this->plugin_slug]['currency']) {
			$curr = new DG_CURRENCYCONVERTER();
            $conversion_rate = $curr->convert(1, $settings[$this->plugin_slug]['currency'], $shop_currency);
		}
		
		//Set up return url
		$action_url = $this->post_url;
		if($settings[$this->plugin_slug]['sandbox'] == 'checked'){
			$action_url = $this->sandbox_url;
		}
		$output = '<form name="dg_paypal_form" id="dg_payment_form" action="' . $action_url . '" method="post">';
        $output .= '<input type="hidden" name="return" value="' . $return_path . '"/>
                     <input type="hidden" name="cmd" value="_ext-enter" />
                     <input type="hidden" name="notify_url" value="' . $this->ipn_url . '"/>
                     <input type="hidden" name="redirect_cmd" value="_cart" />
                     <input type="hidden" name="business" value="' . $settings[$this->plugin_slug]['paypal_id'] . '"/>
                     <input type="hidden" name="cancel_return" value="' . $return_path . '&status=cancel"/>
                     <input type="hidden" name="rm" value="2" />
                     <input type="hidden" name="upload" value="1" />
                     <input type="hidden" name="currency_code" value="' . $settings[$this->plugin_slug]['currency'] . '"/>
                     <input type="hidden" name="no_note" value="1" />
                     <input type="hidden" name="invoice" value="' . $invoice_id . '">';
					 
		$count_product = 1;
        $tax_rate = 0;
		$total_shipping = 0.00;
		$dg_shipping = $_SESSION['dg_shipping_total'];
		if(is_array($dg_shipping)){
			foreach ($dg_shipping as $shipping) {
				$total_shipping += $shipping;
			}
		}
		$amount = $amount + $total_shipping;
		foreach ($dg_cart as $cart_items => $cart) {
			$output .= '<input type="hidden" name="item_name_' . $count_product . '" value="' . $cart['product']. '"/>
                             <input type="hidden" name="amount_' . $count_product . '" value="' . number_format($conversion_rate * $cart['price'], 2) . '"/>
                             <input type="hidden" name="quantity_' . $count_product . '" value="' . $cart['quantity'] . '"/>
                             <input type="hidden" name="item_number_' . $count_product . '" value="' . $count_product. '"/>
                             <input type="hidden" name="tax_rate_' . $count_product . '" value="' . $tax_rate . '"/>';
			
			if(!empty($_SESSION['dg_discount_value'])){
				$output .= '<input type="hidden" name="discount_rate_' . $count_product . '" value="' .$_SESSION['dg_discount_value']. '" />';
			}else{
				if($cart['discount'] > 0){	
					$output .= '<input type="hidden" name="discount_rate_' . $count_product . '" value="' . $cart['discount'] . '" />';
				}
			}	
			if(!empty($dg_shop_settings['tax_rate'])){
				$output .= '<input type="hidden" name="tax_rate_' . $count_product . '" value="' . $dg_shop_settings['tax_rate'] . '" />';
			}
			$count_product++;
		}
		$output .= '<input type="hidden" name="handling_cart" value="' . number_format($total_shipping, 2) . '"/></form>';
		return $output;
	}
}

?>