<?php
/**
 * Dukagate Admin functions
 * 
 */
 
 
//Create Admin menu

add_action('admin_menu', 'dg_create_admin_menu');
function dg_create_admin_menu(){
	$user = wp_get_current_user();
	if ( current_user_can('manage_options') && current_user_can('edit_others_posts')){
		add_submenu_page('edit.php?post_type=dg_product', __('DukaGate Order Log', 'dukagate'), __('Order Log', 'dukagate'), 'edit_others_posts', 'dukagate-order-log', 'dg_dukagate_order_log');
		add_submenu_page('edit.php?post_type=dg_product', __('DukaGate Discount Settings', 'dukagate'), __('Discounts', 'dukagate'), 'edit_others_posts', 'dukagate-discounts', 'dg_dukagate_discounts');
		add_submenu_page('edit.php?post_type=dg_product', __('DukaGate Settings', 'dukagate'), __('Settings', 'dukagate'), 'edit_others_posts', 'dukagate-settings', 'dg_admin_settings');
	}else if(in_array("shopuser",$user->roles)){
		add_object_page( __('Order Log', 'dukagate'), __('Order Log', 'dukagate'), 'read', 'dukagate-order-log', '', DG_DUKAGATE_URL . '/images/dg_icon.png');
		add_submenu_page('dukagate-order-log', __('Order Log', 'dukagate'), __('Order Log', 'dukagate'), 'read', 'dukagate-order-log', 'dukagate_user_order_log');
	}
	
}

/**
 * Creats the Admin Tabs
 *
 */
function dg_admin_tabs($current = 'settings'){
	$tabs = array( 'settings' => __('Settings'), 'payment' => __('Payment Options'), 'shipping' => __('Shipping Options'), 'checkout' => __('Check Out Settings'), 'mail' => __('Mail'), 'tools' => __('Tools'), 'advanced' => __('Advanced Settings') ); 
    $links = array();
    echo '<div id="icon-themes" class="icon32"><br></div>';
    echo '<h2 class="nav-tab-wrapper">';
    foreach( $tabs as $tab => $name ){
        $class = ( $tab == $current ) ? ' nav-tab-active' : '';
        echo "<a class='nav-tab$class' href='".admin_url('edit.php?post_type=dg_product&page=dukagate-settings')."&tab=$tab'>$name</a>";
    }
    echo '</h2>';
}

/**
 * Admin Settings
 *
 */
function dg_admin_settings(){
	global $pagenow;
	?>
	<div class="wrap">
		<h2><?php _e("Dukagate Settings"); ?></h2>
		<?php
			if ( 'true' == esc_attr( $_GET['updated'] ) ) echo '<div class="updated" ><p>Settings updated.</p></div>';
			
			if ( isset ( $_GET['tab'] ) ) dg_admin_tabs($_GET['tab']); else dg_admin_tabs();
		?>
		<div id="poststuff">
			<?php
			if ( $pagenow == 'edit.php' && $_GET['page'] == 'dukagate-settings' ){ 
				$tab = 'settings';
				if ( isset ( $_GET['tab'] ) ) $tab = $_GET['tab']; 
				switch ( $tab ){
					case 'settings' :
						dg_dukagate_settings();
					break;
					
					case 'payment' :
						dg_dukagate_paymnet();
					break;
					
					case 'shipping' :
						dg_dukagate_shipping();
					break;
					
					case 'checkout' :
						dg_dukagate_checkout();
					break;
					
					case 'mail' :
						dg_dukagate_mail();
					break;
					
					case 'tools' :
						dg_dukagate_tools();
					break;
					
					case 'advanced' :
						dg_dukagate_advanced_settings();
					break;
				}
			}
			?>
		</div>
	</div>
	<?php
}

/** 
 * User log
 *
 */
function dukagate_user_order_log(){
	global $dukagate;
	global $current_user;
	if(@$_REQUEST['order_id']){
		dg_dukagate_order_log_info($_REQUEST['order_id']);
	}else{
	
    get_currentuserinfo();
	$order_logs = $dukagate->dg_list_user_order_logs($current_user->user_email);
	?>
	<div class="wrap">
		<h2><?php _e("Dukagate Order Log"); ?></h2>
		<?php if (is_array($order_logs) && count($order_logs) > 0) { ?>
			<table width="100%" border="0" class="widefat">
				<thead>
					<tr>
						<th width="1%" align="left" scope="col">&nbsp;</th>
						<th width="20%" align="left" scope="col"><?php _e("Invoice"); ?></th>
						<th width="20%" align="left" scope="col"><?php _e("Date"); ?></th>
						<th width="10%" align="left" scope="col"><?php _e("Amount"); ?></th>
						<th width="14%" align="left" scope="col"><?php _e("Mode"); ?></th>
						<th width="10%" align="left" scope="col"><?php _e("Status"); ?></th>
						<th width="16%" align="left" scope="col"><?php _e("Actions"); ?></th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th align="left" scope="col">&nbsp;</th>
						<th align="left" scope="col"><?php _e("Invoice"); ?></th>
						<th align="left" scope="col"><?php _e("Date"); ?></th>
						<th align="left" scope="col"><?php _e("Amount"); ?></th>
						<th align="left" scope="col"><?php _e("Mode"); ?></th>
						<th align="left" scope="col"><?php _e("Status"); ?></th>
						<th align="left" scope="col"><?php _e("Actions"); ?></th>
					</tr>
				</tfoot>
				<tbody>
					<?php
					$count = 1;
					$form_url = admin_url("admin.php?page=dukagate-order-log&order_id=");
					foreach($order_logs as $order_log => $log){
						?>
						<tr id="order_<?php echo $log->id; ?>">
							<td align="left"><?php echo $count; ?></td>
							<td align="left"><?php echo $log->invoice; ?></td>
							<td align="left"><?php echo date("d-m-Y", strtotime($log->date)); ?></td>
							<td align="left"><?php echo number_format($log->total,2); ?></td>
							<td align="left"><?php echo $log->payment_gateway; ?></td>
							<td align="left"><?php _e($log->payment_status) ; ?></td>
							<td align="left"><a href="<?php echo $form_url.$log->id ;?>"><?php _e("View"); ?></a></td>
						</tr>
						<?php
						$count++;
					}
					?>
				</tbody>
			</table>
		<?php } else{
			_e("No Orders found.");
		}?>
	</div>
	<?php
	}
}

//Order Log
function dg_dukagate_order_log(){
	global $dukagate;
	if(@$_REQUEST['order_id']){
		dg_dukagate_order_log_info($_REQUEST['order_id']);
	}else{
	?>
	<div class="wrap">
	<?php
		if(@$_REQUEST['delete']){
			$order_id = $_REQUEST['id'];
			$dukagate->dg_delete_order_log($order_id);
			?>
			<div id="message" class="updated fade">
				<h3><?php _e("Successfully deleted order ID $order_id","dukagate"); ?></h3>
			</div>
			<?php
		}
		if (! empty( $_POST['dg_order_export'] ) && check_admin_referer('dg_order_export','dg_order_export_nounce') ){
			$from = DukaGate::to_timestamp(sanitize_text_field($_POST['from_date']));
			$to = DukaGate::to_timestamp(sanitize_text_field($_POST['to_date']));
			$orders = $dukagate->dg_filter_order_logs($from ,$to);
			if(is_array($orders) && (count($orders) > 0)){
				$header = array();
				$csv_array = array();
				$count = 0;
				foreach($orders as $order_log => $log){
					$order_info =  DukaGate::json_to_array($log->order_info);
					$csv_array[$count]['email'] = $log->email;
					$csv_array[$count]['names'] = $log->names;
					foreach ($order_info as $order_in => $order) {
						foreach ($order as $key => $value) {
							$csv_array[$count][$key] = $value;
						}
					}
					$csv_array[$count]['invoice'] = $log->invoice;
					$csv_array[$count]['payment_status'] = $log->payment_status;
					$csv_array[$count]['date'] = date("d-m-Y", strtotime($log->date));
					$csv_array[$count]['mode'] = $log->payment_gateway;
					$csv_array[$count]['price'] = number_format($log->total,2);
					$count++;
				}
				
				$file_name = 'dukagate_order_export_'.date(dHis);
				$output_path = DG_DUKAGATE_CONTENT_URL.'/report/'.$file_name.'.csv';
				$dukagate->create_csv($file_name, $csv_array, $header);
				?>
				<div class="updated fade">
					<p><a href="<?php echo $output_path; ?>" target="_blank"><?php _e("Click here to download the exported file"); ?><a></p>
				</div>
				<?php
			}else{
				?>
				<div class="error fade">
					<p><?php _e("No records found to export"); ?></p>
				</div>
				<?php
			}
		}
		$order_logs = $dukagate->dg_list_order_logs();
	?>
	
		<h2><?php _e("Dukagate Order Log"); ?></h2>
		<?php if (is_array($order_logs) && count($order_logs) > 0) { ?>
			<form method="POST" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
				<?php wp_nonce_field('dg_order_export','dg_order_export_nounce'); ?>
				<?php _e("Export Order Log"); ?>
				<table width="100%" border="0" class="widefat">
					<tr>
						<td align="left" scope="row"><?php _e("From"); ?></td>
						<td><input type="text" id="f_datepicker" name="from_date" /></td>
						<td align="left" scope="row"><?php _e("To"); ?></td>
						<td><input type="text" id="t_datepicker" name="to_date" /></td>
					</tr>
					<tr>
						<th align="left" scope="row" colspan="4">
							<input class="button button-primary" name="dg_order_export" type="submit" value="<?php _e("Download"); ?>" />
						</th>
					</tr>
				</table>
				<script type="text/javascript">
					jQuery(document).ready(function(){
						jQuery('#f_datepicker').datepicker();	
						jQuery('#t_datepicker').datepicker();	
					});
				</script>
			</form>
			<table width="100%" border="0" class="widefat">
				<thead>
					<tr>
						<th width="1%" align="left" scope="col">&nbsp;</th>
						<th width="20%" align="left" scope="col"><?php _e("Invoice"); ?></th>
						<th width="20%" align="left" scope="col"><?php _e("Date"); ?></th>
						<th width="10%" align="left" scope="col"><?php _e("Amount"); ?></th>
						<th width="14%" align="left" scope="col"><?php _e("Mode"); ?></th>
						<th width="10%" align="left" scope="col"><?php _e("Status"); ?></th>
						<th width="16%" align="left" scope="col"><?php _e("Actions"); ?></th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th align="left" scope="col">&nbsp;</th>
						<th align="left" scope="col"><?php _e("Invoice"); ?></th>
						<th align="left" scope="col"><?php _e("Date"); ?></th>
						<th align="left" scope="col"><?php _e("Amount"); ?></th>
						<th align="left" scope="col"><?php _e("Mode"); ?></th>
						<th align="left" scope="col"><?php _e("Status"); ?></th>
						<th align="left" scope="col"><?php _e("Actions"); ?></th>
					</tr>
				</tfoot>
				<tbody>
					<?php
					$count = 1;
					$form_url = admin_url("edit.php?post_type=dg_product&page=dukagate-order-log&order_id=");
					$form_del_url = admin_url("edit.php?post_type=dg_product&page=dukagate-order-log&delete=true&id=");
					foreach($order_logs as $order_log => $log){
						?>
						<tr id="order_<?php echo $log->id; ?>">
							<td align="left"><?php echo $count; ?></td>
							<td align="left"><?php echo $log->invoice; ?></td>
							<td align="left"><?php echo date("d-m-Y", strtotime($log->date)); ?></td>
							<td align="left"><?php echo number_format($log->total,2); ?></td>
							<td align="left"><?php echo $log->payment_gateway; ?></td>
							<td align="left"><?php _e($log->payment_status) ; ?></td>
							<td align="left"><a href="<?php echo $form_url.$log->id ;?>"><?php _e("View","dukagate"); ?></a> | <a href="<?php echo $form_del_url.$log->id; ?>"><?php _e("Delete","dukagate"); ?></a></td>
						</tr>
						<?php
						$count++;
					}
					?>
				</tbody>
			</table>
		<?php } else{
			_e("No Orders found.", "dukagate");
		}?>
	</div>
	<?php
	}
}

if (@$_REQUEST['action'] === 'dg_export_orders') {
	add_action( 'init', 'dg_export_orders');
}


//Get Order Log detail
function dg_dukagate_order_log_info($id){
	global $dukagate;
	$dg_shop_settings = get_option('dukagate_shop_settings');
	$order_log = $dukagate->dg_get_order_log_by_id($id);
	
	$user_can_change = true;
	
	if (!current_user_can('manage_options') && !current_user_can('edit_others_posts')){
		$user = wp_get_current_user();
		$user_can_change = false;
		if(!$dukagate->is_order_for_user($current_user->user, $id)){
			wp_die(__("You are not allowed to view this order", "dukagate"));
		}
		
	}
	?>
	<div class="wrap">
		<h2><?php _e("Viewing order ", "dukagate").' '._e($order_log->invoice); ?></h2>
		<!--div class="csv_export">
			<a href="javascript:;" onclick="dukagate.order_csv_export('<?php echo $order_log->id; ?>');">Export as CSV</a>
		</div-->
		<?php
			$invoice_file = DG_DUKAGATE_INVOICE_DIR. '/invoice_' . $order_log->invoice.'.pdf';
			if(file_exists($invoice_file)){
		?>
		<div class="csv_export">
			<a href="<?php echo DG_DUKAGATE_INVOICE_URL.'/invoice_' . $order_log->invoice . '.pdf'; ?>">Download Invoice</a>
		</div>
		<?php
			}
		?>
		<table width="100%" class="widefat" style="background-color: #FFFFFF;">
			<tr>
				<td><strong><?php _e("Invoice", "dukagate"); ?></strong></td>
				<td><?php echo $order_log->invoice; ?></td>
			</tr>
			<tr>
				<td><strong><?php _e("Status", "dukagate"); ?></strong></td>
				<td>
					<span id="dg_order_status"><?php _e($order_log->payment_status); ?></span>
					<?php if($user_can_change){?>
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:;" onclick="dukagate.change_order_status('<?php echo $order_log->id; ?>');"><?php _e("Change Status"); ?></a>
					<?php } ?>
				</td>
			</tr>
			<?php do_action( 'dg_order_log_'.$order_log->payment_gateway , $order_log->invoice); ?>
			<tr>
				<td><strong><?php _e("Date Created", "dukagate"); ?></strong></td>
				<td><?php echo $order_log->date; ?></td>
			</tr>
			<tr>
				<td><strong><?php _e("Total Shipping", "dukagate"); ?></strong></td>
				<td><?php echo $order_log->shipping; ?></td>
			</tr>
			<tr>
				<td><strong><?php _e("Total Tax", "dukagate"); ?></strong></td>
				<td><?php echo $order_log->tax; ?></td>
			</tr>
			<tr>
				<td><strong><?php _e("Total", "dukagate"); ?></strong></td>
				<td><?php echo $order_log->total; ?></td>
			</tr>
			<tr>
				<td><strong><?php _e("Products", "dukagate"); ?></strong></td>
				<td>
					<?php 
					$cnt  = '';
					$products =  DukaGate::json_to_array($order_log->products); 
					if (is_array($products) && count($products) > 0) {
						$total = 0.00;
						$total_discount = 0.00;
						$cnt .= '<table style="text-align:left" class="widefat">';
						$cnt .= '<tr>';
						$cnt .= '<th scope="col" width="30%">'.__("Product", "dukagate").'</th>';
						$cnt .= '<th scope="col" width="10%">'.__("Quantity", "dukagate").'</th>';
						$cnt .= '<th scope="col" width="30%">'.__("Price", "dukagate").'</th>';
						$cnt .= '<th scope="col" width="30%">'.__("Total", "dukagate").'</th>';
						$cnt .= '</tr>';
						foreach ($products as $cart_items => $cart) {
							$cnt .= '<tr>';
							if(isset($cart['children']))
								$cnt .= '<td>'.$cart['product'].' ('.$cart['children'].') </td>';
							else
								$cnt .= '<td>'.$cart['product'].' </td>';
							$cnt .= '<td>'.$cart['quantity'].'</td>';
							$cnt .= '<td>'.$dg_shop_settings['currency_symbol'].' '. number_format($cart['price'],2).'</td>';
							$cnt .= '<td>'.$dg_shop_settings['currency_symbol'].' '. number_format($cart['total'],2).'</td>';
							$cnt .= '</tr>';
							$total += $cart['total'];
						}
						$cnt .= '<tr>';
						$cnt .= '<td colspan="4">&nbsp;</td>';
						$cnt .= '</tr>';
						$cnt .= '<tr>';
						$cnt .= '<td>'.__("Total Discount", "dukagate").'</td>';
						$cnt .= '<td>&nbsp;</td>';
						$cnt .= '<td>&nbsp;</td>';
						$cnt .= '<td>'.$dg_shop_settings['currency_symbol'].' '.number_format($order_log->discount,2).'</td>';
						$cnt .= '</tr>';
						$cnt .= '<tr>';
						$cnt .= '<td>'.__("Total Shipping", "dukagate").'</td>';
						$cnt .= '<td>&nbsp;</td>';
						$cnt .= '<td>&nbsp;</td>';
						$cnt .= '<td>'.$dg_shop_settings['currency_symbol'].' '.number_format(($order_log->shipping),2).'</td>';
						$cnt .= '</tr>';
						$cnt .= '<tr>';
						$cnt .= '<td>'.__("Total", "dukagate").'</td>';
						$cnt .= '<td>&nbsp;</td>';
						$cnt .= '<td>&nbsp;</td>';
						$cnt .= '<td>'.$dg_shop_settings['currency_symbol'].' '.number_format(($order_log->total),2).'</td>';
						$cnt .= '</tr>';
						$cnt .= '</table>';
					}
					echo $cnt;
					?>
				</td>
			</tr>
			<tr>
				<td><strong><?php _e("Order Info", "dukagate"); ?></strong></td>
				<td>
					<table class="widefat">
						<?php 
						if(!empty($order_log->names)){
						?>
						<tr>
							<td><strong><?php _e("Names"); ?></strong></td>
							<td><?php echo $order_log->names; ?></td>
						</tr>
						<?php } if(!empty($order_log->email)){?>
						<tr>
							<td><strong><?php _e("Email"); ?></strong></td>
							<td><?php echo $order_log->email; ?></td>
						</tr>
						<?php } ?>
					<?php 
					$order_info =  DukaGate::json_to_array($order_log->order_info);
					if (is_array($order_info) && count($order_info) > 0) {
						foreach ($order_info as $order_in => $order) {
							foreach ($order as $key => $value) {
								?>
								<tr>
									<td><strong><?php _e($key); ?></strong></td>
									<td><?php echo $value; ?></td>
								</tr>
								<?php
							}
							
						}
						
					}
					?>
					</table>
				</td>
			</tr>
			<?php 
				$order_info =  DukaGate::json_to_array($order_log->shipping_info);
				if (is_array($order_info) && count($order_info) > 0) {
			?>
			<tr>
				<td><strong><?php _e("Shipping Info"); ?></strong></td>
				<td>
					<table>
					<?php 
					
						foreach ($order_info as $order_in => $order) {
							?>
							<tr>
								<td><strong><?php _e($order['key']); ?></strong></td>
								<td><?php echo $order['value']; ?></td>
							</tr>
							<?php
						}
					
					?>
					</table>
				</td>
			</tr>
			<?php } ?>
		</table>
	</div>
	<?php
}


//Delete Order Log
if (@$_REQUEST['action'] === 'dg_delete_order_log') {
	add_action( 'init', 'dg_delete_order_log');
}

function dg_delete_order_log(){
	global $dukagate;
	$id = $_REQUEST['id'];
	$dukagate->dg_delete_order_log($id);
	header('Content-type: application/json; charset=utf-8');
	echo DukaGate::array_to_json(array('success' => 'true'));
	exit();
}


//Update Order Log status
if (@$_REQUEST['action'] === 'dg_change_order_log') {
	add_action( 'init', 'dg_change_order_log_status');
}
function dg_change_order_log_status(){
	$id = $_REQUEST['id'];
	$stat = $_REQUEST['stat'];
	global $dukagate;
	$status = '';
	switch($stat){
		case 'Pending' :
			$status = 'Paid';
			break;
		case 'Paid' :
			$status = 'Canceled';
			break;
		case 'Canceled' :
			$status = 'Pending';
			break;
		default:
			$status = 'Pending';
			break;
	}
	$dukagate->dg_update_order_log_by_id($id, $status);
	header('Content-type: application/json; charset=utf-8');
	echo DukaGate::array_to_json(array('success' => 'true', 'status' => $status));
	exit();
}


//Payment Options
function dg_dukagate_paymnet(){
	global $dukagate;
	if(@$_POST['dg_payment_settings'] && check_admin_referer('dukagate_payment_settings','dukagate_payment_noncename')){
		$gateways = $_POST['dg_gateway'];
		update_option('dukagate_gateway_options', $gateways);
	}
	
	if(@$_POST['dg_payment_option_settings'] && check_admin_referer('dukagate_paymentoption_settings','dukagate_paymentoption_noncename')){
		$gateway_settings = get_option('dukagate_gateway_settings');
		$setting = $_POST['dg'];
		foreach($setting as $key => $value) {
			$gateway_settings[$key] = $value;
		}
		update_option('dukagate_gateway_settings', $gateway_settings);
	}
	$dg_gateways = $dukagate->list_all_gateways();
	$alowed_options = get_option('dukagate_gateway_options');
	$gateway_settings = get_option('dukagate_gateway_settings');
	?>
		<div id="dg_payments">
			<?php
			if (is_array($dg_gateways) && count($dg_gateways) > 0) {
				?>
				<form method="POST" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
					<?php wp_nonce_field('dukagate_payment_settings','dukagate_payment_noncename'); ?>
				<ul>
				<?php
				$selected = "";
				foreach ($dg_gateways as $dg_gateway) {
					if(is_array($alowed_options))
						if(in_array($dg_gateway->gateway_slug, $alowed_options)){
							$selected = "checked='checked'";
						}else{
							$selected = "";
						}
					?>
					<li>
						<label>
							<input type="checkbox" name="dg_gateway[]" value="<?php echo $dg_gateway->gateway_slug; ?>" <?php echo $selected;?> />
							<?php _e($dg_gateway->gateway_name); ?> 
						</label> 
					</li>	
					<?php
				}
				?>
				</ul>
				<p class="submit">
					<input class='button-primary' type='submit' name='dg_payment_settings' value='<?php _e('Activate Selected', 'dukagate'); ?>'/><br/>
				</p>
				</form>
				<?php 
				if(is_array($alowed_options)){
				?>
				<form method="POST" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
					<?php wp_nonce_field('dukagate_paymentoption_settings','dukagate_paymentoption_noncename'); ?>
					<div id="dg_<?php echo $dg_gateway->id; ?>" class="dg_payment_gateway">
						<?php
							foreach ($alowed_options as $gateway_option) {
								?>
								<h2><?php _e($dukagate->dg_get_gateway_name($gateway_option)); ?></h2>
								<?php
								do_action( 'dg_gateway_option_'.$gateway_option , $gateway_option, $gateway_settings);
								?><hr/><?php
							}
						?>
						<p class="submit">
							<input class='button-primary' type='submit' name='dg_payment_option_settings' value='<?php _e('Save Settings', 'dukagate'); ?>'/><br/>
						</p>
					</div>
				</form>
				<?php
				}
			}else{
				_e("No Payment Gateways Found!!");
			}
			?>
		</div>
	<?php
}



//Shipping Options
function dg_dukagate_shipping(){
	global $dukagate;
	$dg_gateways = $dukagate->list_all_shipping_gateways();
	?>
		<div id="dg_payments">
			<?php
			if (is_array($dg_gateways) && count($dg_gateways) > 0) {
				foreach ($dg_gateways as $dg_gateway) {
					?>
					<div id="dg_<?php echo $dg_gateway->id; ?>" class="dg_payment_gateway">
						<div id="dg_title_<?php echo $dg_gateway->id; ?>" class="dg_payment_title">
							<div class="dg_instructions"><?php _e("Click to show/hide settings","dg-lang"); ?></div>
							<h2><?php _e($dg_gateway->name,"dg-lang"); ?></h2>
						</div>
						<div class="dg_gateway_options" id="dg_opt_<?php echo $dg_gateway->id; ?>">
							<?php DukaGate::call_class_function($dg_gateway->class, 'set_up_options', $dg_gateway->slug); ?>
						</div>
						<script type="text/javascript">
							jQuery(document).ready(function(){
								jQuery('#dg_title_<?php echo $dg_gateway->id; ?>').click(function(){
									jQuery('#dg_opt_<?php echo $dg_gateway->id; ?>').slideToggle();
								});
							});
						</script>
					</div>
					<?php
				}
			}else{
				_e("No Shipping Gateways Found!!","dg-lang");
			}
			?>
		</div>
	<?php
}


//Chekout options
function dg_dukagate_checkout(){
	global $dukagate_settings;
	$settings = $dukagate_settings->get_settings();
	
	if(@$_POST['dg_checkout_settings'] && check_admin_referer('dukagate_checkout_settings','dukagate_checkout_noncename')){
		$total = 20;
		$form_elem = array();
		while($total > 0){
			if(!empty($_POST['name_'.$total])){
				$form_elem[$total]['name'] = @$_POST['name_'.$total];
				$form_elem[$total]['type'] = @$_POST['type_'.$total];
				$form_elem[$total]['uname'] = @$_POST['u_name_'.$total];
				$form_elem[$total]['initial'] = @$_POST['initial_'.$total];
				$form_elem[$total]['mandatory'] = (empty($_POST['manadatory_'.$total])) ? 'notchecked' : 'checked';
				$form_elem[$total]['visible'] = (empty($_POST['visible_'.$total])) ? 'notchecked' : 'checked';
			}
			$total--;
		}
		$form_elem['dg_fullname_mandatory'] = (empty($_POST['dg_fullname_mandatory'])) ? 'notchecked' : 'checked';
		$form_elem['dg_fullname_visible'] = (empty($_POST['dg_fullname_visible'])) ? 'notchecked' : 'checked';
		$form_elem['dg_firstname_mandatory'] = (empty($_POST['dg_firstname_mandatory'])) ? 'notchecked' : 'checked';
		$form_elem['dg_firstname_visible'] = (empty($_POST['dg_firstname_visible'])) ? 'notchecked' : 'checked';
		$form_elem['dg_lastname_mandatory'] = (empty($_POST['dg_lastname_mandatory'])) ? 'notchecked' : 'checked';
		$form_elem['dg_lastname_visible'] = (empty($_POST['dg_lastname_visible'])) ? 'notchecked' : 'checked';
		$form_elem['dg_email_mandatory'] = (empty($_POST['dg_email_mandatory'])) ? 'notchecked' : 'checked';
		$form_elem['dg_email_visible'] = (empty($_POST['dg_email_visible'])) ? 'notchecked' : 'checked';
		$form_elem['dg_phone_mandatory'] = (empty($_POST['dg_phone_mandatory'])) ? 'notchecked' : 'checked';
		$form_elem['dg_phone_visible'] = (empty($_POST['dg_phone_visible'])) ? 'notchecked' : 'checked';
		$form_elem['dg_country_mandatory'] = (empty($_POST['dg_country_mandatory'])) ? 'notchecked' : 'checked';
		$form_elem['dg_country_visible'] = (empty($_POST['dg_country_visible'])) ? 'notchecked' : 'checked';
		$form_elem['dg_state_mandatory'] = (empty($_POST['dg_state_mandatory'])) ? 'notchecked' : 'checked';
		$form_elem['dg_state_visible'] = (empty($_POST['dg_state_visible'])) ? 'notchecked' : 'checked';
		update_option('dukagate_checkout_options', $form_elem);
		
	}
	
	$dg_form_elem = get_option('dukagate_checkout_options');
	
	$dukagate_settings->set_manadatory_forms('dg_fullname', $dg_form_elem);
	$dukagate_settings->set_manadatory_forms('dg_email', $dg_form_elem);
	
	?>

		<form method="POST" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
			<?php wp_nonce_field('dukagate_checkout_settings','dukagate_checkout_noncename'); ?>
			<div class="dg_settings">
				<p class="submit">
					<input class='button-primary' type='submit' name='dg_checkout_settings' value='<?php _e('Save Options'); ?>'/><br/>
				</p>
				<table width="100%" border="0" class="widefat">
					<thead>
						<tr>
							<th width="20%" align="left" scope="col"><?php _e('Name','dg-lang'); ?></th>
							<th width="10%" align="left" scope="col"><?php _e('Type','dg-lang'); ?></th>
							<th width="10%" align="left" scope="col"><?php _e('Unique Name','dg-lang'); ?></th>
							<th width="40%" align="left" scope="col"><?php _e('Initial Value','dg-lang'); ?></th>
							<th width="10%" align="left" scope="col"><?php _e('Mandatory','dg-lang'); ?></th>
							<th width="10%" align="left" scope="col"><?php _e('Visible','dg-lang'); ?></th>
						</tr>
					</thead>
					
					<tfoot>
						<tr>
							<th align="left" scope="col"><?php _e('Name','dg-lang'); ?></th>
							<th align="left" scope="col"><?php _e('Type','dg-lang'); ?></th>
							<th align="left" scope="col"><?php _e('Unique Name','dg-lang'); ?></th>
							<th align="left" scope="col"><?php _e('Initial Value','dg-lang'); ?></th>
							<th align="left" scope="col"><?php _e('Mandatory','dg-lang'); ?></th>
							<th align="left" scope="col"><?php _e('Visible','dg-lang'); ?></th>
						</tr>
					</tfoot>
					
					<tbody>
						<tr>
							<td><?php _e("Full Names "); ?></td>
							<td><?php _e("Text "); ?></td>
							<td><?php _e("dg_fullname"); ?></td>
							<td><?php _e("None "); ?></td>
							<td><input type="checkbox" value="checked" name="dg_fullname_mandatory" <?php echo (@$dg_form_elem['dg_fullname_mandatory'] == 'checked') ? "checked='checked'": ""; ?> /></td>
							<td><input type="checkbox" value="checked" name="dg_fullname_visible" <?php echo (@$dg_form_elem['dg_fullname_visible'] == 'checked') ? "checked='checked'": ""; ?> /></td>
						</tr>
						<tr>
							<td><?php _e("First Name "); ?></td>
							<td><?php _e("Text "); ?></td>
							<td><?php _e("dg_firstname"); ?></td>
							<td><?php _e("None "); ?></td>
							<td><input type="checkbox" value="checked" name="dg_firstname_mandatory" <?php echo (@$dg_form_elem['dg_firstname_mandatory'] == 'checked') ? "checked='checked'": ""; ?> /></td>
							<td><input type="checkbox" value="checked" name="dg_firstname_visible" <?php echo (@$dg_form_elem['dg_firstname_visible']== 'checked') ? "checked='checked'": ""; ?> /></td>
						</tr>
						<tr>
							<td><?php _e("Last Name "); ?></td>
							<td><?php _e("Text "); ?></td>
							<td><?php _e("dg_lastname"); ?></td>
							<td><?php _e("None "); ?></td>
							<td><input type="checkbox" value="checked" name="dg_lastname_mandatory" <?php echo (@$dg_form_elem['dg_lastname_mandatory'] == 'checked') ? "checked='checked'": ""; ?> /></td>
							<td><input type="checkbox" value="checked" name="dg_lastname_visible" <?php echo (@$dg_form_elem['dg_lastname_visible']== 'checked') ? "checked='checked'": ""; ?> /></td>
						</tr>
						<tr>
							<td><?php _e("Email "); ?></td>
							<td><?php _e("Text "); ?></td>
							<td><?php _e("dg_email"); ?></td>
							<td><?php _e("None "); ?></td>
							<td><input type="checkbox" value="checked" name="dg_email_mandatory" <?php echo (@$dg_form_elem['dg_email_mandatory'] == 'checked') ? "checked='checked'": ""; ?> /></td>
							<td><input type="checkbox" value="checked" name="dg_email_visible" <?php echo (@$dg_form_elem['dg_email_visible']== 'checked') ? "checked='checked'": ""; ?> /></td>
						</tr>
						<tr>
							<td><?php _e("Phone "); ?></td>
							<td><?php _e("Text "); ?></td>
							<td><?php _e("dg_phone"); ?></td>
							<td><?php _e("None "); ?></td>
							<td><input type="checkbox" value="checked" name="dg_phone_mandatory" <?php echo (@$dg_form_elem['dg_phone_mandatory'] == 'checked') ? "checked='checked'": ""; ?> /></td>
							<td><input type="checkbox" value="checked" name="dg_phone_visible" <?php echo (@$dg_form_elem['dg_phone_visible']== 'checked') ? "checked='checked'": ""; ?> /></td>
						</tr>
						<tr>
							<td><?php _e("Country "); ?></td>
							<td><?php _e("Select "); ?></td>
							<td><?php _e("dg_country"); ?></td>
							<td>--<?php _e("Country "); ?>--</td>
							<td><input type="checkbox" value="checked" name="dg_country_mandatory" <?php echo (@$dg_form_elem['dg_country_mandatory'] == 'checked') ? "checked='checked'": ""; ?> /></td>
							<td><input type="checkbox" value="checked" name="dg_country_visible" <?php echo (@$dg_form_elem['dg_country_visible']== 'checked') ? "checked='checked'": ""; ?> /></td>
						</tr>
						<tr>
							<td><?php _e("State "); ?></td>
							<td><?php _e("Text "); ?></td>
							<td><?php _e("dg_state"); ?></td>
							<td><?php _e("None "); ?></td>
							<td><input type="checkbox" value="checked" name="dg_state_mandatory" <?php echo (@$dg_form_elem['dg_state_mandatory'] == 'checked') ? "checked='checked'": ""; ?> /></td>
							<td><input type="checkbox" value="checked" name="dg_state_visible" <?php echo (@$dg_form_elem['dg_state_visible']== 'checked') ? "checked='checked'": ""; ?> /></td>
						</tr>
						<?php
							$total = 20;
							while($total > 0){
								
								?>
								<tr>
									<td><input type="text" name="name_<?php echo $total; ?>" value="<?php echo $dg_form_elem[$total]['name']; ?>"/></td>
									<td>
										<select name="type_<?php echo $total; ?>">
											<?php
												foreach ($settings['forms'] as $forms => $form) {
													$cont_selected = '';
													if ($dg_form_elem[$total]['type'] === $form) {
														$cont_selected = 'selected="selected"';
													}
													?>
													<option value="<?php echo $form; ?>" <?php echo $cont_selected; ?> ><?php echo _e($forms); ?></option>
													<?php
												}
											?>
										</select>
									</td>
									<td><input type="text" name="u_name_<?php echo $total; ?>" value="<?php echo $dg_form_elem[$total]['uname']; ?>" /></td>
									<td><input type="text" name="initial_<?php echo $total; ?>" value="<?php echo @$dg_form_elem[$total]['initial']; ?>" style="width:100%"/></td>
									<td><input type="checkbox" value="checked" name="manadatory_<?php echo $total; ?>" <?php echo ($dg_form_elem[$total]['mandatory'] == 'checked') ? "checked='checked'": ""; ?> /></td>
									<td><input type="checkbox" value="checked" name="visible_<?php echo $total; ?>" <?php echo ($dg_form_elem[$total]['visible'] == 'checked') ? "checked='checked'": ""; ?> /></td>
								</tr>
								<?php
								$total --;
							}
						?>
					</tbody>
				</table>
				<p class="submit">
					<input class='button button-primary' type='submit' name='dg_checkout_settings' value='<?php _e('Save Options'); ?>'/>
				</p>
			</div>
		</form>

	<?php
}

//Mail Settings
function dg_dukagate_mail(){
	global $dukagate_mail;
	$dukagate_mails = $dukagate_mail->list_mails();
	$mail_types = DukaGate_Mail::mail_types();
	?>

		<div id="dg_mail_settings">
			<?php
			if (is_array($dukagate_mails) && count($dukagate_mails) > 0) {
				?>
				<p>
					<?php _e("Use");?> :<strong>%inv%</strong>, <strong>%shop%</strong>, <strong>%siteurl%</strong>, <strong>%info%</strong>, <strong>%order-log-transaction%</strong> , <strong>%fname%</strong>,  <strong>%lname%</strong>, <strong>%fullnames%</strong>, <strong>%invoice-link%</strong>, <strong>%username%</strong> , <strong>%password%</strong>  <?php _e("as", "dukagate");?> <?php _e("Invoice Number, Shop Name, Site URL, Order Form Information, Order URL, First Name, Last Name, Full Names, Invoice link, Username, Password", "dukagate");?>
				</p>
				<?php
				foreach ($dukagate_mails as $dukagate_mail) {
					?>
					<div id="dg_<?php echo $dukagate_mail->id; ?>" class="dg_mail_type">
						<div id="dg_title_<?php echo $dukagate_mail->id; ?>" class="dg_mail_title">
							<div class="dg_instructions"><?php _e("Click to show/hide"); ?></div>
							<h2><?php _e($mail_types[$dukagate_mail->type]); ?></h2>
						</div>
						<div class="dg_mail_opts" id="dg_opt_<?php echo $dukagate_mail->id; ?>">
							<div id="<?php echo $dukagate_mail->type; ?>_status"></div>
							<form method="POST" id="<?php echo $dukagate_mail->type; ?>">
								<input type="hidden" name="action" value="dg_save_mail_type" />
								<input type="hidden" name="mail_type" value="<?php echo $dukagate_mail->type; ?>" />
								<table class="form-table">
									<tr>
										<td>
											<p>
												<label><?php _e('Email To') ?><br />
												  <input type="text" class="regular-text" name="<?php echo $dukagate_mail->type.'_to'; ?>" value="<?php echo $dukagate_mail->to_mail; ?>"/>
												</label>
											</p>
										</td>
									</tr>
									<tr>
										<td>
											<p>
												<label><?php _e('Email Subject') ?><br />
												  <input type="text" class="regular-text" name="<?php echo $dukagate_mail->type.'_subject'; ?>" value="<?php echo $dukagate_mail->title; ?>"/>
												</label>
											</p>
										</td>
									</tr>
									<tr>
										<td>
											<p>
												<label><?php _e('Admin Mail') ?><br />
													<textarea name="<?php echo $dukagate_mail->type.'_admin'; ?>" id="<?php echo $dukagate_mail->type.'_admin'; ?>"><?php echo $dukagate_mail->content_admin; ?></textarea>
													<script type="text/javascript">
														make_wyzz('<?php echo $dukagate_mail->type.'_admin'; ?>');
													</script>
												</label>
											</p>
										</td>
									</tr>
									<tr>
										<td>
											<p>
												<label><?php _e('User Mail') ?><br />
													<textarea name="<?php echo $dukagate_mail->type.'_user'; ?>" id="<?php echo $dukagate_mail->type.'_user'; ?>"><?php echo $dukagate_mail->content_user; ?></textarea>
													<script type="text/javascript">
														make_wyzz('<?php echo $dukagate_mail->type.'_user'; ?>');
													</script>
												</label>
											</p>
										</td>
									</tr>
								</table>
							<p>
								<input type="submit" class="button button-primary" name="save_email_settings" value="<?php _e("Update "); ?> <?php _e($mail_types[$dukagate_mail->type]); ?>" />
							</p>
							</form>
						</div>
						<script type="text/javascript">
							jQuery(document).ready(function(){
								jQuery('#dg_title_<?php echo $dukagate_mail->id; ?>').click(function(){
									jQuery('#dg_opt_<?php echo $dukagate_mail->id; ?>').slideToggle();
								});
								dukagate.email_update('<?php echo $dukagate_mail->type; ?>');
							});
						</script>
					</div>
					<?php
				}
				?>
				<?php
			}else{
				_e("No Mail Settings Found!!");
			}
			?>
		</div>

	<?php
}

//Save mail settings
if (@$_REQUEST['action'] === 'dg_save_mail_type') {
	add_action( 'init', 'dg_update_mail_type');
}

function dg_update_mail_type(){
	global $dukagate_mail;
	
	$mail_type = $_REQUEST['mail_type'];
	$to = $_REQUEST[$mail_type.'_to'];
	$subject = $_REQUEST[$mail_type.'_subject'];
	$admin = $_REQUEST[$mail_type.'_admin'];
	$user = $_REQUEST[$mail_type.'_user'];
	$response = $dukagate_mail->update_mail($mail_type, $to, $subject, $admin, $user);
	header('Content-type: application/json; charset=utf-8');
	echo DukaGate::array_to_json(array('success' => 'true', 'response' => $response));
	exit();
}

//Settings
function dg_dukagate_settings(){
	global $dukagate_settings;
	global $dukagate;
	
	if(@$_POST['dg_settings']){
		$shopname = $_POST['shopname'];
		$address = $_POST['address'];
		$state_province = $_POST['state_province'];
		$postal = $_POST['postal'];
		$city = $_POST['city'];
		$country = $_POST['country'];
		$currency = $_POST['currency'];
		$currency_symbol = $_POST['currency_symbol'];
		$currency_position = $_POST['currency_position'];
		$force_login = $_POST['force_login'];
		$register_user = $_POST['register_user'];
		$checkout_page = $_POST['checkout_page'];
		$thankyou_page = $_POST['thankyou_page'];
		$tax_rate = $_POST['tax_rate'];
		$discounts = ($_POST['discounts'] == 'checked') ? "true": "false";
		$shipping = ($_POST['shipping'] == 'checked') ? "true": "false";
		
		$opts = array(
						'shopname' => $shopname, 
						'address' => $address,
						'state_province' => $state_province,
						'postal' => $postal,
						'city' => $city,
						'country' => $country,
						'currency' => $currency,
						'currency_symbol' => $currency_symbol,
						'currency_position' => $currency_position,
						'force_login' => $force_login,
						'register_user' => $register_user,
						'checkout_page' => $checkout_page,
						'thankyou_page' => $thankyou_page,
						'discounts' => $discounts,
						'shipping' => $shipping,
						'tax_rate' => $tax_rate);
		update_option('dukagate_shop_settings', $opts);
		
		
	}
	
	$dg_shop_settings = get_option('dukagate_shop_settings');
	if(empty($dg_shop_settings)) {
		$dg_shop_settings = $dukagate->get_default_settings();;
	}
	
	

	$dg_dukagate_settings = $dukagate_settings->get_settings();
	$dg_currency_codes = $dg_dukagate_settings['currency'];
	$dg_country_code_name = $dg_dukagate_settings['country'];
	?>

			<form method="POST" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
				<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row"><label for="shopname"><?php _e("Name of shop"); ?>: </label></th>
							<td><input id="shopname" name="shopname" type="text" class="regular-text" value="<?php echo $dg_shop_settings['shopname']; ?>" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="address"><?php _e("Address"); ?>: </label></th>
							<td><input id="address" name="address" type="text" value="<?php echo $dg_shop_settings['address']; ?>" class="regular-text" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="state_province"><?php _e("State / Province"); ?>: </label></th>
							<td><input id="state_province" type="text" name="state_province" value="<?php echo $dg_shop_settings['state_province']; ?>" class="regular-text" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="postal"><?php _e("Postal Code"); ?>: </label></th>
							<td><input id="postal" name="postal" type="text" value="<?php echo $dg_shop_settings['postal']; ?>" class="regular-text" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="city"><?php _e("City / Town"); ?>: </label></th>
							<td><input id="city" name="city" type="text" value="<?php echo $dg_shop_settings['city']; ?>" class="regular-text" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="country"><?php _e("Country "); ?>: </label></th>
							<td>
								<select name="country" style="width: 240px;">
									<?php
									foreach ($dg_country_code_name as $country_code => $country_name) {
										$cont_selected = '';
										if ($dg_shop_settings['country'] === $country_code) {
											$cont_selected = 'selected="selected"';
										}
										echo '<option value="' . $country_code . '" ' . $cont_selected . '>' . __($country_name) . '</option>';
									}
									?>
								</select>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="tax_rate"><?php _e("Tax Rate"); ?>: </label></th>
							<td>
								<input id="tax_rate" type="text" name="tax_rate" value="<?php echo $dg_shop_settings['tax_rate']; ?>" class="regular-text" size="2"/> %
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="currency"><?php _e("Currency "); ?>: </label></th>
							<td>
								<select name="currency">
									<?php
										foreach ($dg_currency_codes as $dg_currency_code) {
											?>
											<option value="<?php echo $dg_currency_code;?>" <?php if ($dg_shop_settings['currency'] === $dg_currency_code) {echo 'selected="selected"';}?>><?php echo $dg_currency_code;?></option>
											<?php
										}
									?>
								</select>
							</td>
						</tr>

						<tr valign="top">
							<th scope="row"><label for="currency_symbol"><?php _e("Currency Symbol"); ?>: </label></th>
							<td><input id="currency_symbol" type="text" name="currency_symbol" value="<?php echo $dg_shop_settings['currency_symbol']; ?>" class="regular-text" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="currency_position"><?php _e("Currency Position"); ?>: </label></th>
							<td>
								<select name="currency_position">
									<option value="left" <?php selected($dg_shop_settings['currency_position'], 'left'); ?>><?php _e('Left', 'dukagate') ?></option>
									<option value="right" <?php selected($dg_shop_settings['currency_position'], 'right'); ?>><?php _e('Right', 'dukagate') ?></option>
								</select>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="register_user"><?php _e("Register User"); ?>: </label></th>
							<td>
								<select name="register_user">
									<option value="yes" <?php selected($dg_shop_settings['register_user'], 'yes'); ?>><?php _e('Yes', 'dukagate') ?></option>
									<option value="no" <?php selected($dg_shop_settings['register_user'], 'no'); ?>><?php _e('No', 'dukagate') ?></option>
								</select>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="checkout_page"><?php _e("Checkout Page"); ?>: </label></th>
							<td>
								<select name="checkout_page">
									<?php 
									$pages = get_pages(); 
									foreach ( $pages as $pagg ) {
										$cont_selected = '';
										if (intval($dg_shop_settings['checkout_page']) === $pagg->ID) {
											$cont_selected = 'selected="selected"';
										}
										$option = '<option value="' .$pagg->ID. '" '.$cont_selected.'>';
										$option .= $pagg->post_title;
										$option .= '</option>';
										echo $option;
									}
									?>
								</select>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="thankyou_page"><?php _e("Thank You Page"); ?>: </label></th>
							<td>
								<select name="thankyou_page">
									<?php 
									$pages = get_pages(); 
									foreach ( $pages as $pagg ) {
										$cont_selected = '';
										if (intval($dg_shop_settings['thankyou_page']) === $pagg->ID) {
											$cont_selected = 'selected="selected"';
										}
										$option = '<option value="' .$pagg->ID. '" '.$cont_selected.'>';
										$option .= $pagg->post_title;
										$option .= '</option>';
										echo $option;
									}
									?>
								</select>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="discounts"><?php _e("Enable Discounts"); ?>: </label></th>
							<td>
								<input type="checkbox" value="checked" name="discounts" <?php echo (@$dg_shop_settings['discounts'] == 'true') ? "checked='checked'": ""; ?>/>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="shipping"><?php _e("Enable Shipping"); ?>: </label></th>
							<td>
								<input type="checkbox" value="checked" name="shipping" <?php echo (@$dg_shop_settings['shipping'] == 'true') ? "checked='checked'": ""; ?>/>
							</td>
						</tr>
					</tbody>
				</table>
				<p class="submit">
					<input class='button button-primary' type='submit' name='dg_settings' value='<?php _e('Save Options'); ?>' id='submitbutton' />
				</p>
			
	<?php
}


function dg_dukagate_advanced_settings(){
	
	global $dukagate;
	
	if(@$_POST['dg_advanced_settings']){
		$custom_products = ($_POST['custom_products'] == 'checked') ? "true": "false";
		$max_quantity = $_POST['max_quantity'];
		$up_selling_page = $_POST['up_selling_page'];
		$up_selling_page_checkout = ($_POST['up_selling_page_checkout'] == 'checked') ? "true": "false";
		$products_page = $_POST['products_page'];
		$checkout_prod_image = ($_POST['checkout_prod_image'] == 'checked') ? "true": "false";
		$checkout_prod_image_url = $_POST['checkout_prod_image_url'];
		$checkout_prod_image_width = $_POST['checkout_prod_image_width'];
		$checkout_prod_image_height = $_POST['checkout_prod_image_height'];
		$checkout_gateway_image = ($_POST['checkout_gateway_image'] == 'checked') ? "true": "false"; 
		$products_image = ($_POST['products_image'] == 'checked') ? "true": "false"; 
		$pdf_invoices = ($_POST['pdf_invoices'] == 'checked') ? "true": "false"; 
		$pdf_invoice_file = $_POST['pdf_invoice_file'];
		
		
		$opts = array(
						'custom_products' => $custom_products, 
						'max_quantity' => $max_quantity,
						'up_selling_page' => $up_selling_page,
						'up_selling_page_checkout' => $up_selling_page_checkout,
						'products_page' => $products_page,
						'checkout_prod_image' => $checkout_prod_image,
						'checkout_prod_image_url' => $checkout_prod_image_url,
						'checkout_prod_image_width' => $checkout_prod_image_width,
						'checkout_prod_image_height' => $checkout_prod_image_height,
						'checkout_gateway_image' => $checkout_gateway_image,
						'products_image' => $products_image,
						'pdf_invoices' => $pdf_invoices,
						'pdf_invoice_file' => $pdf_invoice_file);
		update_option('dukagate_advanced_shop_settings', $opts);
		
		
	}
	
	
	if(!get_option('dukagate_advanced_shop_settings')) {
		$dg_shop_settings = $dukagate->get_advanced_settings();;
	}else {
		$dg_shop_settings = get_option('dukagate_advanced_shop_settings');
	}
	if(!$dg_shop_settings['products_image']){
		$dg_shop_settings['products_image'] = 'true';
	}
	?>

			<form method="POST" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
				<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row"><label for="custom_products"><?php _e("Use Custom Product Posts","dg-lang"); ?>: </label></th>
							<td><input type="checkbox" value="checked" name="custom_products" <?php echo ($dg_shop_settings['custom_products'] == 'true') ? "checked='checked'": ""; ?>/></td>
						</tr>
						<tr valign="top">
							<th scope="row">
								<label for="max_quantity"><?php _e("Maximum product in cart","dg-lang"); ?> <em>(<?php _e("defaults to 30"); ?>)</em>: </label>
							</th>
							<td><input id="max_quantity" type="text" name="max_quantity" value="<?php echo $dg_shop_settings['max_quantity']; ?>" /></td></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="products_page"><?php _e("Grouped Products Page","dg-lang"); ?>: </label></th>
							<td>
								<select name="products_page">
									<?php 
									$pages = get_pages(); 
									foreach ( $pages as $pagg ) {
										$cont_selected = '';
										if (intval($dg_shop_settings['products_page']) === $pagg->ID) {
											$cont_selected = 'selected="selected"';
										}
										$option = '<option value="' .$pagg->ID. '" '.$cont_selected.'>';
										$option .= $pagg->post_title;
										$option .= '</option>';
										echo $option;
									}
									?>
								</select>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="up_selling_page"><?php _e("Up Selling Page","dg-lang"); ?>: </label></th>
							<td>
								<select name="up_selling_page">
									<?php 
									$pages = get_pages(); 
									foreach ( $pages as $pagg ) {
										$cont_selected = '';
										if (intval($dg_shop_settings['up_selling_page']) === $pagg->ID) {
											$cont_selected = 'selected="selected"';
										}
										$option = '<option value="' .$pagg->ID. '" '.$cont_selected.'>';
										$option .= $pagg->post_title;
										$option .= '</option>';
										echo $option;
									}
									?>
								</select>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="up_selling_page_checkout"><?php _e("Up Selling page before checkout","dg-lang"); ?>: </label></th>
							<td>
								<input type="checkbox" value="checked" name="up_selling_page_checkout" <?php echo ($dg_shop_settings['up_selling_page_checkout'] == 'true') ? "checked='checked'": ""; ?>/>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="checkout_prod_image"><?php _e("Checkout Product Image","dg-lang"); ?> (<em><?php _e("shows product image thumbnail on checkout page"); ?></em>): </label></th>
							<td><input type="checkbox" value="checked" name="checkout_prod_image" <?php echo ($dg_shop_settings['checkout_prod_image'] == 'true') ? "checked='checked'": ""; ?>/></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="checkout_prod_image_url"><?php _e("Checkout Product Image URL","dg-lang"); ?>: </label></th>
							<td><input id="checkout_prod_image_url" type="text" name="checkout_prod_image_url" value="<?php echo @$dg_shop_settings['checkout_prod_image_url']; ?>" class="regular-text" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="checkout_prod_image_width"><?php _e("Checkout Product Image Width","dg-lang"); ?>: </label></th>
							<td><input id="checkout_prod_image_width" type="text" name="checkout_prod_image_width" value="<?php echo @$dg_shop_settings['checkout_prod_image_width']; ?>" class="regular-text" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="checkout_prod_image_height"><?php _e("Checkout Product Image Height","dg-lang"); ?>: </label></th>
							<td><input id="checkout_prod_image_height" type="text" name="checkout_prod_image_height" value="<?php echo @$dg_shop_settings['checkout_prod_image_height']; ?>" class="regular-text" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="checkout_gateway_image"><?php _e("Display Checkout Payment Gateway Image (Not Yet Implemented)","dg-lang"); ?> (<em><?php _e("shows payment gateway image on checkout page instead of text"); ?></em>): </label></th>
							<td><input type="checkbox" value="checked" name="checkout_gateway_image" <?php echo ($dg_shop_settings['checkout_gateway_image'] == 'true') ? "checked='checked'": ""; ?>/></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="products_image"><?php _e("Show Product Images"); ?> (<em><?php _e("show or hide product images","dg-lang"); ?></em>): </label></th>
							<td><input type="checkbox" value="checked" name="products_image" <?php echo ($dg_shop_settings['products_image'] == 'true') ? "checked='checked'": ""; ?>/></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="pdf_invoices"><?php _e("Enable PDF Invoice"); ?> (<em><?php _e("enable or disable pdf invoices","dg-lang"); ?></em>): </label></th>
							<td><input type="checkbox" value="checked" name="pdf_invoices" <?php echo ($dg_shop_settings['pdf_invoices'] == 'true') ? "checked='checked'": ""; ?>/></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="pdf_invoice_file"><?php _e("Invoice PDF Template","dg-lang"); ?>: </label></th>
							<td>
								<select name="pdf_invoice_file">
									<?php 
									$invoice_templates = DukaGate_Invoice::list_files();
									foreach ( $invoice_templates as $invoice_template ) {
										$cont_selected = '';
										if (intval($dg_shop_settings['pdf_invoice_file']) === $invoice_template) {
											$cont_selected = 'selected="selected"';
										}
										$option = '<option value="' .$invoice_template. '" '.$cont_selected.'>';
										$option .= ucfirst($invoice_template);
										$option .= '</option>';
										echo $option;
									}
									?>
								</select>
							</td>
						</tr>
					</tbody>
				</table>
				<p class="submit">
					<input class='button-primary' type='submit' name='dg_advanced_settings' value='<?php _e('Save Options',"dg-lang"); ?>' id='submitbutton' />
				</p>
			</form>
	<?php
}


/**
 * Discount Management
 */
function dg_dukagate_discounts(){
	global $dukagate_disc;
	if(isset($_REQUEST['act'])){
		if(isset($_REQUEST['edit'])){
			dg_disc_view($_REQUEST['id']);
		}else{
			if($_REQUEST['act'] == 'new'){
				dg_disc_add();
			}
		}
		
	}else{
		if(isset($_REQUEST['action'])){
			if($_REQUEST['action'] == 'del'){
				$dukagate_disc->delete_discount($_REQUEST['id']);
			}
		}
		$form_url = admin_url("admin.php?page=dukagate-discounts&act=new");
		$del_url = admin_url("admin.php?page=dukagate-discounts");
		$discounts = $dukagate_disc->list_discounts();
		$content = '<div class="wrap">';
		$content .= '<h2>Dukagate Discounts</div></h2>';
		$content .= '<a class="button-primary" href="'.$form_url.'" title="Add">Add New</a><br/><br/><br/>';
		if (is_array($discounts) && count($discounts) > 0) {
			$count = 1;
			$content .= '<table class="widefat">';
			$content .= '<thead>';
			$content .= '<tr>';
			$content .= '<th>Number</th>';
			$content .= '<th>Code</th>';
			$content .= '<th>Amount</th>';
			$content .= '<th>Type</th>';
			$content .= '<th>Valid</th>';
			$content .= '<th>Date Created</th>';
			$content .= '<th>Actions</th>';
			$content .= '</tr>';
			$content .= '</thead>';
			$content .= '<tfoot>';
			$content .= '<tr>';
			$content .= '<th>Number</th>';
			$content .= '<th>Code</th>';
			$content .= '<th>Amount</th>';
			$content .= '<th>Type</th>';
			$content .= '<th>Valid</th>';
			$content .= '<th>Date Created</th>';
			$content .= '<th>Actions</th>';
			$content .= '</tr>';
			$content .= '</tfoot>';
			foreach ($discounts as $discount) {
				$valid = $discount->valid;
				if($valid == 0 || $valid = '0'){
					$valid = 'Valid';
				}else{
					$valid = 'Expired';
				}
				$content .= '<tbody>';
				$content .= '<tr>';
				$content .= '<td>'.$count.'</td>';
				$content .= '<td>'.$discount->code.'</td>';
				$content .= '<td>'.$discount->amount.'</td>';
				$content .= '<td>'.Dukagate_Discounts::get_type($discount->type).'</td>';
				$content .= '<td>'.$valid.'</td>';
				$content .= '<td>'.$discount->timestamp.'</td>';
				$content .= '<td>';
				$content .= '<a href="'.$form_url.'&edit=true&id='.$discount->id.'" title="Edit">Edit</a> <a href="'.$del_url.'&action=del&id='.$discount->id.'" title="Delete">Delete</a>';
				$content .= '</td>';
				$content .= '<tr>';
				$content .= '</tbody>';
				$count++;
			}
			$content .= '</table>';
		}else{
			$content .= '<h4>No Discounts found</h4>';
		}
		
		$content .= '</div>';
		echo $content;
	}
}


/**
 * New discount
 */
function dg_disc_add(){
	global $dukagate_disc;
	if(isset($_REQUEST['disc_action'])){
		$dukagate_disc->save_discount($_REQUEST);
	}
	?>
	<div class="wrap">
		<h2><div class="dp_disc_hd"><div class="dp_dics_img" id="dp_disc_hd_img">&nbsp;</div>New Discount</div></h2>
		Items marked with <span class="req"> *</span> are required
		<form method="POST" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
			<ul>
				<li>
					<table width="100%" border="0" class="widefat">
					  <tr>
						<th width="22%" align="left" scope="row">Amount <span class="req"> *</span></th>
						<td width="28%"><input type="text" maxlength="45" size="10" name="disc_amount"  /></td>
						<td width="50%">(Discount amount)</td>
					  </tr>
					  <tr>
						<th align="left" scope="row">Code</th>
						<td><input type="text" name="disc_code"  value="<?php echo $discount->code; ?>"/> </td>
						<td>(Optional discount code. If left blank it will be generated)</td>
					  </tr>
					  <tr>
						<th align="left" scope="row">Type <span class="req"> *</span></th>
						<td>
							<select name="disc_type" >
								<option value="" >--Select Type--</option>
								<?php
									$types = Dukagate_Discounts::discount_types();
									foreach ($types as $type => $t) {
										?>
										<option value="<?php echo $type; ?>" ><?php echo $t; ?></option>
										<?php
									}
								?>
							</select>
						</td>
						<td>(Discount type, can be a percentage or a fixed value)</td>
					  </tr>
					  <tr>
						<th align="left" scope="row">&nbsp;</th>
						<td><input type='submit' value='<?php _e("Save"); ?>' class='button-secondary' name="disc_action" /></td>
						<td>&nbsp;</td>
					  </tr>
					</table>
				</li> 
			</ul>
		</form>
	</div>
	<?php
}


/**
 * view discount
 */
function dg_disc_view($id){
	global $dukagate_disc;
	if(isset($_REQUEST['disc_action_update'])){
		$dukagate_disc->update_discount($_REQUEST);
	}
	$discount = $dukagate_disc->get_discount($id);
	?>
	<div class="wrap">
		<h2>Edit <?php echo $discount->code; ?></h2>
		Items marked with <span class="req"> *</span> are required
		<form method="POST" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
			<input type="hidden" name="disc_id" value="<?php echo $discount->id; ?>" />
			<ul>
				<li>
					<table width="100%" border="0" class="widefat">
					  <tr>
						<th width="22%" align="left" scope="row">Amount <span class="req"> *</span></th>
						<td width="28%"><input type="text" maxlength="45" size="10" name="disc_amount"  value="<?php echo $discount->amount; ?>"/></td>
						<td width="50%">(Discount amount)</td>
					  </tr>
					  <tr>
						<th align="left" scope="row">Code</th>
						<td><input type="text" name="disc_code"  value="<?php echo $discount->code; ?>"/> </td>
						<td>(Optional discount code. If left blank it will be generated)</td>
					  </tr>
					  <tr>
						<th align="left" scope="row">Type <span class="req"> *</span></th>
						<td>
							<select name="disc_type" >
								<option value="" >--Select Type--</option>
								<?php
									$types = Dukagate_Discounts::discount_types();
									
									foreach ($types as $type => $t) {
										$selected = '';
										if(intval($type) == intval($discount->type)){
											$selected = 'selected="selected"';
										}
										?>
										<option value="<?php echo $type; ?>" <?php echo $selected; ?> ><?php echo $t; ?></option>
										<?php
									}
								?>
							</select>
						</td>
						<td>(Discount type, can be a percentage or a fixed value)</td>
					  </tr>
					  <tr>
						<th align="left" scope="row">&nbsp;</th>
						<td><input type='submit' value='<?php _e("Update"); ?>' class='button-secondary' name="disc_action_update" /></td>
						<td>&nbsp;</td>
					  </tr>
					</table>
				</li> 
			</ul>
		</form>
	</div>
	<?php
}

function dg_dukagate_tools(){
	if (isset($_POST['dg_import_xml'])) {
		global $wpdb;
		$delete_old_products = ($_POST['delete_old_products'] == 'checked') ? "true": "false"; 
		$post_type = 'dg_product';
		if($delete_old_products == 'true'){
			$sql = "SELECT `ID` FROM `{$wpdb->posts}` WHERE `post_type`='{$post_type}'";
			$post_ids_to_be_deleted = $wpdb->get_col($sql);
			$count = 0;
			$failed_to_delete = array();
			if (is_array($post_ids_to_be_deleted)) {
				foreach ($post_ids_to_be_deleted as $delete_id) {
					if (wp_delete_post( intval($delete_id), TRUE )) {
						$count++;
					}
					else {
						$failed_to_delete[] = $delete_id;
					}
				}
				echo '<h4>' . $count . ' products were deleted successfully.</h4>';
				if (!empty($failed_to_delete)) {
					$failed_to_delete = implode(', ', $failed_to_delete);
					echo '<h4>Failed to delete products with ID(s): ' . $failed_to_delete . '</h4>';
				}
			}
		}
		
		$xml = @simplexml_load_file($_FILES['xml_file']['tmp_name']);
		if($xml){
			$i = 0;
			$post_data = '';
			$post_title = '';
			$post_content = '';
			$post_price = '';
			$fixed_price = '';
			$main_image = '';
			$sku = '';
			$digital_file = '';
			$affiliate_url = '';
			$dg_posts = array();
			foreach($xml->children() as $child){
				$children = $child->children();
				if(count($children) > 0){
					foreach($children as $c){
						$atts = $c->attributes();
						if($atts['name'] == 'product_name')
							$post_title = (string)$c;
						if($atts['name'] == 'product_content')
							$post_content = (string)$c;
						if($atts['name'] == 'product_price')
							$post_price = (string)$c;
						if($atts['name'] == 'fixed_price')
							$fixed_price = (string)$c;
						if($atts['name'] == 'main_image')
							$main_image = (string)$c;
						if($atts['name'] == 'sku')
							$sku = (string)$c;
						if($atts['name'] == 'digital_file')
							$digital_file = (string)$c;
						if($atts['name'] == 'affiliate_url')
							$affiliate_url = (string)$c;
						$post_data = array(
												'post_title' => $post_title,
												'post_content' => $post_content,
												'post_status' => 'publish',
												'price' => $post_price,
												'fixed_price' => $fixed_price,
												'main_image' => $main_image,
												'sku' => 'sku',
												'digital_file' => $digital_file,
												'affiliate_url' => $affiliate_url
												);
						$dg_posts[$i] = $post_data;
					}
					$i++;
				}
			}
			$i = 0;
			if(count($dg_posts > 0)){
				foreach($dg_posts as $dg_post => $post){
					$post_data = array(
										'post_title' => html_entity_decode($post['post_title']),
										'post_content' => html_entity_decode($post['post_content']),
										'post_status' => 'publish',
										'post_type' => $post_type
										);
					$new_post_id = wp_insert_post($post_data);
					if ($new_post_id) {
						update_post_meta($new_post_id, 'price', $post['price']);
						update_post_meta($post_id, 'fixed_price', $fixed_price);
						update_post_meta($post_id, 'digital_file', $digital_file);
						update_post_meta($post_id, 'sku', $sku);
						update_post_meta($post_id, 'affiliate_url', $affiliate_url);
						$i++;
					}
				}
			}
			if ($i > 0) {
				echo '<h4>' . count($dg_posts) . ' product(s) were created successfully.</h4>';
			}
		}else{
			echo '<h4>No file selected.</h4>';
		}
	}
	?>
	<div class="wrap">
		<h2>Dukagate Tools</h2>
		<form method="POST" enctype="multipart/form-data" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
			<table width="100%" border="0" class="widefat">
				<tr>
					<th align="left" scope="row"><?php _e("Export"); ?></th>
					<td id="dg_prod_export_td"><a href="javascript:;" class='button-secondary' id="dg_prod_export">Click Here to export products</a></td>
				</tr>
				<tr>
					<th align="left" scope="row"><?php _e("Import"); ?></th>
					<td>
						<input id="xml_file" type="file" name="xml_file" /><br/>  
						<label><?php _e("Delete old products ?"); ?> <input type="checkbox" value="checked" name="delete_old_products"/></label> <br/>
						<input type="submit" class='button-primary' name="dg_import_xml" value="<?php _e("Import File"); ?>"/>
					</td>
				</tr>
			</table>
		</form>
		<?php do_action('dukagate_tools'); ?>
		<script type="text/javascript">
			jQuery(document).ready(function(){
				jQuery('#dg_prod_export').on("click", function(){
					var url = '<?php echo get_bloginfo('url');  ?>';
					window.location.href= url+'?action=dg_export_products';
				});
			});
		</script>
	</div>
	<?php
}

if (@$_REQUEST['action'] === 'dg_export_products') {
	add_action( 'init', 'dg_export_products');
}
/**
 * Export products
 */
function dg_export_products(){
	global $dukagate;
	$args = array();
	$args['post_type'] = 'dg_product';
	$get_posts = new WP_Query;
	$products = $get_posts->query($args);
	if (is_array($products) && count($products) > 0) {
		$xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"utf-8\" ?><products></products>");
		foreach ($products as $product) {
			$main_image = $dukagate->product_image($product->ID);
			$content_price = get_post_meta($product->ID, 'price', true);
			$fixed_price = get_post_meta($product->ID, 'fixed_price', true);
			$sku = get_post_meta($product->ID, 'sku', true);
			$digital_file = get_post_meta($product->ID, 'digital_file', true);
			$affiliate_url = get_post_meta($product->ID, 'affiliate_url', true);
			
			$prod = $xml->addChild('product');
			
			$column = $prod->addChild('column',htmlspecialchars($product->post_title));
			$column->addAttribute('name', 'product_name');
			
			$column = $prod->addChild('column',$content_price);
			$column->addAttribute('name', 'product_price');
			
			$column = $prod->addChild('column',$fixed_price);
			$column->addAttribute('name', 'fixed_price');
			
			$column = $prod->addChild('column',$main_image);
			$column->addAttribute('name', 'main_image');
			
			$column = $prod->addChild('column',$sku);
			$column->addAttribute('name', 'sku');
			
			$column = $prod->addChild('column',$digital_file);
			$column->addAttribute('name', 'digital_file');
			
			$column = $prod->addChild('column',$affiliate_url);
			$column->addAttribute('name', 'affiliate_url');
			
			$column = $prod->addChild('column',htmlspecialchars($product->post_content));
			$column->addAttribute('name', 'post_content');
		}
		header( 'Content-disposition: attachment; filename=dukagate_products.xml');
		header('Content-type: text/xml');
		print($xml->asXML());
	}
	die();
}

//Save or delete variation of product
if (@$_REQUEST['action'] === 'dg_change_variation') {
	add_action( 'init', 'dg_change_variation');
}

function dg_change_variation(){
	$product_id = $_REQUEST['product'];
	$variationid = $_REQUEST['variation'];
	$action = $_REQUEST['dg_action'];
	$type = $_REQUEST['type'];
	$value = $_REQUEST['value'];
	$options = get_option('dg_product_variations');
	$total = count($options) + 1;
	if(!empty($action)){
		if($action == 'delete'){
			unset($options[$product_id][$variationid]);
		}
	}else{
		if(empty($variationid)){
			$options[$product_id][$total]['type'] = $type;
			$options[$product_id][$total]['value'] = $value;
		}else{
			$options[$product_id][$variationid]['type'] = $type;
			$options[$product_id][$variationid]['value'] = $value;
			$total  = $variationid;
		}
	}
	
	update_option('dg_product_variations', $options);
	
	$html = '<tr id="dg_var_'.$total.'">
					<td>'.$type.'</td>
					<td>'.$value.'</td>
					<td><a href="javascript:;" onclick="dukagate.del_variation(\''.$total.'\',\''.$product_id.'\')">Delete</a></td>
				</tr>';
	echo DukaGate::array_to_json(array('success' => 'true', 'html' => $html));
	exit();
}
?>