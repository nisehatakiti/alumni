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


if ( ! function_exists( 'alumni_core_get_first_graduation_year' ) ) {
	/**
	 * Returns the configured first graduation year.
	 *
	 * @return int|null
	 */
	function alumni_core_get_first_graduation_year() {
		$settings = \AlumniCore\Includes\Settings::instance()->get_all();
		$year     = isset( $settings['first_graduation_year'] ) ? (int) $settings['first_graduation_year'] : 0;

		return $year > 0 ? $year : null;
	}
}

if ( ! function_exists( 'alumni_core_graduation_year_to_term' ) ) {
	/**
	 * Converts a graduation year to its graduation term using the current
	 * Alumni Core school settings.
	 *
	 * @param int $graduation_year
	 * @return int|null
	 */
	function alumni_core_graduation_year_to_term( $graduation_year ) {
		$first_year = alumni_core_get_first_graduation_year();

		if ( null === $first_year ) {
			return null;
		}

		return \AlumniCore\Includes\Term_Calculator::year_to_term( $graduation_year, $first_year );
	}
}

if ( ! function_exists( 'alumni_core_graduation_term_to_year' ) ) {
	/**
	 * Converts a graduation term to its graduation year using the current
	 * Alumni Core school settings.
	 *
	 * @param int $term
	 * @return int|null
	 */
	function alumni_core_graduation_term_to_year( $term ) {
		$first_year = alumni_core_get_first_graduation_year();

		if ( null === $first_year ) {
			return null;
		}

		return \AlumniCore\Includes\Term_Calculator::term_to_year( $term, $first_year );
	}
}

/**
 * Filter bridge for extensions that need year → term conversion.
 *
 * @param int|null $term             Existing resolved term.
 * @param int      $graduation_year  Graduation year.
 * @param int|null $context_id       Optional related object ID.
 * @return int|null
 */
add_filter( 'alumni_core_graduation_term_from_year', function ( $term, $graduation_year, $context_id = null ) {
	if ( null !== $term && '' !== $term ) {
		return $term;
	}

	return alumni_core_graduation_year_to_term( $graduation_year );
}, 10, 3 );
