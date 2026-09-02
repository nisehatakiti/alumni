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

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only lookup, nothing is written.
		$search_date   = isset( $_GET['birthdate'] ) ? sanitize_text_field( wp_unslash( $_GET['birthdate'] ) ) : '';
		$search_result = null;

		if ( '' !== $search_date ) {
			$search_year = Term_Calculator::birthdate_to_graduation_year( $search_date );
			$search_term = ( '' === $first_graduation_year ) ? null : Term_Calculator::birthdate_to_term( $search_date, $first_graduation_year );

			$search_result = array(
				'year'        => $search_year,
				'term'        => $search_term,
				'birth_range' => null === $search_year ? null : Term_Calculator::graduation_year_to_birth_range( $search_year ),
			);
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

			<h2><?php esc_html_e( '生年月日から調べる', 'alumni-core' ); ?></h2>
			<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::SLUG ); ?>" />
				<p>
					<label for="alumni-lookup-birthdate"><?php esc_html_e( '生年月日', 'alumni-core' ); ?></label>
					<input type="date" id="alumni-lookup-birthdate" name="birthdate" value="<?php echo esc_attr( $search_date ); ?>" />
					<?php submit_button( __( '調べる', 'alumni-core' ), 'secondary', '', false ); ?>
				</p>
			</form>

			<?php if ( null !== $search_result ) : ?>
				<div class="notice notice-info alumni-lookup-search-result">
					<?php if ( null === $search_result['year'] ) : ?>
						<p><?php esc_html_e( '生年月日を正しく入力してください（YYYY-MM-DD）。', 'alumni-core' ); ?></p>
					<?php else : ?>
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
						<?php if ( null !== $search_result['term'] ) : ?>
							<p>
								<?php
								printf(
									/* translators: %d: graduation term (期) */
									esc_html__( '対応する卒業期は 第%d期 です。', 'alumni-core' ),
									(int) $search_result['term']
								);
								?>
							</p>
						<?php else : ?>
							<p><?php esc_html_e( '第1期卒業年が未設定のため、卒業期は算出できません。', 'alumni-core' ); ?></p>
						<?php endif; ?>
						<p class="description"><?php esc_html_e( 'これは標準的な進級・卒業を前提とした推定です。留年・浪人・転校・編入などは考慮していません。実際にその方がその期を卒業したことを保証するものではありません。', 'alumni-core' ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $rows ) ) : ?>
				<h2><?php esc_html_e( '早見表', 'alumni-core' ); ?></h2>
				<table class="wp-list-table widefat fixed striped">
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
											<?php echo esc_html( $color ); ?>
										<?php endif; ?>
									</td>
								<?php endif; ?>
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
