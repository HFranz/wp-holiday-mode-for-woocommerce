<?php
/**
 * Unit tests for hmfw_maybe_flush_cache_on_settings_save(), verifying it
 * only flushes the cache when the Holiday Mode settings tab was actually
 * the one being saved.
 *
 * @package IPHolidayModeWooCommerce
 */

namespace Hfranz\WpHolidayModeForWoocommerce\Tests;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

#[CoversFunction( 'hmfw_maybe_flush_cache_on_settings_save' )]
class SettingsSaveCacheFlushTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		global $wp_filter;
		$wp_filter = array();
		unset( $_GET['tab'] );
	}

	protected function tearDown(): void {
		unset( $_GET['tab'] );
		parent::tearDown();
	}

	/**
	 * Track whether HMFW_Cache_Flusher::flush() actually ran, via the
	 * generic 'hmfw_flush_page_cache' action it always fires at the end.
	 *
	 * Uses a stdClass instead of an array/bool, so the mutation performed by
	 * the closure (bound by reference) is visible on the object returned
	 * here, regardless of PHP's copy-on-write semantics for arrays.
	 */
	private function trackFlushCalls(): \stdClass {
		$state         = new \stdClass();
		$state->called = false;

		\add_action(
			'hmfw_flush_page_cache',
			function () use ( $state ) {
				$state->called = true;
			}
		);

		return $state;
	}

	public function testDoesNotFlushWhenTabIsMissing(): void {
		$state = $this->trackFlushCalls();

		\hmfw_maybe_flush_cache_on_settings_save();

		$this->assertFalse( $state->called );
	}

	public function testDoesNotFlushWhenTabIsSomethingElse(): void {
		$_GET['tab'] = 'general';

		$state = $this->trackFlushCalls();

		\hmfw_maybe_flush_cache_on_settings_save();

		$this->assertFalse( $state->called );
	}

	public function testFlushesWhenHolidayModeTabWasSaved(): void {
		$_GET['tab'] = 'holiday_mode';

		$state = $this->trackFlushCalls();

		\hmfw_maybe_flush_cache_on_settings_save();

		$this->assertTrue( $state->called );
	}
}


