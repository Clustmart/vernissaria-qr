<?php
/**
 * Plugin Name: Vernissaria QR
 * Plugin URI: https://github.com/Clustmart/vernissaria-qr
 * Description: Generate QR codes for artworks and display statistics for visitor engagement.
 * Version: 1.3.0
 * Author: Vernissaria
 * Author URI: https://vernissaria.de
 * License: GPLv2
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: vernissaria-qr
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('VERNISSARIA_QR_VERSION', '1.3.0');
define('VERNISSARIA_QR_PATH', plugin_dir_path(__FILE__));
define('VERNISSARIA_QR_URL', plugin_dir_url(__FILE__));

// Load required files
require_once VERNISSARIA_QR_PATH . 'includes/qr-generator.php';
require_once VERNISSARIA_QR_PATH . 'includes/qr-metabox.php';
require_once VERNISSARIA_QR_PATH . 'includes/qr-settings.php';
require_once VERNISSARIA_QR_PATH . 'includes/qr-stats.php';

/**
 * Initialize the plugin
 */
function vernissaria_qr_init() {
    // Load text domain for translations
    load_plugin_textdomain('vernissaria-qr', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'vernissaria_qr_init');

/**
 * Register activation hook
 */
function vernissaria_qr_activate() {
    // Create necessary directories
    $upload_dir = wp_upload_dir();
    $qr_dir = $upload_dir['basedir'] . '/qr-codes';
    
    // Create directory with proper permissions if it doesn't exist
    if (!file_exists($qr_dir)) {
        wp_mkdir_p($qr_dir);
    }
    
    // Add an index.php file to prevent directory listing
    $index_file = $qr_dir . '/index.php';
    if (!file_exists($index_file)) {
        @file_put_contents($index_file, '<?php // Silence is golden');
    }
}
register_activation_hook(__FILE__, 'vernissaria_qr_activate');

/**
 * Enqueue admin styles
 */
function vernissaria_qr_admin_styles($hook) {
    // Only load on our plugin pages
    $plugin_pages = array(
        'post.php',
        'post-new.php',
        'edit.php',
        'settings_page_vernissaria-qr',
        'dashboard_page_vernissaria-qr-list'
    );
    
    if (in_array($hook, $plugin_pages) || strpos($hook, 'vernissaria-qr') !== false) {
        wp_enqueue_style(
            'vernissaria-qr-admin',
            VERNISSARIA_QR_URL . 'assets/css/admin.css',
            array(),
            VERNISSARIA_QR_VERSION
        );
    }
}
add_action('admin_enqueue_scripts', 'vernissaria_qr_admin_styles');