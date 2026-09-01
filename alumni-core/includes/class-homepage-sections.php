<?php
/**
 * トップページのセクション（スロットベースのレイアウト）を管理する.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes;

use AlumniCore\Includes\Modules\Content\Post_Type as Content_Post_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 「スロットベースのレイアウト」（docs/top-page-slot-based-layout-design.md
 * 他）の実装。トップページはセクションの並びで構成され、各セクションは
 * 1〜3段のスロットを持ち、各スロットに表示コンテンツを割り当てる。
 *
 * 重要な分離:
 *   - このクラス（Core）が持つのは「どのスロットに何を表示するか」という
 *     選択結果だけ。
 *   - 実際の見た目（カード／リンク一覧などのHTML・CSS）はTheme側の責務。
 *
 * スロットの参照先は2種類:
 *   - type=content: 特定の alumni_content 投稿（自由コンテンツ／人物挨拶／
 *     規約類）。
 *   - type=system:  この一覧・機能そのもの（ニュース一覧／イベント一覧／
 *     役員・理事紹介インデックス／規約類一覧／卒業期早見表）— Officer_Lists
 *     のような投稿を持たない機能もスロットに置けるようにするための特別枠。
 *
 * 「非公開コンテンツを安全に処理する」方針はOfficer_Lists／Post_Typeの
 * 既存パターンと同じ: 保存されている値そのものは書き換えず、読み取り時に
 * 無効な参照（削除・非公開化されたコンテンツ）を type=none として扱う。
 * 後で再公開されれば、何もしなくても自動的にまた表示される。
 */
class Homepage_Sections {

	/**
	 * The wp_options row name.
	 */
	const OPTION_NAME = 'alumni_core_homepage_sections';

	const MIN_COLUMNS = 1;
	const MAX_COLUMNS = 3;

	/**
	 * type=systemのスロットが参照できる機能キー一覧。
	 */
	const SYSTEM_NEWS               = 'news';
	const SYSTEM_EVENTS             = 'events';
	const SYSTEM_OFFICERS_INDEX     = 'officers_index';
	const SYSTEM_TERMS_INDEX        = 'terms_index';
	const SYSTEM_GRADUATION_LOOKUP  = 'graduation_lookup';

	/**
	 * Singleton instance.
	 *
	 * @var Homepage_Sections|null
	 */
	private static $instance = null;

	/**
	 * Cached, normalized sections array.
	 *
	 * @var array[]|null
	 */
	private $sections = null;

	/**
	 * @return Homepage_Sections
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
	 * Every valid type=system slot key.
	 *
	 * @return string[]
	 */
	public static function system_keys() {
		return array(
			self::SYSTEM_NEWS,
			self::SYSTEM_EVENTS,
			self::SYSTEM_OFFICERS_INDEX,
			self::SYSTEM_TERMS_INDEX,
			self::SYSTEM_GRADUATION_LOOKUP,
		);
	}

	/**
	 * Human-readable labels for each system key, for the admin picker.
	 *
	 * @return array<string,string>
	 */
	public static function system_key_labels() {
		return array(
			self::SYSTEM_NEWS              => __( 'ニュース一覧', 'alumni-core' ),
			self::SYSTEM_EVENTS            => __( 'イベント一覧', 'alumni-core' ),
			self::SYSTEM_OFFICERS_INDEX    => __( '役員・理事紹介', 'alumni-core' ),
			self::SYSTEM_TERMS_INDEX       => __( '規約類一覧', 'alumni-core' ),
			self::SYSTEM_GRADUATION_LOOKUP => __( '卒業期早見表', 'alumni-core' ),
		);
	}

	/**
	 * The public URL a system key resolves to. Shared by both トップページ
	 * のシステムスロット (public/homepage-functions.php) and メニュー構成
	 * のシステム参照項目 (Menu_Structure) — a single source of truth for
	 * "system key -> URL" so the two don't drift.
	 *
	 * @param string $system_key
	 * @return string Empty string for an unrecognized key.
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
			default:
				return '';
		}
	}

	/**
	 * Every section, sorted by 表示順, with every slot normalized/
	 * revalidated (see class docblock). Triggers a one-time default-seed on
	 * the very first call, so a fresh install starts with a homepage that
	 * matches what earlier rounds already hardcoded into front-page.php
	 * (ニュース／イベント section, then 役員・理事紹介／卒業期早見表／
	 * 規約類 section) — no visual change for a site that never customizes
	 * this screen.
	 *
	 * @return array[]
	 */
	public function get_all() {
		if ( null === $this->sections ) {
			$saved = get_option( self::OPTION_NAME, null );

			if ( null === $saved ) {
				$sections = self::default_sections();
				update_option( self::OPTION_NAME, $sections );
			} else {
				$sections = is_array( $saved ) ? array_values( $saved ) : array();
			}

			$this->sections = self::normalize_sections( $sections );
		}

		return $this->sections;
	}

	/**
	 * The out-of-the-box section layout, matching the previously-hardcoded
	 * front-page.php sections exactly (see class docblock).
	 *
	 * @return array[]
	 */
	private static function default_sections() {
		return array(
			array(
				'section_id' => wp_generate_uuid4(),
				'order'      => 1,
				'heading'    => __( 'お知らせ・イベント', 'alumni-core' ),
				'columns'    => 2,
				'slots'      => array(
					array(
						'type'       => 'system',
						'system_key' => self::SYSTEM_NEWS,
					),
					array(
						'type'       => 'system',
						'system_key' => self::SYSTEM_EVENTS,
					),
				),
			),
			array(
				'section_id' => wp_generate_uuid4(),
				'order'      => 2,
				'heading'    => __( '同窓会情報', 'alumni-core' ),
				'columns'    => 3,
				'slots'      => array(
					array(
						'type'       => 'system',
						'system_key' => self::SYSTEM_OFFICERS_INDEX,
					),
					array(
						'type'       => 'system',
						'system_key' => self::SYSTEM_GRADUATION_LOOKUP,
					),
					array(
						'type'       => 'system',
						'system_key' => self::SYSTEM_TERMS_INDEX,
					),
				),
			),
		);
	}

	/**
	 * @param array[] $sections Raw sections.
	 * @return array[] Normalized, sorted by order.
	 */
	private static function normalize_sections( array $sections ) {
		$sections = array_map( array( __CLASS__, 'normalize_section' ), $sections );

		usort(
			$sections,
			function ( $a, $b ) {
				return $a['order'] <=> $b['order'];
			}
		);

		return $sections;
	}

	/**
	 * @param array $section Raw section.
	 * @return array Normalized section: valid section_id, int order,
	 *                string heading, columns clamped to
	 *                [MIN_COLUMNS,MAX_COLUMNS], and exactly $columns slots
	 *                (padded with type=none or truncated as needed).
	 */
	private static function normalize_section( $section ) {
		$section = is_array( $section ) ? $section : array();

		$columns = isset( $section['columns'] ) ? (int) $section['columns'] : self::MIN_COLUMNS;
		$columns = max( self::MIN_COLUMNS, min( self::MAX_COLUMNS, $columns ) );

		$slots = ( isset( $section['slots'] ) && is_array( $section['slots'] ) ) ? array_values( $section['slots'] ) : array();

		while ( count( $slots ) < $columns ) {
			$slots[] = array( 'type' => 'none' );
		}
		$slots = array_slice( $slots, 0, $columns );
		$slots = array_map( array( __CLASS__, 'normalize_slot' ), $slots );

		return array(
			'section_id' => ( ! empty( $section['section_id'] ) && is_string( $section['section_id'] ) ) ? $section['section_id'] : wp_generate_uuid4(),
			'order'      => isset( $section['order'] ) ? (int) $section['order'] : 0,
			'heading'    => isset( $section['heading'] ) ? (string) $section['heading'] : '',
			'columns'    => $columns,
			'slots'      => $slots,
		);
	}

	/**
	 * @param mixed $slot Raw slot.
	 * @return array Normalized slot: array('type'=>'none'), or
	 *                array('type'=>'content','content_id'=>int) when that
	 *                content still resolves to a published alumni_content
	 *                post, or array('type'=>'system','system_key'=>string)
	 *                when that key is still a recognized system candidate —
	 *                anything else safely degrades to type=none rather than
	 *                erroring (see class docblock: "非公開コンテンツを
	 *                安全に処理する").
	 */
	private static function normalize_slot( $slot ) {
		if ( ! is_array( $slot ) || ! isset( $slot['type'] ) ) {
			return array( 'type' => 'none' );
		}

		if ( 'content' === $slot['type'] ) {
			$content_id = isset( $slot['content_id'] ) ? absint( $slot['content_id'] ) : 0;

			return self::is_publishable_content( $content_id )
				? array(
					'type'       => 'content',
					'content_id' => $content_id,
				)
				: array( 'type' => 'none' );
		}

		if ( 'system' === $slot['type'] ) {
			$key = isset( $slot['system_key'] ) ? (string) $slot['system_key'] : '';

			return in_array( $key, self::system_keys(), true )
				? array(
					'type'       => 'system',
					'system_key' => $key,
				)
				: array( 'type' => 'none' );
		}

		return array( 'type' => 'none' );
	}

	/**
	 * @param int $content_id
	 * @return bool
	 */
	private static function is_publishable_content( $content_id ) {
		if ( ! $content_id ) {
			return false;
		}

		$post = get_post( $content_id );

		return $post && Content_Post_Type::SLUG === $post->post_type && 'publish' === $post->post_status;
	}

	/**
	 * Persists the full sections array (every mutator below funnels
	 * through this single write path).
	 *
	 * @param array[] $sections
	 * @return array[] The normalized, persisted sections.
	 */
	private function save_sections( array $sections ) {
		update_option( self::OPTION_NAME, $sections );

		$this->sections = self::normalize_sections( $sections );

		return $this->sections;
	}

	/**
	 * Appends a new, empty (1段・スロット未設定) section and returns its ID.
	 *
	 * @return string
	 */
	public function create_section() {
		$sections = $this->get_all();

		$sections[] = array(
			'section_id' => wp_generate_uuid4(),
			'order'      => count( $sections ) + 1,
			'heading'    => '',
			'columns'    => self::MIN_COLUMNS,
			'slots'      => array( array( 'type' => 'none' ) ),
		);

		$this->save_sections( $sections );

		return $sections[ count( $sections ) - 1 ]['section_id'];
	}

	/**
	 * Deletes a section entirely and renumbers the remaining sections'
	 * 表示順 to stay contiguous.
	 *
	 * @param string $section_id
	 */
	public function delete_section( $section_id ) {
		$sections = array_values(
			array_filter(
				$this->get_all(),
				function ( $section ) use ( $section_id ) {
					return $section['section_id'] !== $section_id;
				}
			)
		);

		foreach ( $sections as $index => &$section ) {
			$section['order'] = $index + 1;
		}
		unset( $section );

		$this->save_sections( $sections );
	}

	/**
	 * Updates a section's 見出し・段数. Changing 段数 pads/truncates its
	 * slots to match, preserving whatever was already assigned to the
	 * slots that remain.
	 *
	 * @param string $section_id
	 * @param string $heading Raw, sanitized here. May be empty (見出しなし
	 *                          は許容される).
	 * @param mixed  $columns Raw, clamped to [MIN_COLUMNS,MAX_COLUMNS].
	 * @return array|null The updated section, or null if $section_id
	 *                      doesn't exist.
	 */
	public function update_section_meta( $section_id, $heading, $columns ) {
		$sections = $this->get_all();
		$found    = null;
		$columns  = max( self::MIN_COLUMNS, min( self::MAX_COLUMNS, (int) $columns ) );

		foreach ( $sections as &$section ) {
			if ( $section['section_id'] !== $section_id ) {
				continue;
			}

			$section['heading'] = sanitize_text_field( $heading );
			$section['columns'] = $columns;

			$slots = $section['slots'];
			while ( count( $slots ) < $columns ) {
				$slots[] = array( 'type' => 'none' );
			}
			$section['slots'] = array_slice( $slots, 0, $columns );

			$found = $section;
		}
		unset( $section );

		$this->save_sections( $sections );

		return $found;
	}

	/**
	 * Sets one slot's content within a section.
	 *
	 * @param string $section_id
	 * @param int    $slot_index 0-based.
	 * @param array  $slot       Raw slot, e.g.
	 *                             array('type'=>'content','content_id'=>123)
	 *                             or array('type'=>'system','system_key'=>'news')
	 *                             or array('type'=>'none').
	 * @return array|null The updated section, or null if $section_id
	 *                      doesn't exist or $slot_index is out of range.
	 */
	public function set_slot( $section_id, $slot_index, array $slot ) {
		$sections   = $this->get_all();
		$found      = null;
		$slot_index = (int) $slot_index;

		foreach ( $sections as &$section ) {
			if ( $section['section_id'] !== $section_id ) {
				continue;
			}

			if ( $slot_index < 0 || $slot_index >= count( $section['slots'] ) ) {
				continue;
			}

			$section['slots'][ $slot_index ] = self::normalize_slot( $slot );
			$found                            = $section;
		}
		unset( $section );

		$this->save_sections( $sections );

		return $found;
	}

	/**
	 * Moves a section up or down by swapping 表示順 with its neighbor.
	 * A no-op at either end of the list.
	 *
	 * @param string $section_id
	 * @param string $direction 'up' or 'down'.
	 */
	public function move_section( $section_id, $direction ) {
		$sections = $this->get_all(); // Already sorted by order.
		$index    = null;

		foreach ( $sections as $i => $section ) {
			if ( $section['section_id'] === $section_id ) {
				$index = $i;
				break;
			}
		}

		if ( null === $index ) {
			return;
		}

		$swap_with = ( 'up' === $direction ) ? $index - 1 : $index + 1;

		if ( $swap_with < 0 || $swap_with >= count( $sections ) ) {
			return;
		}

		$tmp                        = $sections[ $index ]['order'];
		$sections[ $index ]['order'] = $sections[ $swap_with ]['order'];
		$sections[ $swap_with ]['order'] = $tmp;

		$this->save_sections( $sections );
	}
}
