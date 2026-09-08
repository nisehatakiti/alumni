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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * トップページの固定3×2 Homepage Gridを管理する。
 *
 * A〜Fの各セルは独立して表示内容を選択でき、A+D / B+E / C+Fは
 * 必要に応じて上下結合できる。HTMLやCSS Gridの内部実装を管理者へ
 * 露出せず、「セルを選ぶ」「上下を結合する」だけで操作できるUIとする。
 */
class Homepage_Page {

	const SLUG = 'alumni-core-homepage';

	const NONCE_ACTION_SAVE = 'alumni_core_save_homepage_grid';

	public function render() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			return;
		}

		$grid = Homepage_Sections::instance()->get_grid();
		?>
		<div class="wrap alumni-core-homepage">
			<h1><?php esc_html_e( 'トップページ設定', 'alumni-core' ); ?></h1>

			<?php if ( isset( $_GET['updated'] ) && 'true' === $_GET['updated'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( '保存しました。', 'alumni-core' ); ?></p></div>
			<?php endif; ?>

			<p><?php esc_html_e( 'トップページは横3列×縦2段の6セルで構成します。各セルごとに表示内容を選択でき、同じ列の上下セルは1つの大きな表示ブロックとして結合できます。トップページへの配置とメニュー構成は独立しています。', 'alumni-core' ); ?></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="alumni-core-homepage-form">
				<input type="hidden" name="action" value="alumni_core_save_homepage_grid" />
				<?php wp_nonce_field( self::NONCE_ACTION_SAVE ); ?>

				<div class="alumni-homepage-grid-admin">
					<?php foreach ( array( 'A', 'B', 'C', 'D', 'E', 'F' ) as $cell_key ) : ?>
						<?php
						$slot = $grid['cells'][ $cell_key ];
						$top_cell = in_array( $cell_key, Homepage_Sections::mergeable_top_cells(), true );
						$bottom_to_top = array( 'D' => 'A', 'E' => 'B', 'F' => 'C' );
						$hidden_by_merge = isset( $bottom_to_top[ $cell_key ] ) && ! empty( $grid['merged_columns'][ $bottom_to_top[ $cell_key ] ] );
						?>
						<fieldset class="alumni-homepage-grid-cell alumni-homepage-grid-cell-<?php echo esc_attr( strtolower( $cell_key ) ); ?><?php echo $hidden_by_merge ? ' is-merged-child' : ''; ?>">
							<legend><?php printf( esc_html__( 'ブロック %s', 'alumni-core' ), esc_html( $cell_key ) ); ?></legend>

							<?php if ( $hidden_by_merge ) : ?>
								<p class="description"><?php esc_html_e( '上のブロックと結合中です。結合を解除するとこのブロックの設定が再び使用できます。', 'alumni-core' ); ?></p>
							<?php endif; ?>

							<label>
								<?php esc_html_e( '表示内容', 'alumni-core' ); ?><br />
								<?php $this->render_slot_select( "cells[{$cell_key}]", $slot ); ?>
							</label>

							<?php if ( $top_cell ) : ?>
								<p class="alumni-homepage-grid-merge">
									<label>
										<input type="checkbox" name="merged_columns[<?php echo esc_attr( $cell_key ); ?>]" value="1" <?php checked( ! empty( $grid['merged_columns'][ $cell_key ] ) ); ?> />
										<?php
										printf(
											/* translators: 1: top cell, 2: bottom cell */
											esc_html__( 'ブロック %1$s と下のブロック %2$s を上下結合する', 'alumni-core' ),
											esc_html( $cell_key ),
											esc_html( array( 'A' => 'D', 'B' => 'E', 'C' => 'F' )[ $cell_key ] )
										);
										?>
									</label>
								</p>
							<?php endif; ?>
						</fieldset>
					<?php endforeach; ?>
				</div>

				<?php submit_button( __( 'トップページのブロック配置を保存', 'alumni-core' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * @param string $name
	 * @param array  $current_slot
	 */
	private function render_slot_select( $name, array $current_slot ) {
		$current_value = 'none';
		if ( 'system' === $current_slot['type'] ) {
			$current_value = 'system:' . $current_slot['system_key'];
		} elseif ( 'content' === $current_slot['type'] ) {
			$current_value = 'content:' . $current_slot['content_id'];
		} elseif ( 'person_greeting_group' === $current_slot['type'] ) {
			$current_value = 'person_greeting_group:' . $current_slot['group_id'];
		}
		?>
		<select name="<?php echo esc_attr( $name ); ?>">
			<option value="none" <?php selected( 'none', $current_value ); ?>><?php esc_html_e( '（未設定）', 'alumni-core' ); ?></option>

			<optgroup label="<?php echo esc_attr__( '人物挨拶グループ', 'alumni-core' ); ?>">
				<?php foreach ( Person_Greeting_Groups::instance()->get_all() as $group ) : ?>
					<?php $value = 'person_greeting_group:' . $group['group_id']; ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $current_value ); ?>><?php echo esc_html( $group['name'] ); ?></option>
				<?php endforeach; ?>
			</optgroup>

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
				if ( empty( $tree ) ) {
					continue;
				}
				?>
				<optgroup label="<?php echo esc_attr( $audience_label ); ?>">
					<?php $this->render_slot_option_nodes( $tree, $current_value, 0 ); ?>
				</optgroup>
			<?php endforeach; ?>
		</select>
		<?php
	}

	private function render_slot_option_nodes( array $nodes, $current_value, $depth ) {
		foreach ( $nodes as $node ) {
			$post = $node['post'];
			$value = 'content:' . $post->ID;
			$label = str_repeat( '— ', $depth ) . ( $post->post_title ? $post->post_title : sprintf( '#%d', $post->ID ) );

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

	public function handle_save() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			wp_die( esc_html__( 'この操作を行う権限がありません。', 'alumni-core' ) );
		}

		check_admin_referer( self::NONCE_ACTION_SAVE );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above.
		$raw_cells = isset( $_POST['cells'] ) && is_array( $_POST['cells'] ) ? wp_unslash( $_POST['cells'] ) : array();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above.
		$raw_merged = isset( $_POST['merged_columns'] ) && is_array( $_POST['merged_columns'] ) ? wp_unslash( $_POST['merged_columns'] ) : array();

		$cells = array();
		foreach ( Homepage_Sections::cell_keys() as $cell_key ) {
			$cells[ $cell_key ] = self::parse_slot_value( isset( $raw_cells[ $cell_key ] ) ? (string) $raw_cells[ $cell_key ] : 'none' );
		}

		$merged = array();
		foreach ( Homepage_Sections::mergeable_top_cells() as $cell_key ) {
			$merged[ $cell_key ] = ! empty( $raw_merged[ $cell_key ] );
		}

		Homepage_Sections::instance()->update_grid( $cells, $merged );

		wp_safe_redirect(
			add_query_arg(
				array( 'page' => self::SLUG, 'updated' => 'true' ),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * @param string $raw_value
	 * @return array
	 */
	private static function parse_slot_value( $raw_value ) {
		if ( 0 === strpos( $raw_value, 'person_greeting_group:' ) ) {
			return array(
				'type'     => 'person_greeting_group',
				'group_id' => substr( $raw_value, strlen( 'person_greeting_group:' ) ),
			);
		}

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
