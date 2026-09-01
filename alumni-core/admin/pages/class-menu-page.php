<?php
/**
 * 同窓会 > メニュー構成 screen.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Admin\Pages;

use AlumniCore\Admin\Admin;
use AlumniCore\Includes\Menu_Structure;
use AlumniCore\Includes\Officer_Lists;
use AlumniCore\Includes\Homepage_Sections;
use AlumniCore\Includes\Content_Hierarchy;
use AlumniCore\Includes\Modules\Content\Post_Type as Content_Post_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 「メニューをサイト構造の中心にする」方針の管理画面。従来の
 * 「コンテンツを作り、その親子関係の結果としてメニューができる」
 * のではなく、まずここでメニューの構造（大カテゴリ→フォルダ→コンテンツ
 * へのリンク）を設計し、その中に既存のコンテンツ・システムページ・
 * 役員一覧を配置する。
 *
 * 一覧画面（全項目を階層インデント付きで表示、並び替え・階層変更・削除の
 * 即時実行フォーム）と、1項目の編集画面（?item=id、ラベル・参照先の変更）
 * の2段構え。
 */
class Menu_Page {

	/**
	 * Submenu slug.
	 */
	const SLUG = 'alumni-core-menu';

	/**
	 * Nonce actions.
	 */
	const NONCE_ACTION_CREATE_FOLDER  = 'alumni_core_create_menu_folder';
	const NONCE_ACTION_CREATE_CONTENT = 'alumni_core_create_menu_content';
	const NONCE_ACTION_UPDATE         = 'alumni_core_update_menu_item';
	const NONCE_ACTION_DELETE         = 'alumni_core_delete_menu_item';
	const NONCE_ACTION_MOVE           = 'alumni_core_move_menu_item';
	const NONCE_ACTION_INDENT         = 'alumni_core_indent_menu_item';
	const NONCE_ACTION_OUTDENT        = 'alumni_core_outdent_menu_item';

	/**
	 * Renders the screen: the item edit screen when ?item= is present and
	 * resolves to a real item, otherwise the full-tree index.
	 */
	public function render() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only navigation, nothing is written.
		$item_id = isset( $_GET['item'] ) ? sanitize_key( wp_unslash( $_GET['item'] ) ) : '';
		$item    = '' !== $item_id ? Menu_Structure::instance()->get_item( $item_id ) : null;

		if ( null !== $item ) {
			$this->render_item_editor( $item );
			return;
		}

		$this->render_index();
	}

	/**
	 * Renders the full メニュー構成 as an indented list, plus the
	 * ＋フォルダ／＋コンテンツ creation forms.
	 */
	private function render_index() {
		?>
		<div class="wrap alumni-core-menu">
			<h1><?php esc_html_e( 'メニュー構成', 'alumni-core' ); ?></h1>

			<?php if ( isset( $_GET['updated'] ) && 'true' === $_GET['updated'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only status flag. ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( '保存しました。', 'alumni-core' ); ?></p>
				</div>
			<?php endif; ?>

			<p><?php esc_html_e( 'サイトのメニュー・階層構造をここで設計します。まず「対象者」の下にフォルダやコンテンツへのリンクを配置し、必要に応じてフォルダの中にさらにフォルダやコンテンツを重ねてください。同じコンテンツを複数の場所に配置することもできます。', 'alumni-core' ); ?></p>

			<div class="alumni-menu-tree">
				<?php
				$audience_labels = array(
					Menu_Structure::AUDIENCE_ALUMNI  => __( '卒業生向け', 'alumni-core' ),
					Menu_Structure::AUDIENCE_STUDENT => __( '在校生向け', 'alumni-core' ),
					Menu_Structure::AUDIENCE_COMMON  => __( '共通', 'alumni-core' ),
				);
				foreach ( $audience_labels as $audience_value => $audience_label ) :
					$roots = Menu_Structure::instance()->get_children( '', $audience_value );
					?>
					<div class="alumni-menu-tree-audience">
						<h2><?php echo esc_html( $audience_label ); ?></h2>
						<?php if ( empty( $roots ) ) : ?>
							<p class="description"><?php esc_html_e( 'まだ項目がありません。', 'alumni-core' ); ?></p>
						<?php else : ?>
							<?php $this->render_item_rows( $roots, 0 ); ?>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>

			<h2><?php esc_html_e( '＋ フォルダを追加', 'alumni-core' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="alumni-menu-create-form">
				<input type="hidden" name="action" value="alumni_core_create_menu_folder" />
				<?php wp_nonce_field( self::NONCE_ACTION_CREATE_FOLDER ); ?>
				<p>
					<label><?php esc_html_e( 'フォルダ名', 'alumni-core' ); ?><br />
						<input type="text" name="label" class="regular-text" required="required" placeholder="<?php echo esc_attr__( '例：同窓会情報', 'alumni-core' ); ?>" />
					</label>
				</p>
				<p>
					<label><?php esc_html_e( '対象者（親を選ばない場合のみ使用）', 'alumni-core' ); ?><br />
						<?php $this->render_audience_select( 'audience' ); ?>
					</label>
				</p>
				<p>
					<label><?php esc_html_e( '親', 'alumni-core' ); ?><br />
						<select name="parent_id">
							<option value=""><?php esc_html_e( '（トップレベル）', 'alumni-core' ); ?></option>
							<?php $this->render_parent_options(); ?>
						</select>
					</label>
				</p>
				<?php submit_button( __( '＋ フォルダを追加', 'alumni-core' ), 'secondary' ); ?>
			</form>

			<h2><?php esc_html_e( '＋ コンテンツを追加', 'alumni-core' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="alumni-menu-create-form">
				<input type="hidden" name="action" value="alumni_core_create_menu_content" />
				<?php wp_nonce_field( self::NONCE_ACTION_CREATE_CONTENT ); ?>
				<p>
					<label><?php esc_html_e( '表示対象', 'alumni-core' ); ?><br />
						<?php $this->render_reference_select( 'ref', '' ); ?>
					</label>
				</p>
				<p>
					<label><?php esc_html_e( 'メニュー上の表示名（任意）', 'alumni-core' ); ?><br />
						<input type="text" name="label" class="regular-text" placeholder="<?php echo esc_attr__( '未入力の場合は参照先の名前をそのまま使用します', 'alumni-core' ); ?>" />
					</label>
				</p>
				<p>
					<label><?php esc_html_e( '対象者（親を選ばない場合のみ使用）', 'alumni-core' ); ?><br />
						<?php $this->render_audience_select( 'audience' ); ?>
					</label>
				</p>
				<p>
					<label><?php esc_html_e( '親', 'alumni-core' ); ?><br />
						<select name="parent_id">
							<option value=""><?php esc_html_e( '（トップレベル）', 'alumni-core' ); ?></option>
							<?php $this->render_parent_options(); ?>
						</select>
					</label>
				</p>
				<?php submit_button( __( '＋ コンテンツを追加', 'alumni-core' ), 'secondary' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Renders one 対象者 の下の項目群を、階層インデント付きで再帰的に
	 * 描画する。
	 *
	 * @param array[] $items 同じparent_idを持つ項目（Menu_Structure::get_children()）。
	 * @param int     $depth
	 */
	private function render_item_rows( array $items, $depth ) {
		foreach ( $items as $item ) {
			$edit_url = add_query_arg( array( 'page' => self::SLUG, 'item' => $item['item_id'] ), admin_url( 'admin.php' ) );
			$children = Menu_Structure::instance()->get_children( $item['item_id'] );
			?>
			<div class="alumni-menu-row" style="margin-left: <?php echo (int) ( $depth * 2 ); ?>em;">
				<span class="alumni-menu-row-type">
					<?php echo Menu_Structure::TYPE_FOLDER === $item['type'] ? esc_html__( '[フォルダ]', 'alumni-core' ) : esc_html__( '[ページ]', 'alumni-core' ); ?>
				</span>
				<span class="alumni-menu-row-label"><?php echo esc_html( $this->describe_item( $item ) ); ?></span>
				<?php if ( ! $item['enabled'] ) : ?>
					<span class="alumni-menu-row-disabled"><?php esc_html_e( '（非公開）', 'alumni-core' ); ?></span>
				<?php endif; ?>
				<span class="alumni-menu-row-actions">
					<a class="button button-small" href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( '編集', 'alumni-core' ); ?></a>
					<?php $this->render_action_form( self::NONCE_ACTION_MOVE, 'alumni_core_move_menu_item', $item['item_id'], __( '↑', 'alumni-core' ), array( 'direction' => 'up' ) ); ?>
					<?php $this->render_action_form( self::NONCE_ACTION_MOVE, 'alumni_core_move_menu_item', $item['item_id'], __( '↓', 'alumni-core' ), array( 'direction' => 'down' ) ); ?>
					<?php $this->render_action_form( self::NONCE_ACTION_INDENT, 'alumni_core_indent_menu_item', $item['item_id'], __( '→ 深く', 'alumni-core' ) ); ?>
					<?php $this->render_action_form( self::NONCE_ACTION_OUTDENT, 'alumni_core_outdent_menu_item', $item['item_id'], __( '← 浅く', 'alumni-core' ) ); ?>
					<?php $this->render_action_form( self::NONCE_ACTION_DELETE, 'alumni_core_delete_menu_item', $item['item_id'], __( '削除', 'alumni-core' ), array(), true ); ?>
				</span>
			</div>
			<?php
			if ( ! empty( $children ) ) {
				$this->render_item_rows( $children, $depth + 1 );
			}
		}
	}

	/**
	 * A one-click action form (matching the established pattern used by
	 * Officers_Page/Homepage_Page for move/delete forms).
	 *
	 * @param string $nonce_action
	 * @param string $post_action  admin_post_{$post_action} hook name.
	 * @param string $item_id
	 * @param string $label
	 * @param array  $extra_fields Extra hidden fields, name => value.
	 * @param bool   $confirm      Whether to show a JS confirm() dialog.
	 */
	private function render_action_form( $nonce_action, $post_action, $item_id, $label, array $extra_fields = array(), $confirm = false ) {
		$confirm_attr = $confirm ? ' onsubmit="return confirm(\'' . esc_js( __( 'この項目を削除します（配下の項目も削除されます）。よろしいですか？', 'alumni-core' ) ) . '\');"' : '';
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="alumni-menu-action-form"<?php echo $confirm_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built entirely from esc_js() above. ?>>
			<input type="hidden" name="action" value="<?php echo esc_attr( $post_action ); ?>" />
			<input type="hidden" name="item_id" value="<?php echo esc_attr( $item_id ); ?>" />
			<?php foreach ( $extra_fields as $field_name => $field_value ) : ?>
				<input type="hidden" name="<?php echo esc_attr( $field_name ); ?>" value="<?php echo esc_attr( $field_value ); ?>" />
			<?php endforeach; ?>
			<?php wp_nonce_field( $nonce_action ); ?>
			<button type="submit" class="button button-small"><?php echo esc_html( $label ); ?></button>
		</form>
		<?php
	}

	/**
	 * Renders one item's edit screen.
	 *
	 * @param array $item
	 */
	private function render_item_editor( array $item ) {
		$current_ref = '';
		if ( Menu_Structure::TYPE_CONTENT === $item['type'] && $item['ref_type'] ) {
			$current_ref = $item['ref_type'] . ':' . $item['ref_id'];
		}
		?>
		<div class="wrap alumni-core-menu">
			<h1>
				<?php esc_html_e( '項目の編集', 'alumni-core' ); ?>
				<a href="<?php echo esc_url( add_query_arg( array( 'page' => self::SLUG ), admin_url( 'admin.php' ) ) ); ?>" class="page-title-action"><?php esc_html_e( 'メニュー構成へ戻る', 'alumni-core' ); ?></a>
			</h1>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="alumni_core_update_menu_item" />
				<input type="hidden" name="item_id" value="<?php echo esc_attr( $item['item_id'] ); ?>" />
				<?php wp_nonce_field( self::NONCE_ACTION_UPDATE ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="alumni-menu-item-label"><?php esc_html_e( '表示名', 'alumni-core' ); ?></label></th>
						<td>
							<input type="text" id="alumni-menu-item-label" name="label" class="regular-text" value="<?php echo esc_attr( $item['label'] ); ?>" />
							<?php if ( Menu_Structure::TYPE_CONTENT === $item['type'] ) : ?>
								<p class="description"><?php esc_html_e( '未入力の場合は参照先の名前がそのまま使われます。', 'alumni-core' ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
					<?php if ( Menu_Structure::TYPE_CONTENT === $item['type'] ) : ?>
						<tr>
							<th scope="row"><label for="alumni-menu-item-ref"><?php esc_html_e( '表示対象', 'alumni-core' ); ?></label></th>
							<td><?php $this->render_reference_select( 'ref', $current_ref, 'alumni-menu-item-ref' ); ?></td>
						</tr>
					<?php endif; ?>
					<tr>
						<th scope="row"><?php esc_html_e( '公開状態', 'alumni-core' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="enabled" value="1" <?php checked( ! empty( $item['enabled'] ) ); ?> />
								<?php esc_html_e( '公開する（メニューに表示する）', 'alumni-core' ); ?>
							</label>
						</td>
					</tr>
				</table>

				<?php submit_button( __( '保存', 'alumni-core' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * A short admin-facing label for one item, e.g.
	 * "会長挨拶（コンテンツ）" — never empty, unlike the public-facing
	 * resolve_item() which needs to fail safely on a broken reference,
	 * this is for the admin's own list and always shows something
	 * (including "（見つかりません）" for a stale reference, so the admin
	 * can spot and fix it).
	 *
	 * @param array $item
	 * @return string
	 */
	private function describe_item( array $item ) {
		if ( '' !== $item['label'] ) {
			return $item['label'];
		}

		if ( Menu_Structure::TYPE_FOLDER === $item['type'] ) {
			return __( '（無題フォルダ）', 'alumni-core' );
		}

		if ( Menu_Structure::REF_CONTENT === $item['ref_type'] ) {
			$post = get_post( absint( $item['ref_id'] ) );
			return $post ? $post->post_title : __( '（見つかりません）', 'alumni-core' );
		}

		if ( Menu_Structure::REF_SYSTEM === $item['ref_type'] ) {
			$labels = Homepage_Sections::system_key_labels();
			return isset( $labels[ $item['ref_id'] ] ) ? $labels[ $item['ref_id'] ] : __( '（見つかりません）', 'alumni-core' );
		}

		if ( Menu_Structure::REF_OFFICER_LIST === $item['ref_type'] ) {
			$list = Officer_Lists::instance()->get_list( $item['ref_id'] );
			return $list ? ( $list['title'] ? $list['title'] : $list['name'] ) : __( '（見つかりません）', 'alumni-core' );
		}

		return __( '（未設定）', 'alumni-core' );
	}

	/**
	 * @param string $name
	 */
	private function render_audience_select( $name ) {
		?>
		<select name="<?php echo esc_attr( $name ); ?>">
			<option value="<?php echo esc_attr( Menu_Structure::AUDIENCE_COMMON ); ?>"><?php esc_html_e( '共通', 'alumni-core' ); ?></option>
			<option value="<?php echo esc_attr( Menu_Structure::AUDIENCE_ALUMNI ); ?>"><?php esc_html_e( '卒業生向け', 'alumni-core' ); ?></option>
			<option value="<?php echo esc_attr( Menu_Structure::AUDIENCE_STUDENT ); ?>"><?php esc_html_e( '在校生向け', 'alumni-core' ); ?></option>
		</select>
		<?php
	}

	/**
	 * 親候補の<option>を、対象者ごとにoptgroupでまとめ、階層をインデントで
	 * 表しながら出力する。フォルダ・コンテンツどちらも親になれる
	 * （メニュー構成では「フォルダの下に別のフォルダ」も普通の構成のため）。
	 */
	private function render_parent_options() {
		$audience_labels = array(
			Menu_Structure::AUDIENCE_COMMON  => __( '共通', 'alumni-core' ),
			Menu_Structure::AUDIENCE_ALUMNI  => __( '卒業生向け', 'alumni-core' ),
			Menu_Structure::AUDIENCE_STUDENT => __( '在校生向け', 'alumni-core' ),
		);

		foreach ( $audience_labels as $audience_value => $audience_label ) {
			$roots = Menu_Structure::instance()->get_children( '', $audience_value );

			if ( empty( $roots ) ) {
				continue;
			}

			echo '<optgroup label="' . esc_attr( $audience_label ) . '">';
			$this->render_parent_option_rows( $roots, 0 );
			echo '</optgroup>';
		}
	}

	/**
	 * @param array[] $items
	 * @param int     $depth
	 */
	private function render_parent_option_rows( array $items, $depth ) {
		foreach ( $items as $item ) {
			$label = str_repeat( '— ', $depth ) . $this->describe_item( $item );
			printf( '<option value="%1$s">%2$s</option>', esc_attr( $item['item_id'] ), esc_html( $label ) );

			$children = Menu_Structure::instance()->get_children( $item['item_id'] );
			if ( ! empty( $children ) ) {
				$this->render_parent_option_rows( $children, $depth + 1 );
			}
		}
	}

	/**
	 * Renders the 表示対象 select: システムページ／役員・理事一覧／
	 * （対象者ごとの）公開済みコンテンツ階層、のいずれかを選ぶ。
	 * 送信値は "system:{key}" / "officer_list:{id}" / "content:{id}" の
	 * いずれかの文字列で、handle_create_content()/handle_update() 側で
	 * 解釈する。
	 *
	 * @param string $name
	 * @param string $current_value
	 * @param string $id
	 */
	private function render_reference_select( $name, $current_value, $id = '' ) {
		?>
		<select name="<?php echo esc_attr( $name ); ?>"<?php echo $id ? ' id="' . esc_attr( $id ) . '"' : ''; ?>>
			<optgroup label="<?php echo esc_attr__( 'システムページ', 'alumni-core' ); ?>">
				<?php foreach ( Homepage_Sections::system_key_labels() as $system_key => $label ) : ?>
					<?php $value = 'system:' . $system_key; ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $current_value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</optgroup>
			<?php $officer_lists = Officer_Lists::instance()->get_all(); ?>
			<?php if ( ! empty( $officer_lists ) ) : ?>
				<optgroup label="<?php echo esc_attr__( '役員・理事一覧', 'alumni-core' ); ?>">
					<?php foreach ( $officer_lists as $list ) : ?>
						<?php $value = 'officer_list:' . $list['list_id']; ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $current_value ); ?>><?php echo esc_html( $list['name'] ); ?></option>
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
					<?php $this->render_content_option_rows( $tree, $current_value, 0 ); ?>
				</optgroup>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * @param array[] $nodes         Content_Hierarchy::build_tree() shape
	 *                                 (published-only).
	 * @param string  $current_value
	 * @param int     $depth
	 */
	private function render_content_option_rows( array $nodes, $current_value, $depth ) {
		foreach ( $nodes as $node ) {
			$post  = $node['post'];
			$value = 'content:' . $post->ID;
			$label = str_repeat( '— ', $depth ) . ( $post->post_title ? $post->post_title : sprintf( '#%d', $post->ID ) );

			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $value ),
				selected( $value, $current_value, false ),
				esc_html( $label )
			);

			if ( ! empty( $node['children'] ) ) {
				$this->render_content_option_rows( $node['children'], $current_value, $depth + 1 );
			}
		}
	}

	/**
	 * Parses one 表示対象selectの送信値 ("system:{key}" /
	 * "officer_list:{id}" / "content:{id}") into array(ref_type, ref_id).
	 *
	 * @param string $raw_value
	 * @return array{0:string,1:string}
	 */
	private static function parse_reference_value( $raw_value ) {
		foreach ( array( Menu_Structure::REF_SYSTEM, Menu_Structure::REF_OFFICER_LIST, Menu_Structure::REF_CONTENT ) as $ref_type ) {
			$prefix = $ref_type . ':';
			if ( 0 === strpos( $raw_value, $prefix ) ) {
				return array( $ref_type, substr( $raw_value, strlen( $prefix ) ) );
			}
		}

		return array( '', '' );
	}

	/**
	 * Handles 「＋ フォルダを追加」 (admin_post_alumni_core_create_menu_folder).
	 */
	public function handle_create_folder() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			wp_die( esc_html__( 'この操作を行う権限がありません。', 'alumni-core' ) );
		}

		check_admin_referer( self::NONCE_ACTION_CREATE_FOLDER );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above; sanitized by create_folder().
		$label     = isset( $_POST['label'] ) ? wp_unslash( $_POST['label'] ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$audience  = isset( $_POST['audience'] ) ? sanitize_key( wp_unslash( $_POST['audience'] ) ) : Menu_Structure::AUDIENCE_COMMON;
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$parent_id = isset( $_POST['parent_id'] ) ? sanitize_key( wp_unslash( $_POST['parent_id'] ) ) : '';

		if ( '' !== $parent_id && null === Menu_Structure::instance()->get_item( $parent_id ) ) {
			$parent_id = '';
		}

		Menu_Structure::instance()->create_folder( $parent_id, $audience, $label );

		wp_safe_redirect( add_query_arg( array( 'page' => self::SLUG ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Handles 「＋ コンテンツを追加」 (admin_post_alumni_core_create_menu_content).
	 */
	public function handle_create_content() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			wp_die( esc_html__( 'この操作を行う権限がありません。', 'alumni-core' ) );
		}

		check_admin_referer( self::NONCE_ACTION_CREATE_CONTENT );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above; parsed/sanitized below.
		$raw_ref = isset( $_POST['ref'] ) ? sanitize_text_field( wp_unslash( $_POST['ref'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$label     = isset( $_POST['label'] ) ? wp_unslash( $_POST['label'] ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$audience  = isset( $_POST['audience'] ) ? sanitize_key( wp_unslash( $_POST['audience'] ) ) : Menu_Structure::AUDIENCE_COMMON;
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$parent_id = isset( $_POST['parent_id'] ) ? sanitize_key( wp_unslash( $_POST['parent_id'] ) ) : '';

		if ( '' !== $parent_id && null === Menu_Structure::instance()->get_item( $parent_id ) ) {
			$parent_id = '';
		}

		list( $ref_type, $ref_id ) = self::parse_reference_value( $raw_ref );

		if ( '' !== $ref_type ) {
			Menu_Structure::instance()->create_content_item( $parent_id, $audience, $ref_type, $ref_id, $label );
		}

		wp_safe_redirect( add_query_arg( array( 'page' => self::SLUG ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Handles the 項目の編集 form submission
	 * (admin_post_alumni_core_update_menu_item).
	 */
	public function handle_update() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			wp_die( esc_html__( 'この操作を行う権限がありません。', 'alumni-core' ) );
		}

		check_admin_referer( self::NONCE_ACTION_UPDATE );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above.
		$item_id = isset( $_POST['item_id'] ) ? sanitize_key( wp_unslash( $_POST['item_id'] ) ) : '';
		$item    = Menu_Structure::instance()->get_item( $item_id );

		if ( null === $item ) {
			wp_die( esc_html__( '指定された項目が見つかりません。', 'alumni-core' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above; sanitized by update_item().
		$label = isset( $_POST['label'] ) ? wp_unslash( $_POST['label'] ) : '';

		$ref_type = '';
		$ref_id   = '';
		if ( Menu_Structure::TYPE_CONTENT === $item['type'] && isset( $_POST['ref'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above; parsed below.
			list( $ref_type, $ref_id ) = self::parse_reference_value( sanitize_text_field( wp_unslash( $_POST['ref'] ) ) );
		}

		Menu_Structure::instance()->update_item( $item_id, $label, $ref_type, $ref_id );
		Menu_Structure::instance()->set_item_enabled( $item_id, ! empty( $_POST['enabled'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- unchecked checkbox simply omits the key.

		wp_safe_redirect( add_query_arg( array( 'page' => self::SLUG, 'updated' => 'true' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Handles 削除 (admin_post_alumni_core_delete_menu_item).
	 */
	public function handle_delete() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			wp_die( esc_html__( 'この操作を行う権限がありません。', 'alumni-core' ) );
		}

		check_admin_referer( self::NONCE_ACTION_DELETE );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above.
		$item_id = isset( $_POST['item_id'] ) ? sanitize_key( wp_unslash( $_POST['item_id'] ) ) : '';

		if ( '' !== $item_id ) {
			Menu_Structure::instance()->delete_item( $item_id );
		}

		wp_safe_redirect( add_query_arg( array( 'page' => self::SLUG ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Handles 並び替え (admin_post_alumni_core_move_menu_item).
	 */
	public function handle_move() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			wp_die( esc_html__( 'この操作を行う権限がありません。', 'alumni-core' ) );
		}

		check_admin_referer( self::NONCE_ACTION_MOVE );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above.
		$item_id   = isset( $_POST['item_id'] ) ? sanitize_key( wp_unslash( $_POST['item_id'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$direction = isset( $_POST['direction'] ) ? sanitize_key( wp_unslash( $_POST['direction'] ) ) : '';

		if ( '' !== $item_id && in_array( $direction, array( 'up', 'down' ), true ) ) {
			Menu_Structure::instance()->move_item( $item_id, $direction );
		}

		wp_safe_redirect( add_query_arg( array( 'page' => self::SLUG ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Handles 階層を深くする (admin_post_alumni_core_indent_menu_item).
	 */
	public function handle_indent() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			wp_die( esc_html__( 'この操作を行う権限がありません。', 'alumni-core' ) );
		}

		check_admin_referer( self::NONCE_ACTION_INDENT );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above.
		$item_id = isset( $_POST['item_id'] ) ? sanitize_key( wp_unslash( $_POST['item_id'] ) ) : '';

		if ( '' !== $item_id ) {
			Menu_Structure::instance()->indent_item( $item_id );
		}

		wp_safe_redirect( add_query_arg( array( 'page' => self::SLUG ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Handles 階層を浅くする (admin_post_alumni_core_outdent_menu_item).
	 */
	public function handle_outdent() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			wp_die( esc_html__( 'この操作を行う権限がありません。', 'alumni-core' ) );
		}

		check_admin_referer( self::NONCE_ACTION_OUTDENT );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above.
		$item_id = isset( $_POST['item_id'] ) ? sanitize_key( wp_unslash( $_POST['item_id'] ) ) : '';

		if ( '' !== $item_id ) {
			Menu_Structure::instance()->outdent_item( $item_id );
		}

		wp_safe_redirect( add_query_arg( array( 'page' => self::SLUG ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
