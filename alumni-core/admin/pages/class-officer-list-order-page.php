<?php
/**
 * 役員・理事一覧グループ内の表示順を管理する.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Admin\Pages;

use AlumniCore\Includes\Officer_List_Groups;
use AlumniCore\Includes\Officer_Lists;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Officer_List_Order_Page {

	const SLUG = 'alumni-core-officer-list-order';
	const ACTION = 'alumni_core_save_officer_list_order';
	const NONCE_ACTION = 'alumni_core_save_officer_list_order';

	public function render() {
		$groups = Officer_List_Groups::instance()->get_all();
		?>
		<div class="wrap alumni-person-greeting-order-page">
			<h1><?php esc_html_e( '組織名簿の並び順', 'alumni-core' ); ?></h1>
			<?php if ( isset( $_GET['updated'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( '組織名簿の並び順を保存しました。', 'alumni-core' ); ?></p></div>
			<?php endif; ?>
			<p class="description"><?php esc_html_e( '一覧をドラッグ＆ドロップして順番を変更してください。同じグループの一覧は、1つの公開ページにこの順番で表示されます。', 'alumni-core' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="alumni-person-greeting-order-form">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>" />
				<?php wp_nonce_field( self::NONCE_ACTION ); ?>
				<?php foreach ( $groups as $group ) : ?>
					<?php $members = Officer_Lists::instance()->get_group_members( $group['group_id'] ); ?>
					<section class="alumni-person-greeting-order-group">
						<h2><?php echo esc_html( $group['name'] ); ?></h2>
						<?php if ( empty( $members ) ) : ?>
							<p class="description"><?php esc_html_e( 'このグループには一覧がありません。', 'alumni-core' ); ?></p>
						<?php else : ?>
							<ul class="alumni-person-greeting-sortable">
								<?php foreach ( $members as $member ) : ?>
									<li class="alumni-person-greeting-sortable-item" draggable="true">
										<span class="alumni-person-greeting-drag-handle" aria-hidden="true">☰</span>
										<span class="alumni-person-greeting-sortable-name"><?php echo esc_html( $member['name'] ); ?></span>
										<?php if ( ! empty( $member['title'] ) ) : ?><span class="alumni-person-greeting-sortable-title"><?php echo esc_html( $member['title'] ); ?></span><?php endif; ?>
										<input type="hidden" name="orders[<?php echo esc_attr( $group['group_id'] ); ?>][]" value="<?php echo esc_attr( $member['list_id'] ); ?>" />
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</section>
				<?php endforeach; ?>
				<?php submit_button( __( '並び順を保存', 'alumni-core' ) ); ?>
			</form>
		</div>
		<?php
	}

	public function handle_save() {
		check_admin_referer( self::NONCE_ACTION );
		$orders = isset( $_POST['orders'] ) && is_array( $_POST['orders'] ) ? wp_unslash( $_POST['orders'] ) : array();
		foreach ( Officer_List_Groups::instance()->get_all() as $group ) {
			$group_id = $group['group_id'];
			if ( empty( $orders[ $group_id ] ) || ! is_array( $orders[ $group_id ] ) ) {
				continue;
			}
			Officer_Lists::instance()->save_group_order( $group_id, $orders[ $group_id ] );
		}
		wp_safe_redirect( add_query_arg( array( 'page' => self::SLUG, 'updated' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
