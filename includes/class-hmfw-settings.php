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
		 * Persist submitted settings, but refuse to activate Holiday Mode
		 * while its date range or vacation message is empty/invalid.
		 */
		public function save(): void {
			$this->validate_activation_fields();

			parent::save();
		}

		/**
		 * Validate the Holiday Mode date range and vacation message whenever
		 * the merchant tries to (re-)activate Holiday Mode. Shows admin
		 * errors and forces the status back to "no" if anything is missing
		 * or invalid, so Holiday Mode never goes live in a broken state.
		 */
		private function validate_activation_fields(): void {
			if ( ! isset( $_POST['hmfw_holiday_status'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return;
			}

			$errors = array();

			$start = isset( $_POST['hmfw_holiday_startdate'] ) ? sanitize_text_field( wp_unslash( $_POST['hmfw_holiday_startdate'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$end   = isset( $_POST['hmfw_holiday_enddate'] ) ? sanitize_text_field( wp_unslash( $_POST['hmfw_holiday_enddate'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( ! $this->is_valid_date( $start ) ) {
				$errors[] = __( 'Please enter a valid start date to activate Holiday Mode.', 'holiday-mode-woocommerce' );
			}

			if ( ! $this->is_valid_date( $end ) ) {
				$errors[] = __( 'Please enter a valid end date to activate Holiday Mode.', 'holiday-mode-woocommerce' );
			} elseif ( $this->is_valid_date( $start ) && strtotime( $end ) < strtotime( $start ) ) {
				$errors[] = __( 'The end date of Holiday Mode must not be before the start date.', 'holiday-mode-woocommerce' );
			}

			$message = isset( $_POST['hmfw_holiday_message'] ) ? wp_kses_post( wp_unslash( $_POST['hmfw_holiday_message'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( '' === trim( wp_strip_all_tags( $message ) ) ) {
				$errors[] = __( 'Please enter a vacation message to activate Holiday Mode.', 'holiday-mode-woocommerce' );
			}

			if ( empty( $errors ) ) {
				return;
			}

			foreach ( $errors as $error ) {
				WC_Admin_Settings::add_error( $error );
			}

			// Keep Holiday Mode disabled while its configuration is invalid.
			unset( $_POST['hmfw_holiday_status'] );
		}

		/**
		 * Check whether a given date string is non-empty and represents a real date.
		 *
		 * @param string $date Date string to validate.
		 * @return bool True if the string is a non-empty, parseable date.
		 */
		private function is_valid_date( string $date ): bool {
			if ( '' === $date ) {
				return false;
			}

			try {
				new DateTime( $date, wp_timezone() );
			} catch ( Exception $e ) {
				return false;
			}

			return false !== strtotime( $date );
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
