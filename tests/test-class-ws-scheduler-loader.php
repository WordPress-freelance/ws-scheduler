<?php
/**
 * Tests WS_Scheduler_Loader — 10 tests.
 *
 * add_action / add_filter / do_action sont gérés par Brain\Monkey
 * comme des "hook functions" avec un pipeline séparé → userFunction
 * avec times ne les intercepte pas.
 * On teste via Reflection l'état interne du Loader (plus pur et fiable),
 * et on vérifie l'exécution de run() sans crash.
 */
use WP_Mock\Tools\TestCase;

class Test_WS_Scheduler_Loader extends TestCase {

    private WS_Scheduler_Loader $loader;

    private function getPrivate( string $prop ) {
        $ref = new ReflectionProperty( WS_Scheduler_Loader::class, $prop );
        $ref->setAccessible( true );
        return $ref->getValue( $this->loader );
    }

    public function setUp(): void {
        parent::setUp();
        $this->loader = new WS_Scheduler_Loader();
    }

    public function tearDown(): void { parent::tearDown(); }

    // ── add_action — état interne ─────────────────────────────────────

    public function test_add_action_stores_hook_in_actions_array() {
        $obj = new stdClass();
        $this->loader->add_action( 'init', $obj, 'method' );
        $actions = $this->getPrivate( 'actions' );
        $this->assertCount( 1, $actions );
        $this->assertEquals( 'init', $actions[0]['hook'] );
    }

    public function test_add_action_stores_correct_hook_name() {
        $obj = new stdClass();
        $this->loader->add_action( 'wp_footer', $obj, 'render' );
        $actions = $this->getPrivate( 'actions' );
        $this->assertEquals( 'wp_footer', $actions[0]['hook'] );
    }

    public function test_add_action_stores_custom_priority() {
        $obj = new stdClass();
        $this->loader->add_action( 'init', $obj, 'method', 20 );
        $actions = $this->getPrivate( 'actions' );
        $this->assertEquals( 20, $actions[0]['priority'] );
    }

    public function test_add_action_does_not_pollute_filters() {
        $obj = new stdClass();
        $this->loader->add_action( 'init', $obj, 'method' );
        $this->assertEmpty( $this->getPrivate( 'filters' ) );
        $this->assertEmpty( $this->getPrivate( 'shortcodes' ) );
    }

    // ── add_filter — état interne ─────────────────────────────────────

    public function test_add_filter_stores_hook_in_filters_array() {
        $obj = new stdClass();
        $this->loader->add_filter( 'the_content', $obj, 'filter' );
        $filters = $this->getPrivate( 'filters' );
        $this->assertCount( 1, $filters );
        $this->assertEquals( 'the_content', $filters[0]['hook'] );
    }

    public function test_add_filter_stores_correct_hook_name() {
        $obj = new stdClass();
        $this->loader->add_filter( 'admin_body_class', $obj, 'add_class' );
        $filters = $this->getPrivate( 'filters' );
        $this->assertEquals( 'admin_body_class', $filters[0]['hook'] );
    }

    public function test_add_filter_does_not_pollute_actions() {
        $obj = new stdClass();
        $this->loader->add_filter( 'the_content', $obj, 'filter' );
        $this->assertEmpty( $this->getPrivate( 'actions' ) );
    }

    // ── add_shortcode ─────────────────────────────────────────────────

    public function test_add_shortcode_stores_tag() {
        $obj = new stdClass();
        $this->loader->add_shortcode( 'ws_booking_button', $obj, 'render' );
        $scs = $this->getPrivate( 'shortcodes' );
        $this->assertCount( 1, $scs );
        $this->assertEquals( 'ws_booking_button', $scs[0]['tag'] );
    }

    // ── run() — exécution sans crash ──────────────────────────────────

    public function test_run_with_empty_loader_executes_without_error() {
        // Aucun hook enregistré → run() ne fait rien, aucun appel WP
        $this->loader->run();
        $this->assertTrue( true ); // pas de fatal error
    }

    public function test_run_with_hooks_executes_without_error() {
        $obj = new stdClass();
        $this->loader->add_action( 'init', $obj, 'a' );
        $this->loader->add_filter( 'the_content', $obj, 'b' );
        $this->loader->add_shortcode( 'my_tag', $obj, 'c' );

        // run() appellera add_action, add_filter, add_shortcode — on laisse
        // Brain\Monkey les absorber sans assertion times
        \WP_Mock::userFunction( 'add_action' );
        \WP_Mock::userFunction( 'add_filter' );
        \WP_Mock::userFunction( 'add_shortcode' );
        $this->loader->run();
        $this->assertTrue( true );
    }
}
