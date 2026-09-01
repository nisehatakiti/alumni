<?php
/**
 * サイト全体のメインナビゲーション（上部メニュー／左サイドメニュー
 * どちらのレイアウトでも同じマークアップを使う — 見た目の切替は
 * body_class()とCSSだけで行う。header.php から get_template_part() で
 * 呼び出される。
 *
 * 固定項目（ホーム／ニュース／イベント／役員・理事紹介／卒業期早見表）に
 * 加えて、Alumni Coreの「メニュー構成」（Menu_Structure）から動的に
 * ドリルダウンメニューを生成する — 特定のコンテンツ名をここに
 * ハードコードすることはない。管理者が「同窓会 > メニュー構成」画面で
 * フォルダ・コンテンツへのリンクを配置するだけで、メニュー側のコード
 * 変更なしに自動的にここへ現れる（docs/top-page-slot-and-navigation-design.md
 * 他）。メニュー構成は、以前のコンテンツ階層（Content_Hierarchy）ベースの
 * 構造から初回アクセス時に自動的に移行されるため、アップグレード直後の
 * 見た目は変わらない（Menu_Structure::migrate_from_content_hierarchy()参照）。
 *
 * 「共通」対象者はすべて「同窓会情報」というドリルダウンにまとまる。
 * 「卒業生向け」「在校生向け」はそれぞれ専用のドリルダウンになり、
 * どちらもトップレベル項目が1件もない場合は表示されない。
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
	// 「共通」ドリルダウンは、規約類一覧への固定リンク（常に到達できる
	// ようにする）に、メニュー構成の「共通」対象者のトップレベル項目を
	// 続ける。
	$alumni_nav_terms_url    = alumni_theme_get_terms_listing_url();
	$alumni_nav_common_items = '';
	if ( $alumni_nav_terms_url ) {
		$alumni_nav_common_items .= '<li><a href="' . esc_url( $alumni_nav_terms_url ) . '">' . esc_html__( '規約類', 'alumni-theme' ) . '</a></li>';
	}
	$alumni_nav_common_items .= alumni_theme_render_menu_items( alumni_theme_get_menu_tree( 'common' ) );

	if ( $alumni_nav_common_items ) :
		?>
		<li class="alumni-nav-item alumni-nav-item-has-children">
			<a href="#" class="alumni-nav-drilldown-toggle" aria-haspopup="true" aria-expanded="false">
				<?php esc_html_e( '同窓会情報', 'alumni-theme' ); ?>
			</a>
			<ul class="alumni-nav-submenu">
				<?php echo $alumni_nav_common_items; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built entirely from esc_html()/esc_url() in alumni_theme_render_menu_items() and above. ?>
			</ul>
		</li>
		<?php
	endif;

	// 「卒業生向け」「在校生向け」は、それぞれのトップレベル項目が1件でも
	// ある場合だけドリルダウンとして表示する。
	$alumni_nav_audience_groups = array(
		'alumni'  => __( '卒業生向け', 'alumni-theme' ),
		'student' => __( '在校生向け', 'alumni-theme' ),
	);

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
