<?php
use WP_Mock\Tools\TestCase;

class Test_WS_Scheduler_Admin extends TestCase {

    private WS_Scheduler_Admin $admin;

    public function setUp(): void {
        parent::setUp();
        $this->admin = new WS_Scheduler_Admin( 'ws-scheduler', '4.0.0' );
    }
    public function tearDown(): void { parent::tearDown(); }

    // ── admin_body_class ──────────────────────────────────────────────

    public function test_admin_body_class_adds_class_on_ws_page() {
        $screen     = new WP_Screen();
        $screen->id = 'toplevel_page_ws-scheduler';
        \WP_Mock::userFunction( 'get_current_screen', [ 'return' => $screen ] );
        $result = $this->admin->admin_body_class( 'existing' );
        $this->assertStringContainsString( 'ws-scheduler-page', $result );
    }

    public function test_admin_body_class_unchanged_on_other_page() {
        $screen     = new WP_Screen();
        $screen->id = 'dashboard';
        \WP_Mock::userFunction( 'get_current_screen', [ 'return' => $screen ] );
        $result = $this->admin->admin_body_class( 'existing' );
        $this->assertStringNotContainsString( 'ws-scheduler-page', $result );
    }

    public function test_admin_body_class_unchanged_when_no_screen() {
        \WP_Mock::userFunction( 'get_current_screen', [ 'return' => null ] );
        $this->assertEquals( 'my-class', $this->admin->admin_body_class( 'my-class' ) );
    }

    public function test_admin_body_class_preserves_existing_classes() {
        $screen     = new WP_Screen();
        $screen->id = 'ws-scheduler_page_ws-scheduler-settings';
        \WP_Mock::userFunction( 'get_current_screen', [ 'return' => $screen ] );
        $result = $this->admin->admin_body_class( 'pre-existing other' );
        $this->assertStringContainsString( 'pre-existing', $result );
        $this->assertStringContainsString( 'ws-scheduler-page', $result );
    }

    // ── admin_head_reset ──────────────────────────────────────────────

    public function test_admin_head_reset_outputs_style_on_ws_page() {
        $screen     = new WP_Screen();
        $screen->id = 'toplevel_page_ws-scheduler';
        \WP_Mock::userFunction( 'get_current_screen', [ 'return' => $screen ] );
        ob_start();
        $this->admin->admin_head_reset();
        $output = ob_get_clean();
        $this->assertStringContainsString( '<style>', $output );
        $this->assertStringContainsString( '#14121C', $output );
    }

    public function test_admin_head_reset_silent_on_unrelated_page() {
        $screen     = new WP_Screen();
        $screen->id = 'plugins';
        \WP_Mock::userFunction( 'get_current_screen', [ 'return' => $screen ] );
        ob_start();
        $this->admin->admin_head_reset();
        $this->assertEmpty( ob_get_clean() );
    }

    public function test_admin_head_reset_silent_when_no_screen() {
        \WP_Mock::userFunction( 'get_current_screen', [ 'return' => null ] );
        ob_start();
        $this->admin->admin_head_reset();
        $this->assertEmpty( ob_get_clean() );
    }

    // ── add_plugin_admin_menu ─────────────────────────────────────────

    public function test_add_plugin_admin_menu_registers_main_page() {
        \WP_Mock::userFunction( 'add_menu_page',    [ 'times' => 1, 'return' => 'ws-scheduler' ] );
        \WP_Mock::userFunction( 'add_submenu_page', [ 'times' => 4, 'return' => 'ws-scheduler' ] );
        $this->admin->add_plugin_admin_menu();
        $this->assertTrue( true ); // WP_Mock times assertions verified in tearDown
    }

    public function test_add_plugin_admin_menu_registers_correct_subpages() {
        \WP_Mock::userFunction( 'add_menu_page', [ 'return' => 'ws-scheduler' ] );
        $slugs = [];
        \WP_Mock::userFunction( 'add_submenu_page', [
            'return' => function( $p, $pt, $mt, $cap, $slug ) use ( &$slugs ) {
                $slugs[] = $slug; return $slug;
            },
        ] );
        $this->admin->add_plugin_admin_menu();
        $this->assertContains( 'ws-scheduler',          $slugs );
        $this->assertContains( 'ws-scheduler-unavail',  $slugs );
        $this->assertContains( 'ws-scheduler-settings', $slugs );
        $this->assertContains( 'ws-scheduler-licence',  $slugs );
    }

    // ── enqueue_styles ────────────────────────────────────────────────

    public function test_enqueue_styles_skips_on_unknown_hook() {
        \WP_Mock::userFunction( 'wp_enqueue_style', [ 'times' => 0 ] );
        \WP_Mock::userFunction( 'apply_filters',    [ 'return' => true ] );
        $this->admin->enqueue_styles( 'edit.php' );
        $this->assertTrue( true ); // WP_Mock times assertions verified in tearDown
    }

    public function test_enqueue_styles_loads_on_allowed_hook() {
        \WP_Mock::userFunction( 'apply_filters', [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_enqueue_style', [ 'times' => 2 ] ); // fonts + plugin css
        $this->admin->enqueue_styles( 'toplevel_page_ws-scheduler' );
        $this->assertTrue( true ); // WP_Mock times assertions verified in tearDown
    }

    // ── enqueue_scripts ───────────────────────────────────────────────

    public function test_enqueue_scripts_loads_on_allowed_hook() {
        \WP_Mock::userFunction( 'wp_enqueue_script',  [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_localize_script', [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_create_nonce',    [ 'return' => 'nonce' ] );
        \WP_Mock::userFunction( 'admin_url',          [ 'return' => 'http://example.com/wp-admin/admin-ajax.php' ] );
        $this->admin->enqueue_scripts( 'toplevel_page_ws-scheduler' );
        $this->assertTrue( true ); // WP_Mock times assertions verified in tearDown
    }

    public function test_enqueue_scripts_skips_on_unknown_hook() {
        \WP_Mock::userFunction( 'wp_enqueue_script',  [ 'times' => 0 ] );
        \WP_Mock::userFunction( 'wp_localize_script', [ 'times' => 0 ] );
        $this->admin->enqueue_scripts( 'edit.php' );
        $this->assertTrue( true ); // WP_Mock times assertions verified in tearDown
    }
}
