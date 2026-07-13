<?php
/**
 * Unit tests for the core hmfw_* functions of the plugin.
 *
 * @package IPHolidayModeWooCommerce
 */

namespace Hfranz\WpHolidayModeForWoocommerce\Tests;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WP_Upgrader;

#[CoversFunction( 'hmfw_check_in_range' )]
#[CoversFunction( 'hmfw_is_woocommerce_not_available' )]
#[CoversFunction( 'hmfw_wc_shop_disabled' )]
#[CoversFunction( 'hmfw_declare_wc_compatibility' )]
#[CoversFunction( 'hmfw_migrate_customizer_settings' )]
#[CoversFunction( 'hmfw_migrate_after_plugin_update' )]
#[CoversFunction( 'hmfw_woocommerce_holiday_mode' )]
#[CoversFunction( 'hmfw_plugin_action_links' )]
#[CoversFunction( 'hmfw_wc_missing_notice' )]
class HolidayModeTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		global $_test_theme_mods, $_test_options, $wp_filter, $hmfw_notice_already_printed, $_test_conditional_tags, $_test_is_block_theme;
		$_test_theme_mods            = array();
		$_test_options                = array();
		$wp_filter                    = array();
		$hmfw_notice_already_printed = false;
		$_test_conditional_tags       = array();
		$_test_is_block_theme         = false;
	}

	#[DataProvider( 'rangeProvider' )]
	public function testCheckInRange( string $start, string $end, bool $expected ): void {
		$this->assertSame( $expected, \hmfw_check_in_range( $start, $end ) );
	}

	public static function rangeProvider(): array {
		$today     = new \DateTime( 'today midnight', \wp_timezone() );
		$yesterday = ( clone $today )->modify( '-1 day' )->format( 'Y-m-d' );
		$tomorrow  = ( clone $today )->modify( '+1 day' )->format( 'Y-m-d' );
		$today_str = $today->format( 'Y-m-d' );

		return array(
			'today is within range'   => array( $yesterday, $tomorrow, true ),
			'today equals start date' => array( $today_str, $tomorrow, true ),
			'today equals end date'   => array( $yesterday, $today_str, true ),
			'range is in the future'  => array( $tomorrow, $tomorrow, false ),
			'range is in the past'    => array( $yesterday, $yesterday, false ),
		);
	}

	public function testCheckInRangeReturnsFalseOnInvalidDate(): void {
		$this->assertFalse( \hmfw_check_in_range( 'not-a-date', 'also-not-a-date' ) );
	}

	public function testIsWooCommerceNotAvailableIsFalseWhenWooCommerceClassExists(): void {
		// A "WooCommerce" stub class is defined in bootstrap.php,
		// simulating an active WooCommerce installation.
		$this->assertFalse( \hmfw_is_woocommerce_not_available() );
	}

	public function testShopDisabledPrintsCustomMessageWhenSet(): void {
		\update_option( 'hmfw_holiday_message', 'We are on vacation.' );

		ob_start();
		\hmfw_wc_shop_disabled();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'We are on vacation.', $output );
	}

	public function testShopDisabledFallsBackToStoreNoticeWhenMessageEmpty(): void {
		\update_option( 'woocommerce_demo_store_notice', 'Store notice text' );
		\update_option( 'hmfw_holiday_message', '' );

		ob_start();
		\hmfw_wc_shop_disabled();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Store notice text', $output );
	}

	public function testDeclareWcCompatibilityDoesNotThrowWhenFeaturesUtilMissing(): void {
		$this->expectNotToPerformAssertions();
		\hmfw_declare_wc_compatibility();
	}

	public function testMigrateCustomizerSettingsMovesThemeModsToOptions(): void {
		\set_theme_mod( 'hmfw_holiday-status', true );
		\set_theme_mod( 'hmfw_holiday-startdate', '2026-01-01' );
		\set_theme_mod( 'hmfw_holiday-enddate', '2026-01-31' );
		\set_theme_mod( 'hmfw_holiday-message', 'Legacy message' );

		\hmfw_migrate_customizer_settings();

		$this->assertSame( 'yes', \get_option( 'hmfw_holiday_status' ) );
		$this->assertSame( '2026-01-01', \get_option( 'hmfw_holiday_startdate' ) );
		$this->assertSame( '2026-01-31', \get_option( 'hmfw_holiday_enddate' ) );
		$this->assertSame( 'Legacy message', \get_option( 'hmfw_holiday_message' ) );
		$this->assertSame( HMFW_VERSION, \get_option( 'hmfw_version' ) );
		$this->assertFalse( \get_theme_mod( 'hmfw_holiday-status', false ) );
	}

	public function testMigrateCustomizerSettingsRunsOnlyOnceVersionAlreadyCurrent(): void {
		\update_option( 'hmfw_version', HMFW_VERSION );
		\set_theme_mod( 'hmfw_holiday-status', true );

		\hmfw_migrate_customizer_settings();

		$this->assertFalse( \get_option( 'hmfw_holiday_status' ) );
	}

	public function testMigrateCustomizerSettingsSkipsWhenInstalledVersionIsNewer(): void {
		\update_option( 'hmfw_version', '9.9.9' );
		\set_theme_mod( 'hmfw_holiday-status', true );

		\hmfw_migrate_customizer_settings();

		$this->assertFalse( \get_option( 'hmfw_holiday_status' ) );
	}

	public function testMigrateCustomizerSettingsRunsWhenInstalledVersionIsOlder(): void {
		\update_option( 'hmfw_version', '1.7.1' );
		\set_theme_mod( 'hmfw_holiday-status', true );

		\hmfw_migrate_customizer_settings();

		$this->assertSame( 'yes', \get_option( 'hmfw_holiday_status' ) );
		$this->assertSame( HMFW_VERSION, \get_option( 'hmfw_version' ) );
	}

	/**
	 * Regression test for the scenario the plugin author explicitly cared about:
	 * a shop that already had Holiday Mode active via the old Customizer setting
	 * must keep working immediately after the update - even on the very first
	 * front-end request, before any wp-admin page (which used to run the
	 * migration on 'admin_init' only) has ever been opened.
	 *
	 * Both functions are hooked into the same 'init' action (migration at
	 * priority 5, the activation logic at priority 10), so calling them in
	 * this order faithfully simulates a single real WordPress request.
	 */
	public function testMigratedHolidayModeActivatesOnTheVeryFirstRequest(): void {
		$today = new \DateTime( 'today midnight', \wp_timezone() );

		// Only legacy Customizer data exists at this point, no options yet.
		\set_theme_mod( 'hmfw_holiday-status', true );
		\set_theme_mod( 'hmfw_holiday-startdate', ( clone $today )->modify( '-1 day' )->format( 'Y-m-d' ) );
		\set_theme_mod( 'hmfw_holiday-enddate', ( clone $today )->modify( '+1 day' )->format( 'Y-m-d' ) );

		// Simulate a single request hitting the 'init' hook.
		\hmfw_migrate_customizer_settings();
		\hmfw_woocommerce_holiday_mode();

		$this->assertNotFalse( \has_action( 'woocommerce_before_main_content', 'hmfw_wc_shop_disabled' ) );
	}

	public function testMigrateAfterPluginUpdateRunsMigrationWhenThisPluginWasUpdated(): void {
		\set_theme_mod( 'hmfw_holiday-status', true );

		\hmfw_migrate_after_plugin_update(
			new WP_Upgrader(),
			array(
				'action'  => 'update',
				'type'    => 'plugin',
				'plugins' => array( \plugin_basename( dirname( __DIR__ ) . '/holiday-mode-woocommerce.php' ) ),
			)
		);

		$this->assertSame( 'yes', \get_option( 'hmfw_holiday_status' ) );
	}

	public function testMigrateAfterPluginUpdateIgnoresUnrelatedPluginUpdates(): void {
		\set_theme_mod( 'hmfw_holiday-status', true );

		\hmfw_migrate_after_plugin_update(
			new WP_Upgrader(),
			array(
				'action'  => 'update',
				'type'    => 'plugin',
				'plugins' => array( 'some-other-plugin/some-other-plugin.php' ),
			)
		);

		$this->assertFalse( \get_option( 'hmfw_holiday_status' ) );
	}

	public function testMigrateAfterPluginUpdateIgnoresThemeUpdates(): void {
		\set_theme_mod( 'hmfw_holiday-status', true );

		\hmfw_migrate_after_plugin_update(
			new WP_Upgrader(),
			array(
				'action' => 'update',
				'type'   => 'theme',
				'themes' => array( 'some-theme' ),
			)
		);

		$this->assertFalse( \get_option( 'hmfw_holiday_status' ) );
	}

	public function testWoocommerceHolidayModeSkipsWhenNotActivated(): void {
		\update_option( 'hmfw_holiday_status', 'no' );

		\hmfw_woocommerce_holiday_mode();

		$this->assertFalse( \has_action( 'woocommerce_before_main_content' ) );
	}

	public function testWoocommerceHolidayModeSkipsWhenOutsideDateRange(): void {
		\update_option( 'hmfw_holiday_status', 'yes' );
		\update_option( 'hmfw_holiday_startdate', '2000-01-01' );
		\update_option( 'hmfw_holiday_enddate', '2000-01-02' );

		\hmfw_woocommerce_holiday_mode();

		$this->assertFalse( \has_action( 'woocommerce_before_main_content' ) );
	}

	public function testWoocommerceHolidayModeActivatesWithinDateRange(): void {
		$today = new \DateTime( 'today midnight', \wp_timezone() );

		\update_option( 'hmfw_holiday_status', 'yes' );
		\update_option( 'hmfw_holiday_startdate', ( clone $today )->modify( '-1 day' )->format( 'Y-m-d' ) );
		\update_option( 'hmfw_holiday_enddate', ( clone $today )->modify( '+1 day' )->format( 'Y-m-d' ) );

		\hmfw_woocommerce_holiday_mode();

		$this->assertNotFalse( \has_action( 'woocommerce_before_main_content', 'hmfw_wc_shop_disabled' ) );
		$this->assertNotFalse( \has_action( 'woocommerce_before_cart', 'hmfw_wc_shop_disabled' ) );
		$this->assertNotFalse( \has_action( 'woocommerce_before_checkout_form', 'hmfw_wc_shop_disabled' ) );
	}

	/**
	 * Regression test: for variable products, WooCommerce renders the
	 * variations "Add to cart" button independently of the
	 * woocommerce_is_purchasable filter (purchasability is only enforced
	 * client-side, per selected variation), so it must be removed explicitly.
	 */
	public function testWoocommerceHolidayModeRemovesVariationAddToCartButton(): void {
		$today = new \DateTime( 'today midnight', \wp_timezone() );

		// Simulate WooCommerce core having registered its own add-to-cart
		// button callback for variable products on this hook.
		\add_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20 );

		\update_option( 'hmfw_holiday_status', 'yes' );
		\update_option( 'hmfw_holiday_startdate', ( clone $today )->modify( '-1 day' )->format( 'Y-m-d' ) );
		\update_option( 'hmfw_holiday_enddate', ( clone $today )->modify( '+1 day' )->format( 'Y-m-d' ) );

		\hmfw_woocommerce_holiday_mode();

		$this->assertFalse( \has_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button' ) );
	}

	/**
	 * Regression test: for external/affiliate products, WooCommerce renders
	 * the "Buy product" button as soon as an add-to-cart URL is set,
	 * independently of the woocommerce_is_purchasable filter, so it must be
	 * removed explicitly.
	 */
	public function testWoocommerceHolidayModeRemovesExternalAddToCartButton(): void {
		$today = new \DateTime( 'today midnight', \wp_timezone() );

		// Simulate WooCommerce core having registered its own add-to-cart
		// button callback for external/affiliate products on this hook.
		\add_action( 'woocommerce_external_add_to_cart', 'woocommerce_external_add_to_cart', 30 );

		\update_option( 'hmfw_holiday_status', 'yes' );
		\update_option( 'hmfw_holiday_startdate', ( clone $today )->modify( '-1 day' )->format( 'Y-m-d' ) );
		\update_option( 'hmfw_holiday_enddate', ( clone $today )->modify( '+1 day' )->format( 'Y-m-d' ) );

		\hmfw_woocommerce_holiday_mode();

		$this->assertFalse( \has_action( 'woocommerce_external_add_to_cart', 'woocommerce_external_add_to_cart' ) );
	}

	/**
	 * Regression test: grouped products list each child product's own
	 * add-to-cart control the same way, independently of the
	 * woocommerce_is_purchasable filter, so it must be removed explicitly.
	 */
	public function testWoocommerceHolidayModeRemovesGroupedAddToCartButton(): void {
		$today = new \DateTime( 'today midnight', \wp_timezone() );

		// Simulate WooCommerce core having registered its own add-to-cart
		// button callback for grouped products on this hook.
		\add_action( 'woocommerce_grouped_add_to_cart', 'woocommerce_grouped_add_to_cart', 30 );

		\update_option( 'hmfw_holiday_status', 'yes' );
		\update_option( 'hmfw_holiday_startdate', ( clone $today )->modify( '-1 day' )->format( 'Y-m-d' ) );
		\update_option( 'hmfw_holiday_enddate', ( clone $today )->modify( '+1 day' )->format( 'Y-m-d' ) );

		\hmfw_woocommerce_holiday_mode();

		$this->assertFalse( \has_action( 'woocommerce_grouped_add_to_cart', 'woocommerce_grouped_add_to_cart' ) );
	}

	/**
	 * Regression test: is_product() (and other conditional tags) are not yet
	 * reliable on 'init', since the main query has not run yet at that point.
	 * The woocommerce_before_single_product fallback hook (added for classic
	 * themes that don't call woocommerce_before_main_content on the single
	 * product page) must therefore be registered unconditionally.
	 */
	public function testWoocommerceHolidayModeAlwaysRegistersSingleProductFallback(): void {
		$today = new \DateTime( 'today midnight', \wp_timezone() );

		\update_option( 'hmfw_holiday_status', 'yes' );
		\update_option( 'hmfw_holiday_startdate', ( clone $today )->modify( '-1 day' )->format( 'Y-m-d' ) );
		\update_option( 'hmfw_holiday_enddate', ( clone $today )->modify( '+1 day' )->format( 'Y-m-d' ) );

		\hmfw_woocommerce_holiday_mode();

		$this->assertNotFalse( \has_action( 'woocommerce_before_single_product', 'hmfw_wc_shop_disabled' ) );
	}

	/**
	 * Purchasing must be disabled by default (backwards compatible with
	 * previous plugin versions, where this was not yet configurable), even
	 * when the "hmfw_disable_purchasing" option has never been saved.
	 */
	public function testWoocommerceHolidayModeDisablesPurchasingByDefault(): void {
		$today = new \DateTime( 'today midnight', \wp_timezone() );

		\update_option( 'hmfw_holiday_status', 'yes' );
		\update_option( 'hmfw_holiday_startdate', ( clone $today )->modify( '-1 day' )->format( 'Y-m-d' ) );
		\update_option( 'hmfw_holiday_enddate', ( clone $today )->modify( '+1 day' )->format( 'Y-m-d' ) );

		\hmfw_woocommerce_holiday_mode();

		$this->assertNotFalse( \has_filter( 'woocommerce_is_purchasable', '__return_false' ) );
	}

	/**
	 * Merchants who only want the holiday notice, without actually blocking
	 * purchases, can opt out via the "hmfw_disable_purchasing" checkbox.
	 */
	public function testWoocommerceHolidayModeKeepsPurchasingEnabledWhenOptedOut(): void {
		$today = new \DateTime( 'today midnight', \wp_timezone() );

		\add_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20 );
		\add_action( 'woocommerce_external_add_to_cart', 'woocommerce_external_add_to_cart', 30 );
		\add_action( 'woocommerce_grouped_add_to_cart', 'woocommerce_grouped_add_to_cart', 30 );

		\update_option( 'hmfw_holiday_status', 'yes' );
		\update_option( 'hmfw_disable_purchasing', 'no' );
		\update_option( 'hmfw_holiday_startdate', ( clone $today )->modify( '-1 day' )->format( 'Y-m-d' ) );
		\update_option( 'hmfw_holiday_enddate', ( clone $today )->modify( '+1 day' )->format( 'Y-m-d' ) );

		\hmfw_woocommerce_holiday_mode();

		$this->assertFalse( \has_filter( 'woocommerce_is_purchasable', '__return_false' ) );
		$this->assertNotFalse( \has_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button' ) );
		$this->assertNotFalse( \has_action( 'woocommerce_external_add_to_cart', 'woocommerce_external_add_to_cart' ) );
		$this->assertNotFalse( \has_action( 'woocommerce_grouped_add_to_cart', 'woocommerce_grouped_add_to_cart' ) );
		// The holiday notice must still be shown even when purchasing stays enabled.
		$this->assertNotFalse( \has_action( 'woocommerce_before_main_content', 'hmfw_wc_shop_disabled' ) );
	}

	/**
	 * Regression test: on the single product page, both
	 * woocommerce_before_main_content and woocommerce_before_single_product
	 * fire during the same page load, since the latter is nested inside the
	 * former. hmfw_wc_shop_disabled() must therefore only print the notice
	 * once per request, even when called multiple times.
	 */
	public function testShopDisabledOnlyPrintsOncePerRequest(): void {
		\update_option( 'hmfw_holiday_message', 'We are on vacation.' );

		ob_start();
		\hmfw_wc_shop_disabled();
		\hmfw_wc_shop_disabled();
		$output = ob_get_clean();

		$this->assertSame( 1, substr_count( $output, 'We are on vacation.' ) );
	}

	/**
	 * Regression test: block themes whose /shop template was customized in
	 * the Site Editor to use native blocks instead of the "Legacy Template"
	 * block never fire woocommerce_before_main_content, so the wp_body_open
	 * fallback must register and print the notice on its own.
	 */
	public function testWoocommerceHolidayModeRegistersWpBodyOpenFallback(): void {
		$today = new \DateTime( 'today midnight', \wp_timezone() );

		\update_option( 'hmfw_holiday_status', 'yes' );
		\update_option( 'hmfw_holiday_startdate', ( clone $today )->modify( '-1 day' )->format( 'Y-m-d' ) );
		\update_option( 'hmfw_holiday_enddate', ( clone $today )->modify( '+1 day' )->format( 'Y-m-d' ) );

		\hmfw_woocommerce_holiday_mode();

		$this->assertNotFalse( \has_action( 'wp_body_open', 'hmfw_wc_shop_disabled_body_open_fallback' ) );
	}

	/**
	 * Regression test: on block themes (e.g. Twenty Twenty-Five), the entire
	 * block template is rendered via get_the_block_template_html() before
	 * wp_body_open() ever fires (see wp-includes/template-canvas.php), so the
	 * classic content hooks would run - and claim the print-once guard - at
	 * whatever position the corresponding block happens to occupy in the
	 * template (e.g. below the archive title/result count on WooCommerce's
	 * default Shop template), instead of at the very top of the page. Block
	 * themes must therefore rely exclusively on wp_body_open.
	 */
	public function testWoocommerceHolidayModeSkipsClassicHooksOnBlockThemes(): void {
		global $_test_is_block_theme;
		$_test_is_block_theme = true;

		$today = new \DateTime( 'today midnight', \wp_timezone() );

		\update_option( 'hmfw_holiday_status', 'yes' );
		\update_option( 'hmfw_holiday_startdate', ( clone $today )->modify( '-1 day' )->format( 'Y-m-d' ) );
		\update_option( 'hmfw_holiday_enddate', ( clone $today )->modify( '+1 day' )->format( 'Y-m-d' ) );

		\hmfw_woocommerce_holiday_mode();

		$this->assertFalse( \has_action( 'woocommerce_before_main_content', 'hmfw_wc_shop_disabled' ) );
		$this->assertFalse( \has_action( 'woocommerce_before_single_product', 'hmfw_wc_shop_disabled' ) );
		$this->assertFalse( \has_action( 'woocommerce_before_cart', 'hmfw_wc_shop_disabled' ) );
		$this->assertFalse( \has_action( 'woocommerce_before_checkout_form', 'hmfw_wc_shop_disabled' ) );
		$this->assertNotFalse( \has_action( 'wp_body_open', 'hmfw_wc_shop_disabled_body_open_fallback' ) );
	}

	/**
	 * @param array<string, bool> $conditional_tags
	 */
	#[DataProvider( 'shopFooterFallbackPageProvider' )]
	public function testFooterFallbackPrintsNoticeOnRelevantPages( array $conditional_tags ): void {
		global $_test_conditional_tags;
		$_test_conditional_tags = $conditional_tags;

		\update_option( 'hmfw_holiday_message', 'We are on vacation.' );

		ob_start();
		\hmfw_wc_shop_disabled_body_open_fallback();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'We are on vacation.', $output );
	}

	public static function shopFooterFallbackPageProvider(): array {
		return array(
			'shop archive'     => array( array( 'is_shop' => true ) ),
			'product taxonomy' => array( array( 'is_product_taxonomy' => true ) ),
			'single product'   => array( array( 'is_product' => true ) ),
			'cart'             => array( array( 'is_cart' => true ) ),
			'checkout'         => array( array( 'is_checkout' => true ) ),
		);
	}

	public function testFooterFallbackDoesNothingOnUnrelatedPages(): void {
		\update_option( 'hmfw_holiday_message', 'We are on vacation.' );

		ob_start();
		\hmfw_wc_shop_disabled_body_open_fallback();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	public function testPluginActionLinksAddsSettingsLink(): void {
		$links = \hmfw_plugin_action_links( array( 'deactivate' => 'Deactivate' ) );

		$this->assertStringContainsString( 'wc-settings&tab=holiday_mode', $links[0] );
		$this->assertArrayHasKey( 'deactivate', $links );
	}

	public function testWcMissingNoticeDoesNotThrow(): void {
		$this->expectNotToPerformAssertions();
		\hmfw_wc_missing_notice();
	}
}
