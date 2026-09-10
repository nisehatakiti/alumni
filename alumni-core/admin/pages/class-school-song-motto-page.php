<?php
/**
 * 同窓会 > 校歌・校訓 screen.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Admin\Pages;

use AlumniCore\Admin\Admin;
use AlumniCore\Includes\School_Song_Motto_Shortcode;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class School_Song_Motto_Page {
	const SLUG = 'alumni-core-school-song-motto';
	const NONCE_ACTION = 'alumni_core_save_school_song_motto';

	public function render() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) { return; }
		$mode = School_Song_Motto_Shortcode::get_display_mode();
		$titles = School_Song_Motto_Shortcode::get_titles();
		$song = School_Song_Motto_Shortcode::get_song_data();
		$motto = School_Song_Motto_Shortcode::get_motto_data();
		?>
		<div class="wrap alumni-core-school-song-motto">
			<h1><?php esc_html_e( '校歌・校訓', 'alumni-core' ); ?></h1>
			<?php if ( isset( $_GET['updated'] ) && 'true' === $_GET['updated'] ) : ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e( '設定を保存しました。', 'alumni-core' ); ?></p></div><?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="alumni_core_save_school_song_motto" />
				<?php wp_nonce_field( self::NONCE_ACTION ); ?>
				<h2><?php esc_html_e( '公開設定', 'alumni-core' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr><th><?php esc_html_e( '公開形式', 'alumni-core' ); ?></th><td>
						<label><input type="radio" name="display_mode" value="combined" <?php checked( 'combined', $mode ); ?> /> <?php esc_html_e( '校歌・校訓を同じページに表示', 'alumni-core' ); ?></label><br />
						<label><input type="radio" name="display_mode" value="separate" <?php checked( 'separate', $mode ); ?> /> <?php esc_html_e( '校歌と校訓を別々のページに表示', 'alumni-core' ); ?></label>
					</td></tr>
					<tr><th><label for="alumni-school-song-motto-combined-title"><?php esc_html_e( '校歌・校訓ページタイトル', 'alumni-core' ); ?></label></th><td><input type="text" class="regular-text" id="alumni-school-song-motto-combined-title" name="page_titles[combined]" value="<?php echo esc_attr( $titles['combined'] ); ?>" /></td></tr>
					<tr><th><label for="alumni-school-song-title-page"><?php esc_html_e( '校歌ページタイトル', 'alumni-core' ); ?></label></th><td><input type="text" class="regular-text" id="alumni-school-song-title-page" name="page_titles[song]" value="<?php echo esc_attr( $titles['song'] ); ?>" /></td></tr>
					<tr><th><label for="alumni-school-motto-title-page"><?php esc_html_e( '校訓ページタイトル', 'alumni-core' ); ?></label></th><td><input type="text" class="regular-text" id="alumni-school-motto-title-page" name="page_titles[motto]" value="<?php echo esc_attr( $titles['motto'] ); ?>" /></td></tr>
				</table>

				<h2><?php esc_html_e( '校歌', 'alumni-core' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr><th><label for="song-title"><?php esc_html_e( '校歌タイトル', 'alumni-core' ); ?></label></th><td><input type="text" class="regular-text" id="song-title" name="song[title]" value="<?php echo esc_attr( $song['title'] ); ?>" /></td></tr>
					<tr><th><label for="song-lyricist"><?php esc_html_e( '作詞', 'alumni-core' ); ?></label></th><td><input type="text" class="regular-text" id="song-lyricist" name="song[lyricist]" value="<?php echo esc_attr( $song['lyricist'] ); ?>" /></td></tr>
					<tr><th><label for="song-composer"><?php esc_html_e( '作曲', 'alumni-core' ); ?></label></th><td><input type="text" class="regular-text" id="song-composer" name="song[composer]" value="<?php echo esc_attr( $song['composer'] ); ?>" /></td></tr>
					<tr><th><label for="song-lyrics"><?php esc_html_e( '歌詞', 'alumni-core' ); ?></label></th><td><textarea class="large-text code" rows="12" id="song-lyrics" name="song[lyrics]"><?php echo esc_textarea( $song['lyrics'] ); ?></textarea><p class="description"><?php esc_html_e( '入力した改行は公開側でも保持されます。', 'alumni-core' ); ?></p></td></tr>
					<tr><th><?php esc_html_e( '校歌音源', 'alumni-core' ); ?></th><td><?php $this->media_field( 'song_audio_id', $song['audio_id'], __( '音源を選択', 'alumni-core' ), 'audio' ); ?></td></tr>
					<tr><th><?php esc_html_e( '楽譜', 'alumni-core' ); ?></th><td><?php $this->media_field( 'song_sheet_id', $song['sheet_id'], __( '楽譜を選択', 'alumni-core' ), 'application/pdf' ); ?></td></tr>
				</table>

				<h2><?php esc_html_e( '校訓', 'alumni-core' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr><th><?php esc_html_e( '表示形式', 'alumni-core' ); ?></th><td>
						<label><input type="radio" name="motto[display_type]" value="text" <?php checked( 'text', $motto['display_type'] ); ?> /> <?php esc_html_e( 'テキスト', 'alumni-core' ); ?></label><br />
						<label><input type="radio" name="motto[display_type]" value="image" <?php checked( 'image', $motto['display_type'] ); ?> /> <?php esc_html_e( '画像', 'alumni-core' ); ?></label><br />
						<label><input type="radio" name="motto[display_type]" value="both" <?php checked( 'both', $motto['display_type'] ); ?> /> <?php esc_html_e( 'テキスト＋画像', 'alumni-core' ); ?></label>
					</td></tr>
					<tr><th><label for="motto-text"><?php esc_html_e( '校訓テキスト', 'alumni-core' ); ?></label></th><td><textarea class="large-text" rows="4" id="motto-text" name="motto[text]"><?php echo esc_textarea( $motto['text'] ); ?></textarea></td></tr>
					<tr><th><?php esc_html_e( '校訓画像', 'alumni-core' ); ?></th><td><?php $this->media_field( 'motto_image_id', $motto['image_id'], __( '校訓画像を選択', 'alumni-core' ), 'image' ); ?></td></tr>
					<tr><th><label for="motto-description"><?php esc_html_e( '校訓の説明', 'alumni-core' ); ?></label></th><td><textarea class="large-text" rows="8" id="motto-description" name="motto[description]"><?php echo esc_textarea( $motto['description'] ); ?></textarea><p class="description"><?php esc_html_e( '任意項目です。分からない場合は空欄のままで構いません。', 'alumni-core' ); ?></p></td></tr>
				</table>
				<?php submit_button( __( '設定を保存', 'alumni-core' ) ); ?>
			</form>
		</div>
		<?php
	}

	private function media_field( $name, $id, $button, $type ) {
		$id = absint( $id );
		$url = $id ? wp_get_attachment_url( $id ) : '';
		?>
		<div class="alumni-song-motto-media-field" data-library-type="<?php echo esc_attr( $type ); ?>">
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $id ); ?>" />
			<span class="alumni-song-motto-media-name"><?php echo esc_html( $id ? ( get_the_title( $id ) ? get_the_title( $id ) : basename( (string) $url ) ) : __( '未選択', 'alumni-core' ) ); ?></span><br />
			<button type="button" class="button alumni-song-motto-media-select"><?php echo esc_html( $button ); ?></button>
			<button type="button" class="button alumni-song-motto-media-clear" <?php echo $id ? '' : 'style="display:none"'; ?>><?php esc_html_e( '削除', 'alumni-core' ); ?></button>
		</div>
		<?php
	}

	public function handle_save() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) { wp_die( esc_html__( 'この操作を行う権限がありません。', 'alumni-core' ) ); }
		check_admin_referer( self::NONCE_ACTION );
		$mode = isset( $_POST['display_mode'] ) ? sanitize_key( wp_unslash( $_POST['display_mode'] ) ) : 'combined';
		$mode = in_array( $mode, array( 'combined', 'separate' ), true ) ? $mode : 'combined';
		$raw_titles = isset( $_POST['page_titles'] ) && is_array( $_POST['page_titles'] ) ? wp_unslash( $_POST['page_titles'] ) : array();
		$titles = array(
			'combined' => isset( $raw_titles['combined'] ) && '' !== trim( (string) $raw_titles['combined'] ) ? sanitize_text_field( $raw_titles['combined'] ) : __( '校歌・校訓', 'alumni-core' ),
			'song' => isset( $raw_titles['song'] ) && '' !== trim( (string) $raw_titles['song'] ) ? sanitize_text_field( $raw_titles['song'] ) : __( '校歌', 'alumni-core' ),
			'motto' => isset( $raw_titles['motto'] ) && '' !== trim( (string) $raw_titles['motto'] ) ? sanitize_text_field( $raw_titles['motto'] ) : __( '校訓', 'alumni-core' ),
		);
		$raw_song = isset( $_POST['song'] ) && is_array( $_POST['song'] ) ? wp_unslash( $_POST['song'] ) : array();
		$song = array(
			'title' => isset( $raw_song['title'] ) ? sanitize_text_field( $raw_song['title'] ) : '',
			'lyricist' => isset( $raw_song['lyricist'] ) ? sanitize_text_field( $raw_song['lyricist'] ) : '',
			'composer' => isset( $raw_song['composer'] ) ? sanitize_text_field( $raw_song['composer'] ) : '',
			'lyrics' => isset( $raw_song['lyrics'] ) ? sanitize_textarea_field( $raw_song['lyrics'] ) : '',
			'audio_id' => isset( $_POST['song_audio_id'] ) ? absint( $_POST['song_audio_id'] ) : 0,
			'sheet_id' => isset( $_POST['song_sheet_id'] ) ? absint( $_POST['song_sheet_id'] ) : 0,
		);
		$raw_motto = isset( $_POST['motto'] ) && is_array( $_POST['motto'] ) ? wp_unslash( $_POST['motto'] ) : array();
		$display_type = isset( $raw_motto['display_type'] ) ? sanitize_key( $raw_motto['display_type'] ) : 'text';
		$display_type = in_array( $display_type, array( 'text', 'image', 'both' ), true ) ? $display_type : 'text';
		$motto = array(
			'display_type' => $display_type,
			'text' => isset( $raw_motto['text'] ) ? sanitize_textarea_field( $raw_motto['text'] ) : '',
			'image_id' => isset( $_POST['motto_image_id'] ) ? absint( $_POST['motto_image_id'] ) : 0,
			'description' => isset( $raw_motto['description'] ) ? sanitize_textarea_field( $raw_motto['description'] ) : '',
		);
		update_option( 'alumni_core_school_song_motto_display_mode', $mode );
		update_option( 'alumni_core_school_song_motto_page_titles', $titles );
		update_option( 'alumni_core_school_song_data', $song );
		update_option( 'alumni_core_school_motto_data', $motto );
		School_Song_Motto_Shortcode::maybe_create_pages();
		wp_safe_redirect( add_query_arg( array( 'page' => self::SLUG, 'updated' => 'true' ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
