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
