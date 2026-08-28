<?php
/**
 * Shared graduation-year → term (期) → color calculations.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pure calculation helpers, kept free of WordPress option lookups so they
 * can be unit tested and reused by any future feature (名簿, 卒業期早見表,
 * Alumni Voices, ...) without duplicating the formulas.
 */
class Term_Calculator {

	/**
	 * Converts a graduation year to a graduation term (期), based on the
	 * association's first graduation year.
	 *
	 * term = graduation_year - first_graduation_year + 1
	 *
	 * @param int $graduation_year       e.g. 1996.
	 * @param int $first_graduation_year e.g. 1950.
	 * @return int|null Term number (1-based), or null when it cannot be
	 *                   determined (missing/invalid input, or a graduation
	 *                   year earlier than the first graduating class).
	 */
	public static function year_to_term( $graduation_year, $first_graduation_year ) {
		$graduation_year       = (int) $graduation_year;
		$first_graduation_year = (int) $first_graduation_year;

		if ( $graduation_year <= 0 || $first_graduation_year <= 0 ) {
			return null;
		}

		$term = $graduation_year - $first_graduation_year + 1;

		return $term > 0 ? $term : null;
	}

	/**
	 * Converts a graduation term (期) back to the graduation year it
	 * corresponds to.
	 *
	 * @param int $term                  e.g. 1.
	 * @param int $first_graduation_year e.g. 1950.
	 * @return int|null
	 */
	public static function term_to_year( $term, $first_graduation_year ) {
		$term                   = (int) $term;
		$first_graduation_year = (int) $first_graduation_year;

		if ( $term <= 0 || $first_graduation_year <= 0 ) {
			return null;
		}

		return $first_graduation_year + $term - 1;
	}

	/**
	 * Maps a graduation term to its 1-based position within the color
	 * cycle. Cycle 3: term 1 → 1, term 2 → 2, term 3 → 3, term 4 → 1, ...
	 *
	 * @param int $term  Graduation term (1-based).
	 * @param int $cycle Number of colors in the cycle.
	 * @return int|null 1-based index into the colors array, or null when
	 *                   it cannot be determined.
	 */
	public static function term_to_color_index( $term, $cycle ) {
		$term  = (int) $term;
		$cycle = (int) $cycle;

		if ( $term <= 0 || $cycle <= 0 ) {
			return null;
		}

		return ( ( $term - 1 ) % $cycle ) + 1;
	}

	/**
	 * Resolves a graduation term straight to a color value, given the
	 * colors array saved in 同窓会設定 (keyed 1..cycle).
	 *
	 * $colors is intentionally untyped (not `array $colors`): a corrupted
	 * or unexpected option value must fail safe with null, never a fatal
	 * TypeError, since this is called from public-facing theme code.
	 *
	 * @param int   $term   Graduation term (1-based).
	 * @param int   $cycle  Number of colors in the cycle.
	 * @param mixed $colors Expected: colors keyed by 1-based cycle position.
	 * @return string|null
	 */
	public static function term_to_color( $term, $cycle, $colors ) {
		if ( ! is_array( $colors ) ) {
			return null;
		}

		$index = self::term_to_color_index( $term, $cycle );

		if ( null === $index || ! isset( $colors[ $index ] ) ) {
			return null;
		}

		return $colors[ $index ];
	}
}
