<?php
/**
 * Core plugin bootstrap.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes;

if ( ! defined( 'ABSPATH' ) ) {
\texit;
}

/**
 * Central bootstrap class. Loads dependencies and wires up admin / public
 * areas. Kept as a singleton so `alumni_core()` always returns the same
 * instance.
 */
final class Plugin {
\tprivate static $instance = null;
\tprivate $has_run = false;

\tpublic static function instance() {
\t\tif ( null === self::$instance ) self::$instance = new self();
\t\treturn self::$instance;
\t}

\tprivate function __construct() {}

\tpublic function run() {
\t\tif ( $this->has_run ) return;
\t\t$this->has_run = true;
\t\t$this->load_dependencies();
\t\tdo_action( 'alumni_core_loaded' );
\t\tadd_action( 'init', array( $this, 'load_textdomain' ) );
\t\t\\AlumniCore\\Includes\\Modules\\NewsEvents\\Module::register();
\t\t\\AlumniCore\\Includes\\Modules\\Content\\Module::register();
\t\t\\AlumniCore\\Includes\\Modules\\Forms\\Module::register();
\t\t\\AlumniCore\\Includes\\Graduation_Lookup_Shortcode::register();
\t\t\\AlumniCore\\Includes\\School_Enrollment_Years_Shortcode::register();
\t\t\\AlumniCore\\Includes\\Officers_Shortcode::register();
\t\t\\AlumniCore\\Includes\\Officer_List_Groups_Shortcode::register();
\t\t\\AlumniCore\\Includes\\Terms_Listing_Shortcode::register();
\t\t\\AlumniCore\\Includes\\Org_Chart_Shortcode::register();
\t\t\\AlumniCore\\Includes\\Org_Chart_Groups_Shortcode::register();
\t\t\\AlumniCore\\Includes\\Person_Greeting_Groups_Shortcode::register();
\t\t\\AlumniCore\\Includes\\School_Photos_Shortcode::register();
\t\t\\AlumniCore\\Includes\\School_Song_Motto_Shortcode::register();
\t\t\\AlumniCore\\Includes\\Form_Schema_Provider::register();
\t\t\\AlumniCore\\Includes\\Instagram_Feed::register();
\t\t\\AlumniCore\\Includes\\Links::register();
\t\tif ( is_admin() ) {
\t\t\tadd_action( 'admin_init', array( '\\AlumniCore\\Includes\\Installer', 'maybe_upgrade' ) );
\t\t\t( new \\AlumniCore\\Admin\\Admin() )->run();
\t\t}
\t}

\tprivate function load_dependencies() {
\t\trequire_once ALUMNI_CORE_PATH . 'includes/class-installer.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/class-settings.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/class-term-calculator.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/class-officer-lists.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/class-officer-list-groups.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/class-officer-list-groups-shortcode.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/class-graduation-lookup-shortcode.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/class-school-enrollment-years-shortcode.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/class-officers-shortcode.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/class-terms-listing-shortcode.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/class-content-hierarchy.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/class-homepage-sections.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/class-menu-structure.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/class-org-chart-groups.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/class-org-chart.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/class-org-chart-shortcode.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/class-org-chart-groups-shortcode.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/class-person-greeting-groups.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/class-person-greeting-groups-shortcode.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/class-school-photos-shortcode.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/class-school-song-motto-shortcode.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/class-form-schema-provider.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/class-social-sns.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/class-instagram-feed.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/class-links.php';
\t\trequire_once ALUMNI_CORE_PATH . 'public/functions.php';
\t\trequire_once ALUMNI_CORE_PATH . 'public/extension-functions.php';
\t\trequire_once ALUMNI_CORE_PATH . 'public/org-chart-functions.php';
\t\trequire_once ALUMNI_CORE_PATH . 'admin/class-admin.php';
\t\trequire_once ALUMNI_CORE_PATH . 'admin/pages/class-dashboard-page.php';
\t\trequire_once ALUMNI_CORE_PATH . 'admin/pages/class-settings-page.php';
\t\trequire_once ALUMNI_CORE_PATH . 'admin/pages/class-school-photos-page.php';
\t\trequire_once ALUMNI_CORE_PATH . 'admin/pages/class-school-song-motto-page.php';
\t\trequire_once ALUMNI_CORE_PATH . 'admin/pages/class-officers-page.php';
\t\trequire_once ALUMNI_CORE_PATH . 'admin/pages/class-officer-list-order-page.php';
\t\trequire_once ALUMNI_CORE_PATH . 'admin/pages/class-graduation-lookup-page.php';
\t\trequire_once ALUMNI_CORE_PATH . 'admin/pages/class-school-enrollment-years-page.php';
\t\trequire_once ALUMNI_CORE_PATH . 'admin/pages/class-terms-page.php';
\t\trequire_once ALUMNI_CORE_PATH . 'admin/pages/class-homepage-page.php';
\t\trequire_once ALUMNI_CORE_PATH . 'admin/pages/class-menu-page.php';
\t\trequire_once ALUMNI_CORE_PATH . 'admin/pages/class-org-chart-page.php';
\t\trequire_once ALUMNI_CORE_PATH . 'admin/pages/class-org-chart-order-page.php';
\t\trequire_once ALUMNI_CORE_PATH . 'admin/pages/class-org-chart-group-page.php';
\t\trequire_once ALUMNI_CORE_PATH . 'admin/pages/class-person-greeting-order-page.php';
\t\trequire_once ALUMNI_CORE_PATH . 'admin/pages/class-sns-page.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/modules/news-events/class-post-type.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/modules/news-events/class-listing-shortcode.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/modules/news-events/class-meta-box.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/modules/news-events/class-admin-columns.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/modules/news-events/class-required-fields.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/modules/news-events/class-module.php';
\t\trequire_once ALUMNI_CORE_PATH . 'public/news-events-functions.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/modules/content/class-post-type.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/modules/content/class-meta-box.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/modules/content/class-admin-columns.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/modules/content/class-required-fields.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/modules/content/class-module.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/modules/forms/class-post-type.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/modules/forms/class-meta-box.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/modules/forms/class-public.php';
\t\trequire_once ALUMNI_CORE_PATH . 'includes/modules/forms/class-module.php';
\t\trequire_once ALUMNI_CORE_PATH . 'public/content-functions.php';
\t\trequire_once ALUMNI_CORE_PATH . 'public/officers-functions.php';
\t\trequire_once ALUMNI_CORE_PATH . 'public/hierarchy-functions.php';
\t\trequire_once ALUMNI_CORE_PATH . 'public/homepage-functions.php';
\t\trequire_once ALUMNI_CORE_PATH . 'public/menu-functions.php';
\t}

\tpublic function load_textdomain() {
\t\tload_plugin_textdomain( 'alumni-core', false, dirname( ALUMNI_CORE_BASENAME ) . '/languages' );
\t}
}
