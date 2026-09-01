<?php
/**
 * Global template-tag functions for 役員・理事一覧, for theme use.
 *
 * Same rules as public/functions.php: guarded with function_exists(),
 * every function prefixed alumni_core_.
 *
 * 役員・理事情報は複数の「一覧」に分かれている（Officer_Lists）。この
 * ファイルの関数はどれも一覧ID(list_id)を受け取る／返す形になっており、
 * 旧来の「一覧は1つだけ」というAPI形状はここには残していない。
 *
 * @package AlumniCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'alumni_core_get_officer_lists' ) ) {
	/**
	 * Every saved 役員・理事一覧, in display order — metadata only (no
	 * rows), for building an index of lists (see
	 * Includes\Officers_Shortcode::render_index_shortcode()).
	 *
	 * @return array[] Each: array(
	 *     'list_id'=>string, 'name'=>string, 'title'=>string,
	 *     'title_heading'=>string, 'page_id'=>int, 'order'=>int,
	 * ) — 'rows' is intentionally omitted; use
	 * alumni_core_get_officers_for_list() when you need them.
	 */
	function alumni_core_get_officer_lists() {
		$lists = \AlumniCore\Includes\Officer_Lists::instance()->get_all();

		$lists = array_values(
			array_filter(
				$lists,
				function ( $list ) {
					return ! empty( $list['enabled'] );
				}
			)
		);

		return array_map(
			function ( $list ) {
				unset( $list['rows'] );
				return $list;
			},
			$lists
		);
	}
}

if ( ! function_exists( 'alumni_core_get_officer_list' ) ) {
	/**
	 * A single 一覧's metadata (no rows), or null if $list_id doesn't
	 * exist.
	 *
	 * @param string $list_id
	 * @return array|null
	 */
	function alumni_core_get_officer_list( $list_id ) {
		$list = \AlumniCore\Includes\Officer_Lists::instance()->get_list( $list_id );

		if ( null === $list || empty( $list['enabled'] ) ) {
			return null;
		}

		unset( $list['rows'] );

		return $list;
	}
}

if ( ! function_exists( 'alumni_core_get_officers_for_list' ) ) {
	/**
	 * Every saved officer row for one 一覧, in display order, each with
	 * its リンク先 revalidated against the current Media Library / post
	 * state — a linked コンテンツ that was valid when the list was last
	 * saved can go stale later if that post is trashed/deleted, so this is
	 * checked again here rather than trusting the stored value, the same
	 * "revalidate on read" rule used for 校章／同窓会ロゴ／学校写真.
	 *
	 * @param string $list_id
	 * @return array[] Each row: array(
	 *     'row_id'=>string, 'title'=>string, 'name'=>string,
	 *     'term'=>int|string, 'committee'=>string, 'remarks'=>string,
	 *     'order'=>int, 'linked_content_id'=>int (0 = no valid link,
	 *     already revalidated).
	 * ). Empty array when $list_id doesn't exist.
	 */
	function alumni_core_get_officers_for_list( $list_id ) {
		$list = \AlumniCore\Includes\Officer_Lists::instance()->get_list( $list_id );

		if ( null === $list || empty( $list['enabled'] ) ) {
			return array();
		}

		$rows = $list['rows'];

		foreach ( $rows as &$officer ) {
			$linked_id = isset( $officer['linked_content_id'] ) ? (int) $officer['linked_content_id'] : 0;

			$officer['linked_content_id'] = \AlumniCore\Includes\Officer_Lists::is_valid_linked_content( $linked_id ) ? $linked_id : 0;
		}
		unset( $officer );

		return $rows;
	}
}

if ( ! function_exists( 'alumni_core_get_officer_link_url' ) ) {
	/**
	 * The URL an officer row's name/card should link to, or '' when the
	 * row has no link, its link is no longer valid, or the linked content
	 * exists but isn't currently published — themes can call this
	 * unconditionally and just skip the wrapper `<a>` when it comes back
	 * empty.
	 *
	 * @param array $officer One row from alumni_core_get_officers_for_list()
	 *                        (or any array with a 'linked_content_id' key).
	 * @return string
	 */
	function alumni_core_get_officer_link_url( array $officer ) {
		$linked_id = isset( $officer['linked_content_id'] ) ? (int) $officer['linked_content_id'] : 0;

		if ( ! $linked_id ) {
			return '';
		}

		return alumni_core_get_content_url( $linked_id );
	}
}

if ( ! function_exists( 'alumni_core_get_officers_index_url' ) ) {
	/**
	 * The public URL of the 役員・理事紹介 index page (auto-created by
	 * Includes\Officers_Shortcode), listing every 一覧 with a link to
	 * each.
	 *
	 * @return string
	 */
	function alumni_core_get_officers_index_url() {
		return \AlumniCore\Includes\Officers_Shortcode::get_index_url();
	}
}

if ( ! function_exists( 'alumni_core_get_officer_list_url' ) ) {
	/**
	 * The public URL of one 一覧's own auto-created page, or '' when
	 * $list_id doesn't exist / its page hasn't been created yet.
	 *
	 * @param string $list_id
	 * @return string
	 */
	function alumni_core_get_officer_list_url( $list_id ) {
		return \AlumniCore\Includes\Officers_Shortcode::get_list_url( $list_id );
	}
}
