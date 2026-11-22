=== Vernissaria QR ===
Contributors: vernissaria
Tags: qr-code, analytics, art, gallery, exhibition
Requires at least: 5.0
Tested up to: 7.1
Stable tag: 1.4.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate QR codes for artworks and track visitor engagement with detailed analytics.

== Description ==

Vernissaria QR is a powerful WordPress plugin designed for artisans, art galleries, museums, and exhibitions. It automatically generates QR codes for your in WordPress documented artworks and provides visitor analytics to track engagement.

= Key Features =

* **Automatic QR Code Generation**: Automatically creates QR codes when posts/pages are published
* **QR Code Printing**: Generate printable PDFs containing all QR codes for your domain
* **Record Artwork Details**: Document Dimensions and Year
* **Visitor Analytics**: Track scans, unique visitors, devices and browsers
* **Custom Post Type Support**: Enable QR codes for any post type
* **Dashboard Widgets**: View QR code statistics directly in your WordPress dashboard
* **Shortcode Support**: Display detailed analytics on any page using shortcodes
* **Dark Mode**: Beautiful dark mode for statistics display
* **Mobile Responsive**: Works perfectly on all devices
* **Secure API Connection**: One-click "Connect" gives your site its own API key, valid only for your domain
* **Privacy-minded Statistics**: Statistics show aggregate data only (country, device, browser), never visitor IP addresses

= Use Cases =

* Artists monitoring interest in their work
* Art galleries tracking visitor engagement with artworks
* Museums providing additional information via QR codes
* Exhibitions analyzing visitor patterns
* Digital catalogs with scan analytics
* Printing QR codes for physical artwork labels

= Shortcode Usage =

Display QR code statistics on any page:

`[vernissaria_qr_stats redirect_key="YOUR_KEY" show_chart="yes" show_recent="yes" style="default"]`

= Requirements =

* WordPress 5.0 or higher
* PHP 7.2 or higher
* A free API key for your site, obtained with one click under Settings → Vernissaria QR → "Connect" (required from 15 January 2027)

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/vernissaria-qr` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Settings → Vernissaria QR and click **Connect** to get your free API key
4. Select which post types should have QR codes
5. Configure your API URL only if you use a custom API installation

== Frequently Asked Questions ==

= Why does the plugin need an API key? =

From 15 January 2027 the Vernissaria API only accepts requests with an API key. Each site gets its own key, and it only works for that site's domain. This means nobody else can change your QR codes, point them to other websites, or read your statistics.

= How do I get the API key? =

Go to Settings → Vernissaria QR and click **Connect**. The Vernissaria API checks that the request really comes from your site and stores a key that is valid only for your domain. This takes a few seconds, and you don't need to register or copy any key.

= What happens if I don't connect? =

QR codes you have already printed keep working: scanning them still opens your page. But without a key, from 15 January 2027 the plugin can no longer create new QR codes, update them, show statistics or generate PDFs.

= "Connect" fails. What can I do? =

The Vernissaria API must be able to reach your site over HTTPS at `/?rest_route=/vernissaria/v1/connect-challenge` for a few seconds. Connect fails if:

* your site isn't publicly reachable (localhost, a staging site behind a password, maintenance mode)
* a security plugin blocks the WordPress REST API for visitors

Allow that route, or contact support@vernissaria.de.

= How do I generate QR codes? =

QR codes are automatically generated when you publish or update posts. You can find the QR code in the "Artwork Details" meta box on the post edit screen.

= How do I print QR codes? =

Go to Settings → Vernissaria QR → Print QR Codes tab. Select your preferred QR size and paper format, then click "Generate PDF" to create a printable document with all your QR codes.

= Can I use this plugin for non-art content? =

Yes! While designed for art galleries, this plugin works with any WordPress content type including posts, pages, and custom post types.

= Where can I view statistics? =

You can view statistics in multiple places:
1. Dashboard widgets
2. Individual post edit screens
3. Using the [vernissaria_qr_stats] shortcode
4. In the QR codes overview page

= What data is collected about visitors? =

The plugin itself stores no visitor data in WordPress. When someone scans a QR code, the Vernissaria API records the time of the scan, the device type, the browser, the operating system and the approximate location. The visitor's IP address is processed to determine the approximate location and to count unique visitors. It is never shown in the statistics. See "External services" below.

= How do I customize the appearance of statistics? =

Use the style parameter in the shortcode: style="default" or style="dark"

== Screenshots ==

1. Dashboard widgets showing visitor statistics
2. QR code meta box on post edit screen 
3. QR code statistics displayed using shortcode
4. Settings page - General Settings tab
5. Settings page with new Print QR Codes tab
6. Article list view with scan counts

== External services ==

This plugin relies on the **Vernissaria QR API**, a service operated by Vernissaria. It runs at https://vernissaria.qraft.link by default; you can change the address under Settings → Vernissaria QR. The plugin needs the API to create QR codes, show statistics and generate PDFs.

What is sent to the API, and when:

* **When you publish or update a post** of an enabled post type: the post's URL, title (label), post type (campaign) and post ID, to create or update its QR code.
* **When statistics are shown** (dashboard widgets, post edit screen, the `[vernissaria_qr_stats]` shortcode): your site's domain and the QR code's key.
* **When you generate a PDF**: your site's domain and the chosen QR and paper size.
* **When you click "Connect"**: your site's host name and installation path. The API then requests `/?rest_route=/vernissaria/v1/connect-challenge` on your site once, to verify it.
* **With every request**: your site's API key, once you have connected.

When a visitor scans a printed QR code, their browser is redirected through the API. The API records the time, device type, browser, operating system and approximate location for your statistics, and processes the visitor's IP address for this.

More information, terms and privacy policy: https://vernissaria.de

== Changelog ==

= 1.4.0 =
* New: **Connect** button (Settings → Vernissaria QR). One click gives your site its own API key, valid only for your domain. The Vernissaria API requires it from 15 January 2027.
* New: the API key is sent with every API request.
* New: admin notice until the site is connected, shown on the Dashboard, Plugins and plugin settings screens. It can be postponed for 14 days.
* Improved: API error messages (e.g. a missing or invalid key) are now shown to administrators instead of bare status codes.
* Improved: the statistics shortcode no longer shows technical API errors to visitors.
* Fixed: with WP_DEBUG enabled, publishing a post crashed with a fatal error (endless recursion in the plugin's logging).
* Added "External services" and updated privacy information in this readme.

= 1.3.6 =
* Updated chart.min.js library to latest version (4.5.0)
* Fixed the graphical representation of traffic statistics has been corrected, by adapting it to the API interface provided by qraft.link 

= 1.3.5 =
* Added QR Code Printing Feature: New "Print QR Codes" tab in settings page
* Generate printable PDF containing all QR codes for your domain
* Configurable QR size options (Small, Medium, Large)
* Configurable paper size options (A4, Letter)
* Automatic PDF download and WordPress media library integration
* Settings page UI improved with tabbed interface

= 1.3.4 =
* Fixed code according to WordPress best practices

= 1.3.0 =
* Added REST API integration
* Improved statistics visualization
* Added dark mode for statistics display
* Enhanced dashboard widgets
* Added support for custom post types
* Improved mobile responsiveness

= 1.2.0 =
* Added visitor analytics
* Introduced dashboard widgets
* Added shortcode support
* Improved QR code generation

= 1.1.0 =
* Added automatic QR code generation
* Basic statistics tracking
* Meta box integration

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.4.0 =
Action needed before 15 January 2027: after updating, open Settings → Vernissaria QR and click "Connect" to get your free API key. Printed QR codes keep working. Also fixes a crash with WP_DEBUG.

= 1.3.6 =
The graphical statistical representation has been fixed. Update recommended for all users.

= 1.3.5 =
New QR code printing feature allows you to generate professional PDFs with all your QR codes for printing and physical display. Update recommended for all users.

= 1.3.0 =
This version adds significant improvements to analytics and visualization. Update recommended for all users.

== Additional Info ==

For more information about Vernissaria QR, please visit [vernissaria.de](https://vernissaria.de)

= Support =

For support questions, please contact support@vernissaria.de

= Credits =

* Developed by Vernissaria
* Uses Chart.js for data visualization
* QR code generation powered by Vernissaria API
