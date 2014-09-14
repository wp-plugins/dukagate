<?php

/**
 * Get Cart
 */
function dg_get_cart($atts){
	extract(shortcode_atts(array(
                'layout' => 'fixed'), $atts));
	global $dukagate;
	$dg_shop_settings = get_option('dukagate_shop_settings');
	$dg_shipping_required = 'false';
	
	$dg_cart = '';
	$cnt = '<div class="dg_cart_container">';
	if(isset($_SESSION['dg_cart']) && !empty($_SESSION['dg_cart'])) {
		$dg_cart = $_SESSION['dg_cart'];
	}
	if (is_array($dg_cart) && count($dg_cart) > 0) {
		if($dg_shop_settings['shipping'] === 'true'){
			$gteways = $dukagate->list_all_active_shipping_gateways();
			$class = '';
			if (is_array($gteways) && count($gteways) > 0) {
				foreach ($gteways as $gteways) {
					$class = $gteways->class;
				}
			}
			if(!empty($class))
				$class = new $class();
			if(!isset($_SESSION['dg_shipping_required']))
				$dg_shipping_required = 'true';
			else
				$dg_shipping_required = $_SESSION['dg_shipping_required'];
				
			if($dg_shipping_required == 'true'){
				if($class->before_payment){
					$dg_shipping_required = 'true';
					$_SESSION['dg_shipping_required'] = $dg_shipping_required;
				}
			}
			if(empty($class))
				$dg_shipping_required == 'false';
		}else{
			$dg_shipping_required == 'false';
		}
			
		if($dg_shipping_required == 'true'){
			$cnt .= '<form method="POST" id="shipping_submit">';
			$cnt .= $class->shipping_form();
			$cnt .= '<input type="hidden" value="shipping_submit" name="action" />';
			$cnt .= '<input type="submit" class="shipping_submit" id="shipping_submit_input" value="'.__("Proceed to Checkout").'" />';
			$cnt .= '</form>';
			$cnt .= '<script type="text/javascript">';
			$cnt .= 'jQuery(document).ready(function(){';
			$cnt .= 'dukagate.process_shipping("shipping_submit")';
			$cnt .= '});';
			$cnt .= '</script>';
		}
		else{
			
			$dg_cart = '';
			if(isset($_SESSION['dg_cart']) && !empty($_SESSION['dg_cart'])) {
				$dg_cart = $_SESSION['dg_cart'];
			}
			if (is_array($dg_cart) && count($dg_cart) > 0) {
				$total_value = 0.00;
				$total_discount = 0.00;
				$percentage_discount = 0.00;
				$total_shipping= 0.00;
				$dg_shipping = $_SESSION['dg_shipping_total'];
				if(is_array($dg_shipping)){
					foreach ($dg_shipping as $shipping) {
						$total_shipping += $shipping;
					}
				}
				$cnt .= '<div class="dg_empty_cart"><span class="dg_empty">'.__("Empty Cart").'</span></div>';
				$cnt .= '<script type="text/javascript">';
				$cnt .= 'jQuery(document).ready(function(){';
				$cnt .= 'jQuery("span.dg_empty").click(function() {';
				$cnt .= 'dukagate.empty_cart();';
				$cnt .= 'return null;';
				$cnt .= '});';
				$cnt .= '});';
				$cnt .= '</script>';
				$cnt .= '<table class="dg_cart" id="dg_cart_table">';
				$cnt .= '<tr>';
				if(@$dg_shop_settings['checkout_prod_image'] == 'true'){
					$cnt .= '<th scope="col" class="dg_cart_header image">&nbsp;</th>';
				}
				$cnt .= '<th scope="col" class="dg_cart_header product">'.__("Product").'</th>';
				$cnt .= '<th scope="col" class="dg_cart_header quantity">'.__("Quantity").'</th>';
				$cnt .= '<th scope="col" class="dg_cart_header price">'.__("Price").'</th>';
				$cnt .= '<th scope="col" class="dg_cart_header total">'.__("Total").'</th>';
				$cnt .= '</tr>';
				foreach ($dg_cart as $cart_items => $cart) {
					if(!isset($_SESSION['dg_discount_value'])){
						$discount_value = floatval(($cart['discount'] * $cart['total'])/100);
						$percentage_discount += $cart['discount'];
					}else{
						$discount_value = 0.00;
					}
					$total_value += (floatval($cart['total']) - $discount_value);
					$cnt .= '<tr>';
					if($dg_shop_settings['checkout_prod_image'] == 'true'){
						if(empty($dg_shop_settings['checkout_prod_image_url'])){
							$cart_img = $cart['product_image'];
						}else{
							$cart_img = $dg_shop_settings['checkout_prod_image_url'];
						}
						$cnt .= '<td style="vertical-align:top" class="product image"><img src="'.$cart_img.'"  style="width:'.$dg_shop_settings['checkout_prod_image_width'].'px; height:'.$dg_shop_settings['checkout_prod_image_height'].'px"/></td>';
					}
					$cnt .= '<td class="product name">'.$cart['product'].'</td>';
					$cnt .= '<td class="quantity"><input type="text" id="cart_quantity_'.$cart_items.'" value="'.$cart['quantity'].'" /> <button id="dg_btn_'.$cart_items.'">'.__("Update").'</button></td>';
					$cnt .= '<td class="product price">'.$dg_shop_settings['currency_symbol'].' '. number_format(@$cart['price'],2).'</td>';
					$cnt .= '<td class="product total">'.$dg_shop_settings['currency_symbol'].' '.number_format(@$cart['total'],2).'</td>';
					$cnt .= '</tr>';
					$cnt .= '<script type="text/javascript">';
					$cnt .= 'jQuery(document).ready(function(){';
					$cnt .= 'jQuery("#dg_btn_'.$cart_items.'").click(function() {';
					$cnt .= 'dukagate.update_quantity("cart_quantity_'.$cart_items.'","'.$cart_items.'");';
					$cnt .= 'return null;';
					$cnt .= '});';
					$cnt .= '});';
					$cnt .= '</script>';
					$total_discount += $discount_value;
					
				}
				
				$total_value = $total_value + $total_shipping;
				$total_tax = 0;
				if(!empty($dg_shop_settings['tax_rate'])){
					$total_tax = $total_value * $dg_shop_settings['tax_rate'] / 100;
					$total_value = $total_value + $total_tax;
				}
				if(isset($_SESSION['dg_discount_value'])){
					$total_discount = $_SESSION['dg_discount_value'];
					$percentage_discount = $total_discount;
					$total_discount = floatval(($total_discount * $total_value)/100);
					$total_value = $total_value - $total_discount;
				}
				if($total_value < 0){
					$total_value = 0;
				}
				$_SESSION['dg_cart_discount_value'] = $percentage_discount;
				
				//Check if discounts is on
				if(($dg_shop_settings['discounts'] == 'true')){
					$cnt .= '<tr>';
					if($dg_shop_settings['checkout_prod_image'] == 'true'){
						$cnt .= '<td>&nbsp;</td>';
					}
					
					$cnt .= '<td class="discount">'.__("Total Discount").'</td>';
					$cnt .= '<td>&nbsp;</td>';
					$cnt .= '<td>&nbsp;</td>';
					$cnt .= '<td class="discount amount">'.$dg_shop_settings['currency_symbol'].' '.number_format(($total_discount),2).'</td>';
					$cnt .= '</tr>';
				}
				if(isset($dukagate_shipping)){
					$cnt .= '<tr>';
					if($dg_shop_settings['checkout_prod_image'] == 'true'){
						$cnt .= '<td>&nbsp;</td>';
					}
					$cnt .= '<td class="shipping">'.__("Total Shipping").'</td>';
					$cnt .= '<td>&nbsp;</td>';
					$cnt .= '<td>&nbsp;</td>';
					$cnt .= '<td class="shipping amount">'.$dg_shop_settings['currency_symbol'].' '.number_format($total_shipping,2).'</td>';
					$cnt .= '</tr>';
					
				}
				if(!empty($dg_shop_settings['tax_rate'])){
					
					$cnt .= '<tr>';
					if($dg_shop_settings['checkout_prod_image'] == 'true'){
						$cnt .= '<td>&nbsp;</td>';
					}
					$cnt .= '<td class="shipping">'.__("Total Tax").'</td>';
					$cnt .= '<td>&nbsp;</td>';
					$cnt .= '<td>&nbsp;</td>';
					$cnt .= '<td class="tax amount">'.$dg_shop_settings['currency_symbol'].' '.number_format($total_tax,2).'</td>';
					$cnt .= '</tr>';
				}
				
				$cnt .= '<tr>';
				if(@$dg_shop_settings['checkout_prod_image'] == 'true'){
					$cnt .= '<td>&nbsp;</td>';
				}
				
				$cnt .= '<td class="total">'.__("Total").'</td>';
				$cnt .= '<td>&nbsp;</td>';
				$cnt .= '<td>&nbsp;</td>';
				$cnt .= '<td class="total amount">'.$dg_shop_settings['currency_symbol'].' '.number_format($total_value,2).'</td>';
				$cnt .= '</tr>';
				$cnt .= '</table>';
				
				//Check Discounts
				if(($dg_shop_settings['discounts'] == 'true')){
					$cnt .= '<span class="error" id="dg_disc_reponse"></span>';
					$cnt .= '<table class="dg_discount">';
					$cnt .= '<tr>';
					$cnt .= '<td colspan="2">'.__("Enter Discount Code").'</td>';
					if($dg_shop_settings['checkout_prod_image'] == 'true'){
						$cnt .= '<td>&nbsp;</td>';
					}
					$cnt .= '</tr>';
					$cnt .= '<tr>';
					$cnt .= '<td><input type="text" id="dg_discount_code" autocomplete="off" /></td>';
					$cnt .= '<td><button id="dg_disc_validate">'.__("Validate").'</button></td>';
					if($dg_shop_settings['checkout_prod_image'] == 'true'){
						$cnt .= '<td>&nbsp;</td>';
					}
					$cnt .= '</tr>';
					$cnt .= '</table>';
				}
				$dg_form_elem = get_option('dukagate_checkout_options');
				$cnt .= '<form id="dg_checkout_form">';
				//Add Checkout page
				$cnt .= $dukagate->generate_checkout_form($layout);
				$cnt .= '</form>';
				$cnt .= '<script type="text/javascript">';
				$cnt .= 'jQuery(document).ready(function(){';
				$cnt .= 'jQuery("#dg_checkout_form").validate({';
				$cnt .= 'rules: {';
				$cnt .= 'dg_email: {';
				$cnt .= 'required: true,';
				$cnt .= 'email: true';
				$cnt .= '}},';
				$cnt .= 'submitHandler: function(form) {';
				$cnt .= 'dukagate.checkout(form);';
				$cnt .= 'return false;';
				$cnt .= '}';
				$cnt .= '});';
				//Check Discounts
				if(($dg_shop_settings['discounts'] == 'true')){
					$cnt .= 'jQuery("#dg_disc_validate").click(function(){';
					$cnt .= 'dukagate.validate_discount("dg_discount_code");';
					$cnt .= '});';
				}
				$cnt .= '});';
				$cnt .= '</script>';
				$cnt .= '<div style="display:none" id="dg_payment_return"></div>';
			}else{
				$cnt .= __("There are no items in your cart");
			}
			
		}
	}else{
		$cnt .= __("There are no items in your cart");
	}
	$cnt .= '</div>';
	return $cnt;
}

//Minimal Cart
function dg_cart_min($echo = 'false'){
	global $dukagate;
	$dg_shop_settings = get_option('dukagate_shop_settings');
	$cnt = '<div class="dg_cart_container">';
	$dg_cart = '';
	if(isset($_SESSION['dg_cart']) && !empty($_SESSION['dg_cart'])) {
		$dg_cart = $_SESSION['dg_cart'];
	}
	if (is_array($dg_cart) && count($dg_cart) > 0) {
		$total = 0.00;
		$cnt .= '<table class="dg_cart" id="dg_cart_table">';
		$cnt .= '<tr>';
		$cnt .= '<th scope="col" class="dg_cart_header product">'.__("Product").'</th>';
		$cnt .= '<th scope="col" class="dg_cart_header quantity">'.__("Qty").'</th>';
		$cnt .= '<th scope="col" class="dg_cart_header price">'.__("Price").'</th>';
		$cnt .= '</tr>';
		foreach ($dg_cart as $cart_items => $cart) {
			$cnt .= '<tr>';
			$cnt .= '<td class="product name">'.$cart['product'].'</td>';
			$cnt .= '<td class="product quantity">'.$cart['quantity'].'</td>';
			$cnt .= '<td class="product price">'.$dg_shop_settings['currency_symbol'].' '. number_format($cart['total'],2).'</td>';
			$cnt .= '</tr>';
			$cnt .= '<script type="text/javascript">';
			$cnt .= 'jQuery(document).ready(function(){';
			$cnt .= 'jQuery("#dg_btn_'.$cart_items.'").click(function() {';
			$cnt .= 'dukagate.update_quantity("cart_quantity_'.$cart_items.'","'.$cart_items.'");';
			$cnt .= 'return null;';
			$cnt .= '});';
			$cnt .= '});';
			$cnt .= '</script>';
			$total += $cart['total'];
			
		}
		if($total < 0){
			$total = 0;
			$total_tax = 0;
			if(!empty($dg_shop_settings['tax_rate'])){
				$total_tax = $total * $dg_shop_settings['tax_rate'] / 100;
				$total = $total + $total_tax;
			}
		}
		$url = get_page_link($dg_shop_settings['checkout_page']);
		$cnt .= '</table>';
		if(!empty($dg_shop_settings['tax_rate'])){
			$cnt .= '<p class="total tax">'.__("Total Tax").' : '.$dg_shop_settings['currency_symbol'].' '.number_format($total_tax,2).'</p>';
		}
		$cnt .= '<p class="total">'.__("Total").' : '.$dg_shop_settings['currency_symbol'].' '.number_format($total,2).'</p>';
		$cnt .= '<div class="dg_empty_cart"><span class="dg_empty">'.__("Empty Cart").'</span></div>';
		$cnt .= '<p class="checkout"><a href="'.$url.'" class="go_checkout">'.__("Go to Checkout").'</a></p>';
		$cnt .= '<script type="text/javascript">';
		$cnt .= 'jQuery(document).ready(function(){';
		$cnt .= 'jQuery("span.dg_empty").click(function() {';
		$cnt .= 'dukagate.empty_cart();';
		$cnt .= 'return null;';
		$cnt .= '});';
		$cnt .= '});';
		$cnt .= '</script>';
		
	}else{
		$cnt .= __("There are no items in your cart");
	}
	$cnt .= '</div>';
	if($echo == 'true')
		echo $cnt;
	else
		return $cnt;
}

//Mini cart to just show total
function dg_mini_cart($echo = 'false', $imgurl = 'false'){
	$dg_shop_settings = get_option('dukagate_shop_settings');
	$cnt = '<div class="dg_mini_cart_container">';
	$dg_cart = '';
	$total = 0;
	if(isset($_SESSION['dg_cart']) && !empty($_SESSION['dg_cart'])) {
		$dg_cart = $_SESSION['dg_cart'];
	}
	if (is_array($dg_cart)) {
		$total = count($dg_cart);
	}
	$url = get_page_link($dg_shop_settings['checkout_page']);
	$cnt .= '<table>';
	$cnt .= '<tr>';
	$cnt .= '<td>';
	$cnt .= '<img src="'.$imgurl.'" />';
	$cnt .= '</td>';
	$cnt .= '<td>';
	$cnt .= '<a href="'.$url.'" class="go_checkout"><span id="mini_cart_total">'.$total.'</span> items in cart. '.__("Checkout").'</a>';
	$cnt .= '</td>';
	$cnt .= '</tr>';
	$cnt .= '</table>';
	$cnt .= '</div>';
	if($echo == 'true')
		echo $cnt;
	else
		return $cnt;
}

//Total items in cart
function dg_total_cart_items(){
	$total = 0;
	if(isset($_SESSION['dg_cart']) && !empty($_SESSION['dg_cart'])) {
		$dg_cart = $_SESSION['dg_cart'];
	}
	if (is_array($dg_cart)) {
		$total = count($dg_cart);
	}
	return $total;
}

add_action( 'wp_ajax_nopriv_shipping_submit', 'dg_shipping_submit');
add_action( 'wp_ajax_shipping_submit', 'dg_shipping_submit');


function dg_shipping_submit(){
	$options = "";
	if(@$_REQUEST['shipping_rate_value']){
		$options = $_REQUEST['shipping_rate_value'];
	}
	$_SESSION['dg_shipping_required'] = 'false';
	$_SESSION['dg_shipping_total'] = $options;
	header('Content-type: application/json; charset=utf-8');
	echo DukaGate::array_to_json(array('response' => 'valid', 'shipping' => $_SESSION['dg_shipping_required']));
	exit();
	
}


add_action( 'wp_ajax_nopriv_dg_validate_discount_code', 'dg_validate_discount_code');
add_action( 'wp_ajax_dg_validate_discount_code', 'dg_validate_discount_code');

function dg_validate_discount_code(){
	//Validate Dukapress discount plugin code
	$discount_code = $_REQUEST['dg_code'];
	$dg_valide_code = __('Invalid Code','dg-lang');
	$dp_discount_percentage = dg_disc_validate_discounts($discount_code);
	if($dp_discount_percentage['exists'] == 'true'){
		$dg_valide_code = 'valid';
		$_SESSION['dg_discount'] = $discount_code;
		$_SESSION['dg_discount_value'] = $dp_discount_percentage['value'];
	}
	
	header('Content-type: application/json; charset=utf-8');
	echo DukaGate::array_to_json(array('response' => $dg_valide_code));
	exit();
}



/**
 * Validate DukaGate discounts
 * Process all product ids and category ids
 */
function dg_disc_validate_discounts($discount_code){
	global $dukagate_disc;
	$dg_cart = $_SESSION['dg_cart'];
	$prods = 0;
	$dg_total = 0.00;
	$allowed_discount = false;	
    if (is_array($dg_cart) && count($dg_cart) > 0) {
		foreach ($dg_cart as $cart_items => $cart) {
			$dg_total += $cart['total'];
			$prods += $cart['quantity'];
		}
	}
	
	$discount = $dukagate_disc->verify_code($discount_code, '');
	
	$discount_percentage['exists'] = 'false';
	$discount_percentage['disc'] = $discount;
	
	if($discount){
		if(!empty($_SESSION['dg_disc_disc'])){
			$discount_set = $_SESSION['dg_disc_disc'];
			if($discount_set != 0 || $discount_set != '0'){
				if($discount_set->id == $discount->id){
					$discount = false;
				}else if($discount_set->amount > $discount->amount){
					$discount = false;
				}
			}
		}
	}
	
	if($discount){
		$dg_ds_amount = intval($discount->amount);
		$discount_percentage['exists'] = 'true';
		if(intval($discount->type) == 1){
			$discount_percentage['value'] = floatval($dg_ds_amount);
		}
		else if(intval($discount->type) == 2){
			$discount_percentage['value'] = floatval(($dg_ds_amount/$dg_total)*100);
		}
	}
	return $discount_percentage;
	
}


add_action( 'wp_ajax_nopriv_dg_update_cart', 'dg_update_cart');
add_action( 'wp_ajax_dg_update_cart', 'dg_update_cart');

/**
 * Update Cart
 */
function dg_update_cart(){
	$dg_shop_settings = get_option('dukagate_shop_settings');
	$dg_cart = array();
	if(isset($_SESSION['dg_cart']) && !empty($_SESSION['dg_cart'])) {
		$dg_cart = $_SESSION['dg_cart'];
	}
	
	
	$dg_product_id= $_REQUEST['product_id'];
	$taxonomy_id= @$_REQUEST['taxonomy_id'];
	$dg_product_quantity= @$_REQUEST['quantity'];
	
	$dg_product_price= @$_REQUEST['price'];
	$dg_product_product= @$_REQUEST['product'];
	$dg_product_image = @$_REQUEST['product_image'];
	$dg_discount = @$_REQUEST['discount'];
	$dg_children= @$_REQUEST['children'];
	if(empty($dg_discount)){
		$dg_discount = 0;
	}
	
	$prod = @$dg_cart[$dg_product_id];
	if(!empty($prod)){
		/*if(!empty($dg_product_quantity)){
			$prod['quantity'] = $dg_product_quantity;
		}else{
			if($dg_product_quantity > 0){
				$prod['quantity'] = intval($prod['quantity']) + 1;
			}
		}*/
		if($dg_product_quantity > 0){
			$prod['quantity'] = intval($prod['quantity']) + 1;
		}
		$total = (floatval($prod['price']) * intval($prod['quantity']));
		$prod['total'] = $total; 
		$prod['children'] = $dg_children;
		if($dg_product_quantity == 0){
			unset($dg_cart[$dg_product_id]);
		}
		else{
			$dg_cart[$dg_product_id] = $prod;
		}
	}else{
		$prod = array();
		if(!empty($dg_product_quantity)){
			$prod['quantity'] = $dg_product_quantity;
		}else{
			$prod['quantity'] = 1;
		}
		$prod['price'] = $dg_product_price;
		$prod['discount'] = $dg_discount;
		$prod['product'] = $dg_product_product;
		$prod['product_image'] = $dg_product_image;
		$prod['taxonomy'] = $taxonomy_id;
		$prod['children'] = $dg_children;
		$prod['prod_id'] = $dg_product_id;
		$total = (floatval($prod['price']) * intval($prod['quantity']));
		$prod['total'] = $total;
		$dg_cart[$dg_product_id] = $prod;
	}
	
	session_regenerate_id(true);
	$_SESSION['dg_cart'] = $dg_cart;
	session_write_close();
	
	//Return Json response
	$url = get_page_link($dg_shop_settings['checkout_page']);
	if(!empty($dg_shop_settings['up_selling_page_checkout'])){
		if($dg_shop_settings['up_selling_page_checkout'] == 'true')
			$url = get_page_link($dg_shop_settings['up_selling_page']);
	}
	$html = dg_cart_min();
	header('Content-type: application/json; charset=utf-8');
	echo DukaGate::array_to_json(array('url' => $url, 'success' => 'true', 'total' => count($dg_cart), 'html' => $html));
	exit();
}


//Empty cart
add_action( 'wp_ajax_nopriv_dg_empty_cart', 'dg_empty_cart');
add_action( 'wp_ajax_dg_empty_cart', 'dg_empty_cart');

function dg_empty_cart(){
	$products = $_SESSION['dg_cart'];
    foreach ($products as $cart_items => $cart) {
       unset($products[$cart_items]);
    }
	$_SESSION['dg_shipping_required'] = 'true';
	$_SESSION['dg_cart'] = $products;
}

//Process Cart
add_action( 'wp_ajax_nopriv_dg_process_cart', 'dg_process_cart');
add_action( 'wp_ajax_dg_process_cart', 'dg_process_cart');

function dg_process_cart(){
	global $dukagate;
	$payment_gateway = $_REQUEST['dg_gateway_action'];
	$invoice_id = DukaGate_Products::generate_order_id();
	$total = $dukagate->dg_save_order_log($invoice_id,$_REQUEST, $payment_gateway, 'Pending');
	$class = $dukagate->dg_get_gateway_class($payment_gateway);
	$class = new $class();
	$content = $_REQUEST;
	session_regenerate_id(true);
	$_SESSION['dg_invoice'] = $invoice_id;
	$output = $class->process_payment_form($content);
	$output = str_replace(Array("\n", "\r"), Array("\\n", "\\r"), addslashes($output));
	
	$products = $_SESSION['dg_cart'];
    foreach ($products as $cart_items => $cart) {
       unset($products[$cart_items]);
    }
	if(isset($_SESSION['dg_discount_value'])){
		unset($_SESSION['dg_discount_value']);
	}
	$_SESSION['dg_discount_value'] = null;
	if(isset($_SESSION['dg_cart_discount_value'])){
		unset($_SESSION['dg_cart_discount_value']);
	}
	$_SESSION['dg_cart_discount_value'] = null;
	$_SESSION['dg_cart'] = $products;
	$_SESSION['dg_invoice'] = '';
	session_write_close();
	if($total > 0){
		if($class->form_submit){
			echo "jQuery('div.dg_cart_container').html('$output');";
			echo "jQuery('#dg_payment_form').submit();";
		}else{
			echo "jQuery('div.dg_cart_container').html('$output');";
		}
	}else{
		$dg_shop_settings = get_option('dukagate_shop_settings');
		$return_path = get_page_link($dg_shop_settings['thankyou_page']);
        $check_return_path = explode('?', $return_path);
		$dukagate->dg_update_order_log($invoice_id, 'Paid');
        if (count($check_return_path) > 1) {
            $return_path .= '&id=' . $invoice_id;
        } else {
            $return_path .= '?id=' . $invoice_id;
        }
		$output = "<script type='text/javascript'> window.location.href='".$return_path."'; </script>";
		$output = str_replace(Array("\n", "\r"), Array("\\n", "\\r"), addslashes($output));
		echo "jQuery('div.dg_cart_container').html('$output');";
	}
	exit();
}
?>