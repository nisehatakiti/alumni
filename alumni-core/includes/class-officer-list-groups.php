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
}
