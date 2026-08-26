<?php
/**
 * Unit tests for HMFW_Store_Api and hmfw_is_holiday_mode_active().
 *
 * @package IPHolidayModeWooCommerce
 */

namespace Hfranz\WpHolidayModeForWoocommerce\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use function hmfw_is_holiday_mode_active;
use function update_option;

#[CoversFunction( 'hmfw_is_holiday_mode_active' )]
#[CoversClass( \HMFW_Store_Api::class )]
class StoreApiTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		global $_test_options, $wp_filter;
		$_test_options = array();
		$wp_filter     = array();
	}

	public function testInactiveWhenHolidayStatusIsOff(): void {
		update_option( 'hmfw_holiday_status', 'no' );

		$this->assertFalse( hmfw_is_holiday_mode_active() );

		$data = \HMFW_Store_Api::get_data();
		$this->assertFalse( $data['active'] );
		$this->assertFalse( $data['purchasing_disabled'] );
		$this->assertSame( '', $data['message'] );
	}

	public function testInactiveWhenOutsideDateRange(): void {
		update_option( 'hmfw_holiday_status', 'yes' );
		update_option( 'hmfw_holiday_startdate', '2000-01-01' );
		update_option( 'hmfw_holiday_enddate', '2000-01-31' );

		$this->assertFalse( hmfw_is_holiday_mode_active() );
		$this->assertFalse( \HMFW_Store_Api::get_data()['active'] );
	}

	public function testActiveWithPurchasingDisabledExposesMessageAndNoticeType(): void {
		update_option( 'hmfw_holiday_status', 'yes' );
		update_option( 'hmfw_holiday_startdate', gmdate( 'Y-m-d', strtotime( '-1 day' ) ) );
		update_option( 'hmfw_holiday_enddate', gmdate( 'Y-m-d', strtotime( '+1 day' ) ) );
		update_option( 'hmfw_disable_purchasing', 'yes' );
		update_option( 'hmfw_holiday_message', 'We are on vacation, back soon!' );
		update_option( 'hmfw_holiday_notice_type', 'notice' );

		$data = \HMFW_Store_Api::get_data();

		$this->assertTrue( $data['active'] );
		$this->assertTrue( $data['purchasing_disabled'] );
		$this->assertSame( 'We are on vacation, back soon!', $data['message'] );
		$this->assertSame( 'notice', $data['notice_type'] );
	}

	public function testActiveButNoticeOnlyLeavesPurchasingEnabled(): void {
		update_option( 'hmfw_holiday_status', 'yes' );
		update_option( 'hmfw_holiday_startdate', gmdate( 'Y-m-d', strtotime( '-1 day' ) ) );
		update_option( 'hmfw_holiday_enddate', gmdate( 'Y-m-d', strtotime( '+1 day' ) ) );
		update_option( 'hmfw_disable_purchasing', 'no' );
		update_option( 'hmfw_holiday_message', 'Slower shipping this week.' );

		$data = \HMFW_Store_Api::get_data();

		$this->assertTrue( $data['active'] );
		$this->assertFalse( $data['purchasing_disabled'] );
		$this->assertSame( 'Slower shipping this week.', $data['message'] );
	}

	public function testGetSchemaDescribesAllDataKeys(): void {
		$schema = \HMFW_Store_Api::get_schema();

		$this->assertSame(
			array( 'active', 'purchasing_disabled', 'message', 'notice_type' ),
			array_keys( $schema )
		);
	}

	public function testInitIsANoOpWithoutStoreApiAvailable(): void {
		// tests/wordpress-mock.php intentionally does not define
		// woocommerce_store_api_register_endpoint_data(), mirroring a site
		// where WooCommerce Blocks/Store API isn't loaded; init() must not error.
		$this->expectNotToPerformAssertions();

		\HMFW_Store_Api::init();
	}
}
