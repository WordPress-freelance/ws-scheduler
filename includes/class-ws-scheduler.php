<?php
/**
 * The core plugin class.
 *
 * @since      3.0.0
 * @package    WS_Scheduler
 * @subpackage WS_Scheduler/includes
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WS_Scheduler {

	protected $loader;
	protected $plugin_name;
	protected $version;

	public function __construct() {
		$this->version     = defined( 'WS_SCHEDULER_VERSION' ) ? WS_SCHEDULER_VERSION : '4.0.7';
		$this->plugin_name = 'ws-scheduler';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	private function load_dependencies() {
		require_once WS_SCHEDULER_PLUGIN_DIR . 'includes/class-ws-scheduler-loader.php';
		require_once WS_SCHEDULER_PLUGIN_DIR . 'includes/class-ws-scheduler-i18n.php';
		require_once WS_SCHEDULER_PLUGIN_DIR . 'includes/class-ws-scheduler-activator.php';
		require_once WS_SCHEDULER_PLUGIN_DIR . 'includes/class-ws-scheduler-deactivator.php';
		require_once WS_SCHEDULER_PLUGIN_DIR . 'includes/class-ws-scheduler-db.php';
		require_once WS_SCHEDULER_PLUGIN_DIR . 'includes/class-ws-scheduler-slots.php';
		require_once WS_SCHEDULER_PLUGIN_DIR . 'includes/class-ws-scheduler-email.php';
		require_once WS_SCHEDULER_PLUGIN_DIR . 'includes/class-ws-scheduler-ajax.php';
		require_once WS_SCHEDULER_PLUGIN_DIR . 'includes/class-ws-scheduler-privacy.php';
		require_once WS_SCHEDULER_PLUGIN_DIR . 'admin/class-ws-scheduler-admin.php';
		require_once WS_SCHEDULER_PLUGIN_DIR . 'public/class-ws-scheduler-public.php';

		$this->loader = new WS_Scheduler_Loader();
	}


	private function set_locale() {
		$plugin_i18n = new WS_Scheduler_I18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	private function define_admin_hooks() {
		$plugin_admin = new WS_Scheduler_Admin( $this->plugin_name, $this->version );
		$plugin_ajax  = new WS_Scheduler_Ajax();

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu',            $plugin_admin, 'add_plugin_admin_menu' );
		$this->loader->add_filter( 'admin_body_class',      $plugin_admin, 'admin_body_class' );
		$this->loader->add_action( 'admin_head',            $plugin_admin, 'admin_head_reset' );
		$this->loader->add_action( 'admin_post_ws_scheduler_save_settings', $plugin_admin, 'save_settings' );
		$this->loader->add_filter( 'plugin_action_links_' . WS_SCHEDULER_PLUGIN_BASENAME, $plugin_admin, 'add_action_links' );

		$this->loader->add_action( 'wp_ajax_ws_update_appointment',   $plugin_ajax, 'update_appointment' );
		$this->loader->add_action( 'wp_ajax_ws_delete_appointment',   $plugin_ajax, 'delete_appointment' );
		$this->loader->add_action( 'wp_ajax_ws_add_unavailability',   $plugin_ajax, 'add_unavailability' );
		$this->loader->add_action( 'wp_ajax_ws_delete_unavailability', $plugin_ajax, 'delete_unavailability' );

		$this->loader->add_action( 'wp_ajax_ws_get_month_slots',        $plugin_ajax, 'get_month_slots' );
		$this->loader->add_action( 'wp_ajax_nopriv_ws_get_month_slots', $plugin_ajax, 'get_month_slots' );
		$this->loader->add_action( 'wp_ajax_ws_get_available_slots',        $plugin_ajax, 'get_available_slots' );
		$this->loader->add_action( 'wp_ajax_nopriv_ws_get_available_slots', $plugin_ajax, 'get_available_slots' );
		$this->loader->add_action( 'wp_ajax_ws_book_appointment',        $plugin_ajax, 'book_appointment' );
		$this->loader->add_action( 'wp_ajax_nopriv_ws_book_appointment', $plugin_ajax, 'book_appointment' );
	}

	private function define_public_hooks() {
		$plugin_public = new WS_Scheduler_Public( $this->plugin_name, $this->version );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_action( 'wp_footer',          $plugin_public, 'render_popup_once' );
		$this->loader->add_shortcode( 'ws_booking_button', $plugin_public, 'render_booking_button' );

		// Exclut notre <script> des combineurs/minifieurs de cache plugins
		// (LiteSpeed Cache, WP Rocket, Autoptimize, etc.). Le JS est déjà
		// inliné — pas besoin que les plugins de cache le combinent avec
		// d'autres scripts inline, ce qui causait des erreurs de syntaxe
		// "missing } after function body" sur le bundle minifié final.
		$this->loader->add_filter( 'script_loader_tag', $plugin_public, 'mark_script_no_optimize', 10, 2 );

		// Privacy / RGPD : exporter, eraser et texte suggéré pour la politique
		// de confidentialité du site. Le plugin stocke des PII (email, nom,
		// téléphone, message) — guideline 7 du Plugin Directory.
		$this->loader->add_filter( 'wp_privacy_personal_data_exporters', 'WS_Scheduler_Privacy', 'register_exporter' );
		$this->loader->add_filter( 'wp_privacy_personal_data_erasers',   'WS_Scheduler_Privacy', 'register_eraser' );
		$this->loader->add_action( 'admin_init',                          'WS_Scheduler_Privacy', 'add_privacy_policy_content' );
	}

	public function run() {
		$this->loader->run();
	}

	public function get_plugin_name() { return $this->plugin_name; }
	public function get_loader()      { return $this->loader; }
	public function get_version()     { return $this->version; }
}
