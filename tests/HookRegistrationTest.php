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

#[RunTestsInSeparateProcesses]
class HookRegistrationTest extends TestCase {

	public function testMigrationIsHookedOnInitBeforeHolidayModeActivation(): void {
		global $wp_filter;

		$this->assertArrayHasKey( 'init', $wp_filter, 'Nothing is hooked into init at all.' );

		$migrate_priority = $this->findPriority( $wp_filter['init'], 'hmfw_migrate_customizer_settings' );
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
	 * @param array<int, array{function: mixed, priority: int}> $callbacks
	 */
	private function findPriority( array $callbacks, string $function_name ): ?int {
		foreach ( $callbacks as $callback ) {
			if ( $callback['function'] === $function_name ) {
				return $callback['priority'];
			}
		}

		return null;
	}
}

