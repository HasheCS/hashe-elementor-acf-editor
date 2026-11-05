<?php
/** HP-PLUG-001 site-ownership proof: real installation keys, nonce binding, default-off. */
define('ABSPATH',__DIR__);
$GLOBALS['hashe_options']=[];
$GLOBALS['hashe_hooks']=[];
$GLOBALS['hashe_routes']=[];
$GLOBALS['hashe_contract']=[];
function get_option($key,$default=false){return $GLOBALS['hashe_options'][$key]??$default;}
function add_action($key,$cb){$GLOBALS['hashe_hooks'][$key]=$cb;}
function apply_filters($key,$v){return $key==='hashe_acf_optional_reporting_contract'?$GLOBALS['hashe_contract']:$v;}
function register_rest_route($namespace,$route,$args){$GLOBALS['hashe_routes'][$namespace.$route]=$args;}
class WP_Error {
 public $code;public $data;
 function __construct($code,$message,$data=[]){$this->code=$code;$this->data=$data;}
}
class HasheProofRequest {
 private $nonce;
 function __construct($nonce){$this->nonce=$nonce;}
 function get_param($name){return $name==='challenge'?$this->nonce:null;}
}
require dirname(__DIR__).'/distribution/independent/hashe-core/v1/consent-client.php';
function qa($ok,$msg){if(!$ok){fwrite(STDERR,"FAIL $msg\n");exit(2);}echo "PASS $msg\n";}
$c=new \Hashe\FieldEditor\PluginTools\V1\ConsentClient('hashe-elementor-acf-editor','hashe-elementor-acf-editor/hashe-elementor-acf-editor.php');
$c->register();
qa(isset($GLOBALS['hashe_hooks']['rest_api_init']),'route registration is deferred to REST initialization');
$c->register_site_proof();
qa(isset($GLOBALS['hashe_routes']['hashe/v1/site-proof']),'protected nonce challenge route registered');
$nonce=str_repeat('b',64);
$r=$c->answer_site_proof(new HasheProofRequest($nonce));
qa($r instanceof WP_Error&&$r->data['status']===404,'before consent, proof and identity unavailable');
$GLOBALS['hashe_contract']=[
 'version'=>'v1-reviewed','privacy_url'=>'https://plugins.hashe.com/privacy.html',
 'retention'=>'Only last-reported active, deletion on revoke','cadence'=>'Every 30 days',
 'report_endpoint'=>'https://plugins.hashe.com/api/v1/telemetry/report',
 'revoke_endpoint'=>'https://plugins.hashe.com/api/v1/telemetry/revoke',
 'enroll_endpoint'=>'https://plugins.hashe.com/api/v1/telemetry/enroll',
];
$r=$c->answer_site_proof(new HasheProofRequest($nonce));
qa($r instanceof WP_Error,'contract alone does not create consent or credentials');
$secret=str_repeat('a',64);$id='11111111-1111-4111-8111-111111111111';
$GLOBALS['hashe_options']['hashe_acf_optional_reporting_v1']=[
 'status'=>'allowed','policy_version'=>'v1-reviewed','installation_id'=>$id,
 'secret_hex'=>$secret,'consented_at_utc'=>gmdate('Y-m-d\TH:i:s\Z'),'enrolled'=>false
];
$r=$c->answer_site_proof(new HasheProofRequest($nonce));
$expected=hash_hmac('sha256',$nonce."\n".'hashe-elementor-acf-editor'."\n".$id,hex2bin($secret));
qa(is_array($r)&&($r['proof']??'')===$expected,'proof binds fresh nonce, exact slug, installation ID and local secret');
qa(($r['schema_version']??null)===1&&($r['plugin']??null)==='hashe-elementor-acf-editor','proof schema is exact');
qa(!isset($r['secret_hex']),'proof never discloses local credential');
qa(!isset($r['installation_id']),'public proof response does not disclose installation UUID');
$r=$c->answer_site_proof(new HasheProofRequest('invalid_nonce'));
qa($r instanceof WP_Error,'bad challenge returns no identity');
$GLOBALS['hashe_options']['hashe_acf_optional_reporting_v1']['status']='revocation-pending';
$r=$c->answer_site_proof(new HasheProofRequest($nonce));
qa($r instanceof WP_Error,'revocation blocks further proof');
$GLOBALS['hashe_options']['hashe_acf_optional_reporting_v1']['status']='allowed';
$GLOBALS['hashe_contract']=[];
$r=$c->answer_site_proof(new HasheProofRequest($nonce));
qa($r instanceof WP_Error,'disabling reviewed contract blocks proof');
echo "SITE_PROOF_REGRESSION=PASS\n";
