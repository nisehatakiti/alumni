<?php
/**
 * Plugin Name:       Alumni Core
 * Plugin URI:         https://github.com/nisehatakiti/alumni
 * Description:       同窓会ホームページパッケージの共通データ基盤。設定管理、卒業期計算などの基盤機能を提供します。
 * Version:            0.1.0
 * Requires at least: 5.9
 * Requires PHP:       7.4
 * Author:              nisehatakiti
 * Author URI:         https://github.com/nisehatakiti/alumni
 * License:             GPL v2 or later
 * License URI:        https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       alumni-core
 * Domain Path:       /languages
 */

namespace AlumniCore;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Direct access is not allowed.
}

// Basic plugin constants. Prefixed to avoid collisions with other plugins/themes.
define( 'ALUMNI_CORE_VERSION', '0.1.0' );
define( 'ALUMNI_CORE_DB_VERSION', '1' );
define( 'ALUMNI_CORE_FILE', __FILE__ );
define( 'ALUMNI_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'ALUMNI_CORE_URL', plugin_dir_url( __FILE__ ) );
define( 'ALUMNI_CORE_BASENAME', plugin_basename( __FILE__ ) );

require_once ALUMNI_CORE_PATH . 'includes/class-plugin.php';
require_once ALUMNI_CORE_PATH . 'includes/class-activator.php';
require_once ALUMNI_CORE_PATH . 'includes/class-deactivator.php';

register_activation_hook( __FILE__, array( Includes\Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Includes\Deactivator::class, 'deactivate' ) );

/**
 * Boots the plugin. Kept as a thin wrapper so other files can check
 * `function_exists( 'alumni_core' )` without loading the whole plugin.
 */
function alumni_core() {
	return Includes\Plugin::instance();
}

alumni_core()->run();
