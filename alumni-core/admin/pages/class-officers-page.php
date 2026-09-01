<?php
/**
 * 同窓会 > 役員・理事紹介 screen.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Admin\Pages;

use AlumniCore\Admin\Admin;
use AlumniCore\Includes\Officer_Lists;
use AlumniCore\Includes\Officers_Shortcode;
use AlumniCore\Includes\Modules\Content\Post_Type as Content_Post_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 役員・理事情報は複数の「一覧」（Officer_Lists）に分かれているため、この
 * 画面も二段構え:
 *
 *  - ?page=alumni-core-officers （list パラメータなし）: 一覧の一覧
 *    （名称・操作）＋「新しい一覧を作成」フォーム。
 *  - ?page=alumni-core-officers&list=<id>: その一覧の編集画面 —
 *    一覧名／公開タイトル／肩書見出しの3フィールドと、
 *    <table>/wp-list-table を使わないCSS Gridの役員入力表（add/remove/
 *    reorder rows client-side, officers-admin.js）を1つのフォームにまとめ、
 *    まとめて保存する。
 */
class Officers_Page {

	/**
	 * Submenu slug.
	 */
	const SLUG = 'alumni-core-officers';

	/**
	 * Nonce actions/names.
	 */
	const NONCE_ACTION_CREATE = 'alumni_core_create_officer_list';
	const NONCE_ACTION_DELETE = 'alumni_core_delete_officer_list';
	const NONCE_ACTION_SAVE   = 'alumni_core_save_officer_list';

	/**
	 * Renders the screen: the list index, or one list's edit screen when
	 * ?list= is present and resolves to a real list.
	 */
	public function render() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only navigation, nothing is written.
		$list_id = isset( $_GET['list'] ) ? sanitize_key( wp_unslash( $_GET['list'] ) ) : '';
		$list    = '' !== $list_id ? Officer_Lists::instance()->get_list( $list_id ) : null;

		if ( null !== $list ) {
			$this->render_list_editor( $list );
			return;
		}

		$this->render_index();
	}

	/**
	 * Renders the list-of-lists screen.
	 */
	private function render_index() {
		$lists = Officer_Lists::instance()->get_all();
		?>
		<div class="wrap alumni-core-officers">
			<h1><?php esc_html_e( '役員・理事紹介', 'alumni-core' ); ?></h1>

			<?php if ( isset( $_GET['updated'] ) && 'true' === $_GET['updated'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only status flag. ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( '保存しました。', 'alumni-core' ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( isset( $_GET['deleted'] ) && 'true' === $_GET['deleted'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( '一覧を削除しました。', 'alumni-core' ); ?></p>
				</div>
			<?php endif; ?>

			<p><?php esc_html_e( '役員・理事の一覧は複数作成できます（例：2026年度役員／2026年度理事／歴代会長）。一覧ごとに独立した公開ページを持ちます。', 'alumni-core' ); ?></p>

			<?php if ( empty( $lists ) ) : ?>
				<p class="description"><?php esc_html_e( 'まだ一覧がありません。下のフォームから作成してください。', 'alumni-core' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped alumni-officer-lists-table">
					<thead>
						<tr>
							<th><?php esc_html_e( '一覧名', 'alumni-core' ); ?></th>
							<th><?php esc_html_e( '役員数', 'alumni-core' ); ?></th>
							<th><?php esc_html_e( '公開URL', 'alumni-core' ); ?></th>
							<th><?php esc_html_e( '操作', 'alumni-core' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $lists as $list ) : ?>
							<?php $edit_url = add_query_arg( array( 'page' => self::SLUG, 'list' => $list['list_id'] ), admin_url( 'admin.php' ) ); ?>
							<?php $public_url = Officers_Shortcode::get_list_url( $list['list_id'] ); ?>
							<tr>
								<td>
									<a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $list['name'] ); ?></a>
								</td>
								<td><?php echo esc_html( count( $list['rows'] ) ); ?></td>
								<td>
									<?php if ( $public_url ) : ?>
										<a href="<?php echo esc_url( $public_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $public_url ); ?></a>
									<?php else : ?>
										<?php esc_html_e( '（次回のページ読み込み時に作成されます）', 'alumni-core' ); ?>
									<?php endif; ?>
								</td>
								<td>
									<a class="button" href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( '編集', 'alumni-core' ); ?></a>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="alumni-officer-list-delete-form" onsubmit="return confirm('<?php echo esc_js( __( 'この一覧を削除します。よろしいですか？', 'alumni-core' ) ); ?>');">
										<input type="hidden" name="action" value="alumni_core_delete_officer_list" />
										<input type="hidden" name="list_id" value="<?php echo esc_attr( $list['list_id'] ); ?>" />
										<?php wp_nonce_field( self::NONCE_ACTION_DELETE ); ?>
										<button type="submit" class="button button-link-delete"><?php esc_html_e( '削除', 'alumni-core' ); ?></button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<h2><?php esc_html_e( '新しい一覧を作成', 'alumni-core' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="alumni_core_create_officer_list" />
				<?php wp_nonce_field( self::NONCE_ACTION_CREATE ); ?>
				<p>
					<label for="alumni-officer-list-new-name"><?php esc_html_e( '一覧名', 'alumni-core' ); ?></label><br />
					<input type="text" id="alumni-officer-list-new-name" name="name" class="regular-text" placeholder="<?php echo esc_attr__( '例：2026年度役員', 'alumni-core' ); ?>" required="required" />
				</p>
				<?php submit_button( __( '＋ 新しい一覧を作成', 'alumni-core' ), 'secondary' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Renders one list's edit screen.
	 *
	 * @param array $list Full list (including 'rows'), from
	 *                      Officer_Lists::get_list().
	 */
	private function render_list_editor( array $list ) {
		$greeting_options = self::person_greeting_options();
		$title_heading    = $list['title_heading'] ? $list['title_heading'] : Officer_Lists::DEFAULT_TITLE_HEADING;
		?>
		<div class="wrap alumni-core-officers">
			<h1>
				<?php echo esc_html( $list['name'] ); ?>
				<a href="<?php echo esc_url( add_query_arg( array( 'page' => self::SLUG ), admin_url( 'admin.php' ) ) ); ?>" class="page-title-action"><?php esc_html_e( '一覧一覧へ戻る', 'alumni-core' ); ?></a>
			</h1>

			<?php if ( isset( $_GET['updated'] ) && 'true' === $_GET['updated'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only status flag. ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( '保存しました。', 'alumni-core' ); ?></p>
				</div>
			<?php endif; ?>

			<p><?php esc_html_e( '肩書・委員会・備考は自由入力です（同窓会ごとに構成が異なるため、選択肢は固定していません）。', 'alumni-core' ); ?></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="alumni-core-officers-form">
				<input type="hidden" name="action" value="alumni_core_save_officer_list" />
				<input type="hidden" name="list_id" value="<?php echo esc_attr( $list['list_id'] ); ?>" />
				<?php wp_nonce_field( self::NONCE_ACTION_SAVE ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="alumni-officer-list-name"><?php esc_html_e( '一覧名（管理用）', 'alumni-core' ); ?></label></th>
						<td><input type="text" id="alumni-officer-list-name" name="list_name" class="regular-text" value="<?php echo esc_attr( $list['name'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="alumni-officer-list-title"><?php esc_html_e( '公開タイトル', 'alumni-core' ); ?></label></th>
						<td>
							<input type="text" id="alumni-officer-list-title" name="list_title" class="regular-text" value="<?php echo esc_attr( $list['title'] ); ?>" />
							<p class="description"><?php esc_html_e( '公開ページに表示される見出しです（未入力の場合は一覧名を使用します）。', 'alumni-core' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="alumni-officer-list-title-heading"><?php esc_html_e( '肩書の見出し', 'alumni-core' ); ?></label></th>
						<td>
							<input type="text" id="alumni-officer-list-title-heading" name="list_title_heading" class="regular-text" value="<?php echo esc_attr( $title_heading ); ?>" placeholder="<?php echo esc_attr__( '例：肩書、役職、役名、ポジション', 'alumni-core' ); ?>" />
							<p class="description"><?php esc_html_e( '下の入力表・公開ページの両方で「肩書」列の見出しとして使われます。', 'alumni-core' ); ?></p>
						</td>
					</tr>
				</table>

				<?php
				/*
				 * table / wp-list-table を使わず、CSS Grid の<div>で組む。
				 * wp-list-table・widefat・fixed・striped はWordPress core側の
				 * 表組みスタイルを伴うため、各列に指定した幅がそれらと競合し、
				 * 「画面には空白があるのに入力欄だけ狭い」状態を繰り返す原因に
				 * なっていた。CSS Gridなら各列の幅はこのCSSだけで完結して決まり、
				 * fr単位で余白まで含めて画面の使える横幅いっぱいに配分される
				 * （admin.cssの.alumni-officers-grid参照）。
				 *
				 * 行（.alumni-officers-row）は display: contents にして、
				 * 見た目上のボックスを持たせずに中の各セルだけを親の
				 * .alumni-officers-grid（実際のgridコンテナ）の直接の
				 * グリッドアイテムにする — こうすることで、行をまたいでも
				 * 各列がずれずに揃う。行の追加・削除・並び替え自体は
				 * officers-admin.js が .alumni-officers-row をひとつの
				 * ノードとして操作するだけなので、列構成の変更や項目追加が
				 * あってもJS側の変更は不要。
				 */
				?>
				<h2><?php esc_html_e( '役員・理事', 'alumni-core' ); ?></h2>
				<div class="alumni-officers-grid-wrap">
					<div class="alumni-officers-grid" id="alumni-officers-list">
						<?php foreach ( $list['rows'] as $index => $officer ) : ?>
							<?php $this->render_row( $index, $officer, $greeting_options, $title_heading ); ?>
						<?php endforeach; ?>
					</div>
				</div>
				<template id="alumni-officers-row-template">
					<?php $this->render_row( '__INDEX__', array(), $greeting_options, $title_heading ); ?>
				</template>

				<p class="description">
					<?php esc_html_e( '「人物紹介ページ」は、氏名をクリックした先に表示する人物挨拶コンテンツを選べる項目です。（リンクなし）を選ぶと、その役員の氏名にはリンクが付きません。選択肢は「コンテンツ名（氏名）」の形式で表示されます（例：会長挨拶（山田太郎））。', 'alumni-core' ); ?>
				</p>

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
	 * Renders one officer row as a set of CSS Grid cells (see
	 * render_list_editor()'s docblock comment for why this is <div>
	 * markup, not a <table> row). Also used (with an empty $officer array)
	 * as the JS `<template>` for newly-added rows.
	 *
	 * Field order follows the current spec: 肩書, 氏名, 卒業期, 委員会,
	 * 備考, 人物紹介ページ, 操作. Every field carries its own
	 * always-visible label (not just aria-label) directly above the
	 * input, so which box is for what is clear at a glance without
	 * relying on a header row that can scroll out of view once the list
	 * is long.
	 *
	 * @param int|string $index            0-based row position, or the
	 *                                       '__INDEX__' placeholder used by
	 *                                       the JS template.
	 * @param array      $officer          Officer row (see Officer_Lists
	 *                                       class docblock), or [] for a
	 *                                       new row.
	 * @param array      $greeting_options  post_id => title, from
	 *                                       self::person_greeting_options().
	 * @param string     $title_heading     This list's configured 肩書
	 *                                       column label.
	 */
	private function render_row( $index, array $officer, array $greeting_options, $title_heading ) {
		$row_id    = isset( $officer['row_id'] ) ? $officer['row_id'] : '';
		$title     = isset( $officer['title'] ) ? $officer['title'] : '';
		$name      = isset( $officer['name'] ) ? $officer['name'] : '';
		$term      = isset( $officer['term'] ) ? $officer['term'] : '';
		$committee = isset( $officer['committee'] ) ? $officer['committee'] : '';
		$remarks   = isset( $officer['remarks'] ) ? $officer['remarks'] : '';
		$linked_id = isset( $officer['linked_content_id'] ) ? (int) $officer['linked_content_id'] : 0;
		?>
		<div class="alumni-officers-row" data-index="<?php echo esc_attr( $index ); ?>">
			<input type="hidden" name="officers[<?php echo esc_attr( $index ); ?>][row_id]" class="alumni-officers-row-id" value="<?php echo esc_attr( $row_id ); ?>" />

			<div class="alumni-officers-grid-cell alumni-officers-col-title">
				<label class="alumni-officers-field-label">
					<span class="alumni-officers-field-label-text"><?php echo esc_html( $title_heading ); ?></span>
					<input type="text" class="alumni-officers-field-input" name="officers[<?php echo esc_attr( $index ); ?>][title]"
						value="<?php echo esc_attr( $title ); ?>" placeholder="<?php echo esc_attr__( '例：会長', 'alumni-core' ); ?>" />
				</label>
			</div>

			<div class="alumni-officers-grid-cell alumni-officers-col-name">
				<label class="alumni-officers-field-label">
					<span class="alumni-officers-field-label-text"><?php esc_html_e( '氏名', 'alumni-core' ); ?></span>
					<input type="text" class="alumni-officers-field-input" name="officers[<?php echo esc_attr( $index ); ?>][name]"
						value="<?php echo esc_attr( $name ); ?>" placeholder="<?php echo esc_attr__( '例：山田 太郎', 'alumni-core' ); ?>" />
				</label>
			</div>

			<div class="alumni-officers-grid-cell alumni-officers-col-term">
				<label class="alumni-officers-field-label">
					<span class="alumni-officers-field-label-text"><?php esc_html_e( '卒業期', 'alumni-core' ); ?></span>
					<input type="number" inputmode="numeric" min="1" class="alumni-officers-field-input" name="officers[<?php echo esc_attr( $index ); ?>][term]"
						value="<?php echo esc_attr( $term ); ?>" placeholder="<?php echo esc_attr__( '例：12', 'alumni-core' ); ?>" />
				</label>
			</div>

			<div class="alumni-officers-grid-cell alumni-officers-col-committee">
				<label class="alumni-officers-field-label">
					<span class="alumni-officers-field-label-text"><?php esc_html_e( '委員会', 'alumni-core' ); ?></span>
					<input type="text" class="alumni-officers-field-input" name="officers[<?php echo esc_attr( $index ); ?>][committee]"
						value="<?php echo esc_attr( $committee ); ?>" placeholder="<?php echo esc_attr__( '例：総務委員会（なければ空欄）', 'alumni-core' ); ?>" />
				</label>
			</div>

			<div class="alumni-officers-grid-cell alumni-officers-col-remarks">
				<label class="alumni-officers-field-label">
					<span class="alumni-officers-field-label-text"><?php esc_html_e( '備考', 'alumni-core' ); ?></span>
					<input type="text" class="alumni-officers-field-input" name="officers[<?php echo esc_attr( $index ); ?>][remarks]"
						value="<?php echo esc_attr( $remarks ); ?>" placeholder="<?php echo esc_attr__( '例：母校校長、前会長、名誉会長、顧問', 'alumni-core' ); ?>" />
				</label>
			</div>

			<div class="alumni-officers-grid-cell alumni-officers-col-link">
				<label class="alumni-officers-field-label">
					<span class="alumni-officers-field-label-text"><?php esc_html_e( '人物紹介ページ', 'alumni-core' ); ?></span>
					<select class="alumni-officers-field-input" name="officers[<?php echo esc_attr( $index ); ?>][linked_content_id]">
						<option value="0"><?php esc_html_e( '（リンクなし）', 'alumni-core' ); ?></option>
						<?php foreach ( $greeting_options as $content_id => $content_label ) : ?>
							<option value="<?php echo esc_attr( $content_id ); ?>" <?php selected( $content_id, $linked_id ); ?>>
								<?php echo esc_html( $content_label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</label>
			</div>

			<div class="alumni-officers-grid-cell alumni-officers-col-actions">
				<span class="alumni-officers-field-label-text"><?php esc_html_e( '操作', 'alumni-core' ); ?></span>
				<div class="alumni-officers-actions-buttons">
					<button type="button" class="button alumni-officers-move-up" title="<?php echo esc_attr__( '上へ移動', 'alumni-core' ); ?>">↑</button>
					<button type="button" class="button alumni-officers-move-down" title="<?php echo esc_attr__( '下へ移動', 'alumni-core' ); ?>">↓</button>
					<button type="button" class="button alumni-officers-remove"><?php esc_html_e( '削除', 'alumni-core' ); ?></button>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * 人物紹介ページ dropdown options: every 人物挨拶 content post,
	 * regardless of publish status (an admin linking an officer while both
	 * are still drafts is a normal editing order, not an error) — the
	 * public-facing link itself is still resolved/hidden safely at read
	 * time by Officer_Lists::is_valid_linked_content() and the
	 * theme-facing alumni_core_get_officer_link_url() API.
	 *
	 * Each label combines the コンテンツ名 (free-form, e.g. "会長挨拶")
	 * with the 氏名 (e.g. "会長挨拶（山田太郎）") so an admin can tell
	 * multiple similarly-named entries apart without opening each one.
	 *
	 * @return array post_id => display label.
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
			$title = $post->post_title ? $post->post_title : sprintf( '#%d', $post->ID );
			$name  = Content_Post_Type::get_person_name( $post );

			$options[ $post->ID ] = $name ? sprintf( '%s（%s）', $title, $name ) : $title;
		}

		return $options;
	}

	/**
	 * Handles 「新しい一覧を作成」 (admin_post_alumni_core_create_officer_list).
	 */
	public function handle_create() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			wp_die( esc_html__( 'この操作を行う権限がありません。', 'alumni-core' ) );
		}

		check_admin_referer( self::NONCE_ACTION_CREATE );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above; sanitized by create_list().
		$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';

		$list_id = Officer_Lists::instance()->create_list( $name );

		// Creates the new 一覧のページ immediately (rather than waiting
		// for the next admin_init sweep) so the admin can click through to
		// the public page right away.
		Officers_Shortcode::maybe_create_pages();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page' => Officers_Page::SLUG,
					'list' => $list_id,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handles 一覧の削除 (admin_post_alumni_core_delete_officer_list).
	 */
	public function handle_delete() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			wp_die( esc_html__( 'この操作を行う権限がありません。', 'alumni-core' ) );
		}

		check_admin_referer( self::NONCE_ACTION_DELETE );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above.
		$list_id = isset( $_POST['list_id'] ) ? sanitize_key( wp_unslash( $_POST['list_id'] ) ) : '';

		if ( '' !== $list_id ) {
			Officer_Lists::instance()->delete_list( $list_id );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => self::SLUG,
					'deleted' => 'true',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handles the list edit form submission
	 * (admin_post_alumni_core_save_officer_list): both the list's own
	 * metadata (name/title/title_heading) and its full row set are saved
	 * together, since the edit screen presents them as one form.
	 */
	public function handle_save() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			wp_die( esc_html__( 'この操作を行う権限がありません。', 'alumni-core' ) );
		}

		check_admin_referer( self::NONCE_ACTION_SAVE );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above.
		$list_id = isset( $_POST['list_id'] ) ? sanitize_key( wp_unslash( $_POST['list_id'] ) ) : '';

		if ( '' === $list_id || null === Officer_Lists::instance()->get_list( $list_id ) ) {
			wp_die( esc_html__( '指定された一覧が見つかりません。', 'alumni-core' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above; sanitized by save_list_meta()/save_list_rows().
		$list_name     = isset( $_POST['list_name'] ) ? wp_unslash( $_POST['list_name'] ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$list_title    = isset( $_POST['list_title'] ) ? wp_unslash( $_POST['list_title'] ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$title_heading = isset( $_POST['list_title_heading'] ) ? wp_unslash( $_POST['list_title_heading'] ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$raw_rows      = isset( $_POST['officers'] ) ? $_POST['officers'] : array();

		Officer_Lists::instance()->save_list_meta( $list_id, $list_name, $list_title, $title_heading );
		Officer_Lists::instance()->save_list_rows( $list_id, $raw_rows );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => self::SLUG,
					'list'    => $list_id,
					'updated' => 'true',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
