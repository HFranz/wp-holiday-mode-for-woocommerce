=== Plugin Name ===
Contributors: hfranz
Tags: woocommerce, holiday, customizer, calendar, vacation
Requires at least: 6.7
Tested up to: 6.8
Stable tag: 1.8.0
Requires PHP: 8.4
Donate link: https://impressive-pages.de/en/donate/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Set your WooCommerce® shop to holiday/vacation mode. Use date range to schedule closed time.

== Description ==

Set your WooCommerce® shop to holiday/vacation mode. Use date range to schedule closed time. You can use WooCommerce® store notice or separate message to display information to your customers.

*   Disable Orders (remove add to cart button and display message)
*   Disable Cart (items in active carts will be removed when activated holiday mode)
*   Disable Checkout
*   Display Custom Notification to your clients or use WooCommerce® store notice
*   Set up a Start and End date for your holidays
*   Automatically disable mode when vacation ends

WooCommerce® is a registered trademark of Automattic Inc.

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload `folder` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Open `Appearance -> Customize -> Holiday mode settings` to setup holiday mode

== Privacy Disclosure ==

This plugin does not store any personal data.

== Screenshots ==
Screenshot-1.jpg
Screenshot-2.jpg
Screenshot-3.jpg

== Changelog ==

= 1.7.1 =
* Compatibility for WordPress 5.9

= 1.7 =
* Add additional message on product page to increase theme compatibility (if woocommerce_before_main_content is not used by active theme)
* Settings are only visible if WooCommerce is activated within WordPress
* HTML is now possible for customer message
* Only provide vacation feature if WooCommerce is available
* Lowest PHP version is now 7.3, because lower versions reached EOL

= 1.6 =
* Fixed warning: Timezone is used correctly now

= 1.5 =
* Add further translation

= 1.4 =
* Fixed default translation language

= 1.3 =
* Add german language

= 1.2 =
* Fixed defined version, to allow updates

= 1.1 =
* Fixed settings: names are now more unique

= 1.0 =
* Initial version