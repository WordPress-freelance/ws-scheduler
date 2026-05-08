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

    public function test_activate_runs_without_fatal() {
        \WP_Mock::userFunction( 'dbDelta',      [ 'return' => [] ] );
        \WP_Mock::userFunction( 'get_option',   [ 'return' => false ] );
        \WP_Mock::userFunction( 'update_option',[ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_cache_flush',[ 'return' => true ] );
        \WP_Mock::userFunction( 'do_action' ); // hook Brain\Monkey — pas de times
        \WP_Mock::userFunction( 'get_bloginfo', [ 'return' => 'Blog' ] );
        WS_Scheduler_Activator::activate();
        $this->assertTrue( true );
    }

    public function test_activate_calls_dbdelta_twice() {
        \WP_Mock::userFunction( 'dbDelta',      [ 'times' => 2, 'return' => [] ] );
        \WP_Mock::userFunction( 'get_option',   [ 'return' => false ] );
        \WP_Mock::userFunction( 'update_option',[ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_cache_flush',[ 'return' => true ] );
        \WP_Mock::userFunction( 'do_action' );
        \WP_Mock::userFunction( 'get_bloginfo', [ 'return' => 'Blog' ] );
        WS_Scheduler_Activator::activate();
        // dbDelta times assertion handled by WP_Mock tearDown
        $this->assertTrue( true );
    }

    public function test_activate_sets_default_working_days() {
        \WP_Mock::userFunction( 'dbDelta',      [ 'return' => [] ] );
        \WP_Mock::userFunction( 'get_option',   [ 'return' => false ] );
        \WP_Mock::userFunction( 'wp_cache_flush',[ 'return' => true ] );
        \WP_Mock::userFunction( 'do_action' );
        \WP_Mock::userFunction( 'get_bloginfo', [ 'return' => 'Blog' ] );
        $updated = [];
        \WP_Mock::userFunction( 'update_option', [
            'return' => function( $k, $v ) use ( &$updated ) { $updated[$k] = $v; return true; },
        ] );
        WS_Scheduler_Activator::activate();
        $this->assertArrayHasKey( 'ws_working_days', $updated );
        $this->assertEquals( [1,2,3,4,5], $updated['ws_working_days'] );
    }

    public function test_activate_sets_default_work_start() {
        \WP_Mock::userFunction( 'dbDelta',      [ 'return' => [] ] );
        \WP_Mock::userFunction( 'get_option',   [ 'return' => false ] );
        \WP_Mock::userFunction( 'wp_cache_flush',[ 'return' => true ] );
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
        \WP_Mock::userFunction( 'dbDelta',      [ 'return' => [] ] );
        \WP_Mock::userFunction( 'get_option',   [ 'return' => false ] );
        \WP_Mock::userFunction( 'wp_cache_flush',[ 'return' => true ] );
        \WP_Mock::userFunction( 'do_action' );
        \WP_Mock::userFunction( 'get_bloginfo', [ 'return' => 'Blog' ] );
        $updated = [];
        \WP_Mock::userFunction( 'update_option', [
            'return' => function( $k, $v ) use ( &$updated ) { $updated[$k] = $v; return true; },
        ] );
        WS_Scheduler_Activator::activate();
        $this->assertEquals( '18:00', $updated['ws_work_end'] );
    }

    public function test_activate_sets_default_slot_duration() {
        \WP_Mock::userFunction( 'dbDelta',      [ 'return' => [] ] );
        \WP_Mock::userFunction( 'get_option',   [ 'return' => false ] );
        \WP_Mock::userFunction( 'wp_cache_flush',[ 'return' => true ] );
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
        \WP_Mock::userFunction( 'dbDelta',       [ 'return' => [] ] );
        \WP_Mock::userFunction( 'get_option',    [ 'return' => [1,2,3,4,5] ] );
        \WP_Mock::userFunction( 'wp_cache_flush',[ 'return' => true ] );
        \WP_Mock::userFunction( 'do_action' );
        \WP_Mock::userFunction( 'get_bloginfo',  [ 'return' => 'Blog' ] );
        \WP_Mock::userFunction( 'update_option', [ 'times' => 0 ] );
        WS_Scheduler_Activator::activate();
        $this->assertTrue( true );
    }

    public function test_activate_flushes_wp_cache() {
        \WP_Mock::userFunction( 'dbDelta',       [ 'return' => [] ] );
        \WP_Mock::userFunction( 'get_option',    [ 'return' => false ] );
        \WP_Mock::userFunction( 'update_option', [ 'return' => true ] );
        \WP_Mock::userFunction( 'do_action' );
        \WP_Mock::userFunction( 'get_bloginfo',  [ 'return' => 'Blog' ] );
        \WP_Mock::userFunction( 'wp_cache_flush',[ 'times' => 1, 'return' => true ] );
        WS_Scheduler_Activator::activate();
        $this->assertTrue( true );
    }

    public function test_purge_caches_runs_without_error() {
        // do_action est géré par Brain\Monkey comme hook — pas de times assertion
        \WP_Mock::userFunction( 'do_action' );
        \WP_Mock::userFunction( 'wp_cache_flush', [ 'return' => true ] );
        WS_Scheduler_Activator::purge_page_caches();
        $this->assertTrue( true );
    }
}
