<?php
/**
 * Standalone smoke tests: WordPress/ACF/Elementor API fakes.
 * No database, server, privileged connections or network.
 */
namespace Elementor {
    class Controls_Manager {
        const RAW_HTML = 'raw_html';
        public static $tabs = array();
        public static function add_tab( $id, $label ) { self::$tabs[$id] = $label; }
    }
}
namespace {
    define( 'ABSPATH', __DIR__ . '/' );
    class WP_Smoke_Response extends \Exception {
        public $kind;
        public $data;
        public $status;
        public function __construct( $kind, $data, $status = 200 ) {
            $this->kind = $kind;
            $this->data = $data;
            $this->status = $status;
            parent::__construct( $kind );
        }
    }
    class WP_Smoke_Document {
        private $post_id;
        public $sections = array();
        public $controls = array();
        public function __construct( $id ) { $this->post_id = $id; }
        public function get_main_id() { return $this->post_id; }
        public function start_controls_section( $name, $opts ) { $this->sections[$name] = $opts; }
        public function add_control( $name, $opts ) { $this->controls[$name] = $opts; }
        public function end_controls_section() {}
    }
    $GLOBALS['heae_test'] = array(
        'authorized' => true,
        'valid' => true,
        'saved' => array(),
        'fields' => array(
            array( 'key'=>'field_test_headline', 'name'=>'headline', 'label'=>'Headline', 'type'=>'text' ),
            array( 'key'=>'field_test_repeater', 'name'=>'facts', 'label'=>'Facts', 'type'=>'repeater' ),
        ),
    );
    function expect( $bool, $message ) {
        if ( !$bool ) {
            fwrite( STDERR, "FAIL: $message\n" );
            exit( 1 );
        }
    }
    function add_action( $hook, $handler, $priority = 10 ) {}
    function did_action( $hook ) { return $hook === 'elementor/loaded'; }
    function acf() { return true; }
    function acf_render_fields( $fields, $post_id, $tag, $position ) {
        foreach ($fields as $field) {
            echo '<div class="acf-field"><input name="acf[' . $field['key'] . ']" value="Test"></div>';
        }
    }
    function acf_get_field_groups( $args ) {
        return (int)($args['post_id'] ?? 0) === 654
            ? array( array( 'key'=>'group_heae_test', 'active'=>true ) )
            : array();
    }
    function acf_get_fields( $group ) { return $GLOBALS['heae_test']['fields']; }
    function acf_save_post( $post_id, $payload ) {
        $GLOBALS['heae_test']['saved'][] = array( 'post_id'=>$post_id, 'payload'=>$payload );
    }
    function acf_validate_values( $payload, $prefix ) {
        $GLOBALS['heae_test']['valid'] = !array_key_exists('field_test_headline', $payload)
            || !empty($payload['field_test_headline']);
    }
    function acf_reset_validation_errors() { $GLOBALS['heae_test']['valid'] = true; }
    function acf_get_validation_errors() {
        return $GLOBALS['heae_test']['valid'] ? false
            : array( array( 'message'=>'Headline is required' ) );
    }
    function absint( $n ) { return abs((int)$n); }
    function current_user_can( $cap, $id = 0 ) { return $GLOBALS['heae_test']['authorized']; }
    function __( $text, $domain = '' ) { return $text; }
    function esc_html__( $text, $domain = '' ) { return $text; }
    function esc_html( $text ) { return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8'); }
    function esc_attr( $text ) { return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8'); }
    function wp_create_nonce( $action ) { return 'nonce-' . $action; }
    function get_post( $id ) { return $id > 0 ? (object)array( 'ID'=>$id ) : null; }
    function wp_unslash( $v ) { return $v; }
    function check_ajax_referer( $action, $field ) {
        $nonce = $_POST[$field] ?? '';
        if ( $nonce !== wp_create_nonce($action) ) {
            throw new WP_Smoke_Response('nonce-rejected', array(), 403);
        }
    }
    function wp_send_json_error( $data, $status = 400 ) {
        throw new WP_Smoke_Response('error', $data, $status);
    }
    function wp_send_json_success( $data ) {
        throw new WP_Smoke_Response('success', $data);
    }
    require __DIR__ . '/../hashe-elementor-acf-editor.php';
    $plugin = Hashe_Elementor_ACF_Editor::instance();

    $doc = new WP_Smoke_Document(654);
    $plugin->register_document_controls($doc);
    $panel = $doc->controls['heae_fields_control']['raw'] ?? '';
    expect(strpos($panel,'<iframe')===false,'panel must have no iframe');
    expect(strpos($panel,'wpadminbar')===false,'panel must not include admin toolbar');
    expect(strpos($panel,'data-heae-nonce')!==false,'panel has post-scoped nonce');
    expect(strpos($panel,'name="acf[field_test_headline]"')!==false,'ACF text input rendered');
    expect(strpos($panel,'name="acf[field_test_repeater]"')!==false,'ACF repeater input rendered');
    expect(strpos($panel,'Save ACF Fields')!==false,'save control rendered');
    expect(($doc->sections['heae_fields_section']['tab']??'')==='heae_acf','correct Elementor tab');
    echo "PASS: native ACF markup directly in Elementor panel.\n";

    $empty = new WP_Smoke_Document(13);
    $plugin->register_document_controls($empty);
    $html = $empty->controls['heae_fields_control']['raw'] ?? '';
    expect(strpos($html,'No ACF fields are assigned')!==false,'unassigned content empty state');
    expect(strpos($html,'heae-acf-form')===false,'no form if no groups');
    echo "PASS: unassigned content shows empty state.\n";

    function run_save($fields, $user_can_edit=true, $post_id=654, $nonce_override=null) {
        $GLOBALS['heae_test']['authorized'] = $user_can_edit;
        $_POST = array(
            'post_id' => $post_id,
            'nonce' => $nonce_override === null ? wp_create_nonce('heae_save_acf_'.$post_id) : $nonce_override,
            'acf' => $fields,
        );
        try {
            Hashe_Elementor_ACF_Editor::instance()->ajax_save();
        } catch (WP_Smoke_Response $r) {
            return $r;
        }
        throw new \Exception('Missing response');
    }
    $r = run_save(array('field_test_headline'=>'Edited', 'field_test_repeater'=>array(array('field_test_fact'=>'Fact'))));
    expect($r->kind==='success', 'legitimate ACF save');
    expect($r->data['updated_fields']===2,'both assigned fields');
    expect(count($GLOBALS['heae_test']['saved'])===1,'ACF official save called');
    echo "PASS: assigned fields saved through ACF hook.\n";

    $r = run_save(array('field_elsewhere'=>'Intruder'));
    expect($r->kind==='error' && $r->status===400,'reject unrelated key');
    expect(count($GLOBALS['heae_test']['saved'])===1,'no unrelated save side effects');
    echo "PASS: field keys outside matching group rejected.\n";

    $r = run_save(array('field_test_headline'=>'Unauthorized'), false);
    expect($r->kind==='error' && $r->status===403,'reject user without edit permission');
    echo "PASS: unauthorized user rejected.\n";

    $r = run_save(array('field_test_headline'=>'Invalid nonce'), true, 654, 'bad-nonce');
    expect($r->kind==='nonce-rejected' && $r->status===403,'reject invalid nonce');
    echo "PASS: invalid nonce rejected.\n";

    $r = run_save(array('field_test_headline'=>''));
    expect($r->kind==='error' && $r->status===422,'reject required-field violation');
    expect(count($GLOBALS['heae_test']['saved'])===1,'do not write invalid values');
    echo "PASS: required field validated before update.\n";

    $r = run_save(array('field_test_headline'=>'Wrong post'), true, 13);
    expect($r->kind==='error' && $r->status===400,'reject unassigned field on another post');
    echo "PASS: cross-post field injection rejected.\n";

    echo "ALL HASHE NATIVE ACF REGRESSIONS PASSED\n";
}
