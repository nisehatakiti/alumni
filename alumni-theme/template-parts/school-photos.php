<?php
/**
 * 学校関連写真セクション（固定表示／自動切替）。
 *
 * 写真が1枚も登録されていない場合、またはAlumni Coreが無効な場合は
 * 何も出力せず終了する（空白のセクションを残さない）。
 *
 * @package Alumni_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$alumni_photo_display_mode = alumni_theme_get_school_photo_display_mode();

if ( 'slideshow' === $alumni_photo_display_mode ) {
	$alumni_photo_ids = alumni_theme_get_school_photo_ids();
} else {
	$alumni_featured_id = alumni_theme_get_featured_school_photo_id();
	$alumni_photo_ids   = $alumni_featured_id ? array( $alumni_featured_id ) : array();
}

if ( empty( $alumni_photo_ids ) ) {
	return;
}

$alumni_is_slideshow = 'slideshow' === $alumni_photo_display_mode && count( $alumni_photo_ids ) > 1;
?>
<section class="school-photos <?php echo $alumni_is_slideshow ? 'school-photos-slideshow' : 'school-photos-fixed'; ?>">
	<div class="school-photos-track">
		<?php foreach ( $alumni_photo_ids as $alumni_index => $alumni_photo_id ) : ?>
			<div class="school-photo-slide<?php echo 0 === $alumni_index ? ' is-active' : ''; ?>">
				<?php echo wp_get_attachment_image( $alumni_photo_id, 'large', false, array( 'class' => 'school-photo-image' ) ); ?>
			</div>
		<?php endforeach; ?>
	</div>
</section>
