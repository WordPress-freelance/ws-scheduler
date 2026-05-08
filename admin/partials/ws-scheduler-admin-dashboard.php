<?php
defined( 'ABSPATH' ) || exit;

$filter_status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
$args = array( 'limit' => 50 );
if ( $filter_status ) $args['status'] = $filter_status;
$appointments    = WS_Scheduler_DB::get_all_appointments( $args );
$count_upcoming  = WS_Scheduler_DB::count_upcoming();
$count_confirmed = WS_Scheduler_DB::count_by_status( 'confirmed' );
$count_pending   = WS_Scheduler_DB::count_by_status( 'pending' );
$count_cancelled = WS_Scheduler_DB::count_by_status( 'cancelled' );
?>
<div class="wrap ws-admin-wrap">
  <?php include __DIR__ . '/ws-scheduler-admin-header.php'; ?>
    <main class="ws-main">
      <h1 class="ws-page-title"><svg class="ws-title-logo" width="36" height="36" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="32" height="32" rx="7" fill="#221D32"/><path d="M7 10l4 12 5-8 5 8 4-12" stroke="#7C5CBF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="24" cy="10" r="2" fill="#9B8EC4"/></svg> <?php esc_html_e( 'Tableau de bord', 'ws-scheduler' ); ?> <span><?php esc_html_e( 'Rendez-vous', 'ws-scheduler' ); ?></span></h1>

      <div class="ws-stats-grid">
        <div class="ws-stat-card">
          <div class="ws-stat-label"><?php esc_html_e( 'À venir', 'ws-scheduler' ); ?></div>
          <div class="ws-stat-value ok"><?php echo $count_upcoming; ?></div>
        </div>
        <div class="ws-stat-card">
          <div class="ws-stat-label"><?php esc_html_e( 'Confirmés', 'ws-scheduler' ); ?></div>
          <div class="ws-stat-value"><?php echo $count_confirmed; ?></div>
        </div>
        <div class="ws-stat-card">
          <div class="ws-stat-label"><?php esc_html_e( 'En attente', 'ws-scheduler' ); ?></div>
          <div class="ws-stat-value warn"><?php echo $count_pending; ?></div>
        </div>
        <div class="ws-stat-card">
          <div class="ws-stat-label"><?php esc_html_e( 'Annulés', 'ws-scheduler' ); ?></div>
          <div class="ws-stat-value muted"><?php echo $count_cancelled; ?></div>
        </div>
      </div>

      <div class="ws-card">
        <div class="ws-filters">
          <a class="ws-filter-btn <?php echo empty( $filter_status ) ? 'active' : ''; ?>"
             href="<?php echo esc_url( admin_url( 'admin.php?page=ws-scheduler' ) ); ?>"><?php esc_html_e( 'Tous', 'ws-scheduler' ); ?></a>
          <a class="ws-filter-btn <?php echo $filter_status === 'confirmed' ? 'active' : ''; ?>"
             href="<?php echo esc_url( admin_url( 'admin.php?page=ws-scheduler&status=confirmed' ) ); ?>"><?php esc_html_e( 'Confirmés', 'ws-scheduler' ); ?></a>
          <a class="ws-filter-btn <?php echo $filter_status === 'pending' ? 'active' : ''; ?>"
             href="<?php echo esc_url( admin_url( 'admin.php?page=ws-scheduler&status=pending' ) ); ?>"><?php esc_html_e( 'En attente', 'ws-scheduler' ); ?></a>
          <a class="ws-filter-btn <?php echo $filter_status === 'cancelled' ? 'active' : ''; ?>"
             href="<?php echo esc_url( admin_url( 'admin.php?page=ws-scheduler&status=cancelled' ) ); ?>"><?php esc_html_e( 'Annulés', 'ws-scheduler' ); ?></a>
        </div>

        <?php if ( empty( $appointments ) ) : ?>
          <p class="ws-empty"><?php esc_html_e( 'Aucun rendez-vous trouvé.', 'ws-scheduler' ); ?></p>
        <?php else : ?>
          <table class="ws-table">
            <thead>
              <tr>
                <th><?php esc_html_e( 'Date', 'ws-scheduler' ); ?></th>
                <th><?php esc_html_e( 'Heure', 'ws-scheduler' ); ?></th>
                <th><?php esc_html_e( 'Client', 'ws-scheduler' ); ?></th>
                <th><?php esc_html_e( 'Email', 'ws-scheduler' ); ?></th>
                <th><?php esc_html_e( 'Message', 'ws-scheduler' ); ?></th>
                <th><?php esc_html_e( 'Statut', 'ws-scheduler' ); ?></th>
                <th><?php esc_html_e( 'Actions', 'ws-scheduler' ); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ( $appointments as $a ) :
                $tz = new DateTimeZone( wp_timezone_string() );
                $dt = new DateTime( $a->slot_start, $tz );
                $status_class = $a->status === 'confirmed' ? 'ok' : ( $a->status === 'pending' ? 'warn' : 'muted' );
                $status_label = $a->status === 'confirmed' ? __( 'Confirmé', 'ws-scheduler' ) : ( $a->status === 'pending' ? __( 'En attente', 'ws-scheduler' ) : __( 'Annulé', 'ws-scheduler' ) );
              ?>
              <tr data-id="<?php echo $a->id; ?>">
                <td style="white-space:nowrap;"><?php echo $dt->format( 'd/m/Y' ); ?></td>
                <td style="white-space:nowrap;"><?php echo $dt->format( 'H:i' ); ?></td>
                <td style="white-space:nowrap;">
                  <strong><?php echo esc_html( $a->first_name . ' ' . $a->last_name ); ?></strong><?php
                  if ( $a->company ) echo ' <small>· ' . esc_html( $a->company ) . '</small>';
                  if ( $a->phone )   echo ' <small>· ' . esc_html( $a->phone ) . '</small>';
                ?></td>
                <td><?php echo esc_html( $a->email ); ?></td>
                <td><?php if ( ! empty( $a->message ) ) : ?><span class="ws-msg-preview" title="<?php echo esc_attr( $a->message ); ?>"><?php echo esc_html( mb_strimwidth( $a->message, 0, 40, '…' ) ); ?></span><?php else : ?><span style="color:var(--ws-t4);">—</span><?php endif; ?></td>
                <td><span class="ws-badge <?php echo $status_class; ?>"><?php echo esc_html( $status_label ); ?></span></td>
                <td class="ws-actions">
                  <?php if ( $a->status !== 'confirmed' ) : ?>
                    <button class="ws-action-btn ws-confirm-btn" data-id="<?php echo $a->id; ?>" title="<?php esc_attr_e( 'Confirmer', 'ws-scheduler' ); ?>">✓</button>
                  <?php endif; ?>
                  <?php if ( $a->status !== 'cancelled' ) : ?>
                    <button class="ws-action-btn ws-cancel-btn" data-id="<?php echo $a->id; ?>" title="<?php esc_attr_e( 'Annuler', 'ws-scheduler' ); ?>">✗</button>
                  <?php endif; ?>
                  <button class="ws-action-btn ws-delete-btn" data-id="<?php echo $a->id; ?>" title="<?php esc_attr_e( 'Supprimer', 'ws-scheduler' ); ?>">🗑</button>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>

      <!-- Carte d'aide installation : visible en permanence, repliable -->
      <details class="ws-card ws-help-card" <?php echo empty( $appointments ) ? 'open' : ''; ?>>
        <summary style="cursor:pointer;list-style:none;display:flex;align-items:center;gap:10px;font-family:'Lora',Georgia,serif;font-size:18px;font-weight:600;color:#F0EDE8;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#A899D4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          <?php esc_html_e( 'Comment afficher le bouton de réservation sur votre site', 'ws-scheduler' ); ?>
          <span style="margin-left:auto;font-size:12px;font-weight:400;color:#9590A8;font-family:Inter,sans-serif;"><?php esc_html_e( 'cliquer pour replier/déplier', 'ws-scheduler' ); ?></span>
        </summary>

        <div style="margin-top:18px;color:#C4BFDA;line-height:1.65;font-size:13px;">

          <p style="margin:0 0 16px 0;color:#9590A8;">
            <?php esc_html_e( 'Vous avez deux méthodes pour placer le bouton qui ouvrira la popup de réservation. Choisissez celle qui correspond le mieux à votre site.', 'ws-scheduler' ); ?>
          </p>

          <!-- Méthode 1 : shortcode -->
          <div style="background:#221D32;border:1px solid #2E2B38;border-radius:10px;padding:16px 18px;margin-bottom:14px;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
              <span style="background:#7C5CBF;color:#F0EDE8;font-size:11px;font-weight:600;padding:3px 8px;border-radius:10px;letter-spacing:.04em;"><?php esc_html_e( 'MÉTHODE 1', 'ws-scheduler' ); ?></span>
              <strong style="color:#F0EDE8;font-size:14px;"><?php esc_html_e( 'Shortcode', 'ws-scheduler' ); ?></strong>
            </div>
            <p style="margin:0 0 10px 0;">
              <?php esc_html_e( 'Collez ce shortcode dans n\'importe quelle page, article ou widget texte :', 'ws-scheduler' ); ?>
            </p>
            <code style="display:block;background:#0E0C15;border:1px solid #2E2B38;border-radius:6px;padding:10px 14px;color:#A899D4;font-family:'Courier New',monospace;font-size:13px;margin:0 0 10px 0;user-select:all;">[ws_booking_button]</code>
            <p style="margin:0 0 8px 0;font-size:12px;color:#9590A8;">
              <?php esc_html_e( 'Pour personnaliser le libellé du bouton :', 'ws-scheduler' ); ?>
            </p>
            <code style="display:block;background:#0E0C15;border:1px solid #2E2B38;border-radius:6px;padding:10px 14px;color:#A899D4;font-family:'Courier New',monospace;font-size:13px;user-select:all;">[ws_booking_button label="Réserver une démo"]</code>
          </div>

          <!-- Méthode 2 : classe CSS -->
          <div style="background:#221D32;border:1px solid #2E2B38;border-radius:10px;padding:16px 18px;margin-bottom:14px;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
              <span style="background:#7C5CBF;color:#F0EDE8;font-size:11px;font-weight:600;padding:3px 8px;border-radius:10px;letter-spacing:.04em;"><?php esc_html_e( 'MÉTHODE 2', 'ws-scheduler' ); ?></span>
              <strong style="color:#F0EDE8;font-size:14px;"><?php esc_html_e( 'Classe CSS sur un bouton existant', 'ws-scheduler' ); ?></strong>
            </div>
            <p style="margin:0 0 10px 0;">
              <?php esc_html_e( 'Pratique si vous utilisez Avada, Elementor, Divi, ou un constructeur de header : ajoutez simplement la classe CSS', 'ws-scheduler' ); ?>
              <code style="background:#0E0C15;border:1px solid #2E2B38;padding:1px 6px;border-radius:3px;color:#A899D4;font-size:12px;">ws-book-btn</code>
              <?php esc_html_e( 'à n\'importe quel bouton ou lien existant. Le clic ouvrira automatiquement la popup.', 'ws-scheduler' ); ?>
            </p>
            <p style="margin:0;font-size:12px;color:#9590A8;">
              <?php esc_html_e( 'Exemple — dans un éditeur de bouton de votre constructeur de page, dans le champ « Classe CSS » ou « Class », saisissez simplement :', 'ws-scheduler' ); ?>
              <code style="background:#0E0C15;border:1px solid #2E2B38;padding:1px 6px;border-radius:3px;color:#A899D4;font-family:'Courier New',monospace;font-size:12px;user-select:all;">ws-book-btn</code>
            </p>
          </div>

          <!-- Test -->
          <div style="background:linear-gradient(135deg,#1A1724 0%,#221D32 100%);border:1px solid #4A4260;border-radius:10px;padding:14px 18px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#A899D4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
            <div style="flex:1;min-width:200px;">
              <strong style="color:#F0EDE8;display:block;margin-bottom:2px;font-size:13px;"><?php esc_html_e( 'Une fois placé, testez en navigation privée', 'ws-scheduler' ); ?></strong>
              <span style="color:#9590A8;font-size:12px;"><?php esc_html_e( 'Pour vérifier que la popup s\'ouvre côté visiteur sans cookies de connexion admin.', 'ws-scheduler' ); ?></span>
            </div>
            <a href="<?php echo esc_url( home_url() ); ?>" target="_blank" rel="noopener" style="background:#221D32;border:1px solid #4A4260;color:#A899D4;padding:8px 14px;border-radius:8px;text-decoration:none;font-size:12px;font-weight:500;white-space:nowrap;">
              <?php esc_html_e( 'Ouvrir le site →', 'ws-scheduler' ); ?>
            </a>
          </div>

        </div>
      </details>

    </main>
</div>
