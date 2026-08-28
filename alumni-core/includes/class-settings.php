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
			$saved          = get_option( self::OPTION_NAME, array() );
			$saved          = is_array( $saved ) ? $saved : array();
			$this->settings = wp_parse_args( $saved, $this->defaults() );
		}

		return $this->settings;
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
			'school_founded_year'   => isset( $raw['school_founded_year'] ) && '' !== $raw['school_founded_year'] ? absint( $raw['school_founded_year'] ) : '',
			'first_graduation_year' => isset( $raw['first_graduation_year'] ) && '' !== $raw['first_graduation_year'] ? absint( $raw['first_graduation_year'] ) : '',
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
}
