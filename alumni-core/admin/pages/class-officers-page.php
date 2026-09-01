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
				 * ノードとして操作するだけなので、テーブル行からdivへの
				 * 変更後もJS側の変更は不要。
				 */
				?>
				<div class="alumni-officers-grid-wrap">
					<div class="alumni-officers-grid" id="alumni-officers-list">
						<?php foreach ( $officers as $index => $officer ) : ?>
							<?php $this->render_row( $index, $officer, $greeting_options ); ?>
						<?php endforeach; ?>
					</div>
				</div>
				<template id="alumni-officers-row-template">
					<?php $this->render_row( '__INDEX__', array(), $greeting_options ); ?>
				</template>

				<p class="description">
					<?php esc_html_e( '「人物紹介・挨拶ページ」は、氏名をクリックした先に表示する人物挨拶コンテンツを選べる項目です。（リンクなし）を選ぶと、その役員の氏名にはリンクが付きません。選択肢は「コンテンツ名（氏名）」の形式で表示されます（例：会長挨拶（山田太郎））。', 'alumni-core' ); ?>
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
	 * Renders one officer row as a set of CSS Grid cells (see the render()
	 * docblock comment for why this is <div> markup, not a <table> row).
	 * Also used (with an empty $officer array) as the JS `<template>` for
	 * newly-added rows.
	 *
	 * Every field carries its own always-visible label (not just
	 * aria-label) directly above the input, so which box is for what is
	 * clear at a glance without relying on a header row that can scroll
	 * out of view once the list is long.
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
		<div class="alumni-officers-row" data-index="<?php echo esc_attr( $index ); ?>">
			<input type="hidden" name="officers[<?php echo esc_attr( $index ); ?>][row_id]" class="alumni-officers-row-id" value="<?php echo esc_attr( $row_id ); ?>" />

			<div class="alumni-officers-grid-cell alumni-officers-col-term">
				<label class="alumni-officers-field-label">
					<span class="alumni-officers-field-label-text"><?php esc_html_e( '卒業期', 'alumni-core' ); ?></span>
					<input type="number" inputmode="numeric" min="1" class="alumni-officers-field-input" name="officers[<?php echo esc_attr( $index ); ?>][term]"
						value="<?php echo esc_attr( $term ); ?>" placeholder="<?php echo esc_attr__( '例：12', 'alumni-core' ); ?>" />
				</label>
			</div>

			<div class="alumni-officers-grid-cell alumni-officers-col-title">
				<label class="alumni-officers-field-label">
					<span class="alumni-officers-field-label-text"><?php esc_html_e( '肩書', 'alumni-core' ); ?></span>
					<input type="text" class="alumni-officers-field-input" name="officers[<?php echo esc_attr( $index ); ?>][title]"
						value="<?php echo esc_attr( $title ); ?>" placeholder="<?php echo esc_attr__( '例：会長', 'alumni-core' ); ?>" />
				</label>
			</div>

			<div class="alumni-officers-grid-cell alumni-officers-col-committee">
				<label class="alumni-officers-field-label">
					<span class="alumni-officers-field-label-text"><?php esc_html_e( '委員会', 'alumni-core' ); ?></span>
					<input type="text" class="alumni-officers-field-input" name="officers[<?php echo esc_attr( $index ); ?>][committee]"
						value="<?php echo esc_attr( $committee ); ?>" placeholder="<?php echo esc_attr__( '例：総務委員会（なければ空欄）', 'alumni-core' ); ?>" />
				</label>
			</div>

			<div class="alumni-officers-grid-cell alumni-officers-col-name">
				<label class="alumni-officers-field-label">
					<span class="alumni-officers-field-label-text"><?php esc_html_e( '氏名', 'alumni-core' ); ?></span>
					<input type="text" class="alumni-officers-field-input" name="officers[<?php echo esc_attr( $index ); ?>][name]"
						value="<?php echo esc_attr( $name ); ?>" placeholder="<?php echo esc_attr__( '例：山田 太郎', 'alumni-core' ); ?>" />
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
	 * 人物紹介・挨拶ページ dropdown options: every 人物挨拶 content post,
	 * regardless of publish status (an admin linking an officer while both
	 * are still drafts is a normal editing order, not an error) — the
	 * public-facing link itself is still resolved/hidden safely at read
	 * time by Officers::is_valid_linked_content() and the theme-facing
	 * alumni_core_get_officer_link_url() API.
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
