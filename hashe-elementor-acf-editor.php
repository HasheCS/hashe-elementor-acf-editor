<?php
/**
 * Plugin Name: Hashe Elementor ACF Editor
 * Description: Edit context-matched ACF field groups directly within Elementor Page Settings — no iframe or WordPress admin toolbar.
 * Version: 0.2.0-rc.1
 * Author: Hashe Computer Solutions (Pvt) Ltd
 * Author URI: https://www.hashe.com/
 * Plugin URI: https://github.com/HasheCS/hashe-elementor-acf-editor
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Text Domain: hashe-elementor-acf-editor
 */

defined( 'ABSPATH' ) || exit;

final class Hashe_Elementor_ACF_Editor {
    const VERSION = '0.2.0-rc.1';
    const TAB_ID = 'heae_acf';
    const NONCE_ACTION = 'heae_save_acf_';
    private static $instance;

    public static function instance() {
        if ( ! self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'plugins_loaded', array( $this, 'bootstrap' ), 30 );
        // Authenticated requests only; never expose the edit endpoint publicly.
        add_action( 'wp_ajax_heae_save_acf', array( $this, 'ajax_save' ) );
    }

    public function bootstrap() {
        if ( ! did_action( 'elementor/loaded' ) || ! class_exists( '\Elementor\Controls_Manager' ) ||
             ! function_exists( 'acf' ) || ! function_exists( 'acf_render_fields' ) ||
             ! function_exists( 'acf_get_field_groups' ) || ! function_exists( 'acf_save_post' ) ) {
            add_action( 'admin_notices', array( $this, 'dependency_notice' ) );
            return;
        }

        add_action( 'elementor/init', array( $this, 'register_tab' ) );
        add_action( 'elementor/documents/register_controls', array( $this, 'register_document_controls' ) );
        add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'enqueue_editor_scripts' ), 50 );
        add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'enqueue_editor_styles' ), 50 );
    }

    public function dependency_notice() {
        if ( current_user_can( 'activate_plugins' ) ) {
            echo '<div class="notice notice-error"><p>' .
                 esc_html__( 'Hashe Elementor ACF Editor requires Elementor and Advanced Custom Fields Pro.', 'hashe-elementor-acf-editor' ) .
                 '</p></div>';
        }
    }

    public function register_tab() {
        \Elementor\Controls_Manager::add_tab(
            self::TAB_ID,
            esc_html__( 'ACF', 'hashe-elementor-acf-editor' )
        );
    }

    private function get_post_id( $document ) {
        $post_id = method_exists( $document, 'get_main_id' ) ? absint( $document->get_main_id() ) : 0;
        if ( ! $post_id && method_exists( $document, 'get_id' ) ) {
            $post_id = absint( $document->get_id() );
        }
        return $post_id;
    }

    private function matching_groups( $post_id ) {
        if ( ! $post_id || ! function_exists( 'acf_get_field_groups' ) ) {
            return array();
        }
        $groups = acf_get_field_groups( array( 'post_id' => $post_id ) );
        return array_values( array_filter( (array) $groups, static function ( $group ) {
            return ! isset( $group['active'] ) || $group['active'];
        } ) );
    }

    private function matching_fields( $post_id ) {
        $result = array();
        foreach ( $this->matching_groups( $post_id ) as $group ) {
            $fields = acf_get_fields( $group );
            if ( ! is_array( $fields ) ) {
                continue;
            }
            foreach ( $fields as $field ) {
                if ( isset( $field['key'] ) && ! isset( $result[ $field['key'] ] ) ) {
                    $result[ $field['key'] ] = $field;
                }
            }
        }
        return $result;
    }

    public function register_document_controls( $document ) {
        if ( ! is_object( $document ) || ! method_exists( $document, 'start_controls_section' ) ||
             ! method_exists( $document, 'add_control' ) || ! method_exists( $document, 'end_controls_section' ) ) {
            return;
        }
        $post_id = $this->get_post_id( $document );
        if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $fields = $this->matching_fields( $post_id );
        $markup = '<div class="heae-native-panel" data-post-id="' . esc_attr( $post_id ) . '" data-heae-nonce="' . esc_attr( wp_create_nonce( self::NONCE_ACTION . $post_id ) ) . '" data-heae-version="' . esc_attr( self::VERSION ) . '">';
        if ( empty( $fields ) ) {
            $markup .= '<div class="heae-empty-state"><span class="eicon-database" aria-hidden="true"></span>';
            $markup .= '<p>' . esc_html__( 'No ACF fields are assigned to this page.', 'hashe-elementor-acf-editor' ) . '</p></div>';
        } else {
            $markup .= '<p class="heae-panel-intro">' .
                esc_html__( 'Edit the custom fields assigned to this page.', 'hashe-elementor-acf-editor' ) . '</p>';
            $markup .= '<form class="heae-acf-form acf-form" data-post-id="' . esc_attr( $post_id ) . '" autocomplete="off">';
            $markup .= '<div class="acf-fields -top">';
            ob_start();
            acf_render_fields( array_values( $fields ), $post_id, 'div', 'label' );
            $markup .= ob_get_clean();
            $markup .= '</div>';
            $markup .= '<div class="heae-form-actions">';
            $markup .= '<button type="button" class="elementor-button elementor-button-success heae-save">' .
                esc_html__( 'Save ACF Fields', 'hashe-elementor-acf-editor' ) . '</button>';
            $markup .= '<span class="heae-form-status" role="status" aria-live="polite"></span></div>';
            $markup .= '</form>';
        }
        $markup .= '</div>';

        $document->start_controls_section( 'heae_fields_section', array(
            'label' => esc_html__( 'ACF Fields', 'hashe-elementor-acf-editor' ),
            'tab' => self::TAB_ID,
        ) );
        $document->add_control( 'heae_fields_control', array(
            'type' => \Elementor\Controls_Manager::RAW_HTML,
            'raw' => $markup,
            'show_label' => false,
            'content_classes' => 'heae-editor-control',
        ) );
        $document->end_controls_section();
    }

    public function enqueue_editor_scripts() {
        if ( function_exists( 'acf_enqueue_scripts' ) ) {
            acf_enqueue_scripts();
        }
        wp_enqueue_media();
        wp_enqueue_script(
            'heae-acf-native-editor',
            plugins_url( 'assets/editor-native.js', __FILE__ ),
            array( 'jquery', 'acf-input' ),
            self::VERSION,
            true
        );
        wp_localize_script( 'heae-acf-native-editor', 'HEAE', array(
            'ajax' => admin_url( 'admin-ajax.php' ),
            'action' => 'heae_save_acf',
            'saving' => __( 'Saving…', 'hashe-elementor-acf-editor' ),
            'saved' => __( 'ACF fields saved.', 'hashe-elementor-acf-editor' ),
            'error' => __( 'Could not save ACF fields.', 'hashe-elementor-acf-editor' ),

        ) );
    }

    public function enqueue_editor_styles() {
        wp_enqueue_style(
            'heae-acf-native-editor',
            plugins_url( 'assets/editor-native.css', __FILE__ ),
            array(),
            self::VERSION
        );
    }

    public function ajax_save() {
        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
        if ( ! $post_id || ! get_post( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Editing this page is not permitted.', 'hashe-elementor-acf-editor' ) ), 403 );
        }
        check_ajax_referer( self::NONCE_ACTION . $post_id, 'nonce' );
        if ( ! function_exists( 'acf_save_post' ) ) {
            wp_send_json_error( array( 'message' => __( 'ACF is unavailable.', 'hashe-elementor-acf-editor' ) ), 503 );
        }

        $allowed = $this->matching_fields( $post_id );
        // Do not allow posting arbitrary ACF field keys or cross-post values.
        $raw = isset( $_POST['acf'] ) && is_array( $_POST['acf'] )
            ? wp_unslash( $_POST['acf'] )
            : array();
        $payload = array();
        foreach ( $allowed as $field_key => $field ) {
            if ( array_key_exists( $field_key, $raw ) ) {
                $payload[ $field_key ] = $raw[ $field_key ];
            }
        }
        if ( empty( $payload ) ) {
            wp_send_json_error( array( 'message' => __( 'No ACF field values were received.', 'hashe-elementor-acf-editor' ) ), 400 );
        }

        if ( function_exists( 'acf_reset_validation_errors' ) ) {
            acf_reset_validation_errors();
        }
        if ( function_exists( 'acf_validate_values' ) ) {
            acf_validate_values( $payload, 'acf' );
        }
        $errors = function_exists( 'acf_get_validation_errors' ) ? acf_get_validation_errors() : false;
        if ( $errors ) {
            wp_send_json_error( array( 'message' => __( 'Please correct the highlighted fields.', 'hashe-elementor-acf-editor' ), 'errors' => $errors ), 422 );
        }

        // acf_save_post invokes ACF's official save hooks and field handlers
        // (including repeaters, media fields, custom field types).
        acf_save_post( $post_id, $payload );
        wp_send_json_success( array(
            'message' => __( 'ACF fields saved.', 'hashe-elementor-acf-editor' ),
            'post_id' => $post_id,
            'updated_fields' => count( $payload ),
        ) );
    }
}

Hashe_Elementor_ACF_Editor::instance();
