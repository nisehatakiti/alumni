<?php
/**
 * The front page template.
 *
 * Kept intentionally simple for this foundation phase: a short welcome
 * area plus the standard post loop. Dedicated sections (新着情報, 会長挨拶,
 * 沿革, Alumni Voices, ...) are introduced in later phases once Core
 * provides that data.
 *
 * @package Alumni_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="site-main front-page">
	<section class="front-welcome">
		<h1><?php echo esc_html( alumni_theme_get_association_name() ); ?></h1>

		<?php if ( ! alumni_theme_core_active() ) : ?>
			<p class="alumni-notice">
				<?php esc_html_e( 'Alumni Core プラグインを有効にすると、同窓会の基本情報がここに表示されます。', 'alumni-theme' ); ?>
			</p>
		<?php endif; ?>
	</section>

	<?php if ( alumni_theme_core_active() ) : ?>
		<section class="front-news-events">
			<div class="front-news-events-header">
				<h2><?php esc_html_e( 'お知らせ・イベント', 'alumni-theme' ); ?></h2>
				<a class="front-news-events-more" href="<?php echo esc_url( get_post_type_archive_link( alumni_core_news_events_post_type() ) ); ?>">
					<?php esc_html_e( 'すべて見る', 'alumni-theme' ); ?>
				</a>
			</div>

			<?php
			$alumni_front_news_events = alumni_theme_get_news_events( array( 'posts_per_page' => 5 ) );
			?>
			<?php if ( $alumni_front_news_events && $alumni_front_news_events->have_posts() ) : ?>
				<div class="alumni-news-events-grid">
					<?php
					while ( $alumni_front_news_events->have_posts() ) :
						$alumni_front_news_events->the_post();
						get_template_part( 'template-parts/news-event-card' );
					endwhile;
					wp_reset_postdata();
					?>
				</div>
			<?php else : ?>
				<p><?php esc_html_e( '現在、お知らせ・イベントはありません。', 'alumni-theme' ); ?></p>
			<?php endif; ?>
		</section>
	<?php endif; ?>

	<section class="front-posts">
		<?php if ( have_posts() ) : ?>
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/content' );
			endwhile;
			?>
		<?php else : ?>
			<p><?php esc_html_e( '表示できるコンテンツがありません。', 'alumni-theme' ); ?></p>
		<?php endif; ?>
	</section>
</main>

<?php
get_footer();
