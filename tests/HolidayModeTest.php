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

#[CoversFunction( 'hmfw_check_in_range' )]
#[CoversFunction( 'hmfw_isWooCommerceNotAvailable' )]
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

		global $_test_theme_mods, $_test_options, $wp_filter;
		$_test_theme_mods = array();
		$_test_options    = array();
		$wp_filter        = array();
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
			'today is within range'    => array( $yesterday, $tomorrow, true ),
			'today equals start date'  => array( $today_str, $tomorrow, true ),
			'today equals end date'    => array( $yesterday, $today_str, true ),
			'range is in the future'   => array( $tomorrow, $tomorrow, false ),
			'range is in the past'     => array( $yesterday, $yesterday, false ),
		);
	}

	public function testCheckInRangeReturnsFalseOnInvalidDate(): void {
		$this->assertFalse( \hmfw_check_in_range( 'not-a-date', 'also-not-a-date' ) );
	}

	public function testIsWooCommerceNotAvailableIsFalseWhenWooCommerceClassExists(): void {
		// A "WooCommerce" stub class is defined in bootstrap.php,
		// simulating an active WooCommerce installation.
		$this->assertFalse( \hmfw_isWooCommerceNotAvailable() );
	}

	public function testShopDisabledPrintsCustomMessageWhenEnabled(): void {
		\update_option( 'hmfw_holiday_use_custom_message', 'yes' );
		\update_option( 'hmfw_holiday_message', 'We are on vacation.' );

		ob_start();
		\hmfw_wc_shop_disabled();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'We are on vacation.', $output );
	}

	public function testShopDisabledPrintsStoreNoticeWhenCustomMessageDisabled(): void {
		\update_option( 'woocommerce_demo_store_notice', 'Store notice text' );
		\update_option( 'hmfw_holiday_use_custom_message', 'no' );

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
		\set_theme_mod( 'hmfw_holiday-useCustomMessage', false );
		\set_theme_mod( 'hmfw_holiday-message', 'Legacy message' );

		\hmfw_migrate_customizer_settings();

		$this->assertSame( 'yes', \get_option( 'hmfw_holiday_status' ) );
		$this->assertSame( '2026-01-01', \get_option( 'hmfw_holiday_startdate' ) );
		$this->assertSame( '2026-01-31', \get_option( 'hmfw_holiday_enddate' ) );
		$this->assertSame( 'no', \get_option( 'hmfw_holiday_use_custom_message' ) );
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
			new \stdClass(),
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
			new \stdClass(),
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
			new \stdClass(),
			array(
				'action'  => 'update',
				'type'    => 'theme',
				'themes'  => array( 'some-theme' ),
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
