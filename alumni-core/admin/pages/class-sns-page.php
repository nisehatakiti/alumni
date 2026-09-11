<?php
namespace AlumniCore\Admin\Pages;
use AlumniCore\Admin\Admin;
use AlumniCore\Includes\Social_SNS;
if ( ! defined( 'ABSPATH' ) ) exit;

class SNS_Page {
	const SLUG = 'alumni-core-sns';
	const NONCE_ACTION = 'alumni_core_save_sns';

	public function render() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) return;
		$settings = Social_SNS::get_all();
		?>
		<div class="wrap alumni-core-settings">
			<h1>SNS</h1>
			<p>公式SNSを設定します。有効にしてURLを設定したSNSは、トップ画面・メニューの「コンテンツリンク」で選択できるようになります。</p>
			<?php if ( isset( $_GET['updated'] ) && 'true' === $_GET['updated'] ) : ?>
				<div class="notice notice-success is-dismissible"><p>設定を保存しました。</p></div>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="alumni_core_save_sns">
				<?php wp_nonce_field( self::NONCE_ACTION ); ?>
				<table class="form-table" role="presentation">
				<?php foreach ( Social_SNS::services() as $key => $service ) : $row = $settings[ $key ]; ?>
					<tr>
						<th scope="row"><?php echo esc_html( $service['label'] ); ?></th>
						<td>
							<label><input type="checkbox" name="sns[<?php echo esc_attr( $key ); ?>][enabled]" value="1" <?php checked( ! empty( $row['enabled'] ) ); ?>> 有効にする</label>
							<p><label for="alumni_core_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $service['field_label'] ); ?></label><br>
							<input class="regular-text code" type="url" id="alumni_core_<?php echo esc_attr( $key ); ?>" name="sns[<?php echo esc_attr( $key ); ?>][url]" value="<?php echo esc_attr( $row['url'] ); ?>" placeholder="https://"></p>
							<p class="description">有効化とURL設定の両方が完了したSNSのみ、公開コンテンツとして表示されます。</p>
						</td>
					</tr>
				<?php endforeach; ?>
				</table>
				<?php submit_button( 'SNS設定を保存' ); ?>
			</form>
		</div>
		<?php
	}

	public function handle_save() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) wp_die( 'この操作を行う権限がありません。' );
		check_admin_referer( self::NONCE_ACTION );
		// Nonce verified above.
		$input = isset( $_POST['sns'] ) ? (array) $_POST['sns'] : array();
		Social_SNS::save( $input );
		wp_safe_redirect( add_query_arg( array( 'page' => self::SLUG, 'updated' => 'true' ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
