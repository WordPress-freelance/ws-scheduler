<?php
/**
 * Bootstrap PHPUnit + WP_Mock pour WS Scheduler.
 * Version complète — 100+ tests front + back.
 */

if ( ! defined( 'ABSPATH' ) )        define( 'ABSPATH', dirname( __DIR__ ) . '/' );
if ( ! defined( 'WPINC' ) )          define( 'WPINC', 'wp-includes' );
if ( ! defined( 'OBJECT' ) )         define( 'OBJECT', 'OBJECT' );
if ( ! defined( 'WP_CONTENT_DIR' ) ) define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );

define( 'WS_SCHEDULER_VERSION',         '4.0.0' );
define( 'WS_SCHEDULER_PLUGIN_DIR',      ABSPATH );
define( 'WS_SCHEDULER_PLUGIN_URL',      'http://example.com/wp-content/plugins/ws-scheduler/' );
define( 'WS_SCHEDULER_PLUGIN_BASENAME', 'ws-scheduler/ws-scheduler.php' );

require_once ABSPATH . 'vendor/autoload.php';
require_once ABSPATH . 'tests/helpers/class-mock-wpdb.php';

\WP_Mock::bootstrap();

// ── Stubs fonctions WP ─────────────────────────────────────────────

if ( ! function_exists( 'absint' ) ) {
    function absint( $v ) { return abs( (int) $v ); }
}
if ( ! function_exists( 'wp_unslash' ) ) {
    function wp_unslash( $v ) { return is_array( $v ) ? array_map( 'stripslashes', $v ) : stripslashes( $v ); }
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $s ) { return trim( strip_tags( (string) $s ) ); }
}
if ( ! function_exists( 'sanitize_textarea_field' ) ) {
    function sanitize_textarea_field( $s ) { return trim( (string) $s ); }
}
if ( ! function_exists( 'sanitize_email' ) ) {
    function sanitize_email( $e ) { return filter_var( trim( $e ), FILTER_SANITIZE_EMAIL ); }
}
if ( ! function_exists( 'sanitize_sql_orderby' ) ) {
    function sanitize_sql_orderby( $s ) { return preg_match( '/^[a-zA-Z0-9_, ]+$/', $s ) ? $s : ''; }
}
if ( ! function_exists( 'is_email' ) ) {
    function is_email( $e ) { return (bool) filter_var( $e, FILTER_VALIDATE_EMAIL ); }
}
if ( ! function_exists( 'wp_parse_args' ) ) {
    function wp_parse_args( $a, $d = [] ) { return array_merge( (array) $d, (array) $a ); }
}
if ( ! function_exists( 'trailingslashit' ) ) {
    function trailingslashit( $s ) { return rtrim( $s, '/\\' ) . '/'; }
}
if ( ! function_exists( 'plugin_dir_path' ) ) {
    function plugin_dir_path( $f ) { return trailingslashit( dirname( $f ) ); }
}
if ( ! function_exists( 'plugin_dir_url' ) ) {
    function plugin_dir_url( $f ) { return WS_SCHEDULER_PLUGIN_URL; }
}
if ( ! function_exists( 'plugin_basename' ) ) {
    function plugin_basename( $f ) { return 'ws-scheduler/ws-scheduler.php'; }
}
if ( ! function_exists( 'get_option' ) ) {
    function get_option( $k, $default = false ) { return $default; }
}
if ( ! function_exists( 'update_option' ) ) {
    function update_option( $k, $v, $a = false ) { return true; }
}
if ( ! function_exists( 'delete_option' ) ) {
    function delete_option( $k ) { return true; }
}
if ( ! function_exists( 'get_bloginfo' ) ) {
    function get_bloginfo( $s = '' ) { return 'Test Blog'; }
}
if ( ! function_exists( 'home_url' ) ) {
    function home_url( $p = '' ) { return 'http://example.com' . $p; }
}
if ( ! function_exists( 'admin_url' ) ) {
    function admin_url( $p = '' ) { return 'http://example.com/wp-admin/' . ltrim( $p, '/' ); }
}
if ( ! function_exists( 'wp_timezone_string' ) ) {
    function wp_timezone_string() { return 'UTC'; }
}
if ( ! function_exists( 'wp_mail' ) ) {
    function wp_mail( $to, $sub, $msg, $hdr = '' ) { return true; }
}
if ( ! function_exists( 'esc_url' ) ) {
    function esc_url( $u ) { return $u; }
}
if ( ! function_exists( 'esc_attr' ) ) {
    function esc_attr( $t ) { return htmlspecialchars( $t, ENT_QUOTES, 'UTF-8' ); }
}
if ( ! function_exists( 'esc_html' ) ) {
    function esc_html( $t ) { return htmlspecialchars( $t, ENT_QUOTES, 'UTF-8' ); }
}
if ( ! function_exists( 'esc_html__' ) ) {
    function esc_html__( $t, $d = 'default' ) { return htmlspecialchars( $t ); }
}
if ( ! function_exists( 'esc_url_raw' ) ) {
    function esc_url_raw( $u ) { return $u; }
}
if ( ! function_exists( '__' ) ) {
    function __( $t, $d = 'default' ) { return $t; }
}
if ( ! function_exists( 'sprintf' ) ) {} // built-in
if ( ! function_exists( 'current_user_can' ) ) {
    function current_user_can( $c ) { return true; }
}
if ( ! function_exists( 'check_admin_referer' ) ) {
    function check_admin_referer( $a, $q = '_wpnonce' ) { return true; }
}
if ( ! function_exists( 'check_ajax_referer' ) ) {
    function check_ajax_referer( $a, $q = false ) { return true; }
}
if ( ! function_exists( 'wp_create_nonce' ) ) {
    function wp_create_nonce( $a ) { return 'test_nonce'; }
}
if ( ! function_exists( 'wp_verify_nonce' ) ) {
    function wp_verify_nonce( $n, $a ) { return true; }
}
if ( ! function_exists( 'wp_nonce_field' ) ) {
    function wp_nonce_field( $a, $n = '_wpnonce', $ref = true, $echo = true ) { return ''; }
}
if ( ! function_exists( 'wp_send_json_success' ) ) {
    function wp_send_json_success( $d = null ) { /* stub */ }
}
if ( ! function_exists( 'wp_send_json_error' ) ) {
    function wp_send_json_error( $d = null ) { /* stub */ }
}
if ( ! function_exists( 'wp_die' ) ) {
    function wp_die( $m = '' ) { /* stub */ }
}
if ( ! function_exists( 'wp_redirect' ) ) {
    function wp_redirect( $u, $s = 302 ) { return true; }
}
if ( ! function_exists( 'wp_cache_flush' ) ) {
    function wp_cache_flush() { return true; }
}
if ( ! function_exists( 'apply_filters' ) ) {
    function apply_filters( $tag, $value ) { return $value; }
}
if ( ! function_exists( 'do_action' ) ) {
    function do_action( $tag ) { /* stub */ }
}
if ( ! function_exists( 'add_action' ) ) {
    function add_action( $h, $cb, $p = 10, $a = 1 ) { return true; }
}
if ( ! function_exists( 'add_filter' ) ) {
    function add_filter( $h, $cb, $p = 10, $a = 1 ) { return true; }
}
if ( ! function_exists( 'add_shortcode' ) ) {
    function add_shortcode( $t, $cb ) { return true; }
}
if ( ! function_exists( 'shortcode_atts' ) ) {
    function shortcode_atts( $pairs, $atts, $shortcode = '' ) {
        $atts = (array) $atts;
        $out  = [];
        foreach ( $pairs as $name => $default ) {
            $out[ $name ] = array_key_exists( $name, $atts ) ? $atts[ $name ] : $default;
        }
        return $out;
    }
}
if ( ! function_exists( 'register_activation_hook' ) ) {
    function register_activation_hook( $f, $cb ) { /* stub */ }
}
if ( ! function_exists( 'register_deactivation_hook' ) ) {
    function register_deactivation_hook( $f, $cb ) { /* stub */ }
}
if ( ! function_exists( 'load_plugin_textdomain' ) ) {
    function load_plugin_textdomain( $d, $p = false, $rel = false ) { /* stub */ }
}
if ( ! function_exists( 'get_current_screen' ) ) {
    function get_current_screen() { return null; }
}
if ( ! function_exists( 'add_menu_page' ) ) {
    function add_menu_page( $pt, $mt, $cap, $slug, $cb = '', $icon = '', $pos = null ) { return $slug; }
}
if ( ! function_exists( 'add_submenu_page' ) ) {
    function add_submenu_page( $parent, $pt, $mt, $cap, $slug, $cb = '' ) { return $slug; }
}
if ( ! function_exists( 'wp_enqueue_style' ) ) {
    function wp_enqueue_style() { /* stub */ }
}
if ( ! function_exists( 'wp_enqueue_script' ) ) {
    function wp_enqueue_script() { /* stub */ }
}
if ( ! function_exists( 'wp_register_script' ) ) {
    function wp_register_script() { /* stub */ }
}
if ( ! function_exists( 'wp_localize_script' ) ) {
    function wp_localize_script() { /* stub */ }
}
if ( ! function_exists( 'wp_add_inline_script' ) ) {
    function wp_add_inline_script() { /* stub */ }
}
if ( ! function_exists( 'is_admin' ) ) {
    function is_admin() { return false; }
}
if ( ! function_exists( 'dbDelta' ) ) {
    function dbDelta( $queries = '', $execute = true ) { return []; }
}

// ── Stubs classes WP ────────────────────────────────────────────────

if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        public $errors = [];
        public function __construct( $c = '', $m = '', $d = '' ) {
            if ( $c ) $this->errors[ $c ] = [ $m ];
        }
        public function get_error_message( $c = '' ) {
            return $c && isset( $this->errors[$c] ) ? $this->errors[$c][0] : '';
        }
    }
}
if ( ! class_exists( 'WP_Screen' ) ) {
    class WP_Screen { public $id = ''; }
}

// ── Charger toutes les classes du plugin ───────────────────────────

require_once ABSPATH . 'includes/class-ws-scheduler-loader.php';
require_once ABSPATH . 'includes/class-ws-scheduler-i18n.php';
require_once ABSPATH . 'includes/class-ws-scheduler-db.php';
require_once ABSPATH . 'includes/class-ws-scheduler-slots.php';
require_once ABSPATH . 'includes/class-ws-scheduler-email.php';
require_once ABSPATH . 'includes/class-ws-scheduler-ajax.php';
require_once ABSPATH . 'includes/class-ws-scheduler-activator.php';
require_once ABSPATH . 'includes/class-ws-scheduler-deactivator.php';
require_once ABSPATH . 'includes/class-ws-scheduler.php';
require_once ABSPATH . 'admin/class-ws-scheduler-admin.php';
require_once ABSPATH . 'public/class-ws-scheduler-public.php';
