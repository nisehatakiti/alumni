<?php
/**
 * Global template-tag functions for ニュース／イベント, for theme use.
 *
 * Same rules as public/functions.php: these are the only part of the
 * ニュース／イベント module a theme should talk to directly, every
 * function is prefixed with alumni_core_, and every call site in a theme
 * should be guarded with function_exists() so the theme keeps working
 * when this plugin is inactive.
 *
 * @package AlumniCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'alumni_core_news_events_post_type' ) ) {
	/**
	 * The ニュース／イベント post type slug, so themes never hardcode it.
	 *
	 * @return string
	 */
	function alumni_core_news_events_post_type() {
		return \AlumniCore\Includes\Modules\NewsEvents\Post_Type::SLUG;
	}
}

if ( ! function_exists( 'alumni_core_is_event' ) ) {
	/**
	 * @param int|WP_Post|null $post Post ID or object.
	 * @return bool
	 */
	function alumni_core_is_event( $post = null ) {
		return \AlumniCore\Includes\Modules\NewsEvents\Post_Type::is_event( $post );
	}
}

if ( ! function_exists( 'alumni_core_get_event_date' ) ) {
	/**
	 * @param int|WP_Post|null $post Post ID or object.
	 * @return string Y-m-d, or '' when not an event / not set.
	 */
	function alumni_core_get_event_date( $post = null ) {
		return \AlumniCore\Includes\Modules\NewsEvents\Post_Type::get_event_date( $post );
	}
}

if ( ! function_exists( 'alumni_core_get_news_event_display_date' ) ) {
	/**
	 * The date to display for a ニュース／イベント card: the event date
	 * for events, the published date for news.
	 *
	 * @param int|WP_Post|null $post Post ID or object.
	 * @return string Y-m-d, or '' if the post can't be resolved.
	 */
	function alumni_core_get_news_event_display_date( $post = null ) {
		return \AlumniCore\Includes\Modules\NewsEvents\Post_Type::get_display_date( $post );
	}
}

if ( ! function_exists( 'alumni_core_get_news_listing_url' ) ) {
	/**
	 * URL of the ニュース一覧 page (auto-created by
	 * Modules\NewsEvents\Listing_Shortcode).
	 *
	 * @return string
	 */
	function alumni_core_get_news_listing_url() {
		return \AlumniCore\Includes\Modules\NewsEvents\Listing_Shortcode::get_news_url();
	}
}

if ( ! function_exists( 'alumni_core_get_events_listing_url' ) ) {
	/**
	 * URL of the イベント一覧 page (auto-created by
	 * Modules\NewsEvents\Listing_Shortcode).
	 *
	 * @return string
	 */
	function alumni_core_get_events_listing_url() {
		return \AlumniCore\Includes\Modules\NewsEvents\Listing_Shortcode::get_events_url();
	}
}

if ( ! function_exists( 'alumni_core_get_news_teaser' ) ) {
	/**
	 * A short, newest-first ニュース query for a homepage slot — same
	 * shape as alumni_core_get_news_events_query() but pre-filtered to
	 * ニュース only, so Theme code never needs to know the underlying
	 * postmeta key used to distinguish ニュース from イベント.
	 *
	 * @param int $limit
	 * @return WP_Query
	 */
	function alumni_core_get_news_teaser( $limit = 3 ) {
		return alumni_core_get_news_events_query(
			array(
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- filtering by content type is the entire purpose of this query.
					array(
						'key'   => \AlumniCore\Includes\Modules\NewsEvents\Post_Type::META_CONTENT_TYPE,
						'value' => \AlumniCore\Includes\Modules\NewsEvents\Post_Type::TYPE_NEWS,
					),
				),
				'posts_per_page' => max( 1, (int) $limit ),
			)
		);
	}
}

if ( ! function_exists( 'alumni_core_get_events_teaser' ) ) {
	/**
	 * The イベント counterpart of alumni_core_get_news_teaser(): every
	 * published イベント, newest-post-first, with no 今後／終了済みの区別.
	 * Kept for backward compatibility (existing callers/back-compat), but
	 * the トップページ表示は alumni_core_get_upcoming_events()/
	 * alumni_core_get_past_events() を使う（下記）— 開催日基準で今後・
	 * 終了済みを分けて表示するため。
	 *
	 * @param int $limit
	 * @return WP_Query
	 */
	function alumni_core_get_events_teaser( $limit = 3 ) {
		return alumni_core_get_news_events_query(
			array(
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- filtering by content type is the entire purpose of this query.
					array(
						'key'   => \AlumniCore\Includes\Modules\NewsEvents\Post_Type::META_CONTENT_TYPE,
						'value' => \AlumniCore\Includes\Modules\NewsEvents\Post_Type::TYPE_EVENT,
					),
				),
				'posts_per_page' => max( 1, (int) $limit ),
			)
		);
	}
}

if ( ! function_exists( 'alumni_core_get_events_split_by_date' ) ) {
	/**
	 * すべての公開済みイベントを、開催日(META_EVENT_DATE、'Y-m-d')を基準に
	 * 「今後」(開催日が今日以降、今日を含む)と「終了済み」(開催日が今日
	 * より前)へ分け、それぞれ適切な順序(今後は開催日の近い順=昇順、
	 * 終了済みは開催日の新しい順=降順)に整列して返す。
	 *
	 * WP_Queryのmeta_query比較演算子(>=・<のDATE型比較)には頼らず、PHP側で
	 * 判定・整列している — 同窓会サイトのイベント件数は現実的に大きくなら
	 * ないため、一括取得してPHPで振り分けても性能上の懸念はなく、日付
	 * 未設定のイベントの安全な除外や、将来の比較ロジック変更にも対応
	 * しやすい。
	 *
	 * @param string|null $reference_date 基準日('Y-m-d')。省略時はWordPress
	 *                                      のサイト日時（current_time('Y-m-d')、
	 *                                      サイトのタイムゾーン設定を反映）。
	 *                                      テストで「今日」を固定するためだけ
	 *                                      の引数で、本番コードからは常に省略
	 *                                      する（ハードコードされた日付比較で
	 *                                      はなく、呼び出し側が明示的に基準日
	 *                                      を渡せる形にすることで、実行時刻に
	 *                                      依存しない決定的なテストを書ける
	 *                                      ようにする）。
	 * @return array{upcoming: WP_Post[], past: WP_Post[]}
	 */
	function alumni_core_get_events_split_by_date( $reference_date = null ) {
		$query = alumni_core_get_news_events_query(
			array(
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- filtering by content type is the entire purpose of this query.
					array(
						'key'     => \AlumniCore\Includes\Modules\NewsEvents\Post_Type::META_CONTENT_TYPE,
						'value'   => \AlumniCore\Includes\Modules\NewsEvents\Post_Type::TYPE_EVENT,
						'compare' => '=',
					),
				),
				// 現実的なイベント総数を大きく超えない上限 — 全件をここで
				// 取得してからPHP側で今後／終了済みに振り分ける。
				'posts_per_page' => 200,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		if ( ! is_string( $reference_date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $reference_date ) ) {
			$reference_date = current_time( 'Y-m-d' );
		}

		$upcoming      = array();
		$past          = array();
		$seen_post_ids = array(); // 同じ投稿がmeta_queryのJOIN等で複数行として
		// 返ってきても、一覧には1回だけ現れるようにする防御的な重複排除
		// （「今後」と「終了済み」の両方に同じイベントが入ることは、開催日
		// で一意に分岐が決まるこの処理の性質上そもそも起こり得ない — ここで
		// 防いでいるのは同一の一覧内での重複のみ）。

		foreach ( $query->posts as $alumni_event_post ) {
			if ( isset( $seen_post_ids[ $alumni_event_post->ID ] ) ) {
				continue;
			}
			$seen_post_ids[ $alumni_event_post->ID ] = true;

			$alumni_event_date = \AlumniCore\Includes\Modules\NewsEvents\Post_Type::get_event_date( $alumni_event_post );

			if ( '' === $alumni_event_date ) {
				continue; // 開催日未設定のイベントはどちらにも表示しようがない。
			}

			if ( $alumni_event_date >= $reference_date ) {
				$upcoming[] = array( 'post' => $alumni_event_post, 'date' => $alumni_event_date );
			} else {
				$past[] = array( 'post' => $alumni_event_post, 'date' => $alumni_event_date );
			}
		}

		usort(
			$upcoming,
			function ( $a, $b ) {
				// 近い順(昇順)。同一日に複数ある場合は投稿IDを安定した
				// タイブレークに使う（日付だけでは同着になり得るため、
				// 呼び出しのたびに順序が入れ替わらないようにする）。
				$cmp = strcmp( $a['date'], $b['date'] );
				return 0 !== $cmp ? $cmp : ( $a['post']->ID <=> $b['post']->ID );
			}
		);
		usort(
			$past,
			function ( $a, $b ) {
				// 新しい順(降順)。同日タイブレークは昇順と揃える。
				$cmp = strcmp( $b['date'], $a['date'] );
				return 0 !== $cmp ? $cmp : ( $a['post']->ID <=> $b['post']->ID );
			}
		);

		return array(
			'upcoming' => array_column( $upcoming, 'post' ),
			'past'     => array_column( $past, 'post' ),
		);
	}
}

if ( ! function_exists( 'alumni_core_get_upcoming_events' ) ) {
	/**
	 * 開催日が今日以降(今日を含む)のイベントを、開催日が近い順に返す。
	 *
	 * @param int $limit
	 * @return WP_Post[]
	 */
	function alumni_core_get_upcoming_events( $limit = 3 ) {
		$split = alumni_core_get_events_split_by_date();

		return array_slice( $split['upcoming'], 0, max( 1, (int) $limit ) );
	}
}

if ( ! function_exists( 'alumni_core_get_past_events' ) ) {
	/**
	 * 開催日が今日より前のイベントを、開催日が新しい順に返す。
	 *
	 * @param int $limit
	 * @return WP_Post[]
	 */
	function alumni_core_get_past_events( $limit = 3 ) {
		$split = alumni_core_get_events_split_by_date();

		return array_slice( $split['past'], 0, max( 1, (int) $limit ) );
	}
}

if ( ! function_exists( 'alumni_core_get_news_events_query' ) ) {
	/**
	 * Runs a WP_Query for published ニュース／イベント, newest first by
	 * default.
	 *
	 * @param array $args Extra/overriding WP_Query args.
	 * @return WP_Query
	 */
	function alumni_core_get_news_events_query( $args = array() ) {
		$defaults = array(
			'post_type'      => alumni_core_news_events_post_type(),
			'post_status'    => 'publish',
			'posts_per_page' => 5,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		return new WP_Query( wp_parse_args( $args, $defaults ) );
	}
}
