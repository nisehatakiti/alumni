<?php
/**
 * 同窓会 > 規約類 screen.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Admin\Pages;

use AlumniCore\Admin\Admin;
use AlumniCore\Includes\Modules\Content\Post_Type as Content_Post_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 規約類は既存の alumni_content CPT（kind=terms）として保存されており、
 * 作成・編集そのものは標準の投稿編集画面（post-new.php / post.php）で
 * 行う — この画面は「規約類だけを絞り込んだ、専用の一覧」を提供する
 * 読み取り専用スクリーンで、Graduation_Lookup_Page と同じ「GETのみ、
 * 何も書き込まない」screen。
 */
class Terms_Page {

	/**
	 * Submenu slug.
	 */
	const SLUG = 'alumni-core-terms';

	/**
	 * Renders the screen.
	 */
	public function render() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			return;
		}

		$posts = get_posts(
			array(
				'post_type'      => Content_Post_Type::SLUG,
				'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
				'meta_key'       => Content_Post_Type::META_KIND, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- small admin-only list, filtering by kind is the entire purpose of this query.
				'meta_value'     => Content_Post_Type::KIND_TERMS, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		$new_url = add_query_arg(
			array(
				'post_type'                          => Content_Post_Type::SLUG,
				Content_Post_Type::QUERY_VAR_KIND => Content_Post_Type::KIND_TERMS,
			),
			admin_url( 'post-new.php' )
		);
		?>
		<div class="wrap alumni-core-terms">
			<h1>
				<?php esc_html_e( '規約類', 'alumni-core' ); ?>
				<a href="<?php echo esc_url( $new_url ); ?>" class="page-title-action"><?php esc_html_e( '＋ 新規追加', 'alumni-core' ); ?></a>
			</h1>

			<p><?php esc_html_e( '同窓会規約・会則・細則・個人情報保護方針などを管理できます。作成・編集は通常の投稿編集画面で行います（コンテンツ種別を「規約類」にしてください）。', 'alumni-core' ); ?></p>

			<?php if ( empty( $posts ) ) : ?>
				<p class="description"><?php esc_html_e( 'まだ規約類が登録されていません。', 'alumni-core' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped alumni-terms-table">
					<thead>
						<tr>
							<th><?php esc_html_e( '規約名', 'alumni-core' ); ?></th>
							<th><?php esc_html_e( '公開状態', 'alumni-core' ); ?></th>
							<th><?php esc_html_e( '表示順', 'alumni-core' ); ?></th>
							<th><?php esc_html_e( '操作', 'alumni-core' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $posts as $post ) : ?>
							<?php $edit_url = get_edit_post_link( $post->ID, '' ); ?>
							<tr>
								<td>
									<?php if ( $edit_url ) : ?>
										<a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $post->post_title ? $post->post_title : sprintf( '#%d', $post->ID ) ); ?></a>
									<?php else : ?>
										<?php echo esc_html( $post->post_title ? $post->post_title : sprintf( '#%d', $post->ID ) ); ?>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( self::status_label( $post->post_status ) ); ?></td>
								<td><?php echo esc_html( $post->menu_order ); ?></td>
								<td>
									<?php if ( $edit_url ) : ?>
										<a class="button" href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( '編集', 'alumni-core' ); ?></a>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * A human-readable label for a post_status, for this read-only list —
	 * WordPress core only translates these inside its own list tables.
	 *
	 * @param string $status
	 * @return string
	 */
	private static function status_label( $status ) {
		switch ( $status ) {
			case 'publish':
				return __( '公開', 'alumni-core' );
			case 'draft':
				return __( '下書き', 'alumni-core' );
			case 'pending':
				return __( '承認待ち', 'alumni-core' );
			case 'future':
				return __( '予約', 'alumni-core' );
			case 'private':
				return __( '非公開', 'alumni-core' );
			default:
				return $status;
		}
	}
}
