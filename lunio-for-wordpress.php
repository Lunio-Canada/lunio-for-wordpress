<?php
/**
 * Plugin Name: Lunio for WordPress
 * Plugin URI: https://lunio.ca/wordpress-plugin
 * Description: Embed a Canadian tax calculator using the Lunio Developer API.
 * Version: 0.6.0
 * Author: Lunio
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: lunio-wp
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define constants
define( 'LUNIO_WP_VERSION', '0.6.0' );
define( 'LUNIO_WP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LUNIO_WP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

if ( ! defined( 'LUNIO_WP_UPDATE_CHANNEL' ) ) {
    define( 'LUNIO_WP_UPDATE_CHANNEL', 'github' );
}

// Load includes
require_once LUNIO_WP_PLUGIN_DIR . 'includes/class-lunio-api-client.php';
require_once LUNIO_WP_PLUGIN_DIR . 'includes/class-lunio-admin.php';
require_once LUNIO_WP_PLUGIN_DIR . 'includes/class-lunio-shortcodes.php';

// Initialize classes
new Lunio_Admin();
new Lunio_Shortcodes();

// Register Gutenberg block
add_action('init', 'lunio_register_block');
function lunio_register_block() {
    wp_register_script(
        'lunio-block-editor',
        LUNIO_WP_PLUGIN_URL . 'assets/js/block.js',
        array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'),
        LUNIO_WP_VERSION
    );
    wp_register_style(
        'lunio-block-editor-css',
        LUNIO_WP_PLUGIN_URL . 'assets/css/block-editor.css',
        array(),
        LUNIO_WP_VERSION
    );
    register_block_type('lunio/tax-calculator', array(
        'editor_script' => 'lunio-block-editor',
        'editor_style' => 'lunio-block-editor-css',
        'render_callback' => 'lunio_render_block',
        'attributes' => array(
            'type' => array(
                'type' => 'string',
                'default' => 'standard',
            ),
            'province' => array(
                'type' => 'string',
                'default' => '',
            ),
            'layout' => array(
                'type' => 'string',
                'default' => 'full',
            ),
            'showBreakdown' => array(
                'type' => 'boolean',
                'default' => true,
            ),
            'poweredBy' => array(
                'type' => 'boolean',
                'default' => true,
            ),
        ),
    ));
}

function lunio_render_block($attributes) {
    $type = $attributes['type'] ?? 'standard';
    $province = $attributes['province'] ?? '';
    $layout = $attributes['layout'] ?? 'full';
    $show_breakdown = $attributes['showBreakdown'] ?? true;
    $powered_by = $attributes['poweredBy'] ?? true;

    $shortcode = '[lunio_tax_calculator';
    if ($type !== 'standard') {
        $shortcode .= ' type="' . esc_attr($type) . '"';
    }
    if (!empty($province)) {
        $shortcode .= ' province="' . esc_attr($province) . '"';
    }
    if ($layout !== 'full') {
        $shortcode .= ' layout="' . esc_attr($layout) . '"';
    }
    if (!$show_breakdown) {
        $shortcode .= ' show_breakdown="false"';
    }
    if (!$powered_by) {
        $shortcode .= ' powered_by="false"';
    }
    $shortcode .= ']';

    return do_shortcode($shortcode);
}
