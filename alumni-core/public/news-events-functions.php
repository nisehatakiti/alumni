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

if ( ! function_exists( 'alumni_core_get_news_events_listing_type' ) ) {
	/**
	 * The current request's listing type: 'news' on /news/, 'event' on
	 * /events/, or '' on the combined /news-events/ archive (or anywhere
	 * else) — so themes can vary the archive heading without touching
	 * rewrite/query internals directly.
	 *
	 * @return string
	 */
	function alumni_core_get_news_events_listing_type() {
		return \AlumniCore\Includes\Modules\NewsEvents\Listing_Rewrites::get_listing_type();
	}
}

if ( ! function_exists( 'alumni_core_get_news_listing_url' ) ) {
	/**
	 * URL of the ニュース-only listing (/news/).
	 *
	 * @return string
	 */
	function alumni_core_get_news_listing_url() {
		return home_url( '/news/' );
	}
}

if ( ! function_exists( 'alumni_core_get_events_listing_url' ) ) {
	/**
	 * URL of the イベント-only listing (/events/).
	 *
	 * @return string
	 */
	function alumni_core_get_events_listing_url() {
		return home_url( '/events/' );
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
