<?php
/**
 * コンテンツ階層のパンくずリスト。single-alumni_content.php から
 * get_template_part()で呼び出される。Coreのコンテンツ階層
 * （alumni_theme_get_content_ancestors()）をそのまま使うため、メニューの
 * ドリルダウンで辿れる階層と常に一致する（site-navigation.phpと同じ
 * コンテンツ階層データソース）。
 *
 * 祖先が1件もない（トップレベルの）コンテンツでは何も出力しない。
 *
 * @package Alumni_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$alumni_breadcrumb_ancestors = alumni_theme_get_content_ancestors( get_the_ID() );

if ( empty( $alumni_breadcrumb_ancestors ) ) {
	return;
}
?>
<nav class="alumni-breadcrumbs" aria-label="<?php echo esc_attr__( 'パンくずリスト', 'alumni-theme' ); ?>">
	<ol class="alumni-breadcrumbs-list">
		<li class="alumni-breadcrumbs-item">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'ホーム', 'alumni-theme' ); ?></a>
		</li>
		<?php foreach ( $alumni_breadcrumb_ancestors as $alumni_breadcrumb_ancestor ) : ?>
			<li class="alumni-breadcrumbs-item">
				<a href="<?php echo esc_url( alumni_theme_get_content_url( $alumni_breadcrumb_ancestor->ID ) ); ?>"><?php echo esc_html( $alumni_breadcrumb_ancestor->post_title ); ?></a>
			</li>
		<?php endforeach; ?>
		<li class="alumni-breadcrumbs-item alumni-breadcrumbs-current" aria-current="page">
			<?php echo esc_html( get_the_title() ); ?>
		</li>
	</ol>
</nav>
