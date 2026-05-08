<?php
/**
 * Tests pour WS_Scheduler_Ajax — validation des inputs AJAX.
 *
 * On teste la couche validation : format des données, champs requis,
 * modes d'indisponibilité. On ne teste pas la couche BDD (besoin wpdb réel).
 */

use WP_Mock\Tools\TestCase;

class Test_WS_Scheduler_Ajax extends TestCase {

    private $ajax;

    public function setUp(): void {
        parent::setUp();
        $_POST = [];
        $this->ajax = new WS_Scheduler_Ajax();
    }

    public function tearDown(): void {
        parent::tearDown();
        $_POST = [];
    }

    // ── get_available_slots ──────────────────────────────────────────

    /**
     * Date au mauvais format → wp_send_json_error, pas d'appel à Slots.
     */
    public function test_get_available_slots_rejects_invalid_date_format() {
        $_POST['date'] = '08/05/2026'; // format fr, pas Y-m-d

        \WP_Mock::userFunction( 'wp_send_json_error', [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 0 ] );

        $this->ajax->get_available_slots();
    }

    /**
     * Date vide → wp_send_json_error.
     */
    public function test_get_available_slots_rejects_empty_date() {
        $_POST['date'] = '';

        \WP_Mock::userFunction( 'wp_send_json_error', [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 0 ] );

        $this->ajax->get_available_slots();
    }

    // ── get_month_slots ──────────────────────────────────────────────

    /**
     * Mois 0 → invalide.
     */
    public function test_get_month_slots_rejects_month_zero() {
        $_POST['year']  = '2026';
        $_POST['month'] = '0';

        \WP_Mock::userFunction( 'wp_send_json_error', [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 0 ] );

        $this->ajax->get_month_slots();
    }

    /**
     * Mois 13 → invalide.
     */
    public function test_get_month_slots_rejects_month_thirteen() {
        $_POST['year']  = '2026';
        $_POST['month'] = '13';

        \WP_Mock::userFunction( 'wp_send_json_error', [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 0 ] );

        $this->ajax->get_month_slots();
    }

    // ── book_appointment ─────────────────────────────────────────────

    /**
     * Champ requis manquant (email absent) → erreur immédiate.
     */
    public function test_book_appointment_rejects_missing_required_field() {
        $_POST = [
            'nonce'      => 'test_nonce',
            'first_name' => 'Sophie',
            'last_name'  => 'Martin',
            // email manquant volontairement
            'slot_start' => '2026-06-01 10:00:00',
        ];

        \WP_Mock::userFunction( 'check_ajax_referer', [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_send_json_error', [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 0 ] );

        $this->ajax->book_appointment();
    }

    /**
     * Email invalide → erreur après vérification des champs requis.
     */
    public function test_book_appointment_rejects_invalid_email() {
        $_POST = [
            'nonce'      => 'test_nonce',
            'first_name' => 'Sophie',
            'last_name'  => 'Martin',
            'email'      => 'pas-un-email',
            'slot_start' => '2026-06-01 10:00:00',
        ];

        \WP_Mock::userFunction( 'check_ajax_referer', [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_send_json_error', [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 0 ] );

        $this->ajax->book_appointment();
    }

    /**
     * Format de slot invalide → erreur.
     */
    public function test_book_appointment_rejects_invalid_slot_format() {
        $_POST = [
            'nonce'      => 'test_nonce',
            'first_name' => 'Sophie',
            'last_name'  => 'Martin',
            'email'      => 'sophie@example.com',
            'slot_start' => '10h00 le 1er juin', // mauvais format
        ];

        \WP_Mock::userFunction( 'check_ajax_referer', [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_send_json_error', [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 0 ] );

        $this->ajax->book_appointment();
    }

    // ── add_unavailability ───────────────────────────────────────────

    /**
     * Mode invalide → erreur.
     */
    public function test_add_unavailability_rejects_invalid_mode() {
        $_POST = [
            'nonce' => 'test_nonce',
            'label' => 'Test',
            'mode'  => 'mode_inexistant',
        ];

        \WP_Mock::userFunction( 'check_ajax_referer', [ 'return' => true ] );
        \WP_Mock::userFunction( 'current_user_can', [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_send_json_error', [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 0 ] );

        $this->ajax->add_unavailability();
    }

    /**
     * Mode period_full sans dates → erreur.
     */
    public function test_add_unavailability_period_full_requires_dates() {
        $_POST = [
            'nonce'      => 'test_nonce',
            'label'      => 'Vacances',
            'mode'       => 'period_full',
            'date_start' => '',
            'date_end'   => '',
        ];

        \WP_Mock::userFunction( 'check_ajax_referer', [ 'return' => true ] );
        \WP_Mock::userFunction( 'current_user_can', [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_send_json_error', [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 0 ] );

        $this->ajax->add_unavailability();
    }

    /**
     * Mode recurring sans jours cochés → erreur.
     */
    public function test_add_unavailability_recurring_requires_days() {
        $_POST = [
            'nonce'      => 'test_nonce',
            'label'      => 'Pause',
            'mode'       => 'recurring',
            'recur_days' => [], // aucun jour sélectionné
            'time_start' => '12:00',
            'time_end'   => '13:00',
        ];

        \WP_Mock::userFunction( 'check_ajax_referer', [ 'return' => true ] );
        \WP_Mock::userFunction( 'current_user_can', [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_send_json_error', [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 0 ] );

        $this->ajax->add_unavailability();
    }

    /**
     * Mode recurring avec heure de fin antérieure à l'heure de début → erreur.
     */
    public function test_add_unavailability_recurring_rejects_inverted_times() {
        $_POST = [
            'nonce'      => 'test_nonce',
            'label'      => 'Pause',
            'mode'       => 'recurring',
            'recur_days' => [ '1', '3' ], // Lundi, Mercredi
            'time_start' => '14:00',
            'time_end'   => '12:00', // fin AVANT début
        ];

        \WP_Mock::userFunction( 'check_ajax_referer', [ 'return' => true ] );
        \WP_Mock::userFunction( 'current_user_can', [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_send_json_error', [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 0 ] );

        $this->ajax->add_unavailability();
    }

    // ── delete_unavailability ────────────────────────────────────────

    /**
     * ID 0 → erreur.
     */
    public function test_delete_unavailability_rejects_zero_id() {
        $_POST = [
            'nonce' => 'test_nonce',
            'id'    => '0',
        ];

        \WP_Mock::userFunction( 'check_ajax_referer', [ 'return' => true ] );
        \WP_Mock::userFunction( 'current_user_can', [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_send_json_error', [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 0 ] );

        $this->ajax->delete_unavailability();
    }
}
