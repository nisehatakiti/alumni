<?php
/**
 * Global template-tag functions for 対象者（大カテゴリ）とコンテンツ階層、
 * for theme use.
 *
 * Same rules as public/functions.php: guarded with function_exists(),
 * every function prefixed alumni_core_.
 *
 * @package AlumniCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'alumni_core_get_audience_labels' ) ) {
	/**
	 * Every 対象者 value mapped to its display label, for building select
	 * options / menu group headings without hardcoding Japanese text in
	 * multiple places.
	 *
	 * @return array<string,string>
	 */
	function alumni_core_get_audience_labels() {
		return array(
			\AlumniCore\Includes\Modules\Content\Post_Type::AUDIENCE_ALUMNI  => __( '卒業生向け', 'alumni-core' ),
			\AlumniCore\Includes\Modules\Content\Post_Type::AUDIENCE_STUDENT => __( '在校生向け', 'alumni-core' ),
			\AlumniCore\Includes\Modules\Content\Post_Type::AUDIENCE_COMMON  => __( '共通', 'alumni-core' ),
		);
	}
}

if ( ! function_exists( 'alumni_core_get_content_tree' ) ) {
	/**
	 * The full, published コンテンツ階層 tree for one 対象者 (or every
	 * 対象者 when $audience is null), as a nested array — see
	 * Content_Hierarchy::build_tree() for the exact shape.
	 *
	 * @param string|null $audience \AlumniCore\Includes\Modules\Content\Post_Type::AUDIENCE_*,
	 *                                or null for every 対象者.
	 * @return array[]
	 */
	function alumni_core_get_content_tree( $audience = null ) {
		return \AlumniCore\Includes\Content_Hierarchy::build_tree( $audience, false );
	}
}

if ( ! function_exists( 'alumni_core_get_content_children' ) ) {
	/**
	 * The direct children of one コンテンツ (published only).
	 *
	 * @param int $parent_id 0 for top-level.
	 * @return \WP_Post[]
	 */
	function alumni_core_get_content_children( $parent_id ) {
		return \AlumniCore\Includes\Content_Hierarchy::get_children( $parent_id, null, false );
	}
}

if ( ! function_exists( 'alumni_core_get_content_ancestors' ) ) {
	/**
	 * $post's ancestors, root-first — for breadcrumbs. Does not include
	 * $post itself.
	 *
	 * @param int|\WP_Post|null $post
	 * @return \WP_Post[]
	 */
	function alumni_core_get_content_ancestors( $post ) {
		return \AlumniCore\Includes\Content_Hierarchy::get_ancestors( $post );
	}
}
