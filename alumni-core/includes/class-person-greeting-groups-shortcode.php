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
	 * Renders [alumni_person_greeting_group id="..."]: 歴代の人物挨拶一覧。
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
					<ul class="alumni-person-greeting-group-list">
						<?php foreach ( $members as $member ) : ?>
							<?php
							$name   = \AlumniCore\Includes\Modules\Content\Post_Type::get_person_name( $member );
							$title  = \AlumniCore\Includes\Modules\Content\Post_Type::get_person_title( $member );
							$tenure = \AlumniCore\Includes\Modules\Content\Post_Type::get_person_tenure( $member );
							?>
							<li class="alumni-person-greeting-group-item">
								<span class="alumni-person-greeting-group-title"><?php echo esc_html( $title ); ?></span>
								<span class="alumni-person-greeting-group-name"><?php echo esc_html( $name ); ?></span>
								<?php if ( $tenure ) : ?>
									<span class="alumni-person-greeting-group-tenure">
										<?php
										printf(
											/* translators: %s: 任期の自由記述、例「2020年〜2024年」 */
											esc_html__( '任期：%s', 'alumni-core' ),
											esc_html( $tenure )
										);
										?>
									</span>
								<?php endif; ?>
								<a class="alumni-person-greeting-group-link" href="<?php echo esc_url( get_permalink( $member ) ); ?>">
									<?php esc_html_e( '挨拶を見る', 'alumni-core' ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
