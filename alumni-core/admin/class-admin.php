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
use AlumniCore\Admin\Pages\School_Song_Motto_Page;
use AlumniCore\Admin\Pages\Officers_Page;
use AlumniCore\Admin\Pages\Graduation_Lookup_Page;
use AlumniCore\Admin\Pages\School_Enrollment_Years_Page;
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

	const TOP_HUB_SLUG = 'alumni-core-top';
	const BASIC_HUB_SLUG = 'alumni-core-basic';
	const CONTENT_HUB_SLUG = 'alumni-core-content';
	const ORGANIZATION_HUB_SLUG = 'alumni-core-organization';

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

	/** @var School_Song_Motto_Page */
	private $school_song_motto_page;

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

	/** @var School_Enrollment_Years_Page */
	private $school_enrollment_years_page;

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

	/** @var string */
	private $school_song_motto_hook = '';

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
		$this->school_song_motto_page = new School_Song_Motto_Page();
		$this->officers_page          = new Officers_Page();
		$this->graduation_lookup_page = new Graduation_Lookup_Page();
		$this->school_enrollment_years_page = new School_Enrollment_Years_Page();
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
		add_action( 'admin_post_alumni_core_save_school_song_motto', array( $this->school_song_motto_page, 'handle_save' ) );
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

		// Left navigation is intentionally compact. Detailed functions are accessed from the category hub pages.
		add_submenu_page( self::MENU_SLUG, __( 'ダッシュボード', 'alumni-core' ), __( 'ダッシュボード', 'alumni-core' ), self::CAPABILITY, self::MENU_SLUG, array( $this->dashboard_page, 'render' ) );
		add_submenu_page( self::MENU_SLUG, __( 'トップ画面・メニュー', 'alumni-core' ), __( 'トップ画面・メニュー', 'alumni-core' ), self::CAPABILITY, self::TOP_HUB_SLUG, array( $this, 'render_top_hub' ) );
		add_submenu_page( self::MENU_SLUG, __( '基本情報', 'alumni-core' ), __( '基本情報', 'alumni-core' ), self::CAPABILITY, self::BASIC_HUB_SLUG, array( $this, 'render_basic_hub' ) );
		add_submenu_page( self::MENU_SLUG, __( 'コンテンツ', 'alumni-core' ), __( 'コンテンツ', 'alumni-core' ), self::CAPABILITY, self::CONTENT_HUB_SLUG, array( $this, 'render_content_hub' ) );
		add_submenu_page( self::MENU_SLUG, __( '組織', 'alumni-core' ), __( '組織', 'alumni-core' ), self::CAPABILITY, self::ORGANIZATION_HUB_SLUG, array( $this, 'render_organization_overview' ) );

		// Register existing admin pages without exposing them in the compact left menu.
		$this->settings_hook = add_submenu_page( null, __( '基本設定', 'alumni-core' ), __( '基本設定', 'alumni-core' ), self::CAPABILITY, Settings_Page::SLUG, array( $this->settings_page, 'render' ) );
		$this->school_photos_hook = add_submenu_page( null, __( '学校写真', 'alumni-core' ), __( '学校写真', 'alumni-core' ), self::CAPABILITY, School_Photos_Page::SLUG, array( $this->school_photos_page, 'render' ) );
		$this->school_song_motto_hook = add_submenu_page( null, __( '校歌・校訓', 'alumni-core' ), __( '校歌・校訓', 'alumni-core' ), self::CAPABILITY, School_Song_Motto_Page::SLUG, array( $this->school_song_motto_page, 'render' ) );
		add_submenu_page( null, __( '在校年度一覧', 'alumni-core' ), __( '在校年度一覧', 'alumni-core' ), self::CAPABILITY, School_Enrollment_Years_Page::SLUG, array( $this->school_enrollment_years_page, 'render' ) );
		add_submenu_page( null, __( 'トップページ設定', 'alumni-core' ), __( 'トップページ設定', 'alumni-core' ), self::CAPABILITY, Homepage_Page::SLUG, array( $this->homepage_page, 'render' ) );
		add_submenu_page( null, __( 'メニュー構成', 'alumni-core' ), __( 'メニュー構成', 'alumni-core' ), self::CAPABILITY, Menu_Page::SLUG, array( $this->menu_page, 'render' ) );
		$this->person_greeting_order_hook = add_submenu_page( null, __( '人物挨拶の並び順', 'alumni-core' ), __( '人物挨拶の並び順', 'alumni-core' ), self::CAPABILITY, Person_Greeting_Order_Page::SLUG, array( $this->person_greeting_order_page, 'render' ) );
		$this->officers_hook = add_submenu_page( null, __( '組織名簿', 'alumni-core' ), __( '組織名簿', 'alumni-core' ), self::CAPABILITY, Officers_Page::SLUG, array( $this->officers_page, 'render' ) );
		$this->officer_list_order_hook = add_submenu_page( null, __( '組織名簿の並び順', 'alumni-core' ), __( '組織名簿の並び順', 'alumni-core' ), self::CAPABILITY, Officer_List_Order_Page::SLUG, array( $this->officer_list_order_page, 'render' ) );
		add_submenu_page( null, __( '同窓会組織図', 'alumni-core' ), __( '同窓会組織図', 'alumni-core' ), self::CAPABILITY, Org_Chart_Page::SLUG, array( $this->org_chart_page, 'render' ) );
		add_submenu_page( null, __( '組織図を追加', 'alumni-core' ), __( '組織図を追加', 'alumni-core' ), self::CAPABILITY, Org_Chart_Page::ADD_SLUG, array( $this->org_chart_page, 'render_add' ) );
		$this->org_chart_order_hook = add_submenu_page( null, __( '組織図の並び順', 'alumni-core' ), __( '組織図の並び順', 'alumni-core' ), self::CAPABILITY, Org_Chart_Order_Page::SLUG, array( $this->org_chart_order_page, 'render' ) );
		add_submenu_page( null, __( '組織図グループ管理', 'alumni-core' ), __( '組織図グループ管理', 'alumni-core' ), self::CAPABILITY, Org_Chart_Group_Page::SLUG, array( $this->org_chart_group_page, 'render' ) );

		// Keep custom post type menus out of this plugin's navigation.
		remove_submenu_page( self::MENU_SLUG, 'edit.php?post_type=' . Content_Post_Type::SLUG );
		remove_submenu_page( self::MENU_SLUG, 'post-new.php?post_type=' . Content_Post_Type::SLUG );
		remove_submenu_page( self::MENU_SLUG, 'edit.php?post_type=alumni_news_event' );
		remove_submenu_page( self::MENU_SLUG, 'post-new.php?post_type=alumni_news_event' );
		remove_submenu_page( self::MENU_SLUG, 'edit.php?post_type=alumni_form' );
		remove_submenu_page( self::MENU_SLUG, 'post-new.php?post_type=alumni_form' );

		/**
		 * Lets extension plugins register admin pages after Alumni Core has
		 * created its compact top-level navigation. The top-level menu slug is
		 * passed for extensions that intentionally integrate into this area.
		 *
		 * Extensions should normally keep their own detailed screens hidden and
		 * expose entry points from their own hub page, matching Alumni Core's UI.
		 */
		do_action( 'alumni_core_register_admin_pages', self::MENU_SLUG, self::CAPABILITY );
	}

	private function render_hub_card( $title, $description, $actions, $features = array() ) {
		?>
		<div class="alumni-core-hub-card">
			<h2><?php echo esc_html( $title ); ?></h2>
			<p><?php echo esc_html( $description ); ?></p>
			<?php if ( ! empty( $features ) ) : ?><ul><?php foreach ( $features as $feature ) : ?><li><?php echo esc_html( $feature ); ?></li><?php endforeach; ?></ul><?php endif; ?>
			<div class="alumni-core-hub-actions">
				<?php foreach ( $actions as $action ) : ?>
					<a class="button <?php echo ! empty( $action['primary'] ) ? 'button-primary' : ''; ?>" href="<?php echo esc_url( $action['url'] ); ?>"><?php echo esc_html( $action['label'] ); ?></a>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	public function render_top_hub() {
		?><div class="wrap alumni-core-wrap alumni-core-hub"><h1>トップ画面・メニュー</h1><p>公開サイトのトップページとメニュー構成を設定します。</p><div class="alumni-core-hub-grid"><?php
		$this->render_hub_card( 'トップページ設定', 'トップページに表示する内容や表示順を設定します。', array( array( 'label' => 'トップページ設定', 'url' => admin_url( 'admin.php?page=' . Homepage_Page::SLUG ), 'primary' => true ) ), array( '表示するコンテンツ', '表示数', '見出し・インデント' ) );
		$this->render_hub_card( 'メニュー構成', '公開サイトのメニュー項目、順番、階層を設定します。', array( array( 'label' => 'メニュー構成', 'url' => admin_url( 'admin.php?page=' . Menu_Page::SLUG ), 'primary' => true ) ), array( 'メニュー項目', '表示順', '階層・インデント' ) );
		$this->render_hub_card( '学校写真', 'トップ画面など公開サイトで使用する学校の写真を登録・管理します。', array( array( 'label' => '学校写真を管理', 'url' => admin_url( 'admin.php?page=' . School_Photos_Page::SLUG ), 'primary' => true ) ) );
		?></div></div><?php
	}

	public function render_basic_hub() {
		?><div class="wrap alumni-core-wrap alumni-core-hub"><h1>基本情報</h1><p>学校および同窓会に関する基本情報を設定します。</p><div class="alumni-core-hub-grid"><?php
		$this->render_hub_card( '基本設定', '同窓会と学校の基本情報を設定します。', array( array( 'label' => '基本設定', 'url' => admin_url( 'admin.php?page=' . Settings_Page::SLUG ), 'primary' => true ) ), array( '同窓会名称', '学校名称', '創立年・第1期卒業年' ) );
		$this->render_hub_card( '校歌・校訓', '校歌と校訓を登録し、公開方法を設定します。', array( array( 'label' => '校歌・校訓設定', 'url' => admin_url( 'admin.php?page=' . School_Song_Motto_Page::SLUG ), 'primary' => true ) ), array( '校歌', '校訓・説明文', '校訓画像・表示形式' ) );
		?></div></div><?php
	}

	public function render_content_hub() {
		$base = admin_url( 'edit.php?post_type=' . Content_Post_Type::SLUG );
		?><div class="wrap alumni-core-wrap alumni-core-hub"><h1>コンテンツ</h1><p>Webサイトに掲載するコンテンツを作成・管理します。</p><div class="alumni-core-hub-full"><?php
		$this->render_hub_card( 'すべてのコンテンツ', '登録されているコンテンツを横断して確認・管理します。', array( array( 'label' => 'すべてのコンテンツ', 'url' => $base, 'primary' => true ) ) );
		?></div><h2 class="alumni-core-hub-section-title">コンテンツを追加</h2><div class="alumni-core-hub-grid"><?php
		$this->render_hub_card( '自由コンテンツ', '任意のページを作成します。', array( array( 'label' => '追加する', 'url' => admin_url( 'post-new.php?post_type=' . Content_Post_Type::SLUG . '&' . Content_Post_Type::QUERY_VAR_KIND . '=' . Content_Post_Type::KIND_FREE ), 'primary' => true ), array( 'label' => '一覧', 'url' => $base . '&' . Content_Post_Type::QUERY_VAR_KIND . '=' . Content_Post_Type::KIND_FREE ) ) );
		$this->render_hub_card( 'ニュース・イベント', 'ニュースやイベントを登録します。', array( array( 'label' => '追加する', 'url' => admin_url( 'post-new.php?post_type=alumni_news_event' ), 'primary' => true ), array( 'label' => '一覧', 'url' => admin_url( 'edit.php?post_type=alumni_news_event' ) ) ) );
		$this->render_hub_card( '人物挨拶', '人物の挨拶を登録・管理します。', array( array( 'label' => '追加する', 'url' => admin_url( 'post-new.php?post_type=' . Content_Post_Type::SLUG . '&' . Content_Post_Type::QUERY_VAR_KIND . '=' . Content_Post_Type::KIND_PERSON_GREETING ), 'primary' => true ), array( 'label' => '一覧', 'url' => $base . '&' . Content_Post_Type::QUERY_VAR_KIND . '=' . Content_Post_Type::KIND_PERSON_GREETING ), array( 'label' => '並び順', 'url' => admin_url( 'admin.php?page=' . Person_Greeting_Order_Page::SLUG ) ) ) );
		$this->render_hub_card( '規約類', '規約や規程を登録します。', array( array( 'label' => '追加する', 'url' => admin_url( 'post-new.php?post_type=' . Content_Post_Type::SLUG . '&' . Content_Post_Type::QUERY_VAR_KIND . '=' . Content_Post_Type::KIND_TERMS ), 'primary' => true ), array( 'label' => '一覧', 'url' => admin_url( 'admin.php?page=' . Terms_Page::SLUG ) ) ) );
		?></div></div><?php
	}

	/**
	 * Renders the organization category landing page.
	 *
	 * WordPress core only supports one submenu depth. This landing page makes
	 * 「組織」 a real category while keeping 組織名簿 and 同窓会組織図 grouped
	 * directly beneath it in the Alumni Core menu.
	 */
	public function render_organization_overview() {
		if ( ! current_user_can( self::CAPABILITY ) ) { wp_die( esc_html__( 'この画面を表示する権限がありません。', 'alumni-core' ) ); }
		?><div class="wrap alumni-core-wrap alumni-core-hub"><h1>組織</h1><p>同窓会の組織構成、組織名簿、組織図を管理します。</p><div class="alumni-core-hub-grid"><?php
		$this->render_hub_card( '組織名簿', '役員・理事・委員などの登録情報を管理します。', array( array( 'label' => '組織名簿を管理', 'url' => admin_url( 'admin.php?page=' . Officers_Page::SLUG ), 'primary' => true ), array( 'label' => '並び順を変更', 'url' => admin_url( 'admin.php?page=' . Officer_List_Order_Page::SLUG ) ) ), array( '公開グループ', '役職・所属情報', '人物情報' ) );
		$this->render_hub_card( '同窓会組織図', '複数の組織図を作成・管理します。', array( array( 'label' => '組織図を管理', 'url' => admin_url( 'admin.php?page=' . Org_Chart_Page::SLUG ), 'primary' => true ), array( 'label' => '組織図を追加', 'url' => admin_url( 'admin.php?page=' . Org_Chart_Page::ADD_SLUG ) ), array( 'label' => '並び順を変更', 'url' => admin_url( 'admin.php?page=' . Org_Chart_Order_Page::SLUG ) ), array( 'label' => 'グループ管理', 'url' => admin_url( 'admin.php?page=' . Org_Chart_Group_Page::SLUG ) ) ), array( '複数組織図', '公開グループ', '表示順' ) );
		?></div></div><?php
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
		$is_school_song_motto_page = $this->school_song_motto_hook === $hook_suffix;
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

		if ( ! $is_settings_page && ! $is_school_photos_page && ! $is_school_song_motto_page ) {
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

		if ( $is_school_song_motto_page ) {
			wp_enqueue_script( 'alumni-core-school-song-motto-admin', ALUMNI_CORE_URL . 'admin/assets/js/school-song-motto-admin.js', array(), ALUMNI_CORE_VERSION, true );
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
