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
	 * lists. An empty tree (組織図が未登録) shows a notice instead of an
	 * empty list — never an error.
	 *
	 * @return string
	 */
	public static function render_shortcode() {
		$tree = Org_Chart::instance()->get_tree();

		ob_start();
		?>
		<div class="alumni-org-chart">
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
