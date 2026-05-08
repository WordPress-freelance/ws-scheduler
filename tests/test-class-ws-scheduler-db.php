<?php
/**
 * Tests WS_Scheduler_DB — 18 tests.
 * Utilise MockWpdb injecté dans $GLOBALS['wpdb'].
 */
use WP_Mock\Tools\TestCase;

class Test_WS_Scheduler_DB extends TestCase {

    private MockWpdb $db;
    private $wpdb_backup;

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

    // ── Table names ──────────────────────────────────────────────────

    public function test_insert_appointment_uses_correct_table_name() {
        WS_Scheduler_DB::insert_appointment( [ 'first_name' => 'Sophie', 'email' => 's@test.com' ] );
        $this->assertEquals( 'wp_ws_appointments', $this->db->last_table );
    }

    public function test_insert_unavailability_uses_correct_table_name() {
        WS_Scheduler_DB::insert_unavailability( [ 'label' => 'Vacances' ] );
        $this->assertEquals( 'wp_ws_unavailabilities', $this->db->last_table );
    }

    // ── insert_appointment ───────────────────────────────────────────

    public function test_insert_appointment_calls_wpdb_insert() {
        $data = [ 'first_name' => 'Jean', 'last_name' => 'Dupont', 'email' => 'j@test.com' ];
        WS_Scheduler_DB::insert_appointment( $data );
        $this->assertEquals( 1, $this->db->count_calls( 'insert' ) );
    }

    public function test_insert_appointment_returns_insert_id() {
        $this->db->insert_id = 42;
        $result = WS_Scheduler_DB::insert_appointment( [ 'email' => 'x@test.com' ] );
        $this->assertEquals( 42, $result );
    }

    public function test_insert_appointment_passes_data_to_wpdb() {
        $data = [ 'first_name' => 'Marie', 'email' => 'm@test.com', 'status' => 'confirmed' ];
        WS_Scheduler_DB::insert_appointment( $data );
        $this->assertEquals( $data, $this->db->last_data );
    }

    // ── get_appointment ──────────────────────────────────────────────

    public function test_get_appointment_calls_get_row() {
        WS_Scheduler_DB::get_appointment( 1 );
        $this->assertEquals( 1, $this->db->count_calls( 'get_row' ) );
    }

    public function test_get_appointment_returns_null_when_not_found() {
        $this->db->return_rows = [];
        $result = WS_Scheduler_DB::get_appointment( 999 );
        $this->assertNull( $result );
    }

    public function test_get_appointment_returns_object_when_found() {
        $this->db->return_rows = [ [ 'id' => 7, 'first_name' => 'Sophie', 'status' => 'confirmed' ] ];
        $result = WS_Scheduler_DB::get_appointment( 7 );
        $this->assertIsObject( $result );
        $this->assertEquals( 7, $result->id );
    }

    // ── get_all_appointments ─────────────────────────────────────────

    public function test_get_all_appointments_calls_get_results() {
        WS_Scheduler_DB::get_all_appointments();
        $this->assertEquals( 1, $this->db->count_calls( 'get_results' ) );
    }

    public function test_get_all_appointments_returns_array() {
        $result = WS_Scheduler_DB::get_all_appointments();
        $this->assertIsArray( $result );
    }

    public function test_get_all_appointments_with_status_filter_uses_prepare() {
        WS_Scheduler_DB::get_all_appointments( [ 'status' => 'confirmed' ] );
        $this->assertEquals( 1, $this->db->count_calls( 'get_results' ) );
    }

    // ── update_appointment ───────────────────────────────────────────

    public function test_update_appointment_calls_wpdb_update() {
        WS_Scheduler_DB::update_appointment( 1, [ 'status' => 'cancelled' ] );
        $this->assertEquals( 1, $this->db->count_calls( 'update' ) );
    }

    public function test_update_appointment_uses_correct_table() {
        WS_Scheduler_DB::update_appointment( 5, [ 'status' => 'confirmed' ] );
        $this->assertEquals( 'wp_ws_appointments', $this->db->last_table );
    }

    public function test_update_appointment_passes_where_id() {
        WS_Scheduler_DB::update_appointment( 99, [ 'status' => 'pending' ] );
        $this->assertArrayHasKey( 'id', $this->db->last_where );
        $this->assertEquals( 99, $this->db->last_where['id'] );
    }

    // ── delete_appointment ───────────────────────────────────────────

    public function test_delete_appointment_calls_wpdb_delete() {
        WS_Scheduler_DB::delete_appointment( 3 );
        $this->assertEquals( 1, $this->db->count_calls( 'delete' ) );
    }

    // ── count_upcoming / count_by_status ────────────────────────────

    public function test_count_upcoming_returns_int() {
        $this->db->return_var = 7;
        $result = WS_Scheduler_DB::count_upcoming();
        $this->assertIsInt( $result );
        $this->assertEquals( 7, $result );
    }

    public function test_count_by_status_returns_int() {
        $this->db->return_var = 3;
        $result = WS_Scheduler_DB::count_by_status( 'confirmed' );
        $this->assertIsInt( $result );
        $this->assertEquals( 3, $result );
    }

    // ── unavailabilities ─────────────────────────────────────────────

    public function test_delete_unavailability_uses_correct_table() {
        WS_Scheduler_DB::delete_unavailability( 10 );
        $this->assertEquals( 'wp_ws_unavailabilities', $this->db->last_table );
    }
}
