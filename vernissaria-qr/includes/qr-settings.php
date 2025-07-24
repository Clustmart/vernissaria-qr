<?php
/**
 * QR Code Settings Page
 * 
 * Handles the admin settings page and dashboard widget
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get enabled post types
 */
function vernissaria_get_enabled_post_types() {
    $types = get_option('vernissaria_enabled_post_types', ['post']);
    if (!is_array($types) || empty($types)) {
        return ['post']; // Default to post if no valid setting
    }
    return $types;
}

/**
 * Check if dashboard widget is enabled
 */
function vernissaria_is_dashboard_widget_enabled() {
    return get_option('vernissaria_show_dashboard_widget', true);
}

/**
 * Check if visitor stats dashboard widget is enabled
 */
function vernissaria_is_visitor_stats_widget_enabled() {
    return get_option('vernissaria_show_visitor_stats_widget', true);
}

/**
 * Register admin scripts and styles
 */
function vernissaria_register_admin_assets() {
    // Register Chart.js for admin dashboard widgets
    wp_register_script(
        'vernissaria-admin-chart-js',
        VERNISSARIA_QR_URL . 'assets/js/chart.min.js',
        array(),
        '3.7.1',
        array(
            'strategy' => 'defer',
            'in_footer' => true
        )
    );
}
add_action('admin_enqueue_scripts', 'vernissaria_register_admin_assets');

/**
 * Add settings page to admin menu
 */
function vernissaria_add_settings_page() {
    add_options_page(
        __('Vernissaria QR Settings', 'vernissaria-qr'),
        __('Vernissaria QR', 'vernissaria-qr'),
        'manage_options',
        'vernissaria-qr',
        'vernissaria_render_settings_page'
    );
}
add_action('admin_menu', 'vernissaria_add_settings_page');

/**
 * Register settings
 */
function vernissaria_register_settings() {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Settings API handles sanitization
    register_setting(
        'vernissaria_settings',
        'vernissaria_enabled_post_types',
        [
            'type' => 'array',
            'sanitize_callback' => 'vernissaria_sanitize_post_types',
            'default' => ['post'],
        ]
    );
    
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Settings API handles sanitization
    register_setting(
        'vernissaria_settings',
        'vernissaria_show_dashboard_widget',
        [
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => true,
        ]
    );
    
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Settings API handles sanitization
    register_setting(
        'vernissaria_settings',
        'vernissaria_show_visitor_stats_widget',
        [
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => true,
        ]
    );
    
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Settings API handles sanitization
    register_setting(
        'vernissaria_settings',
        'vernissaria_api_url',
        [
            'type' => 'string',
            'default' => 'https://vernissaria.qraft.link',
            'sanitize_callback' => 'esc_url_raw',
        ]
    );
    
    add_settings_section(
        'vernissaria_general_section',
        __('General Settings', 'vernissaria-qr'),
        'vernissaria_general_section_callback',
        'vernissaria_settings'
    );
    
    add_settings_field(
        'vernissaria_enabled_post_types',
        __('Enable for post types', 'vernissaria-qr'),
        'vernissaria_post_types_field_callback',
        'vernissaria_settings',
        'vernissaria_general_section'
    );
    
    add_settings_field(
        'vernissaria_show_dashboard_widget',
        __('Dashboard Widgets', 'vernissaria-qr'),
        'vernissaria_dashboard_field_callback',
        'vernissaria_settings',
        'vernissaria_general_section'
    );
    
    add_settings_field(
        'vernissaria_api_url',
        __('API URL', 'vernissaria-qr'),
        'vernissaria_api_url_field_callback',
        'vernissaria_settings',
        'vernissaria_general_section'
    );
    
    // Add section for statistics settings
    add_settings_section(
        'vernissaria_stats_section',
        __('Statistics Settings', 'vernissaria-qr'),
        'vernissaria_stats_section_callback',
        'vernissaria_settings'
    );
    
    // Add shortcode help field
    add_settings_field(
        'vernissaria_shortcode_help',
        __('Shortcode Usage', 'vernissaria-qr'),
        'vernissaria_shortcode_help_callback',
        'vernissaria_settings',
        'vernissaria_stats_section'
    );
}
add_action('admin_init', 'vernissaria_register_settings');

/**
 * Sanitize post types option
 */
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

/**
 * General settings section callback
 */
function vernissaria_general_section_callback() {
    echo '<p>' . esc_html__('Configure general settings for QR code generation.', 'vernissaria-qr') . '</p>';
}

/**
 * Stats section callback
 */
function vernissaria_stats_section_callback() {
    echo '<p>' . esc_html__('Configure settings for QR code statistics.', 'vernissaria-qr') . '</p>';
}

/**
 * Render post types field
 */
function vernissaria_post_types_field_callback() {
    $post_types = get_post_types(['public' => true], 'objects');
    $enabled_types = vernissaria_get_enabled_post_types();
    
    echo '<fieldset>';
    foreach ($post_types as $slug => $post_type) {
         echo '<label style="display: block; margin-bottom: 5px;">';
        echo '<input type="checkbox" name="vernissaria_enabled_post_types[]" value="' . esc_attr($slug) . '" ' . checked(in_array($slug, $enabled_types), true, false) . '> ';

        echo esc_html($post_type->labels->name);
        echo '</label>';
    }
    echo '<p class="description">' . esc_html__('Select which post types should have QR code functionality.', 'vernissaria-qr') . '</p>';
    echo '</fieldset>';
}

/**
 * Render dashboard widget field
 */
function vernissaria_dashboard_field_callback() {
    $widget_enabled = vernissaria_is_dashboard_widget_enabled();
    $visitor_stats_enabled = vernissaria_is_visitor_stats_widget_enabled();
    
    echo '<fieldset>';
    echo '<label style="display: block; margin-bottom: 5px;">';
    echo '<input type="checkbox" name="vernissaria_show_dashboard_widget" value="1" ' . checked($widget_enabled, true, false) . '> ';
    echo esc_html__('Show QR code summary widget on dashboard', 'vernissaria-qr');
    echo '</label>';
    
    echo '<label style="display: block; margin-bottom: 5px;">';
    echo '<input type="checkbox" name="vernissaria_show_visitor_stats_widget" value="1" ' . checked($visitor_stats_enabled, true, false) . '> ';
    echo esc_html__('Show visitor statistics widget (last 30 days) on dashboard', 'vernissaria-qr');
    echo '</label>';
    echo '</fieldset>';
}

/**
 * Render API URL field
 */
function vernissaria_api_url_field_callback() {
    $api_url = get_option('vernissaria_api_url', 'https://vernissaria.qraft.link');
    echo '<input type="url" name="vernissaria_api_url" value="' . esc_attr($api_url) . '" class="regular-text">';
    echo '<p class="description">' . esc_html__('The URL of the QR code generation API.', 'vernissaria-qr') . '</p>';
    echo '<p style="color: #dc3232; font-weight: bold;">' . esc_html__('WARNING: Changing this URL may break plugin functionality. Only modify if you are using a custom API installation and know what you are doing.', 'vernissaria-qr') . '</p>';
}

/**
 * Render shortcode help field
 */
function vernissaria_shortcode_help_callback() {
    ?>
    <div class="vernissaria-shortcode-help">
        <h3><?php echo esc_html__('QR Code Statistics Shortcode', 'vernissaria-qr'); ?></h3>
        <p><?php echo esc_html__('Use this shortcode to display QR code statistics on any post or page:', 'vernissaria-qr'); ?></p>
        <code>[vernissaria_qr_stats redirect_key="YOUR_KEY"]</code>
        
        <h4><?php echo esc_html__('Available Options', 'vernissaria-qr'); ?></h4>
        <table class="form-table">
            <tr>
                <th scope="row">redirect_key</th>
                <td><code><?php echo esc_html__('required', 'vernissaria-qr'); ?></code> <?php echo esc_html__('The unique key of your QR code', 'vernissaria-qr'); ?></td>
            </tr>
            <tr>
                <th scope="row">show_chart</th>
                <td><code>yes</code> <?php echo esc_html__('or', 'vernissaria-qr'); ?> <code>no</code> (<?php echo esc_html__('default: yes', 'vernissaria-qr'); ?>) - <?php echo esc_html__('Show visualization charts', 'vernissaria-qr'); ?></td>
            </tr>
            <tr>
                <th scope="row">show_recent</th>
                <td><code>yes</code> <?php echo esc_html__('or', 'vernissaria-qr'); ?> <code>no</code> (<?php echo esc_html__('default: no', 'vernissaria-qr'); ?>) - <?php echo esc_html__('Show table of recent scans', 'vernissaria-qr'); ?></td>
            </tr>
            <tr>
                <th scope="row">style</th>
                <td><code>default</code> <?php echo esc_html__('or', 'vernissaria-qr'); ?> <code>dark</code> (<?php echo esc_html__('default: default', 'vernissaria-qr'); ?>) - <?php echo esc_html__('Choose display style', 'vernissaria-qr'); ?></td>
            </tr>
        </table>
        
        <h4><?php echo esc_html__('Example', 'vernissaria-qr'); ?></h4>
        <code>[vernissaria_qr_stats redirect_key="abc123" show_chart="yes" show_recent="yes" style="dark"]</code>
        
        <p class="description"><?php echo esc_html__('You can find the redirect key for each QR code in the Artwork Details metabox when editing a post.', 'vernissaria-qr'); ?></p>
    </div>
    <?php
}

/**
 * Render settings page
 */
function vernissaria_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Verify nonce for settings-updated parameter
    $settings_updated = false;
    if (isset($_GET['settings-updated']) && 
        isset($_GET['_wpnonce']) && 
        wp_verify_nonce(sanitize_key($_GET['_wpnonce']), 'options-options')) {
        $settings_updated = true;
    }
    
    // Show settings saved message
    if ($settings_updated) {
        add_settings_error(
            'vernissaria_messages',
            'vernissaria_message',
            __('Settings Saved', 'vernissaria-qr'),
            'updated'
        );
    }
    
    // Show settings errors
    settings_errors('vernissaria_messages');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('vernissaria_settings');
            do_settings_sections('vernissaria_settings');
            submit_button(__('Save Settings', 'vernissaria-qr'));
            ?>
        </form>
    </div>
    <?php
}

/**
 * Dashboard widget with post type breakdown
 */
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
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required to find posts with QR codes
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
    echo '<p>' . esc_html__('Total content with QR codes', 'vernissaria-qr') . ': <strong>' . intval($total) . '</strong></p>';
    
    // Output the breakdown if there are any QR codes
    if ($total > 0) {
        echo '<div style="margin-top: 10px;">';
        echo '<p style="margin-bottom: 5px;"><strong>' . esc_html__('Breakdown by type', 'vernissaria-qr') . ':</strong></p>';
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
        echo '<p>' . esc_html__('No content with QR codes found.', 'vernissaria-qr') . '</p>';
    }
}

/**
 * Visitor stats dashboard widget for last 30 days
 */
function vernissaria_visitor_stats_widget() {
    // Enqueue Chart.js for admin
    wp_enqueue_script('vernissaria-admin-chart-js');

    // Get API URL from settings
    $api_url = get_option('vernissaria_api_url', 'https://vernissaria.qraft.link');
    $domain = wp_parse_url(get_site_url(), PHP_URL_HOST);
    $endpoint = $api_url . '/stats/daily?days=30&domain='. urlencode($domain);
    
    // Make API request
    $response = wp_remote_get($endpoint);
    
    // Check for errors
    if (is_wp_error($response)) {
        echo '<p>' . esc_html__('Error fetching visitor statistics', 'vernissaria-qr') . ': ' . esc_html($response->get_error_message()) . '</p>';
        return;
    }
    
    // Check status code
    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code !== 200) {
        echo '<p>' . esc_html__('Error fetching visitor statistics', 'vernissaria-qr') . ' (' . intval($status_code) . ')</p>';
        return;
    }
    
    // Parse response
    $stats = json_decode(wp_remote_retrieve_body($response), true);
    
    if (!$stats || !isset($stats['daily_visitors'])) {
        echo '<p>' . esc_html__('No visitor data available or invalid API response', 'vernissaria-qr') . '</p>';
        return;
    }
    
    // Extract data for the chart
    $days = array();
    $visitors = array();
    $scans = array();
    
    foreach ($stats['daily_visitors'] as $day) {
        $days[] = isset($day['date']) ? $day['date'] : '';
        $visitors[] = isset($day['unique_visitors']) ? intval($day['unique_visitors']) : 0;
        $scans[] = isset($day['total_scans']) ? intval($day['total_scans']) : 0;
    }
    
    // Calculate totals
    $total_scans = array_sum($scans);
    $total_visitors = array_sum($visitors);
    
    // Generate a unique ID for the chart
    $chart_id = 'vernissaria-visitor-chart-' . wp_rand();
    
    // Display summary
    echo '<div class="vernissaria-stats-summary">';
    echo '<p>' . esc_html__('Last 30 days', 'vernissaria-qr') . ':</p>';
    echo '<div class="stats-overview" style="display: flex; justify-content: space-between;">';
    echo '<div style="text-align: center;"><span style="font-size: 24px; font-weight: bold; color: #1e88e5;">' . intval($total_visitors) . '</span><br>' . esc_html__('Unique Visitors', 'vernissaria-qr') . '</div>';
    echo '<div style="text-align: center;"><span style="font-size: 24px; font-weight: bold; color: #43a047;">' . intval($total_scans) . '</span><br>' . esc_html__('Total Scans', 'vernissaria-qr') . '</div>';
    echo '</div>';
    echo '</div>';
    
    // Chart container
    echo '<div style="margin-top: 15px;">';
    echo '<canvas id="' . esc_attr($chart_id) . '" style="width: 100%; height: 250px;"></canvas>';
    echo '</div>';
    
    // Prepare chart data for inline script
    $chart_data = array(
        'chartId' => $chart_id,
        'days' => $days,
        'visitors' => $visitors,
        'scans' => $scans,
        'labels' => array(
            'uniqueVisitors' => __('Unique Visitors', 'vernissaria-qr'),
            'totalScans' => __('Total Scans', 'vernissaria-qr')
        )
    );
    
    // Add inline JavaScript using wp_add_inline_script
    $chart_js = '
    document.addEventListener("DOMContentLoaded", function() {
        var chartData = ' . wp_json_encode($chart_data) . ';
        
        new Chart(document.getElementById(chartData.chartId), {
            type: "line",
            data: {
                labels: chartData.days,
                datasets: [
                    {
                        label: chartData.labels.uniqueVisitors,
                        data: chartData.visitors,
                        borderColor: "#1e88e5",
                        backgroundColor: "rgba(30, 136, 229, 0.1)",
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: chartData.labels.totalScans,
                        data: chartData.scans,
                        borderColor: "#43a047",
                        backgroundColor: "rgba(67, 160, 71, 0.1)",
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: "top",
                    },
                    tooltip: {
                        mode: "index",
                        intersect: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    });';
    
    // Add inline script to the enqueued Chart.js
    wp_add_inline_script('vernissaria-admin-chart-js', $chart_js);
    
    // Add link to view detailed stats
    echo '<p style="margin-top: 15px; text-align: right;">';
    echo '<a href="' . esc_url(admin_url('options-general.php?page=vernissaria-qr')) . '#vernissaria_stats_section">' . esc_html__('View detailed statistics', 'vernissaria-qr') . ' &rarr;</a>';
    echo '</p>';
}

/**
 * Add the widgets to the dashboard if enabled
 */
function vernissaria_add_dashboard_widgets() {
    if (vernissaria_is_dashboard_widget_enabled()) {
        wp_add_dashboard_widget(
            'vernissaria_qr_widget',
            __('Vernissaria QR Codes', 'vernissaria-qr'),
            'vernissaria_dashboard_widget'
        );
    }
    
    if (vernissaria_is_visitor_stats_widget_enabled()) {
        wp_add_dashboard_widget(
            'vernissaria_visitor_stats_widget',
            __('QR Code Visitors (Last 30 Days)', 'vernissaria-qr'),
            'vernissaria_visitor_stats_widget'
        );
    }
}
add_action('wp_dashboard_setup', 'vernissaria_add_dashboard_widgets');