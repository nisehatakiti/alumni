<?php
/**
 * The footer for the theme: closes the main content wrapper opened in
 * header.php and outputs the site footer.
 *
 * @package Alumni_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
	</div><!-- .alumni-container -->
</div><!-- #content -->

<footer id="colophon" class="site-footer">
	<div class="alumni-container">
		<p class="site-footer-text">
			&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( alumni_theme_get_association_name() ); ?>
		</p>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
