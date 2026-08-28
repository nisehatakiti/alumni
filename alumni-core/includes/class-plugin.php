<?php
/**
 * Core plugin bootstrap.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central bootstrap class. Loads dependencies and wires up admin / public
 * areas. Kept as a singleton so `alumni_core()` always returns the same
 * instance.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Whether run() has already executed, so repeated calls are harmless.
	 *
	 * @var bool
	 */
	private $has_run = false;

	/**
	 * Returns the singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Use instance() instead.
	 */
	private function __construct() {}

	/**
	 * Loads dependencies and registers hooks. Safe to call multiple times.
	 */
	public function run() {
		if ( $this->has_run ) {
			return;
		}
		$this->has_run = true;

		$this->load_dependencies();

		add_action( 'init', array( $this, 'load_textdomain' ) );

		if ( is_admin() ) {
			( new \AlumniCore\Admin\Admin() )->run();
		}
	}

	/**
	 * Requires the classes that make up the plugin.
	 */
	private function load_dependencies() {
		require_once ALUMNI_CORE_PATH . 'includes/class-installer.php';
		require_once ALUMNI_CORE_PATH . 'includes/class-settings.php';
		require_once ALUMNI_CORE_PATH . 'includes/class-term-calculator.php';

		require_once ALUMNI_CORE_PATH . 'public/functions.php';

		require_once ALUMNI_CORE_PATH . 'admin/class-admin.php';
		require_once ALUMNI_CORE_PATH . 'admin/pages/class-dashboard-page.php';
		require_once ALUMNI_CORE_PATH . 'admin/pages/class-settings-page.php';
	}

	/**
	 * Loads plugin translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'alumni-core', false, dirname( ALUMNI_CORE_BASENAME ) . '/languages' );
	}
}
