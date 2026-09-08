<?php
/**
 * パンくずリスト。single-alumni_content.php から get_template_part()で
 * 呼び出される。「できるだけメニュー構造を基準にする」方針
 * （Menu_Structure）を優先し、このコンテンツがまだどのメニュー項目からも
 * 参照されていない場合だけ、コンテンツ自身の階層（Content_Hierarchy）へ
 * フォールバックする — メニュー未配置のコンテンツでもパンくずが空に
 * ならないようにするため。
 *
 * 祖先が1件もない（トップレベルの）コンテンツでは何も出力しない。
 *
 * @package Alumni_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$alumni_breadcrumb_nodes = alumni_theme_get_menu_ancestors_for_content( get_the_ID() );

if ( empty( $alumni_breadcrumb_nodes ) ) {
	// Menu_Structureに未配置のコンテンツ: コンテンツ自身の階層
	// （Content_Hierarchy）を、同じ表示形（label/url）に揃えて使う。
	$alumni_breadcrumb_ancestors = alumni_theme_get_content_ancestors( get_the_ID() );
	$alumni_breadcrumb_nodes     = array_map(
		function ( $alumni_ancestor_post ) {
			return array(
				'label' => $alumni_ancestor_post->post_title,
				'url'   => alumni_theme_get_content_url( $alumni_ancestor_post->ID ),
			);
		},
		$alumni_breadcrumb_ancestors
	);
}

if ( empty( $alumni_breadcrumb_nodes ) ) {
	return;
}
?>
<nav class="alumni-breadcrumbs" aria-label="<?php echo esc_attr__( 'パンくずリスト', 'alumni-theme' ); ?>">
	<ol class="alumni-breadcrumbs-list">
		<li class="alumni-breadcrumbs-item">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'ホーム', 'alumni-theme' ); ?></a>
		</li>
		<?php foreach ( $alumni_breadcrumb_nodes as $alumni_breadcrumb_node ) : ?>
			<li class="alumni-breadcrumbs-item">
				<a href="<?php echo esc_url( $alumni_breadcrumb_node['url'] ); ?>"><?php echo esc_html( $alumni_breadcrumb_node['label'] ); ?></a>
			</li>
		<?php endforeach; ?>
		<li class="alumni-breadcrumbs-item alumni-breadcrumbs-current" aria-current="page">
			<?php echo esc_html( get_the_title() ); ?>
		</li>
	</ol>
</nav>
