<?php
/**
 * Tests WS_Scheduler_Public — 10 tests.
 */
use WP_Mock\Tools\TestCase;

class Test_WS_Scheduler_Public extends TestCase {

    private WS_Scheduler_Public $public;

    public function setUp(): void {
        parent::setUp();
        // Reset static flag entre les tests
        $ref = new ReflectionProperty( WS_Scheduler_Public::class, 'popup_rendered' );
        $ref->setAccessible( true );
        $ref->setValue( null, false );

        $this->public = new WS_Scheduler_Public( 'ws-scheduler', '4.0.0' );
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    // ── enqueue_styles ───────────────────────────────────────────────

    public function test_enqueue_styles_calls_wp_enqueue_style() {
        \WP_Mock::userFunction( 'wp_enqueue_style', [ 'times' => 1 ] );
        $this->public->enqueue_styles();
    }

    // ── enqueue_scripts ──────────────────────────────────────────────

    public function test_enqueue_scripts_registers_handle() {
        \WP_Mock::userFunction( 'wp_register_script',  [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_enqueue_script',   [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_localize_script',  [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_add_inline_script',[ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_create_nonce',     [ 'return' => 'nonce_abc' ] );
        \WP_Mock::userFunction( 'admin_url',           [ 'return' => 'http://example.com/wp-admin/admin-ajax.php' ] );
        \WP_Mock::userFunction( 'get_option',          [ 'return' => 90 ] );

        $this->public->enqueue_scripts();
    }

    public function test_enqueue_scripts_localizes_ajax_url() {
        $localized = null;
        \WP_Mock::userFunction( 'wp_register_script',  [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_enqueue_script',   [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_create_nonce',     [ 'return' => 'nonce_test' ] );
        \WP_Mock::userFunction( 'get_option',          [ 'return' => 90 ] );
        \WP_Mock::userFunction( 'admin_url',           [ 'return' => 'http://example.com/wp-admin/admin-ajax.php' ] );
        \WP_Mock::userFunction( 'wp_add_inline_script',[ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_localize_script', [
            'times'  => 1,
            'return' => function( $handle, $obj, $data ) use ( &$localized ) {
                $localized = $data;
                return true;
            },
        ] );

        $this->public->enqueue_scripts();

        $this->assertIsArray( $localized );
        $this->assertArrayHasKey( 'ajax_url', $localized );
        $this->assertArrayHasKey( 'nonce', $localized );
        $this->assertEquals( 'nonce_test', $localized['nonce'] );
    }

    // ── render_booking_button ────────────────────────────────────────

    public function test_render_booking_button_returns_string() {
        \WP_Mock::userFunction( 'get_option', [ 'return' => 'Réserver un créneau' ] );

        $result = $this->public->render_booking_button( [] );
        $this->assertIsString( $result );
    }

    public function test_render_booking_button_contains_ws_book_btn_class() {
        \WP_Mock::userFunction( 'get_option', [ 'return' => 'Réserver' ] );

        $result = $this->public->render_booking_button( [] );
        $this->assertStringContainsString( 'ws-book-btn', $result );
    }

    public function test_render_booking_button_uses_custom_label_attribute() {
        \WP_Mock::userFunction( 'get_option', [ 'return' => 'Default Label' ] );

        $result = $this->public->render_booking_button( [ 'label' => 'Custom Label XYZ' ] );
        $this->assertStringContainsString( 'Custom Label XYZ', $result );
    }

    public function test_render_booking_button_uses_default_label_when_no_atts() {
        \WP_Mock::userFunction( 'get_option', [ 'return' => 'Mon Bouton' ] );

        $result = $this->public->render_booking_button( [] );
        $this->assertStringContainsString( 'Mon Bouton', $result );
    }

    public function test_render_booking_button_is_a_button_element() {
        \WP_Mock::userFunction( 'get_option', [ 'return' => 'Book' ] );
        $result = $this->public->render_booking_button( [] );
        $this->assertStringContainsString( '<button', $result );
    }

    // ── render_popup_once ────────────────────────────────────────────

    public function test_render_popup_once_outputs_html() {
        \WP_Mock::userFunction( 'is_admin', [ 'return' => false ] );
        \WP_Mock::userFunction( 'get_option', [ 'return' => false ] );

        ob_start();
        $this->public->render_popup_once();
        $output = ob_get_clean();

        $this->assertNotEmpty( $output );
    }

    public function test_render_popup_once_skips_when_is_admin() {
        \WP_Mock::userFunction( 'is_admin', [ 'return' => true ] );

        ob_start();
        $this->public->render_popup_once();
        $output = ob_get_clean();

        $this->assertEmpty( $output );
    }

    public function test_render_popup_once_renders_only_once() {
        \WP_Mock::userFunction( 'is_admin', [ 'return' => false ] );
        \WP_Mock::userFunction( 'get_option', [ 'return' => false ] );

        ob_start();
        $this->public->render_popup_once();
        $first = ob_get_clean();

        ob_start();
        $this->public->render_popup_once();
        $second = ob_get_clean();

        $this->assertNotEmpty( $first );
        $this->assertEmpty( $second ); // 2ème appel = silencieux
    }
}
