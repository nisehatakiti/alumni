<?php
/**
 * 同窓会組織図（複数の組織図＋親子関係＋表示順）を管理する.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Org_Chart {

	const OPTION_NAME = 'alumni_core_org_chart'; // legacy single-chart option.
	const OPTION_CHARTS = 'alumni_core_org_charts';
	const MAX_DEPTH = 20;

	private static $instance = null;
	private $charts = null;
	private $active_chart_id = '';

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	private static function normalize_node( $node ) {
		$node = is_array( $node ) ? $node : array();
		return array(
			'node_id'    => ( ! empty( $node['node_id'] ) && is_string( $node['node_id'] ) ) ? $node['node_id'] : wp_generate_uuid4(),
			'parent_id'  => isset( $node['parent_id'] ) ? (string) $node['parent_id'] : '',
			'name'       => isset( $node['name'] ) ? sanitize_text_field( $node['name'] ) : '',
			'sort_order' => isset( $node['sort_order'] ) ? (int) $node['sort_order'] : 0,
		);
	}

	private static function normalize_chart( $chart ) {
		$chart = is_array( $chart ) ? $chart : array();
		$nodes = isset( $chart['nodes'] ) && is_array( $chart['nodes'] ) ? array_values( $chart['nodes'] ) : array();
		return array(
			'chart_id'   => ! empty( $chart['chart_id'] ) ? (string) $chart['chart_id'] : wp_generate_uuid4(),
			'name'       => isset( $chart['name'] ) ? sanitize_text_field( $chart['name'] ) : '',
			'group_id'   => isset( $chart['group_id'] ) ? (string) $chart['group_id'] : '',
			'sort_order' => isset( $chart['sort_order'] ) ? (int) $chart['sort_order'] : 0,
			'nodes'      => array_map( array( __CLASS__, 'normalize_node' ), $nodes ),
		);
	}

	/**
	 * Returns all charts. On first upgrade, the legacy single chart is migrated
	 * without deleting the legacy option, so existing sites remain reversible.
	 */
	public function get_charts() {
		if ( null === $this->charts ) {
			$saved = get_option( self::OPTION_CHARTS, null );
			if ( null === $saved ) {
				$legacy = get_option( self::OPTION_NAME, array() );
				$charts = array();
				if ( is_array( $legacy ) && ! empty( $legacy ) ) {
					$charts[] = self::normalize_chart( array(
						'name'       => __( '組織図', 'alumni-core' ),
						'group_id'   => '',
						'sort_order' => 1,
						'nodes'      => $legacy,
					) );
				}
				update_option( self::OPTION_CHARTS, $charts );
				$this->charts = $charts;
			} else {
				$charts = is_array( $saved ) ? array_values( $saved ) : array();
				$this->charts = array_map( array( __CLASS__, 'normalize_chart' ), $charts );
			}
			usort( $this->charts, function ( $a, $b ) { return $a['sort_order'] <=> $b['sort_order']; } );
		}
		return $this->charts;
	}

	private function save_charts( array $charts ) {
		update_option( self::OPTION_CHARTS, array_values( $charts ) );
		$this->charts = null;
		return $this->get_charts();
	}

	public function get_chart( $chart_id ) {
		foreach ( $this->get_charts() as $chart ) {
			if ( $chart['chart_id'] === (string) $chart_id ) {
				return $chart;
			}
		}
		return null;
	}

	public function create_chart( $name, $group_id = '' ) {
		$charts = $this->get_charts();
		$new = self::normalize_chart( array(
			'name'       => $name,
			'group_id'   => $group_id,
			'sort_order' => count( $charts ) + 1,
			'nodes'      => array(),
		) );
		$charts[] = $new;
		$this->save_charts( $charts );
		return $new['chart_id'];
	}

	public function update_chart( $chart_id, $name, $group_id = '' ) {
		$charts = $this->get_charts();
		foreach ( $charts as &$chart ) {
			if ( $chart['chart_id'] === (string) $chart_id ) {
				$chart['name'] = sanitize_text_field( $name );
				$chart['group_id'] = (string) $group_id;
				$updated = $chart;
				unset( $chart );
				$this->save_charts( $charts );
				return $updated;
			}
		}
		unset( $chart );
		return null;
	}

	public function delete_chart( $chart_id ) {
		$chart_id = (string) $chart_id;
		$charts = array_values( array_filter( $this->get_charts(), function ( $chart ) use ( $chart_id ) {
			return $chart['chart_id'] !== $chart_id;
		} ) );
		$this->save_charts( $charts );
		if ( $this->active_chart_id === $chart_id ) {
			$this->active_chart_id = '';
		}
	}

	public function detach_group( $group_id ) {
		$charts = $this->get_charts();
		$changed = false;
		foreach ( $charts as &$chart ) {
			if ( $chart['group_id'] === (string) $group_id ) {
				$chart['group_id'] = '';
				$changed = true;
			}
		}
		unset( $chart );
		if ( $changed ) {
			$this->save_charts( $charts );
		}
	}

	public function get_group_charts( $group_id ) {
		$charts = array();
		foreach ( $this->get_charts() as $chart ) {
			if ( $chart['group_id'] === (string) $group_id ) {
				$charts[] = $chart;
			}
		}
		return $charts;
	}

	public function save_group_order( $group_id, array $chart_ids ) {
		$charts = $this->get_charts();
		$order = 1;
		foreach ( $chart_ids as $chart_id ) {
			foreach ( $charts as &$chart ) {
				if ( $chart['chart_id'] === (string) $chart_id && $chart['group_id'] === (string) $group_id ) {
					$chart['sort_order'] = $order++;
					break;
				}
			}
			unset( $chart );
		}
		$this->save_charts( $charts );
	}

	/**
	 * Selects the chart used by the existing node API for the current request.
	 */
	public function set_active_chart( $chart_id ) {
		$this->active_chart_id = null !== $this->get_chart( $chart_id ) ? (string) $chart_id : '';
	}

	public function get_active_chart_id() {
		if ( '' !== $this->active_chart_id ) {
			return $this->active_chart_id;
		}
		$charts = $this->get_charts();
		return ! empty( $charts ) ? $charts[0]['chart_id'] : '';
	}

	private function active_chart() {
		$id = $this->get_active_chart_id();
		return '' !== $id ? $this->get_chart( $id ) : null;
	}

	public function get_all() {
		$chart = $this->active_chart();
		return $chart ? $chart['nodes'] : array();
	}

	private function save_nodes( array $nodes ) {
		$chart_id = $this->get_active_chart_id();
		if ( '' === $chart_id ) {
			return array();
		}
		$charts = $this->get_charts();
		foreach ( $charts as &$chart ) {
			if ( $chart['chart_id'] === $chart_id ) {
				$chart['nodes'] = array_map( array( __CLASS__, 'normalize_node' ), $nodes );
			}
		}
		unset( $chart );
		$this->save_charts( $charts );
		return $this->get_all();
	}

	public function get_node( $node_id ) {
		foreach ( $this->get_all() as $node ) {
			if ( $node['node_id'] === (string) $node_id ) {
				return $node;
			}
		}
		return null;
	}

	public function get_children( $parent_id ) {
		$children = array();
		foreach ( $this->get_all() as $node ) {
			if ( $node['parent_id'] === (string) $parent_id ) {
				$children[] = $node;
			}
		}
		usort( $children, function ( $a, $b ) { return $a['sort_order'] <=> $b['sort_order']; } );
		return $children;
	}

	public function get_descendant_ids( $node_id ) {
		$map = array();
		foreach ( $this->get_all() as $node ) {
			$map[ $node['parent_id'] ][] = $node['node_id'];
		}
		$result = array(); $seen = array();
		$queue = isset( $map[ $node_id ] ) ? $map[ $node_id ] : array();
		while ( ! empty( $queue ) ) {
			$id = array_shift( $queue );
			if ( isset( $seen[ $id ] ) ) { continue; }
			$seen[ $id ] = true; $result[] = $id;
			if ( isset( $map[ $id ] ) ) { foreach ( $map[ $id ] as $child ) { $queue[] = $child; } }
		}
		return $result;
	}

	public function get_tree() {
		return $this->build_subtree( '', 0 );
	}

	public function get_tree_for_chart( $chart_id ) {
		$previous = $this->active_chart_id;
		$this->set_active_chart( $chart_id );
		$tree = $this->get_tree();
		$this->active_chart_id = $previous;
		return $tree;
	}

	private function build_subtree( $parent_id, $depth ) {
		if ( $depth > self::MAX_DEPTH ) { return array(); }
		$nodes = array();
		foreach ( $this->get_children( $parent_id ) as $node ) {
			$nodes[] = array(
				'node_id' => $node['node_id'],
				'name' => $node['name'],
				'children' => $this->build_subtree( $node['node_id'], $depth + 1 ),
			);
		}
		return $nodes;
	}

	public function create_node( $parent_id, $name ) {
		if ( '' !== $parent_id && null === $this->get_node( $parent_id ) ) { $parent_id = ''; }
		$nodes = $this->get_all();
		$nodes[] = self::normalize_node( array(
			'parent_id' => $parent_id,
			'name' => $name,
			'sort_order' => count( $this->get_children( $parent_id ) ) + 1,
		) );
		$this->save_nodes( $nodes );
		return $nodes[ count( $nodes ) - 1 ]['node_id'];
	}

	public function update_node( $node_id, $name ) {
		$nodes = $this->get_all(); $found = null;
		foreach ( $nodes as &$node ) {
			if ( $node['node_id'] === (string) $node_id ) { $node['name'] = sanitize_text_field( $name ); $found = $node; }
		}
		unset( $node ); $this->save_nodes( $nodes ); return $found;
	}

	public function delete_node( $node_id ) {
		$delete = array_flip( array_merge( array( (string) $node_id ), $this->get_descendant_ids( $node_id ) ) );
		$this->save_nodes( array_values( array_filter( $this->get_all(), function ( $node ) use ( $delete ) { return ! isset( $delete[ $node['node_id'] ] ); } ) ) );
	}

	public function move_node( $node_id, $direction ) {
		$current = $this->get_node( $node_id ); if ( null === $current ) { return; }
		$siblings = $this->get_children( $current['parent_id'] ); $index = null;
		foreach ( $siblings as $i => $sibling ) { if ( $sibling['node_id'] === (string) $node_id ) { $index = $i; break; } }
		$swap = 'up' === $direction ? $index - 1 : $index + 1;
		if ( null === $index || $swap < 0 || $swap >= count( $siblings ) ) { return; }
		$nodes = $this->get_all();
		$a = $siblings[$index]['node_id'];
		$b = $siblings[$swap]['node_id'];
		$order_a = $siblings[$index]['sort_order'];
		$order_b = $siblings[$swap]['sort_order'];
		foreach ( $nodes as &$node ) {
			if ( $node['node_id'] === $a ) { $node['sort_order'] = $order_b; }
			elseif ( $node['node_id'] === $b ) { $node['sort_order'] = $order_a; }
		}
		unset( $node );
		$this->save_nodes( $nodes );
	}

	public function set_parent( $node_id, $new_parent_id ) {
		if ( (string) $node_id === (string) $new_parent_id ) { return; }
		if ( '' !== $new_parent_id && null === $this->get_node( $new_parent_id ) ) { return; }
		if ( in_array( (string) $new_parent_id, $this->get_descendant_ids( $node_id ), true ) ) { return; }
		$nodes = $this->get_all(); $new_order = count( $this->get_children( $new_parent_id ) ) + 1;
		foreach ( $nodes as &$node ) {
			if ( $node['node_id'] === (string) $node_id ) { $node['parent_id'] = (string) $new_parent_id; $node['sort_order'] = $new_order; }
		}
		unset( $node ); $this->save_nodes( $nodes );
	}
}
