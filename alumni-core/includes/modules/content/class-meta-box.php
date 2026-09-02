<?php
/**
 * コンテンツ種別・本文・人物挨拶用フィールドのメタボックス.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes\Modules\Content;

use AlumniCore\Includes\Settings;
use AlumniCore\Includes\Person_Greeting_Groups;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders and saves the alumni_content post type's fixed input form:
 * コンテンツ種別 (free/person_greeting/terms/folder), 本文 (plain textarea,
 * saved to post_content — for every kind except 規約類), and —
 * kind-specific — 氏名／ふりがな／肩書／人物挨拶グループ／任期／卒業期／
 * 顔写真 (人物挨拶) or 公開タイトル／施行日／改定履歴／文字サイズ／表示順
 * (規約類).
 *
 * 'editor' support (see Post_Type::register()) is enabled for the block
 * editor only for 規約類 (Post_Type::maybe_use_block_editor(), hooked to
 * the 'use_block_editor_for_post_type' filter), so this meta box's 本文
 * textarea only renders for the other kinds and takes the block editor's
 * place there, with post_content written via wp_insert_post_data (not
 * save_post), same reasoning as NewsEvents\Meta_Box. For 規約類, the block
 * editor itself owns post_content — this meta box neither renders nor
 * writes 本文 for that kind (see render()/inject_content()), so a 規約類
 * post can use WordPress's own paragraph-level formatting (太字／文字
 * サイズ) instead of a single plugin-wide font size.
 *
 * 「サイトの階層構造」と「コンテンツそのもの」は完全に分離している —
 * このメタボックスは「親コンテンツ」（旧META_PARENT_ID、サイト階層を
 * 決める汎用の親子関係）をもう表示・編集しない。サイト上のどこに配置
 * するかはMenu Structure（同窓会 > メニュー構成）だけが決める。既存投稿
 * に保存済みのMETA_PARENT_IDは後方互換のため一切削除・上書きしない
 * （単にこの画面から読み書きしなくなっただけ）。
 *
 * 「人物挨拶グループ」（Person_Greeting_Groups、例：母校校長挨拶／同窓会長
 * 挨拶）は、上記の「親コンテンツ」とは別物 — 歴代の人物挨拶を1つに
 * まとめるための人物挨拶専用の分類で、自由コンテンツ・規約類には一切
 * 関与しない（Person_Greeting_Groupsのクラスdocblock参照）。
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
		$group_id       = Post_Type::get_person_greeting_group_id( $post );
		$tenure         = Post_Type::get_person_tenure( $post );
		$display_title  = (string) get_post_meta( $post->ID, Post_Type::META_TERMS_DISPLAY_TITLE, true );
		$effective_date = Post_Type::get_terms_effective_date( $post );
		$revision_dates = Post_Type::get_terms_revision_dates( $post );
		$font_size      = Post_Type::get_terms_font_size( $post );
		$audience       = Post_Type::get_audience( $post );

		// 「＋母校校長挨拶を追加」「＋同窓会長挨拶を追加」等、特定の人物挨拶
		// グループへのクイックリンクから来た場合、グループを自動選択する
		// （kindのプリフィルと同じ仕組み・同じ理由 — auto-draftのときだけ、
		// 実際に保存された値をどのkindでも上書きしない）。
		if ( 'auto-draft' === $post->post_status && isset( $_GET[ Post_Type::QUERY_VAR_GROUP ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only affects which <select> option is pre-selected, never what gets saved.
			$requested_group_id = sanitize_text_field( wp_unslash( $_GET[ Post_Type::QUERY_VAR_GROUP ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( null !== Person_Greeting_Groups::instance()->get_group( $requested_group_id ) ) {
				$group_id = $requested_group_id;
			}
		}
		?>
		<div class="alumni-content-fields">
			<p>
				<strong><?php esc_html_e( 'コンテンツ種別', 'alumni-core' ); ?></strong>
				<?php echo esc_html( self::kind_label( $kind ) ); ?>
				<input type="hidden" name="<?php echo esc_attr( Post_Type::QUERY_VAR_KIND ); ?>" value="<?php echo esc_attr( $kind ); ?>" />
			</p>

			<p>
				<label for="alumni_content_audience"><strong><?php esc_html_e( '対象者', 'alumni-core' ); ?></strong></label><br />
				<select id="alumni_content_audience" name="alumni_content_audience">
					<option value="<?php echo esc_attr( Post_Type::AUDIENCE_COMMON ); ?>" <?php selected( Post_Type::AUDIENCE_COMMON, $audience ); ?>><?php esc_html_e( '共通', 'alumni-core' ); ?></option>
					<option value="<?php echo esc_attr( Post_Type::AUDIENCE_ALUMNI ); ?>" <?php selected( Post_Type::AUDIENCE_ALUMNI, $audience ); ?>><?php esc_html_e( '卒業生向け', 'alumni-core' ); ?></option>
					<option value="<?php echo esc_attr( Post_Type::AUDIENCE_STUDENT ); ?>" <?php selected( Post_Type::AUDIENCE_STUDENT, $audience ); ?>><?php esc_html_e( '在校生向け', 'alumni-core' ); ?></option>
				</select>
				<p class="description"><?php esc_html_e( 'このコンテンツが誰に向けた情報かを表す分類です。サイト上の掲載場所は「メニュー構成」で設定します。', 'alumni-core' ); ?></p>
			</p>

			<div id="alumni-person-greeting-fields" class="alumni-person-greeting-fields"<?php echo Post_Type::KIND_PERSON_GREETING === $kind ? '' : ' style="display:none;"'; ?>>
				<p>
					<label for="alumni_person_greeting_group_id"><strong><?php esc_html_e( '人物挨拶グループ（必須）', 'alumni-core' ); ?></strong></label><br />
					<select id="alumni_person_greeting_group_id" name="alumni_person_greeting_group_id">
						<option value=""><?php esc_html_e( '（未選択）', 'alumni-core' ); ?></option>
						<?php foreach ( Person_Greeting_Groups::instance()->get_all() as $group ) : ?>
							<option value="<?php echo esc_attr( $group['group_id'] ); ?>" <?php selected( $group['group_id'], $group_id ); ?>><?php echo esc_html( $group['name'] ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( '「母校校長挨拶」「同窓会長挨拶」のように、歴代の人物挨拶をまとめる分類です。', 'alumni-core' ); ?></p>
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
					<label for="alumni_person_tenure"><strong><?php esc_html_e( '任期（任意）', 'alumni-core' ); ?></strong></label><br />
					<input type="text" id="alumni_person_tenure" name="alumni_person_tenure" class="regular-text" value="<?php echo esc_attr( $tenure ); ?>" placeholder="<?php echo esc_attr__( '例：2020年〜2024年、初代', 'alumni-core' ); ?>" />
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

			<div id="alumni-terms-fields" class="alumni-terms-fields"<?php echo Post_Type::KIND_TERMS === $kind ? '' : ' style="display:none;"'; ?>>
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
					<strong><?php esc_html_e( '改定履歴（任意）', 'alumni-core' ); ?></strong><br />
					<span class="description"><?php esc_html_e( '規約自体を改定した日を、必要な分だけ追加してください（制定日は上の「施行日」で管理します）。', 'alumni-core' ); ?></span>
					<span id="alumni-terms-revision-dates" class="alumni-terms-revision-dates">
						<?php foreach ( $revision_dates as $revision_date ) : ?>
							<span class="alumni-terms-revision-date-row">
								<input type="date" name="alumni_terms_revision_dates[]" value="<?php echo esc_attr( $revision_date ); ?>" />
								<button type="button" class="button alumni-terms-revision-date-remove"><?php esc_html_e( '削除', 'alumni-core' ); ?></button>
							</span>
						<?php endforeach; ?>
					</span>
					<template id="alumni-terms-revision-date-row-template">
						<span class="alumni-terms-revision-date-row">
							<input type="date" name="alumni_terms_revision_dates[]" value="" />
							<button type="button" class="button alumni-terms-revision-date-remove"><?php esc_html_e( '削除', 'alumni-core' ); ?></button>
						</span>
					</template>
					<p>
						<button type="button" id="alumni-terms-revision-date-add" class="button button-secondary"><?php esc_html_e( '＋ 改定履歴を追加', 'alumni-core' ); ?></button>
					</p>
				</p>
				<p>
					<label for="alumni_terms_font_size"><strong><?php esc_html_e( '本文全体の既定の文字サイズ', 'alumni-core' ); ?></strong></label><br />
					<select id="alumni_terms_font_size" name="alumni_terms_font_size">
						<option value="<?php echo esc_attr( Post_Type::TERMS_FONT_SMALL ); ?>" <?php selected( Post_Type::TERMS_FONT_SMALL, $font_size ); ?>><?php esc_html_e( '小', 'alumni-core' ); ?></option>
						<option value="<?php echo esc_attr( Post_Type::TERMS_FONT_MEDIUM ); ?>" <?php selected( Post_Type::TERMS_FONT_MEDIUM, $font_size ); ?>><?php esc_html_e( '中', 'alumni-core' ); ?></option>
						<option value="<?php echo esc_attr( Post_Type::TERMS_FONT_LARGE ); ?>" <?php selected( Post_Type::TERMS_FONT_LARGE, $font_size ); ?>><?php esc_html_e( '大', 'alumni-core' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( '本文（下のブロックエディター）の段落ごとの文字サイズ・太字は個別に指定できます。ここでの指定は、段落ごとに個別指定していない部分の既定値、および実際のサイズはテーマ側のデザインに従います。', 'alumni-core' ); ?></p>
				</p>
				<p>
					<label for="alumni_terms_menu_order"><strong><?php esc_html_e( '表示順（任意）', 'alumni-core' ); ?></strong></label><br />
					<input type="number" inputmode="numeric" id="alumni_terms_menu_order" name="alumni_terms_menu_order" class="small-text" value="<?php echo esc_attr( $post->menu_order ); ?>" />
					<p class="description"><?php esc_html_e( '規約類一覧での並び順です。小さい数字ほど先に表示されます（同じ数字は登録順）。', 'alumni-core' ); ?></p>
				</p>
			</div>

			<?php if ( Post_Type::KIND_TERMS === $kind ) : ?>
				<p class="description alumni-terms-editor-note">
					<?php esc_html_e( '規約類の本文は、この上の「タイトルを追加」欄の下にあるブロックエディターで編集します。段落ごとに太字や文字サイズ（小・標準・大・特大）を指定できます。', 'alumni-core' ); ?>
				</p>
			<?php else : ?>
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
			<?php endif; ?>
		</div>
		<?php
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
	 * A human-readable label for a kind, for the read-only display in
	 * render() (there's no editable control for this any more — see class
	 * docblock "コンテンツ種別は「入口」で決める").
	 *
	 * @param string $kind
	 * @return string
	 */
	private static function kind_label( $kind ) {
		switch ( $kind ) {
			case Post_Type::KIND_PERSON_GREETING:
				return __( '人物挨拶', 'alumni-core' );
			case Post_Type::KIND_TERMS:
				return __( '規約類', 'alumni-core' );
			case Post_Type::KIND_FOLDER:
				// 新規作成の入口はもう提供していない(既存データの後方
				// 互換表示のみ) — class docblock参照。
				return __( 'フォルダ（既存データ、この画面からは新規作成できません）', 'alumni-core' );
			default:
				return __( '自由コンテンツ', 'alumni-core' );
		}
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

		// コンテンツ種別は作成時の入口だけで決まり、以後は不変
		// （render()のコンテンツ種別欄はもう選択式ではなく、隠しフィールド
		// で現在値をそのまま送り返すだけの表示専用フィールド — class
		// docblock「コンテンツ種別は「入口」で決める」）。この投稿に既に
		// 種別が保存されていれば、送信された値に関わらずそれを維持する
		// （クラフトされたリクエストで既存投稿の種別が書き換わるのを防ぐ
		// 意味も兼ねる）。まだ何も保存されていない(=作成時の最初の保存)場合
		// だけ、送信値(作成時にリンクしたURLのクエリ文字列由来)を採用する。
		$existing_kind = get_post_meta( $post_id, Post_Type::META_KIND, true );

		if ( in_array( $existing_kind, array( Post_Type::KIND_PERSON_GREETING, Post_Type::KIND_TERMS, Post_Type::KIND_FOLDER ), true ) ) {
			$kind = $existing_kind;
		} else {
			$kind = isset( $_POST[ Post_Type::QUERY_VAR_KIND ] ) ? sanitize_key( wp_unslash( $_POST[ Post_Type::QUERY_VAR_KIND ] ) ) : Post_Type::KIND_FREE;

			if ( ! in_array( $kind, array( Post_Type::KIND_PERSON_GREETING, Post_Type::KIND_TERMS, Post_Type::KIND_FOLDER ), true ) ) {
				$kind = Post_Type::KIND_FREE;
			}
		}

		update_post_meta( $post_id, Post_Type::META_KIND, $kind );

		// 対象者はkindに関わらず全種別共通で保存する（誰向けの情報かという
		// 分類であり、コンテンツ種別とは独立した別概念のため）。
		//
		// 旧「親コンテンツ」（META_PARENT_ID、サイト階層を作るための汎用の
		// 親子関係）はこのメタボックスからはもう読み書きしない — サイト上の
		// 掲載場所はMenu Structureだけが決める、という方針変更のため（class
		// docblock参照）。この画面のフォームに該当フィールド自体が存在しない
		// ため、既存投稿に保存済みのMETA_PARENT_IDへは一切触れず、そのまま
		// 保持される（後方互換）。
		$audience = isset( $_POST['alumni_content_audience'] ) ? sanitize_key( wp_unslash( $_POST['alumni_content_audience'] ) ) : Post_Type::AUDIENCE_COMMON;

		if ( ! in_array( $audience, array( Post_Type::AUDIENCE_ALUMNI, Post_Type::AUDIENCE_STUDENT ), true ) ) {
			$audience = Post_Type::AUDIENCE_COMMON;
		}

		update_post_meta( $post_id, Post_Type::META_AUDIENCE, $audience );

		if ( Post_Type::KIND_PERSON_GREETING === $kind ) {
			$name      = isset( $_POST['alumni_person_name'] ) ? sanitize_text_field( wp_unslash( $_POST['alumni_person_name'] ) ) : '';
			$kana      = isset( $_POST['alumni_person_kana'] ) ? sanitize_text_field( wp_unslash( $_POST['alumni_person_kana'] ) ) : '';
			$title     = isset( $_POST['alumni_person_title'] ) ? sanitize_text_field( wp_unslash( $_POST['alumni_person_title'] ) ) : '';
			$tenure    = isset( $_POST['alumni_person_tenure'] ) ? sanitize_text_field( wp_unslash( $_POST['alumni_person_tenure'] ) ) : '';
			$term      = isset( $_POST['alumni_person_term'] ) ? self::sanitize_term( wp_unslash( $_POST['alumni_person_term'] ) ) : '';
			$photo     = isset( $_POST['alumni_person_photo_id'] ) ? Settings::sanitize_attachment_id( wp_unslash( $_POST['alumni_person_photo_id'] ) ) : 0;
			$raw_group = isset( $_POST['alumni_person_greeting_group_id'] ) ? sanitize_text_field( wp_unslash( $_POST['alumni_person_greeting_group_id'] ) ) : '';
			$group_id  = ( '' !== $raw_group && null !== Person_Greeting_Groups::instance()->get_group( $raw_group ) ) ? $raw_group : '';

			update_post_meta( $post_id, Post_Type::META_PERSON_NAME, $name );
			update_post_meta( $post_id, Post_Type::META_PERSON_KANA, $kana );
			update_post_meta( $post_id, Post_Type::META_PERSON_TITLE, $title );

			if ( '' === $group_id ) {
				delete_post_meta( $post_id, Post_Type::META_PERSON_GREETING_GROUP_ID );
			} else {
				update_post_meta( $post_id, Post_Type::META_PERSON_GREETING_GROUP_ID, $group_id );
			}

			if ( '' === $tenure ) {
				delete_post_meta( $post_id, Post_Type::META_PERSON_TENURE );
			} else {
				update_post_meta( $post_id, Post_Type::META_PERSON_TENURE, $tenure );
			}

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
			$font_size      = isset( $_POST['alumni_terms_font_size'] ) ? sanitize_key( wp_unslash( $_POST['alumni_terms_font_size'] ) ) : Post_Type::TERMS_FONT_MEDIUM;

			if ( ! in_array( $font_size, array( Post_Type::TERMS_FONT_SMALL, Post_Type::TERMS_FONT_LARGE ), true ) ) {
				$font_size = Post_Type::TERMS_FONT_MEDIUM;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above (self::NONCE_NAME); each entry is validated by sanitize_date() below.
			$raw_revision_dates = isset( $_POST['alumni_terms_revision_dates'] ) && is_array( $_POST['alumni_terms_revision_dates'] ) ? wp_unslash( $_POST['alumni_terms_revision_dates'] ) : array();
			$revision_dates     = array();
			foreach ( $raw_revision_dates as $raw_revision_date ) {
				$validated = self::sanitize_date( $raw_revision_date );
				if ( $validated && ! in_array( $validated, $revision_dates, true ) ) {
					$revision_dates[] = $validated;
				}
			}
			sort( $revision_dates );

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

			// META_TERMS_REVISED_DATE（旧・単一の改定日）はもう保存しない —
			// 新しい改定履歴（配列）が唯一の書き込み先になる。既存サイトの
			// 旧フィールドの値はここでは一切読み書き・削除しない
			// （Post_Type::get_terms_revision_dates()が読み取り専用の
			// フォールバックとして扱う）。
			if ( empty( $revision_dates ) ) {
				delete_post_meta( $post_id, Post_Type::META_TERMS_REVISION_DATES );
			} else {
				update_post_meta( $post_id, Post_Type::META_TERMS_REVISION_DATES, $revision_dates );
			}

			update_post_meta( $post_id, Post_Type::META_TERMS_FONT_SIZE, $font_size );

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
		delete_post_meta( $post_id, Post_Type::META_PERSON_GREETING_GROUP_ID );
		delete_post_meta( $post_id, Post_Type::META_PERSON_TENURE );
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
		delete_post_meta( $post_id, Post_Type::META_TERMS_REVISION_DATES );
		delete_post_meta( $post_id, Post_Type::META_TERMS_FONT_SIZE );
	}

	/**
	 * Writes the 本文 textarea into post_content (every kind except 規約類
	 * — see class docblock), and (規約類のみ) 表示順 into menu_order.
	 * Hooked to wp_insert_post_data at a lower priority number than
	 * Content_Required_Fields::enforce(), same reasoning as
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

		$kind = isset( $_POST[ Post_Type::QUERY_VAR_KIND ] ) ? sanitize_key( wp_unslash( $_POST[ Post_Type::QUERY_VAR_KIND ] ) ) : Post_Type::KIND_FREE;

		// 規約類は本文をWordPress標準のブロックエディターで編集する
		// (Post_Type::maybe_use_block_editor()参照)ため、この
		// リクエストにはalumni_content_bodyテキストエリア自体が存在しない
		// （render()が規約類の場合は描画しない）。$dataのpost_contentには
		// すでにWordPress自身がブロックエディターの送信値を正しく入れて
		// いるので、ここで空文字などに上書きしない。
		if ( Post_Type::KIND_TERMS !== $kind ) {
			$content = isset( $_POST['alumni_content_body'] ) ? sanitize_textarea_field( wp_unslash( $_POST['alumni_content_body'] ) ) : '';

			$data['post_content'] = wp_slash( $content );
		}

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
