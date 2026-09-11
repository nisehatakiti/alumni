<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class HATAKITI_Form_Post_Type {
 const SLUG='hatakiti_form';
 const META_FIELDS='_hatakiti_form_fields';
 const META_MODE='_hatakiti_form_mode';
 const META_SCHEMA='_hatakiti_form_schema';
 const META_DESCRIPTION='_hatakiti_form_description';
 const META_RECIPIENTS='_hatakiti_form_recipients';
 const META_MAIL_SUBJECT='_hatakiti_form_mail_subject';
 const META_SUCCESS='_hatakiti_form_success';
 const META_AUTO_REPLY='_hatakiti_form_auto_reply_enabled';
 const META_FROM_NAME='_hatakiti_form_from_name';
 const META_FROM_EMAIL='_hatakiti_form_from_email';
 const META_REPLY_TO_MODE='_hatakiti_form_reply_to_mode';
 const META_REPLY_TO_EMAIL='_hatakiti_form_reply_to_email';
 const META_REPLY_TO_FIELD='_hatakiti_form_reply_to_field';
 public static function register(){
  register_post_type(self::SLUG,array(
   'labels'=>array('name'=>'フォーム','singular_name'=>'フォーム','menu_name'=>'フォーム','all_items'=>'フォーム一覧','add_new'=>'新規追加','add_new_item'=>'新規フォームを追加','edit_item'=>'フォームを編集','view_item'=>'フォームを表示','search_items'=>'フォームを検索'),
   'public'=>true,'show_ui'=>true,'show_in_menu'=>true,'supports'=>array('title'),'has_archive'=>false,'rewrite'=>array('slug'=>'form','with_front'=>false)
  ));
 }
 public static function fields($id){$v=get_post_meta($id,self::META_FIELDS,true);return is_array($v)?$v:array();}
 public static function mode($id){$v=sanitize_key((string)get_post_meta($id,self::META_MODE,true));return in_array($v,array('standard','post_submission'),true)?$v:'standard';}
 public static function schema($id){return sanitize_key((string)get_post_meta($id,self::META_SCHEMA,true));}
 public static function description($id){return (string)get_post_meta($id,self::META_DESCRIPTION,true);}
 public static function recipients($id){$parts=preg_split('/[\r\n,;]+/',(string)get_post_meta($id,self::META_RECIPIENTS,true));$out=array();foreach((array)$parts as $v){$v=sanitize_email(trim($v));if($v&&is_email($v))$out[]=$v;}return array_values(array_unique($out));}
 public static function mail_subject($id){$v=sanitize_text_field((string)get_post_meta($id,self::META_MAIL_SUBJECT,true));return $v?$v:'['.get_bloginfo('name').'] '.get_the_title($id);}
 public static function success($id){$v=(string)get_post_meta($id,self::META_SUCCESS,true);return $v?$v:'送信が完了しました。ありがとうございました。';}
 public static function auto_reply($id){return '1'===(string)get_post_meta($id,self::META_AUTO_REPLY,true);}
 public static function from_name($id){return sanitize_text_field((string)get_post_meta($id,self::META_FROM_NAME,true));}
 public static function from_email($id){$v=sanitize_email((string)get_post_meta($id,self::META_FROM_EMAIL,true));return is_email($v)?$v:'';}
 public static function reply_mode($id){$v=sanitize_key((string)get_post_meta($id,self::META_REPLY_TO_MODE,true));return in_array($v,array('none','fixed','form_field'),true)?$v:'none';}
 public static function reply_email($id){$v=sanitize_email((string)get_post_meta($id,self::META_REPLY_TO_EMAIL,true));return is_email($v)?$v:'';}
 public static function reply_field($id){return sanitize_key((string)get_post_meta($id,self::META_REPLY_TO_FIELD,true));}
}