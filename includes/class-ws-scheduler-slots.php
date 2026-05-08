<?php
/**
 * Slot generation engine.
 * Computes available slots based on working hours, booked appointments
 * and manual unavailabilities.
 *
 * @since      3.0.0
 * @package    WS_Scheduler
 * @subpackage WS_Scheduler/includes
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WS_Scheduler_Slots {

	/**
	 * Get all available slots for a given date.
	 *
	 * @param  string $date Y-m-d
	 * @return array  Array of slot strings (H:i)
	 */
	public static function get_slots_for_date( $date ) {
		$tz = new DateTimeZone( wp_timezone_string() );
		$dt = DateTime::createFromFormat( 'Y-m-d', $date, $tz );
		if ( ! $dt ) return array();

		$dow = (int) $dt->format( 'N' );
		$working_days = get_option( 'ws_working_days', array( 1, 2, 3, 4, 5 ) );
		if ( ! in_array( $dow, $working_days ) ) return array();

		// Check if date is in the past
		$now = new DateTime( 'now', $tz );
		if ( $dt->format( 'Y-m-d' ) < $now->format( 'Y-m-d' ) ) return array();

		// Check if date exceeds max booking window
		$max_days = (int) get_option( 'ws_max_booking_days', 90 );
		$max_date = clone $now;
		$max_date->modify( '+' . $max_days . ' days' );
		if ( $dt->format( 'Y-m-d' ) > $max_date->format( 'Y-m-d' ) ) return array();

		$start    = get_option( 'ws_work_start', '09:00' );
		$end      = get_option( 'ws_work_end', '18:00' );
		$duration = (int) get_option( 'ws_slot_duration', 30 );

		$dt_start = DateTime::createFromFormat( 'Y-m-d H:i', $date . ' ' . $start, $tz );
		$dt_end   = DateTime::createFromFormat( 'Y-m-d H:i', $date . ' ' . $end, $tz );
		if ( ! $dt_start || ! $dt_end ) return array();

		// Get booked slots
		$booked = WS_Scheduler_DB::get_booked_slots(
			$date . ' 00:00:00',
			$date . ' 23:59:59'
		);
		$booked_ranges = array();
		foreach ( $booked as $b ) {
			$booked_ranges[] = array(
				'start' => strtotime( $b->slot_start ),
				'end'   => strtotime( $b->slot_end ),
			);
		}

		// Get unavailabilities
		$unavails = WS_Scheduler_DB::get_unavailabilities(
			$date . ' 00:00:00',
			$date . ' 23:59:59'
		);
		$unavail_ranges = array();
		foreach ( $unavails as $u ) {
			if ( $u->is_recurring && ! empty( $u->recur_days ) ) {
				// Récurrent hebdomadaire : on construit le range pour LE jour
				// évalué (et pas celui de création de l'indispo). Ignore si
				// le jour de la semaine ne matche pas, ou si les heures sont
				// manquantes (enregistrement legacy non migré).
				$days = array_map( 'intval', explode( ',', $u->recur_days ) );
				if ( ! in_array( $dow, $days, true ) ) continue;
				if ( empty( $u->time_start ) || empty( $u->time_end ) ) continue;
				$unavail_ranges[] = array(
					'start' => strtotime( $date . ' ' . $u->time_start ),
					'end'   => strtotime( $date . ' ' . $u->time_end ),
				);
			} else {
				// Ponctuel : datetimes complets stockés en BDD.
				if ( empty( $u->date_start ) || empty( $u->date_end ) ) continue;
				$unavail_ranges[] = array(
					'start' => strtotime( $u->date_start ),
					'end'   => strtotime( $u->date_end ),
				);
			}
		}

		// Generate all possible slots
		$slots   = array();
		$cursor  = clone $dt_start;
		$is_today = $dt->format( 'Y-m-d' ) === $now->format( 'Y-m-d' );

		while ( $cursor < $dt_end ) {
			$slot_start_ts = $cursor->getTimestamp();
			$slot_end_ts   = $slot_start_ts + ( $duration * 60 );

			// Skip if in the past (for today)
			if ( $is_today && $slot_start_ts <= $now->getTimestamp() ) {
				$cursor->modify( '+' . $duration . ' minutes' );
				continue;
			}

			// Skip if slot end exceeds work end
			if ( $slot_end_ts > $dt_end->getTimestamp() ) break;

			// Check conflicts
			$conflict = false;
			foreach ( $booked_ranges as $br ) {
				if ( $slot_start_ts < $br['end'] && $slot_end_ts > $br['start'] ) {
					$conflict = true;
					break;
				}
			}
			if ( ! $conflict ) {
				foreach ( $unavail_ranges as $ur ) {
					if ( $slot_start_ts < $ur['end'] && $slot_end_ts > $ur['start'] ) {
						$conflict = true;
						break;
					}
				}
			}

			if ( ! $conflict ) {
				$slots[] = $cursor->format( 'H:i' );
			}

			$cursor->modify( '+' . $duration . ' minutes' );
		}

		return $slots;
	}

	/**
	 * Get availability data for an entire month.
	 *
	 * @param int $year
	 * @param int $month
	 * @return array Keyed by date (Y-m-d) → status string
	 */
	public static function get_month_availability( $year, $month ) {
		$tz     = new DateTimeZone( wp_timezone_string() );
		$now    = new DateTime( 'now', $tz );
		$days   = cal_days_in_month( CAL_GREGORIAN, $month, $year );
		$result = array();

		$working_days = get_option( 'ws_working_days', array( 1, 2, 3, 4, 5 ) );
		$max_days     = (int) get_option( 'ws_max_booking_days', 90 );
		$max_date     = clone $now;
		$max_date->modify( '+' . $max_days . ' days' );

		for ( $d = 1; $d <= $days; $d++ ) {
			$date = sprintf( '%04d-%02d-%02d', $year, $month, $d );
			$dt   = new DateTime( $date, $tz );
			$dow  = (int) $dt->format( 'N' );

			if ( $dt->format( 'Y-m-d' ) < $now->format( 'Y-m-d' ) ) {
				$result[ $date ] = 'past';
			} elseif ( $dt->format( 'Y-m-d' ) > $max_date->format( 'Y-m-d' ) ) {
				$result[ $date ] = 'closed';
			} elseif ( ! in_array( $dow, $working_days ) ) {
				$result[ $date ] = 'closed';
			} else {
				$slots = self::get_slots_for_date( $date );
				if ( count( $slots ) === 0 ) {
					$result[ $date ] = 'full';
				} else {
					$result[ $date ] = 'available';
				}
			}
		}

		return $result;
	}
}
