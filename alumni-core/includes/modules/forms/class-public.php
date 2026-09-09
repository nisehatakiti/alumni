<?php
namespace AlumniCore\Includes\Modules\Forms;
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Public rendering and submission. Depends only on this Forms module's API
 * plus WordPress APIs, keeping extraction into a standalone plugin possible.
 */
class Public_Form {
	const ACTION = 'alumni_core_submit_form';
	const NONCE_ACTION = 'alumni_core_submit_form';

	public static function register() {
		add_filter('the_content', array(__CLASS__,'render_single_form'),20);
		add_action('admin_post_nopriv_'.self::ACTION,array(__CLASS__,'handle_submit'));
		add_action('admin_post_'.self::ACTION,array(__CLASS__,'handle_submit'));
		add_shortcode('alumni_form',array(__CLASS__,'render_shortcode'));
	}
	public static function render_single_form($content) {
		if(!is_singular(Post_Type::SLUG)||!in_the_loop()||!is_main_query()) return $content;
		return self::render_form(get_the_ID());
	}
	public static function render_shortcode($atts) {
		$atts=shortcode_atts(array('id'=>0),$atts,'alumni_form');
		return self::render_form(absint($atts['id']));
	}
	public static function render_form($form_id) {
		if(Post_Type::SLUG!==get_post_type($form_id)||'publish'!==get_post_status($form_id)) return '';
		$description=Post_Type::get_description($form_id);
		$fields=Post_Type::get_fields($form_id);
		usort($fields,function($a,$b){$o=(int)($a['sort_order']??0)<=>(int)($b['sort_order']??0);return $o?:strcmp((string)($a['id']??''),(string)($b['id']??''));});
		$submitted=isset($_GET['alumni_form_submitted'])&&'1'===(string)wp_unslash($_GET['alumni_form_submitted']);
		$error=isset($_GET['alumni_form_error'])?sanitize_key(wp_unslash($_GET['alumni_form_error'])):'';
		ob_start(); ?>
		<div class="alumni-form">
			<?php if($description): ?><div class="alumni-form-description"><?php echo wpautop(esc_html($description)); ?></div><?php endif; ?>
			<?php if($submitted): ?><div class="alumni-form-notice alumni-form-success" role="status"><?php echo esc_html(Post_Type::get_success_message($form_id)); ?></div><?php return ob_get_clean(); endif; ?>
			<?php if($error): ?><div class="alumni-form-notice alumni-form-error" role="alert"><?php echo esc_html(self::error_message($error)); ?></div><?php endif; ?>
			<form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="alumni-form-form">
				<input type="hidden" name="action" value="<?php echo esc_attr(self::ACTION); ?>" />
				<input type="hidden" name="form_id" value="<?php echo esc_attr($form_id); ?>" />
				<?php wp_nonce_field(self::NONCE_ACTION.':'.$form_id,'alumni_form_nonce'); ?>
				<div class="alumni-form-hp" aria-hidden="true"><label>Website<input type="text" name="alumni_form_website" tabindex="-1" autocomplete="off" /></label></div>
				<div class="alumni-form-fields">
				<?php
					$row = array();
					foreach ( $fields as $field ) {
						if ( $row && self::row_width_total( $row ) + self::field_width( $field ) > 100 ) {
							self::render_row( $row );
							$row = array();
						}
						$row[] = $field;
						if ( ! empty( $field['row_end'] ) || self::row_width_total( $row ) >= 100 ) {
							self::render_row( $row );
							$row = array();
						}
					}
					if ( $row ) self::render_row( $row );
				?>
				</div>
				<p class="alumni-form-actions"><button type="submit"><?php esc_html_e('送信する','alumni-core'); ?></button></p>
			</form>
		</div>
		<style>.alumni-form-row{display:flex;flex-wrap:wrap;gap:1rem;margin:0 0 1.25rem}.alumni-form-field{width:calc(var(--alumni-form-width) - (1rem * var(--alumni-form-gap-share) / var(--alumni-form-row-count)));box-sizing:border-box;margin:0;min-width:0}.alumni-form-field label{display:block;font-weight:600}.alumni-form-field input:not([type=checkbox]):not([type=radio]),.alumni-form-field select,.alumni-form-field textarea{width:100%;box-sizing:border-box}.alumni-form-field input[type=file]{padding:.4rem}.alumni-form-required{color:#b42318}.alumni-form-help{font-size:.9em}.alumni-form-hp{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden}.alumni-form-notice{padding:1rem;margin:1rem 0}.alumni-form-success{background:#edf7ed}.alumni-form-error{background:#fff0f0}@media(max-width:640px){.alumni-form-row{display:block;margin-bottom:0}.alumni-form-field{width:100%!important;margin:0 0 1.25rem}}</style>
		<?php return ob_get_clean();
	}
	private static function render_row($fields) {
		echo '<div class="alumni-form-row" style="--alumni-form-row-count:' . max(1, count($fields)) . ';">';
		foreach ($fields as $field) self::render_field($field, count($fields));
		echo '</div>';
	}
	private static function row_width_total($fields) {
		$total = 0;
		foreach ((array)$fields as $field) $total += self::field_width($field);
		return $total;
	}
	private static function field_width($field) {
		$width = isset($field['width']) ? (int)$field['width'] : 100;
		return in_array($width, array(25,33,50,66,75,100), true) ? $width : 100;
	}
	private static function render_field($field, $row_count = 1) {
		$key='alumni_form_field['.$field['key'].']'; $file_key='alumni_form_file['.$field['key'].']'; $id='alumni-form-'.$field['id']; $required=!empty($field['required']); $type=$field['type'];
		$options=array_filter(array_map('trim',preg_split('/\r\n|\r|\n/',(string)($field['options']??''))));
		$min=self::field_min_length($field); $max=self::field_max_length($field); $length=self::length_attributes($min,$max); ?>
		<div class="alumni-form-field alumni-form-field-<?php echo esc_attr($type); ?>" style="--alumni-form-width:<?php echo esc_attr(self::field_width($field)); ?>%;--alumni-form-gap-share:<?php echo esc_attr(max(0, $row_count - 1)); ?>;">
			<?php if('checkbox'!==$type): ?><label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($field['label']); ?><?php if($required): ?><span class="alumni-form-required"> <?php esc_html_e('必須','alumni-core'); ?></span><?php endif; ?></label><?php endif; ?>
			<?php if(!empty($field['help_text'])): ?><div class="alumni-form-help"><?php echo esc_html($field['help_text']); ?></div><?php endif; ?>
			<?php if('textarea'===$type): ?><textarea id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($key); ?>" placeholder="<?php echo esc_attr($field['placeholder']); ?>"<?php echo $length; ?><?php echo $required?' required':''; ?>></textarea>
			<?php elseif('select'===$type): ?><select id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($key); ?>"<?php echo $required?' required':''; ?>><option value=""><?php esc_html_e('選択してください','alumni-core'); ?></option><?php foreach($options as $option): ?><option value="<?php echo esc_attr($option); ?>"><?php echo esc_html($option); ?></option><?php endforeach; ?></select>
			<?php elseif('radio'===$type): foreach($options as $n=>$option): ?><label><input type="radio" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($option); ?>"<?php echo $required&&0===$n?' required':''; ?> /> <?php echo esc_html($option); ?></label><?php endforeach;
			elseif('checkbox'===$type): ?><label><input id="<?php echo esc_attr($id); ?>" type="checkbox" name="<?php echo esc_attr($key); ?>" value="1"<?php echo $required?' required':''; ?> /> <?php echo esc_html($field['label']); ?><?php if($required): ?><span class="alumni-form-required"> <?php esc_html_e('必須','alumni-core'); ?></span><?php endif; ?></label>
			<?php elseif('file'===$type): $accept=self::file_accept_attribute($field); ?><input id="<?php echo esc_attr($id); ?>" type="file" name="<?php echo esc_attr($file_key); ?>"<?php echo $accept?' accept="'.esc_attr($accept).'"':''; ?><?php echo $required?' required':''; ?> /><?php if(self::field_max_file_size_mb($field)): ?><div class="alumni-form-help"><?php echo esc_html(sprintf(__('最大 %d MB','alumni-core'),self::field_max_file_size_mb($field))); ?></div><?php endif; ?>
			<?php else: ?><input id="<?php echo esc_attr($id); ?>" type="<?php echo esc_attr(in_array($type,array('email','tel','number'),true)?$type:'text'); ?>" name="<?php echo esc_attr($key); ?>" placeholder="<?php echo esc_attr($field['placeholder']); ?>"<?php echo $length; ?><?php echo $required?' required':''; ?> /><?php endif; ?>
		</div><?php
	}
	public static function handle_submit() {
		$form_id=isset($_POST['form_id'])?absint($_POST['form_id']):0; $redirect=$form_id?get_permalink($form_id):home_url('/');
		if(!$form_id||Post_Type::SLUG!==get_post_type($form_id)||'publish'!==get_post_status($form_id)) self::redirect($redirect,'invalid');
		if(!isset($_POST['alumni_form_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['alumni_form_nonce'])),self::NONCE_ACTION.':'.$form_id)) self::redirect($redirect,'security');
		if(!empty($_POST['alumni_form_website'])) self::redirect($redirect,'invalid');
		$recipient=Post_Type::get_recipient_email($form_id); if(!$recipient||!is_email($recipient)) self::redirect($redirect,'invalid');
		$input=isset($_POST['alumni_form_field'])&&is_array($_POST['alumni_form_field'])?wp_unslash($_POST['alumni_form_field']):array();
		$values=array(); $attachments=array(); $temporary=array(); $errors=false; $error_code='validation'; $email_values=array();
		foreach(Post_Type::get_fields($form_id) as $field) {
			$key=$field['key'];
			if('file'===$field['type']) {
				$file=self::normalize_uploaded_file($key); $has=!empty($file['name'])&&UPLOAD_ERR_NO_FILE!==(int)$file['error'];
				if(!$has){if(!empty($field['required']))$errors=true; continue;}
				$valid=self::validate_uploaded_file($file,$field);
				if(is_wp_error($valid)){ $errors=true;$error_code='file';continue; }
				$uploaded=self::store_upload($file,$valid['mimes']);
				if(is_wp_error($uploaded)){ $errors=true;$error_code='file';continue; }
				$attachments[]=$uploaded['file'];$temporary[]=$uploaded['file'];
				$values[]=array('label'=>$field['label'],'value'=>sanitize_file_name(basename($uploaded['file'])));continue;
			}
			$raw=isset($input[$key])?$input[$key]:''; if(is_array($raw)){$errors=true;continue;} $raw=is_string($raw)?trim($raw):'';
			if(!empty($field['required'])&&''===$raw){$errors=true;continue;}
			if(''!==$raw){
				$len=self::string_length($raw);$min=self::field_min_length($field);$max=self::field_max_length($field);
				if(($min&&$len<$min)||($max&&$len>$max)){$errors=true;continue;}
				if('email'===$field['type']&&!is_email($raw)){$errors=true;continue;}
				if('number'===$field['type']&&!is_numeric($raw)){$errors=true;continue;}
				$options=array_filter(array_map('trim',preg_split('/\r\n|\r|\n/',(string)($field['options']??''))));
				if(in_array($field['type'],array('select','radio'),true)&&!in_array($raw,$options,true)){$errors=true;continue;}
			}
			if('textarea'===$field['type'])$value=sanitize_textarea_field($raw); elseif('email'===$field['type'])$value=sanitize_email($raw); elseif('checkbox'===$field['type'])$value='1'===$raw?'1':''; else $value=sanitize_text_field($raw);
			$values[]=array('label'=>$field['label'],'value'=>$value); if('email'===$field['type']&&is_email($value))$email_values[$key]=$value;
		}
		if($errors){self::cleanup_files($temporary);self::redirect($redirect,$error_code);}
		$lines=array(get_the_title($form_id),'',__('送信日時: ','alumni-core').current_time('Y-m-d H:i:s')); foreach($values as $row)$lines[]=$row['label'].': '.$row['value'];
		$reply_to=self::resolve_reply_to($form_id,$email_values);
		$headers=self::mail_headers($form_id,$reply_to);
		$sent=wp_mail($recipient,Post_Type::get_mail_subject($form_id),implode("\n",$lines),$headers,$attachments); self::cleanup_files($temporary);
		if(!$sent)self::redirect($redirect,'mail');
		if(Post_Type::is_auto_reply_enabled($form_id)&&$reply_to)wp_mail($reply_to,Post_Type::get_mail_subject($form_id),Post_Type::get_success_message($form_id),self::mail_headers($form_id,''));
		wp_safe_redirect(add_query_arg('alumni_form_submitted','1',$redirect));exit;
	}
	private static function resolve_reply_to($form_id,$email_values){
		$mode=Post_Type::get_reply_to_mode($form_id);
		if('none'===$mode)return '';
		if('fixed'===$mode){$email=Post_Type::get_reply_to_email($form_id);return is_email($email)?$email:'';}
		$key=Post_Type::get_reply_to_field($form_id);
		if($key&&isset($email_values[$key])&&is_email($email_values[$key]))return $email_values[$key];
		foreach($email_values as $email)if(is_email($email))return $email;
		return '';
	}
	private static function mail_headers($form_id,$reply_to=''){
		$headers=array('Content-Type: text/plain; charset=UTF-8');
		$from_email=Post_Type::get_from_email($form_id); $from_name=Post_Type::get_from_name($form_id);
		if($from_email&&is_email($from_email)){$from_name=$from_name?str_replace(array("\r","\n"),'', $from_name):'';$headers[]='From: '.($from_name?wp_specialchars_decode($from_name,ENT_QUOTES).' ':'').'<'.$from_email.'>';}
		if($reply_to&&is_email($reply_to))$headers[]='Reply-To: '.$reply_to;
		return $headers;
	}
	private static function normalize_uploaded_file($key){if(empty($_FILES['alumni_form_file'])||!is_array($_FILES['alumni_form_file']))return array('name'=>'','error'=>UPLOAD_ERR_NO_FILE);$f=$_FILES['alumni_form_file'];return array('name'=>(string)($f['name'][$key]??''),'type'=>(string)($f['type'][$key]??''),'tmp_name'=>(string)($f['tmp_name'][$key]??''),'error'=>(int)($f['error'][$key]??UPLOAD_ERR_NO_FILE),'size'=>(int)($f['size'][$key]??0));}
	private static function validate_uploaded_file($file,$field){if(UPLOAD_ERR_OK!==(int)$file['error'])return new \WP_Error('upload_error','Upload failed.');if(empty($file['tmp_name'])||!is_uploaded_file($file['tmp_name']))return new \WP_Error('upload_invalid','Invalid upload.');$max=self::field_max_file_size_mb($field)*1024*1024;if($max&&(int)$file['size']>$max)return new \WP_Error('file_size','File is too large.');$ext=self::allowed_extensions($field);$mimes=self::allowed_mimes($ext);if(empty($mimes))return new \WP_Error('file_type','No allowed file types.');$checked=wp_check_filetype_and_ext($file['tmp_name'],$file['name'],$mimes);if(empty($checked['ext'])||empty($checked['type'])||!in_array(strtolower($checked['ext']),$ext,true))return new \WP_Error('file_type','File type is not allowed.');return array('mimes'=>$mimes);}
	private static function store_upload($file,$mimes){require_once ABSPATH.'wp-admin/includes/file.php';$r=wp_handle_upload($file,array('test_form'=>false,'mimes'=>$mimes));if(isset($r['error']))return new \WP_Error('upload_error',$r['error']);if(empty($r['file']))return new \WP_Error('upload_error','Upload failed.');return $r;}
	private static function allowed_extensions($field){$parts=preg_split('/[\s,]+/',strtolower((string)($field['allowed_extensions']??'')));$parts=array_filter(array_map(function($x){return preg_replace('/[^a-z0-9]/','',$x);},$parts));return array_values(array_unique($parts));}
	private static function allowed_mimes($extensions){$all=wp_get_mime_types();$allowed=array();foreach($all as $pattern=>$mime)foreach(preg_split('/\|/',$pattern) as $ext)if(in_array(strtolower($ext),$extensions,true))$allowed[$pattern]=$mime;return $allowed;}
	private static function file_accept_attribute($field){$ext=self::allowed_extensions($field);return $ext?implode(',',array_map(function($x){return '.'.$x;},$ext)):'';}
	private static function field_max_file_size_mb($field){return max(1,isset($field['max_file_size_mb'])?absint($field['max_file_size_mb']):5);}
	private static function field_min_length($field){return isset($field['min_length'])?max(0,absint($field['min_length'])):0;}
	private static function field_max_length($field){$max=isset($field['max_length'])?max(0,absint($field['max_length'])):0;$min=self::field_min_length($field);return $max&&$max<$min?$min:$max;}
	private static function length_attributes($min,$max){$s='';if($min)$s.=' minlength="'.esc_attr($min).'"';if($max)$s.=' maxlength="'.esc_attr($max).'"';return $s;}
	private static function string_length($value){return function_exists('mb_strlen')?mb_strlen($value):strlen($value);}
	private static function cleanup_files($files){foreach($files as $file)if(is_string($file)&&$file&&file_exists($file))@unlink($file);}
	private static function error_message($error){$map=array('file'=>__('添付ファイルを確認して、もう一度送信してください。','alumni-core'),'mail'=>__('メール送信に失敗しました。時間をおいてもう一度お試しください。','alumni-core'),'security'=>__('送信を確認できませんでした。もう一度お試しください。','alumni-core'));return $map[$error]??__('入力内容を確認して、もう一度送信してください。','alumni-core');}
	private static function redirect($url,$error){wp_safe_redirect(add_query_arg('alumni_form_error',sanitize_key($error),$url));exit;}
}
