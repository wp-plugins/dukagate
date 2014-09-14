<?php

//Products Shortcode
add_shortcode('dg_display_products', 'dg_display_products');
function dg_display_products($atts){
	extract(shortcode_atts(array(
                'layout' => 'grid',
				'total' => '12',
				'top' => '',
				'per_page' => '',
				'order' => 'DESC',
				'image_width' => '200',
				'image_height' => '200',
				'group' => '',
				'ajax_cart' => 'false',
				'quantity' => 'false',
				'checkout_link' => 'true',
				'productlink' => 'true',
				'add_to_cart_text' => 'Add To Cart',
				'show_add_to_cart' => 'true'), $atts));
				
	$output = DukaGate_Products::show_products($layout, $total, $per_page, $order, $image_width, $image_height, $group, $quantity, $ajax_cart, $top, $checkout_link, $productlink, $add_to_cart_text, $show_add_to_cart);
	return $output;
}

//Product shortcode
add_shortcode('dg_display_product', 'dg_display_product');
function dg_display_product($atts){
	extract(shortcode_atts(array(
                'buy_now' => '',
                'direct' => '',
				'affiliate' => ''
                    ), $atts));
	$product_id = get_the_ID();
	$output = DukaGate_Products::product_details($product_id);
	return $output;
}

//Groped product list
add_shortcode('dg_group_display', 'dg_group_display');
function dg_group_display($atts){
	extract(shortcode_atts(array(
				'top' => '',
				'parent' => '',
				'child' => '',
				'child_hide_images' => '',
				'image_width' => '200',
				'image_height' => '200',
				'ajax_cart' => 'false',
				'quantity' => 'false',
				'checkout_link' => 'true'), $atts));
				
	$output = DukaGate_Products::display_grouped_products($parent, $child, $child_hide_images, $image_width, $image_height, $ajax_cart, $top, $checkout_link, $quantity);
	return $output;
}

//Grouped Products Shortcode
add_shortcode('dg_group_grid', 'dg_display_grouped_products');
function dg_display_grouped_products($atts){
	extract(shortcode_atts(array(
                'image_width' => '200',
				'image_height' => '200',
				'total' => '',
				'top' => '',
				'groups' => ''), $atts));
				
	$output = DukaGate_Products::show_grouped_products($image_width, $image_height, $total, $groups, $top);
	return $output;
}

//Checkout Cart shortcode
add_shortcode('dg_display_cart_checkout', 'dg_get_cart');

//Shopping cart shortcode
add_shortcode('dg_display_cart', 'dg_display_cart');
function dg_display_cart($atts){
	extract(shortcode_atts(array(
				'mini' => 'false'), $atts));
	if($mini == 'false'){
		$output = dg_cart_min('false');;
	}else{
		$output = dg_mini_cart('false');;
	}
	return $output;
}


//Show total items in cart
add_shortcode('dg_display_cart_items_total', 'dg_total_cart_items');


//Diplay links
add_shortcode('dg_display_checkout_link', 'dg_display_checkout_link');
function dg_display_checkout_link($atts){
	extract(shortcode_atts(array(
                'shop' => ''), $atts));
	$dg_shop_settings = get_option('dukagate_shop_settings');
	$url = get_page_link($dg_shop_settings['checkout_page']);
	$home_url = get_bloginfo('url');
	if(isset($shop))
		$home_url = get_page_link($shop);
	$cnt = '<div class="dg_links">';
	$cnt .= '<div class="dg_to_cart">';
	$cnt .= '<a href="'.$url.'" class="dg_show_cart">'.__('Proceed to Checkout').'</a>';
	$cnt .= '</div>';
	$cnt .= '<div class="text_clear">|</div>';
	$cnt .= '<div class="dg_to_shop">';
	$cnt .= '<a href="'.$home_url.'" class="dg_show_home">'.__('Continue Shopping').'</a>';
	$cnt .= '</div>';
	$cnt .= '</div>';
	return $cnt;
}

//Thank you page shortcode
add_shortcode('dg_display_thankyou', 'dg_thankyou_page');
function dg_thankyou_page(){
	$invoice = $_REQUEST['id'];
	global $dukagate;
	global $dukagate_mail;
	$dg_order = $dukagate->dg_get_order_log_by_invoice($invoice);
	$status = isset($_GET['status']) ? $_GET['status'] : FALSE;
	if (!$status) {
		$dg_shop_settings = get_option('dukagate_shop_settings');
		$output = '';
		switch($dg_order->payment_status){
			case 'Paid':
				$output .= '<h4>' . __('Thank you for making the payment of', "dg-lang") . ' <span id="dpsc_payment_amount">' . $dg_shop_settings['currency_symbol'] . $dg_order->total . '</span> ' . __('using', "dg-lang") . ' ' . $dg_order->payment_gateway . '.</h4>';
				break;
			case 'Pending':
				$output .= '<h4>' . __('Thank you for making the payment of', "dg-lang") . ' <span id="dpsc_payment_amount">' . $dg_shop_settings['currency_symbol'] . 					$dg_order->total . '</span> ' . __('using', "dg-lang") . ' ' . $dg_order->payment_gateway . '.</h4>
                                    <p>' . __('We will process your order soon.', "dg-lang") . '</p>';
				break;
			default:
				$output .= '<h4>' . __('There was an error processing your payment', "dg-lang").'</h4>';
				break;
		}
		
		$attachments = array();
		$products = DukaGate::json_to_array($dg_order->products);
		if(!empty($products) && count($products) > 0){
			$total = 0.00;
			$total_discount = 0.00;
			$info = '';
			$info = 'Products<br/>';
			$info .= '<table style="text-align:left">';
			$info .= '<tr>';
			$info .= '<th scope="col" width="30%">'.__("Product").'</th>';
			$info .= '<th scope="col" width="10%">'.__("Quantity").'</th>';
			$info .= '<th scope="col" width="30%">'.__("Price").'</th>';
			$info .= '<th scope="col" width="30%">'.__("Total").'</th>';
			$info .= '</tr>';
			foreach ($products as $cart_items => $cart) {
				$digital_file = get_post_meta($cart['prod_id'], 'digital_file', true);
				if(!empty($digital_file)){
					$attachments[] = $digital_file['file'];
				}
				$info .= '<tr>';
				$info .= '<td>'.$cart['product'].' ('.$cart['children'].')</td>';
				$info .= '<td>'.$cart['quantity'].'</td>';
				$info .= '<td>'.$dg_shop_settings['currency_symbol'].' '. number_format($cart['price'],2).'</td>';
				$info .= '<td>'.$dg_shop_settings['currency_symbol'].' '. number_format($cart['total'],2).'</td>';
				$info .= '</tr>';
			}
			$info .= '<tr>';
			$info .= '<td>'.__("Total Discount").'</td>';
			$info .= '<td>&nbsp;</td>';
			$info .= '<td>&nbsp;</td>';
			$info .= '<td>'.$dg_shop_settings['currency_symbol'].' '.number_format($dg_order->discount,2).'</td>';
			$info .= '</tr>';
			$info .= '<tr>';
			$info .= '<td>'.__("Total Shipping").'</td>';
			$info .= '<td>&nbsp;</td>';
			$info .= '<td>&nbsp;</td>';
			$info .= '<td>'.$dg_shop_settings['currency_symbol'].' '.number_format(($dg_order->shipping),2).'</td>';
			$info .= '</tr>';
			$info .= '<tr>';
			$info .= '<td>'.__("Total").'</td>';
			$info .= '<td>&nbsp;</td>';
			$info .= '<td>&nbsp;</td>';
			$info .= '<td>'.$dg_shop_settings['currency_symbol'].' '.number_format(($dg_order->total),2).'</td>';
			$info .= '</tr>';
			$info .= '</table>';
		}
		
		$output .= $info;
		$shop = get_bloginfo('name');
		if($dg_order->payment_status === 'Paid'){
			$mail = $dukagate_mail->get_mail('payment_received');
			$to =  $mail->to_mail;
			$subject = $mail->title;
			
			
			//To Admin
			$message = $mail->content_admin;
			$array1 = array('%details%', '%inv%', '%shop%');
			$array2 = array($details, $invoice, $shop);
			$message = str_replace($array1, $array2, $message);
			
			$dukagate_mail->send_mail($to, $subject, $message);
			
			
			$message = $mail->content_user;
			$array1 = array('%details%','%inv%','%shop%');
			$array2 = array($info,$invoice,$shop);
			$message = str_replace($array1, $array2, $message);
			$dukagate_mail->send_mail($dg_order->email, $subject, $message, $attachments);
		}
		
	}else{
		$dukagate->dg_update_order_log($invoice, 'Canceled');
		$mail = $dukagate_mail->get_mail('order_canceled');
		$to =  $mail->to_mail;
		$subject = $mail->title;
		$shop = get_bloginfo('name');
		
		//To Admin
		$message = $mail->content_admin;
		$array1 = array('%inv%');
		$array2 = array($invoice);
		$message = str_replace($array1, $array2, $message);
		
		$dukagate_mail->send_mail($to, $subject, $message);
		
		
		//To user
		$message = $mail->content_user;
		$dg_fullname = $order_info['dg_fullname'];
		$array1 = array('%fname%','%inv%','%shop%');
		$array2 = array($dg_fullname,$invoice,$shop);
		$message = str_replace($array1, $array2, $message);
		$dukagate_mail->send_mail($dg_order->email, $subject, $message);
		$output = __('Order canceled !!', "dg-lang"); 
	}
	
	
	return $output;
}
?>