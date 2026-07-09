<?php
/**
 * PHPUnit bootstrap file for holiday-mode-woocommerce.
 *
 * @package IPHolidayModeWooCommerce
 */

// Define ABSPATH for WordPress compatibility.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

// Pretend WordPress core is loaded so the plugin bootstrap file does not die().
if ( ! defined( 'WPINC' ) ) {
	define( 'WPINC', 'wp-includes' );
}

// Load Composer autoloader.
if ( file_exists( dirname( __DIR__ ) . '/vendor/autoload.php' ) ) {
	include_once dirname( __DIR__ ) . '/vendor/autoload.php';
}

require_once __DIR__ . '/wordpress-mock.php';

// Load the plugin's main file so all hmfw_* functions become available for testing.
require_once dirname( __DIR__ ) . '/holiday-mode-woocommerce.php';

// Stub for the WooCommerce main class so hmfw_isWooCommerceNotAvailable()
// can be tested for the "WooCommerce is active" branch as well.
if ( ! class_exists( 'WooCommerce' ) ) {
	class WooCommerce {}
}

