<?php
/**
 * Reads and writes 役員・理事一覧 (Officer Lists) data.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 役員・理事の情報は「一覧」を複数持てる構造に変更されている（例：2026年度
 * 役員／2026年度理事／歴代会長）。各一覧は名称・公開タイトル・肩書見出し・
 * 自動作成ページIDを持ち、その配下に役員行の配列を持つ。
 *
 * 以前は単一の flat な配列（alumni_core_officers オプション、旧
 * Officers クラス）だったが、そのオプション名・データは一切変更・削除
 * しない（uninstall.phpの既存の保持ポリシーの対象のまま）。この
 * クラスが初めて読み込まれる際、まだ alumni_core_officer_lists
 * オプションが存在せず、かつ旧 alumni_core_officers に行があれば、
 * それを「一覧」1件として自動的に取り込む（migrate_legacy()）。
 * 旧オプション自体は読むだけで、書き換えたり削除したりはしない。
 *
 * 各行のフィールド順は指定どおり: title（肩書）, name（氏名）,
 * term（卒業期）, committee（委員会）, remarks（備考、新規）,
 * linked_content_id, order。
 */
class Officer_Lists {

	/**
	 * The wp_options row name.
	 */
	const OPTION_NAME = 'alumni_core_officer_lists';

	/**
	 * The previous single-list implementation's option name — read once
	 * for migration, never written to or deleted by this class.
	 */
	const LEGACY_OPTION_NAME = 'alumni_core_officers';

	/**
	 * Default 肩書見出し when a list doesn't override it.
	 */
	const DEFAULT_TITLE_HEADING = '肩書';

	/**
	 * 対象者の値。Modules\Content\Post_Type::AUDIENCE_* と同じ文字列だが、
	 * このクラスは意図的に他モジュールへ依存せず、同じ2行のルールを
	 * 重複させている（Officer_Lists::sanitize_term()の既存の方針と同じ
	 * 理由 — 状態を共有しない短いルールのために結合を増やす必要はない）。
	 */
	const AUDIENCE_ALUMNI  = 'alumni';
	const AUDIENCE_STUDENT = 'student';
	const AUDIENCE_COMMON  = 'common';

	/**
	 * Singleton instance.
	 *
	 * @var Officer_Lists|null
	 */
	private static $instance = null;

	/**
	 * Cached lists array.
	 *
	 * @var array|null
	 */
	private $lists = null;

	/**
	 * Returns the singleton instance.
	 *
	 * @return Officer_Lists
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Use instance() instead.
	 */
	private function __construct() {}

	/**
	 * Returns every saved list, in display order. Triggers the one-time
	 * legacy-data migration on the very first call if
	 * alumni_core_officer_lists has never been saved before.
	 *
	 * @return array[]
	 */
	public function get_all() {
		if ( null === $this->lists ) {
			$saved = get_option( self::OPTION_NAME, null );

			if ( null === $saved ) {
				$this->lists = self::migrate_legacy();
				// Persisted immediately so the migration only ever runs
				// once, even if the legacy option's contents change later
				// (e.g. a stray write from very old code) — this list
				// becomes the new source of truth from this point on.
				update_option( self::OPTION_NAME, $this->lists );
			} else {
				$this->lists = is_array( $saved ) ? array_values( $saved ) : array();
			}

			// Lists saved before parent_id/audience/enabled existed (an
			// earlier round of this plugin) won't have these keys at all —
			// default them at read time rather than rewriting the stored
			// option, the same "revalidate/backfill on read" rule used
			// throughout this plugin (e.g. Post_Type::get_audience()).
			$this->lists = array_map( array( __CLASS__, 'normalize_list' ), $this->lists );
		}

		return $this->lists;
	}

	/**
	 * Backfills parent_id/audience/enabled with safe defaults on a list
	 * that predates those fields, so every caller can rely on them always
	 * being present.
	 *
	 * @param array $list
	 * @return array
	 */
	private static function normalize_list( array $list ) {
		if ( ! isset( $list['parent_id'] ) ) {
			$list['parent_id'] = 0;
		}
		if ( ! isset( $list['audience'] ) || ! in_array( $list['audience'], array( self::AUDIENCE_ALUMNI, self::AUDIENCE_STUDENT, self::AUDIENCE_COMMON ), true ) ) {
			$list['audience'] = self::AUDIENCE_COMMON;
		}
		if ( ! isset( $list['enabled'] ) ) {
			$list['enabled'] = true;
		}

		return $list;
	}

	/**
	 * Builds a single default list from the legacy alumni_core_officers
	 * option, if it has any rows. Read-only against the legacy option —
	 * never writes to or deletes it, so it stays intact regardless of what
	 * happens to the new list-based data afterwards.
	 *
	 * @return array[] Zero or one list.
	 */
	private static function migrate_legacy() {
		$legacy = get_option( self::LEGACY_OPTION_NAME, array() );

		if ( ! is_array( $legacy ) || empty( $legacy ) ) {
			return array();
		}

		$rows = array();

		foreach ( array_values( $legacy ) as $index => $legacy_row ) {
			if ( ! is_array( $legacy_row ) || empty( $legacy_row['name'] ) ) {
				continue;
			}

			$rows[] = array(
				'row_id'             => isset( $legacy_row['row_id'] ) && '' !== $legacy_row['row_id'] ? $legacy_row['row_id'] : wp_generate_uuid4(),
				'title'              => isset( $legacy_row['title'] ) ? $legacy_row['title'] : '',
				'name'               => $legacy_row['name'],
				'term'               => isset( $legacy_row['term'] ) ? $legacy_row['term'] : '',
				'committee'          => isset( $legacy_row['committee'] ) ? $legacy_row['committee'] : '',
				'remarks'            => '',
				'linked_content_id'  => isset( $legacy_row['linked_content_id'] ) ? (int) $legacy_row['linked_content_id'] : 0,
				'order'              => $index + 1,
			);
		}

		if ( empty( $rows ) ) {
			return array();
		}

		return array(
			array(
				'list_id'       => wp_generate_uuid4(),
				'name'          => __( '既存の役員一覧', 'alumni-core' ),
				'title'         => __( '役員・理事紹介', 'alumni-core' ),
				'title_heading' => self::DEFAULT_TITLE_HEADING,
				'page_id'       => 0,
				'order'         => 1,
				// 既存データからの自動移行なので、階層・対象者・有効状態は
				// 何もしなくても安全な既定値（トップレベル／共通／有効）に
				// なる — 移行によって非表示になったり構造が壊れたりしない。
				'parent_id'     => 0,
				'audience'      => self::AUDIENCE_COMMON,
				'enabled'       => true,
				'rows'          => $rows,
			),
		);
	}

	/**
	 * A single list by ID, or null if it doesn't exist.
	 *
	 * @param string $list_id
	 * @return array|null
	 */
	public function get_list( $list_id ) {
		foreach ( $this->get_all() as $list ) {
			if ( $list['list_id'] === $list_id ) {
				return $list;
			}
		}

		return null;
	}

	/**
	 * Persists the full lists array as-is (every mutation method below
	 * funnels through this single write path).
	 *
	 * @param array[] $lists
	 * @return array[]
	 */
	private function save_lists( array $lists ) {
		update_option( self::OPTION_NAME, $lists );

		$this->lists = $lists;

		return $lists;
	}

	/**
	 * Creates a new, empty list and returns its ID.
	 *
	 * @param string $name 一覧名 (management name). Falls back to a
	 *                      generic placeholder when empty so a list is
	 *                      never nameless.
	 * @return string The new list's ID.
	 */
	public function create_list( $name ) {
		$lists = $this->get_all();

		$name = sanitize_text_field( $name );
		if ( '' === $name ) {
			$name = __( '新しい一覧', 'alumni-core' );
		}

		$list_id = wp_generate_uuid4();

		$lists[] = array(
			'list_id'       => $list_id,
			'name'          => $name,
			'title'         => $name,
			'title_heading' => self::DEFAULT_TITLE_HEADING,
			'page_id'       => 0,
			'order'         => count( $lists ) + 1,
			'parent_id'     => 0,
			'audience'      => self::AUDIENCE_COMMON,
			'enabled'       => true,
			'rows'          => array(),
		);

		$this->save_lists( $lists );

		return $list_id;
	}

	/**
	 * Deletes a list entirely (rows included). Does not touch any
	 * auto-created page for it — the page becomes orphaned (still
	 * viewable at its old URL, showing "この一覧は見つかりません") rather
	 * than being force-deleted, consistent with this plugin's policy of
	 * never deleting WordPress content on the user's behalf without an
	 * explicit opt-in.
	 *
	 * @param string $list_id
	 */
	public function delete_list( $list_id ) {
		$lists = array_values(
			array_filter(
				$this->get_all(),
				function ( $list ) use ( $list_id ) {
					return $list['list_id'] !== $list_id;
				}
			)
		);

		$this->save_lists( $lists );
	}

	/**
	 * Updates a list's own metadata (name/title/title_heading), leaving
	 * its rows untouched.
	 *
	 * @param string $list_id
	 * @param string $name          一覧名 (raw, sanitized here).
	 * @param string $title         公開タイトル (raw, sanitized here).
	 * @param string $title_heading 肩書見出し (raw, sanitized here; falls
	 *                               back to self::DEFAULT_TITLE_HEADING
	 *                               when empty).
	 * @return array|null The updated list, or null if $list_id doesn't exist.
	 */
	public function save_list_meta( $list_id, $name, $title, $title_heading ) {
		$lists  = $this->get_all();
		$found  = null;

		foreach ( $lists as &$list ) {
			if ( $list['list_id'] !== $list_id ) {
				continue;
			}

			$name          = sanitize_text_field( $name );
			$title         = sanitize_text_field( $title );
			$title_heading = sanitize_text_field( $title_heading );

			$list['name']          = '' !== $name ? $name : $list['name'];
			$list['title']         = '' !== $title ? $title : $list['name'];
			$list['title_heading'] = '' !== $title_heading ? $title_heading : self::DEFAULT_TITLE_HEADING;

			$found = $list;
		}
		unset( $list );

		$this->save_lists( $lists );

		return $found;
	}

	/**
	 * Updates a list's site-structure fields (対象者・親コンテンツ・
	 * 有効/無効) — deliberately separate from save_list_meta() so saving
	 * one never silently resets the other when a caller only has one side
	 * of the form.
	 *
	 * @param string $list_id
	 * @param mixed  $parent_id 親コンテンツの投稿ID(alumni_content)、または0。
	 *                           対象の投稿が存在しない場合は0(トップレベル)
	 *                           にフォールバックする。
	 * @param mixed  $audience  self::AUDIENCE_*のいずれか。それ以外は
	 *                           AUDIENCE_COMMONにフォールバックする。
	 * @param mixed  $enabled   有効/無効(公開一覧・メニュー・トップページ
	 *                           候補に出すかどうか)。
	 * @return array|null The updated list, or null if $list_id doesn't exist.
	 */
	public function save_list_structure( $list_id, $parent_id, $audience, $enabled ) {
		$lists = $this->get_all();
		$found = null;

		$parent_id = absint( $parent_id );
		if ( $parent_id && \AlumniCore\Includes\Modules\Content\Post_Type::SLUG !== get_post_type( $parent_id ) ) {
			$parent_id = 0;
		}

		if ( ! in_array( $audience, array( self::AUDIENCE_ALUMNI, self::AUDIENCE_STUDENT, self::AUDIENCE_COMMON ), true ) ) {
			$audience = self::AUDIENCE_COMMON;
		}

		$enabled = (bool) $enabled;

		foreach ( $lists as &$list ) {
			if ( $list['list_id'] !== $list_id ) {
				continue;
			}

			$list['parent_id'] = $parent_id;
			$list['audience']  = $audience;
			$list['enabled']   = $enabled;

			$found = $list;
		}
		unset( $list );

		$this->save_lists( $lists );

		return $found;
	}

	/**
	 * Records the auto-created page ID for a list (see
	 * Officers_Shortcode::maybe_create_list_pages()).
	 *
	 * @param string $list_id
	 * @param int    $page_id
	 */
	public function set_list_page_id( $list_id, $page_id ) {
		$lists = $this->get_all();

		foreach ( $lists as &$list ) {
			if ( $list['list_id'] === $list_id ) {
				$list['page_id'] = (int) $page_id;
			}
		}
		unset( $list );

		$this->save_lists( $lists );
	}

	/**
	 * Sanitizes and saves one list's full row set, replacing whatever was
	 * there before (same "whole list managed as one form" model as the
	 * previous single-list Officers class).
	 *
	 * @param string $list_id
	 * @param mixed  $raw_rows Raw, unsanitized $_POST-derived rows.
	 * @return array[] The sanitized rows that were saved, or [] if
	 *                  $list_id doesn't exist.
	 */
	public function save_list_rows( $list_id, $raw_rows ) {
		$lists     = $this->get_all();
		$sanitized = self::sanitize_rows( $raw_rows );
		$found     = false;

		foreach ( $lists as &$list ) {
			if ( $list['list_id'] === $list_id ) {
				$list['rows'] = $sanitized;
				$found        = true;
			}
		}
		unset( $list );

		if ( ! $found ) {
			return array();
		}

		$this->save_lists( $lists );

		return $sanitized;
	}

	/**
	 * Sanitizes a raw, POSTed officer row list for one 一覧: validates
	 * every field, drops malformed/empty (nameless) rows, and reassigns
	 * 'order' to match final array position. Static so tests can exercise
	 * it directly without a full save.
	 *
	 * Field order in the returned arrays follows the current spec:
	 * title（肩書）, name（氏名）, term（卒業期）, committee（委員会）,
	 * remarks（備考）, linked_content_id, order.
	 *
	 * @param mixed $raw_rows Raw input, expected to be an array of arrays.
	 * @return array[]
	 */
	public static function sanitize_rows( $raw_rows ) {
		if ( ! is_array( $raw_rows ) ) {
			return array();
		}

		$rows = array();

		foreach ( $raw_rows as $raw_row ) {
			if ( ! is_array( $raw_row ) ) {
				continue;
			}

			$name = isset( $raw_row['name'] ) ? sanitize_text_field( wp_unslash( $raw_row['name'] ) ) : '';

			if ( '' === $name ) {
				continue;
			}

			$row_id = isset( $raw_row['row_id'] ) ? sanitize_key( wp_unslash( $raw_row['row_id'] ) ) : '';
			if ( '' === $row_id ) {
				$row_id = wp_generate_uuid4();
			}

			$rows[] = array(
				'row_id'            => $row_id,
				'title'             => isset( $raw_row['title'] ) ? sanitize_text_field( wp_unslash( $raw_row['title'] ) ) : '',
				'name'              => $name,
				'term'              => self::sanitize_term( isset( $raw_row['term'] ) ? $raw_row['term'] : '' ),
				'committee'         => isset( $raw_row['committee'] ) ? sanitize_text_field( wp_unslash( $raw_row['committee'] ) ) : '',
				'remarks'           => isset( $raw_row['remarks'] ) ? sanitize_text_field( wp_unslash( $raw_row['remarks'] ) ) : '',
				'linked_content_id' => self::sanitize_linked_content_id( isset( $raw_row['linked_content_id'] ) ? $raw_row['linked_content_id'] : 0 ),
			);
		}

		foreach ( $rows as $index => &$row ) {
			$row['order'] = $index + 1;
		}
		unset( $row );

		return $rows;
	}

	/**
	 * Validates a raw 卒業期 submission — identical rule to
	 * Content_Meta_Box::sanitize_term(), duplicated rather than
	 * cross-referenced for the same reasoning documented there (a two-line
	 * rule with no shared state doesn't justify a cross-module dependency).
	 *
	 * @param mixed $raw Raw form value.
	 * @return int|string
	 */
	private static function sanitize_term( $raw ) {
		if ( '' === $raw || null === $raw || ! is_numeric( $raw ) ) {
			return '';
		}

		if ( (string) (int) $raw !== (string) ( $raw + 0 ) ) {
			return '';
		}

		$term = (int) $raw;

		return $term > 0 ? $term : '';
	}

	/**
	 * Whether $id is a still-existing alumni_content post — the single
	 * source of truth for "usable linked content ID", shared by save-time
	 * validation (sanitize_linked_content_id()) and read-time
	 * revalidation (the public alumni_core_get_officers_for_list() API).
	 *
	 * @param int $id Post ID (already cast to a non-negative int).
	 * @return bool
	 */
	public static function is_valid_linked_content( $id ) {
		if ( $id <= 0 ) {
			return false;
		}

		return \AlumniCore\Includes\Modules\Content\Post_Type::SLUG === get_post_type( $id );
	}

	/**
	 * Validates a リンク先コンテンツ submission: an existing alumni_content
	 * post ID, or 0 (no link) for anything empty/invalid/nonexistent.
	 *
	 * @param mixed $raw Raw form value.
	 * @return int
	 */
	public static function sanitize_linked_content_id( $raw ) {
		$id = absint( $raw );

		return self::is_valid_linked_content( $id ) ? $id : 0;
	}
}
