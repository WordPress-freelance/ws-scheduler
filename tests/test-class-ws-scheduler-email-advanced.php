<?php
/**
 * Tests email avancés : send_admin_notification, get_colors avec Pro overrides,
 * format_date_fr edge cases, send avec attachments, message conditionnel.
 */
use WP_Mock\Tools\TestCase;

class Test_WS_Scheduler_Email_Advanced extends TestCase {

    private function appt( array $ov = [] ): stdClass {
        return (object) array_merge( [
            'id' => 1, 'first_name' => 'Sophie', 'last_name' => 'Martin',
            'company' => 'Acme', 'email' => 'sophie@example.com',
            'phone' => '+33600000000', 'message' => 'Demo',
            'slot_start' => '2026-06-15 10:00:00', 'slot_end' => '2026-06-15 10:30:00',
            'duration' => 30, 'status' => 'confirmed', 'meet_link' => '',
        ], $ov );
    }

    private function mockBaseOptions( array $opts = [] ) {
        \WP_Mock::userFunction( 'get_option', [
            'return' => function( $k, $d = false ) use ( $opts ) {
                $defaults = [
                    'ws_business_name'   => 'TestBiz',
                    'ws_admin_email'     => 'admin@example.com',
                    'ws_show_powered_by' => false,
                    'wsp_email_logo'     => '',
                    'wsp_email_colors'   => [],
                ];
                $merged = array_merge( $defaults, $opts );
                if ( array_key_exists( $k, $merged ) ) return $merged[ $k ];
                return $d;
            },
        ] );
        \WP_Mock::userFunction( 'wp_timezone_string', [ 'return' => 'UTC' ] );
        \WP_Mock::userFunction( 'get_bloginfo',       [ 'return' => 'TestBlog' ] );
    }

    public function setUp(): void { parent::setUp(); }
    public function tearDown(): void { parent::tearDown(); }

    // ── send_admin_notification ───────────────────────────────────────

    public function test_send_admin_notification_calls_wp_mail() {
        $this->mockBaseOptions();
        \WP_Mock::userFunction( 'wp_mail', [ 'times' => 1, 'return' => true ] );
        WS_Scheduler_Email::send_admin_notification( $this->appt() );
        $this->assertTrue( true );
    }

    public function test_send_admin_notification_uses_admin_email_option() {
        $this->mockBaseOptions( [ 'ws_admin_email' => 'custom@example.com' ] );
        $captured_to = null;
        \WP_Mock::userFunction( 'wp_mail', [
            'return' => function( $to, $sub, $body, $hdr ) use ( &$captured_to ) {
                $captured_to = $to;
                return true;
            },
        ] );
        WS_Scheduler_Email::send_admin_notification( $this->appt() );
        $this->assertEquals( 'custom@example.com', $captured_to );
    }

    public function test_send_admin_notification_falls_back_to_admin_email_when_unset() {
        $this->mockBaseOptions( [ 'ws_admin_email' => '' ] );
        // Si ws_admin_email vide, le code fallback sur get_option('admin_email')
        // qui retourne false avec notre mock par défaut. wp_mail recevra false.
        $captured_to = null;
        \WP_Mock::userFunction( 'wp_mail', [
            'return' => function( $to, $sub, $body, $hdr ) use ( &$captured_to ) {
                $captured_to = $to;
                return true;
            },
        ] );
        WS_Scheduler_Email::send_admin_notification( $this->appt() );
        // false est passé tel quel — c'est au site owner de configurer
        $this->assertFalse( $captured_to );
    }

    public function test_send_admin_notification_includes_client_name_in_subject() {
        $this->mockBaseOptions();
        $captured_subject = null;
        \WP_Mock::userFunction( 'wp_mail', [
            'return' => function( $to, $sub, $body, $hdr ) use ( &$captured_subject ) {
                $captured_subject = $sub;
                return true;
            },
        ] );
        WS_Scheduler_Email::send_admin_notification( $this->appt( [
            'first_name' => 'Sébastien', 'last_name' => 'Chaffer',
        ] ) );
        $this->assertStringContainsString( 'Sébastien', $captured_subject );
        $this->assertStringContainsString( 'Chaffer',   $captured_subject );
    }

    public function test_send_admin_notification_body_contains_email() {
        $this->mockBaseOptions();
        $captured_body = null;
        \WP_Mock::userFunction( 'wp_mail', [
            'return' => function( $to, $sub, $body, $hdr ) use ( &$captured_body ) {
                $captured_body = $body;
                return true;
            },
        ] );
        WS_Scheduler_Email::send_admin_notification( $this->appt( [
            'email' => 'specific.client@example.com',
        ] ) );
        $this->assertStringContainsString( 'specific.client@example.com', $captured_body );
    }

    public function test_send_admin_notification_body_includes_phone_when_present() {
        $this->mockBaseOptions();
        $captured_body = null;
        \WP_Mock::userFunction( 'wp_mail', [
            'return' => function( $to, $sub, $body, $hdr ) use ( &$captured_body ) {
                $captured_body = $body;
                return true;
            },
        ] );
        WS_Scheduler_Email::send_admin_notification( $this->appt( [
            'phone' => '+33612345678',
        ] ) );
        $this->assertStringContainsString( '+33612345678', $captured_body );
    }

    public function test_send_admin_notification_body_skips_phone_block_when_empty() {
        $this->mockBaseOptions();
        $captured_body = null;
        \WP_Mock::userFunction( 'wp_mail', [
            'return' => function( $to, $sub, $body, $hdr ) use ( &$captured_body ) {
                $captured_body = $body;
                return true;
            },
        ] );
        WS_Scheduler_Email::send_admin_notification( $this->appt( [
            'phone' => '',
        ] ) );
        // Body ne devrait pas contenir un row "Téléphone"
        $this->assertStringNotContainsString( 'Téléphone', $captured_body );
    }

    public function test_send_admin_notification_body_includes_message_block_when_present() {
        $this->mockBaseOptions();
        $captured_body = null;
        \WP_Mock::userFunction( 'wp_mail', [
            'return' => function( $to, $sub, $body, $hdr ) use ( &$captured_body ) {
                $captured_body = $body;
                return true;
            },
        ] );
        WS_Scheduler_Email::send_admin_notification( $this->appt( [
            'message' => 'UNIQUE_MARKER_42',
        ] ) );
        $this->assertStringContainsString( 'UNIQUE_MARKER_42', $captured_body );
    }

    public function test_send_admin_notification_includes_meet_link_when_set() {
        $this->mockBaseOptions();
        $captured_body = null;
        \WP_Mock::userFunction( 'wp_mail', [
            'return' => function( $to, $sub, $body, $hdr ) use ( &$captured_body ) {
                $captured_body = $body;
                return true;
            },
        ] );
        WS_Scheduler_Email::send_admin_notification( $this->appt( [
            'meet_link' => 'https://meet.google.com/abc-defg-hij',
        ] ) );
        $this->assertStringContainsString( 'meet.google.com/abc-defg-hij', $captured_body );
    }

    // ── send_client_confirmation : meet_link branch ──────────────────

    public function test_send_client_confirmation_includes_meet_link_when_set() {
        $this->mockBaseOptions();
        $captured_body = null;
        \WP_Mock::userFunction( 'wp_mail', [
            'return' => function( $to, $sub, $body, $hdr ) use ( &$captured_body ) {
                $captured_body = $body;
                return true;
            },
        ] );
        WS_Scheduler_Email::send_client_confirmation( $this->appt( [
            'meet_link' => 'https://meet.google.com/xyz',
        ] ) );
        $this->assertStringContainsString( 'meet.google.com/xyz', $captured_body );
    }

    public function test_send_client_confirmation_uses_pro_custom_greeting() {
        $this->mockBaseOptions( [
            'wsp_email_confirm_greeting' => 'Hola {prenom} {nom},',
        ] );
        $captured_body = null;
        \WP_Mock::userFunction( 'wp_mail', [
            'return' => function( $to, $sub, $body, $hdr ) use ( &$captured_body ) {
                $captured_body = $body;
                return true;
            },
        ] );
        WS_Scheduler_Email::send_client_confirmation( $this->appt() );
        $this->assertStringContainsString( 'Hola Sophie Martin', $captured_body );
    }

    public function test_send_client_confirmation_uses_pro_custom_intro() {
        $this->mockBaseOptions( [
            'wsp_email_confirm_intro' => 'CUSTOM_INTRO_TEXT_777',
        ] );
        $captured_body = null;
        \WP_Mock::userFunction( 'wp_mail', [
            'return' => function( $to, $sub, $body, $hdr ) use ( &$captured_body ) {
                $captured_body = $body;
                return true;
            },
        ] );
        WS_Scheduler_Email::send_client_confirmation( $this->appt() );
        $this->assertStringContainsString( 'CUSTOM_INTRO_TEXT_777', $captured_body );
    }

    // ── send_client_cancellation : custom Pro fields ─────────────────

    public function test_send_client_cancellation_uses_pro_custom_message() {
        $this->mockBaseOptions( [
            'wsp_email_cancel_message' => 'CUSTOM_CANCEL_{date}_{prenom}',
        ] );
        $captured_body = null;
        \WP_Mock::userFunction( 'wp_mail', [
            'return' => function( $to, $sub, $body, $hdr ) use ( &$captured_body ) {
                $captured_body = $body;
                return true;
            },
        ] );
        WS_Scheduler_Email::send_client_cancellation( $this->appt() );
        $this->assertStringContainsString( 'CUSTOM_CANCEL_', $captured_body );
        $this->assertStringContainsString( 'Sophie',         $captured_body );
    }

    // ── format_date_fr : tous les jours ─────────────────────────────

    /**
     * @dataProvider weekdayProvider
     */
    public function test_format_date_fr_returns_french_day_name( $date_str, $expected_day ) {
        $dt     = new DateTime( $date_str, new DateTimeZone( 'UTC' ) );
        $result = WS_Scheduler_Email::format_date_fr( $dt );
        $this->assertStringContainsString( $expected_day, $result );
    }

    public function weekdayProvider(): array {
        // Dates connues en juin 2026 (ISO 8601)
        return [
            'lundi'    => [ '2026-06-01', 'Lundi'    ],
            'mardi'    => [ '2026-06-02', 'Mardi'    ],
            'mercredi' => [ '2026-06-03', 'Mercredi' ],
            'jeudi'    => [ '2026-06-04', 'Jeudi'    ],
            'vendredi' => [ '2026-06-05', 'Vendredi' ],
            'samedi'   => [ '2026-06-06', 'Samedi'   ],
            'dimanche' => [ '2026-06-07', 'Dimanche' ],
        ];
    }

    /**
     * @dataProvider monthProvider
     */
    public function test_format_date_fr_returns_french_month_name( $date_str, $expected_month ) {
        $dt = new DateTime( $date_str, new DateTimeZone( 'UTC' ) );
        $this->assertStringContainsString( $expected_month, WS_Scheduler_Email::format_date_fr( $dt ) );
    }

    public function monthProvider(): array {
        return [
            'jan' => [ '2026-01-15', 'janvier'  ],
            'fév' => [ '2026-02-15', 'février'  ],
            'mar' => [ '2026-03-15', 'mars'     ],
            'avr' => [ '2026-04-15', 'avril'    ],
            'mai' => [ '2026-05-15', 'mai'      ],
            'jun' => [ '2026-06-15', 'juin'     ],
            'jul' => [ '2026-07-15', 'juillet'  ],
            'aoû' => [ '2026-08-15', 'août'     ],
            'sep' => [ '2026-09-15', 'septembre'],
            'oct' => [ '2026-10-15', 'octobre'  ],
            'nov' => [ '2026-11-15', 'novembre' ],
            'déc' => [ '2026-12-15', 'décembre' ],
        ];
    }

    // ── wrap_template : Pro custom logo + colors ─────────────────────

    public function test_wrap_template_uses_custom_logo_url_when_set() {
        \WP_Mock::userFunction( 'get_option', [
            'return' => function( $k, $d = false ) {
                if ( $k === 'wsp_email_logo' ) return 'https://cdn.example.com/logo.png';
                return $d;
            },
        ] );
        $html = WS_Scheduler_Email::wrap_template( 'Biz', '' );
        $this->assertStringContainsString( 'https://cdn.example.com/logo.png', $html );
    }

    public function test_wrap_template_uses_default_W_mark_when_no_logo() {
        \WP_Mock::userFunction( 'get_option', [ 'return' => false ] );
        $html = WS_Scheduler_Email::wrap_template( 'Biz', '' );
        // Default mark contient un div avec un W
        $this->assertStringContainsString( '>W<', $html );
    }

    public function test_wrap_template_applies_custom_pro_colors() {
        \WP_Mock::userFunction( 'get_option', [
            'return' => function( $k, $d = false ) {
                if ( $k === 'wsp_email_colors' ) return [
                    'bg_outer' => '#FF0000',
                    'accent'   => '#00FF00',
                ];
                if ( $k === 'ws_show_powered_by' ) return false;
                return $d;
            },
        ] );
        $html = WS_Scheduler_Email::wrap_template( 'Biz', '' );
        $this->assertStringContainsString( '#FF0000', $html );
        $this->assertStringContainsString( '#00FF00', $html );
    }

    // ── send : headers ─────────────────────────────────────────────

    public function test_send_uses_utf8_charset() {
        $captured_headers = null;
        \WP_Mock::userFunction( 'wp_mail', [
            'return' => function( $to, $sub, $body, $hdr ) use ( &$captured_headers ) {
                $captured_headers = $hdr;
                return true;
            },
        ] );
        WS_Scheduler_Email::send( 'a@b.com', 'sub', 'body' );
        $this->assertStringContainsString( 'UTF-8', implode( ',', (array) $captured_headers ) );
    }
}
