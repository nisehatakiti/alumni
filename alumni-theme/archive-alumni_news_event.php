<?php
/**
 * Archive template for ニュース／イベント. Shared by three URLs: the
 * combined /news-events/ archive, and the /news/ and /events/ listings
 * (Listing_Rewrites in Alumni Core) — all three route here since they all
 * resolve to a post-type-archive query for alumni_news_event, just with
 * a different content-type filter applied. The heading below is the only
 * thing that varies between them.
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

$alumni_listing_type = alumni_theme_get_news_events_listing_type();

if ( 'news' === $alumni_listing_type ) {
	$alumni_archive_title = __( 'ニュース一覧', 'alumni-theme' );
} elseif ( 'event' === $alumni_listing_type ) {
	$alumni_archive_title = __( 'イベント一覧', 'alumni-theme' );
} else {
	$alumni_archive_title = __( 'ニュース・イベント', 'alumni-theme' );
}

get_header();
?>

<main id="primary" class="site-main news-events-archive">
	<h1 class="page-title"><?php echo esc_html( $alumni_archive_title ); ?></h1>

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
