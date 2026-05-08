<?php
defined( 'ABSPATH' ) || exit;
$unavailabilities = WS_Scheduler_DB::get_unavailabilities();

/**
 * Helper d'affichage : transforme une indispo en libellé humain.
 * 3 cas : récurrent / ponctuel journée entière / ponctuel créneau.
 */
$render_unavail_label = function( $u ) {
	$days_fr = array(
		1 => __( 'Lun', 'ws-scheduler' ),
		2 => __( 'Mar', 'ws-scheduler' ),
		3 => __( 'Mer', 'ws-scheduler' ),
		4 => __( 'Jeu', 'ws-scheduler' ),
		5 => __( 'Ven', 'ws-scheduler' ),
		6 => __( 'Sam', 'ws-scheduler' ),
		7 => __( 'Dim', 'ws-scheduler' ),
	);

	if ( $u->is_recurring && ! empty( $u->recur_days ) ) {
		$days = array_map( 'intval', explode( ',', $u->recur_days ) );
		$labels = array_map( function( $d ) use ( $days_fr ) {
			return $days_fr[ $d ] ?? '?';
		}, $days );
		$ts = substr( $u->time_start, 0, 5 );
		$te = substr( $u->time_end,   0, 5 );
		return sprintf(
			/* translators: 1: list of days, 2: start time, 3: end time */
			__( 'Tous les %1$s · %2$s–%3$s', 'ws-scheduler' ),
			implode( ', ', $labels ), $ts, $te
		);
	}

	$ds = strtotime( $u->date_start );
	$de = strtotime( $u->date_end );
	$is_full_day = ( gmdate( 'H:i:s', $ds ) === '00:00:00' && in_array( gmdate( 'H:i:s', $de ), array( '23:59:00', '23:59:59' ), true ) );
	$same_day    = ( gmdate( 'Y-m-d', $ds ) === gmdate( 'Y-m-d', $de ) );

	if ( $is_full_day && $same_day ) {
		return sprintf( __( 'Le %s · journée entière', 'ws-scheduler' ), gmdate( 'd/m/Y', $ds ) );
	}
	if ( $is_full_day ) {
		return sprintf(
			__( 'Du %1$s au %2$s · journée(s) entière(s)', 'ws-scheduler' ),
			gmdate( 'd/m/Y', $ds ), gmdate( 'd/m/Y', $de )
		);
	}
	if ( $same_day ) {
		return sprintf(
			__( 'Le %1$s · %2$s–%3$s', 'ws-scheduler' ),
			gmdate( 'd/m/Y', $ds ), gmdate( 'H:i', $ds ), gmdate( 'H:i', $de )
		);
	}
	return sprintf(
		__( 'Du %1$s à %2$s au %3$s à %4$s', 'ws-scheduler' ),
		gmdate( 'd/m/Y', $ds ), gmdate( 'H:i', $ds ),
		gmdate( 'd/m/Y', $de ), gmdate( 'H:i', $de )
	);
};

$days_full = array(
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
      <h1 class="ws-page-title">
        <svg class="ws-title-logo" width="36" height="36" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="32" height="32" rx="7" fill="#221D32"/><path d="M7 10l4 12 5-8 5 8 4-12" stroke="#7C5CBF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="24" cy="10" r="2" fill="#9B8EC4"/></svg>
        <?php esc_html_e( 'Gestion des', 'ws-scheduler' ); ?> <span><?php esc_html_e( 'Indisponibilités', 'ws-scheduler' ); ?></span>
      </h1>

      <div class="ws-card dk">
        <div class="ws-card-title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <?php esc_html_e( 'Ajouter une indisponibilité', 'ws-scheduler' ); ?>
        </div>

        <!-- Sélecteur de mode -->
        <div class="ws-mode-tabs" role="tablist">
          <label class="ws-mode-tab">
            <input type="radio" name="ws-unavail-mode" value="period_full" checked>
            <span><?php esc_html_e( 'Période complète', 'ws-scheduler' ); ?></span>
            <small><?php esc_html_e( 'Vacances, fériés', 'ws-scheduler' ); ?></small>
          </label>
          <label class="ws-mode-tab">
            <input type="radio" name="ws-unavail-mode" value="punctual_slot">
            <span><?php esc_html_e( 'Créneau ponctuel', 'ws-scheduler' ); ?></span>
            <small><?php esc_html_e( 'Un jour, une plage horaire', 'ws-scheduler' ); ?></small>
          </label>
          <label class="ws-mode-tab">
            <input type="radio" name="ws-unavail-mode" value="recurring">
            <span><?php esc_html_e( 'Récurrent hebdo', 'ws-scheduler' ); ?></span>
            <small><?php esc_html_e( 'Tous les lundis 15h-16h', 'ws-scheduler' ); ?></small>
          </label>
        </div>

        <div class="ws-form-row">
          <div class="ws-form-group">
            <label><?php esc_html_e( 'Libellé (optionnel)', 'ws-scheduler' ); ?></label>
            <input type="text" id="ws-unavail-label" placeholder="<?php esc_attr_e( 'Ex : Vacances août, Pause déjeuner…', 'ws-scheduler' ); ?>">
          </div>
        </div>

        <!-- Mode 1 : Période complète -->
        <div class="ws-mode-pane" data-mode="period_full">
          <div class="ws-form-row">
            <div class="ws-form-group">
              <label><?php esc_html_e( 'Du', 'ws-scheduler' ); ?></label>
              <input type="date" id="ws-pf-start">
            </div>
            <div class="ws-form-group">
              <label><?php esc_html_e( 'Au', 'ws-scheduler' ); ?></label>
              <input type="date" id="ws-pf-end">
            </div>
          </div>
          <p class="ws-field-hint"><?php esc_html_e( 'L\'indisponibilité couvrira la totalité des journées sélectionnées (00:00 à 23:59).', 'ws-scheduler' ); ?></p>
        </div>

        <!-- Mode 2 : Créneau ponctuel -->
        <div class="ws-mode-pane" data-mode="punctual_slot" style="display:none;">
          <div class="ws-form-row">
            <div class="ws-form-group">
              <label><?php esc_html_e( 'Date', 'ws-scheduler' ); ?></label>
              <input type="date" id="ws-ps-date">
            </div>
            <div class="ws-form-group">
              <label><?php esc_html_e( 'De', 'ws-scheduler' ); ?></label>
              <input type="time" id="ws-ps-start" step="900">
            </div>
            <div class="ws-form-group">
              <label><?php esc_html_e( 'À', 'ws-scheduler' ); ?></label>
              <input type="time" id="ws-ps-end" step="900">
            </div>
          </div>
          <p class="ws-field-hint"><?php esc_html_e( 'Pour bloquer une plage précise un jour donné. Précis à la minute.', 'ws-scheduler' ); ?></p>
        </div>

        <!-- Mode 3 : Récurrent -->
        <div class="ws-mode-pane" data-mode="recurring" style="display:none;">
          <div class="ws-form-row">
            <div class="ws-form-group" style="flex:1;">
              <label><?php esc_html_e( 'Jours concernés', 'ws-scheduler' ); ?></label>
              <div class="ws-day-pills">
                <?php foreach ( $days_full as $num => $name ) : ?>
                <label class="ws-day-pill">
                  <input type="checkbox" class="ws-rec-day" value="<?php echo $num; ?>">
                  <span><?php echo esc_html( $name ); ?></span>
                </label>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
          <div class="ws-form-row">
            <div class="ws-form-group">
              <label><?php esc_html_e( 'De', 'ws-scheduler' ); ?></label>
              <input type="time" id="ws-rec-start" step="900">
            </div>
            <div class="ws-form-group">
              <label><?php esc_html_e( 'À', 'ws-scheduler' ); ?></label>
              <input type="time" id="ws-rec-end" step="900">
            </div>
          </div>
          <p class="ws-field-hint"><?php esc_html_e( 'Le créneau sera bloqué chaque semaine, sur tous les jours cochés.', 'ws-scheduler' ); ?></p>
        </div>

        <button type="button" id="ws-add-unavail" class="ws-btn-save">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          <?php esc_html_e( 'Ajouter', 'ws-scheduler' ); ?>
        </button>
      </div>

      <div class="ws-card">
        <div class="ws-card-title"><?php esc_html_e( 'Indisponibilités enregistrées', 'ws-scheduler' ); ?></div>
        <?php if ( empty( $unavailabilities ) ) : ?>
          <p class="ws-empty"><?php esc_html_e( 'Aucune indisponibilité enregistrée.', 'ws-scheduler' ); ?></p>
        <?php else : ?>
          <table class="ws-table">
            <thead><tr>
              <th><?php esc_html_e( 'Type', 'ws-scheduler' ); ?></th>
              <th><?php esc_html_e( 'Libellé', 'ws-scheduler' ); ?></th>
              <th><?php esc_html_e( 'Quand', 'ws-scheduler' ); ?></th>
              <th><?php esc_html_e( 'Actions', 'ws-scheduler' ); ?></th>
            </tr></thead>
            <tbody id="ws-unavail-list">
              <?php foreach ( $unavailabilities as $u ) : ?>
              <tr data-id="<?php echo intval( $u->id ); ?>">
                <td>
                  <?php if ( $u->is_recurring ) : ?>
                    <span class="ws-badge" style="background:rgba(124,92,191,.15);color:var(--ws-accent-l);"><?php esc_html_e( 'Récurrent', 'ws-scheduler' ); ?></span>
                  <?php else : ?>
                    <span class="ws-badge" style="background:rgba(149,144,168,.15);color:var(--ws-t2);"><?php esc_html_e( 'Ponctuel', 'ws-scheduler' ); ?></span>
                  <?php endif; ?>
                </td>
                <td><?php echo esc_html( $u->label ?: '—' ); ?></td>
                <td><?php echo esc_html( $render_unavail_label( $u ) ); ?></td>
                <td><button class="ws-action-btn ws-del-unavail" data-id="<?php echo intval( $u->id ); ?>" title="<?php esc_attr_e( 'Supprimer', 'ws-scheduler' ); ?>">🗑</button></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </main>
</div>
