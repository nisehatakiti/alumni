<?php
/**
 * 同窓会 > 同窓会組織図 screen.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Admin\Pages;

use AlumniCore\Admin\Admin;
use AlumniCore\Includes\Org_Chart;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 組織図（会長→副会長→委員会…のような、組織そのものの親子構造）を
 * 管理する画面。Menu_Page（メニュー構成）と見た目・操作パターンは近いが、
 * 扱うデータは完全に別（このクラスはMenu_Structureを一切参照しない —
 * class-org-chart.phpのdocblock参照）。
 */
class Org_Chart_Page {

	/**
	 * Submenu slug.
	 */
	const SLUG = 'alumni-core-org-chart';

	/**
	 * Nonce actions.
	 */
	const NONCE_ACTION_CREATE  = 'alumni_core_create_org_chart_node';
	const NONCE_ACTION_UPDATE  = 'alumni_core_update_org_chart_node';
	const NONCE_ACTION_DELETE  = 'alumni_core_delete_org_chart_node';
	const NONCE_ACTION_MOVE    = 'alumni_core_move_org_chart_node';
	const NONCE_ACTION_REPARENT = 'alumni_core_reparent_org_chart_node';

	/**
	 * Renders the screen: the node edit screen when ?node= is present and
	 * resolves to a real node, otherwise the full-tree index.
	 */
	public function render() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only navigation, nothing is written.
		$node_id = isset( $_GET['node'] ) ? sanitize_key( wp_unslash( $_GET['node'] ) ) : '';
		$node    = '' !== $node_id ? Org_Chart::instance()->get_node( $node_id ) : null;

		if ( null !== $node ) {
			$this->render_node_editor( $node );
			return;
		}

		$this->render_index();
	}

	/**
	 * Renders the full 組織図 as an indented list, plus the ＋ノード追加
	 * form.
	 */
	private function render_index() {
		?>
		<div class="wrap alumni-core-org-chart">
			<h1><?php esc_html_e( '同窓会組織図', 'alumni-core' ); ?></h1>

			<?php if ( isset( $_GET['updated'] ) && 'true' === $_GET['updated'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only status flag. ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( '保存しました。', 'alumni-core' ); ?></p>
				</div>
			<?php endif; ?>

			<p><?php esc_html_e( '「会長→副会長→委員会」のような、同窓会組織そのものの親子構造をここで管理します。これはメニュー構成（サイトナビゲーション）とは別のデータです。公開ページへの掲載は「同窓会 > メニュー構成」で「同窓会組織図」をシステムページとして配置してください。', 'alumni-core' ); ?></p>

			<div class="alumni-org-chart-tree">
				<?php
				$roots = Org_Chart::instance()->get_children( '' );

				if ( empty( $roots ) ) :
					?>
					<p class="description"><?php esc_html_e( 'まだノードがありません。', 'alumni-core' ); ?></p>
					<?php
				else :
					$this->render_node_rows( $roots, 0 );
				endif;
				?>
			</div>

			<h2><?php esc_html_e( '＋ ノードを追加', 'alumni-core' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="alumni-org-chart-create-form">
				<input type="hidden" name="action" value="alumni_core_create_org_chart_node" />
				<?php wp_nonce_field( self::NONCE_ACTION_CREATE ); ?>
				<p>
					<label><?php esc_html_e( '名前', 'alumni-core' ); ?><br />
						<input type="text" name="name" class="regular-text" required="required" placeholder="<?php echo esc_attr__( '例：会長、副会長、総務委員会', 'alumni-core' ); ?>" />
					</label>
				</p>
				<p>
					<label><?php esc_html_e( '親ノード', 'alumni-core' ); ?><br />
						<select name="parent_id">
							<option value=""><?php esc_html_e( '（トップレベル）', 'alumni-core' ); ?></option>
							<?php $this->render_parent_options(); ?>
						</select>
					</label>
				</p>
				<?php submit_button( __( '＋ ノードを追加', 'alumni-core' ), 'secondary' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * @param array[] $nodes 同じparent_idを持つノード（Org_Chart::get_children()）。
	 * @param int     $depth
	 */
	private function render_node_rows( array $nodes, $depth ) {
		foreach ( $nodes as $node ) {
			$edit_url = add_query_arg( array( 'page' => self::SLUG, 'node' => $node['node_id'] ), admin_url( 'admin.php' ) );
			$children = Org_Chart::instance()->get_children( $node['node_id'] );
			?>
			<div class="alumni-org-chart-row" style="margin-left: <?php echo (int) ( $depth * 2 ); ?>em;">
				<span class="alumni-org-chart-row-label"><?php echo esc_html( $node['name'] ? $node['name'] : __( '（無題）', 'alumni-core' ) ); ?></span>
				<span class="alumni-org-chart-row-actions">
					<a class="button button-small" href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( '編集', 'alumni-core' ); ?></a>
					<?php $this->render_action_form( self::NONCE_ACTION_MOVE, 'alumni_core_move_org_chart_node', $node['node_id'], __( '↑', 'alumni-core' ), array( 'direction' => 'up' ) ); ?>
					<?php $this->render_action_form( self::NONCE_ACTION_MOVE, 'alumni_core_move_org_chart_node', $node['node_id'], __( '↓', 'alumni-core' ), array( 'direction' => 'down' ) ); ?>
					<?php $this->render_action_form( self::NONCE_ACTION_DELETE, 'alumni_core_delete_org_chart_node', $node['node_id'], __( '削除', 'alumni-core' ), array(), true ); ?>
				</span>
			</div>
			<?php
			if ( ! empty( $children ) ) {
				$this->render_node_rows( $children, $depth + 1 );
			}
		}
	}

	/**
	 * A one-click action form (same established pattern as Menu_Page).
	 *
	 * @param string $nonce_action
	 * @param string $post_action  admin_post_{$post_action} hook name.
	 * @param string $node_id
	 * @param string $label
	 * @param array  $extra_fields Extra hidden fields, name => value.
	 * @param bool   $confirm      Whether to show a JS confirm() dialog.
	 */
	private function render_action_form( $nonce_action, $post_action, $node_id, $label, array $extra_fields = array(), $confirm = false ) {
		$confirm_attr = $confirm ? ' onsubmit="return confirm(\'' . esc_js( __( 'このノードを削除します（配下のノードも削除されます）。よろしいですか？', 'alumni-core' ) ) . '\');"' : '';
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="alumni-org-chart-action-form"<?php echo $confirm_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built entirely from esc_js() above. ?>>
			<input type="hidden" name="action" value="<?php echo esc_attr( $post_action ); ?>" />
			<input type="hidden" name="node_id" value="<?php echo esc_attr( $node_id ); ?>" />
			<?php foreach ( $extra_fields as $field_name => $field_value ) : ?>
				<input type="hidden" name="<?php echo esc_attr( $field_name ); ?>" value="<?php echo esc_attr( $field_value ); ?>" />
			<?php endforeach; ?>
			<?php wp_nonce_field( $nonce_action ); ?>
			<button type="submit" class="button button-small"><?php echo esc_html( $label ); ?></button>
		</form>
		<?php
	}

	/**
	 * Renders one node's edit screen: name + 親ノード（reparent）.
	 *
	 * @param array $node
	 */
	private function render_node_editor( array $node ) {
		?>
		<div class="wrap alumni-core-org-chart">
			<h1>
				<?php esc_html_e( 'ノードの編集', 'alumni-core' ); ?>
				<a href="<?php echo esc_url( add_query_arg( array( 'page' => self::SLUG ), admin_url( 'admin.php' ) ) ); ?>" class="page-title-action"><?php esc_html_e( '組織図へ戻る', 'alumni-core' ); ?></a>
			</h1>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="alumni_core_update_org_chart_node" />
				<input type="hidden" name="node_id" value="<?php echo esc_attr( $node['node_id'] ); ?>" />
				<?php wp_nonce_field( self::NONCE_ACTION_UPDATE ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="alumni-org-chart-node-name"><?php esc_html_e( '名前', 'alumni-core' ); ?></label></th>
						<td>
							<input type="text" id="alumni-org-chart-node-name" name="name" class="regular-text" value="<?php echo esc_attr( $node['name'] ); ?>" />
						</td>
					</tr>
				</table>

				<?php submit_button( __( '保存', 'alumni-core' ) ); ?>
			</form>

			<h2><?php esc_html_e( '親ノードを変更', 'alumni-core' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="alumni_core_reparent_org_chart_node" />
				<input type="hidden" name="node_id" value="<?php echo esc_attr( $node['node_id'] ); ?>" />
				<?php wp_nonce_field( self::NONCE_ACTION_REPARENT ); ?>
				<p>
					<select name="parent_id">
						<option value=""><?php esc_html_e( '（トップレベル）', 'alumni-core' ); ?></option>
						<?php $this->render_parent_options( $node['node_id'] ); ?>
					</select>
				</p>
				<?php submit_button( __( '親ノードを変更', 'alumni-core' ), 'secondary' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Renders <option> elements for the 親ノード select, indented to show
	 * depth. When $exclude_node_id is given, that node and its own
	 * descendants are left out (choosing either would create a cycle;
	 * Org_Chart::set_parent() enforces this again regardless).
	 *
	 * @param string $exclude_node_id '' when creating a brand-new node.
	 */
	private function render_parent_options( $exclude_node_id = '' ) {
		$exclude_ids = array();

		if ( '' !== $exclude_node_id ) {
			$exclude_ids = array_merge( array( $exclude_node_id ), Org_Chart::instance()->get_descendant_ids( $exclude_node_id ) );
		}

		$this->render_parent_option_rows( Org_Chart::instance()->get_children( '' ), $exclude_ids, 0 );
	}

	/**
	 * @param array[] $nodes
	 * @param array   $exclude_ids
	 * @param int     $depth
	 */
	private function render_parent_option_rows( array $nodes, array $exclude_ids, $depth ) {
		foreach ( $nodes as $node ) {
			if ( ! in_array( $node['node_id'], $exclude_ids, true ) ) {
				$label = str_repeat( '— ', $depth ) . ( $node['name'] ? $node['name'] : __( '（無題）', 'alumni-core' ) );
				printf( '<option value="%1$s">%2$s</option>', esc_attr( $node['node_id'] ), esc_html( $label ) );
			}

			$children = Org_Chart::instance()->get_children( $node['node_id'] );
			if ( ! empty( $children ) ) {
				$this->render_parent_option_rows( $children, $exclude_ids, $depth + 1 );
			}
		}
	}

	/**
	 * Handles 「＋ ノードを追加」 (admin_post_alumni_core_create_org_chart_node).
	 */
	public function handle_create() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			wp_die( esc_html__( 'この操作を行う権限がありません。', 'alumni-core' ) );
		}

		check_admin_referer( self::NONCE_ACTION_CREATE );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above; sanitized by create_node().
		$name      = isset( $_POST['name'] ) ? wp_unslash( $_POST['name'] ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$parent_id = isset( $_POST['parent_id'] ) ? sanitize_key( wp_unslash( $_POST['parent_id'] ) ) : '';

		Org_Chart::instance()->create_node( $parent_id, $name );

		wp_safe_redirect( add_query_arg( array( 'page' => self::SLUG ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Handles the ノードの編集 form submission
	 * (admin_post_alumni_core_update_org_chart_node).
	 */
	public function handle_update() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			wp_die( esc_html__( 'この操作を行う権限がありません。', 'alumni-core' ) );
		}

		check_admin_referer( self::NONCE_ACTION_UPDATE );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above.
		$node_id = isset( $_POST['node_id'] ) ? sanitize_key( wp_unslash( $_POST['node_id'] ) ) : '';

		if ( '' === $node_id || null === Org_Chart::instance()->get_node( $node_id ) ) {
			wp_die( esc_html__( '指定されたノードが見つかりません。', 'alumni-core' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above; sanitized by update_node().
		$name = isset( $_POST['name'] ) ? wp_unslash( $_POST['name'] ) : '';

		Org_Chart::instance()->update_node( $node_id, $name );

		wp_safe_redirect( add_query_arg( array( 'page' => self::SLUG, 'updated' => 'true' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Handles 削除 (admin_post_alumni_core_delete_org_chart_node).
	 */
	public function handle_delete() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			wp_die( esc_html__( 'この操作を行う権限がありません。', 'alumni-core' ) );
		}

		check_admin_referer( self::NONCE_ACTION_DELETE );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above.
		$node_id = isset( $_POST['node_id'] ) ? sanitize_key( wp_unslash( $_POST['node_id'] ) ) : '';

		if ( '' !== $node_id ) {
			Org_Chart::instance()->delete_node( $node_id );
		}

		wp_safe_redirect( add_query_arg( array( 'page' => self::SLUG ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Handles 並び替え (admin_post_alumni_core_move_org_chart_node).
	 */
	public function handle_move() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			wp_die( esc_html__( 'この操作を行う権限がありません。', 'alumni-core' ) );
		}

		check_admin_referer( self::NONCE_ACTION_MOVE );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above.
		$node_id   = isset( $_POST['node_id'] ) ? sanitize_key( wp_unslash( $_POST['node_id'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$direction = isset( $_POST['direction'] ) ? sanitize_key( wp_unslash( $_POST['direction'] ) ) : '';

		if ( '' !== $node_id && in_array( $direction, array( 'up', 'down' ), true ) ) {
			Org_Chart::instance()->move_node( $node_id, $direction );
		}

		wp_safe_redirect( add_query_arg( array( 'page' => self::SLUG ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Handles 親ノードを変更 (admin_post_alumni_core_reparent_org_chart_node).
	 */
	public function handle_reparent() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			wp_die( esc_html__( 'この操作を行う権限がありません。', 'alumni-core' ) );
		}

		check_admin_referer( self::NONCE_ACTION_REPARENT );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above.
		$node_id   = isset( $_POST['node_id'] ) ? sanitize_key( wp_unslash( $_POST['node_id'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$parent_id = isset( $_POST['parent_id'] ) ? sanitize_key( wp_unslash( $_POST['parent_id'] ) ) : '';

		if ( '' !== $node_id ) {
			Org_Chart::instance()->set_parent( $node_id, $parent_id );
		}

		wp_safe_redirect( add_query_arg( array( 'page' => self::SLUG, 'node' => $node_id ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
