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
		require_once ALUMNI_CORE_PATH . 'admin/class-admin.php';
		require_once ALUMNI_CORE_PATH . 'includes/modules/news-events/class-post-type.php';
		require_once ALUMNI_CORE_PATH . 'includes/modules/news-events/class-module.php';

		Installer::install();
		Settings::instance()->set_defaults();

		// register_activation_hook() fires before 'init' on this request,
		// so the post type must be registered explicitly here — otherwise
		// flush_rewrite_rules() below would flush without its rules.
		\AlumniCore\Includes\Modules\NewsEvents\Post_Type::register();

		flush_rewrite_rules();

		// Record the flush that just happened so Module::maybe_flush_rewrite_rules()
		// doesn't redundantly flush again on the first admin page load.
		update_option(
			\AlumniCore\Includes\Modules\NewsEvents\Module::REWRITE_FLUSHED_OPTION,
			\AlumniCore\Includes\Modules\NewsEvents\Module::REWRITE_VERSION
		);
	}
}
