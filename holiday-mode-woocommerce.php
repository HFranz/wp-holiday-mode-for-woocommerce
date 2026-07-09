<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://impressive-pages.de/?source=wordpress
 * @since             1.0.0
 * @package           IPHolidayModeWooCommerce
 *
 * @wordpress-plugin
 * Plugin Name:       Holiday Mode for WooCommerce
 * Plugin URI: 	  	  https://wordpress.org/plugins/holiday-mode-for-woocommerce/
 * Description:       Set your WooCommerce shop to holiday/vacation mode. Use date range to schedule closed time.
 * Version:           1.7.1
 * Author:            Heinrich Franz
 * Author URI:        https://impressive-pages.de/?source=wordpress
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       holiday-mode-woocommerce
 * Domain Path: 	  /languages
 * Requires at least: 5.4
 * Requires PHP:      7.3
 */

/*
 * @copyright Heinrich Franz, 2021, All Rights Reserved
 * This code is released under the GPL licence version 2 or later, available here http://www.gnu.org/licenses/gpl-2.0.txt
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'IPHolidayModeWooCommerce_VERSION', '1.7.0' );

add_action( 'init', 'hmfw_load_textdomain' );
function hmfw_load_textdomain() {
    load_plugin_textdomain( 'holiday-mode-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); 
}

add_action ('init', 'hmfw_woocommerce_holiday_mode');
function hmfw_woocommerce_holiday_mode() {
	if (hmfw_isWooCommerceNotAvailable() || false == get_theme_mod( 'hmfw_holiday-status', 0)) {
		return;
	}
	
	if (!hmfw_check_in_range(get_theme_mod( 'hmfw_holiday-startdate'), get_theme_mod( 'hmfw_holiday-enddate'))) {
		return;
	}
	
	add_filter( 'woocommerce_is_purchasable', '__return_false');
	// Disable Cart, Checkout, Add Cart
   	remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20 );
   	remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
	
   	add_action( 'woocommerce_before_main_content', 'hmfw_wc_shop_disabled', 10 );
	if(is_product()) {
		add_action( 'woocommerce_before_single_product', 'hmfw_wc_shop_disabled', 10 );
	}
   	add_action( 'woocommerce_before_cart', 'hmfw_wc_shop_disabled', 10 );
   	add_action( 'woocommerce_before_checkout_form', 'hmfw_wc_shop_disabled', 10 );
}
 
// Show Holiday Notice
function hmfw_wc_shop_disabled() {
	$notice = get_theme_mod( 'hmfw_holiday-useCustomMessage', 0) == true ? get_theme_mod( 'hmfw_holiday-message') : get_option( 'woocommerce_demo_store_notice' );
	wc_print_notice($notice, 'error');
}

function hmfw_check_in_range($start_date, $end_date) {
	$timezone = get_option('timezone_string');
  	// Convert to timestamp
  	$start = strtotime($start_date);
  	$end = strtotime($end_date);
  	$check = strtotime('today midnight'. ' '. $timezone);

  	// Check that user date is between start & end
  	return (($start <= $check ) && ($check <= $end));
}

add_action( 'customize_register', 'hmfw_starter_customize_register');
function hmfw_starter_customize_register( $wp_customize ) 
{
	$wp_customize->add_section(
		'ip-holiday-settings', array(
			'title' => __( 'Holiday Mode Settings', 'holiday-mode-woocommerce' )
		)
	);
	
	if (hmfw_isWooCommerceNotAvailable()) {
		$wp_customize->add_setting(  'no-WooCommerce', array(
           'capability' => 'edit_theme_options',
           'type'       => 'hidden',
           'autoload'   => false
         ) );
		
		$wp_customize->add_control( 'no-WooCommerce', array(
			'label'   => __( 'WooCommerce required', 'holiday-mode-woocommerce' ),
			'description' => __( 'Your WordPress installation seems not to have WooCommerce plugin.<br><br>Please install and activate WooCommerce.', 'holiday-mode-woocommerce' ),
			'section' => 'ip-holiday-settings',
			'type'    => 'hidden',
         ) );
		
		return;
	}
	
	$wp_customize->add_setting( 'hmfw_holiday-status', array(
	  'capability' => 'edit_theme_options',
	  'default' => false,
	  'sanitize_callback' => 'hmfw_sanitize_checkbox',
	) );

	$wp_customize->add_control( 'hmfw_holiday-status', array(
	  'type' => 'checkbox',
	  'section' => 'ip-holiday-settings',
	  'label' => __( 'Activate', 'holiday-mode-woocommerce' ),
	  'description' => __( 'Activate Holiday Mode', 'holiday-mode-woocommerce' ),
	) );

	$wp_customize->add_setting( 'hmfw_holiday-startdate', array(
		  'capability' => 'edit_theme_options',
		  'sanitize_callback' => '',
		) );
	
	$wp_customize->add_setting( 'hmfw_holiday-enddate', array(
		'capability' => 'edit_theme_options',
		'sanitize_callback' => '',
	) );

	$wp_customize->add_control( 'hmfw_holiday-startdate', array(
	  'type' => 'date',
	  'section' => 'ip-holiday-settings', // Add a default or your own section
	  'label' => __( 'Start of Holidays', 'holiday-mode-woocommerce' ),
	  'description' => __( 'Enter first day of Holidays here:', 'holiday-mode-woocommerce' ),
	  'input_attrs' => array(
		'placeholder' => __( 'mm/dd/yyyy' ),
	  ),
	) );
	
	$wp_customize->add_control( 'hmfw_holiday-enddate', array(
	  'type' => 'date',
	  'section' => 'ip-holiday-settings', // Add a default or your own section
	  'label' => __( 'End of Holidays', 'holiday-mode-woocommerce' ),
	  'description' => __( 'Enter last day of Holidays here:', 'holiday-mode-woocommerce' ),
	  'input_attrs' => array(
		'placeholder' => __( 'mm/dd/yyyy', 'holiday-mode-woocommerce' ),
	  ),
	) );
	
	$wp_customize->add_setting( 'hmfw_holiday-useCustomMessage', array(
	  'capability' => 'edit_theme_options',
	  'default' => false,
	  'sanitize_callback' => 'hmfw_sanitize_checkbox',
	) );

	$wp_customize->add_control( 'hmfw_holiday-useCustomMessage', array(
	  'type' => 'checkbox',
	  'section' => 'ip-holiday-settings',
	  'label' => __( 'Use own Holiday message', 'holiday-mode-woocommerce' ),
	  'description' => __( 'If activated own message can be entered otherwise Store notice from WooCommerce as Holiday message will be used for customers.', 'holiday-mode-woocommerce' ),
	) );
	
	$wp_customize->add_setting( 'hmfw_holiday-message', array(
	  'capability' => 'edit_theme_options',
	  'default' => __( 'I am on vacation.', 'holiday-mode-woocommerce' ),
	  'sanitize_callback' => 'wp_kses_post',
	) );

	$wp_customize->add_control( 'hmfw_holiday-message', array(
	  'type' => 'textarea',
	  'section' => 'ip-holiday-settings', // // Add a default or your own section
	  'label' => __( 'Vacation message', 'holiday-mode-woocommerce' ),
	  'description' => __( 'Enter your Holiday message here:', 'holiday-mode-woocommerce' ),
	  'active_callback' => 'hmfw_useCustomMessage_enabled',
	) );

}

function hmfw_sanitize_checkbox( $checked ) {
	return ( ( isset( $checked ) && true == $checked ) ? true : false );
}

function hmfw_useCustomMessage_enabled(){
    $useCustomMessage = get_theme_mod( 'hmfw_holiday-useCustomMessage');
    if( empty( $useCustomMessage ) ) {
        return false;
    }
    return true;
}

function hmfw_isWooCommerceNotAvailable(){
    return !class_exists( 'woocommerce' ) ? true : false;
}