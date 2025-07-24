<?php
/**
 * QR Code Statistics Functionality
 * 
 * Handles the shortcode and rendering of QR code statistics
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register scripts and styles for the frontend
 */
function vernissaria_register_frontend_assets() {
    // Register Chart.js script
    wp_register_script(
        'vernissaria-chart-js',
        VERNISSARIA_QR_URL . 'assets/js/chart.min.js',
        array(),
        '3.7.1',
        array(
            'strategy' => 'defer',
            'in_footer' => true
        )
    );
    
    // Register QR stats styles
    wp_register_style(
        'vernissaria-qr-stats',
        VERNISSARIA_QR_URL . 'assets/css/qr-stats.css',
        array(),
        VERNISSARIA_QR_VERSION
    );
}
add_action('wp_enqueue_scripts', 'vernissaria_register_frontend_assets');

/**
 * Register shortcode for displaying QR stats
 */
add_shortcode('vernissaria_qr_stats', 'vernissaria_qr_stats_shortcode');

/**
 * Shortcode handler for QR code statistics
 */
function vernissaria_qr_stats_shortcode($atts) {
    // Get attributes
    $atts = shortcode_atts(
        array(
            'redirect_key' => '',
            'show_chart' => 'yes',
            'show_recent' => 'no',
            'style' => 'default'
        ),
        $atts,
        'vernissaria_qr_stats'
    );
    
    // Validate redirect key
    if (empty($atts['redirect_key'])) {
        return '<p>' . esc_html__('Error: No redirect key provided', 'vernissaria-qr') . '</p>';
    }
    
    // Get the site domain
    $site_domain = wp_parse_url(get_site_url(), PHP_URL_HOST);

    // API settings
    $api_url = get_option('vernissaria_api_url', 'https://vernissaria.qraft.link');
    $endpoint = $api_url . '/qr/' . $atts['redirect_key'];
    
    // Add domain parameter to the API request
    $endpoint = add_query_arg('domain', $site_domain, $endpoint);
    
    // Make API request
    $response = wp_remote_get($endpoint);
    
    // Check for errors
    if (is_wp_error($response)) {
        return '<p>' . esc_html__('Error fetching QR code statistics', 'vernissaria-qr') . ': ' . $response->get_error_message() . '</p>';
    }
    
    // Check status code
    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code !== 200) {
        $error_message = wp_remote_retrieve_response_message($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['detail'])) {
            $error_message = $body['detail'];
        }
        return '<p>' . esc_html__('Error', 'vernissaria-qr') . ': ' . $error_message . ' (' . esc_html__('Code', 'vernissaria-qr') . ': ' . $status_code . ')</p>';
    }
    
    // Parse response
    $stats = json_decode(wp_remote_retrieve_body($response), true);
    
    if (!$stats) {
        return '<p>' . esc_html__('Error: Unable to parse API response', 'vernissaria-qr') . '</p>';
    }
    
    // Enqueue scripts and styles
    wp_enqueue_style('vernissaria-qr-stats');
    
    if ($atts['show_chart'] === 'yes') {
        wp_enqueue_script('vernissaria-chart-js');
    }
    
    // Generate unique IDs for charts
    $chart_id = 'qr-chart-' . uniqid();
    
    // Start building output
    $output = '<div class="vernissaria-qr-stats ' . esc_attr($atts['style']) . '">';
    
    // Add title if label exists
    if (!empty($stats['label'])) {
        $output .= '<h3>' . esc_html($stats['label']) . ' ' . esc_html__('Statistics', 'vernissaria-qr') . '</h3>';
    } else {
        $output .= '<h3>' . esc_html__('QR Code Statistics', 'vernissaria-qr') . '</h3>';
    }
    
    // Main stats
    $output .= '<div class="stats-overview">';
    $output .= '<div class="stat-item"><span class="stat-value">' . esc_html($stats['scan_count']) . '</span><span class="stat-label">' . esc_html__('Total Scans', 'vernissaria-qr') . '</span></div>';
    $output .= '<div class="stat-item"><span class="stat-value">' . esc_html($stats['unique_visitors']) . '</span><span class="stat-label">' . esc_html__('Unique Visitors', 'vernissaria-qr') . '</span></div>';
    
    if (!empty($stats['countries'])) {
        $output .= '<div class="stat-item"><span class="stat-value">' . count($stats['countries']) . '</span><span class="stat-label">' . esc_html__('Countries', 'vernissaria-qr') . '</span></div>';
    }
    
    $output .= '</div>';
    
    // Last scanned info
    if (!empty($stats['updated_at'])) {
        $last_scan = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($stats['updated_at']));
        $output .= '<p class="last-scanned">' . esc_html__('Last scanned', 'vernissaria-qr') . ': ' . $last_scan . '</p>';
    }
    
    // Add device chart
    if ($atts['show_chart'] === 'yes') {
        $output .= '<div class="charts-container">';
        $output .= '<div class="chart-wrapper">';
        $output .= '<canvas id="' . esc_attr($chart_id) . '-devices"></canvas>';
        $output .= '</div>';
        
        // Add browser chart if we have data
        if (!empty($stats['browsers'])) {
            $output .= '<div class="chart-wrapper">';
            $output .= '<canvas id="' . esc_attr($chart_id) . '-browsers"></canvas>';
            $output .= '</div>';
        }
        $output .= '</div>';
        
        // Prepare chart data for inline script
        $device_data = array(
            'mobile' => intval($stats['devices']['mobile']),
            'tablet' => intval($stats['devices']['tablet']),
            'desktop' => intval($stats['devices']['desktop'])
        );
        
        $browser_data = array();
        if (!empty($stats['browsers'])) {
            $i = 0;
            foreach($stats['browsers'] as $browser => $count) {
                $browser_data[$browser] = intval($count);
                $i++;
                if ($i >= 5) break; // Limit to top 5 browsers
            }
        }
        
        // Prepare JavaScript data
        $chart_data = array(
            'chartId' => $chart_id,
            'deviceData' => $device_data,
            'browserData' => $browser_data,
            'labels' => array(
                'mobile' => __('Mobile', 'vernissaria-qr'),
                'tablet' => __('Tablet', 'vernissaria-qr'),
                'desktop' => __('Desktop', 'vernissaria-qr'),
                'deviceTypes' => __('Device Types', 'vernissaria-qr'),
                'browsers' => __('Browsers', 'vernissaria-qr')
            )
        );
        
        // Add inline JavaScript for charts using wp_add_inline_script
        $chart_js = '
        document.addEventListener("DOMContentLoaded", function() {
            var chartData = ' . wp_json_encode($chart_data) . ';
            
            // Device chart
            new Chart(document.getElementById(chartData.chartId + "-devices"), {
                type: "doughnut",
                data: {
                    labels: [chartData.labels.mobile, chartData.labels.tablet, chartData.labels.desktop],
                    datasets: [{
                        data: [chartData.deviceData.mobile, chartData.deviceData.tablet, chartData.deviceData.desktop],
                        backgroundColor: ["#4e73df", "#1cc88a", "#36b9cc"]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: "bottom"
                        },
                        title: {
                            display: true,
                            text: chartData.labels.deviceTypes
                        }
                    }
                }
            });
            
            // Browser chart if we have data
            if (Object.keys(chartData.browserData).length > 0) {
                var browserLabels = Object.keys(chartData.browserData);
                var browserValues = Object.values(chartData.browserData);
                var browserColors = ["#4e73df", "#1cc88a", "#36b9cc", "#f6c23e", "#e74a3b"];
                
                new Chart(document.getElementById(chartData.chartId + "-browsers"), {
                    type: "doughnut",
                    data: {
                        labels: browserLabels,
                        datasets: [{
                            data: browserValues,
                            backgroundColor: browserColors.slice(0, browserLabels.length)
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: "bottom"
                            },
                            title: {
                                display: true,
                                text: chartData.labels.browsers
                            }
                        }
                    }
                });
            }
        });';
        
        // Add inline script to the enqueued Chart.js
        wp_add_inline_script('vernissaria-chart-js', $chart_js);
    }
    
    // Add recent scans table if enabled
    if ($atts['show_recent'] === 'yes' && !empty($stats['recent_scans'])) {
        $output .= '<div class="recent-scans">';
        $output .= '<h4>' . esc_html__('Recent Scans', 'vernissaria-qr') . '</h4>';
        $output .= '<table class="qr-stats-table">';
        $output .= '<tr><th>' . esc_html__('Date/Time', 'vernissaria-qr') . '</th><th>' . esc_html__('Country', 'vernissaria-qr') . '</th><th>' . esc_html__('Device', 'vernissaria-qr') . '</th><th>' . esc_html__('Browser', 'vernissaria-qr') . '</th></tr>';
        
        foreach ($stats['recent_scans'] as $scan) {
            $scan_time = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($scan['timestamp']));
            $output .= '<tr>';
            $output .= '<td>' . esc_html($scan_time) . '</td>';
            $output .= '<td>' . esc_html($scan['country']) . '</td>';
            $output .= '<td>' . esc_html(ucfirst($scan['device_type'])) . '</td>';
            $output .= '<td>' . esc_html($scan['browser']) . '</td>';
            $output .= '</tr>';
        }
        
        $output .= '</table>';
        $output .= '</div>';
    }
    
    $output .= '</div>';
    
    return $output;
}

/**
 * Get the plugin domain for QR code statistics
 * Uses either the option set in the admin or falls back to the site domain
 */
function vernissaria_get_domain() {
    // Check if a specific domain is set in the plugin settings
    $domain = get_option('vernissaria_domain', '');
    
    // If not set, use the site domain
    if (empty($domain)) {
        $domain = wp_parse_url(get_site_url(), PHP_URL_HOST);
    }
    
    return $domain;
}