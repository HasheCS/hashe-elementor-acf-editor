<?php
/**
 * Mandatory optional installation reporting for WordPress.org distribution.
 * The directory's own update provider is the only executable update source.
 * No optional network traffic or installation profile before express consent.
 */
defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/hashe-core/v1/consent-client.php';

add_action( 'plugins_loaded', static function () {
    $main_file = dirname( __DIR__ ) . '/hashe-field-editor.php';
    $consent = new \Hashe\FieldEditor\PluginTools\V1\ConsentClient(
        'hashe-field-editor',
        plugin_basename( $main_file )
    );
    $consent->register();
}, 40 );

register_activation_hook(
    dirname( __DIR__ ) . '/hashe-field-editor.php',
    array( '\Hashe\FieldEditor\PluginTools\V1\ConsentClient', 'on_activation' )
);
register_deactivation_hook(
    dirname( __DIR__ ) . '/hashe-field-editor.php',
    array( '\Hashe\FieldEditor\PluginTools\V1\ConsentClient', 'on_deactivation' )
);
register_uninstall_hook(
    dirname( __DIR__ ) . '/hashe-field-editor.php',
    array( '\Hashe\FieldEditor\PluginTools\V1\ConsentClient', 'on_uninstall' )
);
