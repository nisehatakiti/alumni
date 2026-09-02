<?php
/**
 * 同窓会組織図（親子関係＋表示順を持つツリー構造）を管理する.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 組織図は「会長 → 副会長 → 委員会」のような、組織そのものの構造を
 * 表すためだけのデータであり、Menu_Structure（サイトナビゲーションの
 * 構造）とは完全に分離している — 「卒業生向け → 同窓会情報 → 同窓会
 * 組織図」がサイト構造、こちらは組織構造そのもの、という別概念（この
 * クラスはMenu_Structureを一切参照しない）。
 *
 * 各ノードは node_id / parent_id / name / sort_order の4項目だけを持つ、
 * 意図的に最小限のデータモデル。将来、役員・理事データ（Officer_Lists）
 * とノードを関連付ける拡張の余地は残すが、今回の実装ではその自動連携は
 * 行わない（現時点ではノードは単なる名前の文字列を持つだけ）。
 *
 * データの持ち方・操作パターンはMenu_Structureと同じ「フラットな配列を
 * 1つのwp_optionsに保存し、都度parent_idからツリーを組み立てる」方式を
 * そのまま踏襲している。
 */
class Org_Chart {

	/**
	 * The wp_options row name.
	 */
	const OPTION_NAME = 'alumni_core_org_chart';

	/**
	 * Guards get_tree()/get_descendant_ids() against a corrupted parent_id
	 * chain turning into an infinite loop.
	 */
	const MAX_DEPTH = 20;

	/**
	 * Singleton instance.
	 *
	 * @var Org_Chart|null
	 */
	private static $instance = null;

	/**
	 * Cached, normalized nodes array.
	 *
	 * @var array[]|null
	 */
	private $nodes = null;

	/**
	 * @return Org_Chart
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
	 * Every node, flat (not yet resolved to a tree).
	 *
	 * @return array[]
	 */
	public function get_all() {
		if ( null === $this->nodes ) {
			$saved       = get_option( self::OPTION_NAME, null );
			$nodes       = is_array( $saved ) ? array_values( $saved ) : array();
			$this->nodes = array_map( array( __CLASS__, 'normalize_node' ), $nodes );
		}

		return $this->nodes;
	}

	/**
	 * @param array $node
	 * @return array
	 */
	private static function normalize_node( $node ) {
		$node = is_array( $node ) ? $node : array();

		return array(
			'node_id'    => ( ! empty( $node['node_id'] ) && is_string( $node['node_id'] ) ) ? $node['node_id'] : wp_generate_uuid4(),
			'parent_id'  => isset( $node['parent_id'] ) ? (string) $node['parent_id'] : '',
			'name'       => isset( $node['name'] ) ? (string) $node['name'] : '',
			'sort_order' => isset( $node['sort_order'] ) ? (int) $node['sort_order'] : 0,
		);
	}

	/**
	 * Persists the full nodes array (every mutator below funnels through
	 * this single write path).
	 *
	 * @param array[] $nodes
	 * @return array[]
	 */
	private function save_nodes( array $nodes ) {
		update_option( self::OPTION_NAME, $nodes );

		$this->nodes = array_map( array( __CLASS__, 'normalize_node' ), $nodes );

		return $this->nodes;
	}

	/**
	 * @param string $node_id
	 * @return array|null
	 */
	public function get_node( $node_id ) {
		foreach ( $this->get_all() as $node ) {
			if ( $node['node_id'] === $node_id ) {
				return $node;
			}
		}

		return null;
	}

	/**
	 * Direct children of $parent_id, sorted by sort_order.
	 *
	 * @param string $parent_id '' for root nodes.
	 * @return array[]
	 */
	public function get_children( $parent_id ) {
		$children = array();

		foreach ( $this->get_all() as $node ) {
			if ( $node['parent_id'] === $parent_id ) {
				$children[] = $node;
			}
		}

		usort(
			$children,
			function ( $a, $b ) {
				return $a['sort_order'] <=> $b['sort_order'];
			}
		);

		return $children;
	}

	/**
	 * Every descendant node_id of $node_id (any depth) — used to guard
	 * against creating a cycle when reparenting, and to cascade-delete a
	 * node's subtree.
	 *
	 * @param string $node_id
	 * @return string[]
	 */
	public function get_descendant_ids( $node_id ) {
		$children_map = array();
		foreach ( $this->get_all() as $node ) {
			$children_map[ $node['parent_id'] ][] = $node['node_id'];
		}

		$result = array();
		$seen   = array();
		$queue  = isset( $children_map[ $node_id ] ) ? $children_map[ $node_id ] : array();

		while ( ! empty( $queue ) ) {
			$id = array_shift( $queue );
			if ( isset( $seen[ $id ] ) ) {
				continue;
			}
			$seen[ $id ] = true;
			$result[]    = $id;
			if ( isset( $children_map[ $id ] ) ) {
				foreach ( $children_map[ $id ] as $child_id ) {
					$queue[] = $child_id;
				}
			}
		}

		return $result;
	}

	/**
	 * The full tree, root-first — an empty array when no nodes exist yet
	 * (公開側は「まだ組織図が登録されていません」等、空でもエラーに
	 * ならない前提で扱う).
	 *
	 * @return array[] Each: array('node_id'=>string,'name'=>string,'children'=>(same shape)).
	 */
	public function get_tree() {
		return $this->build_subtree( '', 0 );
	}

	/**
	 * @param string $parent_id
	 * @param int    $depth
	 * @return array[]
	 */
	private function build_subtree( $parent_id, $depth ) {
		if ( $depth > self::MAX_DEPTH ) {
			return array();
		}

		$nodes = array();

		foreach ( $this->get_children( $parent_id ) as $node ) {
			$nodes[] = array(
				'node_id'  => $node['node_id'],
				'name'     => $node['name'],
				'children' => $this->build_subtree( $node['node_id'], $depth + 1 ),
			);
		}

		return $nodes;
	}

	/**
	 * Creates a new node.
	 *
	 * @param string $parent_id '' for a root node.
	 * @param string $name      Raw, sanitized here.
	 * @return string The new node's ID.
	 */
	public function create_node( $parent_id, $name ) {
		if ( '' !== $parent_id && null === $this->get_node( $parent_id ) ) {
			$parent_id = '';
		}

		$nodes    = $this->get_all();
		$siblings = $this->get_children( $parent_id );

		$nodes[] = self::normalize_node(
			array(
				'parent_id'  => $parent_id,
				'name'       => sanitize_text_field( $name ),
				'sort_order' => count( $siblings ) + 1,
			)
		);

		$this->save_nodes( $nodes );

		return $nodes[ count( $nodes ) - 1 ]['node_id'];
	}

	/**
	 * Updates a node's name.
	 *
	 * @param string $node_id
	 * @param string $name Raw, sanitized here.
	 * @return array|null The updated node, or null if $node_id doesn't exist.
	 */
	public function update_node( $node_id, $name ) {
		$nodes = $this->get_all();
		$found = null;

		foreach ( $nodes as &$node ) {
			if ( $node['node_id'] !== $node_id ) {
				continue;
			}

			$node['name'] = sanitize_text_field( $name );
			$found        = $node;
		}
		unset( $node );

		$this->save_nodes( $nodes );

		return $found;
	}

	/**
	 * Deletes a node and its entire subtree (matching Menu_Structure's
	 * delete_item() policy — a subtree has no meaning without its parent in
	 * this structure).
	 *
	 * @param string $node_id
	 */
	public function delete_node( $node_id ) {
		$to_delete     = array_merge( array( $node_id ), $this->get_descendant_ids( $node_id ) );
		$to_delete_map = array_flip( $to_delete );

		$nodes = array_values(
			array_filter(
				$this->get_all(),
				function ( $node ) use ( $to_delete_map ) {
					return ! isset( $to_delete_map[ $node['node_id'] ] );
				}
			)
		);

		$this->save_nodes( $nodes );
	}

	/**
	 * Moves a node up or down among its siblings, by swapping sort_order. A
	 * no-op at either end.
	 *
	 * @param string $node_id
	 * @param string $direction 'up' or 'down'.
	 */
	public function move_node( $node_id, $direction ) {
		$current = $this->get_node( $node_id );

		if ( null === $current ) {
			return;
		}

		$siblings = $this->get_children( $current['parent_id'] ); // Sorted by sort_order.
		$index    = null;

		foreach ( $siblings as $i => $sibling ) {
			if ( $sibling['node_id'] === $node_id ) {
				$index = $i;
				break;
			}
		}

		if ( null === $index ) {
			return;
		}

		$swap_with = ( 'up' === $direction ) ? $index - 1 : $index + 1;

		if ( $swap_with < 0 || $swap_with >= count( $siblings ) ) {
			return;
		}

		$this->swap_sort_orders( $siblings[ $index ]['node_id'], $siblings[ $swap_with ]['node_id'] );
	}

	/**
	 * @param string $node_id_a
	 * @param string $node_id_b
	 */
	private function swap_sort_orders( $node_id_a, $node_id_b ) {
		$nodes = $this->get_all();
		$a     = null;
		$b     = null;

		foreach ( $nodes as &$node ) {
			if ( $node['node_id'] === $node_id_a ) {
				$a = &$node;
			} elseif ( $node['node_id'] === $node_id_b ) {
				$b = &$node;
			}
		}

		if ( null !== $a && null !== $b ) {
			$tmp              = $a['sort_order'];
			$a['sort_order'] = $b['sort_order'];
			$b['sort_order'] = $tmp;
		}
		unset( $a, $b, $node );

		$this->save_nodes( $nodes );
	}

	/**
	 * Reparents $node_id under $new_parent_id, appended to the end of that
	 * new sibling group. Refuses (no-op) a move that would create a cycle
	 * (making a node its own descendant's child) or target a nonexistent
	 * parent (other than '', top-level).
	 *
	 * @param string $node_id
	 * @param string $new_parent_id '' to move to the top level.
	 */
	public function set_parent( $node_id, $new_parent_id ) {
		if ( $node_id === $new_parent_id ) {
			return;
		}

		if ( '' !== $new_parent_id && null === $this->get_node( $new_parent_id ) ) {
			return;
		}

		if ( in_array( $new_parent_id, $this->get_descendant_ids( $node_id ), true ) ) {
			return; // Would create a cycle.
		}

		$nodes     = $this->get_all();
		$new_order = count( $this->get_children( $new_parent_id ) ) + 1;

		foreach ( $nodes as &$node ) {
			if ( $node['node_id'] !== $node_id ) {
				continue;
			}

			$node['parent_id']  = $new_parent_id;
			$node['sort_order'] = $new_order;
		}
		unset( $node );

		$this->save_nodes( $nodes );
	}
}
