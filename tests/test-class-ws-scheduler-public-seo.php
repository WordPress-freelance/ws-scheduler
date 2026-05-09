<?php
/**
 * Tests SEO : aucun h1-h6 ne doit fuiter dans le DOM front-end avant que
 * la popup soit ouverte. Les outils de plan de document (Google, Lighthouse,
 * extensions SEO) crawlent tout le DOM, y compris display:none.
 *
 * @since 4.0.4
 */
use WP_Mock\Tools\TestCase;

class Test_WS_Scheduler_Public_SEO extends TestCase {

    public function test_popup_partial_contains_no_heading_tags() {
        $partial = file_get_contents(
            __DIR__ . '/../public/partials/ws-scheduler-public-popup.php'
        );
        // Match <h1> à <h6> (avec ou sans attributs)
        $this->assertDoesNotMatchRegularExpression(
            '/<h[1-6](\s|>)/i',
            $partial,
            'Le partial popup ne doit contenir aucun <h1>-<h6>. ' .
            'Ces balises fuitent dans le plan du document SEO ' .
            'puisque la popup est rendue dans le DOM même cachée.'
        );
    }

    public function test_popup_titles_use_div_with_heading_role_for_a11y() {
        $partial = file_get_contents(
            __DIR__ . '/../public/partials/ws-scheduler-public-popup.php'
        );
        // Compte les .ws-popup-title — il en faut au moins 3 (steps 1/2/3)
        preg_match_all( '/class="ws-popup-title"/', $partial, $matches );
        $this->assertGreaterThanOrEqual( 3, count( $matches[0] ) );

        // Chaque .ws-popup-title doit avoir role="heading" aria-level="2"
        // pour préserver l'a11y (équivalent ARIA d'un h2).
        preg_match_all(
            '/<div [^>]*class="ws-popup-title"[^>]*role="heading"[^>]*aria-level="2"/',
            $partial,
            $aria_matches
        );
        $this->assertGreaterThanOrEqual(
            3,
            count( $aria_matches[0] ),
            'Tous les .ws-popup-title doivent avoir role="heading" aria-level="2" ' .
            'pour que les screen readers les annoncent comme des titres niveau 2.'
        );
    }

    public function test_popup_dialog_aria_labelledby_targets_existing_id() {
        $partial = file_get_contents(
            __DIR__ . '/../public/partials/ws-scheduler-public-popup.php'
        );
        // Le wrapper a aria-labelledby="ws-popup-title"
        $this->assertMatchesRegularExpression(
            '/aria-labelledby="ws-popup-title"/',
            $partial
        );
        // Et il existe un élément avec id="ws-popup-title"
        $this->assertMatchesRegularExpression(
            '/id="ws-popup-title"/',
            $partial
        );
    }

    public function test_button_partial_contains_no_heading_tags() {
        $partial = file_get_contents(
            __DIR__ . '/../public/partials/ws-scheduler-public-button.php'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<h[1-6](\s|>)/i',
            $partial
        );
    }
}
