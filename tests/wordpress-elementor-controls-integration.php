<?php
/**
 * Real WordPress+Elementor Page Settings registration QA.
 * Runs only on ephemeral WordPress via WP-CLI. This checks the document
 * controls and native save button, not actual Chromium rendering.
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || getenv( 'HASHE_QA_EPHEMERAL' ) !== '1' ) {
    fwrite( STDERR, "ERROR: disposable WP-CLI required\n" );
    exit( 2 );
}
wp_set_current_user( 1 );
$fields = array(
    array( 'key' => 'field_heae_controls_text', 'name' => 'heae_controls_text', 'label' => 'Text field', 'type' => 'text' ),
    array( 'key' => 'field_heae_controls_number', 'name' => 'heae_controls_number', 'label' => 'Number field', 'type' => 'number' ),
    array( 'key' => 'field_heae_controls_toggle', 'name' => 'heae_controls_toggle', 'label' => 'Boolean field', 'type' => 'true_false' ),
);
$group_key = 'group_heae_native_controls_acceptance';
$group = array(
    'key' => $group_key,
    'title' => 'Native controls acceptance',
    'fields' => $fields,
    'location' => array( array( array(
        'param' => 'post_type', 'operator' => '==', 'value' => 'page',
    ) ) ),
);
acf_add_local_field_group( $group );
$id = wp_insert_post( array(
    'post_title' => 'Disposable Elementor Page Settings QA',
    'post_type' => 'page', 'post_status' => 'draft',
), true );
if ( is_wp_error( $id ) || ! $id ) {
    WP_CLI::error( 'Could not create temporary page for native editor controls' );
}
update_post_meta( $id, '_elementor_edit_mode', 'builder' );

$elementor = \Elementor\Plugin::instance();
$document = $elementor->documents->get( $id );
if ( ! $document || ! is_object( $document ) ||
    ! method_exists( $document, 'get_controls' ) ) {
    WP_CLI::error( 'Elementor document for QA page could not be loaded' );
}
$registered = $document->get_controls();
if ( ! is_array( $registered ) ) {
    WP_CLI::error( 'Elementor controls did not return an array' );
}
$want = array(
    'heae_section_' . $group_key,
    'heae_acf_field_heae_controls_text',
    'heae_acf_field_heae_controls_number',
    'heae_acf_field_heae_controls_toggle',
    'heae_acf_save_action',
);
$missing = array_values( array_diff( $want, array_keys( $registered ) ) );
if ( $missing ) {
    WP_CLI::error( 'Field Editor native controls missing: ' . implode( ', ', $missing ) );
}
$button = $registered['heae_acf_save_action'];
// Elementor's runtime get_controls() descriptor contains the normalized
// ID/name/type, but omits presentation attributes such as text and event.
// Field Editor deliberately delegates click handling in editor-native.js;
// that actual handler and AJAX payload are exercised by dirty-save.cjs.
if ( ( $button['type'] ?? '' ) !== \Elementor\Controls_Manager::BUTTON ||
    ( $button['name'] ?? '' ) !== 'heae_acf_save_action' ) {
    WP_CLI::error( 'Native Save ACF Fields button is not registered correctly' );
}
if ( ( $registered['heae_acf_field_heae_controls_text']['type'] ?? '' ) !== \Elementor\Controls_Manager::TEXT ||
    ( $registered['heae_acf_field_heae_controls_number']['type'] ?? '' ) !== \Elementor\Controls_Manager::NUMBER ||
    ( $registered['heae_acf_field_heae_controls_toggle']['type'] ?? '' ) !== \Elementor\Controls_Manager::SWITCHER ) {
    WP_CLI::error( 'Elementor field control type mapping mismatch' );
}

wp_delete_post( $id, true );
echo 'WORDPRESS_NATIVE_ELEMENTOR_DOCUMENT_REGISTRATION=PASS' . PHP_EOL;
echo 'NATIVE_ACF_FIELD_TYPES_AND_SAVE_BUTTON=PASS' . PHP_EOL;
echo 'NOTE=Browser-level visual UI not certified by this test' . PHP_EOL;
