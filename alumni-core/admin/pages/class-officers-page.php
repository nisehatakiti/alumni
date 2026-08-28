<?php
/**
 * 同窓会 > 役員・理事紹介 screen.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Admin\Pages;

use AlumniCore\Admin\Admin;
use AlumniCore\Includes\Officers;
use AlumniCore\Includes\Modules\Content\Post_Type as Content_Post_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A single spreadsheet-style form for the whole 役員・理事 list: add/remove/
 * reorder rows client-side (officers-admin.js), then bulk-save the entire
 * list in one submission — rather than one WordPress post per officer.
 */
class Officers_Page {

	/**
	 * Submenu slug.
	 */
	const SLUG = 'alumni-core-officers';

	/**
	 * Nonce action/name shared by the form and the save handler.
	 */
	const NONCE_ACTION = 'alumni_core_save_officers';

	/**
	 * Renders the screen.
	 */
	public function render() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			return;
		}

		$officers         = Officers::instance()->get_all();
		$greeting_options = self::person_greeting_options();
		?>
		<div class="wrap alumni-core-officers">
			<h1><?php esc_html_e( '役員・理事紹介', 'alumni-core' ); ?></h1>

			<?php if ( isset( $_GET['updated'] ) && 'true' === $_GET['updated'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only status flag. ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( '設定を保存しました。', 'alumni-core' ); ?></p>
				</div>
			<?php endif; ?>

			<p><?php esc_html_e( '卒業期・肩書・委員会・氏名を一覧で登録できます。肩書・委員会は自由入力です（同窓会ごとに構成が異なるため、選択肢は固定していません）。', 'alumni-core' ); ?></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="alumni-core-officers-form">
				<input type="hidden" name="action" value="alumni_core_save_officers" />
				<?php wp_nonce_field( self::NONCE_ACTION ); ?>

				<table class="wp-list-table widefat fixed striped alumni-officers-table">
					<thead>
						<tr>
							<th class="alumni-officers-col-term"><?php esc_html_e( '卒業期', 'alumni-core' ); ?></th>
							<th class="alumni-officers-col-title"><?php esc_html_e( '肩書', 'alumni-core' ); ?></th>
							<th class="alumni-officers-col-committee"><?php esc_html_e( '委員会', 'alumni-core' ); ?></th>
							<th class="alumni-officers-col-name"><?php esc_html_e( '氏名', 'alumni-core' ); ?></th>
							<th class="alumni-officers-col-link"><?php esc_html_e( 'リンク先（人物挨拶）', 'alumni-core' ); ?></th>
							<th class="alumni-officers-col-actions"><?php esc_html_e( '操作', 'alumni-core' ); ?></th>
						</tr>
					</thead>
					<tbody id="alumni-officers-list">
						<?php foreach ( $officers as $index => $officer ) : ?>
							<?php $this->render_row( $index, $officer, $greeting_options ); ?>
						<?php endforeach; ?>
					</tbody>
				</table>
				<template id="alumni-officers-row-template">
					<table><tbody><?php $this->render_row( '__INDEX__', array(), $greeting_options ); ?></tbody></table>
				</template>

				<p>
					<button type="button" id="alumni-officers-add" class="button button-secondary">
						<?php esc_html_e( '＋ 行を追加', 'alumni-core' ); ?>
					</button>
				</p>

				<?php submit_button( __( '保存', 'alumni-core' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Renders one officer `<tr>`. Also used (with an empty $officer array)
	 * as the JS `<template>` for newly-added rows.
	 *
	 * @param int|string $index            0-based row position, or the
	 *                                       '__INDEX__' placeholder used by
	 *                                       the JS template.
	 * @param array      $officer          Officer row (see Officers class
	 *                                       docblock), or [] for a new row.
	 * @param array      $greeting_options  post_id => title, from
	 *                                       self::person_greeting_options().
	 */
	private function render_row( $index, array $officer, array $greeting_options ) {
		$row_id    = isset( $officer['row_id'] ) ? $officer['row_id'] : '';
		$term      = isset( $officer['term'] ) ? $officer['term'] : '';
		$title     = isset( $officer['title'] ) ? $officer['title'] : '';
		$committee = isset( $officer['committee'] ) ? $officer['committee'] : '';
		$name      = isset( $officer['name'] ) ? $officer['name'] : '';
		$linked_id = isset( $officer['linked_content_id'] ) ? (int) $officer['linked_content_id'] : 0;
		?>
		<tr class="alumni-officers-row" data-index="<?php echo esc_attr( $index ); ?>">
			<td>
				<input type="hidden" name="officers[<?php echo esc_attr( $index ); ?>][row_id]" class="alumni-officers-row-id" value="<?php echo esc_attr( $row_id ); ?>" />
				<input type="number" inputmode="numeric" min="1" class="small-text" name="officers[<?php echo esc_attr( $index ); ?>][term]" value="<?php echo esc_attr( $term ); ?>" />
			</td>
			<td>
				<input type="text" class="regular-text" name="officers[<?php echo esc_attr( $index ); ?>][title]" value="<?php echo esc_attr( $title ); ?>" />
			</td>
			<td>
				<input type="text" class="regular-text" name="officers[<?php echo esc_attr( $index ); ?>][committee]" value="<?php echo esc_attr( $committee ); ?>" />
			</td>
			<td>
				<input type="text" class="regular-text" name="officers[<?php echo esc_attr( $index ); ?>][name]" value="<?php echo esc_attr( $name ); ?>" />
			</td>
			<td>
				<select name="officers[<?php echo esc_attr( $index ); ?>][linked_content_id]">
					<option value="0"><?php esc_html_e( 'リンクなし', 'alumni-core' ); ?></option>
					<?php foreach ( $greeting_options as $content_id => $content_title ) : ?>
						<option value="<?php echo esc_attr( $content_id ); ?>" <?php selected( $content_id, $linked_id ); ?>>
							<?php echo esc_html( $content_title ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</td>
			<td>
				<button type="button" class="button alumni-officers-move-up"><?php esc_html_e( '↑', 'alumni-core' ); ?></button>
				<button type="button" class="button alumni-officers-move-down"><?php esc_html_e( '↓', 'alumni-core' ); ?></button>
				<button type="button" class="button alumni-officers-remove"><?php esc_html_e( '削除', 'alumni-core' ); ?></button>
			</td>
		</tr>
		<?php
	}

	/**
	 * リンク先コンテンツ dropdown options: every 人物挨拶 content post,
	 * regardless of publish status (an admin linking an officer while both
	 * are still drafts is a normal editing order, not an error) — the
	 * public-facing link itself is still resolved/hidden safely at read
	 * time by Officers::is_valid_linked_content() and the theme-facing
	 * alumni_core_get_officer_link_url() API.
	 *
	 * @return array post_id => post_title.
	 */
	private static function person_greeting_options() {
		$posts = get_posts(
			array(
				'post_type'      => Content_Post_Type::SLUG,
				'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'meta_key'       => Content_Post_Type::META_KIND, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- small admin-only list, filtering by kind is the entire purpose of this query.
				'meta_value'     => Content_Post_Type::KIND_PERSON_GREETING, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		$options = array();

		foreach ( $posts as $post ) {
			$options[ $post->ID ] = $post->post_title ? $post->post_title : sprintf( '#%d', $post->ID );
		}

		return $options;
	}

	/**
	 * Handles the form submission (admin_post_alumni_core_save_officers).
	 */
	public function handle_save() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			wp_die( esc_html__( 'この操作を行う権限がありません。', 'alumni-core' ) );
		}

		check_admin_referer( self::NONCE_ACTION );

		// Nonce-verified above; Officers::save() sanitizes/unslashes every
		// field of every row individually, and safely ignores a malformed
		// or missing structure rather than trusting it.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$raw_rows = isset( $_POST['officers'] ) ? $_POST['officers'] : array();

		Officers::instance()->save( $raw_rows );

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
