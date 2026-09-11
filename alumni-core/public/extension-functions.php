<?php
/**
 * Public extension API for official and third-party Alumni Core add-ons.
 *
 * @package AlumniCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'alumni_core_get_extension_api_version' ) ) {
	/**
	 * Returns the stable extension API version.
	 *
	 * @return string
	 */
	function alumni_core_get_extension_api_version() {
		return '1.0';
	}
}

if ( ! function_exists( 'alumni_core_is_extension_compatible' ) ) {
	/**
	 * Checks whether the installed Alumni Core extension API satisfies a
	 * minimum version requested by an add-on.
	 *
	 * @param string $minimum_version Minimum required API version.
	 * @return bool
	 */
	function alumni_core_is_extension_compatible( $minimum_version = '1.0' ) {
		return version_compare( alumni_core_get_extension_api_version(), (string) $minimum_version, '>=' );
	}
}

if ( ! function_exists( 'alumni_core_get_admin_menu_slug' ) ) {
	/**
	 * Returns the Alumni Core top-level WordPress admin menu slug.
	 *
	 * Extension plugins should use the alumni_core_register_admin_pages action
	 * instead of modifying Core's menu classes directly.
	 *
	 * @return string
	 */
	function alumni_core_get_admin_menu_slug() {
		return \AlumniCore\Admin\Admin::MENU_SLUG;
	}
}
