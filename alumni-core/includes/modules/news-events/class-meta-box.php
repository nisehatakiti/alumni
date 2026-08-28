<?php
/**
 * コンテンツ種別／イベント開催日 メタボックス.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes\Modules\NewsEvents;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders and saves the ニュース／イベント post type's two custom fields:
 * コンテンツ種別 (news/event) and イベント開催日 (event only).
 */
class Meta_Box {

	/**
	 * Nonce action/name shared by the form and the save handler.
	 */
	const NONCE_ACTION = 'alumni_core_save_news_event_meta';
	const NONCE_NAME   = 'alumni_news_event_meta_nonce';

	/**
	 * Registers the meta box. Hooked to add_meta_boxes.
	 */
	public function register() {
		add_meta_box(
			'alumni-news-event-details',
			__( 'ニュース／イベント詳細', 'alumni-core' ),
			array( $this, 'render' ),
			Post_Type::SLUG,
			'side',
			'default'
		);
	}

	/**
	 * Loads the show/hide toggle script, only on this post type's edit
	 * screen. Hooked to admin_enqueue_scripts.
	 */
	public function enqueue_assets() {
		$screen = get_current_screen();

		if ( ! $screen || Post_Type::SLUG !== $screen->post_type ) {
			return;
		}

		wp_enqueue_script(
			'alumni-core-news-event-meta-box',
			ALUMNI_CORE_URL . 'admin/assets/js/news-event-meta-box.js',
			array(),
			ALUMNI_CORE_VERSION,
			true
		);
	}

	/**
	 * Renders the meta box fields.
	 *
	 * @param \WP_Post $post Current post being edited.
	 */
	public function render( $post ) {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$content_type = Post_Type::get_content_type( $post );
		$event_date   = get_post_meta( $post->ID, Post_Type::META_EVENT_DATE, true );
		?>
		<p>
			<label>
				<input type="radio" name="alumni_content_type" value="<?php echo esc_attr( Post_Type::TYPE_NEWS ); ?>"
					<?php checked( Post_Type::TYPE_NEWS, $content_type ); ?> />
				<?php esc_html_e( 'ニュース', 'alumni-core' ); ?>
			</label>
			<br />
			<label>
				<input type="radio" name="alumni_content_type" value="<?php echo esc_attr( Post_Type::TYPE_EVENT ); ?>"
					<?php checked( Post_Type::TYPE_EVENT, $content_type ); ?> />
				<?php esc_html_e( 'イベント', 'alumni-core' ); ?>
			</label>
		</p>
		<p id="alumni-event-date-field" class="alumni-event-date-field">
			<label for="alumni_event_date"><?php esc_html_e( 'イベント開催日', 'alumni-core' ); ?></label><br />
			<input type="date" id="alumni_event_date" name="alumni_event_date" value="<?php echo esc_attr( $event_date ); ?>" />
		</p>
		<?php
	}

	/**
	 * Saves the meta box fields. Hooked to save_post_{Post_Type::SLUG}, so
	 * this only ever fires for this post type (never for revisions, which
	 * are saved as post_type 'revision').
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public function save( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( wp_unslash( $_POST[ self::NONCE_NAME ] ), self::NONCE_ACTION ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$content_type = isset( $_POST['alumni_content_type'] ) ? sanitize_key( wp_unslash( $_POST['alumni_content_type'] ) ) : Post_Type::TYPE_NEWS;

		if ( Post_Type::TYPE_EVENT !== $content_type ) {
			$content_type = Post_Type::TYPE_NEWS;
		}

		update_post_meta( $post_id, Post_Type::META_CONTENT_TYPE, $content_type );

		if ( Post_Type::TYPE_EVENT === $content_type ) {
			$raw_date   = isset( $_POST['alumni_event_date'] ) ? sanitize_text_field( wp_unslash( $_POST['alumni_event_date'] ) ) : '';
			$event_date = self::sanitize_date( $raw_date );

			if ( $event_date ) {
				update_post_meta( $post_id, Post_Type::META_EVENT_DATE, $event_date );
			} else {
				delete_post_meta( $post_id, Post_Type::META_EVENT_DATE );
			}
		} else {
			delete_post_meta( $post_id, Post_Type::META_EVENT_DATE );
		}
	}

	/**
	 * Validates a Y-m-d date string, rejecting anything that isn't a real
	 * calendar date in that exact format (e.g. "2024-02-30").
	 *
	 * @param string $raw Raw date string.
	 * @return string Validated Y-m-d date, or '' if invalid/empty.
	 */
	public static function sanitize_date( $raw ) {
		if ( '' === $raw ) {
			return '';
		}

		$date = \DateTime::createFromFormat( 'Y-m-d', $raw );

		if ( ! $date || $date->format( 'Y-m-d' ) !== $raw ) {
			return '';
		}

		return $raw;
	}
}
