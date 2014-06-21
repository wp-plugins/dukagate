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
		$this->plugin_name = __('2Checkout (Beta)');
		$this->plugin_slug = __('2Checkout');
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
		dg_register_gateway_plugin('DukaGate_GateWay_2Checkout', $this->plugin_name, $this->plugin_slug, $this->required_fields, $this->currencies, false);
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
		global $dukagate;
		$currencies = DukaGate::json_to_array($dukagate->dg_get_gateway_currencies($plugin_slug));
		?>
			<table class="form-table">
				<tr>
				    <th scope="row"><?php _e('2Checkout Credentials') ?></th>
				    <td>
						<span class="description"><?php print sprintf(__('You must login to 2Checkout vendor dashboard to obtain the seller ID and secret word. <a target="_blank" href="%s">Instructions &raquo;</a>', 'mp'), "http://www.2checkout.com/community/blog/knowledge-base/suppliers/tech-support/3rd-party-carts/md5-hash-checking/where-do-i-set-up-the-secret-word"); ?></span>
						<p>
							<label><?php _e('Seller ID') ?><br />
							  <input value="<?php echo $settings[$plugin_slug]['username']; ?>" size="30" name="dg[<?php echo $plugin_slug; ?>][username]" type="text" />
							</label>
						</p>
						<p>
							<label><?php _e('Secret word') ?><br />
							  <input value="<?php echo $settings[$plugin_slug]['password']; ?>" size="30" name="dg[<?php echo $plugin_slug; ?>][password]" type="text" />
							</label>
						</p>
				    </td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e('2Checkout Currency') ?></th>
					<td>
						<span class="description"><?php _e('Selecting a currency other than that used for your store may cause problems at checkout.'); ?></span><br />
						<select name="dg[<?php echo $plugin_slug; ?>][currency]">
							<?php
							$sel_currency = $settings[$plugin_slug]['currency'];
							foreach ($currencies as $k => $v) {
								echo '<option value="' . $k . '"' . ($k == $sel_currency ? ' selected' : '') . '>' . wp_specialchars($v, true) . '</option>' . "\n";
							}
							?>
						</select>
					</td>
				</tr>
			</table>
		<?php
	}
	
	
	/**
	 * Process Payment
	 */
	function process_payment_form($content){
	}
}
?>