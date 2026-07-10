<?php
/**
 * Unit tests for HMFW_Cache_Flusher, verifying that every supported
 * third-party caching plugin is actually flushed when active.
 *
 * These stub functions/classes are defined in the global namespace (not the
 * test namespace) so HMFW_Cache_Flusher's function_exists()/class_exists()
 * checks - which always look in the global namespace - pick them up, exactly
 * like they would with the real plugins installed. The whole file runs in
 * separate processes so these globally defined stubs never leak into other
 * test classes.
 *
 * @package IPHolidayModeWooCommerce
 */

namespace {

	if ( ! function_exists( 'w3tc_flush_all' ) ) {
		function w3tc_flush_all(): void {
			global $hmfw_test_flush_calls;
			$hmfw_test_flush_calls[] = 'w3tc_flush_all';
		}
	}

	if ( ! function_exists( 'wp_cache_clear_cache' ) ) {
		function wp_cache_clear_cache(): void {
			global $hmfw_test_flush_calls;
			$hmfw_test_flush_calls[] = 'wp_cache_clear_cache';
		}
	}

	if ( ! function_exists( 'rocket_clean_domain' ) ) {
		function rocket_clean_domain(): void {
			global $hmfw_test_flush_calls;
			$hmfw_test_flush_calls[] = 'rocket_clean_domain';
		}
	}

	if ( ! function_exists( 'wpfc_clear_all_cache' ) ) {
		function wpfc_clear_all_cache(): void {
			global $hmfw_test_flush_calls;
			$hmfw_test_flush_calls[] = 'wpfc_clear_all_cache';
		}
	}

	if ( ! function_exists( 'breeze_clear_all_cache' ) ) {
		function breeze_clear_all_cache(): void {
			global $hmfw_test_flush_calls;
			$hmfw_test_flush_calls[] = 'breeze_clear_all_cache';
		}
	}

	if ( ! function_exists( 'wphb_clear_page_cache' ) ) {
		function wphb_clear_page_cache(): void {
			global $hmfw_test_flush_calls;
			$hmfw_test_flush_calls[] = 'wphb_clear_page_cache';
		}
	}

	if ( ! class_exists( 'Cache_Enabler' ) ) {
		class Cache_Enabler {
			public static function clear_total_cache(): void {
				global $hmfw_test_flush_calls;
				$hmfw_test_flush_calls[] = 'Cache_Enabler::clear_total_cache';
			}
		}
	}

	if ( ! class_exists( 'Swift_Performance_Cache' ) ) {
		class Swift_Performance_Cache {
			public static function clear_all_cache(): void {
				global $hmfw_test_flush_calls;
				$hmfw_test_flush_calls[] = 'Swift_Performance_Cache::clear_all_cache';
			}
		}
	}

	if ( ! class_exists( '\HMFW_Tests_WP_Optimize_Page_Cache' ) ) {
		class HMFW_Tests_WP_Optimize_Page_Cache {
			public function purge(): void {
				global $hmfw_test_flush_calls;
				$hmfw_test_flush_calls[] = 'WP_Optimize::get_page_cache()->purge';
			}
		}
	}

	if ( ! class_exists( '\HMFW_Tests_WP_Optimize' ) ) {
		class HMFW_Tests_WP_Optimize {
			public function get_page_cache(): HMFW_Tests_WP_Optimize_Page_Cache {
				return new HMFW_Tests_WP_Optimize_Page_Cache();
			}
		}
	}

	if ( ! function_exists( 'WP_Optimize' ) ) {
		function WP_Optimize(): HMFW_Tests_WP_Optimize {
			return new HMFW_Tests_WP_Optimize();
		}
	}
}

namespace SiteGround_Optimizer\Supercacher {
	if ( ! class_exists( __NAMESPACE__ . '\Supercacher' ) ) {
		class Supercacher {
			public static function purge_cache(): void {
				global $hmfw_test_flush_calls;
				$hmfw_test_flush_calls[] = 'SiteGround_Optimizer\Supercacher\Supercacher::purge_cache';
			}
		}
	}
}

namespace Hfranz\WpHolidayModeForWoocommerce\Tests {

	use PHPUnit\Framework\Attributes\CoversClass;
	use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
	use PHPUnit\Framework\TestCase;

	#[CoversClass( \HMFW_Cache_Flusher::class )]
	#[RunTestsInSeparateProcesses]
	class CacheFlusherWithPluginsTest extends TestCase {

		protected function setUp(): void {
			parent::setUp();

			global $wp_filter, $hmfw_test_flush_calls;
			$wp_filter             = array();
			$hmfw_test_flush_calls = array();
		}

		public function testFlushCallsEveryFunctionBasedCachePlugin(): void {
			\HMFW_Cache_Flusher::flush();

			global $hmfw_test_flush_calls;

			foreach (
				array(
					'w3tc_flush_all',
					'wp_cache_clear_cache',
					'rocket_clean_domain',
					'wpfc_clear_all_cache',
					'breeze_clear_all_cache',
					'wphb_clear_page_cache',
					'Cache_Enabler::clear_total_cache',
					'Swift_Performance_Cache::clear_all_cache',
					'WP_Optimize::get_page_cache()->purge',
					'SiteGround_Optimizer\Supercacher\Supercacher::purge_cache',
				) as $expected_call
			) {
				$this->assertContains(
					$expected_call,
					$hmfw_test_flush_calls,
					"Expected {$expected_call} to be called by HMFW_Cache_Flusher::flush()."
				);
			}
		}
	}
}

