<?php
/**
 * Holiday Mode settings page, hooked into WooCommerce > Settings.
 *
 * @package IPHolidayModeWooCommerce
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'HMFW_Settings' ) ) :

	/**
	 * HMFW_Settings class.
	 */
	class HMFW_Settings extends WC_Settings_Page {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id    = 'holiday_mode';
			$this->label = __( 'Holiday Mode', 'holiday-mode-woocommerce' );

			parent::__construct();

			add_action( 'woocommerce_after_settings_' . $this->id, array( $this, 'output_rating_notice' ) );
		}

		/**
		 * Get settings array for this section.
		 *
		 * @param string $current_section Current section slug (unused, single section only).
		 * @return array
		 */
		public function get_settings( $current_section = '' ): array {
			$settings = array(
				array(
					'title' => __( 'Holiday Mode Settings', 'holiday-mode-woocommerce' ),
					'type'  => 'title',
					'id'    => 'hmfw_settings_title',
					'desc'  => __( 'Set your WooCommerce® shop to holiday or vacation mode. Use a date range to schedule closed time.', 'holiday-mode-woocommerce' ),
				),
				array(
					'title'   => __( 'Activate', 'holiday-mode-woocommerce' ),
					'desc'    => __( 'Activate Holiday Mode', 'holiday-mode-woocommerce' ),
					'id'      => 'hmfw_holiday_status',
					'type'    => 'checkbox',
					'default' => 'no',
				),
				array(
					'title'    => __( 'Start of Holidays', 'holiday-mode-woocommerce' ),
					'desc_tip' => __( 'Enter first day of Holidays here.', 'holiday-mode-woocommerce' ),
					'id'       => 'hmfw_holiday_startdate',
					'type'     => 'date',
				),
				array(
					'title'    => __( 'End of Holidays', 'holiday-mode-woocommerce' ),
					'desc_tip' => __( 'Enter last day of Holidays here.', 'holiday-mode-woocommerce' ),
					'id'       => 'hmfw_holiday_enddate',
					'type'     => 'date',
				),
				array(
					'title'             => __( 'Vacation message', 'holiday-mode-woocommerce' ),
					'desc_tip'          => __( 'Enter your Holiday message here.', 'holiday-mode-woocommerce' ),
					'id'                => 'hmfw_holiday_message',
					'type'              => 'textarea',
					'css'               => 'width: 100%; height: 100px;',
					'default'           => __( 'I am on vacation.', 'holiday-mode-woocommerce' ),
					'sanitize_callback' => 'wp_kses_post',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'hmfw_settings_title',
				),
			);

			/**
			 * Filter the Holiday Mode settings fields.
			 *
			 * @param array  $settings        Settings fields.
			 * @param string $current_section Current section slug.
			 */
			return apply_filters( 'hmfw_holiday_mode_settings', $settings, $current_section );
		}

		/**
		 * Output a rating request notice below the Save button.
		 */
		public function output_rating_notice(): void {
			printf(
				'<div style="padding: 0 30px 40px;background: #f0f0f1;">%s</div>',
				wp_kses(
					sprintf(
						/* translators: %s: five-star rating link */
						__( 'If you like Holiday Mode for WooCommerce, please take a moment to leave us a %s rating. Thank you!', 'holiday-mode-woocommerce' ),
						'<a href="https://wordpress.org/support/plugin/holiday-mode-for-woocommerce/reviews/?rate=5#new-post" target="_blank" rel="noopener noreferrer">&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
					),
					array(
						'a' => array(
							'href'   => array(),
							'target' => array(),
							'rel'    => array(),
						),
					)
				)
			); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

endif;

return new HMFW_Settings();
