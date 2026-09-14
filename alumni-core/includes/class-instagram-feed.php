<?php
namespace AlumniCore\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Instagram Business Login integration.
 *
 * The Meta application credentials are supplied through wp-config.php:
 * ALUMNI_INSTAGRAM_APP_ID and ALUMNI_INSTAGRAM_APP_SECRET.
 */
class Instagram_Feed {
	const OPTION_NAME = 'alumni_core_sns_settings';
	const AUTH_ACTION = 'alumni_core_instagram_callback';
	const API_BASE = 'https://graph.instagram.com';
	const OAUTH_URL = 'https://www.instagram.com/oauth/authorize';
	const TOKEN_URL = 'https://api.instagram.com/oauth/access_token';
	const SCOPE = 'instagram_business_basic';

	public static function register() {
		add_action( 'admin_post_' . self::AUTH_ACTION, array( __CLASS__, 'callback' ) );
	}

	public static function redirect_uri() {
		return admin_url( 'admin-post.php?action=' . self::AUTH_ACTION );
	}

	public static function is_configured() {
		return defined( 'ALUMNI_INSTAGRAM_APP_ID' ) && defined( 'ALUMNI_INSTAGRAM_APP_SECRET' ) && ALUMNI_INSTAGRAM_APP_ID && ALUMNI_INSTAGRAM_APP_SECRET;
	}

	public static function connect_url() {
		if ( ! self::is_configured() ) {
			return '';
		}
		$state = wp_generate_password( 32, false, false );
		set_transient( 'alumni_core_instagram_oauth_' . get_current_user_id(), $state, 10 * MINUTE_IN_SECONDS );
		return add_query_arg(
			array(
				'client_id' => ALUMNI_INSTAGRAM_APP_ID,
				'redirect_uri' => self::redirect_uri(),
				'scope' => self::SCOPE,
				'response_type' => 'code',
				'state' => $state,
			),
			self::OAUTH_URL
		);
	}

	public static function callback() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'この操作を行う権限がありません。', 'alumni-core' ) );
		}
		$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
		$expected = get_transient( 'alumni_core_instagram_oauth_' . get_current_user_id() );
		delete_transient( 'alumni_core_instagram_oauth_' . get_current_user_id() );
		if ( ! $state || ! $expected || ! hash_equals( $expected, $state ) ) {
			self::fail( 'Instagram認証の状態を確認できませんでした。' );
		}
		$code = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
		if ( '' === $code ) {
			self::fail( 'Instagramから認証コードを取得できませんでした。' );
		}
		$response = wp_remote_post( self::TOKEN_URL, array( 'timeout' => 20, 'body' => array( 'client_id' => ALUMNI_INSTAGRAM_APP_ID, 'client_secret' => ALUMNI_INSTAGRAM_APP_SECRET, 'grant_type' => 'authorization_code', 'redirect_uri' => self::redirect_uri(), 'code' => $code ) ) );
		if ( is_wp_error( $response ) ) {
			self::fail( $response->get_error_message() );
		}
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) || empty( $data['access_token'] ) ) {
			self::fail( 'Instagramアクセストークンを取得できませんでした。' );
		}
		$long = wp_remote_get( add_query_arg( array( 'grant_type' => 'ig_exchange_token', 'client_secret' => ALUMNI_INSTAGRAM_APP_SECRET, 'access_token' => $data['access_token'] ), self::API_BASE . '/access_token' ), array( 'timeout' => 20 ) );
		$long_data = is_wp_error( $long ) ? array() : json_decode( wp_remote_retrieve_body( $long ), true );
		if ( ! is_array( $long_data ) || empty( $long_data['access_token'] ) ) {
			self::fail( 'Instagramの長期アクセストークンを取得できませんでした。' );
		}
		$token = sanitize_text_field( $long_data['access_token'] );
		$user = wp_remote_get( add_query_arg( array( 'fields' => 'id,username,account_type,profile_picture_url', 'access_token' => $token ), self::API_BASE . '/me' ), array( 'timeout' => 20 ) );
		$user_data = is_wp_error( $user ) ? array() : json_decode( wp_remote_retrieve_body( $user ), true );
		if ( ! is_array( $user_data ) || empty( $user_data['id'] ) ) {
			self::fail( 'Instagramアカウント情報を取得できませんでした。' );
		}
		$settings = Social_SNS::get_all();
		$ig = isset( $settings[ Social_SNS::INSTAGRAM ] ) ? $settings[ Social_SNS::INSTAGRAM ] : array();
		$ig['access_token'] = $token;
		$ig['user_id'] = sanitize_text_field( $user_data['id'] );
		$ig['username'] = isset( $user_data['username'] ) ? sanitize_text_field( $user_data['username'] ) : '';
		$ig['account_type'] = isset( $user_data['account_type'] ) ? sanitize_text_field( $user_data['account_type'] ) : '';
		$ig['profile_picture_url'] = isset( $user_data['profile_picture_url'] ) ? esc_url_raw( $user_data['profile_picture_url'] ) : '';
		$ig['token_expires'] = time() + ( isset( $long_data['expires_in'] ) ? absint( $long_data['expires_in'] ) : 60 * DAY_IN_SECONDS );
		$settings[ Social_SNS::INSTAGRAM ] = $ig;
		update_option( self::OPTION_NAME, $settings );
		wp_safe_redirect( add_query_arg( array( 'page' => 'alumni-core-sns', 'updated' => 'true', 'instagram_connected' => 'true' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function feed() {
		$settings = Social_SNS::get_all();
		$ig = isset( $settings[ Social_SNS::INSTAGRAM ] ) ? $settings[ Social_SNS::INSTAGRAM ] : array();
		if ( empty( $ig['access_token'] ) || empty( $ig['user_id'] ) ) {
			return array();
		}
		$response = wp_remote_get( add_query_arg( array( 'fields' => 'id,caption,media_type,media_url,permalink,thumbnail_url,timestamp', 'limit' => 12, 'access_token' => $ig['access_token'] ), self::API_BASE . '/' . rawurlencode( $ig['user_id'] ) . '/media' ), array( 'timeout' => 20 ) );
		if ( is_wp_error( $response ) ) {
			return array();
		}
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		return is_array( $data ) && isset( $data['data'] ) && is_array( $data['data'] ) ? $data['data'] : array();
	}

	public static function connected() {
		$settings = Social_SNS::get_all();
		$ig = isset( $settings[ Social_SNS::INSTAGRAM ] ) ? $settings[ Social_SNS::INSTAGRAM ] : array();
		return ! empty( $ig['access_token'] ) && ! empty( $ig['user_id'] );
	}

	private static function fail( $message ) {
		wp_safe_redirect( add_query_arg( array( 'page' => 'alumni-core-sns', 'instagram_error' => rawurlencode( wp_strip_all_tags( $message ) ) ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
