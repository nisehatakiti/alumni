<?php
/**
 * 同窓会 > 学校写真 screen.
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
 * Manages 学校関連写真: a reorderable gallery of media library attachment
 * IDs, the display mode (固定表示／自動切替), and which photo shows when
 * 固定表示 is selected.
 *
 * Saves via Settings::update_fields() rather than Settings::save() (which
 * is 基本設定's own full-form save) — this page owns a different subset
 * of fields, and update_fields() only ever touches the keys it's given,
 * so the two screens' saves can never clobber each other.
 */
class School_Photos_Page {

	/**
	 * Submenu slug.
	 */
	const SLUG = 'alumni-core-school-photos';

	/**
	 * Nonce action/name shared by the form and the save handler.
	 */
	const NONCE_ACTION = 'alumni_core_save_school_photos';

	/**
	 * Renders the screen.
	 */
	public function render() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			return;
		}

		$settings = Settings::instance()->get_all();
		// Filtered to still-existing image attachments: if one was deleted
		// from the Media Library directly, it disappears from this screen
		// (and from the 表示画像 dropdown) rather than showing a broken
		// thumbnail — and saving the form afterwards naturally drops it
		// from the stored list too, since the hidden inputs only reflect
		// what's rendered here.
		$photo_ids    = Settings::filter_valid_image_attachments( $settings['school_photo_ids'] );
		$display_mode = $settings['school_photo_display_mode'];
		$featured_id  = (int) $settings['school_photo_featured_id'];
		?>
		<div class="wrap alumni-core-school-photos">
			<h1><?php esc_html_e( '学校写真', 'alumni-core' ); ?></h1>

			<?php if ( isset( $_GET['updated'] ) && 'true' === $_GET['updated'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only status flag. ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( '設定を保存しました。', 'alumni-core' ); ?></p>
				</div>
			<?php endif; ?>

			<p><?php esc_html_e( '校舎・校門・校庭など、学校に関連する写真を登録できます。トップページに大きく表示されます。', 'alumni-core' ); ?></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="alumni-core-school-photos-form">
				<input type="hidden" name="action" value="alumni_core_save_school_photos" />
				<?php wp_nonce_field( self::NONCE_ACTION ); ?>

				<h2><?php esc_html_e( '写真一覧', 'alumni-core' ); ?></h2>
				<ul id="alumni-photo-gallery-list" class="alumni-photo-gallery-list">
					<?php foreach ( $photo_ids as $id ) : ?>
						<?php $this->render_photo_item( $id ); ?>
					<?php endforeach; ?>
				</ul>
				<template id="alumni-photo-gallery-item-template">
					<?php $this->render_photo_item( 0 ); ?>
				</template>

				<p>
					<button
						type="button"
						id="alumni-photo-gallery-add"
						class="button button-secondary"
						data-title="<?php echo esc_attr__( '学校写真を追加', 'alumni-core' ); ?>"
						data-button-text="<?php echo esc_attr__( '追加', 'alumni-core' ); ?>"
					>
						<?php esc_html_e( '写真を追加', 'alumni-core' ); ?>
					</button>
				</p>

				<h2><?php esc_html_e( '表示方式', 'alumni-core' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( '表示方式', 'alumni-core' ); ?></th>
						<td>
							<label class="alumni-photo-mode-option">
								<input type="radio" name="school_photo_display_mode" value="<?php echo esc_attr( Settings::PHOTO_MODE_FIXED ); ?>"
									<?php checked( Settings::PHOTO_MODE_FIXED, $display_mode ); ?> />
								<?php esc_html_e( '固定表示', 'alumni-core' ); ?>
							</label>
							<label class="alumni-photo-mode-option">
								<input type="radio" name="school_photo_display_mode" value="<?php echo esc_attr( Settings::PHOTO_MODE_SLIDESHOW ); ?>"
									<?php checked( Settings::PHOTO_MODE_SLIDESHOW, $display_mode ); ?> />
								<?php esc_html_e( '自動切替', 'alumni-core' ); ?>
							</label>
							<p class="description"><?php esc_html_e( '自動切替は約5秒ごとに写真をフェード切り替えします。写真が1枚以下の場合は自動的に固定表示と同じになります。', 'alumni-core' ); ?></p>
						</td>
					</tr>
					<tr id="alumni-photo-featured-row" class="alumni-photo-featured-row">
						<th scope="row">
							<label for="alumni-photo-featured-select"><?php esc_html_e( '表示画像（固定表示の場合）', 'alumni-core' ); ?></label>
						</th>
						<td>
							<select name="school_photo_featured_id" id="alumni-photo-featured-select">
								<option value=""><?php esc_html_e( '（未選択）', 'alumni-core' ); ?></option>
								<?php foreach ( $photo_ids as $id ) : ?>
									<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $id, $featured_id ); ?>>
										<?php echo esc_html( self::photo_label( $id ) ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( '登録済みの写真から、固定表示で使う1枚を選びます。未選択の場合は一覧の先頭の写真が使われます。', 'alumni-core' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button( __( '設定を保存', 'alumni-core' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Renders one gallery `<li>`: thumbnail, hidden ID field, and
	 * up/down/remove controls. Also used (with $id = 0) as the JS
	 * `<template>` for newly-added photos.
	 *
	 * @param int $id Attachment ID, or 0 for the empty JS template row.
	 */
	private function render_photo_item( $id ) {
		$id = (int) $id;
		?>
		<li class="alumni-photo-gallery-item" data-id="<?php echo esc_attr( $id ); ?>">
			<span class="alumni-photo-gallery-thumb">
				<?php if ( $id ) : ?>
					<?php echo wp_get_attachment_image( $id, 'thumbnail' ); ?>
				<?php endif; ?>
			</span>
			<input type="hidden" name="school_photo_ids[]" class="alumni-photo-gallery-item-input" value="<?php echo esc_attr( $id ); ?>" />
			<button type="button" class="button alumni-photo-move-up"><?php esc_html_e( '↑ 上へ', 'alumni-core' ); ?></button>
			<button type="button" class="button alumni-photo-move-down"><?php esc_html_e( '↓ 下へ', 'alumni-core' ); ?></button>
			<button type="button" class="button alumni-photo-remove"><?php esc_html_e( '削除', 'alumni-core' ); ?></button>
		</li>
		<?php
	}

	/**
	 * Human-readable label for a photo in the 表示画像 dropdown.
	 *
	 * @param int $id Attachment ID.
	 * @return string
	 */
	private static function photo_label( $id ) {
		$title = get_the_title( $id );

		return $title ? $title : sprintf( '#%d', $id );
	}

	/**
	 * Handles the form submission (admin_post_alumni_core_save_school_photos).
	 */
	public function handle_save() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			wp_die( esc_html__( 'この操作を行う権限がありません。', 'alumni-core' ) );
		}

		check_admin_referer( self::NONCE_ACTION );

		// Nonce-verified above; each value is sanitized individually below.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$raw_ids = isset( $_POST['school_photo_ids'] ) ? wp_unslash( $_POST['school_photo_ids'] ) : array();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$raw_mode = isset( $_POST['school_photo_display_mode'] ) ? sanitize_key( wp_unslash( $_POST['school_photo_display_mode'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$raw_featured = isset( $_POST['school_photo_featured_id'] ) ? wp_unslash( $_POST['school_photo_featured_id'] ) : 0;

		Settings::instance()->update_fields(
			array(
				'school_photo_ids'          => Settings::sanitize_attachment_ids( $raw_ids ),
				'school_photo_display_mode' => Settings::sanitize_display_mode( $raw_mode ),
				'school_photo_featured_id'  => Settings::sanitize_attachment_id( $raw_featured ),
			)
		);

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
