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
	
	}
	
	/**
	 * Set Up Payment gateway options
	 */
	function set_up_options($plugin_slug){
		global $dukagate;
		if(@$_POST[$plugin_slug]){
			$required_fields = array(
									'sandbox' => '',
									'authorize_api' => '',
									'authorize_transaction_key' => '',
									'currency' => '');
									
			$required_fields['authorize_transaction_key'] = $_POST[$plugin_slug.'_transaction_key'];
			$required_fields['authorize_api'] = $_POST[$plugin_slug.'_api'];
			$required_fields['sandbox'] = $_POST[$plugin_slug.'_sandbox'];
			$required_fields['currency'] = $_POST[$plugin_slug.'_currency'];
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
				    <th scope="row"><?php _e('Authorize.Net Settings') ?></th>
				    <td>
						<p>
							<label><?php _e('Use Test Server') ?><br />
							  <input value="checked" name="<?php echo $plugin_slug; ?>_sandbox" type="checkbox" <?php echo ($options['sandbox'] == 'checked') ? "checked='checked'": ""; ?> />
							</label>
						</p>
				    </td>
				</tr>
				<tr>
				    <th scope="row"><?php _e('Authorize.Net Credentials') ?></th>
				    <td>
						<p>
							<label><?php _e('API Login') ?><br />
							  <input value="<?php echo $options['authorize_api']; ?>" size="30" name="<?php echo $plugin_slug; ?>_api" type="text" />
							</label>
						</p>
						<p>
							<label><?php _e('Transaction Key') ?><br />
							  <input value="<?php echo $options['authorize_transaction_key']; ?>" size="30" name="<?php echo $plugin_slug; ?>_transaction_key" type="text" />
							</label>
						</p>
						<p>
							<label><?php _e('Currency') ?><br />
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
		$conversion_rate = 1;
        if ($shop_currency != $options['currency']) {
			$curr = new DG_CURRENCYCONVERTER();
            $conversion_rate = $curr->convert(1, $options['currency'], $shop_currency);
		}
		
		//Set up return url
		$return_url = $this->post_url;
		if($options['sandbox'] == 'checked'){
			$return_url = $this->sandbox_url;
		}
		$output = '<form name="dg_paypal_form" id="dg_payment_form" action="' . $return_url . '" method="post">';
        $output .= '<input type="hidden" name="return" value="' . $return_path . '"/>
                     <input type="hidden" name="cmd" value="_ext-enter" />
                     <input type="hidden" name="notify_url" value="' . $this->ipn_url . '"/>
                     <input type="hidden" name="redirect_cmd" value="_cart" />
                     <input type="hidden" name="business" value="' . $options['paypal_id'] . '"/>
                     <input type="hidden" name="cancel_return" value="' . $return_path . '&status=cancel"/>
                     <input type="hidden" name="rm" value="2" />
                     <input type="hidden" name="upload" value="1" />
                     <input type="hidden" name="currency_code" value="' . $options['currency'] . '"/>
                     <input type="hidden" name="no_note" value="1" />
                     <input type="hidden" name="invoice" value="' . $invoice_id . '">';
					 
		$count_product = 1;
        $tax_rate = 0;
        $shipping_total = 0.00;
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
			$count_product++;
		}
		$output .= '<input type="hidden" name="handling_cart" value="' . number_format($shipping_total, 2) . '"/></form>';
		return $output;
	}
}
?>