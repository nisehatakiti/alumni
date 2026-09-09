<?php
/**
 * 汎用フォームモジュールの起動処理.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes\Modules\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Module {
	const REWRITE_VERSION = '1';
	const REWRITE_FLUSHED_OPTION = 'alumni_forms_rewrite_flushed';

	public static function register() {
		add_action( 'init', array( Post_Type::class, 'register' ) );
		Public_Form::register();

		if ( ! is_admin() ) return;

		add_action( 'admin_init', array( __CLASS__, 'maybe_flush_rewrite_rules' ) );
		$meta_box = new Meta_Box();
		add_action( 'add_meta_boxes', array( $meta_box, 'register' ) );
		add_action( 'save_post_' . Post_Type::SLUG, array( $meta_box, 'save' ), 10, 2 );
	}

	public static function maybe_flush_rewrite_rules() {
		if ( get_option( self::REWRITE_FLUSHED_OPTION ) === self::REWRITE_VERSION ) return;
		Post_Type::register();
		flush_rewrite_rules();
		update_option( self::REWRITE_FLUSHED_OPTION, self::REWRITE_VERSION );
	}
}
