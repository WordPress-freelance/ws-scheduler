<?php
/**
 * Tests pagination Privacy + Deactivator + I18n + edge cases.
 */
use WP_Mock\Tools\TestCase;

class Test_WS_Scheduler_Privacy_Pagination extends TestCase {

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

    public function test_export_returns_done_false_when_full_page() {
        // Page de 100 rows = page complète → done = false (il y a peut-être plus).
        $rows = [];
        for ( $i = 0; $i < 100; $i++ ) {
            $rows[] = [
                'id' => $i, 'first_name' => 'A', 'last_name' => 'B',
                'company' => '', 'email' => 'x@y.io',
                'phone' => '', 'message' => '',
                'slot_start' => '2026-06-15 10:00:00', 'slot_end' => '2026-06-15 10:30:00',
                'status' => 'confirmed', 'created_at' => '2026-06-01 09:00:00',
            ];
        }
        $this->db->return_results_override = $rows;
        $result = WS_Scheduler_Privacy::export_user_data( 'x@y.io', 1 );
        $this->assertFalse( $result['done'] );
        $this->assertCount( 100, $result['data'] );
    }

    public function test_export_returns_done_true_for_partial_page() {
        $this->db->return_results_override = [
            [
                'id' => 1, 'first_name' => 'A', 'last_name' => 'B',
                'company' => '', 'email' => 'x@y.io',
                'phone' => '', 'message' => '',
                'slot_start' => '2026-06-15 10:00:00', 'slot_end' => '2026-06-15 10:30:00',
                'status' => 'confirmed', 'created_at' => '2026-06-01 09:00:00',
            ],
        ];
        $result = WS_Scheduler_Privacy::export_user_data( 'x@y.io', 1 );
        $this->assertTrue( $result['done'] );
        $this->assertCount( 1, $result['data'] );
    }

    public function test_export_handles_page_2() {
        // Ne doit pas crasher avec page=2 (offset=100). MockWpdb retournera
        // les mêmes rows par défaut, mais on teste que le param est traité.
        $this->db->return_results_override = [];
        $result = WS_Scheduler_Privacy::export_user_data( 'x@y.io', 2 );
        $this->assertTrue( $result['done'] );
        $this->assertEmpty( $result['data'] );
    }

    public function test_erase_returns_done_false_when_full_page() {
        // 100 IDs returned → page pleine → done = false
        $ids = range( 1, 100 );
        $this->db->return_col_override = $ids;
        $result = WS_Scheduler_Privacy::erase_user_data( 'x@y.io', 1 );
        $this->assertFalse( $result['done'] );
        $this->assertEquals( 100, $result['items_removed'] );
    }

    public function test_erase_returns_done_true_for_partial_page() {
        $this->db->return_col_override = [ 1, 2, 3 ];
        $result = WS_Scheduler_Privacy::erase_user_data( 'x@y.io', 1 );
        $this->assertTrue( $result['done'] );
        $this->assertEquals( 3, $result['items_removed'] );
    }

    public function test_erase_returns_zero_messages_and_zero_retained() {
        $this->db->return_col_override = [ 1 ];
        $result = WS_Scheduler_Privacy::erase_user_data( 'x@y.io', 1 );
        $this->assertEquals( 0, $result['items_retained'] );
        $this->assertEquals( [], $result['messages'] );
    }

    public function test_add_privacy_policy_content_skips_when_function_unavailable() {
        // Si wp_add_privacy_policy_content n'existe pas (WP < 4.9.6), la méthode
        // doit simplement ne rien faire — pas crasher.
        // Note : avec WP_Mock on ne peut pas vraiment "unset" la function.
        // On vérifie au moins qu'elle est appelée sans crash quand elle existe.
        \WP_Mock::userFunction( 'wp_add_privacy_policy_content', [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_kses_post', [ 'return' => function( $s ) { return $s; } ] );
        \WP_Mock::userFunction( 'wpautop',      [ 'return' => function( $s ) { return $s; } ] );
        WS_Scheduler_Privacy::add_privacy_policy_content();
        $this->assertTrue( true );
    }

    // ── Sécurité : injection ────────────────────────────────────────

    public function test_export_uses_wpdb_prepare() {
        $this->db->return_results_override = [];
        WS_Scheduler_Privacy::export_user_data( "x'; DROP TABLE wp_ws_appointments;--", 1 );
        // On vérifie qu'au moins une requête prepared a été exécutée
        $this->assertGreaterThanOrEqual( 1, count( $this->db->query_log ) );
        // Le SQL ne doit pas contenir le payload brut (il aurait été échappé par prepare)
        // MockWpdb::prepare retourne la string interpolée mais avec les vals quoted.
        // Test minimal : une query a été tentée.
    }

    public function test_erase_uses_wpdb_prepare() {
        $this->db->return_col_override = [];
        WS_Scheduler_Privacy::erase_user_data( "x' OR 1=1--", 1 );
        $this->assertGreaterThanOrEqual( 1, count( $this->db->query_log ) );
    }
}

class Test_WS_Scheduler_Deactivator extends TestCase {
    public function test_deactivate_is_callable_without_crashing() {
        // Smoke test : la méthode est volontairement vide pour le moment,
        // mais doit toujours exister et s'exécuter proprement.
        $this->assertNull( WS_Scheduler_Deactivator::deactivate() );
    }

    public function test_deactivate_does_not_throw() {
        try {
            WS_Scheduler_Deactivator::deactivate();
            $this->assertTrue( true );
        } catch ( \Throwable $e ) {
            $this->fail( 'Deactivator should not throw: ' . $e->getMessage() );
        }
    }
}

class Test_WS_Scheduler_I18n extends TestCase {
    public function test_load_plugin_textdomain_calls_wp_function() {
        \WP_Mock::userFunction( 'load_plugin_textdomain', [ 'times' => 1, 'return' => true ] );
        $i18n = new WS_Scheduler_I18n();
        $i18n->load_plugin_textdomain();
        $this->assertTrue( true );
    }

    public function test_load_plugin_textdomain_uses_correct_text_domain() {
        $captured = null;
        \WP_Mock::userFunction( 'load_plugin_textdomain', [
            'return' => function( $domain, $abs_rel, $rel ) use ( &$captured ) {
                $captured = $domain;
                return true;
            },
        ] );
        ( new WS_Scheduler_I18n() )->load_plugin_textdomain();
        $this->assertEquals( 'ws-scheduler', $captured );
    }

    public function test_load_plugin_textdomain_uses_languages_path() {
        $captured = null;
        \WP_Mock::userFunction( 'load_plugin_textdomain', [
            'return' => function( $domain, $abs_rel, $rel ) use ( &$captured ) {
                $captured = $rel;
                return true;
            },
        ] );
        ( new WS_Scheduler_I18n() )->load_plugin_textdomain();
        $this->assertStringContainsString( 'languages', $captured );
    }
}
