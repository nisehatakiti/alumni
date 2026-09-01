<?php
/**
 * メニュー構成（サイトナビゲーションの正規の構造）を管理する.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes;

use AlumniCore\Includes\Modules\Content\Post_Type as Content_Post_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 従来の「コンテンツを作成し、その親子関係からメニューが決まる」
 * （Content_Hierarchy）という考え方から、「メニュー構成をまず設計し、
 * その中にコンテンツを配置する」という考え方への移行を担うクラス。
 *
 * Content_Hierarchy（コンテンツ自身の対象者・親子関係）は既存コンテンツ
 * との互換性のために引き続き機能する（削除しない）が、このクラスの
 * データが以後の「正規のサイト構造」になる — サイトナビゲーション
 * （site-navigation.php）とパンくずは、以後このクラスの構造を基準に
 * 生成される。
 *
 * メニュー項目（item）は2種類:
 *   - type=folder:   本文を持たない、階層をまとめるためだけの項目。
 *                     必ずしも公開コンテンツを持たない
 *                     （「フォルダは必ずしもコンテンツではない」）。
 *   - type=content:   何かへのリンク。参照先(ref_type)は3種類:
 *       - 'content':      alumni_content投稿（自由コンテンツ／人物挨拶／
 *                          規約類／フォルダ種別のコンテンツ）。
 *       - 'system':       Homepage_Sectionsと共通の「システムページ」
 *                          （ニュース一覧・イベント一覧・役員・理事紹介
 *                          インデックス・規約類一覧・卒業期早見表）。
 *       - 'officer_list': 役員・理事一覧の特定の1つ（Officer_Lists）。
 *                          複数一覧のうち特定の一覧だけをメニューへ個別に
 *                          配置できるようにするため。
 *     ref_type/ref_idという汎用フィールドにしているのは、将来
 *     「外部URL」「固定ページ」等を増やす際に新しい定数を1つ増やすだけで
 *     済むようにするため（既存フィールド構造を壊さない）。
 *
 * 「同一コンテンツの複数配置」（1つのcontent_idを複数のメニュー項目が
 * 参照すること）は、item自体がcontent_idを「参照するだけ」で、
 * コンテンツ側にメニュー項目へのポインタを持たせていないため、
 * 特別な対応なしに既に可能。
 *
 * 「対象者」はトップレベル項目（parent_id===''）にだけ意味を持つ
 * （Content_Hierarchyと同じ設計 — 一度対象者の枝に入ったら、配下の項目の
 * 対象者は無視してそのままその枝の一部として扱う）。
 */
class Menu_Structure {

	/**
	 * The wp_options row name.
	 */
	const OPTION_NAME = 'alumni_core_menu_structure';

	/**
	 * Guards render_tree()/get_descendant_ids() against a corrupted
	 * parent_id chain turning into an infinite loop.
	 */
	const MAX_DEPTH = 20;

	const TYPE_FOLDER  = 'folder';
	const TYPE_CONTENT = 'content';

	const REF_CONTENT      = 'content';
	const REF_SYSTEM       = 'system';
	const REF_OFFICER_LIST = 'officer_list';

	const AUDIENCE_ALUMNI  = 'alumni';
	const AUDIENCE_STUDENT = 'student';
	const AUDIENCE_COMMON  = 'common';

	/**
	 * Singleton instance.
	 *
	 * @var Menu_Structure|null
	 */
	private static $instance = null;

	/**
	 * Cached, normalized items array.
	 *
	 * @var array[]|null
	 */
	private $items = null;

	/**
	 * @return Menu_Structure
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
	 * Every menu item, flat (not yet resolved to a tree), including
	 * disabled ones. Triggers a one-time migration from Content_Hierarchy
	 * on the very first call if the option has never been saved — so an
	 * upgraded site's existing コンテンツ階層 becomes the starting メニュー
	 * 構成 automatically, with the same visible navigation as before
	 * (migration only runs once; idempotent by construction, since it only
	 * fires when OPTION_NAME has literally never been saved).
	 *
	 * @return array[]
	 */
	public function get_all() {
		if ( null === $this->items ) {
			$saved = get_option( self::OPTION_NAME, null );

			if ( null === $saved ) {
				$items = self::migrate_from_content_hierarchy();
				update_option( self::OPTION_NAME, $items );
			} else {
				$items = is_array( $saved ) ? array_values( $saved ) : array();
			}

			$this->items = array_map( array( __CLASS__, 'normalize_item' ), $items );
		}

		return $this->items;
	}

	/**
	 * @param array $item
	 * @return array
	 */
	private static function normalize_item( $item ) {
		$item = is_array( $item ) ? $item : array();

		$type = ( self::TYPE_FOLDER === ( $item['type'] ?? '' ) ) ? self::TYPE_FOLDER : self::TYPE_CONTENT;

		$ref_type = isset( $item['ref_type'] ) ? $item['ref_type'] : '';
		if ( ! in_array( $ref_type, array( self::REF_CONTENT, self::REF_SYSTEM, self::REF_OFFICER_LIST ), true ) ) {
			$ref_type = '';
		}

		$audience = isset( $item['audience'] ) ? $item['audience'] : self::AUDIENCE_COMMON;
		if ( ! in_array( $audience, array( self::AUDIENCE_ALUMNI, self::AUDIENCE_STUDENT, self::AUDIENCE_COMMON ), true ) ) {
			$audience = self::AUDIENCE_COMMON;
		}

		return array(
			'item_id'   => ( ! empty( $item['item_id'] ) && is_string( $item['item_id'] ) ) ? $item['item_id'] : wp_generate_uuid4(),
			'parent_id' => isset( $item['parent_id'] ) ? (string) $item['parent_id'] : '',
			'audience'  => $audience,
			'order'     => isset( $item['order'] ) ? (int) $item['order'] : 0,
			'enabled'   => ! isset( $item['enabled'] ) || (bool) $item['enabled'],
			'type'      => $type,
			'label'     => isset( $item['label'] ) ? (string) $item['label'] : '',
			'ref_type'  => ( self::TYPE_CONTENT === $type ) ? $ref_type : '',
			'ref_id'    => isset( $item['ref_id'] ) ? (string) $item['ref_id'] : '',
		);
	}

	/**
	 * Builds an initial メニュー構成 from every audience's existing
	 * Content_Hierarchy tree (published and unpublished — an admin who was
	 * mid-way through structuring their コンテンツ階層 shouldn't lose that
	 * work just because Menu_Structure takes over). フォルダ種別の投稿は
	 * type=folder、それ以外はtype=content/ref_type=contentになる.
	 *
	 * @return array[]
	 */
	private static function migrate_from_content_hierarchy() {
		$items = array();

		foreach ( array( self::AUDIENCE_ALUMNI, self::AUDIENCE_STUDENT, self::AUDIENCE_COMMON ) as $audience ) {
			$tree = Content_Hierarchy::build_tree( $audience, true );
			self::migrate_tree_nodes( $tree, '', $audience, $items );
		}

		return $items;
	}

	/**
	 * @param array[] $nodes     Content_Hierarchy::build_tree() shape.
	 * @param string  $parent_id '' for top-level.
	 * @param string  $audience
	 * @param array[] $items     Accumulator, passed by reference.
	 */
	private static function migrate_tree_nodes( array $nodes, $parent_id, $audience, array &$items ) {
		$order = 1;

		foreach ( $nodes as $node ) {
			$post    = $node['post'];
			$item_id = wp_generate_uuid4();
			$is_folder = Content_Post_Type::is_folder( $post );

			$items[] = array(
				'item_id'   => $item_id,
				'parent_id' => $parent_id,
				'audience'  => $audience,
				'order'     => $order++,
				'enabled'   => true,
				'type'      => $is_folder ? self::TYPE_FOLDER : self::TYPE_CONTENT,
				'label'     => '',
				'ref_type'  => $is_folder ? '' : self::REF_CONTENT,
				'ref_id'    => $is_folder ? '' : (string) $post->ID,
			);

			if ( ! empty( $node['children'] ) ) {
				self::migrate_tree_nodes( $node['children'], $item_id, $audience, $items );
			}
		}
	}

	/**
	 * Persists the full items array (every mutator below funnels through
	 * this single write path).
	 *
	 * @param array[] $items
	 * @return array[]
	 */
	private function save_items( array $items ) {
		update_option( self::OPTION_NAME, $items );

		$this->items = array_map( array( __CLASS__, 'normalize_item' ), $items );

		return $this->items;
	}

	/**
	 * @param string $item_id
	 * @return array|null
	 */
	public function get_item( $item_id ) {
		foreach ( $this->get_all() as $item ) {
			if ( $item['item_id'] === $item_id ) {
				return $item;
			}
		}

		return null;
	}

	/**
	 * Direct children of $parent_id, in order — every item regardless of
	 * enabled state (for admin use; public rendering filters separately in
	 * get_tree()).
	 *
	 * @param string      $parent_id '' for top-level.
	 * @param string|null $audience  When $parent_id is '', optionally
	 *                                 filter top-level items by 対象者.
	 * @return array[]
	 */
	public function get_children( $parent_id, $audience = null ) {
		$children = array();

		foreach ( $this->get_all() as $item ) {
			if ( $item['parent_id'] !== $parent_id ) {
				continue;
			}
			if ( null !== $audience && '' === $parent_id && $item['audience'] !== $audience ) {
				continue;
			}
			$children[] = $item;
		}

		usort(
			$children,
			function ( $a, $b ) {
				return $a['order'] <=> $b['order'];
			}
		);

		return $children;
	}

	/**
	 * Every descendant item_id of $item_id (any depth) — used to guard
	 * against creating a cycle when reparenting, and to cascade-delete a
	 * folder's contents.
	 *
	 * @param string $item_id
	 * @return string[]
	 */
	public function get_descendant_ids( $item_id ) {
		$children_map = array();
		foreach ( $this->get_all() as $item ) {
			$children_map[ $item['parent_id'] ][] = $item['item_id'];
		}

		$result = array();
		$seen   = array();
		$queue  = isset( $children_map[ $item_id ] ) ? $children_map[ $item_id ] : array();

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
	 * The public, resolved メニュー構成 tree for one 対象者 (or every
	 * 対象者 when $audience is null): every node already carries its
	 * display label and URL (Theme never has to know the difference
	 * between a folder, content link, system link, or 役員・理事一覧
	 * link — see class docblock's Core/Theme responsibility split). A node
	 * pointing at something invalid, unpublished, disabled, or deleted is
	 * safely dropped (not mutated in storage — the same
	 * "revalidate/hide-on-read" rule used throughout this plugin).
	 *
	 * @param string|null $audience
	 * @return array[] Each: array(
	 *     'item_id'=>string, 'type'=>'folder'|'content', 'label'=>string,
	 *     'url'=>string, 'children'=>(same shape).
	 * )
	 */
	public function get_tree( $audience = null ) {
		return $this->build_public_subtree( '', $audience, 0 );
	}

	/**
	 * @param string      $parent_id
	 * @param string|null $audience
	 * @param int         $depth
	 * @return array[]
	 */
	private function build_public_subtree( $parent_id, $audience, $depth ) {
		if ( $depth > self::MAX_DEPTH ) {
			return array();
		}

		$children = $this->get_children( $parent_id, ( 0 === $depth ) ? $audience : null );
		$nodes    = array();

		foreach ( $children as $item ) {
			if ( ! $item['enabled'] ) {
				continue;
			}

			$resolved = self::resolve_item( $item );
			if ( null === $resolved ) {
				continue;
			}

			$resolved['children'] = $this->build_public_subtree( $item['item_id'], $audience, $depth + 1 );
			$nodes[]               = $resolved;
		}

		return $nodes;
	}

	/**
	 * @param array $item Normalized item.
	 * @return array{item_id:string,type:string,label:string,url:string}|null
	 *               Null when this item can't currently be shown publicly
	 *               (invalid/unpublished/deleted reference).
	 */
	private static function resolve_item( array $item ) {
		if ( self::TYPE_FOLDER === $item['type'] ) {
			return array(
				'item_id' => $item['item_id'],
				'type'    => 'folder',
				'label'   => '' !== $item['label'] ? $item['label'] : __( '（無題フォルダ）', 'alumni-core' ),
				'url'     => '',
			);
		}

		if ( self::REF_CONTENT === $item['ref_type'] ) {
			$post = get_post( absint( $item['ref_id'] ) );

			if ( ! $post || Content_Post_Type::SLUG !== $post->post_type || 'publish' !== $post->post_status ) {
				return null;
			}

			$default_label = Content_Post_Type::is_terms( $post ) ? Content_Post_Type::get_terms_display_title( $post ) : $post->post_title;

			return array(
				'item_id' => $item['item_id'],
				'type'    => 'content',
				'label'   => '' !== $item['label'] ? $item['label'] : $default_label,
				'url'     => (string) get_permalink( $post ),
			);
		}

		if ( self::REF_SYSTEM === $item['ref_type'] ) {
			$labels = Homepage_Sections::system_key_labels();

			if ( ! isset( $labels[ $item['ref_id'] ] ) ) {
				return null;
			}

			$url = Homepage_Sections::resolve_system_url( $item['ref_id'] );

			if ( ! $url ) {
				return null;
			}

			return array(
				'item_id' => $item['item_id'],
				'type'    => 'content',
				'label'   => '' !== $item['label'] ? $item['label'] : $labels[ $item['ref_id'] ],
				'url'     => $url,
			);
		}

		if ( self::REF_OFFICER_LIST === $item['ref_type'] ) {
			$list = Officer_Lists::instance()->get_list( $item['ref_id'] );

			if ( null === $list || empty( $list['enabled'] ) ) {
				return null;
			}

			$url = Officers_Shortcode::get_list_url( $item['ref_id'] );

			if ( ! $url ) {
				return null;
			}

			return array(
				'item_id' => $item['item_id'],
				'type'    => 'content',
				'label'   => '' !== $item['label'] ? $item['label'] : ( $list['title'] ? $list['title'] : $list['name'] ),
				'url'     => $url,
			);
		}

		return null;
	}

	/**
	 * Every menu item (any status) referencing $content_id via
	 * ref_type=content — used for パンくず: an ancestor chain is only
	 * meaningful relative to a specific place in the メニュー構成, and a
	 * content post can in principle be referenced from more than one place
	 * (class docblock's "同一コンテンツの複数配置"). Returns the first
	 * match; see get_ancestors_for_content()'s own docblock for how that's
	 * used.
	 *
	 * @param int $content_id
	 * @return array[]
	 */
	public function find_items_for_content( $content_id ) {
		$content_id = (string) absint( $content_id );
		$matches    = array();

		foreach ( $this->get_all() as $item ) {
			if ( self::TYPE_CONTENT === $item['type'] && self::REF_CONTENT === $item['ref_type'] && $item['ref_id'] === $content_id ) {
				$matches[] = $item;
			}
		}

		return $matches;
	}

	/**
	 * Resolved ancestors (root-first) of the first メニュー項目 referencing
	 * $content_id, for breadcrumbs — "できるだけメニュー構造を基準にする"
	 * (a content post referenced from more than one place shows the first
	 * match's ancestors; documented simplification, see
	 * find_items_for_content()).
	 *
	 * @param int $content_id
	 * @return array[] Each: array('item_id'=>..,'type'=>..,'label'=>..,'url'=>..).
	 */
	public function get_ancestors_for_content( $content_id ) {
		$matches = $this->find_items_for_content( $content_id );

		if ( empty( $matches ) ) {
			return array();
		}

		return $this->get_ancestors( $matches[0]['item_id'] );
	}

	/**
	 * $item_id's ancestors, root-first (not including $item_id itself),
	 * each resolved to a display label/URL. Cycle- and depth-safe.
	 *
	 * @param string $item_id
	 * @return array[]
	 */
	public function get_ancestors( $item_id ) {
		$ancestors = array();
		$visited   = array( $item_id => true );
		$current   = $this->get_item( $item_id );
		$depth     = 0;

		if ( null === $current ) {
			return array();
		}

		$parent_id = $current['parent_id'];

		while ( '' !== $parent_id && $depth < self::MAX_DEPTH ) {
			if ( isset( $visited[ $parent_id ] ) ) {
				break;
			}

			$parent_item = $this->get_item( $parent_id );

			if ( null === $parent_item ) {
				break;
			}

			$visited[ $parent_id ] = true;

			$resolved = self::resolve_item( $parent_item );
			if ( null !== $resolved ) {
				$ancestors[] = $resolved;
			}

			$parent_id = $parent_item['parent_id'];
			$depth++;
		}

		return array_reverse( $ancestors );
	}

	/**
	 * Creates a new フォルダ item.
	 *
	 * @param string $parent_id '' for top-level.
	 * @param string $audience  Only meaningful when $parent_id is ''.
	 * @param string $label     Raw, sanitized here. Falls back to a
	 *                           placeholder when empty.
	 * @return string The new item's ID.
	 */
	public function create_folder( $parent_id, $audience, $label ) {
		return $this->create_item(
			array(
				'parent_id' => $parent_id,
				'audience'  => $audience,
				'type'      => self::TYPE_FOLDER,
				'label'     => sanitize_text_field( $label ),
			)
		);
	}

	/**
	 * Creates a new コンテンツへのリンク item.
	 *
	 * @param string $parent_id      '' for top-level.
	 * @param string $audience       Only meaningful when $parent_id is ''.
	 * @param string $ref_type       self::REF_CONTENT/REF_SYSTEM/REF_OFFICER_LIST.
	 * @param string $ref_id         Post ID (content), system key (system),
	 *                                 or list_id (officer_list).
	 * @param string $label_override Raw, sanitized here. Empty = use the
	 *                                 referenced item's own title.
	 * @return string The new item's ID.
	 */
	public function create_content_item( $parent_id, $audience, $ref_type, $ref_id, $label_override = '' ) {
		return $this->create_item(
			array(
				'parent_id' => $parent_id,
				'audience'  => $audience,
				'type'      => self::TYPE_CONTENT,
				'ref_type'  => $ref_type,
				'ref_id'    => $ref_id,
				'label'     => sanitize_text_field( $label_override ),
			)
		);
	}

	/**
	 * @param array $partial Raw fields; normalize_item() fills in the rest
	 *                        (item_id, order = last among new siblings,
	 *                        enabled = true).
	 * @return string The new item's ID.
	 */
	private function create_item( array $partial ) {
		$items    = $this->get_all();
		$siblings = $this->get_children( isset( $partial['parent_id'] ) ? (string) $partial['parent_id'] : '' );

		$partial['order'] = count( $siblings ) + 1;

		$normalized = self::normalize_item( $partial );
		$items[]    = $normalized;

		$this->save_items( $items );

		return $normalized['item_id'];
	}

	/**
	 * Deletes an item and its entire subtree (menu items are pure
	 * navigation records, not content — unlike Content_Hierarchy's
	 * orphan-promotion policy, removing a メニュー上のフォルダ removes its
	 * navigation children too, since they have no meaning without it in
	 * this structure; the underlying alumni_content posts/officer lists
	 * they referenced are never touched).
	 *
	 * @param string $item_id
	 */
	public function delete_item( $item_id ) {
		$to_delete   = array_merge( array( $item_id ), $this->get_descendant_ids( $item_id ) );
		$to_delete_map = array_flip( $to_delete );

		$items = array_values(
			array_filter(
				$this->get_all(),
				function ( $item ) use ( $to_delete_map ) {
					return ! isset( $to_delete_map[ $item['item_id'] ] );
				}
			)
		);

		$this->save_items( $items );
	}

	/**
	 * Updates an item's label and/or (for type=content) its reference.
	 *
	 * @param string $item_id
	 * @param string $label
	 * @param string $ref_type '' to leave the item's own ref_type
	 *                          unchanged (used when updating a folder's
	 *                          label only).
	 * @param string $ref_id
	 * @return array|null The updated item, or null if $item_id doesn't exist.
	 */
	public function update_item( $item_id, $label, $ref_type = '', $ref_id = '' ) {
		$items = $this->get_all();
		$found = null;

		foreach ( $items as &$item ) {
			if ( $item['item_id'] !== $item_id ) {
				continue;
			}

			$item['label'] = sanitize_text_field( $label );

			if ( self::TYPE_CONTENT === $item['type'] && '' !== $ref_type ) {
				$item['ref_type'] = $ref_type;
				$item['ref_id']   = $ref_id;
			}

			$found = $item;
		}
		unset( $item );

		$this->save_items( $items );

		return $found;
	}

	/**
	 * @param string $item_id
	 * @param bool   $enabled
	 */
	public function set_item_enabled( $item_id, $enabled ) {
		$items = $this->get_all();

		foreach ( $items as &$item ) {
			if ( $item['item_id'] === $item_id ) {
				$item['enabled'] = (bool) $enabled;
			}
		}
		unset( $item );

		$this->save_items( $items );
	}

	/**
	 * Moves an item up or down among its siblings (items sharing the same
	 * parent_id), by swapping 表示順. A no-op at either end.
	 *
	 * @param string $item_id
	 * @param string $direction 'up' or 'down'.
	 */
	public function move_item( $item_id, $direction ) {
		$current = $this->get_item( $item_id );

		if ( null === $current ) {
			return;
		}

		$siblings = $this->get_children( $current['parent_id'] ); // Sorted by order.
		$index    = null;

		foreach ( $siblings as $i => $sibling ) {
			if ( $sibling['item_id'] === $item_id ) {
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

		$this->swap_orders( $siblings[ $index ]['item_id'], $siblings[ $swap_with ]['item_id'] );
	}

	/**
	 * @param string $item_id_a
	 * @param string $item_id_b
	 */
	private function swap_orders( $item_id_a, $item_id_b ) {
		$items = $this->get_all();
		$a     = null;
		$b     = null;

		foreach ( $items as &$item ) {
			if ( $item['item_id'] === $item_id_a ) {
				$a = &$item;
			} elseif ( $item['item_id'] === $item_id_b ) {
				$b = &$item;
			}
		}

		if ( null !== $a && null !== $b ) {
			$tmp        = $a['order'];
			$a['order'] = $b['order'];
			$b['order'] = $tmp;
		}
		unset( $a, $b, $item );

		$this->save_items( $items );
	}

	/**
	 * 「階層を深くする」: $item_id をその直前の兄弟の子として再配置する
	 * （直前の兄弟がなければ何もしない — 一覧の先頭は、これ以上深くしよう
	 * がない）。再配置後は新しい兄弟グループの末尾に置かれる。
	 *
	 * @param string $item_id
	 */
	public function indent_item( $item_id ) {
		$current = $this->get_item( $item_id );

		if ( null === $current ) {
			return;
		}

		$siblings = $this->get_children( $current['parent_id'] );
		$index    = null;

		foreach ( $siblings as $i => $sibling ) {
			if ( $sibling['item_id'] === $item_id ) {
				$index = $i;
				break;
			}
		}

		if ( null === $index || 0 === $index ) {
			return; // No preceding sibling to become the new parent.
		}

		$new_parent_id = $siblings[ $index - 1 ]['item_id'];

		// Reparenting under one of the item's own descendants would create
		// a cycle — can't happen here since $new_parent_id is always a
		// sibling (same $parent_id as $item_id itself), never a descendant,
		// but this reuses the same guard the admin form relies on for
		// belt-and-braces safety against any future caller.
		if ( in_array( $new_parent_id, $this->get_descendant_ids( $item_id ), true ) ) {
			return;
		}

		$this->reparent( $item_id, $new_parent_id );
	}

	/**
	 * 「階層を浅くする」: $item_id を、その親と同じ階層（親の直後）へ
	 * 移動する。トップレベルの項目は、これ以上浅くしようがないので
	 * 何もしない。
	 *
	 * @param string $item_id
	 */
	public function outdent_item( $item_id ) {
		$current = $this->get_item( $item_id );

		if ( null === $current || '' === $current['parent_id'] ) {
			return;
		}

		$parent = $this->get_item( $current['parent_id'] );

		if ( null === $parent ) {
			return;
		}

		// $inherit_audience is only actually applied by reparent() when the
		// destination parent_id is '' (i.e. $parent was itself top-level) —
		// harmless to pass unconditionally otherwise.
		$this->reparent( $item_id, $parent['parent_id'], $parent['audience'] );
	}

	/**
	 * Reparents $item_id under $new_parent_id, appended to the end of that
	 * new sibling group. When $new_parent_id is '' (moving to top-level),
	 * $inherit_audience lets outdent_item() carry over the old parent's
	 * 対象者 so the item doesn't silently become "共通".
	 *
	 * @param string $item_id
	 * @param string $new_parent_id
	 * @param string|null $inherit_audience
	 */
	private function reparent( $item_id, $new_parent_id, $inherit_audience = null ) {
		$items       = $this->get_all();
		$new_order   = count( $this->get_children( $new_parent_id ) ) + 1;

		foreach ( $items as &$item ) {
			if ( $item['item_id'] !== $item_id ) {
				continue;
			}

			$item['parent_id'] = $new_parent_id;
			$item['order']     = $new_order;

			if ( '' === $new_parent_id && null !== $inherit_audience ) {
				$item['audience'] = $inherit_audience;
			}
		}
		unset( $item );

		$this->save_items( $items );
	}
}
