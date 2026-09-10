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
use AlumniCore\Admin\Pages\Menu_Page;
use AlumniCore\Admin\Pages\Org_Chart_Page;
use AlumniCore\Admin\Pages\Person_Greeting_Order_Page;
use AlumniCore\Admin\Pages\Officer_List_Order_Page;
use AlumniCore\Admin\Pages\Org_Chart_Group_Page;
use AlumniCore\Admin\Pages\Org_Chart_Order_Page;
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
	 * メニュー構成 screen handler.
	 *
	 * @var Menu_Page
	 */
	private $menu_page;

	/**
	 * 同窓会組織図 screen handler.
	 *
	 * @var Org_Chart_Page
	 */
	private $org_chart_page;

	/** @var Person_Greeting_Order_Page */
	private $person_greeting_order_page;

	/** @var Officer_List_Order_Page */
	private $officer_list_order_page;

	/** @var Org_Chart_Group_Page */
	private $org_chart_group_page;

	/** @var Org_Chart_Order_Page */
	private $org_chart_order_page;

	/** @var string */
	private $person_greeting_order_hook = '';

	/** @var string */
	private $officer_list_order_hook = '';

	/** @var string */
	private $org_chart_order_hook = '';

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
		$this->menu_page                = new Menu_Page();
		$this->org_chart_page           = new Org_Chart_Page();
		$this->person_greeting_order_page = new Person_Greeting_Order_Page();
		$this->officer_list_order_page = new Officer_List_Order_Page();
		$this->org_chart_group_page = new Org_Chart_Group_Page();
		$this->org_chart_order_page = new Org_Chart_Order_Page();

		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'pre_get_posts', array( $this, 'filter_person_greeting_list' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_alumni_core_save_settings', array( $this->settings_page, 'handle_save' ) );
		add_action( 'admin_post_alumni_core_save_school_photos', array( $this->school_photos_page, 'handle_save' ) );
		add_action( 'admin_post_alumni_core_create_officer_list', array( $this->officers_page, 'handle_create' ) );
		add_action( 'admin_post_alumni_core_create_officer_list_group', array( $this->officers_page, 'handle_create_group' ) );
		add_action( 'admin_post_alumni_core_update_officer_list_group', array( $this->officers_page, 'handle_update_group' ) );
		add_action( 'admin_post_alumni_core_delete_officer_list_group', array( $this->officers_page, 'handle_delete_group' ) );
		add_action( 'admin_post_alumni_core_delete_officer_list', array( $this->officers_page, 'handle_delete' ) );
		add_action( 'admin_post_alumni_core_save_officer_list', array( $this->officers_page, 'handle_save' ) );
		add_action( 'admin_post_alumni_core_create_homepage_section', array( $this->homepage_page, 'handle_create' ) );
		add_action( 'admin_post_alumni_core_delete_homepage_section', array( $this->homepage_page, 'handle_delete' ) );
		add_action( 'admin_post_alumni_core_move_homepage_section', array( $this->homepage_page, 'handle_move' ) );
		add_action( 'admin_post_alumni_core_save_homepage_sections', array( $this->homepage_page, 'handle_save' ) );
		add_action( 'admin_post_alumni_core_create_menu_folder', array( $this->menu_page, 'handle_create_folder' ) );
		add_action( 'admin_post_alumni_core_create_menu_content', array( $this->menu_page, 'handle_create_content' ) );
		add_action( 'admin_post_alumni_core_update_menu_item', array( $this->menu_page, 'handle_update' ) );
		add_action( 'admin_post_alumni_core_delete_menu_item', array( $this->menu_page, 'handle_delete' ) );
		add_action( 'admin_post_alumni_core_move_menu_item', array( $this->menu_page, 'handle_move' ) );
		add_action( 'admin_post_alumni_core_indent_menu_item', array( $this->menu_page, 'handle_indent' ) );
		add_action( 'admin_post_alumni_core_outdent_menu_item', array( $this->menu_page, 'handle_outdent' ) );
		add_action( 'admin_post_alumni_core_apply_standard_menu_preset', array( $this->menu_page, 'handle_apply_standard_preset' ) );
		add_action( 'admin_post_alumni_core_save_org_chart_display_settings', array( $this->org_chart_page, 'handle_save_display_settings' ) );
		add_action( 'admin_post_alumni_core_create_org_chart', array( $this->org_chart_page, 'handle_create_chart' ) );
		add_action( 'admin_post_alumni_core_update_org_chart', array( $this->org_chart_page, 'handle_update_chart' ) );
		add_action( 'admin_post_alumni_core_delete_org_chart', array( $this->org_chart_page, 'handle_delete_chart' ) );
		add_action( 'admin_post_alumni_core_create_org_chart_group', array( $this->org_chart_group_page, 'handle_create' ) );
		add_action( 'admin_post_alumni_core_update_org_chart_group', array( $this->org_chart_group_page, 'handle_update' ) );
		add_action( 'admin_post_alumni_core_delete_org_chart_group', array( $this->org_chart_group_page, 'handle_delete' ) );
		add_action( 'admin_post_alumni_core_save_org_chart_order', array( $this->org_chart_order_page, 'handle_save' ) );
		add_action( 'admin_post_alumni_core_create_org_chart_node', array( $this->org_chart_page, 'handle_create' ) );
		add_action( 'admin_post_alumni_core_update_org_chart_node', array( $this->org_chart_page, 'handle_update' ) );
		add_action( 'admin_post_alumni_core_delete_org_chart_node', array( $this->org_chart_page, 'handle_delete' ) );
		add_action( 'admin_post_alumni_core_move_org_chart_node', array( $this->org_chart_page, 'handle_move' ) );
		add_action( 'admin_post_alumni_core_reparent_org_chart_node', array( $this->org_chart_page, 'handle_reparent' ) );
		add_action( 'admin_post_alumni_core_save_person_greeting_order', array( $this->person_greeting_order_page, 'handle_save' ) );
		add_action( 'admin_post_alumni_core_save_officer_list_order', array( $this->officer_list_order_page, 'handle_save' ) );
	}


	/**
	 * Restricts dedicated content lists to their requested content kind.
	 *
	 * The menu uses Content_Post_Type::QUERY_VAR_KIND for the route, but
	 * WordPress does not automatically translate that custom query variable
	 * into a postmeta constraint on the CPT list screen. Apply the constraint
	 * here so dedicated 人物挨拶／自由コンテンツ screens never mix in other content kinds.
	 *
	 * @param \WP_Query $query Main admin query.
	 */
	public function filter_person_greeting_list( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		global $pagenow;

		if ( 'edit.php' !== $pagenow || Content_Post_Type::SLUG !== $query->get( 'post_type' ) ) {
			return;
		}

		$requested_kind = isset( $_GET[ Content_Post_Type::QUERY_VAR_KIND ] ) ? sanitize_key( wp_unslash( $_GET[ Content_Post_Type::QUERY_VAR_KIND ] ) ) : '';

		$allowed_kinds = array(
			Content_Post_Type::KIND_PERSON_GREETING,
			Content_Post_Type::KIND_FREE,
		);

		if ( ! in_array( $requested_kind, $allowed_kinds, true ) ) {
			return;
		}

		$meta_query   = $query->get( 'meta_query' );
		$meta_query   = is_array( $meta_query ) ? $meta_query : array();
		$meta_query[] = array(
			'key'     => Content_Post_Type::META_KIND,
			'value'   => $requested_kind,
			'compare' => '=',
		);

		$query->set( 'meta_query', $meta_query );
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

		add_submenu_page( self::MENU_SLUG, __( 'ダッシュボード', 'alumni-core' ), __( 'ダッシュボード', 'alumni-core' ), self::CAPABILITY, self::MENU_SLUG, array( $this->dashboard_page, 'render' ) );
		$this->settings_hook = add_submenu_page( self::MENU_SLUG, __( '基本設定', 'alumni-core' ), __( '基本設定', 'alumni-core' ), self::CAPABILITY, Settings_Page::SLUG, array( $this->settings_page, 'render' ) );
		$this->school_photos_hook = add_submenu_page( self::MENU_SLUG, __( '学校写真', 'alumni-core' ), __( '学校写真', 'alumni-core' ), self::CAPABILITY, School_Photos_Page::SLUG, array( $this->school_photos_page, 'render' ) );
		add_submenu_page( self::MENU_SLUG, __( 'トップページ設定', 'alumni-core' ), __( 'トップページ設定', 'alumni-core' ), self::CAPABILITY, Homepage_Page::SLUG, array( $this->homepage_page, 'render' ) );
		add_submenu_page( self::MENU_SLUG, __( 'メニュー構成', 'alumni-core' ), __( 'メニュー構成', 'alumni-core' ), self::CAPABILITY, Menu_Page::SLUG, array( $this->menu_page, 'render' ) );

		// WordPress が自動追加する CPT サブメニューを一度外し、管理者に
		// 分かりやすい機能単位の順序で再登録する。
		remove_submenu_page( self::MENU_SLUG, 'edit.php?post_type=' . Content_Post_Type::SLUG );
		remove_submenu_page( self::MENU_SLUG, 'post-new.php?post_type=' . Content_Post_Type::SLUG );
		remove_submenu_page( self::MENU_SLUG, 'edit.php?post_type=alumni_news_event' );
		remove_submenu_page( self::MENU_SLUG, 'post-new.php?post_type=alumni_news_event' );
		remove_submenu_page( self::MENU_SLUG, 'edit.php?post_type=alumni_form' );
		remove_submenu_page( self::MENU_SLUG, 'post-new.php?post_type=alumni_form' );

		add_submenu_page( self::MENU_SLUG, __( 'すべてのコンテンツ', 'alumni-core' ), __( 'すべてのコンテンツ', 'alumni-core' ), self::CAPABILITY, 'edit.php?post_type=' . Content_Post_Type::SLUG );
		add_submenu_page( self::MENU_SLUG, __( '自由コンテンツ', 'alumni-core' ), __( '自由コンテンツ', 'alumni-core' ), self::CAPABILITY, 'edit.php?post_type=' . Content_Post_Type::SLUG . '&' . Content_Post_Type::QUERY_VAR_KIND . '=' . Content_Post_Type::KIND_FREE );
		add_submenu_page( self::MENU_SLUG, __( '自由コンテンツを追加', 'alumni-core' ), __( '└ 自由コンテンツを追加', 'alumni-core' ), self::CAPABILITY, 'post-new.php?post_type=' . Content_Post_Type::SLUG . '&' . Content_Post_Type::QUERY_VAR_KIND . '=' . Content_Post_Type::KIND_FREE );

		add_submenu_page( self::MENU_SLUG, __( 'ニュース・イベント', 'alumni-core' ), __( 'ニュース・イベント', 'alumni-core' ), self::CAPABILITY, 'edit.php?post_type=alumni_news_event' );
		add_submenu_page( self::MENU_SLUG, __( 'ニュース・イベントを追加', 'alumni-core' ), __( '└ ニュース・イベントを追加', 'alumni-core' ), self::CAPABILITY, 'post-new.php?post_type=alumni_news_event' );

		add_submenu_page( self::MENU_SLUG, __( '人物挨拶', 'alumni-core' ), __( '人物挨拶', 'alumni-core' ), self::CAPABILITY, 'edit.php?post_type=' . Content_Post_Type::SLUG . '&' . Content_Post_Type::QUERY_VAR_KIND . '=' . Content_Post_Type::KIND_PERSON_GREETING );
		add_submenu_page( self::MENU_SLUG, __( '人物挨拶を追加', 'alumni-core' ), __( '├ 人物挨拶を追加', 'alumni-core' ), self::CAPABILITY, 'post-new.php?post_type=' . Content_Post_Type::SLUG . '&' . Content_Post_Type::QUERY_VAR_KIND . '=' . Content_Post_Type::KIND_PERSON_GREETING );
		$this->person_greeting_order_hook = add_submenu_page( self::MENU_SLUG, __( '人物挨拶の並び順', 'alumni-core' ), __( '└ 人物挨拶の並び順', 'alumni-core' ), self::CAPABILITY, Person_Greeting_Order_Page::SLUG, array( $this->person_greeting_order_page, 'render' ) );

		$this->officers_hook = add_submenu_page( self::MENU_SLUG, __( '組織名簿', 'alumni-core' ), __( '組織名簿', 'alumni-core' ), self::CAPABILITY, Officers_Page::SLUG, array( $this->officers_page, 'render' ) );
		$this->officer_list_order_hook = add_submenu_page( self::MENU_SLUG, __( '組織名簿の並び順', 'alumni-core' ), __( '└ 組織名簿の並び順', 'alumni-core' ), self::CAPABILITY, Officer_List_Order_Page::SLUG, array( $this->officer_list_order_page, 'render' ) );

		add_submenu_page( self::MENU_SLUG, __( '規約類', 'alumni-core' ), __( '規約類', 'alumni-core' ), self::CAPABILITY, Terms_Page::SLUG, array( $this->terms_page, 'render' ) );
		add_submenu_page( self::MENU_SLUG, __( '規約類を追加', 'alumni-core' ), __( '└ 規約類を追加', 'alumni-core' ), self::CAPABILITY, 'post-new.php?post_type=' . Content_Post_Type::SLUG . '&' . Content_Post_Type::QUERY_VAR_KIND . '=' . Content_Post_Type::KIND_TERMS );

		add_submenu_page( self::MENU_SLUG, __( 'フォーム', 'alumni-core' ), __( 'フォーム', 'alumni-core' ), self::CAPABILITY, 'edit.php?post_type=alumni_form' );
		add_submenu_page( self::MENU_SLUG, __( 'フォームを追加', 'alumni-core' ), __( '└ フォームを追加', 'alumni-core' ), self::CAPABILITY, 'post-new.php?post_type=alumni_form' );
		add_submenu_page( self::MENU_SLUG, __( '同窓会組織図', 'alumni-core' ), __( '同窓会組織図', 'alumni-core' ), self::CAPABILITY, Org_Chart_Page::SLUG, array( $this->org_chart_page, 'render' ) );
		add_submenu_page( self::MENU_SLUG, __( '組織図を追加', 'alumni-core' ), __( '└ 組織図を追加', 'alumni-core' ), self::CAPABILITY, Org_Chart_Page::SLUG . '&new=1', array( $this->org_chart_page, 'render' ) );
		$this->org_chart_order_hook = add_submenu_page( self::MENU_SLUG, __( '組織図の並び順', 'alumni-core' ), __( '└ 組織図の並び順', 'alumni-core' ), self::CAPABILITY, Org_Chart_Order_Page::SLUG, array( $this->org_chart_order_page, 'render' ) );
		add_submenu_page( self::MENU_SLUG, __( '組織図グループ管理', 'alumni-core' ), __( '└ 組織図グループ管理', 'alumni-core' ), self::CAPABILITY, Org_Chart_Group_Page::SLUG, array( $this->org_chart_group_page, 'render' ) );

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
		$is_person_greeting_order_page = $this->person_greeting_order_hook === $hook_suffix;
		$is_officer_list_order_page = $this->officer_list_order_hook === $hook_suffix;
		$is_org_chart_order_page = $this->org_chart_order_hook === $hook_suffix;

		if ( $is_person_greeting_order_page || $is_officer_list_order_page || $is_org_chart_order_page ) {
			wp_enqueue_script(
				'alumni-core-person-greeting-order-admin',
				ALUMNI_CORE_URL . 'admin/assets/js/person-greeting-order-admin.js',
				array(),
				ALUMNI_CORE_VERSION,
				true
			);
		}

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
