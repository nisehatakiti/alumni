<?php
/**
 * Global template-tag functions for コンテンツ管理, for theme use.
 *
 * Same rules as public/functions.php: these are the only part of the
 * コンテンツ管理 module a theme should talk to directly, every function is
 * prefixed with alumni_core_, and every call site in a theme should be
 * guarded with function_exists() so the theme keeps working when this
 * plugin is inactive.
 *
 * @package AlumniCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'alumni_core_content_post_type' ) ) {
	/**
	 * The コンテンツ post type slug, so themes never hardcode it.
	 *
	 * @return string
	 */
	function alumni_core_content_post_type() {
		return \AlumniCore\Includes\Modules\Content\Post_Type::SLUG;
	}
}

if ( ! function_exists( 'alumni_core_get_contents_query' ) ) {
	/**
	 * Runs a WP_Query for published コンテンツ.
	 *
	 * @param array $args Extra/overriding WP_Query args (e.g. 'meta_query'
	 *                     to filter by kind — see
	 *                     alumni_core_get_person_greetings_query()).
	 * @return WP_Query
	 */
	function alumni_core_get_contents_query( $args = array() ) {
		$defaults = array(
			'post_type'      => alumni_core_content_post_type(),
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		return new WP_Query( wp_parse_args( $args, $defaults ) );
	}
}

if ( ! function_exists( 'alumni_core_get_person_greetings_query' ) ) {
	/**
	 * Runs a WP_Query for published 人物挨拶 コンテンツ only.
	 *
	 * @param array $args Extra/overriding WP_Query args.
	 * @return WP_Query
	 */
	function alumni_core_get_person_greetings_query( $args = array() ) {
		$kind_filter = array(
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- filtering by kind is the entire purpose of this query.
				array(
					'key'   => \AlumniCore\Includes\Modules\Content\Post_Type::META_KIND,
					'value' => \AlumniCore\Includes\Modules\Content\Post_Type::KIND_PERSON_GREETING,
				),
			),
		);

		return alumni_core_get_contents_query( wp_parse_args( $kind_filter, $args ) );
	}
}

if ( ! function_exists( 'alumni_core_get_content' ) ) {
	/**
	 * A single published コンテンツ post, or null if $id doesn't resolve to
	 * one (wrong post type, unpublished, or deleted) — themes never need
	 * to check the post type themselves.
	 *
	 * @param int $id Post ID.
	 * @return WP_Post|null
	 */
	function alumni_core_get_content( $id ) {
		$post = get_post( (int) $id );

		if ( ! $post || alumni_core_content_post_type() !== $post->post_type || 'publish' !== $post->post_status ) {
			return null;
		}

		return $post;
	}
}

if ( ! function_exists( 'alumni_core_get_content_url' ) ) {
	/**
	 * @param int $id Post ID.
	 * @return string Permalink, or '' when the content doesn't exist / isn't
	 *                 published.
	 */
	function alumni_core_get_content_url( $id ) {
		$post = alumni_core_get_content( $id );

		return $post ? (string) get_permalink( $post ) : '';
	}
}

if ( ! function_exists( 'alumni_core_is_person_greeting' ) ) {
	/**
	 * @param int|WP_Post|null $post Post ID or object.
	 * @return bool
	 */
	function alumni_core_is_person_greeting( $post = null ) {
		return \AlumniCore\Includes\Modules\Content\Post_Type::is_person_greeting( $post );
	}
}

if ( ! function_exists( 'alumni_core_get_person_greeting' ) ) {
	/**
	 * Every field a theme needs to render a 人物挨拶 card/page, gathered
	 * into one array — so the theme never has to know this is stored as a
	 * CPT with several postmeta keys.
	 *
	 * @param int|WP_Post|null $post Post ID or object.
	 * @return array{
	 *     id:int, content_name:string, name:string, kana:string,
	 *     title:string, term:int|string, photo_id:int, body:string,
	 *     status:string, created_at:string, updated_at:string
	 * }|null Null when $post doesn't resolve to a 人物挨拶 コンテンツ post
	 *         (any post_status — published-only filtering is the caller's
	 *         responsibility via alumni_core_get_person_greetings_query()).
	 */
	function alumni_core_get_person_greeting( $post = null ) {
		$post = get_post( $post );

		if ( ! $post || alumni_core_content_post_type() !== $post->post_type || ! alumni_core_is_person_greeting( $post ) ) {
			return null;
		}

		return array(
			'id'           => $post->ID,
			'content_name' => $post->post_title,
			'name'         => \AlumniCore\Includes\Modules\Content\Post_Type::get_person_name( $post ),
			'kana'         => \AlumniCore\Includes\Modules\Content\Post_Type::get_person_kana( $post ),
			'title'        => \AlumniCore\Includes\Modules\Content\Post_Type::get_person_title( $post ),
			'term'         => \AlumniCore\Includes\Modules\Content\Post_Type::get_person_term( $post ),
			'photo_id'     => \AlumniCore\Includes\Modules\Content\Post_Type::get_person_photo_id( $post ),
			'body'         => $post->post_content,
			'status'       => $post->post_status,
			'created_at'   => $post->post_date,
			'updated_at'   => $post->post_modified,
		);
	}
}
