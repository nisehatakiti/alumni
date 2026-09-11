<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class HATAKITI_Form_Public {
	const ACTION='hatakiti_form_submit';
	public static function register(){add_shortcode('hatakiti_form',array(__CLASS__,'shortcode'));add_action('admin_post_nopriv_'.self::ACTION,array(__CLASS__,'submit'));add_action('admin_post_'.self::ACTION,array(__CLASS__,'submit'));}
	public static function shortcode($a){$a=shortcode_atts(array('id'=>0),$a);$id=absint($a['id']);if(HATAKITI_Form_Post_Type::SLUG!==get_post_type($id)||'publish'!==get_post_status($id))return '';ob_start();?>
	<form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php'));?>" class="hatakiti-form">
	<input type="hidden" name="action" value="<?php echo self::ACTION;?>"><input type="hidden" name="form_id" value="<?php echo $id;?>"><?php wp_nonce_field(self::ACTION.':'.$id,'hatakiti_form_nonce');?>
	<?php foreach(HATAKITI_Form_Post_Type::fields($id) as $f):$name='hatakiti_field['.$f['key'].']';?><p><label><?php echo esc_html($f['label']);?><?php if($f['required'])echo ' *';?><br><?php if($f['type']==='textarea'):?><textarea name="<?php echo esc_attr($name);?>" <?php echo $f['required']?'required':'';?>></textarea><?php elseif($f['type']==='file'):?><input type="file" name="hatakiti_file[<?php echo esc_attr($f['key']);?>]" <?php echo $f['required']?'required':'';?>><?php else:?><input type="<?php echo esc_attr($f['type']==='date'||$f['type']==='email'?$f['type']:'text');?>" name="<?php echo esc_attr($name);?>" <?php echo $f['required']?'required':'';?>><?php endif;?></label></p><?php endforeach;?>
	<button type="submit">送信する</button></form><?php return ob_get_clean();}
	public static function submit(){
		$id=absint($_POST['form_id']??0);$redirect=$id?get_permalink($id):home_url('/');if(!$id||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['hatakiti_form_nonce']??'')),self::ACTION.':'.$id))wp_die('Invalid request');
		$input=isset($_POST['hatakiti_field'])&&is_array($_POST['hatakiti_field'])?wp_unslash($_POST['hatakiti_field']):array();$values=array();
		foreach(HATAKITI_Form_Post_Type::fields($id) as $f){$v=$input[$f['key']]??'';if($f['type']==='textarea')$v=sanitize_textarea_field($v);elseif($f['type']==='email')$v=sanitize_email($v);else $v=sanitize_text_field($v);if($f['required']&&$v==='')wp_safe_redirect(add_query_arg('hatakiti_form_error','required',$redirect));$values[$f['key']]=$v;}
		if(HATAKITI_Form_Post_Type::mode($id)==='post_submission'){$schema=HATAKITI_Form_Schema_Registry::get(HATAKITI_Form_Post_Type::schema($id));if(!$schema)wp_die('Schema not available');$map=$schema['map'];$args=array('post_type'=>$schema['post_type'],'post_status'=>'draft','post_title'=>sanitize_text_field($values[$map['title']]??get_the_title($id)),'post_content'=>wp_kses_post($values[$map['content']]??''));$new=wp_insert_post($args,true);if(is_wp_error($new))wp_die($new->get_error_message());foreach(($map['meta']??array()) as $meta=>$key)update_post_meta($new,$meta,$values[$key]??'');do_action('hatakiti_form_created_post',$new,$id,$values,$schema);}
		else {self::notify($id,$values);}
		wp_safe_redirect(add_query_arg('hatakiti_form_submitted','1',$redirect));exit;
	}
	private static function notify($id,$values){$to=preg_split('/[\r\n,;]+/',(string)get_post_meta($id,HATAKITI_Form_Post_Type::META_RECIPIENTS,true));$to=array_filter(array_map('sanitize_email',$to));if(!$to)return;$lines=array();foreach(HATAKITI_Form_Post_Type::fields($id) as $f)$lines[]=$f['label'].': '.($values[$f['key']]??'');wp_mail($to,'['.get_bloginfo('name').'] '.get_the_title($id),implode("\n",$lines));}
}
