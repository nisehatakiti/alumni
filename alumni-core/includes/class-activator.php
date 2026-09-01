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
		require_once ALUMNI_CORE_PATH . 'includes/modules/news-events/class-listing-rewrites.php';
		require_once ALUMNI_CORE_PATH . 'includes/modules/news-events/class-module.php';
		require_once ALUMNI_CORE_PATH . 'includes/modules/content/class-post-type.php';
		require_once ALUMNI_CORE_PATH . 'includes/modules/content/class-module.php';
		require_once ALUMNI_CORE_PATH . 'includes/class-graduation-lookup-shortcode.php';
		require_once ALUMNI_CORE_PATH . 'includes/class-officers-shortcode.php';

		Installer::install();
		Settings::instance()->set_defaults();

		// Creates the 卒業期早見表／役員・理事紹介 固定ページ immediately on
		// activation (rather than waiting for the next wp-admin page load
		// to trigger each class's register()'s admin_init hook), and only
		// if one doesn't already exist — see each method's docblock for
		// the "don't duplicate" logic.
		Graduation_Lookup_Shortcode::maybe_create_page();
		Officers_Shortcode::maybe_create_page();

		// register_activation_hook() fires before 'init' on this request,
		// so the post types and rewrite rules must be registered explicitly
		// here — otherwise flush_rewrite_rules() below would flush without
		// them.
		\AlumniCore\Includes\Modules\NewsEvents\Post_Type::register();
		\AlumniCore\Includes\Modules\NewsEvents\Listing_Rewrites::register_rewrite_rules();
		\AlumniCore\Includes\Modules\Content\Post_Type::register();

		flush_rewrite_rules();

		// Record the flush that just happened so each module's
		// maybe_flush_rewrite_rules() doesn't redundantly flush again on
		// the first admin page load.
		update_option(
			\AlumniCore\Includes\Modules\NewsEvents\Module::REWRITE_FLUSHED_OPTION,
			\AlumniCore\Includes\Modules\NewsEvents\Module::REWRITE_VERSION
		);
		update_option(
			\AlumniCore\Includes\Modules\Content\Module::REWRITE_FLUSHED_OPTION,
			\AlumniCore\Includes\Modules\Content\Module::REWRITE_VERSION
		);
	}
}
