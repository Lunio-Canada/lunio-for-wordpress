<?php
/**
 * Lunio Shortcodes
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Lunio_Shortcodes {

    public function __construct() {
        add_shortcode('lunio_tax_calculator', array($this, 'render_calculator'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_lunio_calculate_tax', array($this, 'ajax_calculate_tax'));
        add_action('wp_ajax_nopriv_lunio_calculate_tax', array($this, 'ajax_calculate_tax'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('lunio-frontend-css', LUNIO_WP_PLUGIN_URL . 'assets/css/frontend.css', array(), LUNIO_WP_VERSION);
        wp_enqueue_script('lunio-frontend-js', LUNIO_WP_PLUGIN_URL . 'assets/js/frontend.js', array(), LUNIO_WP_VERSION, true);
        wp_localize_script('lunio-frontend-js', 'lunioAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lunio_calculate_tax'),
        ));
    }

    public function render_calculator($atts) {
        $atts = shortcode_atts(array(
            'type' => 'standard',
            'province' => '',
            'show_breakdown' => 'true',
            'powered_by' => 'true',
            'layout' => 'full',
        ), $atts, 'lunio_tax_calculator');

        // Sanitize province
        $province = strtoupper(sanitize_text_field($atts['province']));
        $allowed_provinces = array('AB', 'BC', 'MB', 'NB', 'NL', 'NT', 'NS', 'NU', 'ON', 'PE', 'QC', 'SK', 'YT');
        if (!in_array($province, $allowed_provinces)) {
            $province = '';
        }

        // Sanitize type
        $type = strtolower(sanitize_text_field($atts['type']));
        if ($type !== 'reverse') {
            $type = 'standard';
        }

        // Sanitize show_breakdown
        $show_breakdown = filter_var($atts['show_breakdown'], FILTER_VALIDATE_BOOLEAN);

        // Sanitize powered_by
        $powered_by = filter_var($atts['powered_by'], FILTER_VALIDATE_BOOLEAN);

        // Sanitize layout
        $layout = strtolower(sanitize_text_field($atts['layout']));
        if ($layout !== 'compact') {
            $layout = 'full';
        }
        $classes = 'lunio-tax-calculator';
        if ($layout === 'compact') {
            $classes .= ' lunio-compact';
        }
        ob_start();
        ?>
        <div class="<?php echo esc_attr($classes); ?>" data-type="<?php echo esc_attr($type); ?>" data-show-breakdown="<?php echo $show_breakdown ? 'true' : 'false'; ?>" data-layout="<?php echo esc_attr($layout); ?>" data-debug="<?php echo get_option('lunio_debug_mode', false) ? '1' : '0'; ?>">
            <form id="lunio-tax-form">
                <input type="hidden" name="type" value="<?php echo esc_attr($type); ?>" />
                <div class="lunio-form-group">
                    <label for="lunio-amount"><?php echo $type === 'reverse' ? esc_html__('Tax-Included Total ($)', 'lunio-wp') : esc_html__('Subtotal ($)', 'lunio-wp'); ?></label>
                    <input type="number" id="lunio-amount" name="amount" step="0.01" min="0" required />
                </div>
                <div class="lunio-form-group">
                    <label for="lunio-province"><?php esc_html_e('Province', 'lunio-wp'); ?></label>
                    <select id="lunio-province" name="province_code" required>
                        <option value=""><?php esc_html_e('Select Province', 'lunio-wp'); ?></option>
                        <option value="AB" <?php selected($province, 'AB'); ?>>Alberta</option>
                        <option value="BC" <?php selected($province, 'BC'); ?>>British Columbia</option>
                        <option value="MB" <?php selected($province, 'MB'); ?>>Manitoba</option>
                        <option value="NB" <?php selected($province, 'NB'); ?>>New Brunswick</option>
                        <option value="NL" <?php selected($province, 'NL'); ?>>Newfoundland and Labrador</option>
                        <option value="NT" <?php selected($province, 'NT'); ?>>Northwest Territories</option>
                        <option value="NS" <?php selected($province, 'NS'); ?>>Nova Scotia</option>
                        <option value="NU" <?php selected($province, 'NU'); ?>>Nunavut</option>
                        <option value="ON" <?php selected($province, 'ON'); ?>>Ontario</option>
                        <option value="PE" <?php selected($province, 'PE'); ?>>Prince Edward Island</option>
                        <option value="QC" <?php selected($province, 'QC'); ?>>Quebec</option>
                        <option value="SK" <?php selected($province, 'SK'); ?>>Saskatchewan</option>
                        <option value="YT" <?php selected($province, 'YT'); ?>>Yukon</option>
                    </select>
                </div>
                <button type="submit" id="lunio-calculate-btn"><?php echo $type === 'reverse' ? esc_html__('Reverse Calculate', 'lunio-wp') : esc_html__('Calculate Tax', 'lunio-wp'); ?></button>
            </form>
            <div id="lunio-result" style="display:none;"></div>
            <div id="lunio-error" style="display:none;"></div>
            <?php if ($powered_by) : ?>
                <div class="lunio-powered-by"><a href="https://lunio.ca" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Powered by Lunio', 'lunio-wp'); ?></a></div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_calculate_tax() {
        check_ajax_referer('lunio_calculate_tax', 'nonce');
        $type = sanitize_text_field($_POST['type'] ?? 'standard');
        if ($type !== 'reverse') {
            $type = 'standard';
        }
        $amount = sanitize_text_field($_POST['amount']);
        $province_code = sanitize_text_field($_POST['province_code']);
        if (!is_numeric($amount) || $amount < 0) {
            wp_send_json_error(array('message' => __('Invalid amount', 'lunio-wp')));
        }
        $allowed_provinces = array('AB', 'BC', 'MB', 'NB', 'NL', 'NT', 'NS', 'NU', 'ON', 'PE', 'QC', 'SK', 'YT');
        if (!in_array($province_code, $allowed_provinces)) {
            wp_send_json_error(array('message' => __('Invalid province', 'lunio-wp')));
        }
        $api_client = new Lunio_API_Client();
        $debug = get_option('lunio_debug_mode', false);
        if ($debug) {
            error_log('Calculator Type Received: ' . $type);
        }
        if ($type === 'reverse') {
            $payload = array(
                'total' => floatval($amount),
                'province_code' => $province_code,
            );
            if ($debug) {
                error_log('Reverse Calculator Endpoint: /tax/reverse');
                error_log('Reverse Calculator Payload: ' . print_r($payload, true));
            }
            $response = $api_client->reverse_calculate_tax($payload);
        } else {
            $payload = array(
                'amount' => floatval($amount),
                'province_code' => $province_code,
            );
            if ($debug) {
                error_log('Standard Calculator Endpoint: /tax/calculate');
                error_log('Standard Calculator Payload: ' . print_r($payload, true));
            }
            $response = $api_client->calculate_tax($payload);
        }
        if ($debug) {
            error_log('Raw Lunio API Response: ' . print_r($response, true));
        }
        if (is_wp_error($response)) {
            $error_msg = $response->get_error_message();
            if ($debug) {
                error_log('Calculation Error: ' . $error_msg);
                wp_send_json_error(array('message' => __('Debug: ', 'lunio-wp') . $error_msg));
            } else {
                wp_send_json_error(array('message' => __('Calculation failed. Please check the amount and province, then try again.', 'lunio-wp')));
            }
        } elseif (is_array($response) && isset($response['success']) && $response['success'] === true && isset($response['data'])) {
            wp_send_json_success(array('result' => $response));
        } else {
            if ($debug) {
                error_log('Invalid Response Structure: ' . print_r($response, true));
                wp_send_json_error(array('message' => __('Debug: Invalid API response structure.', 'lunio-wp')));
            } else {
                wp_send_json_error(array('message' => __('Calculation failed. Please check the amount and province, then try again.', 'lunio-wp')));
            }
        }
    }
}