<?php
/**
 * DukaGate Payment Gateway
 * 2 Checkout Gateway Plugin
 */

class DukaGate_GateWay_2Checkout extends DukaGate_GateWay_API{

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
		$this->plugin_name = __('2Checkout', 'dg-lang');
		$this->plugin_slug = __('2Checkout', 'dg-lang');
		$this->required_fields = array(
										'currency' => '',
										'username' => '',
										'password' => '');
		$this->currencies = array(
	            "ARS" => 'ARS - Argentina Peso',
	            "AUD" => 'AUD - Australian Dollar',
							"BRL" => 'BRL - Brazilian Real',
							"CAD" => 'CAD - Canadian Dollar',
							"CHF" => 'CHF - Swiss Franc',
							"DKK" => 'DKK - Danish Krone',
							"EUR" => 'EUR - Euro',
							"GBP" => 'GBP - British Pound',
							"HKD" => 'HKD - Hong Kong Dollar',
							"INR" => 'INR - Indian Rupee',
							"JPY" => 'JPY - Japanese Yen',
							"MXN" => 'MXN - Mexican Peso',
							"NOK" => 'NOK - Norwegian Krone',
							"NZD" => 'NZD - New Zealand Dollar',
							"SEK" => 'SEK - Swedish Krona',
							"USD" => 'USD - U.S. Dollar',
	          );
	}
	
	//Register Plugin
	function register(){
		//Register Plugin
		dg_register_gateway_plugin('DukaGate_GateWay_2Checkout', $this->plugin_name, $this->plugin_slug, $this->required_fields, $this->currencies);
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
									'currency' => '',
									'username' => '',
									'password' => '');
			$required_fields['currency'] = $_POST[$plugin_slug.'_currency'];
			$required_fields['username'] = $_POST[$plugin_slug.'_username'];
			$required_fields['password'] = $_POST[$plugin_slug.'_password'];
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
				    <th scope="row"><?php _e('2Checkout Credentials', 'dg-lang') ?></th>
				    <td>
						<span class="description"><?php print sprintf(__('You must login to 2Checkout vendor dashboard to obtain the seller ID and secret word. <a target="_blank" href="%s">Instructions &raquo;</a>', 'mp'), "http://www.2checkout.com/community/blog/knowledge-base/suppliers/tech-support/3rd-party-carts/md5-hash-checking/where-do-i-set-up-the-secret-word"); ?></span>
						<p>
							<label><?php _e('Seller ID', 'dg-lang') ?><br />
							  <input value="<?php echo $options['username']; ?>" size="30" name="<?php echo $plugin_slug; ?>_username" type="text" />
							</label>
						</p>
						<p>
							<label><?php _e('Secret word', 'dg-lang') ?><br />
							  <input value="<?php echo $options['password']; ?>" size="30" name="<?php echo $plugin_slug; ?>_password" type="text" />
							</label>
						</p>
				    </td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e('2Checkout Currency', 'dg-lang') ?></th>
					<td>
						<span class="description"><?php _e('Selecting a currency other than that used for your store may cause problems at checkout.', 'dg-lang'); ?></span><br />
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
				    <th scope="row"><?php _e('Enable', 'dg-lang') ?></th>
				    <td>
						<p>
							<label><?php _e('Select To enable or disable', 'dg-lang') ?><br />
							  <input value="checked" name="<?php echo $plugin_slug; ?>_enable" type="checkbox" <?php echo (intval($enabled) == 1) ? "checked='checked'": ""; ?> />
							</label>
						</p>
						<p>
							<input type="submit" name="<?php echo $plugin_slug; ?>" value="<?php _e('Save Settings', 'dg-lang') ?>" />
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
	}
}
?>