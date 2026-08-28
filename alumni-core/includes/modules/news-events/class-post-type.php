<?php
/**
 * ニュース／イベント共通コンテンツの Custom Post Type.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes\Modules\NewsEvents;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the alumni_news_event post type and exposes the small set of
 * helpers (content type / event date / display date) that both the admin
 * screens and the theme need, so this business logic lives here once
 * instead of being duplicated on either side.
 */
class Post_Type {

	/**
	 * Post type slug.
	 */
	const SLUG = 'alumni_news_event';

	/**
	 * Meta key storing the content type ('news' or 'event').
	 */
	const META_CONTENT_TYPE = '_alumni_content_type';

	/**
	 * Meta key storing the event date (Y-m-d). Only meaningful when the
	 * content type is 'event'.
	 */
	const META_EVENT_DATE = '_alumni_event_date';

	/**
	 * Content type values.
	 */
	const TYPE_NEWS  = 'news';
	const TYPE_EVENT = 'event';

	/**
	 * Registers the post type. Hooked to 'init' so it runs on every
	 * request (front-end and admin) — the archive/single URLs and the
	 * admin submenu both depend on it.
	 */
	public static function register() {
		register_post_type(
			self::SLUG,
			array(
				'labels'       => array(
					'name'               => __( 'ニュース・イベント', 'alumni-core' ),
					'singular_name'      => __( 'ニュース/イベント', 'alumni-core' ),
					'menu_name'          => __( 'ニュース・イベント', 'alumni-core' ),
					'all_items'          => __( 'すべてのニュース・イベント', 'alumni-core' ),
					'add_new'            => __( '新規追加', 'alumni-core' ),
					'add_new_item'       => __( '新規ニュース/イベントを追加', 'alumni-core' ),
					'edit_item'          => __( 'ニュース/イベントを編集', 'alumni-core' ),
					'new_item'           => __( '新規ニュース/イベント', 'alumni-core' ),
					'view_item'          => __( 'ニュース/イベントを表示', 'alumni-core' ),
					'view_items'         => __( 'ニュース・イベントを表示', 'alumni-core' ),
					'search_items'       => __( 'ニュース/イベントを検索', 'alumni-core' ),
					'not_found'          => __( 'ニュース/イベントが見つかりません', 'alumni-core' ),
					'not_found_in_trash' => __( 'ゴミ箱にニュース/イベントは見つかりません', 'alumni-core' ),
				),
				'public'       => true,
				'show_ui'      => true,
				// Nests this CPT's own list/add-new/edit screens under the
				// 同窓会 top-level menu instead of creating a new one.
				'show_in_menu' => \AlumniCore\Admin\Admin::MENU_SLUG,
				'show_in_rest' => true,
				'supports'     => array( 'title', 'editor', 'thumbnail' ),
				'has_archive'  => 'news-events',
				'rewrite'      => array(
					'slug'       => 'news-events',
					'with_front' => false,
				),
			)
		);
	}

	/**
	 * Returns the saved content type, defaulting to 'news' for anything
	 * missing or unrecognized.
	 *
	 * @param int|\WP_Post|null $post Post ID or object. Defaults to the
	 *                                 current global post.
	 * @return string self::TYPE_NEWS or self::TYPE_EVENT.
	 */
	public static function get_content_type( $post = null ) {
		$post = get_post( $post );

		if ( ! $post ) {
			return self::TYPE_NEWS;
		}

		$type = get_post_meta( $post->ID, self::META_CONTENT_TYPE, true );

		return self::TYPE_EVENT === $type ? self::TYPE_EVENT : self::TYPE_NEWS;
	}

	/**
	 * @param int|\WP_Post|null $post Post ID or object.
	 * @return bool
	 */
	public static function is_event( $post = null ) {
		return self::TYPE_EVENT === self::get_content_type( $post );
	}

	/**
	 * Returns the saved event date (Y-m-d), or '' when the post isn't an
	 * event or has no date saved.
	 *
	 * @param int|\WP_Post|null $post Post ID or object.
	 * @return string
	 */
	public static function get_event_date( $post = null ) {
		$post = get_post( $post );

		if ( ! $post || ! self::is_event( $post ) ) {
			return '';
		}

		$date = get_post_meta( $post->ID, self::META_EVENT_DATE, true );

		return is_string( $date ) ? $date : '';
	}

	/**
	 * The date to display for a card/listing: the event date for events
	 * (falling back to the post date if one is somehow missing), the
	 * post's published date for news. Centralized here so neither the
	 * admin screens nor the theme have to decide which date "wins".
	 *
	 * @param int|\WP_Post|null $post Post ID or object.
	 * @return string Y-m-d, or '' if the post can't be resolved.
	 */
	public static function get_display_date( $post = null ) {
		$post = get_post( $post );

		if ( ! $post ) {
			return '';
		}

		$event_date = self::get_event_date( $post );

		return $event_date ? $event_date : get_the_date( 'Y-m-d', $post );
	}
}
