<?php
/**
 * Tests WS_Scheduler_Email — 16 tests.
 */
use WP_Mock\Tools\TestCase;

class Test_WS_Scheduler_Email extends TestCase {

    /** Helper — crée un objet appointment de test. */
    private function make_appointment( array $overrides = [] ): stdClass {
        return (object) array_merge( [
            'id'         => 1,
            'first_name' => 'Sophie',
            'last_name'  => 'Martin',
            'company'    => 'Acme',
            'email'      => 'sophie@example.com',
            'phone'      => '+33600000000',
            'message'    => 'Test message',
            'slot_start' => '2026-06-15 10:00:00',
            'slot_end'   => '2026-06-15 10:30:00',
            'duration'   => 30,
            'status'     => 'confirmed',
            'meet_link'  => '',
        ], $overrides );
    }

    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    // ── wrap_template ────────────────────────────────────────────────

    public function test_wrap_template_returns_html_string() {
        \WP_Mock::userFunction( 'get_option', [ 'return' => false ] );
        $html = WS_Scheduler_Email::wrap_template( 'MyBiz', '<p>Content</p>' );
        $this->assertIsString( $html );
        $this->assertNotEmpty( $html );
    }

    public function test_wrap_template_contains_doctype() {
        \WP_Mock::userFunction( 'get_option', [ 'return' => false ] );
        $html = WS_Scheduler_Email::wrap_template( 'MyBiz', '<p>Body</p>' );
        $this->assertStringContainsString( '<!DOCTYPE html>', $html );
    }

    public function test_wrap_template_contains_business_name() {
        \WP_Mock::userFunction( 'get_option', [ 'return' => false ] );
        $html = WS_Scheduler_Email::wrap_template( 'WebStrategy Test', '<p>x</p>' );
        $this->assertStringContainsString( 'WebStrategy Test', $html );
    }

    public function test_wrap_template_contains_body_content() {
        \WP_Mock::userFunction( 'get_option', [ 'return' => false ] );
        $body = '<p class="unique-marker">My body</p>';
        $html = WS_Scheduler_Email::wrap_template( 'Biz', $body );
        $this->assertStringContainsString( 'unique-marker', $html );
    }

    public function test_wrap_template_hides_powered_when_option_off() {
        \WP_Mock::userFunction( 'get_option', [
            'return' => function( $key, $default = false ) {
                return $key === 'ws_show_powered_by' ? false : $default;
            },
        ] );
        $html = WS_Scheduler_Email::wrap_template( 'Biz', '<p>x</p>' );
        $this->assertStringNotContainsString( 'WebStrategy</a>', $html );
    }

    public function test_wrap_template_shows_powered_when_option_on() {
        \WP_Mock::userFunction( 'get_option', [
            'return' => function( $key, $default = false ) {
                return $key === 'ws_show_powered_by' ? true : $default;
            },
        ] );
        $html = WS_Scheduler_Email::wrap_template( 'Biz', '<p>x</p>' );
        $this->assertStringContainsString( 'WebStrategy', $html );
    }

    // ── detail_row ───────────────────────────────────────────────────

    public function test_detail_row_returns_html_string() {
        $html = WS_Scheduler_Email::detail_row( 'Date', '15 juin 2026' );
        $this->assertIsString( $html );
        $this->assertNotEmpty( $html );
    }

    public function test_detail_row_contains_label() {
        $html = WS_Scheduler_Email::detail_row( 'MonLabel', 'MaValeur' );
        $this->assertStringContainsString( 'MonLabel', $html );
    }

    public function test_detail_row_contains_value() {
        $html = WS_Scheduler_Email::detail_row( 'X', 'UniqueValue_789' );
        $this->assertStringContainsString( 'UniqueValue_789', $html );
    }

    // ── format_date_fr ───────────────────────────────────────────────

    public function test_format_date_fr_returns_non_empty_string() {
        $dt     = new DateTime( '2026-06-15', new DateTimeZone( 'UTC' ) );
        $result = WS_Scheduler_Email::format_date_fr( $dt );
        $this->assertIsString( $result );
        $this->assertNotEmpty( $result );
    }

    public function test_format_date_fr_contains_year() {
        $dt     = new DateTime( '2026-06-15', new DateTimeZone( 'UTC' ) );
        $result = WS_Scheduler_Email::format_date_fr( $dt );
        $this->assertStringContainsString( '2026', $result );
    }

    public function test_format_date_fr_contains_day_number() {
        $dt     = new DateTime( '2026-06-15', new DateTimeZone( 'UTC' ) );
        $result = WS_Scheduler_Email::format_date_fr( $dt );
        $this->assertStringContainsString( '15', $result );
    }

    // ── send ─────────────────────────────────────────────────────────

    public function test_send_calls_wp_mail() {
        \WP_Mock::userFunction( 'wp_mail', [ 'times' => 1, 'return' => true ] );
        WS_Scheduler_Email::send( 'test@example.com', 'Subject', '<p>HTML</p>' );
    }

    public function test_send_passes_html_content_type_header() {
        $captured_headers = null;
        \WP_Mock::userFunction( 'wp_mail', [
            'times'  => 1,
            'return' => function( $to, $sub, $body, $headers ) use ( &$captured_headers ) {
                $captured_headers = $headers;
                return true;
            },
        ] );
        WS_Scheduler_Email::send( 'test@example.com', 'Subj', '<p>Body</p>' );
        $this->assertIsArray( $captured_headers );
        $this->assertStringContainsString( 'text/html', implode( ',', $captured_headers ) );
    }

    // ── send_client_confirmation ─────────────────────────────────────

    public function test_send_client_confirmation_calls_wp_mail() {
        \WP_Mock::userFunction( 'get_option', [
            'return' => function( $k, $d = false ) {
                $map = [
                    'ws_business_name' => 'MonBiz',
                    'ws_show_powered_by' => false,
                ];
                return $map[$k] ?? $d;
            },
        ] );
        \WP_Mock::userFunction( 'wp_timezone_string', [ 'return' => 'UTC' ] );
        \WP_Mock::userFunction( 'wp_mail', [ 'times' => 1, 'return' => true ] );

        WS_Scheduler_Email::send_client_confirmation( $this->make_appointment() );
    }

    // ── send_client_cancellation ─────────────────────────────────────

    public function test_send_client_cancellation_calls_wp_mail() {
        \WP_Mock::userFunction( 'get_option', [
            'return' => function( $k, $d = false ) {
                return $k === 'ws_business_name' ? 'MonBiz' : $d;
            },
        ] );
        \WP_Mock::userFunction( 'wp_timezone_string', [ 'return' => 'UTC' ] );
        \WP_Mock::userFunction( 'wp_mail', [ 'times' => 1, 'return' => true ] );

        WS_Scheduler_Email::send_client_cancellation( $this->make_appointment() );
    }
}
