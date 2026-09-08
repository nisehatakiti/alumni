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
		$kind_clause = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- filtering by kind is the entire purpose of this clause.
			'key'   => \AlumniCore\Includes\Modules\Content\Post_Type::META_KIND,
			'value' => \AlumniCore\Includes\Modules\Content\Post_Type::KIND_PERSON_GREETING,
		);

		$existing_meta_query = ( isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) ) ? $args['meta_query'] : array();
		$args['meta_query']  = array_merge( array( $kind_clause ), $existing_meta_query );

		return alumni_core_get_contents_query( $args );
	}
}

if ( ! function_exists( 'alumni_core_get_person_greeting_group_members' ) ) {
	/**
	 * 指定した人物挨拶グループに属する公開済み人物挨拶を返す。
	 *
	 * 表示順は管理画面で指定した menu_order の昇順とし、
	 * 同一 menu_order の場合は投稿 ID の昇順で安定的に並べる。
	 *
	 * @param string $group_id グループID。
	 * @return WP_Post[]
	 */
	function alumni_core_get_person_greeting_group_members( $group_id ) {
		$query = alumni_core_get_person_greetings_query(
			array(
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- filtering by kind+group is the entire purpose of this query.
					array(
						'key'   => \AlumniCore\Includes\Modules\Content\Post_Type::META_KIND,
						'value' => \AlumniCore\Includes\Modules\Content\Post_Type::KIND_PERSON_GREETING,
					),
					array(
						'key'   => \AlumniCore\Includes\Modules\Content\Post_Type::META_PERSON_GREETING_GROUP_ID,
						'value' => (string) $group_id,
					),
				),
				'posts_per_page' => -1,
				'orderby'        => array(
					'menu_order' => 'ASC',
					'ID'         => 'ASC',
				),
			)
		);

		return $query->posts;
	}
}

if ( ! function_exists( 'alumni_core_get_content' ) ) {
	function alumni_core_get_content( $id ) {
		$post = get_post( (int) $id );

		if ( ! $post || alumni_core_content_post_type() !== $post->post_type || 'publish' !== $post->post_status ) {
			return null;
		}

		return $post;
	}
}

if ( ! function_exists( 'alumni_core_get_content_url' ) ) {
	function alumni_core_get_content_url( $id ) {
		$post = alumni_core_get_content( $id );

		return $post ? (string) get_permalink( $post ) : '';
	}
}

if ( ! function_exists( 'alumni_core_is_person_greeting' ) ) {
	function alumni_core_is_person_greeting( $post = null ) {
		return \AlumniCore\Includes\Modules\Content\Post_Type::is_person_greeting( $post );
	}
}

if ( ! function_exists( 'alumni_core_get_person_greeting' ) ) {
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

if ( ! function_exists( 'alumni_core_is_terms' ) ) {
	function alumni_core_is_terms( $post = null ) {
		return \AlumniCore\Includes\Modules\Content\Post_Type::is_terms( $post );
	}
}

if ( ! function_exists( 'alumni_core_get_terms_query' ) ) {
	function alumni_core_get_terms_query( $args = array() ) {
		$defaults = array(
			'meta_query' => array(
				array(
					'key'   => \AlumniCore\Includes\Modules\Content\Post_Type::META_KIND,
					'value' => \AlumniCore\Includes\Modules\Content\Post_Type::KIND_TERMS,
				),
			),
			'orderby'    => 'menu_order',
			'order'      => 'ASC',
		);

		return alumni_core_get_contents_query( wp_parse_args( $args, $defaults ) );
	}
}

if ( ! function_exists( 'alumni_core_get_terms' ) ) {
	function alumni_core_get_terms( $post = null ) {
		$post = get_post( $post );

		if ( ! $post || alumni_core_content_post_type() !== $post->post_type || ! alumni_core_is_terms( $post ) ) {
			return null;
		}

		return array(
			'id'                => $post->ID,
			'content_name'      => $post->post_title,
			'display_title'     => \AlumniCore\Includes\Modules\Content\Post_Type::get_terms_display_title( $post ),
			'effective_date'    => \AlumniCore\Includes\Modules\Content\Post_Type::get_terms_effective_date( $post ),
			'revised_date'      => \AlumniCore\Includes\Modules\Content\Post_Type::get_terms_revised_date( $post ),
			'revision_dates'    => \AlumniCore\Includes\Modules\Content\Post_Type::get_terms_revision_dates( $post ),
			'last_revised_date' => \AlumniCore\Includes\Modules\Content\Post_Type::get_terms_last_revised_date( $post ),
			'font_size'         => \AlumniCore\Includes\Modules\Content\Post_Type::get_terms_font_size( $post ),
			'body'              => $post->post_content,
			'status'            => $post->post_status,
			'menu_order'        => (int) $post->menu_order,
		);
	}
}
