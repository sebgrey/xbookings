<?php

/*
Plugin Name: Bookings Module
Description: Booking management functionality.
Author: Seb Grey
Version: 1.0
*/


if (!defined('ABSPATH')) {
	die();
}

/**
 * Abort plugin loading if WordPress is upgrading
 */
if (defined('WP_INSTALLING') && WP_INSTALLING)
	return;

define('XBooking_PLUGIN_FILE', 'xbookings/xbookings.php');
define('XBooking_PLUGIN_DIR', dirname(__FILE__));
define('XBooking_PLUGIN_JS_DIR', dirname(__FILE__) . '/js');
define('XBooking_PLUGIN_CSS_DIR', dirname(__FILE__) . '/css');
define('XBooking_PLUGIN_INC_DIR', dirname(__FILE__) . '/inc');
define('XBooking_PLUGIN_TABLE_DIR', dirname(__FILE__) . '/tables');

define('XBooking_BOOKING_TABLE', 'wp_xbookings');

require_once (XBooking_PLUGIN_INC_DIR . '/functions.php');
require_once (XBooking_PLUGIN_INC_DIR . '/ajax.php');

require_once (XBooking_PLUGIN_TABLE_DIR . '/BookingsListTable.php');

global $xbooking_errors;
$xbooking_errors = new WP_Error();

require_once (XBooking_PLUGIN_DIR . '/Admin.php');
$XBookingAdmin = new XBookingAdmin();
require_once (XBooking_PLUGIN_DIR . '/Booking.php');
$XBooking = new XBooking();

function XBooking_plugin_install(){
	global $wpdb;

	$queries[] = "
	CREATE TABLE ".XBooking_BOOKING_TABLE." (
		`id`  			bigint(20) NOT NULL AUTO_INCREMENT,
		`date`  		date NULL,
		`time`  		time NULL,
		`groupsize`  	int(4) NULL,
		`fullname`  	text(50) NOT NULL ,
		`phone`			text(50) NULL ,
		`email`  		text(50) NULL,
		`status` 		text(50) NULL,
		`active`  		tinyint(1) NULL DEFAULT '1',
		`created`  		datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`id`));";
	

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	foreach($queries as $query){
		dbDelta( $query );
	}
}
register_activation_hook( __FILE__, 'XBooking_plugin_install' );