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

    public function render_calculator() {
        ob_start();
        ?>
        <div class="lunio-tax-calculator">
            <form id="lunio-tax-form">
                <div class="lunio-form-group">
                    <label for="lunio-amount"><?php esc_html_e('Amount ($)', 'lunio-wp'); ?></label>
                    <input type="number" id="lunio-amount" name="amount" step="0.01" min="0" required />
                </div>
                <div class="lunio-form-group">
                    <label for="lunio-province"><?php esc_html_e('Province', 'lunio-wp'); ?></label>
                    <select id="lunio-province" name="province_code" required>
                        <option value=""><?php esc_html_e('Select Province', 'lunio-wp'); ?></option>
                        <option value="AB">Alberta</option>
                        <option value="BC">British Columbia</option>
                        <option value="MB">Manitoba</option>
                        <option value="NB">New Brunswick</option>
                        <option value="NL">Newfoundland and Labrador</option>
                        <option value="NT">Northwest Territories</option>
                        <option value="NS">Nova Scotia</option>
                        <option value="NU">Nunavut</option>
                        <option value="ON">Ontario</option>
                        <option value="PE">Prince Edward Island</option>
                        <option value="QC">Quebec</option>
                        <option value="SK">Saskatchewan</option>
                        <option value="YT">Yukon</option>
                    </select>
                </div>
                <button type="submit" id="lunio-calculate-btn"><?php esc_html_e('Calculate Tax', 'lunio-wp'); ?></button>
            </form>
            <div id="lunio-result" style="display:none;"></div>
            <div id="lunio-error" style="display:none;"></div>
            <div class="lunio-powered-by"><?php esc_html_e('Powered by Lunio', 'lunio-wp'); ?></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_calculate_tax() {
        check_ajax_referer('lunio_calculate_tax', 'nonce');
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
        $response = $api_client->calculate_tax(array(
            'amount' => floatval($amount),
            'province_code' => $province_code,
        ));
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => __('Calculation failed: ', 'lunio-wp') . $response->get_error_message()));
        } else {
            wp_send_json_success(array('result' => $response));
        }
    }
}