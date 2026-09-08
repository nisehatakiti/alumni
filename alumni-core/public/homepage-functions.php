<?php
/**
 * Global template-tag functions for the トップページ セクション設定, for
 * theme use.
 *
 * Same rules as public/functions.php: guarded with function_exists(),
 * every function prefixed alumni_core_. Core never decides HOW a slot is
 * displayed (that's the Theme's job) — these functions only expose WHAT
 * has been assigned to each slot.
 *
 * @package AlumniCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'alumni_core_get_homepage_sections' ) ) {
	/**
	 * Every トップページ section, in display order, with every slot already
	 * revalidated (a slot pointing at content that's since been
	 * unpublished/deleted comes back as type=none — see
	 * Homepage_Sections::normalize_slot()).
	 *
	 * @return array[] Each: array(
	 *     'section_id'=>string, 'order'=>int, 'heading'=>string,
	 *     'columns'=>int(1-3), 'slots'=>array[] (each: array('type'=>'none')
	 *     or array('type'=>'content','content_id'=>int) or
	 *     array('type'=>'system','system_key'=>string)).
	 * )
	 */
	function alumni_core_get_homepage_sections() {
		return \AlumniCore\Includes\Homepage_Sections::instance()->get_all();
	}
}

if ( ! function_exists( 'alumni_core_get_system_slot_keys' ) ) {
	/**
	 * Every valid type=system slot key a section's slot can reference.
	 *
	 * @return string[]
	 */
	function alumni_core_get_system_slot_keys() {
		return \AlumniCore\Includes\Homepage_Sections::system_keys();
	}
}

if ( ! function_exists( 'alumni_core_get_system_slot_label' ) ) {
	/**
	 * @param string $system_key
	 * @return string Empty string for an unrecognized key.
	 */
	function alumni_core_get_system_slot_label( $system_key ) {
		$labels = \AlumniCore\Includes\Homepage_Sections::system_key_labels();

		return isset( $labels[ $system_key ] ) ? $labels[ $system_key ] : '';
	}
}

if ( ! function_exists( 'alumni_core_get_system_slot_url' ) ) {
	/**
	 * The public URL a type=system slot should link to.
	 *
	 * @param string $system_key
	 * @return string Empty string for an unrecognized key.
	 */
	function alumni_core_get_system_slot_url( $system_key ) {
		return \AlumniCore\Includes\Homepage_Sections::resolve_system_url( $system_key );
	}
}
