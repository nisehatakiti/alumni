<?php
/**
 * Runs on plugin activation.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles activation-time setup: DB tables and default settings.
 */
class Activator {

	/**
	 * Fired by register_activation_hook().
	 */
	public static function activate() {
		require_once ALUMNI_CORE_PATH . 'includes/class-installer.php';
		require_once ALUMNI_CORE_PATH . 'includes/class-settings.php';

		Installer::install();
		Settings::instance()->set_defaults();

		flush_rewrite_rules();
	}
}
