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

    // ── Defense against cache plugin script combiners (4.0.7) ────────
    //
    // Bug reproduit en prod (Hostinger + LiteSpeed Cache) : le bundle JS
    // combiné par LiteSpeed plantait avec "missing } after function body"
    // — notre script inliné était combiné avec d'autres inline scripts
    // (un de ces autres scripts avait une syntax error qui faisait planter
    // tout le bundle, y compris le nôtre).
    //
    // Fix : le JS source commence par `;` pour casser l'ASI hostile, et un
    // filtre script_loader_tag marque notre handle comme non-optimisable
    // pour tous les cache plugins majeurs.

    public function test_js_source_starts_with_defensive_semicolon() {
        $js = file_get_contents(
            __DIR__ . '/../public/js/ws-scheduler-public.js'
        );
        // Skip le block comment d'entête s'il y en a un
        $code = preg_replace( '!^/\*.*?\*/\s*!s', '', $js );
        $this->assertStringStartsWith(
            ';',
            $code,
            'Le JS source doit commencer par `;` (après le block comment) ' .
            'pour casser l\'ASI hostile quand un cache plugin combine ' .
            'notre script avec un précédent sans `;` terminal.'
        );
    }

    public function test_mark_script_no_optimize_returns_tag_unchanged_for_other_handles() {
        $public = new WS_Scheduler_Public( 'ws-scheduler', '4.0.7' );
        $original = '<script src="other.js"></script>';
        $result = $public->mark_script_no_optimize( $original, 'jquery-core' );
        $this->assertEquals( $original, $result );
    }

    public function test_mark_script_no_optimize_injects_all_cache_attrs_for_our_handle() {
        $public = new WS_Scheduler_Public( 'ws-scheduler', '4.0.7' );
        $original = '<script id="ws-scheduler-js-after">/* our inline JS */</script>';
        $result = $public->mark_script_no_optimize( $original, 'ws-scheduler' );

        // LiteSpeed Cache + WP Rocket
        $this->assertStringContainsString( 'data-no-optimize="1"', $result );
        // LiteSpeed Cache (minify exclusion)
        $this->assertStringContainsString( 'data-no-minify="1"',   $result );
        // LiteSpeed Cache + WP Rocket (defer exclusion)
        $this->assertStringContainsString( 'data-no-defer="1"',    $result );
        // Cloudflare Rocket Loader exclusion
        $this->assertStringContainsString( 'data-cfasync="false"', $result );
        // Autoptimize exclusion
        $this->assertStringContainsString( 'data-noptimize="1"',   $result );
    }

    public function test_mark_script_no_optimize_preserves_script_content_and_attrs() {
        $public = new WS_Scheduler_Public( 'ws-scheduler', '4.0.7' );
        $original = '<script id="ws-scheduler-js-after">var x = 1;</script>';
        $result = $public->mark_script_no_optimize( $original, 'ws-scheduler' );
        $this->assertStringContainsString( 'id="ws-scheduler-js-after"', $result );
        $this->assertStringContainsString( 'var x = 1;', $result );
        $this->assertStringContainsString( '</script>', $result );
    }
}
