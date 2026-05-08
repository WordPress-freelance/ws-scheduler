<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      3.0.0
 * @package    WS_Scheduler
 * @subpackage WS_Scheduler/admin
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WS_Scheduler_Admin {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	public function enqueue_styles( $hook_suffix ) {
		$allowed = array(
			'toplevel_page_ws-scheduler',
			'ws-scheduler_page_ws-scheduler-unavail',
			'ws-scheduler_page_ws-scheduler-settings',
			'ws-scheduler_page_ws-scheduler-licence',
		);
		if ( ! in_array( $hook_suffix, $allowed, true ) ) return;

		/**
		 * Filter: ws_scheduler_load_google_fonts
		 * Set to false to prevent loading Lora + Inter from Google Fonts CDN
		 * (useful for GDPR-strict environments — bundle the fonts locally yourself).
		 *
		 * @param bool $load Default true.
		 */
		if ( apply_filters( 'ws_scheduler_load_google_fonts', true ) ) {
			wp_enqueue_style(
				'ws-scheduler-fonts',
				'https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,500;0,600;1,400&family=Inter:wght@400;500&display=swap',
				array(), null
			);
		}
		wp_enqueue_style(
			$this->plugin_name,
			WS_SCHEDULER_PLUGIN_URL . 'admin/css/ws-scheduler-admin.css',
			array(), $this->version, 'all'
		);
	}

	public function enqueue_scripts( $hook_suffix ) {
		$allowed = array(
			'toplevel_page_ws-scheduler',
			'ws-scheduler_page_ws-scheduler-unavail',
			'ws-scheduler_page_ws-scheduler-settings',
			'ws-scheduler_page_ws-scheduler-licence',
		);
		if ( ! in_array( $hook_suffix, $allowed, true ) ) return;

		wp_enqueue_script(
			$this->plugin_name,
			WS_SCHEDULER_PLUGIN_URL . 'admin/js/ws-scheduler-admin.js',
			array( 'jquery' ), $this->version, true
		);
		wp_localize_script( $this->plugin_name, 'wsSchedulerAdmin', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'ws_scheduler_admin_nonce' ),
			'i18n'     => array(
				'confirm_cancel' => __( 'Annuler ce rendez-vous ? Le client sera notifié par email.', 'ws-scheduler' ),
				'confirm_delete' => __( 'Supprimer définitivement ? Le client sera notifié si le RDV était actif.', 'ws-scheduler' ),
				'confirm_unavail'=> __( 'Supprimer cette indisponibilité ?', 'ws-scheduler' ),
				'dates_required' => __( 'Renseignez les deux dates.', 'ws-scheduler' ),
				'days_required'  => __( 'Sélectionnez au moins un jour.', 'ws-scheduler' ),
			),
		) );
	}

	public function admin_body_class( $classes ) {
		$screen = get_current_screen();
		if ( $screen && strpos( $screen->id, 'ws-scheduler' ) !== false ) {
			$classes .= ' ws-scheduler-page';
		}
		return $classes;
	}

	public function admin_head_reset() {
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'ws-scheduler' ) === false ) return;
		echo '<style>
		.ws-scheduler-page #wpwrap,
		.ws-scheduler-page #wpcontent,
		.ws-scheduler-page #wpbody-content { background: #14121C !important; }
		.ws-scheduler-page .wrap { margin:0;padding:0;background:transparent;max-width:none; }
		.ws-scheduler-page #wpbody-content > .notice,
		.ws-scheduler-page #wpbody-content > .updated { display:none !important; }
		</style>';
	}

	public function add_plugin_admin_menu() {
		add_menu_page(
			'WS Scheduler',
			'WS Scheduler',
			'manage_options',
			'ws-scheduler',
			array( $this, 'render_dashboard' ),
			'dashicons-calendar-alt',
			30
		);

		add_submenu_page( 'ws-scheduler', __( 'Tableau de bord', 'ws-scheduler' ), __( 'Tableau de bord', 'ws-scheduler' ), 'manage_options', 'ws-scheduler',          array( $this, 'render_dashboard' ) );
		add_submenu_page( 'ws-scheduler', __( 'Indisponibilités', 'ws-scheduler' ), __( 'Indisponibilités', 'ws-scheduler' ), 'manage_options', 'ws-scheduler-unavail',   array( $this, 'render_unavail' ) );
		add_submenu_page( 'ws-scheduler', __( 'Réglages', 'ws-scheduler' ), __( 'Réglages', 'ws-scheduler' ), 'manage_options', 'ws-scheduler-settings',  array( $this, 'render_settings' ) );
		add_submenu_page( 'ws-scheduler', __( 'Licence Pro', 'ws-scheduler' ), __( 'Licence Pro', 'ws-scheduler' ), 'manage_options', 'ws-scheduler-licence',  array( $this, 'render_licence' ) );
	}

	public function render_dashboard() {
		include WS_SCHEDULER_PLUGIN_DIR . 'admin/partials/ws-scheduler-admin-dashboard.php';
	}

	public function render_unavail() {
		include WS_SCHEDULER_PLUGIN_DIR . 'admin/partials/ws-scheduler-admin-unavail.php';
	}

	public function render_settings() {
		include WS_SCHEDULER_PLUGIN_DIR . 'admin/partials/ws-scheduler-admin-settings.php';
	}

	public function render_licence() {
		include WS_SCHEDULER_PLUGIN_DIR . 'admin/partials/ws-scheduler-admin-licence.php';
	}

	public function save_settings() {
		check_admin_referer( 'ws_scheduler_settings' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'Accès refusé.', 'ws-scheduler' ) );

		$days = isset( $_POST['ws_working_days'] ) ? array_map( 'intval', (array) $_POST['ws_working_days'] ) : array();
		update_option( 'ws_working_days',   $days );
		update_option( 'ws_work_start',     sanitize_text_field( $_POST['ws_work_start'] ?? '09:00' ) );
		update_option( 'ws_work_end',       sanitize_text_field( $_POST['ws_work_end']   ?? '18:00' ) );
		update_option( 'ws_slot_duration',  intval( $_POST['ws_slot_duration'] ?? 30 ) );
		update_option( 'ws_max_booking_days', intval( $_POST['ws_max_booking_days'] ?? 90 ) );
		update_option( 'ws_button_label',   sanitize_text_field( $_POST['ws_button_label'] ?? __( 'Réserver un créneau', 'ws-scheduler' ) ) );
		update_option( 'ws_business_name',  sanitize_text_field( $_POST['ws_business_name'] ?? '' ) );
		update_option( 'ws_scheduler_locale', sanitize_text_field( $_POST['ws_scheduler_locale'] ?? '' ) );
		update_option( 'ws_show_powered_by', isset( $_POST['ws_show_powered_by'] ) ? 1 : 0 );

		$email = sanitize_email( $_POST['ws_admin_email'] ?? '' );
		if ( empty( $email ) ) {
			$email = get_option( 'admin_email' );
		}
		update_option( 'ws_admin_email', $email );

		$this->purge_page_caches();

		wp_redirect( admin_url( 'admin.php?page=ws-scheduler-settings&saved=1' ) );
		exit;
	}

	/**
	 * Purge page caches after settings change.
	 * Supports LiteSpeed, WP Super Cache, W3 Total Cache, WP Fastest Cache.
	 */
	private function purge_page_caches() {
		// LiteSpeed Cache
		if ( class_exists( 'LiteSpeed_Cache_API' ) ) {
			LiteSpeed_Cache_API::purge_all();
		} elseif ( function_exists( 'litespeed_purge_all' ) ) {
			litespeed_purge_all();
		}
		// WP Super Cache
		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
		}
		// W3 Total Cache
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
		}
		// WP Fastest Cache
		if ( function_exists( 'wpfc_clear_all_cache' ) ) {
			wpfc_clear_all_cache();
		}
		// WordPress object cache
		wp_cache_flush();
	}
}

