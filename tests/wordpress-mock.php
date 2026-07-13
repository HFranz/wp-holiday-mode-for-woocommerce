<?php
/**
 * WordPress and WooCommerce core function/class mocks used by the PHPUnit test suite.
 *
 * This file provides minimal, behavior-compatible stand-ins for WordPress core
 * (and a few WooCommerce) functions/classes so the plugin's own code can be
 * unit tested without a full WordPress installation.
 *
 * A few coding-standard sniffs are deliberately disabled for this single file
 * only, because they would otherwise flag things that are inherent to its
 * purpose rather than real quality issues:
 *
 * - Squiz.Commenting.FunctionComment: every function here is a 1:1 mock of an
 *   already extensively documented WordPress core function (see
 *   developer.wordpress.org). Duplicating full @param/@return docblocks for
 *   ~70 core functions would only risk documentation drift without adding any
 *   value; the short one-line description above each mock is sufficient.
 * - Generic.CodeAnalysis.UnusedFunctionParameter /
 *   Universal.NamingConventions.NoReservedKeywordParameterNames: parameter
 *   names and signatures intentionally match the real WordPress core
 *   functions being mocked (e.g. `get_theme_mod( $name, $default = false )`),
 *   even where a specific mock does not need every parameter.
 * - WordPress.Security.EscapeOutput / WordPress.WP.AlternativeFunctions /
 *   WordPress.DateTime.RestrictedFunctions: several mocks (esc_html, esc_attr,
 *   wp_json_encode, wp_date, ...) exist specifically to implement the very
 *   functions these sniffs recommend using instead, or only ever echo static,
 *   developer-controlled test fixture strings (never real user input).
 * - WordPress.WP.GlobalVariablesOverride: the add_action()/add_filter() mocks
 *   intentionally mutate `$wp_filter`, mirroring exactly what WordPress core's
 *   real hook registry does internally.
 * - Universal.Files.SeparateFunctionsFromOO / Generic.Files.OneObjectStructurePerFile:
 *   this file intentionally bundles all core stubs (functions and the small
 *   WP_Role/WP_Roles/WP_Site/WP_Error mock classes) in one place, matching the
 *   single `wordpress-mock.php` bootstrap file referenced from phpunit.xml.
 *
 * @package IPHolidayModeWooCommerce
 */

// phpcs:disable Squiz.Commenting.FunctionComment
// phpcs:disable Squiz.Commenting.ClassComment
// phpcs:disable Squiz.Commenting.VariableComment
// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
// phpcs:disable Universal.NamingConventions.NoReservedKeywordParameterNames
// phpcs:disable WordPress.Security.EscapeOutput
// phpcs:disable WordPress.WP.AlternativeFunctions
// phpcs:disable WordPress.DateTime.RestrictedFunctions
// phpcs:disable WordPress.WP.GlobalVariablesOverride
// phpcs:disable Universal.Files.SeparateFunctionsFromOO
// phpcs:disable Generic.Files.OneObjectStructurePerFile

// Prevent direct execution.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/var/www/html/' );
}

// Global test variables.
global $_test_options, $_test_has_site_icon, $_test_site_icon_url,
		$_test_post_meta, $_test_bloginfo, $_test_home_url, $_test_current_screen, $wp_filter, $wp_roles, $_test_sites,
		$_test_is_multisite, $_test_is_main_site;

$_test_options        = array();
$_test_has_site_icon  = false;
$_test_site_icon_url  = '';
$_test_post_meta      = array();
$_test_bloginfo       = array( 'name' => 'Test Site' );
$_test_home_url       = 'http://example.com';
$_test_current_screen = null;
$wp_filter            = array();
$_test_sites          = array();
$_test_is_multisite   = false;
$_test_is_main_site   = true;

if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', '../../../wp-content' );
}

if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
	define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
}

// WordPress time constants.
if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 60 * MINUTE_IN_SECONDS );
}
if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 24 * HOUR_IN_SECONDS );
}
if ( ! defined( 'WEEK_IN_SECONDS' ) ) {
	define( 'WEEK_IN_SECONDS', 7 * DAY_IN_SECONDS );
}
if ( ! defined( 'MONTH_IN_SECONDS' ) ) {
	define( 'MONTH_IN_SECONDS', 30 * DAY_IN_SECONDS );
}
if ( ! defined( 'YEAR_IN_SECONDS' ) ) {
	define( 'YEAR_IN_SECONDS', 365 * DAY_IN_SECONDS );
}

$_SERVER['HTTP_HOST'] = 'example.com';

/**
 * Mock plugin_dir_path function
 *
 * @param  string $file File path.
 * @return string Directory path.
 */
if ( ! function_exists( 'plugin_dir_path' ) ) {
	function plugin_dir_path( $file ): string {
		return dirname( $file ) . '/';
	}
}

/**
 * Mock plugin_dir_url function
 *
 * @param  string $file File path.
 * @return string URL path.
 */
if ( ! function_exists( 'plugin_dir_url' ) ) {
	function plugin_dir_url( $file ): string {
		return 'http://example.com/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	/**
	 * Hooks a function on to a specific filter.
	 */
	function add_filter( string $tag, $function_to_add, int $priority = 10, int $accepted_args = 1 ): true {
		global $wp_filter;
		if ( ! isset( $wp_filter[ $tag ] ) ) {
			$wp_filter[ $tag ] = array();
		}
		$wp_filter[ $tag ][] = array(
			'function'      => $function_to_add,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
		return true;
	}
}

/**
 * Mock apply_filters function
 */
if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( string $tag, $value, ...$args ) {
		global $wp_filter;
		if ( empty( $wp_filter[ $tag ] ) ) {
			return $value;
		}

		$callbacks = $wp_filter[ $tag ];
		usort( $callbacks, fn( $a, $b ) => $a['priority'] <=> $b['priority'] );

		foreach ( $callbacks as $callback ) {
			$accepted_args = max( (int) ( $callback['accepted_args'] ?? 1 ), 1 );
			$all_args      = array_merge( array( $value ), $args );
			$value         = call_user_func_array( $callback['function'], array_slice( $all_args, 0, $accepted_args ) );
		}

		return $value;
	}
}

/**
 * Mock add_action function
 */
if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ): true {
		global $wp_filter;
		if ( ! isset( $wp_filter[ $hook ] ) ) {
			$wp_filter[ $hook ] = array();
		}
		$wp_filter[ $hook ][] = array(
			'function'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
		return true;
	}
}

/**
 * Mock remove_action function
 */
if ( ! function_exists( 'remove_action' ) ) {
	function remove_action( $hook, $callback, $priority = 10 ): bool {
		global $wp_filter;
		if ( empty( $wp_filter[ $hook ] ) ) {
			return false;
		}

		$found = false;
		foreach ( $wp_filter[ $hook ] as $key => $action ) {
			if ( $action['function'] === $callback && (int) $action['priority'] === (int) $priority ) {
				unset( $wp_filter[ $hook ][ $key ] );
				$found = true;
			}
		}

		return $found;
	}
}

/**
 * Mock add_menu_page function
 */
if ( ! function_exists( 'add_menu_page' ) ) {
	function add_menu_page(): true {
		return true;
	}
}

/**
 * Mock add_submenu_page function
 */
if ( ! function_exists( 'add_submenu_page' ) ) {
	function add_submenu_page(): true {
		return true;
	}
}

/**
 * Mock register_setting function
 */
if ( ! function_exists( 'register_setting' ) ) {
	function register_setting(): true {
		return true;
	}
}

/**
 * Mock add_settings_section function
 */
if ( ! function_exists( 'add_settings_section' ) ) {
	function add_settings_section(): true {
		return true;
	}
}

/**
 * Mock add_settings_field function
 */
if ( ! function_exists( 'add_settings_field' ) ) {
	function add_settings_field(): true {
		return true;
	}
}

/**
 * Mock get_option function
 */
if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default = false ) {
		global $_test_options;
		return $_test_options[ $option ] ?? $default;
	}
}

/**
 * Mock update_option function
 */
if ( ! function_exists( 'update_option' ) ) {
	function update_option( $option, $value ): bool {
		global $_test_options;
		$_test_options[ $option ] = $value;
		return true;
	}
}

/**
 * Mock delete_option function
 */
if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( $option ): bool {
		global $_test_options;
		unset( $_test_options[ $option ] );
		return true;
	}
}

/**
 * Mock admin_url function
 */
if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( $path = '' ): string {
		return 'http://example.com/wp-admin/' . ltrim( $path, '/' );
	}
}

/**
 * Mock esc_attr function
 */
if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

/**
 * Mock esc_html function
 */
if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

/**
 * Mock esc_url function
 */
if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( string $url ): string {
		return $url;
	}
}

/**
 * Mock esc_html__ function
 */
if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

/**
 * Mock esc_html_e function
 */
if ( ! function_exists( 'esc_html_e' ) ) {
	function esc_html_e( $text ): void {
		echo htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

/**
 * Mock esc_attr_e function
 */
if ( ! function_exists( 'esc_attr_e' ) ) {
	function esc_attr_e( $text ): void {
		echo htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

/**
 * Mock __() translation function
 */
if ( ! function_exists( '__' ) ) {
	function __( $text ) {
		return $text;
	}
}

/**
 * Mock _e() translation function
 */
if ( ! function_exists( '_e' ) ) {
	function _e( $text ): void {
		echo $text;
	}
}

/**
 * Mock current_user_can function
 */
if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can(): true {
		return true;
	}
}

/**
 * Mock has_site_icon function
 */
if ( ! function_exists( 'has_site_icon' ) ) {
	function has_site_icon(): bool {
		global $_test_has_site_icon;
		return $_test_has_site_icon;
	}
}

/**
 * Mock get_site_icon_url function
 */
if ( ! function_exists( 'get_site_icon_url' ) ) {
	function get_site_icon_url(): string {
		global $_test_site_icon_url;
		return $_test_site_icon_url;
	}
}

/**
 * Mock get_post_meta function
 */
if ( ! function_exists( 'get_post_meta' ) ) {
	function get_post_meta( $post_id, $key = '' ) {
		global $_test_post_meta;
		if ( isset( $_test_post_meta[ $post_id ][ $key ] ) ) {
			return $_test_post_meta[ $post_id ][ $key ];
		}
		return '';
	}
}

/**
 * Mock wp_basename function
 */
if ( ! function_exists( 'wp_basename' ) ) {
	function wp_basename( $path ): string {
		return basename( $path );
	}
}

/**
 * Mock bloginfo function
 */
if ( ! function_exists( 'bloginfo' ) ) {
	function bloginfo( $show = '' ): void {
		global $_test_bloginfo;
		if ( isset( $_test_bloginfo[ $show ] ) ) {
			echo $_test_bloginfo[ $show ];
		}
	}
}

/**
 * Mock form_option function
 */
if ( ! function_exists( 'form_option' ) ) {
	function form_option( $option ): void {
		echo esc_attr( get_option( $option ) );
	}
}

/**
 * Mock wp_enqueue_media function
 */
if ( ! function_exists( 'wp_enqueue_media' ) ) {
	function wp_enqueue_media(): true {
		return true;
	}
}

/**
 * Mock wp_enqueue_script function
 */
if ( ! function_exists( 'wp_enqueue_script' ) ) {
	function wp_enqueue_script(): true {
		return true;
	}
}

/**
 * Mock wp_enqueue_style function
 */
if ( ! function_exists( 'wp_enqueue_style' ) ) {
	function wp_enqueue_style(): true {
		return true;
	}
}

/**
 * Mock sanitize_text_field function
 */
if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ): string {
		return strip_tags( $str );
	}
}

/**
 * Mock sanitize_key function
 */
if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ): string {
		return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $key ) );
	}
}

/**
 * Mock absint function
 */
if ( ! function_exists( 'absint' ) ) {
	function absint( $maybeint ): float|int {
		return abs( (int) $maybeint );
	}
}

/**
 * Mock get_admin_page_title function
 */
if ( ! function_exists( 'get_admin_page_title' ) ) {
	function get_admin_page_title(): string {
		return 'LearnSuite Settings';
	}
}

/**
 * Mock settings_fields function
 */
if ( ! function_exists( 'settings_fields' ) ) {
	function settings_fields( $option_group ): void {
		echo '<input type="hidden" name="option_page" value="' . esc_attr( $option_group ) . '" />';
	}
}

/**
 * Mock do_settings_sections function
 */
if ( ! function_exists( 'do_settings_sections' ) ) {
	function do_settings_sections( $page ): void {
		echo '<div class="settings-section" data-page="' . esc_attr( $page ) . '"></div>';
	}
}

/**
 * Mock submit_button function
 */
if ( ! function_exists( 'submit_button' ) ) {
	function submit_button( $text = null, $type = 'primary', $name = 'submit' ): void {
		$text = ! empty( $text ) ? $text : 'Save Changes';
		echo '<input type="submit" name="' . esc_attr( $name ) . '" value="' . esc_attr( $text ) . '" class="button button-' . esc_attr( $type ) . '" />';
	}
}

/**
 * Mock wp_json_encode function
 */
if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $options = 0, $depth = 512 ): false|string {
		return json_encode( $data, $options, $depth );
	}
}


/**
 * Mock plugins_url function
 */
if ( ! function_exists( 'plugins_url' ) ) {
	function plugins_url( $path = '', $plugin = '' ): string {
		$plugin_basename = $plugin ? basename( dirname( $plugin ) ) : 'wp-learnsuite-admin-settings';
		return 'http://example.com/wp-content/plugins/' . $plugin_basename . '/' . ltrim( $path, '/' );
	}
}

/**
 * Mock wp_create_nonce function
 */
if ( ! function_exists( 'wp_create_nonce' ) ) {
	function wp_create_nonce( $action = -1 ): string {
		return md5( $action );
	}
}

/**
 * Mock trailingslashit function
 */
if ( ! function_exists( 'trailingslashit' ) ) {
	/**
	 * Adds a trailing slash to a string if it doesn't already have one.
	 *
	 * @param string $string The string to modify.
	 *
	 * @return string The modified string with a trailing slash.
	 */
	function trailingslashit( string $string ): string {
		return rtrim( $string, '/' ) . '/';
	}
}

/**
 * Mock network_home_url function
 */
if ( ! function_exists( 'network_home_url' ) ) {
	/**
	 * Mock network_home_url function
	 *
	 * @param string $path   Optional. Path relative to the network home URL.
	 * @param  string $scheme Optional. Scheme to use for the URL.
	 *
	 * @return string The network home URL.
	 */
	function network_home_url( string $path = '', string $scheme = 'https' ): string {
		$base_url = 'http://example.com';
		if ( 'https' === $scheme ) {
			$base_url = preg_replace( '/^http:/', 'https:', $base_url );
		}
		return rtrim( $base_url, '/' ) . '/' . ltrim( $path, '/' );
	}
}

/**
 * Mock do_action function
 */
if ( ! function_exists( 'do_action' ) ) {
	function do_action( string $tag, ...$args ): void {
		global $wp_filter;
		if ( empty( $wp_filter[ $tag ] ) ) {
			return;
		}

		$callbacks = $wp_filter[ $tag ];
		usort( $callbacks, fn( $a, $b ) => $a['priority'] <=> $b['priority'] );

		foreach ( $callbacks as $callback ) {
			$accepted_args = max( (int) ( $callback['accepted_args'] ?? 1 ), 1 );
			call_user_func_array( $callback['function'], array_slice( $args, 0, $accepted_args ) );
		}
	}
}

/**
 * Mock has_action function
 */
if ( ! function_exists( 'has_action' ) ) {
	function has_action( $hook, $callback = false ) {
		global $wp_filter;
		if ( ! isset( $wp_filter[ $hook ] ) ) {
			return false;
		}
		if ( false === $callback ) {
			return ! empty( $wp_filter[ $hook ] );
		}
		foreach ( $wp_filter[ $hook ] as $action ) {
			if ( $action['function'] === $callback ) {
				return $action['priority'];
			}
		}
		return false;
	}
}

/**
 * Mock has_filter function
 */
if ( ! function_exists( 'has_filter' ) ) {
	function has_filter( $hook, $callback = false ) {
		global $wp_filter;
		if ( ! isset( $wp_filter[ $hook ] ) ) {
			return false;
		}
		if ( false === $callback ) {
			return ! empty( $wp_filter[ $hook ] );
		}
		foreach ( $wp_filter[ $hook ] as $filter ) {
			if ( $filter['function'] === $callback ) {
				return $filter['priority'];
			}
		}
		return false;
	}
}

/**
 * Mock WP_Upgrader class, just enough of a stand-in so code that type-hints
 * against it (e.g. 'upgrader_process_complete' callbacks) can be unit tested.
 */
if ( ! class_exists( 'WP_Upgrader' ) ) {
	class WP_Upgrader {
	}
}

/**
 * Mock WP_Role class
 */
if ( ! class_exists( 'WP_Role' ) ) {
	class WP_Role {

		public $name;
		public mixed $capabilities = array();

		public function __construct( $name, $capabilities = array() ) {
			$this->name         = $name;
			$this->capabilities = $capabilities;
		}
	}
}

/**
 * Mock WP_Roles class
 */
if ( ! class_exists( 'WP_Roles' ) ) {
	class WP_Roles {

		public array $role_objects = array();

		public function __construct() {
			$this->init_roles();
		}

		private function init_roles(): void {
			$this->role_objects['administrator']  = new WP_Role(
				'administrator',
				array(
					'manage_options' => true,
				)
			);
			$this->role_objects['wdm_instructor'] = new WP_Role( 'wdm_instructor', array() );
		}

		public function get_role( $role ) {
			return $this->role_objects[ $role ] ?? null;
		}
	}
}

/**
 * Mock WP_Site class
 */
if ( ! class_exists( 'WP_Site' ) ) {
	class WP_Site {

		/**
		 * Blog ID.
		 *
		 * @var int
		 */
		public int $blog_id = 0;

		/**
		 * Constructor.
		 *
		 * @param object|array|int $site Site data or blog ID.
		 */
		public function __construct( object|array|int $site = 0 ) {
			if ( is_numeric( $site ) ) {
				$this->blog_id = (int) $site;
				return;
			}

			if ( is_array( $site ) ) {
				$site = (object) $site;
			}

			if ( is_object( $site ) ) {
				$this->blog_id = isset( $site->blog_id ) ? (int) $site->blog_id : 0;

				// Optional: weitere Felder aus Testdaten uebernehmen.
				foreach ( get_object_vars( $site ) as $key => $value ) {
					$this->$key = $value;
				}
			}
		}
	}
}

// Initialize global $wp_roles.
if ( ! isset( $wp_roles ) ) {
	$wp_roles = new WP_Roles();
}

/**
 * Mock get_role function
 */
if ( ! function_exists( 'get_role' ) ) {
	function get_role( $role ) {
		global $wp_roles;
		return $wp_roles->get_role( $role );
	}
}

/**
 * Mock get_sites function
 */
if ( ! function_exists( 'get_sites' ) ) {
	function get_sites(): array {
		global $_test_sites;

		// Wenn keine Test-Sites definiert sind, Standard-Site zurückgeben.
		if ( empty( $_test_sites ) ) {
			$default_site               = new stdClass();
			$default_site->blog_id      = 1;
			$default_site->domain       = 'example.com';
			$default_site->path         = '/';
			$default_site->site_id      = 1;
			$default_site->registered   = '2024-01-01 00:00:00';
			$default_site->last_updated = '2024-01-01 00:00:00';
			$default_site->public       = 1;
			$default_site->archived     = 0;
			$default_site->mature       = 0;
			$default_site->spam         = 0;
			$default_site->deleted      = 0;
			$default_site->lang_id      = 0;
			return array( $default_site );
		}

		return $_test_sites;
	}
}

/**
 * Mock update_site_option function
 */
if ( ! function_exists( 'update_site_option' ) ) {
	function update_site_option( $option, $value ): bool {
		global $_test_options;
		$_test_options[ 'site_' . $option ] = $value;
		return true;
	}
}

/**
 * Mock delete_site_option function
 */
if ( ! function_exists( 'delete_site_option' ) ) {
	function delete_site_option( $option ): bool {
		global $_test_options;
		unset( $_test_options[ 'site_' . $option ] );
		return true;
	}
}

/**
 * Mock current_time function
 */
if ( ! function_exists( 'current_time' ) ) {
	function current_time( $type = 'timestamp' ): int|string {
		if ( 'mysql' === $type ) {
			return date( 'Y-m-d H:i:s' );
		}
		return time();
	}
}

/**
 * Mock get_site_url function
 */
if ( ! function_exists( 'get_site_url' ) ) {
	function get_site_url(): string {
		return 'http://example.com';
	}
}

/**
 * Mock WP_Error class
 */
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {

		private string $code;
		private string $message;

		public function __construct( string $code = '', string $message = '' ) {
			$this->code    = $code;
			$this->message = $message;
		}
	}
}

/**
 * Mock wp_unslash function
 */
if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ): array|string {
		return is_array( $value ) ? array_map( 'wp_unslash', $value ) : stripslashes( $value );
	}
}

/**
 * Mock wp_verify_nonce function
 */
if ( ! function_exists( 'wp_verify_nonce' ) ) {
	function wp_verify_nonce(): bool {
		return false;
	}
}

/**
 * Mock wp_nonce_field function
 */
if ( ! function_exists( 'wp_nonce_field' ) ) {
	function wp_nonce_field( $action = -1, $name = '_wpnonce', $echo = true ): string {
		$field = '<input type="hidden" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . wp_create_nonce( $action ) . '" />';
		if ( $echo ) {
			echo $field;
		}
		return $field;
	}
}

/**
 * Mock checked function
 */
if ( ! function_exists( 'checked' ) ) {
	function checked( $checked, $current = true, $echo = true ): string {
		$result = ( (string) $checked === (string) $current ) ? ' checked' : '';
		if ( $echo ) {
			echo $result;
		}
		return $result;
	}
}

/**
 * Mock wp_die function
 */
if ( ! function_exists( 'wp_die' ) ) {
	function wp_die( $message = '' ): void {
		throw new RuntimeException( is_string( $message ) ? $message : 'wp_die called' );
	}
}

/**
 * Mock register_activation_hook function
 */
if ( ! function_exists( 'register_activation_hook' ) ) {
	function register_activation_hook( $file, $callback ): void {
	}
}

/**
 * Mock register_deactivation_hook function
 */
if ( ! function_exists( 'register_deactivation_hook' ) ) {
	function register_deactivation_hook( $file, $callback ): void {
	}
}

/**
 * Mock register_uninstall_hook function
 */
if ( ! function_exists( 'register_uninstall_hook' ) ) {
	function register_uninstall_hook( $file, $callback ): void {
	}
}

/**
 * Mock plugin_basename function
 */
if ( ! function_exists( 'plugin_basename' ) ) {
	function plugin_basename( $file ): string {
		return basename( dirname( $file ) ) . '/' . basename( $file );
	}
}

/**
 * Mock load_plugin_textdomain function
 */
if ( ! function_exists( 'load_plugin_textdomain' ) ) {
	function load_plugin_textdomain( $domain, $deprecated = false, $plugin_rel_path = false ): bool {
		return true;
	}
}

/**
 * Mock wp_clear_scheduled_hook function.
 *
 * NOTE: wp_next_scheduled, wp_schedule_event, wp_unschedule_event, and wp_mail
 * are intentionally NOT pre-defined here so that WP_Mock::userFunction() can
 * intercept them with test-specific behavior in individual test cases.
 */
if ( ! function_exists( 'wp_clear_scheduled_hook' ) ) {
	function wp_clear_scheduled_hook(): int {
		return 0;
	}
}


/**
 * Mock wp_kses function
 */
if ( ! function_exists( 'wp_kses' ) ) {
	function wp_kses( $string, $allowed_html ): string {
		if ( ! is_array( $allowed_html ) || empty( $allowed_html ) ) {
			return strip_tags( $string );
		}

		return preg_replace_callback(
			'/<\/?([a-zA-Z][a-zA-Z0-9]*)\b[^>]*>/i',
			function ( $matches ) use ( $allowed_html ) {
				$tag = strtolower( $matches[1] );
				if ( ! array_key_exists( $tag, $allowed_html ) ) {
					return '';
				}
				return $matches[0];
			},
			$string
		);
	}
}

/**
 * Mock get_bloginfo function
 */
if ( ! function_exists( 'get_bloginfo' ) ) {
	function get_bloginfo( $show = '' ): string {
		global $_test_bloginfo;
		return $_test_bloginfo[ $show ] ?? '';
	}
}

/**
 * Mock is_multisite function
 */
if ( ! function_exists( 'is_multisite' ) ) {
	function is_multisite(): bool {
		global $_test_is_multisite;
		return $_test_is_multisite;
	}
}

/**
 * Mock is_main_site function
 */
if ( ! function_exists( 'is_main_site' ) ) {
	function is_main_site(): bool {
		global $_test_is_main_site;
		return $_test_is_main_site;
	}
}

/**
 * Mock wp_date function
 */
if ( ! function_exists( 'wp_date' ) ) {
	function wp_date( string $format, $timestamp = null ): string|false {
		if ( null === $timestamp ) {
			$timestamp = time();
		}
		return date( $format, $timestamp );
	}
}

/**
 * Mock wp_timezone function
 */
if ( ! function_exists( 'wp_timezone' ) ) {
	function wp_timezone(): DateTimeZone {
		return new DateTimeZone( 'UTC' );
	}
}

/**
 * Mock wp_kses_post function
 */
if ( ! function_exists( 'wp_kses_post' ) ) {
	function wp_kses_post( $string ): string {
		return wp_kses( $string, array() );
	}
}

/**
 * Mock wp_strip_all_tags function
 */
if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $string, $remove_breaks = false ): string {
		$string = strip_tags( $string );
		if ( $remove_breaks ) {
			$string = preg_replace( '/[\r\n\t ]+/', ' ', $string );
		}
		return trim( $string );
	}
}

/**
 * Mock theme mod storage & functions
 */
global $_test_theme_mods;
$_test_theme_mods = array();

if ( ! function_exists( 'get_theme_mod' ) ) {
	function get_theme_mod( $name, $default = false ) {
		global $_test_theme_mods;
		return $_test_theme_mods[ $name ] ?? $default;
	}
}

if ( ! function_exists( 'set_theme_mod' ) ) {
	function set_theme_mod( $name, $value ): void {
		global $_test_theme_mods;
		$_test_theme_mods[ $name ] = $value;
	}
}

if ( ! function_exists( 'remove_theme_mod' ) ) {
	function remove_theme_mod( $name ): void {
		global $_test_theme_mods;
		unset( $_test_theme_mods[ $name ] );
	}
}

/**
 * Mock wc_print_notice function
 */
if ( ! function_exists( 'wc_print_notice' ) ) {
	function wc_print_notice( $message, $notice_type = 'success' ): void {
		echo '<div class="woocommerce-' . esc_attr( $notice_type ) . '">' . $message . '</div>';
	}
}

/**
 * Mock is_product function
 */
if ( ! function_exists( 'is_product' ) ) {
	function is_product(): bool {
		global $_test_conditional_tags;
		return ! empty( $_test_conditional_tags['is_product'] );
	}
}

/**
 * Mock WooCommerce conditional tag functions used by the block-theme
 * wp_footer fallback. Controllable via the global $_test_conditional_tags
 * array so tests can simulate being on a given page type.
 */
if ( ! function_exists( 'is_shop' ) ) {
	function is_shop(): bool {
		global $_test_conditional_tags;
		return ! empty( $_test_conditional_tags['is_shop'] );
	}
}

if ( ! function_exists( 'is_product_taxonomy' ) ) {
	function is_product_taxonomy(): bool {
		global $_test_conditional_tags;
		return ! empty( $_test_conditional_tags['is_product_taxonomy'] );
	}
}

if ( ! function_exists( 'is_cart' ) ) {
	function is_cart(): bool {
		global $_test_conditional_tags;
		return ! empty( $_test_conditional_tags['is_cart'] );
	}
}

if ( ! function_exists( 'is_checkout' ) ) {
	function is_checkout(): bool {
		global $_test_conditional_tags;
		return ! empty( $_test_conditional_tags['is_checkout'] );
	}
}

/**
 * Mock wp_is_block_theme function. Controllable via the global
 * $_test_is_block_theme so tests can simulate classic vs. block themes.
 * Defaults to false (classic theme) to match the existing test suite.
 */
if ( ! function_exists( 'wp_is_block_theme' ) ) {
	function wp_is_block_theme(): bool {
		global $_test_is_block_theme;
		return ! empty( $_test_is_block_theme );
	}
}


/**
 * Mock wp_cache_flush function. Counts calls in $_test_wp_cache_flush_count
 * so tests can assert HMFW_Cache_Flusher::flush() actually flushed the
 * WordPress core object cache.
 */
if ( ! function_exists( 'wp_cache_flush' ) ) {
	function wp_cache_flush(): bool {
		global $_test_wp_cache_flush_count;
		$_test_wp_cache_flush_count = ( $_test_wp_cache_flush_count ?? 0 ) + 1;
		return true;
	}
}

