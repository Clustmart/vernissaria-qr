=== Vernissaria QR ===
Contributors: vernissaria
Tags: qr-code, analytics, art, gallery, exhibition
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.3.6
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
* **Privacy Focused**: No personal visitor data is collected

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
* Vernissaria QR API access

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/vernissaria-qr` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Settings → Vernissaria QR to configure the plugin
4. Select which post types should have QR codes
5. Configure your API URL if using a custom installation

== Frequently Asked Questions ==

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

= Is this plugin GDPR compliant? =

Yes, we only collect anonymous usage data and do not store personal information about visitors.

= How do I customize the appearance of statistics? =

Use the style parameter in the shortcode: style="default" or style="dark"

== Screenshots ==

1. Dashboard widgets showing visitor statistics
2. QR code meta box on post edit screen 
3. QR code statistics displayed using shortcode
4. Settings page - General Settings tab
5. Settings page with new Print QR Codes tab
6. Article list view with scan counts

== Changelog ==

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