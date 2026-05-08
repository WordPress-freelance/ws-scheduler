<?php
/**
 * Licence Pro page — static upsell, no API call.
 *
 * @package WS_Scheduler
 * @since   4.0.0
 */
defined( 'ABSPATH' ) || exit;

$buy_url = 'https://plugin.wordpress-freelance.com/ws-scheduler-pro/';
?>
<div class="wrap ws-admin-wrap">
  <?php include __DIR__ . '/ws-scheduler-admin-header.php'; ?>
  <main class="ws-main">

    <h1 class="ws-page-title">
      <svg class="ws-title-logo" width="36" height="36" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect width="32" height="32" rx="7" fill="#221D32"/>
        <path d="M7 10l4 12 5-8 5 8 4-12" stroke="#7C5CBF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="24" cy="10" r="2" fill="#9B8EC4"/>
      </svg>
      WS Scheduler <span><?php esc_html_e( 'Licence Pro', 'ws-scheduler' ); ?></span>
    </h1>

    <!-- Free vs Pro banner -->
    <div class="ws-card" style="background:linear-gradient(135deg,#1A1724 0%,#221D32 100%);border-color:#4A4260;margin-bottom:16px;">
      <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
        <div style="flex:1;min-width:200px;">
          <div style="font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:var(--ws-accent-m);margin-bottom:6px;"><?php esc_html_e( 'Version actuelle', 'ws-scheduler' ); ?></div>
          <div style="font-family:'Lora',serif;font-size:20px;font-weight:600;color:var(--ws-t1);margin-bottom:4px;">WS Scheduler Free</div>
          <div style="font-size:12px;color:var(--ws-t3);line-height:1.6;"><?php esc_html_e( 'Prise de rendez-vous autonome, sans appels serveur externes.', 'ws-scheduler' ); ?></div>
        </div>
        <div style="display:flex;align-items:center;gap:4px;color:var(--ws-t4);font-size:20px;">→</div>
        <div style="flex:1;min-width:200px;">
          <div style="font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:#E8A93A;margin-bottom:6px;"><?php esc_html_e( 'Passer à', 'ws-scheduler' ); ?></div>
          <div style="font-family:'Lora',serif;font-size:20px;font-weight:600;color:var(--ws-t1);margin-bottom:4px;">WS Scheduler Pro</div>
          <div style="font-size:12px;color:var(--ws-t3);line-height:1.6;"><?php esc_html_e( 'Google Calendar, Google Meet, emails personnalisés, sans abonnement.', 'ws-scheduler' ); ?></div>
        </div>
        <a href="<?php echo esc_url( $buy_url ); ?>" target="_blank" rel="noopener noreferrer" class="ws-btn-save" style="white-space:nowrap;text-decoration:none;background:#E8A93A;color:#14121C;">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          <?php esc_html_e( 'Obtenir la licence Pro — 49 €', 'ws-scheduler' ); ?>
        </a>
      </div>
    </div>

    <!-- Features grid -->
    <div class="ws-card">
      <div class="ws-card-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
        <?php esc_html_e( 'Fonctionnalités Pro', 'ws-scheduler' ); ?>
      </div>
      <div class="wsl-features">

        <div class="wsl-feature">
          <div class="wsl-feature-icon">📅</div>
          <div>
            <strong><?php esc_html_e( 'Synchronisation Google Calendar', 'ws-scheduler' ); ?></strong>
            <span><?php esc_html_e( 'Chaque rendez-vous confirmé crée automatiquement un événement dans votre Google Agenda. Annulations et mises à jour synchronisées en temps réel.', 'ws-scheduler' ); ?></span>
          </div>
        </div>

        <div class="wsl-feature">
          <div class="wsl-feature-icon">🎥</div>
          <div>
            <strong><?php esc_html_e( 'Liens Google Meet automatiques', 'ws-scheduler' ); ?></strong>
            <span><?php esc_html_e( 'Un lien Meet unique est généré et inclus dans l'email de confirmation envoyé au client et à l'administrateur.', 'ws-scheduler' ); ?></span>
          </div>
        </div>

        <div class="wsl-feature">
          <div class="wsl-feature-icon">✉️</div>
          <div>
            <strong><?php esc_html_e( 'Emails personnalisés', 'ws-scheduler' ); ?></strong>
            <span><?php esc_html_e( 'Couleurs, logo, messages de confirmation et d'annulation entièrement configurables depuis l'interface admin.', 'ws-scheduler' ); ?></span>
          </div>
        </div>

        <div class="wsl-feature">
          <div class="wsl-feature-icon">🎨</div>
          <div>
            <strong><?php esc_html_e( 'Masquer le crédit WebStrategy', 'ws-scheduler' ); ?></strong>
            <span><?php esc_html_e( 'Supprimez le lien « Propulsé par WebStrategy » du popup et des emails pour une expérience entièrement à votre marque.', 'ws-scheduler' ); ?></span>
          </div>
        </div>

      </div>
    </div>

    <!-- Pricing -->
    <div class="ws-card">
      <div class="ws-card-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
        <?php esc_html_e( 'Tarif', 'ws-scheduler' ); ?>
      </div>
      <div class="wsl-pricing">
        <div class="wsl-price">
          <div class="wsl-price-label"><?php esc_html_e( 'Licence à vie', 'ws-scheduler' ); ?></div>
          <div class="wsl-price-value">49 €</div>
          <div class="wsl-price-sub"><?php esc_html_e( 'Paiement unique · 1 site · Mises à jour incluses', 'ws-scheduler' ); ?></div>
        </div>
        <div class="wsl-price" style="border-color:var(--ws-accent);background:rgba(124,92,191,.06);">
          <div class="wsl-price-label" style="color:var(--ws-accent-m);"><?php esc_html_e( 'Agence (5 sites)', 'ws-scheduler' ); ?></div>
          <div class="wsl-price-value">99 €</div>
          <div class="wsl-price-sub"><?php esc_html_e( 'Paiement unique · 5 sites · Mises à jour incluses', 'ws-scheduler' ); ?></div>
        </div>
      </div>
      <a href="<?php echo esc_url( $buy_url ); ?>" target="_blank" rel="noopener noreferrer" class="ws-btn-save" style="text-decoration:none;margin-top:4px;background:#E8A93A;color:#14121C;">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>
        <?php esc_html_e( 'Acheter sur plugin.wordpress-freelance.com', 'ws-scheduler' ); ?>
      </a>
    </div>

    <!-- FAQ rapide -->
    <div class="ws-card">
      <div class="ws-card-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        FAQ
      </div>
      <div class="ws-info-text">
        <strong style="color:var(--ws-t1);display:block;margin-bottom:2px;"><?php esc_html_e( 'La licence Pro est-elle un abonnement ?', 'ws-scheduler' ); ?></strong>
        <?php esc_html_e( 'Non. C'est un paiement unique. Vous recevez les mises à jour pour la durée de vie du plugin.', 'ws-scheduler' ); ?>
      </div>
      <div class="ws-info-text">
        <strong style="color:var(--ws-t1);display:block;margin-bottom:2px;"><?php esc_html_e( 'Ai-je besoin d'un compte Google pour la version Pro ?', 'ws-scheduler' ); ?></strong>
        <?php esc_html_e( 'Oui. La synchronisation Google Calendar et Meet nécessite une application OAuth2 Google (instructions fournies dans la documentation Pro).', 'ws-scheduler' ); ?>
      </div>
      <div class="ws-info-text">
        <strong style="color:var(--ws-t1);display:block;margin-bottom:2px;"><?php esc_html_e( 'Le plugin Pro remplace-t-il le plugin Free ?', 'ws-scheduler' ); ?></strong>
        <?php esc_html_e( 'Non. WS Scheduler Pro est une extension complémentaire — vous gardez le plugin Free actif et installez Pro en supplément.', 'ws-scheduler' ); ?>
      </div>
    </div>

  </main>
</div>
