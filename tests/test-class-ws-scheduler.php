<?php
/**
 * Test pour la classe orchestratrice WS_Scheduler.
 */

use WP_Mock\Tools\TestCase;

class Test_WS_Scheduler extends TestCase {

    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    /**
     * Teste que la classe WS_Scheduler peut être instanciée.
     */
    public function test_plugin_instantiation() {
        \WP_Mock::userFunction( 'add_action' );
        \WP_Mock::userFunction( 'add_filter' );
        \WP_Mock::userFunction( 'add_shortcode' );

        $plugin = new WS_Scheduler();
        $this->assertInstanceOf( 'WS_Scheduler', $plugin );
    }

    /**
     * Teste que la version est correctement définie.
     */
    public function test_plugin_version() {
        \WP_Mock::userFunction( 'add_action' );
        \WP_Mock::userFunction( 'add_filter' );
        \WP_Mock::userFunction( 'add_shortcode' );

        $plugin = new WS_Scheduler();
        $this->assertEquals( '4.0.0', $plugin->get_version() );
    }

    /**
     * Teste que le nom du plugin est correct.
     */
    public function test_plugin_name() {
        \WP_Mock::userFunction( 'add_action' );
        \WP_Mock::userFunction( 'add_filter' );
        \WP_Mock::userFunction( 'add_shortcode' );

        $plugin = new WS_Scheduler();
        $this->assertEquals( 'ws-scheduler', $plugin->get_plugin_name() );
    }

    /**
     * Teste que le loader est correctement instancié.
     */
    public function test_plugin_loader() {
        \WP_Mock::userFunction( 'add_action' );
        \WP_Mock::userFunction( 'add_filter' );
        \WP_Mock::userFunction( 'add_shortcode' );

        $plugin = new WS_Scheduler();
        $loader = $plugin->get_loader();
        $this->assertInstanceOf( 'WS_Scheduler_Loader', $loader );
    }
}

    /**
     * Teste que run() peut être appelé sans fatal error.
     */
    public function test_plugin_run_executes_without_error() {
        \WP_Mock::userFunction( 'add_action' );
        \WP_Mock::userFunction( 'add_filter' );
        \WP_Mock::userFunction( 'add_shortcode' );

        $plugin = new WS_Scheduler();
        $plugin->run();
        $this->assertTrue( true );
    }

    /**
     * Teste que get_loader() retourne bien un WS_Scheduler_Loader valide.
     */
    public function test_loader_is_instance_of_ws_scheduler_loader() {
        \WP_Mock::userFunction( 'add_action' );
        \WP_Mock::userFunction( 'add_filter' );
        \WP_Mock::userFunction( 'add_shortcode' );

        $plugin = new WS_Scheduler();
        $this->assertInstanceOf( 'WS_Scheduler_Loader', $plugin->get_loader() );
    }
}
