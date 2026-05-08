<?php
/**
 * Tests DB avancés : filtres, edge cases, comptages, get_unavailabilities,
 * get_booked_slots, count_upcoming, count_by_status.
 */
use WP_Mock\Tools\TestCase;

class Test_WS_Scheduler_DB_Advanced extends TestCase {

    private $wpdb_backup;
    /** @var MockWpdb */
    private $db;

    public function setUp(): void {
        parent::setUp();
        $this->wpdb_backup = $GLOBALS['wpdb'] ?? null;
        $this->db          = new MockWpdb();
        $GLOBALS['wpdb']   = $this->db;
        \WP_Mock::userFunction( 'wp_parse_args', [
            'return' => function( $args, $defaults ) {
                if ( is_array( $args ) ) return array_merge( $defaults, $args );
                return $defaults;
            },
        ] );
        \WP_Mock::userFunction( 'sanitize_sql_orderby', [
            'return' => function( $s ) { return $s; },
        ] );
    }

    public function tearDown(): void {
        parent::tearDown();
        $GLOBALS['wpdb'] = $this->wpdb_backup;
    }

    // ── get_all_appointments avec différents args ────────────────

    public function test_get_all_appointments_default_params() {
        $this->db->return_results_override = [
            [ 'id' => 1, 'first_name' => 'A' ],
        ];
        $rows = WS_Scheduler_DB::get_all_appointments();
        $this->assertCount( 1, $rows );
        $this->assertStringContainsString( 'wp_ws_appointments', $this->db->last_sql );
    }

    public function test_get_all_appointments_filters_by_status() {
        $this->db->return_results_override = [];
        WS_Scheduler_DB::get_all_appointments( [ 'status' => 'confirmed' ] );
        $this->assertStringContainsString( 'status = ', $this->db->last_sql );
    }

    public function test_get_all_appointments_filters_by_date_range() {
        $this->db->return_results_override = [];
        WS_Scheduler_DB::get_all_appointments( [
            'date_from' => '2026-06-01',
            'date_to'   => '2026-06-30',
        ] );
        $this->assertStringContainsString( 'slot_start >= ', $this->db->last_sql );
        $this->assertStringContainsString( 'slot_start <= ', $this->db->last_sql );
    }

    public function test_get_all_appointments_orders_desc_by_default() {
        $this->db->return_results_override = [];
        WS_Scheduler_DB::get_all_appointments();
        $this->assertStringContainsString( 'DESC', strtoupper( $this->db->last_sql ) );
    }

    public function test_get_all_appointments_accepts_asc_order() {
        $this->db->return_results_override = [];
        WS_Scheduler_DB::get_all_appointments( [ 'order' => 'ASC' ] );
        $this->assertStringContainsString( 'ASC', strtoupper( $this->db->last_sql ) );
    }

    public function test_get_all_appointments_rejects_invalid_order_falls_to_desc() {
        $this->db->return_results_override = [];
        WS_Scheduler_DB::get_all_appointments( [ 'order' => 'DROP TABLE' ] );
        $this->assertStringNotContainsString( 'DROP', $this->db->last_sql );
        $this->assertStringContainsString( 'DESC', strtoupper( $this->db->last_sql ) );
    }

    public function test_get_all_appointments_applies_limit_offset() {
        $this->db->return_results_override = [];
        WS_Scheduler_DB::get_all_appointments( [ 'limit' => 25, 'offset' => 50 ] );
        $this->assertStringContainsString( 'LIMIT', strtoupper( $this->db->last_sql ) );
        $this->assertStringContainsString( 'OFFSET', strtoupper( $this->db->last_sql ) );
    }

    // ── get_booked_slots ─────────────────────────────────────────

    public function test_get_booked_slots_excludes_cancelled() {
        $this->db->return_results_override = [];
        WS_Scheduler_DB::get_booked_slots( '2026-06-15 00:00:00', '2026-06-15 23:59:59' );
        $this->assertStringContainsString( "status != 'cancelled'", $this->db->last_sql );
    }

    public function test_get_booked_slots_filters_by_range() {
        $this->db->return_results_override = [];
        WS_Scheduler_DB::get_booked_slots( '2026-06-15 00:00:00', '2026-06-15 23:59:59' );
        $this->assertStringContainsString( 'slot_start >=', $this->db->last_sql );
        $this->assertStringContainsString( 'slot_end <=',   $this->db->last_sql );
    }

    public function test_get_booked_slots_returns_objects() {
        $this->db->return_results_override = [
            [ 'slot_start' => '2026-06-15 10:00:00', 'slot_end' => '2026-06-15 10:30:00' ],
        ];
        $rows = WS_Scheduler_DB::get_booked_slots( 'a', 'b' );
        $this->assertIsObject( $rows[0] );
        $this->assertEquals( '2026-06-15 10:00:00', $rows[0]->slot_start );
    }

    // ── get_unavailabilities ─────────────────────────────────────

    public function test_get_unavailabilities_without_filter_uses_simple_query() {
        $this->db->return_results_override = [];
        WS_Scheduler_DB::get_unavailabilities();
        $this->assertStringContainsString( 'wp_ws_unavailabilities', $this->db->last_sql );
        $this->assertStringNotContainsString( 'is_recurring = 1', $this->db->last_sql );
    }

    public function test_get_unavailabilities_with_dates_includes_recurring_or_overlap_clause() {
        $this->db->return_results_override = [];
        WS_Scheduler_DB::get_unavailabilities( '2026-06-01 00:00:00', '2026-06-30 23:59:59' );
        $this->assertStringContainsString( 'is_recurring = 1', $this->db->last_sql );
        $this->assertStringContainsString( 'date_start <',     $this->db->last_sql );
        $this->assertStringContainsString( 'date_end >',       $this->db->last_sql );
    }

    public function test_get_unavailabilities_orders_recurring_first() {
        $this->db->return_results_override = [];
        WS_Scheduler_DB::get_unavailabilities();
        $this->assertStringContainsString( 'is_recurring ASC', $this->db->last_sql );
    }

    // ── Counts ────────────────────────────────────────────────────

    public function test_count_upcoming_returns_int() {
        $this->db->return_var = '42';
        $this->assertSame( 42, WS_Scheduler_DB::count_upcoming() );
    }

    public function test_count_upcoming_excludes_cancelled() {
        $this->db->return_var = 0;
        WS_Scheduler_DB::count_upcoming();
        $this->assertStringContainsString( "status != 'cancelled'", $this->db->last_sql );
    }

    public function test_count_upcoming_uses_now() {
        $this->db->return_var = 0;
        WS_Scheduler_DB::count_upcoming();
        $this->assertStringContainsString( 'NOW()', $this->db->last_sql );
    }

    public function test_count_by_status_returns_int() {
        $this->db->return_var = '7';
        $this->assertSame( 7, WS_Scheduler_DB::count_by_status( 'confirmed' ) );
    }

    public function test_count_by_status_zero_when_db_returns_null() {
        $this->db->return_var = null;
        $this->assertSame( 0, WS_Scheduler_DB::count_by_status( 'confirmed' ) );
    }

    // ── insert / update / delete ─────────────────────────────────

    public function test_insert_appointment_returns_inserted_id() {
        $this->db->insert_id = 99;
        $id = WS_Scheduler_DB::insert_appointment( [ 'first_name' => 'X' ] );
        $this->assertEquals( 99, $id );
    }

    public function test_insert_appointment_writes_to_correct_table() {
        WS_Scheduler_DB::insert_appointment( [ 'first_name' => 'X' ] );
        $this->assertEquals( 'wp_ws_appointments', $this->db->last_table );
    }

    public function test_update_appointment_uses_id_where_clause() {
        WS_Scheduler_DB::update_appointment( 7, [ 'status' => 'cancelled' ] );
        $this->assertEquals( 7, $this->db->last_where['id'] ?? null );
    }

    public function test_delete_appointment_uses_id_where_clause() {
        WS_Scheduler_DB::delete_appointment( 7 );
        $this->assertEquals( 7, $this->db->last_where['id'] ?? null );
    }

    public function test_insert_unavailability_returns_id() {
        $this->db->insert_id = 11;
        $id = WS_Scheduler_DB::insert_unavailability( [ 'label' => 'Test' ] );
        $this->assertEquals( 11, $id );
    }

    public function test_insert_unavailability_uses_unavail_table() {
        WS_Scheduler_DB::insert_unavailability( [ 'label' => 'X' ] );
        $this->assertEquals( 'wp_ws_unavailabilities', $this->db->last_table );
    }

    public function test_delete_unavailability_uses_id_where_clause() {
        WS_Scheduler_DB::delete_unavailability( 13 );
        $this->assertEquals( 13, $this->db->last_where['id'] ?? null );
    }
}
