<?php
/**
 * DukaGate Mini Cart Widget
 */
add_action('widgets_init', create_function('', 'return register_widget("DukaGate_Mini_Cart_Widget");'));
if(!class_exists('DukaGate_Mini_Cart_Widget')) {
	class DukaGate_Mini_Cart_Widget extends WP_Widget {
		
		function DukaGate_Mini_Cart_Widget(){
			$widget_ops = array( 'classname' => 'dukagate_mini_cart_widget', 'description' => __('DukaGate Mini Cart Widget only shows the total number in the cart and a checkout link') ); // Widget Settings
			$control_ops = array( 'id_base' => 'dukagate_mini_cart_widget' ); // Widget Control Settings
			$this->WP_Widget( 'dukagate_mini_cart_widget', __('DukaGate Mini Cart'), $widget_ops, $control_ops ); //
		}
		
		
		function widget($args, $instance) {
			extract($args);
			$title = empty( $instance['title'] ) ? '' : __($instance['title']);
			$image = empty( $instance['image'] ) ? DG_DUKAGATE_URL.'/images/dg_icon.png' : __($instance['image']);
			echo $before_widget;
			//Show cart
			dg_mini_cart('true',$image);
			echo $after_widget;
		}
		
		/**
		 * Update widget
		 */
		function update($new_instance, $old_instance) {
			$instance = $old_instance;
			$instance['title'] = strip_tags( $new_instance['title'] );
			$instance['image'] = strip_tags( $new_instance['image'] );
			return $instance;
		}
		
		function form($instance) {
			$image 	= apply_filters('widget_title', @$instance['image']); // Widget Title
			?>
			<p>
				<label for="<?php echo $this->get_field_id('image'); ?>"><?php _e('Cart Image:');?></label>
				<input type="text" value="<?php echo $title; ?>" name="<?php echo $this->get_field_name('image'); ?>" id="<?php echo $this->get_field_id('image'); ?>" class="widefat" />
			</p>
			<?php
		}
	}
}
?>