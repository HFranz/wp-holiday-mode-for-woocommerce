<?php
/**
 * Unit tests for HMFW_Cache_Flusher, covering the scenario where no
 * supported caching plugin is active, and the generic extensibility hook.
 *
 * @package IPHolidayModeWooCommerce
 */

namespace Hfranz\WpHolidayModeForWoocommerce\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass( \HMFW_Cache_Flusher::class )]
class CacheFlusherTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		global $wp_filter, $_test_wp_cache_flush_count;
		$wp_filter                  = array();
		$_test_wp_cache_flush_count = 0;
	}

	/**
	 * None of the supported third-party cache plugins are loaded in the test
	 * environment, so flush() must simply do nothing (no fatal errors from
	 * calling undefined functions/classes) instead of throwing.
	 */
	public function testFlushDoesNotThrowWhenNoCachePluginIsActive(): void {
		$this->expectNotToPerformAssertions();

		\HMFW_Cache_Flusher::flush();
	}

	/**
	 * flush() must fire a generic 'hmfw_flush_page_cache' action after trying
	 * all built-in integrations, so caching solutions not covered out of the
	 * box can still hook in and flush themselves.
	 */
	public function testFlushFiresGenericExtensibilityAction(): void {
		$called = false;
		\add_action(
			'hmfw_flush_page_cache',
			function () use ( &$called ) {
				$called = true;
			}
		);

		\HMFW_Cache_Flusher::flush();

		$this->assertTrue( $called, 'hmfw_flush_page_cache action was not fired by HMFW_Cache_Flusher::flush().' );
	}

	/**
	 * The WordPress core object cache (wp_cache_flush()) must always be
	 * flushed, independent of which (if any) third-party page-cache plugin
	 * is active, since persistent object cache drop-ins (Redis, Memcached,
	 * ...) are used by several of those plugins internally.
	 */
	public function testFlushFlushesTheWordPressObjectCache(): void {
		global $_test_wp_cache_flush_count;

		\HMFW_Cache_Flusher::flush();

		$this->assertSame( 1, $_test_wp_cache_flush_count, 'wp_cache_flush() was not called by HMFW_Cache_Flusher::flush().' );
	}

	/**
	 * LiteSpeed Cache integrates via its own 'litespeed_purge_all' action
	 * rather than a function/class, so it must only be triggered when
	 * something is actually hooked into it.
	 */
	public function testFlushTriggersLiteSpeedPurgeActionWhenRegistered(): void {
		$called = false;
		\add_action(
			'litespeed_purge_all',
			function () use ( &$called ) {
				$called = true;
			}
		);

		\HMFW_Cache_Flusher::flush();

		$this->assertTrue( $called, 'litespeed_purge_all was not triggered even though something was hooked into it.' );
	}
}
