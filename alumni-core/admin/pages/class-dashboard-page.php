<?php
/**
 * 同窓会 > ダッシュボード screen.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Admin\Pages;

use AlumniCore\Admin\Admin;
use AlumniCore\Includes\Settings;
use AlumniCore\Includes\Officer_Lists;
use AlumniCore\Includes\Org_Chart;
use AlumniCore\Includes\School_Song_Motto_Shortcode;
use AlumniCore\Includes\Modules\Content\Post_Type as Content_Post_Type;
use AlumniCore\Includes\Modules\NewsEvents\Post_Type as News_Event_Post_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Alumni Core の管理状況を一目で確認するダッシュボード。
 *
 * 基本設定の確認だけでなく、校章・校歌・校訓と、各コンテンツ／組織データの
 * 登録件数をまとめて表示する。同窓会メニューのハブとして使えるよう、各項目
 * から既存の管理画面へ直接遷移できる。
 */
class Dashboard_Page {

	/**
	 * Renders the screen.
	 */
	public function render() {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			return;
		}

		$settings    = Settings::instance()->get_all();
		$song        = School_Song_Motto_Shortcode::get_song_data();
		$motto       = School_Song_Motto_Shortcode::get_motto_data();
		$display_mode = School_Song_Motto_Shortcode::get_display_mode();

		$emblem_id  = absint( $settings['school_emblem_id'] );
		$emblem_url = $emblem_id ? wp_get_attachment_image_url( $emblem_id, 'medium' ) : '';

		$free_count     = $this->count_content_kind( Content_Post_Type::KIND_FREE );
		$greeting_count = $this->count_content_kind( Content_Post_Type::KIND_PERSON_GREETING );
		$terms_count    = $this->count_content_kind( Content_Post_Type::KIND_TERMS );
		$news_count     = $this->count_post_type( News_Event_Post_Type::SLUG );
		$officer_stats  = $this->get_officer_stats();
		$chart_count    = $this->get_org_chart_count();

		$all_content_url = admin_url( 'edit.php?post_type=' . Content_Post_Type::SLUG );
		$free_url = $all_content_url . '&' . Content_Post_Type::QUERY_VAR_KIND . '=' . Content_Post_Type::KIND_FREE;
		$greeting_url = $all_content_url . '&' . Content_Post_Type::QUERY_VAR_KIND . '=' . Content_Post_Type::KIND_PERSON_GREETING;
		?>
		<div class="wrap alumni-core-dashboard">
			<div class="alumni-core-dashboard-header">
				<div>
					<h1><?php esc_html_e( '同窓会 ダッシュボード', 'alumni-core' ); ?></h1>
					<p><?php esc_html_e( '学校・同窓会の基本情報と、登録済みコンテンツの状況を確認できます。', 'alumni-core' ); ?></p>
				</div>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . Settings_Page::SLUG ) ); ?>" class="button button-primary">
					<?php esc_html_e( '基本設定を編集', 'alumni-core' ); ?>
				</a>
			</div>

			<div class="alumni-core-dashboard-grid">
				<section class="alumni-core-dashboard-card alumni-core-dashboard-profile">
					<div class="alumni-core-dashboard-card-heading">
						<h2><?php esc_html_e( '学校・同窓会情報', 'alumni-core' ); ?></h2>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . Settings_Page::SLUG ) ); ?>"><?php esc_html_e( '編集', 'alumni-core' ); ?></a>
					</div>
					<div class="alumni-core-dashboard-profile-body">
						<div class="alumni-core-dashboard-emblem">
							<?php if ( $emblem_url ) : ?>
								<img src="<?php echo esc_url( $emblem_url ); ?>" alt="<?php echo esc_attr( $settings['school_name'] ); ?>" />
							<?php else : ?>
								<span class="dashicons dashicons-shield-alt" aria-hidden="true"></span>
								<span><?php esc_html_e( '校章未設定', 'alumni-core' ); ?></span>
							<?php endif; ?>
						</div>
						<table class="widefat striped alumni-core-dashboard-info-table">
							<tbody>
								<tr><th scope="row"><?php esc_html_e( '同窓会名称', 'alumni-core' ); ?></th><td><?php echo esc_html( $this->value_or_unset( $settings['association_name'] ) ); ?></td></tr>
								<tr><th scope="row"><?php esc_html_e( '学校名称', 'alumni-core' ); ?></th><td><?php echo esc_html( $this->value_or_unset( $settings['school_name'] ) ); ?></td></tr>
								<tr><th scope="row"><?php esc_html_e( '学校創立年', 'alumni-core' ); ?></th><td><?php echo esc_html( $this->value_or_unset( $settings['school_founded_year'] ) ); ?></td></tr>
								<tr><th scope="row"><?php esc_html_e( '第1期卒業年', 'alumni-core' ); ?></th><td><?php echo esc_html( $this->value_or_unset( $settings['first_graduation_year'] ) ); ?></td></tr>
								<tr><th scope="row"><?php esc_html_e( '卒業期カラー機能', 'alumni-core' ); ?></th><td><?php echo esc_html( $settings['color_feature_enabled'] ? __( 'ON', 'alumni-core' ) : __( 'OFF', 'alumni-core' ) ); ?></td></tr>
							</tbody>
						</table>
					</div>
				</section>

				<section class="alumni-core-dashboard-card">
					<div class="alumni-core-dashboard-card-heading">
						<h2><?php esc_html_e( '校歌・校訓', 'alumni-core' ); ?></h2>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . School_Song_Motto_Page::SLUG ) ); ?>"><?php esc_html_e( '編集', 'alumni-core' ); ?></a>
					</div>
					<div class="alumni-core-dashboard-school-summary">
						<div class="alumni-core-dashboard-school-item">
							<h3><?php esc_html_e( '校歌', 'alumni-core' ); ?></h3>
							<p class="alumni-core-dashboard-main-value"><?php echo esc_html( $this->value_or_unset( $song['title'] ) ); ?></p>
							<p class="description">
								<?php
								$parts = array();
								if ( ! empty( $song['lyrics'] ) ) { $parts[] = __( '歌詞登録済み', 'alumni-core' ); }
								if ( ! empty( $song['audio_id'] ) ) { $parts[] = __( '音源登録済み', 'alumni-core' ); }
								if ( ! empty( $song['sheet_id'] ) ) { $parts[] = __( '楽譜登録済み', 'alumni-core' ); }
								echo esc_html( ! empty( $parts ) ? implode( '・', $parts ) : __( '未登録', 'alumni-core' ) );
								?>
							</p>
						</div>
						<div class="alumni-core-dashboard-school-item">
							<h3><?php esc_html_e( '校訓', 'alumni-core' ); ?></h3>
							<?php if ( 'image' === $motto['display_type'] || 'both' === $motto['display_type'] ) : ?>
								<?php if ( ! empty( $motto['image_id'] ) ) : ?>
									<div class="alumni-core-dashboard-motto-image"><?php echo wp_get_attachment_image( absint( $motto['image_id'] ), 'medium' ); ?></div>
								<?php else : ?>
									<p class="alumni-core-dashboard-main-value"><?php esc_html_e( '画像未設定', 'alumni-core' ); ?></p>
								<?php endif; ?>
							<?php endif; ?>
							<?php if ( 'text' === $motto['display_type'] || 'both' === $motto['display_type'] ) : ?>
								<p class="alumni-core-dashboard-motto-text"><?php echo esc_html( $this->value_or_unset( $motto['text'] ) ); ?></p>
							<?php endif; ?>
							<?php if ( ! empty( $motto['description'] ) ) : ?>
								<p class="description"><?php echo esc_html( $motto['description'] ); ?></p>
							<?php endif; ?>
						</div>
					</div>
					<div class="alumni-core-dashboard-status-row">
						<span><?php esc_html_e( '公開形式', 'alumni-core' ); ?>: <strong><?php echo esc_html( 'separate' === $display_mode ? __( '校歌と校訓を別ページ', 'alumni-core' ) : __( '校歌・校訓を同一ページ', 'alumni-core' ) ); ?></strong></span>
					</div>
				</section>
			</div>

			<section class="alumni-core-dashboard-section">
				<div class="alumni-core-dashboard-section-heading">
					<h2><?php esc_html_e( 'コンテンツ登録状況', 'alumni-core' ); ?></h2>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=alumni-core-content' ) ); ?>" class="button"><?php esc_html_e( 'コンテンツを管理', 'alumni-core' ); ?></a>
				</div>
				<div class="alumni-core-dashboard-stats">
					<?php $this->render_stat_card( __( '自由コンテンツ', 'alumni-core' ), $free_count, $free_url, 'edit-page' ); ?>
					<?php $this->render_stat_card( __( 'ニュース・イベント', 'alumni-core' ), $news_count, admin_url( 'edit.php?post_type=' . News_Event_Post_Type::SLUG ), 'megaphone' ); ?>
					<?php $this->render_stat_card( __( '人物挨拶', 'alumni-core' ), $greeting_count, $greeting_url, 'admin-users' ); ?>
					<?php $this->render_stat_card( __( '規約類', 'alumni-core' ), $terms_count, admin_url( 'admin.php?page=' . Terms_Page::SLUG ), 'media-text' ); ?>
				</div>
			</section>

			<section class="alumni-core-dashboard-section">
				<div class="alumni-core-dashboard-section-heading">
					<h2><?php esc_html_e( '組織登録状況', 'alumni-core' ); ?></h2>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=alumni-core-organization' ) ); ?>" class="button"><?php esc_html_e( '組織を管理', 'alumni-core' ); ?></a>
				</div>
				<div class="alumni-core-dashboard-stats">
					<?php $this->render_stat_card( __( '組織名簿一覧', 'alumni-core' ), $officer_stats['lists'], admin_url( 'admin.php?page=' . Officers_Page::SLUG ), 'groups' ); ?>
					<?php $this->render_stat_card( __( '登録人物', 'alumni-core' ), $officer_stats['rows'], admin_url( 'admin.php?page=' . Officers_Page::SLUG ), 'id' ); ?>
					<?php $this->render_stat_card( __( '同窓会組織図', 'alumni-core' ), $chart_count, admin_url( 'admin.php?page=' . Org_Chart_Page::SLUG ), 'share' ); ?>
				</div>
			</section>
		</div>
		<?php
	}

	/**
	 * Counts posts of a specific Alumni Core content kind.
	 *
	 * @param string $kind Content kind.
	 * @return int
	 */
	private function count_content_kind( $kind ) {
		$query = new \WP_Query(
			array(
				'post_type'              => Content_Post_Type::SLUG,
				'post_status'            => 'any',
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => false,
				'ignore_sticky_posts'    => true,
				'meta_query'             => array(
					array(
						'key'     => Content_Post_Type::META_KIND,
						'value'   => $kind,
						'compare' => '=',
					),
				),
			)
		);

		return (int) $query->found_posts;
	}

	/**
	 * Counts non-trash posts for one post type.
	 *
	 * @param string $post_type Post type slug.
	 * @return int
	 */
	private function count_post_type( $post_type ) {
		$counts = wp_count_posts( $post_type );
		$total  = 0;

		foreach ( (array) $counts as $status => $count ) {
			if ( 'trash' === $status || 'auto-draft' === $status ) {
				continue;
			}
			$total += (int) $count;
		}

		return $total;
	}

	/**
	 * Returns the number of organization lists and their member rows.
	 *
	 * @return array{lists:int,rows:int}
	 */
	private function get_officer_stats() {
		$lists = Officer_Lists::instance()->get_all();
		$rows  = 0;

		foreach ( $lists as $list ) {
			$rows += isset( $list['rows'] ) && is_array( $list['rows'] ) ? count( $list['rows'] ) : 0;
		}

		return array(
			'lists' => count( $lists ),
			'rows'  => $rows,
		);
	}

	/**
	 * Counts saved organization charts.
	 *
	 * @return int
	 */
	private function get_org_chart_count() {
		return count( Org_Chart::instance()->get_charts() );
	}

	/**
	 * Outputs one numeric dashboard card.
	 *
	 * @param string $label Card label.
	 * @param int    $count Registered count.
	 * @param string $url Management URL.
	 * @param string $icon Dashicon name without prefix.
	 */
	private function render_stat_card( $label, $count, $url, $icon ) {
		?>
		<a class="alumni-core-dashboard-stat-card" href="<?php echo esc_url( $url ); ?>">
			<span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span>
			<span class="alumni-core-dashboard-stat-value"><?php echo esc_html( number_format_i18n( $count ) ); ?></span>
			<span class="alumni-core-dashboard-stat-label"><?php echo esc_html( $label ); ?></span>
		</a>
		<?php
	}

	/**
	 * Converts empty values into the dashboard's standard unset label.
	 *
	 * @param mixed $value Value to display.
	 * @return string
	 */
	private function value_or_unset( $value ) {
		return '' !== $value && null !== $value ? (string) $value : __( '未設定', 'alumni-core' );
	}
}
