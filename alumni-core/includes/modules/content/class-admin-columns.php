<?php
/**
 * 管理画面の一覧に「種別」「氏名」列を追加する.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes\Modules\Content;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds two custom columns to the コンテンツ list table, mirroring
 * NewsEvents\Admin_Columns.
 */
class Content_Admin_Columns {

	/**
	 * Inserts custom columns right after the title column.
	 *
	 * @param array $columns Existing column headers.
	 * @return array
	 */
	public function add_columns( $columns ) {
		$new = array();

		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;

			if ( 'title' === $key ) {
				$new['alumni_content_kind'] = __( '種別', 'alumni-core' );
				$new['alumni_person_name']  = __( '氏名', 'alumni-core' );
			}
		}

		return $new;
	}

	/**
	 * Renders the custom column cells.
	 *
	 * @param string $column  Column key being rendered.
	 * @param int    $post_id Current row's post ID.
	 */
	public function render_column( $column, $post_id ) {
		if ( 'alumni_content_kind' === $column ) {
			echo esc_html(
				Post_Type::is_person_greeting( $post_id )
					? __( '人物挨拶', 'alumni-core' )
					: __( '自由コンテンツ', 'alumni-core' )
			);
			return;
		}

		if ( 'alumni_person_name' === $column ) {
			$name = Post_Type::is_person_greeting( $post_id ) ? Post_Type::get_person_name( $post_id ) : '';
			echo $name ? esc_html( $name ) : '&#8212;';
		}
	}
}
