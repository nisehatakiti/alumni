<?php
/**
 * 汎用フォーム Custom Post Type.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes\Modules\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Post_Type {
	const SLUG = 'alumni_form';

	const META_DESCRIPTION        = '_alumni_form_description';
	const META_RECIPIENT_EMAIL    = '_alumni_form_recipient_email';
	const META_MAIL_SUBJECT       = '_alumni_form_mail_subject';
	const META_SUCCESS_MESSAGE    = '_alumni_form_success_message';
	const META_AUTO_REPLY_ENABLED = '_alumni_form_auto_reply_enabled';
	const META_FROM_NAME          = '_alumni_form_from_name';
	const META_FROM_EMAIL         = '_alumni_form_from_email';
	const META_REPLY_TO_MODE      = '_alumni_form_reply_to_mode';
	const META_REPLY_TO_EMAIL     = '_alumni_form_reply_to_email';
	const META_REPLY_TO_FIELD_KEY = '_alumni_form_reply_to_field_key';
	const META_FIELDS             = '_alumni_form_fields';

	public static function register() {
		register_post_type(
			self::SLUG,
			array(
				'labels' => array(
					'name'               => __( 'フォーム', 'alumni-core' ),
					'singular_name'      => __( 'フォーム', 'alumni-core' ),
					'menu_name'          => __( 'フォーム', 'alumni-core' ),
					'all_items'          => __( 'すべてのフォーム', 'alumni-core' ),
					'add_new'            => __( '新規追加', 'alumni-core' ),
					'add_new_item'       => __( '新規フォームを追加', 'alumni-core' ),
					'edit_item'          => __( 'フォームを編集', 'alumni-core' ),
					'new_item'           => __( '新規フォーム', 'alumni-core' ),
					'view_item'          => __( 'フォームを表示', 'alumni-core' ),
					'search_items'       => __( 'フォームを検索', 'alumni-core' ),
					'not_found'          => __( 'フォームが見つかりません', 'alumni-core' ),
					'not_found_in_trash' => __( 'ゴミ箱にフォームは見つかりません', 'alumni-core' ),
				),
				'public'       => true,
				'show_ui'      => true,
				'show_in_menu' => \AlumniCore\Admin\Admin::MENU_SLUG,
				'show_in_rest' => false,
				'supports'     => array( 'title', 'page-attributes' ),
				'has_archive'  => false,
				'rewrite'      => array(
					'slug'       => 'forms',
					'with_front' => false,
				),
			)
		);
	}

	/**
	 * Keep the form engine independent from the Alumni admin shell. When it is
	 * extracted into a standalone plugin, WordPress can simply use its own menu.
	 */
	private static function menu_parent() {
		$default = class_exists( '\\AlumniCore\\Admin\\Admin' ) ? \AlumniCore\Admin\Admin::MENU_SLUG : true;
		return apply_filters( 'alumni_forms_menu_parent', $default );
	}

	public static function get_description( $post_id ) {
		return (string) get_post_meta( $post_id, self::META_DESCRIPTION, true );
	}

	public static function get_recipient_emails( $post_id ) {
		$raw = (string) get_post_meta( $post_id, self::META_RECIPIENT_EMAIL, true );
		$parts = preg_split( '/[\\r\\n,;]+/', $raw );
		$emails = array();
		foreach ( (array) $parts as $part ) {
			$email = sanitize_email( trim( $part ) );
			if ( $email && is_email( $email ) ) $emails[] = $email;
		}
		return array_values( array_unique( $emails ) );
	}

	public static function get_recipient_email( $post_id ) {
		$emails = self::get_recipient_emails( $post_id );
		return $emails ? $emails[0] : '';
	}

	public static function get_from_name( $post_id ) {
		return sanitize_text_field( (string) get_post_meta( $post_id, self::META_FROM_NAME, true ) );
	}

	public static function get_from_email( $post_id ) {
		$email = sanitize_email( (string) get_post_meta( $post_id, self::META_FROM_EMAIL, true ) );
		return is_email( $email ) ? $email : '';
	}

	public static function get_reply_to_mode( $post_id ) {
		$mode = sanitize_key( (string) get_post_meta( $post_id, self::META_REPLY_TO_MODE, true ) );
		return in_array( $mode, array( 'none', 'fixed', 'form_field' ), true ) ? $mode : 'none';
	}

	public static function get_reply_to_email( $post_id ) {
		$email = sanitize_email( (string) get_post_meta( $post_id, self::META_REPLY_TO_EMAIL, true ) );
		return is_email( $email ) ? $email : '';
	}

	public static function get_reply_to_field_key( $post_id ) {
		return sanitize_key( (string) get_post_meta( $post_id, self::META_REPLY_TO_FIELD_KEY, true ) );
	}

	public static function get_mail_subject( $post_id ) {
		$subject = sanitize_text_field( (string) get_post_meta( $post_id, self::META_MAIL_SUBJECT, true ) );
		return $subject ? $subject : sprintf( '[%s] %s', get_bloginfo( 'name' ), get_the_title( $post_id ) );
	}

	public static function get_success_message( $post_id ) {
		$message = (string) get_post_meta( $post_id, self::META_SUCCESS_MESSAGE, true );
		return $message ? $message : __( '送信が完了しました。ありがとうございました。', 'alumni-core' );
	}

	public static function is_auto_reply_enabled( $post_id ) {
		return '1' === (string) get_post_meta( $post_id, self::META_AUTO_REPLY_ENABLED, true );
	}

	public static function get_fields( $post_id ) {
		$fields = get_post_meta( $post_id, self::META_FIELDS, true );
		return is_array( $fields ) ? $fields : array();
	}
}
