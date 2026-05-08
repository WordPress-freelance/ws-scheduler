<?php
/**
 * Tests pour WS_Scheduler_Slots — moteur de génération de créneaux.
 *
 * On teste les cas qui ne nécessitent pas de BDD réelle :
 * - date invalide
 * - jour non ouvrable
 * - date passée
 * - dépassement de la fenêtre max
 * - structure de get_month_availability sur mois passé
 */

use WP_Mock\Tools\TestCase;

class Test_WS_Scheduler_Slots extends TestCase {

    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    // ── get_slots_for_date ───────────────────────────────────────────

    /**
     * Une chaîne qui n'est pas une date valide doit retourner tableau vide.
     */
    public function test_invalid_date_returns_empty_array() {
        $result = WS_Scheduler_Slots::get_slots_for_date( 'not-a-date' );
        $this->assertIsArray( $result );
        $this->assertEmpty( $result );
    }

    /**
     * Une date passée (hier) ne doit retourner aucun créneau.
     */
    public function test_past_date_returns_empty_array() {
        \WP_Mock::userFunction( 'wp_timezone_string', [ 'return' => 'UTC' ] );
        \WP_Mock::userFunction( 'get_option', [
            'return' => function ( $key, $default = false ) {
                $map = [
                    'ws_working_days'    => [ 1, 2, 3, 4, 5, 6, 7 ], // tous les jours ouvrables
                    'ws_max_booking_days'=> 90,
                    'ws_work_start'      => '09:00',
                    'ws_work_end'        => '18:00',
                    'ws_slot_duration'   => 30,
                ];
                return $map[ $key ] ?? $default;
            },
        ] );

        $yesterday = gmdate( 'Y-m-d', strtotime( '-1 day' ) );
        $result    = WS_Scheduler_Slots::get_slots_for_date( $yesterday );

        $this->assertIsArray( $result );
        $this->assertEmpty( $result );
    }

    /**
     * Un dimanche doit retourner tableau vide quand les jours ouvrables
     * sont Lun–Ven uniquement (DOW 1–5).
     */
    public function test_non_working_day_returns_empty_array() {
        \WP_Mock::userFunction( 'wp_timezone_string', [ 'return' => 'UTC' ] );
        \WP_Mock::userFunction( 'get_option', [
            'return' => function ( $key, $default = false ) {
                $map = [
                    'ws_working_days'    => [ 1, 2, 3, 4, 5 ], // Lun–Ven
                    'ws_max_booking_days'=> 365,
                ];
                return $map[ $key ] ?? $default;
            },
        ] );

        // Trouver le prochain dimanche (N=7)
        $ts = strtotime( 'next sunday' );
        // Si on est dimanche, strtotime('next sunday') donne le dimanche suivant — c'est correct
        $next_sunday = gmdate( 'Y-m-d', $ts );

        $result = WS_Scheduler_Slots::get_slots_for_date( $next_sunday );

        $this->assertIsArray( $result );
        $this->assertEmpty( $result );
    }

    /**
     * Une date au-delà de la fenêtre max doit retourner tableau vide.
     */
    public function test_date_beyond_max_booking_window_returns_empty() {
        \WP_Mock::userFunction( 'wp_timezone_string', [ 'return' => 'UTC' ] );
        \WP_Mock::userFunction( 'get_option', [
            'return' => function ( $key, $default = false ) {
                $map = [
                    'ws_working_days'    => [ 1, 2, 3, 4, 5 ],
                    'ws_max_booking_days'=> 30, // fenêtre : 30 jours
                ];
                return $map[ $key ] ?? $default;
            },
        ] );

        // Dans 60 jours → au-delà de la fenêtre de 30 jours
        $far_future = gmdate( 'Y-m-d', strtotime( '+60 days' ) );
        $result     = WS_Scheduler_Slots::get_slots_for_date( $far_future );

        $this->assertIsArray( $result );
        $this->assertEmpty( $result );
    }

    // ── get_month_availability ───────────────────────────────────────

    /**
     * get_month_availability sur un mois entièrement passé doit retourner
     * un tableau de 28–31 entrées avec toutes les valeurs à 'past'.
     */
    public function test_past_month_all_days_marked_past() {
        \WP_Mock::userFunction( 'wp_timezone_string', [ 'return' => 'UTC' ] );
        \WP_Mock::userFunction( 'get_option', [
            'return' => function ( $key, $default = false ) {
                $map = [
                    'ws_working_days'    => [ 1, 2, 3, 4, 5 ],
                    'ws_max_booking_days'=> 90,
                ];
                return $map[ $key ] ?? $default;
            },
        ] );

        // Janvier 2020 est entièrement passé
        $result = WS_Scheduler_Slots::get_month_availability( 2020, 1 );

        $this->assertIsArray( $result );
        $this->assertCount( 31, $result ); // janvier = 31 jours
        $this->assertArrayHasKey( '2020-01-01', $result );
        $this->assertArrayHasKey( '2020-01-31', $result );

        foreach ( $result as $date => $status ) {
            $this->assertEquals(
                'past',
                $status,
                "La date $date devrait avoir le statut 'past', obtenu '$status'"
            );
        }
    }

    /**
     * get_month_availability doit retourner exactement N entrées
     * correspondant au nombre de jours du mois.
     */
    public function test_month_availability_has_correct_day_count() {
        \WP_Mock::userFunction( 'wp_timezone_string', [ 'return' => 'UTC' ] );
        \WP_Mock::userFunction( 'get_option', [
            'return' => function ( $key, $default = false ) {
                $map = [
                    'ws_working_days'    => [ 1, 2, 3, 4, 5 ],
                    'ws_max_booking_days'=> 90,
                ];
                return $map[ $key ] ?? $default;
            },
        ] );

        // Février 2020 (année bissextile) = 29 jours
        $result = WS_Scheduler_Slots::get_month_availability( 2020, 2 );
        $this->assertCount( 29, $result );

        // Février 2021 (non bissextile) = 28 jours
        $result = WS_Scheduler_Slots::get_month_availability( 2021, 2 );
        $this->assertCount( 28, $result );
    }

    /**
     * Les statuts retournés doivent faire partie de la liste valide.
     */
    public function test_month_availability_statuses_are_valid() {
        \WP_Mock::userFunction( 'wp_timezone_string', [ 'return' => 'UTC' ] );
        \WP_Mock::userFunction( 'get_option', [
            'return' => function ( $key, $default = false ) {
                $map = [
                    'ws_working_days'    => [ 1, 2, 3, 4, 5 ],
                    'ws_max_booking_days'=> 90,
                ];
                return $map[ $key ] ?? $default;
            },
        ] );

        $valid   = [ 'past', 'closed', 'full', 'available' ];
        $result  = WS_Scheduler_Slots::get_month_availability( 2020, 6 );

        foreach ( $result as $date => $status ) {
            $this->assertContains(
                $status,
                $valid,
                "Statut inattendu '$status' pour la date $date"
            );
        }
    }
}
