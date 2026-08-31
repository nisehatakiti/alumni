<?php
/**
 * Single template for コンテンツ (/contents/{slug}/).
 *
 * Both 自由コンテンツ and 人物挨拶 share this one template — the CPT and
 * URL structure are the same for both (see Alumni Core's Content module);
 * only the extra 氏名／肩書／卒業期／顔写真 block is conditional on kind.
 * Without this template, WordPress would fall back to the theme's generic
 * index.php + template-parts/content.php, which shows the title and body
 * but has no idea a 人物挨拶 post carries those extra fields at all — this
 * is what "登録・公開はできるのに実際の内容が表示されない" meant in
 * practice: the post *was* reachable, just missing the fields that make
 * it a 人物挨拶 rather than a generic page.
 *
 * WordPress only ever routes here when the alumni_content post type (and
 * its rewrite rules) are registered, i.e. when Alumni Core is active —
 * so no separate Core-inactive fallback is needed in this file.
 *
 * @package Alumni_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	// alumni_theme_get_person_greeting() already returns null for 自由
	// コンテンツ (or when Core is inactive), so its own return value is
	// the only branch check this template needs.
	$alumni_greeting = alumni_theme_get_person_greeting();
	?>
	<main id="primary" class="site-main alumni-content-single">
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'alumni-content-entry' ); ?>>
			<header class="entry-header">
				<h1 class="entry-title"><?php the_title(); ?></h1>
			</header>

			<?php if ( $alumni_greeting ) : ?>
				<div class="alumni-person-greeting-meta">
					<?php if ( $alumni_greeting['photo_id'] ) : ?>
						<div class="alumni-person-photo">
							<?php echo wp_get_attachment_image( $alumni_greeting['photo_id'], 'medium' ); ?>
						</div>
					<?php endif; ?>

					<p class="alumni-person-name">
						<?php echo esc_html( $alumni_greeting['name'] ); ?>
						<?php if ( $alumni_greeting['kana'] ) : ?>
							<span class="alumni-person-kana">（<?php echo esc_html( $alumni_greeting['kana'] ); ?>）</span>
						<?php endif; ?>
					</p>

					<?php if ( $alumni_greeting['title'] ) : ?>
						<p class="alumni-person-title"><?php echo esc_html( $alumni_greeting['title'] ); ?></p>
					<?php endif; ?>

					<?php if ( $alumni_greeting['term'] ) : ?>
						<p class="alumni-person-term">
							<?php
							printf(
								/* translators: %d: graduation term (期) */
								esc_html__( '第%d期', 'alumni-theme' ),
								(int) $alumni_greeting['term']
							);
							?>
						</p>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="entry-content">
				<?php the_content(); ?>
			</div>
		</article>
	</main>
	<?php
endwhile;

get_footer();
