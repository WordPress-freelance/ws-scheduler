<?php
/**
 * Tests avancés du moteur de slots : conflits booked, conflits unavail
 * (period_full + recurring), edge cases sur work_end, durées variables.
 */
use WP_Mock\Tools\TestCase;

class Test_WS_Scheduler_Slots_Advanced extends TestCase {

    private $wpdb_backup;
    /** @var MockWpdb */
    private $db;

    public function setUp(): void {
        parent::setUp();
        $this->wpdb_backup = $GLOBALS['wpdb'] ?? null;
        $this->db          = new MockWpdb();
        $GLOBALS['wpdb']   = $this->db;
    }

    public function tearDown(): void {
        parent::tearDown();
        $GLOBALS['wpdb'] = $this->wpdb_backup;
    }

    private function mockOptions( array $custom = [] ) {
        \WP_Mock::userFunction( 'wp_timezone_string', [ 'return' => 'UTC' ] );
        \WP_Mock::userFunction( 'get_option', [
            'return' => function( $k, $d = false ) use ( $custom ) {
                $defaults = [
                    'ws_working_days'     => [ 1, 2, 3, 4, 5, 6, 7 ],
                    'ws_max_booking_days' => 365,
                    'ws_work_start'       => '09:00',
                    'ws_work_end'         => '12:00',
                    'ws_slot_duration'    => 30,
                ];
                $merged = array_merge( $defaults, $custom );
                return array_key_exists( $k, $merged ) ? $merged[ $k ] : $d;
            },
        ] );
    }

    /**
     * Configure MockWpdb pour différencier booked vs unavailabilities selon SQL.
     */
    private function routeRows( array $booked = [], array $unavails = [] ) {
        $this->db->sql_routes = [
            [ 'regex' => '/SELECT slot_start, slot_end FROM/', 'rows' => $booked ],
            [ 'regex' => '/FROM wp_ws_unavailabilities/',      'rows' => $unavails ],
        ];
    }

    private function futureDate( int $days_ahead = 30 ): string {
        $tz = new DateTimeZone( 'UTC' );
        $dt = new DateTime( 'today', $tz );
        $dt->modify( "+$days_ahead days" );
        return $dt->format( 'Y-m-d' );
    }

    // ── Pas de conflits ──────────────────────────────────────────────

    public function test_get_slots_returns_full_grid_when_no_conflicts() {
        $this->mockOptions();
        $this->routeRows();
        $slots = WS_Scheduler_Slots::get_slots_for_date( $this->futureDate() );
        $this->assertEquals( [ '09:00', '09:30', '10:00', '10:30', '11:00', '11:30' ], $slots );
    }

    public function test_get_slots_returns_empty_for_past_date() {
        $this->mockOptions();
        $slots = WS_Scheduler_Slots::get_slots_for_date( '2020-01-15' );
        $this->assertEquals( [], $slots );
    }

    public function test_get_slots_returns_empty_for_invalid_date() {
        $this->mockOptions();
        $this->assertEquals( [], WS_Scheduler_Slots::get_slots_for_date( 'not-a-date' ) );
        $this->assertEquals( [], WS_Scheduler_Slots::get_slots_for_date( '' ) );
    }

    public function test_get_slots_returns_empty_when_dow_not_in_working_days() {
        // Force working_days = []. Aucun DOW ne matche.
        $this->mockOptions( [ 'ws_working_days' => [] ] );
        $this->assertEquals( [], WS_Scheduler_Slots::get_slots_for_date( $this->futureDate() ) );
    }

    public function test_get_slots_returns_empty_when_date_exceeds_max_booking_window() {
        $this->mockOptions( [ 'ws_max_booking_days' => 5 ] );
        $this->assertEquals( [], WS_Scheduler_Slots::get_slots_for_date( $this->futureDate( 30 ) ) );
    }

    public function test_get_slots_returns_empty_when_work_start_after_work_end() {
        $this->mockOptions( [ 'ws_work_start' => '18:00', 'ws_work_end' => '09:00' ] );
        $this->assertEquals( [], WS_Scheduler_Slots::get_slots_for_date( $this->futureDate() ) );
    }

    public function test_get_slots_returns_empty_when_work_start_invalid() {
        $this->mockOptions( [ 'ws_work_start' => 'invalid', 'ws_work_end' => '18:00' ] );
        $this->assertEquals( [], WS_Scheduler_Slots::get_slots_for_date( $this->futureDate() ) );
    }

    // ── Conflits booked ──────────────────────────────────────────────

    public function test_get_slots_excludes_booked_slot_exact_match() {
        $this->mockOptions();
        $date = $this->futureDate();
        $this->routeRows( [
                [ 'slot_start' => $date . ' 10:00:00', 'slot_end' => $date . ' 10:30:00' ],
            ]
        );
        $slots = WS_Scheduler_Slots::get_slots_for_date( $date );
        $this->assertNotContains( '10:00', $slots );
        // Les autres slots doivent rester disponibles
        $this->assertContains( '09:00', $slots );
        $this->assertContains( '11:00', $slots );
    }

    public function test_get_slots_excludes_booked_overlapping_partial_start() {
        $this->mockOptions();
        $date = $this->futureDate();
        // Booking de 09:45 à 10:15 : chevauche 09:30 et 10:00
        $this->routeRows( [
            [ 'slot_start' => $date . ' 09:45:00', 'slot_end' => $date . ' 10:15:00' ],
        ] );
        $slots = WS_Scheduler_Slots::get_slots_for_date( $date );
        $this->assertNotContains( '09:30', $slots );
        $this->assertNotContains( '10:00', $slots );
        $this->assertContains( '09:00', $slots );
        $this->assertContains( '10:30', $slots );
    }

    public function test_get_slots_excludes_multiple_booked() {
        $this->mockOptions();
        $date = $this->futureDate();
        $this->routeRows( [
            [ 'slot_start' => $date . ' 09:00:00', 'slot_end' => $date . ' 09:30:00' ],
            [ 'slot_start' => $date . ' 11:00:00', 'slot_end' => $date . ' 11:30:00' ],
        ] );
        $slots = WS_Scheduler_Slots::get_slots_for_date( $date );
        $this->assertNotContains( '09:00', $slots );
        $this->assertNotContains( '11:00', $slots );
        $this->assertContains( '09:30', $slots );
        $this->assertContains( '10:00', $slots );
    }

    // ── Conflits unavailability ponctuel ────────────────────────────

    public function test_get_slots_excludes_punctual_unavail_range() {
        $this->mockOptions();
        $date = $this->futureDate();
        // Indispo ponctuelle de 10:00 à 11:30
        $this->routeRows( [], [
            [
                'is_recurring' => 0,
                'date_start'   => $date . ' 10:00:00',
                'date_end'     => $date . ' 11:30:00',
                'recur_days'   => '',
                'time_start'   => null,
                'time_end'     => null,
            ],
        ] );
        $slots = WS_Scheduler_Slots::get_slots_for_date( $date );
        $this->assertNotContains( '10:00', $slots );
        $this->assertNotContains( '10:30', $slots );
        $this->assertNotContains( '11:00', $slots );
        $this->assertContains( '09:00', $slots );
        // 11:30 + 30min = 12:00 = work_end. Slot end <= work_end → inclus.
        $this->assertContains( '11:30', $slots );
    }

    public function test_get_slots_skips_punctual_unavail_with_empty_dates() {
        $this->mockOptions();
        $date = $this->futureDate();
        // Row corrompu : pas de date_start. Ne doit pas crasher, juste continue.
        $this->routeRows( [], [
            [
                'is_recurring' => 0,
                'date_start'   => '',
                'date_end'     => '',
                'recur_days'   => '',
                'time_start'   => null,
                'time_end'     => null,
            ],
        ] );
        $slots = WS_Scheduler_Slots::get_slots_for_date( $date );
        $this->assertCount( 6, $slots ); // tous les slots disponibles
    }

    // ── Conflits unavailability récurrent ──────────────────────────

    public function test_get_slots_excludes_recurring_unavail_when_dow_matches() {
        $this->mockOptions();
        $tz   = new DateTimeZone( 'UTC' );
        $date = $this->futureDate();
        $dow  = (int) ( new DateTime( $date, $tz ) )->format( 'N' );

        $this->routeRows( [], [
            [
                'is_recurring' => 1,
                'recur_days'   => (string) $dow, // matche le DOW de la date
                'time_start'   => '10:00:00',
                'time_end'     => '11:30:00',
                'date_start'   => null,
                'date_end'     => null,
            ],
        ] );
        $slots = WS_Scheduler_Slots::get_slots_for_date( $date );
        $this->assertNotContains( '10:00', $slots );
        $this->assertNotContains( '11:00', $slots );
        $this->assertContains( '09:00', $slots );
    }

    public function test_get_slots_skips_recurring_unavail_when_dow_does_not_match() {
        $this->mockOptions();
        $tz   = new DateTimeZone( 'UTC' );
        $date = $this->futureDate();
        $dow  = (int) ( new DateTime( $date, $tz ) )->format( 'N' );
        // recur_days = un DOW DIFFÉRENT
        $other_dow = ( $dow % 7 ) + 1;

        $this->routeRows( [], [
            [
                'is_recurring' => 1,
                'recur_days'   => (string) $other_dow,
                'time_start'   => '10:00:00',
                'time_end'     => '11:30:00',
                'date_start'   => null,
                'date_end'     => null,
            ],
        ] );
        $slots = WS_Scheduler_Slots::get_slots_for_date( $date );
        $this->assertCount( 6, $slots );
    }

    public function test_get_slots_skips_recurring_unavail_with_missing_times() {
        $this->mockOptions();
        $tz   = new DateTimeZone( 'UTC' );
        $date = $this->futureDate();
        $dow  = (int) ( new DateTime( $date, $tz ) )->format( 'N' );

        $this->routeRows( [], [
            [
                'is_recurring' => 1,
                'recur_days'   => (string) $dow,
                'time_start'   => '',  // legacy non migré
                'time_end'     => '',
                'date_start'   => null,
                'date_end'     => null,
            ],
        ] );
        $slots = WS_Scheduler_Slots::get_slots_for_date( $date );
        $this->assertCount( 6, $slots );
    }

    // ── Variations de durée et work hours ──────────────────────────

    public function test_get_slots_with_60min_duration() {
        $this->mockOptions( [ 'ws_slot_duration' => 60 ] );
        $slots = WS_Scheduler_Slots::get_slots_for_date( $this->futureDate() );
        $this->assertEquals( [ '09:00', '10:00', '11:00' ], $slots );
    }

    public function test_get_slots_with_15min_duration() {
        $this->mockOptions( [ 'ws_slot_duration' => 15 ] );
        $slots = WS_Scheduler_Slots::get_slots_for_date( $this->futureDate() );
        $this->assertCount( 12, $slots ); // 09:00 → 11:45 par 15min = 12 créneaux
        $this->assertEquals( '09:00', $slots[0] );
        $this->assertEquals( '11:45', end( $slots ) );
    }

    public function test_get_slots_with_120min_duration() {
        $this->mockOptions( [ 'ws_slot_duration' => 120 ] );
        // 09:00-12:00 = 3h, durée 2h → seul 09:00 + 11:00 OUT (11+2=13 > 12).
        // Seulement 09:00.
        $slots = WS_Scheduler_Slots::get_slots_for_date( $this->futureDate() );
        $this->assertEquals( [ '09:00' ], $slots );
    }

    public function test_get_slots_skips_slot_when_end_exceeds_work_end() {
        // Work hours 09:00-09:50, durée 30min : 09:00 OK, 09:30+30=10:00 > 09:50 → break.
        $this->mockOptions( [ 'ws_work_end' => '09:50', 'ws_slot_duration' => 30 ] );
        $slots = WS_Scheduler_Slots::get_slots_for_date( $this->futureDate() );
        $this->assertEquals( [ '09:00' ], $slots );
    }

    // ── get_month_availability ──────────────────────────────────────

    public function test_get_month_availability_returns_status_per_day() {
        $this->mockOptions();
        $tz   = new DateTimeZone( 'UTC' );
        $now  = new DateTime( 'now', $tz );
        $next_year  = (int) $now->format( 'Y' ) + 1;
        $result = WS_Scheduler_Slots::get_month_availability( $next_year, 6 );
        $this->assertCount( 30, $result ); // juin = 30 jours
        // Tous fermés (au-delà de max_booking_days = 365 OK pour juin Y+1
        // si Y+1 est dans la fenêtre de 365 jours)
        // On vérifie juste que toutes les valeurs sont parmi le set autorisé
        $allowed = [ 'past', 'closed', 'full', 'available' ];
        foreach ( $result as $date => $status ) {
            $this->assertContains( $status, $allowed, "date $date status $status" );
        }
    }

    public function test_get_month_availability_marks_past_dates_as_past() {
        $this->mockOptions();
        $result = WS_Scheduler_Slots::get_month_availability( 2020, 1 );
        // Tous les jours de janv 2020 sont dans le passé
        foreach ( $result as $date => $status ) {
            $this->assertEquals( 'past', $status );
        }
    }

    public function test_get_month_availability_marks_dates_beyond_window_as_closed() {
        $this->mockOptions( [ 'ws_max_booking_days' => 30 ] );
        $tz  = new DateTimeZone( 'UTC' );
        $far = ( new DateTime( 'now', $tz ) )->modify( '+2 years' );
        $result = WS_Scheduler_Slots::get_month_availability(
            (int) $far->format( 'Y' ),
            (int) $far->format( 'm' )
        );
        foreach ( $result as $date => $status ) {
            $this->assertEquals( 'closed', $status );
        }
    }

    public function test_get_month_availability_marks_non_working_dow_as_closed() {
        // Working days = [1] (Monday only)
        $this->mockOptions( [ 'ws_working_days' => [ 1 ] ] );
        $tz   = new DateTimeZone( 'UTC' );
        $now  = new DateTime( 'now', $tz );
        $year = (int) $now->format( 'Y' ) + 1;
        $result = WS_Scheduler_Slots::get_month_availability( $year, 6 );

        foreach ( $result as $date => $status ) {
            $dow = (int) ( new DateTime( $date, $tz ) )->format( 'N' );
            if ( $dow !== 1 ) {
                $this->assertContains( $status, [ 'closed', 'past' ] );
            }
        }
    }
}
