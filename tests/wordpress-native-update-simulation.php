<?php
/**
 * Actual WordPress Plugin_Upgrader integration in disposable CI only.
 * Synthetic 0.6.1 ZIP is never published or fetched over the network.
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    fwrite( STDERR, "ERROR: run on isolated WP-CLI only\n" );
    exit( 2 );
}
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

$slug = 'hashe-elementor-acf-editor';
$basename = $slug . '/' . $slug . '.php';
$main = WP_PLUGIN_DIR . '/' . $basename;
$zip = getenv( 'HASHE_QA_SYNTHETIC_ZIP' );
if ( ! is_string( $zip ) || ! is_file( $zip ) || ! is_file( $main ) ) {
    WP_CLI::error( 'Synthetic fixture ZIP or installed candidate missing' );
}
$before = get_plugin_data( $main, false, false );
if ( $before['Version'] !== '0.6.0' ) {
    WP_CLI::error( 'Start state must be independent candidate v0.6.0' );
}
if ( false === has_filter( 'upgrader_pre_download' ) ||
    false === has_filter( 'update_plugins_plugins.hashe.com' ) ) {
    WP_CLI::error( 'WordPress updater hooks are not loaded' );
}

$uri = 'https://plugins.hashe.com/hashe-elementor-acf-editor/';
$endpoint = 'https://plugins.hashe.com/api/v1/updates/hashe-elementor-acf-editor';
$package = 'https://github.com/HasheCS/hashe-elementor-acf-editor/releases/download/v0.6.1/hashe-elementor-acf-editor-v0.6.1.zip';
$manifest = array(
    'slug'         => $slug,
    'status'       => 'stable',
    'approved'     => true,
    'prerelease'   => false,
    'version'      => '0.6.1',
    'tag'          => 'v0.6.1',
    'sha256'       => hash_file( 'sha256', $zip ),
    'details_url'  => 'https://github.com/HasheCS/hashe-elementor-acf-editor/releases/tag/v0.6.1',
    'package_url'  => $package,
    'tested'       => '7.1',
    'requires_php' => '7.4',
);
$good_sha = $manifest['sha256'];
$downloads = 0;
$feed_requests = 0;
$guard = static function ( $pre, $args, $url ) use ( $zip, $package, $endpoint, &$downloads, &$feed_requests ) {
    if ( $url === $endpoint ) {
        ++$feed_requests;
        return new WP_Error( 'qa_offline', 'No real update feed HTTP requests in CI' );
    }
    if ( $url !== $package ) {
        return $pre;
    }
    ++$downloads;
    if ( empty( $args['stream'] ) || empty( $args['filename'] ) ||
        ! copy( $zip, $args['filename'] ) ) {
        return new WP_Error( 'qa_stream_failed', 'Could not simulate ZIP download' );
    }
    return array(
        'response' => array( 'code' => 200, 'message' => 'OK' ),
        'headers'  => array( 'content-type' => 'application/zip' ),
        'body'     => '',
        'cookies'  => array(),
        'filename' => $args['filename'],
    );
};
add_filter( 'pre_http_request', $guard, 10, 3 );
set_site_transient( 'hashe_v1_update_' . $slug, $manifest, 900 );
$offer = apply_filters(
    'update_plugins_plugins.hashe.com',
    false,
    array( 'UpdateURI' => $uri, 'Version' => '0.6.0' ),
    $basename,
    array( 'en_US' )
);
if ( ! is_array( $offer ) ||
    ( $offer['version'] ?? '' ) !== '0.6.1' ||
    ( $offer['package'] ?? '' ) !== $package ) {
    WP_CLI::error( 'Native WordPress update filter did not offer approved synthetic update' );
}

$args = array(
    'package'           => $package,
    'destination'       => WP_PLUGIN_DIR,
    'clear_destination' => true,
    'clear_working'     => true,
    'is_multi'          => false,
    'hook_extra'        => array( 'plugin' => $basename, 'type' => 'plugin', 'action' => 'update' ),
);
$before_hash = hash_file( 'sha256', $main );
$manifest['sha256'] = str_repeat( 'f', 64 );
set_site_transient( 'hashe_v1_update_' . $slug, $manifest, 900 );
$failed = ( new Plugin_Upgrader( new WP_Upgrader_Skin() ) )->run( $args );
if ( ! is_wp_error( $failed ) ||
    $failed->get_error_code() !== 'hashe_acf_checksum_failed' ||
    ! is_file( $main ) ||
    hash_file( 'sha256', $main ) !== $before_hash ) {
    WP_CLI::error( 'Tampered synthetic ZIP was not blocked before altering installed code' );
}
echo "WP_CORE_REJECTS_BAD_SHA256=PASS\n";

$manifest['sha256'] = $good_sha;
set_site_transient( 'hashe_v1_update_' . $slug, $manifest, 900 );
$success = ( new Plugin_Upgrader( new WP_Upgrader_Skin() ) )->run( $args );
if ( is_wp_error( $success ) || ! $success ) {
    WP_CLI::error( 'WordPress native upgrader refused the verified synthetic ZIP' );
}
$after = get_plugin_data( $main, false, false );
if ( $after['Version'] !== '0.6.1' || $feed_requests !== 0 || $downloads !== 2 ) {
    WP_CLI::error( 'Synthetic update install or no-live-network assertions failed' );
}
if ( get_option( 'hashe_acf_optional_reporting_v1', false ) !== false ) {
    WP_CLI::error( 'Native updater silently opted into optional analytics' );
}
remove_filter( 'pre_http_request', $guard, 10 );
echo "WP_CORE_INSTALLED_VERIFIED_SYNTHETIC_ZIP=PASS\n";
echo "UPDATE_WITHOUT_OPTIONAL_TELEMETRY=PASS\n";
echo "NOTE=Only synthetic v0.6.1 in disposable CI; no production API or release tested\n";
