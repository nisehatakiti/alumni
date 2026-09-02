<?php
/**
 * トップページのニュース一覧／イベント一覧スロット専用の、1行あたり
 * コンパクトな行表示（[日付] [タイトル]、タイトル全体がリンク）。
 * 前提: the_post() 済みのループ内で呼び出すこと（メインループ・
 * カスタムWP_Queryのいずれでも可）。
 *
 * カード型のnews-event-card.php（/news/・/events/一覧や詳細への遷移先）
 * とは別の、トップページ専用テンプレートパーツ — トップページの情報密度を
 * 抑えるための表示だけを目的とし、/news/・/events/一覧そのものの見た目は
 * 変更しない。更新日はここでは表示しない（詳細ページの表示は維持）。
 *
 * @package Alumni_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$alumni_news_event_date = alumni_theme_get_news_event_date_display();
?>
<a class="alumni-news-event-row" href="<?php the_permalink(); ?>">
	<?php if ( $alumni_news_event_date ) : ?>
		<time class="alumni-news-event-row-date"><?php echo esc_html( $alumni_news_event_date ); ?></time>
	<?php endif; ?>
	<span class="alumni-news-event-row-title"><?php the_title(); ?></span>
</a>
