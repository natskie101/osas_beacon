/* ==========================================================================
   Student module — Feedback & Support  (student/support.html)
   Ticket list -> create form -> ticket detail, hash routed:
     #/create                       file a new ticket
     #/ticket/TCS-2026-009          ticket detail (conversation + reply)
   Only tickets filed by the signed-in student are ever loaded, so a student
   cannot read another student's thread even by typing the URL directly;
   student-shell.js provides the navigation, the top bar and the
   student-accounts-only gate.

   Controls derive their row from the live hash rather than the row they were
   bound with, so a re-mounted view can never act on stale data.
   ========================================================================== */
(function () {
  'use strict';

  var shell = OSAS.studentShell.mount({ page: 'feedback', title: 'Feedback & Support' });
  if (!shell) { return; }

  var U = OSAS.util, C = OSAS.components;
  var main = shell.main;

  var state = { q: '', tab: 'all', rating: 0, attachment: '' };
  var TABS = [
    { key: 'all', id: 'all', label: 'Support Tickets' },
    { key: 'Open', id: 'open', label: 'Open' },
    { key: 'In Progress', id: 'progress', label: 'In Progress' },
    { key: 'resolved', id: 'resolved', label: 'Resolved' }
  ];
  var RATINGS = [1, 2, 3, 4, 5];

  function $(id) { return document.getElementById(id); }

  /* --------------------------------------------------------------- data */
  function mine(row) {                       /* own tickets only */
    var user = shell.user || {};
    var byEmail = user.email && row.studentEmail &&
      String(row.studentEmail).toLowerCase() === String(user.email).toLowerCase();
    var byId = user.studentId && row.studentId &&
      String(row.studentId) === String(user.studentId);
    return Boolean(byEmail || byId);
  }
  function rows() {
    return U.sortBy(OSAS.store.all('tickets').filter(mine), 'submittedAt', 'desc');
  }
  function byCode(code) {
    return rows().filter(function (row) { return row.code === code; })[0] || null;
  }
  function tabRows() {
    var list = rows();
    if (state.tab === 'all') { return list; }
    if (state.tab === 'resolved') {
      return list.filter(function (row) {
        return row.status === 'Resolved' || row.status === 'Closed';
      });
    }
    return list.filter(function (row) { return row.status === state.tab; });
  }
  function filtered() {
    var q = state.q.trim().toLowerCase();
    if (!q) { return tabRows(); }
    return tabRows().filter(function (row) {
      return (row.code + ' ' + row.subject + ' ' + row.description + ' ' +
        row.category + ' ' + row.status).toLowerCase().indexOf(q) > -1;
    });
  }
  function tabLabel() {
    return (TABS.filter(function (t) { return t.key === state.tab; })[0] || TABS[0]).label;
  }
  function fullName() { return shell.fullName(shell.user); }
  function messages(row) { return row.messages || []; }
  function t12(value) {                      /* 8:41 AM -> 08:41 AM */
    var text = OSAS.fmt.time(value);
    return /^\d:/.test(text) ? '0' + text : text;
  }
  function whenLabel(value) { return OSAS.fmt.shortDate(value) + ' · ' + t12(value); }

  /* ------------------------------------------------------ rating helpers */
  function ratingValue(row) { return Math.round(Number(row.rating) || 0); }
  function ratingLabel(v) {
    if (v >= 4.5) { return 'Excellent'; }
    if (v >= 3.5) { return 'Good'; }
    if (v >= 2.5) { return 'Fair'; }
    if (v >= 1.5) { return 'Poor'; }
    return 'Very Poor';
  }
  function starsHtml(v, cls) {
    var full = Math.round(Number(v) || 0);
    var out = '<span class="' + cls + '">';
    RATINGS.forEach(function (n) {
      out += '<span class="sf-star' + (n <= full ? ' is-on' : '') + '">★</span>';
    });
    return out + '</span>';
  }
  function ratingHtml(row) {
    var v = ratingValue(row);
    if (!v) { return '<span class="sf-rate__none">Not rated yet</span>'; }
    return starsHtml(v, 'sf-stars') +
      '<b class="sf-rate__score">' + v.toFixed(1) + ' / 5.0 (' + ratingLabel(v) + ')</b>';
  }
  function pillHtml(row) { return C.badge(row.status); }

  /* ---------------------------------------------------------- list view */
  function tabsHtml() {
    return TABS.map(function (t) {
      return '<button type="button" class="sf-tab' + (state.tab === t.key ? ' is-on' : '') +
        '" id="sf-tab-' + t.id + '" data-tab="' + t.key + '">' + t.label + '</button>';
    }).join('');
  }
  function cardHtml(row) {
    var rated = ratingValue(row);
    return '<a class="sf-card" href="#/ticket/' + U.esc(row.code) + '">' +
      '<div class="sf-card__top">' +
        '<span class="sf-code">' + U.esc(row.code) + '</span>' + pillHtml(row) +
      '</div>' +
      '<h3 class="sf-card__title">' + U.esc(row.subject) + '</h3>' +
      '<span class="sf-card__cat">' + U.esc(row.category) + '</span>' +
      '<div class="sf-card__foot">' +
        '<div class="sf-card__meta">' +
          '<span class="sf-card__when">' + OSAS.icons.icon('calendar', 12) +
            '<span>' + OSAS.fmt.shortDate(row.submittedAt) + ' · ' + U.esc(row.student) + '</span></span>' +
          '<span class="sf-card__rate">' + starsHtml(rated, 'sf-stars sf-stars--sm') +
            '<b>' + (rated ? rated + '/5' : '—') + '</b></span>' +
        '</div>' +
        '<span class="sf-card__cta">View</span>' +
      '</div>' +
    '</a>';
  }
  function cardsHtml() {
    var list = filtered();
    if (!list.length) {
      if (state.q) {
        return C.emptyState('No ticket matches &ldquo;' + U.esc(state.q) + '&rdquo;');
      }
      if (state.tab !== 'all') {
        return C.emptyState('You have no ' + U.esc(tabLabel()).toLowerCase() + ' ticket right now.');
      }
      return C.emptyState('You have not filed any ticket yet.',
        '<button type="button" class="btn btn--outline btn--sm" id="sf-empty-new">Create New Ticket</button>');
    }
    return list.map(cardHtml).join('');
  }
  function listHtml() {
    return '<div class="sf sf--list">' +
      '<button type="button" class="sf-new" id="sf-new">' +
        '<span class="sf-new__plus">+</span>Create New Ticket</button>' +
      '<div class="sf-search">' +
        '<input type="search" class="sf-search__input" id="sf-q" aria-label="Search tickets" ' +
          'placeholder="Search by ticket ID, subject, or keyword&hellip;" value="' + U.esc(state.q) + '">' +
      '</div>' +
      '<div class="sf-tabs" id="sf-tabs">' + tabsHtml() + '</div>' +
      '<div class="sf-list" id="sf-list">' + cardsHtml() + '</div>' +
    '</div>';
  }

  /* ------------------------------------------------------- create view */
  function createHtml() {
    var cats = (OSAS.store.options() || {}).ticketCategories || [];
    return '<div class="sf sf--create">' +
      '<h1 class="sf-create__h">Create Support Ticket</h1>' +
      '<p class="sf-create__sub">Submit a concern or suggestion to the OSAS Support Center</p>' +
      '<div class="sf-form">' +
        '<div class="sf-field">' +
          '<label class="sf-label" for="sf-category">Category <i>*</i></label>' +
          '<select class="sf-input" id="sf-category">' +
            '<option value="">Select a category</option>' +
            cats.map(function (c) {
              return '<option value="' + U.esc(c) + '">' + U.esc(c) + '</option>';
            }).join('') +
          '</select>' +
        '</div>' +
        '<div class="sf-field">' +
          '<label class="sf-label" for="sf-subject">Subject / Title <i>*</i></label>' +
          '<input class="sf-input" id="sf-subject" type="text" ' +
            'placeholder="e.g., Unable to submit Module 4 quiz">' +
        '</div>' +
        '<div class="sf-field">' +
          '<span class="sf-label">Satisfaction / Evaluation Rating <i>*</i></span>' +
          '<div class="sf-picker" id="sf-picker">' +
            RATINGS.map(function (n) {
              return '<button type="button" class="sf-pick" id="sf-star-' + n + '" ' +
                'data-rating="' + n + '" aria-label="Rate ' + n + ' of 5">&#9733;</button>';
            }).join('') +
          '</div>' +
          '<div class="sf-picker__val" id="sf-rating">0.0 / 5.0 — tap a star to rate</div>' +
        '</div>' +
        '<div class="sf-field">' +
          '<label class="sf-label" for="sf-desc">Detailed Description <i>*</i></label>' +
          '<textarea class="sf-input sf-input--area" id="sf-desc" rows="5" ' +
            'placeholder="Describe your issue or feedback in detail..."></textarea>' +
        '</div>' +
        '<div class="sf-field">' +
          '<span class="sf-label">Attach Screenshot / Document (Optional)</span>' +
          '<label class="sf-drop" for="sf-file" id="sf-drop">' +
            OSAS.icons.icon('upload', 17) +
            '<span class="sf-drop__text">Click to upload a PNG, JPG or PDF up to 10MB</span>' +
            '<span class="sf-drop__file" id="sf-file-name"></span>' +
          '</label>' +
          '<input class="sf-file" type="file" id="sf-file" accept=".png,.jpg,.jpeg,.pdf" ' +
            'aria-label="Attach screenshot or document">' +
        '</div>' +
        '<div id="sf-alert"></div>' +
        '<button type="button" class="sf-submit" id="sf-submit">Submit Ticket</button>' +
        '<button type="button" class="sf-cancel" id="sf-cancel">Cancel</button>' +
      '</div>' +
    '</div>';
  }

  /* ------------------------------------------------------- detail view */
  function factHtml(label, value) {
    return '<div class="sf-fact-box">' +
      '<span class="sf-fact-box__label">' + label + '</span>' +
      '<b>' + U.esc(value) + '</b>' +
    '</div>';
  }
  function fileRowHtml(row) {
    return '<section class="sf-sec">' +
      '<h2 class="sf-sec__h">Attached File</h2>' +
      '<div class="sf-media">' +
        '<span class="sf-media__ico">' + OSAS.icons.icon('fileText', 16) + '</span>' +
        '<span class="sf-media__meta"><b>' + U.esc(row.attachment) + '</b>' +
          '<small>Attached by ' + U.esc(row.student || fullName()) + ' on ' +
            OSAS.fmt.shortDate(row.submittedAt) + '</small></span>' +
      '</div>' +
    '</section>';
  }
  function threadHtml(row) {
    var msgs = messages(row);
    if (!msgs.length) {
      return C.emptyState('No response has been posted yet.');
    }
    return '<div class="sf-thread">' + msgs.map(function (m) {
      var staff = m.role !== 'Student';
      return '<div class="sf-msg' + (staff ? ' sf-msg--staff' : '') + '">' +
        '<div class="sf-msg__head">' +
          '<b>' + U.esc(m.author) + '</b>' +
          '<span class="sf-msg__time">' + whenLabel(m.at) + '</span>' +
        '</div>' +
        '<p class="sf-msg__body">' + U.esc(m.body) + '</p>' +
      '</div>';
    }).join('') + '</div>';
  }
  function detailHtml(row) {
    return '<div class="sf sf--detail">' +
      '<div class="sf-ticket">' +
        '<div class="sf-ticket__top">' +
          '<span class="sf-code">' + U.esc(row.code) + '</span>' + pillHtml(row) +
        '</div>' +
        '<h1 class="sf-ticket__title">' + U.esc(row.subject) + '</h1>' +
        '<span class="sf-ticket__cat">' + U.esc(row.category) + '</span>' +
        '<div class="sf-facts">' +
          factHtml('Date Submitted', OSAS.fmt.shortDate(row.submittedAt)) +
          factHtml('Last Updated', whenLabel(row.updatedAt)) +
        '</div>' +
        '<div class="sf-rate">' +
          '<span class="sf-label">Satisfaction / Evaluation Rating</span>' +
          '<div class="sf-rate__row">' + ratingHtml(row) + '</div>' +
        '</div>' +
        '<section class="sf-sec">' +
          '<h2 class="sf-sec__h">Issue Description</h2>' +
          '<p class="sf-sec__p">' + U.esc(row.description) + '</p>' +
        '</section>' +
        (row.attachment ? fileRowHtml(row) : '') +
        '<section class="sf-sec">' +
          '<h2 class="sf-sec__h">Support Responses &amp; Updates</h2>' + threadHtml(row) +
        '</section>' +
        '<section class="sf-sec">' +
          '<h2 class="sf-sec__h">Add Reply / Follow-up</h2>' +
          '<textarea class="sf-input sf-input--area" id="sf-reply" rows="4" ' +
            'placeholder="Type your message or additional details here..."></textarea>' +
          '<div id="sf-reply-alert"></div>' +
          '<button type="button" class="sf-submit" id="sf-send">Send Reply</button>' +
        '</section>' +
      '</div>' +
    '</div>';
  }

  /* ---------------------------------------------------------- behaviour */
  function wireEmptyNew() {
    bindOnce($('sf-empty-new'), 'click', function () { goCreate(); });
  }
  function setTab(key) {
    state.tab = key;
    TABS.forEach(function (t) {
      var node = $('sf-tab-' + t.id);
      if (node) { node.classList.toggle('is-on', t.key === key); }
    });
    $('sf-list').innerHTML = cardsHtml();
    wireEmptyNew();
  }
  function wireList() {
    bindOnce($('sf-new'), 'click', function () { goCreate(); });
    bindOnce($('sf-q'), 'input', function (event) {
      state.q = event.target.value;
      $('sf-list').innerHTML = cardsHtml();
      wireEmptyNew();
    });
    TABS.forEach(function (t) {
      bindOnce($('sf-tab-' + t.id), 'click', function () { setTab(t.key); });
    });
    wireEmptyNew();
  }

  function setRating(n) {
    state.rating = n;
    RATINGS.forEach(function (i) {
      var star = $('sf-star-' + i);
      if (star) { star.classList.toggle('is-on', i <= n); }
    });
    var line = $('sf-rating');
    if (line) {
      line.innerHTML = n
        ? n.toFixed(1) + ' / 5.0 (' + ratingLabel(n) + ')'
        : '0.0 / 5.0 — tap a star to rate';
    }
  }
  function formError(id, title, text) {
    $('sf-alert').innerHTML = C.alert('warning', title, text);
    var node = $(id);
    if (node && node.focus) { node.focus(); }
  }
  function submitTicket() {
    var category = String(($('sf-category') || {}).value || '').trim();
    var subject = String(($('sf-subject') || {}).value || '').trim();
    var description = String(($('sf-desc') || {}).value || '').trim();
    if (!category) {
      formError('sf-category', 'Category required',
        'Choose the category that best matches your concern.');
      return;
    }
    if (!subject) {
      formError('sf-subject', 'Subject required',
        'Give your ticket a short and specific title.');
      return;
    }
    if (!description) {
      formError('sf-desc', 'Description required',
        'Describe the issue or suggestion so the support team can act on it.');
      return;
    }
    if (!state.rating) {
      formError('sf-picker', 'Rating required',
        'Tap the stars to record your satisfaction rating.');
      return;
    }
    var stamp = OSAS.util.now();
    var code = OSAS.store.nextId('tickets', 'TCS-2026', 3);
    var record = {
      id: code, code: code,
      student: fullName(),
      studentEmail: (shell.user || {}).email || '',
      studentId: (shell.user || {}).studentId || '',
      program: (shell.user || {}).program || '',
      category: category, subject: subject, description: description,
      priority: 'Medium', status: 'Open',
      submittedAt: stamp, updatedAt: stamp, assignedTo: '',
      channel: 'Portal Notification',
      rating: state.rating,
      attachment: state.attachment || '',
      messages: [
        { author: fullName(), role: 'Student', at: stamp, body: description },
        { author: 'OSAS Support (Auto-Response)', role: 'OSAS Support', at: stamp,
          body: 'Ticket received by OSAS Support. Assigned to the ' + category +
            ' desk. Estimated resolution time: within 24 hours.' }
      ]
    };
    OSAS.store.insert('tickets', record);
    OSAS.store.logActivity({
      tone: 'blue', icon: 'headset', title: 'Support Ticket Filed',
      text: fullName() + ' filed ' + code + ' — "' + subject + '" (' + category + ')',
      at: stamp
    });
    C.toast('success', 'Ticket submitted', code + ' — ' + subject);
    state.rating = 0;
    state.attachment = '';
    window.location.hash = '#/ticket/' + code;
  }
  function wireCreate() {
    bindOnce($('sf-barback'), 'click', function () { goList(); });
    bindOnce($('sf-cancel'), 'click', function () { goList(); });
    bindOnce($('sf-submit'), 'click', function () { submitTicket(); });
    RATINGS.forEach(function (n) {
      bindOnce($('sf-star-' + n), 'click', function () { setRating(n); });
    });
    bindOnce($('sf-file'), 'change', function (event) {
      var file = event.target && event.target.files && event.target.files[0];
      state.attachment = file ? file.name : '';
      var label = $('sf-file-name');
      if (label) { label.textContent = state.attachment || ''; }
    });
  }

  function sendReply() {
    var row = hashRow();
    var box = $('sf-reply');
    if (!row || !box) { return; }
    var text = String(box.value || '').trim();
    if (!text) {
      $('sf-reply-alert').innerHTML = C.alert('warning', 'Nothing to send',
        'Type your message or additional details before sending it.');
      return;
    }
    var stamp = OSAS.util.now();
    OSAS.store.update('tickets', row.id, {
      messages: (row.messages || []).concat([{
        author: fullName(), role: 'Student', at: stamp, body: text
      }]),
      updatedAt: stamp
    });
    OSAS.store.logActivity({
      tone: 'blue', icon: 'send', title: 'Ticket Follow-up Sent',
      text: fullName() + ' replied to ' + row.code + ' — "' + row.subject + '"',
      at: stamp
    });
    C.toast('success', 'Reply sent', row.code + ' — the support team will respond here.');
    renderDetail(byCode(row.code) || row);
  }
  function wireDetail() {
    bindOnce($('sf-barback'), 'click', function () { goBack(); });
    bindOnce($('sf-send'), 'click', function () { sendReply(); });
  }

  /* --------------------------------------------------------- bar + nav */
  function barBack(label) {
    shell.setBar({
      menu: false, bell: false, avatar: false,
      title: '<button type="button" class="sf-back" id="sf-barback">' +
        OSAS.icons.icon('chevronLeft', 17) + label + '</button>',
      extra: ''
    });
  }
  function goList() { window.location.hash = ''; }
  function goCreate() { window.location.hash = '#/create'; }
  function goBack() { window.location.hash = ''; }

  /* -------------------------------------------------------- live routing */
  function hashRow() {
    var m = (window.location.hash || '').match(/^#\/ticket\/([A-Za-z0-9-]+)$/);
    return m ? byCode(m[1]) : null;
  }
  function bindOnce(node, type, fn) {
    if (!node) { return; }                  /* one live handler per node+event */
    node._bound = node._bound || {};
    if (node._bound[type]) { return; }
    node._bound[type] = true;
    node.addEventListener(type, fn);
  }
  function scrollTop() { if (window.scrollTo) { window.scrollTo(0, 0); } }

  /* ------------------------------------------------------------ render */
  function renderList() {
    shell.setBar({});
    main.innerHTML = listHtml();
    wireList();
    scrollTop();
  }
  function renderCreate() {
    barBack('Feedback & Support');
    state.rating = 0;
    state.attachment = '';
    main.innerHTML = createHtml();
    wireCreate();
    scrollTop();
  }
  function renderDetail(row) {
    barBack('Ticket Details');
    main.innerHTML = detailHtml(row);
    wireDetail();
    scrollTop();
  }

  /* ----------------------------------------------------------- routing */
  function route() {
    var hash = window.location.hash || '';
    if (hash === '#/create') { renderCreate(); return; }
    var match = hash.match(/^#\/ticket\/([A-Za-z0-9-]+)$/);
    if (match) {
      var row = byCode(match[1]);   /* null for tickets that are not yours */
      if (row) { renderDetail(row); return; }
    }
    renderList();
  }

  window.addEventListener('hashchange', route);
  route();
})();
