<?php

/**
 * DukaGate Payment Gateway
 * WorldPay Gateway Plugin
 */
class DukaGate_GateWay_WorldPay extends DukaGate_GateWay_API{
	
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
	
	var $sandbox_url = 'https://secure-test.worldpay.com/wcc/purchase';
	var $post_url = 'https://secure.worldpay.com/wcc/purchase';
	
	function on_create(){
		$this->plugin_name = __('WorldPay');
		$this->plugin_slug = __('worldpay');
		$this->required_fields = array(
										'installation_id' => '',
										'sandbox' => '',
										'currency' => '');
		$this->currencies = array(
					'ARS' => 'ARS - Argentine Peso', 
					'AUD' => 'AUD - Australian Dollar', 
					'BRL' => 'BRL - Brazilian Real', 
					'CAD' => 'CAD - Canadian Dollar', 
					'CHF' => 'CHF - Swiss Franc', 
					'CLP' => 'CLP - Chilean Peso', 
					'CNY' => 'CNY - Chinese Yuan Renminbi', 
					'COP' => 'COP - Colombian Peso', 
					'CZK' => 'CZK - Czech Koruna', 
					'DKK' => 'DKK - Danish Krone', 
					'EUR' => 'EUR - Euro', 
					'GBP' => 'GBP - Pound Sterling', 
					'HKD' => 'HKD - Hong Kong Dollar', 
					'HUF' => 'HUF - Hungarian Forint', 
					'IDR' => 'IDR - Indonesian Rupiah', 
					'ISK' => 'ISK - Icelandic Krona', 
					'JPY' => 'JPY - Japanese Yen', 
					'KRW' => 'KRW - South Korean Won', 
					'MYR' => 'MYR - Malaysian Ringgits', 
					'NOK' => 'NOK - Norwegian Krone', 
					'NZD' => 'NZD - New Zealand Dollar', 
					'PLN' => 'PLN - Polish Zloty', 
					'PTE' => 'PTE - Portuguese Escudo', 
					'SEK' => 'SEK - Swedish Krona', 
					'SGD' => 'SGD - Singapore Dollar', 
					'SKK' => 'SKK - Slovak koruna', 
					'THB' => 'THB - Thai Baht', 
					'TWD' => 'TWD - Taiwan New Dollars', 
					'USD' => 'USD - U.S. Dollar', 
					'VND' => 'VND - Vietnamese Dong', 
					'ZAR' => 'ZAR - South African Rand'
	          );
	}
	
	//Register Plugin
	function register(){
		//Register Plugin
		dg_register_gateway_plugin('DukaGate_GateWay_WorldPay', $this->plugin_name, $this->plugin_slug, $this->required_fields, $this->currencies);
	}
	
	/**
	 * Set Up Payment gateway options
	 */
	function set_up_options($plugin_slug){
		global $dukagate;
		if(@$_POST[$plugin_slug]){
			$required_fields = array(
									'installation_id' => '',
									'sandbox' => '',
									'currency' => '');
			$required_fields['currency'] = $_POST[$plugin_slug.'_currency'];
			$required_fields['installation_id'] = $_POST[$plugin_slug.'_installation_id'];
			$required_fields['sandbox'] = $_POST[$plugin_slug.'_sandbox'];
			$enabled = ($_POST[$plugin_slug.'_enable'] == 'checked') ? 1 : 0;
			$dukagate->dg_save_gateway_options($plugin_slug ,DukaGate::array_to_json($required_fields), $enabled);
		}
		$options = DukaGate::json_to_array($dukagate->dg_get_gateway_options($plugin_slug));
		$currencies = DukaGate::json_to_array($dukagate->dg_get_gateway_currencies($plugin_slug));
		$enabled = $dukagate->dg_get_enabled_status($plugin_slug);
		?>
		<form method="POST" action="">
			<table class="form-table">
				<tr>
				    <th scope="row"><?php _e('WorldPay Settings') ?></th>
				    <td>
						<p>
							<label><?php _e('Test Mode') ?><br />
							  <input value="checked" name="<?php echo $plugin_slug; ?>_sandbox" type="checkbox" <?php echo ($options['sandbox'] == 'checked') ? "checked='checked'": ""; ?> />
							</label>
						</p>
				    </td>
				</tr>
				<tr>
				    <th scope="row"><?php _e('WorldPay Credentials') ?></th>
				    <td>
						<p>
							<label><?php _e('Installation ID') ?><br />
							  <input value="<?php echo $options['installation_id']; ?>" size="30" name="<?php echo $plugin_slug; ?>_installation_id" type="text" />
							</label>
						</p>
						<p>
							<label><?php _e('WorldPay Currency') ?><br />
								<select name="<?php echo $plugin_slug; ?>_currency">
									<?php
									$sel_currency = $options['currency'];
									foreach ($currencies as $k => $v) {
										echo '<option value="' . $k . '"' . ($k == $sel_currency ? ' selected' : '') . '>' . wp_specialchars($v, true) . '</option>' . "\n";
									}
									?>
								</select>
							</label>
						</p>
				    </td>
				</tr>
				<tr>
				    <th scope="row"><?php _e('Enable') ?></th>
				    <td>
						<p>
							<label><?php _e('Select To enable or disable') ?><br />
							  <input value="checked" name="<?php echo $plugin_slug; ?>_enable" type="checkbox" <?php echo (intval($enabled) == 1) ? "checked='checked'": ""; ?> />
							</label>
						</p>
						<p>
							<input type="submit" name="<?php echo $plugin_slug; ?>" value="<?php _e('Save Settings') ?>" />
						</p>
				    </td>
				</tr>
			</table>
		</form>
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
		$options = DukaGate::json_to_array($dukagate->dg_get_gateway_options($this->plugin_slug));
		
		$return_path = get_page_link($dg_shop_settings['thankyou_page']);
        $check_return_path = explode('?', $return_path);
        if (count($check_return_path) > 1) {
            $return_path .= '&id=' . $invoice_id;
        } else {
            $return_path .= '?id=' . $invoice_id;
        }
		$conversion_rate = 1;
        if ($shop_currency != $options['currency']) {
			$curr = new DG_CURRENCYCONVERTER();
            $conversion_rate = $curr->convert(1, $options['currency'], $shop_currency);
		}
		$total_tax = 0.00;
        $total_discount = 0.00;
        $total_shipping = 0.00;
		$total = 0.00;
		
		$fname = $cart['dg_firstname'];
		$lname = $cart['dg_lastname'];
		$email = $cart['dg_email'];
		$name = $fname . ' ' . $lname;
		$testModeVal = '0';
		//Set up return url
		$action_url = $this->post_url;
		if($options['sandbox'] == 'checked'){
			$action_url = $this->sandbox_url;
			$testModeVal = '100';
			$name = 'AUTHORISED';
		}
		
		$dg_shipping = $_SESSION['dg_shipping_total'];
		if(is_array($dg_shipping)){
			foreach ($dg_shipping as $shipping) {
				$total_shipping += $shipping;
			}
		}
		if(!empty($_SESSION['dg_discount_value'])){
			$total_discount = $_SESSION['dg_discount_value'];
		}else{
			if($cart['discount'] > 0){	
				$total_discount = $cart['discount'];
			}
		}	
		
		if (is_array($products) && count($products) > 0) {
			foreach ($products as $cart_items => $cart) {
				$total += $cart['total'];
			}
		}
		$installation_id = $options['installation_id'];
		$total_amount = ($total + $total_tax + $total_shipping - $total_discount) * $conversion_rate;
        $total_amount = number_format($total_amount, 2, '.', '');
        $lang = (strlen(WPLANG) > 0 ? substr(WPLANG, 0, 2) : 'en');
		$output = '<form name="dg_worldpay_form" id="dg_payment_form" action="' . $action_url . '" method="post">
					<input type="hidden" name="instId" value="' . $installation_id . '" />
					<input type="hidden" name="currency" value="' . $options['currency'] . '" />
					<input type="hidden" name="desc" value="Your Order No.: ' . $invoice_id . '" />
					<input type="hidden" name="cartId" value="101DGK0098" />
					<input type="hidden" name="amount" value="' . $total_amount . '" />
					<input type="hidden" name="testMode" value="' . $testModeVal . '" />
					<input type="hidden" name="name" value="' . $name . '" />
					<input type="hidden" name="address" value="" />
					<input type="hidden" name="postcode" value="" />
					<input type="hidden" name="country" value="" />
					<input type="hidden" name="tel" value="" />
					<input type="hidden" name="email" value="'.$email.'" />
					<input type="hidden" name="lang" value="' . $lang . '" />
					<input type="hidden" name="MC_invoice" value="' . $invoice_id . '" />
					<input type="hidden" name="MC_callback" value="' . $return_path . '" />
				</form>';
		return $output;
	}
	
	/**
	 * Process IPN
	 */
	function process_ipn_return() {
	
	}
}
?>