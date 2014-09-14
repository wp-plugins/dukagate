<?php
if(!class_exists('DukaGate')) {
	
	/**
	 * Sets up the DukaGate plugin.
	 *
	 * Set up Pruducts and load files needed by the plugin. Set up global functions used by the plugin
	 *
	 * @since 1.0
	 */
	class DukaGate{
	
		/**
		 * Plugin upload directory
		 *
		 * @since 1.0
		 *
		 * @var string
		 */
		public $plugin_upload_directory = '';
	
	
		/**
		 * Configures the plugin and future actions.
		 *
		 * @since 1.0
		 */
		public function __construct() {
			$upload_dir = wp_upload_dir();
			$this->plugin_upload_directory = $upload_dir['baseurl'].'/dg_download_files/';
			$this->init();
			//Set up File action
			add_action('dg_daily_file_event', array(&$this, 'delete_files_daily'));
			register_deactivation_hook(__FILE__,array(&$this,'destroy'));

			$this->set_up_directories_and_file_info();
			//Set up shop
			$this->set_up();
		}
	
		/**
		 * Initialize plugin and set it up
		 */
		function init(){
			$this->dukagate_db();
			add_action( 'dg_delete_files_daily', array(&$this, 'delete_files_daily') );
			$this->set_up_directories_and_file_info();
			update_option('dg_version_info', 3.8);
		}
		
		
		function set_up(){
			$this->load_plugins(DG_DUKAGATE_DIR.'/libs/');
			$this->load_plugins(DG_DUKAGATE_WIDGET_DIR);
			require_once(DG_DUKAGATE_DIR.'/dukagate-settings.php');
			require_once(DG_DUKAGATE_DIR.'/dukagate-mail.php');
			require_once(DG_DUKAGATE_DIR.'/dukagate-gateways.php');
			require_once(DG_DUKAGATE_DIR.'/dukagate-invoice.php');
			require_once(DG_DUKAGATE_DIR.'/dukagate-shipping.php');
			require_once(DG_DUKAGATE_DIR.'/dukagate-discounts.php');
			require_once(DG_DUKAGATE_DIR.'/dukagate-admin.php');
			require_once(DG_DUKAGATE_DIR.'/dukagate-products.php');
			require_once(DG_DUKAGATE_DIR.'/dukagate-cart.php');
			require_once(DG_DUKAGATE_DIR.'/dukagate-shortcodes.php');
			
			add_filter( 'cron_schedules', array(__CLASS__, 'custom_cron_schedules'));
			
			add_action('init', array(&$this, 'customer_role_type'));
			add_action('init', array(&$this, 'set_up_product_posts'));
			add_action('init', array(&$this, 'create_product_taxonomies'));
			add_action('add_meta_boxes', array(&$this,'set_up_product_meta_box'));
			add_action('grouped_product_add_form_fields', array(&$this,'grouped_product_metabox_add'), 10, 1);
			add_action('grouped_product_edit_form_fields', array(&$this,'grouped_product_metabox_edit'), 10, 1);
			add_action('created_grouped_product', array(&$this,'save_grouped_product_metadata'), 10, 1);	
			add_action('edited_grouped_product', array(&$this,'update_grouped_product_metadata'), 10, 1);
			add_action('delete_grouped_product', array(&$this,'delete_grouped_product_metadata'), 10, 1);
			add_action('save_post', array(&$this,'product_meta_save'));
			add_action('edit_post', array(&$this,'add_quick_edit_save'), 10, 3);
			add_action('post_edit_form_tag', array(&$this, 'post_form_tag'));
			add_action('admin_print_styles', array($this, 'admin_styles') );
			add_action('admin_print_scripts', array($this, 'admin_scripts') );
			add_action('wp_enqueue_scripts', array(&$this, 'set_up_styles'));
			add_action('wp_enqueue_scripts', array(&$this, 'set_up_js'));
			add_action('init', array(&$this, 'load_dukagate_plugins'));
			
			add_filter('manage_dg_product_posts_columns', array(&$this,'create_post_column'));
			add_action('manage_posts_custom_column', array(&$this,'render_post_columns'), 10, 2);
			add_action('admin_footer-edit.php', array(&$this,'admin_edit_dg_product_foot'), 11);
			add_action('quick_edit_custom_box',  array(&$this,'add_quick_edit'), 10, 2);
			add_action('init', array(&$this,'load_textdomain'));
			
			//Plugin info
			add_filter('plugin_row_meta', array(&$this,'set_up_plugin_info'),10,2);
			
			//Dashboard Widget
			add_action('wp_dashboard_setup', array(&$this,'revenue_graph'), 1);
			
			//add_action( 'template_redirect', array(&$this, 'product_templates') );
		}
		
		
		/**
		 * Load Text Domain
		 */
		function load_textdomain(){
			load_plugin_textdomain( 'dukagate', false, DG_DUKAGATE_DIR . '/lang/' );
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
						'meta' => $wpdb->prefix . "dkgt_taxonomymeta",
						'shipping' => $wpdb->prefix . "dkgt_shipping"						
						);
		}

		
		/**
		 * Register plugin links to allows users to easily access the settings page while on the plugin list
		 *
		 * @since 1.0
		 *
		 * @return void
		 */
		function set_up_plugin_info($links, $file){
			if ($file == DG_PLUGIN_BASENAME) {
				$links[] = '<a href="edit.php?post_type=dg_product&page=dukagate-settings">'.__('Settings','dukagate').'</a>';
				$links[] = '<a href="http://dukagate.info/faq/" target="_blank">'.__('FAQ','dukagate').'</a>';
				$links[] = '<a href="http://dukagate.info/documentation/" target="_blank">'.__('Documentation','dukagate').'</a>';
				$links[] = '<a href="http://dukagate.info/forums/forum/bugs/" target="_blank">'.__('Bugs','dukagate').'</a>';
				$links[] = '<a href="http://dukagate.info/contact/" target="_blank">'.__('Contact','dukagate').'</a>';
			}
			return $links;
		}
		
		/**
		 * Set up revenue widget
		 */
		function revenue_graph(){
			wp_add_dashboard_widget( 'dg_revenue_widget_admin', __( 'Dukagate Revenue Graph','dukagate' ), array(&$this,'draw_revenue_graph') );
		}
		
		/** 
		 * Draw revenue graph
		 */
		function draw_revenue_graph(){
			$dg_shop_settings = get_option('dukagate_shop_settings');
			printf(__("Total %d orders sold with total amount of %s %d"),$this->total_sales(),$dg_shop_settings['currency_symbol'],number_format($this->total_revenue(),2));
			$payment_status = array('Pending', 'Paid', 'Canceled');
			$days = array();
			?>
			<canvas id="revenuedata"></canvas>
			<script type="text/javascript">
				var width = jQuery('#dg_revenue_widget_admin').width() - 20;
				var height = jQuery('#dg_revenue_widget_admin').width() - 200;
				var g = new Bluff.Line('revenuedata', width+'x'+height);
				g.title =  '<?php __( 'Transactions Revenue' ); ?>';
				g.tooltips = true;
				g.theme_37signals();
				g.labels = {};
				<?php
				foreach($payment_status as $status){
					$results = $this->sales_summary($status);
					if(!empty($results)){
						?>
						g.data("<?php echo $status; ?>", <?php echo str_replace('"', "", json_encode($results['total'])); ?>);
						<?php
						array_push($days,$results['days']);
					}
				}
				$days = array_unique($days);
				$i=0;
				foreach($days as $day){
					foreach($day as $d){
						?>
						g.labels[<?php echo $i; ?>] = '<?php echo $d; ?>';
						<?php
						$i++;
					}
				}
				?>
				g.draw();
			</script>
			<?php
		}
		
		//Load admin styles
		function admin_styles(){
			wp_enqueue_style('dg_admin_css', DG_DUKAGATE_URL.'/css/dukagate_admin.css');
		}
		
		//Load up styles
		function set_up_styles(){
			wp_enqueue_style('dukagate_css', DG_DUKAGATE_URL.'/css/dukagate.css');
		}
		
		//Load admin scripts
		function admin_scripts(){
			wp_enqueue_script('dukagate_admin', DG_DUKAGATE_URL.'/js/dukagate_admin.js', array('jquery'), '', false);
			wp_enqueue_script('wysiwyg_js', DG_DUKAGATE_URL.'/js/wyzz0.65/wyzz.php', array('jquery'), '', false);
			wp_enqueue_script('js_class', DG_DUKAGATE_URL.'/js/graph/js-class.js',__FILE__);
			wp_enqueue_script('excanvas', DG_DUKAGATE_URL.'/js/graph/excanvas.js',__FILE__);
			wp_enqueue_script('bluff_min', DG_DUKAGATE_URL.'/js/graph/bluff-min.js',__FILE__);
			wp_enqueue_script( 'jquery-ui-datepicker', array( 'jquery' ) );
			wp_register_style('jquery-ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css');
			wp_enqueue_style( 'jquery-ui' );
			wp_enqueue_script("dukagate_admin");
			wp_enqueue_script("wysiwyg_js");
			wp_enqueue_script("js_class");
			wp_enqueue_script("excanvas");
			wp_enqueue_script("bluff_min");
		}
		
		//Load Javascript
		function set_up_js(){
			add_theme_support('html5');
			wp_enqueue_script('dukagate_js', DG_DUKAGATE_URL.'/js/dukagate.js', array('jquery'), '', false);
			wp_enqueue_script('jquery_validate', DG_DUKAGATE_URL.'/js/jquery.validate.js', array('jquery'), '', false);
			wp_enqueue_script('jquery_form', DG_DUKAGATE_URL.'/js/jquery.form.js', array('jquery'), '', false);
			wp_enqueue_script("jquery_validate");
			wp_enqueue_script("jquery_form");
			wp_enqueue_script("dukagate_js");
			wp_localize_script('dukagate_js', 'dg_js', array( 
				'dg_url' => get_bloginfo('url') , 
				'ajaxurl' => admin_url('admin-ajax.php'),
				'added_to_cart' => __( 'Added To Cart' ),
				'add_to_cart' => __( 'Add To Cart' ),
				'processing' => __( 'Processing' )
				) );
			
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
						'name' => __( 'Dukagate' ,'dukagate'),
						'singular_name' => __( 'Product' ,'dukagate'),
						'add_new' => __('Add New Product','dukagate'),
						'add_new_item' => __('Create New Product','dukagate'),
						'edit_item' => __('Edit Products','dukagate'),
						'edit' => __('Edit Product','dukagate'),
						'new_item' => __('New Product','dukagate'),
						'view_item' => __('View Product','dukagate'),
						'search_items' => __('Search Products','dukagate'),
						'not_found' => __('No Products Found','dukagate'),
						'not_found_in_trash' => __('No Products found in Trash','dukagate'),
						'view' => __('View Product','dukagate')
					),
					'description' => __('Products for your Dukagate store.','dukagate'),
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
				__( 'Product Details' ,'dukagate'),
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
			$post_id = $post->ID;
			$content_price = get_post_meta($post_id, 'price', true);
			$fixed_price = get_post_meta($post_id, 'fixed_price', true);
			$sku = get_post_meta($post_id, 'sku', true);
			$digital_file = get_post_meta($post_id, 'digital_file', true);
			$affiliate_url = get_post_meta($post_id, 'affiliate_url', true);
			
			//Variations
			$dg_variations = get_option('dg_product_variations');
			?>
			<table width="100%">
				<tr>
					<td><?php _e('Price:','dukagate');?> :</td>
					<td><input type="text" value="<?php echo $content_price; ?>" name="price" id="price"></td>
				</tr>
				<tr>
					<td><?php _e('Distinct Price:','dukagate');?> :</td>
					<td><input type="checkbox" value="checked" name="fixed_price" <?php echo ($fixed_price == 'checked') ? "checked='checked'": ""; ?> /></td>
				</tr>
				<tr>
					<td colspan="2">(<?php _e('if selected the grouped product will use this price ','dukagate');?>)</td>
				</tr>
				<tr>
					<td><?php _e('SKU:','dukagate');?> :</td>
					<td><input type="text" value="<?php echo $sku; ?>" name="sku" id="sku"></td>
				</tr>
				<tr>
					<td><?php _e('Digital File:','dukagate');?> :</td>
					<td>
						<?php
							if(!empty($digital_file)){
								?>
								<a href="<?php echo $digital_file['url']; ?>" target="_blank"><?php _e('Download','dukagate');?></a>
								<?php
							}else{
								_e('No file uploaded','dukagate');
							}
						?>
						<input type="file" id="dg_digifile" name="dg_digifile" value="" size="25" />
					</td>
				</tr>
				<tr>
					<td><?php _e('Affiliate URL:','dukagate');?> :</td>
					<td><input type="text" value="<?php echo $affiliate_url; ?>" name="affiliate_url" id="affiliate_url"></td>
				</tr>
			</table>
			<?php
		}
		
		/** 
		 * Post Form Tag
		 */
		public function post_form_tag(){
			echo ' enctype="multipart/form-data"';
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
			
			if(!empty($_FILES['dg_digifile']['name'])) {
				// Get the file type of the upload
				$arr_file_type = wp_check_filetype(basename($_FILES['dg_digifile']['name']));
				$uploaded_type = $arr_file_type['type'];
				$upload = $this->digital_upload($_FILES['dg_digifile']['name'], file_get_contents($_FILES['dg_digifile']['tmp_name']));
     
				if(isset($upload['error']) && $upload['error'] != 0) {
					wp_die('There was an error uploading your file. The error is: ' . $upload['error'],'dukagate');
				} else {
					update_post_meta($post_id, 'digital_file', $upload);     
				} // end if/else
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
		 * Create post columns
		 */
		function create_post_column($columns){
			$columns['image'] = __('Image','dukagate');
			$columns['price'] = __('Price','dukagate');
			return $columns;
		}
		
		/**
		 * Render the post column content
		 */
		function render_post_columns($column_name, $id){
			switch ($column_name) {
				case 'image':
					// show widget set
					$main_image = $this->product_image($id);
					$widget_set = NULL;
					if (!$main_image) 
						$main_image = DG_DUKAGATE_URL.'/images/no.jpg';    
					
					echo '<img src="' . $this->resize_image('', $main_image, 100, 100).'" width="100px" height="100px">';				
					break;
				case 'price':
					// show widget set
					$price = get_post_meta( $id, 'price', TRUE);
					$widget_set = NULL;
					if ($price) 
						echo $price;
					else 
						_e('Not Set');               
					break;
			}
		}
		
		/**
		 * Load custom javascript for quick edit
		 */
		function admin_edit_dg_product_foot(){
			$slug = 'dg_product';
			# load only when editing a dg_product
			if (   (isset($_GET['page']) && $_GET['page'] == $slug)
				|| (isset($_GET['post_type']) && $_GET['post_type'] == $slug)){
				echo '<script type="text/javascript" src="'.DG_DUKAGATE_URL.'/js/admin_edit.js"></script>';
			}
		}
		
		/**
		 * Add Quick Edit options
		 */
		function add_quick_edit($column_name, $post_type){
			if ($column_name != 'price') return;
			?>
			<fieldset class="inline-edit-col-left">
			<div class="inline-edit-col">
				<span class="title"><?php _e('Price','dukagate');?></span>
				<input type="hidden" name="dukagate_noncename" id="dukagate_noncename" value="dukagate_noncename" />
				<input type="text" value="" name="price" id="price" />
				<input type="hidden" name="is_quickedit" value="true" /></div>
			</div>
			</fieldset>
			<?php
		}
		
		/**
		 * Quick edit save
		 */
		public function add_quick_edit_save($post_id, $post){
			if( $post->post_type != 'dg_product' ) return;
			if (isset($_POST['is_quickedit']))
				update_post_meta($post_id, 'price', $_POST['price']);
		}
		
		/**
		 * Product Taxonimies
		 */
		function create_product_taxonomies() {
		  // Add new taxonomy, make it hierarchical (like categories)
		  $labels = array(
			'name' => _x( 'DukaGate Product Categories', 'taxonomy general name' ),
			'singular_name' => _x( 'DukaGate Product Category', 'taxonomy singular name' ),
			'search_items' =>  __( 'Search Product Categories' ,'dukagate'),
			'all_items' => __( 'All Product Categories' ,'dukagate'),
			'parent_item' => __( 'Parent Product Category' ,'dukagate'),
			'parent_item_colon' => __( 'Parent Product Category:','dukagate' ),
			'edit_item' => __( 'Edit Product Category Name' ,'dukagate'), 
			'update_item' => __( 'Update Product Category Name' ,'dukagate'),
			'add_new_item' => __( 'Add New Product Category Name' ,'dukagate'),
			'new_item_name' => __( 'New Product Category Name' ,'dukagate'),
			'menu_name' => __( 'Product Categories' ,'dukagate'),
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
		 * Load content for the custom posts
		 *
		 */
		function product_templates(){
			global $wp;
			if ($wp->query_vars["post_type"] == 'dg_product') {
				add_filter( 'the_content', array(&$this, 'product_template'), 99 );
			}
		}
		
		/** 
		 * Product Template
		 */
		function product_template(){
			echo DukaGate_Products::product_details(get_the_ID());
		}
		
		
		/** 
		 * Custom image meta box add
		 */
		function grouped_product_metabox_add($tag) { ?>
			<div class="form-field">
				<label for="image-url"><?php _e('Image URL','dukagate') ?></label>
				<input name="image-url" id="image-url" type="text" value="" size="40" />
				<p class="description"><?php _e('This image will be the thumbnail shown on the group page.','dukagate'); ?></p>
			</div>
			<div class="form-field">
				<label for="product_image_width"><?php _e('Product Image Width','dukagate') ?></label>
				<input name="product_image_width" id="product_image_width" type="text" value="" size="40" />
				<p class="description"><?php _e('This will be the width of the product images. If blank, it will use the default settings','dukagate'); ?></p>
			</div>
			<div class="form-field">
				<label for="product_image_height"><?php _e('Product Image Height','dukagate') ?></label>
				<input name="product_image_height" id="product_image_height" type="text" value="" size="40" />
				<p class="description"><?php _e('This will be the height of the product images. If blank, it will use the default settings','dukagate'); ?></p>
			</div>
			<div class="form-field">
				<label for="page-url"><?php _e('Page URL','dukagate') ?></label>
				<input name="page-url" id="page-url" type="text" value="" size="40" />
				<p class="description"><?php _e('This will be the group page url.','dukagate'); ?></p>
			</div>
			<div class="form-field">
				<label for="price"><?php _e('Price','dukagate') ?></label>
				<input name="price" id="price" type="text" value="" size="10" />
				<p class="description"><?php _e('This will be the group price.','dukagate'); ?></p>
			</div>
			<div class="form-field">
				<label for="product_select"><?php _e('Product Select','dukagate') ?></label>
				<select name="product_select" id="product_select">
					<option value="checkbox" ><?php _e('Use CheckBox','dukagate'); ?></option>
					<option value="radio" ><?php _e('Use Radio','dukagate'); ?></option>
				</select>
				<p class="description"><?php _e('This will be the select option for the product.','dukagate'); ?></p>
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
					<label for="image-url"><?php _e('Image URL','dukagate'); ?></label>
				</th>
				<td>
					<input name="image-url" id="image-url" type="text" value="<?php echo $this->grouped_product_crude($tag->term_id, 'image-url', '', 'get'); ?>" size="40" />
					<p class="description"><?php _e('This image will be the thumbnail shown on the group page.','dukagate'); ?></p>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="product_image_width"><?php _e('Product Image Width','dukagate') ?></label>
				</th>
				<td>
					<input name="product_image_width" id="product_image_width" type="text" value="" size="40" />
					<p class="description"><?php _e('This will be the width of the product images. If blank, it will use the default settings','dukagate'); ?></p>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="product_image_height"><?php _e('Product Image Height','dukagate') ?></label>
				</th>
				<td>
					<input name="product_image_height" id="product_image_height" type="text" value="" size="40" />
					<p class="description"><?php _e('This will be the height of the product images. If blank, it will use the default settings','dukagate'); ?></p>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="page-url"><?php _e('Page URL','dukagate'); ?></label>
				</th>
				<td>
					<input name="page-url" id="page-url" type="text" value="<?php echo $this->grouped_product_crude($tag->term_id, 'page-url', '', 'get'); ?>" size="40" />
					<p class="description"><?php _e('This will be the group page url.','dukagate'); ?></p>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="price"><?php _e('Price','dukagate'); ?></label>
				</th>
				<td>
					<input name="price" id="price" type="text" value="<?php echo $this->grouped_product_crude($tag->term_id, 'price', '', 'get'); ?>" size="40" />
					<p class="description"><?php _e('This will be the group price.','dukagate'); ?></p>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="product_select"><?php _e('Product Select','dukagate'); ?></label>
				</th>
				<td>
					<select name="product_select" id="product_select">
						<option value="none" <?php selected( $product_select, 'none' ); ?>><?php _e('Simple Click to select','dukagate'); ?></option>
						<option value="checkbox" <?php selected( $product_select, 'checkbox' ); ?>><?php _e('Use CheckBox','dukagate'); ?></option>
						<option value="radio" <?php selected( $product_select, 'radio' ); ?>><?php _e('Use Radio','dukagate'); ?></option>
					</select>
					<p class="description"><?php _e('This will be the select option for the product.','dukagate'); ?></p>
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
		 * Get array position
		 */
		static function array_pos($needle, $haystack){
			for ($i = 0, $l = count($haystack); $i < $l; ++$i) {
				if (in_array($needle, $haystack[$i])) return $i;
			}
			return false;
		}
		
		/**
		 * Convert Array To Json
		 */
		static function array_to_json($array){
			return json_encode($array);
		}
		
		/**
		 * Convert to mysql timestamp
		 *
		 */
		static function to_timestamp($input){
			$time = strtotime($input);
			$newformat = date('Y-m-d H:i:s',$time);
			return $newformat;
		}
		
		/**
		 * Make a blank file
		 */
		static function blank_file($destination){
			$dg_file_handle = fopen($destination, 'w') or die("can't open file");
			fclose($dg_file_handle);
		}
		
		/**
		 * Set Up directories
		 */
		public function set_up_directories_and_file_info(){
			
			if (!is_dir(DG_DOWNLOAD_FILES_DIR)) {
				mkdir(DG_DOWNLOAD_FILES_DIR, 0, true);
				chmod(DG_DOWNLOAD_FILES_DIR, 0777);
				self::blank_file(DG_DOWNLOAD_FILES_DIR.'/index.php');
			}
			if (!is_dir(DG_DOWNLOAD_FILES_DIR_TEMP)) {
				mkdir(DG_DOWNLOAD_FILES_DIR_TEMP, 0, true);
				chmod(DG_DOWNLOAD_FILES_DIR_TEMP, 0777);
				self::blank_file(DG_DOWNLOAD_FILES_DIR_TEMP.'/index.php');
			}
			if (!is_dir(DG_DUKAGATE_CONTENT_DIR)) {
				mkdir(DG_DUKAGATE_CONTENT_DIR, 0, true);
				chmod(DG_DUKAGATE_CONTENT_DIR, 0777);
				self::blank_file(DG_DUKAGATE_CONTENT_DIR.'/index.php');
			}
			if(is_dir(DG_DUKAGATE_CONTENT_DIR.'/cache')) {
				chmod(DG_DUKAGATE_CONTENT_DIR.'/cache', 0777);
			}
			else {
				mkdir(DG_DUKAGATE_CONTENT_DIR.'/cache', 0, true);
				chmod(DG_DUKAGATE_CONTENT_DIR.'/cache', 0777);
				self::blank_file(DG_DUKAGATE_CONTENT_DIR.'/cache/index.php');
			}
			if(is_dir(DG_DUKAGATE_CONTENT_DIR.'/temp')) {
				chmod(DG_DUKAGATE_CONTENT_DIR.'/temp', 0777);
			}
			else {
				mkdir(DG_DUKAGATE_CONTENT_DIR.'/temp', 0, true);
				chmod(DG_DUKAGATE_CONTENT_DIR.'/temp', 0777);
				self::blank_file(DG_DUKAGATE_CONTENT_DIR.'/temp/index.php');
			}
			if(is_dir(DG_DUKAGATE_CONTENT_DIR.'/report')) {
				chmod(DG_DUKAGATE_CONTENT_DIR.'/report', 0777);
			}
			else {
				mkdir(DG_DUKAGATE_CONTENT_DIR.'/report');
				chmod(DG_DUKAGATE_CONTENT_DIR.'/report', 0777);
				self::blank_file(DG_DUKAGATE_CONTENT_DIR.'/report/index.php');
			}
			
			if (!is_dir(DG_DUKAGATE_INVOICE_DIR)) {
				mkdir(DG_DUKAGATE_INVOICE_DIR, 0, true);
				chmod(DG_DUKAGATE_INVOICE_DIR, 0777);
				self::blank_file(DG_DUKAGATE_INVOICE_DIR.'/index.php');
			}

			
			//Download link info
			$dg_dl_expiration_time = get_option('dg_dl_expiration_time');
			if (!$dg_dl_expiration_time) {
				$dg_expiration_time = '48';
				update_option('dg_dl_expiration_time', $dg_expiration_time);
			}
			
			$date = date('M-d-Y', strtotime("+1 days"));
			$next_time_stamp = strtotime($date) + 18000;
			wp_schedule_event($next_time_stamp, 'daily', 'dg_delete_files_daily');
			
			
		}
		
		/**
		 * Delete expired downloaded files
		 */
		public function delete_files_daily(){
			$files = glob(DG_DOWNLOAD_FILES_DIR_TEMP.'/*', GLOB_BRACE);

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
		 * This function creates a copy of the uploaded file in the upload directory
		 *
		 *
		 */
		public function digital_upload($name, $bits, $time = null){
			if ( empty( $name ) )
				return array( 'error' => __( 'Empty filename' ) );
				
			$wp_filetype = wp_check_filetype( $name );
			if ( ! $wp_filetype['ext'] && ! current_user_can( 'unfiltered_upload' ) )
				return array( 'error' => __( 'Invalid file type' ,'dukagate') );
				
			
			$filename = wp_unique_filename( DG_DOWNLOAD_FILES_DIR, $name );
			
			$new_file = DG_DOWNLOAD_FILES_DIR . "$filename";

			$ifp = @ fopen( $new_file, 'wb' );
			if ( ! $ifp )
				return array( 'error' => sprintf( __( 'Could not write file %s' ,'dukagate'), $new_file ) );

			@fwrite( $ifp, $bits );
			fclose( $ifp );
			clearstatcache();

			// Set correct file permissions
			$stat = @ stat( dirname( $new_file ) );
			$perms = $stat['mode'] & 0007777;
			$perms = $perms & 0000666;
			@ chmod( $new_file, $perms );
			clearstatcache();

			// Compute the URL
			$url = $this->plugin_upload_directory . "$filename";

			return array( 'file' => $new_file, 'original' => $name, 'url' => $url , 'error' => false );
		}
		
		/**
		 * Upload File
		 *
		 */
		public function upload_file($name, $bits, $destination_dir, $destination_url){
			if ( empty( $name ) )
				return array( 'error' => __( 'Empty filename' ) );
				
			$wp_filetype = wp_check_filetype( $name );
			if ( ! $wp_filetype['ext'] && ! current_user_can( 'unfiltered_upload' ) )
				return array( 'error' => __( 'Invalid file type' ,'dukagate') );
				
			
			$filename = wp_unique_filename( $destination_dir, $name );
			
			$new_file = $destination_dir . "/$filename";

			$ifp = @ fopen( $new_file, 'wb' );
			if ( ! $ifp )
				return array( 'error' => sprintf( __( 'Could not write file %s' ,'dukagate'), $new_file ) );

			@fwrite( $ifp, $bits );
			fclose( $ifp );
			clearstatcache();

			// Set correct file permissions
			$stat = @ stat( dirname( $new_file ) );
			$perms = $stat['mode'] & 0007777;
			$perms = $perms & 0000666;
			@ chmod( $new_file, $perms );
			clearstatcache();

			// Compute the URL
			$url = $destination_url . "/$filename";

			return array( 'file' => $new_file, 'original' => $name, 'url' => $url , 'error' => false );
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
				`tax` FLOAT NOT NULL DEFAULT '0',
                `total` FLOAT NOT NULL,
                `payment_status` ENUM ('Pending', 'Paid', 'Canceled'),
                UNIQUE (`invoice`),
                PRIMARY KEY  (id)
                )";
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql);
				$db_setup = true;
				update_option('dg_database_update', 1);
			}
			
			if($wpdb->get_var("show tables like '$table_name'") == $table_name) {
				$dg_database_update = get_option('dg_database_update');
				if(empty($dg_database_update)){
					$alter_sql = "ALTER TABLE `$table_name` ADD `tax` FLOAT NOT NULL DEFAULT '0' AFTER `discount`";
					$wpdb->query($alter_sql);
					update_option('dg_database_update', 1);
				}
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
				
				$sql = "INSERT INTO `$table_name`(`type`,`to_mail`, `title`, `content_admin`, `content_user`) VALUES ('order_placed','$defualt_email', 'Order Placed','Hello,<br/>Someone has just placed an order at your shop located at: %siteurl%<br/>Here are the details of the person who placed the order: <br/><p>%info%</p><br/>You can find the order details here: %order-log-transaction%<br/>Warm Regards,<br/>%shop%','Hello,<br/>Thank you for placing your order at %shop%.<br/>We will process your order as soon as we receive payment!<br/>Warm Regards,<br/>%shop%')";
				$wpdb->query($sql);
				
				$sql = "INSERT INTO `$table_name`(`type`,`to_mail`, `title`, `content_admin`, `content_user`) VALUES ('order_canceled','$defualt_email', 'Order Canceled','The order number %inv% has been cancelled!','Hello %fname%<br/>The order number %inv% has been cancelled!<br/>Warm Regards,<br/>%shop%')";
				$wpdb->query($sql);
				
				$sql = "INSERT INTO `$table_name`(`type`,`to_mail`, `title`, `content_admin`, `content_user`) VALUES ('new_user','$defualt_email', 'New User','A new user has registered: %fname% %lname%','Hello %fname%<br/>Your new account has been created. Please log on to %siteurl% with your credentials : <br/> Username : %username% <br/> Password : %password% <br/>Warm Regards,<br/>%shop%')";
				$wpdb->query($sql);
			}
			
			
			//Set up default options
			if($db_setup){
				$this->set_default_options();
			}
			
			//update
			if(get_option('dg_version_info') <= 3.47){
				$sql = "INSERT INTO `$table_name`(`type`,`to_mail`, `title`, `content_admin`, `content_user`) VALUES ('new_user','$defualt_email', 'New User','A new user has registered: %fname% %lname%','Hello %fname%<br/>Your new account has been created. Please log on to %siteurl% with your credentials : <br/> Username : %username% <br/> Password : %password% <br/>Warm Regards,<br/>%shop%')";
				$wpdb->query($sql);
				update_option('dg_version_info', 3.7);
			}
		}
		
		/**
		 * Set Default Settings options
		 */
		public function set_default_options(){
			$opts = array(
							'shopname' => __('DukaGate Shop','dukagate'), 
							'address' => '',
							'state_province' => '',
							'postal' => '',
							'city' => '',
							'country' => '',
							'currency' => 'USD',
							'currency_symbol' => '$',
							'currency_position' => 'left',
							'force_login' => 'false',
							'register_user' => 'false',
							'checkout_page' => '',
							'thankyou_page' => '',
							'discounts' => 'false',
							'shipping' => 'false');
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
							'max_quantity' => '',
							'products_image' => 'true',
							'pdf_invoices' => 'false',
							'pdf_invoice_file' => 'default');
			
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
				_e("$class_name not found!!");
			}
		}
		
		/**
		 * Load plugins
		 */
		function load_dukagate_plugins(){
			$this->load_gateway_plugins();
			$this->load_shipping_plugins();
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
		
		//Load shipping plugins
		private function load_shipping_plugins(){
			$plugins = array();
			$classes = array();
			$dir = DG_SHIPPING;
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
			}
		}
		
		/**
		 * Create csv file
		 * @param file_name - name of file to be created in DG_PLUGIN_DIR.'/report'
		 * @param data - array of data
		 * @param header - an array of headers
		 */
		function create_csv($file_name, $data, $header = array()){
			$filepath = DG_DUKAGATE_CONTENT_DIR.'/report/'.$file_name.'.csv';
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
		
		//Delete order log
		function dg_delete_order_log($id){
			$databases = self::db_names();
			global $wpdb;
			$table_name = $databases['transactions'];
			$sql = "DELETE FROM `$table_name` WHERE `id` = $id";
			return $wpdb->get_results($sql);
		}
		
		
		//List Order Logs
		function dg_list_order_logs(){
			$databases = self::db_names();
			global $wpdb;
			$table_name = $databases['transactions'];
			$sql = "SELECT * FROM `$table_name` ORDER BY `id` DESC;";
			return $wpdb->get_results($sql);
		}

		function dg_list_user_order_logs($email){
			$databases = self::db_names();
			global $wpdb;
			$table_name = $databases['transactions'];
			$sql = "SELECT * FROM `$table_name` WHERE `email` = '$email' ORDER BY `id` DESC;";
			return $wpdb->get_results($sql);
		}

		/**
		 * Check if order is for user
		 *
		 */
		function is_order_for_user($email, $orderid){
			$databases = self::db_names();
			global $wpdb;
			$table_name = $databases['transactions'];
			$sql = "SELECT * FROM `$table_name` WHERE `email` = '$email' AND `id` = $orderid";
			return $wpdb->get_row($sql);
		}
		
		
		//Filter Order Logs
		function dg_filter_order_logs($startdate, $endate){
			$databases = self::db_names();
			global $wpdb;
			$table_name = $databases['transactions'];
			$sql = "SELECT * FROM `$table_name` WHERE `date` BETWEEN '$startdate' AND '$endate' ORDER BY `id` DESC;";
			return $wpdb->get_results($sql);
		}
		
		//Save Order logs
		function dg_save_order_log($invoice, $order_info, $payment_gateway, $payment_status){
			$databases = self::db_names();
			
			$dg_shop_settings = get_option('dukagate_shop_settings');
			$dg_advanced_shop_settings = get_option('dukagate_advanced_shop_settings');
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
			$fname = $order_info['dg_firstname'];
			$lname = $order_info['dg_lastname'];
			if(empty($name))
				$name = $fname.' '.$lname;
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
			$total_tax = 0;
			if(!empty($dg_shop_settings['tax_rate'])){
				$total_tax = $total * $dg_shop_settings['tax_rate'] / 100;
				$total = $total + $total_tax;
			}
			$count = 20;
			$dg_form_elem = get_option('dukagate_checkout_options');
			$order_form_info = array();
			while($count > 0){
				if(!empty($order_info[$dg_form_elem[$count]['uname']])){
					$order_form_info[][$dg_form_elem[$count]['name']] = $order_info[$dg_form_elem[$count]['uname']];
				}
				$count--;
			}		
			$order_form_info[]['First Name'] = $order_info['dg_firstname'];
			$order_form_info[]['Last Name'] = $order_info['dg_lastname'];
			$order_form_info[]['Company'] = $order_info['dg_company'];
			$order_form_info[]['Country'] = $order_info['dg_country'];
			$order_form_info[]['Phone'] = $order_info['dg_phone'];
			
			$order_info = self::array_to_json($order_form_info);
			$table_name = $databases['transactions'];
			$sql = "INSERT INTO `$table_name`(`invoice`,`products`, `shipping_info`, `names`, `email`,`order_info`,`payment_gateway`,`discount`,`total`, `shipping`,`tax`,`payment_status`) 
					VALUES('$invoice', '$products', '$shipping_info' ,'$name', '$email', '$order_info' ,'$payment_gateway', $discount, $total, $total_shipping, $total_tax, '$payment_status')";
			$wpdb->query($sql);
						
			$sql = "SELECT `id` FROM `$table_name` WHERE `invoice` = '$invoice'";
			$order_id =  $wpdb->get_var($sql);

			if($dg_shop_settings['register_user'] === 'yes'){
				$this->save_user($email, $email, $fname, $lname); //save user and send mail
			}
			
			//Send order placed mail
			$mail = $dukagate_mail->get_mail('order_placed');
			$to =  $mail->to_mail;
			$subject = $mail->title;
			
			$shop = $dg_shop_settings['shopname'];
			
			$file_path = '';
			if($dg_advanced_shop_settings['pdf_invoices'] == 'true'){
				DukaGate_Invoice::generate_invoice($_SESSION['dg_cart'], $order_form_info, $invoice);
				$file_path = DG_DUKAGATE_INVOICE_URL.'/invoice_' . $invoice . '.pdf';
				$file_path = '<a href="'.$file_path.'" target="_blank">Invoice</a>';
			}
			
			//To Admin
			$url = site_url();
			$total = 0.00;
			$total_discount = 0.00;
			$info = __("Products",'dukagate');
			$info .= '<br/>';
			$info .= '<table style="text-align:left">';
			$info .= '<tr>';
			$info .= '<th scope="col" width="30%">'.__("Product",'dukagate').'</th>';
			$info .= '<th scope="col" width="10%">'.__("Quantity",'dukagate').'</th>';
			$info .= '<th scope="col" width="30%">'.__("Price",'dukagate').'</th>';
			$info .= '<th scope="col" width="30%">'.__("Total",'dukagate').'</th>';
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
			$info .= '<td>'.__("Total Discount",'dukagate').'</td>';
			$info .= '<td>&nbsp;</td>';
			$info .= '<td>&nbsp;</td>';
			$info .= '<td>'.number_format($discount,2).'</td>';
			$info .= '</tr>';
			$info .= '<tr>';
			$info .= '<td class="total">'.__("Total Shipping",'dukagate').'</td>';
			$info .= '<td>&nbsp;</td>';
			$info .= '<td>&nbsp;</td>';
			$info .= '<td class="total amount">'.$dg_shop_settings['currency_symbol'].' '.number_format($total_shipping,2).'</td>';
			$info .= '</tr>';
			if(!empty($dg_shop_settings['tax_rate'])){
				$info .= '<tr>';
				$info .= '<td class="tax">'.__("Total Tax",'dukagate').'</td>';
				$info .= '<td>&nbsp;</td>';
				$info .= '<td>&nbsp;</td>';
				$info .= '<td class="total tax">'.$dg_shop_settings['currency_symbol'].' '.number_format($total_tax,2).'</td>';
				$info .= '</tr>';
			}
			$info .= '<tr>';
			$info .= '<td>'.__("Total",'dukagate').'</td>';
			$info .= '<td>&nbsp;</td>';
			$info .= '<td>&nbsp;</td>';
			$info .= '<td>'.$dg_shop_settings['currency_symbol'].' '.number_format(($total - $discount) + $total_shipping ,2).'</td>';
			$info .= '</tr>';
			$info .= '</table>';
			$info .= __("User Info",'dukagate').'<br/>';
			foreach ($order_form_info as $order_in => $order) {
				foreach ($order as $key => $value) {
					$info .= $key.' :: '.$value .='<br/>';
				}
				
			}
			
			
			$transaction_url = admin_url("edit.php?post_type=dg_product&page=dukagate-order-log&order_id=".$order_id);
			$user_transaction_url = admin_url("admin.php?post_type=dg_product&page=dukagate-order-log&order_id=".$order_id);
			$message = $mail->content_admin;
			
			$array1 = array('%siteurl%', '%info%', '%shop%', '%order-log-transaction%','%fname%','%lname%','%fullnames%','%invoice-link%');
			$array2 = array($url, $info, $shop, $transaction_url,$fname,$lname,$name,$file_path);
			$u_array2 = array($url, $info, $shop, $user_transaction_url,$fname,$lname,$name,$file_path);

			$message = str_replace($array1, $array2, $message);
			
			$dukagate_mail->send_mail($to, $subject, $message);
			
			//To user
			$message = $mail->content_user;
			$message = str_replace($array1, $u_array2, $message);
			$dukagate_mail->send_mail($email, $subject, $message);
			
			return $total;
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
			if($enabled){
				$active = ($enabled) ? 1: 0;
				if(empty($dg_gateway)){
					$sql = "INSERT INTO `$table_name`(`gateway_name`,`gateway_slug`,`gateway_class`, `gateway_options`, `currencies`, `active`) 
							VALUES('$gateway_name','$gateway_slug','$gateway_class','$gateway_options', '$currencies' ,$active)";
				}else{
					$sql = "UPDATE `$table_name` SET `active` = $active, `gateway_name` = '$gateway_name' WHERE `gateway_slug` = '$gateway_slug'";
				}
			}else{
				$sql = "DELETE FROM `$table_name` WHERE `gateway_slug` = '$gateway_slug'";
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
		
		//Get Gateway name
		function dg_get_gateway_name($gateway_slug){
			$databases = self::db_names();
			global $wpdb;
			$table_name = $databases['payment'];
			$sql = "SELECT `gateway_name` FROM `$table_name` WHERE `gateway_slug` = '$gateway_slug'";
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

		//Save Shipping Gateway
		function dg_save_shipping_gateway($name, $slug, $class, $shipping_info, $enabled = true){
			$databases = self::db_names();
			global $wpdb;
			
			$dg_gateway = $this->dg_get_shipping_gateway($slug);
			$table_name = $databases['shipping'];
			$active = ($enabled) ? 1: 0;
			if(empty($dg_gateway)){
				$sql = "INSERT INTO `$table_name`(`name`,`slug`,`class`, `shipping_info`, `active`) 
						VALUES('$name','$slug','$class','$shipping_info' ,$active)";
			}else{
				$sql = "UPDATE `$table_name` SET `active` = $active WHERE `slug` = '$slug'";
			}
			$wpdb->query($sql);
			
		}

		//Delete Gateway
		function dg_delete_shipping_gateway($gateway_slug){
			$databases = self::db_names();
			global $wpdb;
			
			$dg_gateway = $this->dg_get_shipping_gateway($gateway_slug);
			$table_name = $databases['shipping'];
			if($dg_gateway){
				$sql = "DELETE FROM `$table_name` WHERE `slug` = '$gateway_slug'";
				$wpdb->query($sql);
			}
		}

		//Get Shipping Gateway
		function dg_get_shipping_gateway($slug){
			$databases = self::db_names();
			global $wpdb;
			$table_name = $databases['shipping'];
			$sql = "SELECT * FROM `$table_name` WHERE `slug` = '$slug'";
			return $wpdb->get_row($sql);
		}

		//Update Shipping Gateway options
		function dg_save_shipping_info($slug, $shipping_info, $enabled){
			$databases = self::db_names();
			global $wpdb;
			$table_name = $databases['shipping'];
			$sql = "UPDATE `$table_name` SET `shipping_info` = '$shipping_info', `active` = $enabled WHERE `slug` = '$slug'";
			$wpdb->query($sql);
		}
		
		/**
		 * Save Shipping info only
		 */
		function dg_save_shipping_info_only($slug, $shipping_info){
			$databases = self::db_names();
			global $wpdb;
			$table_name = $databases['shipping'];
			$sql = "UPDATE `$table_name` SET `shipping_info` = '$shipping_info' WHERE `slug` = '$slug'";
			$wpdb->query($sql);
		}
		
		/**
		 * enable or disable Shipping gateway
		 */
		function dg_save_shipping_active($slug, $enabled){
			$databases = self::db_names();
			global $wpdb;
			$table_name = $databases['shipping'];
			$sql = "UPDATE `$table_name` SET `active` = $enabled WHERE `slug` = '$slug'";
			$wpdb->query($sql);
		}


		//Get Shipping enabled status
		function dg_get_enabled_shipping_status($slug){
			$databases = self::db_names();
			global $wpdb;
			$table_name = $databases['shipping'];
			$sql = "SELECT `active` FROM `$table_name` WHERE `slug` = '$slug'";
			return $wpdb->get_var($sql);
		}


		//Get Shipping Gateway options
		function dg_get_shipping_info($slug){
			$databases = self::db_names();
			global $wpdb;
			$table_name = $databases['shipping'];
			$sql = "SELECT `shipping_info` FROM `$table_name` WHERE `slug` = '$slug'";
			return $wpdb->get_var($sql);
		}

		//Get Shipping Gateway class
		function dg_get_shipping_class($slug){
			$databases = self::db_names();
			global $wpdb;
			$table_name = $databases['shipping'];
			$sql = "SELECT `class` FROM `$table_name` WHERE `slug` = '$slug'";
			return $wpdb->get_var($sql);
		}

		//List all Shipping Gateways
		function list_all_shipping_gateways(){
			$databases = self::db_names();
			global $wpdb;
			$table_name = $databases['shipping'];
			$sql = "SELECT * FROM `$table_name`";
			return $wpdb->get_results($sql);
		}
		
		//List all active Shipping options
		function list_all_active_shipping_gateways(){
			$databases = self::db_names();
			global $wpdb;
			$table_name = $databases['shipping'];
			$sql = "SELECT * FROM `$table_name` where `active` = 1";
			return $wpdb->get_results($sql);
		}
		
		
		//Generate the checkout form
		function generate_checkout_form($layout){
			global $dukagate_settings;
			$dg_shop_settings = get_option('dukagate_shop_settings');
			$dg_dukagate_settings = $dukagate_settings->get_settings();
			$dg_form_elem = get_option('dukagate_checkout_options');
			$cnt =  '';
			$get_user = false;
			$fullnames = "";
			$firstname = "";
			$lastname = "";
			$email = "";
			if($dg_shop_settings['register_user'] === 'yes'){
				$get_user = true;
				if ( is_user_logged_in() ){
					global $current_user;
					get_currentuserinfo();
					$email = $current_user->user_email;
					$firstname = $current_user->user_firstname;
					$lastname = $current_user->user_lastname;
					$fullnames = $firstname.' '.$lastname;
				}
			}
			
			if($layout == 'fixed'){
				$cnt = '<table class="dg_checkout_table">';
				if(@$dg_form_elem['dg_fullname_visible'] == 'checked'){
					$cnt .= '<tr>';
					$cnt .= '<td>';
					$cnt .= '<label for="dg_fullname" class="dg_fullname">'.__("Full Names ",'dukagate').'</label>';
					$cnt .= '</td>';
					$cnt .= '<td>';
					$mandatory = '';
					if(@$dg_form_elem['dg_fullname_mandatory'] == 'checked'){
						$mandatory = 'required';
					}
					$cnt .= '<input type="text" class="'.$mandatory.' dg_fullname_input" name="dg_fullname" id="dg_fullname" value="'.$fullnames.'"/>';
					$cnt .= '</td>';
					$cnt .= '</tr>';
				}
				if(@$dg_form_elem['dg_firstname_visible'] == 'checked'){
					$cnt .= '<tr>';
					$cnt .= '<td>';
					$cnt .= '<label for="dg_firstname" class="dg_firstname">'.__("First Name ",'dukagate').'</label>';
					$cnt .= '</td>';
					$cnt .= '<td>';
					$mandatory = '';
					if(@$dg_form_elem['dg_firstname_mandatory'] == 'checked'){
						$mandatory = 'required';
					}
					$cnt .= '<input type="text" class="'.$mandatory.' dg_firstname_input" name="dg_firstname" id="dg_firstname" value="'.$firstname.'" />';
					$cnt .= '</td>';
					$cnt .= '</tr>';
				}
				if(@$dg_form_elem['dg_lastname_visible'] == 'checked'){
					$cnt .= '<tr>';
					$cnt .= '<td>';
					$cnt .= '<label for="dg_lastname" class="dg_lastname">'.__("Last Name ",'dukagate').'</label>';
					$cnt .= '</td>';
					$cnt .= '<td>';
					$mandatory = '';
					if(@$dg_form_elem['dg_lastname_mandatory'] == 'checked'){
						$mandatory = 'required';
					}
					$cnt .= '<input type="text" class="'.$mandatory.' dg_fullname_input" name="dg_lastname" id="dg_lastname" value="'.$lastname.'" />';
					$cnt .= '</td>';
					$cnt .= '</tr>';
				}
				if(@$dg_form_elem['dg_email_visible'] == 'checked'){
					$cnt .= '<tr>';
					$cnt .= '<td>';
					$cnt .= '<label for="dg_email" class="dg_email">'.__("Email",'dukagate').'</label>';
					$cnt .= '</td>';
					$cnt .= '<td>';
					$mandatory = '';
					if(@$dg_form_elem['dg_email_mandatory'] == 'checked'){
						$mandatory = 'required';
					}
					$cnt .= '<input type="text" class="'.$mandatory.' dg_email" name="dg_email" id="dg_email" value="'.$email.'" />';
					$cnt .= '</td>';
					$cnt .= '</tr>';
				}
				if(@$dg_form_elem['dg_phone_visible'] == 'checked'){
					$cnt .= '<tr>';
					$cnt .= '<td>';
					$cnt .= '<label for="dg_phone" class="dg_phone">'.__("Phone",'dukagate').'</label>';
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
					$cnt .= '<label for="dg_country" class="dg_country">'.__("Country",'dukagate').'</label>';
					$cnt .= '</td>';
					$cnt .= '<td>';
					$mandatory = '';
					if(@$dg_form_elem['dg_country_mandatory'] == 'checked'){
						$mandatory = 'required';
					}
					$dg_country_code_name = $dg_dukagate_settings['country'];
					$cnt .= '<select name="dg_country" id="dg_country" style="width: 240px;" class="'.$mandatory.' dg_country">';
					foreach ($dg_country_code_name as $country_code => $country_name) {
						$cnt .= '<option value="' . $country_code . '" >' . __($country_name) . '</option>';
					}
					$cnt .= '</select>';
					$cnt .= '</td>';
					$cnt .= '</tr>';
				}
				if(@$dg_form_elem['dg_state_visible'] == 'checked'){
					$cnt .= '<tr>';
					$cnt .= '<td>';
					$cnt .= '<label for="dg_state" class="dg_state">'.__("State",'dukagate').'</label>';
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
					$total = 20;
					
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
					$cnt .= '<label for="dg_fullname" class="dg_fullname">'.__("Full Names ",'dukagate').'</label>';
					$mandatory = '';
					if(@$dg_form_elem['dg_fullname_mandatory'] == 'checked'){
						$mandatory = 'required';
					}
					$cnt .= '<input type="text" class="'.$mandatory.' dg_fullname_input" name="dg_fullname" id="dg_fullname" value="'.$fullnames.'" />';
					$cnt .= '<br/>';
				}
				if(@$dg_form_elem['dg_firstname_visible'] == 'checked'){
					$cnt .= '<label for="dg_firstname" class="dg_firstname">'.__("First Name ",'dukagate').'</label>';
					$mandatory = '';
					if(@$dg_form_elem['dg_firstname_mandatory'] == 'checked'){
						$mandatory = 'required';
					}
					$cnt .= '<input type="text" class="'.$mandatory.' dg_firstname_input" name="dg_firstname" id="dg_firstname" value="'.$firstname.'" />';
					$cnt .= '<br/>';
				}
				if(@$dg_form_elem['dg_lastname_visible'] == 'checked'){
					$cnt .= '<label for="dg_lastname" class="dg_lastname">'.__("Last Name ",'dukagate').'</label>';
					$mandatory = '';
					if(@$dg_form_elem['dg_lastname_mandatory'] == 'checked'){
						$mandatory = 'required';
					}
					$cnt .= '<input type="text" class="'.$mandatory.' dg_lastname_input" name="dg_lastname" id="dg_lastname" value="'.$lastname.'" />';
					$cnt .= '<br/>';
				}
				if(@$dg_form_elem['dg_email_visible'] == 'checked'){
					$cnt .= '<label for="dg_email" class="dg_email">'.__("Email",'dukagate').'</label>';
					$mandatory = '';
					if(@$dg_form_elem['dg_email_mandatory'] == 'checked'){
						$mandatory = 'required';
					}
					$cnt .= '<input type="text" class="'.$mandatory.' dg_email_input" name="dg_email" id="dg_email" value="'.$email.'" />';
					$cnt .= '<br/>';
				}
				if(@$dg_form_elem['dg_phone_visible'] == 'checked'){
					$cnt .= '<label for="dg_phone" class="dg_phone">'.__("Phone",'dukagate').'</label>';
					$mandatory = '';
					if(@$dg_form_elem['dg_phone_mandatory'] == 'checked'){
						$mandatory = 'required';
					}
					$cnt .= '<input type="text" class="'.$mandatory.' dg_phone_input" name="dg_phone" id="dg_phone" />';
					$cnt .= '<br/>';
				}
				if(@$dg_form_elem['dg_country_visible'] == 'checked'){
					$cnt .= '<label for="dg_country" class="dg_country">'.__("Country",'dukagate').'</label>';
					$mandatory = '';
					if(@$dg_form_elem['dg_country_mandatory'] == 'checked'){
						$mandatory = 'required';
					}
					$dg_country_code_name = $dg_dukagate_settings['country'];
					$cnt .= '<select name="dg_country_input" id="dg_country_input" style="width: 240px;" class="'.$mandatory.' dg_country_input">';
					foreach ($dg_country_code_name as $country_code => $country_name) {
						$cnt .= '<option value="' . $country_code . '" >' . __($country_name) . '</option>';
					}
					$cnt .= '<br/>';
				}
				if(@$dg_form_elem['dg_state_visible'] == 'checked'){
					$cnt .= '<label for="dg_state" class="dg_state">'.__("State",'dukagate').'</label>';
					$mandatory = '';
					if(@$dg_form_elem['dg_state_mandatory'] == 'checked'){
						$mandatory = 'required';
					}
					$cnt .= '<input type="text" class="'.$mandatory.' dg_state_input" name="dg_state" id="dg_state" />';
					$cnt .= '<br/>';
				}
				if (is_array($dg_form_elem) && count($dg_form_elem) > 0) {
					$total = 20;
					
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
								$input_type = '<input type="checkbox" class="'.$form_class.' '.$dg_form_elem[$total]['uname'].'_input" name="'.$dg_form_elem[$total]['uname'].'" id="'.$dg_form_elem[$total]['uname'].'" value="'.$dg_form_elem[$total]['initial'].'"/>'.$dg_form_elem[$total]['initial'];
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
			$dg_gateways = get_option('dukagate_gateway_options');
			$active = 0;
			$gw_name = '';
			if (is_array($dg_gateways) && count($dg_gateways) > 0) {
				if(count($dg_gateways) > 1){
					$cnt .= '<ul>';
					foreach ($dg_gateways as $dg_gateway) {
						$gw_name = $this->dg_get_gateway_name($dg_gateway);
						$cnt .= '<li>';
						$cnt .= '<label for="dg_gateway" class="dg_gateway '.$dg_gateway.'"><input type="radio" class="required dg_gateway_select '.$dg_gateway.'" name="dg_gateway_action" value="'.$dg_gateway.'"/>'.$gw_name.'</label>';
						$cnt .= '</li>';
						$active += 1;
					}
					$cnt .= '</ul>';
				}else{
					foreach ($dg_gateways as $dg_gateway) {
						$gw_name = $this->dg_get_gateway_name($dg_gateway);
						$cnt .= '<label for="dg_gateway" class="dg_gateway '.$dg_gateway.'"><input type="hidden" class="dg_gateway_select '.$dg_gateway.'" name="dg_gateway_action" value="'.$dg_gateway.'"/>'.$gw_name.'</label>';
						$active += 1;
					}
				}
			}
			if($active > 0){
				$cnt .= '<p class="dg_process">';
				$cnt .= '<input type="submit" name="dg_process_payment_form" id="dg_process_payment_form" value="'.__("Process Payment",'dukagate').'" />';
				$cnt .= '</p>';
			}
			$cnt .= '<input type="hidden" name="ajax" value="true" />';
			$cnt .= '<input type="hidden" name="action" value="dg_process_cart" />';
			
			return $cnt;
		}

		/**
		 * Add a custom user role
		 *
		 */
		function customer_role_type(){
			add_role('shopuser', __('Shop User','dukagate'), array(
				'read' => true,
			));
		}
		
		/**
		 * Save user to database
		 */
		function save_user($user_name, $email, $fname, $lname){
			$user_id = username_exists( $user_name );
			$dg_shop_settings = get_option('dukagate_shop_settings');
			if ( !$user_id and email_exists($email) == false ) {
				global $dukagate_mail;
				$random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
				$userdata = array(
					'user_login'    =>  $user_name,
					'user_pass'  => $random_password,
					'user_email' => $email,
					'first_name' => $fname,
					'last_name' => $lname,
					'role' => 'shopuser'
				);
				$user_id = wp_insert_user( $userdata ) ;

				$user = new WP_User( $user_id );
				

				//Send mail
				$mail = $dukagate_mail->get_mail('new_user');
				$shop = $dg_shop_settings['shopname'];
				$to =  $mail->to_mail;
				$subject = $mail->title;
				$message = $mail->content_admin;
				$array1 = array('%fname%','%lname%', '%fullnames%');
				$array2 = array($user->user_firstname,$user->user_lastname,$user->user_firstname.' '.$user->user_lastname);
				$message = str_replace($array1, $array2, $message);
				$dukagate_mail->send_mail($to, $subject, $message); //notify admin

				$message = $mail->content_user;
				$array1 = array('%fname%','%lname%', '%fullnames%','%username%','%password%','%shop%');
				$array2 = array($user->user_firstname,$user->user_lastname,$user->user_firstname.' '.$user->user_lastname, $user_name, $random_password, $shop);
				$dukagate_mail->send_mail($email, $subject, $message); //notify user

			} else {
				$random_password = __('User already exists.  Password inherited.');
			}
		}
		
		/**
		 * Get produt image
		 */
		function product_image($productid){
			$main_image = '';
			$main_images = wp_get_attachment_image_src( get_post_thumbnail_id( $productid ), 'single-post-thumbnail' );
			if(is_array($main_images))
				$main_image =  $main_images[0];
			if (empty($main_image)){
				$attachment_images = '';
				$attachment_images = &get_children('post_type=attachment&post_status=inherit&post_mime_type=image&post_parent=' . $productid);
                foreach ($attachment_images as $image) {
                    $main_image = $image->guid;
                    break;
                }
			}
			return $main_image;
		}
		
		/**
		 * Resize image
		 */
		function resize_image($attach_id = null, $img_url = null, $width, $height, $crop = true){
			$org_img = @getimagesize($img_url);
			if($org_img){
				if(empty($width)){
					$width = $org_img[0];
				}
				if(empty($height)){
					$height = $org_img[1];
				}
				if($attach_id){
					// this is an attachment, so we have the ID
					$image_src = wp_get_attachment_image_src($attach_id, 'full');
					$file_path = get_attached_file($attach_id);
				} elseif($img_url){
					// this is not an attachment, let's use the image url
					$file_path = parse_url($img_url);
					$file_path = $_SERVER['DOCUMENT_ROOT'].$file_path['path'];
					// Look for Multisite Path
					if(file_exists($file_path) === false){
						global $blog_id;
						$file_path = parse_url($img_url);
						if(preg_match('/files/', $file_path['path'])){
							$path = explode('/', $file_path['path']);
							foreach($path as $k => $v){
								if($v == 'files'){
									$path[$k-1] = 'wp-content/blogs.dir/'.$blog_id;
								}
							}
							$path = implode('/', $path);
						}
						$file_path = $_SERVER['DOCUMENT_ROOT'].$path;
					}
					//$file_path = ltrim( $file_path['path'], '/' );
					//$file_path = rtrim( ABSPATH, '/' ).$file_path['path'];
					$orig_size = getimagesize($file_path);
					$image_src[0] = $img_url;
					$image_src[1] = $orig_size[0];
					$image_src[2] = $orig_size[1];
				}
				$file_info = pathinfo($file_path);
				// check if file exists
				$base_file = $file_info['dirname'].'/'.$file_info['filename'].'.'.$file_info['extension'];
				if(!file_exists($base_file))
				return;
				$extension = '.'. $file_info['extension'];
				// the image path without the extension
				$no_ext_path = $file_info['dirname'].'/'.$file_info['filename'];
				$cropped_img_path = $no_ext_path.'-'.$width.'x'.$height.$extension;
				
				//remove old files older than 2 days to keep things fresh incase an image of the same name is changed
				if(file_exists($cropped_img_path))
					if(time() - @filemtime(utf8_decode($cropped_img_path)) >= 2*24*60*60){
						unlink($cropped_img_path);
					}
				// checking if the file size is larger than the target size
				// if it is smaller or the same size, stop right here and return
				if($image_src[1] > $width){
					// the file is larger, check if the resized version already exists (for $crop = true but will also work for $crop = false if the sizes match)
					if(file_exists($cropped_img_path)){
						$cropped_img_url = str_replace(basename($image_src[0]), basename($cropped_img_path), $image_src[0]);
						$dp_image = array(
							'url'   => $cropped_img_url,
							'width' => $width,
							'height'    => $height
						);
						return $dp_image['url'];
					}
					// $crop = false or no height set
					if($crop == false OR !$height){
						// calculate the size proportionaly
						$proportional_size = wp_constrain_dimensions($image_src[1], $image_src[2], $width, $height);
						$resized_img_path = $no_ext_path.'-'.$proportional_size[0].'x'.$proportional_size[1].$extension;
						// checking if the file already exists
						if(file_exists($resized_img_path)){
							$resized_img_url = str_replace(basename($image_src[0]), basename($resized_img_path), $image_src[0]);
							$dp_image = array(
								'url'   => $resized_img_url,
								'width' => $proportional_size[0],
								'height'    => $proportional_size[1]
							);
							return $dp_image['url'];
						}
					}
					// check if image width is smaller than set width
					$img_size = getimagesize($file_path);
					if($img_size[0] <= $width) $width = $img_size[0];
						// Check if GD Library installed
						if(!function_exists('imagecreatetruecolor')){
							echo 'GD Library Error: imagecreatetruecolor does not exist - please contact your webhost and ask them to install the GD library';
							return;
						}
						// no cache files - let's finally resize it
						$new_img_path = image_resize($file_path, $width.'px', $height.'px', $crop);
						$new_img_size = getimagesize($new_img_path);
						$new_img = str_replace(basename($image_src[0]), basename($new_img_path), $image_src[0]);
						// resized output
						$dp_image = array(
							'url'   => $new_img,
							'width' => $new_img_size[0],
							'height'    => $new_img_size[1]
						);
						return $dp_image['url'];
				}
				// default output - without resizing
				$dp_image = array(
					'url'   => $image_src[0],
					'width' => $width,
					'height'    => $height
				);
				return $dp_image['url'];
			}else{
				return $img_url;
			}
		}
		
		/**
		 * Get all sales days
		 */
		private function sales_days(){
			$databases = self::db_names();
			global $wpdb;
			$table_name = $databases['transactions'];
			$sql = "SELECT Date(`date`) as day FROM `$table_name` GROUP BY Date(`date`) ORDER BY Date(`date`) ASC LIMIT 10";
			return $wpdb->get_results($sql);
			
		}
		
		/**
		 * Show sales summmary on home page for the days
		 */
		function sales_summary($payment_status){
			$summary = array();
			$databases = self::db_names();
			$dates = $this->sales_days();
			global $wpdb;
			$table_name = $databases['transactions'];
			foreach ($dates as $date) {
				$summary['days'][] = $date->day;
				$sql = "SELECT SUM(`total`) as total FROM `$table_name` WHERE `payment_status` = '$payment_status' AND  Date(`date`) = '$date->day'";
				$results =  $wpdb->get_results($sql);
				if(!empty($results)){
					foreach ($results as $result) {
						if($result->total != null && !(empty($result->total))){
							$summary['total'][] = $result->total;
						}else{
							$summary['total'][] = "0";
						}
					}
				}else{
					$summary['total'][] = "0";
				}
			}
			return $summary;
		}
		
		/**
		 * Get total revenue
		 */
		function total_revenue(){
			$databases = self::db_names();
			global $wpdb;
			$table_name = $databases['transactions'];
			$sql = "SELECT SUM(`total`) as total FROM `$table_name` WHERE `payment_status` = 'Paid'";
			return $wpdb->get_var($sql);
		}
		
		/**
		 * Count total sales
		 */
		function total_sales(){
			$databases = self::db_names();
			global $wpdb;
			$table_name = $databases['transactions'];
			$sql = "SELECT count(`id`) as total FROM `$table_name` WHERE `payment_status` = 'Paid'";
			return $wpdb->get_var($sql);
		}
	}
}

/**
 * Load plugin function during the WordPress init action
 *
 * @since 3.6.2
 *
 * @return void
 */
function dukagate_action_init() {
	global $dukagate;
	$dukagate = new DukaGate();
}
add_action( 'init', 'dukagate_action_init', 0 ); // load before widgets_init at 1
?>