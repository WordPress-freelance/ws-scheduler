<?php
/**
 * Tests unitaires du rate-limiter par IP + honeypot.
 *
 * Couvre :
 *   1. Rate-limiter unitaire (check_and_increment, seuils, filtres, reset, IP)
 *   2. Intégration book_appointment : throttle après N tentatives
 *   3. Intégration get_available_slots / get_month_slots : throttle
 *   4. Honeypot : réponse simule un succès sans insertion en base
 */
use WP_Mock\Tools\TestCase;

class Test_WS_Scheduler_Rate_Limiter extends TestCase {

    private $wpdb_backup;

    public function setUp(): void {
        parent::setUp();
        $_POST                          = [];
        $_SERVER                        = [];
        $GLOBALS['_ws_test_transients'] = [];
        $this->wpdb_backup              = $GLOBALS['wpdb'] ?? null;
        $GLOBALS['wpdb']                = new MockWpdb();
    }

    public function tearDown(): void {
        parent::tearDown();
        $_POST           = [];
        $_SERVER         = [];
        $GLOBALS['wpdb'] = $this->wpdb_backup;
    }

    // ── 1. Rate-limiter unitaire ─────────────────────────────────────────

    public function test_check_and_increment_allows_first_hit() {
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
        $this->assertTrue( WS_Scheduler_Rate_Limiter::check_and_increment( 'book_appointment' ) );
    }

    public function test_check_and_increment_blocks_after_max_hits_reached() {
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
        // Défaut book_appointment = 5. Les 5 premiers doivent passer,
        // le 6e doit être bloqué (compteur == max_hits).
        for ( $i = 1; $i <= 5; $i++ ) {
            $this->assertTrue(
                WS_Scheduler_Rate_Limiter::check_and_increment( 'book_appointment' ),
                "Hit #{$i} devrait être autorisé"
            );
        }
        $this->assertFalse(
            WS_Scheduler_Rate_Limiter::check_and_increment( 'book_appointment' ),
            'Le 6e hit devrait être bloqué'
        );
    }

    public function test_check_and_increment_isolates_ips() {
        // Deux IP distinctes, compteurs indépendants
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
        for ( $i = 0; $i < 5; $i++ ) WS_Scheduler_Rate_Limiter::check_and_increment( 'book_appointment' );
        $this->assertFalse( WS_Scheduler_Rate_Limiter::check_and_increment( 'book_appointment' ) );

        $_SERVER['REMOTE_ADDR'] = '5.6.7.8';
        $this->assertTrue(
            WS_Scheduler_Rate_Limiter::check_and_increment( 'book_appointment' ),
            'Nouvelle IP doit repartir de zéro'
        );
    }

    public function test_check_and_increment_isolates_actions() {
        // Même IP, deux actions distinctes → compteurs séparés
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
        for ( $i = 0; $i < 5; $i++ ) WS_Scheduler_Rate_Limiter::check_and_increment( 'book_appointment' );
        $this->assertFalse( WS_Scheduler_Rate_Limiter::check_and_increment( 'book_appointment' ) );
        // get_available_slots reste ouvert
        $this->assertTrue( WS_Scheduler_Rate_Limiter::check_and_increment( 'get_available_slots' ) );
    }

    public function test_get_client_ip_prefers_cf_connecting_ip() {
        $_SERVER['REMOTE_ADDR']          = '10.0.0.1';
        $_SERVER['HTTP_CF_CONNECTING_IP'] = '203.0.113.42';
        $this->assertEquals( '203.0.113.42', WS_Scheduler_Rate_Limiter::get_client_ip() );
    }

    public function test_get_client_ip_handles_x_forwarded_for_list() {
        // X-Forwarded-For : "client, proxy1, proxy2" → on prend le 1er
        $_SERVER['REMOTE_ADDR']         = '10.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.99, 10.0.0.5, 10.0.0.6';
        $this->assertEquals( '203.0.113.99', WS_Scheduler_Rate_Limiter::get_client_ip() );
    }

    public function test_get_client_ip_ignores_invalid_ip() {
        $_SERVER['HTTP_X_FORWARDED_FOR'] = 'not-an-ip';
        $_SERVER['REMOTE_ADDR']          = '203.0.113.7';
        $this->assertEquals( '203.0.113.7', WS_Scheduler_Rate_Limiter::get_client_ip() );
    }

    public function test_get_client_ip_falls_back_to_0000_when_all_missing() {
        // Aucune source d'IP disponible
        $this->assertEquals( '0.0.0.0', WS_Scheduler_Rate_Limiter::get_client_ip() );
    }

    public function test_reset_clears_counter_for_action_and_ip() {
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
        for ( $i = 0; $i < 5; $i++ ) WS_Scheduler_Rate_Limiter::check_and_increment( 'book_appointment' );
        $this->assertFalse( WS_Scheduler_Rate_Limiter::check_and_increment( 'book_appointment' ) );
        WS_Scheduler_Rate_Limiter::reset( 'book_appointment' );
        $this->assertTrue(
            WS_Scheduler_Rate_Limiter::check_and_increment( 'book_appointment' ),
            'Après reset, l\'IP doit repartir de zéro'
        );
    }

    public function test_bypass_filter_allows_unlimited_hits() {
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
        \WP_Mock::onFilter( 'ws_scheduler_rate_limit_bypass' )
            ->with( false, 'book_appointment' )
            ->reply( true );

        for ( $i = 0; $i < 100; $i++ ) {
            $this->assertTrue(
                WS_Scheduler_Rate_Limiter::check_and_increment( 'book_appointment' ),
                "Hit #{$i} bypass devrait toujours passer"
            );
        }
    }

    public function test_key_is_hashed_ip_not_plaintext() {
        // On garantit qu'on ne stocke JAMAIS l'IP en clair dans les clés
        // (RGPD). Le hash est un préfixe md5 → 12 chars hex, jamais l'IP brute.
        $_SERVER['REMOTE_ADDR'] = '203.0.113.99';
        WS_Scheduler_Rate_Limiter::check_and_increment( 'book_appointment' );
        foreach ( array_keys( $GLOBALS['_ws_test_transients'] ) as $key ) {
            $this->assertStringNotContainsString( '203.0.113.99', $key );
            $this->assertStringStartsWith( 'ws_sched_rl_', $key );
        }
    }

    // ── 2. Intégration book_appointment ──────────────────────────────────

    public function test_book_appointment_returns_throttle_error_after_max_hits() {
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';

        // Charge le compteur au max — la 6e requête book_appointment
        // doit renvoyer wp_send_json_error avec le message throttled.
        for ( $i = 0; $i < 5; $i++ ) {
            WS_Scheduler_Rate_Limiter::check_and_increment( 'book_appointment' );
        }

        $_POST = [
            'nonce'      => 'n',
            'first_name' => 'X', 'last_name' => 'Y',
            'email'      => 'x@y.io',
            'slot_start' => '2099-06-15 10:00:00',
        ];

        \WP_Mock::userFunction( 'check_ajax_referer', [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_send_json_error', [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 0 ] );

        ( new WS_Scheduler_Ajax() )->book_appointment();
        $this->assertTrue( true );
    }

    // ── 3. Intégration lecture (planning) ────────────────────────────────

    public function test_get_available_slots_returns_throttle_error_after_max_hits() {
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
        for ( $i = 0; $i < 120; $i++ ) {
            WS_Scheduler_Rate_Limiter::check_and_increment( 'get_available_slots' );
        }
        $_POST['date'] = '2099-06-15';

        \WP_Mock::userFunction( 'wp_send_json_error',   [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 0 ] );

        ( new WS_Scheduler_Ajax() )->get_available_slots();
        $this->assertTrue( true );
    }

    public function test_get_month_slots_returns_throttle_error_after_max_hits() {
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
        for ( $i = 0; $i < 60; $i++ ) {
            WS_Scheduler_Rate_Limiter::check_and_increment( 'get_month_slots' );
        }
        $_POST = [ 'year' => 2099, 'month' => 6 ];

        \WP_Mock::userFunction( 'wp_send_json_error',   [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 0 ] );

        ( new WS_Scheduler_Ajax() )->get_month_slots();
        $this->assertTrue( true );
    }

    // ── 4. Honeypot ──────────────────────────────────────────────────────

    public function test_honeypot_filled_returns_fake_success_without_db_insert() {
        $_POST = [
            'nonce'      => 'n',
            'first_name' => 'BotName', 'last_name' => 'BotLast',
            'email'      => 'bot@spam.io', 'slot_start' => '2099-06-15 10:00:00',
            'ws_website' => 'http://spam.example.com', // ← honeypot rempli
        ];
        $db = new MockWpdb();
        $GLOBALS['wpdb'] = $db;

        \WP_Mock::userFunction( 'check_ajax_referer', [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_send_json_error',   [ 'times' => 0 ] );

        ( new WS_Scheduler_Ajax() )->book_appointment();

        // Zéro insertion en base : le RDV n'existe pas.
        $this->assertEquals( 0, $db->count_calls( 'insert' ) );
    }

    public function test_honeypot_does_not_consume_rate_limit_quota() {
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
        $_POST = [
            'nonce'      => 'n',
            'first_name' => 'B', 'last_name' => 'B',
            'email'      => 'b@b.io', 'slot_start' => '2099-06-15 10:00:00',
            'ws_website' => 'trap',
        ];
        \WP_Mock::userFunction( 'check_ajax_referer', [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_send_json_success' );
        \WP_Mock::userFunction( 'wp_send_json_error' );

        // 20 tentatives avec honeypot → le compteur book_appointment
        // ne doit PAS avoir bougé, c'est gratuit pour nous.
        for ( $i = 0; $i < 20; $i++ ) {
            ( new WS_Scheduler_Ajax() )->book_appointment();
        }
        // Rien dans les transients pour book_appointment
        $found = false;
        foreach ( array_keys( $GLOBALS['_ws_test_transients'] ) as $k ) {
            if ( strpos( $k, 'ws_sched_rl_book_appointment' ) === 0 ) $found = true;
        }
        $this->assertFalse( $found, 'Le honeypot ne doit pas consommer de quota rate-limit' );
    }

    // ── 5. Présence du honeypot dans le partial public ───────────────────

    public function test_popup_partial_contains_honeypot_field() {
        $partial = file_get_contents( __DIR__ . '/../public/partials/ws-scheduler-public-popup.php' );
        $this->assertStringContainsString( 'name="ws_website"', $partial );
        $this->assertStringContainsString( 'aria-hidden="true"',  $partial );
        // Positionné hors viewport
        $this->assertMatchesRegularExpression( '/left:\s*-9999px/i', $partial );
    }

    public function test_js_source_sends_honeypot_field() {
        $js = file_get_contents( __DIR__ . '/../public/js/ws-scheduler-public.js' );
        $this->assertStringContainsString( 'ws_website', $js );
    }
}
