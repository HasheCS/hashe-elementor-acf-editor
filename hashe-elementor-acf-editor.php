<?php
/**
 * Plugin Name: Hashe Elementor ACF Editor
 * Description: Native Elementor Page Settings controls backed by ACF fields. No iframe or embedded ACF form.
 * Version: 0.5.1
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
require_once __DIR__ . '/includes/extended-fields.php';

final class Hashe_Elementor_ACF_Editor {
    use Hashe_ACF_Extended_Controls;
    const VERSION = '0.5.1';
    const TAB_ID = 'heae_acf';
    const CONTROL_PREFIX = 'heae_acf_';
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
        add_action( 'wp_ajax_heae_save_acf', array( $this, 'ajax_save' ) );
    }

    public function bootstrap() {
        if ( ! did_action( 'elementor/loaded' )
            || ! class_exists( '\Elementor\Controls_Manager' )
            || ! function_exists( 'acf_get_field_groups' )
            || ! function_exists( 'acf_save_post' ) ) {
            add_action( 'admin_notices', array( $this, 'dependency_notice' ) );
            return;
        }
        add_action( 'elementor/init', array( $this, 'register_tab' ) );
        add_action( 'elementor/documents/register_controls', array( $this, 'register_document_controls' ) );
        add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'enqueue_editor_scripts' ), 50 );
        add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'enqueue_editor_styles' ), 50 );
        // ACF is the sole source of truth. Elementor must not save a second,
        // stale copy of the custom-field values in its Page Settings metadata.
        add_filter( 'elementor/document/save/data', array( $this, 'filter_elementor_document_save' ), 20, 2 );
        add_action( 'elementor/document/after_save', array( $this, 'after_elementor_document_save' ), 20, 2 );
    }

    public function dependency_notice() {
        if ( current_user_can( 'activate_plugins' ) ) {
            echo '<div class="notice notice-error"><p>' .
                esc_html__( 'Hashe Elementor ACF Editor requires Elementor and Advanced Custom Fields (ACF or ACF Pro).', 'hashe-elementor-acf-editor' ) .
                '</p></div>';
        }
    }

    public function register_tab() {
        $logo = function_exists( 'acf_get_setting' )
            ? set_url_scheme( trailingslashit( acf_get_setting( 'url' ) ) . 'assets/images/acf-logo.svg', wp_parse_url( admin_url(), PHP_URL_SCHEME ) )
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
        if ( ! $post_id ) {
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
            foreach ( (array) acf_get_fields( $group ) as $field ) {
                if ( ! empty( $field['key'] ) ) {
                    $result[ $field['key'] ] = $field;
                }
            }
        }
        return $result;
    }

    private function get_value( $field, $post_id ) {
        $value = get_field( $field['key'], $post_id, false );
        if ( false === $value || null === $value ) {
            return isset( $field['default_value'] ) ? $field['default_value'] : '';
        }
        return $value;
    }

    private function make_control( $field, $post_id, $inside_repeater = false ) {
        $type = isset( $field['type'] ) ? $field['type'] : '';
        $id = self::CONTROL_PREFIX . $field['key'];
        $label = isset( $field['label'] ) && $field['label'] !== '' ? $field['label'] : $field['name'];
        $args = array(
            'label' => $label,
            'description' => isset( $field['instructions'] ) ? wp_strip_all_tags( $field['instructions'] ) : '',
            'label_block' => true,
        );
        if ( ! $inside_repeater ) {
            $value = $this->get_value( $field, $post_id );
        } else {
            $value = isset( $field['default_value'] ) ? $field['default_value'] : '';
        }

        switch ( $type ) {
            case 'text':
            case 'oembed':
            case 'email':
            case 'url':
            case 'password':
                $args['type'] = \Elementor\Controls_Manager::TEXT;
                if ( 'email' === $type || 'url' === $type || 'password' === $type ) {
                    $args['input_type'] = $type;
                }
                $args['default'] = is_scalar( $value ) ? (string) $value : '';
                break;
            case 'textarea':
                $args['type'] = \Elementor\Controls_Manager::TEXTAREA;
                $args['rows'] = isset( $field['rows'] ) ? max( 2, min( 8, (int) $field['rows'] ) ) : 4;
                $args['default'] = is_scalar( $value ) ? (string) $value : '';
                break;
            case 'wysiwyg':
                $args['type'] = \Elementor\Controls_Manager::WYSIWYG;
                $args['default'] = is_scalar( $value ) ? (string) $value : '';
                break;
            case 'number':
            case 'range':
                $args['type'] = \Elementor\Controls_Manager::NUMBER;
                $args['default'] = is_numeric( $value ) ? $value : 0;
                foreach ( array( 'min', 'max', 'step' ) as $limit ) {
                    if ( isset( $field[ $limit ] ) && is_numeric( $field[ $limit ] ) ) {
                        $args[ $limit ] = (float) $field[ $limit ];
                    }
                }
                break;
            case 'select':
            case 'checkbox':
            case 'radio':
            case 'button_group':
                $multi = 'checkbox' === $type || ( 'select' === $type && ! empty( $field['multiple'] ) );
                $args['type'] = $multi ? \Elementor\Controls_Manager::SELECT2 : \Elementor\Controls_Manager::SELECT;
                $args['options'] = isset( $field['choices'] ) && is_array( $field['choices'] ) ? $field['choices'] : array();
                if ( $multi ) {
                    $args['multiple'] = true;
                    $args['default'] = is_array( $value ) ? array_values( $value ) : ( '' === $value ? array() : array( (string) $value ) );
                } else {
                    $args['default'] = is_scalar( $value ) ? (string) $value : '';
                }
                break;
            case 'true_false':
                $args['type'] = \Elementor\Controls_Manager::SWITCHER;
                $args['return_value'] = 'yes';
                $args['default'] = $value ? 'yes' : '';
                break;
            case 'image':
            case 'file':
                $args['type'] = \Elementor\Controls_Manager::MEDIA;
                $attachment_id = absint( $value );
                $args['default'] = array(
                    'id' => $attachment_id,
                    'url' => $attachment_id ? wp_get_attachment_url( $attachment_id ) : '',
                );
                $args['media_types'] = 'image' === $type ? array( 'image' ) : array( 'image', 'video', 'application' );
                break;
            case 'gallery':
                // ACF Pro Gallery uses an ordered list of attachment IDs.
                // Elementor's own Repeater + Media control provides the
                // native add / remove / reorder behavior without an ACF form.
                if ( $inside_repeater ) {
                    return null;
                }
                $gallery_repeater = new \Elementor\Repeater();
                $gallery_repeater->add_control( 'heae_gallery_image', array(
                    'type' => \Elementor\Controls_Manager::MEDIA,
                    'label' => __( 'Gallery Image', 'hashe-elementor-acf-editor' ),
                    'media_types' => array( 'image' ),
                    'default' => array( 'id' => '', 'url' => '' ),
                ) );
                $args['type'] = \Elementor\Controls_Manager::REPEATER;
                $args['fields'] = $gallery_repeater->get_controls();
                $args['title_field'] = __( 'Gallery Image', 'hashe-elementor-acf-editor' );
                $args['button_text'] = __( 'Add Gallery Image', 'hashe-elementor-acf-editor' );
                $args['prevent_empty'] = false;
                $args['item_actions'] = array( 'add' => true, 'duplicate' => true, 'remove' => true, 'sort' => true );
                $args['default'] = array();
                foreach ( (array) $value as $index => $media ) {
                    $attachment_id = is_array( $media ) ? absint( $media['ID'] ?? $media['id'] ?? 0 ) : absint( $media );
                    if ( ! $attachment_id ) {
                        continue;
                    }
                    $args['default'][] = array(
                        '_id' => substr( md5( $field['key'] . ':' . $index ), 0, 7 ),
                        'heae_gallery_image' => array(
                            'id' => $attachment_id,
                            'url' => wp_get_attachment_url( $attachment_id ),
                        ),
                    );
                }
                break;
            case 'time_picker':
                $args['type'] = \Elementor\Controls_Manager::DATE_TIME;
                $args['picker_options'] = array(
                    'enableTime' => true,
                    'noCalendar' => true,
                    'dateFormat' => 'H:i',
                    'time_24hr' => true,
                );
                $args['default'] = is_scalar( $value ) ? substr( (string) $value, 0, 5 ) : '';
                break;
            case 'color_picker':
                $args['type'] = \Elementor\Controls_Manager::COLOR;
                $args['default'] = is_scalar( $value ) ? (string) $value : '';
                break;
            case 'date_picker':
            case 'date_time_picker':
                $args['type'] = \Elementor\Controls_Manager::DATE_TIME;
                $args['picker_options'] = array( 'enableTime' => 'date_time_picker' === $type );
                $args['default'] = is_scalar( $value ) ? (string) $value : '';
                break;
            case 'repeater':
                if ( $inside_repeater ) {
                    return null; // Elementor does not support recursively nested repeaters.
                }
                $repeater = new \Elementor\Repeater();
                $names = array();
                foreach ( (array) ( $field['sub_fields'] ?? array() ) as $sub ) {
                    if ( empty( $sub['key'] ) ) {
                        continue;
                    }
                    $sub_control = $this->make_control( $sub, $post_id, true );
                    if ( null === $sub_control ) {
                        return null; // No partially editable repeater: avoid silent data loss.
                    }
                    $repeater->add_control( $sub_control[0], $sub_control[1] );
                    $names[] = $sub_control[0];
                }
                if ( empty( $names ) ) {
                    return null;
                }
                $args['type'] = \Elementor\Controls_Manager::REPEATER;
                $args['fields'] = $repeater->get_controls();
                $args['title_field'] = '{{{ ' . $names[0] . ' }}}';
                /* translators: %s: Label of the ACF repeater field. */
                $args['button_text'] = sprintf( __( 'Add %s', 'hashe-elementor-acf-editor' ), $label );
                $args['prevent_empty'] = false;
                $args['item_actions'] = array( 'add' => true, 'duplicate' => true, 'remove' => true, 'sort' => true );
                $args['default'] = array();
                foreach ( (array) $value as $index => $row ) {
                    if ( ! is_array( $row ) ) {
                        continue;
                    }
                    $item = array( '_id' => substr( md5( $field['key'] . ':' . $index ), 0, 7 ) );
                    foreach ( (array) $field['sub_fields'] as $sub ) {
                        $sub_id = self::CONTROL_PREFIX . $sub['key'];
                        $raw = isset( $row[ $sub['key'] ] ) ? $row[ $sub['key'] ] : ( $row[ $sub['name'] ] ?? '' );
                        if ( 'image' === $sub['type'] || 'file' === $sub['type'] ) {
                            $attachment_id = absint( $raw );
                            $raw = array( 'id' => $attachment_id, 'url' => $attachment_id ? wp_get_attachment_url( $attachment_id ) : '' );
                        } elseif ( 'true_false' === $sub['type'] ) {
                            $raw = $raw ? 'yes' : '';
                        }
                        $item[ $sub_id ] = $raw;
                    }
                    $args['default'][] = $item;
                }
                break;
            default:
                return $this->make_extended_control( $field, $post_id, $inside_repeater );
        }
        return array( $id, $args );
    }

    public function register_document_controls( $document ) {
        if ( ! is_object( $document ) || ! method_exists( $document, 'start_controls_section' ) ) {
            return;
        }
        $post_id = $this->get_post_id( $document );
        if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        $groups = $this->matching_groups( $post_id );
        if ( ! $groups ) {
            $document->start_controls_section( 'heae_empty_section', array(
                'label' => __( 'ACF Fields', 'hashe-elementor-acf-editor' ),
                'tab' => self::TAB_ID,
            ) );
            $document->add_control( 'heae_empty_notice', array(
                'type' => \Elementor\Controls_Manager::ALERT,
                'alert_type' => 'info',
                'content' => __( 'No ACF fields are assigned to this page.', 'hashe-elementor-acf-editor' ),
            ) );
            $document->end_controls_section();
            return;
        }
        // Only groups with actual ACF fields get a Page Settings section.
        // Keep the Save button in the final visible group so it is immediately
        // below the field controls, never in its own collapsed accordion.
        $renderable_groups = array();
        foreach ( $groups as $group ) {
            $fields = acf_get_fields( $group );
            if ( $fields ) {
                $renderable_groups[] = array( 'group' => $group, 'fields' => $fields );
            }
        }
        $supported = 0;
        $last_group_index = count( $renderable_groups ) - 1;
        foreach ( $renderable_groups as $index => $entry ) {
            $group = $entry['group'];
            $fields = $entry['fields'];
            $document->start_controls_section( 'heae_section_' . sanitize_key( $group['key'] ), array(
                'label' => $group['title'],
                'tab' => self::TAB_ID,
            ) );
            foreach ( $fields as $field ) {
                if ( empty( $field['key'] ) ) {
                    continue;
                }
                $control = $this->make_control( $field, $post_id );
                if ( null === $control ) {
                    $document->add_control( 'heae_unsupported_' . sanitize_key( $field['key'] ), array(
                        'type' => \Elementor\Controls_Manager::ALERT,
                        'alert_type' => 'warning',
                        'content' => sprintf(
                            /* translators: %s: Label of an unsupported ACF field. */
                            __( '%s uses an ACF field type not yet mapped to Elementor controls. Its value will not be changed.', 'hashe-elementor-acf-editor' ),
                            esc_html( $field['label'] )
                        ),
                    ) );
                    continue;
                }
                ++$supported;
                $document->add_control( $control[0], $control[1] );
            }
            if ( $supported && $index === $last_group_index ) {
                // Native Elementor BUTTON, immediately after the last ACF field.
                // No separate "Save ACF Fields" dropdown/section.
                $document->add_control( 'heae_acf_save_action', array(
                    'type' => \Elementor\Controls_Manager::BUTTON,
                    'text' => __( 'Save ACF Fields', 'hashe-elementor-acf-editor' ),
                    'button_type' => 'success',
                    'show_label' => false,
                    'event' => 'heae_acf_save',
                ) );
            }
            $document->end_controls_section();
        }
    }

    public function enqueue_editor_scripts() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only Elementor editor context. This function does not change data; writes use a document-specific AJAX nonce.
        $post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
        $schema = array();
        if ( $post_id && current_user_can( 'edit_post', $post_id ) ) {
            foreach ( $this->matching_fields( $post_id ) as $key => $field ) {
                $sub_fields = array();
                if ( 'repeater' === $field['type'] ) {
                    foreach ( (array) ( $field['sub_fields'] ?? array() ) as $sub ) {
                        $sub_fields[] = array(
                            'key' => $sub['key'],
                            'id' => self::CONTROL_PREFIX . $sub['key'],
                            'type' => $sub['type'],
                        );
                    }
                }
                $schema[] = array_merge(
                    array(
                        'key' => $key,
                        'id' => self::CONTROL_PREFIX . $key,
                        'type' => $field['type'],
                        'editable' => null !== $this->make_control( $field, $post_id )
                            && ! in_array( $field['type'], array( 'tab', 'accordion', 'message' ), true ),
                        'sub_fields' => $sub_fields,
                    ),
                    $this->heae_extended_schema( $field, $post_id )
                );
            }
        }
        wp_enqueue_media();
        wp_enqueue_script(
            'heae-acf-native-editor',
            plugins_url( 'assets/editor-native.js', __FILE__ ),
            array( 'jquery', 'elementor-editor' ),
            self::VERSION,
            true
        );
        wp_localize_script( 'heae-acf-native-editor', 'HEAE', array(
            'postId' => $post_id,
            'nonce' => $post_id ? wp_create_nonce( self::NONCE_ACTION . $post_id ) : '',
            'fields' => $schema,
            'ajax' => admin_url( 'admin-ajax.php' ),
            'saving' => __( 'Saving ACF fields…', 'hashe-elementor-acf-editor' ),
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

    /**
     * Elementor stores its Page Settings separately from ACF post meta.
     * Never persist our ACF mirror control values in both locations.
     */
    public function filter_elementor_document_save( $data, $document ) {
        if ( ! is_array( $data ) || ! isset( $data['settings'] ) || ! is_array( $data['settings'] ) ) {
            return $data;
        }
        $post_id = is_object( $document ) ? $this->get_post_id( $document ) : 0;
        if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ||
            ! $this->matching_groups( $post_id ) ) {
            return $data;
        }
        foreach ( array_keys( $data['settings'] ) as $key ) {
            if ( 0 === strpos( $key, self::CONTROL_PREFIX ) ) {
                unset( $data['settings'][ $key ] );
            }
        }
        return $data;
    }

    /**
     * Remove only our obsolete mirrored ACF controls, never Elementor's own
     * page configuration, styling, or post status settings.
     */
    private function strip_stale_elementor_acf_values( $post_id ) {
        $settings = get_post_meta( $post_id, '_elementor_page_settings', true );
        if ( ! is_array( $settings ) ) {
            return 0;
        }
        $removed = 0;
        foreach ( array_keys( $settings ) as $key ) {
            if ( 0 === strpos( $key, self::CONTROL_PREFIX ) ) {
                unset( $settings[ $key ] );
                ++$removed;
            }
        }
        if ( $removed ) {
            update_post_meta( $post_id, '_elementor_page_settings', $settings );
        }
        return $removed;
    }

    public function after_elementor_document_save( $document, $data ) {
        $post_id = is_object( $document ) ? $this->get_post_id( $document ) : 0;
        if ( $post_id && current_user_can( 'edit_post', $post_id ) &&
            $this->matching_groups( $post_id ) ) {
            $this->strip_stale_elementor_acf_values( $post_id );
        }
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
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Preserve typed/nested ACF values; post capability, nonce, field-key allowlist, ACF validation and ACF's native save-time sanitization apply.
        $raw = isset( $_POST['acf'] ) && is_array( $_POST['acf'] ) ? wp_unslash( $_POST['acf'] ) : array();
        $payload = array();
        foreach ( $allowed as $key => $field ) {
            if ( array_key_exists( $key, $raw ) ) {
                $payload[ $key ] = $raw[ $key ];
            }
        }
        if ( empty( $payload ) ) {
            wp_send_json_error( array( 'message' => __( 'No ACF values were submitted.', 'hashe-elementor-acf-editor' ) ), 400 );
        }
        if ( function_exists( 'acf_reset_validation_errors' ) ) {
            acf_reset_validation_errors();
        }
        if ( function_exists( 'acf_validate_values' ) ) {
            acf_validate_values( $payload, 'acf' );
        }
        $errors = function_exists( 'acf_get_validation_errors' ) ? acf_get_validation_errors() : false;
        if ( $errors ) {
            wp_send_json_error( array(
                'message' => __( 'Please correct the highlighted fields.', 'hashe-elementor-acf-editor' ),
                'errors' => $errors,
            ), 422 );
        }
        acf_save_post( $post_id, $payload );
        $stale_removed = $this->strip_stale_elementor_acf_values( $post_id );
        wp_send_json_success( array(
            'message' => __( 'ACF fields saved.', 'hashe-elementor-acf-editor' ),
            'post_id' => $post_id,
            'updated_fields' => count( $payload ),
            'stale_elementor_values_removed' => $stale_removed,
        ) );
    }
}
Hashe_Elementor_ACF_Editor::instance();
