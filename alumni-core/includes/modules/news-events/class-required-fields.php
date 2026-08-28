<?php
/**
 * 必須項目（タイトル・本文・イベントの場合は開催日）の検証.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes\Modules\NewsEvents;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress's post editor has no built-in way to block publishing on
 * empty fields. This downgrades a ニュース／イベント to draft instead of
 * letting it go live with a missing required field, and surfaces an admin
 * notice explaining why — rather than silently publishing incomplete
 * content or fatally rejecting the save.
 */
class Required_Fields {

	/**
	 * Transient name prefix (suffixed with the current user ID) used to
	 * carry the "what was missing" message across the post-save redirect.
	 */
	const TRANSIENT_PREFIX = 'alumni_news_event_missing_';

	/**
	 * Hooked to wp_insert_post_data, which runs before the DB write and
	 * can still modify post_status.
	 *
	 * @param array $data    Slashed post data about to be saved.
	 * @param array $postarr Raw $_POST-derived data as passed to
	 *                        wp_insert_post()/wp_update_post().
	 * @return array
	 */
	public function enforce( $data, $postarr ) {
		if ( Post_Type::SLUG !== $data['post_type'] ) {
			return $data;
		}

		if ( 'publish' !== $data['post_status'] ) {
			return $data;
		}

		$missing = array();

		if ( '' === trim( wp_strip_all_tags( $data['post_title'] ) ) ) {
			$missing[] = __( 'タイトル', 'alumni-core' );
		}

		if ( '' === trim( wp_strip_all_tags( $data['post_content'] ) ) ) {
			$missing[] = __( '本文', 'alumni-core' );
		}

		$content_type = isset( $_POST['alumni_content_type'] ) ? sanitize_key( wp_unslash( $_POST['alumni_content_type'] ) ) : Post_Type::TYPE_NEWS;

		if ( Post_Type::TYPE_EVENT === $content_type ) {
			$raw_date = isset( $_POST['alumni_event_date'] ) ? sanitize_text_field( wp_unslash( $_POST['alumni_event_date'] ) ) : '';

			if ( '' === Meta_Box::sanitize_date( $raw_date ) ) {
				$missing[] = __( 'イベント開催日', 'alumni-core' );
			}
		}

		if ( ! empty( $missing ) ) {
			$data['post_status'] = 'draft';
			set_transient( self::TRANSIENT_PREFIX . get_current_user_id(), $missing, MINUTE_IN_SECONDS );
		}

		return $data;
	}

	/**
	 * Shows the "saved as draft, here's what's missing" notice on this
	 * post type's edit screen. Hooked to admin_notices.
	 */
	public function render_notice() {
		$screen = get_current_screen();

		if ( ! $screen || Post_Type::SLUG !== $screen->post_type ) {
			return;
		}

		$key     = self::TRANSIENT_PREFIX . get_current_user_id();
		$missing = get_transient( $key );

		if ( empty( $missing ) ) {
			return;
		}

		delete_transient( $key );
		?>
		<div class="notice notice-warning">
			<p>
				<?php
				printf(
					/* translators: %s: comma-separated list of missing required fields */
					esc_html__( '次の必須項目が未入力のため、下書きとして保存しました：%s', 'alumni-core' ),
					esc_html( implode( '、', $missing ) )
				);
				?>
			</p>
		</div>
		<?php
	}
}
