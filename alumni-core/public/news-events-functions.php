<?php
/**
 * Global template-tag functions for ニュース／イベント, for theme use.
 *
 * Same rules as public/functions.php: these are the only part of the
 * ニュース／イベント module a theme should talk to directly, every
 * function is prefixed with alumni_core_, and every call site in a theme
 * should be guarded with function_exists() so the theme keeps working
 * when this plugin is inactive.
 *
 * @package AlumniCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'alumni_core_news_events_post_type' ) ) {
	/**
	 * The ニュース／イベント post type slug, so themes never hardcode it.
	 *
	 * @return string
	 */
	function alumni_core_news_events_post_type() {
		return \AlumniCore\Includes\Modules\NewsEvents\Post_Type::SLUG;
	}
}

if ( ! function_exists( 'alumni_core_is_event' ) ) {
	/**
	 * @param int|WP_Post|null $post Post ID or object.
	 * @return bool
	 */
	function alumni_core_is_event( $post = null ) {
		return \AlumniCore\Includes\Modules\NewsEvents\Post_Type::is_event( $post );
	}
}

if ( ! function_exists( 'alumni_core_get_event_date' ) ) {
	/**
	 * @param int|WP_Post|null $post Post ID or object.
	 * @return string Y-m-d, or '' when not an event / not set.
	 */
	function alumni_core_get_event_date( $post = null ) {
		return \AlumniCore\Includes\Modules\NewsEvents\Post_Type::get_event_date( $post );
	}
}

if ( ! function_exists( 'alumni_core_get_news_event_display_date' ) ) {
	/**
	 * The date to display for a ニュース／イベント card: the event date
	 * for events, the published date for news.
	 *
	 * @param int|WP_Post|null $post Post ID or object.
	 * @return string Y-m-d, or '' if the post can't be resolved.
	 */
	function alumni_core_get_news_event_display_date( $post = null ) {
		return \AlumniCore\Includes\Modules\NewsEvents\Post_Type::get_display_date( $post );
	}
}

if ( ! function_exists( 'alumni_core_get_news_listing_url' ) ) {
	/**
	 * URL of the ニュース一覧 page (auto-created by
	 * Modules\NewsEvents\Listing_Shortcode).
	 *
	 * @return string
	 */
	function alumni_core_get_news_listing_url() {
		return \AlumniCore\Includes\Modules\NewsEvents\Listing_Shortcode::get_news_url();
	}
}

if ( ! function_exists( 'alumni_core_get_events_listing_url' ) ) {
	/**
	 * URL of the イベント一覧 page (auto-created by
	 * Modules\NewsEvents\Listing_Shortcode).
	 *
	 * @return string
	 */
	function alumni_core_get_events_listing_url() {
		return \AlumniCore\Includes\Modules\NewsEvents\Listing_Shortcode::get_events_url();
	}
}

if ( ! function_exists( 'alumni_core_get_news_teaser' ) ) {
	/**
	 * A short, newest-first ニュース query for a homepage slot — same
	 * shape as alumni_core_get_news_events_query() but pre-filtered to
	 * ニュース only, so Theme code never needs to know the underlying
	 * postmeta key used to distinguish ニュース from イベント.
	 *
	 * @param int $limit
	 * @return WP_Query
	 */
	function alumni_core_get_news_teaser( $limit = 3 ) {
		return alumni_core_get_news_events_query(
			array(
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- filtering by content type is the entire purpose of this query.
					array(
						'key'   => \AlumniCore\Includes\Modules\NewsEvents\Post_Type::META_CONTENT_TYPE,
						'value' => \AlumniCore\Includes\Modules\NewsEvents\Post_Type::TYPE_NEWS,
					),
				),
				'posts_per_page' => max( 1, (int) $limit ),
			)
		);
	}
}

if ( ! function_exists( 'alumni_core_get_events_teaser' ) ) {
	/**
	 * The イベント counterpart of alumni_core_get_news_teaser().
	 *
	 * @param int $limit
	 * @return WP_Query
	 */
	function alumni_core_get_events_teaser( $limit = 3 ) {
		return alumni_core_get_news_events_query(
			array(
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- filtering by content type is the entire purpose of this query.
					array(
						'key'   => \AlumniCore\Includes\Modules\NewsEvents\Post_Type::META_CONTENT_TYPE,
						'value' => \AlumniCore\Includes\Modules\NewsEvents\Post_Type::TYPE_EVENT,
					),
				),
				'posts_per_page' => max( 1, (int) $limit ),
			)
		);
	}
}

if ( ! function_exists( 'alumni_core_get_news_events_query' ) ) {
	/**
	 * Runs a WP_Query for published ニュース／イベント, newest first by
	 * default.
	 *
	 * @param array $args Extra/overriding WP_Query args.
	 * @return WP_Query
	 */
	function alumni_core_get_news_events_query( $args = array() ) {
		$defaults = array(
			'post_type'      => alumni_core_news_events_post_type(),
			'post_status'    => 'publish',
			'posts_per_page' => 5,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		return new WP_Query( wp_parse_args( $args, $defaults ) );
	}
}
