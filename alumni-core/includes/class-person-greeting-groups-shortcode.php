<?php
/**
 * 人物挨拶グループ（歴代の人物挨拶一覧）を公開ページとして提供する
 * ショートコードと、そのための固定ページの自動作成.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Officers_Shortcode（1つの役員・理事一覧＝1つの固定ページ）と同じ
 * 「グループごとに固定ページを自動作成し、そこにショートコードを設置
 * する」パターン。
 *
 * 公開される一覧ページは、そのグループに属する歴代の人物挨拶(公開済み
 * のみ、menu_order昇順＝歴代順)を、氏名・肩書・任期と個別挨拶ページへの
 * リンクとともに並べる — 「メニュー→人物挨拶グループ→歴代人物一覧→
 * 個別挨拶」という構造の、グループ〜個別のあいだの層。
 */
class Person_Greeting_Groups_Shortcode {

	/**
	 * Option name prefix storing each group's auto-created page ID —
	 * `{PAGE_ID_OPTION_PREFIX}{group_id}`. A per-group option (rather than
	 * one option holding a group_id => page_id map) keeps this consistent
	 * with every other "1 page per record" auto-creation class in this
	 * plugin (Officers_Shortcode stores each list's page_id ON the list
	 * itself instead, but Person_Greeting_Groups intentionally stays a bare
	 * name-only list — see its class docblock — so the mapping lives here).
	 */
	const PAGE_ID_OPTION_PREFIX = 'alumni_core_person_greeting_group_page_id_';

	/**
	 * Shortcode tag.
	 */
	const SHORTCODE = 'alumni_person_greeting_group';

	/**
	 * Registers hooks. Safe to call unconditionally — the page-creation
	 * check is gated internally to is_admin().
	 */
	public static function register() {
		add_shortcode( self::SHORTCODE, array( __CLASS__, 'render_shortcode' ) );

		if ( is_admin() ) {
			add_action( 'admin_init', array( __CLASS__, 'maybe_create_pages' ) );
		}
	}

	/**
	 * Creates each fixed preset page that doesn't exist yet. Idempotent.
	 */
	public static function maybe_create_pages() {
		foreach ( Person_Greeting_Groups::instance()->get_all() as $group ) {
			self::maybe_create_page( $group['group_id'], $group['name'] );
		}
	}

	/**
	 * @param string $group_id
	 * @param string $name
	 */
	private static function maybe_create_page( $group_id, $name ) {
		$option  = self::PAGE_ID_OPTION_PREFIX . $group_id;
		$page_id = (int) get_option( $option, 0 );

		if ( $page_id && 'page' === get_post_type( $page_id ) ) {
			// Preset names are canonical; keep the existing page/URL but update
			// the visible title when migrating from older preset names.
			if ( get_the_title( $page_id ) !== $name ) {
				wp_update_post( array( 'ID' => $page_id, 'post_title' => $name ) );
			}
			return;
		}

		$slug = sanitize_title( $name );
		if ( '' === $slug ) {
			$slug = 'person-greeting-group-' . substr( $group_id, 0, 8 );
		}

		$existing = get_page_by_path( $slug, OBJECT, 'page' );

		if ( $existing ) {
			update_option( $option, $existing->ID );
			return;
		}

		$new_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $name,
				'post_name'    => $slug,
				'post_content' => '[' . self::SHORTCODE . ' id="' . $group_id . '"]',
			),
			true
		);

		if ( ! is_wp_error( $new_id ) && $new_id ) {
			update_option( $option, $new_id );
		}
	}

	/**
	 * The public URL of one グループ's own page, or '' when the group
	 * doesn't exist or its page hasn't been created yet.
	 *
	 * @param string $group_id
	 * @return string
	 */
	public static function get_group_url( $group_id ) {
		$group = Person_Greeting_Groups::instance()->get_group( $group_id );

		if ( null === $group ) {
			return '';
		}

		$option  = self::PAGE_ID_OPTION_PREFIX . $group_id;
		$page_id = (int) get_option( $option, 0 );

		// 旧実装や手動作成済みのグループページでは、ページ自体は存在して
		// ショートコードも正常に表示できるのに、対応する page ID option が
		// 未保存のことがある。その場合トップページだけが URL を取得できず、
		// 人物挨拶グループのスロットが丸ごと空になる。
		//
		// get_group_url() は公開側の唯一のURL解決口なので、ここで既存ページを
		// slug から復旧して option を補完する。管理画面に一度入り直さないと
		// リンクが直らない状態を作らないための自己修復処理。
		if ( ! $page_id || 'page' !== get_post_type( $page_id ) ) {
			$slug = sanitize_title( $group['name'] );

			if ( '' !== $slug ) {
				$existing = get_page_by_path( $slug, OBJECT, 'page' );

				if ( $existing instanceof \WP_Post ) {
					$page_id = (int) $existing->ID;
					update_option( $option, $page_id );
				}
			}
		}

		if ( ! $page_id || 'page' !== get_post_type( $page_id ) ) {
			return '';
		}

		return (string) get_permalink( $page_id );
	}

	/**
	 * Renders [alumni_person_greeting_group id="..."]: one page containing
	 * every published greeting in the selected group.
	 *
	 * The group page is the public destination for the category itself.
	 * Individual alumni_content greeting URLs are redirected here by the
	 * Content module, with an anchor to the corresponding member.
	 *
	 * @param array $atts Shortcode attributes; only 'id' is used.
	 * @return string
	 */
	public static function render_shortcode( $atts ) {
		$atts  = shortcode_atts( array( 'id' => '' ), (array) $atts, self::SHORTCODE );
		$group = Person_Greeting_Groups::instance()->get_group( $atts['id'] );

		ob_start();
		?>
		<div class="alumni-person-greeting-group">
			<?php if ( null === $group ) : ?>
				<p class="alumni-notice">
					<?php esc_html_e( 'この人物挨拶グループは見つかりませんでした。', 'alumni-core' ); ?>
				</p>
			<?php else : ?>
				<?php $members = alumni_core_get_person_greeting_group_members( $atts['id'] ); ?>
				<?php if ( empty( $members ) ) : ?>
					<p class="alumni-notice">
						<?php esc_html_e( '現在、この一覧に人物挨拶は登録されていません。', 'alumni-core' ); ?>
					</p>
				<?php else : ?>
					<?php foreach ( $members as $member ) : ?>
						<?php
						$name     = \AlumniCore\Includes\Modules\Content\Post_Type::get_person_name( $member );
						$kana     = \AlumniCore\Includes\Modules\Content\Post_Type::get_person_kana( $member );
						$title    = \AlumniCore\Includes\Modules\Content\Post_Type::get_person_title( $member );
						$term     = \AlumniCore\Includes\Modules\Content\Post_Type::get_person_term( $member );
						$tenure   = \AlumniCore\Includes\Modules\Content\Post_Type::get_person_tenure( $member );
						$photo_id = \AlumniCore\Includes\Modules\Content\Post_Type::get_person_photo_id( $member );
						?>
						<article id="<?php echo esc_attr( 'person-greeting-' . $member->ID ); ?>" class="alumni-person-greeting-group-item">
							<?php if ( $title ) : ?>
								<h2 class="alumni-person-greeting-group-member-title"><?php echo esc_html( $title ); ?></h2>
							<?php endif; ?>

							<?php if ( $photo_id ) : ?>
								<div class="alumni-person-photo">
									<?php echo wp_get_attachment_image( $photo_id, 'medium' ); ?>
								</div>
							<?php endif; ?>

							<p class="alumni-person-name">
								<?php echo esc_html( $name ); ?>
								<?php if ( $kana ) : ?>
									<span class="alumni-person-kana">（<?php echo esc_html( $kana ); ?>）</span>
								<?php endif; ?>
							</p>

							<?php if ( $tenure ) : ?>
								<p class="alumni-person-greeting-group-tenure">
									<?php
									printf(
										/* translators: %s: 任期の自由記述、例「2020年〜2024年」 */
										esc_html__( '任期：%s', 'alumni-core' ),
										esc_html( $tenure )
									);
									?>
								</p>
							<?php elseif ( $term ) : ?>
								<p class="alumni-person-term">
									<?php
									printf(
										/* translators: %d: graduation term (期) */
										esc_html__( '第%d期', 'alumni-core' ),
										(int) $term
									);
									?>
								</p>
							<?php endif; ?>

							<div class="alumni-person-greeting-group-body entry-content">
								<?php echo apply_filters( 'the_content', $member->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the_content handles post HTML. ?>
							</div>
						</article>
					<?php endforeach; ?>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

}
