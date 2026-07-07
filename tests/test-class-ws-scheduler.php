<?php
use WP_Mock\Tools\TestCase;

class Test_WS_Scheduler extends TestCase {

    private function mockAllHooks() {
        \WP_Mock::userFunction( 'add_action' );
        \WP_Mock::userFunction( 'add_filter' );
        \WP_Mock::userFunction( 'add_shortcode' );
    }

    public function setUp(): void { parent::setUp(); }
    public function tearDown(): void { parent::tearDown(); }

    public function test_plugin_instantiation() {
        $this->mockAllHooks();
        $this->assertInstanceOf( 'WS_Scheduler', new WS_Scheduler() );
    }

    public function test_plugin_version() {
        $this->mockAllHooks();
        $this->assertEquals( '4.0.9', ( new WS_Scheduler() )->get_version() );
    }

    public function test_plugin_name() {
        $this->mockAllHooks();
        $this->assertEquals( 'ws-scheduler', ( new WS_Scheduler() )->get_plugin_name() );
    }

    public function test_plugin_loader() {
        $this->mockAllHooks();
        $this->assertInstanceOf( 'WS_Scheduler_Loader', ( new WS_Scheduler() )->get_loader() );
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
        $this->mockAllHooks();
        $this->assertInstanceOf( 'WS_Scheduler_Loader', ( new WS_Scheduler() )->get_loader() );
    }
}
