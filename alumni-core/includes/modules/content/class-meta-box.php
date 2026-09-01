<?php
/**
 * コンテンツ種別・本文・人物挨拶用フィールドのメタボックス.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes\Modules\Content;

use AlumniCore\Includes\Settings;
use AlumniCore\Includes\Content_Hierarchy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders and saves the alumni_content post type's fixed input form:
 * コンテンツ種別 (free/person_greeting), 本文 (plain textarea, saved to
 * post_content), and — only when the kind is 人物挨拶 — 氏名／ふりがな／
 * 肩書／卒業期／顔写真.
 *
 * Structurally identical to NewsEvents\Meta_Box: no 'editor' support on
 * this CPT (see Post_Type::register()), so this meta box takes the block
 * editor's place, and post_content is written via wp_insert_post_data
 * (not save_post) for the same reason documented there.
 */
class Content_Meta_Box {

	/**
	 * Nonce action/name shared by the form and the save handler.
	 */
	const NONCE_ACTION = 'alumni_core_save_content_meta';
	const NONCE_NAME   = 'alumni_content_meta_nonce';

	/**
	 * Registers the meta box. Hooked to add_meta_boxes.
	 */
	public function register() {
		add_meta_box(
			'alumni-content-details',
			__( 'コンテンツ情報', 'alumni-core' ),
			array( $this, 'render' ),
			Post_Type::SLUG,
			'normal',
			'high'
		);
	}

	/**
	 * Loads the show/hide toggle script and the 顔写真 media picker, only
	 * on this post type's edit screen. Hooked to admin_enqueue_scripts.
	 */
	public function enqueue_assets() {
		$screen = get_current_screen();

		if ( ! $screen || Post_Type::SLUG !== $screen->post_type ) {
			return;
		}

		wp_enqueue_media();

		// Reuses the same generic .alumni-media-picker markup/script as
		// 校章／同窓会ロゴ (Settings_Page), rather than duplicating the
		// wp.media() wiring for 顔写真.
		wp_enqueue_script(
			'alumni-core-media-picker',
			ALUMNI_CORE_URL . 'admin/assets/js/media-picker.js',
			array(),
			ALUMNI_CORE_VERSION,
			true
		);

		wp_enqueue_script(
			'alumni-core-content-meta-box',
			ALUMNI_CORE_URL . 'admin/assets/js/content-meta-box.js',
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

		$kind = Post_Type::get_kind( $post );

		// 「人物挨拶を追加」／「自由コンテンツを追加」 admin menu shortcuts
		// (Admin::register_menu()) link to 新規追加 with the intended kind
		// in the query string, so a brand-new (auto-draft, no postmeta yet)
		// post shows the right fields immediately instead of always
		// defaulting to 自由コンテンツ. Never trusted for an existing post
		// (get_kind() above already reflects whatever was actually saved),
		// and never used to decide what gets saved — see Post_Type::QUERY_VAR_KIND.
		if ( 'auto-draft' === $post->post_status && isset( $_GET[ Post_Type::QUERY_VAR_KIND ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only affects which radio is pre-selected, never what gets saved.
			$requested_kind = sanitize_key( wp_unslash( $_GET[ Post_Type::QUERY_VAR_KIND ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( Post_Type::KIND_PERSON_GREETING === $requested_kind ) {
				$kind = Post_Type::KIND_PERSON_GREETING;
			} elseif ( Post_Type::KIND_TERMS === $requested_kind ) {
				$kind = Post_Type::KIND_TERMS;
			} elseif ( Post_Type::KIND_FOLDER === $requested_kind ) {
				$kind = Post_Type::KIND_FOLDER;
			}
		}

		$name           = Post_Type::get_person_name( $post );
		$kana           = Post_Type::get_person_kana( $post );
		$title          = Post_Type::get_person_title( $post );
		$term           = Post_Type::get_person_term( $post );
		$photo_id       = (int) get_post_meta( $post->ID, Post_Type::META_PERSON_PHOTO_ID, true );
		$display_title  = (string) get_post_meta( $post->ID, Post_Type::META_TERMS_DISPLAY_TITLE, true );
		$effective_date = Post_Type::get_terms_effective_date( $post );
		$revised_date   = Post_Type::get_terms_revised_date( $post );
		$audience       = Post_Type::get_audience( $post );
		$parent_id      = Post_Type::get_parent_id( $post );
		?>
		<div class="alumni-content-fields">
			<p>
				<strong><?php esc_html_e( 'コンテンツ種別', 'alumni-core' ); ?></strong><br />
				<label class="alumni-content-kind-option">
					<input type="radio" name="<?php echo esc_attr( Post_Type::QUERY_VAR_KIND ); ?>" value="<?php echo esc_attr( Post_Type::KIND_FREE ); ?>"
						<?php checked( Post_Type::KIND_FREE, $kind ); ?> />
					<?php esc_html_e( '自由コンテンツ', 'alumni-core' ); ?>
				</label>
				<label class="alumni-content-kind-option">
					<input type="radio" name="<?php echo esc_attr( Post_Type::QUERY_VAR_KIND ); ?>" value="<?php echo esc_attr( Post_Type::KIND_PERSON_GREETING ); ?>"
						<?php checked( Post_Type::KIND_PERSON_GREETING, $kind ); ?> />
					<?php esc_html_e( '人物挨拶', 'alumni-core' ); ?>
				</label>
				<label class="alumni-content-kind-option">
					<input type="radio" name="<?php echo esc_attr( Post_Type::QUERY_VAR_KIND ); ?>" value="<?php echo esc_attr( Post_Type::KIND_TERMS ); ?>"
						<?php checked( Post_Type::KIND_TERMS, $kind ); ?> />
					<?php esc_html_e( '規約類', 'alumni-core' ); ?>
				</label>
				<label class="alumni-content-kind-option">
					<input type="radio" name="<?php echo esc_attr( Post_Type::QUERY_VAR_KIND ); ?>" value="<?php echo esc_attr( Post_Type::KIND_FOLDER ); ?>"
						<?php checked( Post_Type::KIND_FOLDER, $kind ); ?> />
					<?php esc_html_e( 'フォルダ（階層をまとめるだけの見出し）', 'alumni-core' ); ?>
				</label>
				<p class="description"><?php esc_html_e( '「コンテンツ名」（タイトル欄）は自由に決められます。例：会長挨拶、副会長挨拶、校長からのメッセージ、いずれも種別は「人物挨拶」のまま登録できます。規約類の場合、「コンテンツ名」がそのまま規約名になります（例：同窓会規約、会則、個人情報保護方針）。フォルダは、例えば「同窓会情報」「同窓会組織図」のように、他のコンテンツをまとめるためだけの階層ノードです。フォルダ自体に本文を書く必要はありません（メニュー・パンくず用の見出しとしてのみ使われます）。', 'alumni-core' ); ?></p>
			</p>

			<p>
				<label for="alumni_content_audience"><strong><?php esc_html_e( '対象者', 'alumni-core' ); ?></strong></label><br />
				<select id="alumni_content_audience" name="alumni_content_audience">
					<option value="<?php echo esc_attr( Post_Type::AUDIENCE_COMMON ); ?>" <?php selected( Post_Type::AUDIENCE_COMMON, $audience ); ?>><?php esc_html_e( '共通', 'alumni-core' ); ?></option>
					<option value="<?php echo esc_attr( Post_Type::AUDIENCE_ALUMNI ); ?>" <?php selected( Post_Type::AUDIENCE_ALUMNI, $audience ); ?>><?php esc_html_e( '卒業生向け', 'alumni-core' ); ?></option>
					<option value="<?php echo esc_attr( Post_Type::AUDIENCE_STUDENT ); ?>" <?php selected( Post_Type::AUDIENCE_STUDENT, $audience ); ?>><?php esc_html_e( '在校生向け', 'alumni-core' ); ?></option>
				</select>
				<p class="description"><?php esc_html_e( 'このコンテンツが誰に向けた情報かを表します（「在校生向け」は学校からの発信ではなく、同窓会が在校生に向けて発信する情報という意味です）。トップレベル（親コンテンツ未設定）の場合、メニュー上でこの区分ごとにまとめて表示されます。', 'alumni-core' ); ?></p>
			</p>

			<p>
				<label for="alumni_content_parent_id"><strong><?php esc_html_e( '親コンテンツ', 'alumni-core' ); ?></strong></label><br />
				<select id="alumni_content_parent_id" name="alumni_content_parent_id">
					<option value="0"><?php esc_html_e( '（トップレベル）', 'alumni-core' ); ?></option>
					<?php $this->render_parent_options( $post->ID, $parent_id ); ?>
				</select>
				<p class="description"><?php esc_html_e( 'このコンテンツをどのコンテンツの下に整理するかを選びます。未選択（トップレベル）の場合、上の「対象者」の直下に配置されます。', 'alumni-core' ); ?></p>
			</p>

			<div id="alumni-person-greeting-fields" class="alumni-person-greeting-fields">
				<p class="description alumni-person-greeting-intro">
					<?php esc_html_e( '上の「コンテンツ名」（タイトル欄）と、この下の「氏名」「肩書」は別の項目です。コンテンツ名はページの見出し（例：校長挨拶）、氏名・肩書はその挨拶を書いた本人の情報（例：氏名＝鈴木花子、肩書＝校長）を表します。', 'alumni-core' ); ?>
				</p>
				<p>
					<label for="alumni_person_name"><strong><?php esc_html_e( '氏名（必須）', 'alumni-core' ); ?></strong></label><br />
					<input type="text" id="alumni_person_name" name="alumni_person_name" class="regular-text" value="<?php echo esc_attr( $name ); ?>" placeholder="<?php echo esc_attr__( '例：山田 太郎', 'alumni-core' ); ?>" />
				</p>
				<p>
					<label for="alumni_person_kana"><strong><?php esc_html_e( 'ふりがな（任意）', 'alumni-core' ); ?></strong></label><br />
					<input type="text" id="alumni_person_kana" name="alumni_person_kana" class="regular-text" value="<?php echo esc_attr( $kana ); ?>" placeholder="<?php echo esc_attr__( '例：やまだ たろう', 'alumni-core' ); ?>" />
				</p>
				<p>
					<label for="alumni_person_title"><strong><?php esc_html_e( '肩書（必須）', 'alumni-core' ); ?></strong></label><br />
					<input type="text" id="alumni_person_title" name="alumni_person_title" class="regular-text" value="<?php echo esc_attr( $title ); ?>" placeholder="<?php echo esc_attr__( '例：会長、校長、副会長', 'alumni-core' ); ?>" />
				</p>
				<p>
					<label for="alumni_person_term"><strong><?php esc_html_e( '卒業期（任意）', 'alumni-core' ); ?></strong></label><br />
					<input type="number" inputmode="numeric" id="alumni_person_term" name="alumni_person_term" class="small-text" min="1" value="<?php echo esc_attr( $term ); ?>" />
				</p>
				<p>
					<strong><?php esc_html_e( '顔写真（任意）', 'alumni-core' ); ?></strong><br />
					<?php $this->render_photo_picker( $photo_id ); ?>
				</p>
			</div>

			<div id="alumni-terms-fields" class="alumni-terms-fields">
				<p>
					<label for="alumni_terms_display_title"><strong><?php esc_html_e( '公開タイトル（任意）', 'alumni-core' ); ?></strong></label><br />
					<input type="text" id="alumni_terms_display_title" name="alumni_terms_display_title" class="regular-text" value="<?php echo esc_attr( $display_title ); ?>" placeholder="<?php echo esc_attr__( '未入力の場合はコンテンツ名（規約名）をそのまま使用します', 'alumni-core' ); ?>" />
					<p class="description"><?php esc_html_e( '公開ページの見出しをコンテンツ名（規約名）と別にしたい場合だけ入力してください。', 'alumni-core' ); ?></p>
				</p>
				<p>
					<label for="alumni_terms_effective_date"><strong><?php esc_html_e( '施行日（任意）', 'alumni-core' ); ?></strong></label><br />
					<input type="date" id="alumni_terms_effective_date" name="alumni_terms_effective_date" value="<?php echo esc_attr( $effective_date ); ?>" />
				</p>
				<p>
					<label for="alumni_terms_revised_date"><strong><?php esc_html_e( '改定日（任意）', 'alumni-core' ); ?></strong></label><br />
					<input type="date" id="alumni_terms_revised_date" name="alumni_terms_revised_date" value="<?php echo esc_attr( $revised_date ); ?>" />
				</p>
				<p>
					<label for="alumni_terms_menu_order"><strong><?php esc_html_e( '表示順（任意）', 'alumni-core' ); ?></strong></label><br />
					<input type="number" inputmode="numeric" id="alumni_terms_menu_order" name="alumni_terms_menu_order" class="small-text" value="<?php echo esc_attr( $post->menu_order ); ?>" />
					<p class="description"><?php esc_html_e( '規約類一覧での並び順です。小さい数字ほど先に表示されます（同じ数字は登録順）。', 'alumni-core' ); ?></p>
				</p>
			</div>

			<p>
				<label for="alumni_content_body">
					<strong>
						<?php
						echo Post_Type::KIND_FOLDER === $kind
							? esc_html__( '本文（任意）', 'alumni-core' )
							: esc_html__( '本文（必須）', 'alumni-core' );
						?>
					</strong>
				</label><br />
				<textarea id="alumni_content_body" name="alumni_content_body" rows="12" class="large-text"><?php echo esc_textarea( $post->post_content ); ?></textarea>
			</p>
		</div>
		<?php
	}

	/**
	 * Renders <option> elements for the 親コンテンツ select, one per
	 * existing alumni_content post (any status, any kind — including
	 * drafts and other フォルダ, so an editor can see and build the
	 * structure before publishing), grouped by 対象者 and indented to show
	 * depth. $exclude_post_id itself and its own descendants are left out,
	 * since choosing either would create a cycle
	 * (Content_Hierarchy::validate_parent() enforces this again at save
	 * time regardless, in case the submitted value didn't come from this
	 * select at all).
	 *
	 * @param int $exclude_post_id 0 for a not-yet-created post.
	 * @param int $selected_parent_id
	 */
	private function render_parent_options( $exclude_post_id, $selected_parent_id ) {
		$exclude_ids = array( (int) $exclude_post_id );

		if ( $exclude_post_id ) {
			$exclude_ids = array_merge( $exclude_ids, Content_Hierarchy::get_descendant_ids( $exclude_post_id ) );
		}

		$audience_labels = array(
			Post_Type::AUDIENCE_COMMON  => __( '共通', 'alumni-core' ),
			Post_Type::AUDIENCE_ALUMNI  => __( '卒業生向け', 'alumni-core' ),
			Post_Type::AUDIENCE_STUDENT => __( '在校生向け', 'alumni-core' ),
		);

		foreach ( $audience_labels as $audience_value => $audience_label ) {
			$tree = Content_Hierarchy::build_tree( $audience_value, true );

			if ( empty( $tree ) ) {
				continue;
			}

			echo '<optgroup label="' . esc_attr( $audience_label ) . '">';
			$this->render_parent_option_nodes( $tree, $exclude_ids, $selected_parent_id, 0 );
			echo '</optgroup>';
		}
	}

	/**
	 * @param array $nodes               Content_Hierarchy::build_tree() shape.
	 * @param int[] $exclude_ids
	 * @param int   $selected_parent_id
	 * @param int   $depth
	 */
	private function render_parent_option_nodes( array $nodes, array $exclude_ids, $selected_parent_id, $depth ) {
		foreach ( $nodes as $node ) {
			$post = $node['post'];

			if ( ! in_array( (int) $post->ID, $exclude_ids, true ) ) {
				$label = str_repeat( '— ', $depth ) . ( $post->post_title ? $post->post_title : sprintf( '#%d', $post->ID ) );
				printf(
					'<option value="%1$d" %2$s>%3$s</option>',
					(int) $post->ID,
					selected( (int) $post->ID, (int) $selected_parent_id, false ),
					esc_html( $label )
				);
			}

			if ( ! empty( $node['children'] ) ) {
				$this->render_parent_option_nodes( $node['children'], $exclude_ids, $selected_parent_id, $depth + 1 );
			}
		}
	}

	/**
	 * Renders the 顔写真 media picker, using the same markup convention as
	 * Settings_Page::render_media_picker() (a preview + hidden attachment-ID
	 * field + 選択/削除 buttons), so the shared media-picker.js script works
	 * against it without modification.
	 *
	 * @param int $attachment_id Currently saved attachment ID, or 0.
	 */
	private function render_photo_picker( $attachment_id ) {
		$attachment_id = (int) $attachment_id;
		?>
		<div class="alumni-media-picker" data-title="<?php echo esc_attr__( '顔写真を選択', 'alumni-core' ); ?>" data-button-text="<?php echo esc_attr__( '選択', 'alumni-core' ); ?>">
			<div class="alumni-media-preview">
				<?php if ( $attachment_id ) : ?>
					<?php echo wp_get_attachment_image( $attachment_id, 'thumbnail' ); ?>
				<?php endif; ?>
			</div>
			<input type="hidden" name="alumni_person_photo_id" class="alumni-media-picker-input" value="<?php echo esc_attr( $attachment_id ); ?>" />
			<p>
				<button type="button" class="button alumni-media-picker-select"><?php esc_html_e( '選択', 'alumni-core' ); ?></button>
				<button type="button" class="button alumni-media-picker-clear"<?php echo $attachment_id ? '' : ' style="display:none;"'; ?>>
					<?php esc_html_e( '削除', 'alumni-core' ); ?>
				</button>
			</p>
		</div>
		<?php
	}

	/**
	 * Saves the meta box fields. Hooked to save_post_{Post_Type::SLUG}.
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

		$kind = isset( $_POST[ Post_Type::QUERY_VAR_KIND ] ) ? sanitize_key( wp_unslash( $_POST[ Post_Type::QUERY_VAR_KIND ] ) ) : Post_Type::KIND_FREE;

		if ( ! in_array( $kind, array( Post_Type::KIND_PERSON_GREETING, Post_Type::KIND_TERMS, Post_Type::KIND_FOLDER ), true ) ) {
			$kind = Post_Type::KIND_FREE;
		}

		update_post_meta( $post_id, Post_Type::META_KIND, $kind );

		// 対象者・親コンテンツはkindに関わらず全種別共通で保存する
		// （対象者・階層はコンテンツ種別とは独立した別概念のため）。
		$audience = isset( $_POST['alumni_content_audience'] ) ? sanitize_key( wp_unslash( $_POST['alumni_content_audience'] ) ) : Post_Type::AUDIENCE_COMMON;

		if ( ! in_array( $audience, array( Post_Type::AUDIENCE_ALUMNI, Post_Type::AUDIENCE_STUDENT ), true ) ) {
			$audience = Post_Type::AUDIENCE_COMMON;
		}

		update_post_meta( $post_id, Post_Type::META_AUDIENCE, $audience );

		$parent_id = isset( $_POST['alumni_content_parent_id'] ) ? absint( wp_unslash( $_POST['alumni_content_parent_id'] ) ) : 0;

		if ( ! Content_Hierarchy::validate_parent( $post_id, $parent_id ) ) {
			// 循環参照になる／存在しない投稿を指す等、不正な選択は無視して
			// トップレベル扱いにする（保存自体は失敗させない）。
			$parent_id = 0;
		}

		if ( $parent_id ) {
			update_post_meta( $post_id, Post_Type::META_PARENT_ID, $parent_id );
		} else {
			delete_post_meta( $post_id, Post_Type::META_PARENT_ID );
		}

		if ( Post_Type::KIND_PERSON_GREETING === $kind ) {
			$name  = isset( $_POST['alumni_person_name'] ) ? sanitize_text_field( wp_unslash( $_POST['alumni_person_name'] ) ) : '';
			$kana  = isset( $_POST['alumni_person_kana'] ) ? sanitize_text_field( wp_unslash( $_POST['alumni_person_kana'] ) ) : '';
			$title = isset( $_POST['alumni_person_title'] ) ? sanitize_text_field( wp_unslash( $_POST['alumni_person_title'] ) ) : '';
			$term  = isset( $_POST['alumni_person_term'] ) ? self::sanitize_term( wp_unslash( $_POST['alumni_person_term'] ) ) : '';
			$photo = isset( $_POST['alumni_person_photo_id'] ) ? Settings::sanitize_attachment_id( wp_unslash( $_POST['alumni_person_photo_id'] ) ) : 0;

			update_post_meta( $post_id, Post_Type::META_PERSON_NAME, $name );
			update_post_meta( $post_id, Post_Type::META_PERSON_KANA, $kana );
			update_post_meta( $post_id, Post_Type::META_PERSON_TITLE, $title );

			if ( '' === $term ) {
				delete_post_meta( $post_id, Post_Type::META_PERSON_TERM );
			} else {
				update_post_meta( $post_id, Post_Type::META_PERSON_TERM, $term );
			}

			if ( $photo ) {
				update_post_meta( $post_id, Post_Type::META_PERSON_PHOTO_ID, $photo );
			} else {
				delete_post_meta( $post_id, Post_Type::META_PERSON_PHOTO_ID );
			}

			self::clear_terms_meta( $post_id );
		} elseif ( Post_Type::KIND_TERMS === $kind ) {
			$display_title  = isset( $_POST['alumni_terms_display_title'] ) ? sanitize_text_field( wp_unslash( $_POST['alumni_terms_display_title'] ) ) : '';
			$effective_date = isset( $_POST['alumni_terms_effective_date'] ) ? self::sanitize_date( wp_unslash( $_POST['alumni_terms_effective_date'] ) ) : '';
			$revised_date   = isset( $_POST['alumni_terms_revised_date'] ) ? self::sanitize_date( wp_unslash( $_POST['alumni_terms_revised_date'] ) ) : '';

			if ( '' === $display_title ) {
				delete_post_meta( $post_id, Post_Type::META_TERMS_DISPLAY_TITLE );
			} else {
				update_post_meta( $post_id, Post_Type::META_TERMS_DISPLAY_TITLE, $display_title );
			}

			if ( '' === $effective_date ) {
				delete_post_meta( $post_id, Post_Type::META_TERMS_EFFECTIVE_DATE );
			} else {
				update_post_meta( $post_id, Post_Type::META_TERMS_EFFECTIVE_DATE, $effective_date );
			}

			if ( '' === $revised_date ) {
				delete_post_meta( $post_id, Post_Type::META_TERMS_REVISED_DATE );
			} else {
				update_post_meta( $post_id, Post_Type::META_TERMS_REVISED_DATE, $revised_date );
			}

			// Switched to 規約類 (or was already): the 人物挨拶専用 fields no
			// longer apply.
			self::clear_person_greeting_meta( $post_id );
		} else {
			// 自由コンテンツ／フォルダ: neither 人物挨拶 nor 規約類 fields apply.
			self::clear_person_greeting_meta( $post_id );
			self::clear_terms_meta( $post_id );
		}
	}

	/**
	 * Drops the 人物挨拶専用 postmeta — used whenever a post's kind is (or
	 * becomes) something other than 人物挨拶, so switching kinds never
	 * leaves stale data behind that no UI shows.
	 *
	 * @param int $post_id
	 */
	private static function clear_person_greeting_meta( $post_id ) {
		delete_post_meta( $post_id, Post_Type::META_PERSON_NAME );
		delete_post_meta( $post_id, Post_Type::META_PERSON_KANA );
		delete_post_meta( $post_id, Post_Type::META_PERSON_TITLE );
		delete_post_meta( $post_id, Post_Type::META_PERSON_TERM );
		delete_post_meta( $post_id, Post_Type::META_PERSON_PHOTO_ID );
	}

	/**
	 * Drops the 規約類専用 postmeta — used whenever a post's kind is (or
	 * becomes) something other than 規約類.
	 *
	 * @param int $post_id
	 */
	private static function clear_terms_meta( $post_id ) {
		delete_post_meta( $post_id, Post_Type::META_TERMS_DISPLAY_TITLE );
		delete_post_meta( $post_id, Post_Type::META_TERMS_EFFECTIVE_DATE );
		delete_post_meta( $post_id, Post_Type::META_TERMS_REVISED_DATE );
	}

	/**
	 * Writes the 本文 textarea into post_content, and (規約類のみ) 表示順
	 * into menu_order. Hooked to wp_insert_post_data at a lower priority
	 * number than Content_Required_Fields::enforce(), same reasoning as
	 * NewsEvents\Meta_Box::inject_content(). menu_order is a native post
	 * table column, not postmeta, so it must be injected here (via
	 * wp_insert_post_data) rather than via update_post_meta() in save() —
	 * setting it with wp_update_post() from inside save_post would
	 * recursively re-trigger save_post instead.
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

		$content = isset( $_POST['alumni_content_body'] ) ? sanitize_textarea_field( wp_unslash( $_POST['alumni_content_body'] ) ) : '';

		$data['post_content'] = wp_slash( $content );

		$kind = isset( $_POST[ Post_Type::QUERY_VAR_KIND ] ) ? sanitize_key( wp_unslash( $_POST[ Post_Type::QUERY_VAR_KIND ] ) ) : Post_Type::KIND_FREE;

		if ( Post_Type::KIND_TERMS === $kind && isset( $_POST['alumni_terms_menu_order'] ) ) {
			$data['menu_order'] = (int) wp_unslash( $_POST['alumni_terms_menu_order'] );
		}

		return $data;
	}

	/**
	 * Validates a 卒業期 submission: a plain positive integer, or '' when
	 * empty/invalid — this is a term NUMBER (期), not a calendar year, so
	 * Settings::sanitize_year()'s MIN_YEAR/max_year() bounds don't apply
	 * here.
	 *
	 * @param mixed $raw Raw form value.
	 * @return int|string
	 */
	public static function sanitize_term( $raw ) {
		if ( '' === $raw || null === $raw || ! is_numeric( $raw ) ) {
			return '';
		}

		if ( (string) (int) $raw !== (string) ( $raw + 0 ) ) {
			return '';
		}

		$term = (int) $raw;

		return $term > 0 ? $term : '';
	}

	/**
	 * Validates a 'Y-m-d' date submission (from an <input type="date">,
	 * 施行日／改定日), rejecting anything that isn't a real calendar date
	 * via checkdate() — same rule as
	 * Term_Calculator::validate_date_parts(), duplicated rather than
	 * cross-referenced since these fields are plain optional metadata,
	 * not part of any calculation.
	 *
	 * @param mixed $raw Raw form value.
	 * @return string 'Y-m-d', or '' when empty/invalid.
	 */
	public static function sanitize_date( $raw ) {
		if ( ! is_string( $raw ) || ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $matches ) ) {
			return '';
		}

		$year  = (int) $matches[1];
		$month = (int) $matches[2];
		$day   = (int) $matches[3];

		if ( ! checkdate( $month, $day, $year ) ) {
			return '';
		}

		return $raw;
	}
}
