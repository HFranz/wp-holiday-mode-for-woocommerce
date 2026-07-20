<?php
/**
 * Flushes the page cache of common caching plugins.
 *
 * @package IPHolidayModeWooCommerce
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'HMFW_Cache_Flusher' ) ) :

	/**
	 * HMFW_Cache_Flusher class.
	 */
	class HMFW_Cache_Flusher {

		/**
		 * Flush the page cache of common caching plugins, so cached pages
		 * reflect the new Holiday Mode settings immediately instead of
		 * waiting for the cache to expire naturally. Each plugin is only
		 * touched if it is actually active, detected via its own public
		 * API function/class.
		 */
		public static function flush(): void {
			// W3 Total Cache.
			if ( function_exists( 'w3tc_flush_all' ) ) {
				w3tc_flush_all();
			}

			// WP Super Cache.
			if ( function_exists( 'wp_cache_clear_cache' ) ) {
				wp_cache_clear_cache();
			}

			// WP Rocket.
			if ( function_exists( 'rocket_clean_domain' ) ) {
				rocket_clean_domain();
			}

			// LiteSpeed Cache.
			if ( has_action( 'litespeed_purge_all' ) ) {
				do_action( 'litespeed_purge_all' );
			}

			// WP Fastest Cache.
			if ( function_exists( 'wpfc_clear_all_cache' ) ) {
				wpfc_clear_all_cache();
			}

			// Cache Enabler.
			if ( class_exists( 'Cache_Enabler' ) && method_exists( 'Cache_Enabler', 'clear_total_cache' ) ) {
				Cache_Enabler::clear_total_cache();
			}

			// Breeze.
			if ( function_exists( 'breeze_clear_all_cache' ) ) {
				breeze_clear_all_cache();
			}

			// WP-Optimize.
			if ( function_exists( 'WP_Optimize' ) ) {
				$wp_optimize = WP_Optimize();
				if ( is_object( $wp_optimize ) && method_exists( $wp_optimize, 'get_page_cache' ) ) {
					$wp_optimize->get_page_cache()->purge();
				}
			}

			// Hummingbird.
			if ( function_exists( 'wphb_clear_page_cache' ) ) {
				wphb_clear_page_cache();
			}

			// SiteGround Optimizer.
			if ( class_exists( '\SiteGround_Optimizer\Supercacher\Supercacher' ) ) {
				\SiteGround_Optimizer\Supercacher\Supercacher::purge_cache();
			}

			// Swift Performance.
			if ( class_exists( 'Swift_Performance_Cache' ) ) {
				Swift_Performance_Cache::clear_all_cache();
			}

			// WordPress core object cache (also used by persistent object
			// cache drop-ins such as Redis Object Cache or Memcached, which
			// many of the page-cache plugins above rely on internally).
			if ( function_exists( 'wp_cache_flush' ) ) {
				wp_cache_flush();
			}

			/**
			 * Fires after Holiday Mode tried to flush all known caching
			 * plugins, so other caching solutions not covered above can
			 * hook in as well.
			 */
			do_action( 'hmfw_flush_page_cache' );
		}
	}

endif;
