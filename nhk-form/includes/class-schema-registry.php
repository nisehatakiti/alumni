<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 投稿作成フォーム用スキーマの登録・取得API。
 *
 * 外部プラグインは以下のどちらかで連携できます。
 * add_filter('nhk_form_schemas', function($schemas){ $schemas['my_type']=...; return $schemas; });
 * NHK_Form_Schema_Registry::register('my_type', $schema);
 */
class NHK_Form_Schema_Registry {
	private static $schemas = array();

	public static function bootstrap() {
		self::$schemas = apply_filters( 'nhk_form_schemas', self::$schemas );
		do_action( 'nhk_form_register_schemas' );
	}

	public static function register( $key, $schema ) {
		$key = sanitize_key( $key );
		if ( ! $key || ! is_array( $schema ) ) return false;
		$schema = wp_parse_args( $schema, array(
			'label' => $key,
			'post_type' => 'post',
			'fields' => array(),
			'map' => array(),
		) );
		self::$schemas[ $key ] = $schema;
		return true;
	}

	public static function all() {
		return apply_filters( 'nhk_form_available_schemas', self::$schemas );
	}

	public static function get( $key ) {
		$key = sanitize_key( $key );
		$schemas = self::all();
		return isset( $schemas[ $key ] ) ? $schemas[ $key ] : null;
	}
}
