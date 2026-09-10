<?php
/**
 * 同窓会組織図グループを管理する.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Org_Chart_Groups {

	const OPTION_NAME = 'alumni_core_org_chart_groups';

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
			usort( $this->groups, function ( $a, $b ) { return $a['order'] <=> $b['order']; } );
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
			if ( $group['group_id'] === (string) $group_id ) {
				return $group;
			}
		}
		return null;
	}

	private function save_groups( array $groups ) {
		update_option( self::OPTION_NAME, array_values( $groups ) );
		$this->groups = null;
		return $this->get_all();
	}

	public function create_group( $name ) {
		$name = sanitize_text_field( $name );
		if ( '' === $name ) {
			return '';
		}
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

	public function update_group( $group_id, $name ) {
		$group_id = sanitize_text_field( $group_id );
		$name = sanitize_text_field( $name );
		if ( '' === $group_id || '' === $name ) {
			return null;
		}
		$groups = $this->get_all();
		foreach ( $groups as &$group ) {
			if ( $group['group_id'] === $group_id ) {
				$group['name'] = $name;
				$updated = $group;
				unset( $group );
				$this->save_groups( $groups );
				return $updated;
			}
		}
		unset( $group );
		return null;
	}

	public function delete_group( $group_id ) {
		$group_id = sanitize_text_field( $group_id );
		if ( '' === $group_id || null === $this->get_group( $group_id ) ) {
			return false;
		}
		$groups = array_values( array_filter( $this->get_all(), function ( $group ) use ( $group_id ) {
			return $group['group_id'] !== $group_id;
		} ) );
		$this->save_groups( $groups );
		Org_Chart::instance()->detach_group( $group_id );
		return true;
	}
}
