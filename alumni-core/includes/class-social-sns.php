<?php
namespace AlumniCore\Includes;
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AlumniCore standard SNS content settings.
 * Each enabled and configured SNS becomes a selectable system content item.
 */
class Social_SNS {
	const OPTION_NAME = 'alumni_core_sns_settings';
	const X = 'sns_x';
	const INSTAGRAM = 'sns_instagram';
	const FACEBOOK = 'sns_facebook';

	public static function services() {
		return array(
			self::X => array( 'label' => 'X', 'field_label' => 'X アカウントURL' ),
			self::INSTAGRAM => array( 'label' => 'Instagram', 'field_label' => 'Instagram アカウントURL' ),
			self::FACEBOOK => array( 'label' => 'Facebook', 'field_label' => 'Facebook ページURL' ),
		);
	}

	public static function defaults() {
		$settings = array();
		foreach ( self::services() as $key => $service ) {
			$settings[ $key ] = array( 'enabled' => false, 'url' => '' );
		}
		return $settings;
	}

	public static function get_all() {
		$saved = get_option( self::OPTION_NAME, array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
	}

	public static function save( array $input ) {
		$settings = self::defaults();
		foreach ( self::services() as $key => $service ) {
			$row = isset( $input[ $key ] ) && is_array( $input[ $key ] ) ? $input[ $key ] : array();
			$settings[ $key ] = array(
				'enabled' => ! empty( $row['enabled'] ),
				'url' => esc_url_raw( isset( $row['url'] ) ? wp_unslash( $row['url'] ) : '' ),
			);
		}
		update_option( self::OPTION_NAME, $settings );
		return $settings;
	}

	public static function is_available( $key ) {
		$settings = self::get_all();
		return isset( $settings[ $key ] ) && ! empty( $settings[ $key ]['enabled'] ) && ! empty( $settings[ $key ]['url'] );
	}

	public static function url( $key ) {
		$settings = self::get_all();
		return self::is_available( $key ) ? (string) $settings[ $key ]['url'] : '';
	}

	public static function active_keys() {
		$keys = array();
		foreach ( array_keys( self::services() ) as $key ) {
			if ( self::is_available( $key ) ) $keys[] = $key;
		}
		return $keys;
	}

	public static function labels() {
		$labels = array();
		foreach ( self::services() as $key => $service ) $labels[ $key ] = $service['label'];
		return $labels;
	}
}
