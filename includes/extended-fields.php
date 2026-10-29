<?php
/**
 * Elementor-native adapters for ACF/ACF Pro built-in advanced field types.
 * No admin form, iframe, or third-party ACF field extension.
 */
defined( 'ABSPATH' ) || exit;

trait Hashe_ACF_Extended_Controls {
    private function heae_single_row( $field, $row, $values, $title = '' ) {
        $repeater = new \Elementor\Repeater();
        foreach ( $row as $name => $definition ) {
            $repeater->add_control( $name, $definition );
        }
        $id = self::CONTROL_PREFIX . $field['key'];
        return array( $id, array(
            'label' => $field['label'] ?? $field['name'],
            'label_block' => true,
            'description' => wp_strip_all_tags( $field['instructions'] ?? '' ),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $repeater->get_controls(),
            'title_field' => $title ?: __( 'Properties', 'hashe-elementor-acf-editor' ),
            'button_text' => __( 'Add Details', 'hashe-elementor-acf-editor' ),
            'default' => array( array_merge( array( '_id' => substr( md5( $field['key'] ), 0, 7 ) ), $values ) ),
            'min_items' => 1,
            'max_items' => 1,
            'prevent_empty' => true,
            'item_actions' => array( 'add' => false, 'duplicate' => false, 'remove' => false, 'sort' => false ),
        ) );
    }

    private function heae_field_value_for_control( $type, $value ) {
        if ( 'image' === $type || 'file' === $type ) {
            $id = is_array( $value ) ? absint( $value['ID'] ?? $value['id'] ?? 0 ) : absint( $value );
            return array( 'id' => $id, 'url' => $id ? wp_get_attachment_url( $id ) : '' );
        }
        if ( 'true_false' === $type ) {
            return $value ? 'yes' : '';
        }
        return $value;
    }

    private function heae_composite( $field, $post_id, $value ) {
        $subfields = (array) ( $field['sub_fields'] ?? array() );
        if ( ! $subfields ) {
            return null;
        }
        $controls = array();
        $values = array();
        $value = is_array( $value ) ? $value : array();
        foreach ( $subfields as $sub ) {
            if ( empty( $sub['key'] ) ) {
                return null;
            }
            $control = $this->make_control( $sub, $post_id, true );
            if ( ! $control ) {
                return null; // Do not silently discard any nested ACF value.
            }
            $controls[ $control[0] ] = $control[1];
            $raw = $value[ $sub['key'] ] ?? $value[ $sub['name'] ] ?? $sub['default_value'] ?? '';
            $values[ $control[0] ] = $this->heae_field_value_for_control( $sub['type'], $raw );
        }
        return $this->heae_single_row( $field, $controls, $values, __( 'Details', 'hashe-elementor-acf-editor' ) );
    }

    private function heae_post_choices( $field, $post_id, $page_link = false ) {
        $types = (array) ( $field['post_type'] ?? array() );
        if ( ! $types ) {
            $types = array_values( get_post_types( array( 'public' => true ) ) );
        }
        $types = array_values( array_intersect( $types, get_post_types( array(), 'names' ) ) );
        if ( ! $types ) {
            return array();
        }
        $posts = get_posts( array(
            'post_type' => $types, 'post_status' => array( 'publish', 'private', 'draft' ),
            'numberposts' => 500, 'orderby' => 'title', 'order' => 'ASC',
            'suppress_filters' => false,
        ) );
        $options = array();
        foreach ( $posts as $p ) {
            if ( 'private' === $p->post_status && ! current_user_can( 'read_post', $p->ID ) ) {
                continue;
            }
            if ( 'draft' === $p->post_status && ! current_user_can( 'edit_post', $p->ID ) ) {
                continue;
            }
            $key = $page_link ? get_permalink( $p->ID ) : (string) $p->ID;
            if ( $key ) {
                $options[ $key ] = sprintf( '%s (#%d)', get_the_title( $p->ID ) ?: '(Untitled)', $p->ID );
            }
        }
        $current = $this->get_value( $field, $post_id );
        foreach ( (array) $current as $item ) {
            $id = is_object( $item ) ? (int) $item->ID : ( is_array( $item ) ? (int) ( $item['ID'] ?? $item['id'] ?? 0 ) : absint( $item ) );
            if ( $page_link ) {
                $url = is_string( $item ) && ! ctype_digit( $item ) ? $item : ( $id ? get_permalink( $id ) : '' );
                if ( $url && ! isset( $options[ $url ] ) ) {
                    $options[ $url ] = $url;
                }
            } elseif ( $id && ! isset( $options[ (string) $id ] ) ) {
                $options[ (string) $id ] = get_the_title( $id ) ?: 'Post #' . $id;
            }
        }
        return $options;
    }

    private function heae_term_choices( $field, $post_id ) {
        $taxonomy = $field['taxonomy'] ?? 'category';
        if ( ! taxonomy_exists( $taxonomy ) ) {
            return array();
        }
        $terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false, 'number' => 500 ) );
        $options = array();
        if ( ! is_wp_error( $terms ) ) {
            foreach ( $terms as $term ) {
                $options[ (string) $term->term_id ] = $term->name;
            }
        }
        foreach ( (array) $this->get_value( $field, $post_id ) as $id ) {
            $id = absint( $id );
            if ( $id && ! isset( $options[ (string) $id ] ) ) {
                $term = get_term( $id, $taxonomy );
                if ( $term && ! is_wp_error( $term ) ) {
                    $options[ (string) $id ] = $term->name;
                }
            }
        }
        return $options;
    }

    private function heae_user_choices( $field, $post_id ) {
        $options = array();
        $users = current_user_can( 'list_users' )
            ? get_users( array( 'number' => 500, 'orderby' => 'display_name' ) )
            : array( wp_get_current_user() );
        foreach ( $users as $user ) {
            if ( $user && $user->ID ) {
                $options[ (string) $user->ID ] = $user->display_name . ' (#' . $user->ID . ')';
            }
        }
        $raw = $this->get_value( $field, $post_id );
        foreach ( (array) $raw as $item ) {
            $id = is_object( $item ) ? (int) $item->ID : ( is_array( $item ) ? (int) ( $item['ID'] ?? 0 ) : absint( $item ) );
            if ( $id && ! isset( $options[ (string) $id ] ) && current_user_can( 'list_users' ) ) {
                $user = get_userdata( $id );
                if ( $user ) {
                    $options[ (string) $id ] = $user->display_name . ' (#' . $id . ')';
                }
            }
        }
        return $options;
    }

    private function make_extended_control( $field, $post_id, $inside_repeater = false ) {
        $type = $field['type'] ?? '';
        $id = self::CONTROL_PREFIX . $field['key'];
        $label = $field['label'] ?? $field['name'] ?? $type;
        $value = $inside_repeater ? ( $field['default_value'] ?? '' ) : $this->get_value( $field, $post_id );
        $args = array(
            'label' => $label, 'label_block' => true,
            'description' => wp_strip_all_tags( $field['instructions'] ?? '' ),
        );

        if ( 'message' === $type ) {
            return array( $id, array(
                'type' => \Elementor\Controls_Manager::ALERT, 'alert_type' => 'info',
                'content' => wp_kses_post( $field['message'] ?? '' ),
            ) );
        }
        if ( 'tab' === $type || 'accordion' === $type ) {
            return array( $id, array(
                'type' => \Elementor\Controls_Manager::HEADING,
                'label' => $label,
                'separator' => 'before',
            ) );
        }
        if ( in_array( $type, array( 'post_object', 'page_link', 'taxonomy', 'user' ), true ) ) {
            if ( 'post_object' === $type ) {
                $options = $this->heae_post_choices( $field, $post_id );
                $multiple = ! empty( $field['multiple'] );
            } elseif ( 'page_link' === $type ) {
                $options = $this->heae_post_choices( $field, $post_id, true );
                $multiple = ! empty( $field['multiple'] );
            } elseif ( 'taxonomy' === $type ) {
                $options = $this->heae_term_choices( $field, $post_id );
                $multiple = ! empty( $field['multiple'] ) || in_array( $field['field_type'] ?? '', array( 'checkbox', 'multi_select' ), true );
            } else {
                $options = $this->heae_user_choices( $field, $post_id );
                $multiple = ! empty( $field['multiple'] );
            }
            $args['type'] = \Elementor\Controls_Manager::SELECT2;
            $args['options'] = $options;
            $args['multiple'] = $multiple;
            $raw = $value;
            if ( is_object( $raw ) ) {
                $raw = $raw->ID ?? '';
            }
            $norm = array();
            foreach ( (array) $raw as $part ) {
                if ( is_object( $part ) ) {
                    $part = $part->term_id ?? $part->ID ?? 0;
                } elseif ( is_array( $part ) ) {
                    $part = $part['term_id'] ?? $part['ID'] ?? 0;
                }
                $norm[] = (string) $part;
            }
            $args['default'] = $multiple ? $norm : ( $norm[0] ?? '' );
            return array( $id, $args );
        }
        if ( 'relationship' === $type ) {
            if ( $inside_repeater ) return null;
            $items = new \Elementor\Repeater();
            $items->add_control( 'heae_relationship_post', array(
                'label' => __( 'Related Post', 'hashe-elementor-acf-editor' ),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'options' => $this->heae_post_choices( $field, $post_id ),
                'label_block' => true,
            ) );
            $args['type'] = \Elementor\Controls_Manager::REPEATER;
            $args['fields'] = $items->get_controls();
            $args['title_field'] = __( 'Related Post', 'hashe-elementor-acf-editor' );
            $args['button_text'] = __( 'Add Related Post', 'hashe-elementor-acf-editor' );
            $args['prevent_empty'] = false;
            $args['item_actions'] = array( 'add' => true, 'duplicate' => false, 'remove' => true, 'sort' => true );
            if ( ! empty( $field['max'] ) ) $args['max_items'] = absint( $field['max'] );
            if ( ! empty( $field['min'] ) ) $args['min_items'] = absint( $field['min'] );
            $args['default'] = array();
            foreach ( (array) $value as $index => $post ) {
                $post_id_value = is_object( $post ) ? $post->ID : ( is_array( $post ) ? ( $post['ID'] ?? 0 ) : $post );
                $args['default'][] = array( '_id' => substr( md5( $field['key'] . $index ), 0, 7 ), 'heae_relationship_post' => (string) absint( $post_id_value ) );
            }
            return array( $id, $args );
        }
        if ( 'link' === $type ) {
            if ( $inside_repeater ) return null;
            $value = is_array( $value ) ? $value : array( 'url' => is_string( $value ) ? $value : '' );
            return $this->heae_single_row( $field, array(
                'heae_link_url' => array( 'type' => \Elementor\Controls_Manager::TEXT, 'label' => 'URL', 'label_block' => true ),
                'heae_link_title' => array( 'type' => \Elementor\Controls_Manager::TEXT, 'label' => 'Title', 'label_block' => true ),
                'heae_link_target' => array( 'type' => \Elementor\Controls_Manager::SWITCHER, 'label' => 'Open in new tab', 'return_value' => 'yes' ),
            ), array(
                'heae_link_url' => $value['url'] ?? '',
                'heae_link_title' => $value['title'] ?? '',
                'heae_link_target' => ( $value['target'] ?? '' ) === '_blank' ? 'yes' : '',
            ), __( 'Link', 'hashe-elementor-acf-editor' ) );
        }
        if ( 'google_map' === $type ) {
            if ( $inside_repeater ) return null;
            $value = is_array( $value ) ? $value : array();
            return $this->heae_single_row( $field, array(
                'heae_map_address' => array( 'type' => \Elementor\Controls_Manager::TEXT, 'label' => 'Address', 'label_block' => true ),
                'heae_map_lat' => array( 'type' => \Elementor\Controls_Manager::NUMBER, 'label' => 'Latitude', 'min' => -90, 'max' => 90, 'step' => 0.000001 ),
                'heae_map_lng' => array( 'type' => \Elementor\Controls_Manager::NUMBER, 'label' => 'Longitude', 'min' => -180, 'max' => 180, 'step' => 0.000001 ),
                'heae_map_zoom' => array( 'type' => \Elementor\Controls_Manager::NUMBER, 'label' => 'Zoom', 'min' => 1, 'max' => 22 ),
            ), array(
                'heae_map_address' => $value['address'] ?? '',
                'heae_map_lat' => $value['lat'] ?? 0,
                'heae_map_lng' => $value['lng'] ?? 0,
                'heae_map_zoom' => $value['zoom'] ?? 14,
            ), __( 'Map Location', 'hashe-elementor-acf-editor' ) );
        }
        if ( 'icon_picker' === $type ) {
            if ( $inside_repeater ) return null;
            $value = is_array( $value ) ? $value : array( 'type' => 'dashicons', 'value' => is_scalar( $value ) ? (string) $value : '' );
            return $this->heae_single_row( $field, array(
                'heae_icon_type' => array( 'type' => \Elementor\Controls_Manager::SELECT, 'label' => 'Icon Source', 'options' => array( 'dashicons' => 'WordPress Dashicon', 'url' => 'Icon URL' ) ),
                'heae_icon_value' => array( 'type' => \Elementor\Controls_Manager::TEXT, 'label' => 'Dashicon class or icon URL', 'label_block' => true ),
            ), array(
                'heae_icon_type' => $value['type'] ?? 'dashicons',
                'heae_icon_value' => $value['value'] ?? '',
            ), __( 'Icon', 'hashe-elementor-acf-editor' ) );
        }
        if ( 'group' === $type || 'clone' === $type ) {
            if ( $inside_repeater ) return null;
            return $this->heae_composite( $field, $post_id, $value );
        }
        if ( 'flexible_content' === $type ) {
            if ( $inside_repeater ) return null;
            $layouts = (array) ( $field['layouts'] ?? array() );
            if ( ! $layouts ) return null;
            $layout_options = array();
            $rep = new \Elementor\Repeater();
            foreach ( $layouts as $layout ) {
                $layout_options[ $layout['name'] ] = $layout['label'] ?? $layout['name'];
            }
            $rep->add_control( 'heae_fc_layout', array(
                'label' => __( 'Layout', 'hashe-elementor-acf-editor' ),
                'type' => \Elementor\Controls_Manager::SELECT, 'options' => $layout_options,
                'default' => array_key_first( $layout_options ),
            ) );
            $subfields = array();
            foreach ( $layouts as $layout ) {
                foreach ( (array) ( $layout['sub_fields'] ?? array() ) as $sub ) {
                    $ctl = $this->make_control( $sub, $post_id, true );
                    if ( ! $ctl ) return null; // Fail closed when nesting is unsupported.
                    $name = $ctl[0];
                    $ctl[1]['condition'] = array( 'heae_fc_layout' => $layout['name'] );
                    $rep->add_control( $name, $ctl[1] );
                    $subfields[ $layout['name'] ][] = $sub;
                }
            }
            $args['type'] = \Elementor\Controls_Manager::REPEATER;
            $args['fields'] = $rep->get_controls();
            $args['title_field'] = '{{{ heae_fc_layout }}}';
            $args['button_text'] = __( 'Add Layout', 'hashe-elementor-acf-editor' );
            $args['prevent_empty'] = false;
            $args['item_actions'] = array( 'add' => true, 'duplicate' => true, 'remove' => true, 'sort' => true );
            $args['default'] = array();
            foreach ( (array) $value as $index => $row ) {
                if ( ! is_array( $row ) ) continue;
                $layout = $row['acf_fc_layout'] ?? '';
                if ( ! isset( $layout_options[ $layout ] ) ) continue;
                $entry = array( '_id' => substr( md5( $field['key'] . $index ), 0, 7 ), 'heae_fc_layout' => $layout );
                foreach ( (array) ( $subfields[ $layout ] ?? array() ) as $sub ) {
                    $raw = $row[ $sub['key'] ] ?? $row[ $sub['name'] ] ?? '';
                    $entry[ self::CONTROL_PREFIX . $sub['key'] ] = $this->heae_field_value_for_control( $sub['type'], $raw );
                }
                $args['default'][] = $entry;
            }
            return array( $id, $args );
        }
        return null;
    }

    private function heae_extended_schema( $field, $post_id ) {
        $type = $field['type'] ?? '';
        if ( 'group' === $type || 'clone' === $type ) {
            $subs = array();
            foreach ( (array) ( $field['sub_fields'] ?? array() ) as $sub ) {
                $subs[] = array( 'key' => $sub['key'], 'name' => $sub['name'], 'id' => self::CONTROL_PREFIX . $sub['key'], 'type' => $sub['type'] );
            }
            return array( 'sub_fields' => $subs );
        }
        if ( 'flexible_content' === $type ) {
            $layouts = array();
            foreach ( (array) ( $field['layouts'] ?? array() ) as $layout ) {
                $subs = array();
                foreach ( (array) ( $layout['sub_fields'] ?? array() ) as $sub ) {
                    $subs[] = array( 'key' => $sub['key'], 'name' => $sub['name'], 'id' => self::CONTROL_PREFIX . $sub['key'], 'type' => $sub['type'] );
                }
                $layouts[ $layout['name'] ] = $subs;
            }
            return array( 'layouts' => $layouts );
        }
        if ( 'google_map' === $type ) {
            return array( 'original' => $this->get_value( $field, $post_id ) );
        }
        return array();
    }
}
