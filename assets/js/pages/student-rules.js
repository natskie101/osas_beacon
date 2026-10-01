/* ==========================================================================
   Student module — Rules and Regulation  (student/rules.html)
   Searchable rule list + policy detail view (hash routed, e.g. #/rule/RULE-118).
   The shell, the student-only access gate and the navigation live in
   student-shell.js; only Published regulations are visible to students.
   ========================================================================== */
(function () {
  'use strict';

  var shell = OSAS.studentShell.mount({ page: 'rules', title: 'Rules and Regulation' });
  if (!shell) { return; }

  var U = OSAS.util, C = OSAS.components;
  var main = shell.main;
  var user = shell.user;

  /* bookmarks + acknowledgements, kept per browser (reset with the demo data) */
  var STORE_KEY = 'beacon.osas.student.rules';
  var state = { q: '', category: 'All' };

  var BOOKMARK_SVG = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" ' +
    'stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
    '<path d="M6 4h12v16l-6-4-6 4V4Z"/></svg>';

  function $(id) { return document.getElementById(id); }

  /* --------------------------------------------------------------- data */
  function published() {
    return OSAS.store.all('regulations').filter(function (r) { return r.status === 'Published'; });
  }

  function ruleCode(reg) {
    var n = parseInt(String(reg.code).split('-').pop(), 10);
    return 'RULE-' + (100 + (isNaN(n) ? 0 : n));
  }

  function authority(reg) {
    return U.truncate(String(reg.approvalBody || 'OSAS Administration').split(' — ')[0], 34);
  }

  function categories() {
    var counts = {};
    published().forEach(function (r) { counts[r.category] = (counts[r.category] || 0) + 1; });
    return Object.keys(counts).sort(function (a, b) {
      return counts[b] - counts[a] || a.localeCompare(b);
    }).map(function (name) { return { name: name, count: counts[name] }; });
  }

  function filtered() {
    var q = state.q.trim().toLowerCase();
    return U.sortBy(published(), 'updatedAt', 'desc').filter(function (r) {
      if (state.category !== 'All' && r.category !== state.category) { return false; }
      if (!q) { return true; }
      return (r.title + ' ' + r.summary + ' ' + r.category + ' ' + ruleCode(r))
        .toLowerCase().indexOf(q) > -1;
    });
  }

  /* ------------------------------------- bookmarks / acknowledgements */
  function readMap() {
    try { return JSON.parse(window.localStorage.getItem(STORE_KEY)) || {}; }
    catch (err) { return {}; }
  }
  function writeMap(map) {
    try { window.localStorage.setItem(STORE_KEY, JSON.stringify(map)); }
    catch (err) { /* ignore */ }
  }

  /* --------------------------------------------------------- list view */
  function chip(key, label) {
    return '<button type="button" class="sr-tab' + (state.category === key ? ' is-on' : '') +
      '" data-cat="' + U.esc(key) + '">' + U.esc(label) + '</button>';
  }

  function tabsHtml() {
    var html = chip('All', 'All Rules (' + published().length + ')');
    categories().forEach(function (c) { html += chip(c.name, c.name + ' (' + c.count + ')'); });
    return html;
  }

  function filterRows() {
    var html = '<button type="button" class="sr-filter__row' + (state.category === 'All' ? ' is-on' : '') +
      '" data-cat="All"><span>All Rules</span><span class="sr-filter__n">' + published().length + '</span></button>';
    categories().forEach(function (c) {
      html += '<button type="button" class="sr-filter__row' + (state.category === c.name ? ' is-on' : '') +
        '" data-cat="' + U.esc(c.name) + '"><span>' + U.esc(c.name) +
        '</span><span class="sr-filter__n">' + c.count + '</span></button>';
    });
    return html;
  }

  function cardHtml(reg) {
    return '<a class="sr-card" href="#/rule/' + ruleCode(reg) + '">' +
      '<span class="sr-card__bar"></span>' +
      '<div class="sr-card__body">' +
        '<div class="sr-card__pills">' +
          '<span class="sr-pill sr-pill--code">' + ruleCode(reg) + '</span>' +
          '<span class="sr-pill sr-pill--cat">' + U.esc(reg.category) + '</span>' +
        '</div>' +
        '<div class="sr-card__title">' + U.esc(reg.title) + '</div>' +
        '<p class="sr-card__text">' + U.esc(reg.summary) + '</p>' +
        '<div class="sr-card__meta">' + U.esc(authority(reg)) + ' · Revised: ' +
          OSAS.fmt.shortDate(reg.updatedAt) + '</div>' +
      '</div>' +
      '<span class="sr-card__chev">' + OSAS.icons.icon('chevronRight', 16) + '</span>' +
    '</a>';
  }

  function cardsHtml() {
    var rows = filtered();
    if (!rows.length) {
      return C.emptyState(state.q
        ? 'No published rule matches &ldquo;' + U.esc(state.q) + '&rdquo;.'
        : 'No published rule matches the selected category.');
    }
    return rows.map(cardHtml).join('');
  }

  function listHtml() {
    return '<div class="sr sr--list">' +
      '<div class="sr-tools">' +
        '<div class="sr-search">' +
          '<input type="search" class="sr-search__input" id="sr-q" aria-label="Search rules" ' +
            'placeholder="Search by rule title or category&hellip;" value="' + U.esc(state.q) + '">' +
          '<button type="button" class="sr-search__btn" id="sr-filter-btn" aria-expanded="false" ' +
            'aria-label="Filter by category">' + OSAS.icons.icon('filter', 15) + '</button>' +
        '</div>' +
        '<div class="sr-filter" id="sr-filter" hidden>' +
          '<div class="sr-filter__h">Filter by category</div>' + filterRows() +
        '</div>' +
      '</div>' +
      '<div class="sr-tabs">' + tabsHtml() + '</div>' +
      '<div class="sr-list" id="sr-list">' + cardsHtml() + '</div>' +
    '</div>';
  }

  /* -------------------------------------------------------- detail view */
  function sections(html) {
    var out = [], re = /<h4>([\s\S]*?)<\/h4>/g, match, cursor = 0, current = null;
    while ((match = re.exec(html))) {
      if (current && match.index > cursor) { current.html += html.slice(cursor, match.index); }
      current = { heading: match[1], html: '' };
      out.push(current);
      cursor = match.index + match[0].length;
    }
    if (current) { current.html += html.slice(cursor); }
    if (!out.length) { out.push({ heading: '', html: html || '' }); }
    return out;
  }

  function sanctionsHtml(reg) {
    var tiers = reg.sanctions || [];
    if (!tiers.length) { return ''; }
    return '<div class="sr-art">' +
      '<div class="sr-art__h">Article V &mdash; Disciplinary Sanctions</div>' +
      '<div class="sr-sanctions">' + tiers.map(function (t) {
        return '<div class="sr-sanction"><b>' + U.esc(t.tier) + ':</b> ' + U.esc(t.text) + '</div>';
      }).join('') + '</div></div>';
  }

  /* detail screen replaces the bar title with "‹ Policy Details" + bookmark */
  function barFor(reg) {
    var marked = Boolean((readMap().mark || {})[reg.id]);
    shell.setBar({
      menu: false, bell: false, avatar: false,
      title: '<button type="button" class="sr-back" id="sr-back">' +
        OSAS.icons.icon('chevronLeft', 17) + 'Policy Details</button>',
      extra: '<button type="button" class="sr-bookmark' + (marked ? ' is-on' : '') +
        '" id="sr-bookmark" aria-pressed="' + (marked ? 'true' : 'false') +
        '" aria-label="Bookmark this policy">' + BOOKMARK_SVG + '</button>'
    });
  }

  function detailHtml(reg) {
    var map = readMap();
    var acked = (map.ack || {})[reg.id];

    return '<div class="sr sr--detail">' +
      '<div class="sr-pills">' +
        '<span class="sr-pill sr-pill--code">' + ruleCode(reg) + '</span>' +
        '<span class="sr-pill sr-pill--status">In Force</span>' +
        (acked ? '<span class="sr-pill sr-pill--ack">Acknowledged</span>' : '') +
      '</div>' +
      '<h1 class="sr-title">' + U.esc(reg.title) + '</h1>' +
      '<div class="sr-meta">' +
        '<div class="sr-meta__row"><span>Category:</span> <span class="sr-meta__v">' +
          U.esc(reg.category) + '</span></div>' +
        '<div class="sr-meta__row"><span>Last Revised:</span> <span class="sr-meta__v">' +
          OSAS.fmt.shortDate(reg.updatedAt) + '</span> &middot; By ' + U.esc(reg.approvalBody) + '</div>' +
        '<div class="sr-meta__row"><span>Effective:</span> <span class="sr-meta__v">' +
          U.esc(reg.effectiveDate) + '</span></div>' +
      '</div>' +
      sections(reg.body).map(function (s) {
        return '<div class="sr-art">' +
          (s.heading ? '<div class="sr-art__h">' + U.esc(s.heading) + '</div>' : '') +
          '<div class="sr-art__body">' + s.html + '</div></div>';
      }).join('') +
      sanctionsHtml(reg) +
      '<div class="sr-ack">' +
        (acked
          ? '<button type="button" class="sr-ack__btn is-done" disabled>' +
              OSAS.icons.icon('checkCircle', 15) + '&nbsp;&nbsp;Policy acknowledged</button>' +
            '<div class="sr-ack__note">Acknowledged on ' + OSAS.fmt.dateTime(acked) + '</div>'
          : '<button type="button" class="sr-ack__btn" id="sr-ack">I acknowledge this policy</button>') +
      '</div>' +
    '</div>';
  }

  /* --------------------------------------------------------- behaviour */
  function setCategory(key) {
    state.category = key;
    Array.prototype.forEach.call(document.querySelectorAll('[data-cat]'), function (node) {
      node.classList.toggle('is-on', node.getAttribute('data-cat') === key);
    });
    $('sr-list').innerHTML = cardsHtml();
  }

  function closeFilter() {
    var panel = $('sr-filter');
    if (!panel) { return; }
    panel.hidden = true;
    $('sr-filter-btn').classList.remove('is-on');
    $('sr-filter-btn').setAttribute('aria-expanded', 'false');
  }

  function wireList() {
    $('sr-q').addEventListener('input', function (event) {
      state.q = event.target.value;
      $('sr-list').innerHTML = cardsHtml();
    });
    $('sr-filter-btn').addEventListener('click', function (event) {
      event.stopPropagation();
      var panel = $('sr-filter');
      panel.hidden = !panel.hidden;
      this.classList.toggle('is-on', !panel.hidden);
      this.setAttribute('aria-expanded', String(!panel.hidden));
    });
    document.querySelectorAll('[data-cat]').forEach(function (node) {
      node.addEventListener('click', function () {
        setCategory(node.getAttribute('data-cat'));
        closeFilter();
      });
    });
    $('sr-filter').addEventListener('click', function (event) { event.stopPropagation(); });
  }

  function toggleBookmark(reg, button) {
    var map = readMap();
    map.mark = map.mark || {};
    var now = !map.mark[reg.id];
    if (now) { map.mark[reg.id] = OSAS.util.now(); } else { delete map.mark[reg.id]; }
    writeMap(map);

    button.classList.toggle('is-on', now);
    button.setAttribute('aria-pressed', String(now));
    C.toast('success', now ? 'Policy bookmarked' : 'Bookmark removed', reg.title);
  }

  function acknowledge(reg) {
    var map = readMap();
    if ((map.ack || {})[reg.id]) { return; }
    map.ack = map.ack || {};
    map.ack[reg.id] = OSAS.util.now();
    writeMap(map);

    OSAS.store.logActivity({
      tone: 'green', icon: 'checkCircle', title: 'Policy Acknowledged',
      text: reg.title + ' was acknowledged by ' + shell.fullName(user) + '.'
    });
    C.toast('success', 'Policy acknowledged', reg.title);
    renderDetail(reg);
  }

  function wireDetail(reg) {
    $('sr-back').addEventListener('click', function () { window.location.hash = ''; });
    $('sr-bookmark').addEventListener('click', function () { toggleBookmark(reg, this); });
    var ack = $('sr-ack');
    if (ack) { ack.addEventListener('click', function () { acknowledge(reg); }); }
  }

  /* ---------------------------------------------------------- routing */
  function scrollTop() { if (window.scrollTo) { window.scrollTo(0, 0); } }

  function renderList() {
    shell.setBar({});                       /* restore title + bell + avatar */
    main.innerHTML = listHtml();
    wireList();
    scrollTop();
  }

  function renderDetail(reg) {
    main.innerHTML = detailHtml(reg);
    barFor(reg);
    wireDetail(reg);
    scrollTop();
  }

  function route() {
    var match = (window.location.hash || '').match(/^#\/rule\/(.+)$/);
    if (match) {
      var reg = published().filter(function (r) { return ruleCode(r) === match[1]; })[0];
      if (reg) { renderDetail(reg); return; }
    }
    renderList();
  }

  window.addEventListener('hashchange', route);

  /* page-level listeners: bound once, no-ops when the list is not mounted */
  document.addEventListener('click', closeFilter);
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') { closeFilter(); }
  });

  route();
})();
