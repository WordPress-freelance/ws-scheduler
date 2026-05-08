<?php
/**
 * Tests WS_Scheduler — orchestrateur principal. 6 tests.
 */
use WP_Mock\Tools\TestCase;

class Test_WS_Scheduler extends TestCase {

    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    public function test_plugin_instantiation() {
        \WP_Mock::userFunction( 'add_action' );
        \WP_Mock::userFunction( 'add_filter' );
        \WP_Mock::userFunction( 'add_shortcode' );

        $plugin = new WS_Scheduler();
        $this->assertInstanceOf( 'WS_Scheduler', $plugin );
    }

    public function test_plugin_version() {
        \WP_Mock::userFunction( 'add_action' );
        \WP_Mock::userFunction( 'add_filter' );
        \WP_Mock::userFunction( 'add_shortcode' );

        $plugin = new WS_Scheduler();
        $this->assertEquals( '4.0.0', $plugin->get_version() );
    }

    public function test_plugin_name() {
        \WP_Mock::userFunction( 'add_action' );
        \WP_Mock::userFunction( 'add_filter' );
        \WP_Mock::userFunction( 'add_shortcode' );

        $plugin = new WS_Scheduler();
        $this->assertEquals( 'ws-scheduler', $plugin->get_plugin_name() );
    }

    public function test_plugin_loader() {
        \WP_Mock::userFunction( 'add_action' );
        \WP_Mock::userFunction( 'add_filter' );
        \WP_Mock::userFunction( 'add_shortcode' );

        $plugin = new WS_Scheduler();
        $this->assertInstanceOf( 'WS_Scheduler_Loader', $plugin->get_loader() );
    }

    public function test_plugin_run_executes_without_error() {
        \WP_Mock::userFunction( 'add_action' );
        \WP_Mock::userFunction( 'add_filter' );
        \WP_Mock::userFunction( 'add_shortcode' );

        $plugin = new WS_Scheduler();
        $plugin->run();
        $this->assertTrue( true );
    }

    public function test_loader_is_instance_of_ws_scheduler_loader() {
        \WP_Mock::userFunction( 'add_action' );
        \WP_Mock::userFunction( 'add_filter' );
        \WP_Mock::userFunction( 'add_shortcode' );

        $plugin = new WS_Scheduler();
        $this->assertInstanceOf( 'WS_Scheduler_Loader', $plugin->get_loader() );
    }
}
