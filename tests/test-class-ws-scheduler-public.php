<?php
use WP_Mock\Tools\TestCase;

class Test_WS_Scheduler_Public extends TestCase {

    private WS_Scheduler_Public $public;

    public function setUp(): void {
        parent::setUp();
        $ref = new ReflectionProperty( WS_Scheduler_Public::class, 'popup_rendered' );
        $ref->setAccessible( true );
        $ref->setValue( null, false );
        $this->public = new WS_Scheduler_Public( 'ws-scheduler', '4.0.4' );
    }
    public function tearDown(): void { parent::tearDown(); }

    public function test_enqueue_styles_calls_wp_enqueue_style() {
        \WP_Mock::userFunction( 'wp_enqueue_style', [ 'times' => 1 ] );
        $this->public->enqueue_styles();
        $this->assertTrue( true ); // WP_Mock times assertions verified in tearDown
    }

    public function test_enqueue_scripts_registers_and_enqueues_handle() {
        \WP_Mock::userFunction( 'wp_register_script',   [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_enqueue_script',    [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_localize_script',   [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_add_inline_script', [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_create_nonce',      [ 'return' => 'nonce_abc' ] );
        \WP_Mock::userFunction( 'admin_url',            [ 'return' => 'http://example.com/wp-admin/admin-ajax.php' ] );
        \WP_Mock::userFunction( 'get_option',           [ 'return' => 90 ] );
        $this->public->enqueue_scripts();
        $this->assertTrue( true ); // WP_Mock times assertions verified in tearDown
    }

    public function test_enqueue_scripts_localizes_ajax_url_and_nonce() {
        $localized = null;
        \WP_Mock::userFunction( 'wp_register_script',   [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_enqueue_script',    [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_add_inline_script', [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_create_nonce',      [ 'return' => 'my_nonce' ] );
        \WP_Mock::userFunction( 'admin_url',            [ 'return' => 'http://example.com/wp-admin/admin-ajax.php' ] );
        \WP_Mock::userFunction( 'get_option',           [ 'return' => 90 ] );
        \WP_Mock::userFunction( 'wp_localize_script', [
            'return' => function( $h, $obj, $data ) use ( &$localized ) {
                $localized = $data; return true;
            },
        ] );
        $this->public->enqueue_scripts();
        $this->assertArrayHasKey( 'ajax_url', $localized );
        $this->assertArrayHasKey( 'nonce', $localized );
        $this->assertEquals( 'my_nonce', $localized['nonce'] );
    }

    public function test_render_booking_button_returns_string() {
        \WP_Mock::userFunction( 'get_option', [ 'return' => 'Réserver' ] );
        $this->assertIsString( $this->public->render_booking_button( [] ) );
    }

    public function test_render_booking_button_contains_ws_book_btn() {
        \WP_Mock::userFunction( 'get_option', [ 'return' => 'Réserver' ] );
        $this->assertStringContainsString( 'ws-book-btn', $this->public->render_booking_button( [] ) );
    }

    public function test_render_booking_button_uses_custom_label() {
        \WP_Mock::userFunction( 'get_option', [ 'return' => 'Default' ] );
        $result = $this->public->render_booking_button( [ 'label' => 'Custom Label XYZ' ] );
        $this->assertStringContainsString( 'Custom Label XYZ', $result );
    }

    public function test_render_booking_button_uses_saved_label() {
        \WP_Mock::userFunction( 'get_option', [ 'return' => 'Mon Bouton Perso' ] );
        $result = $this->public->render_booking_button( [] );
        $this->assertStringContainsString( 'Mon Bouton Perso', $result );
    }

    public function test_render_booking_button_is_a_button_element() {
        \WP_Mock::userFunction( 'get_option', [ 'return' => 'Book' ] );
        $this->assertStringContainsString( '<button', $this->public->render_booking_button( [] ) );
    }

    public function test_render_popup_once_outputs_html() {
        \WP_Mock::userFunction( 'is_admin', [ 'return' => false ] );
        \WP_Mock::userFunction( 'get_option', [ 'return' => false ] );
        ob_start();
        $this->public->render_popup_once();
        $output = ob_get_clean();
        $this->assertNotEmpty( $output );
    }

    public function test_render_popup_once_skips_in_admin() {
        \WP_Mock::userFunction( 'is_admin', [ 'return' => true ] );
        ob_start();
        $this->public->render_popup_once();
        $this->assertEmpty( ob_get_clean() );
    }

    public function test_render_popup_once_renders_only_once() {
        \WP_Mock::userFunction( 'is_admin', [ 'return' => false ] );
        \WP_Mock::userFunction( 'get_option', [ 'return' => false ] );
        ob_start(); $this->public->render_popup_once(); $first = ob_get_clean();
        ob_start(); $this->public->render_popup_once(); $second = ob_get_clean();
        $this->assertNotEmpty( $first );
        $this->assertEmpty( $second );
    }
}
