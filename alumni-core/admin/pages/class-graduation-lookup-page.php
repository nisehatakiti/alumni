<?php
/**
 * 同窓会 > 卒業期早見表 screen.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Admin\Pages;

use AlumniCore\Admin\Admin;
use AlumniCore\Includes\Settings;
use AlumniCore\Includes\Term_Calculator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read-only reference screen: a 卒業期／卒業年／誕生日範囲 lookup table
 * built from 学校創立年・第1期卒業年・卒業期カラー (already configured in
 * 基本設定), plus a small 生年月日 → 卒業期 search form.
 *
 * Both the table and the search are GET-based (no data is written), so
 * this screen only needs the standard capability check — no nonce, since
 * nothing here mutates state.
 */
class Graduation_Lookup_Page {

	/**
	 * Submenu slug.
	 */
	const SLUG = 'alumni-core-graduation-lookup';

	/**
	 * How many terms the table shows per page when the visitor hasn't
	 * requested a specific range.
	 */
	const DEFAULT_ROWS = 30;

	/**
	 * Renders the screen.
	 */
	public function render() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			return;
		}

		$first_graduation_year = Settings::instance()->get( 'first_graduation_year' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only lookup, nothing is written.
		$from_term = isset( $_GET['from_term'] ) ? max( 1, absint( $_GET['from_term'] ) ) : 1;
		$to_term   = $from_term + self::DEFAULT_ROWS - 1;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['to_term'] ) && absint( $_GET['to_term'] ) >= $from_term ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$to_term = absint( $_GET['to_term'] );
		}

		$rows = ( '' === $first_graduation_year )
			? array()
			: Term_Calculator::build_lookup_table( $first_graduation_year, $from_term, $to_term );

		$settings     = Settings::instance()->get_all();
		$color_cycle  = $settings['color_cycle'];
		$colors       = $settings['colors'];
		$color_active = $settings['color_feature_enabled'];

		// 生年月日は「年」「月」「日」の3つの数値入力に分けて受け取る
		// （<input type="date">は環境によってカレンダーUIの操作性が悪く、
		// スマートフォンで数字キーボードが出ないことがあったため）。
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only lookup, nothing is written.
		$search_year_input  = isset( $_GET['birth_year'] ) ? sanitize_text_field( wp_unslash( $_GET['birth_year'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search_month_input = isset( $_GET['birth_month'] ) ? sanitize_text_field( wp_unslash( $_GET['birth_month'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search_day_input   = isset( $_GET['birth_day'] ) ? sanitize_text_field( wp_unslash( $_GET['birth_day'] ) ) : '';

		$search_submitted = ( '' !== $search_year_input || '' !== $search_month_input || '' !== $search_day_input );
		$search_result    = null;

		if ( $search_submitted ) {
			$search_date = Term_Calculator::validate_date_parts( $search_year_input, $search_month_input, $search_day_input );

			if ( null === $search_date ) {
				$search_result = array( 'year' => null, 'term' => null, 'birth_range' => null );
			} else {
				$search_year = Term_Calculator::birthdate_to_graduation_year( $search_date );
				$search_term = ( '' === $first_graduation_year ) ? null : Term_Calculator::birthdate_to_term( $search_date, $first_graduation_year );

				$search_result = array(
					'year'        => $search_year,
					'term'        => $search_term,
					'birth_range' => null === $search_year ? null : Term_Calculator::graduation_year_to_birth_range( $search_year ),
				);
			}
		}
		?>
		<div class="wrap alumni-core-graduation-lookup">
			<h1><?php esc_html_e( '卒業期早見表', 'alumni-core' ); ?></h1>

			<?php if ( '' === $first_graduation_year ) : ?>
				<div class="notice notice-warning">
					<p>
						<?php
						printf(
							/* translators: %s: link to the 基本設定 screen */
							esc_html__( '第1期卒業年が未設定のため、早見表を表示できません。%s から設定してください。', 'alumni-core' ),
							'<a href="' . esc_url( admin_url( 'admin.php?page=' . Settings_Page::SLUG ) ) . '">' . esc_html__( '基本設定', 'alumni-core' ) . '</a>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built entirely from esc_url()/esc_html() above.
						);
						?>
					</p>
				</div>
			<?php endif; ?>

			<h2><?php esc_html_e( '卒業期を調べる', 'alumni-core' ); ?></h2>
			<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="alumni-lookup-date-form">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::SLUG ); ?>" />
				<p>
					<label for="alumni-lookup-birth-year"><?php esc_html_e( '生年月日', 'alumni-core' ); ?></label><br />
					<input type="number" inputmode="numeric" id="alumni-lookup-birth-year" name="birth_year" class="small-text" min="1000" max="9999" placeholder="<?php echo esc_attr__( '年', 'alumni-core' ); ?>" value="<?php echo esc_attr( $search_year_input ); ?>" />
					<?php esc_html_e( '年', 'alumni-core' ); ?>
					<input type="number" inputmode="numeric" id="alumni-lookup-birth-month" name="birth_month" class="small-text" min="1" max="12" placeholder="<?php echo esc_attr__( '月', 'alumni-core' ); ?>" value="<?php echo esc_attr( $search_month_input ); ?>" />
					<?php esc_html_e( '月', 'alumni-core' ); ?>
					<input type="number" inputmode="numeric" id="alumni-lookup-birth-day" name="birth_day" class="small-text" min="1" max="31" placeholder="<?php echo esc_attr__( '日', 'alumni-core' ); ?>" value="<?php echo esc_attr( $search_day_input ); ?>" />
					<?php esc_html_e( '日', 'alumni-core' ); ?>
					<?php submit_button( __( '卒業期を調べる', 'alumni-core' ), 'secondary', '', false ); ?>
				</p>
			</form>

			<?php if ( null !== $search_result ) : ?>
				<div class="notice notice-info alumni-lookup-search-result">
					<?php if ( null === $search_result['year'] ) : ?>
						<p><?php esc_html_e( '生年月日を正しく入力してください（実在する年月日ではありません）。', 'alumni-core' ); ?></p>
					<?php else : ?>
						<?php if ( null !== $search_result['term'] ) : ?>
							<p class="alumni-lookup-result-term">
								<?php
								printf(
									/* translators: %d: graduation term (期) */
									esc_html__( 'あなたの卒業期：第%d期', 'alumni-core' ),
									(int) $search_result['term']
								);
								?>
							</p>
						<?php else : ?>
							<p><?php esc_html_e( '第1期卒業年が未設定のため、卒業期は算出できません。', 'alumni-core' ); ?></p>
						<?php endif; ?>
						<p>
							<?php
							printf(
								/* translators: 1: graduation year, 2: birth range start, 3: birth range end */
								esc_html__( '標準的な進級・卒業を前提とした推定では、卒業年は %1$d年（誕生日範囲：%2$s 〜 %3$s）です。', 'alumni-core' ),
								(int) $search_result['year'],
								esc_html( $search_result['birth_range']['start'] ),
								esc_html( $search_result['birth_range']['end'] )
							);
							?>
						</p>
						<p class="description"><?php esc_html_e( 'これは標準的な進級・卒業を前提とした推定です。留年・浪人・転校・編入などは考慮していません。実際にその方がその期を卒業したことを保証するものではありません。', 'alumni-core' ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $rows ) ) : ?>
				<h2><?php esc_html_e( '卒業期早見表', 'alumni-core' ); ?></h2>
				<table class="wp-list-table widefat fixed striped">
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
							// 背景色そのものが卒業期カラーを表す。色は管理者が自由に
							// 設定できる動的な値のため、実際の色そのものはインライン
							// styleで指定するしかないが、読みやすさを保つための
							// 白文字／黒文字の切り替えはadmin.cssのクラスに委ねる
							// （Term_Calculator::is_dark_color()が色の明度からどちらの
							// クラスを使うかだけを判定する）。
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

				<p>
					<a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => self::SLUG, 'from_term' => max( 1, $from_term - self::DEFAULT_ROWS ) ), admin_url( 'admin.php' ) ) ); ?>">
						<?php esc_html_e( '前へ', 'alumni-core' ); ?>
					</a>
					<a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => self::SLUG, 'from_term' => $to_term + 1 ), admin_url( 'admin.php' ) ) ); ?>">
						<?php esc_html_e( '次へ', 'alumni-core' ); ?>
					</a>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}
}
