<?php
/**
 * Plugin Name: Vernissaria QR
 * Plugin URI: https://vernissaria.de
 * Description: Adds QR code, dimensions, and year fields to posts for art exhibitions.
 * Version: 1.1.0
 * Author: Vernissaria
 * Author URI: https://vernissaria.de
 * Text Domain: vernissaria-qr
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Add custom meta box
function vernissaria_add_custom_box() {
    add_meta_box(
        'vernissaria_fields',
        'Artwork Details',
        'vernissaria_custom_box_html',
        'post',
        'side'
    );
}
add_action('add_meta_boxes', 'vernissaria_add_custom_box');

// Display fields in meta box
function vernissaria_custom_box_html($post) {
    $qr_code = get_post_meta($post->ID, '_vernissaria_qr_code', true);
    $dimensions = get_post_meta($post->ID, '_vernissaria_dimensions', true);
    $year = get_post_meta($post->ID, '_vernissaria_year', true);

    echo '<p><label for="vernissaria_dimensions">Dimensions</label><br />';
    echo '<input type="text" name="vernissaria_dimensions" value="' . esc_attr($dimensions) . '" class="widefat" /></p>';

    echo '<p><label for="vernissaria_year">Year</label><br />';
    echo '<input type="text" name="vernissaria_year" value="' . esc_attr($year) . '" class="widefat" /></p>';

    echo '<p><label>QR Code</label><br />';
    if ($qr_code) {
        echo '<img src="' . esc_url($qr_code) . '" width="450" height="450" style="margin-bottom: 10px;" /><br />';
        echo '<label><input type="checkbox" name="vernissaria_remove_qr" value="1" /> Remove QR Code</label>';
    } else {
        echo '<img src="" width="450" height="450" style="background:#f0f0f0;border:1px solid #ccc;" />';
    }
    echo '</p>';
}

// Save meta box fields
function vernissaria_save_postdata($post_id) {
    if (array_key_exists('vernissaria_dimensions', $_POST)) {
        update_post_meta($post_id, '_vernissaria_dimensions', sanitize_text_field($_POST['vernissaria_dimensions']));
    }
    if (array_key_exists('vernissaria_year', $_POST)) {
        update_post_meta($post_id, '_vernissaria_year', sanitize_text_field($_POST['vernissaria_year']));
    }
    if (!empty($_POST['vernissaria_remove_qr'])) {
        delete_post_meta($post_id, '_vernissaria_qr_code');
    }
}
add_action('save_post', 'vernissaria_save_postdata');

// Generate and save QR code image on post publish
function vernissaria_generate_qr_on_publish($post_ID, $post) {
    if ($post->post_status === 'publish') {
        $existing_qr = get_post_meta($post_ID, '_vernissaria_qr_code', true);
        if (!$existing_qr) {
            $url = get_permalink($post_ID);
            $response = wp_remote_get('https://vernissaria.qraft.link/generate?url=' . urlencode($url));

            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $body = wp_remote_retrieve_body($response);
                $upload = wp_upload_bits("qr-{$post_ID}.png", null, $body);
                if (!$upload['error']) {
                    update_post_meta($post_ID, '_vernissaria_qr_code', esc_url($upload['url']));
                }
            }
        }
    }
}
add_action('publish_post', 'vernissaria_generate_qr_on_publish', 10, 2);