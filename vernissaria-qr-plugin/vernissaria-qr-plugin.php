<?php
/**
 * Plugin Name: Vernissaria QR
 * Plugin URI: https://vernissaria.de
 * Description: Adds QR code, dimensions, and year fields to posts for art exhibitions.
 * Version: 1.2.0
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

// Load settings
function vernissaria_get_enabled_post_types() {
    $types = get_option('vernissaria_enabled_post_types', ['post']);
    return is_array($types) ? $types : ['post'];
}

// Add plugin settings page
function vernissaria_add_settings_page() {
    add_options_page(
        'Vernissaria QR Settings',
        'Vernissaria QR',
        'manage_options',
        'vernissaria-qr',
        'vernissaria_render_settings_page'
    );
}
add_action('admin_menu', 'vernissaria_add_settings_page');

function vernissaria_render_settings_page() {
    $post_types = get_post_types(['public' => true], 'objects');
    $enabled_types = vernissaria_get_enabled_post_types();
    $show_widget = vernissaria_is_dashboard_widget_enabled();

    echo '<div class="wrap">';
    echo '<h1>Vernissaria QR Settings</h1>';
    echo '<form method="post" action="options.php">';
    settings_fields('vernissaria_qr_settings');
    echo '<table class="form-table">';

    echo '<tr><th scope="row">Enable for post types</th><td>';
    foreach ($post_types as $slug => $obj) {
        $checked = in_array($slug, $enabled_types) ? 'checked' : '';
        echo '<label><input type="checkbox" name="vernissaria_enabled_post_types[]" value="' . esc_attr($slug) . '" ' . $checked . '> ' . esc_html($obj->labels->name) . '</label><br />';
    }
    echo '</td></tr>';

    echo '<tr><th scope="row">Show Dashboard Widget</th><td>';
    echo '<label><input type="checkbox" name="vernissaria_show_dashboard_widget" value="1" ' . checked($show_widget, true, false) . '> Enable dashboard QR summary widget</label>';
    echo '</td></tr>';

    echo '</table>';
    submit_button();
    echo '</form></div>';
}

function vernissaria_is_dashboard_widget_enabled() {
    return get_option('vernissaria_show_dashboard_widget', false);
}

function vernissaria_register_settings() {
    register_setting(
        'vernissaria_qr_settings',
        'vernissaria_enabled_post_types',
        [
            'sanitize_callback' => 'vernissaria_sanitize_post_types',
            'default' => ['post'],
        ]
    );
    register_setting('vernissaria_qr_settings', 'vernissaria_show_dashboard_widget');
}

add_action('admin_init', 'vernissaria_register_settings');

function vernissaria_sanitize_post_types($input) {
    if (!is_array($input)) {
        return ['post'];
    }

    $sanitized = [];
    foreach ($input as $type) {
        $sanitized[] = sanitize_key($type);
    }

    return $sanitized;
}

// Add custom meta box
function vernissaria_add_custom_box() {
    foreach (vernissaria_get_enabled_post_types() as $post_type) {
        add_meta_box(
            'vernissaria_fields',
            __('Artwork Details', 'vernissaria-qr'),
            'vernissaria_custom_box_html',
            $post_type,
            'side'
        );
    }
}
add_action('add_meta_boxes', 'vernissaria_add_custom_box');

// Display fields in meta box
function vernissaria_custom_box_html($post) {
    // Add nonce for verification
    wp_nonce_field('vernissaria_meta_box', 'vernissaria_meta_box_nonce');
    
    $qr_code = get_post_meta($post->ID, '_vernissaria_qr_code', true);
    $dimensions = get_post_meta($post->ID, '_vernissaria_dimensions', true);
    $year = get_post_meta($post->ID, '_vernissaria_year', true);
    ?>
    <p>
        <label for="vernissaria_dimensions"><?php echo esc_html__('Dimensions', 'vernissaria-qr'); ?></label><br />
        <input type="text" id="vernissaria_dimensions" name="vernissaria_dimensions" 
               value="<?php echo esc_attr($dimensions); ?>" class="widefat" />
    </p>

    <p>
        <label for="vernissaria_year"><?php echo esc_html__('Year', 'vernissaria-qr'); ?></label><br />
        <input type="text" id="vernissaria_year" name="vernissaria_year" 
               value="<?php echo esc_attr($year); ?>" class="widefat" />
    </p>

    <p>
        <label><?php echo esc_html__('QR Code', 'vernissaria-qr'); ?></label><br />
        <?php if ($qr_code) : ?>
            <img src="<?php echo esc_url($qr_code); ?>" width="450" height="450" style="margin-bottom: 10px;" /><br />
            <label>
                <input type="checkbox" name="vernissaria_remove_qr" value="1" />
                <?php echo esc_html__('Remove QR Code', 'vernissaria-qr'); ?>
            </label>
        <?php else : ?>
            <img src="" width="450" height="450" style="background:#f0f0f0;border:1px solid #ccc;" />
        <?php endif; ?>
    </p>
    <?php
}

// Save meta box fields
function vernissaria_save_postdata($post_id) {
    // Check if nonce is set and valid
    if (!isset($_POST['vernissaria_meta_box_nonce']) || 
        !wp_verify_nonce($_POST['vernissaria_meta_box_nonce'], 'vernissaria_meta_box')) {
        return;
    }
    
    // Check if user has permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Check if not an autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Save dimensions
    if (array_key_exists('vernissaria_dimensions', $_POST)) {
        update_post_meta(
            $post_id, 
            '_vernissaria_dimensions', 
            sanitize_text_field($_POST['vernissaria_dimensions'])
        );
    }
    
    // Save year
    if (array_key_exists('vernissaria_year', $_POST)) {
        update_post_meta(
            $post_id, 
            '_vernissaria_year', 
            sanitize_text_field($_POST['vernissaria_year'])
        );
    }
    
    // Remove QR code if requested
    if (!empty($_POST['vernissaria_remove_qr'])) {
        delete_post_meta($post_id, '_vernissaria_qr_code');
    }
}
add_action('save_post', 'vernissaria_save_postdata');

// Add a generic hook that works for all post types
add_action('transition_post_status', 'vernissaria_generate_qr_on_status_change', 10, 3);

// Function to handle QR code generation on status change
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
    if ($existing_qr) {
        return;
    }
    
    // Generate the QR code
    $url = get_permalink($post->ID);
    $api_url = 'https://vernissaria.qraft.link/generate?url=' . urlencode($url);
    
    // Add a nonce parameter to the API request for added security
    $api_url = add_query_arg('nonce', wp_create_nonce('vernissaria_qr_generate'), $api_url);
    
    $response = wp_remote_get($api_url);

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
            }
        }
    }
}


// Register activation hook to create necessary directories
function vernissaria_activation() {
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
register_activation_hook(__FILE__, 'vernissaria_activation');

// Admin list filter page (custom admin page)
function vernissaria_add_list_page() {
    add_submenu_page(
        null, // No menu item in sidebar
        'Posts with QR Codes',
        'QR Code Posts',
        'manage_options',
        'vernissaria-qr-list',
        'vernissaria_render_qr_list_page'
    );
}
add_action('admin_menu', 'vernissaria_add_list_page');

function vernissaria_render_qr_list_page() {
    echo '<div class="wrap"><h1>Posts with QR Codes</h1>';

    $enabled_types = vernissaria_get_enabled_post_types();

    foreach ($enabled_types as $type) {
        $query = new WP_Query([
            'post_type' => $type,
            'meta_query' => [[
                'key' => '_vernissaria_qr_code',
                'compare' => 'EXISTS'
            ]],
            'posts_per_page' => -1
        ]);

        if ($query->have_posts()) {
            echo '<h2>' . ucfirst($type) . '</h2><ul>';
            while ($query->have_posts()) {
                $query->the_post();
                echo '<li><a href="' . get_edit_post_link() . '">' . get_the_title() . '</a></li>';
            }
            echo '</ul>';
        }
    }

    echo '</div>';
    wp_reset_postdata();
}

// Add custom column to post list table
function vernissaria_add_qr_column($columns) {
    $new_columns = array();
    
    // Insert the QR column before the date column
    foreach ($columns as $key => $value) {
        if ($key == 'date') {
            $new_columns['vernissaria_qr'] = __('QR Code', 'vernissaria-qr');
        }
        $new_columns[$key] = $value;
    }
    
    // If there's no 'date' column, add it at the end
    if (!isset($new_columns['vernissaria_qr'])) {
        $new_columns['vernissaria_qr'] = __('QR Code', 'vernissaria-qr');
    }
    
    return $new_columns;
}

// Populate the QR code column with content
function vernissaria_populate_qr_column($column_name, $post_id) {
    if ($column_name !== 'vernissaria_qr') {
        return;
    }
    
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

// Add the column to enabled post types
function vernissaria_register_qr_columns() {
    $enabled_types = vernissaria_get_enabled_post_types();
    
    foreach ($enabled_types as $post_type) {
        add_filter("manage_{$post_type}_posts_columns", 'vernissaria_add_qr_column');
        add_action("manage_{$post_type}_posts_custom_column", 'vernissaria_populate_qr_column', 10, 2);
    }
}
add_action('admin_init', 'vernissaria_register_qr_columns');

// Make the column sortable
function vernissaria_make_qr_column_sortable($columns) {
    $columns['vernissaria_qr'] = 'vernissaria_qr';
    return $columns;
}

// Add sorting functionality
function vernissaria_qr_column_orderby($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    
    $orderby = $query->get('orderby');
    
    if ('vernissaria_qr' === $orderby) {
        $query->set('meta_key', '_vernissaria_qr_code');
        $query->set('orderby', 'meta_value');
    }
}

// Register sortable columns for all enabled post types
function vernissaria_register_sortable_columns() {
    $enabled_types = vernissaria_get_enabled_post_types();
    
    foreach ($enabled_types as $post_type) {
        add_filter("manage_edit-{$post_type}_sortable_columns", 'vernissaria_make_qr_column_sortable');
    }
    
    add_action('pre_get_posts', 'vernissaria_qr_column_orderby');
}
add_action('admin_init', 'vernissaria_register_sortable_columns');

// Dashboard widget with post type breakdown
function vernissaria_dashboard_widget() {
    $enabled_types = vernissaria_get_enabled_post_types();
    $total = 0;
    $counts_by_type = array();
    $post_type_objects = array();
    
    // Get post type objects for names
    foreach ($enabled_types as $type) {
        $post_type_obj = get_post_type_object($type);
        if ($post_type_obj) {
            $post_type_objects[$type] = $post_type_obj;
        }
    }

    // Query each post type and count QR codes
    foreach ($enabled_types as $type) {
        $query = new WP_Query([
            'post_type' => $type,
            'meta_key' => '_vernissaria_qr_code',
            'meta_compare' => 'EXISTS',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ]);
        
        $count = $query->found_posts;
        $total += $count;
        
        // Only add to breakdown if there are posts with QR codes
        if ($count > 0) {
            $counts_by_type[$type] = $count;
        }
    }

    // Output the total
    echo '<p>Total content with QR codes: <strong>' . intval($total) . '</strong></p>';
    
    // Output the breakdown if there are any QR codes
    if ($total > 0) {
        echo '<div style="margin-top: 10px;">';
        echo '<p style="margin-bottom: 5px;"><strong>Breakdown by type:</strong></p>';
        echo '<ul style="margin-top: 0; padding-left: 15px;">';
        
        foreach ($counts_by_type as $type => $count) {
            $type_name = isset($post_type_objects[$type]) ? $post_type_objects[$type]->labels->name : ucfirst($type);
            $url = admin_url('edit.php?post_type=' . $type . '&vernissaria_qr_filter=1');
            
            echo '<li>';
            echo '<a href="' . esc_url($url) . '">' . esc_html($type_name) . '</a>: ';
            echo '<strong>' . intval($count) . '</strong>';
            echo '</li>';
        }
        
        echo '</ul>';
        echo '</div>';
    } else {
        echo '<p>No content with QR codes found.</p>';
    }
}

// Add filter to post lists to show only posts with QR codes
function vernissaria_add_qr_filter_to_query($query) {
    if (!is_admin()) {
        return;
    }
    
    if (isset($_GET['vernissaria_qr_filter']) && $_GET['vernissaria_qr_filter'] == 1) {
        $enabled_types = vernissaria_get_enabled_post_types();
        
        // Only apply to enabled post types
        if (in_array($query->get('post_type'), $enabled_types)) {
            $query->set('meta_key', '_vernissaria_qr_code');
            $query->set('meta_compare', 'EXISTS');
        }
    }
}
add_action('pre_get_posts', 'vernissaria_add_qr_filter_to_query');

// Add the widget to the dashboard if enabled
add_action('wp_dashboard_setup', function() {
    if (vernissaria_is_dashboard_widget_enabled()) {
        wp_add_dashboard_widget('vernissaria_qr_widget', 'Vernissaria QR Codes', 'vernissaria_dashboard_widget');
    }
});