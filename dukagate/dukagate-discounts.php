<?php

/**
 * Dukapress discounts class manager
 */
if(!class_exists('Dukagate_Discounts')) {
	class Dukagate_Discounts{
		
		function set_up(){
			global $wpdb;
			$this->create_table();
		}
		
		/**
		 * Get Database name
		 */
		private function get_db_name(){
			global $wpdb;
			return $wpdb->prefix."dkgt_discounts";
		}
		
		/**
		 * Check if database table exists and create a new one
		 */
		private function create_table(){
			global $wpdb;
			$disc_table_name = $this->get_db_name();
			if($wpdb->get_var("show tables like '$disc_table_name'") != $disc_table_name) {
				$sql = "CREATE TABLE `$disc_table_name` (
						`id` int(11) NOT NULL AUTO_INCREMENT,
						`code` varchar(50) NOT NULL,
						`type` varchar(50) NOT NULL,
						`amount` varchar(50) NOT NULL,
						`valid` int(1) DEFAULT '0',
						`timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
						PRIMARY KEY (`id`),
						UNIQUE KEY `code` (`code`)
						)";
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql);
			}
		}
		
		/**
		 * Save discount
		 */
		public function save_discount($request = array()){
			if(!empty($request)){
				global $wpdb;
				if(empty($request['disc_amount']) && empty($request['disc_type'])){
					echo dukagate_disc_message('Both Amount and Type required', 'error');
				}else{
					$disc_table_name = $this->get_db_name();
					if(empty($request['disc_code']))
						$discount_code = $this->generate_code();
					else
						$discount_code = $request['disc_code'];
					$type = $request['disc_type'];
					$amount = $request['disc_amount'];
					
					$query = "INSERT INTO `{$disc_table_name}` (`code`,`type`,`amount`) VALUES('{$discount_code}','{$type}','{$amount}')";
					
					$wpdb->query($query);
					if($wpdb->insert_id > 0){
						echo dukagate_disc_message('New Discount code '.$discount_code.' generaetd');
					}else{
						echo dukagate_disc_message('Error saving discount. Please try again', 'error');
					}
				}
			}
		}
		
		/**
		 * Update Discount
		 */
		public function update_discount($request = array()){
			if(!empty($request)){
				global $wpdb;
				
				if(empty($request['disc_amount']) && empty($request['disc_type'])){
					echo dukagate_disc_message('Both Amount and Type required', 'error');
				}else{
					$disc_table_name = $this->get_db_name();
					$discount_code = $request['disc_code'];
					$type = $request['disc_type'];
					$amount = $request['disc_amount'];
					$id = $this->is_param_set($request['disc_id']);

					$query = "UPDATE `{$disc_table_name}`  SET `code` =  '{$discount_code}', `type` = '{$type}',`amount` = '{$amount}' WHERE id = {$id}";
					
					$wpdb->query($query);
					echo dukagate_disc_message('Updated Discount code '.$discount_code);
				}
			}
		}
		
		/**
		 * Get discount by Id and code
		 */
		public function get_discount($id, $code = ''){
			global $wpdb;
			$disc_table_name = $this->get_db_name();
			if(!empty($id)){
				$query = 'SELECT * from '.$disc_table_name.' WHERE `id` ='.$id;
			}
			if(!empty($code)){
				$query = 'SELECT * from '.$disc_table_name.' WHERE `code` ='.$code;
			}
			return $wpdb->get_row($query);
			
		}
		
		
		public function get_discount_quantity_set($quantity){
			global $wpdb;
			$disc_table_name = $this->get_db_name();
			$query = 'SELECT * from '.$disc_table_name.' WHERE `goods_discount` = '.intval($quantity).' and `valid` = 0 LIMIT 1';
			$discount = $wpdb->get_row($query);
			$discount_assign = false;
			if(empty($discount)){
				$query = 'SELECT * from '.$disc_table_name.' WHERE  '.intval($quantity).' >= `goods_discount` and `valid` = 0  LIMIT 1';
				$discount = $wpdb->get_row($query);
			}
			if(!empty($discount)){
				if(intval($quantity) >= $discount->goods_discount){
					$usage = intval($discount->usage) + 1;
					$sql = "UPDATE `{$disc_table_name}` set `usage` = {$usage}  WHERE `id` = '{$discount->id}'";
					$wpdb->query($sql);
					$discount_assign = $this->get_discount($discount->id);
				}
			}
			return $discount_assign;
		}
		
		/**
		 * Get valid discount by Id and code
		 */
		public function get_valid_discount($id, $code = ''){
			global $wpdb;
			$disc_table_name = $this->get_db_name();
			if(!empty($id)){
				$query = 'SELECT * from '.$disc_table_name.' WHERE `valid` = 0 and `id` ='.$id.' LIMIT 1';
			}
			if(!empty($code)){
				$query = 'SELECT * from '.$disc_table_name.' WHERE `valid` = 0 and `code` = "'.$code.'" LIMIT 1';
			}
			return $wpdb->get_row($query);
			
		}

		/**
		 * Get all discounts
		 */
		public function list_discounts(){
			global $wpdb;
			$disc_table_name = $this->get_db_name();
			$sql = 'SELECT `id`, `valid`, `code`, `amount`, `timestamp`, `type` from '.$disc_table_name.' ORDER BY `timestamp` DESC;';
			$results = $wpdb->get_results($sql);
			
			return $results;
		}
		
		/**
		 * Delete Discount
		 */
		public function delete_discount($id, $code=''){
			global $wpdb;
			$disc_table_name = $this->get_db_name();
			if(!empty($id)){
				$query = 'DELETE from '.$disc_table_name.' WHERE `id` ='.$id;
			}
			if(!empty($code)){
				$query = 'DELETE from '.$disc_table_name.' WHERE `code` ='.$code;
			}
			$wpdb->query($query);
		}
		
		
		/**
		 * Verify Discount code when doing purchase
		 */
		public function verify_code($code){
			global $wpdb;
			$disc_table_name = $this->get_db_name();
			$discount = $this->get_valid_discount('', $code);
			$discount_assign = false;
			if(!empty($discount)){
				$usage = intval($discount->usage) + 1;
				$sql = "UPDATE `{$disc_table_name}` set  `valid` = 1 WHERE `code` = '{$code}'";
				$wpdb->query($sql);
				$discount_assign = $this->get_discount($discount->id);
			}			
			return $discount_assign;
		}
		
		
		
		/**
		 * Check if parameter is set
		 * Done to save data in DB
		 */
		function is_param_set($param, $number = false){
			if(empty($param)){
				if($number){
					return 0;
				}else{
					return __('Not Set','dukagate');
				}
			}else{
				if($number){
					return intval($param);
				}else{
					return $param;
				}
			}
		}
		
		
		/**
		 * Generate Discount code
		 */
		public function generate_code($length = 10){		
			$disc_code= "";

			srand((double)microtime()*1000000);

			$data = "ABCDE123IJKLMN67QRSTUVWXYZ";
			$data .= "0FGH45OP89";

			for($i = 0; $i < $length; $i++){
				$disc_code .= substr($data, (rand()%(strlen($data))), 1);
			}

			return $disc_code;
		}
		
		
		
		/**
		 * Discount types available
		 */
		public static function discount_types(){
			$types = array(
							'1'=>'Percentage',
							'2'=>'Fixed Amount');
			return $types;
		}
		
		/**
		 * Get the discount type
		 */
		public static function get_type($id){
			$types = self::discount_types();
			return $types[$id];
		}
		
		
	}
}

global $dukagate_disc;
if(!isset($dukagate_disc)){
	$dukagate_disc = new Dukagate_Discounts();
	$dukagate_disc->set_up();
}


function dukagate_disc_message($message, $type='updated'){
	$content = '<div id="message" class="'.$type.'">';
	$content .= '<p>';
	$content .= $message;
	$content .= '</p>';
	$content .= '</div>';
	
	return $content;
}
?>