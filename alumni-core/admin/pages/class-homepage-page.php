<?php
/**
 * 同窓会 > トップページ設定 screen.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Admin\Pages;

use AlumniCore\Admin\Admin;
use AlumniCore\Includes\Homepage_Sections;
use AlumniCore\Includes\Content_Hierarchy;
use AlumniCore\Includes\Person_Greeting_Groups;
use AlumniCore\Includes\Modules\Content\Post_Type as Content_Post_Type;
use AlumniCore\Includes\Modules\Forms\Post_Type as Form_Post_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * トップページは「テーマが用意したスロットに、管理者がコンテンツを選ぶ」
 * 方式（docs/top-page-slot-based-layout-design.md）。この画面はセクション
 * （見出し・表示数・表示方向・各スロット）の管理を一括で行う:
 *
 *  - セクションの追加／削除／並び替えは、それぞれ即時実行の小さな
 *    フォーム（Officers_Pageの一覧作成／削除と同じパターン）。
 *  - 見出し・表示数・表示方向・各スロットの内容は、全セクションをまとめて1つの
 *    フォームで保存する（セクション同士の並び順を保ったまま一括更新する
 *    ほうが分かりやすいため）。
 */
class Homepage_Page {

	/**
	 * Submenu slug.
	 */
	const SLUG = 'alumni-core-homepage';

	/**
	 * Nonce actions/names.
	 */
	const NONCE_ACTION_CREATE = 'alumni_core_create_homepage_section';
	const NONCE_ACTION_DELETE = 'alumni_core_delete_homepage_section';
	const NONCE_ACTION_MOVE   = 'alumni_core_move_homepage_section';
	const NONCE_ACTION_SAVE   = 'alumni_core_save_homepage_sections';

	/**
	 * Renders the screen.
	 */
	public function render() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			return;
		}

		$sections = Homepage_Sections::instance()->get_all();
		?>
		<div class="wrap alumni-core-homepage">
			<h1><?php esc_html_e( 'トップページ設定', 'alumni-core' ); ?></h1>

			<?php if ( isset( $_GET['updated'] ) && 'true' === $_GET['updated'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only status flag. ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( '保存しました。', 'alumni-core' ); ?></p>
				</div>
			<?php endif; ?>

			<p><?php esc_html_e( 'トップページは複数の「セクション」で構成されます。各セクションごとに表示する項目数（1〜20件）と、横並び／縦並びの表示方向を選びます。各項目はコンテンツリンクまたは任意テキストの見出しとして設定でき、インデントも指定できます。実際の見た目（カードの形など）はテーマ側のデザインに従います。', 'alumni-core' ); ?></p>

			<?php if ( empty( $sections ) ) : ?>
				<p class="description"><?php esc_html_e( 'まだセクションがありません。下のボタンから追加してください。', 'alumni-core' ); ?></p>
			<?php else : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="alumni-core-homepage-form">
					<input type="hidden" name="action" value="alumni_core_save_homepage_sections" />
					<?php wp_nonce_field( self::NONCE_ACTION_SAVE ); ?>

					<?php foreach ( $sections as $position => $section ) : ?>
						<fieldset class="alumni-homepage-section">
							<legend>
								<?php
								printf(
									/* translators: %d: 1-based section position */
									esc_html__( 'セクション %d', 'alumni-core' ),
									(int) $position + 1
								);
								?>
							</legend>

							<p>
								<label>
									<?php esc_html_e( '見出し（任意）', 'alumni-core' ); ?><br />
									<input type="text" class="regular-text" name="sections[<?php echo esc_attr( $section['section_id'] ); ?>][heading]" value="<?php echo esc_attr( $section['heading'] ); ?>" placeholder="<?php echo esc_attr__( '例：同窓会からのメッセージ（空欄も可）', 'alumni-core' ); ?>" />
								</label>
							</p>

							<p>
								<label>
									<?php esc_html_e( '表示数', 'alumni-core' ); ?><br />
									<select class="alumni-homepage-columns" name="sections[<?php echo esc_attr( $section['section_id'] ); ?>][columns]">
										<?php for ( $columns = Homepage_Sections::MIN_COLUMNS; $columns <= Homepage_Sections::MAX_COLUMNS; $columns++ ) : ?>
											<option value="<?php echo esc_attr( $columns ); ?>" <?php selected( $columns, $section['columns'] ); ?>>
												<?php
												printf(
													/* translators: %d: number of slots */
													esc_html__( '%d件', 'alumni-core' ),
													$columns
												);
												?>
											</option>
										<?php endfor; ?>
									</select>
									<p class="description"><?php esc_html_e( '表示数を変更して保存すると、増えた項目は未設定、減った項目は削除されます。', 'alumni-core' ); ?></p>
								</label>
							</p>

							<p>
								<label>
									<?php esc_html_e( '表示方向', 'alumni-core' ); ?><br />
									<select name="sections[<?php echo esc_attr( $section['section_id'] ); ?>][layout]">
										<option value="<?php echo esc_attr( Homepage_Sections::LAYOUT_HORIZONTAL ); ?>" <?php selected( Homepage_Sections::LAYOUT_HORIZONTAL, isset( $section['layout'] ) ? $section['layout'] : Homepage_Sections::LAYOUT_HORIZONTAL ); ?>><?php esc_html_e( '横並び', 'alumni-core' ); ?></option>
										<option value="<?php echo esc_attr( Homepage_Sections::LAYOUT_VERTICAL ); ?>" <?php selected( Homepage_Sections::LAYOUT_VERTICAL, isset( $section['layout'] ) ? $section['layout'] : Homepage_Sections::LAYOUT_HORIZONTAL ); ?>><?php esc_html_e( '縦並び', 'alumni-core' ); ?></option>
									</select>
									<p class="description"><?php esc_html_e( '横並びは表示数に応じて横方向に配置し、縦並びは表示数に関係なく上から順に1件ずつ表示します。', 'alumni-core' ); ?></p>
								</label>
							</p>

							<div class="alumni-homepage-slots">
								<?php for ( $slot_index = 0; $slot_index < Homepage_Sections::MAX_COLUMNS; $slot_index++ ) : ?>
									<?php
									$slot = isset( $section['slots'][ $slot_index ] ) ? $section['slots'][ $slot_index ] : array( 'type' => 'none' );
									?>
									<div class="alumni-homepage-slot" data-slot-index="<?php echo esc_attr( $slot_index ); ?>"<?php echo $slot_index >= (int) $section['columns'] ? ' hidden' : ''; ?>>
										<div class="alumni-homepage-slot-label">
											<?php
											printf(
												/* translators: %d: 1-based slot position */
												esc_html__( '%d件目', 'alumni-core' ),
												(int) $slot_index + 1
											);
											?>
										</div>
										<?php $this->render_slot_select( "sections[{$section['section_id']}][slots][{$slot_index}]", $slot ); ?>
									</div>
								<?php endfor; ?>
							</div>
						</fieldset>

						<p class="alumni-homepage-section-actions">
							<?php if ( 0 !== $position ) : ?>
								<?php $this->render_move_button( $section['section_id'], 'up', __( '↑ 上へ', 'alumni-core' ) ); ?>
							<?php endif; ?>
							<?php if ( $position < count( $sections ) - 1 ) : ?>
								<?php $this->render_move_button( $section['section_id'], 'down', __( '↓ 下へ', 'alumni-core' ) ); ?>
							<?php endif; ?>
							<?php $this->render_delete_button( $section['section_id'] ); ?>
						</p>
					<?php endforeach; ?>

					<?php submit_button( __( 'すべてのセクションを保存', 'alumni-core' ) ); ?>
				</form>

				<?php
				// 並び替え／削除フォームは一括保存フォームの外に配置する。
				// HTMLでは form 要素の入れ子は許可されないため、各操作用フォームは
				// 独立させ、セクション内のボタンから form 属性で送信先を指定する。
				foreach ( $sections as $section ) {
					$this->render_section_action_forms( $section['section_id'] );
				}
				?>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="alumni_core_create_homepage_section" />
				<?php wp_nonce_field( self::NONCE_ACTION_CREATE ); ?>
				<?php submit_button( __( '＋ セクションを追加', 'alumni-core' ), 'secondary' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * セクション内に表示する並び替えボタン。
	 *
	 * ボタン本体は一括保存フォームの中に置くが、form 属性で外側の独立した
	 * 操作用フォームを送信するため、form の入れ子を作らない。
	 *
	 * @param string $section_id
	 * @param string $direction 'up' or 'down'.
	 * @param string $label
	 */
	private function render_move_button( $section_id, $direction, $label ) {
		$form_id = 'alumni-homepage-move-' . sanitize_html_class( $section_id . '-' . $direction );
		?>
		<button type="submit" class="button" form="<?php echo esc_attr( $form_id ); ?>"><?php echo esc_html( $label ); ?></button>
		<?php
	}

	/**
	 * セクション内に表示する削除ボタン。
	 *
	 * @param string $section_id
	 */
	private function render_delete_button( $section_id ) {
		$form_id = 'alumni-homepage-delete-' . sanitize_html_class( $section_id );
		?>
		<button type="submit" class="button button-link-delete" form="<?php echo esc_attr( $form_id ); ?>" onclick="return confirm('<?php echo esc_js( __( 'このセクションを削除します。よろしいですか？', 'alumni-core' ) ); ?>');"><?php esc_html_e( '削除', 'alumni-core' ); ?></button>
		<?php
	}

	/**
	 * セクション操作用の独立フォームを描画する。
	 *
	 * 一括保存フォームの外側に配置し、対応するボタンから form 属性で送信する。
	 *
	 * @param string $section_id
	 */
	private function render_section_action_forms( $section_id ) {
		$section_id = sanitize_key( $section_id );
		$up_form_id = 'alumni-homepage-move-' . sanitize_html_class( $section_id . '-up' );
		$down_form_id = 'alumni-homepage-move-' . sanitize_html_class( $section_id . '-down' );
		$delete_form_id = 'alumni-homepage-delete-' . sanitize_html_class( $section_id );
		?>
		<form id="<?php echo esc_attr( $up_form_id ); ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="alumni-homepage-move-form" hidden>
			<input type="hidden" name="action" value="alumni_core_move_homepage_section" />
			<input type="hidden" name="section_id" value="<?php echo esc_attr( $section_id ); ?>" />
			<input type="hidden" name="direction" value="up" />
			<?php wp_nonce_field( self::NONCE_ACTION_MOVE ); ?>
		</form>
		<form id="<?php echo esc_attr( $down_form_id ); ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="alumni-homepage-move-form" hidden>
			<input type="hidden" name="action" value="alumni_core_move_homepage_section" />
			<input type="hidden" name="section_id" value="<?php echo esc_attr( $section_id ); ?>" />
			<input type="hidden" name="direction" value="down" />
			<?php wp_nonce_field( self::NONCE_ACTION_MOVE ); ?>
		</form>
		<form id="<?php echo esc_attr( $delete_form_id ); ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="alumni-homepage-delete-form" hidden>
			<input type="hidden" name="action" value="alumni_core_delete_homepage_section" />
			<input type="hidden" name="section_id" value="<?php echo esc_attr( $section_id ); ?>" />
			<?php wp_nonce_field( self::NONCE_ACTION_DELETE ); ?>
		</form>
		<?php
	}

	/**
	 * Renders one スロット選択用の <select>: 未設定・システムページ・
	 * （対象者ごとにまとめた）公開済みコンテンツの階層ツリーをインデント
	 * 付きで一覧表示する — 巨大な単一プルダウンではあるが、階層をたどれる
	 * よう整理されている（ドリルダウン式の専用UIまでは今回のスコープでは
	 * 実装していない。詳しくは完了報告のスコープ注記を参照）。
	 *
	 * 選択値は "none" / "system:{key}" / "content:{post_id}" の3種類の
	 * 文字列で、handle_save() 側で解釈する。
	 *
	 * @param string $name         <select>のname属性。
	 * @param array  $current_slot Homepage_Sections正規化済みのスロット。
	 */
	private function render_slot_select( $name, array $current_slot ) {
		$current_value = 'none';
		$current_type  = ( isset( $current_slot['type'] ) && Homepage_Sections::SLOT_HEADING === $current_slot['type'] ) ? Homepage_Sections::SLOT_HEADING : 'link';
		$current_indent = isset( $current_slot['indent'] ) ? min( Homepage_Sections::MAX_INDENT_LEVEL, absint( $current_slot['indent'] ) ) : 0;
		$current_heading = isset( $current_slot['heading'] ) ? (string) $current_slot['heading'] : '';

		if ( 'system' === $current_slot['type'] ) {
			$current_value = 'system:' . $current_slot['system_key'];
		} elseif ( 'content' === $current_slot['type'] ) {
			$current_value = 'content:' . $current_slot['content_id'];
		} elseif ( 'form' === $current_slot['type'] ) {
			$current_value = 'form:' . $current_slot['form_id'];
		} elseif ( Homepage_Sections::SLOT_PERSON_GREETING_GROUP === $current_slot['type'] ) {
			$current_value = 'person_greeting_group:' . $current_slot['group_id'];
		}
		?>
		<p>
			<label>
				<?php esc_html_e( '項目種別', 'alumni-core' ); ?><br />
				<select class="alumni-homepage-slot-type" name="<?php echo esc_attr( $name ); ?>[type]">
					<option value="link" <?php selected( 'link', $current_type ); ?>><?php esc_html_e( 'コンテンツリンク', 'alumni-core' ); ?></option>
					<option value="<?php echo esc_attr( Homepage_Sections::SLOT_HEADING ); ?>" <?php selected( Homepage_Sections::SLOT_HEADING, $current_type ); ?>><?php esc_html_e( '見出し', 'alumni-core' ); ?></option>
				</select>
			</label>
		</p>
		<p class="alumni-homepage-slot-content-field">
			<label>
				<?php esc_html_e( 'コンテンツ', 'alumni-core' ); ?><br />
				<select name="<?php echo esc_attr( $name ); ?>[value]">
					<option value="none" <?php selected( 'none', $current_value ); ?>><?php esc_html_e( '（未設定）', 'alumni-core' ); ?></option>
					<?php
					$system_groups = array();
					$system_labels = Homepage_Sections::system_key_labels();
					$system_key_groups = Homepage_Sections::system_key_groups();

					foreach ( $system_labels as $system_key => $label ) {
						$group_label = isset( $system_key_groups[ $system_key ] ) && '' !== $system_key_groups[ $system_key ]
							? (string) $system_key_groups[ $system_key ]
							: __( 'システムページ', 'alumni-core' );

						if ( ! isset( $system_groups[ $group_label ] ) ) {
							$system_groups[ $group_label ] = array();
						}

						$system_groups[ $group_label ][ $system_key ] = $label;
					}
					?>
					<?php foreach ( $system_groups as $group_label => $group_items ) : ?>
						<optgroup label="<?php echo esc_attr( $group_label ); ?>">
							<?php foreach ( $group_items as $system_key => $label ) : ?>
								<?php $value = 'system:' . $system_key; ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $current_value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</optgroup>
					<?php endforeach; ?>
					<?php
					$form_posts = get_posts(
						array(
							'post_type'      => Form_Post_Type::SLUG,
							'post_status'    => 'publish',
							'posts_per_page' => -1,
							'orderby'        => 'menu_order title',
							'order'          => 'ASC',
						)
					);
					?>
					<?php if ( ! empty( $form_posts ) ) : ?>
						<optgroup label="<?php echo esc_attr__( 'フォーム', 'alumni-core' ); ?>">
							<?php foreach ( $form_posts as $form_post ) : ?>
								<?php $value = 'form:' . $form_post->ID; ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $current_value ); ?>><?php echo esc_html( $form_post->post_title ? $form_post->post_title : sprintf( '#%d', $form_post->ID ) ); ?></option>
							<?php endforeach; ?>
						</optgroup>
					<?php endif; ?>
					<?php $person_greeting_groups = Person_Greeting_Groups::instance()->get_all(); ?>
					<?php if ( ! empty( $person_greeting_groups ) ) : ?>
						<optgroup label="<?php echo esc_attr__( '人物挨拶グループ', 'alumni-core' ); ?>">
							<?php foreach ( $person_greeting_groups as $person_greeting_group ) : ?>
								<?php $value = 'person_greeting_group:' . $person_greeting_group['group_id']; ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $current_value ); ?>><?php echo esc_html( $person_greeting_group['name'] ); ?></option>
							<?php endforeach; ?>
						</optgroup>
					<?php endif; ?>
					<?php
					$audience_labels = array(
						Content_Post_Type::AUDIENCE_COMMON  => __( '共通', 'alumni-core' ),
						Content_Post_Type::AUDIENCE_ALUMNI  => __( '卒業生向け', 'alumni-core' ),
						Content_Post_Type::AUDIENCE_STUDENT => __( '在校生向け', 'alumni-core' ),
					);
					foreach ( $audience_labels as $audience_value => $audience_label ) :
						$tree = Content_Hierarchy::build_tree( $audience_value, false );
						if ( empty( $tree ) ) :
							continue;
						endif;
						?>
						<optgroup label="<?php echo esc_attr( $audience_label ); ?>">
							<?php $this->render_slot_option_nodes( $tree, $current_value, 0 ); ?>
						</optgroup>
					<?php endforeach; ?>
				</select>
			</label>
		</p>
		<p class="alumni-homepage-slot-heading-field">
			<label>
				<?php esc_html_e( '見出しテキスト', 'alumni-core' ); ?><br />
				<input type="text" class="regular-text" name="<?php echo esc_attr( $name ); ?>[heading]" value="<?php echo esc_attr( $current_heading ); ?>" placeholder="<?php echo esc_attr__( '見出しを直接入力', 'alumni-core' ); ?>" />
			</label>
			<span class="description"><?php esc_html_e( '項目種別が「見出し」の場合に使用します。', 'alumni-core' ); ?></span>
		</p>
		<p>
			<label>
				<?php esc_html_e( 'インデント', 'alumni-core' ); ?><br />
				<select name="<?php echo esc_attr( $name ); ?>[indent]">
					<?php for ( $indent = 0; $indent <= Homepage_Sections::MAX_INDENT_LEVEL; $indent++ ) : ?>
						<option value="<?php echo esc_attr( $indent ); ?>" <?php selected( $indent, $current_indent ); ?>>
							<?php echo esc_html( 0 === $indent ? __( 'なし', 'alumni-core' ) : sprintf( __( '%d段', 'alumni-core' ), $indent ) ); ?>
						</option>
					<?php endfor; ?>
				</select>
			</label>
		</p>
		<?php
	}

	/**
	 * @param array  $nodes         Content_Hierarchy::build_tree() shape
	 *                                (published-only).
	 * @param string $current_value 'none' / 'system:...' / 'content:{id}'.
	 * @param int    $depth
	 */
	private function render_slot_option_nodes( array $nodes, $current_value, $depth ) {
		foreach ( $nodes as $node ) {
			$post  = $node['post'];
			$value = 'content:' . $post->ID;
			$label = str_repeat( '— ', $depth ) . ( $post->post_title ? $post->post_title : sprintf( '#%d', $post->ID ) );

			// フォルダ自体には本文がなく、単なる階層見出しなのでスロットの
			// 実コンテンツとしては選べない(トップページに空のフォルダを
			// 置いても仕方がないため) — ただし、その子コンテンツは選べる。
			if ( ! Content_Post_Type::is_folder( $post ) ) {
				printf(
					'<option value="%1$s" %2$s>%3$s</option>',
					esc_attr( $value ),
					selected( $value, $current_value, false ),
					esc_html( $label )
				);
			}

			if ( ! empty( $node['children'] ) ) {
				$this->render_slot_option_nodes( $node['children'], $current_value, $depth + 1 );
			}
		}
	}

	/**
	 * Handles 「＋ セクションを追加」 (admin_post_alumni_core_create_homepage_section).
	 */
	public function handle_create() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			wp_die( esc_html__( 'この操作を行う権限がありません。', 'alumni-core' ) );
		}

		check_admin_referer( self::NONCE_ACTION_CREATE );

		Homepage_Sections::instance()->create_section();

		wp_safe_redirect( add_query_arg( array( 'page' => self::SLUG ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Handles セクションの削除
	 * (admin_post_alumni_core_delete_homepage_section).
	 */
	public function handle_delete() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			wp_die( esc_html__( 'この操作を行う権限がありません。', 'alumni-core' ) );
		}

		check_admin_referer( self::NONCE_ACTION_DELETE );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above.
		$section_id = isset( $_POST['section_id'] ) ? sanitize_key( wp_unslash( $_POST['section_id'] ) ) : '';

		if ( '' !== $section_id ) {
			Homepage_Sections::instance()->delete_section( $section_id );
		}

		wp_safe_redirect( add_query_arg( array( 'page' => self::SLUG ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Handles セクションの並び替え
	 * (admin_post_alumni_core_move_homepage_section).
	 */
	public function handle_move() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			wp_die( esc_html__( 'この操作を行う権限がありません。', 'alumni-core' ) );
		}

		check_admin_referer( self::NONCE_ACTION_MOVE );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above.
		$section_id = isset( $_POST['section_id'] ) ? sanitize_key( wp_unslash( $_POST['section_id'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$direction  = isset( $_POST['direction'] ) ? sanitize_key( wp_unslash( $_POST['direction'] ) ) : '';

		if ( '' !== $section_id && in_array( $direction, array( 'up', 'down' ), true ) ) {
			Homepage_Sections::instance()->move_section( $section_id, $direction );
		}

		wp_safe_redirect( add_query_arg( array( 'page' => self::SLUG ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Handles the全セクション一括保存
	 * (admin_post_alumni_core_save_homepage_sections).
	 */
	public function handle_save() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			wp_die( esc_html__( 'この操作を行う権限がありません。', 'alumni-core' ) );
		}

		check_admin_referer( self::NONCE_ACTION_SAVE );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above; every value is sanitized below before use.
		$submitted = isset( $_POST['sections'] ) && is_array( $_POST['sections'] ) ? wp_unslash( $_POST['sections'] ) : array();

		$sections = Homepage_Sections::instance();

		foreach ( $submitted as $section_id => $data ) {
			$section_id = sanitize_key( $section_id );

			if ( '' === $section_id || ! is_array( $data ) ) {
				continue;
			}

			$heading = isset( $data['heading'] ) ? sanitize_text_field( $data['heading'] ) : '';
			$columns = isset( $data['columns'] ) ? absint( $data['columns'] ) : Homepage_Sections::MIN_COLUMNS;
			$layout  = isset( $data['layout'] ) ? sanitize_key( $data['layout'] ) : Homepage_Sections::LAYOUT_HORIZONTAL;

			$updated = $sections->update_section_meta( $section_id, $heading, $columns, $layout );

			if ( null === $updated ) {
				continue;
			}

			$raw_slots = isset( $data['slots'] ) && is_array( $data['slots'] ) ? $data['slots'] : array();

			foreach ( $raw_slots as $slot_index => $raw_value ) {
				$sections->set_slot( $section_id, (int) $slot_index, self::parse_slot_value( is_array( $raw_value ) ? $raw_value : array( 'type' => 'link', 'value' => (string) $raw_value ) ) );
			}
		}

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

	/**
	 * Parses one slot's submitted fields into the array shape
	 * Homepage_Sections::set_slot() expects. Supports a content link or
	 * an administrator-entered heading, plus an indentation level.
	 *
	 * @param array $raw_value
	 * @return array
	 */
	private static function parse_slot_value( array $raw_value ) {
		$type    = isset( $raw_value['type'] ) ? sanitize_key( $raw_value['type'] ) : 'link';
		$indent  = isset( $raw_value['indent'] ) ? min( Homepage_Sections::MAX_INDENT_LEVEL, absint( $raw_value['indent'] ) ) : 0;
		$heading = isset( $raw_value['heading'] ) ? sanitize_text_field( $raw_value['heading'] ) : '';
		$value   = isset( $raw_value['value'] ) ? (string) $raw_value['value'] : 'none';

		if ( Homepage_Sections::SLOT_HEADING === $type ) {
			return array(
				'type'    => Homepage_Sections::SLOT_HEADING,
				'heading' => $heading,
				'indent'  => $indent,
			);
		}

		if ( 0 === strpos( $value, 'system:' ) ) {
			return array(
				'type'       => 'system',
				'system_key' => substr( $value, strlen( 'system:' ) ),
				'indent'     => $indent,
			);
		}

		if ( 0 === strpos( $value, 'person_greeting_group:' ) ) {
			return array(
				'type'     => Homepage_Sections::SLOT_PERSON_GREETING_GROUP,
				'group_id' => substr( $value, strlen( 'person_greeting_group:' ) ),
				'indent'   => $indent,
			);
		}

		if ( 0 === strpos( $value, 'form:' ) ) {
			return array(
				'type'    => 'form',
				'form_id' => absint( substr( $value, strlen( 'form:' ) ) ),
				'indent'  => $indent,
			);
		}

		if ( 0 === strpos( $value, 'content:' ) ) {
			return array(
				'type'       => 'content',
				'content_id' => absint( substr( $value, strlen( 'content:' ) ) ),
				'indent'     => $indent,
			);
		}

		return array( 'type' => 'none', 'indent' => 0 );
	}

}
