<?php

// Disable blocked dates in the datepicker

function get_blocked_dates(){
	$blocked = explode(' ', get_option('tm_block_dates'));
	
	$return = array(
		'blocked' => $blocked
	);
	
	wp_send_json_success($return);
}

add_action( 'wp_ajax_get_blocked_dates', 'get_blocked_dates' );
add_action( 'wp_ajax_nopriv_get_blocked_dates', 'get_blocked_dates' );

// Get opening times for the selected day

function get_opening_times(){
	
	$date = strtotime($_POST['chosendate']);
	$day = date('w',$date);
	
	switch($day) {
		case '0':
			$option = "open_sun";
		break;
		case '1':
			$option = "open_mon";
		break;
		case '2':
			$option = "open_tue";
		break;
		case '3':
			$option = "open_wed";
		break;
		case '4':
			$option = "open_thu";
		break;
		case '5':
			$option = "open_fri";
		break;
		case '6':
			$option = "open_sat";
		break;
	}
	
	$openhrs = get_option($option);
	$open = substr($openhrs,0,5);
	$close = date("Y-m-d", $date) . ' ' . substr($openhrs,8,5);
	$close = strtotime($close);
	$close = strtotime("-30 minutes",$close);
	$close = date("H:i", $close);
	
	$return = array(
		'open' => $open,
		'close' => $close
	);	
	
	wp_send_json_success($return);
}
add_action( 'wp_ajax_get_opening_times', 'get_opening_times' );
add_action( 'wp_ajax_nopriv_get_opening_times', 'get_opening_times' );