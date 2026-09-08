<?php
/**
 * トップページの固定Homepage Gridを管理する.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes;

use AlumniCore\Includes\Modules\Content\Post_Type as Content_Post_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * トップページは3列×2段の6セル（A〜F）を正本として管理する。
 *
 * Coreは「各セルに何を表示するか」と「上下セルを結合するか」だけを保存し、
 * 実際のカード・リスト等の見た目はTheme側が担当する。
 *
 * 旧バージョンのsection/columns/sl​​otsデータが保存されている場合は、
 * 読み取り時に先頭からA〜Fへ移行して同じOPTION_NAMEへ保存する。既存の
 * 選択内容を捨てずに固定Gridへ移行するためである。
 */
class Homepage_Sections {

	const OPTION_NAME = 'alumni_core_homepage_sections';

	const GRID_VERSION = 2;

	const CELL_A = 'A';
	const CELL_B = 'B';
	const CELL_C = 'C';
	const CELL_D = 'D';
	const CELL_E = 'E';
	const CELL_F = 'F';

	const SYSTEM_NEWS              = 'news';
	const SYSTEM_EVENTS            = 'events';
	const SYSTEM_OFFICERS_INDEX    = 'officers_index';
	const SYSTEM_TERMS_INDEX       = 'terms_index';
	const SYSTEM_GRADUATION_LOOKUP = 'graduation_lookup';
	const SYSTEM_ORG_CHART         = 'org_chart';
	const SYSTEM_SCHOOL_PHOTOS     = 'school_photos';

	private static $instance = null;

	/**
	 * @var array|null
	 */
	private $grid = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {}

	/**
	 * @return string[]
	 */
	public static function cell_keys() {
		return array( self::CELL_A, self::CELL_B, self::CELL_C, self::CELL_D, self::CELL_E, self::CELL_F );
	}

	/**
	 * @return string[]
	 */
	public static function mergeable_top_cells() {
		return array( self::CELL_A, self::CELL_B, self::CELL_C );
	}

	/**
	 * @return array<string,string>
	 */
	public static function system_key_labels() {
		return array(
			self::SYSTEM_NEWS              => __( 'ニュース一覧', 'alumni-core' ),
			self::SYSTEM_EVENTS            => __( 'イベント一覧', 'alumni-core' ),
			self::SYSTEM_OFFICERS_INDEX    => __( '役員・理事紹介', 'alumni-core' ),
			self::SYSTEM_TERMS_INDEX       => __( '規約類一覧', 'alumni-core' ),
			self::SYSTEM_GRADUATION_LOOKUP => __( '卒業期早見表', 'alumni-core' ),
			self::SYSTEM_ORG_CHART         => __( '同窓会組織図', 'alumni-core' ),
			self::SYSTEM_SCHOOL_PHOTOS     => __( '学校写真', 'alumni-core' ),
		);
	}

	/**
	 * @return string[]
	 */
	public static function system_keys() {
		return array_keys( self::system_key_labels() );
	}

	/**
	 * @param string $system_key
	 * @return string
	 */
	public static function resolve_system_url( $system_key ) {
		switch ( $system_key ) {
			case self::SYSTEM_NEWS:
				return alumni_core_get_news_listing_url();
			case self::SYSTEM_EVENTS:
				return alumni_core_get_events_listing_url();
			case self::SYSTEM_OFFICERS_INDEX:
				return alumni_core_get_officers_listing_url();
			case self::SYSTEM_TERMS_INDEX:
				return alumni_core_get_terms_listing_url();
			case self::SYSTEM_GRADUATION_LOOKUP:
				return alumni_core_get_graduation_lookup_url();
			case self::SYSTEM_ORG_CHART:
				return alumni_core_get_org_chart_url();
			case self::SYSTEM_SCHOOL_PHOTOS:
				return \AlumniCore\Includes\School_Photos_Shortcode::get_url();
			default:
				return '';
		}
	}

	/**
	 * 固定3×2 Gridを返す。
	 *
	 * @return array{
	 *   version:int,
	 *   cells:array<string,array>,
	 *   merged_columns:array<string,bool>
	 * }
	 */
	public function get_grid() {
		if ( null === $this->grid ) {
			$saved = get_option( self::OPTION_NAME, null );

			if ( null === $saved ) {
				$this->grid = self::default_grid();
				update_option( self::OPTION_NAME, $this->grid );
			} elseif ( self::is_grid_shape( $saved ) ) {
				$this->grid = self::normalize_grid( $saved );
			} else {
				// 旧section形式から、保存順にA〜Fへ移行する。
				$this->grid = self::migrate_legacy_sections( $saved );
				update_option( self::OPTION_NAME, $this->grid );
			}
		}

		return $this->grid;
	}

	/**
	 * 旧API互換。V2以降は固定Gridそのものを返す。
	 *
	 * @return array
	 */
	public function get_all() {
		return $this->get_grid();
	}

	/**
	 * @return array
	 */
	private static function default_grid() {
		$principal_group = Person_Greeting_Groups::instance()->create_group( __( '母校校長挨拶', 'alumni-core' ) );
		$chair_group     = Person_Greeting_Groups::instance()->create_group( __( '同窓会長挨拶', 'alumni-core' ) );

		return self::normalize_grid(
			array(
				'version' => self::GRID_VERSION,
				'cells'   => array(
					self::CELL_A => array( 'type' => 'person_greeting_group', 'group_id' => $principal_group ),
					self::CELL_B => array( 'type' => 'person_greeting_group', 'group_id' => $chair_group ),
					self::CELL_C => array( 'type' => 'none' ),
					self::CELL_D => array( 'type' => 'system', 'system_key' => self::SYSTEM_NEWS ),
					self::CELL_E => array( 'type' => 'system', 'system_key' => self::SYSTEM_EVENTS ),
					self::CELL_F => array( 'type' => 'none' ),
				),
				'merged_columns' => array( self::CELL_A => false, self::CELL_B => false, self::CELL_C => false ),
			)
		);
	}

	/**
	 * @param mixed $saved
	 * @return bool
	 */
	private static function is_grid_shape( $saved ) {
		return is_array( $saved ) && isset( $saved['cells'] ) && is_array( $saved['cells'] );
	}

	/**
	 * @param mixed $legacy
	 * @return array
	 */
	private static function migrate_legacy_sections( $legacy ) {
		$cells = array_fill_keys( self::cell_keys(), array( 'type' => 'none' ) );
		$flat  = array();

		if ( is_array( $legacy ) ) {
			foreach ( $legacy as $section ) {
				if ( ! is_array( $section ) || empty( $section['slots'] ) || ! is_array( $section['slots'] ) ) {
					continue;
				}
				foreach ( $section['slots'] as $slot ) {
					$flat[] = $slot;
				}
			}
		}

		foreach ( self::cell_keys() as $index => $cell_key ) {
			if ( isset( $flat[ $index ] ) ) {
				$cells[ $cell_key ] = self::normalize_slot( $flat[ $index ] );
			}
		}

		return self::normalize_grid(
			array(
				'version'        => self::GRID_VERSION,
				'cells'          => $cells,
				'merged_columns' => array(),
			)
		);
	}

	/**
	 * @param array $grid
	 * @return array
	 */
	private static function normalize_grid( $grid ) {
		$grid = is_array( $grid ) ? $grid : array();
		$cells = isset( $grid['cells'] ) && is_array( $grid['cells'] ) ? $grid['cells'] : array();
		$merged = isset( $grid['merged_columns'] ) && is_array( $grid['merged_columns'] ) ? $grid['merged_columns'] : array();

		$normalized_cells = array();
		foreach ( self::cell_keys() as $cell_key ) {
			$normalized_cells[ $cell_key ] = self::normalize_slot( isset( $cells[ $cell_key ] ) ? $cells[ $cell_key ] : array( 'type' => 'none' ) );
		}

		$normalized_merged = array();
		foreach ( self::mergeable_top_cells() as $cell_key ) {
			$normalized_merged[ $cell_key ] = ! empty( $merged[ $cell_key ] );
		}

		return array(
			'version'        => self::GRID_VERSION,
			'cells'          => $normalized_cells,
			'merged_columns' => $normalized_merged,
		);
	}

	/**
	 * @param mixed $slot
	 * @return array
	 */
	private static function normalize_slot( $slot ) {
		if ( ! is_array( $slot ) || empty( $slot['type'] ) ) {
			return self::with_display_options( $slot, array( 'type' => 'none' ) );
		}

		if ( 'content' === $slot['type'] ) {
			$content_id = isset( $slot['content_id'] ) ? absint( $slot['content_id'] ) : 0;
			return self::with_display_options(
				$slot,
				self::is_publishable_content( $content_id )
					? array( 'type' => 'content', 'content_id' => $content_id )
					: array( 'type' => 'none' )
			);
		}

		if ( 'system' === $slot['type'] ) {
			$key = isset( $slot['system_key'] ) ? (string) $slot['system_key'] : '';
			return self::with_display_options(
				$slot,
				in_array( $key, self::system_keys(), true )
					? array( 'type' => 'system', 'system_key' => $key )
					: array( 'type' => 'none' )
			);
		}

		if ( 'person_greeting_group' === $slot['type'] ) {
			$group_id = isset( $slot['group_id'] ) ? sanitize_text_field( $slot['group_id'] ) : '';
			return self::with_display_options(
				$slot,
				'' !== $group_id && null !== Person_Greeting_Groups::instance()->get_group( $group_id )
					? array( 'type' => 'person_greeting_group', 'group_id' => $group_id )
					: array( 'type' => 'none' )
			);
		}

		return self::with_display_options( $slot, array( 'type' => 'none' ) );
	}

	/**
	 * Every cell may optionally override its public heading and configure
	 * how many list items system blocks should output.
	 *
	 * @param mixed $raw
	 * @param array $normalized
	 * @return array
	 */
	private static function with_display_options( $raw, array $normalized ) {
		$raw = is_array( $raw ) ? $raw : array();

		$normalized['heading'] = isset( $raw['heading'] ) ? sanitize_text_field( $raw['heading'] ) : '';
		$normalized['item_count'] = isset( $raw['item_count'] ) ? max( 1, min( 10, absint( $raw['item_count'] ) ) ) : 3;

		return $normalized;
	}

	/**
	 * @param int $content_id
	 * @return bool
	 */
	private static function is_publishable_content( $content_id ) {
		$post = $content_id ? get_post( $content_id ) : null;
		return $post && Content_Post_Type::SLUG === $post->post_type && 'publish' === $post->post_status;
	}

	/**
	 * @param array $cells
	 * @param array $merged_columns
	 * @return array
	 */
	public function update_grid( array $cells, array $merged_columns ) {
		$this->grid = self::normalize_grid(
			array(
				'version'        => self::GRID_VERSION,
				'cells'          => $cells,
				'merged_columns' => $merged_columns,
			)
		);

		update_option( self::OPTION_NAME, $this->grid );

		return $this->grid;
	}
}
