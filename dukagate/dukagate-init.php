<?php

if(!class_exists('DukaGate')) {

	class DukaGate{
	
		/**
		 * Initialize plugin and set it up
		 */
		function init(){
			$this->dukagate_db();
			$this->set_up_plugin_info();
			$this->set_up_directories_and_file_info();
			update_option('dg_version_info', 1.0);
		}
		
		
		function set_up(){
			$this->load_plugins(DG_DUKAGATE_DIR.'/libs/');
			$this->load_plugins(DG_DUKAGATE_WIDGET_DIR);
			require_once(DG_DUKAGATE_DIR.'/dukagate-settings.php');
			require_once(DG_DUKAGATE_DIR.'/dukagate-mail.php');
			require_once(DG_DUKAGATE_DIR.'/dukagate-gateways.php');
			require_once(DG_DUKAGATE_DIR.'/dukagate-admin.php');
			require_once(DG_DUKAGATE_DIR.'/dukagate-products.php');
			require_once(DG_DUKAGATE_DIR.'/dukagate-cart.php');
			require_once(DG_DUKAGATE_DIR.'/dukagate-shortcodes.php');
			
			add_filter( 'cron_schedules', array(__CLASS__, 'custom_cron_schedules'));
			
			add_action( 'init', array(&$this, 'set_up_product_posts'));
			add_action( 'init', array(&$this, 'create_product_taxonomies'));
			add_action( 'add_meta_boxes', array(&$this,'set_up_product_meta_box'));
			add_action('grouped_product_add_form_fields', array(&$this,'grouped_product_metabox_add'), 10, 1);
			add_action('grouped_product_edit_form_fields', array(&$this,'grouped_product_metabox_edit'), 10, 1);
			add_action('created_grouped_product', array(&$this,'save_grouped_product_metadata'), 10, 1);	
			add_action('edited_grouped_product', array(&$this,'update_grouped_product_metadata'), 10, 1);
			add_action('delete_grouped_product', array(&$this,'delete_grouped_product_metadata'), 10, 1);
			add_action( 'save_post', array(&$this,'product_meta_save'));
			add_action( 'init', array(&$this, 'set_up_styles'));
			add_action( 'init', array(&$this, 'set_up_js'));
			$this->load_gateway_plugins();
			add_action ( 'plugins_loaded', array(&$this,'system_load_textdomain'), 7 );
		}
		
		

		
		/**
		 * Plugin deactivated function
		 */
		function destroy(){
			wp_clear_scheduled_hook('dg_daily_file_event');
		}
		
		/**
		 * Public static function to get the database names in use
		 */
		public static function db_names(){
			global $wpdb;
			return array(
						'transactions' => $wpdb->prefix."dkgt_transactions" , 
						'tempfiles' => $wpdb->prefix . "dkgt_temp_file_log",
						'payment' => $wpdb->prefix . "dkgt_payment_options",
						'mail' => $wpdb->prefix . "dkgt_mail_settings",
						'meta' => $wpdb->prefix . "dkgt_taxonomymeta"
						);
		}
		
		/**
		 * Set up plugin info
		 */
		private function set_up_plugin_info(){
			
		}
		
		function system_load_textdomain() {
			$locale = apply_filters( 'wordpress_locale', get_locale() );
			$mofile = DG_DUKAGATE_DIR . "/languages/dukagate-$locale.mo";

			if ( file_exists( $mofile ) )
				load_textdomain( 'dg-lang', $mofile );
		}
		
		//Load up styles
		function set_up_styles(){
			if(is_admin()){
				wp_enqueue_style('dg_admin_css', DG_DUKAGATE_URL.'/css/dukagate_admin.php');
			}else{
				wp_enqueue_style('dukagate_css', DG_DUKAGATE_URL.'/css/dukagate.css');
			}
		}
		
		//Load Javascript
		function set_up_js(){
			if(is_admin()){
				wp_enqueue_script('dukagate_admin', DG_DUKAGATE_URL.'/js/dukagate_admin.js', array('jquery'), '', false);
				wp_enqueue_script('wysiwyg_js', DG_DUKAGATE_URL.'/js/wyzz0.65/wyzz.php', array('jquery'), '', false);
				wp_enqueue_script("dukagate_admin");
				wp_enqueue_script("wysiwyg_js");
			}else{
				wp_enqueue_script('dukagate_js', DG_DUKAGATE_URL.'/js/dukagate.js', array('jquery'), '', false);
				wp_enqueue_script('jquery_validate', DG_DUKAGATE_URL.'/js/jquery.validate.js', array('jquery'), '', false);
				wp_enqueue_script('jquery_form', DG_DUKAGATE_URL.'/js/jquery.form.js', array('jquery'), '', false);
				
				wp_enqueue_script("jquery_validate");
				wp_enqueue_script("jquery_form");
				wp_enqueue_script("dukagate_js");
				wp_localize_script('dukagate_js', 'dg_js', array( 'dg_url' => get_bloginfo('url') , 'ajaxurl' => admin_url('admin-ajax.php')) );
			}
		}
		
		/** 
		 * Custom cron schedules
		 */
		static function custom_cron_schedules(){
			return array(
				'in_per_minute' => array(
					'interval' => 60,
					'display' => 'In every Mintue'
				),
				'in_per_ten_minute' => array(
					'interval' => 60 * 10,
					'display' => 'In every two Mintues'
				),
				'three_hourly' => array(
					'interval' => 60 * 60 * 3,
					'display' => 'Once in Three minute'
				)
			);
		}

		/**
		 * Set Up product posts
		 */
		public function set_up_product_posts(){
			$supports = array( 'title', 'editor', 'excerpt', 'revisions', 'thumbnail' );
			register_post_type( 'dg_product',
				array(
					'labels' => array(
						'name' => __( 'Products', 'dg-lang' ),
						'singular_name' => __( 'Product' , 'dg-lang'),
						'add_new' => __('Add New Product', 'dg-lang'),
						'add_new_item' => __('Create New Product', 'dg-lang'),
						'edit_item' => __('Edit Products', 'dg-lang'),
						'edit' => __('Edit Product', 'dg-lang'),
						'new_item' => __('New Product', 'dg-lang'),
						'view_item' => __('View Product', 'dg-lang'),
						'search_items' => __('Search Products', 'dg-lang'),
						'not_found' => __('No Products Found', 'dg-lang'),
						'not_found_in_trash' => __('No Products found in Trash', 'dg-lang'),
						'view' => __('View Product', 'dg-lang')
					),
					'description' => __('Products for your Dukagate store.', 'dg-lang'),
					'menu_icon' => DG_DUKAGATE_URL . '/images/dg_icon.png',
					'public' => true,
					'publicly_queryable' => true,
					'has_archive' => true,
					'show_ui' => true,
					'show_in_menu' => true, 
					'query_var' => true,
					'hierarchical' => false,
					'capability_type' => 'post',
					'rewrite' => array('slug' => 'products', 'with_front' => false),
					'supports' => $supports
				)
			);
		}
		
		/**
		 * Set Up Product Meta Boxes
		 */
		public function set_up_product_meta_box(){
			add_meta_box( 
				'dukagate_sectionid',
				__( 'Product Details', 'dg-lang' ),
				array(&$this, 'product_inner_custom_box'),
				'dg_product','side', 'high'
			);
		}
		
		/**
		 * Meta Box
		 */
		public function product_inner_custom_box( $post ){
			// Use nonce for verification
			wp_nonce_field( plugin_basename( __FILE__ ), 'dukagate_noncename' );
			$post_id = @$_GET['post'];
			$content_price = get_post_meta($post_id, 'price', true);
			$fixed_price = get_post_meta($post_id, 'fixed_price', true);
			$sku = get_post_meta($post_id, 'sku', true);
			$digital_file = get_post_meta($post_id, 'digital_file', true);
			$affiliate_url = get_post_meta($post_id, 'affiliate_url', true);
			?>
			<table width="100%">
				<tr>
					<td><?php _e('Price:',"dg-lang");?> :</td>
					<td><input type="text" value="<?php echo $content_price; ?>" name="price" id="price"></td>
				</tr>
				<tr>
					<td><?php _e('Distinct Price:',"dg-lang");?> :</td>
					<td><input type="checkbox" value="checked" name="fixed_price" <?php echo ($fixed_price == 'checked') ? "checked='checked'": ""; ?> /></td>
				</tr>
				<tr>
					<td colspan="2">(<?php _e('if selected the grouped product will use this price ',"dg-lang");?>)</td>
				</tr>
				<tr>
					<td><?php _e('SKU:',"dg-lang");?> :</td>
					<td><input type="text" value="<?php echo $sku; ?>" name="sku" id="sku"></td>
				</tr>
				<tr>
					<td><?php _e('Digital File:',"dg-lang");?> :</td>
					<td><input type="text" value="<?php echo $digital_file; ?>" name="digital_file" id="digital_file"></td>
				</tr>
				<tr>
					<td><?php _e('Affiliate URL:',"dg-lang");?> :</td>
					<td><input type="text" value="<?php echo $affiliate_url; ?>" name="affiliate_url" id="affiliate_url"></td>
				</tr>
			</table>
			<?php
		}
		
		/**
		 * Save Product Meta Data
		 */
		public function product_meta_save($post_id){
			// verify if this is an auto save routine. 
			// If it is our form has not been submitted, so we dont want to do anything
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
				return;
				
			if ( !wp_verify_nonce(@$_POST['dukagate_noncename'], plugin_basename( __FILE__ ) ) )
				return;
			
			// Check permissions
			if ( 'dg_product' == @$_POST['post_type'] ) {
				if ( !current_user_can( 'edit_page', $post_id ) )
					return;
			}
			else{
				if ( !current_user_can( 'edit_post', $post_id ) )
					return;
			}
			// for price
			if (NULL == @$_POST['price']) {
				//do nothing
			} else {
				$content_price = $_POST['price'];
				update_post_meta($post_id, 'price', $content_price);
			}
			
			// for fixed price
			if (NULL == @$_POST['fixed_price']) {
				//do nothing
			} else {
				$fixed_price = @$_POST['fixed_price'];
				update_post_meta($post_id, 'fixed_price', $fixed_price);
			}
			
			// for digital_file
			if (NULL == @$_POST['digital_file']) {
				//do nothing
			} else {
				$digital_file = @$_POST['digital_file'];
				update_post_meta($post_id, 'digital_file', $digital_file);
			}
			
			// for sku
			if (NULL == @$_POST['sku']) {
				//do nothing
			} else {
				$sku = @$_POST['sku'];
				update_post_meta($post_id, 'sku', $sku);
			}
			
			// for affiliate_url
			if (NULL == @$_POST['affiliate_url']) {
				//do nothing
			} else {
				$affiliate_url = @$_POST['affiliate_url'];
				update_post_meta($post_id, 'affiliate_url', $affiliate_url);
			}
			
		}
		
		/**
		 * Product Taxonimies
		 */
		function create_product_taxonomies() {
		  // Add new taxonomy, make it hierarchical (like categories)
		  $labels = array(
			'name' => _x( 'DukaGate Product Categories', 'taxonomy general name' ),
			'singular_name' => _x( 'DukaGate Product Category', 'taxonomy singular name' ),
			'search_items' =>  __( 'Search Product Categories' ),
			'all_items' => __( 'All Product Categories' ),
			'parent_item' => __( 'Parent Product Category' ),
			'parent_item_colon' => __( 'Parent Product Category:' ),
			'edit_item' => __( 'Edit Product Category Name' ), 
			'update_item' => __( 'Update Product Category Name' ),
			'add_new_item' => __( 'Add New Product Category Name' ),
			'new_item_name' => __( 'New Product Category Name' ),
			'menu_name' => __( 'Product Categories' ),
		  ); 	

		  register_taxonomy('grouped_product',array('products'), array(
			'hierarchical' => true,
			'labels' => $labels,
			'show_ui' => true,
			'query_var' => true,
			'rewrite' => array( 'slug' => 'product' ),
		  ));
		  
		  register_taxonomy_for_object_type('grouped_product', 'dg_product');
		}
		
		
		/** 
		 * Custom image meta box add
		 */
		function grouped_product_metabox_add($tag) { ?>
			<div class="form-field">
				<label for="image-url"><?php _e('Image URL', 'dg-lang') ?></label>
				<input name="image-url" id="image-url" type="text" value="" size="40" />
				<p class="description"><?php _e('This image will be the thumbnail shown on the group page.', 'dg-lang'); ?></p>
			</div>
			<div class="form-field">
				<label for="product_image_width"><?php _e('Product Image Width', 'dg-lang') ?></label>
				<input name="product_image_width" id="product_image_width" type="text" value="" size="40" />
				<p class="description"><?php _e('This will be the width of the product images. If blank, it will use the default settings', 'dg-lang'); ?></p>
			</div>
			<div class="form-field">
				<label for="product_image_height"><?php _e('Product Image Height', 'dg-lang') ?></label>
				<input name="product_image_height" id="product_image_height" type="text" value="" size="40" />
				<p class="description"><?php _e('This will be the height of the product images. If blank, it will use the default settings', 'dg-lang'); ?></p>
			</div>
			<div class="form-field">
				<label for="page-url"><?php _e('Page URL', 'dg-lang') ?></label>
				<input name="page-url" id="page-url" type="text" value="" size="40" />
				<p class="description"><?php _e('This will be the group page url.', 'dg-lang'); ?></p>
			</div>
			<div class="form-field">
				<label for="price"><?php _e('Price', 'dg-lang') ?></label>
				<input name="price" id="price" type="text" value="" size="10" />
				<p class="description"><?php _e('This will be the group price.', 'dg-lang'); ?></p>
			</div>
			<div class="form-field">
				<label for="product_select"><?php _e('Product Select', 'dg-lang') ?></label>
				<select name="product_select" id="product_select">
					<option value="checkbox" ><?php _e('Use CheckBox', "dg-lang"); ?></option>
					<option value="radio" ><?php _e('Use Radio', "dg-lang"); ?></option>
				</select>
				<p class="description"><?php _e('This will be the select option for the product.', 'dg-lang'); ?></p>
			</div>
			<?php 
		} 	
		
		/**
		 * Custom Image meta box edit
		 */
		function grouped_product_metabox_edit($tag) { 
			$product_select = $this->grouped_product_crude($tag->term_id, 'product_select', '', 'get');
			?>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="image-url"><?php _e('Image URL', 'dg-lang'); ?></label>
				</th>
				<td>
					<input name="image-url" id="image-url" type="text" value="<?php echo $this->grouped_product_crude($tag->term_id, 'image-url', '', 'get'); ?>" size="40" />
					<p class="description"><?php _e('This image will be the thumbnail shown on the group page.', 'dg-lang'); ?></p>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="product_image_width"><?php _e('Product Image Width', 'dg-lang') ?></label>
				</th>
				<td>
					<input name="product_image_width" id="product_image_width" type="text" value="" size="40" />
					<p class="description"><?php _e('This will be the width of the product images. If blank, it will use the default settings', 'dg-lang'); ?></p>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="product_image_height"><?php _e('Product Image Height', 'dg-lang') ?></label>
				</th>
				<td>
					<input name="product_image_height" id="product_image_height" type="text" value="" size="40" />
					<p class="description"><?php _e('This will be the height of the product images. If blank, it will use the default settings', 'dg-lang'); ?></p>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="page-url"><?php _e('Page URL', 'dg-lang'); ?></label>
				</th>
				<td>
					<input name="page-url" id="page-url" type="text" value="<?php echo $this->grouped_product_crude($tag->term_id, 'page-url', '', 'get'); ?>" size="40" />
					<p class="description"><?php _e('This will be the group page url.', 'dg-lang'); ?></p>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="price"><?php _e('Price', 'dg-lang'); ?></label>
				</th>
				<td>
					<input name="price" id="price" type="text" value="<?php echo $this->grouped_product_crude($tag->term_id, 'price', '', 'get'); ?>" size="40" />
					<p class="description"><?php _e('This will be the group price.', 'dg-lang'); ?></p>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="product_select"><?php _e('Product Select', 'dg-lang') ?></label>
				</th>
				<td>
					<select name="product_select" id="product_select">
						<option value="checkbox" <?php selected( $product_select, 'checkbox' ); ?>><?php _e('Use CheckBox', "dg-lang"); ?></option>
						<option value="radio" <?php selected( $product_select, 'radio' ); ?>><?php _e('Use Radio', "dg-lang"); ?></option>
					</select>
					<p class="description"><?php _e('This will be the select option for the product.', 'dg-lang'); ?></p>
				</td>
			</tr>
			<?php 
		}
		
		/**
		 * Grouped products Custom meta. Issues using wordpress meta
		 * @taxonomy_id - id of taxanomy
		 * @meta_key - key of meta tag
		 * @meta_value - value
		 * @action - save, update, delete, get ONLY
		 */
		function grouped_product_crude($taxonomy_id, $meta_key, $meta_value = '', $action){
			$databases = self::db_names();
			global $wpdb;
			$sql = '';
			$table_name = $databases['meta'];
			switch($action){
				case 'save' :
					$sql = "INSERT INTO `$table_name`(`taxonomy_id`, `meta_key` ,`meta_value`) VALUES($taxonomy_id,'$meta_key','$meta_value')";
					$wpdb->query($sql);
					break;
				case 'update' :
					$sql = "UPDATE `$table_name` SET `meta_value` = '$meta_value' WHERE`taxonomy_id` = $taxonomy_id AND `meta_key` = '$meta_key'";
					$wpdb->query($sql);
					if($wpdb->rows_affected <= 0){
						$sql = "INSERT INTO `$table_name`(`taxonomy_id`, `meta_key` ,`meta_value`) VALUES($taxonomy_id,'$meta_key','$meta_value')";
						$wpdb->query($sql);
					}	
					break;
				case 'delete' :
					$sql = "DELETE FROM `$table_name`  WHERE`taxonomy_id` = $taxonomy_id AND `meta_key` = '$meta_key'";
					$wpdb->query($sql);
					break;
				case 'get' :
					$sql = "SELECT  `meta_value` FROM `$table_name`  WHERE `taxonomy_id` = $taxonomy_id AND `meta_key` = '$meta_key'";
					return $wpdb->get_var($sql);
					break;
				default : 
					break;
			}
			
		}
		
		/**
		 * Save Custom term
		 */
		function save_grouped_product_metadata($term_id){
			if (isset($_POST['image-url'])) 
				$this->grouped_product_crude($term_id, 'image-url', $_POST['image-url'], 'save');
			if (isset($_POST['page-url'])) 
				$this->grouped_product_crude($term_id, 'page-url', $_POST['page-url'], 'save');
			if (isset($_POST['price'])) 
				$this->grouped_product_crude($term_id, 'price', $_POST['price'], 'save');
			if (isset($_POST['product_select'])) 
				$this->grouped_product_crude($term_id, 'product_select', $_POST['product_select'], 'save');
			if (isset($_POST['product_image_width'])) 
				$this->grouped_product_crude($term_id, 'product_image_width', $_POST['product_image_width'], 'save');
			if (isset($_POST['product_image_height'])) 
				$this->grouped_product_crude($term_id, 'product_image_height', $_POST['product_image_height'], 'save');
		}
		
		/**
		 * Update Custom term
		 */
		function update_grouped_product_metadata($term_id){
			if (isset($_POST['image-url']))
				$this->grouped_product_crude($term_id, 'image-url', $_POST['image-url'], 'update');
			if (isset($_POST['page-url']))
				$this->grouped_product_crude($term_id, 'page-url', $_POST['page-url'], 'update');
			if (isset($_POST['price']))
				$this->grouped_product_crude($term_id, 'price', $_POST['price'], 'update');
			if (isset($_POST['product_select'])) 
				$this->grouped_product_crude($term_id, 'product_select', $_POST['product_select'], 'update');
			if (isset($_POST['product_image_width'])) 
				$this->grouped_product_crude($term_id, 'product_image_width', $_POST['product_image_width'], 'update');
			if (isset($_POST['product_image_height'])) 
				$this->grouped_product_crude($term_id, 'product_image_height', $_POST['product_image_height'], 'update');
		}
		
		/**
		 * Delete Custom term
		 */
		function delete_grouped_product_metadata($term_id){
			$this->grouped_product_crude($term_id, 'image-url', '', 'delete');
			$this->grouped_product_crude($term_id, 'page-url', '', 'delete');
			$this->grouped_product_crude($term_id, 'price', '', 'delete');
			$this->grouped_product_crude($term_id, 'product_select', '', 'delete');
			$this->grouped_product_crude($term_id, 'product_image_width', '', 'delete');
			$this->grouped_product_crude($term_id, 'product_image_height', '', 'delete');
		}
		/**
		 * Convert Json To Array
		 */
		static function json_to_array($json){
			return json_decode($json, true);
		}
		
		/**
		 * Convert Array To Json
		 */
		static function array_to_json($array){
			return json_encode($array);
		}
		
		/**
		 * Set Up directories
		 */
		public function set_up_directories_and_file_info(){
			
			if (!is_dir(DG_DOWNLOAD_FILES_DIR)) {
				mkdir(DG_DOWNLOAD_FILES_DIR, 0, true);
				chmod(DG_DOWNLOAD_FILES_DIR, 0777);
			}
			if (!is_dir(DG_DOWNLOAD_FILES_DIR_TEMP)) {
				mkdir(DG_DOWNLOAD_FILES_DIR_TEMP, 0, true);
				chmod(DG_DOWNLOAD_FILES_DIR_TEMP, 0777);
			}
			if(is_dir(DG_PLUGIN_DIR.'/cache')) {
				chmod(DG_PLUGIN_DIR.'/cache', 0777);
			}
			else {
				mkdir(DG_PLUGIN_DIR.'/cache', 0, true);
				chmod(DG_PLUGIN_DIR.'/cache', 0777);
			}
			if(is_dir(DG_PLUGIN_DIR.'/temp')) {
				chmod(DG_PLUGIN_DIR.'/temp', 0777);
			}
			else {
				mkdir(DG_PLUGIN_DIR.'/temp', 0, true);
				chmod(DG_PLUGIN_DIR.'/temp', 0777);
			}
			if(is_dir(DG_PLUGIN_DIR.'/report')) {
				chmod(DG_PLUGIN_DIR.'/report', 0777);
			}
			else {
				mkdir(DG_PLUGIN_DIR.'/report');
				chmod(DG_PLUGIN_DIR.'/report', 0777);
			}
			
			//Download link info
			$dg_dl_expiration_time = get_option('dg_dl_expiration_time');
			if (!$dg_dl_expiration_time) {
				$dg_expiration_time = '48';
				update_option('dg_dl_expiration_time', $dg_expiration_time);
			}
			
			$date = date('M-d-Y', strtotime("+1 days"));
			$next_time_stamp = strtotime($date) + 18000;
			wp_schedule_event($next_time_stamp, 'daily', 'dg_daily_file_event');
		}
		
		/**
		 * Delete expired files
		 */
		public function delete_files_daily(){
			$files = glob(DG_DOWNLOAD_FILES_DIR.'/*', GLOB_BRACE);

			if (count($files) > 0) {
				$delete_time = floatval(get_option('dg_dl_expiration_time'));
				$yesterday = time() - ($delete_time * 60 * 60);

				usort($files, 'filemtime_compare');

				foreach ($files as $file) {

					if (@filemtime($file) > $yesterday) {
						return;
					}

					unlink($file);

				}
			}
		}
		
		/**
		 * Check if database tables are created and create them
		 */
		private function dukagate_db(){
			$databases = self::db_names();
			global $wpdb;
			$db_setup = false;
			
			$charset_collate = '';	
			if ( ! empty($wpdb->charset) )
				$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
			if ( ! empty($wpdb->collate) )
				$charset_collate .= " COLLATE $wpdb->collate";
			
			//Transaction logs
			$table_name = $databases['transactions'];
			if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
				$sql =  "CREATE TABLE `$table_name` (
                `id` INT( 5 ) NOT NULL AUTO_INCREMENT,
                `invoice` VARCHAR(50) NOT NULL,
                `date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				`names` VARCHAR(150) NOT NULL,
				`email` VARCHAR(100) NOT NULL,
				`order_info` LONGTEXT NULL,
                `products` LONGTEXT NOT NULL,
				`is_widget_product` bigint(5) unsigned NOT NULL DEFAULT '0',
                `payment_gateway` VARCHAR(100) NOT NULL,
                `discount` FLOAT NOT NULL,
                `total` FLOAT NOT NULL,
                `payment_status` ENUM ('Pending', 'Paid', 'Canceled'),
                UNIQUE (`invoice`),
                PRIMARY KEY  (id)
                )";
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql);
				$db_setup = true;
			}
			
			//Download Temp files
			$table_name = $databases['tempfiles'];
			if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
				$sql = "CREATE TABLE `$table_name` (
						`id` int(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
						`real_name` VARCHAR(250) NOT NULL,
						`saved_name` VARCHAR(250) NOT NULL,
						`sent_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
						`count` int(10) DEFAULT 0
						);";
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql);
				$db_setup = true;
			}
			
			//Payment Plugins settings
			$table_name = $databases['payment'];
			if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
				$sql = "CREATE TABLE `$table_name` (
						`id` int(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
						`gateway_name` VARCHAR(100) NOT NULL,
						`gateway_slug` VARCHAR(100) NOT NULL,
						`gateway_class` VARCHAR(100) NOT NULL,
						`gateway_options` VARCHAR(300) NOT NULL,
						`currencies` LONGTEXT DEFAULT NULL,
						`active` bigint(5) unsigned NOT NULL DEFAULT '0',
						UNIQUE KEY `gateway_slug` (`gateway_slug`)
						);";
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql);
			}
			
			//Shipping
			$table_name = $databases['shipping'];
			if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
				$sql = "CREATE TABLE `$table_name` (
						`id` int(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
						`name` VARCHAR(100) NOT NULL,
						`slug` VARCHAR(100) NOT NULL,
						`class` VARCHAR(100) NOT NULL,
						`shipping_info` LONGTEXT DEFAULT NULL,
						`active` bigint(5) unsigned NOT NULL DEFAULT '0',
						UNIQUE KEY `name` (`name`)
						);";
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql);
			}
			
			//Taxonomy Meta
			$table_name = $databases['meta'];
			if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
				$sql = "CREATE TABLE `$table_name` (
							`meta_id` bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
							`taxonomy_id` bigint(20) unsigned NOT NULL default '0',
							`meta_key` varchar(255) default NULL,
							`meta_value` longtext,
							UNIQUE KEY `taxonomy` (`taxonomy_id`, `meta_key`)
						) $charset_collate;";
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql);
			}
			
			
			//Mail options
			$table_name = $databases['mail'];
			if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
				$sql = "CREATE TABLE `$table_name` (
						`id` int(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
						`type` VARCHAR(100) NOT NULL,
						`to_mail` VARCHAR(150) NOT NULL,
						`title` VARCHAR(150) NOT NULL,
						`content_admin` LONGTEXT DEFAULT NULL,
						`content_user` LONGTEXT DEFAULT NULL,
						UNIQUE KEY `type` (`type`)
						);";
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql);
				
				$defualt_email = get_option('admin_email');
				
				//Set up sample data
				$sql = "INSERT INTO `$table_name`(`type`,`to_mail`, `title`, `content_admin`, `content_user`) VALUES ('payment_received','$defualt_email', 'Payment Received', 'Hello<br/>We have received a payment from:<br/>%details%<br/>This payment is for order No. %inv%<br/>Cheers,<br/>%shop%','Hello<br/>We have received a payment from:<br/>%details%<br/>This payment is for order No. %inv%<br/>Cheers,<br/>%shop%')";
				$wpdb->query($sql);
				
				$sql = "INSERT INTO `$table_name`(`type`,`to_mail`, `title`, `content_admin`, `content_user`) VALUES ('order_placed','$defualt_email', 'Order Placed','Hello,<br/>Someone has just placed an order at your shop located at: %siteurl%<br/>Here are the details of the person who placed the order: <br/><p>%info%</p><br/>You can find the order details here: %order-log-transaction%<br/>Warm regards,<br/>%shop%','Hello,<br/>Thank you for placing your order at %shop%.<br/>We will process your order as soon as we receive payment!<br/>Warm regards,<br/>%shop%')";
				$wpdb->query($sql);
				
				$sql = "INSERT INTO `$table_name`(`type`,`to_mail`, `title`, `content_admin`, `content_user`) VALUES ('order_canceled','$defualt_email', 'Order Canceled','The order number %inv% has been cancelled!','Hello %fname%<br/>The order number %inv% has been cancelled!<br/>Warm regards,<br/>%shop%')";
				$wpdb->query($sql);
			}
			
			
			//Set up default options
			if($db_setup){
				$this->set_default_options();
			}
		}
		
		/**
		 * Set Default Settings options
		 */
		public function set_default_options(){
			$opts = array(
							'shopname' => 'DukaGate Shop', 
							'address' => '',
							'state_province' => '',
							'postal' => '',
							'city' => '',
							'country' => '',
							'currency' => 'USD',
							'currency_symbol' => '$',
							'checkout_page' => '',
							'thankyou_page' => '',
							'discounts' => 'false');
			update_option('dukagate_shop_settings', $opts);
		}
		
		/**
		 * Get Default Settings options
		 */
		public function get_default_settings(){
			$this->set_default_options();
			return get_option('dukagate_shop_settings');
		}
		
		/**
		 * Advanced Settings
		 */
		public function get_advanced_settings(){
			$opts = array(	
							'custom_products' => 'true', 
							'products_page' => '',
							'up_selling_page' => '',
							'up_selling_page_checkout' => 'false',
							'checkout_prod_image' => 'false',
							'checkout_prod_image_url' => '',
							'checkout_prod_image_width' => '100',
							'checkout_prod_image_height' => '100',
							'checkout_gateway_image' => 'false',
							'max_quantity' => '');
			
			update_option('dukagate_advanced_shop_settings', $opts);
			return get_option('dukagate_advanced_shop_settings');
		}
		
		/**
		 * Load plugins
		 */
		private function load_plugins($dir= '', $instatiate = false, $func_call = ''){
			$plugins = array();
			if ( !is_dir( $dir ) )
				return;
			if ( ! $dh = opendir( $dir ) )
				return;
				
			while ( ( $plugin = readdir( $dh ) ) !== false ) {
				if ( substr( $plugin, -4 ) == '.php' )
					$plugins[] = $dir . $plugin;
			}
			closedir( $dh );
			sort( $plugins );

			//include them suppressing errors
			foreach ($plugins as $file)
				@include_once( $file );
			
			if($instatiate){
				//allow plugins from an external location to register themselves
				if(!empty($func_call))
					do_action($func_call);
			}
		}
		
		/**
		 * Call Class Function
		 */
		static function call_class_function($class_name, $func_call,$param){
			if (class_exists($class_name)) {
				if(!empty($func_call))
					call_user_func(array($class_name, $func_call),$param);
			}else{
				$this->dg_delete_gateway($param); //Delete if payment gateway to prevent further errors
				$this->dg_delete_shipping_gateway($param); //Delete if shipping gateway to prevent further errors
				_e("$class_name not found!!","dg-lang");
			}
		}
		
		
		//Load Gateay Plugins
		private function load_gateway_plugins(){
			$plugins = array();
			$classes = array();
			$dir = DG_GATEWAYS;
			if ( !is_dir( $dir ) )
				return;
			if ( ! $dh = opendir( $dir ) )
				return;
				
			while ( ( $plugin = readdir( $dh ) ) !== false ) {
				if ( substr( $plugin, -4 ) == '.php' ){
					$plugins[] = $dir . $plugin;
				}
			}
			closedir( $dh );
			sort( $plugins );
			
			//include them suppressing errors
			foreach ($plugins as $file){
				include_once( $file );
				$fp = fopen($file, 'r');
				$class = $buffer = '';
				$i = 0;
				while (!$class) {
					if (feof($fp)) break;

					$buffer .= fread($fp, 512);
					if (preg_match('/class\s+(\w+)(.*)?\{/', $buffer, $matches)) {
						$classes[]  = $matches[1];
						break;
					}
				}
			}

			//Instantiate classes
			foreach ($classes as $class){
				$c = new $class();
				if(@$_REQUEST['dg_handle_payment_return_'.$c->get_plugin_slug()] === 'true'){
					$c->process_ipn_return();
				}
			}
			
		}
		
		/**
		 * Create csv file
		 * @param file_name - name of file to be created in DG_PLUGIN_DIR.'/report'
		 * @param data - array of data
		 * @param header - an array of headers
		 */
		function create_csv($file_name, $data, $header = array()){
			$filepath = DG_PLUGIN_DIR.'/report/'.$file_name.'.csv';
			if ( $fp = fopen($filepath, 'w') ) { 
				$show_header = true; 
				if ( empty($header) ) { 
					$show_header = false; 
					reset($data); 
					$line = current($data); 
					if ( !empty($line) ) { 
						reset($line); 
						$first = current($line); 
						if ( substr($first, 0, 2) == 'ID' && !preg_match('/["\\s,]/', $first) ) {
							array_shift($data); 
							array_shift($line); 
							if ( empty($line) ) { 
								fwrite($fp, "\"{$first}\"\r\n"); 
							} else { 
								fwrite($fp, "\"{$first}\","); 
								fputcsv($fp, $line); 
								fseek($fp, -1, SEEK_CUR); 
								fwrite($fp, "\r\n"); 
							} 
						} 
					} 
				} else { 
					reset($header); 
					$first = current($header); 
					if ( substr($first, 0, 2) == 'ID' && !preg_match('/["\\s,]/', $first) ) {
						 array_shift($header); 
						if ( empty($header) ) { 
							$show_header = false; 
							fwrite($fp, "\"{$first}\"\r\n"); 
						} else { 
							fwrite($fp, "\"{$first}\","); 
						} 
					} 
				} 
				if ( $show_header ) { 
					fputcsv($fp, $header); 
					fseek($fp, -1, SEEK_CUR); 
					fwrite($fp, "\r\n"); 
				} 
				foreach ( $data as $line ) { 
					fputcsv($fp, $line); 
					fseek($fp, -1, SEEK_CUR); 
					fwrite($fp, "\r\n"); 
				} 
				fclose($fp); 
			} else { 
				return false; 
			} 
			return true;
		}
		
		
		//List Order Logs
		function dg_list_order_logs(){
			$databases = self::db_names();
			global $wpdb;
			$table_name = $databases['transactions'];
			$sql = "SELECT * FROM `$table_name` ORDER BY `id` DESC;";
			return $wpdb->get_results($sql);
		}
		
		//Save Order logs
		function dg_save_order_log($invoice, $order_info, $payment_gateway, $payment_status){
			$databases = self::db_names();
			
			$dg_shop_settings = get_option('dukagate_shop_settings');
			global $wpdb;
			global $dukagate_mail;
			$total = 0.00;
			$products = $_SESSION['dg_cart'];
			if (is_array($products) && count($products) > 0) {
				foreach ($products as $cart_items => $cart) {
					$total += $cart['total'];
				}
			}
			$products = self::array_to_json($products);
			$shipping_info = '';
			$shipping_info_array = $_SESSION['delivery_options'];
			if(isset($_SESSION['delivery_options'])){
				if(is_array($shipping_info_array)){
					$shipping_info = self::array_to_json($shipping_info_array);
				}
			}
			$name = $order_info['dg_fullname'];
			$email = $order_info['dg_email'];
			$discount = $_SESSION['dg_cart_discount_value'];
			$discount = floatval(($discount * $total)/100);
			$total_shipping = 0.00;
			$dg_shipping = $_SESSION['dg_shipping_total'];
			if(is_array($dg_shipping)){
				foreach ($dg_shipping as $shipping) {
					$total_shipping += $shipping;
				}
			}
			$total = floatval(($total - $discount) + $total_shipping);
			if($total < 0){
				$total = 0.00;
			}
			$count = 20;
			$dg_form_elem = get_option('dukagate_checkout_options');
			$order_form_info = array();
			while($count > 0){
				if(!empty($order_info[$dg_form_elem[$count]['uname']])){
					$order_form_info[$count]['key'] = $dg_form_elem[$count]['name'];
					$order_form_info[$count]['value'] = $order_info[$dg_form_elem[$count]['uname']];
				}
				$count--;
			}		
			$order_info = self::array_to_json($order_form_info);
			$table_name = $databases['transactions'];
			$sql = "INSERT INTO `$table_name`(`invoice`,`products`, `shipping_info`, `names`, `email`,`order_info`,`payment_gateway`,`discount`,`total`, `shipping`,`payment_status`) 
					VALUES('$invoice', '$products', '$shipping_info' ,'$name', '$email', '$order_info' ,'$payment_gateway', $discount, $total, $total_shipping, '$payment_status')";
			$wpdb->query($sql);
						
			$sql = "SELECT `id` FROM `$table_name` WHERE `invoice` = '$invoice'";
			$order_id =  $wpdb->get_var($sql);
			
			//Send order placed mail
			$mail = $dukagate_mail->get_mail('order_placed');
			$to =  $mail->to_mail;
			$subject = $mail->title;
			$shop = get_bloginfo('name');
			
			//To Admin
			$url = site_url();
			$total = 0.00;
			$total_discount = 0.00;
			$info = 'Products<br/>';
			$info .= '<table style="text-align:left">';
			$info .= '<tr>';
			$info .= '<th scope="col" width="30%">'.__("Product","dg-lang").'</th>';
			$info .= '<th scope="col" width="10%">'.__("Quantity","dg-lang").'</th>';
			$info .= '<th scope="col" width="30%">'.__("Price","dg-lang").'</th>';
			$info .= '<th scope="col" width="30%">'.__("Total","dg-lang").'</th>';
			$info .= '</tr>';
			$cart_products = $_SESSION['dg_cart'];
			foreach ($cart_products as $cart_items => $cart) {
				$info .= '<tr>';
				$info .= '<td>'.$cart['product'].' ('.$cart['children'].')</td>';
				$info .= '<td>'.$cart['quantity'].'</td>';
				$info .= '<td>'.$dg_shop_settings['currency_symbol'].' '. number_format($cart['price'],2).'</td>';
				$info .= '<td>'.$dg_shop_settings['currency_symbol'].' '. number_format($cart['total'],2).'</td>';
				$info .= '</tr>';
				$total += $cart['total'];
			}
			$info .= '<tr>';
			$info .= '<td>Total Discount</td>';
			$info .= '<td>&nbsp;</td>';
			$info .= '<td>&nbsp;</td>';
			$info .= '<td>'.number_format($discount,2).'</td>';
			$info .= '</tr>';
			$info .= '<tr>';
			$info .= '<td class="total">'.__("Total Shipping","dg-lang").'</td>';
			$info .= '<td>&nbsp;</td>';
			$info .= '<td>&nbsp;</td>';
			$info .= '<td class="total amount">'.$dg_shop_settings['currency_symbol'].' '.number_format($total_shipping,2).'</td>';
			$info .= '</tr>';
			$info .= '<tr>';
			$info .= '<tr>';
			$info .= '<td>Total</td>';
			$info .= '<td>&nbsp;</td>';
			$info .= '<td>&nbsp;</td>';
			$info .= '<td>'.$dg_shop_settings['currency_symbol'].' '.number_format(($total - $discount) + $total_shipping ,2).'</td>';
			$info .= '</tr>';
			$info .= '</table>';
			$info .= 'User Info<br/>';
			foreach ($order_form_info as $order_in => $order) {
				foreach ($order as $key => $value) {
					$info .= $key.' :: '.$value .='<br/>';
				}
				
			}
			
			$transaction_url = admin_url("admin.php?page=dukagate-order-log&order_id=".$order_id);
			$message = $mail->content_admin;
			$array1 = array('%siteurl%', '%info%', '%shop%', '%order-log-transaction%');
			$array2 = array($url, $info, $shop, $transaction_url);
			$message = str_replace($array1, $array2, $message);
			
			$dukagate_mail->send_mail($to, $subject, $message);
			
			//To user
			$message = $mail->content_user;
			$array1 = array('%shop%');
			$array2 = array($shop);
			$message = str_replace($array1, $array2, $message);
			$dukagate_mail->send_mail($email, $subject, $message);
			
			return $total;
		}
		
		//Delete order log
		function dg_delete_order_log($id){
			$databases = self::db_names();
			global $wpdb;
			$table_name = $databases['transactions'];
			$sql = "DELETE FROM `$table_name` WHERE `id` = '$id';";
			$wpdb->query($sql);
		}
		
		//Update Order log status
		function dg_update_order_log($invoice, $status){
			$databases = self::db_names();
			global $wpdb;
			$table_name = $databases['transactions'];
			$sql = "UPDATE `$table_name` SET `payment_status` = '$status' WHERE `invoice` = '$invoice';";
			$wpdb->query($sql);
		}
		
		//Update Order log status by id
		function dg_update_order_log_by_id($id, $status){
			$databases = self::db_names();
			global $wpdb;
			$table_name = $databases['transactions'];
			$sql = "UPDATE `$table_name` SET `payment_status` = '$status' WHERE `id` = '$id';";
			$wpdb->query($sql);
		}
		
		//Get order log by invoice
		function dg_get_order_log_by_invoice($invoice){
			$databases = self::db_names();
			global $wpdb;
			$table_name = $databases['transactions'];
			$sql = "SELECT * FROM `$table_name` WHERE `invoice` = '$invoice'";
			return $wpdb->get_row($sql);
		}
		
		
		//Get order log by id
		function dg_get_order_log_by_id($id){
			$databases = self::db_names();
			global $wpdb;
			$table_name = $databases['transactions'];
			$sql = "SELECT * FROM `$table_name` WHERE `id` = '$id'";
			return $wpdb->get_row($sql);
		}
		
		//Save Payment Gateway
		function dg_save_payment_gateway($gateway_name, $gateway_slug, $gateway_class, $gateway_options, $currencies, $enabled = true){
			$databases = self::db_names();
			global $wpdb;
			
			$dg_gateway = $this->dg_get_payment_gateway($gateway_slug);
			$table_name = $databases['payment'];
			$active = ($enabled) ? 1: 0;
			if(empty($dg_gateway)){
				$sql = "INSERT INTO `$table_name`(`gateway_name`,`gateway_slug`,`gateway_class`, `gateway_options`, `currencies`, `active`) 
				        VALUES('$gateway_name','$gateway_slug','$gateway_class','$gateway_options', '$currencies' ,$active)";
			}else{
				$sql = "UPDATE `$table_name` SET `active` = $active WHERE `gateway_slug` = '$gateway_slug'";
			}
			$wpdb->query($sql);
			
		}
		
		//Delete Gateway
		function dg_delete_gateway($gateway_slug){
			$databases = self::db_names();
			global $wpdb;
			
			$dg_gateway = $this->dg_get_payment_gateway($gateway_slug);
			$table_name = $databases['payment'];
			if($dg_gateway){
				$sql = "DELETE FROM `$table_name` WHERE `gateway_slug` = '$gateway_slug'";
				$wpdb->query($sql);
			}
		}
		
		//Get Payment Gateway
		function dg_get_payment_gateway($gateway_slug){
			$databases = self::db_names();
			global $wpdb;
			$table_name = $databases['payment'];
			$sql = "SELECT * FROM `$table_name` WHERE `gateway_slug` = '$gateway_slug'";
			return $wpdb->get_row($sql);
		}
		
		//Update Gateway options
		function dg_save_gateway_options($gateway_slug, $gateway_options, $enabled){
			$databases = self::db_names();
			global $wpdb;
			$table_name = $databases['payment'];
			$sql = "UPDATE `$table_name` SET `gateway_options` = '$gateway_options', `active` = $enabled WHERE `gateway_slug` = '$gateway_slug'";
			$wpdb->query($sql);
		}
		
		//Get currencies
		function dg_get_gateway_currencies($gateway_slug){
			$databases = self::db_names();
			global $wpdb;
			$table_name = $databases['payment'];
			$sql = "SELECT `currencies` FROM `$table_name` WHERE `gateway_slug` = '$gateway_slug'";
			return $wpdb->get_var($sql);
		}
		
		//Get enabled status
		function dg_get_enabled_status($gateway_slug){
			$databases = self::db_names();
			global $wpdb;
			$table_name = $databases['payment'];
			$sql = "SELECT `active` FROM `$table_name` WHERE `gateway_slug` = '$gateway_slug'";
			return $wpdb->get_var($sql);
		}
		
		
		//Get Gateway options
		function dg_get_gateway_options($gateway_slug){
			$databases = self::db_names();
			global $wpdb;
			$table_name = $databases['payment'];
			$sql = "SELECT `gateway_options` FROM `$table_name` WHERE `gateway_slug` = '$gateway_slug'";
			return $wpdb->get_var($sql);
		}
		
		//Get Gateway class
		function dg_get_gateway_class($gateway_slug){
			$databases = self::db_names();
			global $wpdb;
			$table_name = $databases['payment'];
			$sql = "SELECT `gateway_class` FROM `$table_name` WHERE `gateway_slug` = '$gateway_slug'";
			return $wpdb->get_var($sql);
		}
		
		//List all Gateways
		function list_all_gateways(){
			$databases = self::db_names();
			global $wpdb;
			$table_name = $databases['payment'];
			$sql = "SELECT * FROM `$table_name`";
			return $wpdb->get_results($sql);
		}
		
		//List all active gateeways
		function list_all_active_gateways(){
			$databases = self::db_names();
			global $wpdb;
			$table_name = $databases['payment'];
			$sql = "SELECT * FROM `$table_name` where `active` = 1";
			return $wpdb->get_results($sql);
		}

		
		
		
		//Generate the checkout form
		function generate_checkout_form($layout){
			global $dukagate_settings;
			$dg_dukagate_settings = $dukagate_settings->get_settings();
			$dg_form_elem = get_option('dukagate_checkout_options');
			$cnt =  '';
			if($layout == 'fixed'){
				$cnt = '<table class="dg_checkout_table">';
				if(@$dg_form_elem['dg_fullname_visible'] == 'checked'){
					$cnt .= '<tr>';
					$cnt .= '<td>';
					$cnt .= '<label for="dg_fullname" class="dg_fullname">'.__("Full Names ","dg-lang").'</label>';
					$cnt .= '</td>';
					$cnt .= '<td>';
					$mandatory = '';
					if(@$dg_form_elem['dg_fullname_mandatory'] == 'checked'){
						$mandatory = 'required';
					}
					$cnt .= '<input type="text" class="'.$mandatory.' dg_fullname_input" name="dg_fullname" id="dg_fullname" />';
					$cnt .= '</td>';
					$cnt .= '</tr>';
				}
				if(@$dg_form_elem['dg_firstname_visible'] == 'checked'){
					$cnt .= '<tr>';
					$cnt .= '<td>';
					$cnt .= '<label for="dg_firstname" class="dg_firstname">'.__("First Name ","dg-lang").'</label>';
					$cnt .= '</td>';
					$cnt .= '<td>';
					$mandatory = '';
					if(@$dg_form_elem['dg_firstname_mandatory'] == 'checked'){
						$mandatory = 'required';
					}
					$cnt .= '<input type="text" class="'.$mandatory.' dg_firstname_input" name="dg_firstname" id="dg_firstname" />';
					$cnt .= '</td>';
					$cnt .= '</tr>';
				}
				if(@$dg_form_elem['dg_lastname_visible'] == 'checked'){
					$cnt .= '<tr>';
					$cnt .= '<td>';
					$cnt .= '<label for="dg_lastname" class="dg_lastname">'.__("Last Name ","dg-lang").'</label>';
					$cnt .= '</td>';
					$cnt .= '<td>';
					$mandatory = '';
					if(@$dg_form_elem['dg_lastname_mandatory'] == 'checked'){
						$mandatory = 'required';
					}
					$cnt .= '<input type="text" class="'.$mandatory.' dg_fullname_input" name="dg_lastname" id="dg_lastname" />';
					$cnt .= '</td>';
					$cnt .= '</tr>';
				}
				if(@$dg_form_elem['dg_email_visible'] == 'checked'){
					$cnt .= '<tr>';
					$cnt .= '<td>';
					$cnt .= '<label for="dg_email" class="dg_email">'.__("Email","dg-lang").'</label>';
					$cnt .= '</td>';
					$cnt .= '<td>';
					$mandatory = '';
					if(@$dg_form_elem['dg_email_mandatory'] == 'checked'){
						$mandatory = 'required';
					}
					$cnt .= '<input type="text" class="'.$mandatory.' dg_email" name="dg_email" id="dg_email" />';
					$cnt .= '</td>';
					$cnt .= '</tr>';
				}
				if(@$dg_form_elem['dg_phone_visible'] == 'checked'){
					$cnt .= '<tr>';
					$cnt .= '<td>';
					$cnt .= '<label for="dg_phone" class="dg_phone">'.__("Phone","dg-lang").'</label>';
					$cnt .= '</td>';
					$cnt .= '<td>';
					$mandatory = '';
					if(@$dg_form_elem['dg_phone_mandatory'] == 'checked'){
						$mandatory = 'required';
					}
					$cnt .= '<input type="text" class="'.$mandatory.' dg_phone" name="dg_phone" id="dg_phone" />';
					$cnt .= '</td>';
					$cnt .= '</tr>';
				}
				if(@$dg_form_elem['dg_country_visible'] == 'checked'){
					$cnt .= '<tr>';
					$cnt .= '<td>';
					$cnt .= '<label for="dg_country" class="dg_country">'.__("Country","dg-lang").'</label>';
					$cnt .= '</td>';
					$cnt .= '<td>';
					$mandatory = '';
					if(@$dg_form_elem['dg_country_mandatory'] == 'checked'){
						$mandatory = 'required';
					}
					$dg_country_code_name = $dg_dukagate_settings['country'];
					$cnt .= '<select name="dg_country" id="dg_country" style="width: 240px;" class="'.$mandatory.' dg_country">';
					foreach ($dg_country_code_name as $country_code => $country_name) {
						$cnt .= '<option value="' . $country_code . '" >' . __($country_name,"dg-lang") . '</option>';
					}
					$cnt .= '</select>';
					$cnt .= '</td>';
					$cnt .= '</tr>';
				}
				if(@$dg_form_elem['dg_state_visible'] == 'checked'){
					$cnt .= '<tr>';
					$cnt .= '<td>';
					$cnt .= '<label for="dg_state" class="dg_state">'.__("State","dg-lang").'</label>';
					$cnt .= '</td>';
					$cnt .= '<td>';
					$mandatory = '';
					if(@$dg_form_elem['dg_state_mandatory'] == 'checked'){
						$mandatory = 'required';
					}
					$cnt .= '<input type="text" class="'.$mandatory.' dg_state" name="dg_state" id="dg_state" />';
					$cnt .= '</td>';
					$cnt .= '</tr>';
				}
				if (is_array($dg_form_elem) && count($dg_form_elem) > 0) {
					$total = count($dg_form_elem);
					
					while($total > 0){
						if($dg_form_elem[$total]['visible'] == 'checked'){
							$input_type = $dg_form_elem[$total]['type'];
							$form_class = '';
							if($dg_form_elem[$total]['mandatory'] == 'checked'){
								$form_class = 'required';
							}
							if($input_type == 'text'){
								$input_type = '<input type="text" class="'.$form_class.' '.$dg_form_elem[$total]['uname'].'_input" name="'.$dg_form_elem[$total]['uname'].'" value="'.$dg_form_elem[$total]['initial'].'" id="'.$dg_form_elem[$total]['uname'].'" />';
							}else if($input_type == 'textarea'){
								$input_type = '<textarea class="'.$form_class.' '.$dg_form_elem[$total]['uname'].'_input" name="'.$dg_form_elem[$total]['uname'].'" id="'.$dg_form_elem[$total]['uname'].'" >'.$dg_form_elem[$total]['initial'].'</textarea>';
							}else if($input_type == 'checkbox'){
								$input_type = '<input type="checkbox" class="'.$form_class.' '.$dg_form_elem[$total]['uname'].'_input" name="'.$dg_form_elem[$total]['uname'].'" id="'.$dg_form_elem[$total]['uname'].'" />';
							}else if($input_type == 'paragraph'){
								$input_type = '<p class="'.$form_class.' '.$dg_form_elem[$total]['uname'].'_input">'.$dg_form_elem[$total]['initial'].'</p>';
							}
							$cnt .= '<tr>';
							$cnt .= '<td>';
							$cnt .= '<label for="'.$dg_form_elem[$total]['uname'].'" class="'.$dg_form_elem[$total]['uname'].'">'.$dg_form_elem[$total]['name'].'</label>';
							$cnt .= '</td>';
							$cnt .= '<td>';
							$cnt .= $input_type;
							$cnt .= '</td>';
							$cnt .= '</tr>';
						}
						$total --;
					}
					
				}
				$cnt .= '</table>';
			}else{
				$cnt .= '<div class="dg_user_info_form">';
				if(@$dg_form_elem['dg_fullname_visible'] == 'checked'){
					$cnt .= '<label for="dg_fullname" class="dg_fullname">'.__("Full Names ","dg-lang").'</label>';
					$mandatory = '';
					if(@$dg_form_elem['dg_fullname_mandatory'] == 'checked'){
						$mandatory = 'required';
					}
					$cnt .= '<input type="text" class="'.$mandatory.' dg_fullname_input" name="dg_fullname" id="dg_fullname" />';
					$cnt .= '<br/>';
				}
				if(@$dg_form_elem['dg_firstname_visible'] == 'checked'){
					$cnt .= '<label for="dg_firstname" class="dg_firstname">'.__("First Name ","dg-lang").'</label>';
					$mandatory = '';
					if(@$dg_form_elem['dg_firstname_mandatory'] == 'checked'){
						$mandatory = 'required';
					}
					$cnt .= '<input type="text" class="'.$mandatory.' dg_firstname_input" name="dg_firstname" id="dg_firstname" />';
					$cnt .= '<br/>';
				}
				if(@$dg_form_elem['dg_lastname_visible'] == 'checked'){
					$cnt .= '<label for="dg_lastname" class="dg_lastname">'.__("Last Name ","dg-lang").'</label>';
					$mandatory = '';
					if(@$dg_form_elem['dg_lastname_mandatory'] == 'checked'){
						$mandatory = 'required';
					}
					$cnt .= '<input type="text" class="'.$mandatory.' dg_lastname_input" name="dg_lastname" id="dg_lastname" />';
					$cnt .= '<br/>';
				}
				if(@$dg_form_elem['dg_email_visible'] == 'checked'){
					$cnt .= '<label for="dg_email" class="dg_email">'.__("Email","dg-lang").'</label>';
					$mandatory = '';
					if(@$dg_form_elem['dg_email_mandatory'] == 'checked'){
						$mandatory = 'required';
					}
					$cnt .= '<input type="text" class="'.$mandatory.' dg_email_input" name="dg_email" id="dg_email" />';
					$cnt .= '<br/>';
				}
				if(@$dg_form_elem['dg_phone_visible'] == 'checked'){
					$cnt .= '<label for="dg_phone" class="dg_phone">'.__("Phone","dg-lang").'</label>';
					$mandatory = '';
					if(@$dg_form_elem['dg_phone_mandatory'] == 'checked'){
						$mandatory = 'required';
					}
					$cnt .= '<input type="text" class="'.$mandatory.' dg_phone_input" name="dg_phone" id="dg_phone" />';
					$cnt .= '<br/>';
				}
				if(@$dg_form_elem['dg_country_visible'] == 'checked'){
					$cnt .= '<label for="dg_country" class="dg_country">'.__("Country","dg-lang").'</label>';
					$mandatory = '';
					if(@$dg_form_elem['dg_country_mandatory'] == 'checked'){
						$mandatory = 'required';
					}
					$dg_country_code_name = $dg_dukagate_settings['country'];
					$cnt .= '<select name="dg_country_input" id="dg_country_input" style="width: 240px;" class="'.$mandatory.' dg_country_input">';
					foreach ($dg_country_code_name as $country_code => $country_name) {
						$cnt .= '<option value="' . $country_code . '" >' . __($country_name,"dg-lang") . '</option>';
					}
					$cnt .= '<br/>';
				}
				if(@$dg_form_elem['dg_state_visible'] == 'checked'){
					$cnt .= '<label for="dg_state" class="dg_state">'.__("State","dg-lang").'</label>';
					$mandatory = '';
					if(@$dg_form_elem['dg_state_mandatory'] == 'checked'){
						$mandatory = 'required';
					}
					$cnt .= '<input type="text" class="'.$mandatory.' dg_state_input" name="dg_state" id="dg_state" />';
					$cnt .= '<br/>';
				}
				if (is_array($dg_form_elem) && count($dg_form_elem) > 0) {
					$total = count($dg_form_elem);
					
					while($total > 0){
						if(@$dg_form_elem[$total]['visible'] == 'checked'){
							$input_type = $dg_form_elem[$total]['type'];
							$form_class = '';
							if($dg_form_elem[$total]['mandatory'] == 'checked'){
								$form_class = 'required';
							}
							if($input_type == 'text'){
								$input_type = '<input type="text" class="'.$form_class.' '.$dg_form_elem[$total]['uname'].'_input" name="'.$dg_form_elem[$total]['uname'].'" value="'.$dg_form_elem[$total]['initial'].'" id="'.$dg_form_elem[$total]['uname'].'" />';
							}else if($input_type == 'textarea'){
								$input_type = '<textarea class="'.$form_class.' '.$dg_form_elem[$total]['uname'].'_input" name="'.$dg_form_elem[$total]['uname'].'" id="'.$dg_form_elem[$total]['uname'].'" >'.$dg_form_elem[$total]['initial'].'</textarea>';
							}else if($input_type == 'checkbox'){
								$input_type = '<input type="checkbox" class="'.$form_class.' '.$dg_form_elem[$total]['uname'].'_input" name="'.$dg_form_elem[$total]['uname'].'" id="'.$dg_form_elem[$total]['uname'].'" />';
							}else if($input_type == 'paragraph'){
								$input_type = '<p class="'.$form_class.' '.$dg_form_elem[$total]['uname'].'_input">'.$dg_form_elem[$total]['initial'].'</p>';
							}
							$cnt .= '<label for="'.$dg_form_elem[$total]['uname'].'" class="'.$dg_form_elem[$total]['uname'].'">'.$dg_form_elem[$total]['name'].'</label>';
							$cnt .= $input_type;
							$cnt .= '<br/>';
						}
						$total --;
					}
					
				}
				$cnt .= '</div>';
			}
			$dg_gateways = $this->list_all_active_gateways();
			$active = 0;
			if (is_array($dg_gateways) && count($dg_gateways) > 0) {
				if(count($dg_gateways) > 1){
					$cnt .= '<ul>';
					foreach ($dg_gateways as $dg_gateway) {
						if(intval($dg_gateway->active) == 1){
							$cnt .= '<li>';
							$cnt .= '<label for="dg_gateway"><input type="radio" class="required" name="dg_gateway_action" value="'.$dg_gateway->gateway_slug.'"/>'.$dg_gateway->gateway_name.'</label>';
							$cnt .= '</li>';
							$active += 1;
						}
					}
					$cnt .= '</ul>';
				}else{
					foreach ($dg_gateways as $dg_gateway) {
						if(intval($dg_gateway->active) == 1){
							$active += 1;
							$cnt .= '<label for="dg_gateway" class="'.$dg_gateway->gateway_slug.'">'.__("Pay Using ","dg-lang").$dg_gateway->gateway_name.'</label>';
							$cnt .= '<input type="hidden" name="dg_gateway_action" value="'.$dg_gateway->gateway_slug.'"/></label>';
						}
					}
				}
			}
			if($active > 0){
				$cnt .= '<p>';
				$cnt .= '<input type="submit" name="dg_process_payment_form" id="dg_process_payment_form" value="'.__("Process Payment","dg-lang").'" />';
				$cnt .= '</p>';
			}
			$cnt .= '<input type="hidden" name="ajax" value="true" />';
			$cnt .= '<input type="hidden" name="action" value="dg_process_cart" />';
			
			return $cnt;
		}
	}
}
?>