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
