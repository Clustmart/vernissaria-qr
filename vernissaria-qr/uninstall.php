<?php
/**
 * Uninstall script for Vernissaria QR
 *
 * This file is executed when the plugin is uninstalled through the WordPress admin interface.
 * It removes all data created by the plugin from the database.
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Remove all post meta created by this plugin
global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query required for uninstallation cleanup
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_vernissaria_%'");


// Remove all plugin options
delete_option('vernissaria_enabled_post_types');
delete_option('vernissaria_show_dashboard_widget');
delete_option('vernissaria_show_visitor_stats_widget');
delete_option('vernissaria_api_url');
delete_option('vernissaria_qr_stats_version');
delete_option('vernissaria_domain');

// Clean up all plugin-specific transients
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query required for uninstallation cleanup
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_vernissaria_qr_%' OR option_name LIKE '_transient_timeout_vernissaria_qr_%'");


// Clean up scheduled events if any
wp_clear_scheduled_hook('vernissaria_cleanup_transients');

// Optionally remove uploaded QR codes - uncomment to enable
// Note: This is destructive and will delete all QR code files

$upload_dir = wp_upload_dir();
$qr_dir = $upload_dir['basedir'] . '/qr-codes';

// Only try to remove directory if it exists
if (file_exists($qr_dir) && is_dir($qr_dir)) {
    // Get WordPress filesystem
    global $wp_filesystem;
    if (empty($wp_filesystem)) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        WP_Filesystem();
    }
    
    // Recursively delete the directory
    if ($wp_filesystem) {
        $wp_filesystem->rmdir($qr_dir, true);
    }
}
