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
		<?php
		// トップページのセクション構成（見出し・段数・各段の表示内容）は
		// 「同窓会 > トップページ設定」画面から管理者が設定する
		// （docs/top-page-slot-based-layout-design.md）。初期状態では、
		// 以前このテンプレートに直接書かれていた「お知らせ・イベント」
		// 「同窓会情報（役員・理事紹介／卒業期早見表／規約類）」の2セクション
		// と同じ内容が既定値として自動的に用意される — 何も設定しなければ
		// 見た目は変わらない。
		get_template_part( 'template-parts/homepage-sections' );
		?>
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
