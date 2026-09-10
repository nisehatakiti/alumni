<?php
/**
 * 汎用フォーム編集画面.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes\Modules\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Meta_Box {
	const NONCE_ACTION = 'alumni_core_save_form_meta';
	const NONCE_NAME   = 'alumni_form_meta_nonce';

	public function register() {
		add_meta_box(
			'alumni-form-settings',
			__( 'フォーム設定', 'alumni-core' ),
			array( $this, 'render' ),
			Post_Type::SLUG,
			'normal',
			'high'
		);
	}

	public function render( $post ) {
		$description = Post_Type::get_description( $post->ID );
		$recipient   = implode( "\n", Post_Type::get_recipient_emails( $post->ID ) );
		$subject     = Post_Type::get_mail_subject( $post->ID );
		$success     = Post_Type::get_success_message( $post->ID );
		$auto_reply  = Post_Type::is_auto_reply_enabled( $post->ID );
		$from_name   = Post_Type::get_from_name( $post->ID );
		$from_email  = Post_Type::get_from_email( $post->ID );
		$reply_mode  = Post_Type::get_reply_to_mode( $post->ID );
		$reply_email = Post_Type::get_reply_to_email( $post->ID );
		$reply_field = Post_Type::get_reply_to_field_key( $post->ID );
		$fields      = Post_Type::get_fields( $post->ID );
		$form_mode   = Post_Type::get_mode( $post->ID );
		$form_target = Post_Type::get_target( $post->ID );

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		?>
		<style>
			.alumni-form-admin-field{border:1px solid #ccd0d4;padding:14px;margin:12px 0;background:#fff}
			.alumni-form-admin-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
			.alumni-form-admin-field label{display:block;font-weight:600;margin-bottom:4px}
			.alumni-form-admin-field input[type=text],.alumni-form-admin-field select,.alumni-form-admin-field textarea{width:100%}
			@media(max-width:782px){.alumni-form-admin-grid{grid-template-columns:1fr}}
		</style>
		<hr />
		<h3><?php esc_html_e( 'フォームの用途', 'alumni-core' ); ?></h3>
		<p>
			<label><input type="radio" name="alumni_form_mode" value="standard" <?php checked( $form_mode, 'standard' ); ?> /> <?php esc_html_e( '通常フォーム', 'alumni-core' ); ?></label><br />
			<label><input type="radio" name="alumni_form_mode" value="content_submission" <?php checked( $form_mode, 'content_submission' ); ?> /> <?php esc_html_e( 'コンテンツ投稿フォーム（送信内容からWordPressの下書きを作成）', 'alumni-core' ); ?></label>
		</p>
		<p class="alumni-form-target-setting">
			<label for="alumni_form_target"><strong><?php esc_html_e( '作成対象コンテンツ', 'alumni-core' ); ?></strong></label><br />
			<select id="alumni_form_target" name="alumni_form_target">
				<option value=""><?php esc_html_e( '選択してください', 'alumni-core' ); ?></option>
				<option value="person_greeting" <?php selected( $form_target, 'person_greeting' ); ?>><?php esc_html_e( '人物挨拶', 'alumni-core' ); ?></option>
				<option value="news_event" <?php selected( $form_target, 'news_event' ); ?>><?php esc_html_e( 'ニュース・イベント', 'alumni-core' ); ?></option>
			</select>
			<button type="button" class="button" id="alumni-form-apply-template"><?php esc_html_e( '推奨項目をフォームへ設定', 'alumni-core' ); ?></button>
			<span class="description"><?php esc_html_e( '対象コンテンツに合わせた推奨項目を自動生成します。必要に応じて生成後に編集できます。', 'alumni-core' ); ?></span>
		</p>
		<p>
			<label for="alumni_form_description"><strong><?php esc_html_e( '説明文', 'alumni-core' ); ?></strong></label><br />
			<textarea id="alumni_form_description" name="alumni_form_description" rows="4" class="large-text"><?php echo esc_textarea( $description ); ?></textarea>
		</p>
		<hr />
		<h3><?php esc_html_e( 'メール通知設定', 'alumni-core' ); ?></h3>
		<div class="alumni-form-admin-grid">
			<p>
				<label for="alumni_form_recipient_email"><strong><?php esc_html_e( '通知先メールアドレス', 'alumni-core' ); ?></strong></label>
				<textarea id="alumni_form_recipient_email" name="alumni_form_recipient_email" rows="3" class="large-text" required><?php echo esc_textarea( $recipient ); ?></textarea>
				<span class="description"><?php esc_html_e( '複数指定できます。改行またはカンマ区切りで入力してください。', 'alumni-core' ); ?></span>
			</p>
			<p>
				<label for="alumni_form_mail_subject"><strong><?php esc_html_e( 'メール件名', 'alumni-core' ); ?></strong></label>
				<input id="alumni_form_mail_subject" name="alumni_form_mail_subject" type="text" class="regular-text" value="<?php echo esc_attr( $subject ); ?>" />
			</p>
			<p>
				<label for="alumni_form_from_name"><strong><?php esc_html_e( '差出人名', 'alumni-core' ); ?></strong></label>
				<input id="alumni_form_from_name" name="alumni_form_from_name" type="text" class="regular-text" value="<?php echo esc_attr( $from_name ); ?>" />
			</p>
			<p>
				<label for="alumni_form_from_email"><strong><?php esc_html_e( '差出人メールアドレス', 'alumni-core' ); ?></strong></label>
				<input id="alumni_form_from_email" name="alumni_form_from_email" type="email" class="regular-text" value="<?php echo esc_attr( $from_email ); ?>" />
			</p>
		</div>
		<p><strong><?php esc_html_e( '返信先メールアドレス', 'alumni-core' ); ?></strong></p>
		<p>
			<label><input type="radio" name="alumni_form_reply_to_mode" value="none" <?php checked( $reply_mode, 'none' ); ?> /> <?php esc_html_e( '指定しない', 'alumni-core' ); ?></label><br />
			<label><input type="radio" name="alumni_form_reply_to_mode" value="fixed" <?php checked( $reply_mode, 'fixed' ); ?> /> <?php esc_html_e( '固定メールアドレス', 'alumni-core' ); ?></label>
		</p>
		<p class="alumni-form-reply-to-fixed">
			<input id="alumni_form_reply_to_email" name="alumni_form_reply_to_email" type="email" class="regular-text" value="<?php echo esc_attr( $reply_email ); ?>" placeholder="reply@example.com" />
		</p>
		<p>
			<label><input type="radio" name="alumni_form_reply_to_mode" value="form_field" <?php checked( $reply_mode, 'form_field' ); ?> /> <?php esc_html_e( 'フォーム項目を使用', 'alumni-core' ); ?></label>
		</p>
		<p class="alumni-form-reply-to-field">
			<select id="alumni_form_reply_to_field_key" name="alumni_form_reply_to_field_key">
				<option value=""><?php esc_html_e( 'メールアドレス項目を選択', 'alumni-core' ); ?></option>
				<?php foreach ( $fields as $field ) : if ( 'email' !== ( $field['type'] ?? '' ) ) continue; ?>
					<option value="<?php echo esc_attr( $field['key'] ); ?>" <?php selected( $reply_field, $field['key'] ); ?>><?php echo esc_html( $field['label'] ); ?></option>
				<?php endforeach; ?>
			</select>
			<span class="description"><?php esc_html_e( '管理者への通知メールの Reply-To に、利用者が入力したメールアドレスを設定します。', 'alumni-core' ); ?></span>
		</p>
		<p>
			<label for="alumni_form_success_message"><strong><?php esc_html_e( '送信完了メッセージ', 'alumni-core' ); ?></strong></label><br />
			<textarea id="alumni_form_success_message" name="alumni_form_success_message" rows="3" class="large-text"><?php echo esc_textarea( $success ); ?></textarea>
		</p>
		<p><label><input type="checkbox" name="alumni_form_auto_reply_enabled" value="1" <?php checked( $auto_reply ); ?> /> <?php esc_html_e( 'メールアドレス入力者へ自動返信を送る', 'alumni-core' ); ?></label></p>

		<hr />
		<h3><?php esc_html_e( '入力項目', 'alumni-core' ); ?></h3>
		<p class="description"><?php esc_html_e( '追加・削除・上下移動で公開フォームの項目と表示順を設定します。', 'alumni-core' ); ?></p>
		<div id="alumni-form-fields">
			<?php foreach ( $fields as $index => $field ) : $this->render_field_row( $index, $field ); endforeach; ?>
		</div>
		<p><button type="button" class="button" id="alumni-form-add-field"><?php esc_html_e( '項目を追加', 'alumni-core' ); ?></button></p>

		<template id="alumni-form-field-template"><?php $this->render_field_row( '__INDEX__', array() ); ?></template>
		<script>
		(function(){
			const wrap=document.getElementById('alumni-form-fields'), add=document.getElementById('alumni-form-add-field'), tpl=document.getElementById('alumni-form-field-template');
			if(!wrap||!add||!tpl)return;
			add.addEventListener('click',function(){const i=Date.now();wrap.insertAdjacentHTML('beforeend',tpl.innerHTML.replace(/__INDEX__/g,i));});
			wrap.addEventListener('click',function(e){
				const row=e.target.closest('.alumni-form-admin-field'); if(!row)return;
				if(e.target.classList.contains('alumni-form-remove-field')){row.remove();}
				if(e.target.classList.contains('alumni-form-move-up')&&row.previousElementSibling){wrap.insertBefore(row,row.previousElementSibling);}
				if(e.target.classList.contains('alumni-form-move-down')&&row.nextElementSibling){wrap.insertBefore(row.nextElementSibling,row);}
			});
			const templates={
				person_greeting:[['name','氏名','text',1],['title','肩書','text',1],['body','挨拶本文','textarea',1],['photo','写真','file',0]],
				news_event:[['title','タイトル','text',1],['content','本文','textarea',1],['event_date','開催日','date',0],['photo','画像','file',0]]
			};
			const applyTemplate=document.getElementById('alumni-form-apply-template');
			if(applyTemplate){applyTemplate.addEventListener('click',function(){const target=document.getElementById('alumni_form_target').value;if(!templates[target])return;wrap.innerHTML='';templates[target].forEach((f,n)=>{const i='tpl_'+Date.now()+'_'+n;let html=tpl.innerHTML.replace(/__INDEX__/g,i);const holder=document.createElement('div');holder.innerHTML=html;const row=holder.firstElementChild;row.querySelector('[name$="[label]"]').value=f[1];row.querySelector('[name$="[key]"]').value=f[0];row.querySelector('[name$="[type]"]').value=f[2];const req=row.querySelector('[name$="[required]"]');if(req)req.checked=!!f[3];wrap.appendChild(row);});});}
			const modeToggle=function(){const selected=document.querySelector('input[name="alumni_form_mode"]:checked');document.querySelectorAll('.alumni-form-target-setting').forEach(el=>el.style.display=selected&&selected.value==='content_submission'?'block':'none');};
			document.querySelectorAll('input[name="alumni_form_mode"]').forEach(el=>el.addEventListener('change',modeToggle));modeToggle();
			const replyMode=function(){const selected=document.querySelector('input[name="alumni_form_reply_to_mode"]:checked');const mode=selected?selected.value:'none';document.querySelectorAll('.alumni-form-reply-to-fixed').forEach(el=>el.style.display='fixed'===mode?'block':'none');document.querySelectorAll('.alumni-form-reply-to-field').forEach(el=>el.style.display='form_field'===mode?'block':'none');};
			document.querySelectorAll('input[name="alumni_form_reply_to_mode"]').forEach(el=>el.addEventListener('change',replyMode));replyMode();
		})();
		</script>
		<?php
	}

	private function render_field_row( $index, $field ) {
		$field = wp_parse_args( is_array( $field ) ? $field : array(), array(
			'id' => '', 'key' => '', 'label' => '', 'type' => 'text', 'required' => false,
			'help_text' => '', 'placeholder' => '', 'options' => '', 'min_length' => 0, 'max_length' => 0, 'width' => '100', 'row_end' => true, 'allowed_extensions' => 'pdf,doc,docx,xls,xlsx,jpg,jpeg,png', 'max_file_size_mb' => 5,
		) );
		$types = array(
			'text' => __( '1行テキスト', 'alumni-core' ), 'email' => __( 'メールアドレス', 'alumni-core' ),
			'tel' => __( '電話番号', 'alumni-core' ), 'number' => __( '数値', 'alumni-core' ), 'date' => __( '年月日（カレンダー）', 'alumni-core' ),
			'textarea' => __( '複数行テキスト', 'alumni-core' ), 'select' => __( 'セレクトボックス', 'alumni-core' ),
			'radio' => __( 'ラジオボタン', 'alumni-core' ), 'checkbox' => __( 'チェックボックス', 'alumni-core' ), 'file' => __( 'ファイル添付', 'alumni-core' ),
		);
		?>
		<div class="alumni-form-admin-field">
			<div class="alumni-form-admin-grid">
				<p><label><?php esc_html_e( '項目ラベル', 'alumni-core' ); ?><input type="text" name="alumni_form_fields[<?php echo esc_attr( $index ); ?>][label]" value="<?php echo esc_attr( $field['label'] ); ?>" /></label></p>
				<p><label><?php esc_html_e( '項目キー', 'alumni-core' ); ?><input type="text" name="alumni_form_fields[<?php echo esc_attr( $index ); ?>][key]" value="<?php echo esc_attr( $field['key'] ); ?>" /></label></p>
				<p><label><?php esc_html_e( '項目種別', 'alumni-core' ); ?><select name="alumni_form_fields[<?php echo esc_attr( $index ); ?>][type]"><?php foreach($types as $value=>$label): ?><option value="<?php echo esc_attr($value); ?>" <?php selected($field['type'],$value); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select></label></p>
				<p><label><?php esc_html_e( 'プレースホルダー', 'alumni-core' ); ?><input type="text" name="alumni_form_fields[<?php echo esc_attr( $index ); ?>][placeholder]" value="<?php echo esc_attr( $field['placeholder'] ); ?>" /></label></p>
				<p><label><?php esc_html_e( '最小文字数（テキスト系）', 'alumni-core' ); ?><input type="number" min="0" name="alumni_form_fields[<?php echo esc_attr( $index ); ?>][min_length]" value="<?php echo esc_attr( (int) $field['min_length'] ); ?>" /></label></p>
				<p><label><?php esc_html_e( '最大文字数（テキスト系）', 'alumni-core' ); ?><input type="number" min="0" name="alumni_form_fields[<?php echo esc_attr( $index ); ?>][max_length]" value="<?php echo esc_attr( (int) $field['max_length'] ); ?>" /></label></p>
				<p><label><?php esc_html_e( 'フィールド幅', 'alumni-core' ); ?><select name="alumni_form_fields[<?php echo esc_attr( $index ); ?>][width]"><?php foreach(array('10'=>'10%','25'=>'25%','33'=>'33%','50'=>'50%','66'=>'66%','75'=>'75%','100'=>'100%') as $value=>$label): ?><option value="<?php echo esc_attr($value); ?>" <?php selected((string)$field['width'],(string)$value); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select></label><span class="description"><?php esc_html_e( 'フィールド全体の表示幅です。', 'alumni-core' ); ?></span></p>
			</div>
			<p><label><?php esc_html_e( '補足説明', 'alumni-core' ); ?><input type="text" class="widefat" name="alumni_form_fields[<?php echo esc_attr( $index ); ?>][help_text]" value="<?php echo esc_attr( $field['help_text'] ); ?>" /></label></p>
			<p><label><?php esc_html_e( '選択肢（1行に1件。select / radioで使用）', 'alumni-core' ); ?><textarea rows="3" class="widefat" name="alumni_form_fields[<?php echo esc_attr( $index ); ?>][options]"><?php echo esc_textarea( $field['options'] ); ?></textarea></label></p>
			<div class="alumni-form-admin-grid">
				<p><label><?php esc_html_e( '許可するファイル形式（カンマ区切り）', 'alumni-core' ); ?><input type="text" name="alumni_form_fields[<?php echo esc_attr( $index ); ?>][allowed_extensions]" value="<?php echo esc_attr( $field['allowed_extensions'] ); ?>" /></label><span class="description"><?php esc_html_e( 'ファイル添付項目で使用します。例：pdf,doc,docx,jpg,png', 'alumni-core' ); ?></span></p>
				<p><label><?php esc_html_e( '最大ファイルサイズ（MB）', 'alumni-core' ); ?><input type="number" min="1" step="1" name="alumni_form_fields[<?php echo esc_attr( $index ); ?>][max_file_size_mb]" value="<?php echo esc_attr( (int) $field['max_file_size_mb'] ); ?>" /></label></p>
			</div>
			<p>
				<label><input type="checkbox" name="alumni_form_fields[<?php echo esc_attr( $index ); ?>][required]" value="1" <?php checked( ! empty( $field['required'] ) ); ?> /> <?php esc_html_e( '必須', 'alumni-core' ); ?></label>
				<label><input type="checkbox" name="alumni_form_fields[<?php echo esc_attr( $index ); ?>][row_end]" value="1" <?php checked( ! empty( $field['row_end'] ) ); ?> /> <?php esc_html_e( 'この項目の後で改行', 'alumni-core' ); ?></label>
				<input type="hidden" name="alumni_form_fields[<?php echo esc_attr( $index ); ?>][id]" value="<?php echo esc_attr( $field['id'] ); ?>" />
				<button type="button" class="button alumni-form-move-up"><?php esc_html_e( '上へ', 'alumni-core' ); ?></button>
				<button type="button" class="button alumni-form-move-down"><?php esc_html_e( '下へ', 'alumni-core' ); ?></button>
				<button type="button" class="button-link-delete alumni-form-remove-field"><?php esc_html_e( '削除', 'alumni-core' ); ?></button>
			</p>
		</div>
		<?php
	}

	public function save( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) return;
		if ( Post_Type::SLUG !== $post->post_type || ! current_user_can( 'edit_post', $post_id ) ) return;

		$mode = isset( $_POST['alumni_form_mode'] ) ? sanitize_key( wp_unslash( $_POST['alumni_form_mode'] ) ) : 'standard';
		if ( ! in_array( $mode, array( 'standard', 'content_submission' ), true ) ) $mode = 'standard';
		$target = isset( $_POST['alumni_form_target'] ) ? sanitize_key( wp_unslash( $_POST['alumni_form_target'] ) ) : '';
		if ( ! in_array( $target, array( 'person_greeting', 'news_event' ), true ) ) $target = '';
		if ( 'content_submission' !== $mode || ! $target ) { $mode = 'standard'; $target = ''; }
		update_post_meta( $post_id, Post_Type::META_MODE, $mode );
		update_post_meta( $post_id, Post_Type::META_TARGET, $target );

		$recipient_raw = isset( $_POST['alumni_form_recipient_email'] ) ? (string) wp_unslash( $_POST['alumni_form_recipient_email'] ) : '';
		$recipient_parts = preg_split( '/[\\r\\n,;]+/', $recipient_raw );
		$recipients = array();
		foreach ( (array) $recipient_parts as $part ) {
			$email = sanitize_email( trim( $part ) );
			if ( $email && is_email( $email ) ) $recipients[] = $email;
		}
		$recipients = array_values( array_unique( $recipients ) );
		if ( $recipients ) update_post_meta( $post_id, Post_Type::META_RECIPIENT_EMAIL, implode( ',', $recipients ) );
		else delete_post_meta( $post_id, Post_Type::META_RECIPIENT_EMAIL );

		$from_name = isset( $_POST['alumni_form_from_name'] ) ? sanitize_text_field( wp_unslash( $_POST['alumni_form_from_name'] ) ) : '';
		$from_email = isset( $_POST['alumni_form_from_email'] ) ? sanitize_email( wp_unslash( $_POST['alumni_form_from_email'] ) ) : '';
		$reply_mode = isset( $_POST['alumni_form_reply_to_mode'] ) ? sanitize_key( wp_unslash( $_POST['alumni_form_reply_to_mode'] ) ) : 'none';
		if ( ! in_array( $reply_mode, array( 'none', 'fixed', 'form_field' ), true ) ) $reply_mode = 'none';
		$reply_email = isset( $_POST['alumni_form_reply_to_email'] ) ? sanitize_email( wp_unslash( $_POST['alumni_form_reply_to_email'] ) ) : '';
		$reply_field = isset( $_POST['alumni_form_reply_to_field_key'] ) ? sanitize_key( wp_unslash( $_POST['alumni_form_reply_to_field_key'] ) ) : '';
		update_post_meta( $post_id, Post_Type::META_FROM_NAME, $from_name );
		if ( $from_email && is_email( $from_email ) ) update_post_meta( $post_id, Post_Type::META_FROM_EMAIL, $from_email ); else delete_post_meta( $post_id, Post_Type::META_FROM_EMAIL );
		update_post_meta( $post_id, Post_Type::META_REPLY_TO_MODE, $reply_mode );
		if ( $reply_email && is_email( $reply_email ) ) update_post_meta( $post_id, Post_Type::META_REPLY_TO_EMAIL, $reply_email ); else delete_post_meta( $post_id, Post_Type::META_REPLY_TO_EMAIL );
		update_post_meta( $post_id, Post_Type::META_REPLY_TO_FIELD_KEY, $reply_field );

		$map = array(
			'alumni_form_description' => Post_Type::META_DESCRIPTION,
			'alumni_form_mail_subject' => Post_Type::META_MAIL_SUBJECT,
			'alumni_form_success_message' => Post_Type::META_SUCCESS_MESSAGE,
		);
		foreach ( $map as $input => $meta ) {
			$value = isset( $_POST[$input] ) ? wp_unslash( $_POST[$input] ) : '';
			update_post_meta( $post_id, $meta, 'alumni_form_description' === $input || 'alumni_form_success_message' === $input ? sanitize_textarea_field( $value ) : sanitize_text_field( $value ) );
		}
		update_post_meta( $post_id, Post_Type::META_AUTO_REPLY_ENABLED, isset( $_POST['alumni_form_auto_reply_enabled'] ) ? '1' : '0' );

		$raw = isset( $_POST['alumni_form_fields'] ) && is_array( $_POST['alumni_form_fields'] ) ? wp_unslash( $_POST['alumni_form_fields'] ) : array();
		$allowed = array('text','email','tel','number','date','textarea','select','radio','checkbox','file');
		$fields = array(); $used = array(); $position = 0;
		foreach ( $raw as $row ) {
			$label = isset($row['label']) ? sanitize_text_field($row['label']) : '';
			if ( '' === $label ) continue;
			$key = isset($row['key']) ? sanitize_key($row['key']) : '';
			if ( '' === $key ) $key = 'field_' . ( $position + 1 );
			$base=$key; $suffix=2; while(isset($used[$key])){$key=$base.'_'.$suffix++;}
			$used[$key]=true;
			$type = isset($row['type']) && in_array($row['type'],$allowed,true) ? $row['type'] : 'text';
			$fields[] = array(
				'id' => isset($row['id']) && preg_match('/^[A-Za-z0-9_-]+$/',(string)$row['id']) ? (string)$row['id'] : 'f_' . wp_generate_password(10,false,false),
				'key'=>$key,'label'=>$label,'type'=>$type,'required'=>!empty($row['required']),
				'help_text'=>isset($row['help_text']) ? sanitize_text_field($row['help_text']) : '',
				'placeholder'=>isset($row['placeholder']) ? sanitize_text_field($row['placeholder']) : '',
				'options'=>isset($row['options']) ? sanitize_textarea_field($row['options']) : '',
				'min_length'=>isset($row['min_length']) ? max(0, absint($row['min_length'])) : 0,
				'max_length'=>isset($row['max_length']) ? max(0, absint($row['max_length'])) : 0,
				'width'=>isset($row['width']) && in_array((string)$row['width'],array('10','25','33','50','66','75','100'),true) ? (string)$row['width'] : '100',
				'row_end'=>!empty($row['row_end']),
				'allowed_extensions'=>isset($row['allowed_extensions']) ? self::sanitize_extensions($row['allowed_extensions']) : '',
				'max_file_size_mb'=>isset($row['max_file_size_mb']) ? max(1, absint($row['max_file_size_mb'])) : 5,
				'sort_order'=>$position++,
			);
		}
		update_post_meta( $post_id, Post_Type::META_FIELDS, $fields );
	}

	private static function sanitize_extensions( $value ) {
		$parts = preg_split( '/[\\s,]+/', strtolower( (string) $value ) );
		$parts = array_filter( array_map( function( $ext ) { return preg_replace( '/[^a-z0-9]/', '', $ext ); }, $parts ) );
		return implode( ',', array_values( array_unique( $parts ) ) );
	}
}
