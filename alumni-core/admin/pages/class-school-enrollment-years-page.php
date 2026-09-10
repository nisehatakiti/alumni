<?php
/**
 * 同窓会 > 在校年度一覧 screen.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Admin\Pages;

use AlumniCore\Admin\Admin;
use AlumniCore\Includes\School_Enrollment_Years_Shortcode;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class School_Enrollment_Years_Page {

	const SLUG = 'alumni-core-school-enrollment-years';

	public function render() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			wp_die( esc_html__( 'このページを表示する権限がありません。', 'alumni-core' ) );
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( '在校年度一覧', 'alumni-core' ); ?></h1>
			<p class="description">
				<?php esc_html_e( '基本設定の「学校創立年」と「第1期卒業年」から、高校3年間の在校年度を自動生成しています。追加設定は不要です。', 'alumni-core' ); ?>
			</p>
			<p>
				<a class="button button-secondary" href="<?php echo esc_url( School_Enrollment_Years_Shortcode::get_url() ); ?>" target="_blank" rel="noopener">
					<?php esc_html_e( '公開ページを表示', 'alumni-core' ); ?>
				</a>
			</p>
			<?php echo School_Enrollment_Years_Shortcode::render_table(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php
	}
}
