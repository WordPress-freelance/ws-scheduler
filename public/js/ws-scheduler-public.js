/**
 * WS Scheduler — Front-end popup JS
 * @since 3.5.4
 *
 * Le `;` initial protège contre les concats de plugins de cache qui
 * combinent ce script avec un précédent sans `;` terminal (ASI hostile).
 *
 * Le wrapper d'attente jQuery autour de l'IIFE protège contre les cache
 * plugins qui défèrent le chargement de jQuery (LiteSpeed Defer JS,
 * WP Rocket Defer for JavaScript) : notre script inline arrive avant
 * que jQuery soit chargé → `(function($){...})(jQuery)` plantait avec
 * `ReferenceError: jQuery is not defined`. On polle jQuery toutes les
 * 50ms pendant max 5s avant d'exécuter notre code.
 */
;(function(){
  'use strict';

  function wsSchedulerInit($) {

    let cfg          = window.wsScheduler || {},
      overlay,
      curYear, curMonth,
      selectedDate = null,
      selectedSlot = null,
      i18n         = cfg.i18n || {},
      monthLabels  = i18n.months || ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];

  /* ── Helpers ────────────────────────────────────────────── */

  function ajax(action, data, cb) {
    if (!cfg.ajax_url) {
      console.error('WS Scheduler: ajax_url not defined.');
      return;
    }
    data.action = action;
    data.nonce  = cfg.nonce;
    $.post(cfg.ajax_url, data, function(r) {
      if (r.success) cb(r.data);
      else alert(r.data || i18n.error || 'Erreur');
    }).fail(function(){ alert(i18n.error || 'Erreur de connexion'); });
  }

  /* ── Init overlay ref on DOM ready ─────────────────────── */

  $(function(){
    overlay = $('#ws-popup-overlay');
    if (overlay.length && !overlay.data('moved')) {
      overlay.appendTo('body');
      overlay.data('moved', true);
    }
  });

  /* ── Open popup ────────────────────────────────────────── */

  $(document).on('click', '.ws-book-btn', function(e) {
    e.preventDefault();
    if (!overlay || !overlay.length) {
      overlay = $('#ws-popup-overlay');
    }
    if (!overlay.length) return;
    resetPopup();
    overlay.addClass('ws-visible');
    $('body').css('overflow','hidden');
    let now = new Date();
    curYear  = now.getFullYear();
    curMonth = now.getMonth() + 1;
    loadMonth();
  });

  /* ── Close popup ───────────────────────────────────────── */

  function closePopup() {
    if (!overlay || !overlay.length) return;
    overlay.removeClass('ws-visible');
    $('body').css('overflow','');
  }

  $(document).on('click', '#ws-popup-close, #ws-close-confirm', function(e) {
    e.preventDefault();
    closePopup();
  });

  $(document).on('click', '#ws-popup-overlay', function(e) {
    if (e.target === this) closePopup();
  });

  $(document).on('keydown', function(e) {
    if (e.key === 'Escape' && overlay && overlay.hasClass('ws-visible')) closePopup();
  });

  function resetPopup() {
    selectedDate = null;
    selectedSlot = null;
    showStep(1);
    $('#ws-slots-wrap').attr('hidden','');
    $('#ws-step1-next').prop('disabled', true);
    $('#ws_first_name,#ws_last_name,#ws_email,#ws_phone,#ws_company,#ws_message').val('');
    $('.ws-form-fields input').removeClass('ws-invalid');
  }

  /* ── Step navigation ───────────────────────────────────── */

  function showStep(n) {
    $('.ws-step').attr('hidden','');
    let id = n === 1 ? 'ws-step-calendar' : (n === 2 ? 'ws-step-form' : 'ws-step-confirm');
    $('#' + id).removeAttr('hidden');
  }

  $(document).on('click', '#ws-step1-next', function() {
    if (!selectedDate || !selectedSlot) return;
    $('#ws-recap-text').text(formatDate(selectedDate) + ' à ' + selectedSlot);
    showStep(2);
  });

  $(document).on('click', '#ws-step2-back, #ws-back-to-calendar', function() { showStep(1); });

  /* ── Calendar navigation ───────────────────────────────── */

  function getMaxMonth() {
    let now = new Date();
    let max = new Date(now.getTime() + (cfg.max_booking_days || 90) * 86400000);
    return { year: max.getFullYear(), month: max.getMonth() + 1 };
  }

  function updateNavButtons() {
    let now  = new Date();
    let minY = now.getFullYear(), minM = now.getMonth() + 1;
    let mx   = getMaxMonth();
    $('#ws-cal-prev').prop('disabled', curYear <= minY && curMonth <= minM);
    $('#ws-cal-next').prop('disabled', curYear >= mx.year && curMonth >= mx.month);
  }

  $(document).on('click', '#ws-cal-prev', function() {
    if ($(this).prop('disabled')) return;
    curMonth--;
    if (curMonth < 1) { curMonth = 12; curYear--; }
    loadMonth();
  });

  $(document).on('click', '#ws-cal-next', function() {
    if ($(this).prop('disabled')) return;
    curMonth++;
    if (curMonth > 12) { curMonth = 1; curYear++; }
    loadMonth();
  });

  function loadMonth() {
    $('#ws-cal-month-label').text(monthLabels[curMonth - 1] + ' ' + curYear);
    $('#ws-cal-grid').html('<div class="ws-cal-loading"><span class="ws-spinner"></span> ' + (i18n.loading || 'Chargement…') + '</div>');
    updateNavButtons();
    ajax('ws_get_month_slots', { year: curYear, month: curMonth }, renderMonth);
  }

  function renderMonth(data) {
    let grid     = $('#ws-cal-grid').empty();
    let firstDay = new Date(curYear, curMonth - 1, 1).getDay();
    let offset   = (firstDay === 0) ? 6 : firstDay - 1;

    for (let i = 0; i < offset; i++) {
      grid.append('<div class="ws-cal-day"></div>');
    }

    $.each(data, function(date, status) {
      let day = parseInt(date.split('-')[2], 10);
      let cls = 'ws-cal-day ' + status;
      if (selectedDate === date) cls += ' selected';
      grid.append('<div class="' + cls + '" data-date="' + date + '">' + day + '</div>');
    });
  }

  /* ── Date click → load slots ───────────────────────────── */

  $(document).on('click', '.ws-cal-day.available', function() {
    let date = $(this).data('date');
    selectedDate = date;
    selectedSlot = null;
    $('#ws-step1-next').prop('disabled', true);

    $('.ws-cal-day').removeClass('selected');
    $(this).addClass('selected');

    $('#ws-slots-wrap').removeAttr('hidden');
    $('#ws-slots-date-label').text((i18n.slots_of || 'Créneaux du') + ' ' + formatDate(date));
    $('#ws-slots-grid').html('<span class="ws-spinner"></span>');

    ajax('ws_get_available_slots', { date: date }, function(slots) {
      let grid = $('#ws-slots-grid').empty();
      if (!slots || slots.length === 0) {
        grid.html('<span style="color:var(--ws-t4);font-size:12px;">' + (i18n.no_slots || 'Aucun créneau disponible') + '</span>');
        return;
      }
      $.each(slots, function(i, slot) {
        grid.append('<button type="button" class="ws-slot-btn" data-slot="' + slot + '">' + slot + '</button>');
      });
    });
  });

  /* ── Slot click ────────────────────────────────────────── */

  $(document).on('click', '.ws-slot-btn', function() {
    selectedSlot = $(this).data('slot');
    $('.ws-slot-btn').removeClass('selected');
    $(this).addClass('selected');
    $('#ws-step1-next').prop('disabled', false);
  });

  /* ── Submit booking ────────────────────────────────────── */

  $(document).on('click', '#ws-submit-btn', function() {
    let btn   = $(this);
    let first = $('#ws_first_name').val().trim(),
        last  = $('#ws_last_name').val().trim(),
        email = $('#ws_email').val().trim();

    let valid = true;
    $('.ws-form-fields input').removeClass('ws-invalid');
    if (!first) { $('#ws_first_name').addClass('ws-invalid'); valid = false; }
    if (!last)  { $('#ws_last_name').addClass('ws-invalid');  valid = false; }
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { $('#ws_email').addClass('ws-invalid'); valid = false; }
    if (!valid) return;

    btn.prop('disabled', true).text(i18n.loading || 'Envoi en cours…');

    ajax('ws_book_appointment', {
      first_name: first,
      last_name:  last,
      email:      email,
      phone:      $('#ws_phone').val().trim(),
      company:    $('#ws_company').val().trim(),
      message:    $('#ws_message').val().trim(),
      slot_start: selectedDate + ' ' + selectedSlot,
      // Honeypot : le champ est caché aux humains (position absolue + aria-hidden),
      // donc toujours vide côté légitime. Un bot qui parse le HTML et fill tous
      // les inputs se trahit côté serveur.
      ws_website: $('#ws_website').val()
    }, function(data) {
      btn.prop('disabled', false).html(
        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> ' + (i18n.confirm || 'Confirmer le rendez-vous')
      );
      if (data.meet_link) {
        $('#ws-meet-link').attr('href', data.meet_link).css('display','inline-flex');
      } else {
        $('#ws-meet-link').hide();
      }
      showStep(3);
    });
  });

  /* ── Format helper ─────────────────────────────────────── */

  function formatDate(dateStr) {
    let parts  = dateStr.split('-');
    let d      = new Date(parts[0], parts[1] - 1, parts[2]);
    let days   = i18n.days || ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
    let months = i18n.months_lower || ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
    return days[d.getDay()] + ' ' + d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
  }

  } // end wsSchedulerInit

  // ── Boot : attend que jQuery soit chargé avant d'exécuter notre code ──
  // Cache plugins (LiteSpeed Cache Defer JS, WP Rocket Defer for JavaScript)
  // peuvent défer le chargement de jQuery. Notre script inline arrive avant
  // que jQuery soit dispo dans window → ReferenceError "jQuery is not defined".
  // On polle window.jQuery toutes les 50ms pendant max 5 secondes.
  function wsTryBoot() {
    if (typeof window.jQuery !== 'undefined') {
      wsSchedulerInit(window.jQuery);
      return true;
    }
    return false;
  }

  if (wsTryBoot()) { return; }

  var tries = 0;
  var iv = setInterval(function() {
    if (wsTryBoot() || ++tries > 100) {
      clearInterval(iv);
      if (tries > 100 && window.console && window.console.error) {
        window.console.error('WS Scheduler: jQuery not loaded after 5 seconds.');
      }
    }
  }, 50);

})();
