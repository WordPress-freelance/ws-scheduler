<?php
use WP_Mock\Tools\TestCase;

class Test_WS_Scheduler_Slots extends TestCase {

    private $wpdb_backup;

    public function setUp(): void {
        parent::setUp();
        $this->wpdb_backup = $GLOBALS['wpdb'] ?? null;
        $db = new MockWpdb();
        $db->return_rows = [];
        $GLOBALS['wpdb'] = $db;
    }

    public function tearDown(): void {
        parent::tearDown();
        $GLOBALS['wpdb'] = $this->wpdb_backup;
    }

    private function mockOptions( array $overrides = [] ) {
        $defaults = [
            'ws_working_days'    => [1,2,3,4,5,6,7],
            'ws_max_booking_days'=> 365,
            'ws_work_start'      => '09:00',
            'ws_work_end'        => '18:00',
            'ws_slot_duration'   => 30,
            'ws_show_powered_by' => false,
        ];
        $opts = array_merge( $defaults, $overrides );
        \WP_Mock::userFunction( 'wp_timezone_string', [ 'return' => 'UTC' ] );
        \WP_Mock::userFunction( 'get_option', [
            'return' => function( $key, $default = false ) use ( $opts ) {
                return $opts[$key] ?? $default;
            },
        ] );
    }

    // ── get_slots_for_date ────────────────────────────────────────────

    public function test_invalid_date_returns_empty_array() {
        $this->mockOptions(); // get_slots_for_date instancie DateTimeZone même pour une date invalide
        $result = WS_Scheduler_Slots::get_slots_for_date( 'not-a-date' );
        $this->assertIsArray( $result );
        $this->assertEmpty( $result );
    }

    public function test_past_date_returns_empty_array() {
        $this->mockOptions();
        $yesterday = gmdate( 'Y-m-d', strtotime( '-1 day' ) );
        $result    = WS_Scheduler_Slots::get_slots_for_date( $yesterday );
        $this->assertIsArray( $result );
        $this->assertEmpty( $result );
    }

    public function test_non_working_day_returns_empty_array() {
        $this->mockOptions( [ 'ws_working_days' => [1,2,3,4,5] ] ); // Lun-Ven seulement
        $next_sunday = gmdate( 'Y-m-d', strtotime( 'next sunday' ) );
        $result      = WS_Scheduler_Slots::get_slots_for_date( $next_sunday );
        $this->assertIsArray( $result );
        $this->assertEmpty( $result );
    }

    public function test_date_beyond_max_booking_window_returns_empty() {
        $this->mockOptions( [ 'ws_working_days' => [1,2,3,4,5,6,7], 'ws_max_booking_days' => 7 ] );
        $far = gmdate( 'Y-m-d', strtotime( '+60 days' ) );
        $result = WS_Scheduler_Slots::get_slots_for_date( $far );
        $this->assertIsArray( $result );
        $this->assertEmpty( $result );
    }

    public function test_get_slots_always_returns_array() {
        $this->mockOptions();
        $result = WS_Scheduler_Slots::get_slots_for_date( '2020-01-01' );
        $this->assertIsArray( $result );
    }

    public function test_slot_strings_match_h_i_format() {
        $this->mockOptions( [
            'ws_working_days'    => [1,2,3,4,5,6,7],
            'ws_max_booking_days'=> 365,
            'ws_work_start'      => '09:00',
            'ws_work_end'        => '10:00',
            'ws_slot_duration'   => 30,
        ] );
        $next_monday = gmdate( 'Y-m-d', strtotime( 'next monday' ) );
        $slots       = WS_Scheduler_Slots::get_slots_for_date( $next_monday );
        foreach ( $slots as $slot ) {
            $this->assertMatchesRegularExpression( '/^\d{2}:\d{2}$/', $slot );
        }
    }

    // ── get_month_availability ────────────────────────────────────────

    public function test_past_month_all_days_marked_past() {
        $this->mockOptions();
        $result = WS_Scheduler_Slots::get_month_availability( 2020, 1 );
        $this->assertCount( 31, $result );
        foreach ( $result as $status ) {
            $this->assertEquals( 'past', $status );
        }
    }

    public function test_month_availability_february_2020_has_29_days() {
        $this->mockOptions();
        $result = WS_Scheduler_Slots::get_month_availability( 2020, 2 );
        $this->assertCount( 29, $result );
    }

    public function test_month_availability_february_2021_has_28_days() {
        $this->mockOptions();
        $result = WS_Scheduler_Slots::get_month_availability( 2021, 2 );
        $this->assertCount( 28, $result );
    }

    public function test_month_availability_statuses_are_valid() {
        $this->mockOptions();
        $result = WS_Scheduler_Slots::get_month_availability( 2020, 6 );
        $valid  = [ 'past', 'closed', 'full', 'available' ];
        foreach ( $result as $status ) {
            $this->assertContains( $status, $valid );
        }
    }

    public function test_weekends_are_closed_with_mon_fri_schedule() {
        $this->mockOptions( [
            'ws_working_days'    => [1,2,3,4,5],
            'ws_max_booking_days'=> 365,
        ] );
        $result = WS_Scheduler_Slots::get_month_availability( 2027, 3 );
        foreach ( $result as $date_str => $status ) {
            $dow = (int) ( new DateTime( $date_str ) )->format( 'N' );
            if ( in_array( $dow, [6,7] ) ) {
                $this->assertNotEquals( 'available', $status );
            }
        }
    }

    public function test_future_month_has_no_past_days() {
        $this->mockOptions( [
            'ws_work_start'      => '09:00',
            'ws_work_end'        => '18:00',
            'ws_slot_duration'   => 30,
            'ws_max_booking_days'=> 999,
        ] );
        $future_year = (int) date('Y') + 2;
        $result = WS_Scheduler_Slots::get_month_availability( $future_year, 6 );
        foreach ( $result as $status ) {
            $this->assertNotEquals( 'past', $status );
        }
    }
}
