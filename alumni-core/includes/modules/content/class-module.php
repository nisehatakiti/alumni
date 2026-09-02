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

		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_init', array( __CLASS__, 'maybe_flush_rewrite_rules' ) );

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
