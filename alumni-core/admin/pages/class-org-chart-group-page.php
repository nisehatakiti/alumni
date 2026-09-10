<?php
namespace AlumniCore\Admin\Pages;

use AlumniCore\Admin\Admin;
use AlumniCore\Includes\Org_Chart_Groups;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Org_Chart_Group_Page {
	const SLUG = 'alumni-core-org-chart-groups';
	const CREATE_ACTION = 'alumni_core_create_org_chart_group';
	const UPDATE_ACTION = 'alumni_core_update_org_chart_group';
	const DELETE_ACTION = 'alumni_core_delete_org_chart_group';

	public function render() {
		$groups = Org_Chart_Groups::instance()->get_all();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( '組織図グループ管理', 'alumni-core' ); ?></h1>
			<p class="description"><?php esc_html_e( '組織図専用のグループです。組織名簿や人物挨拶のグループとは共有しません。', 'alumni-core' ); ?></p>
			<h2><?php esc_html_e( 'グループを追加', 'alumni-core' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::CREATE_ACTION ); ?>" />
				<?php wp_nonce_field( self::CREATE_ACTION ); ?>
				<input type="text" name="name" class="regular-text" required />
				<?php submit_button( __( '追加', 'alumni-core' ), 'secondary', 'submit', false ); ?>
			</form>
			<h2><?php esc_html_e( '登録済みグループ', 'alumni-core' ); ?></h2>
			<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'グループ名', 'alumni-core' ); ?></th><th><?php esc_html_e( '操作', 'alumni-core' ); ?></th></tr></thead><tbody>
			<?php foreach ( $groups as $group ) : ?><tr><td>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="<?php echo esc_attr( self::UPDATE_ACTION ); ?>" /><input type="hidden" name="group_id" value="<?php echo esc_attr( $group['group_id'] ); ?>" />
					<?php wp_nonce_field( self::UPDATE_ACTION ); ?><input type="text" name="name" value="<?php echo esc_attr( $group['name'] ); ?>" class="regular-text" /> <?php submit_button( __( '名称変更', 'alumni-core' ), 'secondary', 'submit', false ); ?>
				</form>
			</td><td><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'グループを削除します。組織図自体は削除されません。', 'alumni-core' ) ); ?>');">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::DELETE_ACTION ); ?>" /><input type="hidden" name="group_id" value="<?php echo esc_attr( $group['group_id'] ); ?>" /><?php wp_nonce_field( self::DELETE_ACTION ); ?><button class="button button-small"><?php esc_html_e( '削除', 'alumni-core' ); ?></button>
			</form></td></tr><?php endforeach; ?>
			<?php if ( empty( $groups ) ) : ?><tr><td colspan="2"><?php esc_html_e( 'グループはまだありません。', 'alumni-core' ); ?></td></tr><?php endif; ?>
			</tbody></table>
		</div>
		<?php
	}
	public function handle_create(){ $this->guard(self::CREATE_ACTION); $name=isset($_POST['name'])?wp_unslash($_POST['name']):''; Org_Chart_Groups::instance()->create_group($name); $this->redirect(); }
	public function handle_update(){ $this->guard(self::UPDATE_ACTION); Org_Chart_Groups::instance()->update_group(isset($_POST['group_id'])?sanitize_text_field(wp_unslash($_POST['group_id'])):'',isset($_POST['name'])?wp_unslash($_POST['name']):''); $this->redirect(); }
	public function handle_delete(){ $this->guard(self::DELETE_ACTION); Org_Chart_Groups::instance()->delete_group(isset($_POST['group_id'])?sanitize_text_field(wp_unslash($_POST['group_id'])):''); $this->redirect(); }
	private function guard($action){ if(!current_user_can(Admin::CAPABILITY)){wp_die(esc_html__('この操作を行う権限がありません。','alumni-core'));} check_admin_referer($action); }
	private function redirect(){ wp_safe_redirect(add_query_arg(array('page'=>self::SLUG,'updated'=>'1'),admin_url('admin.php'))); exit; }
}
