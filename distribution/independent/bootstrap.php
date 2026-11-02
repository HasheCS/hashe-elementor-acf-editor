<?php
/**
 * Hashe independent distribution bootstrap.
 * This file is deliberately omitted from WordPress.org submission ZIPs.
 * This plugin's directory-managed counterpart MUST NOT load a remote updater.
 */
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/hashe-core/v1/update-client.php';
require_once __DIR__ . '/hashe-core/v1/consent-client.php';

add_action( 'plugins_loaded', static function () {
    $main_file = dirname( __DIR__ ) . '/hashe-elementor-acf-editor.php';
    $basename  = plugin_basename( $main_file );
    $update = new \Hashe\PluginTools\V1\UpdateClient(
        'hashe-elementor-acf-editor',
        $basename,
        'https://plugins.hashe.com/hashe-elementor-acf-editor/',
        'https://plugins.hashe.com/api/v1/plugins/hashe-elementor-acf-editor/update'
    );
    $update->register();
    $consent = new \Hashe\PluginTools\V1\ConsentClient(
        'hashe-elementor-acf-editor',
        $basename
    );
    $consent->register();
}, 40 );

register_activation_hook(
    dirname( __DIR__ ) . '/hashe-elementor-acf-editor.php',
    array( '\\Hashe\\PluginTools\\V1\\ConsentClient', 'on_activation' )
);
register_deactivation_hook(
    dirname( __DIR__ ) . '/hashe-elementor-acf-editor.php',
    array( '\\Hashe\\PluginTools\\V1\\ConsentClient', 'on_deactivation' )
);
register_uninstall_hook(
    dirname( __DIR__ ) . '/hashe-elementor-acf-editor.php',
    array( '\\Hashe\\PluginTools\\V1\\ConsentClient', 'on_uninstall' )
);
