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
 * Version:           3.1.0
 * Author:            Heinrich Franz
 * Author URI:        https://sevmatic/?source=wordpress
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       holiday-mode-for-woocommerce
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Requires Plugins:  woocommerce
 * WC requires at least: 7.5
 * WC tested up to:   11.2
 */

/*
 * @copyright Heinrich Franz, 2021, All Rights Reserved
 * This code is released under the GPL licence version 2 or later, available here http://www.gnu.org/licenses/gpl-2.0.txt
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'HMFW_VERSION', '3.1.0' );

use Automattic\WooCommerce\Utilities\FeaturesUtil;

require_once plugin_dir_path( __FILE__ ) . 'includes/class-hmfw-cache-flusher.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-hmfw-store-api.php';

add_action( 'init', 'hmfw_load_textdomain' );
/**
 * Load the plugin's translations.
 */
function hmfw_load_textdomain(): void {
	load_plugin_textdomain( 'holiday-mode-for-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

add_action( 'before_woocommerce_init', 'hmfw_declare_wc_compatibility' );
/**
 * Declare compatibility with WooCommerce High-Performance Order Storage (HPOS)
 * and the Cart/Checkout blocks, so the plugin keeps working with modern WooCommerce.
 */
function hmfw_declare_wc_compatibility(): void {
	if ( ! class_exists( FeaturesUtil::class ) ) {
		return;
	}

	FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
}

add_action( 'woocommerce_blocks_loaded', array( 'HMFW_Store_Api', 'init' ) );

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

	$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=holiday_mode' ) ) . '">' . esc_html__( 'Settings', 'holiday-mode-for-woocommerce' ) . '</a>';
	array_unshift( $links, $settings_link );

	return $links;
}

add_filter( 'plugin_row_meta', 'hmfw_plugin_row_meta', 10, 2 );
/**
 * Adds "Support" and rating links to the plugin's row on the Plugins list page.
 *
 * @param array<int, string> $links Existing row meta links.
 * @param string             $file  Plugin basename of the plugin the row meta is for.
 * @return array<int, string>
 */
function hmfw_plugin_row_meta( array $links, string $file ): array {
	if ( plugin_basename( __FILE__ ) !== $file ) {
		return $links;
	}

	$links[] = '<a href="' . esc_url( 'https://wordpress.org/support/plugin/holiday-mode-for-woocommerce/reviews/?rate=5#new-post' ) . '" target="_blank" rel="noopener noreferrer" style="color:#ffb900;font-size:20px;text-decoration:none;">★★★★★</a>';
	$links[] = '<a href="' . esc_url( 'https://wordpress.org/support/plugin/holiday-mode-for-woocommerce/' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Support', 'holiday-mode-for-woocommerce' ) . '</a>';

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

register_activation_hook( __FILE__, 'hmfw_maybe_set_first_activation_time' );
// Also hooked into 'init' as a fallback, for the same reason
// hmfw_migrate_customizer_settings() above needs one: register_activation_hook()
// does not fire when an already-active plugin is merely updated (WordPress
// keeps it active throughout, it only swaps files in place), so without this,
// every pre-existing installation updating to the version that introduced
// hmfw_first_activated_at would never get it set, and would therefore never
// see the review notice at all.
add_action( 'init', 'hmfw_maybe_set_first_activation_time' );
/**
 * Record the first time this plugin was activated. Used by
 * hmfw_maybe_review_notice() below to only ask merchants for a review once
 * they have had a reasonable amount of time to actually use the plugin,
 * rather than nagging them right after install.
 */
function hmfw_maybe_set_first_activation_time(): void {
	if ( false === get_option( 'hmfw_first_activated_at', false ) ) {
		update_option( 'hmfw_first_activated_at', time() );
	}
}

/**
 * Check whether Holiday Mode is currently active, i.e. WooCommerce is
 * available, the merchant enabled it, and today falls within the configured
 * date range. Shared between the classic/block-theme activation below and
 * HMFW_Store_Api, which exposes the same state to headless frontends and the
 * Cart/Checkout blocks via the WooCommerce Store API.
 *
 * @return bool True if Holiday Mode is currently in effect.
 */
function hmfw_is_holiday_mode_active(): bool {
	if ( hmfw_is_woocommerce_not_available() || 'yes' !== get_option( 'hmfw_holiday_status', 'no' ) ) {
		return false;
	}

	return hmfw_check_in_range( get_option( 'hmfw_holiday_startdate' ), get_option( 'hmfw_holiday_enddate' ) );
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
	if ( ! hmfw_is_holiday_mode_active() ) {
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

	hmfw_register_shop_disabled_notice_hooks();
}

/**
 * Register the hooks that print the shop-disabled notice, shared by
 * hmfw_woocommerce_holiday_mode() above and hmfw_upcoming_closure_notice()
 * below - both ultimately print through hmfw_wc_shop_disabled(), which
 * itself decides the actual message/notice type based on which of the two
 * states is currently in effect.
 *
 * Block themes (e.g. Twenty Twenty-Five) render the *entire* block
 * template via get_the_block_template_html() before wp_head()/
 * wp_body_open() ever fire (see wp-includes/template-canvas.php) - the
 * classic content hooks below therefore all run during that early,
 * pre-render pass, in whatever order the blocks happen to appear in the
 * template. WooCommerce's own default block-based Shop template places
 * its "Legacy Template" block (which fires woocommerce_before_main_content)
 * *after* the archive title and result count blocks, so printing there
 * would put the notice below "Shop / X results", not at the very top of
 * the page as intended. To guarantee top placement, classic themes keep
 * using the classic hooks (their natural top-to-bottom render order
 * already puts wp_body_open first), while block themes rely exclusively
 * on the wp_body_open hook below, which always executes - and is echoed
 * - before any block/template output.
 */
function hmfw_register_shop_disabled_notice_hooks(): void {
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
	// classic hook already handled the notice. It is also the only hook that
	// ever fires on non-WooCommerce pages, so it doubles as the mechanism
	// behind the "Show on all pages" setting - see the page-type guard inside
	// hmfw_wc_shop_disabled_body_open_fallback().
	add_action( 'wp_body_open', 'hmfw_wc_shop_disabled_body_open_fallback' );
}

add_action( 'init', 'hmfw_upcoming_closure_notice', 10 );
/**
 * Show a heads-up notice in the days leading up to a scheduled closure (see
 * hmfw_is_upcoming_notice_active()), so customers get a chance to place
 * orders before Holiday Mode actually disables purchasing. Independent of
 * hmfw_woocommerce_holiday_mode() above - the two are mutually exclusive by
 * construction, since the advance-notice window always ends the day before
 * Holiday Mode's own start date.
 *
 * Sets DONOTCACHEPAGE for the same reason hmfw_woocommerce_holiday_mode()
 * does: which notice (if any) is shown depends on today's date, so a cached
 * page could otherwise keep showing yesterday's (non-)notice.
 */
function hmfw_upcoming_closure_notice(): void {
	if ( ! hmfw_is_upcoming_notice_active() ) {
		return;
	}

	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}

	hmfw_register_shop_disabled_notice_hooks();
}

/**
 * Check whether the advance-notice period configured via the
 * "hmfw_upcoming_notice_days" setting is currently in effect, i.e. Holiday
 * Mode is scheduled (but not active yet - see hmfw_is_holiday_mode_active())
 * and today falls within the configured number of days before the start date.
 *
 * @return bool True if the advance notice should currently be shown.
 */
function hmfw_is_upcoming_notice_active(): bool {
	if ( hmfw_is_woocommerce_not_available() || 'yes' !== get_option( 'hmfw_holiday_status', 'no' ) ) {
		return false;
	}

	$lead_days = (int) get_option( 'hmfw_upcoming_notice_days', 0 );

	if ( $lead_days <= 0 ) {
		return false;
	}

	try {
		$start        = new DateTime( get_option( 'hmfw_holiday_startdate' ), wp_timezone() );
		$window_start = ( clone $start )->modify( "-{$lead_days} days" );
		$window_end   = ( clone $start )->modify( '-1 day' );
	} catch ( Exception $e ) {
		return false;
	}

	// Always ends the day before the start date, so this can never overlap
	// with hmfw_is_holiday_mode_active() regardless of how large $lead_days is.
	return hmfw_check_in_range( $window_start->format( 'Y-m-d' ), $window_end->format( 'Y-m-d' ) );
}

/**
 * Replace the {start_date}/{end_date} placeholders in a message with the
 * configured Holiday Mode dates, formatted using the site's date format
 * setting. Shared by the "Vacation message" (hmfw_holiday_message, via
 * hmfw_wc_shop_disabled()) and the "Advance notice message"
 * (hmfw_upcoming_notice_message, via hmfw_build_upcoming_notice_message()
 * below) - both settings document and support the same two placeholders.
 *
 * @param string $message Message possibly containing {start_date}/{end_date}.
 * @return string The message with placeholders replaced.
 */
function hmfw_replace_date_placeholders( string $message ): string {
	$date_format = get_option( 'date_format', 'F j, Y' );

	if ( '' === $date_format ) {
		$date_format = 'F j, Y';
	}

	$start = strtotime( get_option( 'hmfw_holiday_startdate' ) );
	$end   = strtotime( get_option( 'hmfw_holiday_enddate' ) );

	return str_replace(
		array( '{start_date}', '{end_date}' ),
		array(
			$start ? wp_date( $date_format, $start ) : '',
			$end ? wp_date( $date_format, $end ) : '',
		),
		$message
	);
}

/**
 * Build the advance-notice message from the "hmfw_upcoming_notice_message" setting.
 *
 * @return string The advance-notice message with placeholders replaced.
 */
function hmfw_build_upcoming_notice_message(): string {
	return hmfw_replace_date_placeholders( get_option( 'hmfw_upcoming_notice_message', '' ) );
}

/**
 * Print the holiday notice right after the opening <body> tag. Acts as a
 * safety net for block themes whose templates don't fire the classic
 * WooCommerce content hooks (see registration above), and - when the
 * "Show on all pages" setting is enabled - as the actual mechanism for
 * printing the notice on non-WooCommerce pages, since those never fire any
 * WooCommerce content hook to begin with.
 *
 * Restricted to the shop, product, cart and checkout pages unless "Show on
 * all pages" is enabled, matching Holiday Mode's historical, WooCommerce-only
 * behaviour by default.
 */
function hmfw_wc_shop_disabled_body_open_fallback(): void {
	$show_on_all_pages = 'yes' === get_option( 'hmfw_notice_all_pages', 'no' );

	if ( ! $show_on_all_pages && ! is_shop() && ! is_product_taxonomy() && ! is_product() && ! is_cart() && ! is_checkout() ) {
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
 *
 * Called both while Holiday Mode is actually active and during the
 * advance-notice window before it starts (see hmfw_is_upcoming_notice_active()) -
 * the two states are mutually exclusive by construction, so checking
 * hmfw_is_holiday_mode_active() here is enough to tell them apart.
 */
function hmfw_wc_shop_disabled(): void {
	global $hmfw_notice_already_printed;

	if ( ! empty( $hmfw_notice_already_printed ) ) {
		return;
	}

	if ( hmfw_is_holiday_mode_active() ) {
		$notice = get_option( 'hmfw_holiday_message' );

		if ( '' === $notice ) {
			$notice = get_option( 'woocommerce_demo_store_notice' );
		} else {
			$notice = hmfw_replace_date_placeholders( $notice );
		}

		$notice_type = get_option( 'hmfw_holiday_notice_type', 'error' );
	} else {
		// Always the "info" style, deliberately independent of the merchant's
		// chosen Notice Color above, so customers can tell "closing soon" apart
		// from "closed" at a glance even if both happen to use the same color.
		$notice      = hmfw_build_upcoming_notice_message();
		$notice_type = 'notice';
	}

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
			__( '<strong>Holiday Mode for WooCommerce</strong> requires WooCommerce to be installed and active.', 'holiday-mode-for-woocommerce' )
		)
	);
}

add_action( 'admin_notices', 'hmfw_holiday_mode_active_notice' );
/**
 * Show an admin notice on every wp-admin screen while Holiday Mode is
 * active, so merchants don't forget the shop is currently closed to new
 * orders. Intentionally not dismissible: it should keep reappearing on
 * every page load for as long as Holiday Mode actually is active, the same
 * way the holiday notice itself keeps showing to shoppers.
 */
function hmfw_holiday_mode_active_notice(): void {
	if ( ! current_user_can( 'manage_woocommerce' ) || ! hmfw_is_holiday_mode_active() ) {
		return;
	}

	printf(
		'<div class="notice notice-warning"><p>%s</p></div>',
		wp_kses(
			sprintf(
				/* translators: %s: link to the Holiday Mode settings page */
				__( '<strong>Holiday Mode</strong> is currently active - your shop is closed to new orders. %s', 'holiday-mode-for-woocommerce' ),
				'<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=holiday_mode' ) ) . '">' . esc_html__( 'Manage Holiday Mode settings', 'holiday-mode-for-woocommerce' ) . '</a>'
			),
			array(
				'strong' => array(),
				'a'      => array(
					'href' => array(),
				),
			)
		)
	); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Number of seconds after first activation before hmfw_maybe_review_notice()
 * starts asking for a review, so only merchants who have had a real chance
 * to use the plugin see it, not everyone who just installed it.
 */
define( 'HMFW_REVIEW_NOTICE_DELAY', 14 * DAY_IN_SECONDS );

add_action( 'admin_notices', 'hmfw_maybe_review_notice' );
/**
 * Ask merchants who have used the plugin for a while to leave a review.
 * Deliberately independent of Holiday Mode actually being active right
 * now (unlike hmfw_holiday_mode_active_notice() above) - this is about
 * overall plugin usage, not the current holiday period - and only ever
 * shows once, until dismissed via hmfw_maybe_dismiss_review_notice().
 */
function hmfw_maybe_review_notice(): void {
	if ( hmfw_is_woocommerce_not_available() || ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	if ( 'yes' === get_option( 'hmfw_review_notice_dismissed', 'no' ) ) {
		return;
	}

	$first_activated_at = (int) get_option( 'hmfw_first_activated_at', 0 );

	if ( ! $first_activated_at || ( time() - $first_activated_at ) < HMFW_REVIEW_NOTICE_DELAY ) {
		return;
	}

	$dismiss_url = add_query_arg(
		array(
			'hmfw-dismiss-review-notice' => '1',
			'_wpnonce'                   => wp_create_nonce( 'hmfw_dismiss_review_notice' ),
		)
	);

	printf(
		'<div class="notice notice-info"><p>%s</p><p><a href="%s" class="button button-primary" style="margin-right: 10px;" target="_blank" rel="noopener noreferrer">%s</a> <a href="%s">%s</a></p></div>',
		esc_html__( 'Enjoying Holiday Mode for WooCommerce? A quick review helps other merchants find it.', 'holiday-mode-for-woocommerce' ),
		esc_url( 'https://wordpress.org/support/plugin/holiday-mode-for-woocommerce/reviews/#new-post' ),
		esc_html__( 'Leave a review', 'holiday-mode-for-woocommerce' ),
		esc_url( $dismiss_url ),
		esc_html__( 'Dismiss', 'holiday-mode-for-woocommerce' )
	);
}

add_action( 'admin_init', 'hmfw_maybe_dismiss_review_notice' );
/**
 * Handle the "Dismiss" link on the review-request notice above. Runs on
 * admin_init, before any output, so the option is updated and the redirect
 * can still happen before hmfw_maybe_review_notice() would otherwise render
 * the notice again on the same request.
 */
function hmfw_maybe_dismiss_review_notice(): void {
	if ( ! isset( $_GET['hmfw-dismiss-review-notice'], $_GET['_wpnonce'] ) ) {
		return;
	}

	$nonce = sanitize_key( wp_unslash( $_GET['_wpnonce'] ) );

	if ( ! current_user_can( 'manage_woocommerce' ) || ! wp_verify_nonce( $nonce, 'hmfw_dismiss_review_notice' ) ) {
		return;
	}

	update_option( 'hmfw_review_notice_dismissed', 'yes' );

	wp_safe_redirect( remove_query_arg( array( 'hmfw-dismiss-review-notice', '_wpnonce' ) ) );
	exit;
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
