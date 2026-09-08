<?php
/**
 * Global template-tag functions for メニュー構成 (Menu_Structure), for
 * theme use.
 *
 * Same rules as public/functions.php: guarded with function_exists(),
 * every function prefixed alumni_core_. Every node returned already
 * carries its resolved label/url — Theme never has to know whether a node
 * is a folder, a piece of content, a system page, or a 役員・理事一覧
 * (see Menu_Structure::resolve_item()).
 *
 * @package AlumniCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'alumni_core_get_menu_tree' ) ) {
	/**
	 * The public メニュー構成 tree for one 対象者 (or every 対象者 when
	 * $audience is null) — see Menu_Structure::get_tree().
	 *
	 * @param string|null $audience \AlumniCore\Includes\Menu_Structure::AUDIENCE_*,
	 *                                or null for every 対象者.
	 * @return array[]
	 */
	function alumni_core_get_menu_tree( $audience = null ) {
		return \AlumniCore\Includes\Menu_Structure::instance()->get_tree( $audience );
	}
}

if ( ! function_exists( 'alumni_core_get_menu_ancestors_for_content' ) ) {
	/**
	 * Resolved ancestors (root-first) of the first メニュー項目 referencing
	 * $content_id — for パンくず. See
	 * Menu_Structure::get_ancestors_for_content().
	 *
	 * @param int $content_id
	 * @return array[]
	 */
	function alumni_core_get_menu_ancestors_for_content( $content_id ) {
		return \AlumniCore\Includes\Menu_Structure::instance()->get_ancestors_for_content( $content_id );
	}
}
