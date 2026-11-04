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

            $manifest = $this->approved_manifest();
            if ( ! $manifest || version_compare( $manifest['version'], $installed, '<=' ) ) {
                return false;
            }

            return array(
                'id'           => $this->update_uri,
                'slug'         => $this->slug,
                'version'      => $manifest['version'],
                'url'          => $manifest['details_url'],
                'package'      => $manifest['package_url'],
                'tested'       => $manifest['tested'],
                'requires'     => $manifest['requires'] ?? $manifest['requires_wordpress'],
                'requires_php' => $manifest['requires_php'],
            );
        }


        /** Load only validated, approved stable metadata (no site identity or telemetry). */
        private function approved_manifest() {
            $key = 'hashe_v1_update_' . $this->slug;
            $cached = get_site_transient( $key );
            if ( is_array( $cached ) && $this->valid_manifest( $cached ) ) {
                return $cached;
            }

            $response = wp_remote_get( $this->feed_uri, array(
                'timeout'             => 4,
                'redirection'         => 0,
                'sslverify'           => true,
                'limit_response_size' => 32768,
                'user-agent'          => 'Hashe-Plugin-UpdateClient/1.0',
                'headers'             => array( 'Accept' => 'application/json' ),
            ) );
            if ( is_wp_error( $response ) ||
                200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
                return false;
            }
            $body = (string) wp_remote_retrieve_body( $response );
            if ( strlen( $body ) < 40 || strlen( $body ) > 32768 ) {
                return false;
            }
            $manifest = json_decode( $body, true );
            if ( ! is_array( $manifest ) || ! $this->valid_manifest( $manifest ) ) {
                return false;
            }

            $header = wp_remote_retrieve_header( $response, 'cache-control' );
            $cache_control = is_string( $header ) ? $header : '';
            if ( preg_match( '/(?:^|,)\s*(?:no-store|private)\b/i', $cache_control ) ) {
                return false;
            }
            $ttl = 300;
            if ( preg_match( '/(?:^|,)\s*max-age\s*=\s*(\d+)/i', $cache_control, $match ) ) {
                $ttl = min( 300, (int) $match[1] );
            }
            if ( $ttl < 1 ) {
                return false;
            }
            set_site_transient( $key, $manifest, $ttl );
            return $manifest;
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
            $manifest = $this->approved_manifest();
            if ( ! $manifest || $package !== $manifest['package_url'] ) {
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
            $wp_min = $m['requires'] ?? $m['requires_wordpress'] ?? null;
            $compat_version = '/^[0-9]+\.[0-9]+(?:\.[0-9]+)?$/D';
            if ( ! preg_match( '/^[0-9]+\.[0-9]+\.[0-9]+$/D', $ver ) ||
                $m['tag'] !== 'v' . $ver ||
                ! preg_match( '/^[a-f0-9]{64}$/D', $m['sha256'] ) ||
                ! is_string( $wp_min ) || ! preg_match( $compat_version, $wp_min ) ||
                ( isset( $m['requires'], $m['requires_wordpress'] ) &&
                    $m['requires'] !== $m['requires_wordpress'] ) ||
                ! preg_match( $compat_version, $m['requires_php'] ) ||
                ! preg_match( $compat_version, $m['tested'] ) ||
                version_compare( $m['tested'], $wp_min, '<' ) ) {
                return false;
            }

            // The website must approve this exact ZIP, version and SHA256.
            // Accept a versioned independent asset basename without inventing
            // the release filename. Never accept another repository, channel,
            // GitHub branch/source archive, nested path, query or redirect.
            $prefix = 'https://github.com/HasheCS/' . $this->slug .
                '/releases/download/v' . $ver . '/';
            if ( 0 !== strpos( $m['package_url'], $prefix ) ) {
                return false;
            }
            $filename = substr( $m['package_url'], strlen( $prefix ) );
            if ( ! preg_match( '/^' . preg_quote( $this->slug, '/' ) .
                '-[A-Za-z0-9][A-Za-z0-9._-]*\.zip$/D', $filename ) ||
                false !== strpos( $filename, '..' ) ||
                false !== stripos( $filename, 'wporg' ) ||
                false !== stripos( $filename, 'wordpress-org' ) ||
                $m['details_url'] !== 'https://github.com/HasheCS/' .
                $this->slug . '/releases/tag/v' . $ver ) {
                return false;
            }
            return true;
        }
    }
}
