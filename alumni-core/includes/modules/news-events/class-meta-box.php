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
 * Renders and saves the ニュース／イベント post type's fixed input form:
 * 内容 (plain textarea, saved to post_content), コンテンツ種別 (news/event,
 * saved to postmeta), and イベント開催日 (event only, saved to postmeta).
 *
 * This CPT has no 'editor' support (see Post_Type::register()), so the
 * block/classic content editor never renders — this meta box, placed in
 * the 'normal'/'high' position, takes its place in the main column
 * instead, keeping the whole edit screen to a fixed, predictable set of
 * fields rather than free-form WordPress page building.
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
			__( 'ニュース／イベント内容', 'alumni-core' ),
			array( $this, 'render' ),
			Post_Type::SLUG,
			'normal',
			'high'
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
		<div class="alumni-news-event-fields">
			<p>
				<label for="alumni_content"><strong><?php esc_html_e( '内容', 'alumni-core' ); ?></strong></label><br />
				<textarea id="alumni_content" name="alumni_content" rows="12" class="large-text"><?php echo esc_textarea( $post->post_content ); ?></textarea>
			</p>

			<p>
				<strong><?php esc_html_e( '種別', 'alumni-core' ); ?></strong><br />
				<label class="alumni-content-type-option">
					<input type="radio" name="alumni_content_type" value="<?php echo esc_attr( Post_Type::TYPE_NEWS ); ?>"
						<?php checked( Post_Type::TYPE_NEWS, $content_type ); ?> />
					<?php esc_html_e( 'ニュース', 'alumni-core' ); ?>
				</label>
				<label class="alumni-content-type-option">
					<input type="radio" name="alumni_content_type" value="<?php echo esc_attr( Post_Type::TYPE_EVENT ); ?>"
						<?php checked( Post_Type::TYPE_EVENT, $content_type ); ?> />
					<?php esc_html_e( 'イベント', 'alumni-core' ); ?>
				</label>
			</p>

			<p id="alumni-event-date-field" class="alumni-event-date-field">
				<label for="alumni_event_date"><strong><?php esc_html_e( '開催日', 'alumni-core' ); ?></strong></label><br />
				<input type="date" id="alumni_event_date" name="alumni_event_date" value="<?php echo esc_attr( $event_date ); ?>" />
			</p>

			<p>
				<label for="alumni_news_event_audience"><strong><?php esc_html_e( '対象者', 'alumni-core' ); ?></strong></label><br />
				<select id="alumni_news_event_audience" name="alumni_news_event_audience">
					<option value="<?php echo esc_attr( Post_Type::AUDIENCE_COMMON ); ?>" <?php selected( Post_Type::AUDIENCE_COMMON, Post_Type::get_audience( $post ) ); ?>><?php esc_html_e( '共通', 'alumni-core' ); ?></option>
					<option value="<?php echo esc_attr( Post_Type::AUDIENCE_ALUMNI ); ?>" <?php selected( Post_Type::AUDIENCE_ALUMNI, Post_Type::get_audience( $post ) ); ?>><?php esc_html_e( '卒業生向け', 'alumni-core' ); ?></option>
					<option value="<?php echo esc_attr( Post_Type::AUDIENCE_STUDENT ); ?>" <?php selected( Post_Type::AUDIENCE_STUDENT, Post_Type::get_audience( $post ) ); ?>><?php esc_html_e( '在校生向け', 'alumni-core' ); ?></option>
				</select>
			</p>
		</div>
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

		$audience = isset( $_POST['alumni_news_event_audience'] ) ? sanitize_key( wp_unslash( $_POST['alumni_news_event_audience'] ) ) : Post_Type::AUDIENCE_COMMON;

		if ( ! in_array( $audience, array( Post_Type::AUDIENCE_ALUMNI, Post_Type::AUDIENCE_STUDENT ), true ) ) {
			$audience = Post_Type::AUDIENCE_COMMON;
		}

		update_post_meta( $post_id, Post_Type::META_AUDIENCE, $audience );
	}

	/**
	 * Writes the 内容 textarea into post_content. Hooked to
	 * wp_insert_post_data (not save_post_{type}) at a lower priority
	 * number than Required_Fields::enforce(), so that filter's required-
	 * content check sees the real submitted value.
	 *
	 * wp_insert_post_data fires from inside wp_insert_post() itself, so
	 * setting post_content here — rather than calling wp_update_post()
	 * from save_post_{type} — avoids triggering a second, recursive save.
	 *
	 * Unlike save() above, this only checks the nonce, not
	 * current_user_can(): the admin post-save flow already enforces
	 * capability before wp_insert_post() is ever called, and for a
	 * brand-new post the final post ID isn't reliably available yet at
	 * this point in the lifecycle (save_post_{type} fires later, once it
	 * is).
	 *
	 * @param array $data    Slashed post data about to be saved.
	 * @param array $postarr Raw $_POST-derived data.
	 * @return array
	 */
	public function inject_content( $data, $postarr ) {
		if ( Post_Type::SLUG !== $data['post_type'] ) {
			return $data;
		}

		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( wp_unslash( $_POST[ self::NONCE_NAME ] ), self::NONCE_ACTION ) ) {
			return $data;
		}

		$content = isset( $_POST['alumni_content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['alumni_content'] ) ) : '';

		$data['post_content'] = wp_slash( $content );

		return $data;
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
