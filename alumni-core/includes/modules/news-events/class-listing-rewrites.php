<?php
/**
 * ニュース一覧（/news/）・イベント一覧（/events/）専用の書き換えルール。
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes\Modules\NewsEvents;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 既存の alumni_news_event 投稿タイプ・投稿URL・詳細URLはそのまま
 * （Post_Type::register() の rewrite 設定は変更しない）。ここで追加する
 * のは、同じ投稿タイプを内容種別（Post_Type::META_CONTENT_TYPE）で
 * 絞り込むための新しい入口URL /news/ ・ /events/ のみで、既存の
 * /news-events/ 統合一覧および個別投稿URLは一切変更されない。
 *
 * post_type だけを指定するクエリ（name などの投稿識別子を含まない）は
 * WordPressにより投稿タイプアーカイブとして扱われるため、既存の
 * archive-alumni_news_event.php テンプレートがそのまま再利用される。
 */
class Listing_Rewrites {

	/**
	 * このURLがどちらの一覧かを表すクエリ変数名。
	 */
	const QUERY_VAR = 'alumni_content_type';

	/**
	 * Registers this class's hooks.
	 */
	public static function register() {
		add_action( 'init', array( __CLASS__, 'register_rewrite_rules' ) );
		add_filter( 'query_vars', array( __CLASS__, 'register_query_vars' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'filter_listing_query' ) );
	}

	/**
	 * Adds the /news/ and /events/ rewrite rules. 'top' priority so these
	 * take precedence over any conflicting rule that may be added later
	 * (e.g. by a WordPress Page using the same slug — an accepted, documented
	 * trade-off of using top-level slugs here).
	 *
	 * Also explicitly called from Activator::activate(), since
	 * register_activation_hook() fires before 'init' on that request.
	 */
	public static function register_rewrite_rules() {
		add_rewrite_rule(
			'^news/page/([0-9]{1,})/?$',
			'index.php?post_type=' . Post_Type::SLUG . '&' . self::QUERY_VAR . '=' . Post_Type::TYPE_NEWS . '&paged=$matches[1]',
			'top'
		);
		add_rewrite_rule(
			'^news/?$',
			'index.php?post_type=' . Post_Type::SLUG . '&' . self::QUERY_VAR . '=' . Post_Type::TYPE_NEWS,
			'top'
		);
		add_rewrite_rule(
			'^events/page/([0-9]{1,})/?$',
			'index.php?post_type=' . Post_Type::SLUG . '&' . self::QUERY_VAR . '=' . Post_Type::TYPE_EVENT . '&paged=$matches[1]',
			'top'
		);
		add_rewrite_rule(
			'^events/?$',
			'index.php?post_type=' . Post_Type::SLUG . '&' . self::QUERY_VAR . '=' . Post_Type::TYPE_EVENT,
			'top'
		);
	}

	/**
	 * Makes self::QUERY_VAR a recognized public query var, so
	 * get_query_var( self::QUERY_VAR ) / WP_Query::get() work from the URLs
	 * registered above.
	 *
	 * @param string[] $vars Existing public query vars.
	 * @return string[]
	 */
	public static function register_query_vars( $vars ) {
		$vars[] = self::QUERY_VAR;

		return $vars;
	}

	/**
	 * Narrows the /news/ and /events/ archive queries down to just that
	 * content type, via the same postmeta (Post_Type::META_CONTENT_TYPE)
	 * the admin screen and theme already use to decide ニュース vs イベント.
	 * Every alumni_news_event post has this meta explicitly saved by
	 * Meta_Box::save() (defaulting to 'news' when unset in the form), so
	 * this meta_query reliably matches every existing post — no data
	 * migration or backfill is needed.
	 *
	 * @param \WP_Query $query The query being filtered.
	 */
	public static function filter_listing_query( \WP_Query $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$type = $query->get( self::QUERY_VAR );

		if ( ! in_array( $type, array( Post_Type::TYPE_NEWS, Post_Type::TYPE_EVENT ), true ) ) {
			return;
		}

		$query->set(
			'meta_query', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- filtering by content type is the entire purpose of this query.
			array(
				array(
					'key'   => Post_Type::META_CONTENT_TYPE,
					'value' => $type,
				),
			)
		);
	}

	/**
	 * The current request's listing type, for templates to branch on
	 * (e.g. the archive heading) without querying postmeta or rewrite
	 * internals directly.
	 *
	 * @return string Post_Type::TYPE_NEWS, Post_Type::TYPE_EVENT, or ''
	 *                 (the combined /news-events/ archive, or any other
	 *                 request).
	 */
	public static function get_listing_type() {
		$type = get_query_var( self::QUERY_VAR );

		return in_array( $type, array( Post_Type::TYPE_NEWS, Post_Type::TYPE_EVENT ), true ) ? $type : '';
	}
}
