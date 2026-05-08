<?php
use WP_Mock\Tools\TestCase;

class Test_WS_Scheduler_Loader extends TestCase {

    private WS_Scheduler_Loader $loader;

    public function setUp(): void {
        parent::setUp();
        $this->loader = new WS_Scheduler_Loader();
    }
    public function tearDown(): void { parent::tearDown(); }

    public function test_add_action_calls_wp_add_action_on_run() {
        $obj = new stdClass();
        $this->loader->add_action( 'init', $obj, 'method' );
        \WP_Mock::userFunction( 'add_action',   [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'add_filter',   [ 'times' => 0 ] );
        \WP_Mock::userFunction( 'add_shortcode',[ 'times' => 0 ] );
        $this->loader->run();
    }

    public function test_add_action_stores_correct_hook_name() {
        $obj = new stdClass();
        $this->loader->add_action( 'wp_footer', $obj, 'render' );
        $hook = null;
        \WP_Mock::userFunction( 'add_action', [
            'times'  => 1,
            'return' => function( $h ) use ( &$hook ) { $hook = $h; return true; },
        ] );
        $this->loader->run();
        $this->assertEquals( 'wp_footer', $hook );
    }

    public function test_add_action_with_custom_priority() {
        $obj = new stdClass();
        $this->loader->add_action( 'init', $obj, 'method', 20 );
        $priority = null;
        \WP_Mock::userFunction( 'add_action', [
            'times'  => 1,
            'return' => function( $h, $cb, $p ) use ( &$priority ) { $priority = $p; return true; },
        ] );
        $this->loader->run();
        $this->assertEquals( 20, $priority );
    }

    public function test_add_filter_calls_wp_add_filter_on_run() {
        $obj = new stdClass();
        $this->loader->add_filter( 'the_content', $obj, 'filter' );
        \WP_Mock::userFunction( 'add_filter',   [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'add_action',   [ 'times' => 0 ] );
        \WP_Mock::userFunction( 'add_shortcode',[ 'times' => 0 ] );
        $this->loader->run();
    }

    public function test_add_filter_stores_correct_hook_name() {
        $obj = new stdClass();
        $this->loader->add_filter( 'admin_body_class', $obj, 'add_class' );
        $hook = null;
        \WP_Mock::userFunction( 'add_filter', [
            'times'  => 1,
            'return' => function( $h ) use ( &$hook ) { $hook = $h; return true; },
        ] );
        $this->loader->run();
        $this->assertEquals( 'admin_body_class', $hook );
    }

    public function test_add_shortcode_registers_tag() {
        $obj = new stdClass();
        $this->loader->add_shortcode( 'ws_booking_button', $obj, 'render' );
        $tag = null;
        \WP_Mock::userFunction( 'add_shortcode', [
            'times'  => 1,
            'return' => function( $t ) use ( &$tag ) { $tag = $t; return true; },
        ] );
        $this->loader->run();
        $this->assertEquals( 'ws_booking_button', $tag );
    }

    public function test_run_processes_multiple_actions() {
        $obj = new stdClass();
        $this->loader->add_action( 'init',      $obj, 'a' );
        $this->loader->add_action( 'wp_head',   $obj, 'b' );
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
        \WP_Mock::userFunction( 'add_shortcode',[ 'times' => 0 ] );
        $this->loader->run();
        $this->assertTrue( true );
    }

    public function test_run_processes_mix_of_hooks_and_shortcodes() {
        $obj = new stdClass();
        $this->loader->add_action( 'init', $obj, 'a' );
        $this->loader->add_filter( 'the_content', $obj, 'b' );
        $this->loader->add_shortcode( 'my_shortcode', $obj, 'c' );
        \WP_Mock::userFunction( 'add_action',   [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'add_filter',   [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'add_shortcode',[ 'times' => 1 ] );
        $this->loader->run();
    }
}
