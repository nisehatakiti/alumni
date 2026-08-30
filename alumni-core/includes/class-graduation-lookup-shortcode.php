<?php
/**
 * 卒業期早見表を公開ページとして提供するショートコードと、
 * そのための固定ページの自動作成.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 管理画面（Graduation_Lookup_Page）だけでは一般の同窓会員がサイト上で
 * 卒業期早見表にたどり着けないため、[alumni_graduation_lookup]
 * ショートコードと、それを設定済みの固定ページを自動作成する。
 *
 * ページ作成は「既に同じスラッグのページがあれば新規作成しない」
 * 「作成したページIDをoptionに保存し、再度の有効化で重複作成しない」を
 * 徹底する — Activator::activate() から明示的に一度実行するのに加え、
 * 既存の maybe_flush_rewrite_rules() / Installer::maybe_upgrade() と同じ
 * 「admin_initで安全にべき等チェックする」パターンを踏襲し、有効化を
 * またいだ古いサイトでもページが用意される。
 */
class Graduation_Lookup_Shortcode {

	/**
	 * Option name storing the auto-created page's post ID.
	 */
	const OPTION_PAGE_ID = 'alumni_core_graduation_lookup_page_id';

	/**
	 * Page slug / shortcode tag.
	 */
	const PAGE_SLUG = 'graduation-lookup';
	const SHORTCODE = 'alumni_graduation_lookup';

	/**
	 * Query var names used by the shortcode's two lookup forms. Namespaced
	 * to avoid colliding with anything else on the page this shortcode is
	 * embedded in.
	 */
	const QUERY_VAR_BIRTHDATE = 'alumni_lookup_birthdate';
	const QUERY_VAR_TERM      = 'alumni_lookup_term';

	/**
	 * How many rows the 早見表 table shows per page when no range is
	 * requested via the query string.
	 */
	const DEFAULT_ROWS = 30;

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
	 * Creates the 卒業期早見表 固定ページ if it doesn't already exist and
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
				'post_title'   => __( '卒業期早見表', 'alumni-core' ),
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
	 * Renders the [alumni_graduation_lookup] shortcode: a 生年月日→卒業期
	 * 検索フォーム、卒業期→誕生日目安 検索フォーム、and the 早見表 itself
	 * — everything a visitor needs even when the active theme has no
	 * dedicated template for this page.
	 *
	 * @return string
	 */
	public static function render_shortcode() {
		$settings               = Settings::instance()->get_all();
		$first_graduation_year  = $settings['first_graduation_year'];
		$color_active           = $settings['color_feature_enabled'];
		$color_cycle            = $settings['color_cycle'];
		$colors                 = $settings['colors'];

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only lookup, nothing is written.
		$birthdate = isset( $_GET[ self::QUERY_VAR_BIRTHDATE ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::QUERY_VAR_BIRTHDATE ] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$term_query = isset( $_GET[ self::QUERY_VAR_TERM ] ) ? absint( $_GET[ self::QUERY_VAR_TERM ] ) : 0;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only pagination, nothing is written.
		$from_term = isset( $_GET['from_term'] ) ? max( 1, absint( $_GET['from_term'] ) ) : 1;
		$to_term   = $from_term + self::DEFAULT_ROWS - 1;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['to_term'] ) && absint( $_GET['to_term'] ) >= $from_term ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$to_term = absint( $_GET['to_term'] );
		}

		$current_url = home_url( add_query_arg( null, null ) );

		ob_start();
		?>
		<div class="alumni-graduation-lookup">
			<?php if ( '' === $first_graduation_year ) : ?>
				<p class="alumni-notice">
					<?php esc_html_e( '第1期卒業年が未設定のため、卒業期早見表を利用できません。管理者に基本設定の登録を依頼してください。', 'alumni-core' ); ?>
				</p>
			<?php else : ?>

				<section class="alumni-graduation-lookup-section">
					<h2><?php esc_html_e( '生年月日から卒業期を調べる', 'alumni-core' ); ?></h2>
					<form method="get" action="<?php echo esc_url( $current_url ); ?>" class="alumni-graduation-lookup-form">
						<label for="alumni-lookup-birthdate"><?php esc_html_e( '生年月日', 'alumni-core' ); ?></label>
						<input type="date" id="alumni-lookup-birthdate" name="<?php echo esc_attr( self::QUERY_VAR_BIRTHDATE ); ?>" value="<?php echo esc_attr( $birthdate ); ?>" />
						<button type="submit"><?php esc_html_e( '調べる', 'alumni-core' ); ?></button>
					</form>
					<?php if ( '' !== $birthdate ) : ?>
						<?php
						$year = Term_Calculator::birthdate_to_graduation_year( $birthdate );
						$term = ( null === $year ) ? null : Term_Calculator::year_to_term( $year, $first_graduation_year );
						?>
						<?php if ( null === $year ) : ?>
							<p class="alumni-lookup-result"><?php esc_html_e( '生年月日を正しく入力してください。', 'alumni-core' ); ?></p>
						<?php else : ?>
							<p class="alumni-lookup-result">
								<?php
								if ( null !== $term ) {
									printf(
										/* translators: 1: graduation year, 2: graduation term (期) */
										esc_html__( '標準的な進級・卒業を前提とした推定では、卒業年は%1$d年（第%2$d期）です。', 'alumni-core' ),
										(int) $year,
										(int) $term
									);
								} else {
									printf(
										/* translators: %d: graduation year */
										esc_html__( '標準的な進級・卒業を前提とした推定では、卒業年は%d年です。', 'alumni-core' ),
										(int) $year
									);
								}
								?>
							</p>
							<p class="alumni-lookup-caveat"><?php esc_html_e( 'これは標準的な進級・卒業を前提とした推定であり、留年・浪人・転校・編入等は考慮していません。実際の卒業期を保証するものではありません。', 'alumni-core' ); ?></p>
						<?php endif; ?>
					<?php endif; ?>
				</section>

				<section class="alumni-graduation-lookup-section">
					<h2><?php esc_html_e( '卒業期から生年月日の目安を確認する', 'alumni-core' ); ?></h2>
					<form method="get" action="<?php echo esc_url( $current_url ); ?>" class="alumni-graduation-lookup-form">
						<label for="alumni-lookup-term"><?php esc_html_e( '卒業期（例：12）', 'alumni-core' ); ?></label>
						<input type="number" inputmode="numeric" min="1" id="alumni-lookup-term" name="<?php echo esc_attr( self::QUERY_VAR_TERM ); ?>" value="<?php echo $term_query ? esc_attr( $term_query ) : ''; ?>" />
						<button type="submit"><?php esc_html_e( '調べる', 'alumni-core' ); ?></button>
					</form>
					<?php if ( $term_query ) : ?>
						<?php
						$range = Term_Calculator::term_to_birth_range( $term_query, $first_graduation_year );
						$year  = Term_Calculator::term_to_year( $term_query, $first_graduation_year );
						?>
						<?php if ( null === $range || null === $year ) : ?>
							<p class="alumni-lookup-result"><?php esc_html_e( '卒業期を正しく入力してください。', 'alumni-core' ); ?></p>
						<?php else : ?>
							<p class="alumni-lookup-result">
								<?php
								printf(
									/* translators: 1: graduation term (期), 2: graduation year, 3: birth range start, 4: birth range end */
									esc_html__( '第%1$d期（%2$d年卒業）の誕生日の目安は %3$s 〜 %4$s です。', 'alumni-core' ),
									(int) $term_query,
									(int) $year,
									esc_html( $range['start'] ),
									esc_html( $range['end'] )
								);
								?>
							</p>
						<?php endif; ?>
					<?php endif; ?>
				</section>

				<section class="alumni-graduation-lookup-section">
					<h2><?php esc_html_e( '卒業期早見表を見る', 'alumni-core' ); ?></h2>
					<?php $rows = Term_Calculator::build_lookup_table( $first_graduation_year, $from_term, $to_term ); ?>
					<?php if ( ! empty( $rows ) ) : ?>
						<table class="alumni-graduation-lookup-table">
							<thead>
								<tr>
									<th><?php esc_html_e( '期', 'alumni-core' ); ?></th>
									<th><?php esc_html_e( '卒業年', 'alumni-core' ); ?></th>
									<th><?php esc_html_e( '誕生日範囲', 'alumni-core' ); ?></th>
									<?php if ( $color_active ) : ?>
										<th><?php esc_html_e( '卒業期カラー', 'alumni-core' ); ?></th>
									<?php endif; ?>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $rows as $row ) : ?>
									<?php $color = $color_active ? Term_Calculator::term_to_color( $row['term'], $color_cycle, $colors ) : null; ?>
									<tr>
										<td>
											<?php
											/* translators: %d: graduation term (期) */
											echo esc_html( sprintf( __( '第%d期', 'alumni-core' ), $row['term'] ) );
											?>
										</td>
										<td><?php echo esc_html( $row['year'] ); ?></td>
										<td>
											<?php if ( $row['birth_range'] ) : ?>
												<?php echo esc_html( $row['birth_range']['start'] . ' 〜 ' . $row['birth_range']['end'] ); ?>
											<?php endif; ?>
										</td>
										<?php if ( $color_active ) : ?>
											<td>
												<?php if ( $color ) : ?>
													<span class="alumni-lookup-color-swatch" style="background-color: <?php echo esc_attr( $color ); ?>;"></span>
												<?php endif; ?>
											</td>
										<?php endif; ?>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
						<p class="alumni-graduation-lookup-pagination">
							<a href="<?php echo esc_url( add_query_arg( 'from_term', max( 1, $from_term - self::DEFAULT_ROWS ), $current_url ) ); ?>">
								<?php esc_html_e( '前へ', 'alumni-core' ); ?>
							</a>
							<a href="<?php echo esc_url( add_query_arg( 'from_term', $to_term + 1, $current_url ) ); ?>">
								<?php esc_html_e( '次へ', 'alumni-core' ); ?>
							</a>
						</p>
					<?php endif; ?>
				</section>

			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
