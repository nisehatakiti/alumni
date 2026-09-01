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
use AlumniCore\Includes\Modules\Content\Post_Type as Content_Post_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * トップページは「テーマが用意したスロットに、管理者がコンテンツを選ぶ」
 * 方式（docs/top-page-slot-based-layout-design.md）。この画面はセクション
 * （見出し・段数・各段のスロット）の管理を一括で行う:
 *
 *  - セクションの追加／削除／並び替えは、それぞれ即時実行の小さな
 *    フォーム（Officers_Pageの一覧作成／削除と同じパターン）。
 *  - 見出し・段数・各スロットの内容は、全セクションをまとめて1つの
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

			<p><?php esc_html_e( 'トップページは複数の「セクション」で構成されます。各セクションは1〜3段のレイアウトを選び、各段に表示するコンテンツを選びます。実際の見た目（カードの形など）はテーマ側のデザインに従います。', 'alumni-core' ); ?></p>

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
									<?php esc_html_e( '段数', 'alumni-core' ); ?><br />
									<select name="sections[<?php echo esc_attr( $section['section_id'] ); ?>][columns]">
										<?php for ( $columns = Homepage_Sections::MIN_COLUMNS; $columns <= Homepage_Sections::MAX_COLUMNS; $columns++ ) : ?>
											<option value="<?php echo esc_attr( $columns ); ?>" <?php selected( $columns, $section['columns'] ); ?>>
												<?php
												printf(
													/* translators: %d: number of columns */
													esc_html__( '%d段', 'alumni-core' ),
													$columns
												);
												?>
											</option>
										<?php endfor; ?>
									</select>
									<p class="description"><?php esc_html_e( '段数を変更して保存すると、増えた段は未設定、減った段は削除されます。', 'alumni-core' ); ?></p>
								</label>
							</p>

							<div class="alumni-homepage-slots">
								<?php foreach ( $section['slots'] as $slot_index => $slot ) : ?>
									<div class="alumni-homepage-slot">
										<label>
											<?php
											printf(
												/* translators: %d: 1-based slot (column) position */
												esc_html__( '%d段目', 'alumni-core' ),
												(int) $slot_index + 1
											);
											?>
											<br />
											<?php $this->render_slot_select( "sections[{$section['section_id']}][slots][{$slot_index}]", $slot ); ?>
										</label>
									</div>
								<?php endforeach; ?>
							</div>
						</fieldset>

						<p class="alumni-homepage-section-actions">
							<?php if ( 0 !== $position ) : ?>
								<?php $this->render_move_form( $section['section_id'], 'up', __( '↑ 上へ', 'alumni-core' ) ); ?>
							<?php endif; ?>
							<?php if ( $position < count( $sections ) - 1 ) : ?>
								<?php $this->render_move_form( $section['section_id'], 'down', __( '↓ 下へ', 'alumni-core' ) ); ?>
							<?php endif; ?>
							<?php $this->render_delete_form( $section['section_id'] ); ?>
						</p>
					<?php endforeach; ?>

					<?php submit_button( __( 'すべてのセクションを保存', 'alumni-core' ) ); ?>
				</form>
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
	 * @param string $section_id
	 * @param string $direction 'up' or 'down'.
	 * @param string $label
	 */
	private function render_move_form( $section_id, $direction, $label ) {
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="alumni-homepage-move-form">
			<input type="hidden" name="action" value="alumni_core_move_homepage_section" />
			<input type="hidden" name="section_id" value="<?php echo esc_attr( $section_id ); ?>" />
			<input type="hidden" name="direction" value="<?php echo esc_attr( $direction ); ?>" />
			<?php wp_nonce_field( self::NONCE_ACTION_MOVE ); ?>
			<button type="submit" class="button"><?php echo esc_html( $label ); ?></button>
		</form>
		<?php
	}

	/**
	 * @param string $section_id
	 */
	private function render_delete_form( $section_id ) {
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="alumni-homepage-delete-form" onsubmit="return confirm('<?php echo esc_js( __( 'このセクションを削除します。よろしいですか？', 'alumni-core' ) ); ?>');">
			<input type="hidden" name="action" value="alumni_core_delete_homepage_section" />
			<input type="hidden" name="section_id" value="<?php echo esc_attr( $section_id ); ?>" />
			<?php wp_nonce_field( self::NONCE_ACTION_DELETE ); ?>
			<button type="submit" class="button button-link-delete"><?php esc_html_e( '削除', 'alumni-core' ); ?></button>
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
		if ( 'system' === $current_slot['type'] ) {
			$current_value = 'system:' . $current_slot['system_key'];
		} elseif ( 'content' === $current_slot['type'] ) {
			$current_value = 'content:' . $current_slot['content_id'];
		}
		?>
		<select name="<?php echo esc_attr( $name ); ?>">
			<option value="none" <?php selected( 'none', $current_value ); ?>><?php esc_html_e( '（未設定）', 'alumni-core' ); ?></option>
			<optgroup label="<?php echo esc_attr__( 'システムページ', 'alumni-core' ); ?>">
				<?php foreach ( Homepage_Sections::system_key_labels() as $system_key => $label ) : ?>
					<?php $value = 'system:' . $system_key; ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $current_value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</optgroup>
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

			$updated = $sections->update_section_meta( $section_id, $heading, $columns );

			if ( null === $updated ) {
				continue;
			}

			$raw_slots = isset( $data['slots'] ) && is_array( $data['slots'] ) ? $data['slots'] : array();

			foreach ( $raw_slots as $slot_index => $raw_value ) {
				$sections->set_slot( $section_id, (int) $slot_index, self::parse_slot_value( (string) $raw_value ) );
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
	 * Parses one スロットselectの送信値 ("none" / "system:{key}" /
	 * "content:{id}") into the array shape Homepage_Sections::set_slot()
	 * expects.
	 *
	 * @param string $raw_value
	 * @return array
	 */
	private static function parse_slot_value( $raw_value ) {
		if ( 0 === strpos( $raw_value, 'system:' ) ) {
			return array(
				'type'       => 'system',
				'system_key' => substr( $raw_value, strlen( 'system:' ) ),
			);
		}

		if ( 0 === strpos( $raw_value, 'content:' ) ) {
			return array(
				'type'       => 'content',
				'content_id' => absint( substr( $raw_value, strlen( 'content:' ) ) ),
			);
		}

		return array( 'type' => 'none' );
	}
}
