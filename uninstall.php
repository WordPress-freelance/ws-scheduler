<?php
/**
 * Uninstall handler for WS Scheduler.
 * Nettoie tout ce que le plugin (FREE) a posé en BDD : tables et options.
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

global $wpdb;

// ── Tables ──────────────────────────────────────────────────────────
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ws_appointments" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ws_unavailabilities" );

// ── Options nominatives (plugin) ────────────────────────────────────
$options = array(
	'ws_working_days',
	'ws_work_start',
	'ws_work_end',
	'ws_slot_duration',
	'ws_max_booking_days',
	'ws_button_label',
	'ws_admin_email',
	'ws_business_name',
	'ws_scheduler_locale',
);
foreach ( $options as $opt ) {
	delete_option( $opt );
}

