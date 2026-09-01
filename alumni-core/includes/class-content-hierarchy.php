<?php
/**
 * コンテンツ階層（親子関係）を辿るためのヘルパー.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes;

use AlumniCore\Includes\Modules\Content\Post_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 「対象者（大カテゴリ）」と「コンテンツ階層（親子）」は別概念として管理
 * される（Post_Type::META_AUDIENCE / META_PARENT_ID 参照）。このクラスは
 * 階層側だけを扱う、ステートレスな静的ヘルパー — 親子関係そのものは
 * alumni_content の投稿メタとして保存されており、このクラスはそれを
 * 安全に（無限ループ・孤立データに強い形で）辿るための計算だけを担う。
 *
 * 「孤立データの安全な処理」方針: 親が削除・非公開化された場合、その子は
 * 消えたり壊れたりするのではなく、公開側では最上位（トップレベル）へ
 * 自動的に昇格する（effective parent = 0）。管理画面側（$include_unpublished
 * = true）では実際の親IDをそのまま見せ、編集者が階層を組み立てている
 * 途中の状態（親がまだ下書きのまま等）も正しく確認できるようにする。
 */
class Content_Hierarchy {

	/**
	 * A hard cap on ancestor-walk / subtree-build depth, so a corrupted
	 * parent chain (e.g. a manually-edited DB row that formed a cycle
	 * despite validate_parent()'s write-time guard) can never turn a page
	 * render into an infinite loop.
	 */
	const MAX_DEPTH = 20;

	/**
	 * Every published alumni_content post (any kind, folders included).
	 *
	 * @return \WP_Post[]
	 */
	private static function get_all_published() {
		$query = alumni_core_get_contents_query( array( 'posts_per_page' => -1 ) );

		return $query->posts;
	}

	/**
	 * Every alumni_content post regardless of status — used only for
	 * admin-facing tree display and the 親コンテンツ picker, where editors
	 * need to see structure that includes not-yet-published drafts.
	 *
	 * @return \WP_Post[]
	 */
	private static function get_all_including_unpublished() {
		return get_posts(
			array(
				'post_type'      => Post_Type::SLUG,
				'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
	}

	/**
	 * The direct children of $parent_id.
	 *
	 * @param int         $parent_id           0 for top-level (一覧の対象者
	 *                                           直下).
	 * @param string|null $audience             Filter by Post_Type::AUDIENCE_*,
	 *                                           or null for any.
	 * @param bool        $include_unpublished  When true, includes every
	 *                                           status and uses the raw
	 *                                           (non-promoted) parent ID —
	 *                                           see class docblock.
	 * @return \WP_Post[]
	 */
	public static function get_children( $parent_id, $audience = null, $include_unpublished = false ) {
		$parent_id = absint( $parent_id );
		$posts     = $include_unpublished ? self::get_all_including_unpublished() : self::get_all_published();

		if ( $include_unpublished ) {
			$children = array();
			foreach ( $posts as $post ) {
				if ( Post_Type::get_parent_id( $post ) !== $parent_id ) {
					continue;
				}
				if ( null !== $audience && Post_Type::get_audience( $post ) !== $audience ) {
					continue;
				}
				$children[] = $post;
			}
			return $children;
		}

		$by_id = array();
		foreach ( $posts as $post ) {
			$by_id[ $post->ID ] = $post;
		}

		$children = array();
		foreach ( $posts as $post ) {
			$effective_parent = self::effective_parent_id( $post, $by_id );
			if ( $effective_parent !== $parent_id ) {
				continue;
			}
			if ( null !== $audience && Post_Type::get_audience( $post ) !== $audience ) {
				continue;
			}
			$children[] = $post;
		}

		return $children;
	}

	/**
	 * The declared parent ID, "promoted" to 0 (top-level) when that parent
	 * isn't in the given published-posts set — i.e. it's been trashed,
	 * deleted, or unpublished. See class docblock.
	 *
	 * @param \WP_Post $post  A published alumni_content post.
	 * @param array    $by_id Every published alumni_content post, keyed by ID.
	 * @return int
	 */
	private static function effective_parent_id( $post, array $by_id ) {
		$parent_id = Post_Type::get_parent_id( $post );

		if ( ! $parent_id || ! isset( $by_id[ $parent_id ] ) ) {
			return 0;
		}

		return $parent_id;
	}

	/**
	 * Top-level content for one 対象者 (or every top-level content when
	 * $audience is null).
	 *
	 * @param string|null $audience
	 * @param bool        $include_unpublished
	 * @return \WP_Post[]
	 */
	public static function get_roots( $audience = null, $include_unpublished = false ) {
		return self::get_children( 0, $audience, $include_unpublished );
	}

	/**
	 * $post's ancestors, root-first (does not include $post itself). Cycle-
	 * and depth-safe: a corrupted parent chain simply stops rather than
	 * looping or erroring.
	 *
	 * @param int|\WP_Post|null $post
	 * @return \WP_Post[]
	 */
	public static function get_ancestors( $post ) {
		$post = get_post( $post );

		if ( ! $post ) {
			return array();
		}

		$ancestors = array();
		$visited   = array( (int) $post->ID => true );
		$parent_id = Post_Type::get_parent_id( $post );
		$depth     = 0;

		while ( $parent_id && $depth < self::MAX_DEPTH ) {
			if ( isset( $visited[ $parent_id ] ) ) {
				break; // Cycle guard.
			}

			$parent_post = get_post( $parent_id );

			if ( ! $parent_post || Post_Type::SLUG !== $parent_post->post_type ) {
				break;
			}

			$visited[ $parent_id ] = true;
			$ancestors[]           = $parent_post;
			$parent_id             = Post_Type::get_parent_id( $parent_post );
			$depth++;
		}

		return array_reverse( $ancestors );
	}

	/**
	 * Every descendant post ID of $post_id (any depth), regardless of
	 * status — used by validate_parent() to reject a parent choice that
	 * would create a cycle.
	 *
	 * @param int $post_id
	 * @return int[]
	 */
	public static function get_descendant_ids( $post_id ) {
		$posts        = self::get_all_including_unpublished();
		$children_map = array();

		foreach ( $posts as $post ) {
			$children_map[ Post_Type::get_parent_id( $post ) ][] = (int) $post->ID;
		}

		$result = array();
		$seen   = array();
		$queue  = isset( $children_map[ (int) $post_id ] ) ? $children_map[ (int) $post_id ] : array();

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
	 * Whether $candidate_parent_id is a safe 親コンテンツ choice for
	 * $post_id: not itself, an existing alumni_content post (or 0, always
	 * valid), and not one of its own descendants (which would create a
	 * cycle).
	 *
	 * @param int $post_id             0 when validating for a not-yet-created
	 *                                  post (nothing can be its descendant yet).
	 * @param int $candidate_parent_id 0 means "no parent" (top-level), always
	 *                                  valid.
	 * @return bool
	 */
	public static function validate_parent( $post_id, $candidate_parent_id ) {
		$post_id             = absint( $post_id );
		$candidate_parent_id = absint( $candidate_parent_id );

		if ( 0 === $candidate_parent_id ) {
			return true;
		}

		if ( $candidate_parent_id === $post_id ) {
			return false;
		}

		if ( Post_Type::SLUG !== get_post_type( $candidate_parent_id ) ) {
			return false;
		}

		if ( ! $post_id ) {
			return true;
		}

		return ! in_array( $candidate_parent_id, self::get_descendant_ids( $post_id ), true );
	}

	/**
	 * The full content tree for one 対象者 (or every 対象者 when $audience
	 * is null), as a nested array: array( array( 'post' => WP_Post,
	 * 'children' => <same shape> ), ... ). 対象者 is only used to filter the
	 * TOP-LEVEL roots — once inside a branch, every descendant is included
	 * regardless of its own 対象者 tag (see class docblock: 対象者と階層は
	 * 別概念であり、混在は許容される。基本的に同一大カテゴリ内での構成が
	 * 推奨されるだけで、強制はしない).
	 *
	 * @param string|null $audience
	 * @param bool        $include_unpublished
	 * @return array[]
	 */
	public static function build_tree( $audience = null, $include_unpublished = false ) {
		return self::build_subtree( 0, $audience, $include_unpublished, 0 );
	}

	/**
	 * @param int         $parent_id
	 * @param string|null $audience
	 * @param bool        $include_unpublished
	 * @param int         $depth
	 * @return array[]
	 */
	private static function build_subtree( $parent_id, $audience, $include_unpublished, $depth ) {
		if ( $depth > self::MAX_DEPTH ) {
			return array();
		}

		$children = self::get_children( $parent_id, ( 0 === $depth ) ? $audience : null, $include_unpublished );
		$nodes    = array();

		foreach ( $children as $post ) {
			$nodes[] = array(
				'post'     => $post,
				'children' => self::build_subtree( $post->ID, $audience, $include_unpublished, $depth + 1 ),
			);
		}

		return $nodes;
	}
}
