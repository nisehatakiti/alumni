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
 * 「現在の校長」「現在の同窓会長」「歴代校長」「歴代同窓会長」のように、
 * 人物挨拶を固定プリセットへ分類するための専用データ構造。
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
	 * 固定プリセット。人物挨拶グループは管理者が追加・編集・削除できない。
	 * IDは表示名から独立した不変キーなので、表示名の将来的な文言調整でも
	 * 投稿・メニューとの紐付けは切れない。
	 */
	const PRESET_CURRENT_CHAIRMAN  = 'current_chairman';
	const PRESET_CURRENT_PRINCIPAL = 'current_principal';
	const PRESET_CHAIRMEN          = 'chairmen';
	const PRESET_PRINCIPALS        = 'principals';

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
			$saved  = get_option( self::OPTION_NAME, null );
			$stored = is_array( $saved ) ? array_values( $saved ) : array();

			// 既存サイトの「歴代校長／歴代会長」系グループは、保存済みの
			// group_id をそのまま引き継ぐ。投稿・メニューはIDで参照している
			// ため、ここで新しいIDに置き換えないことが重要。
			$specs = array(
				self::PRESET_CURRENT_CHAIRMAN => array(
					'name'    => '現在の同窓会長',
					'order'   => 1,
					'aliases' => array( '現在の同窓会長', '同窓会長挨拶' ),
				),
				self::PRESET_CURRENT_PRINCIPAL => array(
					'name'    => '現在の校長',
					'order'   => 2,
					'aliases' => array( '現在の校長', '母校校長挨拶', '校長挨拶' ),
				),
				self::PRESET_CHAIRMEN => array(
					'name'    => '歴代同窓会長',
					'order'   => 3,
					'aliases' => array( '歴代同窓会長', '歴代会長' ),
				),
				self::PRESET_PRINCIPALS => array(
					'name'    => '歴代校長',
					'order'   => 4,
					'aliases' => array( '歴代校長' ),
				),
			);

			$presets = array();

			foreach ( $specs as $preset_key => $spec ) {
				$matched = null;

				foreach ( $stored as $raw_group ) {
					$raw_group = self::normalize_group( $raw_group );

					if ( $raw_group['group_id'] === $preset_key || in_array( $raw_group['name'], $spec['aliases'], true ) ) {
						$matched = $raw_group;
						break;
					}
				}

				if ( null === $matched ) {
					$matched = array(
						'group_id' => $preset_key,
						'name'     => $spec['name'],
						'order'    => $spec['order'],
					);
				} else {
					$matched['name']  = $spec['name'];
					$matched['order'] = $spec['order'];
				}

				$presets[] = self::normalize_group( $matched );
			}

			$this->groups = $presets;

			// 自由に作られた旧グループは保存値を破壊せず、固定プリセットへ
			// 正規化した結果だけを以後の人物挨拶グループ設定として保持する。
			if ( $stored !== $presets ) {
				update_option( self::OPTION_NAME, $presets );
			}
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
	 * Returns whether an ID is one of the fixed person-greeting presets.
	 *
	 * @param string $group_id
	 * @return bool
	 */
	public function is_preset( $group_id ) {
		return null !== $this->get_group( $group_id );
	}

}
