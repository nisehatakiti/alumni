<?php
/**
 * Archive template for ニュース／イベント (/news-events/).
 *
 * WordPress only ever routes here when the alumni_news_event post type
 * (and its rewrite rules) are registered, i.e. when Alumni Core is
 * active — so no separate Core-inactive fallback is needed in this file.
 *
 * @package Alumni_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="site-main news-events-archive">
	<h1 class="page-title"><?php esc_html_e( 'ニュース・イベント', 'alumni-theme' ); ?></h1>

	<?php if ( have_posts() ) : ?>
		<div class="alumni-news-events-grid">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/news-event-card' );
			endwhile;
			?>
		</div>

		<?php the_posts_pagination(); ?>
	<?php else : ?>
		<p><?php esc_html_e( '表示できるニュース・イベントがありません。', 'alumni-theme' ); ?></p>
	<?php endif; ?>
</main>

<?php
get_footer();
