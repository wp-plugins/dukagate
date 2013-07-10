<?php
/**
 * DukaGate Cart Widget
 */
add_action('widgets_init', create_function('', 'return register_widget("DukaGate_Cart_Widget");'));
if(!class_exists('DukaGate_Cart_Widget')) {
	class DukaGate_Cart_Widget extends WP_Widget {
		
		function DukaGate_Cart_Widget(){
			$widget_ops = array( 'classname' => 'dukagate_cart_widget', 'description' => __('DukaGate Cart Widget') ); // Widget Settings
			$control_ops = array( 'id_base' => 'dukagate_cart_widget' ); // Widget Control Settings
			$this->WP_Widget( 'dukagate_cart_widget', __('DukaGate Cart'), $widget_ops, $control_ops ); //
		}
		
		
		function widget($args, $instance) {
			extract($args);
			$title = empty( $instance['title'] ) ? '' : __($instance['title']);
			echo $before_widget;
			echo $before_title.$title.$after_title;
			//Show cart
			dg_cart_min($echo = 'true');
			echo $after_widget;
		}
		
		/**
		 * Update widget
		 */
		function update($new_instance, $old_instance) {
			$instance = $old_instance;
			$instance['title'] = strip_tags( $new_instance['title'] );
			return $instance;
		}
		
		function form($instance) {
			$title 	= apply_filters('widget_title', @$instance['title']); // Widget Title
			?>
			<p>
				<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Widget Title:');?></label>
				<input type="text" value="<?php echo $title; ?>" name="<?php echo $this->get_field_name('title'); ?>" id="<?php echo $this->get_field_id('title'); ?>" class="widefat" />
			</p>
			<?php
		}
	}
}
?>