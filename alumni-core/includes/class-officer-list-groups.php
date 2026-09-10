<?php
/**
 * 役員・理事一覧グループを管理する.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 複数の役員・理事一覧を1つの公開ページにまとめるための軽量なグループ。
 * 一覧自体は従来どおり個別に編集でき、group_id が同じ一覧だけを同じ
 * グループページに連続表示する。
 */
class Officer_List_Groups {

	const OPTION_NAME = 'alumni_core_officer_list_groups';

	private static $instance = null;
	private $groups = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function get_all() {
		if ( null === $this->groups ) {
			$saved = get_option( self::OPTION_NAME, array() );
			$this->groups = array_map( array( __CLASS__, 'normalize_group' ), is_array( $saved ) ? array_values( $saved ) : array() );
			usort( $this->groups, function( $a, $b ) { return $a['order'] <=> $b['order']; } );
		}
		return $this->groups;
	}

	private static function normalize_group( $group ) {
		$group = is_array( $group ) ? $group : array();
		return array(
			'group_id' => ! empty( $group['group_id'] ) ? (string) $group['group_id'] : wp_generate_uuid4(),
			'name'     => isset( $group['name'] ) ? sanitize_text_field( $group['name'] ) : '',
			'order'    => isset( $group['order'] ) ? (int) $group['order'] : 0,
		);
	}

	public function get_group( $group_id ) {
		foreach ( $this->get_all() as $group ) {
			if ( $group['group_id'] === $group_id ) {
				return $group;
			}
		}
		return null;
	}

	private function save_groups( array $groups ) {
		update_option( self::OPTION_NAME, $groups );
		$this->groups = array_map( array( __CLASS__, 'normalize_group' ), $groups );
		usort( $this->groups, function( $a, $b ) { return $a['order'] <=> $b['order']; } );
		return $this->groups;
	}

	public function create_group( $name ) {
		$name = sanitize_text_field( $name );
		foreach ( $this->get_all() as $group ) {
			if ( $group['name'] === $name ) {
				return $group['group_id'];
			}
		}
		$groups = $this->get_all();
		$new = self::normalize_group( array( 'name' => $name, 'order' => count( $groups ) + 1 ) );
		$groups[] = $new;
		$this->save_groups( $groups );
		return $new['group_id'];
	}

	/**
	 * Renames a freely configurable officer-list group.
	 *
	 * @param string $group_id
	 * @param string $name
	 * @return array|null
	 */
	public function update_group( $group_id, $name ) {
		$group_id = sanitize_text_field( $group_id );
		$name     = sanitize_text_field( $name );
		if ( '' === $group_id || '' === $name ) {
			return null;
		}

		$groups = $this->get_all();
		$found  = null;
		foreach ( $groups as &$group ) {
			if ( $group['group_id'] !== $group_id ) {
				continue;
			}
			$group['name'] = $name;
			$found = $group;
			break;
		}
		unset( $group );

		if ( null !== $found ) {
			$this->save_groups( $groups );
		}

		return $found;
	}

	/**
	 * Deletes a group. Member lists are detached and remain as standalone
	 * lists, so deleting a grouping never deletes officer data.
	 *
	 * @param string $group_id
	 * @return bool
	 */
	public function delete_group( $group_id ) {
		$group_id = sanitize_text_field( $group_id );
		if ( '' === $group_id || null === $this->get_group( $group_id ) ) {
			return false;
		}

		$groups = array_values(
			array_filter(
				$this->get_all(),
				function ( $group ) use ( $group_id ) {
					return $group['group_id'] !== $group_id;
				}
			)
		);

		$this->save_groups( $groups );

		// Lists survive group deletion and simply become standalone pages.
		$lists = Officer_Lists::instance()->get_all();
		foreach ( $lists as $list ) {
			if ( (string) $list['group_id'] === $group_id ) {
				Officer_Lists::instance()->save_list_group( $list['list_id'], '' );
			}
		}

		return true;
	}

}
