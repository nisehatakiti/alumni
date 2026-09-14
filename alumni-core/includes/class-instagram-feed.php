<?php
namespace AlumniCore\Includes;
if ( ! defined( 'ABSPATH' ) ) exit;

/** Instagram Business Login integration for a connected account feed. */
class Instagram_Feed {
	const CONNECTION_OPTION = 'alumni_core_instagram_connection';
	const AUTH_ACTION = 'alumni_core_instagram_callback';
	const API_BASE = 'https://graph.instagram.com';
	const OAUTH_URL = 'https://www.instagram.com/oauth/authorize';
	const TOKEN_URL = 'https://api.instagram.com/oauth/access_token';
	const SCOPE = 'instagram_business_basic';
	const FEED_CACHE_KEY = 'alumni_core_instagram_feed';

	public static function register() {
		add_action( 'admin_post_' . self::AUTH_ACTION, array( __CLASS__, 'callback' ) );
		add_filter( 'alumni_core_social_sns_embed_html', array( __CLASS__, 'render_filter' ), 20, 4 );
		add_action( 'updated_option_alumni_core_sns_settings', array( __CLASS__, 'restore_url' ), 20, 3 );
	}

	public static function redirect_uri() { return admin_url( 'admin-post.php?action=' . self::AUTH_ACTION ); }

	public static function is_configured() {
		return defined( 'ALUMNI_INSTAGRAM_APP_ID' ) && defined( 'ALUMNI_INSTAGRAM_APP_SECRET' ) && ALUMNI_INSTAGRAM_APP_ID && ALUMNI_INSTAGRAM_APP_SECRET;
	}

	public static function connect_url() {
		if ( ! self::is_configured() ) return '';
		$state = wp_generate_password( 32, false, false );
		set_transient( 'alumni_core_instagram_oauth_' . get_current_user_id(), $state, 10 * MINUTE_IN_SECONDS );
		return add_query_arg( array( 'client_id' => ALUMNI_INSTAGRAM_APP_ID, 'redirect_uri' => self::redirect_uri(), 'scope' => self::SCOPE, 'response_type' => 'code', 'state' => $state ), self::OAUTH_URL );
	}

	public static function callback() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'この操作を行う権限がありません。', 'alumni-core' ) );
		$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
		$expected = get_transient( 'alumni_core_instagram_oauth_' . get_current_user_id() );
		delete_transient( 'alumni_core_instagram_oauth_' . get_current_user_id() );
		if ( ! $state || ! $expected || ! hash_equals( $expected, $state ) ) self::fail( 'Instagram認証の状態を確認できませんでした。' );
		$code = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
		if ( '' === $code ) self::fail( 'Instagramから認証コードを取得できませんでした。' );
		$response = wp_remote_post( self::TOKEN_URL, array( 'timeout' => 20, 'body' => array( 'client_id' => ALUMNI_INSTAGRAM_APP_ID, 'client_secret' => ALUMNI_INSTAGRAM_APP_SECRET, 'grant_type' => 'authorization_code', 'redirect_uri' => self::redirect_uri(), 'code' => $code ) ) );
		if ( is_wp_error( $response ) ) self::fail( $response->get_error_message() );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) || empty( $data['access_token'] ) ) self::fail( 'Instagramアクセストークンを取得できませんでした。' );
		$long = wp_remote_get( add_query_arg( array( 'grant_type' => 'ig_exchange_token', 'client_secret' => ALUMNI_INSTAGRAM_APP_SECRET, 'access_token' => $data['access_token'] ), self::API_BASE . '/access_token' ), array( 'timeout' => 20 ) );
		$long_data = is_wp_error( $long ) ? array() : json_decode( wp_remote_retrieve_body( $long ), true );
		if ( ! is_array( $long_data ) || empty( $long_data['access_token'] ) ) self::fail( 'Instagramの長期アクセストークンを取得できませんでした。' );
		$token = sanitize_text_field( $long_data['access_token'] );
		$user = wp_remote_get( add_query_arg( array( 'fields' => 'id,username,account_type,profile_picture_url', 'access_token' => $token ), self::API_BASE . '/me' ), array( 'timeout' => 20 ) );
		$user_data = is_wp_error( $user ) ? array() : json_decode( wp_remote_retrieve_body( $user ), true );
		if ( ! is_array( $user_data ) || empty( $user_data['id'] ) ) self::fail( 'Instagramアカウント情報を取得できませんでした。' );
		$connection = array(
			'access_token' => $token,
			'user_id' => sanitize_text_field( $user_data['id'] ),
			'username' => isset( $user_data['username'] ) ? sanitize_text_field( $user_data['username'] ) : '',
			'account_type' => isset( $user_data['account_type'] ) ? sanitize_text_field( $user_data['account_type'] ) : '',
			'profile_picture_url' => isset( $user_data['profile_picture_url'] ) ? esc_url_raw( $user_data['profile_picture_url'] ) : '',
			'token_expires' => time() + ( isset( $long_data['expires_in'] ) ? absint( $long_data['expires_in'] ) : 60 * DAY_IN_SECONDS ),
			'feed' => array(),
			'feed_updated' => 0,
		);
		update_option( self::CONNECTION_OPTION, $connection, false );
		self::restore_url();
		self::refresh_feed( true );
		wp_safe_redirect( add_query_arg( array( 'page' => 'alumni-core-sns', 'updated' => 'true', 'instagram_connected' => 'true' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function restore_url() {
		static $running = false;
		if ( $running ) return;
		$connection = get_option( self::CONNECTION_OPTION, array() );
		if ( empty( $connection['username'] ) ) return;
		$settings = get_option( Social_SNS::OPTION_NAME, array() );
		if ( ! is_array( $settings ) ) return;
		if ( ! isset( $settings[ Social_SNS::INSTAGRAM ] ) || ! is_array( $settings[ Social_SNS::INSTAGRAM ] ) ) $settings[ Social_SNS::INSTAGRAM ] = array();
		$url = 'https://www.instagram.com/' . rawurlencode( $connection['username'] ) . '/';
		if ( isset( $settings[ Social_SNS::INSTAGRAM ]['url'] ) && $settings[ Social_SNS::INSTAGRAM ]['url'] === $url ) return;
		$settings[ Social_SNS::INSTAGRAM ]['url'] = $url;
		$running = true;
		update_option( Social_SNS::OPTION_NAME, $settings );
		$running = false;
	}

	public static function render_filter( $html, $key, $url, $args ) {
		if ( Social_SNS::INSTAGRAM !== $key || ! self::connected() ) return $html;
		$feed = self::feed();
		$connection = get_option( self::CONNECTION_OPTION, array() );
		$username = isset( $connection['username'] ) ? $connection['username'] : '';
		if ( empty( $feed ) ) return '<div class="alumni-sns-instagram-fallback"><p class="alumni-sns-instagram-fallback-text">Instagramの投稿を取得できませんでした。</p></div>';
		$out = '<div class="alumni-sns-instagram-feed">';
		if ( $username ) $out .= '<div class="alumni-sns-instagram-feed-title">@' . esc_html( $username ) . '</div>';
		$out .= '<div class="alumni-sns-instagram-feed-grid">';
		foreach ( $feed as $item ) {
			$image = ! empty( $item['media_url'] ) ? $item['media_url'] : ( isset( $item['thumbnail_url'] ) ? $item['thumbnail_url'] : '' );
			$link = isset( $item['permalink'] ) ? $item['permalink'] : '';
			if ( ! $image || ! $link ) continue;
			$caption = isset( $item['caption'] ) ? $item['caption'] : '';
			$out .= sprintf( '<a class="alumni-sns-instagram-feed-item" href="%1$s" target="_blank" rel="noopener noreferrer"><img src="%2$s" alt="%3$s" loading="lazy"></a>', esc_url( $link ), esc_url( $image ), esc_attr( wp_trim_words( $caption, 12, '…' ) ) );
		}
		$out .= '</div></div>';
		return $out;
	}

	public static function feed() {
		$connection = get_option( self::CONNECTION_OPTION, array() );
		if ( empty( $connection['access_token'] ) || empty( $connection['user_id'] ) ) return array();
		if ( ! empty( $connection['feed'] ) && ! empty( $connection['feed_updated'] ) && (int) $connection['feed_updated'] > time() - 6 * HOUR_IN_SECONDS ) return $connection['feed'];
		self::refresh_feed();
		$connection = get_option( self::CONNECTION_OPTION, array() );
		return isset( $connection['feed'] ) && is_array( $connection['feed'] ) ? $connection['feed'] : array();
	}

	public static function refresh_feed( $force = false ) {
		$connection = get_option( self::CONNECTION_OPTION, array() );
		if ( empty( $connection['access_token'] ) || empty( $connection['user_id'] ) ) return false;
		if ( ! $force && ! empty( $connection['feed_updated'] ) && (int) $connection['feed_updated'] > time() - 6 * HOUR_IN_SECONDS ) return true;
		$response = wp_remote_get( add_query_arg( array( 'fields' => 'id,caption,media_type,media_url,permalink,thumbnail_url,timestamp', 'limit' => 12, 'access_token' => $connection['access_token'] ), self::API_BASE . '/' . rawurlencode( $connection['user_id'] ) . '/media' ), array( 'timeout' => 20 ) );
		if ( is_wp_error( $response ) ) return false;
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) || ! isset( $data['data'] ) || ! is_array( $data['data'] ) ) return false;
		$connection['feed'] = $data['data'];
		$connection['feed_updated'] = time();
		update_option( self::CONNECTION_OPTION, $connection, false );
		return true;
	}

	public static function connected() {
		$connection = get_option( self::CONNECTION_OPTION, array() );
		return ! empty( $connection['access_token'] ) && ! empty( $connection['user_id'] );
	}

	private static function fail( $message ) {
		wp_safe_redirect( add_query_arg( array( 'page' => 'alumni-core-sns', 'instagram_error' => rawurlencode( wp_strip_all_tags( $message ) ) ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
