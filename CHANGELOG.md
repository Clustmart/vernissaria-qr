# Changelog

[1.3.0] - 21-04-2025


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