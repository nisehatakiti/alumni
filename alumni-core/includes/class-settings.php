<?php
/**
 * Reads and writes the 同窓会設定 (association settings).
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores every Alumni Core setting in a single wp_options row and provides
 * a small, stable API for the admin screens, the theme, and future modules
 * to read from and write to.
 */
class Settings {

	/**
	 * The wp_options row name.
	 */
	const OPTION_NAME = 'alumni_core_settings';

	/**
	 * Upper bound on the color cycle. Enforced here (not just in the form)
	 * since this is also the loop bound used to build the colors array.
	 */
	const MAX_COLOR_CYCLE = 100;

	/**
	 * Sanity floor for 学校創立年 / 第1期卒業年. Not a real historical
	 * constraint, just low enough to reject obvious garbage (negative
	 * numbers, single/double-digit values) without ever rejecting a real
	 * school's founding year.
	 */
	const MIN_YEAR = 1000;

	/**
	 * How many years past the current year 学校創立年 / 第1期卒業年 may be
	 * set to. Kept as an offset from "now" (not a fixed year) so the
	 * allowed range doesn't need updating as time passes.
	 */
	const YEAR_FUTURE_BUFFER = 10;

	/**
	 * 学校関連写真 display modes.
	 */
	const PHOTO_MODE_FIXED     = 'fixed';
	const PHOTO_MODE_SLIDESHOW = 'slideshow';

	/**
	 * 写真の切替時間（自動切替モード）の許容範囲（秒）と既定値。未設定、
	 * または範囲外／不正な値は常にこの既定値にフォールバックする。
	 */
	const MIN_SLIDESHOW_INTERVAL     = 1;
	const MAX_SLIDESHOW_INTERVAL     = 60;
	const DEFAULT_SLIDESHOW_INTERVAL = 5;

	/**
	 * Singleton instance.
	 *
	 * @var Settings|null
	 */
	private static $instance = null;

	/**
	 * Cached settings array.
	 *
	 * @var array|null
	 */
	private $settings = null;

	/**
	 * Returns the singleton instance.
	 *
	 * @return Settings
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
	 * Default values for every setting.
	 *
	 * @return array
	 */
	public function defaults() {
		return array(
			'association_name'          => '',
			'school_name'                => '',
			// Only used as a fallback when the setting has never been saved
			// at all (see get_all()/set_defaults()); an existing site's
			// saved value — even an explicitly empty one — is never
			// overwritten by this default.
			'school_founded_year'        => 1950,
			'first_graduation_year'      => '',
			'color_feature_enabled'      => false,
			'color_cycle'                => 1,
			'colors'                     => array( 1 => '#cc0000' ),
			// 0 means "unset", matching WordPress's own convention for
			// attachment ID fields (e.g. get_post_thumbnail_id()).
			'school_emblem_id'           => 0,
			'alumni_logo_id'             => 0,
			'school_photo_ids'           => array(),
			'school_photo_display_mode'  => self::PHOTO_MODE_FIXED,
			'school_photo_featured_id'   => 0,
			// 自動切替（スライドショー）モードでのみ使用され、固定表示では
			// 無視される。
			'school_photo_slideshow_interval' => self::DEFAULT_SLIDESHOW_INTERVAL,
		);
	}

	/**
	 * Writes the default values to the database. Only called on
	 * activation, and only fills in values that are not already set.
	 */
	public function set_defaults() {
		$existing = get_option( self::OPTION_NAME, array() );
		if ( ! is_array( $existing ) ) {
			$existing = array();
		}

		update_option( self::OPTION_NAME, wp_parse_args( $existing, $this->defaults() ) );
	}

	/**
	 * Returns every saved setting, merged with defaults for missing keys.
	 *
	 * @return array
	 */
	public function get_all() {
		if ( null === $this->settings ) {
			$saved    = get_option( self::OPTION_NAME, array() );
			$saved    = is_array( $saved ) ? $saved : array();
			$settings = wp_parse_args( $saved, $this->defaults() );

			// wp_parse_args() only fills in missing keys; a 'colors' key
			// that exists but is corrupted (not an array) must still be
			// repaired here so every caller can trust get( 'colors' ) is
			// always an array.
			if ( ! is_array( $settings['colors'] ) ) {
				$settings['colors'] = $this->defaults()['colors'];
			}

			$this->settings = $settings;
		}

		return $this->settings;
	}

	/**
	 * The latest year 学校創立年 / 第1期卒業年 may be set to, recomputed
	 * from the current date on every call rather than hardcoded.
	 *
	 * @return int
	 */
	public static function max_year() {
		return (int) gmdate( 'Y' ) + self::YEAR_FUTURE_BUFFER;
	}

	/**
	 * Returns a single setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Fallback value if the key is unknown.
	 * @return mixed
	 */
	public function get( $key, $default = '' ) {
		$all = $this->get_all();

		return array_key_exists( $key, $all ) ? $all[ $key ] : $default;
	}

	/**
	 * Sanitizes and saves the 基本設定 form's fields (as submitted by
	 * Settings_Page). Fields owned by other admin screens (currently just
	 * 学校写真, see update_field()) are carried over unchanged from the
	 * current saved values — this form never touches them.
	 *
	 * @param array $raw Raw, unsanitized input (e.g. from $_POST).
	 * @return array The sanitized values that were saved.
	 */
	public function save( array $raw ) {
		$sanitized = $this->sanitize( $raw, $this->get_all() );

		update_option( self::OPTION_NAME, $sanitized );

		$this->settings = $sanitized;

		return $sanitized;
	}

	/**
	 * Updates a single field and persists the full settings array, without
	 * touching any other field. For admin screens (currently 学校写真の管理)
	 * that manage a specific subset of settings separately from 基本設定's
	 * single form, so saving one never clobbers the other's fields.
	 *
	 * @param string $key   A key from defaults().
	 * @param mixed  $value Already-sanitized value to store.
	 * @return array The full settings array that was saved.
	 */
	public function update_field( $key, $value ) {
		return $this->update_fields( array( $key => $value ) );
	}

	/**
	 * Same as update_field(), but for several fields in one write — used
	 * when an admin screen's form (e.g. 学校写真) submits more than one
	 * field it owns, to avoid a separate update_option() call per field.
	 *
	 * @param array $values Map of already-sanitized key => value pairs.
	 * @return array The full settings array that was saved.
	 */
	public function update_fields( array $values ) {
		$current = array_merge( $this->get_all(), $values );

		update_option( self::OPTION_NAME, $current );

		$this->settings = $current;

		return $current;
	}

	/**
	 * Sanitizes the 基本設定 form's fields against the current saved
	 * settings shape. Any key not explicitly handled here (i.e. not owned
	 * by this form) is carried over unchanged from $fallback, rather than
	 * reset to defaults()  — otherwise saving 基本設定 would silently wipe
	 * out fields other admin screens manage (e.g. 学校写真).
	 *
	 * @param array $raw      Raw input.
	 * @param array $fallback Current full settings, used for any field
	 *                         this form doesn't submit.
	 * @return array
	 */
	private function sanitize( array $raw, array $fallback ) {
		$sanitized = array(
			'association_name'     => isset( $raw['association_name'] ) ? sanitize_text_field( wp_unslash( $raw['association_name'] ) ) : $fallback['association_name'],
			'school_name'           => isset( $raw['school_name'] ) ? sanitize_text_field( wp_unslash( $raw['school_name'] ) ) : $fallback['school_name'],
			'school_founded_year'   => $this->sanitize_year( isset( $raw['school_founded_year'] ) ? $raw['school_founded_year'] : '' ),
			'first_graduation_year' => $this->sanitize_year( isset( $raw['first_graduation_year'] ) ? $raw['first_graduation_year'] : '' ),
			'color_feature_enabled' => ! empty( $raw['color_feature_enabled'] ),
			'color_cycle'           => isset( $raw['color_cycle'] ) ? min( self::MAX_COLOR_CYCLE, max( 1, absint( $raw['color_cycle'] ) ) ) : $fallback['color_cycle'],
			// array_key_exists() (not isset()) so a genuinely missing key
			// preserves the current value, while a key present with an
			// empty value (the media picker's "削除" button) still clears
			// it to 0 — those are different submissions and must not be
			// treated the same.
			'school_emblem_id'      => array_key_exists( 'school_emblem_id', $raw ) ? self::sanitize_attachment_id( $raw['school_emblem_id'] ) : $fallback['school_emblem_id'],
			'alumni_logo_id'        => array_key_exists( 'alumni_logo_id', $raw ) ? self::sanitize_attachment_id( $raw['alumni_logo_id'] ) : $fallback['alumni_logo_id'],
			// Not part of the 基本設定 form — always carried over as-is.
			'school_photo_ids'                => $fallback['school_photo_ids'],
			'school_photo_display_mode'       => $fallback['school_photo_display_mode'],
			'school_photo_featured_id'        => $fallback['school_photo_featured_id'],
			'school_photo_slideshow_interval' => $fallback['school_photo_slideshow_interval'],
		);

		$cycle  = $sanitized['color_cycle'];
		$colors = array();

		for ( $i = 1; $i <= $cycle; $i++ ) {
			$raw_color = isset( $raw['colors'][ $i ] ) ? wp_unslash( $raw['colors'][ $i ] ) : '';
			$color     = sanitize_hex_color( $raw_color );
			$colors[ $i ] = $color ? $color : '#cccccc';
		}

		$sanitized['colors'] = $colors;

		return $sanitized;
	}

	/**
	 * Validates a raw 学校創立年 / 第1期卒業年 submission as a plausible
	 * calendar year, rather than relying on absint() (which silently
	 * turns a negative input like "-1950" into a different, valid-looking
	 * year, 1950, instead of rejecting it).
	 *
	 * @param mixed $raw Raw form value.
	 * @return int|string A validated year, or '' when empty/invalid
	 *                     (treated as "未設定" throughout the plugin).
	 */
	private function sanitize_year( $raw ) {
		if ( '' === $raw || null === $raw || ! is_numeric( $raw ) ) {
			return '';
		}

		// Reject non-integer numeric strings (e.g. "1950.5") up front;
		// (int) casting them below would silently truncate instead.
		if ( (string) (int) $raw !== (string) ( $raw + 0 ) ) {
			return '';
		}

		$year = (int) $raw;

		if ( $year < self::MIN_YEAR || $year > self::max_year() ) {
			return '';
		}

		return $year;
	}

	/**
	 * Whether $id is a real, currently-existing WordPress media attachment
	 * that is an image. This is the single source of truth for "usable
	 * image ID", shared by save-time validation (sanitize_attachment_id())
	 * and read-time filtering (filter_valid_image_attachments()) — an ID
	 * that was valid when saved can go stale later if the attachment is
	 * deleted from the Media Library, so both paths need to agree on what
	 * still counts as valid "now", not just "at save time".
	 *
	 * @param int $id Attachment ID (already cast to a non-negative int).
	 * @return bool
	 */
	public static function is_valid_image_attachment( $id ) {
		return $id > 0 && wp_attachment_is_image( $id );
	}

	/**
	 * Validates a single WordPress media attachment ID (校章 / 同窓会ロゴ /
	 * 固定表示の写真). Confirms it's a real image attachment rather than
	 * trusting an arbitrary submitted number, since a stale or invalid ID
	 * would otherwise just silently render nothing on the front end.
	 *
	 * @param mixed $raw Raw form value.
	 * @return int A valid attachment ID, or 0 when empty/invalid.
	 */
	public static function sanitize_attachment_id( $raw ) {
		$id = absint( $raw );

		return self::is_valid_image_attachment( $id ) ? $id : 0;
	}

	/**
	 * Validates an array of attachment IDs (学校関連写真), preserving
	 * submission order (which is also display order) and dropping
	 * duplicates and anything that isn't a real image attachment.
	 *
	 * @param mixed $raw Raw form value, expected to be an array.
	 * @return int[] Ordered, de-duplicated, validated attachment IDs.
	 */
	public static function sanitize_attachment_ids( $raw ) {
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$ids = array();

		foreach ( $raw as $value ) {
			$id = self::sanitize_attachment_id( $value );

			if ( $id > 0 && ! in_array( $id, $ids, true ) ) {
				$ids[] = $id;
			}
		}

		return $ids;
	}

	/**
	 * Filters a list of previously-saved attachment IDs down to only
	 * those that are still valid, currently-existing image attachments —
	 * for read-time use (学校写真一覧取得 / featured photo取得), since a
	 * save-time-valid ID can go stale if the attachment is deleted from
	 * the Media Library afterwards. Order is preserved.
	 *
	 * @param array $ids Attachment IDs, as saved (already de-duplicated
	 *                    and save-time-validated by sanitize_attachment_ids(),
	 *                    but not necessarily still valid *now*).
	 * @return int[] Only the still-valid ones, in the same order.
	 */
	public static function filter_valid_image_attachments( array $ids ) {
		$valid = array();

		foreach ( $ids as $id ) {
			$id = absint( $id );

			if ( self::is_valid_image_attachment( $id ) ) {
				$valid[] = $id;
			}
		}

		return $valid;
	}

	/**
	 * Validates a 学校関連写真 display mode, defaulting to
	 * self::PHOTO_MODE_FIXED for anything unrecognized.
	 *
	 * @param mixed $raw Raw form value.
	 * @return string self::PHOTO_MODE_FIXED or self::PHOTO_MODE_SLIDESHOW.
	 */
	public static function sanitize_display_mode( $raw ) {
		return self::PHOTO_MODE_SLIDESHOW === $raw ? self::PHOTO_MODE_SLIDESHOW : self::PHOTO_MODE_FIXED;
	}

	/**
	 * Validates a 写真の切替時間（秒）submission: integers only, clamped to
	 * [MIN_SLIDESHOW_INTERVAL, MAX_SLIDESHOW_INTERVAL]. Anything empty,
	 * non-numeric, or non-integer (e.g. "5.5") falls back to
	 * self::DEFAULT_SLIDESHOW_INTERVAL rather than being silently truncated
	 * or rejected outright — this setting is only ever cosmetic (a
	 * slideshow timing), so a safe default is preferable to blocking save.
	 *
	 * @param mixed $raw Raw form value.
	 * @return int An integer within the allowed range.
	 */
	public static function sanitize_slideshow_interval( $raw ) {
		if ( '' === $raw || null === $raw || ! is_numeric( $raw ) ) {
			return self::DEFAULT_SLIDESHOW_INTERVAL;
		}

		if ( (string) (int) $raw !== (string) ( $raw + 0 ) ) {
			return self::DEFAULT_SLIDESHOW_INTERVAL;
		}

		$seconds = (int) $raw;

		if ( $seconds < self::MIN_SLIDESHOW_INTERVAL || $seconds > self::MAX_SLIDESHOW_INTERVAL ) {
			return self::DEFAULT_SLIDESHOW_INTERVAL;
		}

		return $seconds;
	}
}
