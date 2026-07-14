<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://sevmatic/?source=wordpress
 * @since             1.0.0
 * @package           IPHolidayModeWooCommerce
 *
 * @wordpress-plugin
 * Plugin Name:       Holiday Mode for WooCommerce
 * Plugin URI:        https://wordpress.org/plugins/holiday-mode-for-woocommerce/
 * Description:       Set your WooCommerce® shop to holiday or vacation mode with ease.
 * Version:           2.4.0
 * Author:            Heinrich Franz
 * Author URI:        https://sevmatic/?source=wordpress
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       holiday-mode-woocommerce
 * Domain Path:       /languages
 * Requires at least: 6.7
 * Requires PHP:      8.0
 * Requires Plugins:  woocommerce
 * WC requires at least: 8.0
 * WC tested up to:   9.4
 */

/*
 * @copyright Heinrich Franz, 2021, All Rights Reserved
 * This code is released under the GPL licence version 2 or later, available here http://www.gnu.org/licenses/gpl-2.0.txt
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'HMFW_VERSION', '2.4.0' );

require_once plugin_dir_path( __FILE__ ) . 'includes/class-hmfw-cache-flusher.php';

add_action( 'init', 'hmfw_load_textdomain' );
/**
 * Load the plugin's translations.
 */
function hmfw_load_textdomain(): void {
	load_plugin_textdomain( 'holiday-mode-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

add_action( 'before_woocommerce_init', 'hmfw_declare_wc_compatibility' );
/**
 * Declare compatibility with WooCommerce High-Performance Order Storage (HPOS)
 * and the Cart/Checkout blocks, so the plugin keeps working with modern WooCommerce.
 */
function hmfw_declare_wc_compatibility(): void {
	if ( ! class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		return;
	}

	\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
}

add_filter( 'woocommerce_get_settings_pages', 'hmfw_add_settings_page' );
/**
 * Add the "Holiday Mode" settings page as a new tab under WooCommerce > Settings.
 *
 * @param WC_Settings_Page[] $settings Registered WooCommerce settings pages.
 * @return WC_Settings_Page[] Settings pages, including our own.
 */
function hmfw_add_settings_page( $settings ): array {
	$settings[] = include plugin_dir_path( __FILE__ ) . 'includes/class-hmfw-settings.php';

	return $settings;
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'hmfw_plugin_action_links' );
/**
 * Add a "Settings" link on the Plugins list page, pointing to the new settings tab.
 *
 * @param string[] $links Existing plugin action links.
 * @return string[] Plugin action links, including our "Settings" link.
 */
function hmfw_plugin_action_links( $links ): array {
	if ( hmfw_is_woocommerce_not_available() ) {
		return $links;
	}

	$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=holiday_mode' ) ) . '">' . esc_html__( 'Settings', 'holiday-mode-woocommerce' ) . '</a>';
	array_unshift( $links, $settings_link );

	return $links;
}

/**
 * One-time migration of settings that used to live in the Customizer (theme mods)
 * into normal WordPress options, managed by the new WooCommerce settings page.
 *
 * This runs from three different triggers, from "most immediate" to "most reliable":
 *
 * 1. register_activation_hook() - runs instantly on (re-)activation. Does NOT
 *    fire on a normal update though, because WordPress keeps the plugin active
 *    the whole time (files are just swapped in place), so this alone is not
 *    enough to cover the by far most common upgrade path.
 * 2. 'upgrader_process_complete' - fires right after WordPress (or an
 *    auto-update) finishes updating this plugin, so the migration typically
 *    happens immediately, without waiting for the next page load.
 * 3. 'init' (priority 5, fallback) - guarantees the migration eventually runs
 *    even if neither hook above fired, e.g. after a manual file replacement
 *    via FTP/SFTP/deployment script that bypasses the WordPress updater
 *    entirely. The check itself is a single, cheap get_option() call, so
 *    keeping it as a safety net has no meaningful performance impact once
 *    migrated.
 */
register_activation_hook( __FILE__, 'hmfw_migrate_customizer_settings' );

add_action( 'upgrader_process_complete', 'hmfw_migrate_after_plugin_update', 10, 2 );
/**
 * Trigger the Customizer migration right after this plugin was updated,
 * instead of waiting for the 'init' fallback below.
 *
 * @param WP_Upgrader $upgrader_object Upgrader instance (unused).
 * @param array       $options         Details about the bulk/single update that just completed.
 */
function hmfw_migrate_after_plugin_update( WP_Upgrader $upgrader_object, array $options ): void {
	if ( 'update' !== ( $options['action'] ?? '' ) || 'plugin' !== ( $options['type'] ?? '' ) ) {
		return;
	}

	if ( ! in_array( plugin_basename( __FILE__ ), $options['plugins'] ?? array(), true ) ) {
		return;
	}

	hmfw_migrate_customizer_settings();
}

/**
 * Version of this plugin at which the Customizer settings were migrated to
 * options managed by the WooCommerce settings page. Used as the threshold
 * for hmfw_migrate_customizer_settings() below.
 *
 * This must stay fixed at the version below which the Customizer settings
 * existed - it must NOT be bumped alongside HMFW_VERSION on future releases,
 * otherwise the migration would incorrectly re-run on every update.
 */
define( 'HMFW_CUSTOMIZER_MIGRATION_VERSION', '1.8.0' );

add_action( 'init', 'hmfw_migrate_customizer_settings', 5 );
/**
 * Migrate the legacy Customizer theme mods to WordPress options, once, when
 * upgrading from a version older than HMFW_CUSTOMIZER_MIGRATION_VERSION.
 */
function hmfw_migrate_customizer_settings(): void {
	$installed_version = get_option( 'hmfw_version', '0' );

	if ( version_compare( $installed_version, HMFW_CUSTOMIZER_MIGRATION_VERSION, '>=' ) ) {
		return;
	}

	$map = array(
		'hmfw_holiday-status'    => 'hmfw_holiday_status',
		'hmfw_holiday-startdate' => 'hmfw_holiday_startdate',
		'hmfw_holiday-enddate'   => 'hmfw_holiday_enddate',
		'hmfw_holiday-message'   => 'hmfw_holiday_message',
	);

	foreach ( $map as $theme_mod => $option ) {
		$value = get_theme_mod( $theme_mod, null );

		if ( null === $value || '' === $value ) {
			continue;
		}

		// The status theme mod stored a boolean; the option expects 'yes'/'no'.
		if ( 'hmfw_holiday-status' === $theme_mod ) {
			$value = $value ? 'yes' : 'no';
		}

		update_option( $option, $value );
		remove_theme_mod( $theme_mod );
	}

	update_option( 'hmfw_version', HMFW_VERSION );
}

// Runs after hmfw_migrate_customizer_settings() (priority 5) on the same 'init' hook,
// so freshly migrated options are already available on the very first request.
add_action( 'init', 'hmfw_woocommerce_holiday_mode', 10 );
/**
 * Activate Holiday Mode: disable purchasing and show the holiday notice
 * when the shop is within the configured date range.
 *
 * Also marks the current request as non-cacheable via the DONOTCACHEPAGE
 * constant, which W3 Total Cache and other cache plugins (WP Super Cache,
 * WP Rocket, LiteSpeed Cache, ...) respect. Holiday Mode is time controlled,
 * so instead of flushing the cache we simply never cache pages while it is
 * actually active - every request then evaluates the current date
 * correctly, without ever serving stale cached output.
 */
function hmfw_woocommerce_holiday_mode(): void {
	if ( hmfw_is_woocommerce_not_available() || 'yes' !== get_option( 'hmfw_holiday_status', 'no' ) ) {
		return;
	}

	if ( ! hmfw_check_in_range( get_option( 'hmfw_holiday_startdate' ), get_option( 'hmfw_holiday_enddate' ) ) ) {
		return;
	}

	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}

	// Disable Cart, Checkout, Add Cart. Kept as its own, separately switchable
	// option ('yes' by default, matching the plugin's historical behaviour),
	// since some merchants only want the holiday notice without actually
	// blocking purchases.
	if ( 'yes' === get_option( 'hmfw_disable_purchasing', 'yes' ) ) {
		add_filter( 'woocommerce_is_purchasable', '__return_false' );
		remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20 );
		remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
		// Variable products render their "Add to cart" button independently of
		// woocommerce_is_purchasable (WooCommerce only hides/disables it client-side,
		// per selected variation, via JS), so it must be removed explicitly here too.
		remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20 );
		// External/affiliate products render their "Buy product" button as soon as
		// an add-to-cart URL is set, regardless of woocommerce_is_purchasable, so it
		// must be removed explicitly here too, same as for variable products above.
		remove_action( 'woocommerce_external_add_to_cart', 'woocommerce_external_add_to_cart', 30 );
		// Grouped products list each child's own add-to-cart control the same way,
		// independently of woocommerce_is_purchasable.
		remove_action( 'woocommerce_grouped_add_to_cart', 'woocommerce_grouped_add_to_cart', 30 );
	}

	// Block themes (e.g. Twenty Twenty-Five) render the *entire* block
	// template via get_the_block_template_html() before wp_head()/
	// wp_body_open() ever fire (see wp-includes/template-canvas.php) - the
	// classic content hooks below therefore all run during that early,
	// pre-render pass, in whatever order the blocks happen to appear in the
	// template. WooCommerce's own default block-based Shop template places
	// its "Legacy Template" block (which fires woocommerce_before_main_content)
	// *after* the archive title and result count blocks, so printing there
	// would put the notice below "Shop / X results", not at the very top of
	// the page as intended. To guarantee top placement, classic themes keep
	// using the classic hooks (their natural top-to-bottom render order
	// already puts wp_body_open first), while block themes rely exclusively
	// on the wp_body_open hook below, which always executes - and is echoed
	// - before any block/template output.
	if ( ! wp_is_block_theme() ) {
		add_action( 'woocommerce_before_main_content', 'hmfw_wc_shop_disabled', 10 );
		// Registered unconditionally (not guarded by is_product()): conditional
		// tags are not yet reliable on 'init', since the main query has not run
		// yet at this point. This hook only ever fires on single product pages
		// anyway, so the guard was both redundant and broken.
		add_action( 'woocommerce_before_single_product', 'hmfw_wc_shop_disabled', 10 );
		add_action( 'woocommerce_before_cart', 'hmfw_wc_shop_disabled', 10 );
		add_action( 'woocommerce_before_checkout_form', 'hmfw_wc_shop_disabled', 10 );
	}

	// For block themes this is the sole mechanism (see above); for classic
	// themes it remains a safety net for header.php files that don't fire
	// any of the classic WooCommerce content hooks. wp_body_open is called
	// right after the opening <body> tag - WordPress core itself guarantees
	// this for block themes (template-canvas.php), and it is standard
	// practice in classic theme header.php files since WP 5.2. hmfw_wc_shop_disabled()
	// already guards against printing twice, so this is a no-op wherever a
	// classic hook already handled the notice.
	add_action( 'wp_body_open', 'hmfw_wc_shop_disabled_body_open_fallback' );
}

/**
 * Print the holiday notice right after the opening <body> tag, but only on
 * the pages Holiday Mode actually affects. Acts as a safety net for block
 * themes whose templates don't fire the classic WooCommerce content hooks
 * (see registration above).
 */
function hmfw_wc_shop_disabled_body_open_fallback(): void {
	if ( ! is_shop() && ! is_product_taxonomy() && ! is_product() && ! is_cart() && ! is_checkout() ) {
		return;
	}

	hmfw_wc_shop_disabled();
}

/**
 * Print the holiday notice on the shop, single product, cart and checkout pages.
 *
 * Guarded against printing more than once per request: on the single product
 * page, both woocommerce_before_main_content and woocommerce_before_single_product
 * fire for the same page load (the latter is nested inside the former), and
 * this function is hooked into both for theme-compatibility reasons - so
 * without this guard the notice would be duplicated.
 */
function hmfw_wc_shop_disabled(): void {
	global $hmfw_notice_already_printed;

	if ( ! empty( $hmfw_notice_already_printed ) ) {
		return;
	}

	$notice = get_option( 'hmfw_holiday_message' );

	if ( '' === $notice ) {
		$notice = get_option( 'woocommerce_demo_store_notice' );
	}

	$notice_type = get_option( 'hmfw_holiday_notice_type', 'error' );
	if ( ! in_array( $notice_type, array( 'error', 'notice' ), true ) ) {
		$notice_type = 'error';
	}

	wc_print_notice( wp_kses_post( $notice ), $notice_type );

	$hmfw_notice_already_printed = true;
}

/**
 * Check whether the given date range includes today.
 *
 * @param string $start_date Start date, parseable by DateTime.
 * @param string $end_date   End date, parseable by DateTime.
 *
 * @return bool True if today falls within [start_date, end_date].
 */
function hmfw_check_in_range( string $start_date, string $end_date ): bool {
	try {
		$timezone = wp_timezone();
		$start    = new DateTime( $start_date, $timezone );
		$end      = new DateTime( $end_date, $timezone );
		$today    = new DateTime( 'today midnight', $timezone );
	} catch ( Exception $e ) {
		return false;
	}

	// Check that today's date is between start & end.
	return ( $start <= $today && $today <= $end );
}

add_action( 'admin_notices', 'hmfw_wc_missing_notice' );
/**
 * Show an admin notice when WooCommerce is not active, since the settings
 * page is hooked into the WooCommerce admin menu and would otherwise be
 * invisible without any explanation.
 */
function hmfw_wc_missing_notice(): void {
	if ( ! hmfw_is_woocommerce_not_available() || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		wp_kses_post(
			__( '<strong>Holiday Mode for WooCommerce</strong> requires WooCommerce to be installed and active.', 'holiday-mode-woocommerce' )
		)
	);
}

add_action( 'woocommerce_settings_saved', 'hmfw_maybe_flush_cache_on_settings_save' );
/**
 * Flush the page cache once when the Holiday Mode settings are saved, so any
 * page that was already cached before Holiday Mode got (de)activated is
 * refreshed immediately. Ongoing date-range transitions no longer need a
 * flush, since hmfw_woocommerce_holiday_mode() sets DONOTCACHEPAGE and keeps
 * the affected pages out of the cache entirely while Holiday Mode is active.
 *
 * The actual flushing logic lives in HMFW_Cache_Flusher, see
 * includes/class-hmfw-cache-flusher.php.
 */
function hmfw_maybe_flush_cache_on_settings_save(): void {
	if ( ! isset( $_GET['tab'] ) || 'holiday_mode' !== sanitize_key( wp_unslash( $_GET['tab'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	HMFW_Cache_Flusher::flush();
}

/**
 * Check whether the WooCommerce plugin is active.
 *
 * @return bool True if WooCommerce is NOT available.
 */
function hmfw_is_woocommerce_not_available(): bool {
	return ! class_exists( 'WooCommerce' );
}
