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
	const KIND_TERMS           = 'terms';
	const KIND_FOLDER          = 'folder';

	/**
	 * Meta key storing a content post's 対象者（大カテゴリ）. Separate from
	 * the コンテンツ階層（親子関係、META_PARENT_ID）— 対象者 answers "誰に
	 * 向けた情報か", 階層 answers "どこに整理されているか". Every kind
	 * (自由コンテンツ／人物挨拶／規約類／フォルダ) carries this field.
	 */
	const META_AUDIENCE = '_alumni_audience';

	/**
	 * 対象者の値。AUDIENCE_COMMON がすべての未設定値の既定（新しいフィール
	 * ドなので、既存コンテンツは何もしなくても「共通」として扱われる）。
	 */
	const AUDIENCE_ALUMNI  = 'alumni';
	const AUDIENCE_STUDENT = 'student';
	const AUDIENCE_COMMON  = 'common';

	/**
	 * Meta key storing a content post's 親コンテンツ（コンテンツ階層）。
	 * 値は別の alumni_content 投稿ID、または 0（最上位＝対象者の直下）。
	 * 階層は固定の深さを持たず、Content_Hierarchy がループ検出込みで
	 * 祖先／子孫を辿る。
	 */
	const META_PARENT_ID = '_alumni_parent_id';

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
	 * Meta keys used only when META_KIND is KIND_TERMS (規約類). 表示順は
	 * 専用のmeta keyを持たず、全投稿が既に持つ標準のmenu_orderカラムを
	 * そのまま使う（他のkindは使わないので影響しない）。
	 */
	const META_TERMS_DISPLAY_TITLE  = '_alumni_terms_display_title';
	const META_TERMS_EFFECTIVE_DATE = '_alumni_terms_effective_date';
	const META_TERMS_REVISED_DATE   = '_alumni_terms_revised_date';

	/**
	 * Meta key storing the 本文の文字サイズ（意味だけをCoreが持ち、実際の
	 * ピクセル数はTheme側のCSSクラスが決定する — class docblock参照）.
	 */
	const META_TERMS_FONT_SIZE = '_alumni_terms_font_size';

	const TERMS_FONT_SMALL  = 'small';
	const TERMS_FONT_MEDIUM = 'medium';
	const TERMS_FONT_LARGE  = 'large';

	/**
	 * Meta key storing the 改定履歴: 複数の 'Y-m-d' 日付を累積して持つ配列
	 * （wp_options同様、WordPressのpostmetaは配列をそのままシリアライズ
	 * できるため、専用テーブルを新設していない）。META_TERMS_REVISED_DATE
	 * （単一の改定日、旧仕様）はこのフィールド導入後も読み取り専用の
	 * フォールバックとして残しており、削除も上書きもしない — 既存サイトの
	 * 単一改定日は、このフィールドが空の間は自動的に「1件だけの履歴」
	 * として扱われる（get_terms_revision_dates()参照）。
	 */
	const META_TERMS_REVISION_DATES = '_alumni_terms_revision_dates';

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
	 * @return string self::KIND_FREE, self::KIND_PERSON_GREETING, or
	 *                 self::KIND_TERMS.
	 */
	public static function get_kind( $post = null ) {
		$post = get_post( $post );

		if ( ! $post ) {
			return self::KIND_FREE;
		}

		$kind = get_post_meta( $post->ID, self::META_KIND, true );

		if ( self::KIND_PERSON_GREETING === $kind ) {
			return self::KIND_PERSON_GREETING;
		}

		if ( self::KIND_TERMS === $kind ) {
			return self::KIND_TERMS;
		}

		if ( self::KIND_FOLDER === $kind ) {
			return self::KIND_FOLDER;
		}

		return self::KIND_FREE;
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
	 * @return bool
	 */
	public static function is_terms( $post = null ) {
		return self::KIND_TERMS === self::get_kind( $post );
	}

	/**
	 * フォルダ（中身を持たない、階層をまとめるためだけのノード）かどうか。
	 * 例：「同窓会情報」「同窓会組織図」「同窓会規約」— それ自体は本文を
	 * 持たず、他のコンテンツ／役員一覧の親としてのみ使われる。
	 *
	 * @param int|\WP_Post|null $post Post ID or object.
	 * @return bool
	 */
	public static function is_folder( $post = null ) {
		return self::KIND_FOLDER === self::get_kind( $post );
	}

	/**
	 * @param int|\WP_Post|null $post Post ID or object.
	 * @return string self::AUDIENCE_ALUMNI/AUDIENCE_STUDENT/AUDIENCE_COMMON.
	 *                 Defaults to AUDIENCE_COMMON for anything missing or
	 *                 unrecognized — including every pre-existing post from
	 *                 before this field existed, so no data migration is
	 *                 needed for it.
	 */
	public static function get_audience( $post = null ) {
		$post = get_post( $post );

		if ( ! $post ) {
			return self::AUDIENCE_COMMON;
		}

		$audience = get_post_meta( $post->ID, self::META_AUDIENCE, true );

		if ( self::AUDIENCE_ALUMNI === $audience || self::AUDIENCE_STUDENT === $audience ) {
			return $audience;
		}

		return self::AUDIENCE_COMMON;
	}

	/**
	 * The saved 親コンテンツ ID, revalidated at read time against the
	 * current post state (same "revalidate on read" rule used throughout
	 * this plugin — see get_person_photo_id()): a parent that was deleted,
	 * trashed, or is no longer an alumni_content post reads back as "no
	 * parent" (0) rather than a dangling reference.
	 *
	 * @param int|\WP_Post|null $post Post ID or object.
	 * @return int Parent post ID, or 0 when unset/invalid/top-level.
	 */
	public static function get_parent_id( $post = null ) {
		$post = get_post( $post );

		if ( ! $post ) {
			return 0;
		}

		$parent_id = absint( get_post_meta( $post->ID, self::META_PARENT_ID, true ) );

		if ( ! $parent_id || $parent_id === (int) $post->ID ) {
			return 0;
		}

		return self::SLUG === get_post_type( $parent_id ) ? $parent_id : 0;
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

	/**
	 * 公開タイトル（規約類）: 公開ページの見出しに使う任意の上書き値。
	 * 未設定ならコンテンツ名（post_title、＝規約名）をそのまま使う —
	 * ほとんどの規約は規約名と公開タイトルが同じ文字列で構わないため、
	 * 上書きは必要な場合だけの任意項目にしている。
	 *
	 * @param int|\WP_Post|null $post Post ID or object.
	 * @return string
	 */
	public static function get_terms_display_title( $post = null ) {
		$post = get_post( $post );

		if ( ! $post ) {
			return '';
		}

		$override = (string) get_post_meta( $post->ID, self::META_TERMS_DISPLAY_TITLE, true );

		return '' !== $override ? $override : $post->post_title;
	}

	/**
	 * @param int|\WP_Post|null $post Post ID or object.
	 * @return string 'Y-m-d'、または未設定なら ''。
	 */
	public static function get_terms_effective_date( $post = null ) {
		$post = get_post( $post );

		return $post ? (string) get_post_meta( $post->ID, self::META_TERMS_EFFECTIVE_DATE, true ) : '';
	}

	/**
	 * @param int|\WP_Post|null $post Post ID or object.
	 * @return string 'Y-m-d'、または未設定なら ''。
	 */
	public static function get_terms_revised_date( $post = null ) {
		$post = get_post( $post );

		return $post ? (string) get_post_meta( $post->ID, self::META_TERMS_REVISED_DATE, true ) : '';
	}

	/**
	 * 本文の文字サイズの意味（small/medium/large）— 実際のピクセル数は
	 * Theme側が決める（class docblock参照）。不正・未設定は
	 * TERMS_FONT_MEDIUMにフォールバックする。
	 *
	 * @param int|\WP_Post|null $post Post ID or object.
	 * @return string
	 */
	public static function get_terms_font_size( $post = null ) {
		$post = get_post( $post );

		if ( ! $post ) {
			return self::TERMS_FONT_MEDIUM;
		}

		$size = get_post_meta( $post->ID, self::META_TERMS_FONT_SIZE, true );

		return in_array( $size, array( self::TERMS_FONT_SMALL, self::TERMS_FONT_LARGE ), true ) ? $size : self::TERMS_FONT_MEDIUM;
	}

	/**
	 * 改定履歴（累積、古い順）。META_TERMS_REVISION_DATESが未保存の
	 * サイトでは、旧仕様の単一 META_TERMS_REVISED_DATE を「1件だけの
	 * 履歴」として扱う（読み取り時のみのフォールバック、既存データは
	 * 一切書き換えない）。
	 *
	 * @param int|\WP_Post|null $post Post ID or object.
	 * @return string[] 'Y-m-d'文字列の配列、古い順。
	 */
	public static function get_terms_revision_dates( $post = null ) {
		$post = get_post( $post );

		if ( ! $post ) {
			return array();
		}

		$dates = get_post_meta( $post->ID, self::META_TERMS_REVISION_DATES, true );

		if ( is_array( $dates ) && ! empty( $dates ) ) {
			$dates = array_values( array_filter( array_map( 'strval', $dates ) ) );
			sort( $dates );
			return $dates;
		}

		$legacy = self::get_terms_revised_date( $post );

		return $legacy ? array( $legacy ) : array();
	}

	/**
	 * The most recent 改定日 (最終改定日), or '' when no revision history
	 * exists at all.
	 *
	 * @param int|\WP_Post|null $post Post ID or object.
	 * @return string
	 */
	public static function get_terms_last_revised_date( $post = null ) {
		$dates = self::get_terms_revision_dates( $post );

		return empty( $dates ) ? '' : end( $dates );
	}
}
