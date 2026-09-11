<?php
namespace AlumniCore\Includes;
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AlumniCore standard SNS content settings.
 * Each enabled and configured SNS becomes a selectable system content item.
 */
class Social_SNS {
	const OPTION_NAME = 'alumni_core_sns_settings';
	const FACEBOOK_APP_ID = '185738782028798';
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

	/**
	 * True when the given key is one of AlumniCore's standard SNS services.
	 *
	 * @param string $key
	 * @return bool
	 */
	public static function is_sns_key( $key ) {
		return array_key_exists( $key, self::services() );
	}

	/**
	 * Return provider-specific front-end markup for the SNS content window.
	 *
	 * X and Facebook use their official public embed mechanisms. Instagram
	 * account feeds do not have an equivalent simple profile-feed embed, so
	 * Core intentionally exposes a stable account window that can later be
	 * replaced by an authenticated/API-backed provider through the filter.
	 *
	 * @param string $key
	 * @param array  $args Supported: height (px), title.
	 * @return string Safe HTML for direct front-end output.
	 */
	public static function render_embed( $key, array $args = array() ) {
		if ( ! self::is_available( $key ) ) {
			return '';
		}

		$url    = self::url( $key );
		$labels = self::labels();
		$label  = isset( $labels[ $key ] ) ? $labels[ $key ] : '';
		$height = isset( $args['height'] ) ? max( 240, min( 1200, absint( $args['height'] ) ) ) : 520;

		$html = '';

		switch ( $key ) {
			case self::X:
				$html = sprintf(
					'<a class="twitter-timeline" data-height="%1$d" data-dnt="true" data-theme="light" href="%2$s">%3$s</a>',
					$height,
					esc_url( $url ),
					esc_html( sprintf( __( '%s の投稿', 'alumni-core' ), $label ) )
				);
				break;

			case self::FACEBOOK:
				/*
				 * Keep this URL structure aligned with Facebook's Page Plugin
				 * markup confirmed to work on the live alumni site. In particular,
				 * the plugin's internal render width is 340px while the surrounding
				 * iframe may be shown at a different CSS size; using the previous
				 * 500px/adapt-container combination produced only the plugin shell
				 * in this site's layout.
				 */
				$facebook_width  = 340;
				$facebook_height = 720;
				$iframe_url = add_query_arg(
					array(
						'href'                  => $url,
						'tabs'                  => 'timeline',
						'width'                 => $facebook_width,
						'height'                => $facebook_height,
						'small_header'          => 'true',
						'adapt_container_width' => 'false',
						'hide_cover'            => 'true',
						'show_facepile'         => 'true',
						'appId'                 => self::FACEBOOK_APP_ID,
					),
					'https://www.facebook.com/plugins/page.php'
				);
				// Use the exact outer dimensions from the verified live embed.
				// The previous responsive CSS stretched the iframe to a width that
				// did not match the Page Plugin's own width parameter, which can
				// leave the shell visible while the timeline itself remains blank.
				$html = sprintf(
					'<iframe class="alumni-sns-facebook-frame" src="%1$s" width="%2$d" height="540" style="border:none;overflow:hidden" scrolling="no" frameborder="0" allowfullscreen="true" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share" title="%3$s"></iframe>',
					esc_url( $iframe_url ),
					$facebook_width,
					esc_attr( sprintf( __( '%s タイムライン', 'alumni-core' ), $label ) )
				);
				break;

			case self::INSTAGRAM:
				$html = sprintf(
					'<div class="alumni-sns-instagram-fallback"><div class="alumni-sns-instagram-fallback-icon" aria-hidden="true">◎</div><p class="alumni-sns-instagram-fallback-text">%1$s</p><a class="alumni-sns-instagram-fallback-button" href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a></div>',
					esc_html__( 'Instagramのアカウントページを表示します。', 'alumni-core' ),
					esc_url( $url ),
					esc_html__( 'Instagramを開く', 'alumni-core' )
				);
				break;
		}

		/**
		 * Allows a provider plugin or site-specific integration to replace or
		 * enhance the standard embed. This is especially useful for Instagram
		 * feeds backed by an authenticated API/widget.
		 */
		return (string) apply_filters( 'alumni_core_social_sns_embed_html', $html, $key, $url, $args );
	}
}
