<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://heinrichfranz.de
 * @since      1.0.0
 *
 * @package    ImpressivePages
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove options used by the WooCommerce settings page.
$hmfw_options = array(
	'hmfw_holiday_status',
	'hmfw_holiday_startdate',
	'hmfw_holiday_enddate',
	'hmfw_holiday_use_custom_message',
	'hmfw_holiday_message',
	'hmfw_version',
);

foreach ( $hmfw_options as $hmfw_option ) {
	delete_option( $hmfw_option );
}

// Remove legacy Customizer theme mods in case the migration never ran
// (e.g. plugin was removed before it ever executed on 'init').
$hmfw_theme_mods = array(
	'hmfw_holiday-status',
	'hmfw_holiday-startdate',
	'hmfw_holiday-enddate',
	'hmfw_holiday-useCustomMessage',
	'hmfw_holiday-message',
);

foreach ( $hmfw_theme_mods as $hmfw_theme_mod ) {
	remove_theme_mod( $hmfw_theme_mod );
}

