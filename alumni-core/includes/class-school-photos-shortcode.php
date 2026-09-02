<?php
/**
 * 学校写真を独立した公開ページとして提供するショートコードと、
 * そのための固定ページの自動作成.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * トップページ上の学校写真表示（alumni-theme/template-parts/school-photos.php、
 * 固定表示／自動切替のCore設定をそのまま使う）に加えて、「学校写真」
 * という独立した固定ページからも同じ写真を見られるようにする。
 *
 * マークアップはtemplate-parts/school-photos.phpと意図的に同じクラス名
 * （.school-photos/.school-photos-track/.school-photo-slide/
 * .school-photo-image）を使う — Themeの既存CSS（main.css）がそのまま
 * 適用され、新しいスタイルを追加する必要がない。
 *
 * Graduation_Lookup_Shortcode / Officers_Shortcode / Org_Chart_Shortcode
 * と同じ「既に同じスラッグのページがあれば新規作成せずそれを採用し、
 * なければ作成する」パターン。
 */
class School_Photos_Shortcode {

	/**
	 * Option name storing the page's post ID.
	 */
	const OPTION_PAGE_ID = 'alumni_core_school_photos_page_id';

	/**
	 * Page slug / shortcode tag.
	 */
	const PAGE_SLUG = 'school-photos';
	const SHORTCODE = 'alumni_school_photos';

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
	 * Creates the 学校写真 固定ページ if it doesn't already exist and isn't
	 * already tracked. Idempotent.
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
				'post_title'   => __( '学校写真', 'alumni-core' ),
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
	 * The public URL of the 学校写真 page.
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
	 * Renders [alumni_school_photos]. 写真が1枚も登録されていない場合は
	 * 通知だけを表示する（空の大きな領域を作らない — template-parts/
	 * school-photos.phpと同じ方針）。
	 *
	 * @return string
	 */
	public static function render_shortcode() {
		$display_mode = alumni_core_get_school_photo_display_mode();

		if ( \AlumniCore\Includes\Settings::PHOTO_MODE_SLIDESHOW === $display_mode ) {
			$photo_ids = alumni_core_get_school_photo_ids();
		} else {
			$featured_id = alumni_core_get_featured_school_photo_id();
			$photo_ids   = $featured_id ? array( $featured_id ) : array();
		}

		if ( empty( $photo_ids ) ) {
			return '<p class="alumni-notice">' . esc_html__( '現在、学校写真は登録されていません。', 'alumni-core' ) . '</p>';
		}

		$slides = array();
		foreach ( $photo_ids as $photo_id ) {
			$image_html = wp_get_attachment_image( $photo_id, 'large', false, array( 'class' => 'school-photo-image' ) );
			if ( $image_html ) {
				$slides[] = $image_html;
			}
		}

		if ( empty( $slides ) ) {
			return '<p class="alumni-notice">' . esc_html__( '現在、学校写真は登録されていません。', 'alumni-core' ) . '</p>';
		}

		$is_slideshow = ( \AlumniCore\Includes\Settings::PHOTO_MODE_SLIDESHOW === $display_mode ) && count( $slides ) > 1;

		ob_start();
		?>
		<section class="school-photos <?php echo $is_slideshow ? 'school-photos-slideshow' : 'school-photos-fixed'; ?>">
			<div class="school-photos-track">
				<?php foreach ( $slides as $index => $image_html ) : ?>
					<div class="school-photo-slide<?php echo 0 === $index ? ' is-active' : ''; ?>">
						<?php echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already-safe HTML from wp_get_attachment_image(). ?>
					</div>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}
}
