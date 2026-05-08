/**
 * WS Scheduler — Admin JS
 * @since 3.3.4
 */
(function($){
  'use strict';

  var cfg = window.wsSchedulerAdmin || {};
  var i18n = cfg.i18n || {};

  function ajax(action, data, cb) {
    data.action = action;
    data.nonce  = cfg.nonce;
    $.post(cfg.ajax_url, data, function(r) {
      if (r.success) { cb(r.data); }
      else { alert(r.data || 'Error'); }
    }).fail(function(){ alert('Network error'); });
  }

  $(document).on('click', '.ws-confirm-btn', function(){
    var id = $(this).data('id');
    ajax('ws_update_appointment', { id: id, status: 'confirmed' }, function(){ location.reload(); });
  });

  $(document).on('click', '.ws-cancel-btn', function(){
    if (!confirm(i18n.confirm_cancel)) return;
    var id = $(this).data('id');
    ajax('ws_update_appointment', { id: id, status: 'cancelled' }, function(){ location.reload(); });
  });

  $(document).on('click', '.ws-delete-btn', function(){
    if (!confirm(i18n.confirm_delete)) return;
    var id = $(this).data('id');
    ajax('ws_delete_appointment', { id: id }, function(){ location.reload(); });
  });

  // ── Switch entre les 3 modes d'ajout d'indispo ──────────────────
  $(document).on('change', 'input[name="ws-unavail-mode"]', function(){
    var mode = $(this).val();
    $('.ws-mode-pane').hide();
    $('.ws-mode-pane[data-mode="'+mode+'"]').show();
  });

  $(document).on('click', '#ws-add-unavail', function(){
    var label = $('#ws-unavail-label').val(),
        mode  = $('input[name="ws-unavail-mode"]:checked').val() || 'period_full',
        payload = { label: label, mode: mode };

    if (mode === 'period_full') {
      var ds = $('#ws-pf-start').val(),
          de = $('#ws-pf-end').val();
      if (!ds || !de) { alert(i18n.dates_required); return; }
      payload.date_start = ds;
      payload.date_end   = de;

    } else if (mode === 'punctual_slot') {
      var d  = $('#ws-ps-date').val(),
          ts = $('#ws-ps-start').val(),
          te = $('#ws-ps-end').val();
      if (!d || !ts || !te) { alert(i18n.dates_required); return; }
      payload.date       = d;
      payload.time_start = ts;
      payload.time_end   = te;

    } else if (mode === 'recurring') {
      var days = $('.ws-rec-day:checked').map(function(){ return parseInt(this.value, 10); }).get();
      var ts = $('#ws-rec-start').val(),
          te = $('#ws-rec-end').val();
      if (!days.length) { alert(i18n.days_required || 'Sélectionnez au moins un jour.'); return; }
      if (!ts || !te) { alert(i18n.dates_required); return; }
      payload.recur_days = days;
      payload.time_start = ts;
      payload.time_end   = te;
    }

    ajax('ws_add_unavailability', payload, function(){ location.reload(); });
  });

  $(document).on('click', '.ws-del-unavail', function(){
    if (!confirm(i18n.confirm_unavail)) return;
    var id = $(this).data('id'), row = $(this).closest('tr');
    ajax('ws_delete_unavailability', { id: id }, function(){ row.fadeOut(300, function(){ row.remove(); }); });
  });

})(jQuery);
