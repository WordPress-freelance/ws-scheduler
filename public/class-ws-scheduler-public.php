<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @since      3.0.0
 * @package    WS_Scheduler
 * @subpackage WS_Scheduler/public
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WS_Scheduler_Public {

	private $plugin_name;
	private $version;
	private static $popup_rendered = false;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Enqueue le CSS public sur tout le frontend.
	 *
	 * Pas de "lazy enqueue" conditionnel à la présence du shortcode :
	 * le bouton de booking peut être placé dans un builder de header
	 * (Avada, Elementor, etc.) qui ne passe pas par le shortcode et donc
	 * ne déclencherait pas l'enqueue. Charger inconditionnellement coûte
	 * ~8 KB sur les pages sans bouton — négligeable, et ça garantit que
	 * la popup s'affiche partout où l'utilisateur a placé un .ws-book-btn.
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			$this->plugin_name,
			WS_SCHEDULER_PLUGIN_URL . 'public/css/ws-scheduler-public.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Enqueue le JS public + injection de la config (ajax_url, nonce, i18n)
	 * via wp_localize_script.
	 *
	 * Le code JS est INLINÉ (wp_add_inline_script sur un handle sans src)
	 * plutôt que servi en fichier externe. Raison : les plugins de cache
	 * comme LiteSpeed Cache combinent/différent les fichiers JS externes
	 * et cassent parfois l'ordre de chargement (jQuery pas encore dispo
	 * quand notre code s'exécute, ou script combiné après wp_localize_script
	 * d'autres plugins). En inlinant, on garantit que :
	 *  - le code est dans le HTML, imperméable aux optims cache
	 *  - wsScheduler est défini AVANT le code qui l'utilise (wp_localize_script
	 *    imprime "before" l'inline data, l'inline body après)
	 *  - jQuery est chargé avant grâce à la dépendance déclarée
	 */
	public function enqueue_scripts() {
		wp_register_script(
			$this->plugin_name,
			false, // pas de src — handle vide pour rattacher inline + localize
			array( 'jquery' ),
			$this->version,
			true
		);
		wp_enqueue_script( $this->plugin_name );

		wp_localize_script( $this->plugin_name, 'wsScheduler', array(
			'ajax_url'         => admin_url( 'admin-ajax.php' ),
			'nonce'            => wp_create_nonce( 'ws_scheduler_nonce' ),
			'max_booking_days' => (int) get_option( 'ws_max_booking_days', 90 ),
			'i18n'             => array(
				'confirm'    => __( 'Rendez-vous confirmé !', 'ws-scheduler' ),
				'slot_taken' => __( 'Ce créneau n\'est plus disponible.', 'ws-scheduler' ),
				'loading'    => __( 'Chargement…', 'ws-scheduler' ),
				'no_slots'   => __( 'Aucun créneau disponible ce jour.', 'ws-scheduler' ),
				'error'      => __( 'Une erreur est survenue.', 'ws-scheduler' ),
				'slots_of'   => __( 'Créneaux du', 'ws-scheduler' ),
				'months'     => array(
					__( 'Janvier', 'ws-scheduler' ), __( 'Février', 'ws-scheduler' ),
					__( 'Mars', 'ws-scheduler' ), __( 'Avril', 'ws-scheduler' ),
					__( 'Mai', 'ws-scheduler' ), __( 'Juin', 'ws-scheduler' ),
					__( 'Juillet', 'ws-scheduler' ), __( 'Août', 'ws-scheduler' ),
					__( 'Septembre', 'ws-scheduler' ), __( 'Octobre', 'ws-scheduler' ),
					__( 'Novembre', 'ws-scheduler' ), __( 'Décembre', 'ws-scheduler' ),
				),
				'days'       => array(
					__( 'Dimanche', 'ws-scheduler' ), __( 'Lundi', 'ws-scheduler' ),
					__( 'Mardi', 'ws-scheduler' ), __( 'Mercredi', 'ws-scheduler' ),
					__( 'Jeudi', 'ws-scheduler' ), __( 'Vendredi', 'ws-scheduler' ),
					__( 'Samedi', 'ws-scheduler' ),
				),
				'months_lower' => array(
					__( 'janvier', 'ws-scheduler' ), __( 'février', 'ws-scheduler' ),
					__( 'mars', 'ws-scheduler' ), __( 'avril', 'ws-scheduler' ),
					__( 'mai', 'ws-scheduler' ), __( 'juin', 'ws-scheduler' ),
					__( 'juillet', 'ws-scheduler' ), __( 'août', 'ws-scheduler' ),
					__( 'septembre', 'ws-scheduler' ), __( 'octobre', 'ws-scheduler' ),
					__( 'novembre', 'ws-scheduler' ), __( 'décembre', 'ws-scheduler' ),
				),
			),
		) );

		// Inline le code JS directement dans le HTML.
		$js_path = WS_SCHEDULER_PLUGIN_DIR . 'public/js/ws-scheduler-public.js';
		if ( file_exists( $js_path ) ) {
			$js_source = file_get_contents( $js_path );
			if ( false !== $js_source ) {
				wp_add_inline_script( $this->plugin_name, $js_source );
			}
		}
	}

	/**
	 * Exclut notre handle des combineurs/minifieurs de plugins de cache.
	 *
	 * S'applique à TOUS les tags <script> que WP émet pour notre handle :
	 * - le tag inline injecté par wp_localize_script (config wsScheduler)
	 * - le tag "before" et "after" injectés par wp_add_inline_script
	 *
	 * Attributs reconnus :
	 *  - `data-no-optimize="1"`           → LiteSpeed Cache, WP Rocket
	 *  - `data-no-minify="1"`             → LiteSpeed Cache
	 *  - `data-no-defer="1"`              → LiteSpeed Cache, WP Rocket
	 *  - `data-cfasync="false"`           → Cloudflare Rocket Loader
	 *  - `data-noptimize="1"`             → Autoptimize
	 *
	 * Sans ces attributs, LiteSpeed Cache combine notre JS inliné avec
	 * d'autres scripts inline du site dans un seul bundle minifié. Si un
	 * autre script du bundle a un défaut de syntaxe, TOUT le bundle plante
	 * et notre popup ne fonctionne plus — d'où "missing } after function
	 * body" remonté sur LE bundle, pas notre code.
	 *
	 * @param string $tag    Le HTML du tag <script>.
	 * @param string $handle Le handle du script enqueued.
	 * @return string
	 */
	public function mark_script_no_optimize( $tag, $handle ) {
		if ( $handle !== $this->plugin_name ) {
			return $tag;
		}
		// Inject les attributs APRÈS `<script` et avant le premier `>` ou ` `
		return preg_replace(
			'/<script(\s|>)/',
			'<script data-no-optimize="1" data-no-minify="1" data-no-defer="1" data-cfasync="false" data-noptimize="1"$1',
			$tag,
			1 // une seule occurrence (les ws_add_inline_script peuvent générer plusieurs <script>)
		);
	}

	/**
	 * Shortcode [ws_booking_button label="..." class="..."]
	 *
	 * Produit uniquement le HTML du bouton — l'enqueue du JS/CSS et
	 * l'injection de la config se font dans enqueue_styles/enqueue_scripts
	 * sur le hook wp_enqueue_scripts.
	 */
	public function render_booking_button( $atts ) {
		$saved_label   = get_option( 'ws_button_label', '' );
		$default_fr    = 'Réserver un créneau';
		$button_label  = ( empty( $saved_label ) || $saved_label === $default_fr )
			? __( 'Réserver un créneau', 'ws-scheduler' )
			: $saved_label;

		$atts = shortcode_atts( array(
			'label' => $button_label,
			'class' => '',
		), $atts, 'ws_booking_button' );

		ob_start();
		include WS_SCHEDULER_PLUGIN_DIR . 'public/partials/ws-scheduler-public-button.php';
		return ob_get_clean();
	}

	/**
	 * Render the popup HTML in wp_footer.
	 *
	 * Rendue inconditionnellement sur tout le frontend pour couvrir le cas
	 * où le bouton .ws-book-btn est placé via un builder (Avada, Elementor)
	 * sans passer par le shortcode [ws_booking_button]. Le coût HTML est
	 * minime, et ça garantit que la popup est toujours dispo dès qu'un
	 * .ws-book-btn est cliqué.
	 *
	 * Le static $popup_rendered évite simplement le double rendu si
	 * wp_footer est déclenché plusieurs fois (cas exotique).
	 */
	public function render_popup_once() {
		if ( self::$popup_rendered ) return;
		if ( is_admin() ) return;
		self::$popup_rendered = true;
		include WS_SCHEDULER_PLUGIN_DIR . 'public/partials/ws-scheduler-public-popup.php';
	}
}
