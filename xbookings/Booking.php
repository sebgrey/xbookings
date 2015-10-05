<?php
class XBooking {
	
	function __construct(){
		add_action('init',array($this,'request'));
	}
	
	function request(){
		global $xbooking_errors;
		
		$nonce = $_REQUEST['_wpnonce'];
		if(!wp_verify_nonce($nonce,'booking-form')){
			return false;
		}
		extract($_POST, EXTR_SKIP);
		// Server side validation
		if(empty($date)){
			$xbooking_errors->add('invalid_date', 'Please select a date');
		}
		if(empty($time)){
			$xbooking_errors->add('invalid_time', 'Please select a time');
		}
		if(empty($groupsize)){
			$xbooking_errors->add('invalid_groupsize', 'Please enter the size of your group');
		}
		if(empty($fullname)){
			$xbooking_errors->add('invalid_fullname', 'Please enter your name');
		}
		if(empty($phone)){
			$xbooking_errors->add('invalid_phone', 'Please enter a contact number');
		}
		if(empty($email)){
			$xbooking_errors->add('invalid_email', 'Please enter a valid email address');
		}
		// Check if errors exist
		if($xbooking_errors->errors){
			return $xbooking_errors;
		} else {
			$booking = compact( array('date','time','groupsize','fullname','phone','email','status','active','created') );
			// Save booking in DB
			$this->add($booking);
			function success_message() {
				return "<p class='successmessage'>Your request has been submitted. We will be in touch shortly to confirm.</p>";	
			}
			add_action("the_content", "success_message");
			$this->success_notification($booking);
		}		 
	}
	/** Check booking availability
	 * 
	* @author Seb Grey
	* @version 1.0
	* @since 4 Aug 2014
	*
	* @param string $date
	* @return number
	 */
	function check_availability($date){
		
		$string_date = strtotime($date);
		// First minute of this day
		$after = date("Y-m-d H:i:s",$string_date);
		// First minute of next day
		$before = date("Y-m-d H:i:s",strtotime('+1 day',$string_date));
			
		$args = array(
			'active'	=> '1',
			'date_query'	=> array(
					'after'		=> $after,
					'before'	=> $before,
			),
		);
		// Count the number of bookings found
		$availability = count($this->get($args));
		
		return $availability;
	}
	
	function add($booking = array()){
		global $wpdb;
	
		// export array as variables
		extract($booking, EXTR_SKIP);
	
		// Are we updating or creating?
		$booking_id = 0;
		$update = false;
		if ( ! empty( $id ) ) {
			$update = true;
	
			// Get the subscription ID
			$booking_id = $id;
		}
	
		
		// If the post date is empty (due to having been new or a draft) and status is not 'draft' or 'pending', set date to now
		if ( empty($created) || '0000-00-00 00:00:00' == $created )
			$created = current_time('mysql');
	
		if ( $update || '0000-00-00 00:00:00' == $created ) {
			$modified = current_time( 'mysql' );
		} else {
			$modified = $created;
		}

		$date = strtotime($date);
		$date = date("Y-m-d", $date);
		$time = strtotime($time);
		$time = date("H:i:s", $time);
		$status = 'pending';
		
		// Package back up into array
		$data = compact( array( 'date','time','groupsize','fullname','phone','email','status','active', 'created') );
	
		// expected_slashed (everything!)
		$data = wp_unslash( $data );
		$where = array( 'id' => $booking_id );
	
		if ( $update ) {
			if ( false === $wpdb->update( XBooking_BOOKING_TABLE, $data, $where ) ) {
				if ( $wp_error )
					return new WP_Error('db_update_error', __('Could not update booking in the database'), $wpdb->last_error);
				else
					return 0;
			}
		} else {
			if ( false === $wpdb->insert( XBooking_BOOKING_TABLE, $data ) ) {
				if ( $wp_error )
					return new WP_Error('db_insert_error', __('Could not insert booking into the database'), $wpdb->last_error);
				else
					return 0;
			}
			$booking_id = (int) $wpdb->insert_id;
		}
		return $booking_id;
	}
	
	public static function get( $args = array(), $output = OBJECT ){
		global $wpdb;
	
		$query = wp_parse_args($args);
		$query_length = count($query);
		$i = 1;
		$table = XBooking_BOOKING_TABLE;
	
		$where = ($query_length > 0)? 'WHERE ' : '';
		foreach($query as $column => $value){
			if($column == 'date_query'){
				$query_args = array(
						array(
								'column' => 'date',
								'before'    => $value['before'],
								'inclusive' => true
						),
						array(
								'column' => 'date',
								'after'    => $value['after'],
								'inclusive' => true
						)
				);
				$date_query = new WP_Date_Query( $query_args, 'date' );
				$where .= $date_query->get_sql();
				// Reduce counter as get_sql produces 'AND' for us
				$i--;
			} else {
				$where .= "{$column} = '{$value}' ";
			}
			$i++;
		}
	
		$select = "SELECT * FROM {$table} {$where} ORDER BY date DESC";
		
		// If unique value queried only get single row
		if(isset($query['id'])){
			$results = $wpdb->get_row( $select, $output );
		} else {
			$results = $wpdb->get_results( $select, $output );
		}
	
		return (!empty($results)) ? $results : false;
	}
	
	public static function unique_months($output = 'OBJECT'){
		global $wpdb;
		
		$table = XBooking_BOOKING_TABLE;
		
		$results = $wpdb->get_results("SELECT DATE_FORMAT(date,'%m') as month,DATE_FORMAT(date,'%Y') as year,count(*) FROM {$table} GROUP BY DATE_FORMAT(date,'%m/%Y')",$output);
		
		return $results;		
	}
	
	public static function update($booking){
		global $wpdb;
		
		if (!isset($booking['id']))
			return false;
		
		$wpdb->update(XBooking_BOOKING_TABLE, $booking, array('id'=>$booking['id']));
	}
	
	public static function success_notification($booking){
		function set_html_content_type() {
			return "text/html";
		}
		add_filter( 'wp_mail_content_type', 'set_html_content_type' );
		
		$message = sprintf(__('<p>A new booking request has been submitted.</p>'));
		$message .= sprintf(__('<p>Date: %s</p>'), $booking['date']);
		$message .= sprintf(__('<p>Time: %s</p>'), $booking['time']);
		$message .= sprintf(__('<p>Group size: %s</p>'), $booking['groupsize']);
		$message .= sprintf(__('<p>Name: %s</p>'), $booking['fullname']);
		$message .= sprintf(__('<p>Contact number: %s</p>'), $booking['phone']);
		$message .= sprintf(__('<p>Email: %s</p>'), $booking['email']);

		$to = get_bloginfo('admin_email');
		$subject = 'New Booking Request';
		$headers = 'Reply-To: '. $booking['email'];
		
		wp_mail( $to, $subject, $message, $headers, $attachments );
		
		remove_filter( 'wp_mail_content_type', 'set_html_content_type' );
	}
	
	public static function email_update($booking, $action) {
		if ($action=='confirm') {
			function set_html_content_type() {
				return "text/html";
			}
			add_filter( 'wp_mail_content_type', 'set_html_content_type' );
			
			$message = sprintf(__('<p>Your booking has been confirmed.</p>'));
			$message .= sprintf(__('<p>Date: %s</p>'), $booking['date']);
			$message .= sprintf(__('<p>Time: %s</p>'), $booking['time']);
			$message .= sprintf(__('<p>Group size: %s</p>'), $booking['groupsize']);
			$message .= sprintf(__('<p>Name: %s</p>'), $booking['fullname']);
			$message .= sprintf(__('<p>Contact number: %s</p>'), $booking['phone']);
			$message .= sprintf(__('<p>Email: %s</p>'), $booking['email']);
			
			$to = $booking['email'];
			$subject = 'Booking Confirmation';
			$headers = 'Reply-To: '.get_bloginfo('admin_email');
			
			wp_mail( $to, $subject, $message, $headers, $attachments );
			
			remove_filter( 'wp_mail_content_type', 'set_html_content_type' );
		}
		if ($action=='deny') {
			function set_html_content_type() {
				return "text/html";
			}
			add_filter( 'wp_mail_content_type', 'set_html_content_type' );
				
			$message = sprintf(__('<p>Your booking could not be confirmed.</p>'));
			$message .= sprintf(__('<p>Date: %s</p>'), $booking['date']);
			$message .= sprintf(__('<p>Time: %s</p>'), $booking['time']);
			$message .= sprintf(__('<p>Group size: %s</p>'), $booking['groupsize']);
			$message .= sprintf(__('<p>Name: %s</p>'), $booking['fullname']);
			$message .= sprintf(__('<p>Contact number: %s</p>'), $booking['phone']);
			$message .= sprintf(__('<p>Email: %s</p>'), $booking['email']);
				
			$to = $booking['email'];
			$subject = 'Your booking could not be confirmed';
			$headers = 'Reply-To: '.get_bloginfo('admin_email');
				
			wp_mail( $to, $subject, $message, $headers, $attachments );
				
			remove_filter( 'wp_mail_content_type', 'set_html_content_type' );
		}
		if ($action=='delete') {
			function set_html_content_type() {
				return "text/html";
			}
			add_filter( 'wp_mail_content_type', 'set_html_content_type' );
				
			$message = sprintf(__('<p>Your booking has been cancelled.</p>'));
			$message .= sprintf(__('<p>Date: %s</p>'), $booking['date']);
			$message .= sprintf(__('<p>Time: %s</p>'), $booking['time']);
			$message .= sprintf(__('<p>Group size: %s</p>'), $booking['groupsize']);
			$message .= sprintf(__('<p>Name: %s</p>'), $booking['fullname']);
			$message .= sprintf(__('<p>Contact number: %s</p>'), $booking['phone']);
			$message .= sprintf(__('<p>Email: %s</p>'), $booking['email']);
				
			$to = $booking['email'];
			$subject = 'Booking Cancelled';
			$headers = 'Reply-To: '.get_bloginfo('admin_email');
				
			wp_mail( $to, $subject, $message, $headers, $attachments );
				
			remove_filter( 'wp_mail_content_type', 'set_html_content_type' );
		}
	}
	
	public static function block_check($thedate) {
		$blocked = explode(' ', get_option('tm_block_dates') );
		
		if ( in_array($thedate, $blocked) ) {
			return false;
		} else {
			return true;
		}
	}
}