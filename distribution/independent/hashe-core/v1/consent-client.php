<?php
/**
 * Optional, independent Hashe installation reporting consent client.
 *
 * This module is bundled with the plugin and contains NO enabled reporting
 * service, site identifiers, credentials, or mandatory analytics.
 */
namespace Hashe\FieldEditor\PluginTools\V1;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( __NAMESPACE__ . '\\ConsentClient', false ) ) {
    final class ConsentClient {
        private $option = 'hashe_acf_optional_reporting_v1';
        private $notice = 'hashe_acf_show_privacy_notice';
        private $hook = 'hashe_acf_optional_reporting_tick';
        private $slug;
        private $plugin_file;

        public function __construct( $slug, $plugin_file ) {
            $this->slug = $slug;
            $this->plugin_file = $plugin_file;
        }

        public function register() {
            add_action( 'admin_menu', array( $this, 'menu' ) );
            add_action( 'admin_notices', array( $this, 'activation_notice' ) );
            add_action( 'admin_post_hashe_acf_privacy_choice', array( $this, 'handle_admin_choice' ) );
            add_action( $this->hook, array( $this, 'send_report' ) );
            add_action( 'rest_api_init', array( $this, 'register_site_proof' ) );
        }

        public static function on_activation() {
            $state = get_option( 'hashe_acf_optional_reporting_v1', false );
            if ( false === $state ) {
                update_option( 'hashe_acf_show_privacy_notice', '1', false );
            } elseif ( is_array( $state ) && 'allowed' === ( $state['status'] ?? '' ) &&
                ! wp_next_scheduled( 'hashe_acf_optional_reporting_tick' ) ) {
                wp_schedule_single_event( time() + 60, 'hashe_acf_optional_reporting_tick' );
            }
        }

        public static function on_deactivation() {
            wp_clear_scheduled_hook( 'hashe_acf_optional_reporting_tick' );
            // Previously granted consent is not reset to "allowed" if revoked.
        }

        public static function on_uninstall() {
            wp_clear_scheduled_hook( 'hashe_acf_optional_reporting_tick' );
            delete_option( 'hashe_acf_optional_reporting_v1' );
            delete_option( 'hashe_acf_show_privacy_notice' );
        }

        private function preference() {
            $value = get_option( $this->option, array() );
            return is_array( $value ) ? $value : array();
        }

        /**
         * The service owner must explicitly supply a reviewed contract.
         * Empty/default contract means NO outgoing optional requests and no
         * possible opt-in. Site operators cannot enable this using a URL
         * entered in a form.
         *
         * Contract fields: version, privacy_url, retention, cadence,
         * report_endpoint, revoke_endpoint, enroll_endpoint. Authentication uses a
         * local random secret generated only after explicit administrator Allow.
         */
        private function contract() {
            $c = apply_filters( 'hashe_acf_optional_reporting_contract', array() );
            if ( ! is_array( $c ) ) {
                return false;
            }
            foreach ( array( 'version', 'privacy_url', 'retention', 'cadence',
                'report_endpoint', 'revoke_endpoint', 'enroll_endpoint' ) as $field ) {
                if ( ! isset( $c[ $field ] ) ) {
                    return false;
                }
            }
            foreach ( array( 'version', 'privacy_url', 'retention', 'cadence',
                'report_endpoint', 'revoke_endpoint', 'enroll_endpoint' ) as $field ) {
                if ( ! is_string( $c[ $field ] ) || '' === trim( $c[ $field ] ) ) {
                    return false;
                }
            }
            if ( ! preg_match( '/^[A-Za-z0-9._-]{1,32}$/D', $c['version'] ) ||
                strlen( $c['retention'] ) > 300 ||
                strlen( $c['cadence'] ) > 200 || 'Every 30 days' !== $c['cadence'] ) {
                return false;
            }
            $allowed = array(
                'report_endpoint' => 'https://plugins.hashe.com/api/v1/telemetry/report',
                'revoke_endpoint' => 'https://plugins.hashe.com/api/v1/telemetry/revoke',
                'enroll_endpoint' => 'https://plugins.hashe.com/api/v1/telemetry/enroll',
            );
            foreach ( $allowed as $field => $url ) {
                if ( $c[ $field ] !== $url ) {
                    return false; // No redirect, SSRF or arbitrary destination.
                }
            }
            if ( 0 !== strpos( $c['privacy_url'], 'https://plugins.hashe.com/' ) ||
                false === filter_var( $c['privacy_url'], FILTER_VALIDATE_URL ) ) {
                return false;
            }
            return $c;
        }

        public function menu() {
            add_options_page(
                'Hashe Field Editor for Elementor Privacy',
                'Hashe Field Editor for Elementor Privacy',
                'manage_options',
                'hashe-field-editor-privacy',
                array( $this, 'settings_page' )
            );
        }

        public function activation_notice() {
            if ( ! current_user_can( 'manage_options' ) ||
                '1' !== get_option( $this->notice, '' ) ) {
                return;
            }
            echo '<div class="notice notice-info"><p>' .
                esc_html( 'Hashe Field Editor for Elementor works without installation analytics.' ) .
                ' <a href="' . esc_url( admin_url( 'options-general.php?page=hashe-field-editor-privacy' ) ) . '">' .
                esc_html( 'Review optional privacy settings' ) . '</a></p></div>';
        }

        private function form_button( $action, $caption ) {
            echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
            echo '<input type="hidden" name="action" value="hashe_acf_privacy_choice">';
            echo '<input type="hidden" name="hashe_acf_choice" value="' . esc_attr( $action ) . '">';
            wp_nonce_field( 'hashe_acf_privacy_choice' );
            submit_button( $caption, 'secondary', 'submit', false );
            echo '</form>';
        }

        public function settings_page() {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html( 'Insufficient permissions.' ) );
            }
            $state = $this->preference();
            $status = $state['status'] ?? 'not-decided';
            $contract = $this->contract();
            echo '<div class="wrap"><h1>' . esc_html( 'Hashe Field Editor for Elementor: Privacy' ) . '</h1>';
            echo '<p>' . esc_html( 'Icon libraries, licensed Kit rendering and WordPress update checks work without optional installation reporting.' ) . '</p>';
            echo '<p><strong>' . esc_html( 'Optional reporting status:' ) . '</strong> ' .
                esc_html( $status ) . '</p>';
            echo '<p>' . esc_html( 'Operator: Hashe Computer Solutions (Pvt) Ltd. If enabled after an explicit choice, optional reports would contain: site URL, plugin slug and version, random installation identifier, reporting time, and last-reported activation state. No site content, user accounts, emails, passwords, debug logs or Kit credentials.' ) . '</p>';
            if ( $contract ) {
                echo '<p>' . esc_html( 'Reporting interval: ' . $contract['cadence'] ) . '</p>';
                echo '<p>' . esc_html( 'Data retention and deletion: ' . $contract['retention'] ) . '</p>';
                echo '<p><a href="' . esc_url( $contract['privacy_url'] ) . '" rel="noopener noreferrer" target="_blank">' . esc_html( 'Privacy policy' ) . '</a></p>';
            } else {
                echo '<p><strong>' . esc_html( 'Optional installation reporting is not available. No reporting endpoint or approved disclosure contract is enabled in this plugin. The Allow action is disabled; Skip remains available. WordPress update checks are separate.' ) . '</strong></p>';
            }
            if ( 'allowed' === $status ) {
                $this->form_button( 'revoke', 'Revoke permission and request deletion' );
            } elseif ( 'revocation-pending' === $status ) {
                echo '<p>' . esc_html( 'Optional reports have stopped. Server-side deletion has not yet been confirmed. Retry the deletion request when the reporting service is available.' ) . '</p>';
                $this->form_button( 'retry-deletion', 'Retry deletion request' );
            } else {
                if ( $contract ) {
                    echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
                    echo '<input type="hidden" name="action" value="hashe_acf_privacy_choice">';
                    echo '<input type="hidden" name="hashe_acf_choice" value="allow">';
                    wp_nonce_field( 'hashe_acf_privacy_choice' );
                    echo '<p><label><input type="checkbox" name="hashe_acf_explicit_consent" value="1" required> ' .
                        esc_html( 'I have read the details above and voluntarily allow optional installation reporting.' ) .
                        '</label></p>';
                    submit_button( 'Allow & Continue', 'primary', 'submit', false );
                    echo '</form>';
                }
                $this->form_button( 'skip', 'Skip / Continue without sharing' );
            }
            echo '</div>';
        }

        public function handle_admin_choice() {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html( 'Insufficient permissions.' ), '', array( 'response' => 403 ) );
            }
            check_admin_referer( 'hashe_acf_privacy_choice' );
            $choice = isset( $_POST['hashe_acf_choice'] ) && is_string( $_POST['hashe_acf_choice'] )
                ? sanitize_key( wp_unslash( $_POST['hashe_acf_choice'] ) ) : '';
            $prior = $this->preference();
            $now = time();
            if ( 'skip' === $choice ) {
                // A stale or forged Skip request must never erase an active
                // installation identity or an unfinished deletion request.
                if ( in_array( $prior['status'] ?? '', array( 'allowed', 'revocation-pending' ), true ) ) {
                    wp_die( esc_html( 'Use Revoke or Retry deletion before changing this privacy choice.' ), '', array( 'response' => 409 ) );
                }
                wp_clear_scheduled_hook( $this->hook );
                update_option( $this->option, array( 'status' => 'declined', 'at' => $now ), false );
            } elseif ( 'allow' === $choice ) {
                if ( 'revocation-pending' === ( $prior['status'] ?? '' ) ) {
                    wp_die( esc_html( 'Complete the outstanding deletion request before enabling reporting again.' ), '', array( 'response' => 409 ) );
                }
                $contract = $this->contract();
                $explicit = isset( $_POST['hashe_acf_explicit_consent'] ) &&
                    is_string( $_POST['hashe_acf_explicit_consent'] )
                    ? sanitize_text_field( wp_unslash( $_POST['hashe_acf_explicit_consent'] ) ) : '';
                if ( ! $contract || '1' !== $explicit ) {
                    wp_die( esc_html( 'Reporting cannot be enabled without the approved disclosure and explicit opt-in.' ), '', array( 'response' => 400 ) );
                }
                if ( 'allowed' === ( $prior['status'] ?? '' ) &&
                    ( $prior['policy_version'] ?? '' ) !== $contract['version'] ) {
                    wp_die( esc_html( 'Revoke the old reporting permission before accepting a changed privacy notice.' ), '', array( 'response' => 409 ) );
                }
                $reuse = 'allowed' === ( $prior['status'] ?? '' ) &&
                    preg_match( '/\\A[a-f0-9]{64}\\z/D', (string) ( $prior['secret_hex'] ?? '' ) ) &&
                    preg_match( '/\\A[0-9a-f-]{36}\\z/D', (string) ( $prior['installation_id'] ?? '' ) );
                $identity = $reuse ? $prior['installation_id'] : wp_generate_uuid4();
                $secret = $reuse ? $prior['secret_hex'] : bin2hex( random_bytes(32) );
                update_option( $this->option, array(
                    'status' => 'allowed',
                    'at' => $now,
                    'policy_version' => $contract['version'],
                    'installation_id' => $identity,
                    'secret_hex' => $secret,
                    'consented_at_utc' => $reuse && isset( $prior['consented_at_utc'] )
                        ? $prior['consented_at_utc'] : gmdate( 'Y-m-d\\TH:i:s\\Z', $now ),
                    'enrolled' => $reuse && ! empty( $prior['enrolled'] ),
                ), false );
                $this->schedule_report( 60 );
            } elseif ( 'revoke' === $choice || 'retry-deletion' === $choice ) {
                wp_clear_scheduled_hook( $this->hook );
                if ( ! in_array( $prior['status'] ?? '', array( 'allowed', 'revocation-pending' ), true ) ) {
                    wp_die( esc_html( 'No active consent to revoke.' ), '', array( 'response' => 400 ) );
                }
                // Stop all reporting BEFORE attempting server-side removal.
                $prior['status'] = 'revocation-pending';
                $prior['revoked_at'] = $now;
                update_option( $this->option, $prior, false );
                if ( $this->send_deletion_request( $prior ) ) {
                    update_option( $this->option, array( 'status' => 'revoked', 'at' => $now ), false );
                }
            } else {
                wp_die( esc_html( 'Unsupported privacy action.' ), '', array( 'response' => 400 ) );
            }
            delete_option( $this->notice );
            wp_safe_redirect( admin_url( 'options-general.php?page=hashe-field-editor-privacy' ) );
            exit;
        }

        /** Publicly verifies domain ownership only after admin Allow and a reviewed contract.
         * An arbitrary visitor never receives the secret, only a nonce-bound HMAC.
         * There is no installation ID or proof route response before opt-in.
         */
        public function register_site_proof() {
            if ( ! function_exists( 'register_rest_route' ) ) return;
            register_rest_route( 'hashe/v1', '/site-proof', array(
                'methods' => 'GET',
                'callback' => array( $this, 'answer_site_proof' ),
                'permission_callback' => '__return_true',
            ) );
        }

        public function answer_site_proof( $request ) {
            $state = $this->preference();
            $contract = $this->contract();
            $nonce = is_object( $request ) && method_exists( $request, 'get_param' )
                ? $request->get_param( 'challenge' ) : '';
            $installation = $state['installation_id'] ?? '';
            $secret = $state['secret_hex'] ?? '';
            if ( ! $contract || 'allowed' !== ( $state['status'] ?? '' ) ||
                $contract['version'] !== ( $state['policy_version'] ?? '' ) ||
                ! is_string( $nonce ) || ! preg_match( '/\A[0-9a-f]{64}\z/D', $nonce ) ||
                ! is_string( $secret ) || ! preg_match( '/\A[0-9a-f]{64}\z/D', $secret ) ||
                ! is_string( $installation ) ||
                ! preg_match( '/\A[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/D', $installation ) ) {
                return new \WP_Error( 'hashe_site_proof_unavailable', 'Not available.', array( 'status' => 404 ) );
            }
            $proof = hash_hmac( 'sha256', $nonce . "\n" . $this->slug . "\n" . $installation,
                hex2bin( $secret ) );
            return array(
                'schema_version' => 1,
                'plugin' => $this->slug,
                'proof' => $proof,
            );
        }

        private function schedule_report( $delay ) {
            if ( ! wp_next_scheduled( $this->hook ) ) {
                wp_schedule_single_event( time() + (int) $delay, $this->hook );
            }
        }

        /** An installation secret is local and not issued by an external filter. */
        private function headers( $state ) {
            $secret = (string) ( $state['secret_hex'] ?? '' );
            if ( ! preg_match( '/\\A[0-9a-f]{64}\\z/D', $secret ) ) return false;
            return array(
                'Authorization' => 'Bearer ' . $secret,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            );
        }

        private function post( $url, $data, $state ) {
            $allowed = array(
                'https://plugins.hashe.com/api/v1/telemetry/enroll',
                'https://plugins.hashe.com/api/v1/telemetry/report',
                'https://plugins.hashe.com/api/v1/telemetry/revoke',
            );
            if ( ! in_array( $url, $allowed, true ) ) return false;
            $headers = $this->headers( $state );
            if ( ! $headers ) return false;
            $response = wp_remote_post( $url, array(
                'timeout' => 10,
                'redirection' => 0,
                'sslverify' => true,
                'limit_response_size' => 4096,
                'headers' => $headers,
                'body' => wp_json_encode( $data ),
            ) );
            return ! is_wp_error( $response ) &&
                in_array( (int) wp_remote_retrieve_response_code( $response ), array( 200, 201, 204 ), true );
        }

        public function send_report() {
            $state = $this->preference();
            $contract = $this->contract();
            if ( ! $contract || 'allowed' !== ( $state['status'] ?? '' ) ||
                $contract['version'] !== ( $state['policy_version'] ?? '' ) ||
                ! isset( $state['installation_id'], $state['secret_hex'], $state['consented_at_utc'] ) ) {
                return; // Reporting cannot activate without a reviewed contract and Allow.
            }
            $data = array(
                'plugin' => $this->slug,
                'version' => (string) ( get_file_data( WP_PLUGIN_DIR . '/' . $this->plugin_file, array( 'Version' => 'Version' ) )['Version'] ?? '' ),
                'site_url' => get_site_url(),
                'installation_id' => $state['installation_id'],
                'consent' => array(
                    'granted' => true,
                    'notice_version' => $state['policy_version'],
                    'granted_at_utc' => $state['consented_at_utc'],
                ),
            );
            if ( empty( $state['enrolled'] ) ) {
                // Exact same-host WordPress REST URL is verified by Hashe with
                // a fresh server-owned challenge and the local consent secret.
                $enrollment = $data;
                $enrollment['site_proof_url'] = rest_url( 'hashe/v1/site-proof' );
                if ( ! $this->post( $contract['enroll_endpoint'], $enrollment, $state ) ) {
                    $this->schedule_report( DAY_IN_SECONDS );
                    return; // Site-ownership proof failed; do not send report.
                }
                $state['enrolled'] = true;
                update_option( $this->option, $state, false );
            }
            $ok = $this->post( $contract['report_endpoint'], $data, $state );
            if ( $ok ) {
                $state['last_reported_at'] = time();
                update_option( $this->option, $state, false );
            }
            $this->schedule_report( $ok ? 30 * DAY_IN_SECONDS : DAY_IN_SECONDS );
        }

        private function send_deletion_request( $state ) {
            if ( ! isset( $state['installation_id'], $state['secret_hex'] ) ) return false;
            // Revocation works even if the operator disabled NEW reporting.
            return $this->post( 'https://plugins.hashe.com/api/v1/telemetry/revoke', array(
                'installation_id' => $state['installation_id'],
                'plugin' => $this->slug,
            ), $state );
        }
    }
}
