<?php
/**
 * Exposes Holiday Mode state through the WooCommerce Store API.
 *
 * The Cart/Checkout blocks and fully headless/decoupled frontends never fire
 * the classic template hooks (woocommerce_before_main_content, ...) or
 * wp_body_open that hmfw_wc_shop_disabled() in the main plugin file relies
 * on to print the holiday notice - a decoupled frontend only ever talks to
 * the Store API. Blocking purchasing itself already works there for free,
 * since the Store API's own Cart/Checkout validation checks
 * WC_Product::is_purchasable(), which the woocommerce_is_purchasable filter
 * added in hmfw_woocommerce_holiday_mode() already forces to false. What is
 * missing without this class is the merchant's custom absence message and an
 * explicit "is Holiday Mode active" flag for such a frontend to display.
 *
 * @package IPHolidayModeWooCommerce
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'HMFW_Store_Api' ) ) :

	/**
	 * HMFW_Store_Api class.
	 */
	class HMFW_Store_Api {

		/**
		 * Register the "holiday-mode-woocommerce" extension data on the Store
		 * API Cart and Checkout endpoints, so it appears under `extensions.
		 * holiday-mode-woocommerce` in their JSON responses. Hooked into
		 * 'woocommerce_blocks_loaded', the hook WooCommerce Blocks documents
		 * for registering Store API schema extensions.
		 */
		public static function init(): void {
			if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
				return;
			}

			foreach ( array( 'cart', 'checkout' ) as $endpoint ) {
				woocommerce_store_api_register_endpoint_data(
					array(
						'endpoint'        => $endpoint,
						'namespace'       => 'holiday-mode-woocommerce',
						'data_callback'   => array( __CLASS__, 'get_data' ),
						'schema_callback' => array( __CLASS__, 'get_schema' ),
					)
				);
			}
		}

		/**
		 * Build the Holiday Mode data exposed under `extensions.holiday-mode-woocommerce`
		 * in the Cart and Checkout Store API responses.
		 *
		 * @return array{active: bool, purchasing_disabled: bool, message: string, notice_type: string}
		 */
		public static function get_data(): array {
			$active = hmfw_is_holiday_mode_active();

			return array(
				'active'              => $active,
				'purchasing_disabled' => $active && 'yes' === get_option( 'hmfw_disable_purchasing', 'yes' ),
				'message'             => $active ? wp_kses_post( get_option( 'hmfw_holiday_message', '' ) ) : '',
				'notice_type'         => get_option( 'hmfw_holiday_notice_type', 'error' ),
			);
		}

		/**
		 * Describe the shape of get_data() for the Store API's schema/OpenAPI docs.
		 *
		 * @return array
		 */
		public static function get_schema(): array {
			return array(
				'active'              => array(
					'description' => __( 'Whether Holiday Mode is currently active.', 'holiday-mode-woocommerce' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'purchasing_disabled' => array(
					'description' => __( 'Whether Holiday Mode is currently blocking new orders.', 'holiday-mode-woocommerce' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'message'             => array(
					'description' => __( 'The holiday absence message to display to shoppers. Empty unless Holiday Mode is active.', 'holiday-mode-woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'notice_type'         => array(
					'description' => __( 'Visual style of the holiday notice: "error" (red) or "notice" (blue).', 'holiday-mode-woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			);
		}
	}

endif;
