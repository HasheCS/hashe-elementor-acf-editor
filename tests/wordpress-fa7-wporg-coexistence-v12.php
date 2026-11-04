<?php
/**
 * HP-PLUG-001 v1.2: both official-directory candidates coexist in real,
 * disposable WordPress. No remote reporting or live WordPress write permitted.
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || getenv( 'HASHE_QA_EPHEMERAL' ) !== '1' ) {
    fwrite( STDERR, "ERROR: ephemeral WP-CLI only\n" );
    exit( 2 );
}
require_once ABSPATH . 'wp-admin/includes/plugin.php';
function hp12_assert( $yes, $message ) {
    if ( ! $yes ) {
        WP_CLI::error( 'HP12_COEXISTENCE=' . $message );
    }
    echo 'PASS ' . $message . PHP_EOL;
}
$acf = 'hashe-field-editor/hashe-field-editor.php';
$fa7 = 'hashe-fa7-elementor/hashe-fa7-elementor.php';
hp12_assert( is_plugin_active( $acf ) && is_plugin_active( $fa7 ),
    'both WordPress.org candidates active on the same WordPress installation' );
hp12_assert( get_plugin_data( WP_PLUGIN_DIR . '/' . $acf, false, false )['Version'] === '0.6.0' &&
    get_plugin_data( WP_PLUGIN_DIR . '/' . $fa7, false, false )['Version'] === '1.1.3',
    'correct pinned plugin versions installed' );
hp12_assert( ! strpos( file_get_contents( WP_PLUGIN_DIR . '/' . $acf ), 'Update URI:' ) &&
    ! strpos( file_get_contents( WP_PLUGIN_DIR . '/' . $fa7 ), 'Update URI:' ),
    'both official-directory builds omit custom Update URI' );
hp12_assert( ! has_filter( 'update_plugins_plugins.hashe.com' ) &&
    ! has_filter( 'upgrader_pre_download' ),
    'neither WordPress.org build registers third-party updates or ZIP hooks' );
hp12_assert( class_exists( '\Hashe\FieldEditor\PluginTools\V1\ConsentClient', false ) &&
    class_exists( '\Hashe\FA7\PluginTools\V1\ConsentClient', false ),
    'both embedded consent clients load without class collisions' );
hp12_assert( has_action( 'admin_post_hashe_acf_privacy_choice' ) &&
    has_action( 'admin_post_hashe_fa7_privacy_choice' ) &&
    has_action( 'hashe_acf_optional_reporting_tick' ) &&
    has_action( 'hashe_fa7_optional_reporting_tick' ),
    'consent actions and cron names are independent' );
$acf_key = 'hashe_acf_optional_reporting_v1';
$fa7_key = 'hashe_fa7_optional_reporting_v1';
hp12_assert( get_option( $acf_key, false ) === false &&
    get_option( $fa7_key, false ) === false &&
    ! wp_next_scheduled( 'hashe_acf_optional_reporting_tick' ) &&
    ! wp_next_scheduled( 'hashe_fa7_optional_reporting_tick' ),
    'both plugins default to zero reporting consent and zero scheduled reports' );
update_option( $acf_key, array( 'status' => 'declined', 'at' => time() ), false );
hp12_assert( get_option( $fa7_key, false ) === false,
    'skipping Field Editor does not opt into or alter FA7 reporting' );
update_option( $fa7_key, array( 'status' => 'declined', 'at' => time() ), false );
hp12_assert( get_option( $acf_key )['status'] === 'declined',
    'skipping FA7 does not alter Field Editor reporting' );
delete_option( $acf_key );
delete_option( $fa7_key );
hp12_assert( class_exists( 'Hashe_Elementor_ACF_Editor', false ) &&
    function_exists( 'hashe_fa7_additive_kit' ) &&
    has_action( 'elementor/documents/register_controls' ),
    'both core Elementor plugin integrations remain loaded' );
echo "HP12_WPORG_FA7_AND_ACF_COEXISTENCE=PASS\n";
