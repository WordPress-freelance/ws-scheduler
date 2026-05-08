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

// ── Tests supplémentaires ────────────────────────────────────────────

class Test_WS_Scheduler_Slots_Extended extends TestCase {

    public function setUp(): void { parent::setUp(); }
    public function tearDown(): void { parent::tearDown(); }

    /**
     * Les créneaux générés doivent être des chaînes au format H:i (ex: "09:00").
     */
    public function test_slot_strings_match_h_i_format() {
        \WP_Mock::userFunction( 'wp_timezone_string', [ 'return' => 'UTC' ] );
        \WP_Mock::userFunction( 'get_option', [
            'return' => function( $k, $d = false ) {
                $map = [
                    'ws_working_days'    => [1,2,3,4,5,6,7],
                    'ws_max_booking_days'=> 365,
                    'ws_work_start'      => '09:00',
                    'ws_work_end'        => '10:00',
                    'ws_slot_duration'   => 30,
                ];
                return $map[$k] ?? $d;
            },
        ] );

        // Trouver un lundi futur
        $ts = strtotime('next monday');
        $date = gmdate('Y-m-d', $ts);

        // Mock DB pour retourner zéro créneaux réservés
        $wpdb = new MockWpdb();
        $wpdb->return_rows = [];
        $GLOBALS['wpdb'] = $wpdb;

        $slots = WS_Scheduler_Slots::get_slots_for_date( $date );

        foreach ( $slots as $slot ) {
            $this->assertMatchesRegularExpression( '/^\d{2}:\d{2}$/', $slot,
                "Créneau '$slot' ne respecte pas le format H:i" );
        }
    }

    /**
     * La durée de créneau affecte le nombre de créneaux générés.
     * 60 min de plage / 30 min par slot = 2 créneaux.
     * 60 min de plage / 15 min par slot = 4 créneaux.
     */
    public function test_slot_duration_affects_slot_count() {
        $wpdb = new MockWpdb();
        $wpdb->return_rows = [];
        $GLOBALS['wpdb'] = $wpdb;

        $ts   = strtotime( 'next monday' );
        $date = gmdate( 'Y-m-d', $ts );

        // 30 min slots
        \WP_Mock::userFunction( 'wp_timezone_string', [ 'return' => 'UTC' ] );
        \WP_Mock::userFunction( 'get_option', [
            'return' => function( $k, $d = false ) {
                $map = [
                    'ws_working_days'    => [1,2,3,4,5,6,7],
                    'ws_max_booking_days'=> 365,
                    'ws_work_start'      => '09:00',
                    'ws_work_end'        => '10:00',
                    'ws_slot_duration'   => 30,
                ];
                return $map[$k] ?? $d;
            },
        ] );
        $slots_30 = WS_Scheduler_Slots::get_slots_for_date( $date );

        \WP_Mock::tearDown();
        \WP_Mock::setUp();

        // 15 min slots
        \WP_Mock::userFunction( 'wp_timezone_string', [ 'return' => 'UTC' ] );
        \WP_Mock::userFunction( 'get_option', [
            'return' => function( $k, $d = false ) {
                $map = [
                    'ws_working_days'    => [1,2,3,4,5,6,7],
                    'ws_max_booking_days'=> 365,
                    'ws_work_start'      => '09:00',
                    'ws_work_end'        => '10:00',
                    'ws_slot_duration'   => 15,
                ];
                return $map[$k] ?? $d;
            },
        ] );
        $slots_15 = WS_Scheduler_Slots::get_slots_for_date( $date );

        $this->assertGreaterThan( count( $slots_30 ), count( $slots_15 ) );
    }

    /**
     * Un mois futur doit avoir des jours avec statut 'available' ou 'closed'.
     */
    public function test_future_month_has_no_past_days() {
        \WP_Mock::userFunction( 'wp_timezone_string', [ 'return' => 'UTC' ] );
        \WP_Mock::userFunction( 'get_option', [
            'return' => function( $k, $d = false ) {
                $map = [
                    'ws_working_days'    => [1,2,3,4,5],
                    'ws_max_booking_days'=> 365,
                    'ws_work_start'      => '09:00',
                    'ws_work_end'        => '18:00',
                    'ws_slot_duration'   => 30,
                ];
                return $map[$k] ?? $d;
            },
        ] );

        $wpdb = new MockWpdb();
        $wpdb->return_rows = [];
        $GLOBALS['wpdb'] = $wpdb;

        // Mois dans 2 ans
        $result = WS_Scheduler_Slots::get_month_availability( (int)date('Y') + 2, 6 );

        foreach ( $result as $date => $status ) {
            $this->assertNotEquals( 'past', $status,
                "La date $date ne devrait pas avoir le statut 'past' (mois futur)" );
        }
    }

    /**
     * Les samedis et dimanches doivent être 'closed' si seuls les jours 1-5 sont ouvrés.
     */
    public function test_weekends_are_closed_when_not_in_working_days() {
        \WP_Mock::userFunction( 'wp_timezone_string', [ 'return' => 'UTC' ] );
        \WP_Mock::userFunction( 'get_option', [
            'return' => function( $k, $d = false ) {
                $map = [
                    'ws_working_days'    => [1,2,3,4,5], // Lun-Ven uniquement
                    'ws_max_booking_days'=> 365,
                ];
                return $map[$k] ?? $d;
            },
        ] );

        // Mars 2027 : on cherche un samedi (N=6) ou dimanche (N=7)
        $result = WS_Scheduler_Slots::get_month_availability( 2027, 3 );

        foreach ( $result as $date_str => $status ) {
            $dow = (int) ( new DateTime( $date_str ) )->format( 'N' );
            if ( in_array( $dow, [ 6, 7 ] ) ) {
                $this->assertNotEquals( 'available', $status,
                    "Le $date_str (sam/dim) devrait être 'closed', pas '$status'" );
            }
        }
    }

    /**
     * get_slots_for_date doit toujours retourner un tableau.
     */
    public function test_get_slots_always_returns_array() {
        // Pas de mock WP — utilise les stubs du bootstrap
        $result = WS_Scheduler_Slots::get_slots_for_date( '2020-01-01' );
        $this->assertIsArray( $result );
    }
}
