/* ==========================================================================
   Student module — Events & Activities  (student/events.html)
   Event list -> event detail, hash routed:
     #/evt/EVT-2026-011            event detail
   Events addressed to faculty/staff only stay hidden; student-shell.js
   provides the navigation, the top bar and the student-accounts-only gate.

   The Upcoming / Ongoing / Completed chips are derived from the portal's own
   demo clock (OSAS.SEED_NOW) plus the stored publication status, so phases
   stay consistent with the admin dashboard at any machine date.

   Controls derive their row from the live hash rather than the row they were
   bound with, so a re-mounted view can never act on stale data.
   ========================================================================== */
(function () {
  'use strict';

  var shell = OSAS.studentShell.mount({ page: 'events', title: 'Events & Activities' });
  if (!shell) { return; }

  var U = OSAS.util, C = OSAS.components;
  var main = shell.main;

  var state = { q: '', phase: 'All' };
  var PHASES = [
    { key: 'All', id: 'all', label: 'All Events' },
    { key: 'Upcoming', id: 'upcoming', label: 'Upcoming' },
    { key: 'Ongoing', id: 'ongoing', label: 'Ongoing' },
    { key: 'Completed', id: 'completed', label: 'Completed' }
  ];

  function $(id) { return document.getElementById(id); }

  /* --------------------------------------------------------------- data */
  var FACULTY_ONLY = ['Faculty & Staff'];

  function rows() {
    return U.sortBy(OSAS.store.all('events').filter(function (row) {
      return FACULTY_ONLY.indexOf(String(row.audience || '')) === -1;
    }), 'code', 'asc');
  }
  function byCode(code) {
    return rows().filter(function (row) { return row.code === code; })[0] || null;
  }
  function phase(row) {
    if (row.status === 'Completed') { return 'Completed'; }
    if (row.status === 'Cancelled') { return 'Cancelled'; }
    var now = new Date(OSAS.SEED_NOW).getTime();       /* the portal demo clock */
    var start = new Date(row.startAt).getTime();
    var end = new Date(row.endAt).getTime();
    if (!isNaN(start) && !isNaN(end) && now >= start && now <= end) { return 'Ongoing'; }
    return 'Upcoming';
  }
  function files(row) { return row.attachments || []; }
  function t12(value) {                                 /* 8:05 AM -> 08:05 AM */
    var text = OSAS.fmt.time(value);
    return /^\d:/.test(text) ? '0' + text : text;
  }
  function timeLabel(row) { return t12(row.startAt) + ' - ' + t12(row.endAt); }
  function dateLabel(row) {
    var a = new Date(row.startAt), b = new Date(row.endAt);
    if (isNaN(a.getTime())) { return '—'; }
    if (isNaN(b.getTime()) || a.toDateString() === b.toDateString()) {
      return OSAS.fmt.date(row.startAt);
    }
    var head = OSAS.fmt.shortDate(row.startAt);
    var tail = OSAS.fmt.shortDate(row.endAt);
    if (a.getFullYear() === b.getFullYear()) { head = head.replace(/, \d{4}$/, ''); }
    return head + ' – ' + tail;
  }
  function whenLabel(row) { return OSAS.fmt.shortDate(row.startAt) + ' · ' + timeLabel(row); }
  function pillHtml(row) {
    var label = phase(row);
    return '<span class="se-pill se-pill--' + U.slug(label) + '">' + label + '</span>';
  }
  function filtered() {
    var q = state.q.trim().toLowerCase();
    return rows().filter(function (row) {
      if (state.phase !== 'All' && phase(row) !== state.phase) { return false; }
      if (!q) { return true; }
      return (row.title + ' ' + row.description + ' ' + row.venue + ' ' + row.code + ' ' +
        row.category + ' ' + row.organizer).toLowerCase().indexOf(q) > -1;
    });
  }

  /* ---------------------------------------------------------- list view */
  function chipsHtml() {
    var total = rows().length;
    return PHASES.map(function (p) {
      var on = state.phase === p.key;
      return '<button type="button" class="se-chip' + (on ? ' is-on' : '') +
        '" id="se-chip-' + p.id + '" data-phase="' + p.key + '">' + p.label +
        (p.key === 'All' ? ' (' + total + ')' : '') + '</button>';
    }).join('');
  }
  function cardHtml(row) {
    return '<a class="se-card" href="#/evt/' + U.esc(row.code) + '">' +
      '<div class="se-card__top">' +
        '<span class="se-code">' + U.esc(row.code) + '</span>' + pillHtml(row) +
      '</div>' +
      '<h3 class="se-card__title">' + U.esc(row.title) + '</h3>' +
      '<p class="se-card__text">' + U.esc(row.description) + '</p>' +
      '<div class="se-card__foot">' +
        '<div class="se-card__facts">' +
          '<span class="se-fact">' + OSAS.icons.icon('calendar', 13) +
            '<span>' + whenLabel(row) + '</span></span>' +
          '<span class="se-fact">' + OSAS.icons.icon('pin', 13) +
            '<span>' + U.esc(row.venue) + '</span></span>' +
        '</div>' +
        '<span class="se-card__cta">View Details</span>' +
      '</div>' +
    '</a>';
  }
  function cardsHtml() {
    var list = filtered();
    if (!list.length) {
      return C.emptyState(state.q
        ? 'No event matches &ldquo;' + U.esc(state.q) + '&rdquo;'
        : 'No ' + state.phase.toLowerCase() + ' event is listed right now.');
    }
    return list.map(cardHtml).join('');
  }
  function listHtml() {
    return '<div class="se se--list">' +
      '<div class="se-search">' +
        '<input type="search" class="se-search__input" id="se-q" aria-label="Search events" ' +
          'placeholder="Search title, venue, or code&hellip;" value="' + U.esc(state.q) + '">' +
      '</div>' +
      '<div class="se-chips" id="se-chips">' + chipsHtml() + '</div>' +
      '<div class="se-list" id="se-list">' + cardsHtml() + '</div>' +
    '</div>';
  }

  /* -------------------------------------------------------- detail view */
  function factHtml(icon, label, value, wide) {
    return '<div class="se-fact-box' + (wide ? ' se-fact-box--wide' : '') + '">' +
      '<span class="se-fact-box__ico">' + OSAS.icons.icon(icon, 15) + '</span>' +
      '<div class="se-fact-box__body">' +
        '<span class="se-fact-box__label">' + label + '</span>' +
        '<b>' + U.esc(value) + '</b>' +
      '</div>' +
    '</div>';
  }
  function mediaHtml(list) {
    return '<section class="se-sec">' +
      '<h2 class="se-sec__h">Attached Media &amp; Promo</h2>' +
      list.map(function (f, i) {
        return '<div class="se-media">' +
          '<span class="se-media__ico">' + OSAS.icons.icon('fileText', 17) + '</span>' +
          '<span class="se-media__meta"><b>' + U.esc(f.name) + '</b>' +
            '<small>' + U.esc(f.role || f.size || 'Attached media') + '</small></span>' +
          '<button type="button" class="se-media__dl" id="se-dl-' + i + '" aria-label="Preview ' +
            U.esc(f.name) + '">' + OSAS.icons.icon('download', 14) + '</button>' +
        '</div>';
      }).join('') +
    '</section>';
  }
  function detailHtml(row) {
    return '<div class="se se--detail">' +
      '<div class="se-hero">' +
        '<span class="se-hero__eyebrow">' + U.esc(row.category) + '</span>' +
        '<span class="se-hero__title">' + U.esc(row.title) + '</span>' +
        '<span class="se-hero__org">' + U.esc(row.organizer) + '</span>' +
      '</div>' +
      '<div class="se-head">' +
        '<span class="se-code">' + U.esc(row.code) + '</span>' +
        '<span class="se-open">Open to ' + U.esc(row.audience) + '</span>' + pillHtml(row) +
      '</div>' +
      '<h1 class="se-title">' + U.esc(row.title) + '</h1>' +
      '<div class="se-facts">' +
        factHtml('calendar', 'Event Date', dateLabel(row)) +
        factHtml('clock', 'Time Duration', timeLabel(row)) +
        factHtml('pin', 'Location / Venue', row.venue, true) +
      '</div>' +
      '<section class="se-sec">' +
        '<h2 class="se-sec__h">About the Event &amp; Objectives</h2>' +
        '<p class="se-sec__p">' + U.esc(row.description) + '</p>' +
      '</section>' +
      '<div class="se-kv">' +
        '<span class="se-kv__label">Organized By</span>' +
        '<span class="se-kv__value">' + U.esc(row.organizer) + '</span>' +
      '</div>' +
      (files(row).length ? mediaHtml(files(row)) : '') +
    '</div>';
  }

  /* ---------------------------------------------------------- behaviour */
  function wireChips() {
    PHASES.forEach(function (p) {
      bindOnce($('se-chip-' + p.id), 'click', function () { setPhase(p.key); });
    });
  }
  function setPhase(key) {
    state.phase = key;
    $('se-chips').innerHTML = chipsHtml();
    PHASES.forEach(function (p) {
      var node = $('se-chip-' + p.id);
      if (node) { node.classList.toggle('is-on', p.key === key); }
    });
    wireChips();
    $('se-list').innerHTML = cardsHtml();
  }
  function wireList() {
    bindOnce($('se-q'), 'input', function (event) {
      state.q = event.target.value;
      $('se-list').innerHTML = cardsHtml();
    });
    wireChips();
  }

  function barFor(row) {
    shell.setBar({
      menu: false, bell: false, avatar: false,
      title: '<button type="button" class="se-back" id="se-barback">' +
        OSAS.icons.icon('chevronLeft', 17) + 'Event Details</button>',
      extra: '<button type="button" class="se-share" id="se-share" ' +
        'aria-label="Share this event">' + OSAS.icons.icon('upload', 16) + '</button>'
    });
  }
  function shareEvent() {
    var row = hashRow();
    var url = (window.location.origin || '') + (window.location.pathname || '') +
      (row ? '#/evt/' + row.code : (window.location.hash || ''));
    var copied = false;
    try {
      if (typeof navigator !== 'undefined' && navigator.clipboard && navigator.clipboard.writeText) {
        var result = navigator.clipboard.writeText(url);
        copied = true;
        if (result && typeof result.catch === 'function') { result.catch(function () {}); }
      }
    } catch (err) { copied = false; }
    C.toast(copied ? 'success' : 'info', copied ? 'Event link copied' : 'Share this event',
      (row ? row.title + ' · ' : '') + url);
  }
  function previewMedia(index) {
    var row = hashRow();
    var file = row && files(row)[index];
    if (!file) { return; }
    C.modal({
      title: U.esc(file.name),
      subtitle: U.esc(file.role || 'Attached media') + (file.size ? ' &bull; ' + U.esc(file.size) : ''),
      body: '<div class="se-preview">' +
        '<div class="se-preview__art">' + OSAS.icons.icon('fileText', 26) + '</div>' +
        '<div class="se-preview__title">' + U.esc(row.title) + '</div>' +
        '<div class="se-preview__meta">' + U.esc(dateLabel(row)) + ' &bull; ' + U.esc(row.venue) + '</div>' +
        '<p class="se-preview__note">Promo asset released by ' + U.esc(row.organizer) +
        '. The original file is served through the university media library.</p>' +
      '</div>',
      footer: '<button type="button" class="btn btn--ghost" data-close>Close</button>'
    });
  }
  function wireDetail() {
    var row = hashRow();
    bindOnce($('se-barback'), 'click', goBack);
    bindOnce($('se-share'), 'click', function () { shareEvent(); });
    if (row) {
      files(row).forEach(function (f, i) {
        bindOnce($('se-dl-' + i), 'click', function () { previewMedia(i); });
      });
    }
  }

  /* -------------------------------------------------------- live routing */
  function hashRow() {
    var m = (window.location.hash || '').match(/^#\/evt\/([A-Za-z0-9-]+)$/);
    return m ? byCode(m[1]) : null;
  }
  function bindOnce(node, type, fn) {
    if (!node) { return; }                    /* one live handler per node+event */
    node._bound = node._bound || {};
    if (node._bound[type]) { return; }
    node._bound[type] = true;
    node.addEventListener(type, fn);
  }
  function goBack() { window.location.hash = ''; }
  function scrollTop() { if (window.scrollTo) { window.scrollTo(0, 0); } }

  /* ------------------------------------------------------------ render */
  function renderList() {
    shell.setBar({});
    main.innerHTML = listHtml();
    wireList();
    scrollTop();
  }
  function renderDetail(row) {
    barFor(row);
    main.innerHTML = detailHtml(row);
    wireDetail();
    scrollTop();
  }

  /* ---------------------------------------------------------- routing */
  function route() {
    var match = (window.location.hash || '').match(/^#\/evt\/([A-Za-z0-9-]+)$/);
    if (match) {
      var row = byCode(match[1]);
      if (row) { renderDetail(row); return; }
    }
    renderList();
  }

  window.addEventListener('hashchange', route);
  route();
})();
