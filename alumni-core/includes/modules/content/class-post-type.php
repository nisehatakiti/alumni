<?php
/**
 * 汎用コンテンツ管理の Custom Post Type.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes\Modules\Content;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the alumni_content post type: a single CPT for every non
 * ニュース／イベント content the association manages, distinguished by a
 * "コンテンツ種別" postmeta rather than by separate post types — mirroring
 * how alumni_news_event distinguishes ニュース from イベント. This keeps
 * "会長挨拶" / "副会長挨拶" / "校長挨拶" from ever being separate features:
 * they're all just alumni_content posts with kind=person_greeting and a
 * different post_title (コンテンツ名).
 *
 * Only two kinds are wired up in this phase (自由コンテンツ／人物挨拶), but
 * the kind is a plain string constant — same as
 * NewsEvents\Post_Type::TYPE_NEWS/TYPE_EVENT — so a future kind (同窓会沿革,
 * 組織図, お問い合わせ, ...) is added the same way: a new KIND_* constant,
 * its own fields in Content_Meta_Box, and a branch in Required_Fields.
 */
class Post_Type {

	/**
	 * Post type slug.
	 */
	const SLUG = 'alumni_content';

	/**
	 * Meta key storing the content kind ('free' or 'person_greeting').
	 */
	const META_KIND = '_alumni_content_kind';

	/**
	 * Content kind values.
	 */
	const KIND_FREE            = 'free';
	const KIND_PERSON_GREETING = 'person_greeting';

	/**
	 * Query var used only by the 「人物挨拶を追加」／「自由コンテンツを
	 * 追加」 admin menu shortcuts (Admin::register_menu()) to pre-select
	 * the intended kind on the 新規追加 screen — see
	 * Content_Meta_Box::render(). Never read at save time: the actually
	 * saved kind always comes from the submitted 'alumni_content_kind'
	 * POST field (see Content_Meta_Box::save()), so this can't be used to
	 * change an existing post's kind via a crafted URL.
	 */
	const QUERY_VAR_KIND = 'alumni_content_kind';

	/**
	 * Meta keys used only when META_KIND is KIND_PERSON_GREETING.
	 */
	const META_PERSON_NAME     = '_alumni_person_name';
	const META_PERSON_KANA     = '_alumni_person_kana';
	const META_PERSON_TITLE    = '_alumni_person_title';
	const META_PERSON_TERM     = '_alumni_person_term';
	const META_PERSON_PHOTO_ID = '_alumni_person_photo_id';

	/**
	 * Registers the post type. Hooked to 'init' so it runs on every
	 * request (front-end and admin).
	 */
	public static function register() {
		register_post_type(
			self::SLUG,
			array(
				'labels'       => array(
					'name'               => __( 'コンテンツ', 'alumni-core' ),
					'singular_name'      => __( 'コンテンツ', 'alumni-core' ),
					'menu_name'          => __( 'コンテンツ', 'alumni-core' ),
					'all_items'          => __( 'すべてのコンテンツ', 'alumni-core' ),
					'add_new'            => __( '新規追加', 'alumni-core' ),
					'add_new_item'       => __( '新規コンテンツを追加', 'alumni-core' ),
					'edit_item'          => __( 'コンテンツを編集', 'alumni-core' ),
					'new_item'           => __( '新規コンテンツ', 'alumni-core' ),
					'view_item'          => __( 'コンテンツを表示', 'alumni-core' ),
					'view_items'         => __( 'コンテンツを表示', 'alumni-core' ),
					'search_items'       => __( 'コンテンツを検索', 'alumni-core' ),
					'not_found'          => __( 'コンテンツが見つかりません', 'alumni-core' ),
					'not_found_in_trash' => __( 'ゴミ箱にコンテンツは見つかりません', 'alumni-core' ),
				),
				'public'       => true,
				'show_ui'      => true,
				// Nests this CPT's own list/add-new/edit screens under the
				// 同窓会 top-level menu instead of creating a new one.
				'show_in_menu' => \AlumniCore\Admin\Admin::MENU_SLUG,
				'show_in_rest' => true,
				// No 'editor' support, same reasoning as alumni_news_event:
				// this disables both the block and classic editors, and
				// Content_Meta_Box renders a plain, fixed set of fields in
				// their place (see Content_Meta_Box::render()).
				'supports'     => array( 'title' ),
				'has_archive'  => false,
				'rewrite'      => array(
					'slug'       => 'contents',
					'with_front' => false,
				),
			)
		);
	}

	/**
	 * Returns the saved content kind, defaulting to KIND_FREE for anything
	 * missing or unrecognized.
	 *
	 * @param int|\WP_Post|null $post Post ID or object. Defaults to the
	 *                                 current global post.
	 * @return string self::KIND_FREE or self::KIND_PERSON_GREETING.
	 */
	public static function get_kind( $post = null ) {
		$post = get_post( $post );

		if ( ! $post ) {
			return self::KIND_FREE;
		}

		$kind = get_post_meta( $post->ID, self::META_KIND, true );

		return self::KIND_PERSON_GREETING === $kind ? self::KIND_PERSON_GREETING : self::KIND_FREE;
	}

	/**
	 * @param int|\WP_Post|null $post Post ID or object.
	 * @return bool
	 */
	public static function is_person_greeting( $post = null ) {
		return self::KIND_PERSON_GREETING === self::get_kind( $post );
	}

	/**
	 * @param int|\WP_Post|null $post Post ID or object.
	 * @return string
	 */
	public static function get_person_name( $post = null ) {
		$post = get_post( $post );

		return $post ? (string) get_post_meta( $post->ID, self::META_PERSON_NAME, true ) : '';
	}

	/**
	 * @param int|\WP_Post|null $post Post ID or object.
	 * @return string
	 */
	public static function get_person_kana( $post = null ) {
		$post = get_post( $post );

		return $post ? (string) get_post_meta( $post->ID, self::META_PERSON_KANA, true ) : '';
	}

	/**
	 * @param int|\WP_Post|null $post Post ID or object.
	 * @return string
	 */
	public static function get_person_title( $post = null ) {
		$post = get_post( $post );

		return $post ? (string) get_post_meta( $post->ID, self::META_PERSON_TITLE, true ) : '';
	}

	/**
	 * @param int|\WP_Post|null $post Post ID or object.
	 * @return int|string Term number (1-based), or '' when unset/invalid
	 *                     (卒業期 is optional for a 人物挨拶).
	 */
	public static function get_person_term( $post = null ) {
		$post = get_post( $post );

		if ( ! $post ) {
			return '';
		}

		$term = get_post_meta( $post->ID, self::META_PERSON_TERM, true );

		return ( '' !== $term && is_numeric( $term ) && (int) $term > 0 ) ? (int) $term : '';
	}

	/**
	 * The 顔写真 attachment ID, re-validated against the Media Library at
	 * read time (not just trusted from what was valid when saved) — same
	 * "revalidate on read" rule as 校章／同窓会ロゴ／学校写真
	 * (Settings::is_valid_image_attachment()), so a since-deleted photo
	 * reads back as "unset" instead of a broken image.
	 *
	 * @param int|\WP_Post|null $post Post ID or object.
	 * @return int Attachment ID, or 0 when unset or no longer a valid image.
	 */
	public static function get_person_photo_id( $post = null ) {
		$post = get_post( $post );

		if ( ! $post ) {
			return 0;
		}

		$id = absint( get_post_meta( $post->ID, self::META_PERSON_PHOTO_ID, true ) );

		return \AlumniCore\Includes\Settings::is_valid_image_attachment( $id ) ? $id : 0;
	}
}
