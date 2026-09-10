<?php
/**
 * 校歌・校訓の公開ページショートコードと自動ページ管理.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class School_Song_Motto_Shortcode {

	const OPTION_COMBINED_PAGE_ID = 'alumni_core_school_song_motto_page_id';
	const OPTION_SONG_PAGE_ID     = 'alumni_core_school_song_page_id';
	const OPTION_MOTTO_PAGE_ID    = 'alumni_core_school_motto_page_id';

	const COMBINED_SLUG = 'school-song-motto';
	const SONG_SLUG     = 'school-song';
	const MOTTO_SLUG    = 'school-motto';

	const SHORTCODE_COMBINED = 'alumni_school_song_motto';
	const SHORTCODE_SONG     = 'alumni_school_song';
	const SHORTCODE_MOTTO    = 'alumni_school_motto';

	public static function register() {
		add_shortcode( self::SHORTCODE_COMBINED, array( __CLASS__, 'render_combined' ) );
		add_shortcode( self::SHORTCODE_SONG, array( __CLASS__, 'render_song' ) );
		add_shortcode( self::SHORTCODE_MOTTO, array( __CLASS__, 'render_motto' ) );

		if ( is_admin() ) {
			add_action( 'admin_init', array( __CLASS__, 'maybe_create_pages' ) );
		}
	}

	public static function maybe_create_pages() {
		$mode = self::get_display_mode();
		if ( 'separate' === $mode ) {
			self::ensure_page( self::OPTION_SONG_PAGE_ID, self::SONG_SLUG, self::get_title( 'song' ), '[' . self::SHORTCODE_SONG . ']' );
			self::ensure_page( self::OPTION_MOTTO_PAGE_ID, self::MOTTO_SLUG, self::get_title( 'motto' ), '[' . self::SHORTCODE_MOTTO . ']' );
		} else {
			self::ensure_page( self::OPTION_COMBINED_PAGE_ID, self::COMBINED_SLUG, self::get_title( 'combined' ), '[' . self::SHORTCODE_COMBINED . ']' );
		}
	}

	private static function ensure_page( $option_name, $slug, $title, $content ) {
		$page_id = (int) get_option( $option_name, 0 );
		if ( $page_id && 'page' === get_post_type( $page_id ) ) {
			if ( get_the_title( $page_id ) !== $title ) {
				wp_update_post( array( 'ID' => $page_id, 'post_title' => $title ) );
			}
			return $page_id;
		}
		$existing = get_page_by_path( $slug, OBJECT, 'page' );
		if ( $existing ) {
			update_option( $option_name, $existing->ID );
			return $existing->ID;
		}
		$new_id = wp_insert_post( array(
			'post_type' => 'page',
			'post_status' => 'publish',
			'post_title' => $title,
			'post_name' => $slug,
			'post_content' => $content,
		), true );
		if ( ! is_wp_error( $new_id ) && $new_id ) {
			update_option( $option_name, $new_id );
			return (int) $new_id;
		}
		return 0;
	}

	public static function get_display_mode() {
		$mode = (string) get_option( 'alumni_core_school_song_motto_display_mode', 'combined' );
		return in_array( $mode, array( 'combined', 'separate' ), true ) ? $mode : 'combined';
	}

	public static function get_titles() {
		$titles = get_option( 'alumni_core_school_song_motto_page_titles', array() );
		$titles = is_array( $titles ) ? $titles : array();
		return wp_parse_args( $titles, array(
			'combined' => __( '校歌・校訓', 'alumni-core' ),
			'song'     => __( '校歌', 'alumni-core' ),
			'motto'    => __( '校訓', 'alumni-core' ),
		) );
	}

	public static function get_title( $key ) {
		$titles = self::get_titles();
		return isset( $titles[ $key ] ) ? $titles[ $key ] : '';
	}

	public static function get_url( $key = 'combined' ) {
		if ( 'combined' === $key && 'separate' === self::get_display_mode() ) {
			return '';
		}
		if ( in_array( $key, array( 'song', 'motto' ), true ) && 'separate' !== self::get_display_mode() ) {
			return '';
		}
		$map = array(
			'combined' => array( self::OPTION_COMBINED_PAGE_ID, self::COMBINED_SLUG ),
			'song'     => array( self::OPTION_SONG_PAGE_ID, self::SONG_SLUG ),
			'motto'    => array( self::OPTION_MOTTO_PAGE_ID, self::MOTTO_SLUG ),
		);
		if ( ! isset( $map[ $key ] ) ) {
			return '';
		}
		$page_id = (int) get_option( $map[ $key ][0], 0 );
		if ( $page_id && 'page' === get_post_type( $page_id ) ) {
			return (string) get_permalink( $page_id );
		}
		return home_url( '/' . $map[ $key ][1] . '/' );
	}

	public static function render_combined() {
		return self::render_song_section() . self::render_motto_section();
	}

	public static function render_song() {
		return self::render_song_section();
	}

	public static function render_motto() {
		return self::render_motto_section();
	}

	private static function render_song_section() {
		$data = self::get_song_data();
		if ( ! self::has_song_content( $data ) ) {
			return '';
		}
		ob_start();
		?>
		<section class="alumni-school-song">
			<h2><?php echo esc_html( $data['title'] ? $data['title'] : self::get_title( 'song' ) ); ?></h2>
			<?php if ( $data['lyricist'] || $data['composer'] ) : ?>
				<p class="alumni-school-song-credits">
					<?php if ( $data['lyricist'] ) : ?><span><?php echo esc_html( sprintf( __( '作詞：%s', 'alumni-core' ), $data['lyricist'] ) ); ?></span><?php endif; ?>
					<?php if ( $data['composer'] ) : ?><span><?php echo esc_html( sprintf( __( '作曲：%s', 'alumni-core' ), $data['composer'] ) ); ?></span><?php endif; ?>
				</p>
			<?php endif; ?>
			<?php if ( $data['lyrics'] ) : ?><div class="alumni-school-song-lyrics"><?php echo nl2br( esc_html( $data['lyrics'] ) ); ?></div><?php endif; ?>
			<?php if ( $data['audio_url'] ) : ?><div class="alumni-school-song-audio"><audio controls preload="metadata" src="<?php echo esc_url( $data['audio_url'] ); ?>"></audio></div><?php endif; ?>
			<?php if ( $data['sheet_url'] ) : ?><p class="alumni-school-song-sheet"><a href="<?php echo esc_url( $data['sheet_url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( '楽譜を見る', 'alumni-core' ); ?></a></p><?php endif; ?>
		</section>
		<?php
		return ob_get_clean();
	}

	private static function render_motto_section() {
		$data = self::get_motto_data();
		if ( ! self::has_motto_content( $data ) ) {
			return '';
		}
		ob_start();
		?>
		<section class="alumni-school-motto">
			<h2><?php echo esc_html( self::get_title( 'motto' ) ); ?></h2>
			<?php if ( in_array( $data['display_type'], array( 'text', 'both' ), true ) && $data['text'] ) : ?><div class="alumni-school-motto-text"><?php echo nl2br( esc_html( $data['text'] ) ); ?></div><?php endif; ?>
			<?php if ( in_array( $data['display_type'], array( 'image', 'both' ), true ) && $data['image_id'] ) : ?><div class="alumni-school-motto-image"><?php echo wp_get_attachment_image( $data['image_id'], 'large', false, array( 'loading' => 'lazy' ) ); ?></div><?php endif; ?>
			<?php if ( $data['description'] ) : ?><div class="alumni-school-motto-description"><?php echo wpautop( wp_kses_post( $data['description'] ) ); ?></div><?php endif; ?>
		</section>
		<?php
		return ob_get_clean();
	}

	public static function get_song_data() {
		$data = get_option( 'alumni_core_school_song_data', array() );
		$data = is_array( $data ) ? $data : array();
		$data = wp_parse_args( $data, array( 'title' => '', 'lyricist' => '', 'composer' => '', 'lyrics' => '', 'audio_id' => 0, 'sheet_id' => 0 ) );
		$data['audio_id'] = absint( $data['audio_id'] );
		$data['sheet_id'] = absint( $data['sheet_id'] );
		$data['audio_url'] = $data['audio_id'] ? (string) wp_get_attachment_url( $data['audio_id'] ) : '';
		$data['sheet_url'] = $data['sheet_id'] ? (string) wp_get_attachment_url( $data['sheet_id'] ) : '';
		return $data;
	}

	public static function get_motto_data() {
		$data = get_option( 'alumni_core_school_motto_data', array() );
		$data = is_array( $data ) ? $data : array();
		$data = wp_parse_args( $data, array( 'display_type' => 'text', 'text' => '', 'image_id' => 0, 'description' => '' ) );
		$data['display_type'] = in_array( $data['display_type'], array( 'text', 'image', 'both' ), true ) ? $data['display_type'] : 'text';
		$data['image_id'] = absint( $data['image_id'] );
		return $data;
	}

	private static function has_song_content( $data ) {
		return (bool) ( $data['title'] || $data['lyricist'] || $data['composer'] || $data['lyrics'] || $data['audio_url'] || $data['sheet_url'] );
	}

	private static function has_motto_content( $data ) {
		return (bool) ( $data['text'] || $data['image_id'] || $data['description'] );
	}
}
