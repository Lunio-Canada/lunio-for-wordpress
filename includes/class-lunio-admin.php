<?php
/**
 * Lunio Admin Settings
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Lunio_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_lunio_test_connection', array($this, 'ajax_test_connection'));
    }

    public function add_admin_menu() {
        add_options_page(
            'Lunio Settings',
            'Lunio',
            'manage_options',
            'lunio-settings',
            array($this, 'settings_page')
        );
    }

    public function settings_init() {
        register_setting('lunio_settings', 'lunio_api_key', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('lunio_settings', 'lunio_api_base_url', array('sanitize_callback' => 'esc_url_raw'));
        register_setting('lunio_settings', 'lunio_debug_mode', array('sanitize_callback' => array($this, 'sanitize_checkbox')));

        add_settings_section(
            'lunio_settings_section',
            __('Lunio API Settings', 'lunio-wp'),
            array($this, 'settings_section_callback'),
            'lunio_settings'
        );

        add_settings_field(
            'lunio_api_key',
            __('API Key', 'lunio-wp'),
            array($this, 'api_key_render'),
            'lunio_settings',
            'lunio_settings_section'
        );

        add_settings_field(
            'lunio_api_base_url',
            __('API Base URL', 'lunio-wp'),
            array($this, 'api_base_url_render'),
            'lunio_settings',
            'lunio_settings_section'
        );

        add_settings_field(
            'lunio_debug_mode',
            __('Debug Mode', 'lunio-wp'),
            array($this, 'debug_mode_render'),
            'lunio_settings',
            'lunio_settings_section'
        );
    }

    public function sanitize_checkbox($input) {
        return isset($input) ? 1 : 0;
    }

    public function settings_section_callback() {
        echo '<p>' . esc_html__('Configure your Lunio API settings here.', 'lunio-wp') . '</p>';
    }

    public function api_key_render() {
        $value = get_option('lunio_api_key', '');
        echo '<input type="password" name="lunio_api_key" value="' . esc_attr($value) . '" />';
        echo '<p class="description">' . esc_html__('Enter your Lunio API key.', 'lunio-wp') . '</p>';
    }

    public function api_base_url_render() {
        $value = get_option('lunio_api_base_url', 'https://lunio.ca/api/v1');
        echo '<input type="url" name="lunio_api_base_url" value="' . esc_attr($value) . '" />';
        echo '<p class="description">' . esc_html__('Default: https://lunio.ca/api/v1', 'lunio-wp') . '</p>';
    }

    public function debug_mode_render() {
        $value = get_option('lunio_debug_mode', false);
        echo '<input type="checkbox" name="lunio_debug_mode" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="lunio_debug_mode">' . esc_html__('Enable debug logging', 'lunio-wp') . '</label>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Lunio Settings', 'lunio-wp'); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('lunio_settings');
                do_settings_sections('lunio_settings');
                submit_button();
                ?>
            </form>
            <hr />
            <h2><?php esc_html_e('Test Connection', 'lunio-wp'); ?></h2>
            <p><?php esc_html_e('Test Connection verifies your API key using a small authenticated tax calculation request.', 'lunio-wp'); ?></p>
            <button id="lunio-test-connection" class="button button-secondary"><?php esc_html_e('Test Connection', 'lunio-wp'); ?></button>
            <div id="lunio-test-result"></div>
        </div>
        <?php
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'settings_page_lunio-settings') {
            return;
        }
        wp_enqueue_style('lunio-admin-css', LUNIO_WP_PLUGIN_URL . 'assets/css/admin.css', array(), LUNIO_WP_VERSION);
        wp_enqueue_script('lunio-admin-js', LUNIO_WP_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), LUNIO_WP_VERSION, true);
        wp_localize_script('lunio-admin-js', 'lunioAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lunio_test_connection'),
        ));
    }

    public function ajax_test_connection() {
        check_ajax_referer('lunio_test_connection', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'lunio-wp'));
        }
        $api_key = get_option('lunio_api_key', '');
        if (empty($api_key)) {
            wp_send_json_error(array('message' => '<div class="notice notice-error"><p>' . esc_html__('Please enter a Lunio API key before testing the connection.', 'lunio-wp') . '</p></div>'));
            return;
        }
        $api_client = new Lunio_API_Client();
        $debug = get_option('lunio_debug_mode', false);
        $response = $api_client->calculate_tax(array('province_code' => 'NL', 'amount' => 100));
        if ($debug) {
            error_log('Test Connection Response: ' . print_r($response, true));
        }
        if (!is_wp_error($response) && is_array($response) && isset($response['success']) && $response['success'] === true && isset($response['data']['total'])) {
            wp_send_json_success(array('message' => '<div class="notice notice-success"><p>' . esc_html__('Connection successful!', 'lunio-wp') . '</p></div>'));
        } elseif (!is_wp_error($response) && is_array($response) && isset($response['success']) && $response['success'] === false) {
            $error_msg = isset($response['message']) ? $response['message'] : 'API returned success: false';
            wp_send_json_error(array('message' => '<div class="notice notice-error"><p>' . esc_html__('API Error: ', 'lunio-wp') . esc_html($error_msg) . '</p></div>'));
        } elseif (!is_wp_error($response)) {
            wp_send_json_error(array('message' => '<div class="notice notice-error"><p>' . esc_html__('Lunio responded, but the plugin could not understand the response format.', 'lunio-wp') . '</p></div>'));
        } else {
            $msg = $response->get_error_message();
            if (strpos($msg, '401') !== false) {
                $error_msg = esc_html__('Invalid Lunio API key or unauthorized request.', 'lunio-wp');
            } elseif (strpos($msg, '422') !== false) {
                $error_msg = esc_html__('Validation error: ', 'lunio-wp') . esc_html($msg);
            } else {
                $error_msg = esc_html__('Connection failed: ', 'lunio-wp') . esc_html($msg);
            }
            wp_send_json_error(array('message' => '<div class="notice notice-error"><p>' . $error_msg . '</p></div>'));
        }
    }
}