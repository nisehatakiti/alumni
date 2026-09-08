<?php
/**
 * サイト全体のメインナビゲーション(上部メニュー／左サイドメニュー
 * どちらのレイアウトでも同じマークアップを使う — 見た目の切替は
 * body_class()とCSSだけで行う。header.php から get_template_part() で
 * 呼び出される。
 *
 * トップレベルは「ホーム」と、Alumni Coreの「メニュー構成」
 * （Menu_Structure）が持つ3つの対象者(共通／卒業生向け／在校生向け)だけに
 * 固定する — ニュース・イベント・役員・理事紹介・卒業期早見表・規約類と
 * いった個別の項目をここへ直接ハードコードすることはない。それらは
 * 管理者が「同窓会 > メニュー構成」画面で対象者の下(必要ならさらに
 * フォルダの下)に配置することで、メニュー側のコード変更なしに自動的に
 * ここへ現れる(docs/top-page-slot-and-navigation-design.md 他)。メニュー
 * 構成は、以前のコンテンツ階層(Content_Hierarchy)ベースの構造から初回
 * アクセス時に自動的に移行されるため、アップグレード直後にメニュー構成が
 * 空になることはない(Menu_Structure::migrate_from_content_hierarchy()参照)。
 *
 * 3つの対象者ドリルダウンは、いずれもトップレベル項目が1件もない場合は
 * 表示されない(空の開閉ボタンだけを見せないため)。
 *
 * @package AlumniTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$alumni_nav_audience_groups = array(
	'common'  => __( '共通', 'alumni-theme' ),
	'alumni'  => __( '卒業生向け', 'alumni-theme' ),
	'student' => __( '在校生向け', 'alumni-theme' ),
);
?>
<ul id="primary-menu" class="alumni-nav-menu">
	<li class="alumni-nav-item">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'ホーム', 'alumni-theme' ); ?></a>
	</li>
	<?php
	foreach ( $alumni_nav_audience_groups as $alumni_nav_audience_key => $alumni_nav_audience_label ) :
		$alumni_nav_audience_items = alumni_theme_render_menu_items( alumni_theme_get_menu_tree( $alumni_nav_audience_key ) );

		if ( ! $alumni_nav_audience_items ) :
			continue;
		endif;
		?>
		<li class="alumni-nav-item alumni-nav-item-has-children">
			<a href="#" class="alumni-nav-drilldown-toggle" aria-haspopup="true" aria-expanded="false">
				<?php echo esc_html( $alumni_nav_audience_label ); ?>
			</a>
			<ul class="alumni-nav-submenu">
				<?php echo $alumni_nav_audience_items; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built entirely from esc_html()/esc_url() in alumni_theme_render_menu_items(). ?>
			</ul>
		</li>
		<?php
	endforeach;
	?>
</ul>
