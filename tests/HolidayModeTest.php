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
#[CoversFunction( 'hmfw_sanitize_checkbox' )]
#[CoversFunction( 'hmfw_isWooCommerceNotAvailable' )]
#[CoversFunction( 'hmfw_useCustomMessage_enabled' )]
#[CoversFunction( 'hmfw_wc_shop_disabled' )]
#[CoversFunction( 'hmfw_declare_wc_compatibility' )]
class HolidayModeTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		global $_test_theme_mods, $_test_options;
		$_test_theme_mods = array();
		$_test_options    = array();
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

	#[DataProvider( 'checkboxProvider' )]
	public function testSanitizeCheckbox( $input, bool $expected ): void {
		$this->assertSame( $expected, \hmfw_sanitize_checkbox( $input ) );
	}

	public static function checkboxProvider(): array {
		return array(
			'true stays true'        => array( true, true ),
			'1 (string) becomes true' => array( '1', true ),
			'1 (int) becomes true'    => array( 1, true ),
			'false stays false'      => array( false, false ),
			'0 becomes false'        => array( 0, false ),
			'null becomes false'     => array( null, false ),
			'empty string is false'  => array( '', false ),
		);
	}

	public function testIsWooCommerceNotAvailableIsFalseWhenWooCommerceClassExists(): void {
		// A "WooCommerce" stub class is defined at the top of this file,
		// simulating an active WooCommerce installation.
		$this->assertFalse( \hmfw_isWooCommerceNotAvailable() );
	}

	public function testUseCustomMessageEnabledReflectsThemeMod(): void {
		\set_theme_mod( 'hmfw_holiday-useCustomMessage', false );
		$this->assertFalse( \hmfw_useCustomMessage_enabled() );

		\set_theme_mod( 'hmfw_holiday-useCustomMessage', true );
		$this->assertTrue( \hmfw_useCustomMessage_enabled() );
	}

	public function testShopDisabledPrintsCustomMessageWhenEnabled(): void {
		\set_theme_mod( 'hmfw_holiday-useCustomMessage', true );
		\set_theme_mod( 'hmfw_holiday-message', 'We are on vacation.' );

		ob_start();
		\hmfw_wc_shop_disabled();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'We are on vacation.', $output );
	}

	public function testShopDisabledPrintsStoreNoticeWhenCustomMessageDisabled(): void {
		global $_test_options;
		$_test_options['woocommerce_demo_store_notice'] = 'Store notice text';
		\set_theme_mod( 'hmfw_holiday-useCustomMessage', false );

		ob_start();
		\hmfw_wc_shop_disabled();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Store notice text', $output );
	}

	public function testDeclareWcCompatibilityDoesNotThrowWhenFeaturesUtilMissing(): void {
		$this->expectNotToPerformAssertions();
		\hmfw_declare_wc_compatibility();
	}
}


