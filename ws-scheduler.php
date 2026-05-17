<?php
/**
 * Plugin Name:       WS Scheduler
 * Plugin URI:        https://wordpress-freelance.com/plugins/ws-scheduler
 * Description:       Système de prise de rendez-vous complet avec popup calendrier, formulaire et emails automatiques.
 * Version:           4.0.6
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            WebStrategy
 * Author URI:        https://wordpress-freelance.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ws-scheduler
 * Domain Path:       /languages
 */

/*
 * Changelog
 * =========
 *
 * 3.9.10 — JS inliné pour bypass des plugins de cache agressifs
 *   - Le JS public est maintenant inliné dans le HTML via wp_add_inline_script
 *     au lieu d'être servi en fichier externe. Raison : LiteSpeed Cache
 *     (et autres) combinent/différent les fichiers JS, ce qui cassait
 *     l'ordre de chargement et empêchait le handler de clic d'être attaché
 *     pour les visiteurs anonymes.
 *   - Conséquence : aucun plugin de cache ne peut désormais toucher le JS
 *     du booking. Coût : ~5 KB inlinés sur chaque page frontend.
 *
 * 3.9.9 — Purge cache HTML automatique à l'activation
 *   - Le plugin purge maintenant LiteSpeed, WP Rocket, W3TC, WP Super Cache,
 *     WP Fastest Cache et l'object cache WP à chaque (ré)activation.
 *   - Sans ça, les visiteurs anonymes recevaient la version cachée du HTML
 *     générée avant que le plugin soit actif (donc sans le JS de la popup
 *     ni la config wsScheduler), pendant que les loggés voyaient bien le
 *     bon contenu — fenêtre de désynchronisation pendant 12-24h selon le
 *     TTL du cache.
 *
 * 3.9.8 — Rollback sélecteurs élargis (3.9.7)
 *   - Retour au seul .ws-book-btn pour le déclencheur popup
 *   - Suppression de l'API globale window.wsScheduler.open/close
 *
 * 3.9.7 — [révoqué] Sélecteurs étendus, voir 3.9.8
 *
 * 3.9.6 — Fix architecture enqueue (vraie cause du bug booking)
 *   - L'enqueue du JS public et l'appel à wp_localize_script étaient dans
 *     le handler de shortcode render_booking_button. Quand le bouton était
 *     placé via un builder de header (Avada, Elementor) sans passer par
 *     le shortcode, le JS et la config (ajax_url, nonce) n'étaient jamais
 *     injectés. Et même quand le shortcode était rendu, le wp_localize_script
 *     arrivait après wp_head ce qui interagissait mal avec les caches HTML.
 *   - Refactor : enqueue + wp_localize_script déplacés dans la méthode
 *     enqueue_scripts hookée sur wp_enqueue_scripts. Le shortcode handler
 *     ne fait plus que produire le HTML.
 *   - Conséquence : la popup est désormais rendue inconditionnellement
 *     dans wp_footer (plus de garde sur self::$shortcode_used) pour
 *     couvrir tous les emplacements de bouton (.ws-book-btn).
 *
 * 3.9.5 — Fix 403 booking : retrait du nonce sur les endpoints de lecture
 *   - get_month_slots et get_available_slots ne vérifient plus de nonce :
 *     ce sont des endpoints de lecture publique (créneaux disponibles), le
 *     nonce ne protégeait rien et créait du faux 403 quand la page était
 *     servie depuis un cache HTML avec un nonce expiré.
 *   - book_appointment garde le nonce (mutation : insert BDD + envoi email),
 *     c'est là que la protection CSRF a un sens.
 *   - Rollback de la 3.9.4 : suppression de l'endpoint ws_refresh_nonce et
 *     de la logique de retry côté JS, devenues inutiles.
 *
 * 3.9.4 — [révoquée] Refresh nonce + retry, mauvaise approche, voir 3.9.5
 *
 * 3.9.3 — Throttle notice "Renseignez votre clé de licence"
 *   - Petit JS injecté sur toutes les pages admin qui intercepte le
 *     bouton dismiss natif WordPress et persiste un dismiss de 24h dans
 *     localStorage. La notice ne réapparaît qu'une fois par jour.
 *   - Découplé de l'API ws-connector : la signature texte de la notice
 *     suffit à la cibler, fonctionne peu importe la version.
 *   - Pour ré-afficher avant 24h après achat de licence :
 *     localStorage.removeItem('ws_scheduler_license_notice_dismissed_until')
 *
 * 3.9.2 — Retrait des constantes Hub (architecture ws-connector singleton v2.17.11)
 *   - Aucune lecture de WORDPRESS_HUB_URL / WORDPRESS_HUB_API_KEY dans le
 *     plugin enfant. Les credentials Hub vivent désormais uniquement dans
 *     ws-connector
 *   - Partial Licence : URL d'achat hardcodée vers plugin.wordpress-freelance.com
 *     (URL publique stable, pas une credential)
 *   - Handler save_licence : migration vers la convention standard
 *     ws_license_ws-scheduler-pro / ws_license_status_ws-scheduler-pro
 *     (au lieu des conventions legacy ws-scheduler-pro_license_*)
 *   - Purge des caches transient ws-connector + appel sync_now() si
 *     disponible pour re-validation immédiate
 *   - Cleanup des anciennes options à chaque save
 *
 * 3.9.1 — Migration vers ws-connector singleton API (Hub v2.17.11)
 *   - Remplacement de "new WS_Hub_Connector(...)->init()" par l'appel
 *     statique WS_Hub_Connector::register_plugin(...)
 *   - L'enregistrement passe par add_action('plugins_loaded', ..., 20)
 *     pour garantir que ws-connector est chargé avant l'appel
 *   - Mutualisation des hooks WP et requêtes HTTP au Hub côté ws-connector :
 *     bénéfice de TTFB proportionnel au nombre de plugins enfants migrés
 *   - Pas de changement métier — refonte purement architecturale
 *
 * 3.9.0 — Migration vers WS Connector standalone
 *   - Suppression de la copie embarquée de class-ws-hub-connector.php
 *   - Dépendance déclarée via le header WordPress "Requires Plugins: ws-connector"
 *     (WP 6.5+ refuse l'activation si ws-connector n'est pas actif)
 *   - Suppression des defines WORDPRESS_HUB_URL et WORDPRESS_HUB_API_KEY :
 *     ws-connector porte désormais ces constantes de manière centralisée,
 *     plus besoin d'injecter de clé au packaging du plugin enfant
 *   - Init via class_exists('WS_Hub_Connector') avec fallback admin_notices
 *     si ws-connector est absent — pas de fatal Error
 *
 * 3.8.0 — Refonte UX indisponibilités
 *   - 3 modes de saisie clairement séparés : période complète (vacances,
 *     fériés), créneau ponctuel (un jour, plage horaire), récurrent
 *     hebdomadaire (jours cochés + plage horaire)
 *   - FIX : la récurrence hebdomadaire ne fonctionnait pas — la logique de
 *     slots utilisait les datetimes de création de l'indispo au lieu de
 *     reconstruire les bornes pour le jour évalué
 *   - Schéma BDD étendu : ajout de time_start et time_end (TIME) pour
 *     stocker proprement les heures des indispos récurrentes ; migration
 *     idempotente des enregistrements existants au moment de l'activation
 *   - UI : input type="date" et type="time" natifs (plus de datetime-local
 *     ambigu), pills cliquables pour les jours de la semaine
 *   - Liste des indispos enrichie avec un badge Type et un libellé humain
 *
 * 3.7.0 — Audit release (sécurité + alignement écosystème)
 * 3.6.x — Voir l'historique Git
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'WS_SCHEDULER_VERSION',         '4.0.6' );
define( 'WS_SCHEDULER_PLUGIN_DIR',      plugin_dir_path( __FILE__ ) );
define( 'WS_SCHEDULER_PLUGIN_URL',      plugin_dir_url( __FILE__ ) );
define( 'WS_SCHEDULER_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Plugin activation.
 */
function activate_ws_scheduler() {
	require_once WS_SCHEDULER_PLUGIN_DIR . 'includes/class-ws-scheduler-activator.php';
	WS_Scheduler_Activator::activate();
}

/**
 * Plugin deactivation.
 */
function deactivate_ws_scheduler() {
	require_once WS_SCHEDULER_PLUGIN_DIR . 'includes/class-ws-scheduler-deactivator.php';
	WS_Scheduler_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_ws_scheduler' );
register_deactivation_hook( __FILE__, 'deactivate_ws_scheduler' );

require WS_SCHEDULER_PLUGIN_DIR . 'includes/class-ws-scheduler.php';

function run_ws_scheduler() {
	$plugin = new WS_Scheduler();
	$plugin->run();
}
run_ws_scheduler();
