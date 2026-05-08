<?php
/**
 * Fired during plugin activation — creates database tables.
 *
 * @since      3.0.0
 * @package    WS_Scheduler
 * @subpackage WS_Scheduler/includes
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WS_Scheduler_Activator {

	public static function activate() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$table_appointments = $wpdb->prefix . 'ws_appointments';
		$sql_appointments = "CREATE TABLE IF NOT EXISTS $table_appointments (
			id           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			first_name   VARCHAR(100) NOT NULL,
			last_name    VARCHAR(100) NOT NULL,
			company      VARCHAR(150) DEFAULT '',
			email        VARCHAR(200) NOT NULL,
			phone        VARCHAR(30) DEFAULT '',
			message      TEXT DEFAULT '',
			slot_start   DATETIME NOT NULL,
			slot_end     DATETIME NOT NULL,
			duration     SMALLINT UNSIGNED NOT NULL DEFAULT 30,
			status       ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
			meet_link    VARCHAR(500) DEFAULT '',
			google_event_id VARCHAR(255) DEFAULT '',
			with_meet    TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
			created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY idx_slot_start (slot_start),
			KEY idx_status (status),
			KEY idx_email (email(100))
		) $charset_collate;";

		$table_unavail = $wpdb->prefix . 'ws_unavailabilities';
		$sql_unavail = "CREATE TABLE IF NOT EXISTS $table_unavail (
			id           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			label        VARCHAR(255) DEFAULT '',
			date_start   DATETIME NULL,
			date_end     DATETIME NULL,
			time_start   TIME NULL,
			time_end     TIME NULL,
			is_recurring TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
			recur_days   VARCHAR(20) DEFAULT '',
			PRIMARY KEY  (id),
			KEY idx_date_start (date_start),
			KEY idx_recurring (is_recurring)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_appointments );
		dbDelta( $sql_unavail );

		// Migration : pour les indispos récurrentes pré-3.8, extrait les heures
		// depuis date_start/date_end vers time_start/time_end. Idempotent
		// grâce au filtre time_start IS NULL.
		$wpdb->query(
			"UPDATE $table_unavail
			 SET time_start = TIME(date_start),
			     time_end   = TIME(date_end)
			 WHERE is_recurring = 1
			   AND time_start IS NULL
			   AND date_start IS NOT NULL"
		);

		// Default options
		if ( false === get_option( 'ws_working_days' ) ) {
			update_option( 'ws_working_days', array( 1, 2, 3, 4, 5 ) );
		}
		if ( false === get_option( 'ws_work_start' ) ) {
			update_option( 'ws_work_start', '09:00' );
		}
		if ( false === get_option( 'ws_work_end' ) ) {
			update_option( 'ws_work_end', '18:00' );
		}
		if ( false === get_option( 'ws_slot_duration' ) ) {
			update_option( 'ws_slot_duration', 30 );
		}
		if ( false === get_option( 'ws_button_label' ) ) {
			update_option( 'ws_button_label', 'Réserver un créneau' );
		}
		if ( false === get_option( 'ws_admin_email' ) ) {
			update_option( 'ws_admin_email', get_option( 'admin_email' ) );
		}
		if ( false === get_option( 'ws_business_name' ) ) {
			update_option( 'ws_business_name', get_bloginfo( 'name' ) );
		}

		self::purge_page_caches();
	}

	/**
	 * Purge les caches HTML connus à chaque activation/réactivation du
	 * plugin. Sans ça, les visiteurs anonymes peuvent recevoir une version
	 * cachée de la page qui a été générée AVANT que les hooks d'enqueue
	 * du plugin ne soient en place — donc sans le JS de la popup, sans
	 * la config wsScheduler, etc. Le bouton ne fait alors rien pour eux
	 * tandis que les utilisateurs loggés voient bien le nouveau HTML.
	 */
	public static function purge_page_caches() {
		// LiteSpeed Cache
		if ( class_exists( '\\LiteSpeed\\Purge' ) && method_exists( '\\LiteSpeed\\Purge', 'purge_all' ) ) {
			\LiteSpeed\Purge::purge_all();
		}
		do_action( 'litespeed_purge_all' );

		// WP Rocket
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}

		// W3 Total Cache
		if ( function_exists( 'w3tc_pgcache_flush' ) ) {
			w3tc_pgcache_flush();
		}

		// WP Super Cache
		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
		}

		// WP Fastest Cache
		if ( function_exists( 'wpfc_clear_all_cache' ) ) {
			wpfc_clear_all_cache();
		}

		// Object cache WordPress
		wp_cache_flush();
	}
}
