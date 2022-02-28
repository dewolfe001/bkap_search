<?php
/*
Plugin Name: Booking Search System Shortcode
Plugin URI:  https://web321.co/booking-search-shortcode
Description: BKAP search system for Divi. The Tyche Software Booking System for WordPress does not work in Divi. This shortcode generator gets around this issue.
Version:     1.0.1
Author:      Shawn DeWolfe (dewolfe001)
Donate:      https://www.paypal.com/paypalme/web321co/20/
Author URI:  https://shawndewolfe.com
License:     GPL2License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

defined( 'ABSPATH' ) or die( 'No script help, please!' );

define('BSSD_PLUGIN_MAIN', __FILE__);
define('BSSD_PLUGIN_PATH', plugin_dir_path(__FILE__));

/*
 * bssd override
 */

add_shortcode('bssd_dates', 'bssd_shortcodes');

function bssd_shortcodes($atts = array()) {
    $atts = shortcode_atts( array(
		'exclude' => '',
		'sizing' => 'shop_single'
    ), $atts, 'bssd' );

	// grab the dates
	$checkin = str_replace('-', '', $_GET['w_checkin']);
	$checkout = str_replace('-', '', $_GET['w_checkout']);
	if ($atts['exclude'] != '') {
		$excluded_posts = explode(',', $atts['exclude']);		
	}
	else {
		$excluded_posts = array();		
	}

	// get the SQL exclusions
	if (!$checkin) {
		$checkin = '20220101';	
	}
	if (!$checkout) {
		$checkout = date('Y').'1231';	
	}

	$meta_query = array(
		'relation' => 'AND',
		array(
			'key'     => '_bkap_start',
			'value'   => intval($checkin.'000000'),
			'compare' => '>=',
			'type'    => 'NUMERIC',
		),
		array(
			'key'     => '_bkap_end',
			'value'   => intval($checkout.'000000'),
			'compare' => '<=',
			'type'    => 'NUMERIC',
		),
	);	

	$exclusion_query = new WP_Query( array(
		'post_type'              => array( 'bkap_booking' ),
		'post_status'            => array( 'paid', ' confirmed', 'pending-confirmation' ),
		'meta_query' => $meta_query
	));

	// Check that we have query results.
	if ( $exclusion_query->have_posts() ) {
		// Start looping over the query results.
		while ( $exclusion_query->have_posts() ) {
			$exclusion_query->the_post();
			$excluded_posts[] = get_post_meta( get_the_ID(), "_bkap_product_id", true);			
		}
	}
	wp_reset_postdata();

	$meta_query = array(
		'relation' => 'AND',
		array(
			'key'     => '_bkap_enable_booking',
			'value'   => 'on'
		)
	);	

	// The Main Query
	$results_query = new WP_Query( array(
		'post_type'              => array( 'product' ),
		'post_status'            => array( 'publish' ),
		'post__not_in' => $excluded_posts,
		'meta_query' => $meta_query
	));

	// return the display
	if ( $results_query->have_posts() ) {
		// Start looping over the query results.
		while ( $results_query->have_posts() ) {
			// $results_query
			$results_query->the_post();
			$found_posts[] =  get_the_ID(); 			
		}
	}
	wp_reset_postdata();
	$args = array(
		'post_type'      => 'product',
		'posts_per_page' => 10,
		'post__in'		 => $found_posts,
	);

	$output_query = new WP_Query( $args );

	if ( $output_query->have_posts() ) {
		$output .= '<ul class="bssd_list">';
		while ( $output_query->have_posts() ) : $output_query->the_post();
			global $product;
			$output .= '<li><a href="'.get_permalink().'"><span class="bssd_img">' . woocommerce_get_product_thumbnail($atts['sizing']).'</span> <span class="bssd_title">'.get_the_title().'</span></a></li>';
		endwhile;
		$output .= "</ul>";
	}
	wp_reset_postdata();

	return $output;
}