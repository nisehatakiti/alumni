<?php
/**
 * コンテンツ管理モジュールの起動処理.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes\Modules\Content;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wires up the コンテンツ管理 module. Structurally identical to
 * NewsEvents\Module: post type registration always runs, admin-only pieces
 * only wire up in wp-admin.
 */
class Module {

	/**
	 * Bumped whenever the post type's rewrite rules change, to trigger a
	 * one-time flush on sites updated in place (no deactivate/reactivate).
	 */
	const REWRITE_VERSION = '1';

	/**
	 * Option name storing which rewrite version has already been flushed.
	 */
	const REWRITE_FLUSHED_OPTION = 'alumni_content_rewrite_flushed';

	/**
	 * Registers this module's hooks. Safe to call unconditionally — the
	 * admin-only hooks are gated internally by is_admin().
	 */
	public static function register() {
		add_action( 'init', array( Post_Type::class, 'register' ) );
		add_action( 'template_redirect', array( __CLASS__, 'redirect_person_greeting_to_group' ) );

		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_init', array( __CLASS__, 'maybe_flush_rewrite_rules' ) );

		// 規約類のみWordPress標準のブロックエディターで本文を編集できるように
		// する(段落単位の太字・文字サイズ)。以前はload-post.php/
		// load-post-new.phpのフックタイミングに依存してremove_post_type_support()
		// する方式だったが、WordPress内部の呼び出し順序という保証のない
		// 前提に頼っておりリスクがあったため、WordPress core自身がこの
		// 「投稿ごとにブロックエディターを使うか決める」目的のために用意
		// している専用フィルターへ置き換えた
		// (Post_Type::maybe_use_block_editor()参照)。
		add_filter( 'use_block_editor_for_post_type', array( Post_Type::class, 'maybe_use_block_editor' ), 10, 2 );

		// 人物挨拶は専用フォーム内の「本文」だけを入力箇所にする。規約類は
		// 上のフィルターでブロックエディターを使い続けるため、CPT全体では
		// editor supportを残したまま、人物挨拶の編集リクエストだけで
		// WordPress標準エディターを外す。
		add_action( 'load-post.php', array( Post_Type::class, 'maybe_hide_person_greeting_editor' ) );
		add_action( 'load-post-new.php', array( Post_Type::class, 'maybe_hide_person_greeting_editor' ) );

		$meta_box = new Content_Meta_Box();
		add_action( 'add_meta_boxes', array( $meta_box, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( $meta_box, 'enqueue_assets' ) );
		add_action( 'save_post_' . Post_Type::SLUG, array( $meta_box, 'save' ), 10, 2 );
		// Priority 5: must run before Content_Required_Fields::enforce()
		// (priority 10) so the required-field check sees the real
		// submitted value.
		add_filter( 'wp_insert_post_data', array( $meta_box, 'inject_content' ), 5, 2 );

		$columns = new Content_Admin_Columns();
		add_filter( 'manage_' . Post_Type::SLUG . '_posts_columns', array( $columns, 'add_columns' ) );
		add_action( 'manage_' . Post_Type::SLUG . '_posts_custom_column', array( $columns, 'render_column' ), 10, 2 );

		$required_fields = new Content_Required_Fields();
		add_filter( 'wp_insert_post_data', array( $required_fields, 'enforce' ), 10, 2 );
		add_action( 'admin_notices', array( $required_fields, 'render_notice' ) );
	}


	/**
	 * Keeps a person greeting group as the single public destination.
	 *
	 * Historical members remain separate posts for editing and ordering, but
	 * visitors should see the group's consolidated page. A direct old/member
	 * URL therefore lands on the same group page and jumps to that member.
	 */
	public static function redirect_person_greeting_to_group() {
		if ( is_admin() || ! is_singular( Post_Type::SLUG ) ) {
			return;
		}

		$post_id = get_queried_object_id();

		if ( ! Post_Type::is_person_greeting( $post_id ) ) {
			return;
		}

		$group_id = Post_Type::get_person_greeting_group_id( $post_id );

		if ( '' === $group_id ) {
			return;
		}

		$group_url = \AlumniCore\Includes\Person_Greeting_Groups_Shortcode::get_group_url( $group_id );

		if ( '' === $group_url ) {
			return;
		}

		wp_safe_redirect( $group_url . '#person-greeting-' . $post_id, 302 );
		exit;
	}

	/**
	 * Flushes rewrite rules once so /contents/... URLs work on sites where
	 * the plugin's files were updated in place rather than
	 * deactivated/reactivated (activation already flushes explicitly; see
	 * Activator::activate()).
	 */
	public static function maybe_flush_rewrite_rules() {
		if ( get_option( self::REWRITE_FLUSHED_OPTION ) === self::REWRITE_VERSION ) {
			return;
		}

		flush_rewrite_rules();
		update_option( self::REWRITE_FLUSHED_OPTION, self::REWRITE_VERSION );
	}
}
