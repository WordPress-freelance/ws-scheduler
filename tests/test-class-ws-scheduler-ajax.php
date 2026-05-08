<?php
use WP_Mock\Tools\TestCase;

class Test_WS_Scheduler_Ajax extends TestCase {

    private WS_Scheduler_Ajax $ajax;
    private $wpdb_backup;

    public function setUp(): void {
        parent::setUp();
        $_POST = [];
        $this->wpdb_backup = $GLOBALS['wpdb'] ?? null;
        $GLOBALS['wpdb']   = new MockWpdb();
        $this->ajax = new WS_Scheduler_Ajax();
    }

    public function tearDown(): void {
        parent::tearDown();
        $_POST = [];
        $GLOBALS['wpdb'] = $this->wpdb_backup;
    }

    // ── get_available_slots ───────────────────────────────────────────

    public function test_get_available_slots_rejects_invalid_date_format() {
        $_POST['date'] = '08/05/2026';
        \WP_Mock::userFunction( 'wp_send_json_error',   [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 0 ] );
        $this->ajax->get_available_slots();
    }

    public function test_get_available_slots_rejects_empty_date() {
        $_POST['date'] = '';
        \WP_Mock::userFunction( 'wp_send_json_error',   [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 0 ] );
        $this->ajax->get_available_slots();
    }

    public function test_get_available_slots_accepts_valid_date() {
        $_POST['date'] = '2020-01-15'; // date passée → slots vides mais pas d'erreur
        \WP_Mock::userFunction( 'wp_timezone_string', [ 'return' => 'UTC' ] );
        \WP_Mock::userFunction( 'get_option', [ 'return' => false ] );
        \WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_send_json_error',   [ 'times' => 0 ] );
        $this->ajax->get_available_slots();
    }

    // ── get_month_slots ───────────────────────────────────────────────

    public function test_get_month_slots_rejects_month_zero() {
        $_POST = [ 'year' => '2026', 'month' => '0' ];
        \WP_Mock::userFunction( 'wp_send_json_error',   [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 0 ] );
        $this->ajax->get_month_slots();
    }

    public function test_get_month_slots_rejects_month_thirteen() {
        $_POST = [ 'year' => '2026', 'month' => '13' ];
        \WP_Mock::userFunction( 'wp_send_json_error',   [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 0 ] );
        $this->ajax->get_month_slots();
    }

    public function test_get_month_slots_accepts_valid_month() {
        $_POST = [ 'year' => '2020', 'month' => '3' ]; // passé → tout closed/past
        \WP_Mock::userFunction( 'wp_timezone_string', [ 'return' => 'UTC' ] );
        \WP_Mock::userFunction( 'get_option', [ 'return' => false ] );
        \WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_send_json_error',   [ 'times' => 0 ] );
        $this->ajax->get_month_slots();
    }

    // ── book_appointment ──────────────────────────────────────────────

    public function test_book_appointment_rejects_missing_required_field() {
        $_POST = [ 'nonce' => 'n', 'first_name' => 'Sophie', 'last_name' => 'Martin', 'slot_start' => '2026-06-01 10:00:00' ];
        \WP_Mock::userFunction( 'check_ajax_referer',   [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_send_json_error',   [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 0 ] );
        $this->ajax->book_appointment();
    }

    public function test_book_appointment_rejects_invalid_email() {
        $_POST = [ 'nonce' => 'n', 'first_name' => 'Sophie', 'last_name' => 'Martin', 'email' => 'pas-un-email', 'slot_start' => '2026-06-01 10:00:00' ];
        \WP_Mock::userFunction( 'check_ajax_referer',   [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_send_json_error',   [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 0 ] );
        $this->ajax->book_appointment();
    }

    public function test_book_appointment_rejects_invalid_slot_format() {
        $_POST = [ 'nonce' => 'n', 'first_name' => 'S', 'last_name' => 'M', 'email' => 's@ex.com', 'slot_start' => 'mauvais-format' ];
        \WP_Mock::userFunction( 'check_ajax_referer',   [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_send_json_error',   [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 0 ] );
        $this->ajax->book_appointment();
    }

    // ── update_appointment ────────────────────────────────────────────

    public function test_update_appointment_rejects_invalid_status() {
        $_POST = [ 'nonce' => 'n', 'id' => '5', 'status' => 'invalid' ];
        \WP_Mock::userFunction( 'check_ajax_referer', [ 'return' => true ] );
        \WP_Mock::userFunction( 'current_user_can',   [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_send_json_error', [ 'times' => 1 ] );
        $this->ajax->update_appointment();
    }

    public function test_update_appointment_rejects_zero_id() {
        $_POST = [ 'nonce' => 'n', 'id' => '0', 'status' => 'confirmed' ];
        \WP_Mock::userFunction( 'check_ajax_referer', [ 'return' => true ] );
        \WP_Mock::userFunction( 'current_user_can',   [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_send_json_error', [ 'times' => 1 ] );
        $this->ajax->update_appointment();
    }

    public function test_update_appointment_accepts_status_confirmed() {
        $_POST = [ 'nonce' => 'n', 'id' => '7', 'status' => 'confirmed' ];
        \WP_Mock::userFunction( 'check_ajax_referer',   [ 'return' => true ] );
        \WP_Mock::userFunction( 'current_user_can',     [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_send_json_error',   [ 'times' => 0 ] );
        $this->ajax->update_appointment();
    }

    public function test_update_appointment_accepts_status_pending() {
        $_POST = [ 'nonce' => 'n', 'id' => '7', 'status' => 'pending' ];
        \WP_Mock::userFunction( 'check_ajax_referer',   [ 'return' => true ] );
        \WP_Mock::userFunction( 'current_user_can',     [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 1 ] );
        \WP_Mock::userFunction( 'wp_send_json_error',   [ 'times' => 0 ] );
        $this->ajax->update_appointment();
    }

    // ── delete_appointment ────────────────────────────────────────────

    public function test_delete_appointment_rejects_zero_id() {
        $_POST = [ 'nonce' => 'n', 'id' => '0' ];
        \WP_Mock::userFunction( 'check_ajax_referer', [ 'return' => true ] );
        \WP_Mock::userFunction( 'current_user_can',   [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_send_json_error', [ 'times' => 1 ] );
        $this->ajax->delete_appointment();
    }

    public function test_delete_appointment_accepts_valid_id() {
        $_POST = [ 'nonce' => 'n', 'id' => '42' ];
        \WP_Mock::userFunction( 'check_ajax_referer',   [ 'return' => true ] );
        \WP_Mock::userFunction( 'current_user_can',     [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 1 ] );
        $this->ajax->delete_appointment();
    }

    // ── add_unavailability ────────────────────────────────────────────

    public function test_add_unavailability_rejects_invalid_mode() {
        $_POST = [ 'nonce' => 'n', 'label' => 'T', 'mode' => 'mode_inexistant' ];
        \WP_Mock::userFunction( 'check_ajax_referer', [ 'return' => true ] );
        \WP_Mock::userFunction( 'current_user_can',   [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_send_json_error', [ 'times' => 1 ] );
        $this->ajax->add_unavailability();
    }

    public function test_add_unavailability_period_full_requires_dates() {
        $_POST = [ 'nonce' => 'n', 'label' => 'V', 'mode' => 'period_full', 'date_start' => '', 'date_end' => '' ];
        \WP_Mock::userFunction( 'check_ajax_referer', [ 'return' => true ] );
        \WP_Mock::userFunction( 'current_user_can',   [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_send_json_error', [ 'times' => 1 ] );
        $this->ajax->add_unavailability();
    }

    public function test_add_unavailability_period_full_rejects_inverted_dates() {
        $_POST = [ 'nonce' => 'n', 'label' => 'T', 'mode' => 'period_full', 'date_start' => '2026-06-20', 'date_end' => '2026-06-10' ];
        \WP_Mock::userFunction( 'check_ajax_referer', [ 'return' => true ] );
        \WP_Mock::userFunction( 'current_user_can',   [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_send_json_error', [ 'times' => 1 ] );
        $this->ajax->add_unavailability();
    }

    public function test_add_unavailability_period_full_accepts_same_day() {
        $_POST = [ 'nonce' => 'n', 'label' => 'F', 'mode' => 'period_full', 'date_start' => '2026-07-14', 'date_end' => '2026-07-14' ];
        \WP_Mock::userFunction( 'check_ajax_referer',   [ 'return' => true ] );
        \WP_Mock::userFunction( 'current_user_can',     [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 1 ] );
        $this->ajax->add_unavailability();
    }

    public function test_add_unavailability_recurring_requires_days() {
        $_POST = [ 'nonce' => 'n', 'label' => 'P', 'mode' => 'recurring', 'recur_days' => [], 'time_start' => '12:00', 'time_end' => '13:00' ];
        \WP_Mock::userFunction( 'check_ajax_referer', [ 'return' => true ] );
        \WP_Mock::userFunction( 'current_user_can',   [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_send_json_error', [ 'times' => 1 ] );
        $this->ajax->add_unavailability();
    }

    public function test_add_unavailability_recurring_rejects_inverted_times() {
        $_POST = [ 'nonce' => 'n', 'label' => 'P', 'mode' => 'recurring', 'recur_days' => ['1'], 'time_start' => '14:00', 'time_end' => '12:00' ];
        \WP_Mock::userFunction( 'check_ajax_referer', [ 'return' => true ] );
        \WP_Mock::userFunction( 'current_user_can',   [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_send_json_error', [ 'times' => 1 ] );
        $this->ajax->add_unavailability();
    }

    public function test_add_unavailability_recurring_accepts_valid_payload() {
        $_POST = [ 'nonce' => 'n', 'label' => 'P', 'mode' => 'recurring', 'recur_days' => ['1','3'], 'time_start' => '12:00', 'time_end' => '13:00' ];
        \WP_Mock::userFunction( 'check_ajax_referer',   [ 'return' => true ] );
        \WP_Mock::userFunction( 'current_user_can',     [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 1 ] );
        $this->ajax->add_unavailability();
    }

    // ── delete_unavailability ─────────────────────────────────────────

    public function test_delete_unavailability_rejects_zero_id() {
        $_POST = [ 'nonce' => 'n', 'id' => '0' ];
        \WP_Mock::userFunction( 'check_ajax_referer', [ 'return' => true ] );
        \WP_Mock::userFunction( 'current_user_can',   [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_send_json_error', [ 'times' => 1 ] );
        $this->ajax->delete_unavailability();
    }

    public function test_delete_unavailability_accepts_valid_id() {
        $_POST = [ 'nonce' => 'n', 'id' => '99' ];
        \WP_Mock::userFunction( 'check_ajax_referer',   [ 'return' => true ] );
        \WP_Mock::userFunction( 'current_user_can',     [ 'return' => true ] );
        \WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 1 ] );
        $this->ajax->delete_unavailability();
    }
}
