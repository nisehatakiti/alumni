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
		<?php get_template_part( 'template-parts/alumni-logo' ); ?>
	<?php endif; ?>

	<?php if ( alumni_theme_core_active() ) : ?>
		<?php get_template_part( 'template-parts/school-photos' ); ?>
	<?php endif; ?>

	<?php if ( alumni_theme_core_active() ) : ?>
		<section class="front-news-events">
			<div class="front-news-events-header">
				<h2><?php esc_html_e( 'お知らせ・イベント', 'alumni-theme' ); ?></h2>
			</div>

			<?php
			// alumni_core_get_news_events_query()（wp-content 側）は
			// orderby => 'date', order => 'DESC' を既定にしており、ニュース・
			// イベントを問わず「投稿日時」順で並ぶ — イベントの開催日順には
			// ならない。
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

			<div class="front-news-events-links">
				<a class="front-news-events-link" href="<?php echo esc_url( alumni_theme_get_news_listing_url() ); ?>">
					<?php esc_html_e( 'ニュース一覧を見る', 'alumni-theme' ); ?>
				</a>
				<a class="front-news-events-link" href="<?php echo esc_url( alumni_theme_get_events_listing_url() ); ?>">
					<?php esc_html_e( 'イベント一覧を見る', 'alumni-theme' ); ?>
				</a>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( alumni_theme_core_active() ) : ?>
		<section class="front-quick-links">
			<h2><?php esc_html_e( '同窓会情報', 'alumni-theme' ); ?></h2>
			<ul class="front-quick-links-list">
				<?php if ( alumni_theme_get_officers_listing_url() ) : ?>
					<li>
						<a class="front-quick-links-link" href="<?php echo esc_url( alumni_theme_get_officers_listing_url() ); ?>">
							<?php esc_html_e( '役員・理事紹介を見る', 'alumni-theme' ); ?>
						</a>
					</li>
				<?php endif; ?>
				<?php if ( alumni_theme_get_graduation_lookup_url() ) : ?>
					<li>
						<a class="front-quick-links-link" href="<?php echo esc_url( alumni_theme_get_graduation_lookup_url() ); ?>">
							<?php esc_html_e( '卒業期早見表を見る', 'alumni-theme' ); ?>
						</a>
					</li>
				<?php endif; ?>
			</ul>
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
