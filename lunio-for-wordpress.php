<?php
/**
 * Plugin Name: Lunio for WordPress
 * Plugin URI: https://lunio.ca/wordpress-plugin
 * Description: Embed a Canadian tax calculator using the Lunio Developer API.
 * Version: 0.1.2
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
define( 'LUNIO_WP_VERSION', '0.1.2' );
define( 'LUNIO_WP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LUNIO_WP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load includes
require_once LUNIO_WP_PLUGIN_DIR . 'includes/class-lunio-api-client.php';
require_once LUNIO_WP_PLUGIN_DIR . 'includes/class-lunio-admin.php';
require_once LUNIO_WP_PLUGIN_DIR . 'includes/class-lunio-shortcodes.php';

// Initialize classes
new Lunio_Admin();
new Lunio_Shortcodes();