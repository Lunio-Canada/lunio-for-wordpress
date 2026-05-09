<?php
/**
 * Lunio GitHub Updater
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Lunio_Updater {

    const UPDATE_TRANSIENT_KEY = 'lunio_github_release_info';
    const UPDATE_CACHE_TTL = 12 * HOUR_IN_SECONDS;
    const GITHUB_LATEST_RELEASE_URL = 'https://api.github.com/repos/Lunio-Canada/lunio-for-wordpress/releases/latest';
    const PLUGIN_FILE = 'lunio-for-wordpress/lunio-for-wordpress.php';
    const PLUGIN_SLUG = 'lunio-for-wordpress';

    public function __construct() {
        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'inject_update_data' ) );
        add_filter( 'plugins_api', array( $this, 'plugins_api' ), 10, 3 );
        add_filter( 'upgrader_source_selection', array( $this, 'fix_plugin_folder_name' ), 10, 4 );
    }

    public function inject_update_data( $transient ) {
        if ( ! $this->is_github_channel_enabled() ) {
            return $transient;
        }

        if ( ! is_object( $transient ) ) {
            $transient = new stdClass();
        }

        $release = $this->get_latest_release();

        if ( isset( $release['error'] ) || empty( $release['version'] ) ) {
            return $transient;
        }

        if ( ! version_compare( $this->normalize_version( $release['version'] ), $this->normalize_version( LUNIO_WP_VERSION ), '>' ) ) {
            if ( isset( $transient->response[ self::PLUGIN_FILE ] ) ) {
                unset( $transient->response[ self::PLUGIN_FILE ] );
            }

            return $transient;
        }

        $package_url = $this->get_preferred_package_url( $release );

        if ( empty( $package_url ) ) {
            return $transient;
        }

        if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
            $transient->response = array();
        }

        $transient->response[ self::PLUGIN_FILE ] = array(
            'slug'        => self::PLUGIN_SLUG,
            'plugin'      => self::PLUGIN_FILE,
            'new_version' => $this->normalize_version( $release['version'] ),
            'package'     => $package_url,
            'url'         => ! empty( $release['release_url'] ) ? $release['release_url'] : 'https://github.com/Lunio-Canada/lunio-for-wordpress',
            'tested'      => ! empty( $release['tested'] ) ? $release['tested'] : '',
            'requires'    => ! empty( $release['requires'] ) ? $release['requires'] : '',
        );

        return $transient;
    }

    public function plugins_api( $result, $action, $args ) {
        if ( 'plugin_information' !== $action || empty( $args->slug ) || self::PLUGIN_SLUG !== $args->slug ) {
            return $result;
        }

        if ( ! $this->is_github_channel_enabled() ) {
            return $result;
        }

        $release = $this->get_latest_release();

        if ( isset( $release['error'] ) || empty( $release['version'] ) ) {
            return $result;
        }

        $sections = array(
            'description' => __( 'Embed a Canadian tax calculator using the Lunio Developer API.', 'lunio-wp' ),
            'changelog'   => ! empty( $release['body'] ) ? wp_kses_post( wpautop( $release['body'] ) ) : esc_html__( 'No changelog provided for this release.', 'lunio-wp' ),
        );

        $package_url = $this->get_preferred_package_url( $release );

        return (object) array(
            'name'          => 'Lunio for WordPress',
            'slug'          => self::PLUGIN_SLUG,
            'version'       => $this->normalize_version( $release['version'] ),
            'author'        => '<a href="https://lunio.ca">Lunio</a>',
            'homepage'      => ! empty( $release['release_url'] ) ? $release['release_url'] : 'https://github.com/Lunio-Canada/lunio-for-wordpress',
            'requires'      => ! empty( $release['requires'] ) ? $release['requires'] : '',
            'tested'        => ! empty( $release['tested'] ) ? $release['tested'] : '',
            'download_link' => $package_url,
            'last_updated'  => ! empty( $release['published_at'] ) ? $release['published_at'] : '',
            'banners'       => array(),
            'sections'      => $sections,
            'external'      => true,
        );
    }

    public function fix_plugin_folder_name( $source, $remote_source, $upgrader, $hook_extra ) {
        if ( ! $this->is_lunio_update( $hook_extra ) ) {
            return $source;
        }

        $expected = trailingslashit( $remote_source ) . self::PLUGIN_SLUG;

        if ( wp_normalize_path( $source ) === wp_normalize_path( $expected ) ) {
            return $source;
        }

        global $wp_filesystem;

        if ( ! $wp_filesystem ) {
            return $source;
        }

        if ( $wp_filesystem->exists( $expected ) ) {
            $wp_filesystem->delete( $expected, true );
        }

        if ( ! $wp_filesystem->move( $source, $expected, true ) ) {
            return new WP_Error( 'lunio_update_rename_failed', __( 'Could not prepare the Lunio update package for installation.', 'lunio-wp' ) );
        }

        return $expected;
    }

    public function clear_cached_release() {
        delete_transient( self::UPDATE_TRANSIENT_KEY );
        delete_site_transient( 'update_plugins' );
    }

    public function get_update_status( $force_refresh = false ) {
        $channel = $this->get_update_channel();
        $installed_version = $this->normalize_version( LUNIO_WP_VERSION );

        $status = array(
            'channel'           => $channel,
            'installed_version' => $installed_version,
            'latest_version'    => __( 'Not checked yet', 'lunio-wp' ),
            'last_checked'      => '',
            'status'            => 'unknown',
            'status_label'      => __( 'Unable to check', 'lunio-wp' ),
            'status_message'    => __( 'Update status has not been checked yet.', 'lunio-wp' ),
            'release_url'       => '',
            'download_url'      => '',
            'package_type'      => '',
        );

        if ( 'disabled' === $channel ) {
            $status['status'] = 'disabled';
            $status['status_label'] = __( 'Disabled', 'lunio-wp' );
            $status['status_message'] = __( 'GitHub update checks are disabled for this site.', 'lunio-wp' );

            return $status;
        }

        if ( 'wordpress_org' === $channel ) {
            $status['status'] = 'wordpress_org';
            $status['status_label'] = __( 'Managed by WordPress.org', 'lunio-wp' );
            $status['status_message'] = __( 'WordPress.org will handle plugin update notifications for this channel.', 'lunio-wp' );

            return $status;
        }

        $release = $this->get_latest_release( $force_refresh );

        if ( isset( $release['last_checked'] ) && is_numeric( $release['last_checked'] ) ) {
            $status['last_checked'] = $this->format_last_checked( (int) $release['last_checked'] );
        }

        if ( isset( $release['error'] ) && '' !== $release['error'] ) {
            $status['status'] = 'error';
            $status['status_label'] = __( 'Unable to check', 'lunio-wp' );
            $status['status_message'] = $release['error'];

            return $status;
        }

        if ( empty( $release['version'] ) ) {
            $status['status'] = 'error';
            $status['status_label'] = __( 'Unable to check', 'lunio-wp' );
            $status['status_message'] = __( 'No GitHub releases were found.', 'lunio-wp' );

            return $status;
        }

        $status['latest_version'] = $release['version'];
        $status['release_url'] = ! empty( $release['release_url'] ) ? $release['release_url'] : '';
        $status['download_url'] = $this->get_preferred_package_url( $release );
        $status['package_type'] = ! empty( $release['package_type'] ) ? $release['package_type'] : '';

        if ( version_compare( $this->normalize_version( $release['version'] ), $installed_version, '>' ) ) {
            $status['status'] = 'update_available';
            $status['status_label'] = __( 'Update available', 'lunio-wp' );
            $status['status_message'] = __( 'A newer GitHub release is available for download and one-click update installation.', 'lunio-wp' );

            return $status;
        }

        $status['status'] = 'up_to_date';
        $status['status_label'] = __( 'Up to date', 'lunio-wp' );
        $status['status_message'] = __( 'You are using the latest available GitHub release.', 'lunio-wp' );

        return $status;
    }

    public function get_latest_release( $force_refresh = false ) {
        if ( ! $force_refresh ) {
            $cached = get_transient( self::UPDATE_TRANSIENT_KEY );

            if ( false !== $cached && is_array( $cached ) ) {
                return $cached;
            }
        }

        $debug = $this->is_debug_enabled();
        $fallback_message = __( 'Unable to check GitHub releases right now. Please try again later.', 'lunio-wp' );
        $response = wp_remote_get(
            self::GITHUB_LATEST_RELEASE_URL,
            array(
                'timeout' => 15,
                'headers' => array(
                    'Accept'     => 'application/vnd.github+json',
                    'User-Agent' => 'Lunio-WordPress-Plugin/' . LUNIO_WP_VERSION . '; ' . home_url( '/' ),
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return $this->cache_release_result(
                array(
                    'error'        => $debug ? $response->get_error_message() : $fallback_message,
                    'last_checked' => time(),
                )
            );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        if ( 200 !== $status_code ) {
            $message = $fallback_message;

            if ( 403 === $status_code ) {
                $message = __( 'GitHub rate limit reached. Please try again later.', 'lunio-wp' );
            } elseif ( $debug ) {
                $message = sprintf( __( 'GitHub API returned status %d.', 'lunio-wp' ), $status_code );
            }

            return $this->cache_release_result(
                array(
                    'error'        => $message,
                    'last_checked' => time(),
                )
            );
        }

        $data = json_decode( $body, true );

        if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $data ) ) {
            return $this->cache_release_result(
                array(
                    'error'        => $debug ? __( 'GitHub returned malformed JSON.', 'lunio-wp' ) : $fallback_message,
                    'last_checked' => time(),
                )
            );
        }

        if ( empty( $data['tag_name'] ) ) {
            return $this->cache_release_result(
                array(
                    'error'        => __( 'No GitHub releases were found.', 'lunio-wp' ),
                    'last_checked' => time(),
                )
            );
        }

        $package = $this->find_release_package( $data );
        $release = array(
            'version'      => sanitize_text_field( $data['tag_name'] ),
            'release_url'  => isset( $data['html_url'] ) ? esc_url_raw( $data['html_url'] ) : '',
            'download_url' => isset( $package['url'] ) ? $package['url'] : '',
            'package_type' => isset( $package['type'] ) ? $package['type'] : '',
            'tested'       => isset( $data['target_commitish'] ) ? '' : '',
            'requires'     => '',
            'body'         => isset( $data['body'] ) ? wp_kses_post( $data['body'] ) : '',
            'published_at' => isset( $data['published_at'] ) ? sanitize_text_field( $data['published_at'] ) : '',
            'last_checked' => time(),
        );

        if ( empty( $release['download_url'] ) ) {
            $release['error'] = __( 'No installable release package was found.', 'lunio-wp' );
        }

        return $this->cache_release_result( $release );
    }

    public function normalize_version( $version ) {
        return ltrim( (string) $version, "vV \t\n\r\0\x0B" );
    }

    public function format_last_checked( $timestamp ) {
        return sprintf(
            __( '%1$s ago', 'lunio-wp' ),
            human_time_diff( $timestamp, current_time( 'timestamp' ) )
        );
    }

    private function cache_release_result( $release ) {
        set_transient( self::UPDATE_TRANSIENT_KEY, $release, self::UPDATE_CACHE_TTL );

        return $release;
    }

    private function get_preferred_package_url( $release ) {
        return ! empty( $release['download_url'] ) ? $release['download_url'] : '';
    }

    private function find_release_package( $data ) {
        if ( ! empty( $data['assets'] ) && is_array( $data['assets'] ) ) {
            foreach ( $data['assets'] as $asset ) {
                if ( empty( $asset['name'] ) || empty( $asset['browser_download_url'] ) ) {
                    continue;
                }

                if ( 'lunio-for-wordpress.zip' === $asset['name'] ) {
                    return array(
                        'url'  => esc_url_raw( $asset['browser_download_url'] ),
                        'type' => 'release_asset',
                    );
                }
            }

            foreach ( $data['assets'] as $asset ) {
                if ( empty( $asset['name'] ) || empty( $asset['browser_download_url'] ) ) {
                    continue;
                }

                if ( '.zip' === strtolower( substr( $asset['name'], -4 ) ) ) {
                    return array(
                        'url'  => esc_url_raw( $asset['browser_download_url'] ),
                        'type' => 'release_asset',
                    );
                }
            }
        }

        if ( ! empty( $data['zipball_url'] ) ) {
            return array(
                'url'  => esc_url_raw( $data['zipball_url'] ),
                'type' => 'zipball',
            );
        }

        return array();
    }

    private function is_lunio_update( $hook_extra ) {
        return ! empty( $hook_extra['plugin'] ) && self::PLUGIN_FILE === $hook_extra['plugin'];
    }

    private function get_update_channel() {
        return apply_filters( 'lunio_wp_update_channel', LUNIO_WP_UPDATE_CHANNEL );
    }

    private function is_github_channel_enabled() {
        return 'github' === $this->get_update_channel();
    }

    private function is_debug_enabled() {
        return (bool) get_option( 'lunio_debug_mode', false );
    }
}
