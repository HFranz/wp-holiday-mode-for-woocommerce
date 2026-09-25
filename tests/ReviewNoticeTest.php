<?php
/**
 * Unit tests for the review-request admin notice and its dismiss handler.
 *
 * @package IPHolidayModeWooCommerce
 */

namespace Hfranz\WpHolidayModeForWoocommerce\Tests;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use function get_option;
use function hmfw_maybe_dismiss_review_notice;
use function hmfw_maybe_review_notice;
use function hmfw_maybe_set_first_activation_time;
use function update_option;
use function wp_create_nonce;

#[CoversFunction( 'hmfw_maybe_review_notice' )]
#[CoversFunction( 'hmfw_maybe_dismiss_review_notice' )]
#[CoversFunction( 'hmfw_maybe_set_first_activation_time' )]
class ReviewNoticeTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		global $_test_options;
		$_test_options = array();

		unset( $_GET['hmfw-dismiss-review-notice'], $_GET['_wpnonce'] );
	}

	protected function tearDown(): void {
		unset( $_GET['hmfw-dismiss-review-notice'], $_GET['_wpnonce'] );

		parent::tearDown();
	}

	public function testFirstActivationTimeIsRecordedOnce(): void {
		hmfw_maybe_set_first_activation_time();
		$first = get_option( 'hmfw_first_activated_at' );

		hmfw_maybe_set_first_activation_time();
		$second = get_option( 'hmfw_first_activated_at' );

		$this->assertSame( $first, $second );
	}

	public function testReviewNoticeIsSilentWithoutFirstActivationTime(): void {
		ob_start();
		hmfw_maybe_review_notice();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	public function testReviewNoticeIsSilentBeforeDelayElapses(): void {
		update_option( 'hmfw_first_activated_at', time() );

		ob_start();
		hmfw_maybe_review_notice();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	public function testReviewNoticeIsPrintedAfterDelayElapses(): void {
		update_option( 'hmfw_first_activated_at', time() - ( 15 * DAY_IN_SECONDS ) );

		ob_start();
		hmfw_maybe_review_notice();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'notice-info', $output );
		$this->assertStringContainsString( 'wordpress.org/support/plugin/holiday-mode-for-woocommerce/reviews', $output );
		$this->assertStringContainsString( 'hmfw-dismiss-review-notice', $output );
	}

	public function testReviewNoticeIsSilentOnceDismissed(): void {
		update_option( 'hmfw_first_activated_at', time() - ( 15 * DAY_IN_SECONDS ) );
		update_option( 'hmfw_review_notice_dismissed', 'yes' );

		ob_start();
		hmfw_maybe_review_notice();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	public function testDismissHandlerDoesNothingWithoutQueryArgs(): void {
		$this->expectNotToPerformAssertions();
		hmfw_maybe_dismiss_review_notice();
	}

	public function testDismissHandlerIgnoresInvalidNonce(): void {
		$_GET['hmfw-dismiss-review-notice'] = '1';
		$_GET['_wpnonce']                   = 'not-a-valid-nonce';

		hmfw_maybe_dismiss_review_notice();

		$this->assertSame( 'no', get_option( 'hmfw_review_notice_dismissed', 'no' ) );
	}

	public function testDismissHandlerPersistsDismissalAndRedirects(): void {
		$_GET['hmfw-dismiss-review-notice'] = '1';
		$_GET['_wpnonce']                   = wp_create_nonce( 'hmfw_dismiss_review_notice' );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessageMatches( '/^wp_safe_redirect:/' );

		try {
			hmfw_maybe_dismiss_review_notice();
		} finally {
			$this->assertSame( 'yes', get_option( 'hmfw_review_notice_dismissed', 'no' ) );
		}
	}
}
