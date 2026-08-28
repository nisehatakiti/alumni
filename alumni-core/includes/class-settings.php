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
			'association_name'      => '',
			'school_name'            => '',
			'school_founded_year'    => '',
			'first_graduation_year'  => '',
			'color_feature_enabled'  => false,
			'color_cycle'            => 1,
			'colors'                 => array( 1 => '#cc0000' ),
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
	 * Sanitizes and saves a full settings array (as submitted by the
	 * settings form).
	 *
	 * @param array $raw Raw, unsanitized input (e.g. from $_POST).
	 * @return array The sanitized values that were saved.
	 */
	public function save( array $raw ) {
		$sanitized = $this->sanitize( $raw );

		update_option( self::OPTION_NAME, $sanitized );

		$this->settings = $sanitized;

		return $sanitized;
	}

	/**
	 * Sanitizes a raw settings array against the defaults' shape.
	 *
	 * @param array $raw Raw input.
	 * @return array
	 */
	private function sanitize( array $raw ) {
		$defaults = $this->defaults();

		$sanitized = array(
			'association_name'     => isset( $raw['association_name'] ) ? sanitize_text_field( wp_unslash( $raw['association_name'] ) ) : $defaults['association_name'],
			'school_name'           => isset( $raw['school_name'] ) ? sanitize_text_field( wp_unslash( $raw['school_name'] ) ) : $defaults['school_name'],
			'school_founded_year'   => $this->sanitize_year( isset( $raw['school_founded_year'] ) ? $raw['school_founded_year'] : '' ),
			'first_graduation_year' => $this->sanitize_year( isset( $raw['first_graduation_year'] ) ? $raw['first_graduation_year'] : '' ),
			'color_feature_enabled' => ! empty( $raw['color_feature_enabled'] ),
			'color_cycle'           => isset( $raw['color_cycle'] ) ? min( self::MAX_COLOR_CYCLE, max( 1, absint( $raw['color_cycle'] ) ) ) : $defaults['color_cycle'],
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
}
