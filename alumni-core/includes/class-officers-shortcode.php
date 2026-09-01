<?php
/**
 * 役員・理事紹介を公開ページとして提供するショートコードと、
 * そのための固定ページの自動作成.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 管理画面（Officers_Page）だけでは一般の同窓会員がサイト上で役員・理事の
 * 一覧にたどり着けないため、[alumni_officers] ショートコードと、それを
 * 設定済みの固定ページを自動作成する。Graduation_Lookup_Shortcode と全く
 * 同じ「既に同じスラッグのページがあれば新規作成しない」
 * 「作成したページIDをoptionに保存し、再度の有効化で重複作成しない」
 * パターンを踏襲する。
 */
class Officers_Shortcode {

	/**
	 * Option name storing the auto-created page's post ID.
	 */
	const OPTION_PAGE_ID = 'alumni_core_officers_page_id';

	/**
	 * Page slug / shortcode tag.
	 */
	const PAGE_SLUG = 'officers';
	const SHORTCODE = 'alumni_officers';

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
	 * Creates the 役員・理事紹介 固定ページ if it doesn't already exist and
	 * isn't already tracked. Idempotent: short-circuits after a single
	 * get_option() call once the page is known.
	 */
	public static function maybe_create_page() {
		$page_id = (int) get_option( self::OPTION_PAGE_ID, 0 );

		if ( $page_id && 'page' === get_post_type( $page_id ) ) {
			return; // Already created and still exists.
		}

		// A page at this slug may already exist — created by hand, left
		// over from a previous activation whose option got cleared, or
		// (per this plugin's own uninstall policy) surviving a prior
		// uninstall since Core never deletes content it didn't explicitly
		// opt into deleting. Adopt it instead of creating a duplicate.
		$existing = get_page_by_path( self::PAGE_SLUG, OBJECT, 'page' );

		if ( $existing ) {
			update_option( self::OPTION_PAGE_ID, $existing->ID );
			return;
		}

		$new_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => __( '役員・理事紹介', 'alumni-core' ),
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
	 * The public URL of the 役員・理事紹介 page, for Theme navigation/links.
	 * Falls back to the plain slug URL when the tracked page id is stale
	 * (e.g. this runs before maybe_create_page() has ever executed) —
	 * still correct once the page exists, and harmless if it doesn't yet.
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
	 * Renders the [alumni_officers] shortcode: the saved 役員・理事一覧を
	 * 保存順（表示順）のまま公開する。氏名は、リンク先の人物紹介・挨拶
	 * コンテンツが現在公開されている場合のみ<a>になり、それ以外は
	 * プレーンテキストのまま — alumni_core_get_officer_link_url() が
	 * その判定（存在するか、かつ現在公開されているか）を担う。
	 *
	 * @return string
	 */
	public static function render_shortcode() {
		$officers = alumni_core_get_officers();

		ob_start();
		?>
		<div class="alumni-officers-listing">
			<?php if ( empty( $officers ) ) : ?>
				<p class="alumni-notice">
					<?php esc_html_e( '現在、役員・理事の情報は登録されていません。', 'alumni-core' ); ?>
				</p>
			<?php else : ?>
				<table class="alumni-officers-listing-table">
					<thead>
						<tr>
							<th><?php esc_html_e( '卒業期', 'alumni-core' ); ?></th>
							<th><?php esc_html_e( '肩書', 'alumni-core' ); ?></th>
							<th><?php esc_html_e( '委員会', 'alumni-core' ); ?></th>
							<th><?php esc_html_e( '氏名', 'alumni-core' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $officers as $officer ) : ?>
							<?php $link_url = alumni_core_get_officer_link_url( $officer ); ?>
							<tr>
								<td>
									<?php
									echo '' === $officer['term'] || null === $officer['term']
										? ''
										: esc_html( sprintf( __( '第%s期', 'alumni-core' ), $officer['term'] ) );
									?>
								</td>
								<td><?php echo esc_html( $officer['title'] ); ?></td>
								<td><?php echo esc_html( $officer['committee'] ); ?></td>
								<td>
									<?php if ( $link_url ) : ?>
										<a href="<?php echo esc_url( $link_url ); ?>"><?php echo esc_html( $officer['name'] ); ?></a>
									<?php else : ?>
										<?php echo esc_html( $officer['name'] ); ?>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
