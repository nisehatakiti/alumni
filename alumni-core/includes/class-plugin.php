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

		\AlumniCore\Includes\Modules\NewsEvents\Module::register();
		\AlumniCore\Includes\Modules\Content\Module::register();
		\AlumniCore\Includes\Graduation_Lookup_Shortcode::register();
		\AlumniCore\Includes\Officers_Shortcode::register();
		\AlumniCore\Includes\Terms_Listing_Shortcode::register();

		if ( is_admin() ) {
			add_action( 'admin_init', array( '\AlumniCore\Includes\Installer', 'maybe_upgrade' ) );
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
		require_once ALUMNI_CORE_PATH . 'includes/class-officer-lists.php';
		require_once ALUMNI_CORE_PATH . 'includes/class-graduation-lookup-shortcode.php';
		require_once ALUMNI_CORE_PATH . 'includes/class-officers-shortcode.php';
		require_once ALUMNI_CORE_PATH . 'includes/class-terms-listing-shortcode.php';
		require_once ALUMNI_CORE_PATH . 'includes/class-content-hierarchy.php';
		require_once ALUMNI_CORE_PATH . 'includes/class-homepage-sections.php';
		require_once ALUMNI_CORE_PATH . 'includes/class-menu-structure.php';

		require_once ALUMNI_CORE_PATH . 'public/functions.php';

		require_once ALUMNI_CORE_PATH . 'admin/class-admin.php';
		require_once ALUMNI_CORE_PATH . 'admin/pages/class-dashboard-page.php';
		require_once ALUMNI_CORE_PATH . 'admin/pages/class-settings-page.php';
		require_once ALUMNI_CORE_PATH . 'admin/pages/class-school-photos-page.php';
		require_once ALUMNI_CORE_PATH . 'admin/pages/class-officers-page.php';
		require_once ALUMNI_CORE_PATH . 'admin/pages/class-graduation-lookup-page.php';
		require_once ALUMNI_CORE_PATH . 'admin/pages/class-terms-page.php';
		require_once ALUMNI_CORE_PATH . 'admin/pages/class-homepage-page.php';
		require_once ALUMNI_CORE_PATH . 'admin/pages/class-menu-page.php';

		require_once ALUMNI_CORE_PATH . 'includes/modules/news-events/class-post-type.php';
		require_once ALUMNI_CORE_PATH . 'includes/modules/news-events/class-listing-shortcode.php';
		require_once ALUMNI_CORE_PATH . 'includes/modules/news-events/class-meta-box.php';
		require_once ALUMNI_CORE_PATH . 'includes/modules/news-events/class-admin-columns.php';
		require_once ALUMNI_CORE_PATH . 'includes/modules/news-events/class-required-fields.php';
		require_once ALUMNI_CORE_PATH . 'includes/modules/news-events/class-module.php';

		require_once ALUMNI_CORE_PATH . 'public/news-events-functions.php';

		require_once ALUMNI_CORE_PATH . 'includes/modules/content/class-post-type.php';
		require_once ALUMNI_CORE_PATH . 'includes/modules/content/class-meta-box.php';
		require_once ALUMNI_CORE_PATH . 'includes/modules/content/class-admin-columns.php';
		require_once ALUMNI_CORE_PATH . 'includes/modules/content/class-required-fields.php';
		require_once ALUMNI_CORE_PATH . 'includes/modules/content/class-module.php';

		require_once ALUMNI_CORE_PATH . 'public/content-functions.php';
		require_once ALUMNI_CORE_PATH . 'public/officers-functions.php';
		require_once ALUMNI_CORE_PATH . 'public/hierarchy-functions.php';
		require_once ALUMNI_CORE_PATH . 'public/homepage-functions.php';
		require_once ALUMNI_CORE_PATH . 'public/menu-functions.php';
	}

	/**
	 * Loads plugin translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'alumni-core', false, dirname( ALUMNI_CORE_BASENAME ) . '/languages' );
	}
}
