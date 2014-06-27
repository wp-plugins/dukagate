<?php
class DukaGate_Products{

	/**
	 * Generate Order ID
	 */
	static function generate_order_id() {
		$order_id = date('yzB');
		
		$order_id = apply_filters( 'dg_order_id', $order_id ); //Very important to make sure order numbers are unique and not sequential if filtering

		return $order_id;
	}
	
	/**
	 * Show The widget Product
	 */
	static function widget_product($instance){
		global $dukagate;
		$dg_shop_settings = get_option('dukagate_shop_settings');
		$dg_width = (!empty($instance['dg_product_image_width'])) ? $instance['dg_product_image_width'] : 170; 
		$dg_height = (!empty($instance['dg_product_image_height'])) ? $instance['dg_product_image_height'] : 200; 
		$dg_img_url = $instance['dg_product_image_imageurl'];
		$dg_bg_image = ($instance['dg_product_image_background'] == 'checked') ? "true": "false";
		$dg_image_align = '';
		if (!empty($instance['dg_product_image_align']) && $instance['dg_product_image_align'] != 'none') {
			$dg_image_align = "class=\"align {$instance['dg_product_image_align']}\"";
		}
		$dg_alt = @$instance['dg_product_image_alt'];
		
		$discount_type = $instance['dg_product_discount_type'];
		if($discount_type == 'percentage'){
			$discount = $instance['dg_product_discount'];
		}else{
			$discount = floatval(($instance['dg_product_discount']/$instance['dg_product_price'])*100);
		}
		
		?>
		<form method="post" action="" id="dg_widget_<?php echo $instance['dg_product_sku']; ?>">
			<input type="hidden" name="action" value="dg_update_cart">
			<input type="hidden" name="price" value="<?php echo $instance['dg_product_price']; ?>" />
			<input type="hidden" name="sku" value="<?php echo $instance['dg_product_sku']; ?>" />
			<input type="hidden" name="product_id" value="<?php echo $instance['dg_product_id']; ?>" />
			<input type="hidden" name="product" value="<?php echo $instance['dg_product_name']; ?>" />
			<input type="hidden" name="discount" value="<?php echo $discount; ?>" />
			<input type="hidden" name="product_image" value="<?php echo $dg_img_url; ?>" />
			<div class="dg_widget_image" style="width:<?php echo $dg_width.'px'; ?> ; height:<?php echo $dg_height.'px'; ?> ">
				<?php if($dg_bg_image === 'false'){?>
					<img src="<?php echo $dukagate->resize_image('', $dg_img_url, $dg_width, $dg_height); ?>" alt="<?php echo $dg_alt ?>" <?php echo $dg_image_align; ?>/>
				<?php } ?>
			</div>
			<div class="dg_widget_info">
				<ul>
					<li class="dg_widget_product_name"><?php echo $instance['dg_product_name']; ?></li>
				</ul>
				<ul>
					<li class="dg_widget_product_price"><?php _e("Price"); ?> : <?php echo $dg_shop_settings['currency_symbol'].' '.$instance['dg_product_price']; ?></li>
				</ul>
				<ul>
					<li class="dg_widget_product_payment"><input class="dg_make_payment" type="submit" value="<?php _e("Make Payment"); ?>" /></li>
				</ul>
			</div>
		</form>
		<script type="text/javascript">
			jQuery(document).ready(function(){
				dukagate.update_cart('dg_widget_<?php echo $instance['dg_product_sku']; ?>', 'false');
				<?php
				if($dg_bg_image === 'true'){
					?>
					jQuery('#<?php echo $instance['dg_product_id']; ?>').css('background-image', 'url(<?php echo $dg_img_url; ?>)');
					jQuery('#<?php echo $instance['dg_product_id']; ?>').css('background-size', '100% 100%');
					<?php
					if (!empty($instance['dg_product_image_align']) && $instance['dg_product_image_align'] != 'none') {
						?>
						jQuery('#<?php echo $instance['dg_product_id']; ?>').css('background-position', '<?php echo $instance['dg_product_image_align']; ?>');
						<?php
					}
				}
				?>
			});
		</script>
			<?php
	}
	
	
	/**
	 * List products
	 * @param layout - Display layout
	 * @param total - number to show
	 * @param per_page - number to show per page
	 * @param order - post order : DESC, ASC , RAND
	 * @param prod_width - width of image
	 * @param prod_height - height of image
	 * @param group - taxonomy of product
	 * @param productlink - product link
	 * @param ajax_cart - set to use ajax to add to cart
	 * @param show_add_to_cart - show add to cart
	 */
	static function show_products($layout, $total, $per_page, $order, $prod_width, $prod_height, $group, $show_quantity, $ajax_cart, $top_row, $checkout_link,$productlink = 'false', $add_to_cart_text = 'Add To Cart',$show_add_to_cart = 'true'){
		global $dukagate;
		$offset = '';
		$content = '';
		$page_links = '';
		$grouped_id = '';
		$args = array();
		if(!empty($group)){
			$term = get_term( $group, 'grouped_product');
			if($term)
				$args['grouped_product'] = $term->name;
		}
		if (!empty($per_page)) {
			$pagenum = isset($_GET['dp_page']) ? $_GET['dp_page'] : 1;
			$count = count(get_posts('numberposts=' . $total . '&post_type=dg_product' . $grouped_id));
			$page_links = paginate_links(array(
                    'base' => add_query_arg('dp_page', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo;'),
                    'next_text' => __('&raquo;'),
                    'total' => ceil($count / $per_page),
                    'current' => $pagenum
                ));
			$post_offset = ($pagenum - 1) * $per_page;
			$args['offset'] = $post_offset;
			$page_links = '<div class="dg_pagination">' . $page_links . '</div>';
		}else{
			$per_page = $total;
		}
		if ($order != 'rand') {
			$args['orderby'] = 'post_date';
			$args['order'] = $order;
		} else {
			$args['order'] = 'rand';
		}
		$args['numberposts'] = $per_page;
		$args['posts_per_page'] = $per_page;
		$args['post_type'] = 'dg_product';
		$get_posts = new WP_Query;
		$products = $get_posts->query($args);
		if (is_array($products) && count($products) > 0) {
			$content .= '<div class="dg_product_holder '.$layout.'">';
			$count = 1;
			$dg_shop_settings = get_option('dukagate_shop_settings');
			$dg_advanced_shop_settings = get_option('dukagate_advanced_shop_settings');
			$attachment_images = '';
			foreach ($products as $product) {
				$main_image = $dukagate->product_image($product->ID);
				$prod_permalink = get_permalink($product->ID);
				if($productlink == 'false'){
					$prod_permalink= 'javascript:;';
				}
				$style= '';
				if($layout != 'list'){
					$style= 'style="width:'.$prod_width.'px;"';
				}else{
					$style= 'style="min-height:'.$prod_height.'px;"';
				}
				$content .= '<div class="dg_product_item '.$layout.'" '.$style.'>';
				if($dg_advanced_shop_settings['products_image'] == 'true'){
					$content .= '<div class="dg_prod_image">';
					if (empty($main_image)) {
						$main_image = DG_DUKAGATE_URL.'/images/no.jpg';
					}
					$content .= '<a href="' . $prod_permalink . '" title="' . $product->post_title . '"><img src="' . $dukagate->resize_image('', $main_image, $prod_width, $prod_height).'" ></a>';
					$content .= '</div>';
				}
				$price  = get_post_meta($product->ID, 'price', true);
				$content .= '<div class="dg_prod_info">';
				$content .= '<p class="title"><a href="' . $prod_permalink . '" title="' . $product->post_title . '">' . __($product->post_title) . '</a></p>';
				$content .= '<p class="detail">' . $product->post_excerpt . '</p>';
				if($dg_shop_settings['currency_position'] === 'left'){
					$content .= '<p class="price">' .__("Price", "dukagate").': '. $dg_shop_settings['currency_symbol'].' '.$price . '</p>';
				}else{
					$content .= '<p class="price">' .__("Price", "dukagate").': '.$price . ' '.$dg_shop_settings['currency_symbol'].'</p>';
				}
				if($show_quantity == 'true'){
					$total_q = 30;
					if(isset($dg_shop_settings['max_quantity']) && !empty($dg_shop_settings['max_quantity']))
						$total_q  = intval($dg_shop_settings['max_quantity']);
					$content .= '<p class="quantity">'.__("Quantity ", "dukagate").' : ';
					$content .= '<select class="prod_quantity" onchange="dukagate.add_quantity(this.value, \'dg_quantity_'.$product->ID.'\', \'uniq\')">';
					for($i=1; $i<$total_q; $i++){
						$content .= '<option value="'.$i.'">'.$i.'</option>';
					}
					
					$content .= '</select>';
					$content .= '<p>';
				}
				if($show_add_to_cart =='true'){
					$content .= '<div class="button">';
					$content .= '<form method="POST" action="" id="dg_prod_'.$product->ID.'">';
					$content .= '<input type="hidden" name="action" value="dg_update_cart">';
					$content .= '<input type="hidden" name="product_id" value="'.$product->ID.'">';
					$content .= '<input type="hidden" name="quantity" id="dg_quantity_'.$product->ID.'" value="1">';
					$content .= '<input type="hidden" name="price" value="'.$price.'">';
					$content .= '<input type="hidden" name="product" value="'.$product->post_title.'">';
					$content .= '<input type="hidden" name="product_image" value="'.$main_image.'">';
					$content .= '<input type="submit" value="'.__($add_to_cart_text, "dukagate").'" class="dg_make_payment"/>';
					$content .= '</form>';
					$content .= '</div>';
				}
				$content .= '</div>';
				$content .= '</div>';
				$content .= '<script type="text/javascript">';
				$content .= 'jQuery(document).ready(function(){';
				$content .= 'dukagate.update_cart("dg_prod_'.$product->ID.'", "'.$ajax_cart.'");';
				$content .= '});';
				$content .= '</script>';
				if(isset($top_row))
					if(intval($top_row) == $count){
						$content .= '<div class="dg_clear">&nbsp;</div>';
					}
				$count++;
				
				
					
			}
			$content .= '<div class="clear"></div>' . $page_links . '<div class="clear"></div>';
			$content .= '</div>';
			if($ajax_cart == 'true'){
				if($checkout_link == 'true'){
					$url = get_page_link($dg_shop_settings['checkout_page']);
					$content .= '<div class="dg_to_cart">';
					$content .= '<a href="'.$url.'" class="dg_show_cart">'.__('Proceed to Checkout','dukagate').'</a>';
					$content .= '</div>';
				}
			}
		}
		
		return $content;
	}
	
	/**
	 *
	 * Display Grouped products
	 * @param prod_width - width of image
	 * @param prod_height - height of image
	 */
	static function show_grouped_products($prod_width, $prod_height, $total, $groups, $top_row){
		global $dukagate;
		$content = '';
		if(@$_REQUEST['grouped_product']){
			$content = self::show_products('grid', '12', '', 'DESC', $prod_width, $prod_height, $_REQUEST['grouped_product']);
		}else{
			$group_include = '';
			if(!empty($groups)){
				$group_include = '&include='.$groups;
			}
			$total_to_show = '';
			if(!empty($total))
				if(intval($total) > 0)
					$total_to_show = 'number='.$total;
			$taxonomies=get_terms('grouped_product', $total_to_show.$group_include); 
			$dg_shop_settings = get_option('dukagate_shop_settings');
			$dg_advanced_shop_settings = get_option('dukagate_advanced_shop_settings');
			if  ($taxonomies) {
				$content .= '<div class="dg_product_holder">';
				$count = 1;
				foreach ($taxonomies  as $taxonomy ) {
					$prod_permalink = get_term_link($taxonomy->slug, 'grouped_product');
					$categ_url = $dukagate->grouped_product_crude($taxonomy->term_id, 'page-url', '', 'get');
					if(!$categ_url){
						if(isset($dg_shop_settings['products_page'])){
							$prod_permalink = get_page_link($dg_shop_settings['products_page']);
							$check_perm_path = explode('?', $prod_permalink);
							if (count($check_perm_path) > 1) {
								$prod_permalink .= '&grouped_product=' . $taxonomy->term_id;
							} else {
								$prod_permalink .= '?grouped_product=' . $taxonomy->term_id;
							}
						}
					}else{
						$prod_permalink = $categ_url;
					}
					$content .= '<div class="dg_grouped_product" style="min-height:'.$prod_height.'px;">';
					if($dg_advanced_shop_settings['products_image'] == 'true'){
						$main_image = $dukagate->grouped_product_crude($taxonomy->term_id, 'image-url', '', 'get');
						if (empty($main_image)) {
							$main_image = DG_DUKAGATE_URL.'/images/no.jpg';
						}
						$content .= '<a href="' . $prod_permalink . '" title="' . $taxonomy->name . '"><img src="' . $dukagate->resize_image('', $main_image, $prod_width, $prod_height).'" class="dg_image" ></a>';
					}
					$content .= '<p class="dg_title"><a href="' .$prod_permalink . '" title="' . $taxonomy->name . '">' . __($taxonomy->name) . '</a></p>';
					$price = $dukagate->grouped_product_crude($taxonomy->term_id, 'price', '', 'get');
					if($price){
						if($dg_shop_settings['currency_position'] === 'left'){
							$content .= '<p class="price">' .__("Price", "dukagate").': '. $dg_shop_settings['currency_symbol'].' '.$price . '</p>';
						}else{
							$content .= '<p class="price">' .__("Price", "dukagate").': '.$price . ' '.$dg_shop_settings['currency_symbol'].'</p>';
						}
					}
					$content .= '</div>';
					
					if(isset($top_row))
						if(intval($top_row) == $count){
							$content .= '<div class="dg_clear">&nbsp;</div>';
						}
					$count++;
				}
				$content .= '</div>';
			} 
		}
		
		return $content;
	}
	
	/**
	 * Product Details
	 * Shows price and add to cart button
	 */
	static function product_details($id){
		global $dukagate;
		$content = '';
		$product = get_page($id);
		$main_image = $dukagate->product_image($product->ID);
		if (empty($main_image)) {
			$main_image = DG_DUKAGATE_URL.'/images/no.jpg';
		}
		$dg_shop_settings = get_option('dukagate_shop_settings');
		$price  = get_post_meta($product->ID, 'price', true);
		
		$content .= '<div class="dg_prod_info">';
		$content .= '<p class="price">' .__("Price","dukagate").': '. $dg_shop_settings['currency_symbol'].' '.$price . '</p>';
		$content .= '<div class="button">';
		$content .= '<form method="POST" action="" id="dg_prod_'.$product->ID.'">';
		$content .= '<input type="hidden" name="action" value="dg_update_cart">';
		$content .= '<input type="hidden" name="product_id" value="'.$product->ID.'">';
		$content .= '<input type="hidden" name="quantity" id="dg_quantity_'.$product->ID.'" value="">';
		$content .= '<input type="hidden" name="price" value="'.$price.'">';
		$content .= '<input type="hidden" name="product" value="'.$product->post_title.'">';
		$content .= '<input type="hidden" name="product_image" value="'.$main_image.'">';
		$content .= '<input type="submit" value="'.__('Add To Cart','dukagate').'" class="dg_make_payment"/>';
		$content .= '</form>';
		$content .= '</div>';
		$content .= '</div>';
		$content .= '<script type="text/javascript">';
		$content .= 'jQuery(document).ready(function(){';
		$content .= 'dukagate.update_cart("dg_prod_'.$product->ID.'", "'.$ajax_cart.'");';
		$content .= '});';
		$content .= '</script>';
		return $content;
	}
	
	/**
	 * Disaply Grouped product
	 * @param product - product object
	 * @param term - taxanomy object
	 * @param dg_width - width
	 * @param dg_height - height
	 * @param children_img - array of taxanomy to show or hide image from shortcode
	 */
	static function grouped_product($product, $term, $dg_width, $dg_height, $dg_prod_option, $children_img){
		global $dukagate;
		$content = '';
		$dg_shop_settings = get_option('dukagate_shop_settings');
		$dg_advanced_shop_settings = get_option('dukagate_advanced_shop_settings');
		$price  = get_post_meta($product->ID, 'price', true);
		$fixed_price  = get_post_meta($product->ID, 'fixed_price', true);
		$main_image = $dukagate->product_image($product->ID);
		
		$fixed_price  = get_post_meta($product->ID, 'fixed_price', true);
		$prod_permalink = get_permalink($product->ID);
		$style= 'style="width:'.$dg_width.'px;"';
		$content .= '<div class="dg_product_item '.$term->slug.'" '.$style.'>';
		$content .= '<div class="dg_prod_image '.$term->slug.'">';
		if (empty($main_image)) {
			$main_image = DG_DUKAGATE_URL.'/images/no.jpg';
		}
		$view = '';
		if(is_array($children_img)){
			if(!in_array($term->term_id,$children_img)){
				if($dg_advanced_shop_settings['products_image'] == 'true')
					$content .= '<img src="' . $dukagate->resize_image('', $main_image, $dg_width, $dg_height).'" alt="'.$product->post_title.'" >';	
			}else
				$view = 'dg_list';
		}
		else{
			if($dg_advanced_shop_settings['products_image'] == 'true')
				$content .= '<img src="' . $dukagate->resize_image('', $main_image, $dg_width, $dg_height).'" alt="'.$product->post_title.'"  >';
		}
		
		$content .= '</div>';
		$content .= '<div class="dg_prod_info '.$view.'">';
		$content .= '<p class="title '.$term->slug.'">' . __($product->post_title) . '</p>';
		if(!empty($product->post_excerpt))
			$content .= '<p class="detail">' . $product->post_excerpt . '</p>';
		if($fixed_price == 'checked'){
			$content .= '<input type="hidden" name="fixed_price_'.$product->ID.'" id="fixed_price_'.$product->ID.'" value="'.$price.'">';
			if($dg_shop_settings['currency_position'] === 'left'){
				$content .= '<p class="price"><span class="price_label">' .__("Price", "dukagate").':</span> '. $dg_shop_settings['currency_symbol'].' '.$price . '</p>';
			}else{
				$content .= '<p class="price"><span class="price_label">' .__("Price", "dukagate").':</span> '.$price . ' '.$dg_shop_settings['currency_symbol'].'</p>';
			}
		}
		if($dg_prod_option != 'none'){
			$content .= '<p class="select_option"><input type="'.$dg_prod_option.'" onclick="dukagate.add_price(\''.$price.'\', \'dg_cart_price\', \'sub_product_'.$product->ID.'\', \''.$fixed_price.'\');" name="sub_product_'.$product->ID.'" id="sub_product_'.$product->ID.'" value="'.$product->post_title.'"/></p>';
		}else{
			$content .= '<div class="button">';
			$content .= '<form method="POST" action="" id="dg_prod_'.$product->ID.'">';
			$content .= '<input type="hidden" name="action" value="dg_update_cart">';
			$content .= '<input type="hidden" name="product_id" value="'.$product->ID.'">';
			$content .= '<input type="hidden" name="quantity" id="dg_quantity_'.$product->ID.'" value="">';
			$content .= '<input type="hidden" name="price" value="'.$price.'">';
			$content .= '<input type="hidden" name="product" value="'.$product->post_title.'">';
			$content .= '<input type="hidden" name="product_image" value="'.$main_image.'">';
			$content .= '<input type="submit" value="'.__('Add To Cart','dukagate').'" class="dg_make_payment"/>';
			$content .= '</form>';
			$content .= '</div>';
		}
		$content .= '</div>';
		$content .= '</div>';
		return $content;
	}
	
	/** 
	 * Display Grouped products
	 * @param group - id of group
	 */
	static function display_grouped_products($parent, $child, $child_images ,$prod_width, $prod_height, $ajax_cart, $top_row, $checkout, $quantity){
		global $dukagate;
		$content = '';
		$term = get_term( $parent, 'grouped_product');
		if($term){
			$content .= '<div class="dg_product_holder '.$term->slug.'">';
			$content .= '<h2 class="taxonomy_name '.$term->slug.'">'.$term->name.'</h2>';
			$children = explode(',', $child);
			$children_img = explode(',', $child_images);
			$price = 0.00;
			$parent_image = $dukagate->grouped_product_crude($term->term_id, 'image-url', '', 'get');
			$parent_name = $term->name;
			$parent_price = $dukagate->grouped_product_crude($term->term_id, 'price', '', 'get');
			if (!empty($child) && is_array($children) && count($children) > 0) {
				foreach ($children as $child) {
					$term = get_term( $child, 'grouped_product'); 
					$dg_prod_option = $dukagate->grouped_product_crude($term->term_id, 'product_select', '', 'get'); //Product display option
					$dg_width = $dukagate->grouped_product_crude($term->term_id, 'product_image_width', '', 'get'); //Product width
					$dg_height = $dukagate->grouped_product_crude($term->term_id, 'product_image_height', '', 'get'); //Product height
					if(empty($dg_width)){
						$dg_width = $prod_width;
					}
					if(empty($dg_height)){
						$dg_height = $prod_height;
					}
					if(empty($dg_prod_option)){
						$dg_prod_option = 'none';
					}
					$args=array(
					  'grouped_product' => $term->name,
					  'post_type' => 'dg_product',
					  'post_status' => 'publish'
					);
					$get_posts = new WP_Query;
					$products = $get_posts->query($args);
					if($term){
						$content .= '<h3 class="taxonomy_child '.$term->slug.'">'.$term->name.'</h3>';
						if (is_array($products) && count($products) > 0) {
							$count = 1;
							foreach ($products as $product) {
								$content .= self::grouped_product($product, $term, $dg_width, $dg_height, $dg_prod_option,  $children_img);
								if(isset($top_row))
									if(intval($top_row) == $count){
										$content .= '<div class="dg_clear">&nbsp;</div>';
									}
								$count++;
							}
						}
						$content .= '<div class="dg_clear">&nbsp;</div>';
					}
				}
			}
			else if (!empty($child)) {
				$term = get_term( $child, 'grouped_product');
				$args=array(
				  'grouped_product' => $term->name,
				  'post_type' => 'dg_product',
				  'post_status' => 'publish'
				);
				$get_posts = new WP_Query;
				$products = $get_posts->query($args);
				if($term){
					$dg_prod_option = $dukagate->grouped_product_crude($term->term_id, 'product_select', '', 'get'); //Product display option
					$dg_width = $dukagate->grouped_product_crude($term->term_id, 'product_image_width', '', 'get'); //Product width
					$dg_height = $dukagate->grouped_product_crude($term->term_id, 'product_image_height', '', 'get'); //Product height
					if(empty($dg_width)){
						$dg_width = $prod_width;
					}
					if(empty($dg_height)){
						$dg_height = $prod_height;
					}
					if(empty($dg_prod_option)){
						$dg_prod_option = 'radio';
					}
					$content .= '<h3 class="taxonomy_child '.$term->slug.'">'.$term->name.'</h3>';
					if (is_array($products) && count($products) > 0) {
						$count = 1;
						foreach ($products as $product) {
							$content .= self::grouped_product($product, $term, $dg_width, $dg_height,  $dg_prod_option, $children_img);
							if(isset($top_row))
								if(intval($top_row) == $count){
									$content .= '<div class="dg_clear">&nbsp;</div>';
								}
							$count++;
						}
					}
				}
			}
			else{
				$args=array(
				  'grouped_product' => $term->name,
				  'post_type' => 'dg_product',
				  'post_status' => 'publish'
				);
				$dg_prod_option = $dukagate->grouped_product_crude($term->term_id, 'product_select', '', 'get'); //Product display option
				$dg_width = $dukagate->grouped_product_crude($term->term_id, 'product_image_width', '', 'get'); //Product width
				$dg_height = $dukagate->grouped_product_crude($term->term_id, 'product_image_height', '', 'get'); //Product height
				if(empty($dg_width)){
					$dg_width = $prod_width;
				}
				if(empty($dg_height)){
					$dg_height = $prod_height;
				}
				if(empty($dg_prod_option)){
					$dg_prod_option = 'radio';
				}
				$get_posts = new WP_Query;
				$products = $get_posts->query($args);
				if (is_array($products) && count($products) > 0) {
					$count = 1;
					foreach ($products as $product) {
						$content .= self::grouped_product($product, $term, $dg_width, $dg_height,  $dg_prod_option, $children_img);
						if(isset($top_row))
							if(intval($top_row) == $count){
								$content .= '<div class="dg_clear">&nbsp;</div>';
							}
						$count++;
					}
					
				}
			}
			if($parent_price)
				$price = $parent_price;
			$content .= '<div class="dg_grouped_submit">';
			if($ajax_cart == 'true'){
				$total_q = 30;
				$dg_shop_settings = get_option('dukagate_shop_settings');
				if(isset($dg_shop_settings['max_quantity']) && !empty($dg_shop_settings['max_quantity']))
					$total_q  = intval($dg_shop_settings['max_quantity']);
				$content .= '<p class="quantity">'.__("Quantity ").' : ';
				$content .= '<select class="prod_quantity" onchange="dukagate.add_quantity(this.value, \'dg_quantity\', \'uniq\')">';
				for($i=1; $i<$total_q; $i++){
					$content .= '<option value="'.$i.'">'.$i.'</option>';
				}
				
				$content .= '</select>';
				$content .= '<p>';
			}
			if($quantity == 'true'){
				$total_q = 30;
				$dg_shop_settings = get_option('dukagate_shop_settings');
				if(isset($dg_shop_settings['max_quantity']) && !empty($dg_shop_settings['max_quantity']))
					$total_q  = intval($dg_shop_settings['max_quantity']);
				$content .= '<p class="quantity">'.__("Quantity ","dukagate").' : ';
				$content .= '<select class="prod_quantity" onchange="dukagate.add_quantity(this.value, \'dg_quantity\', \'uniq\')">';
				for($i=1; $i<$total_q; $i++){
					$content .= '<option value="'.$i.'">'.$i.'</option>';
				}
				
				$content .= '</select>';
				$content .= '<p>';
			}
			$content .= '<p class="button">';
			$content .= '<form method="POST" action="" id="dg_prod_tax">';
			$content .= '<input type="hidden" name="action" value="dg_update_cart">';
			$content .= '<input type="hidden" name="product_id" value="'.$parent.'">';
			$content .= '<input type="hidden" name="quantity" id="dg_quantity" value="">';
			$content .= '<input type="hidden" name="price" id="dg_cart_price" value="'.$price.'">';
			$content .= '<input type="hidden" name="taxonomy_id" id="taxonomy_id" value="'.$parent.'">';
			$content .= '<input type="hidden" name="product" id="product" value="'.$parent_name.'">';
			$content .= '<input type="hidden" name="product_image" value="'.$parent_image.'">';
			$content .= '<input type="submit" value="'.__("Add To Cart","dukagate").'" class="dg_make_payment"/>';
			$content .= '</form>';
			$content .= '<script type="text/javascript">';
			$content .= 'jQuery(document).ready(function(){';
			$content .= 'dukagate.group_update_cart("dg_prod_tax", "'.$ajax_cart.'");';
			$content .= '});';
			$content .= '</script>';
			$content .= '</p>';
			$content .= '</div>';
			$content .= '</div>';
			
			if($ajax_cart == 'true'){
				if($checkout == 'true'){
					$dg_shop_settings = get_option('dukagate_shop_settings');
					$url = get_page_link($dg_shop_settings['checkout_page']);
					$content .= '<div class="dg_to_cart">';
					$content .= '<a href="'.$url.'" class="dg_show_cart">'.__('Proceed to Checkout','dukagate').'</a>';
					$content .= '</div>';
				}
			}
			$content .= '<div class="dg_clear">&nbsp;</div>';
		}
		return $content;
	}
	
}
?>