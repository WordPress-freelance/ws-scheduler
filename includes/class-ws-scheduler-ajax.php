<?php
/**
 * Handles all AJAX requests for WS Scheduler.
 *
 * @since      3.0.0
 * @package    WS_Scheduler
 * @subpackage WS_Scheduler/includes
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WS_Scheduler_Ajax {

	// ── Public endpoints (front-end) ─────────────────────────────────
	//
	// Pas de check_ajax_referer sur les endpoints de lecture (get_month_slots,
	// get_available_slots) : ce sont des données publiques, le nonce ne
	// protégeait rien et créait du faux 403 quand la page était servie
	// depuis un cache HTML avec un nonce expiré (LiteSpeed, WP Rocket…).
	// Le nonce reste obligatoire sur book_appointment (mutation BDD + envoi
	// d'emails) où il joue son vrai rôle anti-CSRF.

	public function get_available_slots() {
		$date = sanitize_text_field( isset( $_POST['date'] ) ? $_POST['date'] : '' );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			wp_send_json_error( __( 'Date invalide.', 'ws-scheduler' ) );
		}
		$slots = WS_Scheduler_Slots::get_slots_for_date( $date );
		wp_send_json_success( $slots );
	}

	public function get_month_slots() {
		$year  = (int) ( isset( $_POST['year'] )  ? $_POST['year']  : date( 'Y' ) );
		$month = (int) ( isset( $_POST['month'] ) ? $_POST['month'] : date( 'm' ) );
		if ( $month < 1 || $month > 12 ) wp_send_json_error( __( 'Mois invalide.', 'ws-scheduler' ) );
		$data = WS_Scheduler_Slots::get_month_availability( $year, $month );
		wp_send_json_success( $data );
	}

	public function book_appointment() {
		check_ajax_referer( 'ws_scheduler_nonce', 'nonce' );

		$required = array( 'first_name', 'last_name', 'email', 'slot_start' );
		foreach ( $required as $field ) {
			if ( empty( $_POST[ $field ] ) ) {
				wp_send_json_error( __( 'Champ requis manquant : ', 'ws-scheduler' ) . $field );
			}
		}

		$email = sanitize_email( $_POST['email'] );
		if ( ! is_email( $email ) ) {
			wp_send_json_error( __( 'Adresse email invalide.', 'ws-scheduler' ) );
		}

		$slot_start = sanitize_text_field( $_POST['slot_start'] );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}/', $slot_start ) ) {
			wp_send_json_error( __( 'Créneau invalide.', 'ws-scheduler' ) );
		}

		$duration = (int) get_option( 'ws_slot_duration', 30 );
		$tz       = new DateTimeZone( wp_timezone_string() );
		$dt_start = new DateTime( $slot_start, $tz );
		$dt_end   = clone $dt_start;
		$dt_end->modify( '+' . $duration . ' minutes' );

		// Double-check slot availability
		$date_str    = $dt_start->format( 'Y-m-d' );
		$time_str    = $dt_start->format( 'H:i' );
		$free_slots  = WS_Scheduler_Slots::get_slots_for_date( $date_str );
		if ( ! in_array( $time_str, $free_slots ) ) {
			wp_send_json_error( __( 'Ce créneau n\'est plus disponible.', 'ws-scheduler' ) );
		}

		$data = array(
			'first_name' => sanitize_text_field( wp_unslash( $_POST['first_name'] ) ),
			'last_name'  => sanitize_text_field( wp_unslash( $_POST['last_name'] ) ),
			'company'    => sanitize_text_field( wp_unslash( isset( $_POST['company'] ) ? $_POST['company'] : '' ) ),
			'email'      => $email,
			'phone'      => sanitize_text_field( wp_unslash( isset( $_POST['phone'] ) ? $_POST['phone'] : '' ) ),
			'message'    => sanitize_textarea_field( wp_unslash( isset( $_POST['message'] ) ? $_POST['message'] : '' ) ),
			'slot_start' => $dt_start->format( 'Y-m-d H:i:s' ),
			'slot_end'   => $dt_end->format( 'Y-m-d H:i:s' ),
			'duration'   => $duration,
			'status'     => 'confirmed',
		);

		$id = WS_Scheduler_DB::insert_appointment( $data );
		if ( ! $id ) {
			wp_send_json_error( __( 'Erreur lors de l\'enregistrement.', 'ws-scheduler' ) );
		}

		$appointment = WS_Scheduler_DB::get_appointment( $id );

		// Google Calendar sync (Pro hook)
		$meet_link = '';
		$meet_link = apply_filters( 'ws_scheduler_after_booking', $meet_link, $appointment );
		if ( ! empty( $meet_link ) ) {
			WS_Scheduler_DB::update_appointment( $id, array( 'meet_link' => $meet_link ) );
			$appointment->meet_link = $meet_link;
		}

		// Emails
		WS_Scheduler_Email::send_client_confirmation( $appointment );
		WS_Scheduler_Email::send_admin_notification( $appointment );

		wp_send_json_success( array(
			'message'   => __( 'Rendez-vous confirmé !', 'ws-scheduler' ),
			'meet_link' => $appointment->meet_link,
		) );
	}

	// ── Admin endpoints ──────────────────────────────────────────────

	public function update_appointment() {
		check_ajax_referer( 'ws_scheduler_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( __( 'Accès refusé.', 'ws-scheduler' ) );

		$id     = intval( $_POST['id'] ?? 0 );
		$status = sanitize_text_field( $_POST['status'] ?? '' );

		if ( ! $id || ! in_array( $status, array( 'confirmed', 'pending', 'cancelled' ) ) ) {
			wp_send_json_error( __( 'Paramètres invalides.', 'ws-scheduler' ) );
		}

		$appointment = WS_Scheduler_DB::get_appointment( $id );
		WS_Scheduler_DB::update_appointment( $id, array( 'status' => $status ) );

		// Send cancellation email to client
		if ( $status === 'cancelled' && $appointment ) {
			WS_Scheduler_Email::send_client_cancellation( $appointment );
		}

		wp_send_json_success( __( 'Statut mis à jour.', 'ws-scheduler' ) );
	}

	public function delete_appointment() {
		check_ajax_referer( 'ws_scheduler_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( __( 'Accès refusé.', 'ws-scheduler' ) );

		$id = intval( $_POST['id'] ?? 0 );
		if ( ! $id ) wp_send_json_error( __( 'ID manquant.', 'ws-scheduler' ) );

		// Send cancellation email before deleting
		$appointment = WS_Scheduler_DB::get_appointment( $id );
		if ( $appointment && $appointment->status !== 'cancelled' ) {
			WS_Scheduler_Email::send_client_cancellation( $appointment );
		}

		WS_Scheduler_DB::delete_appointment( $id );
		wp_send_json_success( __( 'Rendez-vous supprimé.', 'ws-scheduler' ) );
	}

	public function add_unavailability() {
		check_ajax_referer( 'ws_scheduler_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( __( 'Accès refusé.', 'ws-scheduler' ) );

		$label = sanitize_text_field( $_POST['label'] ?? '' );
		$mode  = sanitize_text_field( $_POST['mode'] ?? 'period_full' );

		// Whitelist des modes acceptés.
		if ( ! in_array( $mode, array( 'period_full', 'punctual_slot', 'recurring' ), true ) ) {
			wp_send_json_error( __( 'Mode invalide.', 'ws-scheduler' ) );
		}

		$payload = array( 'label' => $label );

		// Helper local : valide un format date (Y-m-d) ou heure (H:i / H:i:s).
		$is_date = function( $s ) { return (bool) preg_match( '/^\d{4}-\d{2}-\d{2}$/', $s ); };
		$is_time = function( $s ) { return (bool) preg_match( '/^\d{2}:\d{2}(:\d{2})?$/', $s ); };

		switch ( $mode ) {

			// ── Mode 1 : période complète, journée(s) entière(s) ──────────
			case 'period_full':
				$d_start = sanitize_text_field( $_POST['date_start'] ?? '' );
				$d_end   = sanitize_text_field( $_POST['date_end']   ?? '' );
				if ( ! $is_date( $d_start ) || ! $is_date( $d_end ) ) {
					wp_send_json_error( __( 'Dates de début et de fin requises.', 'ws-scheduler' ) );
				}
				if ( strtotime( $d_end ) < strtotime( $d_start ) ) {
					wp_send_json_error( __( 'La date de fin doit être postérieure ou égale à la date de début.', 'ws-scheduler' ) );
				}
				$payload['date_start']   = $d_start . ' 00:00:00';
				$payload['date_end']     = $d_end   . ' 23:59:59';
				$payload['is_recurring'] = 0;
				$payload['recur_days']   = '';
				$payload['time_start']   = null;
				$payload['time_end']     = null;
				break;

			// ── Mode 2 : créneau ponctuel (un jour, plage horaire précise) ──
			case 'punctual_slot':
				$d   = sanitize_text_field( $_POST['date']       ?? '' );
				$t_s = sanitize_text_field( $_POST['time_start'] ?? '' );
				$t_e = sanitize_text_field( $_POST['time_end']   ?? '' );
				if ( ! $is_date( $d ) ) {
					wp_send_json_error( __( 'Date requise.', 'ws-scheduler' ) );
				}
				if ( ! $is_time( $t_s ) || ! $is_time( $t_e ) ) {
					wp_send_json_error( __( 'Heures de début et de fin requises (format HH:MM).', 'ws-scheduler' ) );
				}
				$ts_start = strtotime( $d . ' ' . $t_s );
				$ts_end   = strtotime( $d . ' ' . $t_e );
				if ( $ts_end <= $ts_start ) {
					wp_send_json_error( __( 'L\'heure de fin doit être postérieure à l\'heure de début.', 'ws-scheduler' ) );
				}
				$payload['date_start']   = gmdate( 'Y-m-d H:i:s', $ts_start );
				$payload['date_end']     = gmdate( 'Y-m-d H:i:s', $ts_end );
				$payload['is_recurring'] = 0;
				$payload['recur_days']   = '';
				$payload['time_start']   = null;
				$payload['time_end']     = null;
				break;

			// ── Mode 3 : récurrent hebdomadaire ───────────────────────────
			case 'recurring':
				$days_raw = $_POST['recur_days'] ?? array();
				if ( ! is_array( $days_raw ) ) {
					$days_raw = explode( ',', sanitize_text_field( (string) $days_raw ) );
				}
				$days = array_values( array_filter(
					array_map( 'intval', $days_raw ),
					function( $d ) { return $d >= 1 && $d <= 7; }
				) );
				if ( empty( $days ) ) {
					wp_send_json_error( __( 'Sélectionnez au moins un jour de la semaine.', 'ws-scheduler' ) );
				}

				$t_s = sanitize_text_field( $_POST['time_start'] ?? '' );
				$t_e = sanitize_text_field( $_POST['time_end']   ?? '' );
				if ( ! $is_time( $t_s ) || ! $is_time( $t_e ) ) {
					wp_send_json_error( __( 'Heures de début et de fin requises (format HH:MM).', 'ws-scheduler' ) );
				}
				// Normalise vers H:i:s pour MySQL.
				$ts_dummy_start = strtotime( '2000-01-01 ' . $t_s );
				$ts_dummy_end   = strtotime( '2000-01-01 ' . $t_e );
				if ( $ts_dummy_end <= $ts_dummy_start ) {
					wp_send_json_error( __( 'L\'heure de fin doit être postérieure à l\'heure de début.', 'ws-scheduler' ) );
				}
				$payload['date_start']   = null;
				$payload['date_end']     = null;
				$payload['time_start']   = gmdate( 'H:i:s', $ts_dummy_start );
				$payload['time_end']     = gmdate( 'H:i:s', $ts_dummy_end );
				$payload['is_recurring'] = 1;
				$payload['recur_days']   = implode( ',', $days );
				break;
		}

		$id = WS_Scheduler_DB::insert_unavailability( $payload );

		wp_send_json_success( array( 'id' => $id ) );
	}

	public function delete_unavailability() {
		check_ajax_referer( 'ws_scheduler_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( __( 'Accès refusé.', 'ws-scheduler' ) );

		$id = intval( $_POST['id'] ?? 0 );
		if ( ! $id ) wp_send_json_error( __( 'ID manquant.', 'ws-scheduler' ) );

		WS_Scheduler_DB::delete_unavailability( $id );
		wp_send_json_success( __( 'Indisponibilité supprimée.', 'ws-scheduler' ) );
	}
}
