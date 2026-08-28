<?php
/**
 * Creates / upgrades the plugin's custom database tables.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * No custom tables ship in this first foundation release, but the
 * scaffolding is in place so future modules (Directory, Voices, Mail, ...)
 * can register their schema here via the alumni_core_db_schema filter and
 * get dbDelta() upgrades for free on plugin activation.
 */
class Installer {

	/**
	 * Option name storing the currently installed DB schema version.
	 */
	const DB_VERSION_OPTION = 'alumni_core_db_version';

	/**
	 * Creates or upgrades all registered tables and records the DB version.
	 */
	public static function install() {
		self::create_tables();
		update_option( self::DB_VERSION_OPTION, ALUMNI_CORE_DB_VERSION );
	}

	/**
	 * Runs dbDelta() against every table schema registered by Core and by
	 * future modules.
	 */
	private static function create_tables() {
		$schemas = self::get_schemas();

		if ( empty( $schemas ) ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		foreach ( $schemas as $sql ) {
			dbDelta( $sql );
		}
	}

	/**
	 * Collects CREATE TABLE statements to run through dbDelta().
	 *
	 * Modules add their own tables with:
	 *     add_filter( 'alumni_core_db_schema', function ( $schemas ) {
	 *         $schemas[] = "CREATE TABLE ...";
	 *         return $schemas;
	 *     } );
	 *
	 * @return string[] CREATE TABLE statements.
	 */
	private static function get_schemas() {
		/**
		 * Filters the list of CREATE TABLE statements installed on
		 * activation. Empty by default; reserved for future modules.
		 *
		 * @param string[] $schemas CREATE TABLE statements.
		 */
		return apply_filters( 'alumni_core_db_schema', array() );
	}
}
