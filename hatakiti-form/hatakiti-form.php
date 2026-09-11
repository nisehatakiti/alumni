<?php
/**
 * Plugin Name: HATAKITI Form
 * Description: 汎用フォームエディタ。通常フォームとWordPress投稿の下書き作成フォームを提供し、外部プラグインの投稿スキーマ連携に対応します。
 * Version: 0.2.0
 * Requires at least: 5.9
 * Requires PHP: 7.4
 * Author: nisehatakiti
 * License: GPL-2.0-or-later
 * Text Domain: hatakiti-form
 */
if ( ! defined( 'ABSPATH' ) ) exit;
define( 'HATAKITI_FORM_VERSION', '0.2.0' );
define( 'HATAKITI_FORM_PATH', plugin_dir_path( __FILE__ ) );
require_once HATAKITI_FORM_PATH . 'includes/class-schema-registry.php';
require_once HATAKITI_FORM_PATH . 'includes/class-form-post-type.php';
require_once HATAKITI_FORM_PATH . 'includes/class-form-admin.php';
require_once HATAKITI_FORM_PATH . 'includes/class-form-public.php';
add_action( 'init', array( 'HATAKITI_Form_Post_Type', 'register' ) );
add_action( 'plugins_loaded', array( 'HATAKITI_Form_Schema_Registry', 'bootstrap' ), 20 );
if ( is_admin() ) HATAKITI_Form_Admin::register();
HATAKITI_Form_Public::register();
register_activation_hook( __FILE__, function(){ HATAKITI_Form_Post_Type::register(); flush_rewrite_rules(); } );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
