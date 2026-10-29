<?php
/**
 * Standalone WordPress/Elementor/ACF API fakes for CI release regression.
 * No database, credentials, licensed source code, or network.
 * Compatible with PHP 7.4 and later.
 */
namespace Elementor {
    class Controls_Manager {
        const TEXT='text', TEXTAREA='textarea', NUMBER='number', SLIDER='slider',
              SELECT='select', SELECT2='select2', SWITCHER='switcher',
              COLOR='color', MEDIA='media', WYSIWYG='wysiwyg',
              DATE_TIME='date_time', BUTTON='button', REPEATER='repeater',
              HEADING='heading', ALERT='alert', URL='url',
              CHOOSE='choose', ICONS='icons', GALLERY='gallery', HIDDEN='hidden';
        public static $tabs=[];
        public static function add_tab($name,$label){self::$tabs[$name]=$label;}
    }
    class Repeater {
        private $controls=[];
        public function add_control($name,$data){$this->controls[]=array_merge(['name'=>$name],$data);}
        public function get_controls(){return $this->controls;}
    }
}
namespace {
    define('ABSPATH',__DIR__.'/');
    class TestJsonResponse extends \Exception {
        public $kind,$data,$status;
        public function __construct($kind,$data,$status){
            parent::__construct($kind);
            $this->kind=$kind; $this->data=$data; $this->status=$status;
        }
    }
    $GLOBALS['heae_mock']=[
        'can_edit'=>true,'nonce_valid'=>true,'errors'=>[],
        'saved'=>[], 'postmeta'=>[
            654=>['_elementor_page_settings'=>[
                'heae_acf_field_heae_test_headline'=>'old mirror',
                'page_title'=>'keep me'
            ]]
        ],
        'fields'=>[
            ['key'=>'field_heae_test_headline','name'=>'headline','label'=>'Headline','type'=>'text','required'=>1],
            ['key'=>'field_heae_test_repeater','name'=>'facts','label'=>'Facts','type'=>'repeater','sub_fields'=>[
                ['key'=>'field_heae_test_fact_label','name'=>'fact_label','label'=>'Label','type'=>'text']
            ]]
        ]
    ];
    function expect($condition,$message){
        if(!$condition){fwrite(STDERR,"FAIL: $message\n");exit(1);}
    }
    function add_action($name,$callback,$priority=10,$args=1){}
    function add_filter($name,$callback,$priority=10,$args=1){}
    function did_action($name){return $name==='elementor/loaded'?1:0;}
    function __($message,$domain=''){return $message;}
    function esc_html__($message,$domain=''){return $message;}
    function wp_strip_all_tags($s){return strip_tags((string)$s);}
    function wp_kses_post($s){return (string)$s;}
    function absint($n){return abs((int)$n);}
    function current_user_can($cap,$id=null){
        if($cap==='edit_post')return $GLOBALS['heae_mock']['can_edit'];
        return true;
    }
    function get_post($id){return in_array((int)$id,[13,654],true)?(object)['ID'=>(int)$id]:null;}
    function acf_get_field_groups($args){
        return ($args['post_id']??0)===654?[['key'=>'group_heae_test','active'=>true]]:[];
    }
    function acf_get_fields($group){return $GLOBALS['heae_mock']['fields'];}
    function acf_get_field($key){return null;}
    function get_field($key,$id=null,$formatted=false){return false;}
    function acf_save_post($id,$payload){$GLOBALS['heae_mock']['saved'][]=['id'=>$id,'payload'=>$payload];}
    function acf_reset_validation_errors(){$GLOBALS['heae_mock']['errors']=[];}
    function acf_validate_values($values,$prefix='acf'){
        if(array_key_exists('field_heae_test_headline',$values) &&
            trim((string)$values['field_heae_test_headline'])===''){
            $GLOBALS['heae_mock']['errors'][]=['message'=>'Headline is required'];
        }
    }
    function acf_get_validation_errors(){return $GLOBALS['heae_mock']['errors'];}
    function wp_unslash($values){return $values;}
    function check_ajax_referer($action,$name){
        if(!$GLOBALS['heae_mock']['nonce_valid'] || ($_POST[$name]??'')!=='valid'){
            throw new TestJsonResponse('nonce-error',[],403);
        }
    }
    function wp_send_json_error($data,$status=200){throw new TestJsonResponse('error',$data,$status);}
    function wp_send_json_success($data,$status=200){throw new TestJsonResponse('success',$data,$status);}
    function get_post_meta($id,$key,$single=true){
        return $GLOBALS['heae_mock']['postmeta'][$id][$key]??($single?'':[]);
    }
    function update_post_meta($id,$key,$data){
        $GLOBALS['heae_mock']['postmeta'][$id][$key]=$data;return true;
    }
    function get_post_types($args=[],$output='names'){return ['page'=>'page','post'=>'post'];}
    function get_posts($args=[]){return [];}
    function get_permalink($id){return 'https://example.test/page/'.$id.'/';}
    function get_the_title($id){return 'Test #'.$id;}
    function taxonomy_exists($tax){return true;}
    function get_terms($args=[]){return [];}
    function get_term($id,$taxonomy){return null;}
    function is_wp_error($value){return false;}
    function get_users($args=[]){return [(object)['ID'=>1,'display_name'=>'Test User']];}
    function wp_get_current_user(){return (object)['ID'=>1,'display_name'=>'Test User'];}
    function get_userdata($id){return (object)['ID'=>$id,'display_name'=>'Test User'];}
    function wp_get_attachment_url($id){return 'https://example.test/image/'.$id.'.png';}
    function run_save($postId,$values,$authorized=true,$nonce=true){
        $GLOBALS['heae_mock']['can_edit']=$authorized;
        $GLOBALS['heae_mock']['nonce_valid']=$nonce;
        $_POST=['post_id'=>$postId,'nonce'=>'valid','acf'=>$values];
        try { \Hashe_Elementor_ACF_Editor::instance()->ajax_save(); }
        catch(TestJsonResponse $result){return $result;}
        throw new \RuntimeException('Handler should send JSON response');
    }
    require dirname(__DIR__).'/hashe-elementor-acf-editor.php';
    $plugin=\Hashe_Elementor_ACF_Editor::instance();

    $doc=new class {
        public function get_main_id(){return 654;}
    };
    $input=['settings'=>[
        'heae_acf_field_heae_test_headline'=>'stale',
        'heae_acf_field_heae_qa12_link'=>['value'=>'stale'],
        'post_status'=>'draft','custom_css'=>'keep-css'
    ],'elements'=>[['id'=>'a']]];
    $filtered=$plugin->filter_elementor_document_save($input,$doc);
    expect(!isset($filtered['settings']['heae_acf_field_heae_test_headline']),
        'ACF values must not be duplicated in Elementor page settings');
    expect(!isset($filtered['settings']['heae_acf_field_heae_qa12_link']),
        'all mirrored ACF keys must be stripped');
    expect($filtered['settings']['custom_css']==='keep-css' &&
           $filtered['settings']['post_status']==='draft',
        'native Elementor settings must be preserved');
    echo "PASS Elementor/ACF source of truth separation.\n";

    $reflect=new \ReflectionMethod('Hashe_Elementor_ACF_Editor','make_control');
    $reflect->setAccessible(true);
    $subfields=[['key'=>'field_sub','name'=>'sub','label'=>'Sub','type'=>'text']];
    $cases=[
        ['type'=>'text'],['type'=>'textarea'],['type'=>'image'],
        ['type'=>'message'],['type'=>'accordion'],['type'=>'tab'],
        ['type'=>'link'],['type'=>'google_map'],['type'=>'icon_picker'],
        ['type'=>'relationship','post_type'=>['page']],
        ['type'=>'post_object','post_type'=>['page']],
        ['type'=>'page_link','post_type'=>['page']],
        ['type'=>'taxonomy','taxonomy'=>'category'],
        ['type'=>'user'],
        ['type'=>'group','sub_fields'=>$subfields],
        ['type'=>'clone','sub_fields'=>$subfields],
        ['type'=>'flexible_content','layouts'=>[
            ['name'=>'intro','label'=>'Intro','sub_fields'=>$subfields]
        ]]
    ];
    foreach($cases as $spec){
        $spec=array_merge(['key'=>'field_release_'.$spec['type'],
            'name'=>'release_'.$spec['type'],'label'=>'Release '.$spec['type']],$spec);
        $result=$reflect->invoke($plugin,$spec,654,false);
        expect(is_array($result)&&count($result)===2,'mapping '.$spec['type'].' must register');
        expect(!empty($result[1]['type']),'mapping '.$spec['type'].' must provide Elementor type');
    }
    echo "PASS native Elementor mappings for representative standard/Pro field types.\n";

    $bad=run_save(654,['field_heae_test_headline'=>'Unauthorized'],false);
    expect($bad->kind==='error'&&$bad->status===403,'unauthorized user rejected');
    $bad=run_save(654,['field_heae_test_headline'=>'Bad nonce'],true,false);
    expect($bad->kind==='nonce-error'&&$bad->status===403,'bad nonce rejected');
    $bad=run_save(13,['field_heae_test_headline'=>'Outside group']);
    expect($bad->kind==='error'&&$bad->status===400,'cross-document field rejected');
    $bad=run_save(654,['field_wrong_site'=>'Unassigned field']);
    expect($bad->kind==='error'&&$bad->status===400,'unassigned-only payload rejected');
    $bad=run_save(654,['field_heae_test_headline'=>'']);
    expect($bad->kind==='error'&&$bad->status===422,'ACF validation error rejected');

    $good=run_save(654,[
       'field_heae_test_headline'=>'Changed',
       'field_unrelated'=>'Should not save'
    ]);
    expect($good->kind==='success'&&$good->data['updated_fields']===1,
      'only allowed field should be saved');
    expect(count($GLOBALS['heae_mock']['saved'])===1,'only one authorized write');
    expect(!isset($GLOBALS['heae_mock']['saved'][0]['payload']['field_unrelated']),
      'unrelated field must never reach ACF');
    $settings=get_post_meta(654,'_elementor_page_settings',true);
    expect($settings['page_title']==='keep me' && !isset($settings['heae_acf_field_heae_test_headline']),
      'stale Elementor ACF mirror removed without affecting page settings');
    echo "PASS capability, nonce, document-scope, allowlist, ACF validation and selective save.\n";
    echo "ALL PHP RELEASE REGRESSION TESTS PASSED\n";
}
