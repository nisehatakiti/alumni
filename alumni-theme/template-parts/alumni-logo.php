<?php
/**
 * 同窓会ブランドロゴを大きく表示する専用セクション。
 *
 * ヘッダーの「学校情報」（校章・同窓会名・学校名）とはあえて役割を分離し、
 * 同窓会独自ロゴはここでのみ、大きく・全体が見える形（object-fit: contain）
 * で表示する。未設定、またはAlumni Core無効時は何も出力せず終了する。
 *
 * @package Alumni_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$alumni_logo_feature_html = alumni_theme_get_alumni_logo_html( 'large', 'alumni-logo-feature-img' );

if ( ! $alumni_logo_feature_html ) {
	return;
}
?>
<section class="alumni-logo-feature">
	<span class="alumni-logo-feature-image">
		<?php echo $alumni_logo_feature_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already-safe HTML from wp_get_attachment_image(). ?>
	</span>
</section>
