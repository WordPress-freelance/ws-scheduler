<?php
/**
 * Bootstrap pour les tests PHPUnit avec WP_Mock.
 *
 * Initialise les constantes WordPress, charge WP_Mock, et définit
 * les stubs pour les fonctions WP qui n'ont pas besoin d'être mockées.
 */

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', dirname( dirname( __FILE__ ) ) . '/' );
}

if ( ! defined( 'WPINC' ) ) {
    define( 'WPINC', 'wp-includes' );
}

define( 'WS_SCHEDULER_VERSION',         '4.0.0' );
define( 'WS_SCHEDULER_PLUGIN_DIR',      ABSPATH );
define( 'WS_SCHEDULER_PLUGIN_URL',      'http://example.com/wp-content/plugins/ws-scheduler/' );
define( 'WS_SCHEDULER_PLUGIN_BASENAME', 'ws-scheduler/ws-scheduler.php' );

// Charger WP_Mock
require_once ABSPATH . 'vendor/autoload.php';
\WP_Mock::bootstrap();

// Stubs pour les fonctions WP simples
if ( ! function_exists( 'absint' ) ) {
    function absint( $maybeint ) {
        return abs( (int) $maybeint );
    }
}

if ( ! function_exists( 'wp_unslash' ) ) {
    function wp_unslash( $value ) {
        return stripslashes_deep( $value );
    }
}

if ( ! function_exists( 'stripslashes_deep' ) ) {
    function stripslashes_deep( $value ) {
        if ( is_array( $value ) ) {
            return array_map( 'stripslashes_deep', $value );
        }
        return stripslashes( $value );
    }
}

if ( ! function_exists( 'plugin_dir_path' ) ) {
    function plugin_dir_path( $file ) {
        return dirname( $file ) . '/';
    }
}

if ( ! function_exists( 'plugin_dir_url' ) ) {
    function plugin_dir_url( $file ) {
        return 'http://example.com/wp-content/plugins/ws-scheduler/';
    }
}

if ( ! function_exists( 'plugin_basename' ) ) {
    function plugin_basename( $file ) {
        return 'ws-scheduler/ws-scheduler.php';
    }
}

if ( ! function_exists( 'get_option' ) ) {
    function get_option( $option, $default = false ) {
        return $default;
    }
}

if ( ! function_exists( 'update_option' ) ) {
    function update_option( $option, $value ) {
        return true;
    }
}

if ( ! function_exists( 'get_bloginfo' ) ) {
    function get_bloginfo( $show = '' ) {
        return 'Test Blog';
    }
}

if ( ! function_exists( 'esc_url_raw' ) ) {
    function esc_url_raw( $url ) {
        return $url;
    }
}

if ( ! function_exists( 'esc_attr' ) ) {
    function esc_attr( $text ) {
        return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
    }
}

if ( ! function_exists( '__' ) ) {
    function __( $text, $domain = 'default' ) {
        return $text;
    }
}

if ( ! function_exists( 'esc_html__' ) ) {
    function esc_html__( $text, $domain = 'default' ) {
        return htmlspecialchars( $text );
    }
}

// Stubs pour les classes WP
if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        public $errors = [];

        public function __construct( $code = '', $message = '', $data = '' ) {
            if ( empty( $code ) ) {
                return;
            }
            $this->errors[ $code ] = [ $message ];
        }
    }
}

if ( ! class_exists( 'WP_Screen' ) ) {
    class WP_Screen {
        public $id = '';
    }
}

// Charger les classes du plugin
require_once ABSPATH . 'includes/class-ws-scheduler-loader.php';
require_once ABSPATH . 'includes/class-ws-scheduler-i18n.php';
require_once ABSPATH . 'includes/class-ws-scheduler.php';
