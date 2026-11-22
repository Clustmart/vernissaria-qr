<?php
/**
 * Vernissaria API access: API key handling and one-click "Connect".
 *
 * From 15 January 2027 the Vernissaria API requires an API key. Each site gets
 * its own key, valid only for its own domain. "Connect" obtains it:
 *
 *  1. this site asks the API for a one-time code (POST /keys/connect/start);
 *  2. the code is published for a few minutes at
 *     ?rest_route=/vernissaria/v1/connect-challenge on this site;
 *  3. the API reads it back (POST /keys/connect/verify), which proves this site
 *     controls its domain, and returns the key. The key is stored in an option.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

define('VERNISSARIA_API_KEY_OPTION', 'vernissaria_api_key');
define('VERNISSARIA_API_KEY_DOMAIN_OPTION', 'vernissaria_api_key_domain');
define('VERNISSARIA_CONNECT_TRANSIENT', 'vernissaria_qr_connect_challenge');
define('VERNISSARIA_KEY_DEADLINE', '2027-01-15');

/**
 * Base URL of the API, without trailing slash.
 */
function vernissaria_api_base() {
    return rtrim(get_option('vernissaria_api_url', 'https://vernissaria.qraft.link'), '/');
}

/**
 * The stored API key ('' if not connected).
 */
function vernissaria_get_api_key() {
    return (string) get_option(VERNISSARIA_API_KEY_OPTION, '');
}

/**
 * Add the API key header to wp_remote_* arguments.
 *
 * @param array $args wp_remote_* arguments
 * @return array
 */
function vernissaria_api_args($args = array()) {
    $api_key = vernissaria_get_api_key();
    if ($api_key !== '') {
        if (!isset($args['headers']) || !is_array($args['headers'])) {
            $args['headers'] = array();
        }
        $args['headers']['X-API-Key'] = $api_key;
    }
    return $args;
}

/**
 * The human-readable error from an API response (FastAPI puts it in "detail").
 *
 * @param array|WP_Error $response
 * @return string
 */
function vernissaria_api_error_message($response) {
    if (is_wp_error($response)) {
        return $response->get_error_message();
    }
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (is_array($body) && !empty($body['detail']) && is_string($body['detail'])) {
        return $body['detail'];
    }
    return sprintf(
        /* translators: %d: HTTP status code */
        __('The Vernissaria API returned status %d.', 'vernissaria-qr'),
        (int) wp_remote_retrieve_response_code($response)
    );
}

/**
 * Whether an API response means "you need (another) API key".
 */
function vernissaria_api_is_auth_error($response) {
    if (is_wp_error($response)) {
        return false;
    }
    $code = (int) wp_remote_retrieve_response_code($response);
    return $code === 401 || $code === 403;
}

/**
 * Host and install path of this site as seen from outside (home URL).
 *
 * @return array{host: string, path: string}
 */
function vernissaria_site_identity() {
    $home = home_url('/');
    $host = (string) wp_parse_url($home, PHP_URL_HOST);
    $path = (string) wp_parse_url($home, PHP_URL_PATH);
    return array(
        'host' => strtolower($host),
        'path' => $path === '' ? '/' : $path,
    );
}

/**
 * POST JSON to the API and decode the answer.
 *
 * @return array|WP_Error decoded body on HTTP 200
 */
function vernissaria_api_post_json($path, $data) {
    $response = wp_remote_post(vernissaria_api_base() . $path, array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ),
        'body' => wp_json_encode($data),
        'timeout' => 20,
    ));
    if (is_wp_error($response)) {
        return $response;
    }
    if ((int) wp_remote_retrieve_response_code($response) !== 200) {
        return new WP_Error('vernissaria_api', vernissaria_api_error_message($response));
    }
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (!is_array($body)) {
        return new WP_Error('vernissaria_api', __('Invalid answer from the Vernissaria API.', 'vernissaria-qr'));
    }
    return $body;
}

/**
 * Run the whole connect flow. Stores the key on success.
 *
 * @return array|WP_Error {domain, key_prefix}
 */
function vernissaria_connect() {
    $site = vernissaria_site_identity();

    $started = vernissaria_api_post_json('/keys/connect/start', array(
        'host' => $site['host'],
        'site_path' => $site['path'],
    ));
    if (is_wp_error($started)) {
        return $started;
    }
    if (empty($started['challenge']) || empty($started['connect_token'])) {
        return new WP_Error('vernissaria_api', __('Invalid answer from the Vernissaria API.', 'vernissaria-qr'));
    }

    // Published by vernissaria_rest_connect_challenge() while the API checks it.
    set_transient(VERNISSARIA_CONNECT_TRANSIENT, (string) $started['challenge'], 10 * MINUTE_IN_SECONDS);

    $verified = vernissaria_api_post_json('/keys/connect/verify', array(
        'connect_token' => $started['connect_token'],
    ));
    delete_transient(VERNISSARIA_CONNECT_TRANSIENT);

    if (is_wp_error($verified)) {
        return $verified;
    }
    if (empty($verified['api_key'])) {
        return new WP_Error('vernissaria_api', __('Invalid answer from the Vernissaria API.', 'vernissaria-qr'));
    }

    // Not autoloaded: only needed when talking to the API.
    update_option(VERNISSARIA_API_KEY_OPTION, sanitize_text_field($verified['api_key']), false);
    update_option(VERNISSARIA_API_KEY_DOMAIN_OPTION, sanitize_text_field($verified['domain']), false);

    return array(
        'domain' => sanitize_text_field($verified['domain']),
        'key_prefix' => sanitize_text_field(isset($verified['key_prefix']) ? $verified['key_prefix'] : ''),
    );
}

/**
 * Public REST route the API reads during "Connect".
 *
 * It only returns the short-lived one-time code (or 404 when no connect is
 * running). The code is useless to anyone else: it only completes the connect
 * attempt this site started, and the key goes back to this site.
 */
function vernissaria_register_connect_route() {
    register_rest_route('vernissaria/v1', '/connect-challenge', array(
        'methods' => 'GET',
        'callback' => 'vernissaria_rest_connect_challenge',
        'permission_callback' => '__return_true',
    ));
}
add_action('rest_api_init', 'vernissaria_register_connect_route');

function vernissaria_rest_connect_challenge() {
    $challenge = get_transient(VERNISSARIA_CONNECT_TRANSIENT);
    if (!$challenge) {
        return new WP_Error('vernissaria_no_challenge', 'No connect in progress.', array('status' => 404));
    }
    $response = rest_ensure_response(array('challenge' => $challenge));
    $response->header('Cache-Control', 'no-store');
    return $response;
}

/**
 * AJAX: Connect / Disconnect buttons on the settings page.
 */
function vernissaria_ajax_connect() {
    check_ajax_referer('vernissaria_connect', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Insufficient permissions', 'vernissaria-qr')), 403);
    }

    $result = vernissaria_connect();
    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
    }
    wp_send_json_success($result);
}
add_action('wp_ajax_vernissaria_connect', 'vernissaria_ajax_connect');

function vernissaria_ajax_disconnect() {
    check_ajax_referer('vernissaria_connect', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Insufficient permissions', 'vernissaria-qr')), 403);
    }
    delete_option(VERNISSARIA_API_KEY_OPTION);
    delete_option(VERNISSARIA_API_KEY_DOMAIN_OPTION);
    wp_send_json_success();
}
add_action('wp_ajax_vernissaria_disconnect', 'vernissaria_ajax_disconnect');

/**
 * The "API connection" box at the top of the settings page.
 */
function vernissaria_render_connection_box() {
    $api_key = vernissaria_get_api_key();
    $domain = get_option(VERNISSARIA_API_KEY_DOMAIN_OPTION, '');
    $site = vernissaria_site_identity();

    wp_enqueue_script(
        'vernissaria-connect-js',
        VERNISSARIA_QR_URL . 'assets/js/connect.js',
        array('jquery'),
        VERNISSARIA_QR_VERSION,
        true
    );
    wp_localize_script('vernissaria-connect-js', 'vernissariaConnect', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('vernissaria_connect'),
        'strings' => array(
            'connecting' => __('Connecting…', 'vernissaria-qr'),
            'connected' => __('Connected. Reloading…', 'vernissaria-qr'),
            'confirmDisconnect' => __('Disconnect this site? It will stop working with the Vernissaria API until you connect again.', 'vernissaria-qr'),
            'failed' => __('Connecting failed:', 'vernissaria-qr'),
        ),
    ));
    ?>
    <div class="vernissaria-connection card" style="max-width: 760px; margin: 16px 0; padding: 12px 20px;">
        <h2 style="margin-top: 8px;"><?php echo esc_html__('API connection', 'vernissaria-qr'); ?></h2>
        <?php if ($api_key !== ''): ?>
            <p>
                <span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span>
                <?php
                printf(
                    /* translators: 1: domain, 2: start of the API key */
                    esc_html__('Connected for %1$s (key %2$s…).', 'vernissaria-qr'),
                    '<strong>' . esc_html($domain) . '</strong>',
                    '<code>' . esc_html(substr($api_key, 0, 10)) . '</code>'
                );
                ?>
            </p>
            <p>
                <button type="button" class="button" id="vernissaria-connect"><?php echo esc_html__('Reconnect', 'vernissaria-qr'); ?></button>
                <button type="button" class="button-link" id="vernissaria-disconnect" style="margin-left: 12px;"><?php echo esc_html__('Disconnect', 'vernissaria-qr'); ?></button>
            </p>
        <?php else: ?>
            <p>
                <span class="dashicons dashicons-warning" style="color: #dba617;"></span>
                <?php
                printf(
                    /* translators: %s: date */
                    esc_html__('Not connected. From %s the Vernissaria API requires a free API key for this site. Connect once — it takes a few seconds.', 'vernissaria-qr'),
                    '<strong>' . esc_html(date_i18n(get_option('date_format'), strtotime(VERNISSARIA_KEY_DEADLINE))) . '</strong>'
                );
                ?>
            </p>
            <p>
                <button type="button" class="button button-primary" id="vernissaria-connect"><?php echo esc_html__('Connect', 'vernissaria-qr'); ?></button>
            </p>
        <?php endif; ?>
        <p class="description">
            <?php
            printf(
                /* translators: %s: domain */
                esc_html__('Connecting proves to the Vernissaria API that this site controls %s and stores a key that only works for this domain. Already printed QR codes keep working either way.', 'vernissaria-qr'),
                '<strong>' . esc_html($site['host']) . '</strong>'
            );
            ?>
        </p>
        <p id="vernissaria-connect-status" style="display: none;"></p>
    </div>
    <?php
}

/**
 * Admin notice while the site is not connected.
 *
 * Shown only on the Dashboard, the Plugins screen and this plugin's settings
 * page; dismissible (comes back after 14 days) and gone once connected.
 */
function vernissaria_key_notice() {
    if (vernissaria_get_api_key() !== '' || !current_user_can('manage_options')) {
        return;
    }
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || !in_array($screen->id, array('dashboard', 'plugins', 'settings_page_vernissaria-qr'), true)) {
        return;
    }

    $past_deadline = time() >= strtotime(VERNISSARIA_KEY_DEADLINE);
    $dismissed_until = (int) get_user_meta(get_current_user_id(), 'vernissaria_key_notice_dismissed_until', true);
    if (!$past_deadline && $dismissed_until > time()) {
        return;
    }

    $settings_url = admin_url('options-general.php?page=vernissaria-qr');
    $dismiss_url = wp_nonce_url(add_query_arg('vernissaria_dismiss_key_notice', '1'), 'vernissaria_dismiss_key_notice');
    $deadline = date_i18n(get_option('date_format'), strtotime(VERNISSARIA_KEY_DEADLINE));
    ?>
    <div class="notice <?php echo $past_deadline ? 'notice-error' : 'notice-warning'; ?>">
        <p>
            <strong><?php echo esc_html__('Vernissaria QR:', 'vernissaria-qr'); ?></strong>
            <?php
            if ($past_deadline) {
                echo esc_html__('This site is not connected, so creating QR codes, statistics and PDFs no longer work. Printed QR codes keep working.', 'vernissaria-qr');
            } else {
                printf(
                    /* translators: %s: date */
                    esc_html__('From %s the Vernissaria API requires a free API key. Connect this site once — it takes one click.', 'vernissaria-qr'),
                    esc_html($deadline)
                );
            }
            ?>
        </p>
        <p>
            <a class="button button-primary" href="<?php echo esc_url($settings_url); ?>"><?php echo esc_html__('Connect now', 'vernissaria-qr'); ?></a>
            <?php if (!$past_deadline): ?>
                <a href="<?php echo esc_url($dismiss_url); ?>" style="margin-left: 12px;"><?php echo esc_html__('Remind me later', 'vernissaria-qr'); ?></a>
            <?php endif; ?>
        </p>
    </div>
    <?php
}
add_action('admin_notices', 'vernissaria_key_notice');

function vernissaria_handle_key_notice_dismiss() {
    if (empty($_GET['vernissaria_dismiss_key_notice'])) {
        return;
    }
    check_admin_referer('vernissaria_dismiss_key_notice');
    update_user_meta(get_current_user_id(), 'vernissaria_key_notice_dismissed_until', time() + 14 * DAY_IN_SECONDS);
    wp_safe_redirect(remove_query_arg(array('vernissaria_dismiss_key_notice', '_wpnonce')));
    exit;
}
add_action('admin_init', 'vernissaria_handle_key_notice_dismiss');
