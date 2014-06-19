<?php

/**
 * DukaGate Payment Gateway
 * Bank Transfer Gateway Plugin
 */
class DukaGate_GateWay_BankTransfer extends DukaGate_GateWay_API{
	
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
		$this->plugin_name = __('Bank Transfer');
		$this->plugin_slug = __('bank_transfer');
		$this->required_fields = array('instructions' =>'');
		$this->currencies = array();
	}
	
	//Register Plugin
	function register(){
		//Register Plugin
		dg_register_gateway_plugin('DukaGate_GateWay_BankTransfer', $this->plugin_name, $this->plugin_slug, $this->required_fields, $this->currencies);
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
		$output .= '<button class="bank_continue_btn" onclick="bank_continue();">Continue</button>';
		$output .= '<script type="text/javascript">';
        $output .= 'function bank_continue(){
					window.location.href="'.$return_path.'"
					};
                     </script>';
		return $output;
	}
	
	function order_form_action($invoice){
		global $dukagate;
		if (! empty( $_POST ) && check_admin_referer('dg_bank_payments','dg_bank_payments_noncename') ){
			$dg_bank_files = get_option('dg_bank_files');
			if(!empty($_FILES['dg_bank_file']['name'])) {
				// Get the file type of the upload
				$arr_file_type = wp_check_filetype(basename($_FILES['dg_bank_file']['name']));
				$uploaded_type = $arr_file_type['type'];
				$upload = $dukagate->upload_file($_FILES['dg_bank_file']['name'], file_get_contents($_FILES['dg_bank_file']['tmp_name']), DG_DUKAGATE_INVOICE_DIR, DG_DUKAGATE_INVOICE_URL);
				
				$dg_bank_files[$invoice]['file'] = $upload['url'];
				update_option('dg_bank_files', $dg_bank_files);
			}
			
		}
		$dg_bank_files = get_option('dg_bank_files');
		?>
		<tr>
			<td><strong><?php _e("Upload a copy of successful EFT form", "dukagate"); ?></strong></td>
			<td>
				<?php
					if((isset($dg_bank_files[$invoice]['file'])) && !empty($dg_bank_files[$invoice]['file'])){
						echo sprintf( __('<a href="%1$s" target="_blank">EFT File</a>'), $dg_bank_files[$invoice]['file']);
					}else{
						?>
						<form enctype="multipart/form-data" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post">
							<?php wp_nonce_field('dg_bank_payments','dg_bank_payments_noncename'); ?>
							<input class="button" type="file" id="dg_bank_file" name="dg_bank_file" value="" size="25" />
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