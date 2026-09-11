<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class HATAKITI_Form_Post_Type {
	const SLUG='hatakiti_form';
	const META_FIELDS='_hatakiti_form_fields';
	const META_MODE='_hatakiti_form_mode';
	const META_SCHEMA='_hatakiti_form_schema';
	const META_SUCCESS='_hatakiti_form_success';
	const META_RECIPIENTS='_hatakiti_form_recipients';
	public static function register(){
		register_post_type(self::SLUG,array(
			'labels'=>array('name'=>'フォーム','singular_name'=>'フォーム','menu_name'=>'フォーム','all_items'=>'フォーム一覧','add_new'=>'新規追加','add_new_item'=>'新規フォームを追加','edit_item'=>'フォームを編集'),
			'public'=>true,'show_ui'=>true,'show_in_menu'=>true,'supports'=>array('title'),'has_archive'=>false,'rewrite'=>array('slug'=>'form','with_front'=>false)
		));
	}
	public static function fields($id){$v=get_post_meta($id,self::META_FIELDS,true);return is_array($v)?$v:array();}
	public static function mode($id){$v=get_post_meta($id,self::META_MODE,true);return in_array($v,array('standard','post_submission'),true)?$v:'standard';}
	public static function schema($id){return sanitize_key((string)get_post_meta($id,self::META_SCHEMA,true));}
}
