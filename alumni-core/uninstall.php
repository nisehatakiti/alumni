<?php
/**
 * Fires only when the plugin is deleted from the Plugins screen (never on
 * simple deactivation).
 *
 * @package AlumniCore
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Standard WordPress plugin practice: deactivating a plugin never touches
 * its data, and deleting it shouldn't either by default — a site owner
 * who deletes and later reinstalls Alumni Core (or upgrades by deleting
 * the old copy and uploading a new one) expects 同窓会基本設定・役員一覧
 * ・登録済みコンテンツ to still be there, not to have to re-enter
 * everything. So by default this file removes nothing.
 *
 * A site that genuinely wants a full wipe on delete can opt in with:
 *     add_filter( 'alumni_core_uninstall_delete_user_data', '__return_true' );
 * in mu-plugins (a normal plugin's own filters are gone by the time
 * uninstall.php runs, since WordPress has already deactivated it — only
 * mu-plugins, which never deactivate, are still loaded at this point).
 * This intentionally stays a single filter rather than an admin-screen
 * toggle: a delete-my-data checkbox is easy to click by accident, while a
 * filter requires deliberately writing code.
 */
$alumni_core_delete_user_data = apply_filters( 'alumni_core_uninstall_delete_user_data', false );

if ( $alumni_core_delete_user_data ) {
	delete_option( 'alumni_core_settings' );
	delete_option( 'alumni_core_officers' );
	delete_option( 'alumni_core_graduation_lookup_page_id' );

	// alumni_news_event / alumni_content posts (news, events, and every
	// コンテンツ including 人物挨拶) are deliberately left alone even in
	// this opt-in full-wipe path: WordPress's own "delete this plugin"
	// action has never implied "delete the posts made while it was
	// active" for any other CPT-registering plugin, and reversing that
	// here would be surprising. A site that wants those gone too can
	// delete them the normal way (Trash → Empty Trash) before or after
	// removing the plugin.
}

// Bookkeeping-only options: pure internal/derived state with no
// user-entered content, so clearing them is never data loss (worst case:
// one extra rewrite-rules flush or reinstall page-lookup after
// reactivation). Always removed, regardless of the filter above.
delete_option( 'alumni_core_db_version' );
delete_option( 'alumni_news_event_rewrite_flushed' );
delete_option( 'alumni_content_rewrite_flushed' );

// Future modules are expected to clean up their own tables/options here
// (or via an alumni_core_uninstall action) once they exist, following the
// same user-data-vs-bookkeeping distinction as above.
