<?php
/**
 * 規約類を公開ページとして提供するショートコードと、
 * そのための固定ページの自動作成.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 規約類は alumni_content CPT の一種別（kind=terms）として保存される —
 * 新しい投稿タイプを増やさず、既存の「コンテンツ管理」基盤（メタボックス・
 * 管理画面の一覧列・個別記事URL・single-alumni_content.phpテンプレート）を
 * そのまま再利用する。ここで追加するのは、個々の規約（詳細ページ）への
 * 入口となる一覧ページ（/terms/、[alumni_terms_list]）だけ — 個別詳細
 * ページ自体は既存の alumni_content の仕組みで既に到達可能（
 * alumni_core_get_content_url()）。
 *
 * ページ作成は Graduation_Lookup_Shortcode / Officers_Shortcode と全く
 * 同じ「既に同じスラッグのページがあれば新規作成しない」「作成したページ
 * IDをoptionに保存し、再度の有効化で重複作成しない」パターン。
 */
class Terms_Listing_Shortcode {

	/**
	 * Option name storing the auto-created page's post ID.
	 */
	const OPTION_PAGE_ID = 'alumni_core_terms_listing_page_id';

	/**
	 * Page slug / shortcode tag.
	 */
	const PAGE_SLUG = 'terms';
	const SHORTCODE = 'alumni_terms_list';

	/**
	 * Registers hooks. Safe to call unconditionally — the page-creation
	 * check is gated internally to is_admin().
	 */
	public static function register() {
		add_shortcode( self::SHORTCODE, array( __CLASS__, 'render_shortcode' ) );

		if ( is_admin() ) {
			add_action( 'admin_init', array( __CLASS__, 'maybe_create_page' ) );
		}
	}

	/**
	 * Creates the 規約類 固定ページ if it doesn't already exist and isn't
	 * already tracked. Idempotent.
	 */
	public static function maybe_create_page() {
		$page_id = (int) get_option( self::OPTION_PAGE_ID, 0 );

		if ( $page_id && 'page' === get_post_type( $page_id ) ) {
			return;
		}

		$existing = get_page_by_path( self::PAGE_SLUG, OBJECT, 'page' );

		if ( $existing ) {
			update_option( self::OPTION_PAGE_ID, $existing->ID );
			return;
		}

		$new_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => __( '規約類', 'alumni-core' ),
				'post_name'    => self::PAGE_SLUG,
				'post_content' => '[' . self::SHORTCODE . ']',
			),
			true
		);

		if ( ! is_wp_error( $new_id ) && $new_id ) {
			update_option( self::OPTION_PAGE_ID, $new_id );
		}
	}

	/**
	 * The public URL of the 規約類 一覧ページ, for Theme navigation/links.
	 *
	 * @return string
	 */
	public static function get_url() {
		$page_id = (int) get_option( self::OPTION_PAGE_ID, 0 );

		if ( $page_id && 'page' === get_post_type( $page_id ) ) {
			return (string) get_permalink( $page_id );
		}

		return home_url( '/' . self::PAGE_SLUG . '/' );
	}

	/**
	 * Renders [alumni_terms_list]: every published 規約類, 表示順順に
	 * リンクの一覧として表示する。
	 *
	 * @return string
	 */
	public static function render_shortcode() {
		$query = alumni_core_get_terms_query( array( 'posts_per_page' => -1 ) );

		ob_start();
		?>
		<div class="alumni-terms-listing">
			<?php if ( $query->have_posts() ) : ?>
				<ul class="alumni-terms-listing-list">
					<?php
					while ( $query->have_posts() ) :
						$query->the_post();
						$display_title = \AlumniCore\Includes\Modules\Content\Post_Type::get_terms_display_title();
						?>
						<li>
							<a class="alumni-terms-listing-link" href="<?php the_permalink(); ?>">
								<?php echo esc_html( $display_title ); ?>
							</a>
						</li>
						<?php
					endwhile;
					wp_reset_postdata();
					?>
				</ul>
			<?php else : ?>
				<p class="alumni-notice">
					<?php esc_html_e( '現在、公開されている規約類はありません。', 'alumni-core' ); ?>
				</p>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
