<?php
/**
 * ニュース一覧（/news/）・イベント一覧（/events/）を公開ページとして
 * 提供するショートコードと、そのための固定ページの自動作成.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes\Modules\NewsEvents;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 以前は post_type=alumni_news_event を独自の書き換えルール（/news/,
 * /events/）だけで絞り込む方式だったが、実際のWordPress環境で「一覧画面
 * として機能しない」ことが確認された。カスタム書き換えルールは
 * flush_rewrite_rules() の実行タイミングに依存しやすく、この不具合の
 * 温床になっていたと判断し、この案件で既に実績のある
 * Graduation_Lookup_Shortcode / Officers_Shortcode と全く同じ
 * 「固定ページを自動作成し、そこにショートコードを設置する」方式へ
 * 置き換える。固定ページのURLはWordPress標準のページ表示の仕組みに
 * 乗るため、rewrite flushのタイミングに一切依存しない。
 *
 * 既存の /news-events/ 統合アーカイブ・個別記事URLはCPT自身の標準的な
 * rewrite（Post_Type::register()）に乗っており、この不具合とは無関係
 * なため一切変更していない。
 */
class Listing_Shortcode {

	/**
	 * Option names storing each auto-created page's post ID.
	 */
	const OPTION_NEWS_PAGE_ID   = 'alumni_core_news_listing_page_id';
	const OPTION_EVENTS_PAGE_ID = 'alumni_core_events_listing_page_id';

	/**
	 * Page slugs / shortcode tags.
	 */
	const NEWS_PAGE_SLUG   = 'news';
	const EVENTS_PAGE_SLUG = 'events';
	const NEWS_SHORTCODE   = 'alumni_news_list';
	const EVENTS_SHORTCODE = 'alumni_events_list';

	/**
	 * Registers hooks. Safe to call unconditionally — the page-creation
	 * check is gated internally to is_admin().
	 */
	public static function register() {
		add_shortcode( self::NEWS_SHORTCODE, array( __CLASS__, 'render_news_shortcode' ) );
		add_shortcode( self::EVENTS_SHORTCODE, array( __CLASS__, 'render_events_shortcode' ) );

		if ( is_admin() ) {
			add_action( 'admin_init', array( __CLASS__, 'maybe_create_pages' ) );
		}
	}

	/**
	 * Creates both 固定ページ if they don't already exist. Idempotent, same
	 * "check option → adopt existing same-slug page → create" logic as
	 * Graduation_Lookup_Shortcode::maybe_create_page().
	 */
	public static function maybe_create_pages() {
		self::maybe_create_page( self::OPTION_NEWS_PAGE_ID, self::NEWS_PAGE_SLUG, __( 'ニュース一覧', 'alumni-core' ), self::NEWS_SHORTCODE );
		self::maybe_create_page( self::OPTION_EVENTS_PAGE_ID, self::EVENTS_PAGE_SLUG, __( 'イベント一覧', 'alumni-core' ), self::EVENTS_SHORTCODE );
	}

	/**
	 * @param string $option   Option name for the page ID.
	 * @param string $slug     Page slug.
	 * @param string $title    Page title.
	 * @param string $shortcode Shortcode tag to place as the page content.
	 */
	private static function maybe_create_page( $option, $slug, $title, $shortcode ) {
		$page_id = (int) get_option( $option, 0 );

		if ( $page_id && 'page' === get_post_type( $page_id ) ) {
			return; // Already created and still exists.
		}

		$existing = get_page_by_path( $slug, OBJECT, 'page' );

		if ( $existing ) {
			update_option( $option, $existing->ID );
			return;
		}

		$new_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => '[' . $shortcode . ']',
			),
			true
		);

		if ( ! is_wp_error( $new_id ) && $new_id ) {
			update_option( $option, $new_id );
		}
	}

	/**
	 * The public URL of the ニュース一覧 page.
	 *
	 * @return string
	 */
	public static function get_news_url() {
		return self::get_url( self::OPTION_NEWS_PAGE_ID, self::NEWS_PAGE_SLUG );
	}

	/**
	 * The public URL of the イベント一覧 page.
	 *
	 * @return string
	 */
	public static function get_events_url() {
		return self::get_url( self::OPTION_EVENTS_PAGE_ID, self::EVENTS_PAGE_SLUG );
	}

	/**
	 * @param string $option Option name for the page ID.
	 * @param string $slug   Fallback slug, for before the page exists.
	 * @return string
	 */
	private static function get_url( $option, $slug ) {
		$page_id = (int) get_option( $option, 0 );

		if ( $page_id && 'page' === get_post_type( $page_id ) ) {
			return (string) get_permalink( $page_id );
		}

		return home_url( '/' . $slug . '/' );
	}

	/**
	 * Renders [alumni_news_list].
	 *
	 * @return string
	 */
	public static function render_news_shortcode() {
		return self::render_listing( Post_Type::TYPE_NEWS );
	}

	/**
	 * Renders [alumni_events_list].
	 *
	 * @return string
	 */
	public static function render_events_shortcode() {
		return self::render_listing( Post_Type::TYPE_EVENT );
	}

	/**
	 * Renders a full (non-paginated) list of published ニュース or イベント,
	 * newest first: 投稿日（イベントは開催日も）／タイトル／概要／詳細への
	 * リンク.
	 *
	 * @param string $type Post_Type::TYPE_NEWS or Post_Type::TYPE_EVENT.
	 * @return string
	 */
	private static function render_listing( $type ) {
		$query = alumni_core_get_news_events_query(
			array(
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- filtering by content type is the entire purpose of this query.
					array(
						'key'   => Post_Type::META_CONTENT_TYPE,
						'value' => $type,
					),
				),
				'posts_per_page' => -1,
			)
		);

		$is_event = ( Post_Type::TYPE_EVENT === $type );

		ob_start();
		?>
		<div class="alumni-news-events-listing">
			<?php if ( $query->have_posts() ) : ?>
				<ul class="alumni-news-events-listing-list">
					<?php
					while ( $query->have_posts() ) :
						$query->the_post();
						$event_date = $is_event ? \AlumniCore\Includes\Modules\NewsEvents\Post_Type::get_event_date() : '';
						$excerpt    = wp_trim_words( wp_strip_all_tags( get_the_content() ), 40 );
						?>
						<li class="alumni-news-events-listing-item">
							<a class="alumni-news-events-listing-link" href="<?php the_permalink(); ?>">
								<?php if ( $event_date ) : ?>
									<span class="alumni-news-events-listing-event-date"><?php echo esc_html( $event_date ); ?></span>
								<?php endif; ?>
								<span class="alumni-news-events-listing-date"><?php echo esc_html( get_the_date( 'Y-m-d' ) ); ?></span>
								<span class="alumni-news-events-listing-title"><?php the_title(); ?></span>
								<?php if ( $excerpt ) : ?>
									<span class="alumni-news-events-listing-excerpt"><?php echo esc_html( $excerpt ); ?></span>
								<?php endif; ?>
							</a>
						</li>
						<?php
					endwhile;
					wp_reset_postdata();
					?>
				</ul>
			<?php else : ?>
				<p class="alumni-notice">
					<?php
					echo esc_html(
						$is_event
							? __( '現在、公開されているイベントはありません。', 'alumni-core' )
							: __( '現在、公開されているニュースはありません。', 'alumni-core' )
					);
					?>
				</p>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
