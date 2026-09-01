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
	 * Query var names used by the shortcode's 生年月日 lookup form.
	 * Namespaced to avoid colliding with anything else on the page this
	 * shortcode is embedded in. There is deliberately no 卒業期→生年月日
	 * reverse-lookup form (it was removed — the only supported direction
	 * is 生年月日→卒業期).
	 */
	const QUERY_VAR_BIRTH_YEAR  = 'alumni_lookup_birth_year';
	const QUERY_VAR_BIRTH_MONTH = 'alumni_lookup_birth_month';
	const QUERY_VAR_BIRTH_DAY   = 'alumni_lookup_birth_day';

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
	 * The public URL of the 卒業期早見表 page, for Theme navigation/links.
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
	 * Renders the [alumni_graduation_lookup] shortcode: a 生年月日→卒業期
	 * 検索フォーム followed by the 早見表 itself — everything a visitor
	 * needs even when the active theme has no dedicated template for this
	 * page. The 卒業期→生年月日 reverse-lookup form that used to appear
	 * here has been removed (生年月日→卒業期 is the only supported
	 * direction), and the 早見表 no longer has a separate 色 column — each
	 * row's own background color already carries that information.
	 *
	 * @return string
	 */
	public static function render_shortcode() {
		$settings              = Settings::instance()->get_all();
		$first_graduation_year = $settings['first_graduation_year'];
		$color_active          = $settings['color_feature_enabled'];
		$color_cycle           = $settings['color_cycle'];
		$colors                = $settings['colors'];

		// 生年月日は「年」「月」「日」の3つの数値入力に分けて受け取る
		// （<input type="date">は環境によってカレンダーUIの操作性が悪く、
		// スマートフォンで数字キーボードが出ないことがあったため）。
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only lookup, nothing is written.
		$birth_year_input  = isset( $_GET[ self::QUERY_VAR_BIRTH_YEAR ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::QUERY_VAR_BIRTH_YEAR ] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$birth_month_input = isset( $_GET[ self::QUERY_VAR_BIRTH_MONTH ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::QUERY_VAR_BIRTH_MONTH ] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$birth_day_input   = isset( $_GET[ self::QUERY_VAR_BIRTH_DAY ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::QUERY_VAR_BIRTH_DAY ] ) ) : '';

		$search_submitted = ( '' !== $birth_year_input || '' !== $birth_month_input || '' !== $birth_day_input );

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
					<h2><?php esc_html_e( '卒業期を調べる', 'alumni-core' ); ?></h2>
					<form method="get" action="<?php echo esc_url( $current_url ); ?>" class="alumni-graduation-lookup-form alumni-graduation-lookup-date-form">
						<label for="alumni-lookup-birth-year"><?php esc_html_e( '生年月日', 'alumni-core' ); ?></label>
						<span class="alumni-graduation-lookup-date-fields">
							<input type="number" inputmode="numeric" id="alumni-lookup-birth-year" name="<?php echo esc_attr( self::QUERY_VAR_BIRTH_YEAR ); ?>" min="1000" max="9999" placeholder="<?php echo esc_attr__( '年', 'alumni-core' ); ?>" value="<?php echo esc_attr( $birth_year_input ); ?>" /><?php esc_html_e( '年', 'alumni-core' ); ?>
							<input type="number" inputmode="numeric" id="alumni-lookup-birth-month" name="<?php echo esc_attr( self::QUERY_VAR_BIRTH_MONTH ); ?>" min="1" max="12" placeholder="<?php echo esc_attr__( '月', 'alumni-core' ); ?>" value="<?php echo esc_attr( $birth_month_input ); ?>" /><?php esc_html_e( '月', 'alumni-core' ); ?>
							<input type="number" inputmode="numeric" id="alumni-lookup-birth-day" name="<?php echo esc_attr( self::QUERY_VAR_BIRTH_DAY ); ?>" min="1" max="31" placeholder="<?php echo esc_attr__( '日', 'alumni-core' ); ?>" value="<?php echo esc_attr( $birth_day_input ); ?>" /><?php esc_html_e( '日', 'alumni-core' ); ?>
						</span>
						<button type="submit"><?php esc_html_e( '卒業期を調べる', 'alumni-core' ); ?></button>
					</form>
					<?php if ( $search_submitted ) : ?>
						<?php $birthdate = Term_Calculator::validate_date_parts( $birth_year_input, $birth_month_input, $birth_day_input ); ?>
						<?php if ( null === $birthdate ) : ?>
							<p class="alumni-lookup-result"><?php esc_html_e( '生年月日を正しく入力してください（実在する年月日ではありません）。', 'alumni-core' ); ?></p>
						<?php else : ?>
							<?php
							$year = Term_Calculator::birthdate_to_graduation_year( $birthdate );
							$term = ( null === $year ) ? null : Term_Calculator::year_to_term( $year, $first_graduation_year );
							?>
							<?php if ( null !== $term ) : ?>
								<p class="alumni-lookup-result-term">
									<?php
									printf(
										/* translators: %d: graduation term (期) */
										esc_html__( 'あなたの卒業期：第%d期', 'alumni-core' ),
										(int) $term
									);
									?>
								</p>
							<?php endif; ?>
							<p class="alumni-lookup-result">
								<?php
								printf(
									/* translators: %d: graduation year */
									esc_html__( '標準的な進級・卒業を前提とした推定では、卒業年は%d年です。', 'alumni-core' ),
									(int) $year
								);
								?>
							</p>
							<p class="alumni-lookup-caveat"><?php esc_html_e( 'これは標準的な進級・卒業を前提とした推定であり、留年・浪人・転校・編入等は考慮していません。実際の卒業期を保証するものではありません。', 'alumni-core' ); ?></p>
						<?php endif; ?>
					<?php endif; ?>
				</section>

				<section class="alumni-graduation-lookup-section">
					<h2><?php esc_html_e( '卒業期早見表', 'alumni-core' ); ?></h2>
					<?php $rows = Term_Calculator::build_lookup_table( $first_graduation_year, $from_term, $to_term ); ?>
					<?php if ( ! empty( $rows ) ) : ?>
						<table class="alumni-graduation-lookup-table">
							<thead>
								<tr>
									<th><?php esc_html_e( '卒業期', 'alumni-core' ); ?></th>
									<th><?php esc_html_e( '卒業年度', 'alumni-core' ); ?></th>
									<th><?php esc_html_e( '生年月日', 'alumni-core' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $rows as $row ) : ?>
									<?php
									$color = $color_active ? Term_Calculator::term_to_color( $row['term'], $color_cycle, $colors ) : null;

									// 卒業期カラーは行全体の背景色として適用する（ヘッダー行には
									// 適用しない）。色そのものを示す専用の列は置かない — 行の
									// 背景色そのものが卒業期カラーを表す。色そのものは管理者が
									// 自由に設定する動的な値なのでインラインstyleで指定するしか
									// ないが、読みやすさを保つための白文字／黒文字の切り替えは
									// Theme側のCSSクラスに委ねる（main.cssの
									// .alumni-lookup-row-text-light/-dark）— Coreは明度判定の結果
									// （どちらのクラスを使うか）だけを渡す。
									$alumni_row_attrs = '';
									if ( $color ) {
										$text_class       = Term_Calculator::is_dark_color( $color ) ? 'alumni-lookup-row-text-light' : 'alumni-lookup-row-text-dark';
										$alumni_row_attrs = sprintf( ' class="%s" style="background-color: %s;"', esc_attr( $text_class ), esc_attr( $color ) );
									}
									?>
									<tr<?php echo $alumni_row_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built entirely from esc_attr() above. ?>>
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
