/* ==========================================================================
   Student module — Announcements  (student/announcements.html)
   Bulletin list -> announcement detail, hash routed:
     #/ann/ANNC-2026-085            announcement detail
   Only Published bulletins addressed to students are visible; student-shell.js
   provides the navigation, the top bar and the student-accounts-only gate.

   Controls derive their row from the live hash rather than the row they were
   bound with, so a re-mounted view can never act on stale data.
   ========================================================================== */
(function () {
  'use strict';

  var shell = OSAS.studentShell.mount({ page: 'announcements', title: 'Announcements' });
  if (!shell) { return; }

  var U = OSAS.util, C = OSAS.components;
  var main = shell.main;
  var user = shell.user;

  var KEY = 'beacon.osas.student.announcements';   /* acknowledgements + bookmarks */
  var state = { q: '', category: 'All', sort: 'new' };

  var TUNE_SVG = '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" ' +
    'stroke-width="1.7" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/>' +
    '<circle cx="9" cy="7" r="2.3" fill="#fff"/><circle cx="15" cy="12" r="2.3" fill="#fff"/>' +
    '<circle cx="8" cy="17" r="2.3" fill="#fff"/></svg>';
  var SORT_SVG = '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" ' +
    'stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">' +
    '<path d="M7 4.5v15M7 19.5l-3-3M7 19.5l3-3M17 19.5v-15M17 4.5l-3 3M17 4.5l3 3"/></svg>';
  var BOOKMARK_SVG = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" ' +
    'stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
    '<path d="M6 4h12v16l-6-4-6 4V4Z"/></svg>';

  function $(id) { return document.getElementById(id); }

  /* --------------------------------------------------------------- data */
  var STUDENT_AUDIENCES = ['All Student Portals', 'Incoming Freshmen', 'Student Officers', 'Graduating Students'];
  var SORTS = [
    { key: 'new', label: 'Newest first' },
    { key: 'old', label: 'Oldest first' },
    { key: 'views', label: 'Most viewed' }
  ];

  function published() {
    return OSAS.store.all('announcements').filter(function (row) {
      return row.status === 'Published' && STUDENT_AUDIENCES.indexOf(row.audience) > -1;
    });
  }
  function pad3(n) { return String(n).padStart(3, '0'); }
  function annCode(row) {                       /* ANN-2026-025 -> ANNC-2026-085 */
    var n = parseInt(String(row.code).split('-').pop(), 10);
    var year = String(row.code).match(/(\d{4})/);
    return 'ANNC-' + (year ? year[1] : '2026') + '-' + pad3((isNaN(n) ? 0 : n) + 60);
  }
  function byCode(code) {
    return published().filter(function (row) { return annCode(row) === code; })[0] || null;
  }
  function refPrefix(row) { return /^revised\b/i.test(String(row.title)) ? 'REV:' : 'REF:'; }
  function audienceLabel(row) {
    return row.audience === 'All Student Portals' ? 'All Students' : row.audience;
  }
  function authorLabel(row) {
    return String(row.author || '').replace(/\bAdministration\b/, 'Admin') || 'OSAS Admin';
  }
  function dateOf(row) { return row.publishedAt || row.date; }
  function files(row) { return row.attachments || []; }

  /* ------------------------------------------------ storage (per browser) */
  function read() {
    try { return JSON.parse(window.localStorage.getItem(KEY)) || {}; }
    catch (err) { return {}; }
  }
  function write(map) {
    try { window.localStorage.setItem(KEY, JSON.stringify(map)); }
    catch (err) { /* ignore */ }
  }
  function acked(code) { return (read().ack || {})[code]; }
  function marked(code) { return Boolean((read().mark || {})[code]); }

  /* -------------------------------------------------------- live routing */
  function hashRow() {
    var m = (window.location.hash || '').match(/^#\/ann\/([A-Za-z0-9-]+)$/);
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

  /* ---------------------------------------------------------- list view */
  function filtered() {
    var q = state.q.trim().toLowerCase();
    var list = published().filter(function (row) {
      if (state.category !== 'All' && row.category !== state.category) { return false; }
      if (!q) { return true; }
      return (row.title + ' ' + row.summary + ' ' + row.category + ' ' + row.author + ' ' +
        row.audience + ' ' + annCode(row)).toLowerCase().indexOf(q) > -1;
    });
    if (state.sort === 'old') { return U.sortBy(list, 'date', 'asc'); }
    if (state.sort === 'views') { return U.sortBy(list, 'views', 'desc'); }
    return U.sortBy(list, 'date', 'desc');
  }
  function categories() {
    var counts = {};
    published().forEach(function (row) {
      counts[row.category] = (counts[row.category] || 0) + 1;
    });
    return Object.keys(counts).sort(function (a, b) {
      return counts[b] - counts[a] || a.localeCompare(b);
    }).map(function (name) { return { name: name, count: counts[name] }; });
  }
  function catRows() {
    var html = '<button type="button" class="sa-filter__row' + (state.category === 'All' ? ' is-on' : '') +
      '" data-cat="All"><span>All Announcements</span><span class="sa-filter__n">' +
      published().length + '</span></button>';
    categories().forEach(function (c) {
      html += '<button type="button" class="sa-filter__row' + (state.category === c.name ? ' is-on' : '') +
        '" data-cat="' + U.esc(c.name) + '"><span>' + U.esc(c.name) +
        '</span><span class="sa-filter__n">' + c.count + '</span></button>';
    });
    return html;
  }
  function sortRows() {
    return SORTS.map(function (s) {
      var on = state.sort === s.key;
      return '<button type="button" class="sa-filter__row' + (on ? ' is-on' : '') +
        '" data-sort="' + s.key + '"><span>' + s.label + '</span>' +
        (on ? '<span class="sa-filter__n">' + OSAS.icons.icon('check', 13) + '</span>' : '') +
        '</button>';
    }).join('');
  }
  function cardHtml(row) {
    var code = annCode(row);
    var ack = acked(code);
    return '<a class="sa-card" href="#/ann/' + code + '">' +
      '<div class="sa-card__top">' +
        '<span class="sa-ref">' + refPrefix(row) + ' ' + code + '</span>' +
        '<span class="sa-status">' + U.esc(row.status) + '</span>' +
      '</div>' +
      '<h3 class="sa-card__title">' + U.esc(row.title) + '</h3>' +
      '<p class="sa-card__text">' + U.esc(row.summary) + '</p>' +
      '<div class="sa-card__foot">' +
        '<span class="sa-card__meta">' + U.esc(audienceLabel(row)) + ' &bull; ' +
          OSAS.fmt.shortDate(dateOf(row)) + ' &bull; ' + U.esc(authorLabel(row)) +
          (ack ? '<span class="sa-card__ack">' + OSAS.icons.icon('checkCircle', 11) + ' Acknowledged</span>' : '') +
        '</span>' +
        '<span class="sa-card__chev">' + OSAS.icons.icon('chevronRight', 16) + '</span>' +
      '</div>' +
    '</a>';
  }
  function cardsHtml() {
    var rows = filtered();
    if (!rows.length) {
      return C.emptyState(state.q
        ? 'No published announcement matches &ldquo;' + U.esc(state.q) + '&rdquo;'
        : 'No published announcement matches the selected category.');
    }
    return rows.map(cardHtml).join('');
  }
  function listHtml() {
    return '<div class="sa sa--list">' +
      '<div class="sa-tools">' +
        '<div class="sa-search">' +
          '<input type="search" class="sa-search__input" id="sa-q" aria-label="Search announcements" ' +
            'placeholder="Search titles, tags, authors&hellip;" value="' + U.esc(state.q) + '">' +
        '</div>' +
        '<button type="button" class="sa-tool" id="sa-filter-btn" aria-expanded="false" ' +
          'aria-label="Filter by category">' + TUNE_SVG + '</button>' +
        '<button type="button" class="sa-tool" id="sa-sort-btn" aria-expanded="false" ' +
          'aria-label="Sort announcements">' + SORT_SVG + '</button>' +
        '<div class="sa-filter" id="sa-filter" hidden>' +
          '<div class="sa-filter__h">Filter by category</div>' + catRows() +
        '</div>' +
        '<div class="sa-filter" id="sa-sort" hidden>' +
          '<div class="sa-filter__h">Sort by</div>' + sortRows() +
        '</div>' +
      '</div>' +
      '<div class="sa-list" id="sa-list">' + cardsHtml() + '</div>' +
    '</div>';
  }

  /* -------------------------------------------------------- detail view */
  function filesHtml(list) {
    return '<div class="sa-files">' +
      '<div class="sa-files__h">Attached Documents (' + list.length + ')</div>' +
      list.map(function (f, i) {
        return '<div class="sa-file">' +
          '<span class="sa-file__icon">' + OSAS.icons.icon('fileText', 18) + '</span>' +
          '<span class="sa-file__meta"><b>' + U.esc(f.name) + '</b>' +
            '<small>' + U.esc(f.size || 'Document') + ' &bull; Click to download Preview</small></span>' +
          '<button type="button" class="sa-file__dl" id="sa-dl-' + i + '" aria-label="Preview ' +
            U.esc(f.name) + '">' + OSAS.icons.icon('download', 15) + '</button>' +
        '</div>';
      }).join('') +
    '</div>';
  }
  function ackHtml(ack) {
    if (ack) {
      return '<div class="sa-ack__done">' + OSAS.icons.icon('checkCircle', 18) +
        '<div><b>Acknowledged &amp; saved to your calendar</b>' +
        '<span>Recorded ' + OSAS.fmt.dateTime(ack.at) + '</span></div></div>';
    }
    return '<button type="button" class="sa-ack__btn" id="sa-ack">' +
      'Acknowledge &amp; Save to Calendar</button>';
  }
  function detailHtml(row) {
    var ack = acked(annCode(row));
    var list = files(row);
    return '<div class="sa sa--detail">' +
      '<article class="sa-doc">' +
        '<div class="sa-doc__top">' +
          '<span class="sa-ref">' + refPrefix(row) + ' ' + annCode(row) + '</span>' +
          (ack ? '<span class="sa-status sa-status--ok">Acknowledged</span>' : '') +
        '</div>' +
        '<h1 class="sa-doc__title">' + U.esc(row.title) + '</h1>' +
        '<div class="sa-doc__meta">' + U.esc(audienceLabel(row)) + ' &bull; ' +
          OSAS.fmt.shortDate(dateOf(row)) + ' &bull; Published by: ' + U.esc(authorLabel(row)) + '</div>' +
        '<div class="sa-body">' + (row.body || '<p>' + U.esc(row.summary) + '</p>') + '</div>' +
        (list.length ? filesHtml(list) : '') +
        ackHtml(ack) +
      '</article>' +
    '</div>';
  }

  /* ---------------------------------------------------------- behaviour */
  function closePanels() {
    [['sa-filter', 'sa-filter-btn'], ['sa-sort', 'sa-sort-btn']].forEach(function (pair) {
      var panel = $(pair[0]);
      if (!panel || panel.hidden) { return; }
      panel.hidden = true;
      var btn = $(pair[1]);
      if (btn) { btn.classList.remove('is-on'); btn.setAttribute('aria-expanded', 'false'); }
    });
  }
  function togglePanel(panelId, btnId) {
    var panel = $(panelId);
    if (!panel) { return; }
    var willOpen = panel.hidden;
    closePanels();
    panel.hidden = !willOpen;
    var btn = $(btnId);
    if (btn) {
      btn.classList.toggle('is-on', willOpen);
      btn.setAttribute('aria-expanded', String(willOpen));
    }
  }
  function setCategory(key) {
    state.category = key;
    Array.prototype.forEach.call(document.querySelectorAll('[data-cat]'), function (node) {
      node.classList.toggle('is-on', node.getAttribute('data-cat') === key);
    });
    $('sa-list').innerHTML = cardsHtml();
  }
  function setSort(key) {
    state.sort = key;
    Array.prototype.forEach.call(document.querySelectorAll('[data-sort]'), function (node) {
      node.classList.toggle('is-on', node.getAttribute('data-sort') === key);
    });
    $('sa-list').innerHTML = cardsHtml();
  }
  function wireList() {
    bindOnce($('sa-q'), 'input', function (event) {
      state.q = event.target.value;
      $('sa-list').innerHTML = cardsHtml();
    });
    bindOnce($('sa-filter-btn'), 'click', function (event) {
      event.stopPropagation();
      togglePanel('sa-filter', 'sa-filter-btn');
    });
    bindOnce($('sa-sort-btn'), 'click', function (event) {
      event.stopPropagation();
      togglePanel('sa-sort', 'sa-sort-btn');
    });
    Array.prototype.forEach.call(document.querySelectorAll('[data-cat]'), function (node) {
      bindOnce(node, 'click', function () {
        setCategory(node.getAttribute('data-cat'));
        closePanels();
      });
    });
    Array.prototype.forEach.call(document.querySelectorAll('[data-sort]'), function (node) {
      bindOnce(node, 'click', function () {
        setSort(node.getAttribute('data-sort'));
        closePanels();
      });
    });
    bindOnce($('sa-filter'), 'click', function (event) { event.stopPropagation(); });
    bindOnce($('sa-sort'), 'click', function (event) { event.stopPropagation(); });
  }

  function barFor(row) {
    var code = annCode(row);
    shell.setBar({
      menu: false, bell: false, avatar: false,
      title: '<button type="button" class="sa-back" id="sa-barback">' +
        OSAS.icons.icon('chevronLeft', 17) + 'Official Announcement</button>',
      extra: '<button type="button" class="sa-bookmark' + (marked(code) ? ' is-on' : '') +
        '" id="sa-bookmark" aria-pressed="' + (marked(code) ? 'true' : 'false') +
        '" aria-label="Bookmark this announcement">' + BOOKMARK_SVG + '</button>'
    });
  }
  function toggleBookmark() {
    var row = hashRow();
    if (!row) { return; }
    var code = annCode(row), map = read();
    map.mark = map.mark || {};
    var now = !map.mark[code];
    if (now) { map.mark[code] = OSAS.util.now(); } else { delete map.mark[code]; }
    write(map);
    var btn = $('sa-bookmark');
    if (btn) {
      btn.classList.toggle('is-on', now);
      btn.setAttribute('aria-pressed', String(now));
    }
    C.toast('success', now ? 'Announcement bookmarked' : 'Bookmark removed', row.title);
  }
  function acknowledge() {
    var row = hashRow();
    if (!row) { return; }
    var code = annCode(row), map = read();
    map.ack = map.ack || {};
    if (!map.ack[code]) { map.ack[code] = { at: OSAS.util.now() }; }
    write(map);
    OSAS.store.logActivity({
      tone: 'green', icon: 'megaphone',
      title: 'Announcement Acknowledged',
      text: row.title + ' \u2014 acknowledged by ' + shell.fullName(user) + '.'
    });
    C.toast('success', 'Acknowledged & saved to calendar', row.title);
    renderDetail(row);
  }
  function previewFile(index) {
    var row = hashRow();
    var file = row && files(row)[index];
    if (!file) { return; }
    C.modal({
      title: U.esc(file.name),
      subtitle: (file.size ? file.size + ' &bull; ' : '') + 'Attached to ' + annCode(row),
      body: '<div class="sa-preview">' + (row.body || '<p>' + U.esc(row.summary) + '</p>') + '</div>' +
        '<p class="sa-preview__note">Portal preview of the attached document. The original file is ' +
        'released through the university document server.</p>',
      footer: '<button type="button" class="btn btn--ghost" data-close>Close</button>'
    });
  }
  function wireDetail() {
    var row = hashRow();
    bindOnce($('sa-barback'), 'click', goBack);
    bindOnce($('sa-bookmark'), 'click', toggleBookmark);
    bindOnce($('sa-ack'), 'click', function () { acknowledge(); });
    if (row) {
      files(row).forEach(function (f, i) {
        bindOnce($('sa-dl-' + i), 'click', function () { previewFile(i); });
      });
    }
  }

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
    var match = (window.location.hash || '').match(/^#\/ann\/([A-Za-z0-9-]+)$/);
    if (match) {
      var row = byCode(match[1]);
      if (row) { renderDetail(row); return; }
    }
    renderList();
  }

  window.addEventListener('hashchange', route);
  document.addEventListener('click', closePanels);
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') { closePanels(); }
  });
  route();
})();
