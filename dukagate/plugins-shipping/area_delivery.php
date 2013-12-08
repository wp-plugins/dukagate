<?php
/**
 * DukaGate Shipping
 * Simple Shipping Plugin
 */
 
class DukaGate_Shipping_Area_Delivery extends DukaGate_Shipping_API{
	//private shipping method name. Lowercase alpha (a-z) and dashes (-) only please!
	var $plugin_name;
	
	//public name of your method, for lists and such.
	var $plugin_slug;
	
	 //set to true if you need to use the shipping_metabox() method to add per-product shipping options
	var $use_metabox = false;
	
	//set to true to show before payment form
	var $before_payment = true;
	
	var $shipping_info = '';
	
	/**
	 * Runs when your class is instantiated. Use to setup your plugin instead of __construct()
	 */
	function on_create() {
		$this->plugin_name = __('Area Delivery', 'dg-lang');
		$this->plugin_slug = __('area_delivery', 'dg-lang');
		
		if (@$_REQUEST['action'] === 'dg_save_shipping_rate') {
			$this->dg_save_shipping_settings();
		}
		if (@$_REQUEST['action'] === 'dg_del_area_delivery') {
			$this->delete_rate();
		}
		if (@$_REQUEST['action'] === 'dg_sel_area_delivery') {
			$this->select_rate_id();
		}

	}
	
	//Register Plugin
	function register(){
		dg_register_shipping_plugin('DukaGate_Shipping_Area_Delivery', $this->plugin_name, $this->plugin_slug, $this->shipping_info, true);
	}

	
	 /**
	 * Echo anything you want to add to the top of the shipping screen
	 */
	function shipping_form() {
		global $dukagate;
		$options = DukaGate::json_to_array($dukagate->dg_get_shipping_info($this->plugin_slug));
		$cnt = '';
		$cnt .= '<h2>'.__('Shipping using', 'dg-lang').' : '.$this->plugin_name.'</h2>';
		$cnt .= '<table width="100%" class="shipiing_rates">';
		$cnt .= '<thead>';
		$cnt .= '<tr>';
		$cnt .= '<th scope="row" align="left"><strong>'.__('Area', 'dg-lang').'</strong></th>';
		$cnt .= '<th scope="row" align="left"><strong>'.__('Rate', 'dg-lang').'</strong></th>';
		$cnt .= '<th scope="row"></th>';
		$cnt .= '</tr>';
		$cnt .= '</thead>';
		$cnt .= '<tbody>';
		if(is_array($options)){
			foreach ($options as $option => $o) {
				$cnt .= '<tr>';
				$cnt .= '<td>'.$o['area'].'</td>';
				$cnt .= '<td>'.$o['rate'].'</td>';
				$cnt .= '<td><input type="radio" name="shipping_rate_value[]" id="'.$option.'" value="'.$o['rate'].'" onclick="area_delivery_select(\''.$option.'\')" /></td>';
				$cnt .= '</tr>';
			}
		}
		$cnt .= '</tbody>';
		$cnt .= '</table>';
		$cnt .= '<script type="text/javascript">';
		$cnt .= 'function area_delivery_select(id){';
		$cnt .= 'var elem = jQuery("#"+id);';
		$cnt .= 'jQuery.ajax({';
		$cnt .= 'type: "POST",';
		$cnt .= 'url: dg_js.ajaxurl,';
		$cnt .= 'data: {"action" : "dg_sel_area_delivery", "id" : id},';
		$cnt .= 'success: function(response){}';
		$cnt .= '});';
		$cnt .= 'return false;';
		$cnt .= '}';
		$cnt .= '</script>';
		return $cnt;
	}
	
	
	function set_up_options($plugin_slug){
		global $dukagate;
		if(@$_POST[$plugin_slug]){
			$enabled = ($_POST[$plugin_slug.'_enable'] == 'checked')  ? 1 : 0;
			$dukagate->dg_save_shipping_active($this->plugin_slug, $enabled);
		}
		$options = DukaGate::json_to_array($dukagate->dg_get_shipping_info($plugin_slug));
		$enabled = $dukagate->dg_get_enabled_shipping_status($plugin_slug);
		?>
		<table class="form-table">
			<tr>
				<th scope="row"><?php _e('Areas and Rates', 'dg-lang') ?></th>
				<td>
					<p>
						<input type="hidden" id="area_id" name="area_id" value="" />
						<label><?php _e('Area', 'dg-lang') ?><br />
						  <input value="" name="<?php echo $plugin_slug; ?>_area"  id="<?php echo $plugin_slug; ?>_area" type="text" />
						</label><br/>
						<label><?php _e('Rate', 'dg-lang') ?><br />
						  <input value="" size="30" name="<?php echo $plugin_slug; ?>_rate" id="<?php echo $plugin_slug; ?>_rate" type="text" />
						</label><br/>
						<button onclick="area_delivery.save('<?php echo $plugin_slug; ?>_area', '<?php echo $plugin_slug; ?>_rate', '<?php echo $plugin_slug; ?>_div');"><?php _e('Save', 'dg-lang') ?></button>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e('Areas', 'dg-lang') ?></th>
				<td>
					<table width="100%" border="0" class="widefat">
						<thead>
							<tr>
								<th scope="row"><strong><?php _e('Area', 'dg-lang') ?></strong></th>
								<th scope="row"><strong><?php _e('Rate', 'dg-lang') ?></strong></th>
								<th scope="row">&nbsp;</th>
							</tr>
						</thead>
						<tfoot>
							<tr>
								<th scope="row"><strong><?php _e('Area', 'dg-lang') ?></strong></th>
								<th scope="row"><strong><?php _e('Rate', 'dg-lang') ?></strong></th>
								<th scope="row">&nbsp;</th>
							</tr>
						</tfoot>
						<tbody id="<?php echo $plugin_slug; ?>_div">
							<?php 
							if(is_array($options)){
							foreach ($options as $option => $o) {
							?>
							<tr id="del_<?php echo $option; ?>">
								<td id="area_<?php echo $option; ?>"><?php echo $o['area']; ?></td>
								<td id="rate_<?php echo $option; ?>"><?php echo $o['rate']; ?></td>
								<td><a href="javascript:;" onclick="area_delivery.edit('<?php echo $option; ?>', '<?php echo $plugin_slug; ?>')">Edit</a>&nbsp;&nbsp;<a href="javascript:;" onclick="area_delivery.del('<?php echo $option; ?>')">Delete</a></td>
							</tr>
							<?php }
							}?>
						</tbody>
					</table>
				</td>
			</tr>
			<tr>
				<form method="POST" action="">
					<th scope="row"><?php _e('Enable', 'dg-lang') ?></th>
					<td>
						<p>
							<label><?php _e('Select To enable or disable', 'dg-lang') ?><br />
							  <input value="checked" name="<?php echo $plugin_slug; ?>_enable" type="checkbox" <?php echo (intval($enabled) == 1) ? "checked='checked'": ""; ?> />
							</label>
						</p>
						<p>
							<input type="submit" name="<?php echo $plugin_slug; ?>" value="<?php _e('Save Settings', 'dg-lang'); ?>" />
						</p>
					</td>
				</form>
			</tr>
		</table>
		<script type="text/javascript" src="<?php echo DG_SHIPPING_URL; ?>/area_delivery/area_delivery.js"></script>
		<?php
	}
		
	//Save Shipping rates
	function dg_save_shipping_settings(){
		global $dukagate;
		$area = $_REQUEST['area'];
		$rate = $_REQUEST['rate'];
		$id = @$_REQUEST['id'];
		$options = DukaGate::json_to_array($dukagate->dg_get_shipping_info($this->plugin_slug));
		$total = count($options) + 1;
		if(empty($id)){
			$options[$total]['area'] = $area;
			$options[$total]['rate'] = $rate;
		}else{
			$options[$id]['area'] = $area;
			$options[$id]['rate'] = $rate;
			$total = $id;
		}
		$dukagate->dg_save_shipping_info_only($this->plugin_slug, DukaGate::array_to_json($options));
		$html = '<tr id="del_'.$total.'">
					<td>'.$area.'</td>
					<td>'.$rate.'</td>
					<td><a href="javascript:;" onclick="area_delivery.edit(\''.$total.'\', \''.$this->plugin_slug.'\')">Edit</a>&nbsp;&nbsp;<a href="javascript:;" onclick="area_delivery.del(\''.$total.'\')">Delete</a></td>
				</tr>';
		//$html = str_replace(Array("\n", "\r"), Array("\\n", "\\r"), addslashes($html));
		header('Content-type: application/json; charset=utf-8');
		echo DukaGate::array_to_json(array('success' => 'true', 'html' => $html));
		exit();
	}
	
	//Delete
	function delete_rate(){
		global $dukagate;
		$id = $_REQUEST['id'];
		$options = DukaGate::json_to_array($dukagate->dg_get_shipping_info($this->plugin_slug));
		unset($options[$id]);
		$dukagate->dg_save_shipping_info_only($this->plugin_slug, DukaGate::array_to_json($options));
		header('Content-type: application/json; charset=utf-8');
		echo DukaGate::array_to_json(array('success' => 'true', 'response' => __('Deleted', 'dg-lang')));
		exit();
	}
	
	//Select rate ID from page
	function select_rate_id(){
		global $dukagate;
		$id = $_REQUEST['id'];
		$options = DukaGate::json_to_array($dukagate->dg_get_shipping_info($this->plugin_slug));
		$option = $options[$id];
		$opts = array();
		if(isset($_SESSION['delivery_options'])){
			$opts  = $_SESSION['delivery_options'];
		}
		if(isset($opts[$id])){
			unset($opts[$id]);
		}else{
			$opts[$id] = $option;
		}
		$_SESSION['delivery_options'] = $opts;
		header('Content-type: application/json; charset=utf-8');
		echo DukaGate::array_to_json(array('success' => 'true'));
		exit();
	}
	
	/**
	 * Echo a settings meta box with whatever settings you need for you shipping module.
	 *  Form field names should be prefixed with mp[shipping][plugin_name], like "mp[shipping][plugin_name][mysetting]".
	 *  You can access saved settings via $settings array.
	 */
	function shipping_settings_box($settings) {

	}
	
	
	function calculate_shipping() {
	
	}
}
?>