<?php
/** Isolated PHP 7.4+ consent, opt-out, reporting boundary and revocation tests. */
define( 'ABSPATH', __DIR__ );
define( 'DAY_IN_SECONDS', 86400 );
$GLOBALS['qa_options'] = array();
$GLOBALS['qa_scheduled'] = array();
$GLOBALS['qa_remote'] = array();
$GLOBALS['qa_contract'] = array();
$GLOBALS['qa_hooks'] = array();
function qa( $assert, $description ) {
    if ( ! $assert ) { fwrite( STDERR, "FAIL $description\n" ); exit( 2 ); }
    echo "PASS $description\n";
}
function add_action( $hook, $callback ) { $GLOBALS['qa_hooks'][$hook] = $callback; }
function get_option( $key, $default = false ) { return $GLOBALS['qa_options'][$key] ?? $default; }
function update_option( $key, $value, $autoload = null ) { $GLOBALS['qa_options'][$key] = $value; return true; }
function delete_option( $key ) { unset( $GLOBALS['qa_options'][$key] ); return true; }
function wp_next_scheduled( $hook ) { return $GLOBALS['qa_scheduled'][$hook] ?? false; }
function wp_schedule_single_event( $when, $hook ) { $GLOBALS['qa_scheduled'][$hook] = $when; return true; }
function wp_clear_scheduled_hook( $hook ) { unset( $GLOBALS['qa_scheduled'][$hook] ); return 1; }
function apply_filters( $name, $default ) { return $name === 'hashe_acf_optional_reporting_contract' ? $GLOBALS['qa_contract'] : $default; }
function esc_html( $s ) { return htmlspecialchars( $s, ENT_QUOTES ); }
function esc_attr( $s ) { return htmlspecialchars( $s, ENT_QUOTES ); }
function esc_url( $s ) { return htmlspecialchars( $s, ENT_QUOTES ); }
function admin_url( $s ) { return 'http://local.test/wp-admin/' . $s; }
function current_user_can( $c ) { return true; }
function wp_nonce_field( $a ) { echo 'NONCE_FOR_' . $a; }
function submit_button( $label ) { echo 'SUBMIT_BUTTON_' . $label; }
function add_options_page( $title, $menu, $cap, $slug, $cb ) { $GLOBALS['qa_menu'] = array( $cap, $slug, $cb ); }
function wp_remote_post( $url, $options ) {
    $GLOBALS['qa_remote'][] = array( 'url' => $url, 'options' => $options );
    return array( 'response' => array( 'code' => 204 ) );
}
function is_wp_error( $value ) { return false; }
function wp_remote_retrieve_response_code( $response ) { return $response['response']['code'] ?? 0; }
function wp_json_encode( $v ) { return json_encode( $v ); }
function get_site_url() { return 'https://client.example/'; }
function rest_url( $route ) { return 'https://client.example/wp-json/' . ltrim( $route, '/' ); }
function get_file_data( $p, $fields ) { return array( 'Version' => '0.6.0' ); }
function wp_generate_uuid4() { return '11111111-1111-4111-8111-111111111111'; }
function check_admin_referer( $action ) { return true; }
function sanitize_key( $value ) { return strtolower((string)$value); }
function sanitize_text_field( $value ) { return (string)$value; }
function wp_unslash( $value ) { return $value; }
class QaExit extends Exception {}
function wp_safe_redirect( $url ) { throw new QaExit( 'redirect_after_choice' ); }
function wp_die( $message, $title = '', $args = array() ) { throw new RuntimeException( 'wp_die:' . $message ); }
define( 'WP_PLUGIN_DIR', __DIR__ . '/qa-plugins' );

require dirname( __DIR__ ) . '/distribution/independent/hashe-core/v1/consent-client.php';
$client = new \Hashe\FieldEditor\PluginTools\V1\ConsentClient(
    'hashe-elementor-acf-editor', 'hashe-elementor-acf-editor/hashe-elementor-acf-editor.php'
);
$client->register();
qa( isset( $GLOBALS['qa_hooks']['admin_post_hashe_acf_privacy_choice'] ), 'admin only POST hook registered' );
qa( isset( $GLOBALS['qa_hooks']['hashe_acf_optional_reporting_tick'] ), 'optional scheduled callback registered' );
\Hashe\FieldEditor\PluginTools\V1\ConsentClient::on_activation();
qa( get_option( 'hashe_acf_show_privacy_notice' ) === '1', 'first activation shows privacy settings notice' );
qa( get_option( 'hashe_acf_optional_reporting_v1', false ) === false, 'no consent or installation ID created on activation' );
$client->send_report();
qa( $GLOBALS['qa_remote'] === array(), 'no telemetry requests before consent' );
$client->menu();
qa( $GLOBALS['qa_menu'][0] === 'manage_options', 'only site administrators may access privacy controls' );
ob_start();
$client->settings_page();
$page = ob_get_clean();
qa( strpos( $page, 'Skip / Continue without sharing' ) !== false, 'Skip always offered' );
qa( strpos( $page, 'SUBMIT_BUTTON_Allow & Continue' ) === false, 'Allow disabled without reviewed contract' );
qa( strpos( $page, 'No reporting endpoint or approved disclosure contract' ) !== false, 'unavailable service disclosed' );

update_option( 'hashe_acf_optional_reporting_v1', array( 'status' => 'declined', 'at' => time() ), false );
$client->send_report();
qa( $GLOBALS['qa_remote'] === array(), 'declined mode sends no data' );
\Hashe\FieldEditor\PluginTools\V1\ConsentClient::on_activation();
qa( count( $GLOBALS['qa_scheduled'] ) === 0, 'declined consent is not reinstated at activation' );

$GLOBALS['qa_contract'] = array(
    'version' => 'v1-approved',
    'privacy_url' => 'https://plugins.hashe.com/privacy',
    'retention' => '30 days after revocation.',
    'cadence' => 'Every 30 days',
    'report_endpoint' => 'https://plugins.hashe.com/api/v1/telemetry/report',
    'revoke_endpoint' => 'https://plugins.hashe.com/api/v1/telemetry/revoke',
    'enroll_endpoint' => 'https://plugins.hashe.com/api/v1/telemetry/enroll',
);
ob_start();
$client->settings_page();
$page = ob_get_clean();
qa( strpos( $page, 'Allow & Continue' ) !== false, 'Allow form only with approved contract and unchecked opt-in checkbox' );
qa( strpos( $page, 'type="checkbox"' ) !== false && strpos( $page, 'checked' ) === false, 'opt-in checkbox initially unchecked' );


$_POST = array('hashe_acf_choice'=>'allow','hashe_acf_explicit_consent'=>'1');
try { $client->handle_admin_choice(); qa(false,'Allow should redirect'); }
catch(QaExit $ex) { qa(true,'Allow accepted only through verified admin action'); }
$state=get_option('hashe_acf_optional_reporting_v1');
qa( $state['status']==='allowed' && preg_match('/^[0-9a-f]{64}$/D',$state['secret_hex'])===1,
    'first Allow creates random site-local 256-bit secret' );
qa( isset($state['consented_at_utc']) && ($state['enrolled']??true)===false,
    'Allow records timestamp and does not self-enroll' );
qa( $GLOBALS['qa_remote']===array(),'Allow does not synchronously contact reporting backend' );
$client->send_report();
qa( count($GLOBALS['qa_remote'])===2, 'first opt-in triggers verified enrollment then first report' );
$enroll=$GLOBALS['qa_remote'][0];
$report=$GLOBALS['qa_remote'][1];
qa( $enroll['url']===$GLOBALS['qa_contract']['enroll_endpoint'] &&
    $report['url']===$GLOBALS['qa_contract']['report_endpoint'],
    'enroll/report destination both pinned and ordered' );
qa( $enroll['options']['headers']['Authorization']==='Bearer '.$state['secret_hex'],
    'request token derived solely from locally consented site secret' );
$body=json_decode($report['options']['body'],true);
qa( $body['plugin']==='hashe-elementor-acf-editor' && $body['version']==='0.6.0' &&
    $body['consent']['granted']===true && $body['consent']['notice_version']==='v1-approved',
    'backend report schema binds plugin/version/notice and explicit consent' );
qa( count($body)===5 && !isset($body['email']) && !isset($body['password']),
    'optional report sends no email/content/password' );
qa( get_option('hashe_acf_optional_reporting_v1')['enrolled']===true,
    'enrollment status persisted only after successful server response' );
qa( !empty($GLOBALS['qa_scheduled']['hashe_acf_optional_reporting_tick']),
    'next report scheduled after consented reporting' );

$_POST = array('hashe_acf_choice'=>'revoke');
try {$client->handle_admin_choice();qa(false,'Revoke should redirect');}
catch(QaExit $ex){qa(true,'Revoke action accepted via admin approval');}
qa( count($GLOBALS['qa_remote'])===3 &&
    $GLOBALS['qa_remote'][2]['url']==='https://plugins.hashe.com/api/v1/telemetry/revoke',
    'revoke posts deletion to fixed endpoint' );
qa( get_option('hashe_acf_optional_reporting_v1')['status']==='revoked',
    'successful revocation clears local active installation credential' );
$client->send_report();
qa( count($GLOBALS['qa_remote'])===3, 'revocation immediately stops all further reports' );
\Hashe\FieldEditor\PluginTools\V1\ConsentClient::on_deactivation();
qa( $GLOBALS['qa_scheduled']===array(), 'deactivation removes optional telemetry cron event' );
\Hashe\FieldEditor\PluginTools\V1\ConsentClient::on_activation();
qa( $GLOBALS['qa_scheduled']===array(), 'revoked consent never silently reactivates' );

$GLOBALS['qa_contract']['report_endpoint']='https://evil.example/collect';
update_option('hashe_acf_optional_reporting_v1', array(
    'status'=>'allowed', 'policy_version'=>'v1-approved',
    'installation_id'=>'11111111-1111-4111-8111-111111111111',
    'secret_hex'=>str_repeat('b',64),
    'consented_at_utc'=>gmdate('Y-m-d\\TH:i:s\\Z'),
    'enrolled'=>true,
), false);
$client->send_report();
qa( count($GLOBALS['qa_remote'])===3,'unapproved endpoint never receives report');
\Hashe\FieldEditor\PluginTools\V1\ConsentClient::on_uninstall();
qa( get_option('hashe_acf_optional_reporting_v1',false)===false,
    'uninstall removes local consent state' );
echo "CONSENT_SECURITY_AND_LIFECYCLE_TESTS=PASS\n";
