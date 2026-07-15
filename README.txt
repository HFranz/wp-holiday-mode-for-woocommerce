=== Holiday Mode for WooCommerce ===
Contributors: hfranz
Tags: woocommerce, holiday, settings, calendar, vacation
Requires at least: 6.7
Tested up to: 7.0
Stable tag: 2.5.0
Requires PHP: 8.0
Donate link: https://sevmatic.com/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Set your WooCommerce® shop to holiday mode. Schedule closures and keep customers informed automatically.

== Description ==

WooCommerce® Holiday Mode allows you to temporarily pause your online store during holidays, vacations, maintenance periods, or other planned breaks.

Define a start and end date for your closed period and automatically enable or disable holiday mode. During this time, you can display a prominently visible custom absence message to inform your customers about your temporary closure, return date, or other important information.

Features:

- **Disable new orders** by removing the add-to-cart button and displaying a custom message
- **Disable the shopping cart** (existing cart items will be removed when holiday mode is activated)
- **Disable checkout** during the holiday period
- **Display a prominent custom absence message** to inform customers about your temporary closure
- **Schedule holiday mode with a specific start and end date**
- **Automatically deactivate holiday mode when the vacation period ends**

WooCommerce® is a registered trademark of Automattic Inc.

== Installation ==

Follow these steps to install and configure the plugin:

Upload the plugin folder to the /wp-content/plugins/ directory
Activate the plugin through the "Plugins" menu in WordPress
Go to WooCommerce -> Settings -> Holiday Mode to configure your settings

== Privacy Disclosure ==

This plugin does not collect, process, or store any personal data.

== Screenshots ==
screenshot-1.jpg
screenshot-2.jpg
screenshot-3.jpg
screenshot-4.jpg

== Changelog ==

= 2.5.0 =
* Lower the minimum required PHP version to 8.0 to support websites still running PHP 8.0–8.3.
* New: Added the option to select the color and icon for the absence message.

= 2.4.0 =
* New: Settings are now validated on save - Holiday Mode can no longer be activated with a missing/invalid date range or an empty vacation message.
* New: "Disable purchasing" setting lets you choose whether Holiday Mode blocks purchases (removes add-to-cart) or only shows the holiday notice. Enabled by default.

= 2.3.0 =
* Lower minimum required PHP version to 8.3 to allow installation on WordPress 6.7 and higher.

= 2.2.0 =
* Fixed: Holiday notice could appear below the archive title/result count instead of at the very top of the page on block themes (e.g. Twenty Twenty-Five) using WooCommerce's default block-based Shop template.

= 2.1.0 =
* Fixed: Holiday mode now also removes the "Add to cart" / "Buy product" button for external/affiliate and grouped products.

= 2.0.0 =
* Enhanced: Holiday mode now explicitly removes the "Add to cart" button for variable products.

= 1.9.3 =
* Fixed: Plugin was incorrectly deactivated after upgrading from a previous active version.

= 1.9.2 =
* Security: Added improved escaping for holiday notice messages to strengthen protection against XSS vulnerabilities.
* Maintenance: Refactored internal code and cleaned up various areas of the plugin.

= 1.9.1 =
* Fixed: Holiday notice was not displayed on single product pages in some classic themes.
* Fixed: Holiday notice was missing on shop, archive, cart, and checkout pages in some block theme setups. The notice is now displayed correctly in these cases.

= 1.9.0 =
* Improved compatibility with caching plugins.
* Replaced the start and end date text fields with date pickers to improve usability.

= 1.8.3 =
Minor translations update

= 1.8.2 =
Update plugins meta data

= 1.8.1 =
Update README.txt

= 1.8.0 =
* Settings migrated from the Customizer to a dedicated page under WooCommerce -> Settings -> Holiday Mode
* Existing Customizer settings are migrated automatically on first admin page load
* WooCommerce HPOS (custom order tables) and Cart/Checkout Blocks compatibility declared
* Security and reliability hardening (escaping, sanitization, timezone handling)

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