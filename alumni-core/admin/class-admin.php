<?php
/**
 * Registers the WordPress admin menu and screens.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Admin;

use AlumniCore\Admin\Pages\Dashboard_Page;
use AlumniCore\Admin\Pages\Settings_Page;
use AlumniCore\Admin\Pages\School_Photos_Page;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the top-level 「同窓会」 menu and its submenus.
 *
 * Kept intentionally small: future modules (名簿, メールマガジン, Voices,
 * その他管理) register their own submenus by hooking the
 * alumni_core_register_admin_pages action instead of editing this class.
 */
class Admin {

	/**
	 * Slug shared by the top-level menu and the dashboard submenu.
	 */
	const MENU_SLUG = 'alumni-core';

	/**
	 * Capability required to see any Alumni Core admin screen.
	 */
	const CAPABILITY = 'manage_options';

	/**
	 * Dashboard screen handler.
	 *
	 * @var Dashboard_Page
	 */
	private $dashboard_page;

	/**
	 * Settings screen handler.
	 *
	 * @var Settings_Page
	 */
	private $settings_page;

	/**
	 * 学校写真 screen handler.
	 *
	 * @var School_Photos_Page
	 */
	private $school_photos_page;

	/**
	 * Registers WordPress hooks.
	 */
	public function run() {
		$this->dashboard_page     = new Dashboard_Page();
		$this->settings_page      = new Settings_Page();
		$this->school_photos_page = new School_Photos_Page();

		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_alumni_core_save_settings', array( $this->settings_page, 'handle_save' ) );
		add_action( 'admin_post_alumni_core_save_school_photos', array( $this->school_photos_page, 'handle_save' ) );
	}

	/**
	 * Adds the 「同窓会」 top-level menu plus its current submenus.
	 */
	public function register_menu() {
		add_menu_page(
			__( '同窓会', 'alumni-core' ),
			__( '同窓会', 'alumni-core' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this->dashboard_page, 'render' ),
			'dashicons-groups',
			26
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'ダッシュボード', 'alumni-core' ),
			__( 'ダッシュボード', 'alumni-core' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this->dashboard_page, 'render' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( '基本設定', 'alumni-core' ),
			__( '基本設定', 'alumni-core' ),
			self::CAPABILITY,
			Settings_Page::SLUG,
			array( $this->settings_page, 'render' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( '学校写真', 'alumni-core' ),
			__( '学校写真', 'alumni-core' ),
			self::CAPABILITY,
			School_Photos_Page::SLUG,
			array( $this->school_photos_page, 'render' )
		);

		/**
		 * Fires after Alumni Core's own submenus are registered, so future
		 * modules (名簿, メールマガジン, Voices, その他管理) can add their
		 * own submenu pages under self::MENU_SLUG without modifying this
		 * class.
		 *
		 * @param string $menu_slug Parent menu slug ('alumni-core').
		 */
		do_action( 'alumni_core_register_admin_pages', self::MENU_SLUG );
	}

	/**
	 * Loads admin CSS/JS only on Alumni Core's own screens.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( false === strpos( $hook_suffix, self::MENU_SLUG ) ) {
			return;
		}

		wp_enqueue_style(
			'alumni-core-admin',
			ALUMNI_CORE_URL . 'admin/assets/css/admin.css',
			array(),
			ALUMNI_CORE_VERSION
		);

		wp_enqueue_script(
			'alumni-core-admin',
			ALUMNI_CORE_URL . 'admin/assets/js/admin.js',
			array(),
			ALUMNI_CORE_VERSION,
			true
		);

		// wp.media() (校章／同窓会ロゴ／学校写真の各ピッカーが利用) is only
		// registered when this is explicitly enqueued.
		wp_enqueue_media();

		wp_enqueue_script(
			'alumni-core-media-picker',
			ALUMNI_CORE_URL . 'admin/assets/js/media-picker.js',
			array(),
			ALUMNI_CORE_VERSION,
			true
		);

		wp_enqueue_script(
			'alumni-core-school-photos-admin',
			ALUMNI_CORE_URL . 'admin/assets/js/school-photos-admin.js',
			array(),
			ALUMNI_CORE_VERSION,
			true
		);
	}
}
