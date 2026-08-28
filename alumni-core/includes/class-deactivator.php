<?php
/**
 * Runs on plugin deactivation.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles deactivation-time cleanup. Deactivation never deletes data —
 * that is uninstall.php's job, and only when the user explicitly deletes
 * the plugin from the Plugins screen.
 */
class Deactivator {

	/**
	 * Fired by register_deactivation_hook().
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
