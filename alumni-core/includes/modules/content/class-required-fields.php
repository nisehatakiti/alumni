<?php
/**
 * 必須項目（コンテンツ名・本文・人物挨拶の場合は氏名／肩書）の検証.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes\Modules\Content;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Same "downgrade to draft, don't fatally reject" strategy as
 * NewsEvents\Required_Fields: publishing a alumni_content post with a
 * missing required field saves it as a draft instead, with an admin
 * notice explaining what's missing.
 */
class Content_Required_Fields {

	/**
	 * Transient name prefix (suffixed with the current user ID) used to
	 * carry the "what was missing" message across the post-save redirect.
	 */
	const TRANSIENT_PREFIX = 'alumni_content_missing_';

	/**
	 * Hooked to wp_insert_post_data, which runs before the DB write and
	 * can still modify post_status.
	 *
	 * @param array $data    Slashed post data about to be saved.
	 * @param array $postarr Raw $_POST-derived data.
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
			$missing[] = __( 'コンテンツ名', 'alumni-core' );
		}

		$kind = isset( $_POST[ Post_Type::QUERY_VAR_KIND ] ) ? sanitize_key( wp_unslash( $_POST[ Post_Type::QUERY_VAR_KIND ] ) ) : Post_Type::KIND_FREE;

		// フォルダは階層をまとめるための見出しに過ぎず、本文は必須にしない
		// （空のまま公開できる — メニュー・パンくず用の名前があれば十分）。
		if ( Post_Type::KIND_FOLDER !== $kind && '' === trim( wp_strip_all_tags( $data['post_content'] ) ) ) {
			$missing[] = __( '本文', 'alumni-core' );
		}

		if ( Post_Type::KIND_PERSON_GREETING === $kind ) {
			$name  = isset( $_POST['alumni_person_name'] ) ? trim( wp_strip_all_tags( wp_unslash( $_POST['alumni_person_name'] ) ) ) : '';
			$title = isset( $_POST['alumni_person_title'] ) ? trim( wp_strip_all_tags( wp_unslash( $_POST['alumni_person_title'] ) ) ) : '';

			if ( '' === $name ) {
				$missing[] = __( '氏名', 'alumni-core' );
			}

			if ( '' === $title ) {
				$missing[] = __( '肩書', 'alumni-core' );
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
