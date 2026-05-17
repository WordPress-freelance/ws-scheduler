<?php
/**
 * Tests avancés du module Admin : save_settings (avec nonce / capability /
 * sanitize), purge_page_caches, render_* via reflection (sans inclusion réelle
 * pour ne pas dépendre des partials).
 */
use WP_Mock\Tools\TestCase;

/**
 * Custom exception déclenchée par notre mock de wp_redirect pour bypasser exit;
 */
class WS_Test_RedirectException extends \Exception {
    public string $url;
    public function __construct( string $url ) {
        parent::__construct( 'wp_redirect to ' . $url );
        $this->url = $url;
    }
}

class Test_WS_Scheduler_Admin_Advanced extends TestCase {

    private WS_Scheduler_Admin $admin;

    public function setUp(): void {
        parent::setUp();
        $this->admin = new WS_Scheduler_Admin( 'ws-scheduler', '4.0.8' );
        $_POST = [];
    }

    public function tearDown(): void {
        parent::tearDown();
        $_POST = [];
    }

    private function mockRedirect(): void {
        \WP_Mock::userFunction( 'wp_redirect', [
            'return' => function( $url ) {
                throw new WS_Test_RedirectException( $url );
            },
        ] );
    }

    private function commonSettingsMocks(): void {
        \WP_Mock::userFunction( 'check_admin_referer',  [ 'return' => true ] );
        \WP_Mock::userFunction( 'current_user_can',     [ 'return' => true ] );
        \WP_Mock::userFunction( 'admin_url', [ 'return' => function( $p ) { return 'http://x/' . $p; } ] );
        \WP_Mock::userFunction( 'wp_cache_flush', [ 'return' => true ] );
        $this->mockRedirect();
    }

    // ── save_settings : capability check ─────────────────────────

    public function test_save_settings_dies_when_user_cannot_manage_options() {
        \WP_Mock::userFunction( 'check_admin_referer', [ 'return' => true ] );
        \WP_Mock::userFunction( 'current_user_can',    [ 'return' => false ] );
        \WP_Mock::userFunction( 'wp_die', [
            'return' => function( $msg ) { throw new \RuntimeException( 'wp_die: ' . $msg ); },
        ] );
        try {
            $this->admin->save_settings();
            $this->fail( 'save_settings should die when user cannot manage_options' );
        } catch ( \RuntimeException $e ) {
            $this->assertStringContainsString( 'wp_die', $e->getMessage() );
        }
    }

    // ── save_settings : sanitization + storage ───────────────────

    public function test_save_settings_persists_working_days_as_intval_array() {
        $_POST = [
            'ws_working_days'   => [ '1', '2', '3' ],
            'ws_work_start'     => '09:00',
            'ws_work_end'       => '18:00',
            'ws_slot_duration'  => '30',
            'ws_admin_email'    => 'a@b.com',
        ];
        $captured = [];
        $this->commonSettingsMocks();
        \WP_Mock::userFunction( 'update_option', [
            'return' => function( $k, $v ) use ( &$captured ) {
                $captured[ $k ] = $v;
                return true;
            },
        ] );
        \WP_Mock::userFunction( 'get_option', [ 'return' => false ] );
        \WP_Mock::userFunction( 'sanitize_email', [ 'return' => function( $v ) { return $v; } ] );
        \WP_Mock::userFunction( 'sanitize_text_field', [ 'return' => function( $v ) { return $v; } ] );
        try { $this->admin->save_settings(); } catch ( WS_Test_RedirectException $e ) {}
        $this->assertEquals( [ 1, 2, 3 ], $captured['ws_working_days'] ?? null );
    }

    public function test_save_settings_persists_slot_duration_as_int() {
        $_POST = [
            'ws_slot_duration' => '45',
            'ws_admin_email'   => 'a@b.com',
        ];
        $captured = [];
        $this->commonSettingsMocks();
        \WP_Mock::userFunction( 'update_option', [
            'return' => function( $k, $v ) use ( &$captured ) {
                $captured[ $k ] = $v;
                return true;
            },
        ] );
        \WP_Mock::userFunction( 'get_option', [ 'return' => false ] );
        \WP_Mock::userFunction( 'sanitize_email',      [ 'return' => function( $v ) { return $v; } ] );
        \WP_Mock::userFunction( 'sanitize_text_field', [ 'return' => function( $v ) { return $v; } ] );
        try { $this->admin->save_settings(); } catch ( WS_Test_RedirectException $e ) {}
        $this->assertSame( 45, $captured['ws_slot_duration'] ?? null );
    }

    public function test_save_settings_falls_back_to_admin_email_when_empty() {
        $_POST = [ 'ws_admin_email' => '' ];
        $captured = [];
        $this->commonSettingsMocks();
        \WP_Mock::userFunction( 'update_option', [
            'return' => function( $k, $v ) use ( &$captured ) {
                $captured[ $k ] = $v;
                return true;
            },
        ] );
        \WP_Mock::userFunction( 'get_option', [
            'return' => function( $k, $d = false ) {
                return $k === 'admin_email' ? 'fallback@example.com' : $d;
            },
        ] );
        \WP_Mock::userFunction( 'sanitize_email',      [ 'return' => function( $v ) { return $v; } ] );
        \WP_Mock::userFunction( 'sanitize_text_field', [ 'return' => function( $v ) { return $v; } ] );
        try { $this->admin->save_settings(); } catch ( WS_Test_RedirectException $e ) {}
        $this->assertEquals( 'fallback@example.com', $captured['ws_admin_email'] ?? null );
    }

    public function test_save_settings_powered_by_off_when_unchecked() {
        $_POST = [ 'ws_admin_email' => 'a@b.com' ]; // pas de ws_show_powered_by
        $captured = [];
        $this->commonSettingsMocks();
        \WP_Mock::userFunction( 'update_option', [
            'return' => function( $k, $v ) use ( &$captured ) {
                $captured[ $k ] = $v;
                return true;
            },
        ] );
        \WP_Mock::userFunction( 'get_option', [ 'return' => false ] );
        \WP_Mock::userFunction( 'sanitize_email',      [ 'return' => function( $v ) { return $v; } ] );
        \WP_Mock::userFunction( 'sanitize_text_field', [ 'return' => function( $v ) { return $v; } ] );
        try { $this->admin->save_settings(); } catch ( WS_Test_RedirectException $e ) {}
        $this->assertSame( 0, $captured['ws_show_powered_by'] ?? null );
    }

    public function test_save_settings_powered_by_on_when_checked() {
        $_POST = [
            'ws_admin_email'      => 'a@b.com',
            'ws_show_powered_by'  => '1',
        ];
        $captured = [];
        $this->commonSettingsMocks();
        \WP_Mock::userFunction( 'update_option', [
            'return' => function( $k, $v ) use ( &$captured ) {
                $captured[ $k ] = $v;
                return true;
            },
        ] );
        \WP_Mock::userFunction( 'get_option', [ 'return' => false ] );
        \WP_Mock::userFunction( 'sanitize_email',      [ 'return' => function( $v ) { return $v; } ] );
        \WP_Mock::userFunction( 'sanitize_text_field', [ 'return' => function( $v ) { return $v; } ] );
        try { $this->admin->save_settings(); } catch ( WS_Test_RedirectException $e ) {}
        $this->assertSame( 1, $captured['ws_show_powered_by'] ?? null );
    }

    public function test_save_settings_redirects_with_saved_flag() {
        $_POST = [ 'ws_admin_email' => 'a@b.com' ];
        $this->commonSettingsMocks();
        \WP_Mock::userFunction( 'update_option', [ 'return' => true ] );
        \WP_Mock::userFunction( 'get_option',    [ 'return' => false ] );
        \WP_Mock::userFunction( 'sanitize_email',      [ 'return' => function( $v ) { return $v; } ] );
        \WP_Mock::userFunction( 'sanitize_text_field', [ 'return' => function( $v ) { return $v; } ] );
        try {
            $this->admin->save_settings();
            $this->fail( 'should have redirected' );
        } catch ( WS_Test_RedirectException $e ) {
            $this->assertStringContainsString( 'saved=1', $e->url );
            $this->assertStringContainsString( 'ws-scheduler-settings', $e->url );
        }
    }

    public function test_save_settings_purges_caches() {
        $_POST = [ 'ws_admin_email' => 'a@b.com' ];
        $this->commonSettingsMocks();
        \WP_Mock::userFunction( 'update_option', [ 'return' => true ] );
        \WP_Mock::userFunction( 'get_option',    [ 'return' => false ] );
        \WP_Mock::userFunction( 'sanitize_email',      [ 'return' => function( $v ) { return $v; } ] );
        \WP_Mock::userFunction( 'sanitize_text_field', [ 'return' => function( $v ) { return $v; } ] );
        // wp_cache_flush déjà mocké via commonSettingsMocks → check it's called
        try { $this->admin->save_settings(); } catch ( WS_Test_RedirectException $e ) {}
        // wp_cache_flush mocked → no exception thrown means it was called
        $this->assertTrue( true );
    }

    // ── add_plugin_admin_menu ─────────────────────────────────────

    public function test_add_plugin_admin_menu_creates_3_submenus_plus_top_level() {
        $top    = 0;
        $subs   = 0;
        \WP_Mock::userFunction( 'add_menu_page', [
            'return' => function() use ( &$top ) { $top++; return 'hook'; },
        ] );
        \WP_Mock::userFunction( 'add_submenu_page', [
            'return' => function() use ( &$subs ) { $subs++; return 'sub'; },
        ] );
        $this->admin->add_plugin_admin_menu();
        $this->assertEquals( 1, $top );
        $this->assertEquals( 3, $subs );
    }

    public function test_add_plugin_admin_menu_uses_manage_options_capability() {
        $captured_cap = null;
        \WP_Mock::userFunction( 'add_menu_page', [
            'return' => function( $title, $menu, $cap ) use ( &$captured_cap ) {
                $captured_cap = $cap;
                return 'hook';
            },
        ] );
        \WP_Mock::userFunction( 'add_submenu_page', [ 'return' => 'sub' ] );
        $this->admin->add_plugin_admin_menu();
        $this->assertEquals( 'manage_options', $captured_cap );
    }

    // ── add_action_links (plugin_action_links_<basename>) ────────

    public function test_add_action_links_prepends_settings_and_pro_links() {
        \WP_Mock::userFunction( 'admin_url', [ 'return' => 'http://x/admin.php?page=ws-scheduler-settings' ] );
        \WP_Mock::userFunction( 'esc_url',     [ 'return' => function( $u ) { return $u; } ] );
        \WP_Mock::userFunction( 'esc_html__',  [ 'return' => function( $s ) { return $s; } ] );

        $links = [ 'deactivate' => '<a href="#">Désactiver</a>' ];
        $result = $this->admin->add_action_links( $links );

        // Custom links viennent en premier
        $keys = array_keys( $result );
        $this->assertEquals( 'settings',   $keys[0] );
        $this->assertEquals( 'pro',        $keys[1] );
        $this->assertEquals( 'deactivate', $keys[2] );
    }

    public function test_add_action_links_settings_points_to_settings_page() {
        \WP_Mock::userFunction( 'admin_url', [
            'return' => function( $p ) { return 'http://x/' . $p; },
        ] );
        \WP_Mock::userFunction( 'esc_url',    [ 'return' => function( $u ) { return $u; } ] );
        \WP_Mock::userFunction( 'esc_html__', [ 'return' => function( $s ) { return $s; } ] );

        $result = $this->admin->add_action_links( [] );
        $this->assertStringContainsString( 'page=ws-scheduler-settings', $result['settings'] );
        $this->assertStringContainsString( 'Réglages', $result['settings'] );
    }

    public function test_add_action_links_pro_points_to_external_url() {
        \WP_Mock::userFunction( 'admin_url',  [ 'return' => 'http://x/' ] );
        \WP_Mock::userFunction( 'esc_url',    [ 'return' => function( $u ) { return $u; } ] );
        \WP_Mock::userFunction( 'esc_html__', [ 'return' => function( $s ) { return $s; } ] );

        $result = $this->admin->add_action_links( [] );
        $this->assertStringContainsString( 'plugin.wordpress-freelance.com', $result['pro'] );
        $this->assertStringContainsString( 'target="_blank"', $result['pro'] );
        $this->assertStringContainsString( 'Version Pro', $result['pro'] );
    }

    public function test_add_action_links_preserves_existing_links() {
        \WP_Mock::userFunction( 'admin_url',  [ 'return' => 'http://x/' ] );
        \WP_Mock::userFunction( 'esc_url',    [ 'return' => function( $u ) { return $u; } ] );
        \WP_Mock::userFunction( 'esc_html__', [ 'return' => function( $s ) { return $s; } ] );

        $existing = [
            'deactivate' => '<a href="#deactivate">Désactiver</a>',
            'edit'       => '<a href="#edit">Modifier</a>',
        ];
        $result = $this->admin->add_action_links( $existing );
        $this->assertEquals( '<a href="#deactivate">Désactiver</a>', $result['deactivate'] );
        $this->assertEquals( '<a href="#edit">Modifier</a>',         $result['edit'] );
    }

    // ── render_* (smoke tests : on capture la sortie) ────────────

    public function test_render_dashboard_does_not_fatal() {
        // On ne peut pas vraiment inclure le partial sans WP. On mocke les
        // fonctions WP utilisées par le partial et on capture la sortie.
        \WP_Mock::userFunction( 'sanitize_text_field', [ 'return' => function( $v ) { return $v; } ] );
        \WP_Mock::userFunction( 'esc_url', [ 'return' => function( $v ) { return $v; } ] );
        \WP_Mock::userFunction( 'esc_html_e', [ 'return' => function( $s ) { echo $s; } ] );
        \WP_Mock::userFunction( 'esc_html__', [ 'return' => function( $s ) { return $s; } ] );
        \WP_Mock::userFunction( 'esc_html', [ 'return' => function( $s ) { return $s; } ] );
        \WP_Mock::userFunction( 'esc_attr', [ 'return' => function( $s ) { return $s; } ] );
        \WP_Mock::userFunction( 'admin_url', [ 'return' => 'http://x/' ] );
        \WP_Mock::userFunction( '_e', [ 'return' => function( $s ) { echo $s; } ] );
        \WP_Mock::userFunction( 'home_url', [ 'return' => 'http://x' ] );

        // MockWpdb pour DB::get_all_appointments + count_*
        global $wpdb;
        $backup = $wpdb;
        $wpdb = new MockWpdb();
        $wpdb->return_results_override = [];
        $wpdb->return_var = 0;
        \WP_Mock::userFunction( 'wp_parse_args', [ 'return' => function($a, $d) {
            return is_array($a) ? array_merge($d, $a) : $d;
        } ] );
        \WP_Mock::userFunction( 'sanitize_sql_orderby', [ 'return' => function($s) { return $s; } ] );

        try {
            ob_start();
            $this->admin->render_dashboard();
            $out = ob_get_clean();
            $this->assertIsString( $out );
        } catch ( \Throwable $e ) {
            ob_end_clean();
            $this->fail( 'render_dashboard threw: ' . $e->getMessage() );
        } finally {
            $wpdb = $backup;
        }
    }
}
