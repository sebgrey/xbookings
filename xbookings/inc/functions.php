<?php

/**
* Shortcode to output booking form
* @author Seb Grey
* @version 1.0
* @since 9 Dec 2014
 */

function bookingform_shortcode( $atts, $content = null ) {
	load_template(XBooking_PLUGIN_DIR.'/bookingform.php');
}
add_shortcode( 'bookingform', 'bookingform_shortcode' );


/** Register additional valid wpdb columns
 * 
* @author Seb Grey
* @version 1.0
* @since 4 Aug 2014
*
* @param array $valid_columns
* @return array 
 */
function iverna_date_query_valid_columns($valid_columns){
	$valid_columns[] = 'date';

	return $valid_columns;
}
add_filter('date_query_valid_columns','iverna_date_query_valid_columns');

/*
 * Enqueue scripts and styles for booking form
*/

function form_scripts(){

	$js_dir = plugins_url('xbookings').'/js';
	wp_register_script('jquery-timepicker',$js_dir.'/jquery.timepicker.min.js',false);
	$deps = array('jquery', 'jquery-ui-datepicker', 'jquery-timepicker');
	wp_register_script('form-script', $js_dir.'/form.js', $deps);
	wp_enqueue_script('form-script');
	
	$css_dir = plugins_url('xbookings').'/css';
	wp_register_style('form-style', $css_dir.'/form.css');
	wp_enqueue_style('form-style');
	
	wp_localize_script( 'form-script', 'wp_ajax', array(
		'url' => admin_url( 'admin-ajax.php' ),
	));
}

add_action('wp_enqueue_scripts', 'form_scripts');