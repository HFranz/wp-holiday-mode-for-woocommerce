<?php
/**
 * Unit tests for the HMFW_Settings class (WooCommerce settings page).
 *
 * @package IPHolidayModeWooCommerce
 */

namespace Hfranz\WpHolidayModeForWoocommerce\Tests;

use HMFW_Settings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use function add_filter;
use function apply_filters;
use function do_action;
use function has_action;

/**
 * Unit tests for the HMFW_Settings WooCommerce settings page.
 */
#[CoversClass( HMFW_Settings::class )]
class HMFWSettingsTest extends TestCase {

	/**
	 * Reset the global hook registry before each test.
	 */
	protected function setUp(): void {
		parent::setUp();

		global $wp_filter;
		$wp_filter = array();
	}

	/**
	 * Instantiate a fresh HMFW_Settings page for each test.
	 *
	 * @return HMFW_Settings
	 */
	private function createSettingsPage(): HMFW_Settings {
		return require dirname( __DIR__ ) . '/includes/class-hmfw-settings.php';
	}

	/**
	 * The settings page must expose the expected WooCommerce tab id/label.
	 */
	public function testIdAndLabelAreSet(): void {
		$page = $this->createSettingsPage();

		$this->assertSame( 'holiday_mode', $page->id );
		$this->assertSame( 'Holiday Mode', $page->label );
	}

	/**
	 * Instantiating the page must register it with WooCommerce's settings tabs.
	 */
	public function testSettingsPageIsRegisteredWithWooCommerceTabs(): void {
		$this->createSettingsPage();

		$tabs = apply_filters( 'woocommerce_settings_tabs_array', array() );

		$this->assertArrayHasKey( 'holiday_mode', $tabs );
		$this->assertSame( 'Holiday Mode', $tabs['holiday_mode'] );
	}

	/**
	 * get_settings() must expose every option id used elsewhere in the plugin.
	 */
	public function testGetSettingsContainsAllExpectedFieldIds(): void {
		$page     = $this->createSettingsPage();
		$settings = $page->get_settings();

		$ids = array_column( $settings, 'id' );

		$this->assertContains( 'hmfw_settings_title', $ids );
		$this->assertContains( 'hmfw_holiday_status', $ids );
		$this->assertContains( 'hmfw_holiday_startdate', $ids );
		$this->assertContains( 'hmfw_holiday_enddate', $ids );
		$this->assertContains( 'hmfw_holiday_message', $ids );
	}

	/**
	 * The settings array must be wrapped by a matching title/sectionend pair.
	 */
	public function testGetSettingsStartsWithTitleAndEndsWithSectionend(): void {
		$page     = $this->createSettingsPage();
		$settings = $page->get_settings();

		$this->assertSame( 'title', $settings[0]['type'] );
		$this->assertSame( 'sectionend', end( $settings )['type'] );
		$this->assertSame( $settings[0]['id'], end( $settings )['id'] );
	}

	/**
	 * All checkbox fields must default to 'no' (WooCommerce's yes/no convention).
	 */
	public function testCheckboxFieldsDefaultToNo(): void {
		$page     = $this->createSettingsPage();
		$settings = $page->get_settings();

		$checkboxes = array_filter( $settings, fn( $field ) => 'checkbox' === ( $field['type'] ?? '' ) );

		$this->assertNotEmpty( $checkboxes );

		foreach ( $checkboxes as $field ) {
			$this->assertSame( 'no', $field['default'] );
		}
	}

	/**
	 * The start/end date fields must render as HTML5 date inputs.
	 */
	public function testDateFieldsUseHtml5DateInput(): void {
		$page     = $this->createSettingsPage();
		$settings = $page->get_settings();

		$date_field_ids = array( 'hmfw_holiday_startdate', 'hmfw_holiday_enddate' );

		foreach ( $settings as $field ) {
			if ( in_array( $field['id'] ?? '', $date_field_ids, true ) ) {
				$this->assertSame( 'date', $field['type'] );
			}
		}
	}

	/**
	 * The vacation message field must be a textarea with the expected default text.
	 */
	public function testMessageFieldIsTextareaWithDefaultText(): void {
		$page     = $this->createSettingsPage();
		$settings = $page->get_settings();

		$message_field = current(
			array_filter( $settings, fn( $field ) => ( $field['id'] ?? '' ) === 'hmfw_holiday_message' )
		);

		$this->assertNotFalse( $message_field );
		$this->assertSame( 'textarea', $message_field['type'] );
		$this->assertSame( 'I am on vacation.', $message_field['default'] );
	}

	/**
	 * The settings array must be filterable via 'hmfw_holiday_mode_settings'.
	 */
	public function testGetSettingsIsFilterable(): void {
		$page = $this->createSettingsPage();

		add_filter(
			'hmfw_holiday_mode_settings',
			function ( $settings ) {
				$settings[] = array(
					'id'   => 'hmfw_test_extra_field',
					'type' => 'text',
				);
				return $settings;
			},
			10,
			2
		);

		$settings = $page->get_settings();
		$ids      = array_column( $settings, 'id' );

		$this->assertContains( 'hmfw_test_extra_field', $ids );
	}

	/**
	 * The current section argument must be passed through to the filter unchanged.
	 */
	public function testGetSettingsPassesCurrentSectionToFilter(): void {
		$page = $this->createSettingsPage();

		$received_section = null;
		add_filter(
			'hmfw_holiday_mode_settings',
			function ( $settings, $current_section ) use ( &$received_section ) {
				$received_section = $current_section;
				return $settings;
			},
			10,
			2
		);

		$page->get_settings( 'some-section' );

		$this->assertSame( 'some-section', $received_section );
	}

	/**
	 * Instantiating the page must register a rating notice callback on the
	 * 'woocommerce_after_settings_holiday_mode' action (fired below the Save button).
	 */
	public function testRatingNoticeIsRegisteredOnAfterSettingsHook(): void {
		$page = $this->createSettingsPage();

		$this->assertSame(
			10,
			has_action( 'woocommerce_after_settings_holiday_mode', array( $page, 'output_rating_notice' ) )
		);
	}

	/**
	 * output_rating_notice() must print an escaped rating request containing
	 * a link to the WordPress.org reviews page.
	 */
	public function testOutputRatingNoticePrintsFiveStarReviewLink(): void {
		$page = $this->createSettingsPage();

		ob_start();
		$page->output_rating_notice();
		$html = ob_get_clean();

		$this->assertStringContainsString( '<div', $html );
		$this->assertStringContainsString(
			'https://wordpress.org/support/plugin/holiday-mode-for-woocommerce/reviews/?rate=5#new-post',
			$html
		);
		$this->assertStringContainsString( 'target="_blank"', $html );
		$this->assertStringContainsString( 'rel="noopener noreferrer"', $html );
		$this->assertStringContainsString( 'Holiday Mode for WooCommerce', $html );
	}

	/**
	 * Triggering the 'woocommerce_after_settings_holiday_mode' action must
	 * invoke the registered rating notice callback (end-to-end hook wiring).
	 */
	public function testAfterSettingsActionTriggersRatingNoticeOutput(): void {
		$this->createSettingsPage();

		ob_start();
		do_action( 'woocommerce_after_settings_holiday_mode' );
		$html = ob_get_clean();

		$this->assertStringContainsString( 'wordpress.org/support/plugin/holiday-mode-for-woocommerce', $html );
	}
}
