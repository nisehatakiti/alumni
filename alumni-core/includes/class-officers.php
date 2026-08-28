<?php
/**
 * Reads and writes 役員・理事紹介 (Officers) data.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Officers are managed as one spreadsheet-style admin screen (add/remove/
 * reorder rows, bulk save) rather than one WordPress post per officer, so
 * they're stored as a single ordered array in their own wp_options row —
 * not folded into alumni_core_settings, since this is a variable-length
 * list of records rather than a scalar setting (the same reasoning that
 * keeps 学校写真 as an array *field*, just one level further: here the
 * array itself, not just one field of it, deserves to scale independently
 * of the settings blob). A dedicated option also keeps the door open for
 * a future officer-year/CSV-import feature to version or replace this
 * option without touching unrelated settings.
 *
 * Each row is a plain associative array:
 *   - row_id:            string, a stable per-row identifier (assigned
 *                         once, kept across saves) — useful for a future
 *                         CSV import/export or year-based diffing that
 *                         needs to track "the same officer row" across
 *                         edits, even though today's admin UI only ever
 *                         reads/writes the whole list at once.
 *   - term:               int|'' (卒業期; optional)
 *   - title:              string (肩書; free text, never a master list —
 *                         associations differ too much to standardize)
 *   - committee:          string (委員会; free text, optional)
 *   - name:               string (氏名; required — rows without one are
 *                         dropped at save time, see sanitize_rows())
 *   - linked_content_id:  int (0 = no link; otherwise a still-existing
 *                         alumni_content post ID)
 *   - order:              int (1-based display position; kept in sync
 *                         with array order on every save — redundant with
 *                         the array's own order today, but an explicit
 *                         field survives a future export/reimport where
 *                         array order alone might not)
 */
class Officers {

	/**
	 * The wp_options row name.
	 */
	const OPTION_NAME = 'alumni_core_officers';

	/**
	 * Singleton instance.
	 *
	 * @var Officers|null
	 */
	private static $instance = null;

	/**
	 * Cached officers array.
	 *
	 * @var array|null
	 */
	private $officers = null;

	/**
	 * Returns the singleton instance.
	 *
	 * @return Officers
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
	 * Returns every saved officer row, in display order.
	 *
	 * @return array[]
	 */
	public function get_all() {
		if ( null === $this->officers ) {
			$saved          = get_option( self::OPTION_NAME, array() );
			$this->officers = is_array( $saved ) ? array_values( $saved ) : array();
		}

		return $this->officers;
	}

	/**
	 * Sanitizes and saves the full officer list, replacing whatever was
	 * there before (this screen manages the whole list as one form, not
	 * individual rows).
	 *
	 * @param mixed $raw_rows Raw, unsanitized $_POST-derived rows (expected:
	 *                         a numeric-indexed array of associative
	 *                         sub-arrays; anything else — POST tampering,
	 *                         a missing key entirely — is treated as "no
	 *                         rows" rather than fatally erroring).
	 * @return array[] The sanitized rows that were saved.
	 */
	public function save( $raw_rows ) {
		$sanitized = self::sanitize_rows( $raw_rows );

		update_option( self::OPTION_NAME, $sanitized );

		$this->officers = $sanitized;

		return $sanitized;
	}

	/**
	 * Sanitizes a raw, POSTed officer list: validates every field, drops
	 * malformed/empty rows, and reassigns 'order' to match final array
	 * position. Static (not just private) so tests can exercise it
	 * directly without going through a full save() write.
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
				continue; // Malformed POST structure — skip rather than fatal.
			}

			$name = isset( $raw_row['name'] ) ? sanitize_text_field( wp_unslash( $raw_row['name'] ) ) : '';

			// A row without a name identifies nobody, so it's dropped
			// rather than stored as a blank placeholder.
			if ( '' === $name ) {
				continue;
			}

			$row_id = isset( $raw_row['row_id'] ) ? sanitize_key( wp_unslash( $raw_row['row_id'] ) ) : '';
			if ( '' === $row_id ) {
				$row_id = self::generate_row_id();
			}

			$rows[] = array(
				'row_id'             => $row_id,
				'term'               => self::sanitize_term( isset( $raw_row['term'] ) ? $raw_row['term'] : '' ),
				'title'              => isset( $raw_row['title'] ) ? sanitize_text_field( wp_unslash( $raw_row['title'] ) ) : '',
				'committee'          => isset( $raw_row['committee'] ) ? sanitize_text_field( wp_unslash( $raw_row['committee'] ) ) : '',
				'name'               => $name,
				'linked_content_id'  => self::sanitize_linked_content_id( isset( $raw_row['linked_content_id'] ) ? $raw_row['linked_content_id'] : 0 ),
			);
		}

		foreach ( $rows as $index => &$row ) {
			$row['order'] = $index + 1;
		}
		unset( $row );

		return $rows;
	}

	/**
	 * A stable, unique row identifier. Wraps wp_generate_uuid4() (a
	 * WordPress core function since 4.7) so the format itself is an
	 * implementation detail callers never need to depend on.
	 *
	 * @return string
	 */
	private static function generate_row_id() {
		return wp_generate_uuid4();
	}

	/**
	 * Validates a raw 卒業期 submission the same way
	 * Content_Meta_Box::sanitize_term() does for a 人物挨拶's own 卒業期 —
	 * a plain positive integer, or '' when empty/invalid. Duplicated
	 * (rather than a cross-module dependency) since it's a two-line rule
	 * with no shared state; see Content_Meta_Box::sanitize_term() for the
	 * identical reasoning against Settings::sanitize_year().
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
	 * revalidation (the public alumni_core_get_officers() API), since a
	 * link that was valid when saved can go stale later if that content
	 * post is trashed/deleted — same "revalidate on read" rule as
	 * Settings::is_valid_image_attachment().
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
	 * post ID, or 0 (no link) for anything empty/invalid/nonexistent —
	 * never a raw URL, and never trusted without checking it still exists.
	 *
	 * @param mixed $raw Raw form value.
	 * @return int
	 */
	public static function sanitize_linked_content_id( $raw ) {
		$id = absint( $raw );

		return self::is_valid_linked_content( $id ) ? $id : 0;
	}
}
