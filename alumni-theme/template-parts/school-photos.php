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

// alumni_theme_get_school_photo_ids()/_get_featured_school_photo_id() already
// filter out attachments that no longer exist, but render defensively anyway:
// build each slide's HTML first and drop any that come back empty, so a
// deleted-between-resolve-and-render race (or any future change to those
// Core helpers) can never leave an empty .school-photo-slide box in the
// markup.
$alumni_photo_slides = array();

foreach ( $alumni_photo_ids as $alumni_photo_id ) {
	$alumni_image_html = wp_get_attachment_image( $alumni_photo_id, 'large', false, array( 'class' => 'school-photo-image' ) );

	if ( $alumni_image_html ) {
		$alumni_photo_slides[] = $alumni_image_html;
	}
}

if ( empty( $alumni_photo_slides ) ) {
	return;
}

// Re-check after dropping empties: e.g. 自動切替 with two registered
// photos where one turned out to be invalid still ends up as a single
// slide, and must render (and behave) exactly like 固定表示, not run the
// slideshow JS over a single slide.
$alumni_is_slideshow = 'slideshow' === $alumni_photo_display_mode && count( $alumni_photo_slides ) > 1;
?>
<section class="school-photos <?php echo $alumni_is_slideshow ? 'school-photos-slideshow' : 'school-photos-fixed'; ?>">
	<div class="school-photos-track">
		<?php foreach ( $alumni_photo_slides as $alumni_index => $alumni_image_html ) : ?>
			<div class="school-photo-slide<?php echo 0 === $alumni_index ? ' is-active' : ''; ?>">
				<?php echo $alumni_image_html; ?>
			</div>
		<?php endforeach; ?>
	</div>
</section>
