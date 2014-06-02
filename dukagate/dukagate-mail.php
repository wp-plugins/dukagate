<?php

/**
 * Duka Gate Mail handler
 * Handles all Dukagate mail functions
 */
class DukaGate_Mail{
	
	/** 
	 * Mail Types accepted in the system
	 */
	static function mail_types(){
		return array('payment_received'=> __('Payment Received', "dukagate"), 
					'order_placed'=> __('Order Placed', "dukagate"), 
					'order_canceled'=> __('Order Canceled', "dukagate"),
					'new_user'=> __('New User', "dukagate"));
	}
	
	/**
	 * List mails
	 */
	function list_mails(){
		global $wpdb;
		$databases = DukaGate::db_names();
		$table_name = $databases['mail'];
		$sql = "SELECT * FROM `$table_name`";
		return $wpdb->get_results($sql);
	}
	
	/**
	 * Get mail
	 *@param type
	 */
	function get_mail($type){
		global $wpdb;
		$databases = DukaGate::db_names();
		$table_name = $databases['mail'];
		$sql = "SELECT * FROM `$table_name` WHERE `type` = '$type'";
		return $wpdb->get_row($sql);
	}
	
	
	function update_mail($type, $to, $subject, $admin, $user){
		$mail_types = self::mail_types();
		global $wpdb;
		$databases = DukaGate::db_names();
		$table_name = $databases['mail'];
		$sql = "UPDATE `$table_name` SET `title` = '$subject', `content_admin` = '$admin', `content_user` = '$user', `to_mail` = '$to' WHERE `type` = '$type'";
		$wpdb->query($sql);
		return '<div class="updated">'.__('Updated', "dg-lang").' '.$mail_types[$type].'</div>';
	}

	
	/**
	 * Send Mail
	 * @param to - Email to send to
	 * @param subject - Mail subject
	 * @param message - Mail content (Plain text or html)
	 */
	function send_mail($to, $subject, $message, $attachments = ''){
		$site_name = get_bloginfo('name');
		$defualt_email = get_option('admin_email');
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		$headers .= "From: $site_name <$defualt_email>" . "\r\n";
		add_filter('wp_mail_content_type',create_function('', 'return "text/html";'));
		@wp_mail($to, $subject, $message, $headers, $attachments);
	}
}


global $dukagate_mail;
if(!isset($dukagate_mail)){
	$dukagate_mail = new DukaGate_Mail();
}
?>