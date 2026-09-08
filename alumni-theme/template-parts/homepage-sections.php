<?php
/**
 * トップページのセクション（スロットベースのレイアウト）を描画する。
 * front-page.php から get_template_part() で呼び出される。
 *
 * Coreが持つのは「各スロットに何を表示するか」という選択結果だけ
 * （alumni_theme_get_homepage_sections()）— 実際の見た目（段組みのCSS・
 * カードの形）はここ、Theme側の責務。段数はCSS Gridのカラム数として
 * そのまま使う（alumni-homepage-section-columns-{1,2,3}、main.css参照）。
 *
 * @package Alumni_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$alumni_hp_sections = alumni_theme_get_homepage_sections();

if ( empty( $alumni_hp_sections ) ) {
	return;
}

foreach ( $alumni_hp_sections as $alumni_hp_section ) :
	// 全スロットが未設定(type=none)のセクションは、見出しだけの空箱を
	// 出さないよう丸ごとスキップする。
	$alumni_hp_has_content = false;
	foreach ( $alumni_hp_section['slots'] as $alumni_hp_probe_slot ) {
		if ( 'none' !== $alumni_hp_probe_slot['type'] ) {
			$alumni_hp_has_content = true;
			break;
		}
	}
	if ( ! $alumni_hp_has_content ) {
		continue;
	}
	?>
	<section class="alumni-homepage-section alumni-homepage-section-columns-<?php echo (int) $alumni_hp_section['columns']; ?>">
		<?php if ( $alumni_hp_section['heading'] ) : ?>
			<h2 class="alumni-homepage-section-heading"><?php echo esc_html( $alumni_hp_section['heading'] ); ?></h2>
		<?php endif; ?>

		<div class="alumni-homepage-section-grid">
			<?php foreach ( $alumni_hp_section['slots'] as $alumni_hp_slot ) : ?>
				<div class="alumni-homepage-slot">
					<?php if ( 'system' === $alumni_hp_slot['type'] ) : ?>

						<?php
						$alumni_hp_system_key   = $alumni_hp_slot['system_key'];
						$alumni_hp_system_label = alumni_theme_get_system_slot_label( $alumni_hp_system_key );
						$alumni_hp_system_url   = alumni_theme_get_system_slot_url( $alumni_hp_system_key );
						?>
						<?php if ( $alumni_hp_system_label && $alumni_hp_system_url ) : ?>
							<div class="alumni-homepage-slot-system alumni-homepage-slot-system-<?php echo esc_attr( $alumni_hp_system_key ); ?>">
								<h3 class="alumni-homepage-slot-title">
									<a href="<?php echo esc_url( $alumni_hp_system_url ); ?>"><?php echo esc_html( $alumni_hp_system_label ); ?></a>
								</h3>

								<?php if ( 'news' === $alumni_hp_system_key ) : ?>
									<?php $alumni_hp_teaser = alumni_theme_get_news_teaser( 3 ); ?>
									<?php if ( $alumni_hp_teaser && $alumni_hp_teaser->have_posts() ) : ?>
										<div class="alumni-homepage-slot-teaser-list">
											<?php
											// トップページはコンパクトな1行リスト表示
											// （news-event-row.php）を使う — カード型の
											// news-event-card.phpは/news/・/events/一覧や
											// 詳細ページ用のまま変更しない。
											while ( $alumni_hp_teaser->have_posts() ) :
												$alumni_hp_teaser->the_post();
												get_template_part( 'template-parts/news-event-row' );
											endwhile;
											wp_reset_postdata();
											?>
										</div>
									<?php endif; ?>
								<?php elseif ( 'events' === $alumni_hp_system_key ) : ?>
									<?php
									// イベントは「今後」(開催日が近い順)と「終了済み」
									// (開催日が新しい順)を見出しで明確に分けて表示する
									// — 今日のイベントは「今後」に含める
									// (alumni_core_get_events_split_by_date()参照)。
									$alumni_hp_upcoming_events = alumni_theme_get_upcoming_events( 3 );
									$alumni_hp_past_events     = alumni_theme_get_past_events( 3 );
									?>
									<?php if ( $alumni_hp_upcoming_events ) : ?>
										<h4 class="alumni-homepage-slot-teaser-heading"><?php esc_html_e( '今後のイベント', 'alumni-theme' ); ?></h4>
										<div class="alumni-homepage-slot-teaser-list">
											<?php
											foreach ( $alumni_hp_upcoming_events as $alumni_hp_event_post ) :
												setup_postdata( $alumni_hp_event_post );
												get_template_part( 'template-parts/news-event-row' );
											endforeach;
											wp_reset_postdata();
											?>
										</div>
									<?php endif; ?>
									<?php if ( $alumni_hp_past_events ) : ?>
										<h4 class="alumni-homepage-slot-teaser-heading"><?php esc_html_e( '終了したイベント', 'alumni-theme' ); ?></h4>
										<div class="alumni-homepage-slot-teaser-list">
											<?php
											foreach ( $alumni_hp_past_events as $alumni_hp_event_post ) :
												setup_postdata( $alumni_hp_event_post );
												get_template_part( 'template-parts/news-event-row' );
											endforeach;
											wp_reset_postdata();
											?>
										</div>
									<?php endif; ?>
								<?php endif; ?>
							</div>
						<?php endif; ?>

					<?php elseif ( 'content' === $alumni_hp_slot['type'] ) : ?>

						<?php
						$alumni_hp_content_post = alumni_theme_get_content( $alumni_hp_slot['content_id'] );
						?>
						<?php if ( $alumni_hp_content_post ) : ?>
							<?php
							$alumni_hp_content_terms    = alumni_theme_get_terms( $alumni_hp_content_post );
							$alumni_hp_content_greeting = alumni_theme_get_person_greeting( $alumni_hp_content_post );
							$alumni_hp_content_title    = $alumni_hp_content_terms ? $alumni_hp_content_terms['display_title'] : $alumni_hp_content_post->post_title;
							$alumni_hp_content_excerpt  = wp_trim_words( wp_strip_all_tags( $alumni_hp_content_post->post_content ), 30 );
							?>
							<div class="alumni-homepage-slot-content">
								<h3 class="alumni-homepage-slot-title">
									<a href="<?php echo esc_url( alumni_theme_get_content_url( $alumni_hp_content_post->ID ) ); ?>"><?php echo esc_html( $alumni_hp_content_title ); ?></a>
								</h3>
								<?php if ( $alumni_hp_content_greeting && $alumni_hp_content_greeting['name'] ) : ?>
									<p class="alumni-homepage-slot-content-person">
										<?php echo esc_html( $alumni_hp_content_greeting['name'] ); ?>
										<?php if ( $alumni_hp_content_greeting['title'] ) : ?>
											（<?php echo esc_html( $alumni_hp_content_greeting['title'] ); ?>）
										<?php endif; ?>
									</p>
								<?php endif; ?>
								<?php if ( $alumni_hp_content_excerpt ) : ?>
									<p class="alumni-homepage-slot-content-excerpt"><?php echo esc_html( $alumni_hp_content_excerpt ); ?></p>
								<?php endif; ?>
							</div>
						<?php endif; ?>

					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
endforeach;
