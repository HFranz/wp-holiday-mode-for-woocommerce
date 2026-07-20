<?php
/**
 * Unit tests verifying that hmfw_woocommerce_holiday_mode() marks the
 * current request as non-cacheable (DONOTCACHEPAGE) for cache plugins such
 * as W3 Total Cache, but only while Holiday Mode is actually active.
 *
 * Each test runs in its own process because DONOTCACHEPAGE, once defined,
 * cannot be undefined again within the same PHP process.
 *
 * @package IPHolidayModeWooCommerce
 */

namespace Hfranz\WpHolidayModeForWoocommerce\Tests;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[CoversFunction( 'hmfw_woocommerce_holiday_mode' )]
#[RunTestsInSeparateProcesses]
class DonotCachePageTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		global $_test_options, $wp_filter;
		$_test_options = array();
		$wp_filter     = array();
	}

	public function testDefinesDonotcachepageWhenHolidayModeIsActive(): void {
		$today = new \DateTime( 'today midnight', \wp_timezone() );

		\update_option( 'hmfw_holiday_status', 'yes' );
		\update_option( 'hmfw_holiday_startdate', ( clone $today )->modify( '-1 day' )->format( 'Y-m-d' ) );
		\update_option( 'hmfw_holiday_enddate', ( clone $today )->modify( '+1 day' )->format( 'Y-m-d' ) );

		\hmfw_woocommerce_holiday_mode();

		$this->assertTrue( defined( 'DONOTCACHEPAGE' ), 'DONOTCACHEPAGE was not defined while Holiday Mode is active.' );
		$this->assertTrue( DONOTCACHEPAGE );
	}

	public function testDoesNotDefineDonotcachepageWhenHolidayModeIsDisabled(): void {
		\update_option( 'hmfw_holiday_status', 'no' );

		\hmfw_woocommerce_holiday_mode();

		$this->assertFalse( defined( 'DONOTCACHEPAGE' ), 'DONOTCACHEPAGE must not be defined while Holiday Mode is disabled.' );
	}

	public function testDoesNotDefineDonotcachepageWhenOutsideDateRange(): void {
		\update_option( 'hmfw_holiday_status', 'yes' );
		\update_option( 'hmfw_holiday_startdate', '2000-01-01' );
		\update_option( 'hmfw_holiday_enddate', '2000-01-02' );

		\hmfw_woocommerce_holiday_mode();

		$this->assertFalse( defined( 'DONOTCACHEPAGE' ), 'DONOTCACHEPAGE must not be defined while today is outside the configured date range.' );
	}

	/**
	 * If some other plugin/theme already defined DONOTCACHEPAGE earlier in
	 * the request (e.g. always disabling the cache for logged-in users),
	 * hmfw_woocommerce_holiday_mode() must not try to redefine it, which
	 * would trigger a PHP warning/fatal error.
	 */
	public function testDoesNotRedefineExistingDonotcachepageConstant(): void {
		define( 'DONOTCACHEPAGE', false );

		$today = new \DateTime( 'today midnight', \wp_timezone() );
		\update_option( 'hmfw_holiday_status', 'yes' );
		\update_option( 'hmfw_holiday_startdate', ( clone $today )->modify( '-1 day' )->format( 'Y-m-d' ) );
		\update_option( 'hmfw_holiday_enddate', ( clone $today )->modify( '+1 day' )->format( 'Y-m-d' ) );

		\hmfw_woocommerce_holiday_mode();

		$this->assertFalse( DONOTCACHEPAGE, 'An already-defined DONOTCACHEPAGE constant must be left untouched.' );
	}
}
