<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class HATAKITI_Form_Admin {
	const NONCE='hatakiti_form_meta_nonce';
	public static function register(){
		add_action('add_meta_boxes',array(__CLASS__,'meta_box'));
		add_action('save_post_'.HATAKITI_Form_Post_Type::SLUG,array(__CLASS__,'save'),10,2);
	}
	public static function meta_box(){add_meta_box('hatakiti-form-settings','フォーム設定',array(__CLASS__,'render'),'hatakiti_form','normal','high');}
	public static function render($post){
		wp_nonce_field(self::NONCE,self::NONCE);
		$mode=HATAKITI_Form_Post_Type::mode($post->ID);$schema=HATAKITI_Form_Post_Type::schema($post->ID);$fields=HATAKITI_Form_Post_Type::fields($post->ID);$schemas=HATAKITI_Form_Schema_Registry::all();
		?>
		<p><strong>フォーム用途</strong></p>
		<label><input type="radio" name="hatakiti_form_mode" value="standard" <?php checked($mode,'standard');?>> 通常フォーム（通知・受付）</label><br>
		<label><input type="radio" name="hatakiti_form_mode" value="post_submission" <?php checked($mode,'post_submission');?>> 投稿作成フォーム（送信内容から下書きを作成）</label>
		<div id="hatakiti-schema-wrap" style="margin-top:15px;">
			<p><label><strong>投稿スキーマ</strong><br><select name="hatakiti_form_schema" id="hatakiti_form_schema"><option value="">選択してください</option><?php foreach($schemas as $key=>$item):?><option value="<?php echo esc_attr($key);?>" <?php selected($schema,$key);?>><?php echo esc_html($item['label']);?></option><?php endforeach;?></select></label></p>
			<p><button type="button" class="button" id="hatakiti-apply-schema">スキーマから項目を生成</button> <span class="description">投稿先プラグインが提供する入力定義からフォーム項目を自動生成します。</span></p>
		</div>
		<p><label>通知先メールアドレス（複数可）<br><input class="large-text" type="text" name="hatakiti_form_recipients" value="<?php echo esc_attr(get_post_meta($post->ID,HATAKITI_Form_Post_Type::META_RECIPIENTS,true));?>"></label></p>
		<p><label>送信完了メッセージ<br><textarea class="large-text" rows="2" name="hatakiti_form_success"><?php echo esc_textarea(get_post_meta($post->ID,HATAKITI_Form_Post_Type::META_SUCCESS,true));?></textarea></label></p>
		<h3>入力項目</h3><div id="hatakiti-fields"><?php foreach($fields as $i=>$f)self::field($i,$f);?></div>
		<p><button type="button" class="button" id="hatakiti-add-field">項目を追加</button></p>
		<template id="hatakiti-field-template"><?php self::field('__INDEX__',array());?></template>
		<script>
		(function(){const wrap=document.getElementById('hatakiti-fields'),tpl=document.getElementById('hatakiti-field-template');
		document.getElementById('hatakiti-add-field').onclick=()=>wrap.insertAdjacentHTML('beforeend',tpl.innerHTML.replace(/__INDEX__/g,Date.now()));
		wrap.addEventListener('click',e=>{if(e.target.classList.contains('hatakiti-remove'))e.target.closest('.hatakiti-field').remove();});
		const schemas=<?php echo wp_json_encode(array_map(function($s){return $s['fields'];},$schemas));?>;
		document.getElementById('hatakiti-apply-schema').onclick=()=>{const key=document.getElementById('hatakiti_form_schema').value;if(!schemas[key])return;wrap.innerHTML='';schemas[key].forEach((f,n)=>{let h=tpl.innerHTML.replace(/__INDEX__/g,'s_'+Date.now()+'_'+n);let d=document.createElement('div');d.innerHTML=h;let r=d.firstElementChild;r.querySelector('[name$="[label]"]').value=f.label||'';r.querySelector('[name$="[key]"]').value=f.key||'';r.querySelector('[name$="[type]"]').value=f.type||'text';r.querySelector('[name$="[required]"]').checked=!!f.required;wrap.appendChild(r);});};
		})();</script>
		<?php
	}
	private static function field($i,$f){$f=wp_parse_args($f,array('key'=>'','label'=>'','type'=>'text','required'=>false));?>
	<div class="hatakiti-field" style="border:1px solid #ddd;padding:12px;margin:8px 0;display:flex;gap:8px;align-items:end;flex-wrap:wrap">
	<label>ラベル<br><input name="hatakiti_form_fields[<?php echo esc_attr($i);?>][label]" value="<?php echo esc_attr($f['label']);?>"></label>
	<label>キー<br><input name="hatakiti_form_fields[<?php echo esc_attr($i);?>][key]" value="<?php echo esc_attr($f['key']);?>"></label>
	<label>種別<br><select name="hatakiti_form_fields[<?php echo esc_attr($i);?>][type]"><?php foreach(array('text'=>'テキスト','textarea'=>'複数行','email'=>'メール','date'=>'年月日','file'=>'ファイル') as $k=>$v):?><option value="<?php echo esc_attr($k);?>" <?php selected($f['type'],$k);?>><?php echo esc_html($v);?></option><?php endforeach;?></select></label>
	<label><input type="checkbox" name="hatakiti_form_fields[<?php echo esc_attr($i);?>][required]" value="1" <?php checked(!empty($f['required']));?>> 必須</label>
	<button type="button" class="button-link-delete hatakiti-remove">削除</button>
	</div><?php }
	public static function save($id,$post){
		if(!isset($_POST[self::NONCE])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE])),self::NONCE)||!current_user_can('edit_post',$id))return;
		$mode=isset($_POST['hatakiti_form_mode'])?sanitize_key(wp_unslash($_POST['hatakiti_form_mode'])):'standard';
		$schema=isset($_POST['hatakiti_form_schema'])?sanitize_key(wp_unslash($_POST['hatakiti_form_schema'])):'';
		if('post_submission'===$mode&&!HATAKITI_Form_Schema_Registry::get($schema)){$mode='standard';$schema='';}
		update_post_meta($id,HATAKITI_Form_Post_Type::META_MODE,$mode);update_post_meta($id,HATAKITI_Form_Post_Type::META_SCHEMA,$schema);
		update_post_meta($id,HATAKITI_Form_Post_Type::META_RECIPIENTS,sanitize_text_field(wp_unslash($_POST['hatakiti_form_recipients']??'')));
		update_post_meta($id,HATAKITI_Form_Post_Type::META_SUCCESS,sanitize_textarea_field(wp_unslash($_POST['hatakiti_form_success']??'送信が完了しました。')));
		$rows=isset($_POST['hatakiti_form_fields'])&&is_array($_POST['hatakiti_form_fields'])?wp_unslash($_POST['hatakiti_form_fields']):array();$fields=array();$used=array();
		foreach($rows as $r){$label=sanitize_text_field($r['label']??'');if(!$label)continue;$key=sanitize_key($r['key']??'');if(!$key)$key='field_'.(count($fields)+1);if(isset($used[$key]))$key.='_'.(count($fields)+1);$used[$key]=1;$type=in_array($r['type']??'',array('text','textarea','email','date','file'),true)?$r['type']:'text';$fields[]=array('key'=>$key,'label'=>$label,'type'=>$type,'required'=>!empty($r['required']));}
		update_post_meta($id,HATAKITI_Form_Post_Type::META_FIELDS,$fields);
	}
}
