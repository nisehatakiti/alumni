<?php
/**
 * サイト全体のメインナビゲーション（上部メニュー／左サイドメニュー
 * どちらのレイアウトでも同じマークアップを使う — 見た目の切替は
 * body_class()とCSSだけで行う。header.php から get_template_part() で
 * 呼び出される。
 *
 * 固定項目（ホーム／ニュース／イベント／役員・理事紹介／卒業期早見表）に
 * 加えて、「同窓会情報」というドリルダウン（親子）メニューを1つ持つ。
 * その配下（会長挨拶・校長挨拶など）は固定文字列としてハードコードせず、
 * 公開されている「人物挨拶」コンテンツから毎回動的に生成する — 管理者が
 * 人物挨拶コンテンツを新規作成するだけで、メニュー側の変更なしに
 * 自動的にここへ現れる。
 *
 * @package AlumniTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<ul id="primary-menu" class="alumni-nav-menu">
	<li class="alumni-nav-item">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'ホーム', 'alumni-theme' ); ?></a>
	</li>
	<?php if ( alumni_theme_get_news_listing_url() ) : ?>
		<li class="alumni-nav-item">
			<a href="<?php echo esc_url( alumni_theme_get_news_listing_url() ); ?>"><?php esc_html_e( 'ニュース', 'alumni-theme' ); ?></a>
		</li>
	<?php endif; ?>
	<?php if ( alumni_theme_get_events_listing_url() ) : ?>
		<li class="alumni-nav-item">
			<a href="<?php echo esc_url( alumni_theme_get_events_listing_url() ); ?>"><?php esc_html_e( 'イベント', 'alumni-theme' ); ?></a>
		</li>
	<?php endif; ?>
	<?php if ( alumni_theme_get_officers_listing_url() ) : ?>
		<li class="alumni-nav-item">
			<a href="<?php echo esc_url( alumni_theme_get_officers_listing_url() ); ?>"><?php esc_html_e( '役員・理事紹介', 'alumni-theme' ); ?></a>
		</li>
	<?php endif; ?>
	<?php if ( alumni_theme_get_graduation_lookup_url() ) : ?>
		<li class="alumni-nav-item">
			<a href="<?php echo esc_url( alumni_theme_get_graduation_lookup_url() ); ?>"><?php esc_html_e( '卒業期早見表', 'alumni-theme' ); ?></a>
		</li>
	<?php endif; ?>
	<?php
	$alumni_nav_greetings = alumni_theme_get_person_greetings();
	if ( $alumni_nav_greetings && $alumni_nav_greetings->have_posts() ) :
		?>
		<li class="alumni-nav-item alumni-nav-item-has-children">
			<a href="#" class="alumni-nav-drilldown-toggle" aria-haspopup="true" aria-expanded="false">
				<?php esc_html_e( '同窓会情報', 'alumni-theme' ); ?>
			</a>
			<ul class="alumni-nav-submenu">
				<?php
				while ( $alumni_nav_greetings->have_posts() ) :
					$alumni_nav_greetings->the_post();
					?>
					<li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
					<?php
				endwhile;
				wp_reset_postdata();
				?>
			</ul>
		</li>
		<?php
	endif;
	?>
</ul>
