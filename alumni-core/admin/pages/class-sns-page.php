<?php
namespace AlumniCore\Admin\Pages;
use AlumniCore\Admin\Admin;
use AlumniCore\Includes\Social_SNS;
use AlumniCore\Includes\Instagram_Feed;
if ( ! defined( 'ABSPATH' ) ) exit;

class SNS_Page {
	const SLUG = 'alumni-core-sns';
	const NONCE_ACTION = 'alumni_core_save_sns';

	public function render() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) return;
		$settings = Social_SNS::get_all();
		$instagram = isset( $settings[ Social_SNS::INSTAGRAM ] ) ? $settings[ Social_SNS::INSTAGRAM ] : array();
		?>
		<div class="wrap alumni-core-settings">
			<h1>SNS</h1>
			<p>公式SNSを設定します。有効にして設定を完了したSNSは、トップ画面・メニューの「コンテンツリンク」で選択できるようになります。</p>
			<?php if ( isset( $_GET['updated'] ) && 'true' === $_GET['updated'] ) : ?><div class="notice notice-success is-dismissible"><p>設定を保存しました。</p></div><?php endif; ?>
			<?php if ( isset( $_GET['instagram_connected'] ) && 'true' === $_GET['instagram_connected'] ) : ?><div class="notice notice-success is-dismissible"><p>Instagramアカウントを接続しました。</p></div><?php endif; ?>
			<?php if ( ! empty( $_GET['instagram_error'] ) ) : ?><div class="notice notice-error is-dismissible"><p><?php echo esc_html( rawurldecode( wp_unslash( $_GET['instagram_error'] ) ) ); ?></p></div><?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="alumni_core_save_sns">
				<?php wp_nonce_field( self::NONCE_ACTION ); ?>
				<table class="form-table" role="presentation">
				<?php foreach ( Social_SNS::services() as $key => $service ) : $row = $settings[ $key ]; ?>
					<tr>
						<th scope="row"><?php echo esc_html( $service['label'] ); ?></th>
						<td>
							<label><input type="checkbox" name="sns[<?php echo esc_attr( $key ); ?>][enabled]" value="1" <?php checked( ! empty( $row['enabled'] ) ); ?>> 有効にする</label>
							<?php if ( Social_SNS::X === $key ) : ?>
								<div class="alumni-core-sns-x-instructions" style="max-width:760px;margin-top:16px;"><p><strong>Xタイムラインの表示</strong></p><p>Xのタイムラインを表示するには、X公式のPublishページから埋め込みコードを生成してください。</p><ol><li>X公式Publishを開く：<a href="https://publish.x.com/" target="_blank" rel="noopener noreferrer">https://publish.x.com/</a></li><li>表示したいXアカウントのURLを入力する</li><li>「Embedded Timeline」を選択して、表示内容を設定する</li><li>生成された埋め込みコードをコピーする</li><li>下の「X埋め込みコード」欄に貼り付けて保存する</li></ol><p class="description">※埋め込みコードは、X公式Publishページで生成されたものをそのまま貼り付けてください。</p></div>
								<p><label for="alumni_core_<?php echo esc_attr( $key ); ?>">X埋め込みコード</label><br><textarea class="large-text code" rows="8" id="alumni_core_<?php echo esc_attr( $key ); ?>" name="sns[<?php echo esc_attr( $key ); ?>][embed_code]" placeholder="X公式Publishページで生成したコードを貼り付けてください。"><?php echo esc_textarea( isset( $row['embed_code'] ) ? $row['embed_code'] : '' ); ?></textarea></p>
							<?php elseif ( Social_SNS::INSTAGRAM === $key ) : ?>
								<div style="max-width:760px;margin-top:16px;"><p><strong>Instagramフィードの表示</strong></p><p>学校・同窓会など、表示したいInstagramアカウントの管理者が接続を許可することで、そのアカウントの投稿をサイトに表示できます。</p><p><strong>手順</strong></p><ol><li>表示するInstagramアカウントをプロフェッショナルアカウント（ビジネスまたはクリエイター）に切り替えます。</li><li>Meta for DevelopersでInstagramアプリを作成し、Instagram Business Loginを設定します。</li><li>このサイトのOAuthコールバックURLをMeta側に登録します。</li><li>表示したいInstagramアカウントの管理者が、下の「Instagramを接続」からログインしてアクセスを許可します。</li></ol><?php if ( Instagram_Feed::is_configured() ) : ?><p><a class="button button-primary" href="<?php echo esc_url( Instagram_Feed::connect_url() ); ?>">Instagramを接続</a></p><?php else : ?><p><strong>Metaアプリの設定が必要です。</strong></p><p class="description">wp-config.php に <code>ALUMNI_INSTAGRAM_APP_ID</code> と <code>ALUMNI_INSTAGRAM_APP_SECRET</code> を設定してから接続してください。</p><?php endif; ?><?php if ( ! empty( $instagram['username'] ) ) : ?><p>接続中のアカウント：<strong>@<?php echo esc_html( $instagram['username'] ); ?></strong></p><?php endif; ?><p class="description">Instagramのログイン情報そのものはAlumni Coreに保存せず、許可されたアクセストークンを使って投稿を取得します。</p></div>
							<?php else : ?>
								<p><label for="alumni_core_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $service['field_label'] ); ?></label><br><input class="regular-text code" type="url" id="alumni_core_<?php echo esc_attr( $key ); ?>" name="sns[<?php echo esc_attr( $key ); ?>][url]" value="<?php echo esc_attr( $row['url'] ); ?>" placeholder="https://"></p>
							<?php endif; ?>
							<p class="description"><?php echo Social_SNS::X === $key ? '有効化とX埋め込みコードの設定が完了した場合に、公開コンテンツとして表示されます。' : ( Social_SNS::INSTAGRAM === $key ? '有効化とInstagramアカウントの接続が完了した場合に、公開コンテンツとして表示されます。' : '有効化とURL設定の両方が完了したSNSのみ、公開コンテンツとして表示されます。' ); ?></p>
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
		$input = isset( $_POST['sns'] ) ? (array) $_POST['sns'] : array();
		Social_SNS::save( $input );
		wp_safe_redirect( add_query_arg( array( 'page' => self::SLUG, 'updated' => 'true' ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
