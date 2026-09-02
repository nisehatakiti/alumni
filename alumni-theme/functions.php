<?php
/**
 * Alumni Theme functions and definitions.
 *
 * Every function here is prefixed with alumni_theme_ to avoid collisions
 * with plugins or other themes. Business logic (data storage, settings,
 * calculations) belongs in Alumni Core, not here — this file only wires up
 * theme support, assets, and display helpers.
 *
 * @package Alumni_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ALUMNI_THEME_VERSION', '0.1.0' );
define( 'ALUMNI_THEME_DIR', get_template_directory() );
define( 'ALUMNI_THEME_URI', get_template_directory_uri() );

/**
 * Registers theme support and the primary navigation menu.
 */
function alumni_theme_setup() {
	load_theme_textdomain( 'alumni-theme', ALUMNI_THEME_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
	);

	register_nav_menus(
		array(
			'primary' => __( 'メインナビゲーション', 'alumni-theme' ),
		)
	);
}
add_action( 'after_setup_theme', 'alumni_theme_setup' );

/**
 * Enqueues the theme's stylesheet and scripts.
 */
function alumni_theme_enqueue_assets() {
	wp_enqueue_style( 'alumni-theme-style', get_stylesheet_uri(), array(), ALUMNI_THEME_VERSION );

	wp_enqueue_style(
		'alumni-theme-main',
		ALUMNI_THEME_URI . '/assets/css/main.css',
		array( 'alumni-theme-style' ),
		ALUMNI_THEME_VERSION
	);

	wp_enqueue_script(
		'alumni-theme-navigation',
		ALUMNI_THEME_URI . '/assets/js/navigation.js',
		array(),
		ALUMNI_THEME_VERSION,
		true
	);

	wp_enqueue_script(
		'alumni-theme-school-photos',
		ALUMNI_THEME_URI . '/assets/js/school-photos.js',
		array(),
		ALUMNI_THEME_VERSION,
		true
	);

	// Passes the admin-configurable 写真の切替時間 to the slideshow script
	// via WordPress's standard localization mechanism, rather than hardcoding
	// it in JS or embedding it directly into a template.
	wp_localize_script(
		'alumni-theme-school-photos',
		'alumniSchoolPhotos',
		array(
			'intervalMs' => alumni_theme_get_school_photo_slideshow_interval() * 1000,
		)
	);
}
add_action( 'wp_enqueue_scripts', 'alumni_theme_enqueue_assets' );

/**
 * Adds a body class reflecting the admin-selected ナビゲーション配置
 * ('alumni-nav-layout-top' or 'alumni-nav-layout-side'), so main.css can
 * switch the whole page shell (header.php/footer.php) between the two
 * layouts without any template-level branching beyond this one class.
 *
 * @param string[] $classes Existing body classes.
 * @return string[]
 */
function alumni_theme_body_class_nav_layout( $classes ) {
	$classes[] = 'alumni-nav-layout-' . alumni_theme_get_nav_layout();

	return $classes;
}
add_filter( 'body_class', 'alumni_theme_body_class_nav_layout' );

/**
 * Whether Alumni Core is active and its data can be used. Every call into
 * Core elsewhere in the theme must be guarded by this, so the theme keeps
 * working normally when the plugin is inactive.
 *
 * @return bool
 */
function alumni_theme_core_active() {
	return function_exists( 'alumni_core_is_active' ) && alumni_core_is_active();
}

/**
 * Returns the association name to show in the header, with a safe fallback
 * to the WordPress site title when Alumni Core is inactive or the setting
 * is empty.
 *
 * @return string
 */
function alumni_theme_get_association_name() {
	if ( alumni_theme_core_active() ) {
		$name = alumni_core_get_setting( 'association_name', '' );
		if ( ! empty( $name ) ) {
			return $name;
		}
	}

	return get_bloginfo( 'name' );
}

/**
 * Returns the school name configured in Alumni Core, or an empty string
 * when Core is inactive or the setting is empty.
 *
 * @return string
 */
function alumni_theme_get_school_name() {
	if ( ! alumni_theme_core_active() ) {
		return '';
	}

	return alumni_core_get_setting( 'school_name', '' );
}

/**
 * Rendered `<img>` HTML for the 校章 (school emblem), or '' when Core is
 * inactive or none is set — callers should simply omit the wrapper
 * element entirely in that case, not leave an empty box.
 *
 * @return string Safe HTML from wp_get_attachment_image(), or ''.
 */
function alumni_theme_get_school_emblem_html() {
	if ( ! alumni_theme_core_active() ) {
		return '';
	}

	$id = alumni_core_get_school_emblem_id();

	if ( ! $id ) {
		return '';
	}

	return (string) wp_get_attachment_image(
		$id,
		array( 120, 60 ),
		false,
		array( 'class' => 'alumni-school-emblem' )
	);
}

/**
 * Rendered `<img>` HTML for the 同窓会独自ロゴ (alumni association's own
 * logo, distinct from the school emblem), or '' when Core is inactive or
 * none is set.
 *
 * 学校情報（校章・同窓会名・学校名、ヘッダーで表示）とはあえて役割を分け、
 * ロゴの見た目（サイズ・CSSクラス）は呼び出し側が用途ごとに指定する —
 * ヘッダー用の小さな表示と、トップページの大きな専用セクション
 * （template-parts/alumni-logo.php）とで、同じ画像を別の見た目で使い回す。
 *
 * @param string|int[] $size  WordPress image size name, or array( width, height ).
 * @param string       $class CSS class for the <img> element.
 * @return string Safe HTML from wp_get_attachment_image(), or ''.
 */
function alumni_theme_get_alumni_logo_html( $size = array( 120, 60 ), $class = 'alumni-logo' ) {
	if ( ! alumni_theme_core_active() ) {
		return '';
	}

	$id = alumni_core_get_alumni_logo_id();

	if ( ! $id ) {
		return '';
	}

	return (string) wp_get_attachment_image(
		$id,
		$size,
		false,
		array( 'class' => $class )
	);
}

/**
 * Runs a WP_Query for the latest ニュース／イベント, or returns null when
 * Alumni Core isn't available — so templates never have to guard this
 * themselves.
 *
 * @param array $args Extra WP_Query args, merged over Core's defaults.
 * @return WP_Query|null
 */
function alumni_theme_get_news_events( $args = array() ) {
	if ( ! alumni_theme_core_active() ) {
		return null;
	}

	return alumni_core_get_news_events_query( $args );
}

/**
 * A short, newest-first ニュースのみ の query (トップページのスロット用)、
 * またはCore無効時は null。
 *
 * @param int $limit
 * @return WP_Query|null
 */
function alumni_theme_get_news_teaser( $limit = 3 ) {
	if ( ! alumni_theme_core_active() ) {
		return null;
	}

	return alumni_core_get_news_teaser( $limit );
}

/**
 * alumni_theme_get_news_teaser() のイベント版。
 *
 * @param int $limit
 * @return WP_Query|null
 */
function alumni_theme_get_events_teaser( $limit = 3 ) {
	if ( ! alumni_theme_core_active() ) {
		return null;
	}

	return alumni_core_get_events_teaser( $limit );
}

/**
 * 開催日が今日以降(今日を含む)のイベントを、開催日が近い順に返す —
 * トップページの「今後のイベント」セクション用。
 *
 * @param int $limit
 * @return WP_Post[]
 */
function alumni_theme_get_upcoming_events( $limit = 3 ) {
	if ( ! alumni_theme_core_active() ) {
		return array();
	}

	return alumni_core_get_upcoming_events( $limit );
}

/**
 * 開催日が今日より前のイベントを、開催日が新しい順に返す —
 * トップページの「終了したイベント」セクション用。
 *
 * @param int $limit
 * @return WP_Post[]
 */
function alumni_theme_get_past_events( $limit = 3 ) {
	if ( ! alumni_theme_core_active() ) {
		return array();
	}

	return alumni_core_get_past_events( $limit );
}

/**
 * 学校関連写真として登録された添付ファイルIDの一覧（表示順）、または
 * Core無効時は空配列。
 *
 * @return int[]
 */
function alumni_theme_get_school_photo_ids() {
	if ( ! alumni_theme_core_active() ) {
		return array();
	}

	return alumni_core_get_school_photo_ids();
}

/**
 * 学校関連写真の表示方式（'fixed' または 'slideshow'）。Core無効時は
 * 'fixed' を返す（呼び出し側は写真が0件なら結局何も表示しないため、
 * 実質的に無害なフォールバック）。
 *
 * @return string
 */
function alumni_theme_get_school_photo_display_mode() {
	if ( ! alumni_theme_core_active() ) {
		return 'fixed';
	}

	return alumni_core_get_school_photo_display_mode();
}

/**
 * 固定表示で使う学校写真の添付ファイルID（未選択時のフォールバックを
 * 含め、判断はCore側が担う）。
 *
 * @return int Attachment ID, or 0.
 */
function alumni_theme_get_featured_school_photo_id() {
	if ( ! alumni_theme_core_active() ) {
		return 0;
	}

	return alumni_core_get_featured_school_photo_id();
}

/**
 * Whether a ニュース／イベント post is an event, so templates can adjust
 * their layout (e.g. showing the date before the title) without calling
 * into Core directly.
 *
 * @param int|WP_Post|null $post Post ID or object.
 * @return bool
 */
function alumni_theme_is_event( $post = null ) {
	if ( ! alumni_theme_core_active() ) {
		return false;
	}

	return alumni_core_is_event( $post );
}

/**
 * Japanese label for a ニュース／イベント post's content type.
 *
 * @param int|WP_Post|null $post Post ID or object.
 * @return string
 */
function alumni_theme_get_news_event_type_label( $post = null ) {
	if ( ! alumni_theme_core_active() ) {
		return '';
	}

	return alumni_core_is_event( $post ) ? __( 'イベント', 'alumni-theme' ) : __( 'ニュース', 'alumni-theme' );
}

/**
 * The date to show for a ニュース／イベント card: the event date for
 * events, the published date for news (decided by Alumni Core), formatted
 * with the site's date_format option.
 *
 * @param int|WP_Post|null $post Post ID or object.
 * @return string Formatted date, or '' when Core is inactive.
 */
function alumni_theme_get_news_event_date_display( $post = null ) {
	if ( ! alumni_theme_core_active() ) {
		return '';
	}

	$date = alumni_core_get_news_event_display_date( $post );

	return $date ? mysql2date( get_option( 'date_format' ), $date ) : '';
}

/**
 * The functions below are thin, guarded wrappers around the new
 * コンテンツ管理／役員紹介／卒業期早見表／ニュース・イベント一覧分離／
 * 学校写真切替間隔 Core API — intentionally minimal, matching each
 * phase's scope (Core data foundation, not Theme layout). They exist so
 * a later phase can build actual page layouts against a stable
 * alumni_theme_*() surface without ever calling alumni_core_*() directly,
 * same as every function above.
 */

/**
 * Runs a WP_Query for published コンテンツ, or null when Core is inactive.
 *
 * @param array $args Extra WP_Query args, merged over Core's defaults.
 * @return WP_Query|null
 */
function alumni_theme_get_contents( $args = array() ) {
	if ( ! alumni_theme_core_active() ) {
		return null;
	}

	return alumni_core_get_contents_query( $args );
}

/**
 * Runs a WP_Query for published 人物挨拶 コンテンツ only, or null when Core
 * is inactive.
 *
 * @param array $args Extra WP_Query args, merged over Core's defaults.
 * @return WP_Query|null
 */
function alumni_theme_get_person_greetings( $args = array() ) {
	if ( ! alumni_theme_core_active() ) {
		return null;
	}

	return alumni_core_get_person_greetings_query( $args );
}

/**
 * A single published コンテンツ post, or null when Core is inactive or the
 * ID doesn't resolve to one.
 *
 * @param int $id Post ID.
 * @return WP_Post|null
 */
function alumni_theme_get_content( $id ) {
	if ( ! alumni_theme_core_active() ) {
		return null;
	}

	return alumni_core_get_content( $id );
}

/**
 * @param int $id Post ID.
 * @return string Permalink, or '' when Core is inactive or the content
 *                 isn't available.
 */
function alumni_theme_get_content_url( $id ) {
	if ( ! alumni_theme_core_active() ) {
		return '';
	}

	return alumni_core_get_content_url( $id );
}

/**
 * Every field needed to render a 人物挨拶 card/page (name, kana, title,
 * term, photo ID, body, ...), or null when Core is inactive or $post
 * isn't a 人物挨拶 コンテンツ post.
 *
 * @param int|WP_Post|null $post Post ID or object.
 * @return array|null
 */
function alumni_theme_get_person_greeting( $post = null ) {
	if ( ! alumni_theme_core_active() ) {
		return null;
	}

	return alumni_core_get_person_greeting( $post );
}

/**
 * Every field needed to render a 規約類 detail page (display title,
 * effective/revised dates, body, ...), or null when Core is inactive or
 * $post isn't a 規約類 コンテンツ post.
 *
 * @param int|WP_Post|null $post Post ID or object.
 * @return array|null
 */
function alumni_theme_get_terms( $post = null ) {
	if ( ! alumni_theme_core_active() ) {
		return null;
	}

	return alumni_core_get_terms( $post );
}

/**
 * Every saved 役員・理事一覧, in display order (metadata only, no rows —
 * see alumni_theme_get_officers_for_list() for one list's rows), or an
 * empty array when Core is inactive.
 *
 * @return array[]
 */
function alumni_theme_get_officer_lists() {
	if ( ! alumni_theme_core_active() ) {
		return array();
	}

	return alumni_core_get_officer_lists();
}

/**
 * A single 役員・理事一覧's metadata (no rows), or null when Core is
 * inactive or $list_id doesn't exist.
 *
 * @param string $list_id
 * @return array|null
 */
function alumni_theme_get_officer_list( $list_id ) {
	if ( ! alumni_theme_core_active() ) {
		return null;
	}

	return alumni_core_get_officer_list( $list_id );
}

/**
 * Every saved 役員・理事 row for one 一覧, in display order, with stale
 * リンク先 already resolved to 0 by Core — or an empty array when Core is
 * inactive.
 *
 * @param string $list_id
 * @return array[]
 */
function alumni_theme_get_officers_for_list( $list_id ) {
	if ( ! alumni_theme_core_active() ) {
		return array();
	}

	return alumni_core_get_officers_for_list( $list_id );
}

/**
 * The public URL of one 役員・理事一覧's own page, or '' when Core is
 * inactive or $list_id doesn't exist / its page hasn't been created yet.
 *
 * @param string $list_id
 * @return string
 */
function alumni_theme_get_officer_list_url( $list_id ) {
	if ( ! alumni_theme_core_active() ) {
		return '';
	}

	return alumni_core_get_officer_list_url( $list_id );
}

/**
 * @param array $officer One row from alumni_theme_get_officers_for_list().
 * @return string URL, or '' when Core is inactive or the row has no
 *                 (currently valid) link.
 */
function alumni_theme_get_officer_link_url( array $officer ) {
	if ( ! alumni_theme_core_active() ) {
		return '';
	}

	return alumni_core_get_officer_link_url( $officer );
}

/**
 * A 卒業期早見表 (term/year/birth-range/color rows), or an empty array
 * when Core is inactive.
 *
 * @param int $from_term First term to include (1-based).
 * @param int $to_term   Last term to include (inclusive).
 * @return array[]
 */
function alumni_theme_get_graduation_lookup_table( $from_term, $to_term ) {
	if ( ! alumni_theme_core_active() ) {
		return array();
	}

	return alumni_core_get_graduation_lookup_table( $from_term, $to_term );
}

/**
 * Estimates the standard-progression graduation term (期) for a birth
 * date (see Term_Calculator::GRADUATION_AGE_YEARS for the underlying
 * assumption — not a guarantee for any one person), or null when Core is
 * inactive or the estimate can't be resolved.
 *
 * @param string $birthdate 'Y-m-d'.
 * @return int|null
 */
function alumni_theme_birthdate_to_term( $birthdate ) {
	if ( ! alumni_theme_core_active() ) {
		return null;
	}

	return alumni_core_birthdate_to_term( $birthdate );
}

/**
 * 学校写真の自動切替（スライドショー）の切替間隔（秒）。固定表示では
 * 使われない値だが、Core無効時のフォールバックとしても既定の5秒を返す。
 *
 * @return int 1〜60 の整数。
 */
function alumni_theme_get_school_photo_slideshow_interval() {
	if ( ! alumni_theme_core_active() ) {
		return 5;
	}

	return alumni_core_get_school_photo_slideshow_interval();
}

/**
 * ニュース一覧（/news/）のURL、またはCore無効時は ''。
 *
 * @return string
 */
function alumni_theme_get_news_listing_url() {
	if ( ! alumni_theme_core_active() ) {
		return '';
	}

	return alumni_core_get_news_listing_url();
}

/**
 * イベント一覧（/events/）のURL、またはCore無効時は ''。
 *
 * @return string
 */
function alumni_theme_get_events_listing_url() {
	if ( ! alumni_theme_core_active() ) {
		return '';
	}

	return alumni_core_get_events_listing_url();
}

/**
 * 卒業期早見表ページ（[alumni_graduation_lookup] ショートコードが自動的に
 * 配置される固定ページ）のURL、またはCore無効時は ''。トップページや
 * メニューはこの関数でリンクし、スラッグを直接ハードコードしない。
 *
 * @return string
 */
function alumni_theme_get_graduation_lookup_url() {
	if ( ! alumni_theme_core_active() ) {
		return '';
	}

	return alumni_core_get_graduation_lookup_url();
}

/**
 * 役員・理事紹介ページ（[alumni_officers] ショートコードが自動的に配置
 * される固定ページ）のURL、またはCore無効時は ''。トップページやメニュー
 * はこの関数でリンクし、スラッグを直接ハードコードしない。
 *
 * @return string
 */
function alumni_theme_get_officers_listing_url() {
	if ( ! alumni_theme_core_active() ) {
		return '';
	}

	return alumni_core_get_officers_listing_url();
}

/**
 * 規約類一覧ページ（[alumni_terms_list] ショートコードが自動的に配置
 * される固定ページ）のURL、またはCore無効時は ''。トップページやメニュー
 * はこの関数でリンクし、スラッグを直接ハードコードしない。
 *
 * @return string
 */
function alumni_theme_get_terms_listing_url() {
	if ( ! alumni_theme_core_active() ) {
		return '';
	}

	return alumni_core_get_terms_listing_url();
}

/**
 * 上部メニュー('top')か左サイドメニュー('side')かの現在の管理者設定。
 * Core無効時も安全な既定値（'top'）を返すので、Themeはこの値が常に有効
 * な2値のどちらかであることを前提にしてよい。
 *
 * @return string 'top' または 'side'。
 */
function alumni_theme_get_nav_layout() {
	if ( ! alumni_theme_core_active() ) {
		return 'top';
	}

	return alumni_core_get_nav_layout();
}

/**
 * 対象者（大カテゴリ）の値 => 表示ラベルの一覧、またはCore無効時は空配列。
 *
 * @return array<string,string>
 */
function alumni_theme_get_audience_labels() {
	if ( ! alumni_theme_core_active() ) {
		return array();
	}

	return alumni_core_get_audience_labels();
}

/**
 * 公開済みコンテンツ階層ツリー（1つの対象者、または全対象者）、または
 * Core無効時は空配列。メニュー生成・トップページのコンテンツ選択候補など、
 * 「今どんな階層が存在するか」が必要などこでも使える。
 *
 * @param string|null $audience alumni_core_get_audience_labels()のキーの
 *                                いずれか、またはnullで全対象者。
 * @return array[] 各要素: array('post'=>WP_Post,'children'=>同じ形の配列)。
 */
function alumni_theme_get_content_tree( $audience = null ) {
	if ( ! alumni_theme_core_active() ) {
		return array();
	}

	return alumni_core_get_content_tree( $audience );
}

/**
 * $parent_idの直接の子コンテンツ（公開済みのみ）、またはCore無効時は
 * 空配列。フォルダ（本文を持たないコンテンツ）の公開ページが、自分の
 * 子コンテンツの一覧を表示するのに使う。
 *
 * @param int $parent_id
 * @return WP_Post[]
 */
function alumni_theme_get_content_children( $parent_id ) {
	if ( ! alumni_theme_core_active() ) {
		return array();
	}

	return alumni_core_get_content_children( $parent_id );
}

/**
 * $postの祖先（ルートが先頭）、またはCore無効時は空配列。パンくず表示用。
 * コンテンツ自身の階層（Content_Hierarchy）を基準にする — 以下の
 * alumni_theme_get_menu_ancestors_for_content() のフォールバックとして
 * 使う（メニュー構成にまだ配置されていないコンテンツでもパンくずが
 * 出せるように）。
 *
 * @param int|WP_Post|null $post
 * @return WP_Post[]
 */
function alumni_theme_get_content_ancestors( $post = null ) {
	if ( ! alumni_theme_core_active() ) {
		return array();
	}

	return alumni_core_get_content_ancestors( $post );
}

/**
 * $content_idを参照する最初のメニュー項目の祖先（ルートが先頭、
 * label/url解決済み）、またはCore無効時／どのメニュー項目からも参照
 * されていない場合は空配列。パンくずは「できるだけメニュー構造を基準に
 * する」ため、single-alumni_content.phpはこちらを優先し、空の場合だけ
 * alumni_theme_get_content_ancestors()（コンテンツ自身の階層）へ
 * フォールバックする。
 *
 * @param int $content_id
 * @return array[] 各要素: array('label'=>string,'url'=>string,...)。
 */
function alumni_theme_get_menu_ancestors_for_content( $content_id ) {
	if ( ! alumni_theme_core_active() ) {
		return array();
	}

	return alumni_core_get_menu_ancestors_for_content( $content_id );
}

/**
 * alumni_theme_get_content_tree() が返すノード配列を、階層に沿った
 * <li>...</li> のマークアップへ再帰的に変換する（呼び出し側で<ul>に
 * まとめる）。子を持つ<li>には alumni-nav-item-has-children クラスを
 * 付け、子は入れ子の<ul class="alumni-nav-submenu">として続ける — 既存の
 * ドリルダウンサブメニューと同じマークアップ規則を、任意の深さの階層に
 * 対して適用する。
 *
 * フォルダ（Modules\Content\Post_Type::KIND_FOLDER）も他のコンテンツと
 * 同様にリンク付きの項目として描画される — フォルダ自身のページが
 * その子コンテンツの一覧を表示するため（single-alumni_content.php）、
 * メニューとページの階層が一致する。
 *
 * @param array[] $nodes alumni_theme_get_content_tree()と同じ形。
 * @return string
 */
function alumni_theme_render_content_tree_items( array $nodes ) {
	$html = '';

	foreach ( $nodes as $node ) {
		$post           = $node['post'];
		$url            = alumni_theme_get_content_url( $post->ID );
		$label          = $post->post_title ? $post->post_title : '';
		$children_items = ! empty( $node['children'] ) ? alumni_theme_render_content_tree_items( $node['children'] ) : '';

		$html .= '<li' . ( $children_items ? ' class="alumni-nav-item-has-children"' : '' ) . '>';
		$html .= '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
		if ( $children_items ) {
			$html .= '<ul class="alumni-nav-submenu">' . $children_items . '</ul>';
		}
		$html .= '</li>';
	}

	return $html;
}

/**
 * メニュー構成の公開ツリー（対象者1つ、または全対象者）、またはCore無効時
 * は空配列。各ノードは既にlabel/urlが解決済み — Theme側はフォルダか
 * コンテンツかシステムページか役員一覧かを一切気にする必要がない
 * （Menu_Structure::resolve_item()参照）。
 *
 * @param string|null $audience \AlumniCore\Includes\Menu_Structure::AUDIENCE_*、
 *                                またはnullで全対象者。
 * @return array[] 各要素: array('item_id'=>string,'type'=>'folder'|'content',
 *                   'label'=>string,'url'=>string,'children'=>同じ形の配列)。
 */
function alumni_theme_get_menu_tree( $audience = null ) {
	if ( ! alumni_theme_core_active() ) {
		return array();
	}

	return alumni_core_get_menu_tree( $audience );
}

/**
 * alumni_theme_get_menu_tree() が返すノード配列を、階層に沿った
 * <li>...</li> のマークアップへ再帰的に変換する（呼び出し側で<ul>に
 * まとめる）— alumni_theme_render_content_tree_items() と同じマークアップ
 * 規則。フォルダはリンク先を持たない（href="#"、クリックで開閉する
 * だけ）— site-navigation.phpのJS（navigation.js）が
 * .alumni-nav-drilldown-toggle をどの深さでも汎用的に開閉できるため、
 * フォルダ自身の公開ページを別途用意する必要がない
 * （「フォルダは必ずしもコンテンツではない」）。
 *
 * @param array[] $nodes alumni_theme_get_menu_tree()と同じ形。
 * @return string
 */
function alumni_theme_render_menu_items( array $nodes ) {
	$html = '';

	foreach ( $nodes as $node ) {
		$children_items = ! empty( $node['children'] ) ? alumni_theme_render_menu_items( $node['children'] ) : '';
		$is_folder      = ( 'folder' === $node['type'] );
		$has_children    = (bool) $children_items;

		$html .= '<li' . ( $has_children ? ' class="alumni-nav-item-has-children"' : '' ) . '>';

		if ( $is_folder ) {
			// フォルダは自分自身の公開ページを持たない。子がある場合は
			// クリックで開閉するトグルにし、子が1件もない（空の）フォルダは
			// 押しても何も起きないリンクにしないよう、ただのテキストとして
			// 出す。
			if ( $has_children ) {
				$html .= '<a href="#" class="alumni-nav-drilldown-toggle" aria-haspopup="true" aria-expanded="false">' . esc_html( $node['label'] ) . '</a>';
			} else {
				$html .= '<span class="alumni-nav-empty-folder">' . esc_html( $node['label'] ) . '</span>';
			}
		} else {
			// コンテンツへのリンク項目に子がある場合（同一コンテンツの
			// 複数配置等で稀に起こり得る構成）でも、リンク自体は常に
			// 参照先へ遷移する — 開閉トグルは別に用意する。
			$html .= '<a href="' . esc_url( $node['url'] ) . '">' . esc_html( $node['label'] ) . '</a>';
			if ( $has_children ) {
				$html .= '<button type="button" class="alumni-nav-drilldown-toggle alumni-nav-drilldown-caret" aria-haspopup="true" aria-expanded="false" aria-label="' . esc_attr__( 'サブメニューを開く', 'alumni-theme' ) . '">▾</button>';
			}
		}

		if ( $has_children ) {
			$html .= '<ul class="alumni-nav-submenu">' . $children_items . '</ul>';
		}

		$html .= '</li>';
	}

	return $html;
}

/**
 * トップページの全セクション（表示順・見出し・段数・各段のスロット、
 * すでに検証済み）、またはCore無効時は空配列。
 *
 * @return array[]
 */
function alumni_theme_get_homepage_sections() {
	if ( ! alumni_theme_core_active() ) {
		return array();
	}

	return alumni_core_get_homepage_sections();
}

/**
 * @param string $system_key
 * @return string Core無効時、または未知のキーの場合は ''。
 */
function alumni_theme_get_system_slot_label( $system_key ) {
	if ( ! alumni_theme_core_active() ) {
		return '';
	}

	return alumni_core_get_system_slot_label( $system_key );
}

/**
 * @param string $system_key
 * @return string Core無効時、または未知のキーの場合は ''。
 */
function alumni_theme_get_system_slot_url( $system_key ) {
	if ( ! alumni_theme_core_active() ) {
		return '';
	}

	return alumni_core_get_system_slot_url( $system_key );
}

/**
 * 「更新日：2026年9月1日」のような、表示用に整形済みの更新日時文字列、
 * またはCore無効時／未解決の場合は ''。WordPress投稿（自由コンテンツ・
 * 人物挨拶・規約類・フォルダ・ニュース・イベント、いずれも）は
 * post_modifiedをそのまま使う。
 *
 * @param int|WP_Post|null $post Post ID or object.
 * @return string
 */
function alumni_theme_get_updated_at( $post = null ) {
	if ( ! alumni_theme_core_active() ) {
		return '';
	}

	return alumni_core_format_updated_at( alumni_core_get_updated_at( $post ) );
}

/**
 * 役員・理事一覧データ（対象のリストに限らず、全体）が最後に保存された
 * 日時の表示用文字列、またはCore無効時／未保存の場合は ''。
 *
 * @return string
 */
function alumni_theme_get_officer_lists_updated_at() {
	if ( ! alumni_theme_core_active() ) {
		return '';
	}

	return alumni_core_format_updated_at( alumni_core_get_officer_lists_updated_at() );
}

/**
 * 役員・理事一覧の任期を表す表示用文字列（alumni_theme_get_officer_list()
 * 等が返す一覧データを渡す）、またはCore無効時／任期未設定の場合は ''。
 *
 * @param array $list
 * @return string
 */
function alumni_theme_format_officer_list_term( array $list ) {
	if ( ! alumni_theme_core_active() ) {
		return '';
	}

	return alumni_core_format_officer_list_term( $list );
}
