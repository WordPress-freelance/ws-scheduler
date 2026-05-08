<?php
use WP_Mock\Tools\TestCase;

class Test_WS_Scheduler_Email extends TestCase {

    private function appt( array $ov = [] ): stdClass {
        return (object) array_merge( [
            'id'         => 1, 'first_name' => 'Sophie', 'last_name' => 'Martin',
            'company'    => 'Acme', 'email' => 'sophie@example.com',
            'phone'      => '+33600000000', 'message' => 'Test',
            'slot_start' => '2026-06-15 10:00:00', 'slot_end' => '2026-06-15 10:30:00',
            'duration'   => 30, 'status' => 'confirmed', 'meet_link' => '',
        ], $ov );
    }

    private function mockBaseOptions() {
        \WP_Mock::userFunction( 'get_option', [
            'return' => function( $k, $d = false ) {
                $map = [
                    'ws_business_name'   => 'TestBiz',
                    'ws_show_powered_by' => false,
                    'wsp_email_logo'     => '',
                    'wsp_email_colors'   => [],
                    'wsp_email_hide_powered' => false,
                ];
                return $map[$k] ?? $d;
            },
        ] );
        \WP_Mock::userFunction( 'wp_timezone_string', [ 'return' => 'UTC' ] );
    }

    public function setUp(): void { parent::setUp(); }
    public function tearDown(): void { parent::tearDown(); }

    public function test_wrap_template_returns_string() {
        \WP_Mock::userFunction( 'get_option', [ 'return' => false ] );
        $html = WS_Scheduler_Email::wrap_template( 'Biz', '<p>x</p>' );
        $this->assertIsString( $html );
    }

    public function test_wrap_template_contains_doctype() {
        \WP_Mock::userFunction( 'get_option', [ 'return' => false ] );
        $this->assertStringContainsString( '<!DOCTYPE html>', WS_Scheduler_Email::wrap_template( 'Biz', '' ) );
    }

    public function test_wrap_template_contains_business_name() {
        \WP_Mock::userFunction( 'get_option', [ 'return' => false ] );
        $this->assertStringContainsString( 'UniqueBizName', WS_Scheduler_Email::wrap_template( 'UniqueBizName', '' ) );
    }

    public function test_wrap_template_contains_body_content() {
        \WP_Mock::userFunction( 'get_option', [ 'return' => false ] );
        $html = WS_Scheduler_Email::wrap_template( 'Biz', '<p class="unique-marker-xyz">ok</p>' );
        $this->assertStringContainsString( 'unique-marker-xyz', $html );
    }

    public function test_wrap_template_hides_credit_when_option_off() {
        \WP_Mock::userFunction( 'get_option', [
            'return' => function( $k, $d = false ) {
                return $k === 'ws_show_powered_by' ? false : $d;
            },
        ] );
        $html = WS_Scheduler_Email::wrap_template( 'Biz', '' );
        $this->assertStringNotContainsString( 'wordpress-freelance.com', $html );
    }

    public function test_wrap_template_shows_credit_when_option_on() {
        \WP_Mock::userFunction( 'get_option', [
            'return' => function( $k, $d = false ) {
                return $k === 'ws_show_powered_by' ? true : $d;
            },
        ] );
        $html = WS_Scheduler_Email::wrap_template( 'Biz', '' );
        $this->assertStringContainsString( 'wordpress-freelance.com', $html );
    }

    public function test_detail_row_returns_string() {
        $html = WS_Scheduler_Email::detail_row( 'Label', 'Value' );
        $this->assertIsString( $html );
        $this->assertNotEmpty( $html );
    }

    public function test_detail_row_contains_label() {
        $this->assertStringContainsString( 'MonLabel', WS_Scheduler_Email::detail_row( 'MonLabel', 'X' ) );
    }

    public function test_detail_row_contains_value() {
        $this->assertStringContainsString( 'UniqueVal999', WS_Scheduler_Email::detail_row( 'L', 'UniqueVal999' ) );
    }

    public function test_format_date_fr_returns_non_empty_string() {
        $dt = new DateTime( '2026-06-15', new DateTimeZone( 'UTC' ) );
        $r  = WS_Scheduler_Email::format_date_fr( $dt );
        $this->assertIsString( $r );
        $this->assertNotEmpty( $r );
    }

    public function test_format_date_fr_contains_year() {
        $dt = new DateTime( '2026-06-15', new DateTimeZone( 'UTC' ) );
        $this->assertStringContainsString( '2026', WS_Scheduler_Email::format_date_fr( $dt ) );
    }

    public function test_format_date_fr_contains_day_number() {
        $dt = new DateTime( '2026-06-15', new DateTimeZone( 'UTC' ) );
        $this->assertStringContainsString( '15', WS_Scheduler_Email::format_date_fr( $dt ) );
    }

    public function test_send_calls_wp_mail() {
        \WP_Mock::userFunction( 'wp_mail', [ 'times' => 1, 'return' => true ] );
        WS_Scheduler_Email::send( 'test@example.com', 'Subject', '<p>HTML</p>' );
    }

    public function test_send_passes_html_content_type_header() {
        $headers = null;
        \WP_Mock::userFunction( 'wp_mail', [
            'times'  => 1,
            'return' => function( $to, $sub, $body, $hdr ) use ( &$headers ) {
                $headers = $hdr;
                return true;
            },
        ] );
        WS_Scheduler_Email::send( 'test@example.com', 'Subj', '<p>Body</p>' );
        $this->assertStringContainsString( 'text/html', implode( ',', (array) $headers ) );
    }

    public function test_send_client_confirmation_calls_wp_mail() {
        $this->mockBaseOptions();
        \WP_Mock::userFunction( 'wp_mail', [ 'times' => 1, 'return' => true ] );
        WS_Scheduler_Email::send_client_confirmation( $this->appt() );
    }

    public function test_send_client_cancellation_calls_wp_mail() {
        $this->mockBaseOptions();
        \WP_Mock::userFunction( 'wp_mail', [ 'times' => 1, 'return' => true ] );
        WS_Scheduler_Email::send_client_cancellation( $this->appt() );
    }
}
