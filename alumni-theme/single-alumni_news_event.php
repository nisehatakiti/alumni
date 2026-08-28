<?php
/**
 * Single template for ニュース／イベント (/news-events/{slug}/).
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

while ( have_posts() ) :
	the_post();

	$alumni_news_event_type     = alumni_theme_get_news_event_type_label();
	$alumni_news_event_date     = alumni_theme_get_news_event_date_display();
	$alumni_news_event_is_event = alumni_theme_is_event();
	?>
	<main id="primary" class="site-main news-event-single">
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'alumni-news-event' ); ?>>
			<header class="entry-header">
				<?php if ( $alumni_news_event_is_event && $alumni_news_event_date ) : ?>
					<time class="alumni-news-event-date alumni-news-event-date-event"><?php echo esc_html( $alumni_news_event_date ); ?></time>
				<?php endif; ?>

				<?php if ( $alumni_news_event_type ) : ?>
					<span class="alumni-news-event-type"><?php echo esc_html( $alumni_news_event_type ); ?></span>
				<?php endif; ?>

				<h1 class="entry-title"><?php the_title(); ?></h1>

				<?php if ( ! $alumni_news_event_is_event && $alumni_news_event_date ) : ?>
					<time class="alumni-news-event-date"><?php echo esc_html( $alumni_news_event_date ); ?></time>
				<?php endif; ?>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<div class="entry-thumbnail">
					<?php the_post_thumbnail( 'large' ); ?>
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
