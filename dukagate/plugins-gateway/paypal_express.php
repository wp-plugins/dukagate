<?php
/**
 * DukaGate Payment Gateway
 * PayPal Express Gateway Plugin
 */

class DukaGate_GateWay_PayPalExpress extends DukaGate_GateWay_API{

	//private gateway slug. Lowercase alpha (a-z) and dashes (-) only please!
	var $plugin_name;
	
	//shortname of plugin
	var $plugin_slug;
	
	//always contains the url to send payment notifications to if needed by your gateway. Populated by the parent class
	var $ipn_url;
	
	//Payment gateway required fields
	var $required_fields;
	
	var $currencies;

	
	//Default method called on create
	function on_create(){
		$this->plugin_name = __('PayPal Express');
		$this->plugin_slug = __('paypal_express');
		$this->required_fields = array(
										'username' => '',
										'password' => '',
										'signature' => '',
										'email' => '',
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
		dg_register_gateway_plugin('DukaGate_GateWay_PayPalExpress', $this->plugin_name, $this->plugin_slug, $this->required_fields, $this->currencies);
	}
	
	
	/**
	 * Process IPN
	 */
	function process_ipn_return() {
		$options = DukaGate::json_to_array($dukagate->dg_get_gateway_options($this->plugin_slug));
		$gateway = new Merchant_Billing_PaypalExpress( array(
		  'login' => $options['username'],
		  'password' => $options['password'],
		  'signature' => $options['signature'],
		  'currency' => $options['currency']
		  )
		);
		$response = $gateway->get_details_for( $_GET['token'], $_GET['PayerID']);
		$response = $gateway->purchase($response->amount());
		if ( $response->success() ) {
			echo 'Success payment!';
		} else {
			echo $response->message();
		}
	}
	
	/**
	 * Set Up Payment gateway options
	 */
	function set_up_options($plugin_slug){
		global $dukagate;
		if(@$_POST[$plugin_slug]){
			$required_fields = array(
									'username' => '',
									'password' => '',
									'signature' => '',
									'email' => '',
									'currency' => '');
			$required_fields['currency'] = $_POST[$plugin_slug.'_currency'];
			$required_fields['username'] = $_POST[$plugin_slug.'_username'];
			$required_fields['password'] = $_POST[$plugin_slug.'_password'];
			$required_fields['signature'] = $_POST[$plugin_slug.'_signature'];
			$required_fields['email'] = $_POST[$plugin_slug.'_email'];
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
				    <th scope="row"><?php _e('PayPal Express Checkout Settings') ?></th>
				    <td>
						<span class="description"><?php _e('Express Checkout is PayPal\'s premier checkout solution, which streamlines the checkout process for buyers and keeps them on your site after making a purchase. Unlike PayPal Pro, there are no additional fees to use Express Checkout, though you may need to do a free upgrade to a business account. <a target="_blank" href="https://cms.paypal.com/us/cgi-bin/?&cmd=_render-content&content_ID=developer/e_howto_api_ECGettingStarted">More Info &raquo;</a>'); ?></span>
						<p>
							<label><?php _e('PayPal Merchant E-mail') ?><br />
							  <input value="<?php echo $options['email']; ?>" size="30" name="<?php echo $plugin_slug; ?>_email" type="text" />
							</label>
						</p>
				    </td>
				</tr>
				<tr>
				    <th scope="row"><?php _e('PayPal API Credentials') ?></th>
				    <td>
						<span class="description"><?php print _e('You must login to PayPal and create an API signature to get your credentials. <a target="_blank" href="https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_api_NVPAPIBasics#id084DN0AK0HS">Instructions &raquo;</a>'); ?></span>
						<p>
							<label><?php _e('API Username') ?><br />
							  <input value="<?php echo $options['username']; ?>" size="30" name="<?php echo $plugin_slug; ?>_username" type="text" />
							</label>
						</p>
						<p>
							<label><?php _e('API Password') ?><br />
							  <input value="<?php echo $options['password']; ?>" size="30" name="<?php echo $plugin_slug; ?>_password" type="text" />
							</label>
						</p>
						<p>
							<label><?php _e('Signature') ?><br />
							  <input value="<?php echo $options['signature']; ?>" size="30" name="<?php echo $plugin_slug; ?>_signature" type="text" />
							</label>
						</p>
				    </td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e('Paypal Currency') ?></th>
					<td>
						<select name="<?php echo $plugin_slug; ?>_currency">
							<?php
							$sel_currency = $options['currency'];
							foreach ($currencies as $k => $v) {
								echo '<option value="' . $k . '"' . ($k == $sel_currency ? ' selected' : '') . '>' . wp_specialchars($v, true) . '</option>' . "\n";
							}
							?>
						</select>
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
	function process_payment_form($content){
		global $dukagate;
		$options = DukaGate::json_to_array($dukagate->dg_get_gateway_options($content['dg_gateway_action']));
		$gateway = new Merchant_Billing_PaypalExpress( array(
		  'login' => $options['username'],
		  'password' => $options['password'],
		  'signature' => $options['signature'],
		  'currency' => $options['currency']
		  )
		);
		$dg_cart = $_SESSION['dg_cart'];
		$total = 0.00;
		if (is_array($dg_cart) && count($dg_cart) > 0) {
			foreach ($dg_cart as $cart_items => $cart) {
				$total += $cart['total'];
			}
		}
		$response = $gateway->setup_purchase($total, array(
				'return_url' => $this->ipn_url,
				'cancel_return_url' => $this->ipn_url,
			)
		);
		
		die ( header('Location: '. $gateway->url_for_token( $response->token() )) );
	}
	
}
?>