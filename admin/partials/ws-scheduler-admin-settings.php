<?php
defined( 'ABSPATH' ) || exit;
$working_days = get_option( 'ws_working_days', array( 1, 2, 3, 4, 5 ) );
$work_start   = get_option( 'ws_work_start', '09:00' );
$work_end     = get_option( 'ws_work_end', '18:00' );
$slot_duration = get_option( 'ws_slot_duration', 30 );
$button_label = get_option( 'ws_button_label', __( 'Réserver un créneau', 'ws-scheduler' ) );
$max_days     = get_option( 'ws_max_booking_days', 90 );
$admin_email  = get_option( 'ws_admin_email', '' );
if ( empty( $admin_email ) ) {
	$admin_email = get_option( 'admin_email' );
}
$business     = get_option( 'ws_business_name', get_bloginfo( 'name' ) );
$ws_locale    = get_option( 'ws_scheduler_locale', '' );
$day_labels   = array(
	1 => __( 'Lundi', 'ws-scheduler' ),
	2 => __( 'Mardi', 'ws-scheduler' ),
	3 => __( 'Mercredi', 'ws-scheduler' ),
	4 => __( 'Jeudi', 'ws-scheduler' ),
	5 => __( 'Vendredi', 'ws-scheduler' ),
	6 => __( 'Samedi', 'ws-scheduler' ),
	7 => __( 'Dimanche', 'ws-scheduler' ),
);
?>
<div class="wrap ws-admin-wrap">
  <?php include __DIR__ . '/ws-scheduler-admin-header.php'; ?>
    <main class="ws-main">
      <h1 class="ws-page-title"><svg class="ws-title-logo" width="36" height="36" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="32" height="32" rx="7" fill="#221D32"/><path d="M7 10l4 12 5-8 5 8 4-12" stroke="#7C5CBF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="24" cy="10" r="2" fill="#9B8EC4"/></svg> WS Scheduler <span><?php esc_html_e( 'Réglages', 'ws-scheduler' ); ?></span></h1>

      <?php if ( isset( $_GET['saved'] ) ) : ?>
        <div class="ws-notice ws-notice-ok"><?php esc_html_e( '✅ Réglages sauvegardés.', 'ws-scheduler' ); ?></div>
      <?php endif; ?>

      <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
        <?php wp_nonce_field( 'ws_scheduler_settings' ); ?>
        <input type="hidden" name="action" value="ws_scheduler_save_settings">

        <div class="ws-card">
          <div class="ws-card-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            <?php esc_html_e( 'Informations générales', 'ws-scheduler' ); ?>
          </div>
          <div class="ws-form-row">
            <div class="ws-form-group">
              <label><?php esc_html_e( 'Nom de l\'entreprise', 'ws-scheduler' ); ?></label>
              <input type="text" name="ws_business_name" value="<?php echo esc_attr( $business ); ?>">
            </div>
            <div class="ws-form-group">
              <label><?php esc_html_e( 'Email de notification', 'ws-scheduler' ); ?></label>
              <input type="email" name="ws_admin_email" value="<?php echo esc_attr( $admin_email ); ?>">
              <p class="ws-field-hint"><?php esc_html_e( 'Recevra les notifications de nouveaux RDV.', 'ws-scheduler' ); ?></p>
            </div>
          </div>
          <div class="ws-form-row">
            <div class="ws-form-group">
              <label><?php esc_html_e( 'Langue du plugin', 'ws-scheduler' ); ?></label>
              <select name="ws_scheduler_locale">
                <option value="" <?php selected( $ws_locale, '' ); ?>><?php esc_html_e( 'Langue du site (par défaut)', 'ws-scheduler' ); ?></option>
                <option value="fr_FR" <?php selected( $ws_locale, 'fr_FR' ); ?>>Français</option>
                <option value="en_US" <?php selected( $ws_locale, 'en_US' ); ?>>English</option>
                <option value="es_ES" <?php selected( $ws_locale, 'es_ES' ); ?>>Español</option>
                <option value="de_DE" <?php selected( $ws_locale, 'de_DE' ); ?>>Deutsch</option>
              </select>
              <p class="ws-field-hint"><?php esc_html_e( 'Appliqué au back-office, à la popup et aux emails.', 'ws-scheduler' ); ?></p>
            </div>
          </div>
        </div>

        <div class="ws-card">
          <div class="ws-card-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <?php esc_html_e( 'Horaires et créneaux', 'ws-scheduler' ); ?>
          </div>
          <div class="ws-form-section-title"><?php esc_html_e( 'Jours ouvrables', 'ws-scheduler' ); ?></div>
          <div class="ws-days-grid">
            <?php foreach ( $day_labels as $num => $label ) : ?>
            <label class="ws-day-check">
              <input type="checkbox" name="ws_working_days[]" value="<?php echo $num; ?>"
                <?php checked( in_array( $num, $working_days ) ); ?>>
              <span><?php echo esc_html( $label ); ?></span>
            </label>
            <?php endforeach; ?>
          </div>

          <div class="ws-form-row" style="margin-top:16px;">
            <div class="ws-form-group">
              <label><?php esc_html_e( 'Début de journée', 'ws-scheduler' ); ?></label>
              <input type="time" name="ws_work_start" value="<?php echo esc_attr( $work_start ); ?>">
            </div>
            <div class="ws-form-group">
              <label><?php esc_html_e( 'Fin de journée', 'ws-scheduler' ); ?></label>
              <input type="time" name="ws_work_end" value="<?php echo esc_attr( $work_end ); ?>">
            </div>
          </div>
          <div class="ws-form-row">
            <div class="ws-form-group">
              <label><?php esc_html_e( 'Durée d\'un créneau (minutes)', 'ws-scheduler' ); ?></label>
              <select name="ws_slot_duration">
                <?php foreach ( array( 15, 20, 30, 45, 60, 90, 120 ) as $d ) : ?>
                <option value="<?php echo $d; ?>" <?php selected( $slot_duration, $d ); ?>><?php echo $d; ?> min</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="ws-form-group">
              <label><?php esc_html_e( 'Réservation possible jusqu\'à', 'ws-scheduler' ); ?></label>
              <select name="ws_max_booking_days">
                <?php foreach ( array(
                  7   => __( '1 semaine', 'ws-scheduler' ),
                  14  => __( '2 semaines', 'ws-scheduler' ),
                  30  => __( '1 mois', 'ws-scheduler' ),
                  60  => __( '2 mois', 'ws-scheduler' ),
                  90  => __( '3 mois', 'ws-scheduler' ),
                  180 => __( '6 mois', 'ws-scheduler' ),
                  365 => __( '1 an', 'ws-scheduler' ),
                ) as $v => $l ) : ?>
                <option value="<?php echo $v; ?>" <?php selected( $max_days, $v ); ?>><?php echo esc_html( $l ); ?></option>
                <?php endforeach; ?>
              </select>
              <p class="ws-field-hint"><?php esc_html_e( 'Les clients ne pourront pas réserver au-delà de cette période.', 'ws-scheduler' ); ?></p>
            </div>
          </div>
        </div>

        <div class="ws-card">
          <div class="ws-card-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="7" width="20" height="10" rx="3"/></svg>
            <?php esc_html_e( 'Bouton front-office', 'ws-scheduler' ); ?>
          </div>
          <div class="ws-form-row">
            <div class="ws-form-group">
              <label><?php esc_html_e( 'Texte du bouton', 'ws-scheduler' ); ?></label>
              <input type="text" name="ws_button_label" value="<?php echo esc_attr( $button_label ); ?>">
            </div>
          </div>
          <p class="ws-field-hint"><?php
            printf(
              /* translators: %s: shortcode example */
              esc_html__( 'Shortcode : %1$s — accepte aussi %2$s', 'ws-scheduler' ),
              '<code>[ws_booking_button]</code>',
              '<code>label="' . esc_html__( 'Mon texte', 'ws-scheduler' ) . '"</code>'
            );
          ?></p>
        </div>

        <button type="submit" class="ws-btn-save">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
          <?php esc_html_e( 'Sauvegarder les réglages', 'ws-scheduler' ); ?>
        </button>
      </form>
    </main>
</div>
