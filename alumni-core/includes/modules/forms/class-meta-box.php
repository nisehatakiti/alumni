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
		$recipient   = Post_Type::get_recipient_email( $post->ID );
		$subject     = Post_Type::get_mail_subject( $post->ID );
		$success     = Post_Type::get_success_message( $post->ID );
		$auto_reply  = Post_Type::is_auto_reply_enabled( $post->ID );
		$fields      = Post_Type::get_fields( $post->ID );

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		?>
		<style>
			.alumni-form-admin-field{border:1px solid #ccd0d4;padding:14px;margin:12px 0;background:#fff}
			.alumni-form-admin-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
			.alumni-form-admin-field label{display:block;font-weight:600;margin-bottom:4px}
			.alumni-form-admin-field input[type=text],.alumni-form-admin-field select,.alumni-form-admin-field textarea{width:100%}
			@media(max-width:782px){.alumni-form-admin-grid{grid-template-columns:1fr}}
		</style>
		<p>
			<label for="alumni_form_description"><strong><?php esc_html_e( '説明文', 'alumni-core' ); ?></strong></label><br />
			<textarea id="alumni_form_description" name="alumni_form_description" rows="4" class="large-text"><?php echo esc_textarea( $description ); ?></textarea>
		</p>
		<div class="alumni-form-admin-grid">
			<p>
				<label for="alumni_form_recipient_email"><strong><?php esc_html_e( '送信先メールアドレス', 'alumni-core' ); ?></strong></label>
				<input id="alumni_form_recipient_email" name="alumni_form_recipient_email" type="email" class="regular-text" value="<?php echo esc_attr( $recipient ); ?>" required />
			</p>
			<p>
				<label for="alumni_form_mail_subject"><strong><?php esc_html_e( 'メール件名', 'alumni-core' ); ?></strong></label>
				<input id="alumni_form_mail_subject" name="alumni_form_mail_subject" type="text" class="regular-text" value="<?php echo esc_attr( $subject ); ?>" />
			</p>
		</div>
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
		})();
		</script>
		<?php
	}

	private function render_field_row( $index, $field ) {
		$field = wp_parse_args( is_array( $field ) ? $field : array(), array(
			'id' => '', 'key' => '', 'label' => '', 'type' => 'text', 'required' => false,
			'help_text' => '', 'placeholder' => '', 'options' => '', 'min_length' => 0, 'max_length' => 0, 'allowed_extensions' => 'pdf,doc,docx,xls,xlsx,jpg,jpeg,png', 'max_file_size_mb' => 5,
		) );
		$types = array(
			'text' => __( '1行テキスト', 'alumni-core' ), 'email' => __( 'メールアドレス', 'alumni-core' ),
			'tel' => __( '電話番号', 'alumni-core' ), 'number' => __( '数値', 'alumni-core' ),
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
			</div>
			<p><label><?php esc_html_e( '補足説明', 'alumni-core' ); ?><input type="text" class="widefat" name="alumni_form_fields[<?php echo esc_attr( $index ); ?>][help_text]" value="<?php echo esc_attr( $field['help_text'] ); ?>" /></label></p>
			<p><label><?php esc_html_e( '選択肢（1行に1件。select / radioで使用）', 'alumni-core' ); ?><textarea rows="3" class="widefat" name="alumni_form_fields[<?php echo esc_attr( $index ); ?>][options]"><?php echo esc_textarea( $field['options'] ); ?></textarea></label></p>
			<div class="alumni-form-admin-grid">
				<p><label><?php esc_html_e( '許可するファイル形式（カンマ区切り）', 'alumni-core' ); ?><input type="text" name="alumni_form_fields[<?php echo esc_attr( $index ); ?>][allowed_extensions]" value="<?php echo esc_attr( $field['allowed_extensions'] ); ?>" /></label><span class="description"><?php esc_html_e( 'ファイル添付項目で使用します。例：pdf,doc,docx,jpg,png', 'alumni-core' ); ?></span></p>
				<p><label><?php esc_html_e( '最大ファイルサイズ（MB）', 'alumni-core' ); ?><input type="number" min="1" step="1" name="alumni_form_fields[<?php echo esc_attr( $index ); ?>][max_file_size_mb]" value="<?php echo esc_attr( (int) $field['max_file_size_mb'] ); ?>" /></label></p>
			</div>
			<p>
				<label><input type="checkbox" name="alumni_form_fields[<?php echo esc_attr( $index ); ?>][required]" value="1" <?php checked( ! empty( $field['required'] ) ); ?> /> <?php esc_html_e( '必須', 'alumni-core' ); ?></label>
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

		$recipient = isset( $_POST['alumni_form_recipient_email'] ) ? sanitize_email( wp_unslash( $_POST['alumni_form_recipient_email'] ) ) : '';
		if ( $recipient && is_email( $recipient ) ) update_post_meta( $post_id, Post_Type::META_RECIPIENT_EMAIL, $recipient );
		else delete_post_meta( $post_id, Post_Type::META_RECIPIENT_EMAIL );

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
		$allowed = array('text','email','tel','number','textarea','select','radio','checkbox','file');
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
