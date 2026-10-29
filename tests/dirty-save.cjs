'use strict';
const vm=require('node:vm'),fs=require('node:fs'),path=require('node:path'),assert=require('node:assert/strict');
const source=fs.readFileSync(path.join(__dirname,'../assets/editor-native.js'),'utf8');
function makeHarness(initial,fields){
 const values={...initial};
 let click=null,lastStatus='',ajaxCount=0,lastPayload=null;
 const doc={querySelector(){return {offsetWidth:100};}};
 function $(selector){
  if(typeof selector==='function'){selector();return;}
  return {
   length:1,first(){return this;},find(){return this;},append(){return this;},
   text(t){if(t!==undefined)lastStatus=t;return this;},
   toggleClass(){return this;},prop(){return this;},
   on(event,selector,cb){if(event==='click'&&selector.includes('heae_acf_save_action'))click=cb;return this;}
  };
 }
 $.ajax=function(args){
  ajaxCount++;lastPayload=args.data.acf;
  return {done(cb){cb({success:true,data:{message:'Saved'}});return this;},
   fail(){return this;},always(cb){cb();return this;}};
 };
 const win={HEAE:{postId:654,nonce:'local-only',fields,ajax:'/wp-admin/admin-ajax.php',
   saving:'Saving',saved:'Saved',error:'Error'},
   elementor:{settings:{page:{model:{get(key){return values[key];}}}}},
   setInterval(callback){callback();return 1;},clearInterval(){}
 };
 const sandbox={window:win,document:doc,jQuery:$,console:{warn(){}}};
 sandbox.HEAE=win.HEAE;sandbox.elementor=win.elementor;
 vm.runInNewContext(source,sandbox);
 assert.equal(typeof click,'function');
 return {values,click(){click({preventDefault(){},stopPropagation(){}});},get count(){return ajaxCount;},get payload(){return lastPayload;},get status(){return lastStatus;}};
}
const id=n=>'heae_acf_field_'+n,field=(n,type,extras={})=>({key:'field_'+n,id:id(n),type,editable:true,...extras});
const fields=[
 field('headline','text'),
 field('secret','password'),
 field('clock','time_picker'),
 field('calendar','date_picker'),
 field('meeting','date_time_picker'),
 field('map','google_map',{original:{lat:31.5204,lng:74.3587,address:'Lahore',zoom:12}})
];
const values={
 [id('headline')]:'Unchanged',
 [id('secret')]:'null', // reproduce browser/ACF masked field state
 [id('clock')]:'14:30',
 [id('calendar')]:'2026-10-30',
 [id('meeting')]:'2026-10-30 14:30',
 [id('map')]:[{heae_map_address:'Lahore',heae_map_lat:31.5204,heae_map_lng:74.3587,heae_map_zoom:12}]
};
const h=makeHarness(values,fields);
h.click();
assert.equal(h.count,0,'No-op must not submit any ACF values');
assert.match(h.status,/no acf changes/i);
console.log('PASS=NOOP_DOES_NOT_POST_PASSWORD_TIME_OR_MAP');

h.values[id('headline')]='Changed by user';
h.click();
assert.equal(h.count,1);
assert.deepEqual(Object.keys(h.payload),['field_headline']);
assert.equal(h.payload.field_headline,'Changed by user');
console.log('PASS=ONLY_DIRTY_FIELD_SAVED');
h.click();assert.equal(h.count,1,'After successful Save, second click must be a no-op');
console.log('PASS=SUCCESS_UPDATES_DIRTY_BASELINE');

h.values[id('clock')]='16:45';
h.values[id('calendar')]='2026-11-01';
h.values[id('meeting')]='2026-11-01 09:30';
h.click();
assert.equal(h.count,2);
assert.deepEqual(JSON.parse(JSON.stringify(h.payload)),{
 field_clock:'16:45:00',field_calendar:'20261101',field_meeting:'2026-11-01 09:30:00'
});
console.log('PASS=CHANGED_ACF_DATE_TIME_FORMATS');

h.values[id('map')]=[{heae_map_address:'Karachi',heae_map_lat:24.86,heae_map_lng:67.01,heae_map_zoom:12}];
h.click();
assert.equal(h.count,3);
assert.deepEqual(Object.keys(h.payload),['field_map']);
assert.equal(h.payload.field_map.address,'Karachi');
console.log('PASS=CHANGED_MAP_ONLY');
