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

/**
 * Minimal stub of WooCommerce's WC_Settings_Page abstract class, close enough
 * to the real implementation so HMFW_Settings can be instantiated and its
 * get_settings() output can be tested in isolation.
 */
if ( ! class_exists( 'WC_Settings_Page' ) ) {
	class WC_Settings_Page {
		public $id    = '';
		public $label = '';

		public function __construct() {
			add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
			add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
			add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
		}

		public function add_settings_page( $pages ) {
			$pages[ $this->id ] = $this->label;
			return $pages;
		}

		public function get_settings( $current_section = '' ) {
			return array();
		}

		public function output() {
			WC_Admin_Settings::output_fields( $this->get_settings() );
		}

		public function save() {
			WC_Admin_Settings::save_fields( $this->get_settings() );
		}
	}
}

/**
 * Stub of WooCommerce's WC_Admin_Settings helper, only providing the two
 * static methods used by WC_Settings_Page::output()/save().
 */
if ( ! class_exists( 'WC_Admin_Settings' ) ) {
	class WC_Admin_Settings {
		public static function output_fields( $settings ) {}
		public static function save_fields( $settings ) {}
	}
}

