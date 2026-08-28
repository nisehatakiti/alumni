<?php
/**
 * 管理画面の一覧に「種別」「イベント開催日」列を追加する.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes\Modules\NewsEvents;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds two custom columns to the ニュース・イベント list table. Title,
 * date and publish status are already shown by WordPress's default
 * columns, so only the two custom fields need adding here.
 */
class Admin_Columns {

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
				$new['alumni_content_type'] = __( '種別', 'alumni-core' );
				$new['alumni_event_date']   = __( 'イベント開催日', 'alumni-core' );
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
		if ( 'alumni_content_type' === $column ) {
			echo esc_html(
				Post_Type::is_event( $post_id )
					? __( 'イベント', 'alumni-core' )
					: __( 'ニュース', 'alumni-core' )
			);
			return;
		}

		if ( 'alumni_event_date' === $column ) {
			$date = Post_Type::get_event_date( $post_id );
			echo $date ? esc_html( mysql2date( get_option( 'date_format' ), $date ) ) : '&#8212;';
		}
	}
}
