<?php
/**
 * QR Code Metabox Functionality
 * 
 * Handles the admin metabox for displaying and managing QR codes
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add custom meta box
 */
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

/**
 * Get QR code scan count from API
 */
function vernissaria_get_qr_scan_count($redirect_key) {
    if (empty($redirect_key)) {
        return false;
    }
    
    // Check transient first to avoid unnecessary API calls
    $transient_key = 'vernissaria_qr_count_' . $redirect_key;
    $scan_count = get_transient($transient_key);
    
    if ($scan_count !== false) {
        return $scan_count;
    }
    
    // Make API request
    $api_url = get_option('vernissaria_api_url', 'https://vernissaria.qraft.link');
    $endpoint = $api_url . '/qr/' . $redirect_key;
    
    $response = wp_remote_get($endpoint);
    
    // Check for errors
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        return false;
    }
    
    // Parse response
    $stats = json_decode(wp_remote_retrieve_body($response), true);
    
    if (!$stats || !isset($stats['scan_count'])) {
        return false;
    }
    
    // Store in transient for 30 minutes
    set_transient($transient_key, $stats['scan_count'], 30 * MINUTE_IN_SECONDS);
    
    return $stats['scan_count'];
}

/**
 * Display fields in meta box
 */
function vernissaria_custom_box_html($post) {
    // Add nonce for verification
    wp_nonce_field('vernissaria_meta_box', 'vernissaria_meta_box_nonce');
    
    $qr_code = get_post_meta($post->ID, '_vernissaria_qr_code', true);
    $dimensions = get_post_meta($post->ID, '_vernissaria_dimensions', true);
    $year = get_post_meta($post->ID, '_vernissaria_year', true);
    $redirect_key = get_post_meta($post->ID, '_vernissaria_redirect_key', true);
    
    // Get the QR label and campaign values (new)
    $qr_label = get_post_meta($post->ID, '_vernissaria_qr_label', true);
    $qr_campaign = get_post_meta($post->ID, '_vernissaria_qr_campaign', true);
    
    // Default the label to post title if not set
    if (empty($qr_label)) {
        $qr_label = get_the_title($post->ID);
    }
    
    // Default the campaign to post type if not set
    if (empty($qr_campaign)) {
        $qr_campaign = $post->post_type;
    }
    
    // Get scan count if we have a redirect key
    $scan_count = false;
    if ($redirect_key) {
        $scan_count = vernissaria_get_qr_scan_count($redirect_key);
    }
    
    // Define allowed HTML for form elements
    $allowed_html = array(
        'p' => array(),
        'label' => array(
            'for' => array(),
            'class' => array(),
            'style' => array(),
        ),
        'input' => array(
            'type' => array(),
            'id' => array(),
            'name' => array(),
            'value' => array(),
            'checked' => array(),
            'class' => array(),
            'style' => array(),
        ),
        'br' => array(),
        'div' => array(
            'class' => array(),
            'style' => array(),
        ),
        'span' => array(
            'class' => array(),
            'style' => array(),
        ),
        'strong' => array(),
        'a' => array(
            'href' => array(),
            'onclick' => array(),
            'style' => array(),
            'class' => array(),
        ),
        'img' => array(
            'src' => array(),
            'width' => array(),
            'height' => array(),
            'style' => array(),
        ),
        'code' => array(),
    );
    ?>
    <!-- Add new QR metadata fields at the top -->
    <p>
        <label for="vernissaria_qr_label"><?php echo esc_html__('QR Label', 'vernissaria-qr'); ?></label><br />
        <input type="text" id="vernissaria_qr_label" name="vernissaria_qr_label" 
               value="<?php echo esc_attr($qr_label); ?>" class="widefat" 
               placeholder="<?php echo esc_attr__('Used as QR code title', 'vernissaria-qr'); ?>" />
        <small class="description"><?php echo esc_html__('This will be sent to the QR code API', 'vernissaria-qr'); ?></small>
    </p>

    <p>
        <label for="vernissaria_qr_campaign"><?php echo esc_html__('QR Campaign', 'vernissaria-qr'); ?></label><br />
        <input type="text" id="vernissaria_qr_campaign" name="vernissaria_qr_campaign" 
               value="<?php echo esc_attr($qr_campaign); ?>" class="widefat" 
               placeholder="<?php echo esc_attr__('Campaign or category', 'vernissaria-qr'); ?>" />
        <small class="description"><?php echo esc_html__('For grouping QR codes in statistics', 'vernissaria-qr'); ?></small>
    </p>

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
            
            <?php if ($scan_count !== false) : ?>
                <div class="vernissaria-qr-scan-count" style="margin-bottom: 10px; padding: 8px; background: #f8f9fa; border-left: 4px solid #4e73df;">
                    <strong><?php echo esc_html__('Total Scans', 'vernissaria-qr'); ?>:</strong> 
                    <span style="font-size: 16px; font-weight: bold;"><?php echo intval($scan_count); ?></span>
                    <a href="#" onclick="jQuery(this).next('.vernissaria-qr-refresh-count').show(); return false;" style="margin-left: 8px; font-size: 12px; text-decoration: none;">
                        <span class="dashicons dashicons-update" style="font-size: 14px; width: 14px; height: 14px;"></span>
                        <?php echo esc_html__('Refresh', 'vernissaria-qr'); ?>
                    </a>
                    <span class="vernissaria-qr-refresh-count" style="display: none; font-size: 12px; color: #666; margin-left: 5px;">
                        <?php echo esc_html__('Refreshed every 30 minutes', 'vernissaria-qr'); ?>
                    </span>
                </div>
            <?php endif; ?>
            
            <?php
            // Display the current QR target URL if available
            $original_url = get_post_meta($post->ID, '_vernissaria_original_url', true);
            if ($original_url) : 
            ?>
            <div class="vernissaria-qr-target" style="margin-bottom: 10px; padding: 8px; background: #f8f9fa; border-left: 4px solid #46b450;">
                <strong><?php echo esc_html__('QR Target URL', 'vernissaria-qr'); ?>:</strong><br>
                <span style="font-size: 12px; word-break: break-all;"><?php echo esc_url($original_url); ?></span>
            </div>
            <?php endif; ?>
            
            <label>
                <input type="checkbox" name="vernissaria_remove_qr" value="1" />
                <?php echo esc_html__('Remove QR Code', 'vernissaria-qr'); ?>
            </label>
            
            <div style="margin-top: 10px;">
                <button type="button" class="button button-secondary" id="vernissaria_update_qr_now">
                    <?php echo esc_html__('Update QR Info Now', 'vernissaria-qr'); ?>
                </button>
                <span id="vernissaria_update_qr_status" style="display:none; margin-left: 5px; font-style: italic;"></span>
            </div>
            
            <?php if ($redirect_key) : ?>
                <div class="vernissaria-stats-section" style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #ddd;">
                    <p><strong><?php echo esc_html__('QR Code Statistics', 'vernissaria-qr'); ?>:</strong></p>
                    <p><?php echo esc_html__('Use this shortcode to display statistics', 'vernissaria-qr'); ?>:</p>
                    <code>[vernissaria_qr_stats redirect_key="<?php echo esc_attr($redirect_key); ?>"]</code>
                    <p class="description" style="margin-top: 5px;">
                        <?php echo esc_html__('Add to any post or page to show visitor statistics.', 'vernissaria-qr'); ?>
                    </p>
                </div>
            <?php endif; ?>
        <?php else : ?>
            <div style="text-align: center; background:#f0f0f0; border:1px solid #ccc; padding: 20px;">
                <p><?php echo esc_html__('QR code will be generated when this post is published.', 'vernissaria-qr'); ?></p>
            </div>
        <?php endif; ?>
    </p>
    
    <!-- Add JavaScript for the update button -->
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#vernissaria_update_qr_now').on('click', function() {
            const button = $(this);
            const status = $('#vernissaria_update_qr_status');
            
            // Get values from form
            const label = $('#vernissaria_qr_label').val();
            const campaign = $('#vernissaria_qr_campaign').val();
            const redirectKey = '<?php echo esc_js($redirect_key); ?>';
            
            button.prop('disabled', true);
            status.text('<?php echo esc_js(__('Updating...', 'vernissaria-qr')); ?>').show();
            
            // Make AJAX request to update QR info
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vernissaria_update_qr_ajax',
                    nonce: '<?php echo wp_create_nonce('vernissaria_update_qr_ajax'); ?>',
                    post_id: <?php echo intval($post->ID); ?>,
                    redirect_key: redirectKey,
                    label: label,
                    campaign: campaign
                },
                success: function(response) {
                    if (response.success) {
                        status.text('<?php echo esc_js(__('Successfully updated!', 'vernissaria-qr')); ?>');
                        // Set a timeout to hide the status message
                        setTimeout(function() {
                            status.fadeOut();
                        }, 3000);
                    } else {
                        status.text('<?php echo esc_js(__('Error updating QR info', 'vernissaria-qr')); ?>');
                    }
                    button.prop('disabled', false);
                },
                error: function() {
                    status.text('<?php echo esc_js(__('Error updating QR info', 'vernissaria-qr')); ?>');
                    button.prop('disabled', false);
                }
            });
        });
    });
    </script>
    <?php
}

/**
 * Save meta box fields
 */
function vernissaria_save_postdata($post_id) {
    if (!isset($_POST['vernissaria_meta_box_nonce']) || 
       !wp_verify_nonce(sanitize_key(wp_unslash($_POST['vernissaria_meta_box_nonce'])), 'vernissaria_meta_box')) {
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
            sanitize_text_field(wp_unslash($_POST['vernissaria_dimensions']))
        );
    }
    
    // Save year
    if (array_key_exists('vernissaria_year', $_POST)) {
        update_post_meta(
            $post_id, 
            '_vernissaria_year', 
            sanitize_text_field(wp_unslash($_POST['vernissaria_year']))
        );
    }
    
    // Save QR label (new)
    if (array_key_exists('vernissaria_qr_label', $_POST)) {
        update_post_meta(
            $post_id, 
            '_vernissaria_qr_label', 
            sanitize_text_field(wp_unslash($_POST['vernissaria_qr_label']))
        );
    }
    
    // Save QR campaign (new)
    if (array_key_exists('vernissaria_qr_campaign', $_POST)) {
        update_post_meta(
            $post_id, 
            '_vernissaria_qr_campaign', 
            sanitize_text_field(wp_unslash($_POST['vernissaria_qr_campaign']))
        );
    }
    
    // Remove QR code if requested
    if (!empty($_POST['vernissaria_remove_qr'])) {
        delete_post_meta($post_id, '_vernissaria_qr_code');
        delete_post_meta($post_id, '_vernissaria_redirect_key');
        delete_post_meta($post_id, '_vernissaria_original_url');
        
        // Clear any transients
        $redirect_key = get_post_meta($post_id, '_vernissaria_redirect_key', true);
        if ($redirect_key) {
            delete_transient('vernissaria_qr_count_' . $redirect_key);
        }
    }
    
    // If the post is being published, update QR metadata if QR code already exists
    if ('publish' === get_post_status($post_id)) {
        $redirect_key = get_post_meta($post_id, '_vernissaria_redirect_key', true);
        if ($redirect_key) {
            $post = get_post($post_id);
            vernissaria_update_qr_metadata($post, $redirect_key);
        }
    }
}
add_action('save_post', 'vernissaria_save_postdata');

/**
 * AJAX handler for updating QR info
 */
function vernissaria_update_qr_ajax() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_key($_POST['nonce']), 'vernissaria_update_qr_ajax')) {
        wp_send_json_error('Invalid nonce');
        return;
    }
    
    // Check permission
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if (!current_user_can('edit_post', $post_id)) {
        wp_send_json_error('Permission denied');
        return;
    }
    
    $redirect_key = isset($_POST['redirect_key']) ? sanitize_text_field(wp_unslash($_POST['redirect_key'])) : '';
    if (empty($redirect_key)) {
        wp_send_json_error('No redirect key provided');
        return;
    }
    
    // Get post data
    $post = get_post($post_id);
    if (!$post) {
        wp_send_json_error('Post not found');
        return;
    }
    
    // Update label and campaign meta values
    if (isset($_POST['label'])) {
        update_post_meta($post_id, '_vernissaria_qr_label', sanitize_text_field(wp_unslash($_POST['label'])));
    }
    
    if (isset($_POST['campaign'])) {
        update_post_meta($post_id, '_vernissaria_qr_campaign', sanitize_text_field(wp_unslash($_POST['campaign'])));
    }
    
    // Call the update function
    $result = vernissaria_update_qr_metadata($post, $redirect_key);
    
    if ($result) {
        wp_send_json_success();
    } else {
        wp_send_json_error();
    }
}
add_action('wp_ajax_vernissaria_update_qr_ajax', 'vernissaria_update_qr_ajax');

/**
 * Admin list filter page (custom admin page)
 */
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

/**
 * Render QR code list page with scan counts
 */
function vernissaria_render_qr_list_page() {
    echo '<div class="wrap"><h1>' . esc_html__('Posts with QR Codes', 'vernissaria-qr') . '</h1>';
    echo '<p>' . esc_html__('Overview of all content items with generated QR codes and their scan statistics.', 'vernissaria-qr') . '</p>';
   
    $enabled_types = vernissaria_get_enabled_post_types();
    $total_count = 0;
    $total_scans = 0;

    foreach ($enabled_types as $type) {
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required to find posts with QR codes
        $query = new WP_Query([
            'post_type' => $type,
            'meta_query' => [[
                'key' => '_vernissaria_qr_code',
                'compare' => 'EXISTS'
            ]],
            'posts_per_page' => -1
        ]);

        if ($query->have_posts()) {
            $post_type_obj = get_post_type_object($type);
            $type_name = $post_type_obj ? $post_type_obj->labels->name : ucfirst($type);
            
            echo '<div class="vernissaria-qr-list">';
            echo '<h2>' . esc_html($type_name) . '</h2>';
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>' . esc_html__('Title', 'vernissaria-qr') . '</th>';
            echo '<th>' . esc_html__('Label', 'vernissaria-qr') . '</th>';
            echo '<th>' . esc_html__('Campaign', 'vernissaria-qr') . '</th>';
            echo '<th>' . esc_html__('Dimensions', 'vernissaria-qr') . '</th>';
            echo '<th>' . esc_html__('Year', 'vernissaria-qr') . '</th>';
            echo '<th>' . esc_html__('Scan Count', 'vernissaria-qr') . '</th>';
            echo '<th>' . esc_html__('Actions', 'vernissaria-qr') . '</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                $total_count++;
                
                // Get post meta
                $dimensions = get_post_meta($post_id, '_vernissaria_dimensions', true);
                $year = get_post_meta($post_id, '_vernissaria_year', true);
                $redirect_key = get_post_meta($post_id, '_vernissaria_redirect_key', true);
                $qr_label = get_post_meta($post_id, '_vernissaria_qr_label', true);
                $qr_campaign = get_post_meta($post_id, '_vernissaria_qr_campaign', true);
                
                // Get scan count
                $scan_count = 0;
                if ($redirect_key) {
                    $count = vernissaria_get_qr_scan_count($redirect_key);
                    if ($count !== false) {
                        $scan_count = $count;
                        $total_scans += $scan_count;
                    }
                }
                
                echo '<tr>';
                echo '<td>' . esc_html(get_the_title()) . '</td>';
                echo '<td>' . ($qr_label ? esc_html($qr_label) : '<span class="vernissaria-qr-na">' . esc_html__('N/A', 'vernissaria-qr') . '</span>') . '</td>';
                echo '<td>' . ($qr_campaign ? esc_html($qr_campaign) : '<span class="vernissaria-qr-na">' . esc_html__('N/A', 'vernissaria-qr') . '</span>') . '</td>';
                echo '<td>' . ($dimensions ? esc_html($dimensions) : '<span class="vernissaria-qr-na">' . esc_html__('N/A', 'vernissaria-qr') . '</span>') . '</td>';
                echo '<td>' . ($year ? esc_html($year) : '<span class="vernissaria-qr-na">' . esc_html__('N/A', 'vernissaria-qr') . '</span>') . '</td>';
                
                if ($redirect_key) {
                    echo '<td><span class="vernissaria-qr-count">' . intval($scan_count) . '</span></td>';
                } else {
                    echo '<td><span class="vernissaria-qr-na">' . esc_html__('No data', 'vernissaria-qr') . '</span></td>';
                }
                
                echo '<td>';
                echo '<a href="' . esc_url(get_edit_post_link()) . '" class="button button-small">' . esc_html__('Edit', 'vernissaria-qr') . '</a> ';
                echo '<a href="' . esc_url(get_permalink()) . '" class="button button-small" target="_blank">' . esc_html__('View', 'vernissaria-qr') . '</a>';
                echo '</td>';
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
            echo '</div>';
        }
    }

    if ($total_count === 0) {
        echo '<div class="notice notice-info"><p>' . esc_html__('No content with QR codes found.', 'vernissaria-qr') . '</p></div>';
    } else {
        // Show summary at the bottom
        echo '<div class="vernissaria-qr-summary" style="margin-top: 30px; padding: 15px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 3px;">';
        echo '<h3>' . esc_html__('Summary', 'vernissaria-qr') . '</h3>';
        echo '<p><strong>' . esc_html__('Total QR Codes', 'vernissaria-qr') . ':</strong> ' . intval($total_count) . '</p>';
        echo '<p><strong>' . esc_html__('Total Scans', 'vernissaria-qr') . ':</strong> ' . intval($total_scans) . '</p>';
        echo '<p><strong>' . esc_html__('Average Scans per QR Code', 'vernissaria-qr') . ':</strong> ' . 
        esc_html($total_count > 0 ? round($total_scans / $total_count, 1) : 0) . '</p>';
        echo '</div>';
    }

    echo '</div>';
    wp_reset_postdata();
}

/**
 * Set defaults for QR label and campaign when publishing
 */
function vernissaria_set_qr_defaults($post_id) {
    // Skip if this is an autosave or not a main post
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Only proceed for enabled post types
    $post_type = get_post_type($post_id);
    $enabled_types = vernissaria_get_enabled_post_types();
    if (!in_array($post_type, $enabled_types)) {
        return;
    }
    
    // Only proceed if the post is published
    if (get_post_status($post_id) !== 'publish') {
        return;
    }
    
    // Set default label if it doesn't exist
    if (!get_post_meta($post_id, '_vernissaria_qr_label', true)) {
        update_post_meta($post_id, '_vernissaria_qr_label', get_the_title($post_id));
    }
    
    // Set default campaign if it doesn't exist
    if (!get_post_meta($post_id, '_vernissaria_qr_campaign', true)) {
        update_post_meta($post_id, '_vernissaria_qr_campaign', $post_type);
    }
}
add_action('save_post', 'vernissaria_set_qr_defaults', 10);