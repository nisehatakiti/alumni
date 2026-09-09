<?php
/**
 * 汎用フォームの公開表示と送信処理.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes\Modules\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Public_Form {
	const ACTION = 'alumni_core_submit_form';
	const NONCE_ACTION = 'alumni_core_submit_form';

	public static function register() {
		add_filter( 'the_content', array( __CLASS__, 'render_single_form' ), 20 );
		add_action( 'admin_post_nopriv_' . self::ACTION, array( __CLASS__, 'handle_submit' ) );
		add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'handle_submit' ) );
		add_shortcode( 'alumni_form', array( __CLASS__, 'render_shortcode' ) );
	}

	public static function render_single_form( $content ) {
		if ( ! is_singular( Post_Type::SLUG ) || ! in_the_loop() || ! is_main_query() ) return $content;
		return self::render_form( get_the_ID() );
	}

	public static function render_shortcode( $atts ) {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'alumni_form' );
		return self::render_form( absint( $atts['id'] ) );
	}

	public static function render_form( $form_id ) {
		if ( Post_Type::SLUG !== get_post_type( $form_id ) || 'publish' !== get_post_status( $form_id ) ) return '';

		$description = Post_Type::get_description( $form_id );
		$fields = Post_Type::get_fields( $form_id );
		usort($fields,function($a,$b){$o=(int)($a['sort_order']??0)<=>(int)($b['sort_order']??0);return $o?:strcmp((string)($a['id']??''),(string)($b['id']??''));});

		$submitted = isset($_GET['alumni_form_submitted']) && '1' === (string) wp_unslash($_GET['alumni_form_submitted']);
		$error = isset($_GET['alumni_form_error']) ? sanitize_key(wp_unslash($_GET['alumni_form_error'])) : '';

		ob_start(); ?>
		<div class="alumni-form">
			<?php if($description): ?><div class="alumni-form-description"><?php echo wpautop(esc_html($description)); ?></div><?php endif; ?>
			<?php if($submitted): ?><div class="alumni-form-notice alumni-form-success" role="status"><?php echo esc_html(Post_Type::get_success_message($form_id)); ?></div><?php return ob_get_clean(); endif; ?>
			<?php if($error): ?><div class="alumni-form-notice alumni-form-error" role="alert"><?php esc_html_e('入力内容を確認して、もう一度送信してください。', 'alumni-core'); ?></div><?php endif; ?>
			<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="alumni-form-form">
				<input type="hidden" name="action" value="<?php echo esc_attr(self::ACTION); ?>" />
				<input type="hidden" name="form_id" value="<?php echo esc_attr($form_id); ?>" />
				<?php wp_nonce_field(self::NONCE_ACTION . ':' . $form_id, 'alumni_form_nonce'); ?>
				<div class="alumni-form-hp" aria-hidden="true"><label>Website<input type="text" name="alumni_form_website" tabindex="-1" autocomplete="off" /></label></div>
				<?php foreach($fields as $field): self::render_field($field); endforeach; ?>
				<p class="alumni-form-actions"><button type="submit"><?php esc_html_e('送信する', 'alumni-core'); ?></button></p>
			</form>
		</div>
		<style>.alumni-form-field{margin:0 0 1.25rem}.alumni-form-field label{display:block;font-weight:600}.alumni-form-field input:not([type=checkbox]):not([type=radio]),.alumni-form-field select,.alumni-form-field textarea{width:100%;box-sizing:border-box}.alumni-form-required{color:#b42318}.alumni-form-help{font-size:.9em}.alumni-form-hp{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden}.alumni-form-notice{padding:1rem;margin:1rem 0}.alumni-form-success{background:#edf7ed}.alumni-form-error{background:#fff0f0}</style>
		<?php return ob_get_clean();
	}

	private static function render_field( $field ) {
		$key='alumni_form_field['.$field['key'].']'; $id='alumni-form-'.$field['id']; $required=!empty($field['required']); $type=$field['type'];
		$options=array_filter(array_map('trim',preg_split('/\r\n|\r|\n/',(string)$field['options'])));
		?>
		<div class="alumni-form-field alumni-form-field-<?php echo esc_attr($type); ?>">
			<?php if('checkbox'!==$type): ?><label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($field['label']); ?><?php if($required): ?><span class="alumni-form-required"> <?php esc_html_e('必須','alumni-core'); ?></span><?php endif; ?></label><?php endif; ?>
			<?php if(!empty($field['help_text'])): ?><div class="alumni-form-help"><?php echo esc_html($field['help_text']); ?></div><?php endif; ?>
			<?php if('textarea'===$type): ?><textarea id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($key); ?>" placeholder="<?php echo esc_attr($field['placeholder']); ?>" <?php echo $required ? ' required' : ''; ?>></textarea>
			<?php elseif('select'===$type): ?><select id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($key); ?>" <?php echo $required ? ' required' : ''; ?>><option value=""><?php esc_html_e('選択してください','alumni-core'); ?></option><?php foreach($options as $option): ?><option value="<?php echo esc_attr($option); ?>"><?php echo esc_html($option); ?></option><?php endforeach; ?></select>
			<?php elseif('radio'===$type): foreach($options as $n=>$option): ?><label><input type="radio" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($option); ?>" <?php echo $required&&0===$n?'required':''; ?> /> <?php echo esc_html($option); ?></label><?php endforeach;
			elseif('checkbox'===$type): ?><label><input id="<?php echo esc_attr($id); ?>" type="checkbox" name="<?php echo esc_attr($key); ?>" value="1" <?php echo $required ? ' required' : ''; ?> /> <?php echo esc_html($field['label']); ?><?php if($required): ?><span class="alumni-form-required"> <?php esc_html_e('必須','alumni-core'); ?></span><?php endif; ?></label>
			<?php else: ?><input id="<?php echo esc_attr($id); ?>" type="<?php echo esc_attr(in_array($type,array('email','tel','number'),true)?$type:'text'); ?>" name="<?php echo esc_attr($key); ?>" placeholder="<?php echo esc_attr($field['placeholder']); ?>" <?php echo $required ? ' required' : ''; ?> /><?php endif; ?>
		</div>
		<?php
	}

	public static function handle_submit() {
		$form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;
		$redirect = $form_id ? get_permalink($form_id) : home_url('/');
		if(!$form_id || Post_Type::SLUG!==get_post_type($form_id) || 'publish'!==get_post_status($form_id)) self::redirect($redirect,'invalid');
		if(!isset($_POST['alumni_form_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['alumni_form_nonce'])),self::NONCE_ACTION.':'.$form_id)) self::redirect($redirect,'security');
		if(!empty($_POST['alumni_form_website'])) self::redirect($redirect,'invalid');

		$recipient=Post_Type::get_recipient_email($form_id);
		if(!$recipient || !is_email($recipient)) self::redirect($redirect,'invalid');

		$input=isset($_POST['alumni_form_field'])&&is_array($_POST['alumni_form_field'])?wp_unslash($_POST['alumni_form_field']):array();
		$values=array(); $errors=false; $reply_to='';
		foreach(Post_Type::get_fields($form_id) as $field){
			$key=$field['key']; $raw=isset($input[$key])?$input[$key]:'';
			if(is_array($raw)){$errors=true;continue;}
			$raw=is_string($raw)?trim($raw):'';
			if(!empty($field['required']) && ''===$raw){$errors=true;continue;}
			if(''!==$raw){
				if('email'===$field['type'] && !is_email($raw)){$errors=true;continue;}
				if('number'===$field['type'] && !is_numeric($raw)){$errors=true;continue;}
				$options=array_filter(array_map('trim',preg_split('/\r\n|\r|\n/',(string)$field['options'])));
				if(in_array($field['type'],array('select','radio'),true) && !in_array($raw,$options,true)){$errors=true;continue;}
			}
			if('textarea'===$field['type'])$value=sanitize_textarea_field($raw);
			elseif('email'===$field['type'])$value=sanitize_email($raw);
			elseif('checkbox'===$field['type'])$value='1'===$raw?'1':'';
			else $value=sanitize_text_field($raw);
			$values[] = array('label'=>$field['label'],'value'=>$value);
			if('email'===$field['type'] && is_email($value) && !$reply_to)$reply_to=$value;
		}
		if($errors) self::redirect($redirect,'validation');

		$lines=array(get_the_title($form_id),'',__('送信日時: ','alumni-core').current_time('Y-m-d H:i:s'));
		foreach($values as $row)$lines[]=$row['label'].': '.$row['value'];
		$headers=array('Content-Type: text/plain; charset=UTF-8');
		if($reply_to)$headers[]='Reply-To: '.$reply_to;
		$sent=wp_mail($recipient,Post_Type::get_mail_subject($form_id),implode("\n",$lines),$headers);
		if(!$sent) self::redirect($redirect,'mail');

		if(Post_Type::is_auto_reply_enabled($form_id) && $reply_to){
			wp_mail($reply_to,Post_Type::get_mail_subject($form_id),Post_Type::get_success_message($form_id),array('Content-Type: text/plain; charset=UTF-8'));
		}
		wp_safe_redirect(add_query_arg('alumni_form_submitted','1',$redirect)); exit;
	}

	private static function redirect($url,$error){wp_safe_redirect(add_query_arg('alumni_form_error',sanitize_key($error),$url));exit;}
}
