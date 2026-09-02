<?php
/**
 * Global template-tag functions for 役員・理事紹介, for theme use.
 *
 * Same rules as public/functions.php: guarded with function_exists(),
 * every function prefixed alumni_core_.
 *
 * @package AlumniCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'alumni_core_get_officers' ) ) {
	/**
	 * Every saved officer row, in display order, each with its リンク先
	 * revalidated against the current Media Library / post state — a
	 * linked コンテンツ that was valid when the officer list was last
	 * saved can go stale later if that post is trashed/deleted, so this
	 * is checked again here rather than trusting the stored value, the
	 * same "revalidate on read" rule used for 校章／同窓会ロゴ／学校写真.
	 *
	 * @return array[] Each row: array(
	 *     'row_id'=>string, 'term'=>int|string, 'title'=>string,
	 *     'committee'=>string, 'name'=>string, 'order'=>int,
	 *     'linked_content_id'=>int (0 = no valid link, already revalidated),
	 * ).
	 */
	function alumni_core_get_officers() {
		$officers = \AlumniCore\Includes\Officers::instance()->get_all();

		foreach ( $officers as &$officer ) {
			$linked_id = isset( $officer['linked_content_id'] ) ? (int) $officer['linked_content_id'] : 0;

			$officer['linked_content_id'] = \AlumniCore\Includes\Officers::is_valid_linked_content( $linked_id ) ? $linked_id : 0;
		}
		unset( $officer );

		return $officers;
	}
}

if ( ! function_exists( 'alumni_core_get_officer_link_url' ) ) {
	/**
	 * The URL an officer row's name/card should link to, or '' when the
	 * row has no link, its link is no longer valid, or the linked content
	 * exists but isn't currently published (e.g. reverted to draft) —
	 * themes can call this unconditionally and just skip the wrapper `<a>`
	 * when it comes back empty. Note this can return '' even for a row
	 * whose 'linked_content_id' from alumni_core_get_officers() is
	 * nonzero: that field only confirms the linked post still exists,
	 * while this function additionally requires it to be publicly
	 * viewable right now.
	 *
	 * @param array $officer One row from alumni_core_get_officers() (or
	 *                        any array with a 'linked_content_id' key).
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
