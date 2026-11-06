<?php
/**
 * HP-API-001 v1.1 section 4: isolated cross-plugin class, hooks and state QA.
 * Requires the exact pinned FA7 draft source; never sends HTTP or updates WP.
 */
if ( PHP_SAPI !== 'cli' || ! isset( $argv[1] ) ||
    ! is_dir( $argv[1] ) || ! is_file( $argv[1] . '/hashe-fa7-elementor.php' ) ) {
    fwrite( STDERR, "ERROR: pinned FA7 plugin directory argument required\n" );
    exit( 2 );
}
define( 'ABSPATH', __DIR__ );
$GLOBALS['qa_hooks'] = array();
function add_filter( $name, $callback, $priority = 10, $accepted_args = 1 ) {
    $GLOBALS['qa_hooks']['filter'][ $name ][] = array( $callback, $accepted_args );
}
function add_action( $name, $callback, $priority = 10, $accepted_args = 1 ) {
    $GLOBALS['qa_hooks']['action'][ $name ][] = array( $callback, $accepted_args );
}
function qa_assert( $ok, $name ) {
    if ( ! $ok ) {
        fwrite( STDERR, 'FAIL ' . $name . PHP_EOL );
        exit( 3 );
    }
    echo 'PASS ' . $name . PHP_EOL;
}

$acf = dirname( __DIR__ ) . '/distribution/independent/hashe-core/v1/';
$fa7 = rtrim( $argv[1], '/' ) . '/includes/hashe-core/v1/';
$module_paths = array(
    $acf . 'update-client.php', $acf . 'consent-client.php',
    $fa7 . 'update-client.php', $fa7 . 'consent-client.php',
);
if ( ( $argv[2] ?? '' ) === 'fa7-first' ) {
    $module_paths = array(
        $fa7 . 'update-client.php', $fa7 . 'consent-client.php',
        $acf . 'update-client.php', $acf . 'consent-client.php',
    );
}
foreach ( $module_paths as $path ) {
    qa_assert( is_file( $path ), 'required pinned module file exists' );
    require $path;
}
$update_a = 'Hashe\\FieldEditor\\PluginTools\\V1\\UpdateClient';
$update_f = 'Hashe\\FA7\\PluginTools\\V1\\UpdateClient';
$consent_a = 'Hashe\\FieldEditor\\PluginTools\\V1\\ConsentClient';
$consent_f = 'Hashe\\FA7\\PluginTools\\V1\\ConsentClient';
foreach ( array( $update_a, $update_f, $consent_a, $consent_f ) as $class ) {
    qa_assert( class_exists( $class, false ), 'embedded class loaded: ' . $class );
}
qa_assert( $update_a !== $update_f && $consent_a !== $consent_f,
    'distinct plugin namespaces prevent class redeclaration and version fallback' );

$basename_a = 'hashe-elementor-acf-editor/hashe-elementor-acf-editor.php';
$basename_f = 'hashe-fa7-elementor/hashe-fa7-elementor.php';
$uri_a = 'https://plugins.hashe.com/hashe-elementor-acf-editor/';
$uri_f = 'https://plugins.hashe.com/hashe-fa7-elementor/';
$u_a = new $update_a( 'hashe-elementor-acf-editor', $basename_a, $uri_a,
    'https://plugins.hashe.com/api/v1/updates/hashe-elementor-acf-editor' );
$u_f = new $update_f( 'hashe-fa7-elementor', $basename_f, $uri_f,
    'https://plugins.hashe.com/api/v1/updates/hashe-fa7-elementor' );
$u_a->register();
$u_f->register();
qa_assert( count( $GLOBALS['qa_hooks']['filter']['update_plugins_plugins.hashe.com'] ?? array() ) === 2,
    'both plugins register independent native update callbacks' );
qa_assert( count( $GLOBALS['qa_hooks']['filter']['upgrader_pre_download'] ?? array() ) === 2,
    'both plugins retain separate ZIP integrity handlers' );
qa_assert( $u_a->check( 'unchanged', array(), $basename_f, array() ) === 'unchanged' &&
    $u_f->check( 'unchanged', array(), $basename_a, array() ) === 'unchanged',
    'each native updater ignores other plugin update checks' );
qa_assert( $u_a->verify_download( 'untouched', '', null, array( 'plugin' => $basename_f ) ) === 'untouched' &&
    $u_f->verify_download( 'untouched', '', null, array( 'plugin' => $basename_a ) ) === 'untouched',
    'each ZIP verifier ignores other plugin downloads' );

$c_a = new $consent_a( 'hashe-elementor-acf-editor', $basename_a );
$c_f = new $consent_f( 'hashe-fa7-elementor', $basename_f );
$c_a->register();
$c_f->register();
foreach ( array( 'option', 'notice', 'hook' ) as $field ) {
    $a = new ReflectionProperty( $consent_a, $field );
    $f = new ReflectionProperty( $consent_f, $field );
    $a->setAccessible( true );
    $f->setAccessible( true );
    $value_a = $a->getValue( $c_a );
    $value_f = $f->getValue( $c_f );
    qa_assert( is_string( $value_a ) && is_string( $value_f ) &&
        $value_a !== $value_f && strpos( $value_a, 'hashe_acf_' ) === 0 &&
        strpos( $value_f, 'hashe_fa7_' ) === 0,
        'independent ' . $field . ' avoids cross-plugin consent state' );
}
qa_assert( isset( $GLOBALS['qa_hooks']['action']['admin_post_hashe_acf_privacy_choice'] ) &&
    isset( $GLOBALS['qa_hooks']['action']['admin_post_hashe_fa7_privacy_choice'] ),
    'opt-in routes do not collide' );
qa_assert( isset( $GLOBALS['qa_hooks']['action']['hashe_acf_optional_reporting_tick'] ) &&
    isset( $GLOBALS['qa_hooks']['action']['hashe_fa7_optional_reporting_tick'] ),
    'optional report schedules do not collide' );
echo "HP_PLUGINS_CROSS_PLUGIN_NAMESPACE_AND_HOOK_QA=PASS\n";
