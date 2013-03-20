<?php
/**
 * Dukagate Admin functions
 * 
 */
 
 
//Create Admin menu

add_action('admin_menu', 'dg_create_admin_menu');
function dg_create_admin_menu(){
	add_object_page('DukaGate', 'DukaGate', 'edit_others_posts', 'dukagate-order-log', '', DG_DUKAGATE_URL . '/images/dg_icon.png');
	add_submenu_page('dukagate-order-log', __('DukaGate Order Log'), __('Order Log'), 'edit_others_posts', 'dukagate-order-log', 'dg_dukagate_order_log');
	add_submenu_page('dukagate-order-log', __('DukaGate Payment Options'), __('Payment Options'), 'edit_others_posts', 'dukagate-payment-options', 'dg_dukagate_paymnet');
	add_submenu_page('dukagate-order-log', __('DukaGate Shipping Options'), __('Shipping Options'), 'edit_others_posts', 'dukagate-shipping-options', 'dg_dukagate_shipping');
	add_submenu_page('dukagate-order-log', __('DukaGate CheckOut Settings'), __('Check Out Settings'), 'edit_others_posts', 'dukagate-checkout-options', 'dg_dukagate_checkout');
	add_submenu_page('dukagate-order-log', __('DukaGate Mail Settings'), __('Mail Settings'), 'edit_others_posts', 'dukagate-mail-options', 'dg_dukagate_mail');
	add_submenu_page('dukagate-order-log', __('DukaGate Settings'), __('Settings'), 'edit_others_posts', 'dukagate-settings', 'dg_dukagate_settings');
	add_submenu_page('dukagate-order-log', __('DukaGate Advanced Settings'), __('Advanced Settings'), 'edit_others_posts', 'dukagate-advanced-settings', 'dg_dukagate_advanced_settings');
	
}



//Order Log
function dg_dukagate_order_log(){
	global $dukagate;
	if(@$_REQUEST['order_id']){
		dg_dukagate_order_log_info($_REQUEST['order_id']);
	}else{
	$order_logs = $dukagate->dg_list_order_logs();
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
							<td align="left"><a href="javascript:void(null);" onclick="dukagate.delete_order('<?php echo $log->id; ?>');"><?php _e("Delete"); ?></a>&nbsp;&nbsp; | &nbsp;&nbsp;<a href="<?php echo $form_url.$log->id ;?>"><?php _e("View"); ?></a></td>
						</tr>
						<?php
						$count++;
					}
					?>
				</tbody>
			</table>
		<?php } else{
			__("No Records found");
		}?>
	</div>
	<?php
	}
}

//Get Order Log detail
function dg_dukagate_order_log_info($id){
	global $dukagate;
	$dg_shop_settings = get_option('dukagate_shop_settings');
	$order_log = $dukagate->dg_get_order_log_by_id($id);
	?>
	<div class="wrap">
		<h2><?php _e("Viewing order ").' '._e($order_log->invoice); ?></h2>
		<div class="csv_export">
			<a href="javascript:;" onclick="dukagate.order_csv_export('<?php echo $order_log->id; ?>');">Export as CSV</a>
		</div>
		<table width="100%" class="widefat" style="background-color: #FFFFFF;">
			<tr>
				<td><strong><?php _e("Invoice"); ?></strong></td>
				<td><?php echo $order_log->invoice; ?></td>
			</tr>
			<tr>
				<td><strong><?php _e("Status"); ?></strong></td>
				<td><span id="dg_order_status"><?php _e($order_log->payment_status); ?></span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:;" onclick="dukagate.change_order_status('<?php echo $order_log->id; ?>');"><?php _e("Change Status"); ?></a></td>
			</tr>
			<tr>
				<td><strong><?php _e("Date Created"); ?></strong></td>
				<td><?php echo $order_log->date; ?></td>
			</tr>
			<tr>
				<td><strong><?php _e("Total Shipping"); ?></strong></td>
				<td><?php echo $order_log->shipping; ?></td>
			</tr>
			<tr>
				<td><strong><?php _e("Total"); ?></strong></td>
				<td><?php echo $order_log->total; ?></td>
			</tr>
			<tr>
				<td><strong><?php _e("Products"); ?></strong></td>
				<td>
					<?php 
					$cnt  = '';
					$products =  DukaGate::json_to_array($order_log->products); 
					if (is_array($products) && count($products) > 0) {
						$total = 0.00;
						$total_discount = 0.00;
						$cnt .= '<table style="text-align:left" class="widefat">';
						$cnt .= '<tr>';
						$cnt .= '<th scope="col" width="30%">'.__("Product").'</th>';
						$cnt .= '<th scope="col" width="10%">'.__("Quantity").'</th>';
						$cnt .= '<th scope="col" width="30%">'.__("Price").'</th>';
						$cnt .= '<th scope="col" width="30%">'.__("Total").'</th>';
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
						$cnt .= '<td>'.__("Total Discount").'</td>';
						$cnt .= '<td>&nbsp;</td>';
						$cnt .= '<td>&nbsp;</td>';
						$cnt .= '<td>'.$dg_shop_settings['currency_symbol'].' '.number_format($order_log->discount,2).'</td>';
						$cnt .= '</tr>';
						$cnt .= '<tr>';
						$cnt .= '<td>'.__("Total Shipping").'</td>';
						$cnt .= '<td>&nbsp;</td>';
						$cnt .= '<td>&nbsp;</td>';
						$cnt .= '<td>'.$dg_shop_settings['currency_symbol'].' '.number_format(($order_log->shipping),2).'</td>';
						$cnt .= '</tr>';
						$cnt .= '<tr>';
						$cnt .= '<td>'.__("Total").'</td>';
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
				<td><strong><?php _e("Order Info"); ?></strong></td>
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
							?>
							<tr>
								<td><strong><?php _e($order['key']); ?></strong></td>
								<td><?php echo $order['value']; ?></td>
							</tr>
							<?php
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
	$dg_gateways = $dukagate->list_all_gateways();
	?>
	<div class="wrap">
		<h2><?php _e("Dukagate Payment Plugins"); ?></h2>
		<div id="dg_payments">
			<?php
			if (is_array($dg_gateways) && count($dg_gateways) > 0) {
				foreach ($dg_gateways as $dg_gateway) {
					?>
					<div id="dg_<?php echo $dg_gateway->id; ?>" class="dg_payment_gateway">
						<div id="dg_title_<?php echo $dg_gateway->id; ?>" class="dg_payment_title">
							<div class="dg_instructions"><?php _e("Click to show/hide settings"); ?></div>
							<h2><?php _e($dg_gateway->gateway_name); ?></h2>
						</div>
						<div class="dg_gateway_options" id="dg_opt_<?php echo $dg_gateway->id; ?>">
							<?php DukaGate::call_class_function($dg_gateway->gateway_class, 'set_up_options', $dg_gateway->gateway_slug); ?>
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
				_e("No Payment Gateways Found!!");
			}
			?>
		</div>
	</div>
	<?php
}



//Shipping Options
function dg_dukagate_shipping(){
	global $dukagate;
	$dg_gateways = $dukagate->list_all_shipping_gateways();
	?>
	<div class="wrap">
		<h2><?php _e("Dukagate Shipping Plugins","dg-lang"); ?></h2>
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
	</div>
	<?php
}


//Chekout options
function dg_dukagate_checkout(){
	global $dukagate_settings;
	$settings = $dukagate_settings->get_settings();
	
	if(@$_POST['dg_checkout_settings']){
		$total = 20;
		$form_elem = array();
		while($total > 0){
			$form_elem[$total]['name'] = @$_POST['name_'.$total];
			$form_elem[$total]['type'] = @$_POST['type_'.$total];
			$form_elem[$total]['uname'] = @$_POST['u_name_'.$total];
			$form_elem[$total]['initial'] = @$_POST['initial_'.$total];
			$form_elem[$total]['mandatory'] = (empty($_POST['manadatory_'.$total])) ? 'notchecked' : 'checked';
			$form_elem[$total]['visible'] = (empty($_POST['visible_'.$total])) ? 'notchecked' : 'checked';
			$total--;
		}
		$form_elem['dg_fullname_mandatory'] = (empty($_POST['dg_fullname_mandatory'])) ? 'notchecked' : 'checked';
		$form_elem['dg_fullname_visible'] = (empty($_POST['dg_fullname_visible'])) ? 'notchecked' : 'checked';
		$form_elem['dg_email_mandatory'] = (empty($_POST['dg_email_mandatory'])) ? 'notchecked' : 'checked';
		$form_elem['dg_email_visible'] = (empty($_POST['dg_email_visible'])) ? 'notchecked' : 'checked';
		update_option('dukagate_checkout_options', $form_elem);
		
	}
	
	$dg_form_elem = get_option('dukagate_checkout_options');
	
	$dukagate_settings->set_manadatory_forms('dg_fullname', $dg_form_elem);
	$dukagate_settings->set_manadatory_forms('dg_email', $dg_form_elem);
	
	?>
	<div class="wrap">
		<h2><?php _e("Dukagate Check Out Page Settings"); ?></h2>
		<form method="POST" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
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
													<option value="<?php echo $form; ?>" <?php echo $cont_selected; ?> ><?php echo _e($forms,'dg-lang'); ?></option>
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
					<input class='button-primary' type='submit' name='dg_checkout_settings' value='<?php _e('Save Options'); ?>'/>
				</p>
			</div>
		</form>
	</div>
	<?php
}

//Mail Settings
function dg_dukagate_mail(){
	global $dukagate_mail;
	$dukagate_mails = $dukagate_mail->list_mails();
	$mail_types = DukaGate_Mail::mail_types();
	?>
	<div class="wrap">
		<h2><?php _e("Dukagate Mail Settings"); ?></h2>
		<div id="dg_mail_settings">
			<?php
			if (is_array($dukagate_mails) && count($dukagate_mails) > 0) {
				?>
				<p>
					<?php _e("Use");?> :<strong>%details%</strong>, <strong>%inv%</strong>, <strong>%shop%</strong>, <strong>%siteurl%</strong>, <strong>%info%</strong>, <strong>%order-log-transaction%</strong> , <strong>%fname%</strong> <?php _e("as");?> <?php _e("Order Details, Invoice Number, Shop Name, Site URL, Order Form Information, Order URL, First Name");?>
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
								<input type="submit" name="save_email_settings" value="<?php _e("Update "); ?> <?php _e($mail_types[$dukagate_mail->type]); ?>" />
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
		$checkout_page = $_POST['checkout_page'];
		$thankyou_page = $_POST['thankyou_page'];
		$discounts = ($_POST['discounts'] == 'checked') ? "true": "false";
		
		$opts = array(
						'shopname' => $shopname, 
						'address' => $address,
						'state_province' => $state_province,
						'postal' => $postal,
						'city' => $city,
						'country' => $country,
						'currency' => $currency,
						'currency_symbol' => $currency_symbol,
						'checkout_page' => $checkout_page,
						'thankyou_page' => $thankyou_page,
						'discounts' => $discounts);
		update_option('dukagate_shop_settings', $opts);
		
		
	}
	
	
	if(!get_option('dukagate_shop_settings')) {
		$dg_shop_settings = $dukagate->get_default_settings();;
	}else {
		$dg_shop_settings = get_option('dukagate_shop_settings');
	}
	
	

	$dg_dukagate_settings = $dukagate_settings->get_settings();
	$dg_currency_codes = $dg_dukagate_settings['currency'];
	$dg_country_code_name = $dg_dukagate_settings['country'];
	?>
		<div class="wrap">
			<h2><?php _e("Dukagate Settings"); ?></h2>
			<form method="POST" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
				<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row"><label for="shopname"><?php _e("Name of shop"); ?>: </label></th>
							<td><input id="shopname" name="shopname" class="regular-text" value="<?php echo $dg_shop_settings['shopname']; ?>" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="address"><?php _e("Address"); ?>: </label></th>
							<td><input id="address" name="address" value="<?php echo $dg_shop_settings['address']; ?>" class="regular-text" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="state_province"><?php _e("State / Province"); ?>: </label></th>
							<td><input id="state_province" name="state_province" value="<?php echo $dg_shop_settings['state_province']; ?>" class="regular-text" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="postal"><?php _e("Postal Code"); ?>: </label></th>
							<td><input id="postal" name="postal" value="<?php echo $dg_shop_settings['postal']; ?>" class="regular-text" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="city"><?php _e("City / Town"); ?>: </label></th>
							<td><input id="city" name="city" value="<?php echo $dg_shop_settings['city']; ?>" class="regular-text" /></td>
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
							<td><input id="currency_symbol" name="currency_symbol" value="<?php echo $dg_shop_settings['currency_symbol']; ?>" class="regular-text" /></td>
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
					</tbody>
				</table>
				<p class="submit">
					<input class='button-primary' type='submit' name='dg_settings' value='<?php _e('Save Options'); ?>' id='submitbutton' />
				</p>
			</form>
		</div>
	<?php
}


function dg_dukagate_advanced_settings(){
	global $dukagate_settings;
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
						'checkout_gateway_image' => $checkout_gateway_image);
		update_option('dukagate_advanced_shop_settings', $opts);
		
		
	}
	
	
	if(!get_option('dukagate_advanced_shop_settings')) {
		$dg_shop_settings = $dukagate->get_advanced_settings();;
	}else {
		$dg_shop_settings = get_option('dukagate_advanced_shop_settings');
	}
	?>
		<div class="wrap">
			<h2><?php _e("Dukagate Advanced Settings"); ?></h2>
			<form method="POST" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
				<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row"><label for="custom_products"><?php _e("Use Custom Product Posts"); ?>: </label></th>
							<td><input type="checkbox" value="checked" name="custom_products" <?php echo ($dg_shop_settings['custom_products'] == 'true') ? "checked='checked'": ""; ?>/></td>
						</tr>
						<tr valign="top">
							<th scope="row">
								<label for="max_quantity"><?php _e("Maximum product in cart"); ?> <em>(<?php _e("defaults to 30"); ?>)</em>: </label>
							</th>
							<td><input id="max_quantity" name="max_quantity" value="<?php echo $dg_shop_settings['max_quantity']; ?>" /></td></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="products_page"><?php _e("Grouped Products Page"); ?>: </label></th>
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
							<th scope="row"><label for="up_selling_page"><?php _e("Up Selling Page"); ?>: </label></th>
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
							<th scope="row"><label for="up_selling_page_checkout"><?php _e("Up Selling page before checkout"); ?>: </label></th>
							<td>
								<input type="checkbox" value="checked" name="up_selling_page_checkout" <?php echo ($dg_shop_settings['up_selling_page_checkout'] == 'true') ? "checked='checked'": ""; ?>/>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="checkout_prod_image"><?php _e("Checkout Product Image"); ?> (<em><?php _e("shows product image thumbnail on checkout page"); ?></em>): </label></th>
							<td><input type="checkbox" value="checked" name="checkout_prod_image" <?php echo ($dg_shop_settings['checkout_prod_image'] == 'true') ? "checked='checked'": ""; ?>/></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="checkout_prod_image_url"><?php _e("Checkout Product Image URL"); ?>: </label></th>
							<td><input id="checkout_prod_image_url" name="checkout_prod_image_url" value="<?php echo @$dg_shop_settings['checkout_prod_image_url']; ?>" class="regular-text" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="checkout_prod_image_width"><?php _e("Checkout Product Image Width"); ?>: </label></th>
							<td><input id="checkout_prod_image_width" name="checkout_prod_image_width" value="<?php echo @$dg_shop_settings['checkout_prod_image_width']; ?>" class="regular-text" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="checkout_prod_image_height"><?php _e("Checkout Product Image Height"); ?>: </label></th>
							<td><input id="checkout_prod_image_height" name="checkout_prod_image_height" value="<?php echo @$dg_shop_settings['checkout_prod_image_height']; ?>" class="regular-text" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="checkout_gateway_image"><?php _e("Display Checkout Payment Gateway Image (Not Yet Implemented)"); ?> (<em><?php _e("shows payment gateway image on checkout page instead of text"); ?></em>): </label></th>
							<td><input type="checkbox" value="checked" name="checkout_gateway_image" <?php echo ($dg_shop_settings['checkout_gateway_image'] == 'true') ? "checked='checked'": ""; ?>/></td>
						</tr>
					</tbody>
				</table>
				<p class="submit">
					<input class='button-primary' type='submit' name='dg_advanced_settings' value='<?php _e('Save Options'); ?>' id='submitbutton' />
				</p>
			</form>
		</div>
	<?php
}
?>