<?php
/**
 * サイト全体のメインナビゲーション（上部メニュー／左サイドメニュー
 * どちらのレイアウトでも同じマークアップを使う — 見た目の切替は
 * body_class()とCSSだけで行う。header.php から get_template_part() で
 * 呼び出される。
 *
 * 固定項目（ホーム／ニュース／イベント／役員・理事紹介／卒業期早見表）に
 * 加えて、Alumni Coreの「コンテンツ階層」（対象者×親子関係）から動的に
 * ドリルダウンメニューを生成する — 特定のコンテンツ名をここに
 * ハードコードすることはない。管理者がコンテンツを作成し、対象者・親を
 * 設定するだけで、メニュー側の変更なしに自動的にここへ現れる
 * （docs/top-page-slot-and-navigation-design.md 他）。
 *
 * 「共通」対象者のトップレベルコンテンツ（会長挨拶・校長挨拶などの人物
 * 挨拶や、規約類、自由コンテンツで対象者を明示的に設定していないもの）は
 * すべて「同窓会情報」というドリルダウンにまとまる。「卒業生向け」
 * 「在校生向け」はそれぞれ専用のドリルダウンになり、どちらもトップレベル
 * コンテンツが1件もない場合は表示されない。
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
	$alumni_nav_terms_url = alumni_theme_get_terms_listing_url();

	// 「共通」ドリルダウンは、規約類一覧への固定リンク（常に到達できる
	// ようにする）に、「共通」対象者のトップレベルコンテンツ階層を続ける。
	// 既存の人物挨拶・規約類コンテンツは対象者未設定=common・親未設定=
	// トップレベルが既定値になるため、ここへ何もしなくても現れる
	// （移行不要、既存の見え方を壊さない）。
	$alumni_nav_common_items = '';
	if ( $alumni_nav_terms_url ) {
		$alumni_nav_common_items .= '<li><a href="' . esc_url( $alumni_nav_terms_url ) . '">' . esc_html__( '規約類', 'alumni-theme' ) . '</a></li>';
	}
	$alumni_nav_common_items .= alumni_theme_render_content_tree_items( alumni_theme_get_content_tree( 'common' ) );

	if ( $alumni_nav_common_items ) :
		?>
		<li class="alumni-nav-item alumni-nav-item-has-children">
			<a href="#" class="alumni-nav-drilldown-toggle" aria-haspopup="true" aria-expanded="false">
				<?php esc_html_e( '同窓会情報', 'alumni-theme' ); ?>
			</a>
			<ul class="alumni-nav-submenu">
				<?php echo $alumni_nav_common_items; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built entirely from esc_html()/esc_url() in alumni_theme_render_content_tree_items() and above. ?>
			</ul>
		</li>
		<?php
	endif;

	// 「卒業生向け」「在校生向け」は、それぞれのトップレベルコンテンツが
	// 1件でもある場合だけドリルダウンとして表示する。
	$alumni_nav_audience_groups = array(
		'alumni'  => __( '卒業生向け', 'alumni-theme' ),
		'student' => __( '在校生向け', 'alumni-theme' ),
	);

	foreach ( $alumni_nav_audience_groups as $alumni_nav_audience_key => $alumni_nav_audience_label ) :
		$alumni_nav_audience_items = alumni_theme_render_content_tree_items( alumni_theme_get_content_tree( $alumni_nav_audience_key ) );

		if ( ! $alumni_nav_audience_items ) :
			continue;
		endif;
		?>
		<li class="alumni-nav-item alumni-nav-item-has-children">
			<a href="#" class="alumni-nav-drilldown-toggle" aria-haspopup="true" aria-expanded="false">
				<?php echo esc_html( $alumni_nav_audience_label ); ?>
			</a>
			<ul class="alumni-nav-submenu">
				<?php echo $alumni_nav_audience_items; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built entirely from esc_html()/esc_url() in alumni_theme_render_content_tree_items(). ?>
			</ul>
		</li>
		<?php
	endforeach;
	?>
</ul>
