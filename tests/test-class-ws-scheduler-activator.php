<?php
use WP_Mock\Tools\TestCase;

class Test_WS_Scheduler_Activator extends TestCase {

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

    private function mockActivateBase( bool $optionExists = false ) {
        \WP_Mock::userFunction( 'dbDelta', [ 'return' => [] ] );
        \WP_Mock::userFunction( 'get_option', [ 'return' => $optionExists ? [1,2,3,4,5] : false ] );
        \WP_Mock::userFunction( 'update_option', [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_cache_flush', [ 'return' => true ] );
        \WP_Mock::userFunction( 'do_action' );
        \WP_Mock::userFunction( 'get_bloginfo', [ 'return' => 'Test Blog' ] );
        \WP_Mock::userFunction( 'get_option' ); // catch-all for admin_email
    }

    public function test_activate_runs_without_fatal() {
        $this->mockActivateBase();
        WS_Scheduler_Activator::activate();
        $this->assertTrue( true );
    }

    public function test_activate_calls_dbdelta_twice() {
        \WP_Mock::userFunction( 'dbDelta', [ 'times' => 2, 'return' => [] ] );
        \WP_Mock::userFunction( 'get_option', [ 'return' => false ] );
        \WP_Mock::userFunction( 'update_option', [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_cache_flush', [ 'return' => true ] );
        \WP_Mock::userFunction( 'do_action' );
        \WP_Mock::userFunction( 'get_bloginfo', [ 'return' => 'Blog' ] );
        WS_Scheduler_Activator::activate();
    }

    public function test_activate_calls_update_option_when_options_missing() {
        \WP_Mock::userFunction( 'dbDelta', [ 'return' => [] ] );
        \WP_Mock::userFunction( 'get_option', [ 'return' => false ] );
        \WP_Mock::userFunction( 'wp_cache_flush', [ 'return' => true ] );
        \WP_Mock::userFunction( 'do_action' );
        \WP_Mock::userFunction( 'get_bloginfo', [ 'return' => 'Blog' ] );

        $updated = [];
        \WP_Mock::userFunction( 'update_option', [
            'return' => function( $k, $v ) use ( &$updated ) {
                $updated[$k] = $v;
                return true;
            },
        ] );

        WS_Scheduler_Activator::activate();
        $this->assertArrayHasKey( 'ws_working_days', $updated );
        $this->assertEquals( [1,2,3,4,5], $updated['ws_working_days'] );
    }

    public function test_activate_sets_default_work_start() {
        \WP_Mock::userFunction( 'dbDelta', [ 'return' => [] ] );
        \WP_Mock::userFunction( 'get_option', [ 'return' => false ] );
        \WP_Mock::userFunction( 'wp_cache_flush', [ 'return' => true ] );
        \WP_Mock::userFunction( 'do_action' );
        \WP_Mock::userFunction( 'get_bloginfo', [ 'return' => 'Blog' ] );

        $updated = [];
        \WP_Mock::userFunction( 'update_option', [
            'return' => function( $k, $v ) use ( &$updated ) { $updated[$k] = $v; return true; },
        ] );

        WS_Scheduler_Activator::activate();
        $this->assertEquals( '09:00', $updated['ws_work_start'] );
    }

    public function test_activate_sets_default_work_end() {
        \WP_Mock::userFunction( 'dbDelta', [ 'return' => [] ] );
        \WP_Mock::userFunction( 'get_option', [ 'return' => false ] );
        \WP_Mock::userFunction( 'wp_cache_flush', [ 'return' => true ] );
        \WP_Mock::userFunction( 'do_action' );
        \WP_Mock::userFunction( 'get_bloginfo', [ 'return' => 'Blog' ] );
        $updated = [];
        \WP_Mock::userFunction( 'update_option', [
            'return' => function( $k, $v ) use ( &$updated ) { $updated[$k] = $v; return true; },
        ] );
        WS_Scheduler_Activator::activate();
        $this->assertEquals( '18:00', $updated['ws_work_end'] );
    }

    public function test_activate_sets_default_slot_duration_30() {
        \WP_Mock::userFunction( 'dbDelta', [ 'return' => [] ] );
        \WP_Mock::userFunction( 'get_option', [ 'return' => false ] );
        \WP_Mock::userFunction( 'wp_cache_flush', [ 'return' => true ] );
        \WP_Mock::userFunction( 'do_action' );
        \WP_Mock::userFunction( 'get_bloginfo', [ 'return' => 'Blog' ] );
        $updated = [];
        \WP_Mock::userFunction( 'update_option', [
            'return' => function( $k, $v ) use ( &$updated ) { $updated[$k] = $v; return true; },
        ] );
        WS_Scheduler_Activator::activate();
        $this->assertEquals( 30, $updated['ws_slot_duration'] );
    }

    public function test_activate_skips_update_when_option_exists() {
        \WP_Mock::userFunction( 'dbDelta', [ 'return' => [] ] );
        \WP_Mock::userFunction( 'get_option', [ 'return' => [1,2,3,4,5] ] ); // options exist
        \WP_Mock::userFunction( 'wp_cache_flush', [ 'return' => true ] );
        \WP_Mock::userFunction( 'do_action' );
        \WP_Mock::userFunction( 'get_bloginfo', [ 'return' => 'Blog' ] );
        \WP_Mock::userFunction( 'update_option', [ 'times' => 0 ] );
        WS_Scheduler_Activator::activate();
    }

    public function test_activate_flushes_wp_cache() {
        \WP_Mock::userFunction( 'dbDelta', [ 'return' => [] ] );
        \WP_Mock::userFunction( 'get_option', [ 'return' => false ] );
        \WP_Mock::userFunction( 'update_option', [ 'return' => true ] );
        \WP_Mock::userFunction( 'do_action' );
        \WP_Mock::userFunction( 'get_bloginfo', [ 'return' => 'Blog' ] );
        \WP_Mock::userFunction( 'wp_cache_flush', [ 'times' => 1, 'return' => true ] );
        WS_Scheduler_Activator::activate();
    }

    public function test_purge_caches_calls_do_action_litespeed() {
        \WP_Mock::userFunction( 'do_action', [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_cache_flush', [ 'return' => true ] );
        WS_Scheduler_Activator::purge_page_caches();
    }
}
