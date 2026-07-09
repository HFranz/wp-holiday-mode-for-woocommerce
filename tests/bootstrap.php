<?php
/**
 * PHPUnit bootstrap file for wp-learnsuite-brevo-mailer.
 *
 * @package WpLearnsuiteBrevoMailer
 */

// Define ABSPATH for WordPress compatibility.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

// Load Composer autoloader.
if ( file_exists( dirname( __DIR__ ) . '/vendor/autoload.php' ) ) {
	include_once dirname( __DIR__ ) . '/vendor/autoload.php';
}

require_once __DIR__ . '/wordpress-mock.php';

// Load the plugin includes directly (without re-running the bootstrap header).
