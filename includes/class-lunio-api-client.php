<?php
/**
 * Lunio API Client
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Lunio_API_Client {

    private $api_key;
    private $base_url;
    private $debug;

    public function __construct() {
        $this->api_key = get_option('lunio_api_key', '');
        $this->base_url = get_option('lunio_api_base_url', 'https://lunio.ca/api/v1');
        $this->debug = get_option('lunio_debug_mode', false);
    }

    public function get_tax_rates() {
        $url = $this->base_url . '/tax/rates';
        $response = wp_remote_get($url, array(
            'headers' => $this->get_headers(),
            'timeout' => 30,
        ));
        return $this->handle_response($response);
    }

    public function calculate_tax($data) {
        $url = $this->base_url . '/tax/calculate';
        $response = wp_remote_post($url, array(
            'headers' => $this->get_headers(),
            'body' => wp_json_encode($data),
            'timeout' => 30,
        ));
        return $this->handle_response($response);
    }

    public function reverse_calculate_tax($data) {
        $url = $this->base_url . '/tax/reverse';
        $response = wp_remote_post($url, array(
            'headers' => $this->get_headers(),
            'body' => wp_json_encode($data),
            'timeout' => 30,
        ));
        return $this->handle_response($response);
    }

    private function get_headers() {
        $headers = array(
            'Content-Type' => 'application/json',
        );
        if (!empty($this->api_key)) {
            $headers['Authorization'] = 'Bearer ' . $this->api_key;
        }
        return $headers;
    }

    private function handle_response($response) {
        if (is_wp_error($response)) {
            if ($this->debug) {
                error_log('Lunio API Error: ' . $response->get_error_message());
            }
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($this->debug) {
            error_log('Lunio API Response Status: ' . $status_code);
            error_log('Lunio API Response Body: ' . $body);
        }

        if ($status_code !== 200) {
            return new WP_Error('lunio_api_error', 'API returned status ' . $status_code . ': ' . $body);
        }

        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('lunio_json_error', 'Invalid JSON response: ' . json_last_error_msg());
        }

        return $data;
    }
}