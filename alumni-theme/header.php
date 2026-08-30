<?php
/**
 * The header for the theme: doctype, <head>, and the opening site markup
 * (branding + primary navigation).
 *
 * @package Alumni_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
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

		<nav id="site-navigation" class="main-navigation" aria-label="<?php esc_attr_e( 'メインナビゲーション', 'alumni-theme' ); ?>">
			<button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false">
				<?php esc_html_e( 'メニュー', 'alumni-theme' ); ?>
			</button>

			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'menu_id'        => 'primary-menu',
					'container'      => false,
					'fallback_cb'    => false,
				)
			);
			?>
		</nav>
	</div>
</header>

<div id="content" class="site-content">
	<div class="alumni-container">
