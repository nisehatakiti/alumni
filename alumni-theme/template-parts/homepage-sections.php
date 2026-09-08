<?php
/**
 * トップページの固定3×2 Homepage Gridを描画する.
 *
 * CoreがA〜Fセルと上下結合状態を管理し、Themeはその選択結果を
 * ブロック単位のUIへ変換する。
 *
 * @package Alumni_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$alumni_hp_grid = function_exists( 'alumni_theme_get_homepage_grid' ) ? alumni_theme_get_homepage_grid() : array();

if ( empty( $alumni_hp_grid['cells'] ) ) {
	return;
}

$alumni_hp_cells  = $alumni_hp_grid['cells'];
$alumni_hp_merged = isset( $alumni_hp_grid['merged_columns'] ) ? $alumni_hp_grid['merged_columns'] : array();
$alumni_hp_order  = array( 'A', 'B', 'C', 'D', 'E', 'F' );

/**
 * Render one configured block. Empty/invalid references render nothing.
 *
 * @param array $alumni_hp_slot
 */
$alumni_hp_render_block = static function( array $alumni_hp_slot ) {
	if ( 'system' === $alumni_hp_slot['type'] ) {
		$alumni_hp_system_key   = $alumni_hp_slot['system_key'];
		$alumni_hp_system_label = alumni_theme_get_system_slot_label( $alumni_hp_system_key );
		$alumni_hp_system_url   = alumni_theme_get_system_slot_url( $alumni_hp_system_key );

		if ( ! $alumni_hp_system_label || ! $alumni_hp_system_url ) {
			return;
		}
		?>
		<div class="alumni-homepage-slot-system alumni-homepage-slot-system-<?php echo esc_attr( $alumni_hp_system_key ); ?>">
			<h2 class="alumni-homepage-slot-title"><a href="<?php echo esc_url( $alumni_hp_system_url ); ?>"><?php echo esc_html( $alumni_hp_system_label ); ?></a></h2>

			<?php if ( 'news' === $alumni_hp_system_key ) : ?>
				<?php $alumni_hp_teaser = alumni_theme_get_news_teaser( 3 ); ?>
				<?php if ( $alumni_hp_teaser && $alumni_hp_teaser->have_posts() ) : ?>
					<div class="alumni-homepage-slot-teaser-list">
						<?php while ( $alumni_hp_teaser->have_posts() ) : $alumni_hp_teaser->the_post(); ?>
							<?php get_template_part( 'template-parts/news-event-row' ); ?>
						<?php endwhile; wp_reset_postdata(); ?>
					</div>
				<?php endif; ?>
			<?php elseif ( 'events' === $alumni_hp_system_key ) : ?>
				<?php $alumni_hp_upcoming_events = alumni_theme_get_upcoming_events( 3 ); ?>
				<?php $alumni_hp_past_events = alumni_theme_get_past_events( 3 ); ?>
				<?php if ( $alumni_hp_upcoming_events ) : ?>
					<h3 class="alumni-homepage-slot-teaser-heading"><?php esc_html_e( '今後のイベント', 'alumni-theme' ); ?></h3>
					<div class="alumni-homepage-slot-teaser-list">
						<?php foreach ( $alumni_hp_upcoming_events as $alumni_hp_event_post ) : setup_postdata( $alumni_hp_event_post ); ?>
							<?php get_template_part( 'template-parts/news-event-row' ); ?>
						<?php endforeach; wp_reset_postdata(); ?>
					</div>
				<?php endif; ?>
				<?php if ( $alumni_hp_past_events ) : ?>
					<h3 class="alumni-homepage-slot-teaser-heading"><?php esc_html_e( '終了したイベント', 'alumni-theme' ); ?></h3>
					<div class="alumni-homepage-slot-teaser-list">
						<?php foreach ( $alumni_hp_past_events as $alumni_hp_event_post ) : setup_postdata( $alumni_hp_event_post ); ?>
							<?php get_template_part( 'template-parts/news-event-row' ); ?>
						<?php endforeach; wp_reset_postdata(); ?>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
		return;
	}

	if ( 'person_greeting_group' === $alumni_hp_slot['type'] ) {
		$alumni_hp_group = \AlumniCore\Includes\Person_Greeting_Groups::instance()->get_group( $alumni_hp_slot['group_id'] );
		$alumni_hp_group_url = \AlumniCore\Includes\Person_Greeting_Groups_Shortcode::get_group_url( $alumni_hp_slot['group_id'] );

		if ( null === $alumni_hp_group ) {
			return;
		}
		?>
		<div class="alumni-homepage-slot-person-greeting-group">
			<h2 class="alumni-homepage-slot-title">
				<?php if ( $alumni_hp_group_url ) : ?>
					<a href="<?php echo esc_url( $alumni_hp_group_url ); ?>"><?php echo esc_html( $alumni_hp_group['name'] ); ?></a>
				<?php else : ?>
					<?php echo esc_html( $alumni_hp_group['name'] ); ?>
				<?php endif; ?>
			</h2>
		</div>
		<?php
		return;
	}

	if ( 'content' === $alumni_hp_slot['type'] ) {
		$alumni_hp_content_post = alumni_theme_get_content( $alumni_hp_slot['content_id'] );
		if ( ! $alumni_hp_content_post ) {
			return;
		}

		$alumni_hp_content_terms    = alumni_theme_get_terms( $alumni_hp_content_post );
		$alumni_hp_content_greeting = alumni_theme_get_person_greeting( $alumni_hp_content_post );
		$alumni_hp_content_title    = $alumni_hp_content_terms ? $alumni_hp_content_terms['display_title'] : $alumni_hp_content_post->post_title;
		$alumni_hp_content_excerpt  = wp_trim_words( wp_strip_all_tags( $alumni_hp_content_post->post_content ), 30 );
		?>
		<div class="alumni-homepage-slot-content">
			<h2 class="alumni-homepage-slot-title">
				<a href="<?php echo esc_url( alumni_theme_get_content_url( $alumni_hp_content_post->ID ) ); ?>"><?php echo esc_html( $alumni_hp_content_title ); ?></a>
			</h2>
			<?php if ( $alumni_hp_content_greeting && $alumni_hp_content_greeting['name'] ) : ?>
				<p class="alumni-homepage-slot-content-person">
					<?php echo esc_html( $alumni_hp_content_greeting['name'] ); ?>
					<?php if ( $alumni_hp_content_greeting['title'] ) : ?>（<?php echo esc_html( $alumni_hp_content_greeting['title'] ); ?>）<?php endif; ?>
				</p>
			<?php endif; ?>
			<?php if ( $alumni_hp_content_excerpt ) : ?>
				<p class="alumni-homepage-slot-content-excerpt"><?php echo esc_html( $alumni_hp_content_excerpt ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}
};

$alumni_hp_has_visible_block = false;
foreach ( $alumni_hp_order as $alumni_hp_cell_key ) {
	$alumni_hp_slot = isset( $alumni_hp_cells[ $alumni_hp_cell_key ] ) ? $alumni_hp_cells[ $alumni_hp_cell_key ] : array( 'type' => 'none' );
	if ( 'none' !== $alumni_hp_slot['type'] ) {
		$alumni_hp_has_visible_block = true;
		break;
	}
}

if ( ! $alumni_hp_has_visible_block ) {
	return;
}
?>
<div class="alumni-homepage-grid">
	<?php foreach ( $alumni_hp_order as $alumni_hp_cell_key ) : ?>
		<?php
		$alumni_hp_slot = isset( $alumni_hp_cells[ $alumni_hp_cell_key ] ) ? $alumni_hp_cells[ $alumni_hp_cell_key ] : array( 'type' => 'none' );
		$alumni_hp_hidden_by_merge = ( 'D' === $alumni_hp_cell_key && ! empty( $alumni_hp_merged['A'] ) )
			|| ( 'E' === $alumni_hp_cell_key && ! empty( $alumni_hp_merged['B'] ) )
			|| ( 'F' === $alumni_hp_cell_key && ! empty( $alumni_hp_merged['C'] ) );

		if ( $alumni_hp_hidden_by_merge || 'none' === $alumni_hp_slot['type'] ) {
			continue;
		}

		$alumni_hp_is_merged = in_array( $alumni_hp_cell_key, array( 'A', 'B', 'C' ), true ) && ! empty( $alumni_hp_merged[ $alumni_hp_cell_key ] );
		?>
		<section class="alumni-homepage-grid-block alumni-homepage-grid-block-<?php echo esc_attr( strtolower( $alumni_hp_cell_key ) ); ?><?php echo $alumni_hp_is_merged ? ' is-vertically-merged' : ''; ?>">
			<?php $alumni_hp_render_block( $alumni_hp_slot ); ?>
		</section>
	<?php endforeach; ?>
</div>