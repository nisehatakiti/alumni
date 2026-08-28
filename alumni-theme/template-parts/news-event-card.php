<?php
/**
 * ニュース／イベント一件分のカード表示。
 * 前提: the_post() 済みのループ内で呼び出すこと（メインループ・
 * カスタムWP_Queryのいずれでも可）。
 *
 * @package Alumni_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$alumni_news_event_type      = alumni_theme_get_news_event_type_label();
$alumni_news_event_date      = alumni_theme_get_news_event_date_display();
$alumni_news_event_has_thumb = has_post_thumbnail();
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'alumni-news-event-card' . ( $alumni_news_event_has_thumb ? ' has-thumbnail' : '' ) ); ?>>
	<a class="alumni-news-event-card-link" href="<?php the_permalink(); ?>">
		<?php if ( $alumni_news_event_has_thumb ) : ?>
			<div class="alumni-news-event-card-thumb">
				<?php the_post_thumbnail( 'medium' ); ?>
			</div>
		<?php endif; ?>

		<div class="alumni-news-event-card-body">
			<?php if ( $alumni_news_event_type ) : ?>
				<span class="alumni-news-event-type"><?php echo esc_html( $alumni_news_event_type ); ?></span>
			<?php endif; ?>

			<h3 class="alumni-news-event-card-title"><?php the_title(); ?></h3>

			<?php if ( $alumni_news_event_date ) : ?>
				<time class="alumni-news-event-card-date"><?php echo esc_html( $alumni_news_event_date ); ?></time>
			<?php endif; ?>
		</div>
	</a>
</article>
