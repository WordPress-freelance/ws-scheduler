<?php defined( 'ABSPATH' ) || exit;
$show_powered = (bool) get_option( 'ws_show_powered_by', false );
?>
<div id="ws-popup-overlay" class="ws-overlay" role="dialog" aria-modal="true" aria-labelledby="ws-popup-title">
  <div class="ws-popup" id="ws-popup">

    <button class="ws-popup-close" id="ws-popup-close" aria-label="<?php esc_attr_e( 'Fermer', 'ws-scheduler' ); ?>" type="button">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>

    <!-- ═══ STEP 1 : Calendar ═══ -->
    <div class="ws-step" id="ws-step-calendar" data-step="1">
      <div class="ws-popup-header">
        <span class="ws-popup-badge"><?php printf( esc_html__( 'Étape %1$d / %2$d', 'ws-scheduler' ), 1, 2 ); ?></span>
        <h2 class="ws-popup-title" id="ws-popup-title"><?php esc_html_e( 'Choisissez votre créneau', 'ws-scheduler' ); ?></h2>
        <p class="ws-popup-sub"><?php esc_html_e( 'Sélectionnez une date disponible, puis un horaire.', 'ws-scheduler' ); ?></p>
      </div>

      <div class="ws-calendar-wrap">
        <div class="ws-cal-nav">
          <button class="ws-cal-arrow" id="ws-cal-prev" aria-label="<?php esc_attr_e( 'Mois précédent', 'ws-scheduler' ); ?>" type="button">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
          </button>
          <span class="ws-cal-month-label" id="ws-cal-month-label">—</span>
          <button class="ws-cal-arrow" id="ws-cal-next" aria-label="<?php esc_attr_e( 'Mois suivant', 'ws-scheduler' ); ?>" type="button">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
          </button>
        </div>
        <div class="ws-cal-dow-row">
          <span><?php esc_html_e( 'Lun', 'ws-scheduler' ); ?></span>
          <span><?php esc_html_e( 'Mar', 'ws-scheduler' ); ?></span>
          <span><?php esc_html_e( 'Mer', 'ws-scheduler' ); ?></span>
          <span><?php esc_html_e( 'Jeu', 'ws-scheduler' ); ?></span>
          <span><?php esc_html_e( 'Ven', 'ws-scheduler' ); ?></span>
          <span><?php esc_html_e( 'Sam', 'ws-scheduler' ); ?></span>
          <span><?php esc_html_e( 'Dim', 'ws-scheduler' ); ?></span>
        </div>
        <div class="ws-cal-grid" id="ws-cal-grid" aria-live="polite">
          <div class="ws-cal-loading"><span class="ws-spinner"></span> <?php esc_html_e( 'Chargement…', 'ws-scheduler' ); ?></div>
        </div>
        <div class="ws-cal-legend">
          <span class="ws-legend-dot available"></span> <?php esc_html_e( 'Disponible', 'ws-scheduler' ); ?>
          <span class="ws-legend-dot full"></span> <?php esc_html_e( 'Complet', 'ws-scheduler' ); ?>
          <span class="ws-legend-dot closed"></span> <?php esc_html_e( 'Fermé', 'ws-scheduler' ); ?>
        </div>
      </div>

      <div class="ws-slots-wrap" id="ws-slots-wrap" hidden>
        <div class="ws-slots-header">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          <span id="ws-slots-date-label"><?php esc_html_e( 'Créneaux disponibles', 'ws-scheduler' ); ?></span>
        </div>
        <div class="ws-slots-grid" id="ws-slots-grid"></div>
      </div>

      <div class="ws-step-footer">
        <span></span>
        <button class="ws-btn-primary" id="ws-step1-next" disabled type="button">
          <?php esc_html_e( 'Continuer', 'ws-scheduler' ); ?>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
      </div>
    </div>

    <!-- ═══ STEP 2 : Form ═══ -->
    <div class="ws-step" id="ws-step-form" data-step="2" hidden>
      <div class="ws-popup-header">
        <span class="ws-popup-badge"><?php printf( esc_html__( 'Étape %1$d / %2$d', 'ws-scheduler' ), 2, 2 ); ?></span>
        <h2 class="ws-popup-title"><?php esc_html_e( 'Vos coordonnées', 'ws-scheduler' ); ?></h2>
        <p class="ws-popup-sub"><?php esc_html_e( 'Complétez le formulaire pour finaliser votre réservation.', 'ws-scheduler' ); ?></p>
      </div>

      <div class="ws-recap-bar" id="ws-recap-bar">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        <span id="ws-recap-text">—</span>
        <button class="ws-recap-edit" id="ws-back-to-calendar" type="button">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
          <?php esc_html_e( 'Modifier', 'ws-scheduler' ); ?>
        </button>
      </div>

      <div class="ws-form-fields">
        <div class="ws-form-row">
          <div class="ws-form-group">
            <label for="ws_first_name"><?php esc_html_e( 'Prénom', 'ws-scheduler' ); ?> <span class="ws-req">*</span></label>
            <input type="text" id="ws_first_name" name="first_name" required autocomplete="given-name">
          </div>
          <div class="ws-form-group">
            <label for="ws_last_name"><?php esc_html_e( 'Nom', 'ws-scheduler' ); ?> <span class="ws-req">*</span></label>
            <input type="text" id="ws_last_name" name="last_name" required autocomplete="family-name">
          </div>
        </div>
        <div class="ws-form-row">
          <div class="ws-form-group">
            <label for="ws_email"><?php esc_html_e( 'Email', 'ws-scheduler' ); ?> <span class="ws-req">*</span></label>
            <input type="email" id="ws_email" name="email" required autocomplete="email">
          </div>
          <div class="ws-form-group">
            <label for="ws_phone"><?php esc_html_e( 'Téléphone', 'ws-scheduler' ); ?></label>
            <input type="tel" id="ws_phone" name="phone" autocomplete="tel">
          </div>
        </div>
        <div class="ws-form-group" style="margin-bottom:12px;">
          <label for="ws_company"><?php esc_html_e( 'Société', 'ws-scheduler' ); ?></label>
          <input type="text" id="ws_company" name="company" autocomplete="organization">
        </div>
        <div class="ws-form-group" style="margin-bottom:12px;">
          <label for="ws_message"><?php esc_html_e( 'Message (optionnel)', 'ws-scheduler' ); ?></label>
          <textarea id="ws_message" name="message" rows="3" placeholder="<?php esc_attr_e( 'Précisez l\'objet de votre rendez-vous…', 'ws-scheduler' ); ?>"></textarea>
        </div>
      </div>

      <div class="ws-step-footer">
        <button class="ws-btn-secondary" id="ws-step2-back" type="button">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
          <?php esc_html_e( 'Retour', 'ws-scheduler' ); ?>
        </button>
        <button class="ws-btn-primary" id="ws-submit-btn" type="button">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
          <?php esc_html_e( 'Confirmer le rendez-vous', 'ws-scheduler' ); ?>
        </button>
      </div>
    </div>

    <!-- ═══ STEP 3 : Confirmation ═══ -->
    <div class="ws-step" id="ws-step-confirm" data-step="3" hidden>
      <div class="ws-confirm-content">
        <div class="ws-confirm-icon">✓</div>
        <h2 class="ws-popup-title"><?php esc_html_e( 'Rendez-vous confirmé !', 'ws-scheduler' ); ?></h2>
        <p class="ws-popup-sub" id="ws-confirm-msg"><?php esc_html_e( 'Vous allez recevoir un email de confirmation.', 'ws-scheduler' ); ?></p>
        <a id="ws-meet-link" class="ws-meet-btn" href="#" target="_blank" rel="noopener">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15.6 11.6L22 7v10l-6.4-4.5v-1zM4 5h9a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V7c0-1.1.9-2 2-2z"/></svg>
          <?php esc_html_e( 'Rejoindre Google Meet', 'ws-scheduler' ); ?>
        </a>
        <button class="ws-btn-secondary" id="ws-close-confirm" type="button"><?php esc_html_e( 'Fermer', 'ws-scheduler' ); ?></button>
      </div>
    </div>

    <?php if ( $show_powered ) : ?>
    <div class="ws-popup-footer">
      <a href="https://wordpress-freelance.com" target="_blank" rel="noopener" class="ws-powered">
        <?php printf( esc_html__( 'Propulsé par %s', 'ws-scheduler' ), '<strong>WebStrategy</strong>' ); ?>
      </a>
    </div>
    <?php endif; ?>
  </div>
</div>
