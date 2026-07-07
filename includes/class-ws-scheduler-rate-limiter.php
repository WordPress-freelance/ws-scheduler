<?php
/**
 * Rate limiter par IP basé sur les transients WordPress.
 *
 * Anti-abus des endpoints publics AJAX du scheduler :
 *   - book_appointment      : spam de RDV, spam d'emails (fenêtre stricte)
 *   - get_available_slots   : scraping du planning (fenêtre large)
 *   - get_month_slots       : scraping du planning (fenêtre large)
 *
 * Chaque action a sa propre clé de transient, chaque IP son propre compteur.
 * Transient natif : auto-expire, pas de cron nécessaire, backend compatible
 * (Redis, Memcached, DB) — aucune dépendance objet ni écriture directe wpdb.
 *
 * Les seuils sont filtrables (`ws_scheduler_rate_limit_max_hits`,
 * `ws_scheduler_rate_limit_window`) et neutralisables par filtre global
 * (`ws_scheduler_rate_limit_bypass`) — utile pour les tests d'intégration
 * ou pour whitelister une IP de staging.
 *
 * Clés d'options / transients : préfixe `ws_scheduler_` partagé strictement
 * avec ws-scheduler-pro pour la rétrocompat FREE→PRO. Le PRO peut réutiliser
 * cette classe telle quelle ou surcharger les seuils via les filtres.
 *
 * @since 4.0.9
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WS_Scheduler_Rate_Limiter {

	/**
	 * Seuils par défaut par action : max_hits sur une fenêtre glissante
	 * (en secondes). Volontairement stricts sur book_appointment (émet un
	 * email) et plus larges sur les lectures.
	 */
	const DEFAULTS = array(
		'book_appointment'    => array( 'max_hits' => 5,   'window' => 3600 ),  // 5 réservations / heure / IP
		'get_available_slots' => array( 'max_hits' => 120, 'window' => 3600 ),  // 120 lectures / heure / IP
		'get_month_slots'     => array( 'max_hits' => 60,  'window' => 3600 ),  // 60 mois / heure / IP
	);

	/**
	 * Vérifie et incrémente le compteur pour l'action donnée.
	 *
	 * @param string $action  Identifiant de l'action (ex. 'book_appointment').
	 * @return bool  true si l'IP est SOUS le seuil (autorisée),
	 *               false si le seuil est ATTEINT ou DÉPASSÉ (à bloquer).
	 */
	public static function check_and_increment( $action ) {
		if ( apply_filters( 'ws_scheduler_rate_limit_bypass', false, $action ) ) {
			return true;
		}

		$defaults = isset( self::DEFAULTS[ $action ] )
			? self::DEFAULTS[ $action ]
			: array( 'max_hits' => 30, 'window' => 3600 );

		$max_hits = (int) apply_filters( 'ws_scheduler_rate_limit_max_hits', $defaults['max_hits'], $action );
		$window   = (int) apply_filters( 'ws_scheduler_rate_limit_window',   $defaults['window'],   $action );

		$ip  = self::get_client_ip();
		$key = self::build_key( $action, $ip );

		$hits = (int) get_transient( $key );
		if ( $hits >= $max_hits ) {
			return false;
		}

		set_transient( $key, $hits + 1, $window );
		return true;
	}

	/**
	 * Reset explicite du compteur (utilisé par les tests + par un
	 * éventuel bouton admin "débloquer une IP" à venir).
	 */
	public static function reset( $action, $ip = null ) {
		$ip = $ip ?: self::get_client_ip();
		delete_transient( self::build_key( $action, $ip ) );
	}

	/**
	 * Récupère l'IP client. Priorité aux en-têtes de proxy standards
	 * si le site est derrière Cloudflare/nginx, sinon REMOTE_ADDR.
	 *
	 * On hash l'IP avec un salt WP pour ne PAS stocker d'IP en clair
	 * dans les transients (RGPD) — la clé reste stable mais anonymisée.
	 */
	public static function get_client_ip() {
		$candidates = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR' );
		foreach ( $candidates as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$value = $_SERVER[ $header ];
				// X-Forwarded-For peut contenir plusieurs IP séparées par des virgules
				$ip = trim( explode( ',', $value )[0] );
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}
		return '0.0.0.0';
	}

	/**
	 * Construit une clé de transient stable et anonymisée pour (action, IP).
	 * Format court (< 172 chars, limite MySQL des transients longs).
	 */
	protected static function build_key( $action, $ip ) {
		$hash = substr( md5( $ip . wp_salt( 'auth' ) ), 0, 12 );
		return 'ws_sched_rl_' . $action . '_' . $hash;
	}

	/**
	 * Message d'erreur i18n uniforme.
	 */
	public static function throttled_message() {
		return __( 'Trop de requêtes. Merci de patienter quelques minutes avant de réessayer.', 'ws-scheduler' );
	}
}
