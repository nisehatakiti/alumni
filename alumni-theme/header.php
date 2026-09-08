<?php
/**
 * The header for the theme: doctype, <head>, and the opening site markup
 * (branding + primary navigation).
 *
 * ナビゲーションの配置（上部／左サイド）は同じ template-parts/site-navigation.php
 * を使い回し、DOM上の位置だけを admin の nav_layout 設定で切り替える:
 * 上部メニューでは従来どおり <header> の内側に、左サイドメニューでは
 * <header> の外、#page-shell 内の最初の子要素として配置する（そのすぐ下の
 * #content と横に並ぶフレックスの兄弟要素にするため）。見た目の実際の
 * 切替は body_class() が付与する alumni-nav-layout-top / -side クラスと
 * main.css が担い、このファイルはDOM構造の違いだけを用意する。
 *
 * @package Alumni_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$alumni_theme_nav_layout = alumni_theme_get_nav_layout();
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header id="masthead" class="site-header">
	<div class="alumni-container alumni-header-inner">
		<div class="site-branding">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php endif; ?>

			<div class="alumni-brand-row">
				<?php $alumni_theme_emblem_html = alumni_theme_get_school_emblem_html(); ?>
				<?php if ( $alumni_theme_emblem_html ) : ?>
					<span class="alumni-brand-emblem">
						<?php echo $alumni_theme_emblem_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already-safe HTML from wp_get_attachment_image(). ?>
					</span>
				<?php endif; ?>

				<div class="alumni-brand-text">
					<p class="site-title">
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
							<?php echo esc_html( alumni_theme_get_association_name() ); ?>
						</a>
					</p>

					<?php $alumni_theme_school_name = alumni_theme_get_school_name(); ?>
					<?php if ( $alumni_theme_school_name ) : ?>
						<p class="site-description"><?php echo esc_html( $alumni_theme_school_name ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<?php if ( 'side' !== $alumni_theme_nav_layout ) : ?>
			<nav id="site-navigation" class="main-navigation" aria-label="<?php esc_attr_e( 'メインナビゲーション', 'alumni-theme' ); ?>">
				<button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false">
					<?php esc_html_e( 'メニュー', 'alumni-theme' ); ?>
				</button>

				<?php get_template_part( 'template-parts/site-navigation' ); ?>
			</nav>
		<?php endif; ?>
	</div>
</header>

<div id="page-shell" class="alumni-page-shell">
	<?php if ( 'side' === $alumni_theme_nav_layout ) : ?>
		<nav id="site-navigation" class="main-navigation main-navigation-side" aria-label="<?php esc_attr_e( 'メインナビゲーション', 'alumni-theme' ); ?>">
			<button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false">
				<?php esc_html_e( 'メニュー', 'alumni-theme' ); ?>
			</button>

			<?php get_template_part( 'template-parts/site-navigation' ); ?>
		</nav>
	<?php endif; ?>

	<div id="content" class="site-content">
		<div class="alumni-container">
