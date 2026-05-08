<?php
/**
 * Database abstraction layer for WS Scheduler.
 *
 * @since      3.0.0
 * @package    WS_Scheduler
 * @subpackage WS_Scheduler/includes
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WS_Scheduler_DB {

	private static function appointments_table() {
		global $wpdb;
		return $wpdb->prefix . 'ws_appointments';
	}

	private static function unavail_table() {
		global $wpdb;
		return $wpdb->prefix . 'ws_unavailabilities';
	}

	// ── Appointments ──────────────────────────────────────────────────

	public static function insert_appointment( array $data ) {
		global $wpdb;
		$wpdb->insert( self::appointments_table(), $data );
		return $wpdb->insert_id;
	}

	public static function get_appointment( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare(
			'SELECT * FROM ' . self::appointments_table() . ' WHERE id = %d', $id
		) );
	}

	public static function get_all_appointments( $args = array() ) {
		global $wpdb;
		$defaults = array(
			'status'    => null,
			'date_from' => null,
			'date_to'   => null,
			'orderby'   => 'slot_start',
			'order'     => 'DESC',
			'limit'     => 100,
			'offset'    => 0,
		);
		$args = wp_parse_args( $args, $defaults );

		$where  = '1=1';
		$params = array();

		if ( $args['status'] ) {
			$where   .= ' AND status = %s';
			$params[] = $args['status'];
		}
		if ( $args['date_from'] ) {
			$where   .= ' AND slot_start >= %s';
			$params[] = $args['date_from'];
		}
		if ( $args['date_to'] ) {
			$where   .= ' AND slot_start <= %s';
			$params[] = $args['date_to'];
		}

		$order   = in_array( strtoupper( $args['order'] ), array( 'ASC', 'DESC' ) ) ? $args['order'] : 'DESC';
		$orderby = sanitize_sql_orderby( $args['orderby'] . ' ' . $order ) ?: 'slot_start DESC';

		$sql = "SELECT * FROM " . self::appointments_table() . " WHERE $where ORDER BY $orderby LIMIT %d OFFSET %d";
		$params[] = intval( $args['limit'] );
		$params[] = intval( $args['offset'] );

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
	}

	public static function update_appointment( $id, array $data ) {
		global $wpdb;
		return $wpdb->update( self::appointments_table(), $data, array( 'id' => $id ) );
	}

	public static function delete_appointment( $id ) {
		global $wpdb;
		return $wpdb->delete( self::appointments_table(), array( 'id' => $id ) );
	}

	public static function get_booked_slots( $date_from, $date_to ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			'SELECT slot_start, slot_end FROM ' . self::appointments_table() .
			" WHERE status != 'cancelled' AND slot_start >= %s AND slot_end <= %s",
			$date_from, $date_to
		) );
	}

	// ── Unavailabilities ──────────────────────────────────────────────

	public static function insert_unavailability( array $data ) {
		global $wpdb;
		$wpdb->insert( self::unavail_table(), $data );
		return $wpdb->insert_id;
	}

	public static function get_unavailabilities( $date_from = null, $date_to = null ) {
		global $wpdb;
		// Quand on filtre par date, on veut récupérer :
		//  (1) les indispos ponctuelles dont la fenêtre chevauche [from, to]
		//  (2) toutes les indispos récurrentes (filtrage par jour de la semaine
		//      ensuite, côté slots logic — la BDD n'a pas la notion de "lundi
		//      tombe-t-il dans la fenêtre")
		if ( $date_from && $date_to ) {
			return $wpdb->get_results( $wpdb->prepare(
				'SELECT * FROM ' . self::unavail_table() .
				' WHERE is_recurring = 1
				    OR (date_start < %s AND date_end > %s)
				 ORDER BY is_recurring ASC, date_start ASC',
				$date_to, $date_from
			) );
		}
		return $wpdb->get_results( 'SELECT * FROM ' . self::unavail_table() . ' ORDER BY is_recurring ASC, date_start ASC' );
	}

	public static function delete_unavailability( $id ) {
		global $wpdb;
		return $wpdb->delete( self::unavail_table(), array( 'id' => $id ) );
	}

	public static function count_upcoming() {
		global $wpdb;
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM " . self::appointments_table() .
			" WHERE slot_start >= NOW() AND status != 'cancelled'"
		);
	}

	public static function count_by_status( $status ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM " . self::appointments_table() . " WHERE status = %s", $status
		) );
	}
}
