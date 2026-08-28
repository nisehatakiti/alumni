<?php
/**
 * Fires only when the plugin is deleted from the Plugins screen (never on
 * simple deactivation). Removes the data Alumni Core owns.
 *
 * @package AlumniCore
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'alumni_core_settings' );
delete_option( 'alumni_core_db_version' );

// No custom tables ship yet; future modules are expected to clean up their
// own tables/options here (or via an alumni_core_uninstall action) once
// they exist.
