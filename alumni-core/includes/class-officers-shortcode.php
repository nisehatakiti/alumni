<?php
/**
 * 役員・理事一覧（複数）を公開ページとして提供するショートコードと、
 * そのための固定ページの自動作成.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 役員・理事情報は複数の「一覧」（Officer_Lists）に分かれているため、公開側
 * も二段構えになっている:
 *
 *  - 「役員・理事紹介」固定ページ（/officers/、[alumni_officers]）:
 *    全一覧への入口となる一覧のリスト（インデックス）。
 *  - 各一覧ごとの固定ページ（[alumni_officer_list id="..."]）: その一覧
 *    だけの表。一覧の作成時（Admin\Pages\Officers_Page）・admin_initの
 *    べき等チェックの両方でページを自動作成する — Graduation_Lookup_Shortcode
 *    と同じ「既に同じスラッグのページがあれば新規作成しない」
 *    「作成したページIDを保存し、重複作成しない」パターン。
 *
 * インデックスページのオプション名・スラッグ・ショートコード名は前回の
 * ラウンド（単一一覧だった頃）と同じ値を維持している — 既にサイトに
 * 存在する /officers/ ページをそのまま新しいインデックスとして使い続け、
 * 孤立させないため。
 */
class Officers_Shortcode {

	/**
	 * Option name storing the index page's post ID.
	 */
	const OPTION_INDEX_PAGE_ID = 'alumni_core_officers_page_id';

	/**
	 * Index page slug / shortcode tag.
	 */
	const INDEX_PAGE_SLUG = 'officers';
	const INDEX_SHORTCODE = 'alumni_officers';

	/**
	 * Shortcode tag for a single 一覧's own page.
	 */
	const LIST_SHORTCODE = 'alumni_officer_list';

	/**
	 * Registers hooks. Safe to call unconditionally — the page-creation
	 * check is gated internally to is_admin().
	 */
	public static function register() {
		add_shortcode( self::INDEX_SHORTCODE, array( __CLASS__, 'render_index_shortcode' ) );
		add_shortcode( self::LIST_SHORTCODE, array( __CLASS__, 'render_list_shortcode' ) );

		if ( is_admin() ) {
			add_action( 'admin_init', array( __CLASS__, 'maybe_create_pages' ) );
		}
	}

	/**
	 * Creates the インデックスページ if missing, and creates any 一覧の
	 * ページ that doesn't exist yet (e.g. a list created since the last
	 * sweep, or the single list produced by Officer_Lists's legacy-data
	 * migration). Idempotent.
	 */
	public static function maybe_create_pages() {
		self::maybe_create_index_page();

		foreach ( Officer_Lists::instance()->get_all() as $list ) {
			self::maybe_create_list_page( $list['list_id'], $list['name'], (int) $list['page_id'] );
		}
	}

	/**
	 * Creates the 役員・理事紹介 インデックスページ if it doesn't already
	 * exist and isn't already tracked.
	 */
	private static function maybe_create_index_page() {
		$page_id = (int) get_option( self::OPTION_INDEX_PAGE_ID, 0 );

		if ( $page_id && 'page' === get_post_type( $page_id ) ) {
			return;
		}

		$existing = get_page_by_path( self::INDEX_PAGE_SLUG, OBJECT, 'page' );

		if ( $existing ) {
			update_option( self::OPTION_INDEX_PAGE_ID, $existing->ID );
			return;
		}

		$new_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => __( '役員・理事紹介', 'alumni-core' ),
				'post_name'    => self::INDEX_PAGE_SLUG,
				'post_content' => '[' . self::INDEX_SHORTCODE . ']',
			),
			true
		);

		if ( ! is_wp_error( $new_id ) && $new_id ) {
			update_option( self::OPTION_INDEX_PAGE_ID, $new_id );
		}
	}

	/**
	 * Creates one 一覧's own page if $current_page_id is stale/missing,
	 * and records the new page ID on the list itself.
	 *
	 * @param string $list_id
	 * @param string $name             一覧名, used as both the page title
	 *                                  and (sanitized) the slug seed.
	 * @param int    $current_page_id  The list's currently recorded page
	 *                                  ID (0 if never created).
	 */
	public static function maybe_create_list_page( $list_id, $name, $current_page_id ) {
		if ( $current_page_id && 'page' === get_post_type( $current_page_id ) ) {
			return;
		}

		// WordPress's own wp_insert_post()/wp_unique_post_slug() appends
		// -2, -3, ... automatically if this slug is already taken by
		// another page — including another 一覧's page — so no manual
		// uniqueness handling is needed here.
		$slug = sanitize_title( 'officers-' . $name );
		if ( '' === $slug ) {
			$slug = 'officers-' . substr( $list_id, 0, 8 );
		}

		$new_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $name,
				'post_name'    => $slug,
				'post_content' => '[' . self::LIST_SHORTCODE . ' id="' . $list_id . '"]',
			),
			true
		);

		if ( ! is_wp_error( $new_id ) && $new_id ) {
			Officer_Lists::instance()->set_list_page_id( $list_id, $new_id );
		}
	}

	/**
	 * The public URL of the 役員・理事紹介 インデックスページ.
	 *
	 * @return string
	 */
	public static function get_index_url() {
		$page_id = (int) get_option( self::OPTION_INDEX_PAGE_ID, 0 );

		if ( $page_id && 'page' === get_post_type( $page_id ) ) {
			return (string) get_permalink( $page_id );
		}

		return home_url( '/' . self::INDEX_PAGE_SLUG . '/' );
	}

	/**
	 * The public URL of one 一覧's own page, or '' when the list doesn't
	 * exist or its page hasn't been created yet.
	 *
	 * @param string $list_id
	 * @return string
	 */
	public static function get_list_url( $list_id ) {
		$list = Officer_Lists::instance()->get_list( $list_id );

		if ( null === $list || ! $list['page_id'] ) {
			return '';
		}

		if ( 'page' !== get_post_type( $list['page_id'] ) ) {
			return '';
		}

		return (string) get_permalink( $list['page_id'] );
	}

	/**
	 * Renders [alumni_officers]: an index of every 一覧, each linking to
	 * its own page.
	 *
	 * @return string
	 */
	public static function render_index_shortcode() {
		$lists = alumni_core_get_officer_lists();

		ob_start();
		?>
		<div class="alumni-officers-index">
			<?php if ( empty( $lists ) ) : ?>
				<p class="alumni-notice">
					<?php esc_html_e( '現在、役員・理事の一覧は登録されていません。', 'alumni-core' ); ?>
				</p>
			<?php else : ?>
				<ul class="alumni-officers-index-list">
					<?php foreach ( $lists as $list ) : ?>
						<?php $url = self::get_list_url( $list['list_id'] ); ?>
						<?php if ( $url ) : ?>
							<li>
								<a class="alumni-officers-index-link" href="<?php echo esc_url( $url ); ?>">
									<?php echo esc_html( $list['title'] ? $list['title'] : $list['name'] ); ?>
								</a>
							</li>
						<?php endif; ?>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Renders [alumni_officer_list id="..."]: one 一覧's own table.
	 *
	 * @param array $atts Shortcode attributes; only 'id' is used.
	 * @return string
	 */
	public static function render_list_shortcode( $atts ) {
		$atts = shortcode_atts( array( 'id' => '' ), (array) $atts, self::LIST_SHORTCODE );

		$list = alumni_core_get_officer_list( $atts['id'] );

		ob_start();
		?>
		<div class="alumni-officers-listing">
			<?php if ( null === $list ) : ?>
				<p class="alumni-notice">
					<?php esc_html_e( 'この役員・理事一覧は見つかりませんでした。', 'alumni-core' ); ?>
				</p>
			<?php else : ?>
				<?php $officers = alumni_core_get_officers_for_list( $atts['id'] ); ?>
				<?php if ( empty( $officers ) ) : ?>
					<p class="alumni-notice">
						<?php esc_html_e( '現在、この一覧に役員・理事の情報は登録されていません。', 'alumni-core' ); ?>
					</p>
				<?php else : ?>
					<table class="alumni-officers-listing-table">
						<thead>
							<tr>
								<th><?php echo esc_html( $list['title_heading'] ? $list['title_heading'] : Officer_Lists::DEFAULT_TITLE_HEADING ); ?></th>
								<th><?php esc_html_e( '氏名', 'alumni-core' ); ?></th>
								<th><?php esc_html_e( '卒業期', 'alumni-core' ); ?></th>
								<th><?php esc_html_e( '委員会', 'alumni-core' ); ?></th>
								<th><?php esc_html_e( '備考', 'alumni-core' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $officers as $officer ) : ?>
								<?php $link_url = alumni_core_get_officer_link_url( $officer ); ?>
								<tr>
									<td><?php echo esc_html( $officer['title'] ); ?></td>
									<td>
										<?php if ( $link_url ) : ?>
											<a href="<?php echo esc_url( $link_url ); ?>"><?php echo esc_html( $officer['name'] ); ?></a>
										<?php else : ?>
											<?php echo esc_html( $officer['name'] ); ?>
										<?php endif; ?>
									</td>
									<td>
										<?php
										echo '' === $officer['term'] || null === $officer['term']
											? ''
											: esc_html( sprintf( __( '第%s期', 'alumni-core' ), $officer['term'] ) );
										?>
									</td>
									<td><?php echo esc_html( $officer['committee'] ); ?></td>
									<td><?php echo esc_html( $officer['remarks'] ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
