<?php
/**
 * The main template file. Used whenever a more specific template
 * (front-page.php, single.php, page.php, ...) doesn't match.
 *
 * @package Alumni_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="site-main">
	<?php if ( have_posts() ) : ?>
		<?php
		while ( have_posts() ) :
			the_post();
			get_template_part( 'template-parts/content' );
		endwhile;

		the_posts_navigation();
		?>
	<?php else : ?>
		<p><?php esc_html_e( '表示できるコンテンツがありません。', 'alumni-theme' ); ?></p>
	<?php endif; ?>
</main>

<?php
get_footer();
