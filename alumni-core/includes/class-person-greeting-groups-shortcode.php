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
 * 公開されるグループページは、そのグループに属する歴代の人物挨拶
 * （公開済みのみ、menu_order昇順＝管理画面で指定した表示順）を、
 * 1人ずつ独立したブロックとして本文まで直接表示する。通常の閲覧導線で
 * 個別ページへ遷移させず、「母校校長挨拶」「同窓会長挨拶」の1ページで
 * 歴代の挨拶を完結して読める構造にする。
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
	 * Creates any group's page that doesn't exist yet. Idempotent.
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

		$page_id = (int) get_option( self::PAGE_ID_OPTION_PREFIX . $group_id, 0 );

		if ( ! $page_id || 'page' !== get_post_type( $page_id ) ) {
			return '';
		}

		return (string) get_permalink( $page_id );
	}

	/**
	 * Renders [alumni_person_greeting_group id="..."].
	 *
	 * グループに所属する人物挨拶を、一覧リンクではなく「人物1人＝1ブロック」
	 * として直接表示する。本文は保存済みのWordPressブロックを the_content
	 * フィルター経由でレンダリングするため、見出し・段落・画像などのブロック
	 * 構造を保ったまま出力される。
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
					<div class="alumni-person-greeting-blocks">
						<?php foreach ( $members as $member ) : ?>
							<?php
							$name     = \AlumniCore\Includes\Modules\Content\Post_Type::get_person_name( $member );
							$kana     = \AlumniCore\Includes\Modules\Content\Post_Type::get_person_kana( $member );
							$title    = \AlumniCore\Includes\Modules\Content\Post_Type::get_person_title( $member );
							$term     = \AlumniCore\Includes\Modules\Content\Post_Type::get_person_term( $member );
							$photo_id = \AlumniCore\Includes\Modules\Content\Post_Type::get_person_photo_id( $member );
							?>
							<article class="alumni-person-greeting-block" id="person-greeting-<?php echo esc_attr( $member->ID ); ?>">
								<header class="alumni-person-greeting-block-header">
									<?php if ( $photo_id ) : ?>
										<div class="alumni-person-greeting-block-photo">
											<?php echo wp_get_attachment_image( $photo_id, 'medium', false, array( 'loading' => 'lazy' ) ); ?>
										</div>
									<?php endif; ?>

									<div class="alumni-person-greeting-block-profile">
										<?php if ( $title ) : ?>
											<p class="alumni-person-greeting-block-title"><?php echo esc_html( $title ); ?></p>
										<?php endif; ?>

										<?php if ( $name ) : ?>
											<h2 class="alumni-person-greeting-block-name">
												<?php echo esc_html( $name ); ?>
												<?php if ( $kana ) : ?><span class="alumni-person-greeting-block-kana"><?php echo esc_html( $kana ); ?></span><?php endif; ?>
											</h2>
										<?php endif; ?>

										<?php if ( $term ) : ?>
											<p class="alumni-person-greeting-block-term"><?php echo esc_html( $term ); ?></p>
										<?php endif; ?>
									</div>
								</header>

								<div class="alumni-person-greeting-block-body">
									<?php echo apply_filters( 'the_content', $member->post_content ); ?>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
