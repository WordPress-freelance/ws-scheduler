<?php
/**
 * Tests WS_Scheduler_Loader — 10 tests.
 */
use WP_Mock\Tools\TestCase;

class Test_WS_Scheduler_Loader extends TestCase {

    private $loader;

    public function setUp(): void {
        parent::setUp();
        $this->loader = new WS_Scheduler_Loader();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    // ── add_action ───────────────────────────────────────────────────

    public function test_add_action_increments_actions_array() {
        $obj = new stdClass();
        $this->loader->add_action( 'init', $obj, 'my_method' );
        // run() appellera add_action WP — on vérifie qu'il y a bien 1 hook
        \WP_Mock::userFunction( 'add_action', [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'add_filter',   [ 'times' => 0 ] );
        \WP_Mock::userFunction( 'add_shortcode', [ 'times' => 0 ] );
        $this->loader->run();
    }

    public function test_add_action_stores_correct_hook_name() {
        $obj = new stdClass();
        $this->loader->add_action( 'wp_footer', $obj, 'render' );
        $called_hook = null;
        \WP_Mock::userFunction( 'add_action', [
            'times'  => 1,
            'return' => function( $hook ) use ( &$called_hook ) {
                $called_hook = $hook;
                return true;
            },
        ] );
        $this->loader->run();
        $this->assertEquals( 'wp_footer', $called_hook );
    }

    public function test_add_action_with_custom_priority() {
        $obj = new stdClass();
        $this->loader->add_action( 'init', $obj, 'method', 20 );
        $called_priority = null;
        \WP_Mock::userFunction( 'add_action', [
            'times'  => 1,
            'return' => function( $h, $cb, $p ) use ( &$called_priority ) {
                $called_priority = $p;
                return true;
            },
        ] );
        $this->loader->run();
        $this->assertEquals( 20, $called_priority );
    }

    // ── add_filter ───────────────────────────────────────────────────

    public function test_add_filter_increments_filters_array() {
        $obj = new stdClass();
        $this->loader->add_filter( 'the_content', $obj, 'filter_content' );
        \WP_Mock::userFunction( 'add_filter', [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'add_action',   [ 'times' => 0 ] );
        \WP_Mock::userFunction( 'add_shortcode', [ 'times' => 0 ] );
        $this->loader->run();
    }

    public function test_add_filter_stores_correct_hook_name() {
        $obj = new stdClass();
        $this->loader->add_filter( 'admin_body_class', $obj, 'add_class' );
        $called_hook = null;
        \WP_Mock::userFunction( 'add_filter', [
            'times'  => 1,
            'return' => function( $hook ) use ( &$called_hook ) {
                $called_hook = $hook;
                return true;
            },
        ] );
        $this->loader->run();
        $this->assertEquals( 'admin_body_class', $called_hook );
    }

    // ── add_shortcode ────────────────────────────────────────────────

    public function test_add_shortcode_registers_tag() {
        $obj = new stdClass();
        $this->loader->add_shortcode( 'ws_booking_button', $obj, 'render' );
        $called_tag = null;
        \WP_Mock::userFunction( 'add_shortcode', [
            'times'  => 1,
            'return' => function( $tag ) use ( &$called_tag ) {
                $called_tag = $tag;
                return true;
            },
        ] );
        $this->loader->run();
        $this->assertEquals( 'ws_booking_button', $called_tag );
    }

    // ── run ──────────────────────────────────────────────────────────

    public function test_run_processes_multiple_actions() {
        $obj = new stdClass();
        $this->loader->add_action( 'init',     $obj, 'a' );
        $this->loader->add_action( 'wp_head',  $obj, 'b' );
        $this->loader->add_action( 'wp_footer', $obj, 'c' );
        \WP_Mock::userFunction( 'add_action', [ 'times' => 3 ] );
        $this->loader->run();
    }

    public function test_run_processes_multiple_filters() {
        $obj = new stdClass();
        $this->loader->add_filter( 'the_content',      $obj, 'a' );
        $this->loader->add_filter( 'admin_body_class', $obj, 'b' );
        \WP_Mock::userFunction( 'add_filter', [ 'times' => 2 ] );
        $this->loader->run();
    }

    public function test_run_with_empty_loader_calls_nothing() {
        \WP_Mock::userFunction( 'add_action',   [ 'times' => 0 ] );
        \WP_Mock::userFunction( 'add_filter',   [ 'times' => 0 ] );
        \WP_Mock::userFunction( 'add_shortcode', [ 'times' => 0 ] );
        $this->loader->run(); // No hooks registered
        $this->assertTrue( true ); // Reached without errors
    }

    public function test_run_processes_mix_of_hooks_and_shortcodes() {
        $obj = new stdClass();
        $this->loader->add_action( 'init', $obj, 'a' );
        $this->loader->add_filter( 'the_content', $obj, 'b' );
        $this->loader->add_shortcode( 'my_shortcode', $obj, 'c' );
        \WP_Mock::userFunction( 'add_action',   [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'add_filter',   [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'add_shortcode', [ 'times' => 1 ] );
        $this->loader->run();
    }
}
