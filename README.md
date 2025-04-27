# Vernissaria QR Plugin

A WordPress plugin that adds QR code generation capabilities with visitor analytics for artisans, art galleries, museums, and exhibitions.


## Description

Vernissaria QR seamlessly integrates with your WordPress site to help artists and galleries showcase their artwork with modern QR code technology and track visitor engagement. The plugin automatically generates QR codes for each artwork post, allowing exhibition visitors to access detailed information about the pieces by simply scanning the code with their mobile devices.

## Features

- **Automatic QR Code Generation**: QR codes are created automatically when artwork documentation is published
- **Visitor Analytics**: Track scans, unique visitors, devices, browsers, and geographic data
- **Custom Metadata Fields**: Add dimensions, year, and other details to each artwork
- **Dashboard Widgets**: View QR code statistics directly in your WordPress admin dashboard
- **Flexible Display Options**: Display analytics using shortcodes or PHP functions
- **Manual Regeneration**: Easily regenerate QR codes through the admin interface
- **Multi Post Type Support**: Enable QR codes for posts, pages, or custom post types
- **Privacy Focused**: No personal visitor data is collected


## Installation

1. Upload the `vernissaria-qr` directory to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings → Vernissaria QR to configure which post types should have QR codes
4. Start creating or editing posts to add artwork details and generate QR codes

## Usage

### Basic Usage

1. Create or edit a post representing an artwork
2. Fill in the "Dimensions" and "Year" fields in the sidebar
3. When you publish the post, a QR code will be automatically generated
4. The QR code links to the post's URL, allowing visitors to scan and view details

### Displaying Artwork Details

You can display the artwork details (QR code, dimensions, and year) using the shortcode:

```
[vernissaria_qr_stats redirect_key="YOUR_KEY" show_chart="yes" show_recent="yes" style="default"]
```

Alternatively, you can use the provided PHP function in your theme:

```php

```

To automatically append artwork details to all post content, uncomment this line in the plugin:

```php
<?php 
if (function_exists('vernissaria_qr_stats_shortcode')) {
    echo vernissaria_qr_stats_shortcode(array(
        'redirect_key' => 'YOUR_KEY',
        'show_chart' => 'yes',
        'show_recent' => 'yes',
        'style' => 'default'
    ));
}
?>
```

### Regenerating QR Codes

If you need to regenerate a QR code:

1. Edit the post
2. Check the "Remove QR Code" below the QR code preview
3. Save (Update the post)
4. Edit the post again
5. Save (Update the post to generate a new QR code)

## Technical Details

- QR codes are generated via the Vernissaria API service
- QR codes are stored in your WordPress uploads/qr-codes directory
- Generated QR codes are 450x450px PNG images with embedded logo
- Analytics data is cached for performance (30-minute refresh)
- Full REST API integration for external applications

## API Configuration
The plugin connects to the Vernissaria API by default. To use a custom API endpoint:

1. Go to Settings → Vernissaria QR
2. Check post type with QR functionality
3. Save changes

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- Access to the Vernissaria QR code generation API

## Troubleshooting

If QR codes aren't generating properly:

1. Check your server's error log for specific error messages
2. Ensure your server can make outbound HTTP requests
3. Verify that your WordPress uploads directory is writable
4. Enable WordPress debug mode in wp-config.php: define('WP_DEBUG', true);
5. Check the plugin settings to ensure correct configuration


## Privacy
This plugin collects anonymous usage data for QR code scans, including:

- Device type and browser
- Geographic location (country/city)
- Scan timestamps

No personally identifiable information is collected or stored.

## Credits

Developed for Vernissaria - https://vernissaria.de

## License

This plugin is licensed under the GPL v2 or later.