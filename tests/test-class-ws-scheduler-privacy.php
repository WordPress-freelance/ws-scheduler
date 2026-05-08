<?php
/**
 * Tests WS_Scheduler_Privacy — exporter + eraser + privacy policy content.
 */
use WP_Mock\Tools\TestCase;

class Test_WS_Scheduler_Privacy extends TestCase {

    private $wpdb_backup;

    public function setUp(): void {
        parent::setUp();
        $this->wpdb_backup = $GLOBALS['wpdb'] ?? null;
        $GLOBALS['wpdb']   = new MockWpdb();
    }

    public function tearDown(): void {
        parent::tearDown();
        $GLOBALS['wpdb'] = $this->wpdb_backup;
    }

    // ── register_exporter / register_eraser ───────────────────────────

    public function test_register_exporter_adds_ws_scheduler_key() {
        $result = WS_Scheduler_Privacy::register_exporter( [] );
        $this->assertArrayHasKey( 'ws-scheduler', $result );
        $this->assertArrayHasKey( 'callback', $result['ws-scheduler'] );
        $this->assertArrayHasKey( 'exporter_friendly_name', $result['ws-scheduler'] );
    }

    public function test_register_exporter_preserves_existing_exporters() {
        $result = WS_Scheduler_Privacy::register_exporter( [ 'other' => [ 'foo' => 'bar' ] ] );
        $this->assertArrayHasKey( 'other', $result );
        $this->assertArrayHasKey( 'ws-scheduler', $result );
    }

    public function test_register_eraser_adds_ws_scheduler_key() {
        $result = WS_Scheduler_Privacy::register_eraser( [] );
        $this->assertArrayHasKey( 'ws-scheduler', $result );
        $this->assertArrayHasKey( 'callback', $result['ws-scheduler'] );
        $this->assertArrayHasKey( 'eraser_friendly_name', $result['ws-scheduler'] );
    }

    public function test_register_eraser_preserves_existing_erasers() {
        $result = WS_Scheduler_Privacy::register_eraser( [ 'other' => [ 'foo' => 'bar' ] ] );
        $this->assertArrayHasKey( 'other', $result );
        $this->assertArrayHasKey( 'ws-scheduler', $result );
    }

    // ── export_user_data ──────────────────────────────────────────────

    public function test_export_user_data_returns_done_when_no_rows() {
        $GLOBALS['wpdb']->return_rows = [];
        $result = WS_Scheduler_Privacy::export_user_data( 'absent@example.com', 1 );
        $this->assertArrayHasKey( 'data', $result );
        $this->assertArrayHasKey( 'done', $result );
        $this->assertEmpty( $result['data'] );
        $this->assertTrue( $result['done'] );
    }

    public function test_export_user_data_returns_items_for_matching_rows() {
        $GLOBALS['wpdb']->return_rows = [
            [
                'id' => 7, 'first_name' => 'Sophie', 'last_name' => 'Martin',
                'company' => 'Acme', 'email' => 'sophie@example.com',
                'phone' => '+33600000000', 'message' => 'Test',
                'slot_start' => '2026-06-15 10:00:00', 'slot_end' => '2026-06-15 10:30:00',
                'status' => 'confirmed', 'created_at' => '2026-06-01 09:00:00',
            ],
        ];
        $result = WS_Scheduler_Privacy::export_user_data( 'sophie@example.com', 1 );
        $this->assertCount( 1, $result['data'] );
        $this->assertEquals( 'ws-scheduler', $result['data'][0]['group_id'] );
        $this->assertEquals( 'ws-appointment-7', $result['data'][0]['item_id'] );
        $this->assertTrue( $result['done'] );
    }

    public function test_export_user_data_includes_all_pii_fields() {
        $GLOBALS['wpdb']->return_rows = [
            [
                'id' => 1, 'first_name' => 'Alice', 'last_name' => 'Doe',
                'company' => '', 'email' => 'alice@example.com',
                'phone' => '', 'message' => '',
                'slot_start' => '2026-06-15 10:00:00', 'slot_end' => '2026-06-15 10:30:00',
                'status' => 'pending', 'created_at' => '2026-06-01 09:00:00',
            ],
        ];
        $result = WS_Scheduler_Privacy::export_user_data( 'alice@example.com', 1 );
        $names  = array_column( $result['data'][0]['data'], 'name' );
        // Vérifie qu'on retourne au moins les champs PII clés
        $this->assertContains( 'Email',     $names );
        $this->assertContains( 'Statut',    $names );
        $this->assertNotEmpty( array_intersect( [ 'Prénom', 'First Name' ], $names ) );
    }

    // ── erase_user_data ───────────────────────────────────────────────

    public function test_erase_user_data_returns_done_when_no_rows() {
        $GLOBALS['wpdb']->return_rows = [];
        $result = WS_Scheduler_Privacy::erase_user_data( 'absent@example.com', 1 );
        $this->assertArrayHasKey( 'items_removed', $result );
        $this->assertArrayHasKey( 'items_retained', $result );
        $this->assertArrayHasKey( 'done', $result );
        $this->assertEquals( 0, $result['items_removed'] );
        $this->assertTrue( $result['done'] );
    }

    public function test_erase_user_data_done_flag_true_when_less_than_page_size() {
        $GLOBALS['wpdb']->return_rows = [];
        $result = WS_Scheduler_Privacy::erase_user_data( 'x@y.com', 1 );
        $this->assertTrue( $result['done'] );
    }

    // ── add_privacy_policy_content ────────────────────────────────────

    public function test_add_privacy_policy_content_calls_wp_function() {
        \WP_Mock::userFunction( 'wp_add_privacy_policy_content', [ 'times' => 1, 'return' => true ] );
        \WP_Mock::userFunction( 'wp_kses_post', [ 'return' => function( $s ) { return $s; } ] );
        \WP_Mock::userFunction( 'wpautop',      [ 'return' => function( $s ) { return $s; } ] );
        WS_Scheduler_Privacy::add_privacy_policy_content();
        $this->assertTrue( true );
    }
}
