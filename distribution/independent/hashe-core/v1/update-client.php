<?php
/**
 * Hashe WordPress standalone update client v1.0 (embedded, shared, no telemetry).
 *
 * The website-owned HP-WEB-001 approved-release feed is the sole authority. Independent package ONLY; excluded from the WordPress.org build.
 * This module never contacts a site-registration or analytics endpoint.
 */
namespace Hashe\PluginTools\V1;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( __NAMESPACE__ . '\\UpdateClient', false ) ) {
    final class UpdateClient {
        private $slug;
        private $plugin_file;
        private $update_uri;
        private $feed_uri;

        public function __construct( $slug, $plugin_file, $update_uri, $feed_uri ) {
            $this->slug        = $slug;
            $this->plugin_file = $plugin_file;
            $this->update_uri  = $update_uri;
            $this->feed_uri    = $feed_uri;
        }

        public function register() {
            add_filter( 'update_plugins_plugins.hashe.com', array( $this, 'check' ), 10, 4 );
            add_filter( 'upgrader_pre_download', array( $this, 'verify_download' ), 10, 4 );
        }

        /** Return false instead of interfering with WordPress on any error. */
        public function check( $update, $plugin_data, $plugin_file, $locales ) {
            if ( $plugin_file !== $this->plugin_file ||
                ! is_array( $plugin_data ) ||
                ( $plugin_data['UpdateURI'] ?? '' ) !== $this->update_uri ) {
                return $update;
            }
            $installed = (string) ( $plugin_data['Version'] ?? '' );
            if ( ! preg_match( '/^[0-9]+\.[0-9]+\.[0-9]+$/D', $installed ) ) {
                return false;
            }

            $cache_key = 'hashe_v1_update_' . $this->slug;
            $manifest  = get_site_transient( $cache_key );
            if ( ! is_array( $manifest ) ) {
                $result = wp_remote_get(
                    $this->feed_uri,
                    array(
                        'timeout'             => 4,
                        'redirection'         => 0,
                        'sslverify'           => true,
                        'limit_response_size' => 32768,
                        'user-agent'         => 'Hashe-Plugin-UpdateClient/1.0',
                        'headers'             => array( 'Accept' => 'application/json' ),
                    )
                );
                if ( is_wp_error( $result ) ||
                    200 !== (int) wp_remote_retrieve_response_code( $result ) ) {
                    return false;
                }
                $body = (string) wp_remote_retrieve_body( $result );
                if ( strlen( $body ) < 40 || strlen( $body ) > 32768 ) {
                    return false;
                }
                $manifest = json_decode( $body, true );
                if ( ! is_array( $manifest ) || ! $this->valid_manifest( $manifest ) ) {
                    return false;
                }
                // Only validated, explicitly approved stable metadata is cached.
                set_site_transient( $cache_key, $manifest, 6 * HOUR_IN_SECONDS );
            }
            if ( ! $this->valid_manifest( $manifest ) ||
                version_compare( $manifest['version'], $installed, '<=' ) ) {
                return false;
            }

            return array(
                'id'           => $this->update_uri,
                'slug'         => $this->slug,
                'version'      => $manifest['version'],
                'url'          => $manifest['details_url'],
                'package'      => $manifest['package_url'],
                'tested'       => $manifest['tested'],
                'requires_php' => $manifest['requires_php'],
            );
        }


        /**
         * Verify the exact published GitHub ZIP against the approved feed
         * before WordPress unpacks or executes it. Only this plugin is touched.
         *
         * WP_Upgrader::download_package() accepts a temporary ZIP path
         * returned by upgrader_pre_download.
         */
        public function verify_download( $reply, $package, $upgrader, $hook_extra ) {
            if ( false !== $reply ||
                ! is_array( $hook_extra ) ||
                ( $hook_extra['plugin'] ?? '' ) !== $this->plugin_file ) {
                return $reply;
            }
            $manifest = get_site_transient( 'hashe_v1_update_' . $this->slug );
            if ( ! is_array( $manifest ) ||
                ! $this->valid_manifest( $manifest ) ||
                $package !== $manifest['package_url'] ) {
                return new \WP_Error(
                    'hashe_acf_unverified_update',
                    'The approved Hashe update metadata is unavailable or does not match this package.'
                );
            }

            if ( ! function_exists( 'download_url' ) ) {
                return new \WP_Error(
                    'hashe_acf_download_unavailable',
                    'WordPress download_url is not available for this update.'
                );
            }
            $temporary = download_url( $package, 300 );
            if ( is_wp_error( $temporary ) ) {
                return $temporary;
            }
            if ( ! is_string( $temporary ) || ! is_file( $temporary ) ) {
                return new \WP_Error(
                    'hashe_acf_missing_zip',
                    'The update ZIP was not downloaded to a readable local file.'
                );
            }
            $digest = hash_file( 'sha256', $temporary );
            if ( ! is_string( $digest ) ||
                ! hash_equals( $manifest['sha256'], $digest ) ) {
                wp_delete_file( $temporary );
                return new \WP_Error(
                    'hashe_acf_checksum_failed',
                    'The update ZIP did not match the approved SHA-256 checksum.'
                );
            }
            return $temporary;
        }

        private function valid_manifest( $m ) {
            if ( ( $m['slug'] ?? null ) !== $this->slug ||
                ( $m['status'] ?? null ) !== 'stable' ||
                ( $m['approved'] ?? null ) !== true ||
                ( $m['prerelease'] ?? null ) !== false ) {
                return false;
            }

            foreach ( array( 'version', 'tag', 'sha256', 'package_url', 'details_url', 'tested', 'requires_php' ) as $key ) {
                if ( ! isset( $m[ $key ] ) || ! is_string( $m[ $key ] ) ) {
                    return false;
                }
            }

            $ver = $m['version'];
            if ( ! preg_match( '/^[0-9]+\.[0-9]+\.[0-9]+$/D', $ver ) ||
                $m['tag'] !== 'v' . $ver ||
                ! preg_match( '/^[a-f0-9]{64}$/D', $m['sha256'] ) ||
                ! preg_match( '/^[0-9]+\.[0-9]+(?:\.[0-9]+)?$/D', $m['requires_php'] ) ||
                ! preg_match( '/^[0-9]+\.[0-9]+(?:\.[0-9]+)?$/D', $m['tested'] ) ) {
                return false;
            }

            // The only acceptable package is this repository's exact stable
            // GitHub release asset; never use a dynamic branch, prerelease
            // source archive, arbitrary host, or a redirect supplied by data.
            $expected = 'https://github.com/HasheCS/' . $this->slug .
                '/releases/download/v' . $ver . '/' . $this->slug . '-v' . $ver . '.zip';
            if ( $m['package_url'] !== $expected ||
                $m['details_url'] !== 'https://github.com/HasheCS/' .
                $this->slug . '/releases/tag/v' . $ver ) {
                return false;
            }
            return true;
        }
    }
}
