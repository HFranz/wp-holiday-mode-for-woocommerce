<?php
/**
 * Verifies that the plugin's top-level hook registrations are wired up
 * correctly, in a fresh PHP process so the global $wp_filter reflects
 * exactly what happens when WordPress loads the plugin for real - without
 * interference from other tests that reset/populate $wp_filter.
 *
 * @package IPHolidayModeWooCommerce
 */

namespace Hfranz\WpHolidayModeForWoocommerce\Tests;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * Hook registration order tests, run in isolated processes.
 */
#[RunTestsInSeparateProcesses]
class HookRegistrationTest extends TestCase {

	/**
	 * The Customizer migration must be registered on 'init' with a lower
	 * priority than the holiday-mode activation check, so migrated options
	 * are already available the first time the shop is activated.
	 */
	public function testMigrationIsHookedOnInitBeforeHolidayModeActivation(): void {
		global $wp_filter;

		$this->assertArrayHasKey( 'init', $wp_filter, 'Nothing is hooked into init at all.' );

		$migrate_priority  = $this->findPriority( $wp_filter['init'], 'hmfw_migrate_customizer_settings' );
		$activate_priority = $this->findPriority( $wp_filter['init'], 'hmfw_woocommerce_holiday_mode' );

		$this->assertNotNull( $migrate_priority, 'hmfw_migrate_customizer_settings is not hooked into init.' );
		$this->assertNotNull( $activate_priority, 'hmfw_woocommerce_holiday_mode is not hooked into init.' );

		$this->assertLessThan(
			$activate_priority,
			$migrate_priority,
			'The Customizer migration must run before the holiday-mode activation check on the same init hook, ' .
			'otherwise a shop that was already active via the Customizer would appear inactive on the very ' .
			'first request after the update.'
		);
	}

	/**
	 * The Store API extension data (holiday notice + purchasing state for
	 * headless frontends and the Cart/Checkout blocks) must be registered on
	 * 'woocommerce_blocks_loaded', the hook WooCommerce Blocks documents for
	 * extending Store API schemas.
	 */
	public function testStoreApiExtensionIsHookedOnBlocksLoaded(): void {
		global $wp_filter;

		$this->assertArrayHasKey( 'woocommerce_blocks_loaded', $wp_filter, 'Nothing is hooked into woocommerce_blocks_loaded at all.' );

		$priority = $this->findPriority( $wp_filter['woocommerce_blocks_loaded'], array( 'HMFW_Store_Api', 'init' ) );

		$this->assertNotNull( $priority, 'HMFW_Store_Api::init() is not hooked into woocommerce_blocks_loaded.' );
	}

	/**
	 * Find the registered priority of a callback for a given hook.
	 *
	 * @param array<int, array{function: mixed, priority: int}> $callbacks Registered callbacks for a hook.
	 * @param callable-string|array                             $function_name Name (or [class, method] callback) to find.
	 * @return int|null The registered priority, or null if not found.
	 */
	private function findPriority( array $callbacks, string|array $function_name ): ?int {
		foreach ( $callbacks as $callback ) {
			if ( $callback['function'] === $function_name ) {
				return $callback['priority'];
			}
		}

		return null;
	}
}
