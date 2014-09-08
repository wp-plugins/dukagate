<?php
/**
 * DukaGate Payment Gateway
 * PesaPal Gateway Plugin
 */
 
class DukaGate_GateWay_PesaPal extends DukaGate_GateWay_API{

	//private gateway slug. Lowercase alpha (a-z) and dashes (-) only please!
	var $plugin_name;
	
	//shortname of plugin
	var $plugin_slug;
	
	//always contains the url to send payment notifications to if needed by your gateway. Populated by the parent class
	var $ipn_url;
	
	//Payment gateway required fields
	var $required_fields;
	
	var $post_url = 'https://www.pesapal.com/api/PostPesapalDirectOrderV4';
	
	var $test_post_url = 'http://demo.pesapal.com/api/PostPesapalDirectOrderV4';
	
	var $status_request = 'https://www.pesapal.com/api/querypaymentstatus';
	
	var $test_status_request = 'https://demo.pesapal.com/api/querypaymentstatus';
	
	//Determine if to use form submit or other method
	var $form_submit = false;
	
	//Currencies
	var $currencies;
	
	//Default method called on create
	function on_create(){
		
		
		$this->plugin_name = __('Pesapal');
		$this->plugin_slug = __('pesapal');
		$this->required_fields = array(
										'customer_key' => '',
										'customer_secret' => '',
										'sandbox' => '',
										'custom_name' => '');
						
		//Set Pesapal transaction ID field
		$this->add_column();
		
		require_once(DG_GATEWAYS.'libraries/pesapal/OAuth.php');
		//require_once(DG_GATEWAYS.'libraries/pesapal/class.XMLHttpRequest.php');
		
		//add_action( 'pesapal_per_minute_event', array(&$this, 'cron'));
		add_action( 'dg_pesapal', array(&$this, 'activation'));
	}
	
	//Register Plugin
	function register(){
		//Register Plugin
		dg_register_gateway_plugin('DukaGate_GateWay_PesaPal', $this->plugin_name, $this->plugin_slug, $this->required_fields, $this->currencies);
	}
	
	private function add_column(){
		$table_update = get_option('pesapal_install');
		if(!isset($table_update) || empty($table_update)){
			global $wpdb;
			$databases = DukaGate::db_names();
			$table_name = $databases['transactions'];
			$alter_sql = "ALTER TABLE `$table_name` ADD `tx_id` VARCHAR( 100 ) NULL;";
			$wpdb->query($alter_sql);
			update_option('pesapal_install', 'pesapal_install');
		}
	}
	/**
	 * Process IPN
	 */
	function process_ipn_return() {
		global $wpdb;
		global $dukagate;
		$dg_shop_settings = get_option('dukagate_shop_settings');
		$settings = get_option('dukagate_gateway_settings');
		$consumer_key = $settings[$this->plugin_slug]['customer_key'];
		$consumer_secret = $settings[$this->plugin_slug]['customer_secret'];
		
		$transaction_tracking_id = $_REQUEST['pesapal_transaction_tracking_id'];
		$payment_notification = $_REQUEST['pesapal_notification_type'];
		$invoice = $_REQUEST['pesapal_merchant_reference'];
		
		$statusrequestAPI = $this->status_request;
		if($settings[$this->plugin_slug]['sandbox'] == 'checked'){
			$statusrequestAPI = $this->test_status_request;
		}
		$this->ipn_request($transaction_tracking_id , $payment_notification, $invoice, $consumer_key, $consumer_secret,$statusrequestAPI);
		
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
	 * Private Pesapal function to process IPN requests from url and from the cron job
	 * @param transaction_tracking_id - Pesapal Tracking ID
	 * @param payment_notification - Pesapal Notification Type
	 * @param invoice - invoice id
	 */
	private function ipn_request($transaction_tracking_id , $payment_notification, $invoice, $consumer_key, $consumer_secret, $statusrequestAPI){
		global $dukagate;
		
		if($payment_notification=="CHANGE" && $transaction_tracking_id!=''){
			$token = $params = NULL;
			$consumer = new OAuthConsumer($consumer_key, $consumer_secret);
			$signature_method = new OAuthSignatureMethod_HMAC_SHA1();

			//get transaction status
			$request_status = OAuthRequest::from_consumer_and_token($consumer, $token, "GET", $statusrequestAPI, $params);
			$request_status->set_parameter("pesapal_merchant_reference", $invoice);
			$request_status->set_parameter("pesapal_transaction_tracking_id",$transaction_tracking_id);
			$request_status->sign_request($signature_method, $consumer, $token);
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $request_status);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			 if(defined('CURL_PROXY_REQUIRED')) if (CURL_PROXY_REQUIRED == 'True'){
				$proxy_tunnel_flag = (defined('CURL_PROXY_TUNNEL_FLAG') && strtoupper(CURL_PROXY_TUNNEL_FLAG) == 'FALSE') ? false : true;
				curl_setopt ($ch, CURLOPT_HTTPPROXYTUNNEL, $proxy_tunnel_flag);
				curl_setopt ($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
				curl_setopt ($ch, CURLOPT_PROXY, CURL_PROXY_SERVER_DETAILS);
			}

			$response = curl_exec($ch);

			$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$raw_header  = substr($response, 0, $header_size - 4);
			$headerArray = explode("\r\n\r\n", $raw_header);
			$header      = $headerArray[count($headerArray) - 1];

			 //transaction status
			$elements = preg_split("/=/",substr($response, $header_size));
			$status = $elements[1];

			curl_close ($ch);
			switch ($status) {
				case 'PENDING':
					$updated_status = 'Pending';
					break;
				case 'COMPLETED':
					$updated_status = 'Paid';
					break;
				case 'FAILED':
					$updated_status = 'Canceled';
					break;
				default:
					$updated_status = 'Canceled';
					break;
			}
			$dukagate->dg_update_order_log($invoice, $updated_status);
		}
	}
	
	/**
	 * Set Up Payment gateway options
	 */
	function set_up_options($plugin_slug, $settings){
		global $dukagate;
		?>
			<table class="form-table">
				<tr>
				    <th scope="row"><?php _e('PesaPal Checkout') ?></th>
				    <td>
						<p>
							<?php _e('PesaPal requires Full names and email or  phone number. To handle APN return requests, please set the url '); ?>
							<strong><?php echo admin_url("admin-ajax.php?action=dg_handle_payment_return_pesapal"); ?></strong>
							<?php _e(' on your <a href="https://www.pesapal.com/merchantdashboard" target="_blank">pesapal</a> account settings'); ?>
						</p>
						
				    </td>
				</tr>
				<tr>
				    <th scope="row"><?php _e('PesaPal Merchant Credentials'); ?></th>
				    <td>
						<p>
							<label><?php _e('Custom Checkout Name'); ?><br />
							  <input value="<?php echo $settings[$plugin_slug]['custom_name']; ?>" size="30" name="dg[<?php echo $plugin_slug; ?>][custom_name]" type="text" />
							</label>
						</p>
						<p>
							<label><?php _e('Use PesaPal Sandbox'); ?><br />
							  <input value="checked" name="dg[<?php echo $plugin_slug; ?>][sandbox]" type="checkbox" <?php echo ($settings[$plugin_slug]['sandbox'] == 'checked') ? "checked='checked'": ""; ?> />
							</label>
						</p>
						<p>
							<label><?php _e('Customer Key') ?><br />
							  <input value="<?php echo $settings[$plugin_slug]['customer_key']; ?>" size="30" name="dg[<?php echo $plugin_slug; ?>][customer_key]" type="text" />
							</label>
						</p>
						<p>
							<label><?php _e('Customer Secret') ?><br />
								 <input value="<?php echo $settings[$plugin_slug]['customer_secret']; ?>" size="30" name="dg[<?php echo $plugin_slug; ?>][customer_secret]" type="text" />
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
		$conversion_rate = 1;
		$allowed_currency = "KES";
		if ($shop_currency != $allowed_currency) {
			$curr = new DG_CURRENCYCONVERTER();
            $conversion_rate = $curr->convert(1, $allowed_currency, $shop_currency);
		}
		if(!empty($dg_shop_settings['tax_rate'])){
			$tax_rate = $dg_shop_settings['tax'];
            $total_tax = $amount * $tax_rate / 100;
			$amount = $amount + $total_tax;
		}
		$amount = $amount * $conversion_rate;
		
		$token = $params = NULL;
		$consumer_key = $settings[$this->plugin_slug]['customer_key'];
		$consumer_secret = $settings[$this->plugin_slug]['customer_secret'];
		$signature_method = new OAuthSignatureMethod_HMAC_SHA1();
		
		//get form details
		$desc = 'Your Order No.: '.$invoice_id;
		$type = 'MERCHANT';
		$reference = $invoice_id;
		$first_name = '';
		$fullnames = 
		$last_name = '';
		$email = $content['dg_email'];
		$username = $email; //same as email
		$phonenumber = '';//leave blank
		$payment_method = '';//leave blank
		$code = '';//leave blank
		
		$callback_url = $return_path; //redirect url, the page that will handle the response from pesapal.
		$post_xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?><PesapalDirectOrderInfo xmlns:xsi=\"http://www.w3.org/2001/XMLSchemainstance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" Amount=\"".$amount."\" Description=\"".$desc."\" Code=\"".$code."\" Type=\"".$type."\" PaymentMethod=\"".$payment_method."\" Reference=\"".$reference."\" FirstName=\"".$first_name."\" LastName=\"".$last_name."\" Email=\"".$email."\" PhoneNumber=\"".$phonenumber."\" UserName=\"".$username."\" xmlns=\"http://www.pesapal.com\" />";
		$post_xml = htmlentities($post_xml);
		
		$consumer = new OAuthConsumer($consumer_key, $consumer_secret);
		//post transaction to pesapal
		$pp_post_url = $this->post_url;
		if($options['sandbox'] == 'checked'){
			$pp_post_url = $this->test_post_url;
		}
		$iframe_src = OAuthRequest::from_consumer_and_token($consumer, $token, "GET", $this->post_url, $params);
		$iframe_src->set_parameter("oauth_callback", $callback_url);
		$iframe_src->set_parameter("pesapal_request_data", $post_xml);
		$iframe_src->sign_request($signature_method, $consumer, $token);
		
		$output = '<iframe src="'.$iframe_src.'" width="100%" height="620px"  scrolling="no" frameBorder="0" >';
		$output .= '</iframe>';
		
		return $output;
	}
	
	/** 
	 * Cron check
	 * Calls pesapals API for status
	 */
	function pesapal_cron_check($transaction_id){
		global $dukagate;
		$settings = get_option('dukagate_gateway_settings');
		
		$consumer_key = $settings[$this->plugin_slug]['customer_key'];
		$consumer_secret = $settings[$this->plugin_slug]['customer_secret'];
		$signature_method = new OAuthSignatureMethod_HMAC_SHA1();

		$token = $params = NULL;
		$consumer = new OAuthConsumer($consumer_key, $consumer_secret);

		//get transaction status
		$request_status = OAuthRequest::from_consumer_and_token($consumer, $token, "GET", $this->status_request, $params);
		$request_status->set_parameter("pesapal_request_data", $transaction_id);
		$request_status->sign_request($signature_method, $consumer, $token);

		//curl request
		$ajax_req = new XMLHttpRequest();
		$ajax_req->open("GET",$request_status);
		$ajax_req->send();

		//if curl request successful
		if($ajax_req->status == 200){
			$values = array();
			$elements = split("=",$ajax_req->responseText);
			$values[$elements[0]] = $elements[1];
		}

		//transaction status
		$status = $values['pesapal_response_data'];
		return $status;
	}

	/**
	 * Cron function to run
	 */
	function cron() {
		global $wpdb;
		$databases = DukaGate::db_names();
		$table_name = $databases['transactions'];
		$query = "SELECT * FROM {$table_name} WHERE `payment_status` = 'Pending' AND `payment_gateway` = '{$this->plugin_slug}' LIMIT 10";
		$results = $wpdb->get_results($query);
		if($results){
			foreach ($results as $result) {
				$payment_status = $this->pesapal_cron_check($result->tx_id);
				$this->ipn_request($result->tx_id , $payment_status, $result->invoice);
			}
		}
	}
	
	/**
	 * Activation
	 */
	function activation() {
		if ( !wp_next_scheduled( 'pesapal_per_minute_event' ) ) {
			wp_schedule_event(time(), 'in_per_ten_minute', 'pesapal_per_minute_event');
		}
	}
}
?>