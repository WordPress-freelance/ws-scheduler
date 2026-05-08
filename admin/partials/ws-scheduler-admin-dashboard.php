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
    </main>
</div>
