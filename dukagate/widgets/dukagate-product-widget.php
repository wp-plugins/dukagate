<?php

/**
 * DukaGate Product Widget
 */
add_action('widgets_init', create_function('', 'return register_widget("DukaGate_Product_Widget");'));
 
if(!class_exists('DukaGate_Product_Widget')) {
	class DukaGate_Product_Widget extends WP_Widget {
		
		
		function DukaGate_Product_Widget() {
			$widget_ops = array( 'classname' => 'dukagate_product_widget', 'description' => __('DukaGate Product Widget', "dg-lang") ); // Widget Settings
			$control_ops = array( 'id_base' => 'dukagate_product_widget' ); // Widget Control Settings
			$this->WP_Widget( 'dukagate_product_widget', __('DukaGate Product', "dg-lang"), $widget_ops, $control_ops ); //
			
			global $pagenow;
			if (defined("WP_ADMIN") && WP_ADMIN) {
				add_action( 'admin_init', array( $this, 'fix_async_upload_image' ) );
				if ( 'widgets.php' == $pagenow ) {
					wp_enqueue_style( 'thickbox' );
					wp_enqueue_script( $control_ops['id_base'], DG_DUKAGATE_URL.'/js/dukagate_widget.js',array('thickbox'), false, true );
					add_action( 'admin_head-widgets.php', array( $this, 'admin_head' ) );
				} elseif ( 'media-upload.php' == $pagenow || 'async-upload.php' == $pagenow ) {
					add_filter( 'image_send_to_editor', array( $this,'image_send_to_editor'), 1, 8 );
					add_filter( 'gettext', array( $this, 'replace_text_in_thickbox' ), 1, 3 );
					add_filter( 'media_upload_tabs', array( $this, 'media_upload_tabs' ) );
				}
			}
		}
		
		
		function fix_async_upload_image() {
			if(isset($_REQUEST['attachment_id'])) {
				$GLOBALS['post'] = get_post($_REQUEST['attachment_id']);
			}
		}
		
		
		/**
		 * Retrieve resized image URL
		 *
		 * @param int $id Post ID or Attachment ID
		 * @param int $width desired width of image (optional)
		 * @param int $height desired height of image (optional)
		 * @return string URL
		 * @author Shane & Peter, Inc. (Peter Chester)
		 */
		function get_image_url( $id, $width=false, $height=false ) {
			
			/**/
			// Get attachment and resize but return attachment path (needs to return url)
			$attachment = wp_get_attachment_metadata( $id );
			$attachment_url = wp_get_attachment_url( $id );
			if (isset($attachment_url)) {
				if ($width && $height) {
					$uploads = wp_upload_dir();
					$imgpath = $uploads['basedir'].'/'.$attachment['file'];
					error_log($imgpath);
					$image = image_resize( $imgpath, $width, $height );
					if ( $image && !is_wp_error( $image ) ) {
						error_log( is_wp_error($image) );
						$image = path_join( dirname($attachment_url), basename($image) );
					} else {
						$image = $attachment_url;
					}
				} else {
					$image = $attachment_url;
				}
				if (isset($image)) {
					return $image;
				}
			}
		}
		
		
		/**
		 * Test context to see if the uploader is being used for the image widget or for other regular uploads
		 *
		 * @return void
		 * @author Shane & Peter, Inc. (Peter Chester)
		 */
		function is_sp_widget_context() {
			if ( isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'],$this->id_base) !== false ) {
				return true;
			} elseif ( isset($_REQUEST['_wp_http_referer']) && strpos($_REQUEST['_wp_http_referer'],$this->id_base) !== false ) {
				return true;
			} elseif ( isset($_REQUEST['widget_id']) && strpos($_REQUEST['widget_id'],$this->id_base) !== false ) {
				return true;
			}
			return false;
		}
		
		
		/**
		 * Somewhat hacky way of replacing "Insert into Post" with "Insert into Widget"
		 *
		 * @param string $translated_text text that has already been translated (normally passed straight through)
		 * @param string $source_text text as it is in the code
		 * @param string $domain domain of the text
		 * @return void
		 * @author Shane & Peter, Inc. (Peter Chester)
		 */
		function replace_text_in_thickbox($translated_text, $source_text, $domain) {
			if ( $this->is_sp_widget_context() ) {
				if ('Insert into Post' == $source_text) {
					return __('Insert Into Widget');
				}
			}
			return $translated_text;
		}
		
		
		/**
		 * Filter image_end_to_editor results
		 *
		 * @param string $html 
		 * @param int $id 
		 * @param string $alt 
		 * @param string $title 
		 * @param string $align 
		 * @param string $url 
		 * @param array $size 
		 * @return string javascript array of attachment url and id or just the url
		 * @author Shane & Peter, Inc. (Peter Chester)
		 */
		function image_send_to_editor( $html, $id, $caption, $title, $align, $url, $size, $alt = '' ) {
			// Normally, media uploader return an HTML string (in this case, typically a complete image tag surrounded by a caption).
			// Don't change that; instead, send custom javascript variables back to opener.
			// Check that this is for the widget. Shouldn't hurt anything if it runs, but let's do it needlessly.
			if ( $this->is_sp_widget_context() ) {
				if ($alt=='') $alt = $title;
				?>
				<script type="text/javascript">
					// send image variables back to opener
					var win = window.dialogArguments || opener || parent || top;
					win.IW_html = '<?php echo addslashes($html); ?>';
					win.IW_img_id = '<?php echo $id; ?>';
					win.IW_alt = '<?php echo addslashes($alt); ?>';
					win.IW_caption = '<?php echo addslashes($caption); ?>';
					win.IW_title = '<?php echo addslashes($title); ?>';
					win.IW_align = '<?php echo $align; ?>';
					win.IW_url = '<?php echo $url; ?>';
					win.IW_size = '<?php echo $size; ?>';
				</script>
				<?php
			}
			return $html;
		}
		
		/**
		 * Admin header css
		 *
		 * @return void
		 * @author Shane & Peter, Inc. (Peter Chester)
		 */
		function admin_head() {
			?>
			<style type="text/css">
				.aligncenter {
					display: block;
					margin-left: auto;
					margin-right: auto;
				}
				
				#TB_iframeContent 
			</style>
			<?php
		}

		/**
		 * Remove from url tab until that functionality is added to widgets.
		 *
		 * @param array $tabs 
		 * @return void
		 * @author Shane & Peter, Inc. (Peter Chester)
		 */
		function media_upload_tabs($tabs) {
			if ( $this->is_sp_widget_context() ) {
				unset($tabs['type_url']);
			}
			return $tabs;
		}
		
	
	
		
		function widget($args, $instance) {
			extract($args);
			$title = empty( $instance['title'] ) ? '' : __($instance['title']);
			echo $before_widget;
			echo $before_title.$title.$after_title;
			//Show product
			DukaGate_Products::widget_product($instance);
			echo $after_widget;
		}
		
		
		/**
		 * Update widget
		 */
		function update($new_instance, $old_instance) {
			$instance = $old_instance;
			$instance['title'] = strip_tags( $new_instance['title'] );
			$instance['dg_product_name'] = strip_tags( $new_instance['dg_product_name'] );
			$instance['dg_product_sku'] = strip_tags( $new_instance['dg_product_sku'] );
			$instance['dg_product_price'] = strip_tags( $new_instance['dg_product_price'] );
			$instance['dg_product_discount'] = strip_tags( $new_instance['dg_product_discount'] );
			$instance['dg_product_discount_type'] = strip_tags( $new_instance['dg_product_discount_type'] );
			$instance['link'] = $new_instance['link'];
			$instance['dg_product_image_align'] = strip_tags( $new_instance['dg_product_image_align'] );
			$instance['dg_product_image'] = strip_tags( $new_instance['dg_product_image'] );
			$instance['dg_product_image_imageurl'] = $this->get_image_url($new_instance['dg_product_image'],$new_instance['dg_product_image_width'],$new_instance['dg_product_image_height']);  // image resizing not working right now
			if( $_SERVER["HTTPS"] == "on" ) {
				$instance['dg_product_image_imageurl'] = str_replace('http://', 'https://', $instance['dg_product_image_imageurl']);
			}
			$instance['dg_product_image_alt'] = $new_instance['dg_product_image_alt'];
			$instance['dg_product_image_width'] = strip_tags( $new_instance['dg_product_image_width'] );
			$instance['dg_product_image_height'] = strip_tags( $new_instance['dg_product_image_height'] );
			$instance['dg_product_image_background'] = strip_tags( $new_instance['dg_product_image_background'] );
			$instance['dg_product_id'] = $this->id;
			return $instance;
		}
		
		function form($instance) {
			$title 	= apply_filters('widget_title', @$instance['title']); // Widget Title
			$dg_product_sku = @$instance['dg_product_sku']; // sku
			$dg_product_name = @$instance['dg_product_name']; // the product name
			$dg_product_price = @$instance['dg_product_price']; //Price
			$dg_product_discount = @$instance['dg_product_discount']; //Discount
			$dg_product_discount_type = @$instance['dg_product_discount_type']; //Discount Type
			$dg_product_image = @$instance['dg_product_image']; // URL of image
			$dg_product_image_width = @$instance['dg_product_image_width']; // Image width
			$dg_product_image_height = @$instance['dg_product_image_height']; // Image height
			$dg_product_image_background = @$instance['dg_product_image_background']; // Use as background image
			$dg_product_image_alt = @$instance['dg_product_image_alt']; // Image Alt Text
			$dg_product_image_imageurl = @$instance['dg_product_image_imageurl']; // Image Alt Text
			$dg_product_image_align = @$instance['dg_product_image_align']; // Image Align
			$dg_product_id = $this->id; // Product Id
			
			?>	
				<p>
					<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Widget Title:');?></label>
					<input type="text" value="<?php echo $title; ?>" name="<?php echo $this->get_field_name('title'); ?>" id="<?php echo $this->get_field_id('title'); ?>" class="widefat" />
				</p>
				<p>
					<label for="<?php echo $this->get_field_id('dg_product_name'); ?>"><?php _e('Product Name:');?></label>
					<input type="text" value="<?php echo $dg_product_name; ?>" name="<?php echo $this->get_field_name('dg_product_name'); ?>" id="<?php echo $this->get_field_id('dg_product_name'); ?>" class="widefat" />
				</p>
				<p>
					<label for="<?php echo $this->get_field_id('dg_product_sku'); ?>"><?php _e('SKU:');?></label>
					<input type="text" value="<?php echo $dg_product_sku; ?>" name="<?php echo $this->get_field_name('dg_product_sku'); ?>" id="<?php echo $this->get_field_id('dg_product_sku'); ?>" class="widefat" />
				</p>
				<p>
					<label for="<?php echo $this->get_field_id('dg_product_price'); ?>"><?php _e('Price:');?></label>
					<input type="text" value="<?php echo $dg_product_price; ?>" name="<?php echo $this->get_field_name('dg_product_price'); ?>" id="<?php echo $this->get_field_id('dg_product_price'); ?>" class="widefat" />
				</p>
				<p>
					<label for="<?php echo $this->get_field_id('dg_product_discount'); ?>"><?php _e('Discount:');?></label>
					<input type="text" value="<?php echo $dg_product_discount; ?>" name="<?php echo $this->get_field_name('dg_product_discount'); ?>" id="<?php echo $this->get_field_id('dg_product_discount'); ?>" class="widefat" />
				</p>
				<p>
					<label for="<?php echo $this->get_field_id('dg_product_discount_type'); ?>"><?php _e('Discount Type:');?></label>
					<select name="<?php echo $this->get_field_name('dg_product_discount_type'); ?>" id="<?php echo $this->get_field_id('dg_product_discount_type'); ?>" class="widefat">
						<option value="percentage"<?php selected( $dg_product_discount_type, 'percentage' ); ?>><?php _e('Percentage', "dg-lang"); ?></option>
						<option value="fixed"<?php selected( $dg_product_discount_type, 'fixed' ); ?>><?php _e('Fixed', "dg-lang"); ?></option>
					</select>
				</p>
				<p>
					<label for="<?php echo $this->get_field_id('dg_product_image'); ?>"><?php _e('Image:');?></label>
					<?php
						$media_upload_iframe_src = "media-upload.php?type=image&widget_id=".$this->id; //NOTE #1: the widget id is added here to allow uploader to only return array if this is used with image widget so that all other uploads are not harmed.
						$image_upload_iframe_src = apply_filters('image_upload_iframe_src', "$media_upload_iframe_src");
						$image_title = __(($instance['dg_product_image'] ? 'Change Image' : 'Add Image'), $this->pluginDomain);
					?><br />
					<a href="<?php echo $image_upload_iframe_src; ?>&TB_iframe=true" id="add_image-<?php echo $this->get_field_id('dg_product_image'); ?>" class="thickbox-image-widget" title='<?php echo $image_title; ?>' onClick="set_active_widget('<?php echo $this->id; ?>');return false;" style="text-decoration:none"><img src='images/media-button-image.gif' alt='<?php echo $image_title; ?>' align="absmiddle" /> <?php echo $image_title; ?></a>
					<div id="display-<?php echo $this->get_field_id('dg_product_image'); ?>"><?php 
					if ($dg_product_image_imageurl) {
						echo "<img src=\"{$dg_product_image_imageurl}\" alt=\"{$dg_product_image_alt}\" style=\"";
							if ($instance['dg_product_image_width'] && is_numeric($dg_product_image_width)) {
								echo "max-width: {$dg_product_image_width}px;";
							}
							if ($instance['height'] && is_numeric($dg_product_image_height)) {
								echo "max-height: {$dg_product_image_height}px;";
							}
							echo "\"";
							if (!empty($dg_product_image_align) && $dg_product_image_align != 'none') {
								echo " class=\"align{$dg_product_image_align}\"";
							}
							echo " />";
					}
					?></div>
					<br clear="all" />
					<input id="<?php echo $this->get_field_id('dg_product_image'); ?>" name="<?php echo $this->get_field_name('dg_product_image'); ?>" type="hidden" value="<?php echo $instance['dg_product_image']; ?>" />
				</p>
				<p>
					<label for="<?php echo $this->get_field_id('dg_product_image_width'); ?>"><?php _e('Image Width:');?></label>
					<input type="text" value="<?php echo $dg_product_image_width; ?>" name="<?php echo $this->get_field_name('dg_product_image_width'); ?>" id="<?php echo $this->get_field_id('dg_product_image_width'); ?>" class="widefat" onchange="changeImgWidth('<?php echo $this->id; ?>')" />
				</p>
				<p>
					<label for="<?php echo $this->get_field_id('dg_product_image_height'); ?>"><?php _e('Image Height:');?></label>
					<input type="text" value="<?php echo $dg_product_image_height; ?>" name="<?php echo $this->get_field_name('dg_product_image_height'); ?>" id="<?php echo $this->get_field_id('dg_product_image_height'); ?>" class="widefat" onchange="changeImgHeight('<?php echo $this->id; ?>')" />
				</p>
				<p>
					<label for="<?php echo $this->get_field_id('dg_product_image_align'); ?>"><?php _e('Image Align:');?></label>
					<select onchange="changeImgAlign('<?php echo $this->id; ?>')" name="<?php echo $this->get_field_name('dg_product_image_align'); ?>" id="<?php echo $this->get_field_id('dg_product_image_align'); ?>" class="widefat">
						<option value="none"<?php selected( $dg_product_image_align, 'none' ); ?>><?php _e('none', "dg-lang"); ?></option>
						<option value="left"<?php selected( $dg_product_image_align, 'left' ); ?>><?php _e('left', "dg-lang"); ?></option>
						<option value="center"<?php selected( $dg_product_image_align, 'center' ); ?>><?php _e('center', "dg-lang"); ?></option>
						<option value="right"<?php selected( $dg_product_image_align, 'right' ); ?>><?php _e('right', "dg-lang"); ?></option>
					</select>
				</p>
				<p>
					<label for="<?php echo $this->get_field_id('dg_product_image_alt'); ?>"><?php _e('Image Alt:');?></label>
					<input type="text" value="<?php echo $dg_product_image_alt; ?>" name="<?php echo $this->get_field_name('dg_product_image_alt'); ?>" id="<?php echo $this->get_field_id('dg_product_image_alt'); ?>" class="widefat" />
				</p>
				<p>
					<label for="<?php echo $this->get_field_id('dg_product_image_background'); ?>"><?php _e('Use as background image:');?></label>
					<input type="checkbox" value="checked" name="<?php echo $this->get_field_name('dg_product_image_background'); ?>" <?php echo ($dg_product_image_background == 'checked') ? "checked='checked'": ""; ?>/>
				</p>
			<?php
		}
	}
}
?>