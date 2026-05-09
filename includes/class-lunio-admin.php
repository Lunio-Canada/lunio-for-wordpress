<?php
/**
 * Lunio Admin Settings
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Lunio_Admin {

    private $updater;

    public function __construct( $updater ) {
        $this->updater = $updater;
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_lunio_test_connection', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_lunio_check_updates', array($this, 'ajax_check_updates'));
        add_action('wp_ajax_lunio_refresh_status', array($this, 'ajax_refresh_status'));
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
        echo '<p>' . esc_html__('Configure your Lunio API settings below. Save changes to apply.', 'lunio-wp') . '</p>';
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
        $api_key = get_option('lunio_api_key', '');
        $api_connected = !empty($api_key);
        $update_data = $this->updater->get_update_status();
        ?>
        <div class="wrap lunio-admin-wrap">
            <!-- Hero Status Card -->
            <div class="lunio-hero-card">
                <div class="lunio-hero-content">
                    <h1>Lunio for WordPress</h1>
                    <p><?php esc_html_e('Embed a professional Canadian tax calculator on your website with real-time calculations.', 'lunio-wp'); ?></p>
                    <div class="lunio-status-badges">
                        <span class="lunio-version-badge">v<?php echo esc_html(LUNIO_WP_VERSION); ?></span>
                        <span class="lunio-connection-badge <?php echo $api_connected ? 'connected' : 'disconnected'; ?>">
                            <?php echo $api_connected ? '✓ Connected' : '⚠ Not Connected'; ?>
                        </span>
                    </div>
                </div>
                <div class="lunio-hero-actions">
                    <a href="https://lunio.ca" target="_blank" rel="noopener noreferrer" class="button button-primary">Documentation</a>
                    <a href="https://lunio.ca/support" target="_blank" rel="noopener noreferrer" class="button button-secondary">Support</a>
                </div>
            </div>

            <!-- Setup Progress -->
            <div class="lunio-setup-progress">
                <h2><?php esc_html_e('Setup Progress', 'lunio-wp'); ?></h2>
                <ul class="lunio-progress-list">
                    <li class="completed">✓ Plugin Activated</li>
                    <li class="<?php echo $api_connected ? 'completed' : 'pending'; ?>"><?php echo $api_connected ? '✓' : '□'; ?> API Key Added</li>
                    <li class="pending">□ Add Calculator Shortcode To Page</li>
                </ul>
            </div>

            <div class="lunio-admin-columns">
                <!-- Left Column -->
                <div class="lunio-main-column">
                    <!-- API Settings -->
                    <div class="lunio-card">
                        <h2><?php esc_html_e('API Configuration', 'lunio-wp'); ?></h2>
                        <form action="options.php" method="post">
                            <?php
                            settings_fields('lunio_settings');
                            do_settings_sections('lunio_settings');
                            submit_button(__('Save Settings', 'lunio-wp'));
                            ?>
                        </form>
                        <div class="lunio-test-section">
                            <button id="lunio-test-connection" class="button button-secondary"><?php esc_html_e('Test Connection', 'lunio-wp'); ?></button>
                            <div id="lunio-test-result"></div>
                        </div>
                    </div>

                    <!-- Shortcode Cards -->
                    <div class="lunio-card">
                        <h2><?php esc_html_e('Shortcode Examples', 'lunio-wp'); ?></h2>
                        <p><?php esc_html_e('Use these shortcodes directly or configure visually with the Gutenberg block.', 'lunio-wp'); ?></p>
                        <div class="lunio-shortcode-grid">
                            <div class="lunio-shortcode-card">
                                <h3>Basic Calculator</h3>
                                <p>Standard tax calculator with full layout.</p>
                                <code>[lunio_tax_calculator]</code>
                                <button class="lunio-copy-btn" data-shortcode="[lunio_tax_calculator]">Copy</button>
                            </div>
                            <div class="lunio-shortcode-card">
                                <h3>Pre-selected Province</h3>
                                <p>Calculator with Ontario pre-selected.</p>
                                <code>[lunio_tax_calculator province="ON"]</code>
                                <button class="lunio-copy-btn" data-shortcode="[lunio_tax_calculator province=&quot;ON&quot;]">Copy</button>
                            </div>
                            <div class="lunio-shortcode-card">
                                <h3>Compact Layout</h3>
                                <p>Smaller calculator design.</p>
                                <code>[lunio_tax_calculator layout="compact"]</code>
                                <button class="lunio-copy-btn" data-shortcode="[lunio_tax_calculator layout=&quot;compact&quot;]">Copy</button>
                            </div>
                            <div class="lunio-shortcode-card">
                                <h3>Simple View</h3>
                                <p>Hides tax breakdown, shows totals only.</p>
                                <code>[lunio_tax_calculator show_breakdown="false"]</code>
                                <button class="lunio-copy-btn" data-shortcode="[lunio_tax_calculator show_breakdown=&quot;false&quot;]">Copy</button>
                            </div>
                            <div class="lunio-shortcode-card">
                                <h3><?php esc_html_e('Reverse Calculator', 'lunio-wp'); ?></h3>
                                <p><?php esc_html_e('Calculate pre-tax amount from tax-included total.', 'lunio-wp'); ?></p>
                                <code>[lunio_tax_calculator type="reverse"]</code>
                                <button class="lunio-copy-btn" data-shortcode="[lunio_tax_calculator type=&quot;reverse&quot;]">Copy</button>
                            </div>
                            <div class="lunio-shortcode-card">
                                <h3><?php esc_html_e('Custom Setup', 'lunio-wp'); ?></h3>
                                <p><?php esc_html_e('Fully customized calculator.', 'lunio-wp'); ?></p>
                                <code>[lunio_tax_calculator province="ON" show_breakdown="true" powered_by="true" layout="compact"]</code>
                                <button class="lunio-copy-btn" data-shortcode="[lunio_tax_calculator province=&quot;ON&quot; show_breakdown=&quot;true&quot; powered_by=&quot;true&quot; layout=&quot;compact&quot;]">Copy</button>
                            </div>
                        </div>
                    </div>

                    <!-- Calculator Types -->
                    <div class="lunio-card">
                        <h2><?php esc_html_e('Calculator Types', 'lunio-wp'); ?></h2>
                        <div class="lunio-calculator-types">
                            <div class="lunio-type-card">
                                <h3><?php esc_html_e('Standard Tax Calculator', 'lunio-wp'); ?></h3>
                                <p><?php esc_html_e('Enter a subtotal amount and calculate the taxes and final total.', 'lunio-wp'); ?></p>
                            </div>
                            <div class="lunio-type-card">
                                <h3><?php esc_html_e('Reverse Tax Calculator', 'lunio-wp'); ?></h3>
                                <p><?php esc_html_e('Enter a tax-included total and calculate the pre-tax subtotal and tax breakdown.', 'lunio-wp'); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Calculator Preview -->
                    <div class="lunio-card">
                        <h2><?php esc_html_e('Calculator Preview', 'lunio-wp'); ?></h2>
                        <p><?php esc_html_e('Preview of the standard tax calculator. The reverse calculator has similar styling with different labels.', 'lunio-wp'); ?></p>
                        <div class="lunio-calculator-preview">
                            <div class="lunio-preview-calculator">
                                <div class="lunio-preview-group">
                                    <label>Amount ($)</label>
                                    <input type="number" placeholder="100.00" disabled />
                                </div>
                                <div class="lunio-preview-group">
                                    <label>Province</label>
                                    <select disabled><option>Ontario</option></select>
                                </div>
                                <button class="lunio-preview-btn" disabled>Calculate Tax</button>
                                <div class="lunio-preview-result">
                                    <div class="lunio-preview-result-header">Tax Calculation for ON</div>
                                    <div class="lunio-preview-result-row"><span>Subtotal:</span><span>$100.00</span></div>
                                    <div class="lunio-preview-result-row"><span>HST:</span><span>$13.00</span></div>
                                    <div class="lunio-preview-result-row lunio-preview-total-tax"><span>Total Tax:</span><span>$13.00</span></div>
                                    <div class="lunio-preview-result-row lunio-preview-grand-total"><span>Total:</span><span>$113.00</span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Troubleshooting -->
                    <div class="lunio-card">
                        <h2><?php esc_html_e('Troubleshooting', 'lunio-wp'); ?></h2>
                        <div class="lunio-accordion">
                            <div class="lunio-accordion-item">
                                <button class="lunio-accordion-toggle">Missing API Key</button>
                                <div class="lunio-accordion-content">
                                    <p><?php esc_html_e('Enter your Lunio API key in the API Configuration section above and save the settings.', 'lunio-wp'); ?></p>
                                </div>
                            </div>
                            <div class="lunio-accordion-item">
                                <button class="lunio-accordion-toggle">Invalid API Key</button>
                                <div class="lunio-accordion-content">
                                    <p><?php esc_html_e('Verify your API key is correct and active. Use the Test Connection button to check.', 'lunio-wp'); ?></p>
                                </div>
                            </div>
                            <div class="lunio-accordion-item">
                                <button class="lunio-accordion-toggle">Shortcode Not Appearing</button>
                                <div class="lunio-accordion-content">
                                    <p><?php esc_html_e('Ensure the shortcode is added to the content of a page or post, not in a sidebar or header widget.', 'lunio-wp'); ?></p>
                                </div>
                            </div>
                            <div class="lunio-accordion-item">
                                <button class="lunio-accordion-toggle">Calculator Shows Error</button>
                                <div class="lunio-accordion-content">
                                    <p><?php esc_html_e('Check that your server can make HTTPS requests. Enable debug mode in settings for detailed logs.', 'lunio-wp'); ?></p>
                                </div>
                            </div>
                            <div class="lunio-accordion-item">
                                <button class="lunio-accordion-toggle">Theme/Plugin Conflicts</button>
                                <div class="lunio-accordion-content">
                                    <p><?php esc_html_e('Temporarily switch to a default WordPress theme and disable other plugins to isolate issues.', 'lunio-wp'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="lunio-sidebar-column">
                    <!-- Quick Actions -->
                    <div class="lunio-card">
                        <h3><?php esc_html_e('Quick Actions', 'lunio-wp'); ?></h3>
                        <div class="lunio-quick-copy">
                            <p><?php esc_html_e('Popular Shortcode:', 'lunio-wp'); ?></p>
                            <code>[lunio_tax_calculator]</code>
                            <button class="lunio-copy-btn" data-shortcode="[lunio_tax_calculator]">Copy</button>
                        </div>
                    </div>

                    <!-- API Status -->
                    <div class="lunio-card">
                        <h3><?php esc_html_e('API Status', 'lunio-wp'); ?></h3>
                        <div class="lunio-api-status <?php echo $api_connected ? 'connected' : 'disconnected'; ?>">
                            <div class="lunio-status-icon"><?php echo $api_connected ? '✓' : '⚠'; ?></div>
                            <div class="lunio-status-text">
                                <strong><?php echo $api_connected ? 'Connected' : 'Not Connected'; ?></strong>
                                <p><?php echo $api_connected ? 'Your API key is configured.' : 'Add your API key to get started.'; ?></p>
                            </div>
                        </div>
                        <div class="lunio-account-details" id="lunio-account-details">
                            <div class="lunio-status-row">
                                <span><?php esc_html_e('Click "Refresh Status" to load account data.', 'lunio-wp'); ?></span>
                            </div>
                        </div>
                        <div class="lunio-status-actions">
                            <button id="lunio-refresh-status" class="button button-secondary"><?php esc_html_e('Refresh Status', 'lunio-wp'); ?></button>
                            <div id="lunio-refresh-result"></div>
                        </div>
                    </div>

                    <div class="lunio-card">
                        <h3><?php esc_html_e('Plugin Updates', 'lunio-wp'); ?></h3>
                        <div id="lunio-update-card" class="lunio-update-card <?php echo esc_attr($this->get_update_status_class($update_data['status'])); ?>">
                            <?php echo $this->render_update_status_html($update_data); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>
                        <div class="lunio-update-actions">
                            <button id="lunio-check-updates" class="button button-secondary" <?php disabled($update_data['channel'], 'disabled'); ?>><?php esc_html_e('Check for Updates', 'lunio-wp'); ?></button>
                            <div id="lunio-update-result"></div>
                        </div>
                    </div>

                    <!-- Plugin Info -->
                    <div class="lunio-card">
                        <h3><?php esc_html_e('Plugin Info', 'lunio-wp'); ?></h3>
                        <ul class="lunio-plugin-info">
                            <li><strong>Version:</strong> <?php echo esc_html(LUNIO_WP_VERSION); ?></li>
                            <li><strong>Author:</strong> Lunio</li>
                            <li><strong>License:</strong> GPL v2 or later</li>
                        </ul>
                    </div>

                    <!-- Links -->
                    <div class="lunio-card">
                        <h3><?php esc_html_e('Resources', 'lunio-wp'); ?></h3>
                        <ul class="lunio-resource-links">
                            <li><a href="https://lunio.ca" target="_blank" rel="noopener noreferrer">🌐 Lunio Website</a></li>
                            <li><a href="https://lunio.ca/docs" target="_blank" rel="noopener noreferrer">📚 Documentation</a></li>
                            <li><a href="https://lunio.ca/support" target="_blank" rel="noopener noreferrer">🆘 Support</a></li>
                            <li><a href="https://lunio.ca/api" target="_blank" rel="noopener noreferrer">🔑 Get API Key</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="lunio-gutenberg-notice">
                <hr />
                <h2><?php esc_html_e('Gutenberg Block Support', 'lunio-wp'); ?></h2>
                <p><?php esc_html_e('This plugin includes a Gutenberg block for visual calculator insertion. Look for "Lunio Tax Calculator" in the block inserter under the Widgets category.', 'lunio-wp'); ?></p>
                <ul>
                    <li><?php esc_html_e('Visual block preview in the editor', 'lunio-wp'); ?></li>
                    <li><?php esc_html_e('Sidebar controls for all calculator options', 'lunio-wp'); ?></li>
                    <li><?php esc_html_e('Same functionality as shortcodes', 'lunio-wp'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'lunio-settings') {
            return;
        }
        wp_enqueue_style('lunio-admin-css', LUNIO_WP_PLUGIN_URL . 'assets/css/admin.css', array(), LUNIO_WP_VERSION);
        wp_enqueue_script('lunio-admin-js', LUNIO_WP_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), LUNIO_WP_VERSION, true);
        wp_localize_script('lunio-admin-js', 'lunioAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'test_nonce' => wp_create_nonce('lunio_test_connection'),
            'update_nonce' => wp_create_nonce('lunio_check_updates'),
            'refresh_nonce' => wp_create_nonce('lunio_refresh_status'),
        ));
    }

    public function ajax_check_updates() {
        check_ajax_referer('lunio_check_updates', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => '<div class="notice notice-error"><p>' . esc_html__('Insufficient permissions.', 'lunio-wp') . '</p></div>',
            ));
        }

        $this->updater->clear_cached_release();
        $update_data = $this->updater->get_update_status(true);

        wp_send_json_success(array(
            'message' => '<div class="notice notice-success"><p>' . esc_html__('Update check completed.', 'lunio-wp') . '</p></div>',
            'card_html' => $this->render_update_status_html($update_data),
            'card_class' => $this->get_update_status_class($update_data['status']),
        ));
    }

    public function ajax_test_connection() {
        error_log('Lunio test connection handler reached');
        check_ajax_referer('lunio_test_connection', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => '<div class="notice notice-error"><p>' . esc_html__('Insufficient permissions.', 'lunio-wp') . '</p></div>',
            ));
        }

        $this->clear_status_cache();
        $api_key = get_option('lunio_api_key', '');

        if (empty($api_key)) {
            wp_send_json_error(array('message' => '<div class="notice notice-error"><p>' . esc_html__('Please enter a Lunio API key before testing the connection.', 'lunio-wp') . '</p></div>'));
        }

        $api_client = new Lunio_API_Client();
        $debug = (bool) get_option('lunio_debug_mode', false);
        $response = $api_client->calculate_tax(array(
            'province_code' => 'NL',
            'amount' => 100,
            'subtotal' => 100,
        ));

        if (is_wp_error($response)) {
            $message = __('Connection test failed. Please verify your API settings and try again.', 'lunio-wp');

            if ($debug) {
                $message = $response->get_error_message();
            }

            wp_send_json_error(array(
                'message' => '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>',
            ));
        }

        if (!is_wp_error($response) && is_array($response) && isset($response['success']) && $response['success'] === true && isset($response['data']['total'])) {
            wp_send_json_success(array(
                'message' => '<div class="notice notice-success"><p>' . esc_html__('Connection successful!', 'lunio-wp') . '</p></div>',
            ));
        }

        $message = __('Connection test failed.', 'lunio-wp');

        if (is_array($response) && isset($response['message']) && is_string($response['message']) && '' !== $response['message']) {
            $message = $response['message'];
        }

        wp_send_json_error(array(
            'message' => '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>',
        ));
    }

    public function ajax_refresh_status() {
        check_ajax_referer('lunio_refresh_status', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'lunio-wp'));
        }
        $this->clear_status_cache();
        $account_status = $this->get_account_status();
        wp_send_json_success(array('account_status' => $account_status));
    }

    private function get_account_status() {
        $cache_key = 'lunio_account_status';
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }
        $api_client = new Lunio_API_Client();
        $response = $api_client->get_account_status();
        if (!is_wp_error($response) && isset($response['success']) && $response['success'] === true) {
            set_transient($cache_key, $response['data'], 5 * MINUTE_IN_SECONDS);
            return $response['data'];
        }
        return null;
    }

    public function clear_status_cache() {
        delete_transient('lunio_account_status');
    }

    private function render_update_status_html($update_data) {
        $output = '<div class="lunio-update-summary">';
        $output .= '<div class="lunio-update-badge">' . esc_html($update_data['status_label']) . '</div>';
        $output .= '<p class="lunio-update-message">' . esc_html($update_data['status_message']) . '</p>';
        $output .= '</div>';
        $output .= '<div class="lunio-account-details">';
        $output .= '<div class="lunio-status-row"><span>' . esc_html__('Current version', 'lunio-wp') . '</span><span>' . esc_html($update_data['installed_version']) . '</span></div>';
        $output .= '<div class="lunio-status-row"><span>' . esc_html__('Latest GitHub release', 'lunio-wp') . '</span><span>' . esc_html($update_data['latest_version']) . '</span></div>';
        $output .= '<div class="lunio-status-row"><span>' . esc_html__('Last checked', 'lunio-wp') . '</span><span>' . esc_html($update_data['last_checked'] ? $update_data['last_checked'] : __('Never', 'lunio-wp')) . '</span></div>';
        $output .= '<div class="lunio-status-row"><span>' . esc_html__('Status', 'lunio-wp') . '</span><span>' . esc_html($update_data['status_label']) . '</span></div>';
        if (!empty($update_data['package_type'])) {
            $output .= '<div class="lunio-status-row"><span>' . esc_html__('Package source', 'lunio-wp') . '</span><span>' . esc_html($update_data['package_type']) . '</span></div>';
        }
        $output .= '</div>';

        if ('update_available' === $update_data['status']) {
            $output .= '<div class="lunio-update-links">';

            if (!empty($update_data['release_url'])) {
                $output .= '<a class="button button-primary" href="' . esc_url($update_data['release_url']) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('View GitHub Release', 'lunio-wp') . '</a>';
            }

            if (!empty($update_data['download_url'])) {
                $output .= '<a class="button button-secondary" href="' . esc_url($update_data['download_url']) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Download Latest ZIP', 'lunio-wp') . '</a>';
            }

            $output .= '</div>';
        }

        if ('github' === $update_data['channel']) {
            $output .= '<p class="lunio-update-note">' . esc_html__('For the best one-click update experience, attach a release asset named lunio-for-wordpress.zip to each GitHub release.', 'lunio-wp') . '</p>';
        }

        return $output;
    }

    private function get_update_status_class($status) {
        $classes = array(
            'update_available' => 'update-available',
            'up_to_date' => 'up-to-date',
            'error' => 'update-error',
            'disabled' => 'update-disabled',
            'wordpress_org' => 'update-wordpress-org',
            'unknown' => 'update-unknown',
        );

        return isset($classes[$status]) ? $classes[$status] : 'update-unknown';
    }
}
