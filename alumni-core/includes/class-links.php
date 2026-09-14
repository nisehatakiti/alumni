<?php
/**
 * Mother-school homepage and external links directory.
 *
 * @package AlumniCore
 */
namespace AlumniCore\Includes;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Provides the mother-school homepage setting and a categorized public links page.
 */
class Links {
	const OPTION_LINKS = 'alumni_core_links';
	const OPTION_SCHOOL_HOME = 'alumni_core_school_home_url';
	const PAGE_ID_OPTION = 'alumni_core_links_page_id';
	const PAGE_SLUG = 'alumni-links';
	const SHORTCODE = 'alumni_links';
	const SYSTEM_SCHOOL_HOME = 'school_homepage';
	const SYSTEM_LINKS = 'links';
	const ADMIN_SLUG = 'alumni-core-links';
	const SAVE_ACTION = 'alumni_core_save_links';
	const SAVE_SCHOOL_ACTION = 'alumni_core_save_school_homepage';
	const NONCE_ACTION = 'alumni_core_links_nonce';

	public static function register() {
		add_shortcode( self::SHORTCODE, array( __CLASS__, 'render_shortcode' ) );
		add_filter( 'alumni_core_system_content_keys', array( __CLASS__, 'filter_system_keys' ) );
		add_filter( 'alumni_core_system_content_labels', array( __CLASS__, 'filter_system_labels' ) );
		add_filter( 'alumni_core_system_content_groups', array( __CLASS__, 'filter_system_groups' ) );
		add_filter( 'alumni_core_system_content_url', array( __CLASS__, 'filter_system_url' ), 10, 2 );
		if ( is_admin() ) {
			add_action( 'admin_init', array( __CLASS__, 'maybe_create_page' ) );
			add_action( 'admin_notices', array( __CLASS__, 'render_school_homepage_notice' ) );
			add_action( 'admin_post_' . self::SAVE_ACTION, array( __CLASS__, 'handle_save_links' ) );
			add_action( 'admin_post_' . self::SAVE_SCHOOL_ACTION, array( __CLASS__, 'handle_save_school_homepage' ) );
			add_action( 'alumni_core_register_admin_pages', array( __CLASS__, 'register_admin_page' ), 10, 2 );
		}
	}

	public static function filter_system_keys( $keys ) {
		$keys = is_array( $keys ) ? $keys : array();
		if ( self::get_school_home_url() ) $keys[] = self::SYSTEM_SCHOOL_HOME;
		$keys[] = self::SYSTEM_LINKS;
		return array_values( array_unique( $keys ) );
	}

	public static function filter_system_labels( $labels ) {
		$labels = is_array( $labels ) ? $labels : array();
		$labels[ self::SYSTEM_SCHOOL_HOME ] = __( '母校ホームページ', 'alumni-core' );
		$labels[ self::SYSTEM_LINKS ] = __( 'リンク集', 'alumni-core' );
		return $labels;
	}

	public static function filter_system_groups( $groups ) {
		$groups = is_array( $groups ) ? $groups : array();
		$groups[ self::SYSTEM_SCHOOL_HOME ] = __( '基本情報', 'alumni-core' );
		$groups[ self::SYSTEM_LINKS ] = __( 'リンク', 'alumni-core' );
		return $groups;
	}

	public static function filter_system_url( $url, $system_key ) {
		if ( self::SYSTEM_SCHOOL_HOME === $system_key ) return self::get_school_home_url();
		if ( self::SYSTEM_LINKS === $system_key ) return self::get_page_url();
		return $url;
	}

	public static function get_school_home_url() {
		return esc_url_raw( (string) get_option( self::OPTION_SCHOOL_HOME, '' ) );
	}

	public static function get_groups() {
		$saved = get_option( self::OPTION_LINKS, array() );
		$saved = is_array( $saved ) ? $saved : array();
		$groups = array();
		foreach ( $saved as $group ) {
			if ( ! is_array( $group ) ) continue;
			$title = isset( $group['title'] ) ? sanitize_text_field( $group['title'] ) : '';
			$items = array();
			$raw_items = isset( $group['items'] ) && is_array( $group['items'] ) ? $group['items'] : array();
			foreach ( $raw_items as $item ) {
				if ( ! is_array( $item ) ) continue;
				$item_title = isset( $item['title'] ) ? sanitize_text_field( $item['title'] ) : '';
				$item_url = isset( $item['url'] ) ? esc_url_raw( $item['url'] ) : '';
				if ( '' !== $item_title && '' !== $item_url ) $items[] = array( 'title' => $item_title, 'url' => $item_url );
			}
			if ( '' !== $title || ! empty( $items ) ) $groups[] = array( 'title' => $title, 'items' => $items );
		}
		return $groups;
	}

	public static function maybe_create_page() {
		$page_id = absint( get_option( self::PAGE_ID_OPTION, 0 ) );
		if ( $page_id && 'page' === get_post_type( $page_id ) ) return;
		$existing = get_page_by_path( self::PAGE_SLUG, OBJECT, 'page' );
		if ( $existing ) { update_option( self::PAGE_ID_OPTION, $existing->ID ); return; }
		$new_id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => __( 'リンク集', 'alumni-core' ), 'post_name' => self::PAGE_SLUG, 'post_content' => '[' . self::SHORTCODE . ']' ), true );
		if ( ! is_wp_error( $new_id ) && $new_id ) update_option( self::PAGE_ID_OPTION, $new_id );
	}

	public static function get_page_url() {
		$page_id = absint( get_option( self::PAGE_ID_OPTION, 0 ) );
		if ( $page_id && 'page' === get_post_type( $page_id ) ) return (string) get_permalink( $page_id );
		return home_url( '/' . self::PAGE_SLUG . '/' );
	}

	public static function render_shortcode() {
		$groups = self::get_groups();
		ob_start();
		?>
		<div class="alumni-links-directory">
		<?php if ( empty( $groups ) ) : ?>
			<p class="alumni-notice"><?php esc_html_e( '現在、登録されているリンクはありません。', 'alumni-core' ); ?></p>
		<?php else : foreach ( $groups as $group ) : ?>
			<section class="alumni-links-group">
				<?php if ( '' !== $group['title'] ) : ?><h2 class="alumni-links-group-title"><?php echo esc_html( $group['title'] ); ?></h2><?php endif; ?>
				<?php if ( ! empty( $group['items'] ) ) : ?><ul class="alumni-links-list">
					<?php foreach ( $group['items'] as $item ) : ?><li class="alumni-links-item"><a href="<?php echo esc_url( $item['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $item['title'] ); ?></a></li><?php endforeach; ?>
				</ul><?php endif; ?>
			</section>
		<?php endforeach; endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	public static function register_admin_page( $parent_slug, $capability ) {
		add_submenu_page( $parent_slug, __( 'リンク集', 'alumni-core' ), __( 'リンク集', 'alumni-core' ), $capability, self::ADMIN_SLUG, array( __CLASS__, 'render_admin_page' ) );
	}

	public static function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) return;
		$groups = self::get_groups();
		?>
		<div class="wrap alumni-core-wrap">
			<h1><?php esc_html_e( 'リンク集', 'alumni-core' ); ?></h1>
			<p><?php esc_html_e( '関連団体や卒業生に関する外部サイトを、見出しごとに整理して登録します。', 'alumni-core' ); ?></p>
			<?php if ( isset( $_GET['updated'] ) && 'true' === $_GET['updated'] ) : ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'リンク集を保存しました。', 'alumni-core' ); ?></p></div><?php endif; ?>
			<p><?php esc_html_e( '入力形式：見出しは [見出し名]、その下に「リンク名 | URL」を1行ずつ入力してください。空行を入れて次の見出しを続けられます。', 'alumni-core' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::SAVE_ACTION ); ?>" />
				<?php wp_nonce_field( self::NONCE_ACTION ); ?>
				<textarea name="links_text" rows="24" class="large-text code" placeholder="[関連団体]\n○○県同窓会 | https://example.com/\n○○高校PTA | https://example.com/\n\n[卒業生関連]\n○○先輩の会社 | https://example.com/"><?php echo esc_textarea( self::groups_to_text( $groups ) ); ?></textarea>
				<?php submit_button( __( 'リンク集を保存', 'alumni-core' ) ); ?>
			</form>
		</div>
		<?php
	}

	private static function groups_to_text( $groups ) {
		$lines = array();
		foreach ( $groups as $group ) {
			if ( '' !== $group['title'] ) $lines[] = '[' . $group['title'] . ']';
			foreach ( $group['items'] as $item ) $lines[] = $item['title'] . ' | ' . $item['url'];
			$lines[] = '';
		}
		return trim( implode( "\n", $lines ) );
	}

	private static function text_to_groups( $text ) {
		$groups = array(); $current = null;
		$lines = preg_split( '/\r\n|\r|\n/', (string) $text );
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line ) continue;
			if ( preg_match( '/^\[(.+)\]$/', $line, $matches ) ) {
				if ( is_array( $current ) ) $groups[] = $current;
				$current = array( 'title' => sanitize_text_field( $matches[1] ), 'items' => array() );
				continue;
			}
			$parts = explode( '|', $line, 2 );
			if ( 2 !== count( $parts ) ) continue;
			$title = sanitize_text_field( trim( $parts[0] ) );
			$url = esc_url_raw( trim( $parts[1] ) );
			if ( '' === $title || '' === $url ) continue;
			if ( ! is_array( $current ) ) $current = array( 'title' => '', 'items' => array() );
			$current['items'][] = array( 'title' => $title, 'url' => $url );
		}
		if ( is_array( $current ) ) $groups[] = $current;
		return $groups;
	}

	public static function handle_save_links() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( '権限がありません。', 'alumni-core' ) );
		check_admin_referer( self::NONCE_ACTION );
		$text = isset( $_POST['links_text'] ) ? wp_unslash( $_POST['links_text'] ) : '';
		update_option( self::OPTION_LINKS, self::text_to_groups( $text ) );
		wp_safe_redirect( add_query_arg( array( 'page' => self::ADMIN_SLUG, 'updated' => 'true' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function render_school_homepage_notice() {
		if ( ! current_user_can( 'manage_options' ) || ! isset( $_GET['page'] ) || 'alumni-core-settings' !== sanitize_key( wp_unslash( $_GET['page'] ) ) ) return;
		if ( isset( $_GET['school_home_updated'] ) && 'true' === $_GET['school_home_updated'] ) echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( '母校ホームページを保存しました。', 'alumni-core' ) . '</p></div>';
		?>
		<div class="notice" style="padding:16px;">
			<h2 style="margin-top:0;"><?php esc_html_e( '母校ホームページ', 'alumni-core' ); ?></h2>
			<p><?php esc_html_e( '母校の公式ホームページURLを設定します。設定すると、トップページ・メニュー構成から「母校ホームページ」を独立したシステムコンテンツとして選択できます。', 'alumni-core' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::SAVE_SCHOOL_ACTION ); ?>" />
				<?php wp_nonce_field( self::NONCE_ACTION ); ?>
				<input type="url" name="school_home_url" class="regular-text" value="<?php echo esc_attr( self::get_school_home_url() ); ?>" placeholder="https://www.example.ed.jp/" />
				<?php submit_button( __( '母校ホームページを保存', 'alumni-core' ), 'secondary', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}

	public static function handle_save_school_homepage() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( '権限がありません。', 'alumni-core' ) );
		check_admin_referer( self::NONCE_ACTION );
		$url = isset( $_POST['school_home_url'] ) ? esc_url_raw( wp_unslash( $_POST['school_home_url'] ) ) : '';
		update_option( self::OPTION_SCHOOL_HOME, $url );
		wp_safe_redirect( add_query_arg( array( 'page' => 'alumni-core-settings', 'school_home_updated' => 'true' ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
