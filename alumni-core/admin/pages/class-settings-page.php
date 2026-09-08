<?php
/**
 * 同窓会 > 基本設定 screen.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Admin\Pages;

use AlumniCore\Admin\Admin;
use AlumniCore\Includes\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders and saves the association's basic settings, including the
 * variable-length 卒業期カラー cycle.
 */
class Settings_Page {

	/**
	 * Submenu slug.
	 */
	const SLUG = 'alumni-core-settings';

	/**
	 * Nonce action/name shared by the form and the save handler.
	 */
	const NONCE_ACTION = 'alumni_core_save_settings';

	/**
	 * Renders the screen.
	 */
	public function render() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			return;
		}

		$settings = Settings::instance()->get_all();
		?>
		<div class="wrap alumni-core-settings">
			<h1><?php esc_html_e( '同窓会 基本設定', 'alumni-core' ); ?></h1>

			<?php if ( isset( $_GET['updated'] ) && 'true' === $_GET['updated'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only status flag. ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( '設定を保存しました。', 'alumni-core' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="alumni-core-settings-form">
				<input type="hidden" name="action" value="alumni_core_save_settings" />
				<?php wp_nonce_field( self::NONCE_ACTION ); ?>

				<h2><?php esc_html_e( '基本情報', 'alumni-core' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="alumni_core_association_name"><?php esc_html_e( '同窓会名称', 'alumni-core' ); ?></label>
						</th>
						<td>
							<input type="text" id="alumni_core_association_name" name="association_name" class="regular-text"
								value="<?php echo esc_attr( $settings['association_name'] ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="alumni_core_school_name"><?php esc_html_e( '学校名称', 'alumni-core' ); ?></label>
						</th>
						<td>
							<input type="text" id="alumni_core_school_name" name="school_name" class="regular-text"
								value="<?php echo esc_attr( $settings['school_name'] ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="alumni_core_school_founded_year"><?php esc_html_e( '学校創立年（任意）', 'alumni-core' ); ?></label>
						</th>
						<td>
							<input type="number" inputmode="numeric" id="alumni_core_school_founded_year" name="school_founded_year" class="small-text"
								min="<?php echo esc_attr( Settings::MIN_YEAR ); ?>" max="<?php echo esc_attr( Settings::max_year() ); ?>"
								value="<?php echo esc_attr( $settings['school_founded_year'] ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="alumni_core_first_graduation_year"><?php esc_html_e( '第1期卒業年', 'alumni-core' ); ?></label>
						</th>
						<td>
							<input type="number" inputmode="numeric" id="alumni_core_first_graduation_year" name="first_graduation_year" class="small-text"
								min="<?php echo esc_attr( Settings::MIN_YEAR ); ?>" max="<?php echo esc_attr( Settings::max_year() ); ?>"
								value="<?php echo esc_attr( self::first_graduation_year_display_value( $settings ) ); ?>" />
							<p class="description"><?php esc_html_e( '例：1950 と設定すると、1950年卒業が第1期になります。', 'alumni-core' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="alumni_core_association_founded_year"><?php esc_html_e( '同窓会組織創立年（任意）', 'alumni-core' ); ?></label>
						</th>
						<td>
							<input type="number" inputmode="numeric" id="alumni_core_association_founded_year" name="association_founded_year" class="small-text"
								min="<?php echo esc_attr( Settings::MIN_YEAR ); ?>" max="<?php echo esc_attr( Settings::max_year() ); ?>"
								value="<?php echo esc_attr( $settings['association_founded_year'] ); ?>" />
							<p class="description"><?php esc_html_e( '同窓会という組織そのものが発足した年です。学校創立年・第1期卒業年とは別の項目で、卒業期計算には使用されません（同窓会の沿革・周年記念などに利用します）。', 'alumni-core' ); ?></p>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( '学校ブランディング', 'alumni-core' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( '学校の校章', 'alumni-core' ); ?></th>
						<td>
							<?php
							$this->render_media_picker(
								'school_emblem_id',
								$settings['school_emblem_id'],
								__( '校章を選択', 'alumni-core' ),
								__( '校章を選択', 'alumni-core' )
							);
							?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( '同窓会独自ロゴ', 'alumni-core' ); ?></th>
						<td>
							<?php
							$this->render_media_picker(
								'alumni_logo_id',
								$settings['alumni_logo_id'],
								__( '同窓会ロゴを選択', 'alumni-core' ),
								__( 'ロゴを選択', 'alumni-core' )
							);
							?>
							<p class="description"><?php esc_html_e( '学校の校章とは別に、同窓会独自のロゴを設定できます。どちらも任意です。', 'alumni-core' ); ?></p>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( '卒業期カラー', 'alumni-core' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( '卒業期カラー機能', 'alumni-core' ); ?></th>
						<td>
							<label>
								<input type="checkbox" id="alumni_core_color_feature_enabled" name="color_feature_enabled" value="1"
									<?php checked( $settings['color_feature_enabled'] ); ?> />
								<?php esc_html_e( 'ON にする', 'alumni-core' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="alumni_core_color_cycle"><?php esc_html_e( 'カラー周期数', 'alumni-core' ); ?></label>
						</th>
						<td>
							<input type="number" min="1" max="<?php echo esc_attr( Settings::MAX_COLOR_CYCLE ); ?>" id="alumni_core_color_cycle" name="color_cycle" class="small-text"
								value="<?php echo esc_attr( $settings['color_cycle'] ); ?>" />
							<p class="description"><?php esc_html_e( '例：3 に設定すると、カラー1〜3が第1期〜第3期に割り当てられ、第4期は再びカラー1になります。', 'alumni-core' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'カラー', 'alumni-core' ); ?></th>
						<td>
							<div id="alumni-core-colors" class="alumni-core-colors">
								<?php foreach ( $settings['colors'] as $index => $color ) : ?>
									<?php $this->render_color_row( (int) $index, $color ); ?>
								<?php endforeach; ?>
							</div>
							<template id="alumni-core-color-row-template">
								<?php $this->render_color_row( '__INDEX__', '#cccccc' ); ?>
							</template>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'サイトナビゲーション', 'alumni-core' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'メニューの配置', 'alumni-core' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><?php esc_html_e( 'メニューの配置', 'alumni-core' ); ?></legend>
								<label>
									<input type="radio" name="nav_layout" value="<?php echo esc_attr( Settings::NAV_LAYOUT_TOP ); ?>"
										<?php checked( $settings['nav_layout'], Settings::NAV_LAYOUT_TOP ); ?> />
									<?php esc_html_e( '上部メニュー', 'alumni-core' ); ?>
								</label>
								<br />
								<label>
									<input type="radio" name="nav_layout" value="<?php echo esc_attr( Settings::NAV_LAYOUT_SIDE ); ?>"
										<?php checked( $settings['nav_layout'], Settings::NAV_LAYOUT_SIDE ); ?> />
									<?php esc_html_e( '左サイドメニュー', 'alumni-core' ); ?>
								</label>
							</fieldset>
							<p class="description"><?php esc_html_e( 'サイト全体のナビゲーションを、画面上部の横並びメニューにするか、画面左の縦並びメニューにするかを選べます。将来メニュー項目が増えても、この配置設定はそのまま使えます。', 'alumni-core' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button( __( '設定を保存', 'alumni-core' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * The value to show in the 第1期卒業年 input. When 第1期卒業年 has never
	 * been explicitly saved (i.e. is still '', the "未設定" sentinel — see
	 * Settings::sanitize_year()), the input's initial value should track
	 * 学校創立年 instead of a hardcoded "1950", so it follows 学校創立年
	 * if that's changed before 第1期卒業年 is ever set.
	 *
	 * This only affects what's shown in the form; it never writes to the
	 * database, so an already-saved 第1期卒業年 (including one explicitly
	 * saved as empty) is never altered by this method.
	 *
	 * @param array $settings Current settings, as from Settings::get_all().
	 * @return int|string
	 */
	private static function first_graduation_year_display_value( array $settings ) {
		if ( '' !== $settings['first_graduation_year'] ) {
			return $settings['first_graduation_year'];
		}

		return $settings['school_founded_year'];
	}

	/**
	 * Renders one 「第N期カラー」 field row.
	 *
	 * @param int|string $index 1-based cycle position, or the '__INDEX__'
	 *                           placeholder used by the JS template.
	 * @param string     $color Hex color value.
	 */
	private function render_color_row( $index, $color ) {
		?>
		<div class="alumni-core-color-row" data-index="<?php echo esc_attr( $index ); ?>">
			<label>
				<?php
				/* translators: %s: cycle position, e.g. "1" */
				echo esc_html( sprintf( __( '第%s カラー', 'alumni-core' ), $index ) );
				?>
				<input type="color" name="colors[<?php echo esc_attr( $index ); ?>]" value="<?php echo esc_attr( $color ); ?>" />
			</label>
		</div>
		<?php
	}

	/**
	 * Renders a single-image WordPress media library picker (used for
	 * 校章 and 同窓会ロゴ): a preview, a hidden field holding the
	 * attachment ID, and 選択/削除 buttons. The actual picker UI is wired
	 * up by admin/assets/js/media-picker.js against the
	 * .alumni-media-picker markup below.
	 *
	 * @param string $field_name    Form field name, also the Settings key.
	 * @param int    $attachment_id Currently saved attachment ID, or 0.
	 * @param string $dialog_title  Title shown in the media library modal.
	 * @param string $button_text   Label for the 選択 button.
	 */
	private function render_media_picker( $field_name, $attachment_id, $dialog_title, $button_text ) {
		$attachment_id = (int) $attachment_id;
		?>
		<div class="alumni-media-picker" data-title="<?php echo esc_attr( $dialog_title ); ?>" data-button-text="<?php echo esc_attr( $button_text ); ?>">
			<div class="alumni-media-preview">
				<?php if ( $attachment_id ) : ?>
					<?php echo wp_get_attachment_image( $attachment_id, 'thumbnail' ); ?>
				<?php endif; ?>
			</div>
			<input type="hidden" name="<?php echo esc_attr( $field_name ); ?>" class="alumni-media-picker-input" value="<?php echo esc_attr( $attachment_id ); ?>" />
			<p>
				<button type="button" class="button alumni-media-picker-select"><?php echo esc_html( $button_text ); ?></button>
				<button type="button" class="button alumni-media-picker-clear"<?php echo $attachment_id ? '' : ' style="display:none;"'; ?>>
					<?php esc_html_e( '削除', 'alumni-core' ); ?>
				</button>
			</p>
		</div>
		<?php
	}

	/**
	 * Handles the settings form submission (admin_post_alumni_core_save_settings).
	 */
	public function handle_save() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			wp_die( esc_html__( 'この操作を行う権限がありません。', 'alumni-core' ) );
		}

		check_admin_referer( self::NONCE_ACTION );

		// Nonce-verified above; Settings::save() unslashes each field itself.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		Settings::instance()->save( $_POST );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => self::SLUG,
					'updated' => 'true',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
