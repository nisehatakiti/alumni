<?php
/**
 * Single template for コンテンツ (/contents/{slug}/).
 *
 * 自由コンテンツ・人物挨拶・規約類のすべてがこの1つのテンプレートを
 * 共有する — CPTとURL構造はどのkindでも同じ（Alumni CoreのContent
 * モジュール参照）で、氏名／肩書／卒業期／顔写真（人物挨拶）や
 * 施行日／改定日（規約類）といった追加ブロックだけがkindに応じて変わる。
 * Without this template, WordPress would fall back to the theme's generic
 * index.php + template-parts/content.php, which shows the title and body
 * but has no idea a 人物挨拶 post carries those extra fields at all — this
 * is what "登録・公開はできるのに実際の内容が表示されない" meant in
 * practice: the post *was* reachable, just missing the fields that make
 * it a 人物挨拶 rather than a generic page.
 *
 * WordPress only ever routes here when the alumni_content post type (and
 * its rewrite rules) are registered, i.e. when Alumni Core is active —
 * so no separate Core-inactive fallback is needed in this file.
 *
 * @package Alumni_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	// alumni_theme_get_person_greeting()/alumni_theme_get_terms() already
	// return null for a kind they don't apply to (or when Core is
	// inactive), so their own return values are the only branch checks
	// this template needs.
	$alumni_greeting = alumni_theme_get_person_greeting();
	$alumni_terms    = alumni_theme_get_terms();
	// フォルダ（階層をまとめるだけの見出し、本文なし）は、本文の代わりに
	// 自分の子コンテンツの一覧を表示する — これにより「メニューで辿れる
	// 階層」と「実際に閲覧できる公開ページの階層」が一致する
	// （site-navigation.phpと同じ Content_Hierarchy データソース）。
	$alumni_children = alumni_theme_get_content_children( get_the_ID() );
	?>
	<main id="primary" class="site-main alumni-content-single">
		<?php get_template_part( 'template-parts/breadcrumbs' ); ?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'alumni-content-entry' ); ?>>
			<header class="entry-header">
				<h1 class="entry-title"><?php echo esc_html( $alumni_terms ? $alumni_terms['display_title'] : get_the_title() ); ?></h1>
			</header>

			<?php if ( $alumni_terms ) : ?>
				<?php if ( $alumni_terms['effective_date'] || $alumni_terms['revised_date'] ) : ?>
					<div class="alumni-terms-meta">
						<?php if ( $alumni_terms['effective_date'] ) : ?>
							<p class="alumni-terms-effective-date">
								<?php
								printf(
									/* translators: %s: effective date, Y-m-d */
									esc_html__( '施行日：%s', 'alumni-theme' ),
									esc_html( $alumni_terms['effective_date'] )
								);
								?>
							</p>
						<?php endif; ?>
						<?php if ( $alumni_terms['revised_date'] ) : ?>
							<p class="alumni-terms-revised-date">
								<?php
								printf(
									/* translators: %s: revised date, Y-m-d */
									esc_html__( '改定日：%s', 'alumni-theme' ),
									esc_html( $alumni_terms['revised_date'] )
								);
								?>
							</p>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<?php if ( $alumni_greeting ) : ?>
				<div class="alumni-person-greeting-meta">
					<?php if ( $alumni_greeting['photo_id'] ) : ?>
						<div class="alumni-person-photo">
							<?php echo wp_get_attachment_image( $alumni_greeting['photo_id'], 'medium' ); ?>
						</div>
					<?php endif; ?>

					<p class="alumni-person-name">
						<?php echo esc_html( $alumni_greeting['name'] ); ?>
						<?php if ( $alumni_greeting['kana'] ) : ?>
							<span class="alumni-person-kana">（<?php echo esc_html( $alumni_greeting['kana'] ); ?>）</span>
						<?php endif; ?>
					</p>

					<?php if ( $alumni_greeting['title'] ) : ?>
						<p class="alumni-person-title"><?php echo esc_html( $alumni_greeting['title'] ); ?></p>
					<?php endif; ?>

					<?php if ( $alumni_greeting['term'] ) : ?>
						<p class="alumni-person-term">
							<?php
							printf(
								/* translators: %d: graduation term (期) */
								esc_html__( '第%d期', 'alumni-theme' ),
								(int) $alumni_greeting['term']
							);
							?>
						</p>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="entry-content">
				<?php the_content(); ?>
			</div>

			<?php if ( ! empty( $alumni_children ) ) : ?>
				<nav class="alumni-content-children" aria-label="<?php echo esc_attr__( 'このページの下にあるコンテンツ', 'alumni-theme' ); ?>">
					<ul class="alumni-content-children-list">
						<?php foreach ( $alumni_children as $alumni_child ) : ?>
							<li>
								<a href="<?php echo esc_url( alumni_theme_get_content_url( $alumni_child->ID ) ); ?>"><?php echo esc_html( $alumni_child->post_title ); ?></a>
							</li>
						<?php endforeach; ?>
					</ul>
				</nav>
			<?php endif; ?>
		</article>
	</main>
	<?php
endwhile;

get_footer();
