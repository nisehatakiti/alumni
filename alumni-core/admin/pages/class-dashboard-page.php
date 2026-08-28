<?php
/**
 * 同窓会 > ダッシュボード screen.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Admin\Pages;

use AlumniCore\Includes\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simple landing page for the 同窓会 menu. Currently shows a summary of the
 * basic settings; future modules can hook alumni_core_dashboard_widgets to
 * add their own summary boxes here.
 */
class Dashboard_Page {

	/**
	 * Renders the screen.
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = Settings::instance()->get_all();
		?>
		<div class="wrap alumni-core-dashboard">
			<h1><?php esc_html_e( '同窓会 ダッシュボード', 'alumni-core' ); ?></h1>

			<p>
				<?php esc_html_e( 'Alumni Core が有効になりました。まずは「基本設定」で同窓会の基本情報を登録してください。', 'alumni-core' ); ?>
			</p>

			<table class="widefat striped alumni-core-summary-table">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( '同窓会名称', 'alumni-core' ); ?></th>
						<td><?php echo esc_html( $settings['association_name'] ? $settings['association_name'] : __( '未設定', 'alumni-core' ) ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( '学校名称', 'alumni-core' ); ?></th>
						<td><?php echo esc_html( $settings['school_name'] ? $settings['school_name'] : __( '未設定', 'alumni-core' ) ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( '第1期卒業年', 'alumni-core' ); ?></th>
						<td><?php echo esc_html( $settings['first_graduation_year'] ? $settings['first_graduation_year'] : __( '未設定', 'alumni-core' ) ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( '卒業期カラー機能', 'alumni-core' ); ?></th>
						<td><?php echo esc_html( $settings['color_feature_enabled'] ? __( 'ON', 'alumni-core' ) : __( 'OFF', 'alumni-core' ) ); ?></td>
					</tr>
				</tbody>
			</table>

			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . Settings_Page::SLUG ) ); ?>" class="button button-primary">
					<?php esc_html_e( '基本設定を編集', 'alumni-core' ); ?>
				</a>
			</p>
		</div>
		<?php
	}
}
