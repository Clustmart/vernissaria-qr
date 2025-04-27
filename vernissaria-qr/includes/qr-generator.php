<?php
/**
 * QR Code Generation Functionality
 * 
 * Handles the generation of QR codes when posts are published
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generate QR code when post status changes to published
 */
function vernissaria_generate_qr_on_status_change($new_status, $old_status, $post) {
    // Only proceed if the post is being published (new status is 'publish')
    if ($new_status !== 'publish') {
        return;
    }
    
    // Get enabled post types
    $enabled_types = vernissaria_get_enabled_post_types();
    
    // Check if this post type is enabled for QR codes
    if (!in_array($post->post_type, $enabled_types)) {
        return;
    }
    
    // Check if QR code already exists
    $existing_qr = get_post_meta($post->ID, '_vernissaria_qr_code', true);
    $existing_redirect_key = get_post_meta($post->ID, '_vernissaria_redirect_key', true);
    
    // If QR code exists, update the metadata in the API
    if ($existing_qr && $existing_redirect_key) {
        // Make sure we have the latest post data
        if (!isset($post->post_title) || empty($post->post_title)) {
            // Reload the post to get fresh data
            $post = get_post($post->ID);
        }
        vernissaria_update_qr_metadata($post, $existing_redirect_key);
        return;
    }
    
    // Generate the QR code using the API URL from settings
    $url = get_permalink($post->ID);
    $api_url = get_option('vernissaria_api_url', 'https://vernissaria.qraft.link');
    $api_endpoint = $api_url . '/qr';
    
    // Prepare POST data
    $post_data = array(
        'url' => $url,
        'label' => get_the_title($post->ID),
        'campaign' => $post->post_type
    );
    
    // Add a nonce parameter for added security
    $post_data['nonce'] = wp_create_nonce('vernissaria_qr_generate');
    
    $response = wp_remote_post($api_endpoint, array(
        'body' => $post_data,
        'timeout' => 15
    ));

    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
        $body = wp_remote_retrieve_body($response);
        
        // Validate that the response is actually a PNG image
        if (substr($body, 0, 8) === "\x89PNG\r\n\x1a\n") {
            // Determine upload directory and subfolder by domain
            $upload_dir = wp_upload_dir();
            $subdir = $upload_dir['basedir'] . '/qr-codes/';

            // Create directory with proper permissions
            if (!file_exists($subdir)) {
                wp_mkdir_p($subdir);
                
                // Add .htaccess file for extra security in the QR codes directory
                $htaccess_file = $upload_dir['basedir'] . '/qr-codes/.htaccess';
                if (!file_exists($htaccess_file)) {
                    $htaccess_content = "# Protect image files\n";
                    $htaccess_content .= "<Files ~ '\.png$'>\n";
                    $htaccess_content .= "    <IfModule mod_headers.c>\n";
                    $htaccess_content .= "        Header set Content-Disposition 'inline'\n";
                    $htaccess_content .= "    </IfModule>\n";
                    $htaccess_content .= "</Files>\n";
                    
                    @file_put_contents($htaccess_file, $htaccess_content);
                }
            }

            $filename = "qr-{$post->ID}.png";
            $filepath = $subdir . '/' . $filename;

            // Write file with WP filesystem API
            global $wp_filesystem;
            if (empty($wp_filesystem)) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                WP_Filesystem();
            }
            
            if ($wp_filesystem) {
                $wp_filesystem->put_contents($filepath, $body, FS_CHMOD_FILE);
                
                $fileurl = $upload_dir['baseurl'] . '/qr-codes/' . $filename;
                update_post_meta($post->ID, '_vernissaria_qr_code', esc_url_raw($fileurl));
                
                // Get the redirect key from the headers
                $headers = wp_remote_retrieve_headers($response);
                if ($headers && isset($headers['X-Redirect-Key'])) {
                    $redirect_key = $headers['X-Redirect-Key'];
                    update_post_meta($post->ID, '_vernissaria_redirect_key', $redirect_key);
                    
                    // Log successful generation
                    error_log('Vernissaria: Successfully generated QR code for post ID ' . $post->ID . ' with redirect key ' . $redirect_key);
                }
            }
        }
    } else {
        // Log error
        if (is_wp_error($response)) {
            error_log('Vernissaria: Failed to generate QR code - ' . $response->get_error_message());
        } else {
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            error_log('Vernissaria: Failed to generate QR code - Response code: ' . $response_code . ', Body: ' . $response_body);
        }
    }
}
add_action('transition_post_status', 'vernissaria_generate_qr_on_status_change', 10, 3);

/**
 * Update QR code metadata when a post with existing QR code is updated
 */
function vernissaria_update_qr_metadata($post, $redirect_key) {
    $api_url = get_option('vernissaria_api_url', 'https://vernissaria.qraft.link');
    $api_endpoint = $api_url . '/qr/' . $redirect_key;
    
    // Prepare the data to update
    $data = array(
        'label' => get_the_title($post->ID),
        'campaign' => $post->post_type,
        'metadata' => json_encode(array(
            'post_id' => $post->ID,
            'updated_at' => current_time('mysql')
        ))
    );
    
    // Make sure to log the data being sent for debugging
    error_log('Vernissaria: Updating metadata for redirect_key ' . $redirect_key . ' with data: ' . print_r($data, true));
    
    // WordPress doesn't natively support PATCH, so we use a custom approach
    $response = wp_remote_request($api_endpoint, array(
        'method' => 'PATCH',
        'body' => $data,
        'headers' => array(
            'Content-Type' => 'application/x-www-form-urlencoded'
        ),
        'timeout' => 15
    ));
    
    if (is_wp_error($response)) {
        error_log('Vernissaria: Failed to update QR metadata - ' . $response->get_error_message());
    } else {
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            error_log('Vernissaria: Failed to update QR metadata - Response code: ' . $response_code . ', Body: ' . $response_body);
        } else {
            error_log('Vernissaria: Successfully updated QR metadata for redirect_key: ' . $redirect_key);
        }
    }
}

/**
 * Update QR metadata when post is saved
 */
function vernissaria_update_on_save($post_id) {
    // Verify this is not an autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Check user permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Get the post object - make sure to get the latest version
    $post = get_post($post_id);
    
    // Get existing QR data
    $existing_qr = get_post_meta($post_id, '_vernissaria_qr_code', true);
    $existing_redirect_key = get_post_meta($post_id, '_vernissaria_redirect_key', true);
    
    // If QR code exists, update the metadata
    if ($existing_qr && $existing_redirect_key) {
        // Log for debugging
        error_log('Vernissaria: Updating metadata for post ID ' . $post_id . ' with redirect key ' . $existing_redirect_key);
        vernissaria_update_qr_metadata($post, $existing_redirect_key);
    }
}
add_action('save_post', 'vernissaria_update_on_save', 30);

/**
 * Store the redirect key when generating QR codes
 */
function vernissaria_store_redirect_key($qr_url, $post_id) {
    // Extract the redirect key from headers if available
    if (function_exists('wp_remote_get')) {
        $response = wp_remote_get($qr_url);
        if (!is_wp_error($response)) {
            $headers = wp_remote_retrieve_headers($response);
            if ($headers && isset($headers['X-Redirect-Key'])) {
                $redirect_key = $headers['X-Redirect-Key'];
                update_post_meta($post_id, '_vernissaria_redirect_key', $redirect_key);
            }
        }
    }
}

/**
 * Hook to store the redirect key when QR code is saved
 */
function vernissaria_attach_redirect_key($post_id) {
    $qr_code = get_post_meta($post_id, '_vernissaria_qr_code', true);
    if ($qr_code && !get_post_meta($post_id, '_vernissaria_redirect_key', true)) {
        vernissaria_store_redirect_key($qr_code, $post_id);
    }
}
add_action('save_post', 'vernissaria_attach_redirect_key', 20);

/**
 * Add custom columns to post list table
 */
function vernissaria_add_qr_columns($columns) {
    $new_columns = array();
    
    // Insert the QR and Scans columns before the date column
    foreach ($columns as $key => $value) {
        if ($key == 'date') {
            $new_columns['vernissaria_qr'] = __('QR Code', 'vernissaria-qr');
            $new_columns['vernissaria_scans'] = __('QR Scans', 'vernissaria-qr');
        }
        $new_columns[$key] = $value;
    }
    
    // If there's no 'date' column, add it at the end
    if (!isset($new_columns['vernissaria_qr'])) {
        $new_columns['vernissaria_qr'] = __('QR Code', 'vernissaria-qr');
        $new_columns['vernissaria_scans'] = __('QR Scans', 'vernissaria-qr');
    }
    
    return $new_columns;
}

/**
 * Populate the QR code and scan columns with content
 */
function vernissaria_populate_qr_columns($column_name, $post_id) {
    if ($column_name === 'vernissaria_qr') {
        $qr_code = get_post_meta($post_id, '_vernissaria_qr_code', true);
        
        if ($qr_code) {
            // Show a checkmark icon and link to the QR code
            echo '<a href="' . esc_url($qr_code) . '" target="_blank" title="' . esc_attr__('View QR Code', 'vernissaria-qr') . '">';
            echo '<span class="dashicons dashicons-yes" style="color: #46b450;"></span>';
            echo '</a>';
        } else {
            // Show an "x" icon if no QR code
            echo '<span class="dashicons dashicons-no" style="color: #dc3232;"></span>';
        }
    }
    
    if ($column_name === 'vernissaria_scans') {
        $redirect_key = get_post_meta($post_id, '_vernissaria_redirect_key', true);
        
        if ($redirect_key) {
            // Get scan count if we have a function for it
            $scan_count = 0;
            if (function_exists('vernissaria_get_qr_scan_count')) {
                $count = vernissaria_get_qr_scan_count($redirect_key);
                if ($count !== false) {
                    $scan_count = $count;
                }
            }
            echo '<span class="vernissaria-scan-count" style="background: #f0f7ff; border-radius: 3px; padding: 3px 8px; font-weight: bold; color: #4e73df;">' . intval($scan_count) . '</span>';
        } else {
            echo '<span style="color: #999; font-style: italic;">' . esc_html__('N/A', 'vernissaria-qr') . '</span>';
        }
    }
}

/**
 * Add the column to enabled post types
 */
function vernissaria_register_qr_columns() {
    $enabled_types = vernissaria_get_enabled_post_types();
    
    foreach ($enabled_types as $post_type) {
        add_filter("manage_{$post_type}_posts_columns", 'vernissaria_add_qr_columns');
        add_action("manage_{$post_type}_posts_custom_column", 'vernissaria_populate_qr_columns', 10, 2);
    }
}
add_action('admin_init', 'vernissaria_register_qr_columns');

/**
 * Make the QR code and scans columns sortable
 */
function vernissaria_make_qr_columns_sortable($columns) {
    $columns['vernissaria_qr'] = 'vernissaria_qr';
    $columns['vernissaria_scans'] = 'vernissaria_scans';
    return $columns;
}

/**
 * Add sorting functionality to the admin list
 */
function vernissaria_columns_orderby($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    
    $orderby = $query->get('orderby');
    
    if ('vernissaria_qr' === $orderby) {
        $query->set('meta_key', '_vernissaria_qr_code');
        $query->set('orderby', 'meta_value');
    }
    
    // We can't sort by scan count directly since it's from the API
    // But we could adapt this later if needed
}

/**
 * Register sortable columns for all enabled post types
 */
function vernissaria_register_sortable_columns() {
    $enabled_types = vernissaria_get_enabled_post_types();
    
    foreach ($enabled_types as $post_type) {
        add_filter("manage_edit-{$post_type}_sortable_columns", 'vernissaria_make_qr_columns_sortable');
    }
    
    add_action('pre_get_posts', 'vernissaria_columns_orderby');
}
add_action('admin_init', 'vernissaria_register_sortable_columns');
