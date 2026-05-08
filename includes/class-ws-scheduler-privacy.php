<?php
/**
 * Privacy / GDPR integration for WS Scheduler.
 *
 * Implémente les hooks WordPress de gestion des données personnelles :
 *  - Personal data exporter   (wp_privacy_personal_data_exporters)
 *  - Personal data eraser     (wp_privacy_personal_data_erasers)
 *  - Suggested privacy text   (wp_add_privacy_policy_content)
 *
 * Le plugin stocke nom, prénom, email, société, téléphone, message — soit
 * de la PII au sens RGPD. Le bouton "Exporter les données personnelles" et
 * "Effacer les données personnelles" de WordPress (Outils → Données
 * personnelles) doit pouvoir cibler ces enregistrements.
 *
 * @since 4.0.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WS_Scheduler_Privacy {

	/**
	 * Enregistre l'exporter de données personnelles.
	 *
	 * @param array $exporters
	 * @return array
	 */
	public static function register_exporter( $exporters ) {
		$exporters['ws-scheduler'] = array(
			'exporter_friendly_name' => __( 'Rendez-vous WS Scheduler', 'ws-scheduler' ),
			'callback'               => array( __CLASS__, 'export_user_data' ),
		);
		return $exporters;
	}

	/**
	 * Enregistre l'eraser de données personnelles.
	 *
	 * @param array $erasers
	 * @return array
	 */
	public static function register_eraser( $erasers ) {
		$erasers['ws-scheduler'] = array(
			'eraser_friendly_name' => __( 'Rendez-vous WS Scheduler', 'ws-scheduler' ),
			'callback'             => array( __CLASS__, 'erase_user_data' ),
		);
		return $erasers;
	}

	/**
	 * Exporte tous les rendez-vous associés à un email.
	 *
	 * @param string $email_address
	 * @param int    $page (1-indexed) — paginé par 100
	 * @return array
	 */
	public static function export_user_data( $email_address, $page = 1 ) {
		$page     = (int) $page;
		$per_page = 100;
		$offset   = ( $page - 1 ) * $per_page;

		global $wpdb;
		$table = $wpdb->prefix . 'ws_appointments';

		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $table WHERE email = %s ORDER BY slot_start DESC LIMIT %d OFFSET %d",
			$email_address, $per_page, $offset
		) );

		$export_items = array();
		foreach ( (array) $rows as $row ) {
			$data = array(
				array( 'name' => __( 'Prénom', 'ws-scheduler' ),    'value' => $row->first_name ),
				array( 'name' => __( 'Nom', 'ws-scheduler' ),       'value' => $row->last_name ),
				array( 'name' => __( 'Société', 'ws-scheduler' ),   'value' => $row->company ),
				array( 'name' => __( 'Email', 'ws-scheduler' ),     'value' => $row->email ),
				array( 'name' => __( 'Téléphone', 'ws-scheduler' ), 'value' => $row->phone ),
				array( 'name' => __( 'Message', 'ws-scheduler' ),   'value' => $row->message ),
				array( 'name' => __( 'Début', 'ws-scheduler' ),     'value' => $row->slot_start ),
				array( 'name' => __( 'Fin', 'ws-scheduler' ),       'value' => $row->slot_end ),
				array( 'name' => __( 'Statut', 'ws-scheduler' ),    'value' => $row->status ),
				array( 'name' => __( 'Créé le', 'ws-scheduler' ),   'value' => $row->created_at ),
			);

			$export_items[] = array(
				'group_id'    => 'ws-scheduler',
				'group_label' => __( 'Rendez-vous', 'ws-scheduler' ),
				'item_id'     => 'ws-appointment-' . $row->id,
				'data'        => $data,
			);
		}

		$done = count( $rows ) < $per_page;

		return array(
			'data' => $export_items,
			'done' => $done,
		);
	}

	/**
	 * Efface tous les rendez-vous associés à un email.
	 *
	 * @param string $email_address
	 * @param int    $page (1-indexed)
	 * @return array
	 */
	public static function erase_user_data( $email_address, $page = 1 ) {
		$page     = (int) $page;
		$per_page = 100;
		$offset   = ( $page - 1 ) * $per_page;

		global $wpdb;
		$table = $wpdb->prefix . 'ws_appointments';

		$ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT id FROM $table WHERE email = %s ORDER BY id ASC LIMIT %d OFFSET %d",
			$email_address, $per_page, $offset
		) );

		$items_removed = 0;
		foreach ( (array) $ids as $id ) {
			if ( $wpdb->delete( $table, array( 'id' => (int) $id ), array( '%d' ) ) ) {
				$items_removed++;
			}
		}

		$done = count( $ids ) < $per_page;

		return array(
			'items_removed'  => $items_removed,
			'items_retained' => 0,
			'messages'       => array(),
			'done'           => $done,
		);
	}

	/**
	 * Ajoute un texte suggéré à la politique de confidentialité du site.
	 * Apparaît dans Réglages → Confidentialité → Modèle de politique.
	 */
	public static function add_privacy_policy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) return;

		$content = __( 'Lorsque les visiteurs prennent rendez-vous via WS Scheduler, les données suivantes sont collectées et stockées localement dans la base de données du site (tables wp_ws_appointments et wp_ws_unavailabilities) : prénom, nom, société (facultatif), email, téléphone (facultatif), message (facultatif), date et heure du créneau, statut du rendez-vous, date de création.', 'ws-scheduler' );
		$content .= "\n\n";
		$content .= __( 'Aucune donnée n\'est transmise à des serveurs externes par la version gratuite. Les emails de confirmation et de notification sont envoyés via wp_mail() (configuration SMTP du site).', 'ws-scheduler' );
		$content .= "\n\n";
		$content .= __( 'Les données peuvent être exportées ou effacées à la demande de la personne concernée via Outils → Données personnelles → Exporter / Effacer.', 'ws-scheduler' );

		wp_add_privacy_policy_content( 'WS Scheduler', wp_kses_post( wpautop( $content, false ) ) );
	}
}
