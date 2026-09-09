<?php
/**
 * 同窓会 > 人物挨拶グループ screen.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Admin\Pages;

use AlumniCore\Admin\Admin;
use AlumniCore\Includes\Person_Greeting_Groups;
use AlumniCore\Includes\Person_Greeting_Groups_Shortcode;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages the labels used to group multiple person greetings on one
 * public page, such as 母校校長挨拶 and 同窓会長挨拶.
 */
class Person_Greeting_Groups_Page {

	const SLUG = 'alumni-core-person-greeting-groups';

	const NONCE_ACTION_CREATE = 'alumni_core_create_person_greeting_group';
	const NONCE_ACTION_UPDATE = 'alumni_core_update_person_greeting_group';
	const NONCE_ACTION_DELETE = 'alumni_core_delete_person_greeting_group';
	const NONCE_ACTION_MOVE   = 'alumni_core_move_person_greeting_group';

	public function render() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			return;
		}

		$groups = Person_Greeting_Groups::instance()->get_all();
		?>
		<div class="wrap alumni-core-person-greeting-groups">
			<h1><?php esc_html_e( '人物挨拶グループ', 'alumni-core' ); ?></h1>

			<?php if ( isset( $_GET['updated'] ) && 'true' === $_GET['updated'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( '保存しました。', 'alumni-core' ); ?></p></div>
			<?php endif; ?>

			<?php if ( isset( $_GET['created'] ) && 'true' === $_GET['created'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( '人物挨拶グループを作成しました。', 'alumni-core' ); ?></p></div>
			<?php endif; ?>

			<?php if ( isset( $_GET['deleted'] ) && 'true' === $_GET['deleted'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( '人物挨拶グループを削除しました。', 'alumni-core' ); ?></p></div>
			<?php endif; ?>

			<?php if ( isset( $_GET['delete_blocked'] ) && 'true' === $_GET['delete_blocked'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-warning"><p><?php esc_html_e( 'このグループには人物挨拶が登録されているため削除できません。先に人物挨拶の所属グループを変更してください。', 'alumni-core' ); ?></p></div>
			<?php endif; ?>

			<p><?php esc_html_e( '「母校校長挨拶」「同窓会長挨拶」のように、複数の人物挨拶を1ページにまとめるためのグループを管理します。人物挨拶を追加するときは、この画面で作成したグループを選択します。', 'alumni-core' ); ?></p>

			<?php if ( empty( $groups ) ) : ?>
				<p class="description"><?php esc_html_e( 'まだ人物挨拶グループがありません。下のフォームから作成してください。', 'alumni-core' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'グループ名', 'alumni-core' ); ?></th>
							<th><?php esc_html_e( '登録人数', 'alumni-core' ); ?></th>
							<th><?php esc_html_e( '公開URL', 'alumni-core' ); ?></th>
							<th><?php esc_html_e( '操作', 'alumni-core' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $groups as $group ) : ?>
							<?php $member_count = Person_Greeting_Groups::instance()->count_members( $group['group_id'] ); ?>
							<?php $public_url = Person_Greeting_Groups_Shortcode::get_group_url( $group['group_id'] ); ?>
							<tr>
								<td>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
										<input type="hidden" name="action" value="alumni_core_update_person_greeting_group" />
										<input type="hidden" name="group_id" value="<?php echo esc_attr( $group['group_id'] ); ?>" />
										<?php wp_nonce_field( self::NONCE_ACTION_UPDATE ); ?>
										<input type="text" name="name" class="regular-text" value="<?php echo esc_attr( $group['name'] ); ?>" required="required" />
										<button type="submit" class="button button-small"><?php esc_html_e( '保存', 'alumni-core' ); ?></button>
									</form>
								</td>
								<td><?php echo esc_html( $member_count ); ?></td>
								<td>
									<?php if ( $public_url ) : ?>
										<a href="<?php echo esc_url( $public_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $public_url ); ?></a>
									<?php else : ?>
										<?php esc_html_e( '（次回の管理画面読み込み時に作成されます）', 'alumni-core' ); ?>
									<?php endif; ?>
								</td>
								<td>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
										<input type="hidden" name="action" value="alumni_core_move_person_greeting_group" />
										<input type="hidden" name="group_id" value="<?php echo esc_attr( $group['group_id'] ); ?>" />
										<input type="hidden" name="direction" value="up" />
										<?php wp_nonce_field( self::NONCE_ACTION_MOVE ); ?>
										<button type="submit" class="button button-small"><?php esc_html_e( '↑', 'alumni-core' ); ?></button>
									</form>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
										<input type="hidden" name="action" value="alumni_core_move_person_greeting_group" />
										<input type="hidden" name="group_id" value="<?php echo esc_attr( $group['group_id'] ); ?>" />
										<input type="hidden" name="direction" value="down" />
										<?php wp_nonce_field( self::NONCE_ACTION_MOVE ); ?>
										<button type="submit" class="button button-small"><?php esc_html_e( '↓', 'alumni-core' ); ?></button>
									</form>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline" onsubmit="return confirm('<?php echo esc_js( __( 'この人物挨拶グループを削除します。よろしいですか？', 'alumni-core' ) );');">
										<input type="hidden" name="action" value="alumni_core_delete_person_greeting_group" />
										<input type="hidden" name="group_id" value="<?php echo esc_attr( $group['group_id'] ); ?>" />
										<?php wp_nonce_field( self::NONCE_ACTION_DELETE ); ?>
										<button type="submit" class="button button-link-delete"><?php esc_html_e( '削除', 'alumni-core' ); ?></button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<h2><?php esc_html_e( '新しい人物挨拶グループを作成', 'alumni-core' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="alumni_core_create_person_greeting_group" />
				<?php wp_nonce_field( self::NONCE_ACTION_CREATE ); ?>
				<p>
					<label for="alumni-person-greeting-group-name"><?php esc_html_e( 'グループ名', 'alumni-core' ); ?></label><br />
					<input type="text" id="alumni-person-greeting-group-name" name="name" class="regular-text" placeholder="<?php echo esc_attr__( '例：歴代校長挨拶', 'alumni-core' ); ?>" required="required" />
				</p>
				<?php submit_button( __( '＋ 人物挨拶グループを作成', 'alumni-core' ), 'secondary' ); ?>
			</form>
		</div>
		<?php
	}

	public function handle_create() {
		$this->require_capability();
		check_admin_referer( self::NONCE_ACTION_CREATE );
		$name = isset( $_POST['name'] ) ? wp_unslash( $_POST['name'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( '' !== trim( (string) $name ) ) {
			Person_Greeting_Groups::instance()->create_group( $name );
			Person_Greeting_Groups_Shortcode::maybe_create_pages();
		}
		$this->redirect( array( 'created' => 'true' ) );
	}

	public function handle_update() {
		$this->require_capability();
		check_admin_referer( self::NONCE_ACTION_UPDATE );
		$group_id = isset( $_POST['group_id'] ) ? sanitize_text_field( wp_unslash( $_POST['group_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$name = isset( $_POST['name'] ) ? wp_unslash( $_POST['name'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		Person_Greeting_Groups::instance()->update_group( $group_id, $name );
		$this->redirect( array( 'updated' => 'true' ) );
	}

	public function handle_delete() {
		$this->require_capability();
		check_admin_referer( self::NONCE_ACTION_DELETE );
		$group_id = isset( $_POST['group_id'] ) ? sanitize_text_field( wp_unslash( $_POST['group_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$groups = Person_Greeting_Groups::instance();
		$deleted = $groups->delete_group( $group_id );
		$this->redirect( $deleted ? array( 'deleted' => 'true' ) : array( 'delete_blocked' => 'true' ) );
	}

	public function handle_move() {
		$this->require_capability();
		check_admin_referer( self::NONCE_ACTION_MOVE );
		$group_id = isset( $_POST['group_id'] ) ? sanitize_text_field( wp_unslash( $_POST['group_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$direction = isset( $_POST['direction'] ) ? sanitize_key( wp_unslash( $_POST['direction'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		Person_Greeting_Groups::instance()->move_group( $group_id, $direction );
		$this->redirect();
	}

	private function require_capability() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			wp_die( esc_html__( 'この操作を行う権限がありません。', 'alumni-core' ) );
		}
	}

	private function redirect( array $args = array() ) {
		$args['page'] = self::SLUG;
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}
}
