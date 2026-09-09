<?php
/**
 * 人物挨拶グループ内の表示順をドラッグ＆ドロップで管理する管理画面.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Admin\Pages;

use AlumniCore\Includes\Person_Greeting_Groups;
use AlumniCore\Includes\Modules\Content\Post_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Person_Greeting_Order_Page {

	const SLUG = 'alumni-core-person-greeting-order';
	const ACTION = 'alumni_core_save_person_greeting_order';
	const NONCE_ACTION = 'alumni_core_save_person_greeting_order';

	public function render() {
		$groups = Person_Greeting_Groups::instance()->get_all();
		?>
		<div class="wrap alumni-person-greeting-order-page">
			<h1><?php esc_html_e( '人物挨拶の並び順', 'alumni-core' ); ?></h1>
			<?php if ( isset( $_GET['updated'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( '人物挨拶の並び順を保存しました。', 'alumni-core' ); ?></p></div>
			<?php endif; ?>
			<p class="description"><?php esc_html_e( '人物をドラッグ＆ドロップして順番を変更してください。各グループの一番上の人物が、トップページでそのグループを指定した場合の代表として表示されます。', 'alumni-core' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="alumni-person-greeting-order-form">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>" />
				<?php wp_nonce_field( self::NONCE_ACTION ); ?>
				<?php foreach ( $groups as $group ) : ?>
					<?php $members = alumni_core_get_person_greeting_group_members( $group['group_id'] ); ?>
					<section class="alumni-person-greeting-order-group">
						<h2><?php echo esc_html( $group['name'] ); ?></h2>
						<?php if ( empty( $members ) ) : ?>
							<p class="description"><?php esc_html_e( 'このグループには公開中の人物挨拶がありません。', 'alumni-core' ); ?></p>
						<?php else : ?>
							<ul class="alumni-person-greeting-sortable">
								<?php foreach ( $members as $member ) : ?>
									<?php $name = Post_Type::get_person_name( $member ); $title = Post_Type::get_person_title( $member ); ?>
									<li class="alumni-person-greeting-sortable-item" draggable="true">
										<span class="alumni-person-greeting-drag-handle" aria-hidden="true">☰</span>
										<span class="alumni-person-greeting-sortable-name"><?php echo esc_html( $name ? $name : $member->post_title ); ?></span>
										<?php if ( $title ) : ?><span class="alumni-person-greeting-sortable-title"><?php echo esc_html( $title ); ?></span><?php endif; ?>
										<input type="hidden" name="orders[<?php echo esc_attr( $group['group_id'] ); ?>][]" value="<?php echo esc_attr( $member->ID ); ?>" />
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

		foreach ( Person_Greeting_Groups::instance()->get_all() as $group ) {
			$group_id = $group['group_id'];
			if ( empty( $orders[ $group_id ] ) || ! is_array( $orders[ $group_id ] ) ) {
				continue;
			}
			$allowed_ids = wp_list_pluck( alumni_core_get_person_greeting_group_members( $group_id ), 'ID' );
			$position = 1;
			foreach ( $orders[ $group_id ] as $raw_post_id ) {
				$post_id = absint( $raw_post_id );
				if ( ! in_array( $post_id, array_map( 'intval', $allowed_ids ), true ) ) {
					continue;
				}
				wp_update_post( array( 'ID' => $post_id, 'menu_order' => $position ) );
				++$position;
			}
		}

		wp_safe_redirect( add_query_arg( array( 'page' => self::SLUG, 'updated' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
