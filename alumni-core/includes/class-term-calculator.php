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
	 * Years from a birth cohort's start (see graduation_year_to_birth_range())
	 * to that cohort's high school graduation, under Japan's standard
	 * 6-3-3 school system (6 elementary + 3 junior-high + 3 high-school
	 * years) and the April-2 school-year cutoff: a child born between
	 * April 2 of year N and April 1 of year N+1 enters 1st grade in April
	 * of N+6, and finishes 12th grade (graduates) in March of N+6+12 =
	 * N+18. This is a standard-progression estimate only — it does not,
	 * and cannot, account for repeated years, transfers, or any other
	 * non-standard history.
	 */
	const GRADUATION_AGE_YEARS = 18;

	/**
	 * Upper bound on how many rows build_lookup_table() will ever compute
	 * in one call, so a pathological from/to range (e.g. a corrupted
	 * request) can't turn one lookup into thousands of loop iterations.
	 */
	const MAX_LOOKUP_ROWS = 200;

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

	/**
	 * The standard-progression 誕生日範囲 (birth-date range) for a
	 * graduation year: children born from April 2 of (graduation_year -
	 * GRADUATION_AGE_YEARS) through April 1 of the following year are the
	 * standard cohort for that graduation year. See GRADUATION_AGE_YEARS
	 * for the full reasoning, and the "重要" caveat there: this is an
	 * estimate assuming standard progression, not a guarantee.
	 *
	 * @param int $graduation_year e.g. 2026.
	 * @return array{start:string,end:string}|null 'Y-m-d' strings, or null
	 *                                               when $graduation_year
	 *                                               is missing/invalid.
	 */
	public static function graduation_year_to_birth_range( $graduation_year ) {
		$graduation_year = (int) $graduation_year;

		if ( $graduation_year <= 0 ) {
			return null;
		}

		$cohort_start_year = $graduation_year - self::GRADUATION_AGE_YEARS;

		return array(
			'start' => sprintf( '%04d-04-02', $cohort_start_year ),
			'end'   => sprintf( '%04d-04-01', $cohort_start_year + 1 ),
		);
	}

	/**
	 * The standard-progression 誕生日範囲 for a graduation term (期).
	 *
	 * @param int $term                  Graduation term (1-based).
	 * @param int $first_graduation_year e.g. 1950.
	 * @return array{start:string,end:string}|null
	 */
	public static function term_to_birth_range( $term, $first_graduation_year ) {
		$year = self::term_to_year( $term, $first_graduation_year );

		if ( null === $year ) {
			return null;
		}

		return self::graduation_year_to_birth_range( $year );
	}

	/**
	 * Estimates the standard-progression graduation year for a birth date,
	 * using the same April-2 cutoff as graduation_year_to_birth_range().
	 * This is an estimate, not a guarantee — see GRADUATION_AGE_YEARS.
	 *
	 * @param string $birthdate 'Y-m-d', e.g. '2008-05-10'.
	 * @return int|null
	 */
	public static function birthdate_to_graduation_year( $birthdate ) {
		if ( ! is_string( $birthdate ) || '' === $birthdate ) {
			return null;
		}

		$date = \DateTime::createFromFormat( 'Y-m-d', $birthdate );

		if ( ! $date || $date->format( 'Y-m-d' ) !== $birthdate ) {
			return null;
		}

		$year  = (int) $date->format( 'Y' );
		$month = (int) $date->format( 'n' );
		$day   = (int) $date->format( 'j' );

		// On/after April 2: this year's cohort. On/before April 1
		// (January through March, or April 1st itself): last year's.
		$cohort_start_year = ( $month > 4 || ( 4 === $month && $day >= 2 ) ) ? $year : $year - 1;

		return $cohort_start_year + self::GRADUATION_AGE_YEARS;
	}

	/**
	 * Estimates the standard-progression graduation term (期) for a birth
	 * date. This is an estimate, not a guarantee — see GRADUATION_AGE_YEARS.
	 *
	 * @param string $birthdate            'Y-m-d'.
	 * @param int    $first_graduation_year e.g. 1950.
	 * @return int|null
	 */
	public static function birthdate_to_term( $birthdate, $first_graduation_year ) {
		$year = self::birthdate_to_graduation_year( $birthdate );

		if ( null === $year ) {
			return null;
		}

		return self::year_to_term( $year, $first_graduation_year );
	}

	/**
	 * Validates and combines separately-submitted 生年／月／日 number
	 * fields into a single 'Y-m-d' string, rejecting anything that isn't a
	 * real calendar date (e.g. month 13, or February 31) via PHP's own
	 * checkdate() rather than a hand-rolled range check — this is the
	 * single source of truth both the admin screen's and the public
	 * shortcode's 生年月日 search forms call, so the two never drift.
	 *
	 * @param mixed $year  Raw year input (e.g. from $_GET).
	 * @param mixed $month Raw month input (1-12).
	 * @param mixed $day   Raw day input (1-31).
	 * @return string|null 'Y-m-d', or null when any part is missing, not a
	 *                      plain integer, or doesn't form a real date.
	 */
	public static function validate_date_parts( $year, $month, $day ) {
		foreach ( array( $year, $month, $day ) as $part ) {
			if ( '' === $part || null === $part || ! is_numeric( $part ) ) {
				return null;
			}
			if ( (string) (int) $part !== (string) ( $part + 0 ) ) {
				return null; // Rejects non-integer numeric input (e.g. "5.5").
			}
		}

		$year  = (int) $year;
		$month = (int) $month;
		$day   = (int) $day;

		if ( $year < 1000 || $year > 9999 ) {
			return null;
		}

		if ( ! checkdate( $month, $day, $year ) ) {
			return null;
		}

		return sprintf( '%04d-%02d-%02d', $year, $month, $day );
	}

	/**
	 * Builds a 卒業期早見表: one row per term in [from_term, to_term],
	 * each with its graduation year and standard-progression birth range.
	 * Safe against a corrupted/invalid $first_graduation_year (returns an
	 * empty array, since every row would fail to resolve a year) and
	 * against a pathologically large range (silently capped at
	 * MAX_LOOKUP_ROWS rows).
	 *
	 * @param int $first_graduation_year e.g. 1950.
	 * @param int $from_term             First term to include (1-based).
	 * @param int $to_term               Last term to include (inclusive).
	 * @return array[] Each row: array('term'=>int,'year'=>int,'birth_range'=>array|null).
	 */
	public static function build_lookup_table( $first_graduation_year, $from_term, $to_term ) {
		$from_term = (int) $from_term;
		$to_term   = (int) $to_term;

		if ( $from_term <= 0 || $to_term < $from_term ) {
			return array();
		}

		$to_term = min( $to_term, $from_term + self::MAX_LOOKUP_ROWS - 1 );

		$rows = array();

		for ( $term = $from_term; $term <= $to_term; $term++ ) {
			$year = self::term_to_year( $term, $first_graduation_year );

			if ( null === $year ) {
				continue;
			}

			$rows[] = array(
				'term'        => $term,
				'year'        => $year,
				'birth_range' => self::graduation_year_to_birth_range( $year ),
			);
		}

		return $rows;
	}

	/**
	 * The default last term (期) a 卒業期早見表 should show when the caller
	 * has no explicit to_term override: 第1期から「現在の年に卒業する期」
	 * まで — a full listing, not a fixed-size page (no hardcoded term
	 * count, e.g. no "30期まで"; this class stays free of WordPress calls
	 * per its docblock, so the caller passes in $current_year, typically
	 * (int) current_time( 'Y' )). MAX_LOOKUP_ROWS in build_lookup_table()
	 * still caps how many rows are actually ever computed, so this can't
	 * turn into an unbounded table.
	 *
	 * A future or otherwise invalid $first_graduation_year — the current
	 * year's term can't be resolved, or resolves to before $from_term —
	 * safely yields an empty table ($from_term - 1, which
	 * build_lookup_table() treats as "nothing to show") rather than
	 * guessing at some other range.
	 *
	 * @param mixed $first_graduation_year e.g. 1950, or '' when unset.
	 * @param int   $from_term             First term the table will show.
	 * @param int   $current_year          e.g. (int) current_time( 'Y' ).
	 * @return int
	 */
	public static function default_to_term( $first_graduation_year, $from_term, $current_year ) {
		$from_term    = max( 1, (int) $from_term );
		$current_term = self::year_to_term( (int) $current_year, $first_graduation_year );

		if ( null === $current_term || $current_term < $from_term ) {
			return $from_term - 1; // Empty range — build_lookup_table() returns no rows.
		}

		return $current_term;
	}

	/**
	 * Whether a hex color is "dark" enough that white text reads better on
	 * it than black — used by 卒業期早見表 to pick a readable text color
	 * when a 卒業期カラー is applied as a row's background rather than
	 * shown as a small swatch. Uses the standard YIQ perceived-brightness
	 * formula (a simple, widely-used luminance heuristic — not full WCAG
	 * contrast-ratio math, which needs a specific text color to compare
	 * against and would be overkill for a binary black/white choice).
	 *
	 * @param string $hex A '#rrggbb' color, as stored in 卒業期カラー
	 *                     settings (see Settings::sanitize() /
	 *                     sanitize_hex_color()).
	 * @return bool True if white text is more readable than black on this
	 *              color; false (including for anything not a valid
	 *              '#rrggbb' string) defaults to black text, the safer
	 *              fallback against the common case of light/pastel colors.
	 */
	public static function is_dark_color( $hex ) {
		if ( ! is_string( $hex ) || ! preg_match( '/^#([0-9a-fA-F]{6})$/', $hex, $matches ) ) {
			return false;
		}

		$r = hexdec( substr( $matches[1], 0, 2 ) );
		$g = hexdec( substr( $matches[1], 2, 2 ) );
		$b = hexdec( substr( $matches[1], 4, 2 ) );

		$yiq = ( ( $r * 299 ) + ( $g * 587 ) + ( $b * 114 ) ) / 1000;

		return $yiq < 128;
	}
}
