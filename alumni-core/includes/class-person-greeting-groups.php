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

				// 旧実装では名称からUUIDを生成していたため、既存投稿・既存メニューが
				// 旧UUIDを参照していることがある。固定プリセット化では「表示名」だけ
				// ではなくIDも固定し、参照元を一度だけ正規IDへ移行する。
				if ( null !== $matched && $matched['group_id'] !== $preset_key ) {
					$this->migrate_group_references( $matched['group_id'], $preset_key );
				}

				$presets[] = array(
					'group_id' => $preset_key,
					'name'     => $spec['name'],
					'order'    => $spec['order'],
				);
			}

			$this->groups = $presets;

			if ( $stored !== $presets ) {
				update_option( self::OPTION_NAME, $presets );
			}
		}

		return $this->groups;
	}

	/**
	 * Migrates every persisted reference from a legacy person-greeting
	 * group ID to the fixed preset ID.
	 *
	 * This is intentionally ID-to-ID. Display names are never used as
	 * foreign keys, so future label changes cannot break memberships.
	 *
	 * @param string $old_id
	 * @param string $new_id
	 * @return void
	 */
	private function migrate_group_references( $old_id, $new_id ) {
		$old_id = (string) $old_id;
		$new_id = (string) $new_id;

		if ( '' === $old_id || '' === $new_id || $old_id === $new_id ) {
			return;
		}

		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE meta_key = %s AND meta_value = %s",
				$new_id,
				'_' . 'alumni_person_greeting_group_id',
				$old_id
			)
		);

		$menu_items = get_option( Menu_Structure::OPTION_NAME, null );
		if ( is_array( $menu_items ) ) {
			$changed = false;

			foreach ( $menu_items as &$item ) {
				if (
					is_array( $item )
					&& ( $item['ref_type'] ?? '' ) === Menu_Structure::REF_PERSON_GREETING_GROUP
					&& (string) ( $item['ref_id'] ?? '' ) === $old_id
				) {
					$item['ref_id'] = $new_id;
					$changed = true;
				}
			}
			unset( $item );

			if ( $changed ) {
				update_option( Menu_Structure::OPTION_NAME, $menu_items );
			}
		}

		$old_option = Person_Greeting_Groups_Shortcode::PAGE_ID_OPTION_PREFIX . $old_id;
		$new_option = Person_Greeting_Groups_Shortcode::PAGE_ID_OPTION_PREFIX . $new_id;
		$page_id    = (int) get_option( $old_option, 0 );

		if ( $page_id && 'page' === get_post_type( $page_id ) ) {
			update_option( $new_option, $page_id );
			delete_option( $old_option );

			$post = get_post( $page_id );
			if ( $post instanceof \WP_Post ) {
				$new_content = str_replace(
					'id="' . $old_id . '"',
					'id="' . $new_id . '"',
					(string) $post->post_content
				);

				if ( $new_content !== $post->post_content ) {
					wp_update_post(
						array(
							'ID'           => $page_id,
							'post_content' => $new_content,
						)
					);
				}
			}
		}
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
