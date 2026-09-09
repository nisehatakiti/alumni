<?php
/**
 * 同窓会組織図を公開ページとして提供するショートコードと、
 * そのための固定ページの自動作成.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Graduation_Lookup_Shortcode / Officers_Shortcode と同じ「既に同じ
 * スラッグのページがあれば新規作成せずそれを採用し、なければ作成する」
 * パターンの固定ページ自動作成 + ショートコード。
 */
class Org_Chart_Shortcode {

	/**
	 * Option name storing the page's post ID.
	 */
	const OPTION_PAGE_ID = 'alumni_core_org_chart_page_id';

	/**
	 * Page slug / shortcode tag.
	 */
	const PAGE_SLUG  = 'org-chart';
	const SHORTCODE  = 'alumni_org_chart';

	/**
	 * Option name storing public display settings.
	 */
	const OPTION_DISPLAY_SETTINGS = 'alumni_core_org_chart_display_settings';

	/**
	 * Registers hooks. Safe to call unconditionally — the page-creation
	 * check is gated internally to is_admin().
	 */
	public static function register() {
		add_shortcode( self::SHORTCODE, array( __CLASS__, 'render_shortcode' ) );

		if ( is_admin() ) {
			add_action( 'admin_init', array( __CLASS__, 'maybe_create_page' ) );
		}
	}

	/**
	 * Creates the 同窓会組織図 固定ページ if it doesn't already exist and
	 * isn't already tracked. Idempotent.
	 */
	public static function maybe_create_page() {
		$page_id = (int) get_option( self::OPTION_PAGE_ID, 0 );

		if ( $page_id && 'page' === get_post_type( $page_id ) ) {
			return;
		}

		$existing = get_page_by_path( self::PAGE_SLUG, OBJECT, 'page' );

		if ( $existing ) {
			update_option( self::OPTION_PAGE_ID, $existing->ID );
			return;
		}

		$new_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => __( '同窓会組織図', 'alumni-core' ),
				'post_name'    => self::PAGE_SLUG,
				'post_content' => '[' . self::SHORTCODE . ']',
			),
			true
		);

		if ( ! is_wp_error( $new_id ) && $new_id ) {
			update_option( self::OPTION_PAGE_ID, $new_id );
		}
	}

	/**
	 * The public URL of the 同窓会組織図 page.
	 *
	 * @return string
	 */
	public static function get_url() {
		$page_id = (int) get_option( self::OPTION_PAGE_ID, 0 );

		if ( $page_id && 'page' === get_post_type( $page_id ) ) {
			return (string) get_permalink( $page_id );
		}

		return home_url( '/' . self::PAGE_SLUG . '/' );
	}

	/**
	 * Renders [alumni_org_chart]: the full tree, root-first, as nested
	 * lists connected with ruled lines. An empty tree (組織図が未登録) shows a notice instead of an
	 * empty list — never an error.
	 *
	 * @return string
	 */
	public static function render_shortcode() {
		$tree     = Org_Chart::instance()->get_tree();
		$settings = self::get_display_settings();

		ob_start();
		?>
		<div class="alumni-org-chart<?php echo $settings['show_connectors'] ? ' alumni-org-chart--connectors' : ''; ?><?php echo $settings['show_boxes'] ? ' alumni-org-chart--boxes' : ''; ?>">
			<style>
				/* 同窓会組織図: 親子関係を罫線でつなぐツリー表示 */
				.alumni-org-chart .alumni-org-chart-list {
					list-style: none;
					margin: 0;
					padding: 0;
				}

				.alumni-org-chart .alumni-org-chart-node {
					list-style: none;
					margin: 0;
					padding: 0;
				}

				.alumni-org-chart .alumni-org-chart-node-name {
					display: inline-block;
					padding: 0.35em 0.75em;
					line-height: 1.5;
				}

				/* 子階層全体の縦線・子ノードへの横線は「罫線でつなぐ」がONの時だけ表示 */
				.alumni-org-chart .alumni-org-chart-node > .alumni-org-chart-list {
					position: relative;
					margin: 0 0 0 1.25rem;
					padding: 0 0 0 1.5rem;
				}

				.alumni-org-chart--connectors .alumni-org-chart-node > .alumni-org-chart-list::before {
					content: "";
					position: absolute;
					top: 0;
					bottom: 1.05em;
					left: 0;
					border-left: 1px solid currentColor;
					opacity: 0.45;
				}

				.alumni-org-chart .alumni-org-chart-node > .alumni-org-chart-list > .alumni-org-chart-node {
					position: relative;
					padding: 0.35em 0;
				}

				.alumni-org-chart--connectors .alumni-org-chart-node > .alumni-org-chart-list > .alumni-org-chart-node::before {
					content: "";
					position: absolute;
					top: 1.45em;
					left: -1.5rem;
					width: 1.5rem;
					border-top: 1px solid currentColor;
					opacity: 0.45;
				}

				/* 「箱で囲む」がONの時だけノードを枠線で表示 */
				.alumni-org-chart--boxes .alumni-org-chart-node-name {
					border: 1px solid currentColor;
					border-radius: 0.25rem;
				}

			</style>
			<?php if ( empty( $tree ) ) : ?>
				<p class="alumni-notice">
					<?php esc_html_e( '現在、組織図は登録されていません。', 'alumni-core' ); ?>
				</p>
			<?php else : ?>
				<?php self::render_nodes( $tree ); ?>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Returns normalized public display settings.
	 * Defaults preserve the existing public appearance: connectors on,
	 * node boxes off.
	 *
	 * @return array
	 */
	public static function get_display_settings() {
		$saved = get_option( self::OPTION_DISPLAY_SETTINGS, array() );
		$saved = is_array( $saved ) ? $saved : array();

		return array(
			'show_connectors' => ! isset( $saved['show_connectors'] ) || (bool) $saved['show_connectors'],
			'show_boxes'      => ! empty( $saved['show_boxes'] ),
		);
	}

	/**
	 * @param array[] $nodes Org_Chart::get_tree()と同じ形.
	 */
	private static function render_nodes( array $nodes ) {
		?>
		<ul class="alumni-org-chart-list">
			<?php foreach ( $nodes as $node ) : ?>
				<li class="alumni-org-chart-node">
					<span class="alumni-org-chart-node-name"><?php echo esc_html( $node['name'] ? $node['name'] : __( '（無題）', 'alumni-core' ) ); ?></span>
					<?php if ( ! empty( $node['children'] ) ) : ?>
						<?php self::render_nodes( $node['children'] ); ?>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
	}
}
