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
use AlumniCore\Admin\Pages\Officers_Page;
use AlumniCore\Admin\Pages\Graduation_Lookup_Page;
use AlumniCore\Admin\Pages\Terms_Page;
use AlumniCore\Admin\Pages\Homepage_Page;
use AlumniCore\Includes\Modules\Content\Post_Type as Content_Post_Type;

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
	 * 役員・理事紹介 screen handler.
	 *
	 * @var Officers_Page
	 */
	private $officers_page;

	/**
	 * 卒業期早見表 screen handler.
	 *
	 * @var Graduation_Lookup_Page
	 */
	private $graduation_lookup_page;

	/**
	 * 規約類 screen handler.
	 *
	 * @var Terms_Page
	 */
	private $terms_page;

	/**
	 * トップページ設定 screen handler.
	 *
	 * @var Homepage_Page
	 */
	private $homepage_page;

	/**
	 * Hook suffix for 基本設定, as returned by add_submenu_page(). Used to
	 * scope the media-library assets to just this screen.
	 *
	 * @var string
	 */
	private $settings_hook = '';

	/**
	 * Hook suffix for 学校写真, as returned by add_submenu_page(). Used to
	 * scope the media-library assets to just this screen.
	 *
	 * @var string
	 */
	private $school_photos_hook = '';

	/**
	 * Hook suffix for 役員・理事紹介, as returned by add_submenu_page().
	 * Used to scope its admin JS to just this screen.
	 *
	 * @var string
	 */
	private $officers_hook = '';

	/**
	 * Registers WordPress hooks.
	 */
	public function run() {
		$this->dashboard_page         = new Dashboard_Page();
		$this->settings_page          = new Settings_Page();
		$this->school_photos_page     = new School_Photos_Page();
		$this->officers_page          = new Officers_Page();
		$this->graduation_lookup_page = new Graduation_Lookup_Page();
		$this->terms_page             = new Terms_Page();
		$this->homepage_page           = new Homepage_Page();

		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_alumni_core_save_settings', array( $this->settings_page, 'handle_save' ) );
		add_action( 'admin_post_alumni_core_save_school_photos', array( $this->school_photos_page, 'handle_save' ) );
		add_action( 'admin_post_alumni_core_create_officer_list', array( $this->officers_page, 'handle_create' ) );
		add_action( 'admin_post_alumni_core_delete_officer_list', array( $this->officers_page, 'handle_delete' ) );
		add_action( 'admin_post_alumni_core_save_officer_list', array( $this->officers_page, 'handle_save' ) );
		add_action( 'admin_post_alumni_core_create_homepage_section', array( $this->homepage_page, 'handle_create' ) );
		add_action( 'admin_post_alumni_core_delete_homepage_section', array( $this->homepage_page, 'handle_delete' ) );
		add_action( 'admin_post_alumni_core_move_homepage_section', array( $this->homepage_page, 'handle_move' ) );
		add_action( 'admin_post_alumni_core_save_homepage_sections', array( $this->homepage_page, 'handle_save' ) );
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

		$this->settings_hook = add_submenu_page(
			self::MENU_SLUG,
			__( '基本設定', 'alumni-core' ),
			__( '基本設定', 'alumni-core' ),
			self::CAPABILITY,
			Settings_Page::SLUG,
			array( $this->settings_page, 'render' )
		);

		// 卒業期早見表 is placed right after 基本設定 (its actual data
		// source — 学校創立年／第1期卒業年／卒業期カラー all live there),
		// deliberately NOT adjacent to the コンテンツ / 人物挨拶 entries
		// below. 卒業期早見表 has no relationship to 人物挨拶: it's an
		// independent, always-available system page (see
		// Graduation_Lookup_Shortcode), not "a kind of content" — this
		// menu ordering, and the flat (non-indented) labels on the コンテ
		// ンツ shortcuts further down, both exist so the admin screen
		// doesn't visually suggest a parent/child relationship that
		// doesn't exist in the data or the URL structure (/graduation-lookup/
		// vs /contents/...  are completely independent).
		add_submenu_page(
			self::MENU_SLUG,
			__( '卒業期早見表', 'alumni-core' ),
			__( '卒業期早見表', 'alumni-core' ),
			self::CAPABILITY,
			Graduation_Lookup_Page::SLUG,
			array( $this->graduation_lookup_page, 'render' )
		);

		$this->school_photos_hook = add_submenu_page(
			self::MENU_SLUG,
			__( '学校写真', 'alumni-core' ),
			__( '学校写真', 'alumni-core' ),
			self::CAPABILITY,
			School_Photos_Page::SLUG,
			array( $this->school_photos_page, 'render' )
		);

		$this->officers_hook = add_submenu_page(
			self::MENU_SLUG,
			__( '役員・理事紹介', 'alumni-core' ),
			__( '役員・理事紹介', 'alumni-core' ),
			self::CAPABILITY,
			Officers_Page::SLUG,
			array( $this->officers_page, 'render' )
		);

		// WordPress already adds a generic 「すべてのコンテンツ」/「新規追加」
		// pair for the alumni_content CPT (via its show_in_menu => self::MENU_SLUG),
		// but nothing there hints that this single CPT is how 校長挨拶・
		// 会長挨拶 etc. (人物挨拶) or 沿革・お問い合わせ等 (自由コンテンツ)
		// get created. These two shortcuts link straight to the 新規追加
		// screen with the intended種別 pre-selected — see
		// Content_Meta_Box::render()'s use of $_GET[Content_Post_Type::QUERY_VAR_KIND].
		// No new post type or admin screen is introduced: both still save
		// through the same alumni_content CPT + _alumni_content_kind meta.
		// Labels are flat siblings (no tree-drawing indentation) — they
		// relate only to "すべてのコンテンツ" above them, not to 卒業期早見表.
		add_submenu_page(
			self::MENU_SLUG,
			__( '人物挨拶を追加', 'alumni-core' ),
			__( '＋ 人物挨拶を追加', 'alumni-core' ),
			self::CAPABILITY,
			'post-new.php?post_type=' . Content_Post_Type::SLUG . '&' . Content_Post_Type::QUERY_VAR_KIND . '=' . Content_Post_Type::KIND_PERSON_GREETING
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( '自由コンテンツを追加', 'alumni-core' ),
			__( '＋ 自由コンテンツを追加', 'alumni-core' ),
			self::CAPABILITY,
			'post-new.php?post_type=' . Content_Post_Type::SLUG . '&' . Content_Post_Type::QUERY_VAR_KIND . '=' . Content_Post_Type::KIND_FREE
		);

		// 規約類も同じ alumni_content CPT（kind=terms）だが、他のコンテンツ
		// と混ざらない専用の一覧screen（Terms_Page）を独立した項目として
		// 持つ — 同窓会規約・会則・個人情報保護方針などを他のコンテンツと
		// 一緒くたにせず、まとめて見渡せるようにするため。
		add_submenu_page(
			self::MENU_SLUG,
			__( '規約類', 'alumni-core' ),
			__( '規約類', 'alumni-core' ),
			self::CAPABILITY,
			Terms_Page::SLUG,
			array( $this->terms_page, 'render' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'トップページ設定', 'alumni-core' ),
			__( 'トップページ設定', 'alumni-core' ),
			self::CAPABILITY,
			Homepage_Page::SLUG,
			array( $this->homepage_page, 'render' )
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
	 * Loads admin CSS/JS only on Alumni Core's own screens, and the
	 * heavier media-library assets only on the specific screens that
	 * actually use them (not, e.g., the plain ダッシュボード).
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

		$is_settings_page      = $this->settings_hook === $hook_suffix;
		$is_school_photos_page = $this->school_photos_hook === $hook_suffix;
		$is_officers_page      = $this->officers_hook === $hook_suffix;

		if ( $is_officers_page ) {
			// No wp.media() here: 役員・理事紹介 only has text/number/select
			// fields (リンク先コンテンツ is a <select>, not an image picker).
			wp_enqueue_script(
				'alumni-core-officers-admin',
				ALUMNI_CORE_URL . 'admin/assets/js/officers-admin.js',
				array(),
				ALUMNI_CORE_VERSION,
				true
			);
		}

		if ( ! $is_settings_page && ! $is_school_photos_page ) {
			return;
		}

		// wp.media() (校章／同窓会ロゴ／学校写真の各ピッカーが利用) is only
		// registered when this is explicitly enqueued.
		wp_enqueue_media();

		if ( $is_settings_page ) {
			wp_enqueue_script(
				'alumni-core-media-picker',
				ALUMNI_CORE_URL . 'admin/assets/js/media-picker.js',
				array(),
				ALUMNI_CORE_VERSION,
				true
			);
		}

		if ( $is_school_photos_page ) {
			wp_enqueue_script(
				'alumni-core-school-photos-admin',
				ALUMNI_CORE_URL . 'admin/assets/js/school-photos-admin.js',
				array(),
				ALUMNI_CORE_VERSION,
				true
			);
		}
	}
}
