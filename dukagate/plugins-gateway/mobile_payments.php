<?php

/**
 * DukaGate Payment Gateway
 * Bank Transfer Gateway Plugin
 */
class DukaGate_GateWay_MobilePayments extends DukaGate_GateWay_API{
	
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
		$this->plugin_name = __('Mobile Money');
		$this->plugin_slug = __('mobile_money');
		$this->required_fields = array('none' =>'');
		$this->currencies = array('instructions' => '');
	}
	
	//Register Plugin
	function register(){
		//Register Plugin
		dg_register_gateway_plugin('DukaGate_GateWay_MobilePayments', $this->plugin_name, $this->plugin_slug, $this->required_fields, $this->currencies);
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
	function set_up_options($plugin_slug){
		global $dukagate;
		if(@$_POST[$plugin_slug]){
			$required_fields = array(
									'instructions' => '');
			$required_fields['instructions'] = $_POST[$plugin_slug.'_instructions'];
			$enabled = ($_POST[$plugin_slug.'_enable'] == 'checked') ? 1 : 0;
			$dukagate->dg_save_gateway_options($plugin_slug ,DukaGate::array_to_json($required_fields), $enabled);
		}
		$options = DukaGate::json_to_array($dukagate->dg_get_gateway_options($plugin_slug));
		$enabled = $dukagate->dg_get_enabled_status($plugin_slug);
		$editor_id = $plugin_slug.'_instructions';
		$settings = array( 'media_buttons' => false );
		?>
		<form method="POST" action="">
			<table class="form-table">
				<tr>
				    <th scope="row"><?php _e('Instructions') ?></th>
				    <td>
						<p>
							<?php wp_editor( $options['instructions'], $editor_id , $settings); ?>
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
		
		$options = DukaGate::json_to_array($dukagate->dg_get_gateway_options($this->plugin_slug));
		
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
		
		$output = $options['instructions'];
		$output .= '<br/>';
		$output .= '<button class="mobile_continue_btn" onclick="mobile_continue();">Continue</button>';
		$output .= '<script type="text/javascript">';
        $output .= 'function mobile_continue(){
					window.location.href="'.$return_path.'"
					};
                     </script>';
		return $output;
	}
	
	function order_form_action($invoice){
		if (! empty( $_POST ) && check_admin_referer('dg_mobile_payments','dg_mobile_payments_noncename') ){
			$dg_mp_codes = get_option('dg_mp_codes');
			$dg_mp_codes[$invoice]['code'] = sanitize_text_field($_POST['dg_mp_code']);
			update_option('dg_mp_codes', $dg_mp_codes);
		}
		$dg_mp_codes = get_option('dg_mp_codes');
		?>
		<tr>
			<td><strong><?php _e("Input the confirmation code received for verification", "dukagate"); ?></strong></td>
			<td>
				<?php
					if((isset($dg_mp_codes[$invoice]['code'])) && !empty($dg_mp_codes[$invoice]['code'])){
						echo $dg_mp_codes[$invoice]['code'];
					}else{
						?>
						<form enctype="multipart/form-data" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post">
							<?php wp_nonce_field('dg_mobile_payments','dg_mobile_payments_noncename'); ?>
							<input type="text" class="regular-text" value="" id="dg_mp_code" name="dg_mp_code" />
							<p class="submit">
								<input class='button-primary' type='submit' value='<?php _e('Save','dukagate'); ?>'/><br/>
							</p>
						</form>
						<?php
					}
				?>
			</td>
		</tr>
		<?php
	}
}
?>