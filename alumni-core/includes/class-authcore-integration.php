<?php
/**
 * Optional AuthCore integration.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AuthCore_Integration {
	const APPLICATION_KEY = 'alumni';

	/**
	 * Register the AlumniCore application and its common user-management UI
	 * when AuthCore is active. The integration is deliberately optional so
	 * AlumniCore continues to work without AuthCore.
	 */
	public function run() {
		add_action( 'plugins_loaded', array( $this, 'register_with_authcore' ), 30 );
	}

	public function register_with_authcore() {
		if ( ! class_exists( '\\AuthCore\\AuthCore' ) ) {
			return;
		}

		try {
			\\AuthCore\\AuthCore::registerPlugin( ALUMNI_CORE_FILE );
			\\AuthCore\\AuthCore::registerUserManagementMenu(
				self::APPLICATION_KEY,
				'alumni-core',
				__( 'ユーザー', 'alumni-core' ),
				__( 'AlumniCore ユーザー', 'alumni-core' )
			);
			\\AuthCore\\AuthCore::registerOnboardingMenu(
				self::APPLICATION_KEY,
				'alumni-core',
				__( '初期設定', 'alumni-core' ),
				__( 'AlumniCore 管理者の初期設定', 'alumni-core' )
			);
		} catch ( \Throwable $e ) {
			do_action( 'alumni_core_authcore_integration_error', $e );
		}
	}
}
