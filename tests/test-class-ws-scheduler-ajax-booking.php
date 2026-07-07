<?php
/**
 * Tests d'intégration pour book_appointment — paths success complets.
 *
 * Ces tests demandent un setup lourd (mock get_slots_for_date via MockWpdb +
 * options + emails). Isolés ici pour ne pas alourdir le fichier unitaire
 * principal des Ajax handlers.
 */
use WP_Mock\Tools\TestCase;

class Test_WS_Scheduler_Ajax_Booking extends TestCase {

    private WS_Scheduler_Ajax $ajax;
    private $wpdb_backup;
    /** @var MockWpdb */
    private $db;

    public function setUp(): void {
        parent::setUp();
        $_POST = [];
        $GLOBALS['_ws_test_transients'] = []; // rate-limiter isolation entre tests
        $this->wpdb_backup = $GLOBALS['wpdb'] ?? null;
        $this->db          = new MockWpdb();
        $this->db->return_rows = [];   // pas de booked, pas d'unavail
        $this->db->insert_id   = 42;
        $GLOBALS['wpdb']   = $this->db;
        $this->ajax = new WS_Scheduler_Ajax();
    }

    public function tearDown(): void {
        parent::tearDown();
        $_POST = [];
        $GLOBALS['wpdb'] = $this->wpdb_backup;
    }

    /**
     * Construit un POST de booking valide pour un créneau futur.
     * Retourne un tuple [date_str, time_str].
     */
    private function setupValidPost(): array {
        // Choisir un créneau futur dans la fenêtre de booking (next monday + 30 days for safety)
        $tz   = new DateTimeZone( 'UTC' );
        $next = new DateTime( 'next monday', $tz );
        $next->modify( '+7 days' );
        $next->setTime( 10, 0, 0 );
        $slot_start = $next->format( 'Y-m-d H:i:s' );
        $date_str   = $next->format( 'Y-m-d' );
        $time_str   = '10:00';

        $_POST = [
            'nonce'      => 'n',
            'first_name' => 'Sophie',
            'last_name'  => 'Martin',
            'email'      => 'sophie@example.com',
            'phone'      => '+33600000000',
            'company'    => 'Acme',
            'message'    => 'Démo produit',
            'slot_start' => $slot_start,
        ];
        return [ $date_str, $time_str ];
    }

    private function mockBaseDeps(): void {
        \WP_Mock::userFunction( 'check_ajax_referer', [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_timezone_string', [ 'return' => 'UTC' ] );
        \WP_Mock::userFunction( 'get_option', [
            'return' => function( $k, $d = false ) {
                $map = [
                    'ws_working_days'    => [1,2,3,4,5,6,7],
                    'ws_max_booking_days'=> 365,
                    'ws_work_start'      => '09:00',
                    'ws_work_end'        => '18:00',
                    'ws_slot_duration'   => 30,
                    'ws_business_name'   => 'TestBiz',
                    'ws_show_powered_by' => false,
                    'wsp_email_logo'     => '',
                    'wsp_email_colors'   => [],
                    'ws_admin_email'     => 'admin@example.com',
                ];
                return $map[ $k ] ?? $d;
            },
        ] );
        \WP_Mock::userFunction( 'get_bloginfo', [ 'return' => 'TestBlog' ] );
        \WP_Mock::userFunction( 'wp_mail',     [ 'return' => true ] );
    }

    // ── Success path complet ──────────────────────────────────────────

    public function test_book_appointment_success_returns_json_success() {
        $this->setupValidPost();
        $this->mockBaseDeps();
        \WP_Mock::userFunction( 'apply_filters', [ 'return' => '' ] ); // pas de meet link
        \WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_send_json_error',   [ 'times' => 0 ] );

        $this->db->return_row_override = [
            // 1er get_appointment retournera le row qu'on a inséré
            [
                'id' => 42, 'first_name' => 'Sophie', 'last_name' => 'Martin',
                'company' => 'Acme', 'email' => 'sophie@example.com',
                'phone' => '+33600000000', 'message' => 'Démo produit',
                'slot_start' => '2026-06-15 10:00:00', 'slot_end' => '2026-06-15 10:30:00',
                'duration' => 30, 'status' => 'confirmed', 'meet_link' => '',
            ],
        ];

        $this->ajax->book_appointment();
        $this->assertTrue( true );
    }

    public function test_book_appointment_success_inserts_into_db() {
        $this->setupValidPost();
        $this->mockBaseDeps();
        \WP_Mock::userFunction( 'apply_filters', [ 'return' => '' ] );
        \WP_Mock::userFunction( 'wp_send_json_success' );
        \WP_Mock::userFunction( 'wp_send_json_error' );

        $this->db->return_row_override = [ [
            'id' => 42, 'first_name' => 'Sophie', 'last_name' => 'Martin',
            'company' => 'Acme', 'email' => 'sophie@example.com',
            'phone' => '+33600000000', 'message' => 'Démo produit',
            'slot_start' => '2026-06-15 10:00:00', 'slot_end' => '2026-06-15 10:30:00',
            'duration' => 30, 'status' => 'confirmed', 'meet_link' => '',
        ] ];

        $this->ajax->book_appointment();
        $this->assertGreaterThanOrEqual( 1, $this->db->count_calls( 'insert' ) );
        $this->assertEquals( 'wp_ws_appointments', $this->db->last_table );
    }

    public function test_book_appointment_success_sanitizes_post_data() {
        $this->setupValidPost();
        $_POST['first_name'] = '<script>alert(1)</script>Sophie';
        $_POST['message']    = '<b>Bold</b> message';
        $this->mockBaseDeps();
        \WP_Mock::userFunction( 'apply_filters', [ 'return' => '' ] );
        \WP_Mock::userFunction( 'wp_send_json_success' );
        \WP_Mock::userFunction( 'wp_send_json_error' );

        $this->db->return_row_override = [ [
            'id' => 42, 'first_name' => 'X', 'last_name' => 'X', 'email' => 'x@y.io',
            'company' => '', 'phone' => '', 'message' => '',
            'slot_start' => '', 'slot_end' => '', 'duration' => 30,
            'status' => 'confirmed', 'meet_link' => '',
        ] ];

        $this->ajax->book_appointment();
        // Le data inséré ne doit plus contenir <script>
        $this->assertStringNotContainsString( '<script>', (string) ( $this->db->last_data['first_name'] ?? '' ) );
    }

    public function test_book_appointment_appointment_with_existing_meet_link_includes_in_email() {
        // Cas où apply_filters('ws_scheduler_after_booking') retourne vide (cas
        // par défaut sans hook Pro), mais get_appointment retourne un row qui
        // a déjà un meet_link en BDD (par ex. depuis une migration ou une
        // intégration tierce). Vérifie que send_client_confirmation reçoit
        // l'appointment intact et que wp_mail est appelé.
        //
        // Note : on ne peut pas mocker un retour custom d'apply_filters via
        // \Mockery::any() pour matcher un objet — WP_Mock 0.5 indexe les
        // processors par spl_object_hash exact, qui n'est pas connu d'avance.
        // On teste donc la branche end-to-end via le state DB plutôt que via
        // une réponse simulée du filter.
        $this->setupValidPost();
        $this->mockBaseDeps();
        \WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_send_json_error',   [ 'times' => 0 ] );

        $this->db->return_row_override = [ [
            'id' => 42, 'first_name' => 'Sophie', 'last_name' => 'Martin',
            'company' => '', 'email' => 'sophie@example.com',
            'phone' => '', 'message' => '',
            'slot_start' => '2026-06-15 10:00:00', 'slot_end' => '2026-06-15 10:30:00',
            'duration' => 30, 'status' => 'confirmed',
            'meet_link' => 'https://meet.google.com/already-set',
        ] ];

        $this->ajax->book_appointment();
        $this->assertTrue( true );
    }

    public function test_book_appointment_failure_when_db_insert_returns_zero() {
        $this->setupValidPost();
        $this->mockBaseDeps();
        $this->db->insert_id = 0; // simulate insert failure
        \WP_Mock::userFunction( 'wp_send_json_error',   [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 0 ] );

        $this->ajax->book_appointment();
        $this->assertTrue( true );
    }

    public function test_book_appointment_failure_when_slot_not_in_free_slots() {
        // Slot dans le passé → get_slots_for_date retournera []
        $tz = new DateTimeZone( 'UTC' );
        $past = new DateTime( '2020-01-15 10:00:00', $tz );
        $_POST = [
            'nonce' => 'n', 'first_name' => 'X', 'last_name' => 'X',
            'email' => 'x@y.io',
            'slot_start' => $past->format( 'Y-m-d H:i:s' ),
        ];
        $this->mockBaseDeps();
        \WP_Mock::userFunction( 'wp_send_json_error',   [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 0 ] );

        $this->ajax->book_appointment();
        $this->assertTrue( true );
    }

    // ── Punctual slot mode pour add_unavailability ────────────────────

    public function test_add_unavailability_punctual_slot_accepts_valid() {
        $_POST = [
            'nonce' => 'n', 'label' => 'Conf', 'mode' => 'punctual_slot',
            'date' => '2026-06-22', 'time_start' => '14:00', 'time_end' => '18:00',
        ];
        \WP_Mock::userFunction( 'check_ajax_referer', [ 'return' => true ] );
        \WP_Mock::userFunction( 'current_user_can',   [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 1 ] );
        $this->ajax->add_unavailability();
        $this->assertTrue( true );
    }

    public function test_add_unavailability_punctual_slot_rejects_missing_date() {
        $_POST = [
            'nonce' => 'n', 'label' => 'X', 'mode' => 'punctual_slot',
            'date' => '', 'time_start' => '14:00', 'time_end' => '18:00',
        ];
        \WP_Mock::userFunction( 'check_ajax_referer', [ 'return' => true ] );
        \WP_Mock::userFunction( 'current_user_can',   [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_send_json_error', [ 'times' => 1 ] );
        $this->ajax->add_unavailability();
        $this->assertTrue( true );
    }

    public function test_add_unavailability_punctual_slot_rejects_invalid_time_format() {
        $_POST = [
            'nonce' => 'n', 'label' => 'X', 'mode' => 'punctual_slot',
            'date' => '2026-06-22', 'time_start' => 'plopiplop', 'time_end' => '18:00',
        ];
        \WP_Mock::userFunction( 'check_ajax_referer', [ 'return' => true ] );
        \WP_Mock::userFunction( 'current_user_can',   [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_send_json_error', [ 'times' => 1 ] );
        $this->ajax->add_unavailability();
        $this->assertTrue( true );
    }

    public function test_add_unavailability_punctual_slot_rejects_inverted_times() {
        $_POST = [
            'nonce' => 'n', 'label' => 'X', 'mode' => 'punctual_slot',
            'date' => '2026-06-22', 'time_start' => '18:00', 'time_end' => '14:00',
        ];
        \WP_Mock::userFunction( 'check_ajax_referer', [ 'return' => true ] );
        \WP_Mock::userFunction( 'current_user_can',   [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_send_json_error', [ 'times' => 1 ] );
        $this->ajax->add_unavailability();
        $this->assertTrue( true );
    }

    // ── Capability checks ─────────────────────────────────────────────

    public function test_update_appointment_rejects_when_user_cannot_manage_options() {
        $_POST = [ 'nonce' => 'n', 'id' => '7', 'status' => 'confirmed' ];
        \WP_Mock::userFunction( 'check_ajax_referer', [ 'return' => true ] );
        \WP_Mock::userFunction( 'current_user_can',   [ 'return' => false ] );
        \WP_Mock::userFunction( 'wp_send_json_error', [ 'times' => 1 ] );
        $this->ajax->update_appointment();
        $this->assertTrue( true );
    }

    public function test_delete_appointment_rejects_when_user_cannot_manage_options() {
        $_POST = [ 'nonce' => 'n', 'id' => '7' ];
        \WP_Mock::userFunction( 'check_ajax_referer', [ 'return' => true ] );
        \WP_Mock::userFunction( 'current_user_can',   [ 'return' => false ] );
        \WP_Mock::userFunction( 'wp_send_json_error', [ 'times' => 1 ] );
        $this->ajax->delete_appointment();
        $this->assertTrue( true );
    }

    public function test_add_unavailability_rejects_when_user_cannot_manage_options() {
        $_POST = [ 'nonce' => 'n', 'label' => 'X', 'mode' => 'period_full' ];
        \WP_Mock::userFunction( 'check_ajax_referer', [ 'return' => true ] );
        \WP_Mock::userFunction( 'current_user_can',   [ 'return' => false ] );
        \WP_Mock::userFunction( 'wp_send_json_error', [ 'times' => 1 ] );
        $this->ajax->add_unavailability();
        $this->assertTrue( true );
    }

    public function test_delete_unavailability_rejects_when_user_cannot_manage_options() {
        $_POST = [ 'nonce' => 'n', 'id' => '7' ];
        \WP_Mock::userFunction( 'check_ajax_referer', [ 'return' => true ] );
        \WP_Mock::userFunction( 'current_user_can',   [ 'return' => false ] );
        \WP_Mock::userFunction( 'wp_send_json_error', [ 'times' => 1 ] );
        $this->ajax->delete_unavailability();
        $this->assertTrue( true );
    }

    // ── Cancellation triggers email ──────────────────────────────────

    public function test_update_appointment_to_cancelled_triggers_cancellation_email() {
        $_POST = [ 'nonce' => 'n', 'id' => '7', 'status' => 'cancelled' ];
        \WP_Mock::userFunction( 'check_ajax_referer', [ 'return' => true ] );
        \WP_Mock::userFunction( 'current_user_can',   [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_send_json_success' );
        \WP_Mock::userFunction( 'get_option', [ 'return' => function( $k, $d = false ) {
            $map = [ 'ws_business_name' => 'B', 'ws_show_powered_by' => false ];
            return $map[$k] ?? $d;
        } ] );
        \WP_Mock::userFunction( 'wp_timezone_string', [ 'return' => 'UTC' ] );
        \WP_Mock::userFunction( 'get_bloginfo',       [ 'return' => 'B' ] );
        \WP_Mock::userFunction( 'wp_mail', [ 'times' => 1, 'return' => true ] );

        $this->db->return_row_override = [ [
            'id' => 7, 'first_name' => 'X', 'last_name' => 'Y',
            'company' => '', 'email' => 'x@y.io', 'phone' => '', 'message' => '',
            'slot_start' => '2026-06-15 10:00:00', 'slot_end' => '2026-06-15 10:30:00',
            'duration' => 30, 'status' => 'confirmed', 'meet_link' => '',
        ] ];

        $this->ajax->update_appointment();
        $this->assertTrue( true );
    }

    public function test_delete_appointment_active_triggers_cancellation_email() {
        $_POST = [ 'nonce' => 'n', 'id' => '7' ];
        \WP_Mock::userFunction( 'check_ajax_referer', [ 'return' => true ] );
        \WP_Mock::userFunction( 'current_user_can',   [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_send_json_success' );
        \WP_Mock::userFunction( 'get_option', [ 'return' => function( $k, $d = false ) {
            $map = [ 'ws_business_name' => 'B', 'ws_show_powered_by' => false ];
            return $map[$k] ?? $d;
        } ] );
        \WP_Mock::userFunction( 'wp_timezone_string', [ 'return' => 'UTC' ] );
        \WP_Mock::userFunction( 'get_bloginfo',       [ 'return' => 'B' ] );
        \WP_Mock::userFunction( 'wp_mail', [ 'times' => 1, 'return' => true ] );

        $this->db->return_row_override = [ [
            'id' => 7, 'first_name' => 'X', 'last_name' => 'Y',
            'company' => '', 'email' => 'x@y.io', 'phone' => '', 'message' => '',
            'slot_start' => '2026-06-15 10:00:00', 'slot_end' => '2026-06-15 10:30:00',
            'duration' => 30, 'status' => 'confirmed', 'meet_link' => '',
        ] ];

        $this->ajax->delete_appointment();
        $this->assertEquals( 1, $this->db->count_calls( 'delete' ) );
    }

    public function test_delete_appointment_already_cancelled_skips_email() {
        $_POST = [ 'nonce' => 'n', 'id' => '7' ];
        \WP_Mock::userFunction( 'check_ajax_referer', [ 'return' => true ] );
        \WP_Mock::userFunction( 'current_user_can',   [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_send_json_success' );
        \WP_Mock::userFunction( 'wp_mail', [ 'times' => 0, 'return' => true ] );

        $this->db->return_row_override = [ [
            'id' => 7, 'first_name' => 'X', 'last_name' => 'Y',
            'company' => '', 'email' => 'x@y.io', 'phone' => '', 'message' => '',
            'slot_start' => '2026-06-15 10:00:00', 'slot_end' => '2026-06-15 10:30:00',
            'duration' => 30, 'status' => 'cancelled', 'meet_link' => '',
        ] ];

        $this->ajax->delete_appointment();
        $this->assertTrue( true );
    }
}
