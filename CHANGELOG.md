# Changelog

[1.4.0] - 2026-11-22

## Added
- "Connect" button under Settings → Vernissaria QR (one-click). The site proves domain control to the API and gets its own API key, valid only for its domain. The API requires it from 2027-01-15.
- `includes/qr-api.php`: key storage (`vernissaria_api_key` option, not autoloaded); `vernissaria_api_args()` adds `X-API-Key` to every API request; public REST route `vernissaria/v1/connect-challenge` (serves the short-lived one-time code during Connect).
- Admin notice while not connected, shown only on the Dashboard, Plugins and settings screens. It can be postponed for 14 days and becomes an error after the deadline.
- readme.txt: "External services" section, FAQ about API keys, upgrade notice.

## Changed
- API errors (e.g. a missing or invalid key) are shown to admins (PDF dialog, visitor-stats widget) instead of bare status codes. The stats shortcode shows visitors a neutral message for auth errors.
- Privacy wording corrected: the API processes visitor IPs for location and unique counts, and never shows them.

## Fixed
- `vernissaria_log()` called itself endlessly, so every publish crashed with a fatal error when WP_DEBUG was on (bug since 1.3.4).


[1.3.0] - 27-04-2025

## Changed
Udated all four WordPress plugin files to match the new API endpoints:
qr-generator.php:

Changed QR generation endpoint from /generate to /qr (with POST request)
Changed metadata update endpoint from /r/{redirect_key}/update to /qr/{redirect_key} (with PATCH request)
Modified the request to use proper POST data instead of URL parameters for QR generation

qr-metabox.php:

Changed the stats endpoint from /api/stats/{redirect_key} to /qr/{redirect_key}

qr-settings.php:

Changed the daily stats endpoint from /api/stats/daily to /stats/daily

qr-stats.php:

Changed the stats endpoint from /api/stats/{redirect_key} to /qr/{redirect_key}

The key improvements:

All endpoints now follow RESTful conventions
Consistent URL structure with proper resource grouping (/qr/* for QR operations, /stats/* for statistics)
Proper HTTP methods (POST for creation, PATCH for updates, GET for retrieval)

[1.3.6] - 04-08-2025
### Changed
    - chart.min.js library to latest version (4.5.0)
    - The graphical representation of traffic statistics has been corrected, by adapting it to the API interface provided by qraft.link 

[1.3.5] - 02-08-2025
### Added
- QR Code Printing Feature: New "Print QR Codes" tab in settings page
    - Generate printable PDF containing all QR codes for your domain
    - Configurable QR size options (Small, Medium, Large)
    - Configurable paper size options (A4, Letter)
    - Automatic PDF download and WordPress media library integration

### Changed
- Settings page UI improved with tabbed interface

[1.3.4] - 25-07-2025
### Changed
- Fixed code according WordPress best practices

[1.3.0] - 21-04-2025

##Added
- Integrated QR code statistics functionality
- Shortcode [vernissaria_qr_stats] for displaying QR code statistics in posts and pages
- Visual charts showing device and browser usage
- Optional table for displaying recent scan information
- Light and dark style options for statistics display
- QR code scan count display in the artwork details meta box
- Dashboard widget showing visitor statistics for the last 30 days
- Enhanced QR codes list page with scan counts for each item
- Added uninstall.php script for clean plugin removal
- Total and average scan statistics in admin views

### Changed
- Unified plugin structure with modular file organization
- Enhanced QR code generation to include post title as label and post type as campaign
- Added API URL configuration in plugin settings
- Added direct statistics shortcode info in QR code meta box
- Improved dashboard widgets with enable/disable options

### Fixed
- Proper escaping and sanitization of all output and inputs
- Internationalization support with text domains
- Optimized database queries with transient caching
- Better error handling for API connectivity issues


## [1.2.0] - 17.04.2025
Improved QR Code Management & UI Features
- Fixed critical error in Settings -> Vernissaria QR page
- Added post type selection to control which content types generate QR codes
- Implemented dashboard widget with toggle functionality (enabled by default)
- Enhanced dashboard widget to display QR code counts broken down by post type
- Added "QR Code" column to post list views with status indicators
- Implemented filtering/sorting by QR code status in admin lists
- Fixed QR code generation for custom post types (previously only worked for standard posts)
- Improved widget links to filtered content views showing only items with QR codes

## [1.1.]0 - 16.04.2025
- Added checkbox to allow removal of QR code image if checked
- QR code generation request only occurs if no QR code is associated with the post

## [1.0.0] 16.04.2025
- Initial release