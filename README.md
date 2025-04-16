# Vernissaria QR

A WordPress plugin that adds QR code generation capabilities for art exhibitions.

## Description

Vernissaria QR seamlessly integrates with your WordPress site to help artists and galleries showcase their artwork with modern QR code technology. The plugin automatically generates QR codes for each artwork post, allowing exhibition visitors to access detailed information about the pieces by simply scanning the code with their mobile devices.

## Features

- **Automatic QR Code Generation**: QR codes are created automatically when posts are published
- **Custom Metadata Fields**: Add dimensions and year information to each artwork
- **Simple Integration**: All fields appear directly in the WordPress post editor sidebar
- **Flexible Display Options**: Use shortcodes or theme integration to display artwork details
- **Manual Regeneration**: Refresh or force-generate QR codes when needed

## Installation

1. Upload the `vernissaria-qr` directory to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Start creating or editing posts to add artwork details and generate QR codes

## Usage

### Basic Usage

1. Create or edit a post representing an artwork
2. Fill in the "Dimensions" and "Year" fields in the sidebar
3. When you publish the post, a QR code will be automatically generated
4. The QR code links to the post's URL, allowing visitors to scan and view details

### Displaying Artwork Details

You can display the artwork details (QR code, dimensions, and year) using the shortcode:

```
[artwork_details]
```

Alternatively, you can use the provided PHP function in your theme:

```php

```

To automatically append artwork details to all post content, uncomment this line in the plugin:

```php
add_filter('the_content', 'vernissaria_qr_append_to_content');
```

### Regenerating QR Codes

If you need to regenerate a QR code:

1. Edit the post
2. Click the "Refresh QR Code" button beside the QR code preview
3. For persistent issues, use the "Force Generate QR" button

## Technical Details

- QR codes are generated via the Vernissaria API service
- QR codes are stored in your WordPress uploads directory
- Generated QR codes are 450x450px PNG images
- The plugin uses error logging for troubleshooting

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- Access to the Vernissaria QR code generation API

## Troubleshooting

If QR codes aren't generating properly:

1. Check your server's error log for specific error messages
2. Ensure your server can make outbound HTTP requests
3. Verify that your WordPress uploads directory is writable
4. Try using the "Force Generate QR" button which uses a direct approach

## Credits

Developed for Vernissaria - https://vernissaria.de

## License

This plugin is licensed under the GPL v2 or later.