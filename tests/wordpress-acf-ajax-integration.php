<?php
/**
 * Disposable WordPress + Elementor + ACF Free AJAX acceptance.
 *
 * Run with WP-CLI eval-file in separate processes in a single ephemeral site.
 * Uses real core nonces, edit_post capabilities, ACF validation and ACF saves.
 * The save/denial stages exit via wp_send_json; their output is verified by
 * tests/check-wordpress-acf-ajax.py in the GitHub Actions workflow.
 *
 * No browser-level Elementor UX or ACF Pro certification is implied.
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    fwrite( STDERR, "ERROR: disposable WP-CLI only\n" );
    exit( 2 );
}

$stage  = getenv( 'HASHE_QA_AJAX_STAGE' );
$option = 'heae_qa_ajax_post_id';
$keys = array(
    'headline' => 'field_heae_real_qa_headline',
    'email'    => 'field_heae_real_qa_email',
    'switch'   => 'field_heae_real_qa_switch',
    'number'   => 'field_heae_real_qa_number',
);
$group = array(
    'key' => 'group_heae_real_ajax_qa',
    'title' => 'Hashe real AJAX QA group',
    'fields' => array(
        array(
            'key' => $keys['headline'], 'name' => 'heae_qa_headline',
            'label' => 'QA headline', 'type' => 'text',
            'required' => 1,
        ),
        array(
            'key' => $keys['email'], 'name' => 'heae_qa_email',
            'label' => 'QA email', 'type' => 'email',
        ),
        array(
            'key' => $keys['switch'], 'name' => 'heae_qa_switch',
            'label' => 'QA switch', 'type' => 'true_false',
        ),
        array(
            'key' => $keys['number'], 'name' => 'heae_qa_number',
            'label' => 'QA number', 'type' => 'number',
        ),
    ),
    'location' => array( array( array(
        'param' => 'post_type', 'operator' => '==', 'value' => 'page',
    ) ) ),
);
acf_add_local_field_group( $group );

function heae_qa_assert( $truth, $message ) {
    if ( ! $truth ) {
        WP_CLI::error( 'HEAE_AJAX_QA_ERROR=' . $message );
    }
}
function heae_qa_stored( $id, $keys ) {
    return array(
        'headline' => get_field( $keys['headline'], $id, false ),
        'email'    => get_field( $keys['email'], $id, false ),
        'switch'   => get_field( $keys['switch'], $id, false ),
        'number'   => get_field( $keys['number'], $id, false ),
    );
}
if ( 'prepare' === $stage ) {
    wp_set_current_user( 1 );
    heae_qa_assert( current_user_can( 'edit_posts' ), 'No administrator capability' );
    $id = wp_insert_post( array(
        'post_title' => 'Hashe AJAX acceptance page',
        'post_type' => 'page',
        'post_status' => 'draft',
    ), true );
    heae_qa_assert( ! is_wp_error( $id ) && $id > 0, 'Could not create isolated page' );
    heae_qa_assert( update_field( $keys['headline'], 'Original headline', $id ), 'Initial headline write' );
    heae_qa_assert( update_field( $keys['email'], 'initial@example.invalid', $id ), 'Initial email write' );
    update_field( $keys['switch'], 0, $id );
    update_field( $keys['number'], 12, $id );
    update_post_meta( $id, '_elementor_page_settings', array(
        'page_title' => 'Keep Elementor title',
        'custom_css' => 'keep-css',
        'heae_acf_' . $keys['headline'] => 'obsolete duplicate',
    ) );
    update_option( $option, $id, false );
    echo 'HEAE_AJAX_PREPARE=PASS post_id=' . $id . PHP_EOL;
    return;
}
$id = absint( get_option( $option, 0 ) );
heae_qa_assert( $id && get_post( $id ), 'Prepare stage was not completed' );
$initial = 'Original headline';
if ( 'verify-original' === $stage ) {
    $current = heae_qa_stored( $id, $keys );
    heae_qa_assert( $current['headline'] === $initial, 'Denied request changed headline' );
    heae_qa_assert( $current['email'] === 'initial@example.invalid', 'Denied request changed email' );
    heae_qa_assert( (int) $current['switch'] === 0, 'Denied request changed switch' );
    heae_qa_assert( (int) $current['number'] === 12, 'Denied request changed number' );
    echo "HEAE_AJAX_DENIALS_PRESERVED_DATA=PASS\n";
    return;
}
if ( 'verify-saved' === $stage ) {
    $current = heae_qa_stored( $id, $keys );
    heae_qa_assert( $current['headline'] === 'Edited by Elementor', 'Headline was not persisted' );
    heae_qa_assert( $current['email'] === 'qa@example.invalid', 'Email was not persisted' );
    heae_qa_assert( (int) $current['switch'] === 1, 'Boolean was not persisted' );
    heae_qa_assert( (int) $current['number'] === 42, 'Number was not persisted' );
    heae_qa_assert( get_post_meta( $id, 'field_heae_real_qa_rogue', true ) === '', 'Rogue field was saved' );
    $settings = get_post_meta( $id, '_elementor_page_settings', true );
    heae_qa_assert( is_array( $settings ) && $settings['page_title'] === 'Keep Elementor title'
        && $settings['custom_css'] === 'keep-css', 'Elementor unrelated settings were changed' );
    heae_qa_assert( ! array_key_exists( 'heae_acf_' . $keys['headline'], $settings ),
        'Stale Elementor mirror was not removed' );
    echo "HEAE_AJAX_REAL_ACF_SAVE=PASS\n";
    echo "HEAE_AJAX_FIELD_ALLOWLIST=PASS\n";
    echo "HEAE_AJAX_ELEMENTOR_SETTINGS_PRESERVED=PASS\n";
    return;
}
if ( 'cleanup' === $stage ) {
    wp_delete_post( $id, true );
    delete_option( $option );
    echo "HEAE_AJAX_CLEANUP=PASS\n";
    return;
}
if ( ! in_array( $stage, array(
    'forbidden', 'bad-nonce', 'rogue-only', 'invalid-required', 'valid-save',
), true ) ) {
    WP_CLI::error( 'Unsupported QA stage' );
}

wp_set_current_user( 'forbidden' === $stage ? 0 : 1 );
$nonce = 'bad-nonce' === $stage
    ? 'deliberately-invalid-nonce'
    : wp_create_nonce( 'heae_save_acf_' . $id );
$fields = array( $keys['headline'] => 'Edited by Elementor' );
if ( 'rogue-only' === $stage ) {
    $fields = array( 'field_heae_real_qa_rogue' => 'should never persist' );
}
if ( 'invalid-required' === $stage ) {
    $fields = array( $keys['headline'] => '' );
}
if ( 'valid-save' === $stage ) {
    $fields = array(
        $keys['headline'] => 'Edited by Elementor',
        $keys['email'] => 'qa@example.invalid',
        $keys['switch'] => 1,
        $keys['number'] => 42,
        'field_heae_real_qa_rogue' => 'should never persist',
    );
}
$_POST = array(
    'action' => 'heae_save_acf',
    'post_id' => (string) $id,
    'nonce' => $nonce,
    'acf' => $fields,
);
$_REQUEST = $_POST;
// Dispatch through the same registered action as admin-ajax.php. This is
// intentionally PHP request integration, not a browser/cookie E2E test.
do_action( 'wp_ajax_heae_save_acf' );
WP_CLI::error( 'The AJAX handler returned without JSON termination' );
