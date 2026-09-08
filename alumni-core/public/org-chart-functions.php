<?php
/**
 * Global template-tag functions for 同窓会組織図 (Org_Chart), for theme use.
 *
 * Same rules as public/functions.php: these are the only part of the
 * 組織図 feature a theme should talk to directly, every function is
 * prefixed with alumni_core_, and every call site in a theme should be
 * guarded with function_exists() so the theme keeps working when this
 * plugin is inactive.
 *
 * @package AlumniCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'alumni_core_get_org_chart_tree' ) ) {
	/**
	 * The full 組織図 tree, root-first. See
	 * \AlumniCore\Includes\Org_Chart::get_tree().
	 *
	 * @return array[] Each: array('node_id'=>string,'name'=>string,'children'=>(same shape)).
	 *                  Empty array when no nodes exist yet.
	 */
	function alumni_core_get_org_chart_tree() {
		return \AlumniCore\Includes\Org_Chart::instance()->get_tree();
	}
}

if ( ! function_exists( 'alumni_core_get_org_chart_url' ) ) {
	/**
	 * URL of the 同窓会組織図 page (auto-created by Org_Chart_Shortcode).
	 *
	 * @return string
	 */
	function alumni_core_get_org_chart_url() {
		return \AlumniCore\Includes\Org_Chart_Shortcode::get_url();
	}
}
