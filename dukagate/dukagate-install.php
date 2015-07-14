<?php

/**
 * Admin notice to install sample pages
 *
 */
function dukagate_install_admin_notice(){
	
	$dukagate_sample_data = get_option('dukagate_sample_data');
	if(empty($dukagate_sample_data)){
		?>
		<div class="updated">
			<p><?php echo sprintf( __('Click <a href="%1$s">here</a> to create default pages. Click <a href="%2$s">here</a> to ignore'), admin_url('edit.php?post_type=dg_product&page=dukagate-settings&sample_data=true'), admin_url('edit.php?post_type=dg_product&page=dukagate-settings&sample_data=false')); ?></p>
		</div>
		<?php
	}
}
add_action( 'admin_notices', 'dukagate_install_admin_notice' );

/** 
 * Create pages
 */
function dukagate_create_pages(){
	$dukagate_sample_data = get_option('dukagate_sample_data');
	if(empty($dukagate_sample_data)){
		dukagate_create_post_page(__('Products'),"[dg_display_products]");
		$checkout_page = dukagate_create_post_page(__('Checkout'),"[dg_display_cart_checkout]");
		$thankyou_page = dukagate_create_post_page(__('Thank you'),"[dg_display_thankyou]");
		
		$dg_shop_settings = get_option('dukagate_shop_settings');
		if(empty($dg_shop_settings)) {
			$dg_shop_settings = $dukagate->get_default_settings();;
		}
		
		$opts = array('checkout_page' => $checkout_page, 'thankyou_page' => $thankyou_page);
		update_option('dukagate_shop_settings', $opts);
		
		update_option('dukagate_sample_data', 'done');
	}
}

function dukagate_create_post_page($title, $shortcode){
	$post = array(
	  'comment_status' => 'closed', // 'closed' means no comments.
	  'post_author' => 1 , //The user ID number of the author.
	  'post_content' => $shortcode ,//The full text of the post.
	  'post_date' => date('Y-m-d H:i:s'), //The time post was made.
	  'post_date_gmt' => date('Y-m-d H:i:s'), //The time post was made, in GMT.
	  'post_name' => $title , // The name (slug) for your post
	  'post_status' => 'publish', //Set the status of the new post.
	  'post_title' => $title, //The title of your post.
	  'post_type' => 'page' //Sometimes you want to post a page.
	);
	$postid = wp_insert_post( $post, true );
}
?>