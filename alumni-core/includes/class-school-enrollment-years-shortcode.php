<?php
/**
 * 在校年度一覧を基本設定から自動生成するショートコードと固定ページ.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 「学校創立年」と「第1期卒業年」だけを基礎データとして利用し、
 * 高校3年間の在校状態を年度ごと・期ごとに自動配置する。
 *
 * 追加の年表マスタは持たない。基本設定を変更すれば次回表示時に
 * 自動的に再計算される。
 */
class School_Enrollment_Years_Shortcode {

	const OPTION_PAGE_ID = 'alumni_core_school_enrollment_years_page_id';
	const PAGE_SLUG      = 'school-enrollment-years';
	const SHORTCODE      = 'alumni_school_enrollment_years';
	const SCHOOL_YEARS   = 3;
	const TERMS_PER_TABLE = 10;

	public static function register() {
		add_shortcode( self::SHORTCODE, array( __CLASS__, 'render_shortcode' ) );

		if ( is_admin() ) {
			add_action( 'admin_init', array( __CLASS__, 'maybe_create_page' ) );
		}
	}

	public static function maybe_create_page() {
		$page_id = (int) get_option( self::OPTION_PAGE_ID, 0 );

		if ( $page_id && 'page' === get_post_type( $page_id ) ) {
			return;
		}

		$existing = get_page_by_path( self::PAGE_SLUG, OBJECT, 'page' );
		if ( $existing ) {
			update_option( self::OPTION_PAGE_ID, $existing->ID );
			return;
		}

		$new_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => __( '在校年度一覧', 'alumni-core' ),
				'post_name'    => self::PAGE_SLUG,
				'post_content' => '[' . self::SHORTCODE . ']',
			),
			true
		);

		if ( ! is_wp_error( $new_id ) && $new_id ) {
			update_option( self::OPTION_PAGE_ID, $new_id );
		}
	}

	public static function get_url() {
		$page_id = (int) get_option( self::OPTION_PAGE_ID, 0 );

		if ( $page_id && 'page' === get_post_type( $page_id ) ) {
			return (string) get_permalink( $page_id );
		}

		return home_url( '/' . self::PAGE_SLUG . '/' );
	}

	/**
	 * 現在の「年度」を返す（1〜3月は前年4月開始の年度）。
	 *
	 * @return int
	 */
	public static function current_academic_year() {
		$now   = current_time( 'timestamp' );
		$year  = (int) wp_date( 'Y', $now );
		$month = (int) wp_date( 'n', $now );

		return $month <= 3 ? $year - 1 : $year;
	}

	/**
	 * 基本設定から表全体のデータを生成する。
	 *
	 * @return array{start_year:int,end_year:int,first_entry_year:int,last_term:int,blocks:array}|null
	 */
	public static function build_data() {
		$settings              = Settings::instance()->get_all();
		$school_founded_year   = (int) $settings['school_founded_year'];
		$first_graduation_year = (int) $settings['first_graduation_year'];

		if ( $school_founded_year < 1000 || $first_graduation_year < 1000 ) {
			return null;
		}

		$first_entry_year = $first_graduation_year - ( self::SCHOOL_YEARS - 1 );
		$start_year       = min( $school_founded_year, $first_entry_year );
		$end_year         = self::current_academic_year();

		if ( $end_year < $start_year ) {
			return null;
		}

		$last_term = $end_year - $first_entry_year + 1;
		if ( $last_term < 1 ) {
			$last_term = 1;
		}

		$blocks = array();
		for ( $from_term = 1; $from_term <= $last_term; $from_term += self::TERMS_PER_TABLE ) {
			$to_term = min( $last_term, $from_term + self::TERMS_PER_TABLE - 1 );
			$terms   = range( $from_term, $to_term );
			$rows    = array();

			for ( $year = $start_year; $year <= $end_year; $year++ ) {
				$cells = array();

				foreach ( $terms as $term ) {
					$entry_year = $first_entry_year + $term - 1;
					$grade      = $year - $entry_year + 1;
					$cells[ $term ] = ( $grade >= 1 && $grade <= self::SCHOOL_YEARS ) ? $grade : 0;
				}

				$rows[] = array(
					'year'       => $year,
					'era_year'   => self::format_era_year( $year ),
					'era_range'  => self::format_academic_era_range( $year ),
					'greg_range' => sprintf( '%d年4月～%d年3月', $year, $year + 1 ),
					'cells'      => $cells,
				);
			}

			$blocks[] = array(
				'from_term' => $from_term,
				'to_term'   => $to_term,
				'terms'     => $terms,
				'rows'      => $rows,
			);
		}

		return array(
			'start_year'      => $start_year,
			'end_year'        => $end_year,
			'first_entry_year'=> $first_entry_year,
			'last_term'       => $last_term,
			'blocks'          => $blocks,
		);
	}

	/**
	 * 西暦年の4月開始年度を「昭和40年度」のように表示する。
	 *
	 * @param int $year
	 * @return string
	 */
	public static function format_era_year( $year ) {
		$era = self::era_for_date( (int) $year, 4, 1 );

		return $era['name'] . $era['year'] . __( '年度', 'alumni-core' );
	}

	/**
	 * 年度期間を和暦で表示する。元号をまたぐ場合も両端を個別に変換する。
	 *
	 * @param int $year Academic year start.
	 * @return string
	 */
	public static function format_academic_era_range( $year ) {
		$start = self::era_for_date( (int) $year, 4, 1 );
		$end   = self::era_for_date( (int) $year + 1, 3, 31 );

		return sprintf(
			'%s%d年4月～%s%d年3月',
			$start['name'],
			$start['year'],
			$end['name'],
			$end['year']
		);
	}

	/**
	 * 対象日が属する近現代の元号と元号年。
	 *
	 * @return array{name:string,year:int}
	 */
	private static function era_for_date( $year, $month, $day ) {
		$stamp = sprintf( '%04d-%02d-%02d', (int) $year, (int) $month, (int) $day );

		$eras = array(
			array( 'start' => '2019-05-01', 'name' => '令和', 'base' => 2018 ),
			array( 'start' => '1989-01-08', 'name' => '平成', 'base' => 1988 ),
			array( 'start' => '1926-12-25', 'name' => '昭和', 'base' => 1925 ),
			array( 'start' => '1912-07-30', 'name' => '大正', 'base' => 1911 ),
			array( 'start' => '1868-09-08', 'name' => '明治', 'base' => 1867 ),
		);

		foreach ( $eras as $era ) {
			if ( $stamp >= $era['start'] ) {
				return array(
					'name' => $era['name'],
					'year' => (int) $year - (int) $era['base'],
				);
			}
		}

		// 現代の学校設定では通常到達しないが、常に表示可能な値を返す。
		return array( 'name' => '', 'year' => (int) $year );
	}

	public static function render_shortcode() {
		return self::render_table();
	}

	/**
	 * 公開ページ・管理画面で共通利用する表HTML。
	 *
	 * @return string
	 */
	public static function render_table() {
		$data = self::build_data();

		ob_start();
		?>
		<style>
			.alumni-school-years-wrap{overflow-x:auto;margin:1.5em 0}
			.alumni-school-years-table{border-collapse:collapse;width:100%;min-width:980px;font-size:16px}
			.alumni-school-years-table th,.alumni-school-years-table td{border:1px solid #d6dbe1;padding:.45em .55em;text-align:center;white-space:nowrap}
			.alumni-school-years-table thead th,.alumni-school-years-label{background:#8fc0d8;color:#22313b;font-weight:700}
			.alumni-school-years-table .alumni-school-years-term{min-width:68px}
			.alumni-school-years-table .alumni-school-years-period{min-width:220px}
			.alumni-school-years-table tbody tr:nth-child(even) td{background-color:#f8fafb}
		</style>
		<?php
		if ( null === $data ) {
			echo '<p>' . esc_html__( '在校年度一覧を生成するには、基本設定の「学校創立年」と「第1期卒業年」を入力してください。', 'alumni-core' ) . '</p>';
			return (string) ob_get_clean();
		}

		foreach ( $data['blocks'] as $block ) :
			?>
			<div class="alumni-school-years-wrap">
				<table class="alumni-school-years-table">
					<thead>
						<tr>
							<th scope="col"><?php echo esc_html__( '在校年度', 'alumni-core' ); ?></th>
							<?php foreach ( $block['terms'] as $term ) : ?>
								<?php
								$color = function_exists( 'alumni_core_term_to_color' ) ? alumni_core_term_to_color( $term ) : null;
								$style = $color ? 'background-color:' . esc_attr( $color ) . ';color:' . ( Term_Calculator::is_dark_color( $color ) ? '#fff' : '#22313b' ) . ';' : '';
								?>
								<th scope="col" class="alumni-school-years-term" style="<?php echo $style; ?>"><?php echo esc_html( sprintf( '%02d期生', $term ) ); ?></th>
							<?php endforeach; ?>
							<th scope="col" class="alumni-school-years-period"><?php echo esc_html__( '在校年・月（元号）', 'alumni-core' ); ?></th>
							<th scope="col" class="alumni-school-years-period"><?php echo esc_html__( '在校年・月（西暦）', 'alumni-core' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $block['rows'] as $row ) : ?>
							<tr>
								<th scope="row" class="alumni-school-years-label"><?php echo esc_html( $row['era_year'] ); ?></th>
								<?php foreach ( $block['terms'] as $term ) : ?>
									<td><?php echo $row['cells'][ $term ] ? esc_html( $row['cells'][ $term ] . '年生' ) : ''; ?></td>
								<?php endforeach; ?>
								<td><?php echo esc_html( $row['era_range'] ); ?></td>
								<td><?php echo esc_html( $row['greg_range'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php
		endforeach;

		return (string) ob_get_clean();
	}
}
