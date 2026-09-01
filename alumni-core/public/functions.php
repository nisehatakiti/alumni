<?php
/**
 * Global template-tag functions for theme / third-party use.
 *
 * These are the only part of Alumni Core a theme should talk to directly.
 * Every function is prefixed with alumni_core_ to avoid collisions, and
 * every call site in a theme should be guarded with function_exists()
 * (or alumni_core_is_active()) so the theme keeps working when this
 * plugin is inactive.
 *
 * @package AlumniCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'alumni_core_is_active' ) ) {
	/**
	 * Themes can call this (guarded with function_exists) to know whether
	 * Alumni Core is available before using any of its data.
	 *
	 * @return bool
	 */
	function alumni_core_is_active() {
		return true;
	}
}

if ( ! function_exists( 'alumni_core_get_setting' ) ) {
	/**
	 * Reads a single 同窓会設定 value.
	 *
	 * @param string $key     Setting key, e.g. 'association_name'.
	 * @param mixed  $default Fallback value.
	 * @return mixed
	 */
	function alumni_core_get_setting( $key, $default = '' ) {
		return \AlumniCore\Includes\Settings::instance()->get( $key, $default );
	}
}

if ( ! function_exists( 'alumni_core_get_settings' ) ) {
	/**
	 * Reads every 同窓会設定 value.
	 *
	 * @return array
	 */
	function alumni_core_get_settings() {
		return \AlumniCore\Includes\Settings::instance()->get_all();
	}
}

if ( ! function_exists( 'alumni_core_graduation_year_to_term' ) ) {
	/**
	 * Converts a graduation year to a graduation term (期), using the
	 * association's configured first graduation year.
	 *
	 * @param int $graduation_year e.g. 1996.
	 * @return int|null
	 */
	function alumni_core_graduation_year_to_term( $graduation_year ) {
		$first_graduation_year = alumni_core_get_setting( 'first_graduation_year' );

		return \AlumniCore\Includes\Term_Calculator::year_to_term( $graduation_year, $first_graduation_year );
	}
}

if ( ! function_exists( 'alumni_core_term_to_year' ) ) {
	/**
	 * Converts a graduation term (期) back to its graduation year.
	 *
	 * @param int $term Graduation term (1-based).
	 * @return int|null
	 */
	function alumni_core_term_to_year( $term ) {
		$first_graduation_year = alumni_core_get_setting( 'first_graduation_year' );

		return \AlumniCore\Includes\Term_Calculator::term_to_year( $term, $first_graduation_year );
	}
}

if ( ! function_exists( 'alumni_core_get_school_emblem_id' ) ) {
	/**
	 * 学校の校章として設定されたメディアの添付ファイルID。
	 *
	 * @return int Attachment ID, or 0 when unset.
	 */
	function alumni_core_get_school_emblem_id() {
		return (int) alumni_core_get_setting( 'school_emblem_id', 0 );
	}
}

if ( ! function_exists( 'alumni_core_get_alumni_logo_id' ) ) {
	/**
	 * 同窓会独自ロゴとして設定されたメディアの添付ファイルID。
	 *
	 * @return int Attachment ID, or 0 when unset.
	 */
	function alumni_core_get_alumni_logo_id() {
		return (int) alumni_core_get_setting( 'alumni_logo_id', 0 );
	}
}

if ( ! function_exists( 'alumni_core_get_school_photo_ids' ) ) {
	/**
	 * 学校関連写真として登録された添付ファイルIDの一覧（表示順）。
	 *
	 * 保存時点では有効だったIDでも、その後メディアライブラリから削除
	 * されている可能性があるため、ここで改めて実在する画像のみに絞り
	 * 込む（Settings::filter_valid_image_attachments()）。呼び出し側
	 * （テーマ側・固定表示のフォールバック判断）は常に「実在する画像
	 * のID」だけを受け取れる。
	 *
	 * @return int[]
	 */
	function alumni_core_get_school_photo_ids() {
		$ids = alumni_core_get_setting( 'school_photo_ids', array() );
		$ids = is_array( $ids ) ? $ids : array();

		return \AlumniCore\Includes\Settings::filter_valid_image_attachments( $ids );
	}
}

if ( ! function_exists( 'alumni_core_get_school_photo_display_mode' ) ) {
	/**
	 * 学校関連写真の表示方式。
	 *
	 * @return string 'fixed' or 'slideshow'.
	 */
	function alumni_core_get_school_photo_display_mode() {
		return alumni_core_get_setting( 'school_photo_display_mode', \AlumniCore\Includes\Settings::PHOTO_MODE_FIXED );
	}
}

if ( ! function_exists( 'alumni_core_get_featured_school_photo_id' ) ) {
	/**
	 * 固定表示（表示方式が 'fixed' の場合）で使う写真の添付ファイルID。
	 * 明示的に選択された写真が無効（未選択、学校関連写真の一覧から外れて
	 * いる、またはメディアライブラリから削除済み）な場合は、一覧の先頭の
	 * 写真にフォールバックする — alumni_core_get_school_photo_ids() が
	 * 既に「実在する画像のみ」に絞り込んだ一覧を返すため、この関数は
	 * その一覧に対して判断するだけでよい。この判断はCore側の責務とし、
	 * テーマ側では単純に「返ってきたIDがあればそれを1枚表示する」だけ
	 * でよい。
	 *
	 * @return int Attachment ID, or 0 when display mode isn't 'fixed', or
	 *              no valid photos are registered at all.
	 */
	function alumni_core_get_featured_school_photo_id() {
		if ( \AlumniCore\Includes\Settings::PHOTO_MODE_FIXED !== alumni_core_get_school_photo_display_mode() ) {
			return 0;
		}

		$photo_ids = alumni_core_get_school_photo_ids();

		if ( empty( $photo_ids ) ) {
			return 0;
		}

		$featured_id = (int) alumni_core_get_setting( 'school_photo_featured_id', 0 );

		return in_array( $featured_id, $photo_ids, true ) ? $featured_id : $photo_ids[0];
	}
}

if ( ! function_exists( 'alumni_core_get_birth_range_for_year' ) ) {
	/**
	 * The standard-progression 誕生日範囲 for a graduation year. See
	 * Term_Calculator::GRADUATION_AGE_YEARS for the underlying assumption
	 * (standard 6-3-3 progression; not a guarantee for any one person).
	 *
	 * @param int $graduation_year e.g. 2026.
	 * @return array{start:string,end:string}|null
	 */
	function alumni_core_get_birth_range_for_year( $graduation_year ) {
		return \AlumniCore\Includes\Term_Calculator::graduation_year_to_birth_range( $graduation_year );
	}
}

if ( ! function_exists( 'alumni_core_get_birth_range_for_term' ) ) {
	/**
	 * The standard-progression 誕生日範囲 for a graduation term (期), using
	 * the association's configured first graduation year.
	 *
	 * @param int $term Graduation term (1-based).
	 * @return array{start:string,end:string}|null
	 */
	function alumni_core_get_birth_range_for_term( $term ) {
		$first_graduation_year = alumni_core_get_setting( 'first_graduation_year' );

		return \AlumniCore\Includes\Term_Calculator::term_to_birth_range( $term, $first_graduation_year );
	}
}

if ( ! function_exists( 'alumni_core_birthdate_to_graduation_year' ) ) {
	/**
	 * Estimates the standard-progression graduation year for a birth date.
	 * Does not depend on any 同窓会設定 (the April-2 cutoff rule alone
	 * determines this).
	 *
	 * @param string $birthdate 'Y-m-d'.
	 * @return int|null
	 */
	function alumni_core_birthdate_to_graduation_year( $birthdate ) {
		return \AlumniCore\Includes\Term_Calculator::birthdate_to_graduation_year( $birthdate );
	}
}

if ( ! function_exists( 'alumni_core_birthdate_to_term' ) ) {
	/**
	 * Estimates the standard-progression graduation term (期) for a birth
	 * date, using the association's configured first graduation year.
	 *
	 * @param string $birthdate 'Y-m-d'.
	 * @return int|null
	 */
	function alumni_core_birthdate_to_term( $birthdate ) {
		$first_graduation_year = alumni_core_get_setting( 'first_graduation_year' );

		return \AlumniCore\Includes\Term_Calculator::birthdate_to_term( $birthdate, $first_graduation_year );
	}
}

if ( ! function_exists( 'alumni_core_get_graduation_lookup_table' ) ) {
	/**
	 * A 卒業期早見表: one row per term in [from_term, to_term], each with
	 * its graduation year, standard-progression birth range, and (when
	 * 卒業期カラー is enabled) its color — keeping this enrichment here
	 * rather than in Term_Calculator, which stays free of WordPress option
	 * lookups by design.
	 *
	 * @param int $from_term First term to include (1-based).
	 * @param int $to_term   Last term to include (inclusive).
	 * @return array[] Each row: array('term'=>int,'year'=>int,
	 *                  'birth_range'=>array|null,'color'=>string|null).
	 */
	function alumni_core_get_graduation_lookup_table( $from_term, $to_term ) {
		$first_graduation_year = alumni_core_get_setting( 'first_graduation_year' );
		$rows                  = \AlumniCore\Includes\Term_Calculator::build_lookup_table( $first_graduation_year, $from_term, $to_term );

		foreach ( $rows as &$row ) {
			$row['color'] = alumni_core_term_to_color( $row['term'] );
		}
		unset( $row );

		return $rows;
	}
}

if ( ! function_exists( 'alumni_core_get_school_photo_slideshow_interval' ) ) {
	/**
	 * 学校関連写真の自動切替（スライドショー）の切替間隔（秒）。固定表示
	 * では無視される設定だが、値そのものは常に返す（呼び出し側でモード
	 * を判断する）。保存値が未設定・不正な場合でも常に有効な範囲の整数を
	 * 返すよう、読み出し時にも Settings::sanitize_slideshow_interval() で
	 * 再検証する — 過去バージョンで保存された値や、直接DBを編集された
	 * ケースにも対応するため。
	 *
	 * @return int 1〜60 の整数。
	 */
	function alumni_core_get_school_photo_slideshow_interval() {
		$raw = alumni_core_get_setting( 'school_photo_slideshow_interval', \AlumniCore\Includes\Settings::DEFAULT_SLIDESHOW_INTERVAL );

		return \AlumniCore\Includes\Settings::sanitize_slideshow_interval( $raw );
	}
}

if ( ! function_exists( 'alumni_core_term_to_color' ) ) {
	/**
	 * Resolves the 卒業期カラー for a graduation term, honoring the
	 * association's ON/OFF switch and configured color cycle.
	 *
	 * @param int $term Graduation term (1-based).
	 * @return string|null Hex color, or null when the color feature is
	 *                      off or no color could be resolved.
	 */
	function alumni_core_term_to_color( $term ) {
		if ( ! alumni_core_get_setting( 'color_feature_enabled', false ) ) {
			return null;
		}

		$cycle  = alumni_core_get_setting( 'color_cycle', 1 );
		$colors = alumni_core_get_setting( 'colors', array() );

		return \AlumniCore\Includes\Term_Calculator::term_to_color( $term, $cycle, $colors );
	}
}

if ( ! function_exists( 'alumni_core_get_graduation_lookup_url' ) ) {
	/**
	 * The public URL of the 卒業期早見表 page (auto-created by
	 * Graduation_Lookup_Shortcode). Themes use this for top-page/menu
	 * links rather than hardcoding the slug.
	 *
	 * @return string
	 */
	function alumni_core_get_graduation_lookup_url() {
		return \AlumniCore\Includes\Graduation_Lookup_Shortcode::get_url();
	}
}

if ( ! function_exists( 'alumni_core_get_officers_listing_url' ) ) {
	/**
	 * The public URL of the 役員・理事紹介 page (auto-created by
	 * Officers_Shortcode). Themes use this for top-page/menu links rather
	 * than hardcoding the slug.
	 *
	 * @return string
	 */
	function alumni_core_get_officers_listing_url() {
		return \AlumniCore\Includes\Officers_Shortcode::get_url();
	}
}

if ( ! function_exists( 'alumni_core_get_nav_layout' ) ) {
	/**
	 * The admin-selected ナビゲーション配置: 'top'（上部メニュー）または
	 * 'side'（左サイドメニュー）. Always one of
	 * \AlumniCore\Includes\Settings::NAV_LAYOUT_TOP /
	 * \AlumniCore\Includes\Settings::NAV_LAYOUT_SIDE, never an arbitrary
	 * string — the setting is re-validated on read via
	 * Settings::sanitize_nav_layout(), the same "revalidate on read"
	 * pattern used elsewhere in this file.
	 *
	 * @return string
	 */
	function alumni_core_get_nav_layout() {
		return \AlumniCore\Includes\Settings::sanitize_nav_layout( alumni_core_get_setting( 'nav_layout', \AlumniCore\Includes\Settings::DEFAULT_NAV_LAYOUT ) );
	}
}
