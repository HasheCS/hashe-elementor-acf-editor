<?php
/**
 * CI-only integration checks on disposable WordPress, Elementor and ACF Free.
 *
 * This verifies a real plugin load, dependency hooks, ACF storage, package
 * identity, and the boundary between update checks and optional telemetry.
 * It is NOT browser/editor UI QA or ACF Pro acceptance.
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    fwrite( STDERR, "ERROR: isolated WP-CLI test only\n" );
    exit( 2 );
}
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$distribution = getenv( 'HASHE_QA_DISTRIBUTION' );
$slug         = 'wordpress-org' === $distribution
    ? 'hashe-field-editor'
    : 'hashe-elementor-acf-editor';
$basename     = $slug . '/' . $slug . '.php';
$main_file    = WP_PLUGIN_DIR . '/' . $basename;
if ( ! is_file( $main_file ) ) {
    WP_CLI::error( 'Expected candidate ZIP main file is missing: ' . $basename );
}
$header = get_plugin_data( $main_file, false, false );
if ( 'Hashe Field Editor' !== $header['Name'] || '0.6.0' !== $header['Version'] ) {
    WP_CLI::error( 'Plugin name or candidate version mismatched' );
}
if ( strpos( file_get_contents( $main_file ), 'Requires Plugins: elementor, advanced-custom-fields' ) === false ) {
    WP_CLI::error( 'Mandatory dependency header is absent' );
}
if ( ! is_plugin_active( 'elementor/elementor.php' ) ||
    ! is_plugin_active( 'advanced-custom-fields/acf.php' ) ||
    ! is_plugin_active( $basename ) ) {
    WP_CLI::error( 'Real Elementor, ACF Free and candidate must all be active' );
}
if ( ! class_exists( 'Hashe_Elementor_ACF_Editor' ) ||
    ! class_exists( '\Elementor\Controls_Manager' ) ||
    ! function_exists( 'acf_add_local_field_group' ) ||
    ! function_exists( 'acf_save_post' ) ) {
    WP_CLI::error( 'Expected integration classes/functions did not load' );
}
if ( false === has_action( 'wp_ajax_heae_save_acf' ) ||
    false === has_action( 'elementor/documents/register_controls' ) ||
    false === has_filter( 'elementor/document/save/data' ) ) {
    WP_CLI::error( 'Elementor/ACF save and editor lifecycle hooks are missing' );
}

$standalone = 'wordpress-org' !== $distribution;
$update_uri = $header['UpdateURI'] ?? '';
if ( $standalone ) {
    if ( 'https://plugins.hashe.com/hashe-elementor-acf-editor/' !== $update_uri ||
        false === has_filter( 'update_plugins_plugins.hashe.com' ) ||
        false === has_filter( 'upgrader_pre_download' ) ) {
        WP_CLI::error( 'Independent native updater identity/hooks are missing' );
    }
    if ( false === has_action( 'admin_post_hashe_acf_privacy_choice' ) ||
        false === has_action( 'hashe_acf_optional_reporting_tick' ) ) {
        WP_CLI::error( 'Independent opt-in and revocation controls are missing' );
    }
} else {
    if ( ! empty( $update_uri ) ||
        false !== has_filter( 'update_plugins_plugins.hashe.com' ) ||
        false !== has_action( 'admin_post_hashe_acf_privacy_choice' ) ) {
        WP_CLI::error( 'Directory package contains independent update/telemetry functionality' );
    }
}

$privacy = get_option( 'hashe_acf_optional_reporting_v1', false );
if ( false !== $privacy && ! is_array( $privacy ) ) {
    WP_CLI::error( 'Unexpected consent data format' );
}
if ( is_array( $privacy ) && 'allowed' === ( $privacy['status'] ?? '' ) ) {
    WP_CLI::error( 'Install or upgrade silently opted into installation reporting' );
}
if ( wp_next_scheduled( 'hashe_acf_optional_reporting_tick' ) ) {
    WP_CLI::error( 'Installation analytics scheduled without consent' );
}

if ( getenv( 'HASHE_QA_UPGRADE' ) === '1' &&
    get_option( 'hashe_acf_qa_upgrade_marker' ) !== 'preserved-from-v0.5.1' ) {
    WP_CLI::error( 'Existing WordPress option did not survive old-version upgrade' );
}

$field_key = 'field_heae_qa_text';
$page_id = wp_insert_post( array(
    'post_title'   => 'Hashe Field Editor isolated CI page',
    'post_type'    => 'page',
    'post_status'  => 'draft',
    'post_content' => '',
), true );
if ( is_wp_error( $page_id ) || ! $page_id ) {
    WP_CLI::error( 'Could not create disposable ACF QA page' );
}
acf_add_local_field_group( array(
    'key'      => 'group_heae_qa',
    'title'    => 'Hashe isolated ACF test',
    'fields'   => array( array(
        'key'   => $field_key,
        'name'  => 'heae_qa_text',
        'label' => 'Field Editor QA',
        'type'  => 'text',
    ) ),
    'location' => array( array( array(
        'param'    => 'post_type',
        'operator' => '==',
        'value'    => 'page',
    ) ) ),
) );
if ( ! update_field( $field_key, 'acf-qa-value', $page_id ) ||
    get_field( $field_key, $page_id, false ) !== 'acf-qa-value' ) {
    WP_CLI::error( 'Real ACF Free field storage round-trip failed' );
}

$contact = array();
$guard = static function ( $pre, $args, $url ) use ( &$contact ) {
    if ( strpos( $url, 'https://plugins.hashe.com/' ) === 0 ) {
        $contact[] = $url;
        return new WP_Error( 'hashe_qa_offline', 'No external Hashe requests in isolated CI' );
    }
    return $pre;
};
add_filter( 'pre_http_request', $guard, 10, 3 );
if ( $standalone ) {
    do_action( 'hashe_acf_optional_reporting_tick' );
}
remove_filter( 'pre_http_request', $guard, 10 );
if ( $contact !== array() ) {
    WP_CLI::error( 'Core functionality generated optional service calls without consent' );
}

wp_delete_post( $page_id, true );
echo 'WORDPRESS_VERSION=' . get_bloginfo( 'version' ) . PHP_EOL;
echo 'PHP_VERSION=' . PHP_VERSION . PHP_EOL;
echo 'ELEMENTOR_VERSION=' . ( defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : 'unknown' ) . PHP_EOL;
echo 'ACF_VERSION=' . ( defined( 'ACF_VERSION' ) ? ACF_VERSION : 'unknown' ) . PHP_EOL;
echo 'DISTRIBUTION=' . $distribution . PHP_EOL;
echo 'CLEAN_INSTALL_OR_UPGRADE=PASS' . PHP_EOL;
echo 'REAL_ACF_STORAGE=PASS' . PHP_EOL;
echo 'ELEMENTOR_ACF_HOOKS=PASS' . PHP_EOL;
echo 'PRIVACY_DEFAULT_OFF=PASS' . PHP_EOL;
echo 'INDEPENDENT_UPDATE_VS_WORDPRESS_DIRECTORY_BOUNDARY=PASS' . PHP_EOL;
echo 'NOTE=Browser editor/save UX and ACF Pro remain separate acceptance gates' . PHP_EOL;
