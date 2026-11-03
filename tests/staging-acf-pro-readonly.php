<?php
/**
 * Hashe Field Editor v0.6.0 read-only ACF Pro integration on authorized QA.
 *
 * Strict guard: https://dev.sasseagency.com/solutions only. Reads existing
 * draft post 654 and checks Elementor Page Settings registration against
 * real ACF Pro field groups. Does not add/update posts, fields or options.
 *
 * Does not replace real-browser UI, save workflows or ACF Pro license testing.
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ||
    getenv( 'HASHE_FIELD_EDITOR_STAGING_AUTHORIZED' ) !== '1' ) {
    fwrite( STDERR, "ERROR: explicit authorized staging WP-CLI only\n" );
    exit( 2 );
}
$url = home_url();
$parts = wp_parse_url( $url );
if ( ! is_array( $parts ) ||
    ( $parts['scheme'] ?? '' ) !== 'https' ||
    ( $parts['host'] ?? '' ) !== 'dev.sasseagency.com' ||
    rtrim( $parts['path'] ?? '', '/' ) !== '/solutions' ) {
    WP_CLI::error( 'Refusing to run outside the authorized Solutions QA site' );
}
require_once ABSPATH . 'wp-admin/includes/plugin.php';
$expected = 'hashe-elementor-acf-editor/hashe-elementor-acf-editor.php';
$installed = WP_PLUGIN_DIR . '/' . $expected;
if ( ! is_plugin_active( $expected ) ||
    ! is_plugin_active( 'advanced-custom-fields-pro/acf.php' ) ||
    ! is_plugin_active( 'elementor/elementor.php' ) ||
    ! is_file( $installed ) ) {
    WP_CLI::error( 'Field Editor, Elementor and ACF Pro must be active' );
}
$meta = get_plugin_data( $installed, false, false );
if ( $meta['Version'] !== '0.6.0' || $meta['Name'] !== 'Hashe Field Editor' ) {
    WP_CLI::error( 'Expected installed v0.6.0 QA candidate' );
}
if ( ! defined( 'ACF_VERSION' ) || ! function_exists( 'acf_get_field_groups' ) ||
    ! function_exists( 'acf_get_fields' ) || ! function_exists( 'acf_save_post' ) ) {
    WP_CLI::error( 'Real ACF Pro runtime was not initialized' );
}
$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
if ( ! $admins ) {
    WP_CLI::error( 'QA administrator context not available' );
}
wp_set_current_user( (int) $admins[0] );
$id = 654;
$post = get_post( $id );
if ( ! $post || $post->post_type !== 'page' || $post->post_status !== 'draft' ||
    ! current_user_can( 'edit_post', $id ) ) {
    WP_CLI::error( 'Expected protected Field Editor QA draft page 654' );
}
$groups = acf_get_field_groups( array( 'post_id' => $id ) );
if ( ! is_array( $groups ) || ! $groups ) {
    WP_CLI::error( 'No ACF Pro field group assigned to QA page 654' );
}
$plugin = \Hashe_Elementor_ACF_Editor::instance();
$document = \Elementor\Plugin::instance()->documents->get( $id );
if ( ! $document || ! method_exists( $document, 'get_controls' ) ) {
    WP_CLI::error( 'Real Elementor Page Settings document not available' );
}
$controls = $document->get_controls();
$expected_ids = array();
$actual_types = array();
foreach ( $groups as $group ) {
    $fields = acf_get_fields( $group );
    if ( ! is_array( $fields ) ) { continue; }
    foreach ( $fields as $field ) {
        if ( empty( $field['key'] ) ) { continue; }
        $actual_types[] = $field['type'] ?? 'unknown';
        $expected_ids[] = 'heae_acf_' . $field['key'];
    }
}
$registered_ids = array_filter( $expected_ids, static function ( $key ) use ( $controls ) {
    return array_key_exists( $key, $controls );
} );
if ( ! $registered_ids ||
    ! isset( $controls['heae_acf_save_action'] ) ||
    ( $controls['heae_acf_save_action']['type'] ?? '' ) !== \Elementor\Controls_Manager::BUTTON ) {
    WP_CLI::error( 'Native ACF Pro controls or Save button missing from real Elementor document' );
}
if ( false === has_action( 'wp_ajax_heae_save_acf' ) ||
    false === has_filter( 'elementor/document/save/data' ) ) {
    WP_CLI::error( 'ACF saving or Elementor source-of-truth hooks absent' );
}
if ( ! class_exists( '\Elementor\Repeater' ) ) {
    WP_CLI::error( 'Native Elementor repeatable controls not loaded' );
}
$reflect = new \ReflectionMethod( 'Hashe_Elementor_ACF_Editor', 'make_control' );
$reflect->setAccessible( true );
$sample = array(
    'key' => 'field_heae_qa_inner',
    'name' => 'inner',
    'label' => 'Inner',
    'type' => 'text',
);
$pro_cases = array(
    array( 'type' => 'gallery' ),
    array( 'type' => 'repeater', 'sub_fields' => array( $sample ) ),
    array( 'type' => 'group', 'sub_fields' => array( $sample ) ),
    array( 'type' => 'clone', 'sub_fields' => array( $sample ) ),
    array( 'type' => 'flexible_content', 'layouts' => array(
        array( 'name' => 'intro', 'label' => 'Intro', 'sub_fields' => array( $sample ) ),
    ) ),
    array( 'type' => 'relationship', 'post_type' => array( 'page' ) ),
    array( 'type' => 'google_map' ),
    array( 'type' => 'link' ),
);
foreach ( $pro_cases as $case ) {
    $field = array_merge( array(
        'key' => 'field_heae_qa_' . $case['type'],
        'name' => 'qa_' . $case['type'],
        'label' => 'QA ' . $case['type'],
    ), $case );
    $result = $reflect->invoke( $plugin, $field, $id, false );
    if ( ! is_array( $result ) || count( $result ) !== 2 ||
        empty( $result[1]['type'] ) ) {
        WP_CLI::error( 'ACF Pro native adapter failed: ' . $case['type'] );
    }
}
echo 'STAGING_WORDPRESS_VERSION=' . get_bloginfo( 'version' ) . PHP_EOL;
echo 'STAGING_ACF_PRO_VERSION=' . ACF_VERSION . PHP_EOL;
echo 'STAGING_ELEMENTOR_VERSION=' . ( defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : 'unknown' ) . PHP_EOL;
echo 'STAGING_FIELD_GROUPS=' . count( $groups ) . PHP_EOL;
echo 'STAGING_MATCHING_FIELD_COUNT=' . count( $actual_types ) . PHP_EOL;
echo 'STAGING_REGISTERED_FIELD_CONTROLS=' . count( $registered_ids ) . PHP_EOL;
echo 'STAGING_ACF_PRO_ADAPTER_MAPPINGS=' . count( $pro_cases ) . PHP_EOL;
echo 'STAGING_ACF_PRO_ELEMENTOR_NATIVE_CONTROL_QA=PASS' . PHP_EOL;
echo 'NO_POST_OR_SETTINGS_WRITES_IN_THIS_TEST=YES' . PHP_EOL;
