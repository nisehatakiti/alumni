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
 * Every saved 役員・理事 row, in display order, with stale リンク先
 * already resolved to 0 by Core — or an empty array when Core is
 * inactive.
 *
 * @return array[]
 */
function alumni_theme_get_officers() {
	if ( ! alumni_theme_core_active() ) {
		return array();
	}

	return alumni_core_get_officers();
}

/**
 * @param array $officer One row from alumni_theme_get_officers().
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
 * 現在の一覧ページの種別（'news'／'event'）。/news-events/ の統合一覧や
 * それ以外のページでは '' を返す。
 *
 * @return string
 */
function alumni_theme_get_news_events_listing_type() {
	if ( ! alumni_theme_core_active() ) {
		return '';
	}

	return alumni_core_get_news_events_listing_type();
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
