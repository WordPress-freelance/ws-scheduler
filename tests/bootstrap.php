<?php
/**
 * Bootstrap PHPUnit + WP_Mock — WS Scheduler.
 *
 * RÈGLE : ne jamais pré-définir les fonctions WP_API ici.
 * WP_Mock (Brain\Monkey) ne peut pas redéfinir une fonction PHP
 * déjà déclarée → les assertions times=>N ne voient jamais les appels.
 * Seules les fonctions utilitaires pures (sanitize_*, esc_*, __) peuvent
 * être définies ici car elles ne sont jamais mockées avec times.
 */

if ( ! defined( 'ABSPATH' ) )        define( 'ABSPATH', dirname( __DIR__ ) . '/' );
if ( ! defined( 'WPINC' ) )          define( 'WPINC', 'wp-includes' );
if ( ! defined( 'OBJECT' ) )         define( 'OBJECT', 'OBJECT' );
if ( ! defined( 'WP_CONTENT_DIR' ) ) define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );

define( 'WS_SCHEDULER_VERSION',         '4.0.7' );
define( 'WS_SCHEDULER_PLUGIN_DIR',      ABSPATH );
define( 'WS_SCHEDULER_PLUGIN_URL',      'http://example.com/wp-content/plugins/ws-scheduler/' );
define( 'WS_SCHEDULER_PLUGIN_BASENAME', 'ws-scheduler/ws-scheduler.php' );

// ── Créer le stub wp-admin/includes/upgrade.php ──────────────────────
// L'Activator fait require_once de ce fichier pour charger dbDelta().
// On le crée dans /tmp pour ne pas polluer le repo.
$_ws_upgrade_dir = sys_get_temp_dir() . '/wp-admin/includes';
if ( ! is_dir( $_ws_upgrade_dir ) ) {
    mkdir( $_ws_upgrade_dir, 0777, true );
}
$_ws_upgrade_file = $_ws_upgrade_dir . '/upgrade.php';
if ( ! file_exists( $_ws_upgrade_file ) ) {
    file_put_contents( $_ws_upgrade_file, "<?php\n// WP upgrade stub for tests\nif (!function_exists('dbDelta')) { function dbDelta(\$q='',\$e=true) { return []; } }\n" );
}

// Redéfinir ABSPATH pour l'Activator (pointe vers le tempdir pour upgrade.php)
// On garde le vrai ABSPATH mais on symlinke wp-admin dans le répertoire du projet
$_ws_wpadmin_dir = ABSPATH . 'wp-admin/includes';
if ( ! is_dir( $_ws_wpadmin_dir ) ) {
    mkdir( $_ws_wpadmin_dir, 0777, true );
}
if ( ! file_exists( ABSPATH . 'wp-admin/includes/upgrade.php' ) ) {
    file_put_contents(
        ABSPATH . 'wp-admin/includes/upgrade.php',
        "<?php\n// WP upgrade stub for tests\nif (!function_exists('dbDelta')) { function dbDelta(\$q='',\$e=true) { return []; } }\n"
    );
}

require_once ABSPATH . 'vendor/autoload.php';
require_once ABSPATH . 'tests/helpers/class-mock-wpdb.php';

\WP_Mock::bootstrap();

// ── Fonctions utilitaires pures (jamais mockées avec times) ───────────
// Comportement déterministe, aucune logique WP — on peut les pré-définir.

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
if ( ! function_exists( 'esc_url' ) ) {
    function esc_url( $u ) { return $u; }
}
if ( ! function_exists( 'esc_url_raw' ) ) {
    function esc_url_raw( $u ) { return $u; }
}
if ( ! function_exists( 'esc_attr' ) ) {
    function esc_attr( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES, 'UTF-8' ); }
}
if ( ! function_exists( 'esc_html' ) ) {
    function esc_html( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES, 'UTF-8' ); }
}
if ( ! function_exists( 'esc_html__' ) ) {
    function esc_html__( $t, $d = 'default' ) { return htmlspecialchars( (string) $t ); }
}
if ( ! function_exists( '__' ) ) {
    function __( $t, $d = 'default' ) { return $t; }
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

// ── Stubs classes WP ───────────────────────────────────────────────────

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

// ── Charger toutes les classes du plugin ───────────────────────────────

require_once ABSPATH . 'includes/class-ws-scheduler-loader.php';
require_once ABSPATH . 'includes/class-ws-scheduler-i18n.php';
require_once ABSPATH . 'includes/class-ws-scheduler-db.php';
require_once ABSPATH . 'includes/class-ws-scheduler-slots.php';
require_once ABSPATH . 'includes/class-ws-scheduler-email.php';
require_once ABSPATH . 'includes/class-ws-scheduler-ajax.php';
require_once ABSPATH . 'includes/class-ws-scheduler-activator.php';
require_once ABSPATH . 'includes/class-ws-scheduler-deactivator.php';
require_once ABSPATH . 'includes/class-ws-scheduler-privacy.php';
require_once ABSPATH . 'includes/class-ws-scheduler.php';
require_once ABSPATH . 'admin/class-ws-scheduler-admin.php';
require_once ABSPATH . 'public/class-ws-scheduler-public.php';
