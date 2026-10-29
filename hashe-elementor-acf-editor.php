<?php
/**
 * Plugin Name: Hashe Elementor ACF Editor
 * Description: Edit context-matched ACF field groups directly within Elementor Page Settings — no iframe or WordPress admin toolbar.
 * Version: 0.3.1-rc.1
 * Author: Hashe Computer Solutions
 * Author URI: https://hashe.com/
 * License: GPL-2.0-or-later
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Text Domain: hashe-elementor-acf-editor
 */

defined( 'ABSPATH' ) || exit;

final class Hashe_Elementor_ACF_Editor {
    const VERSION = '0.3.1-rc.1';
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
        // Elementor renders this label using its own documented tab template
        // (triple-brace title markup). Register the official ACF icon WITH
        // the label instead of adding it after Elementor mounts the sidebar.
        $logo = function_exists( 'acf_get_setting' )
            ? set_url_scheme(
                trailingslashit( acf_get_setting( 'url' ) ) . 'assets/images/acf-logo.svg',
                wp_parse_url( admin_url(), PHP_URL_SCHEME )
            )
            : '';
        $label = esc_html__( 'ACF', 'hashe-elementor-acf-editor' );
        if ( $logo ) {
            $label = '<img class="heae-acf-tab-logo" src="' . esc_url( $logo ) .
                '" width="18" height="18" alt="" aria-hidden="true" />' . $label;
        }
        \Elementor\Controls_Manager::add_tab( self::TAB_ID, $label );
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
        $groups = $this->matching_groups( $post_id );
        $markup = '<div class="heae-native-panel" data-post-id="' . esc_attr( $post_id ) . '" data-heae-nonce="' . esc_attr( wp_create_nonce( self::NONCE_ACTION . $post_id ) ) . '" data-heae-version="' . esc_attr( self::VERSION ) . '">';
        if ( empty( $fields ) ) {
            $markup .= '<div class="heae-empty-state"><span class="eicon-database" aria-hidden="true"></span>';
            $markup .= '<p>' . esc_html__( 'No ACF fields are assigned to this page.', 'hashe-elementor-acf-editor' ) . '</p></div>';
        } else {
            $markup .= '<p class="heae-panel-intro">' .
                esc_html__( 'Edit the custom fields assigned to this page.', 'hashe-elementor-acf-editor' ) . '</p>';
            $markup .= '<form class="heae-acf-form acf-form" data-post-id="' . esc_attr( $post_id ) . '" autocomplete="off">';
            foreach ( $groups as $group ) {
                $group_fields = acf_get_fields( $group );
                if ( ! is_array( $group_fields ) || ! $group_fields ) {
                    continue;
                }
                // ACF's Table layout is too wide for Elementor's narrow
                // editor sidebar. Change only the rendered copy; do not
                // modify the saved ACF group or repeater configuration.
                foreach ( $group_fields as &$field ) {
                    if ( isset( $field['type'] ) && 'repeater' === $field['type'] ) {
                        $field['layout'] = 'block';
                    }
                }
                unset( $field );
                $markup .= '<section class="heae-field-group">';
                $markup .= '<h4 class="heae-field-group-title">' . esc_html( $group['title'] ) . '</h4>';
                $markup .= '<div class="acf-fields -top">';
                ob_start();
                acf_render_fields( $group_fields, $post_id, 'div', 'label' );
                $markup .= ob_get_clean();
                $markup .= '</div></section>';
            }
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

    /**
     * Elementor 4 resets the global WP_Scripts and WP_Styles registries
     * before its editor enqueue hooks run. ACF registers its assets on init,
     * so the handles silently disappear unless we re-register them here.
     * Restore ACF's own core asset registry, and the ACF Pro handlers needed
     * for native repeater, gallery and flexible-content controls.
     */
    private function register_acf_editor_assets() {
        if ( ! function_exists( 'acf_get_instance' ) || ! class_exists( 'ACF_Assets' ) ||
             ! function_exists( 'acf_get_url' ) ) {
            return;
        }
        if ( ! wp_script_is( 'acf-input', 'registered' ) || ! wp_style_is( 'acf-input', 'registered' ) ) {
            acf_get_instance( 'ACF_Assets' )->register_scripts();
        }

        if ( class_exists( 'acf_pro' ) ) {
            $suffix  = defined( 'ACF_DEVELOPMENT_MODE' ) && ACF_DEVELOPMENT_MODE ? '' : '.min';
            $version = acf_get_setting( 'version' );
            if ( ! wp_script_is( 'acf-pro-input', 'registered' ) ) {
                wp_register_script(
                    'acf-pro-input',
                    acf_get_url( 'assets/build/js/pro/acf-pro-input' . $suffix . '.js' ),
                    array( 'acf-input' ),
                    $version,
                    true
                );
            }
            if ( ! wp_style_is( 'acf-pro-input', 'registered' ) ) {
                wp_register_style(
                    'acf-pro-input',
                    acf_get_url( 'assets/build/css/pro/acf-pro-input' . $suffix . '.css' ),
                    array( 'acf-input' ),
                    $version
                );
            }
        }
    }

    public function enqueue_editor_scripts() {
        $this->register_acf_editor_assets();
        if ( function_exists( 'acf_enqueue_scripts' ) ) {
            acf_enqueue_scripts( array( 'input' => true, 'uploader' => true ) );
            // The normal ACF admin_enqueue_scripts event may already have
            // passed before Elementor reset WordPress's asset registry.
            // Reapply the core/Pro queue and localization after that reset.
            acf_get_instance( 'ACF_Assets' )->enqueue_scripts();
        }
        // Elementor Page Settings is not ACF's normal edit screen, so
        // its 'acf/input/admin_enqueue_scripts' hook does not run.
        // Explicitly load Pro's repeater/flexible-content field handlers.
        if ( wp_script_is( 'acf-pro-input', 'registered' ) ) {
            wp_enqueue_script( 'acf-pro-input' );
        }
        wp_enqueue_media();
        wp_enqueue_script(
            'heae-acf-native-editor',
            set_url_scheme( plugins_url( 'assets/editor-native.js', __FILE__ ), 'https' ),
            array( 'jquery', 'acf-input', 'acf-pro-input' ),
            self::VERSION,
            true
        );
        $acf_logo_url = function_exists( 'acf_get_setting' )
            ? set_url_scheme( trailingslashit( acf_get_setting( 'url' ) ) . 'assets/images/acf-logo.svg', wp_parse_url( admin_url(), PHP_URL_SCHEME ) )
            : '';
        wp_localize_script( 'heae-acf-native-editor', 'HEAE', array(
            'acfLogo' => esc_url( $acf_logo_url ),
            'ajax' => admin_url( 'admin-ajax.php' ),
            'action' => 'heae_save_acf',
            'saving' => __( 'Saving…', 'hashe-elementor-acf-editor' ),
            'saved' => __( 'ACF fields saved.', 'hashe-elementor-acf-editor' ),
            'error' => __( 'Could not save ACF fields.', 'hashe-elementor-acf-editor' ),

        ) );
    }

    public function enqueue_editor_styles() {
        $this->register_acf_editor_assets();
        if ( wp_style_is( 'acf-input', 'registered' ) ) {
            wp_enqueue_style( 'acf-input' );
        }
        if ( wp_style_is( 'acf-pro-input', 'registered' ) ) {
            wp_enqueue_style( 'acf-pro-input' );
        }
        wp_enqueue_style(
            'heae-acf-native-editor',
            set_url_scheme( plugins_url( 'assets/editor-native.css', __FILE__ ), 'https' ),
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
