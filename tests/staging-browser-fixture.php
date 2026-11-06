<?php
/**
 * Explicitly authorized, isolated browser acceptance fixture for Solutions QA.
 *
 * WP-CLI only. Snapshot/verify/restore ONLY one draft test post's ACF text
 * value and Elementor page-settings meta. No production/site-wide changes.
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ||
    getenv( 'HASHE_FIELD_BROWSER_QA_AUTHORIZED' ) !== '1' ) {
    fwrite( STDERR, "ERROR: Browser QA explicitly authorized staging only\n" );
    exit( 2 );
}
$home = wp_parse_url( home_url() );
if ( ! is_array( $home ) || ( $home['scheme'] ?? '' ) !== 'https' ||
    ( $home['host'] ?? '' ) !== 'dev.sasseagency.com' ||
    rtrim( $home['path'] ?? '', '/' ) !== '/solutions' ) {
    WP_CLI::error( 'Refusing non-Solutions staging site' );
}
$id = 654;
$post = get_post( $id );
if ( ! $post || $post->post_type !== 'page' || $post->post_status !== 'draft' ) {
    WP_CLI::error( 'QA page 654 is not the protected draft' );
}
$key = 'heae_matrix_text';
$settings_key = '_elementor_page_settings';
$mode = getenv( 'HASHE_FIELD_BROWSER_QA_MODE' );
$file = getenv( 'HASHE_FIELD_BROWSER_QA_SNAPSHOT' );
$private = '/bigdisk/home/devsasseagency/.hashe-field-editor-qa';
$parent = is_string( $file ) ? realpath( dirname( $file ) ) : false;
if ( ! $parent || 0 !== strpos( $parent . '/', $private . '/' ) ||
    ! is_writable( $parent ) ) {
    WP_CLI::error( 'Snapshot must be in the private QA backup area' );
}
if ( 'snapshot' === $mode ) {
    if ( file_exists( $file ) ) {
        WP_CLI::error( 'Refusing to overwrite an existing QA snapshot' );
    }
    $state = array(
        'id' => $id,
        'text_present' => metadata_exists( 'post', $id, $key ),
        'text' => get_post_meta( $id, $key, true ),
        'settings_present' => metadata_exists( 'post', $id, $settings_key ),
        'settings' => get_post_meta( $id, $settings_key, true ),
    );
    $bytes = serialize( $state );
    if ( strlen( $bytes ) < 20 || strlen( $bytes ) > 1000000 ||
        file_put_contents( $file, $bytes, LOCK_EX ) !== strlen( $bytes ) ||
        ! chmod( $file, 0600 ) ) {
        WP_CLI::error( 'Could not create private immutable state snapshot' );
    }
    echo 'BROWSER_QA_SNAPSHOT_SHA256=' . hash_file( 'sha256', $file ) . PHP_EOL;
    echo 'BROWSER_QA_SOURCE_VALUE=' . $state['text'] . PHP_EOL;
    echo "BROWSER_QA_SNAPSHOT=PASS\n";
    return;
}
if ( ! is_file( $file ) || filesize( $file ) > 1000000 ) {
    WP_CLI::error( 'Missing or oversized private state snapshot' );
}
$state = unserialize( file_get_contents( $file ), array( 'allowed_classes' => false ) );
if ( ! is_array( $state ) || ( $state['id'] ?? null ) !== $id ||
    ! array_key_exists( 'text', $state ) ||
    ! array_key_exists( 'settings', $state ) ) {
    WP_CLI::error( 'Private QA snapshot invalid' );
}
if ( 'verify-edited' === $mode ) {
    $expected = getenv( 'HASHE_FIELD_BROWSER_QA_EDITED' );
    if ( ! is_string( $expected ) || $expected === '' ||
        get_post_meta( $id, $key, true ) !== $expected ) {
        WP_CLI::error( 'Browser edit was not saved to real ACF post meta' );
    }
    echo "BROWSER_QA_REAL_ACF_TEXT_SAVE=PASS\n";
    return;
}
if ( 'restore' === $mode ) {
    if ( ! empty( $state['text_present'] ) ) {
        update_post_meta( $id, $key, $state['text'] );
    } else {
        delete_post_meta( $id, $key );
    }
    if ( ! empty( $state['settings_present'] ) ) {
        update_post_meta( $id, $settings_key, $state['settings'] );
    } else {
        delete_post_meta( $id, $settings_key );
    }
} elseif ( 'verify-restored' !== $mode ) {
    WP_CLI::error( 'Unsupported QA mode' );
}
$text_same = metadata_exists( 'post', $id, $key ) === (bool) $state['text_present']
    && get_post_meta( $id, $key, true ) === $state['text'];
$settings_same = metadata_exists( 'post', $id, $settings_key ) === (bool) $state['settings_present']
    && get_post_meta( $id, $settings_key, true ) === $state['settings'];
if ( ! $text_same || ! $settings_same ) {
    WP_CLI::error( 'One or more QA draft values failed targeted restoration' );
}
echo "BROWSER_QA_DRAFT_META_RESTORED=PASS\n";
