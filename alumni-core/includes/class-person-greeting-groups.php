<?php
/**
 * 人物挨拶グループ（歴代の人物挨拶をまとめる専用の分類）を管理する.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 「母校校長挨拶」「同窓会長挨拶」のように、同種の人物挨拶（歴代の
 * 校長・会長など）を1つにまとめるための専用データ構造。
 *
 * これはサイトナビゲーションの階層を作るための汎用的な「親コンテンツ」
 * ではない — 人物挨拶（Modules\Content\Post_Type::KIND_PERSON_GREETING）
 * 専用の分類であり、自由コンテンツ・規約類には一切関与しない。個々の
 * 人物挨拶投稿（alumni_content、kind=person_greeting）が
 * META_PERSON_GREETING_GROUP_ID postmetaでどのグループに属するかを
 * 「参照するだけ」で、グループ自体は投稿を持たない軽量なラベルの集合
 * （node_id/parent_idを持たないOrg_Chartよりさらに単純な「名前のリスト」
 * だけの構造）。
 *
 * 公開ページ（歴代一覧＋個別挨拶ページへのリンク）は
 * Person_Greeting_Groups_Shortcode が担当する。Menu_Structure からは
 * REF_PERSON_GREETING_GROUP という専用のref_typeで参照する
 * （Officer_Listsの各一覧をREF_OFFICER_LISTで参照するのと同じ設計）。
 */
class Person_Greeting_Groups {

	/**
	 * The wp_options row name.
	 */
	const OPTION_NAME = 'alumni_core_person_greeting_groups';

	/**
	 * Singleton instance.
	 *
	 * @var Person_Greeting_Groups|null
	 */
	private static $instance = null;

	/**
	 * Cached groups array.
	 *
	 * @var array[]|null
	 */
	private $groups = null;

	/**
	 * @return Person_Greeting_Groups
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
	 * Every group, in display order.
	 *
	 * @return array[] Each: array('group_id'=>string,'name'=>string,'order'=>int).
	 */
	public function get_all() {
		if ( null === $this->groups ) {
			$saved        = get_option( self::OPTION_NAME, null );
			$groups       = is_array( $saved ) ? array_values( $saved ) : array();
			$this->groups = array_map( array( __CLASS__, 'normalize_group' ), $groups );

			usort(
				$this->groups,
				function ( $a, $b ) {
					return $a['order'] <=> $b['order'];
				}
			);
		}

		return $this->groups;
	}

	/**
	 * @param array $group
	 * @return array
	 */
	private static function normalize_group( $group ) {
		$group = is_array( $group ) ? $group : array();

		return array(
			'group_id' => ( ! empty( $group['group_id'] ) && is_string( $group['group_id'] ) ) ? $group['group_id'] : wp_generate_uuid4(),
			'name'     => isset( $group['name'] ) ? (string) $group['name'] : '',
			'order'    => isset( $group['order'] ) ? (int) $group['order'] : 0,
		);
	}

	/**
	 * @param string $group_id
	 * @return array|null
	 */
	public function get_group( $group_id ) {
		foreach ( $this->get_all() as $group ) {
			if ( $group['group_id'] === $group_id ) {
				return $group;
			}
		}

		return null;
	}

	/**
	 * Persists the full groups array.
	 *
	 * @param array[] $groups
	 * @return array[]
	 */
	private function save_groups( array $groups ) {
		update_option( self::OPTION_NAME, $groups );

		$this->groups = array_map( array( __CLASS__, 'normalize_group' ), $groups );
		usort(
			$this->groups,
			function ( $a, $b ) {
				return $a['order'] <=> $b['order'];
			}
		);

		return $this->groups;
	}

	/**
	 * Creates a new group and returns its ID. A group with the same name
	 * (trimmed, case-sensitive match) is never duplicated — returns the
	 * existing group's ID instead (used by the standard preset, which must
	 * be safe to re-run without creating duplicate "母校校長挨拶" groups).
	 *
	 * @param string $name Raw, sanitized here.
	 * @return string The (new or pre-existing) group's ID.
	 */
	public function create_group( $name ) {
		$name    = sanitize_text_field( $name );
		$groups  = $this->get_all();

		foreach ( $groups as $group ) {
			if ( $group['name'] === $name ) {
				return $group['group_id'];
			}
		}

		$new_group = self::normalize_group(
			array(
				'name'  => $name,
				'order' => count( $groups ) + 1,
			)
		);

		$groups[] = $new_group;
		$this->save_groups( $groups );

		return $new_group['group_id'];
	}
}
