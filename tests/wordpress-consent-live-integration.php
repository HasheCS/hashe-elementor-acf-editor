<?php
/**
 * Field Editor consent / revocation integration in disposable WordPress with simulated HTTP.
 * Each QA_STAGE invocation is a separate WP-CLI process sharing only the QA database.
 * Never run on a live site.
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    fwrite( STDERR, "This test requires isolated WordPress WP-CLI.\n" );
    exit( 2 );
}
require_once ABSPATH . 'wp-admin/includes/template.php';
$stage = getenv( 'HASHE_QA_CONSENT_STAGE' );
$option = 'hashe_acf_optional_reporting_v1';
$hook = 'hashe_acf_optional_reporting_tick';
$slug = 'hashe-elementor-acf-editor';
$client = new \Hashe\FieldEditor\PluginTools\V1\ConsentClient(
    $slug, 'hashe-elementor-acf-editor/hashe-elementor-acf-editor.php'
);
wp_set_current_user( 1 );
if ( ! current_user_can( 'manage_options' ) ) {
    WP_CLI::error( 'QA administrator is unavailable' );
}
$contract = array(
    'version' => 'qa-v1',
    'privacy_url' => 'https://plugins.hashe.com/privacy',
    'retention' => 'QA simulation only. No actual remote data stored.',
    'cadence' => 'Every 30 days',
    'report_endpoint' => 'https://plugins.hashe.com/api/v1/telemetry/report',
    'revoke_endpoint' => 'https://plugins.hashe.com/api/v1/telemetry/revoke',
    'authenticated_headers' => static function ( $mode ) {
        return array( 'Authorization' => 'Bearer ' . str_repeat( 'q', 40 ) );
    },
);
if ( ! in_array( $stage, array( 'default', 'skip', 'skipped', 'offline' ), true ) ) {
    add_filter( 'hashe_acf_optional_reporting_contract', static function () use ( $contract ) {
        return $contract;
    } );
}
add_filter( 'pre_http_request', static function ( $pre, $args, $url ) use ( $stage, $contract ) {
    if ( $url !== $contract['report_endpoint'] && $url !== $contract['revoke_endpoint'] ) {
        if ( 0 === strpos( $url, 'https://plugins.hashe.com/' ) ) {
            return new WP_Error( 'qa_unexpected_hashe_url', 'No external Hashe service in QA' );
        }
        return $pre;
    }
    $log = get_option( 'hashe_acf_qa_http_log', array() );
    $log[] = array( 'url' => $url, 'body' => (string) ( $args['body'] ?? '' ) );
    update_option( 'hashe_acf_qa_http_log', $log, false );
    if ( empty( $args['sslverify'] ) || ( $args['redirection'] ?? 1 ) !== 0 ) {
        return new WP_Error( 'qa_tls_violation', 'TLS verification or redirect safeguards missing' );
    }
    return array(
        'response' => array( 'code' => $stage === 'revoke-fail' ? 503 : 204 ),
        'headers' => array(),
        'cookies' => array(),
        'body' => '',
    );
}, 10, 3 );
function qa_consent_assert( $ok, $message ) {
    if ( ! $ok ) { WP_CLI::error( $message ); }
}
function qa_consent_choice( $client, $choice, $checked = false ) {
    $_POST = array(
        'action' => 'hashe_acf_privacy_choice',
        'hashe_acf_choice' => $choice,
        '_wpnonce' => wp_create_nonce( 'hashe_acf_privacy_choice' ),
    );
    if ( $checked ) { $_POST['hashe_acf_explicit_consent'] = '1'; }
    $_REQUEST = $_POST;
    $client->handle_admin_choice();
    WP_CLI::error( 'Admin choice handler returned instead of redirect/exit' );
}
$state = get_option( $option, false );
$log = get_option( 'hashe_acf_qa_http_log', array() );
switch ( $stage ) {
    case 'default':
        qa_consent_assert( $state === false && ! wp_next_scheduled( $hook ), 'Default-off initialization failed' );
        ob_start(); $client->settings_page(); $page = ob_get_clean();
        qa_consent_assert( strpos( $page, 'Skip / Continue without sharing' ) !== false, 'Skip button missing' );
        qa_consent_assert( strpos( $page, 'hashe_acf_choice" value="allow"' ) === false, 'Allow exposed without contract' );
        do_action( $hook );
        qa_consent_assert( get_option( 'hashe_acf_qa_http_log', array() ) === array(), 'Unconsented request made' );
        break;
    case 'skip':
        qa_consent_choice( $client, 'skip' );
        break;
    case 'skipped':
        qa_consent_assert( is_array( $state ) && $state['status'] === 'declined' && ! isset( $state['installation_id'] ), 'Skip generated an installation identity' );
        qa_consent_assert( ! wp_next_scheduled( $hook ), 'Skip scheduled reporting' );
        do_action( $hook );
        qa_consent_assert( get_option( 'hashe_acf_qa_http_log', array() ) === array(), 'Skipped site made request' );
        break;
    case 'allow':
        qa_consent_choice( $client, 'allow', true );
        break;
    case 'report':
        qa_consent_assert( is_array( $state ) && $state['status'] === 'allowed' && $state['policy_version'] === 'qa-v1', 'Explicit consent missing' );
        qa_consent_assert( isset( $state['installation_id'] ) && wp_next_scheduled( $hook ), 'Identifier / scheduled reporting missing' );
        do_action( $hook );
        $log = get_option( 'hashe_acf_qa_http_log', array() );
        qa_consent_assert( count( $log ) === 1 && $log[0]['url'] === $contract['report_endpoint'], 'Expected exactly one mock report' );
        $payload = json_decode( $log[0]['body'], true );
        qa_consent_assert( is_array( $payload ) && count( $payload ) === 6, 'Report fields do not match disclosure' );
        qa_consent_assert( $payload['installation_id'] === $state['installation_id'] && $payload['plugin_slug'] === $slug, 'Report identity mismatched' );
        qa_consent_assert( ! isset( $payload['email'] ) && ! isset( $payload['password'] ), 'Sensitive fields disclosed' );
        break;
    case 'offline':
        qa_consent_assert( is_array( $state ) && $state['status'] === 'allowed', 'Offline check has no consent state' );
        do_action( $hook );
        qa_consent_assert( count( get_option( 'hashe_acf_qa_http_log', array() ) ) === 1, 'Report sent when contract unavailable' );
        break;
    case 'revoke-fail':
        qa_consent_choice( $client, 'revoke' );
        break;
    case 'pending':
        qa_consent_assert( is_array( $state ) && $state['status'] === 'revocation-pending' && isset( $state['installation_id'] ), 'Failed deletion lost identity' );
        qa_consent_assert( ! wp_next_scheduled( $hook ), 'Reporting still scheduled after revocation' );
        ob_start(); $client->settings_page(); $page = ob_get_clean();
        qa_consent_assert( strpos( $page, 'Retry deletion request' ) !== false, 'Deletion retry missing' );
        qa_consent_assert( strpos( $page, 'name="hashe_acf_choice" value="skip"' ) === false, 'Skip offered during pending deletion' );
        qa_consent_assert( strpos( $page, 'name="hashe_acf_choice" value="allow"' ) === false, 'Allow offered during pending deletion' );
        do_action( $hook );
        qa_consent_assert( count( get_option( 'hashe_acf_qa_http_log', array() ) ) === 2, 'Revoked site reported again' );
        break;
    case 'skip-pending':
        qa_consent_choice( $client, 'skip' );
        break;
    case 'allow-pending':
        qa_consent_choice( $client, 'allow', true );
        break;
    case 'pending-persisted':
        qa_consent_assert( $state['status'] === 'revocation-pending' && isset( $state['installation_id'] ), 'Stale action erased pending deletion identity' );
        qa_consent_assert( count( $log ) === 2, 'Stale action caused remote request' );
        break;
    case 'retry':
        qa_consent_choice( $client, 'retry-deletion' );
        break;
    case 'revoked':
        qa_consent_assert( $state['status'] === 'revoked' && ! isset( $state['installation_id'] ), 'Successful deletion did not clear local identity' );
        qa_consent_assert( ! wp_next_scheduled( $hook ), 'Revoked site still schedules reports' );
        do_action( $hook );
        $log = get_option( 'hashe_acf_qa_http_log', array() );
        qa_consent_assert( count( $log ) === 3 && $log[1]['url'] === $contract['revoke_endpoint'] && $log[2]['url'] === $contract['revoke_endpoint'], 'Deletion was not retried correctly' );
        qa_consent_assert( $log[1]['body'] === $log[2]['body'], 'Retry changed the target installation identifier' );
        break;
    default:
        WP_CLI::error( 'Unsupported integration QA stage: ' . $stage );
}
echo 'WORDPRESS_CONSENT_STAGE=' . $stage . '=PASS' . PHP_EOL;
