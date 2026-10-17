/* ==========================================================================
   Module 7 — Feedback & Student Support Core
   ========================================================================== */
(function () {
  'use strict';
  var C = OSAS.components, U = OSAS.util;
  var shell = OSAS.shell.mount();
  if (!shell) { return; }
  var content = shell.content;
  var OPTIONS = OSAS.store.options();

  var state = { search: '', category: 'All', status: 'All', tab: 'tickets', page: 1 };

  function filtered() {
    var rows = OSAS.store.all('tickets').slice();
    rows = U.search(rows, state.search, ['code', 'student', 'studentEmail', 'category', 'subject', 'description']);
    rows = U.byStatus(rows, state.status);
    if (state.category !== 'All') {
      rows = rows.filter(function (r) { return r.category === state.category; });
    }
    if (state.tab === 'resolved') { rows = rows.filter(function (r) { return r.status === 'Resolved' || r.status === 'Closed'; }); }
    return U.sortBy(rows, 'submittedAt', 'desc');
  }

  /* ------------------------------------------------------------- toolbar */
  function toolbar() {
    return '<div class="tkt-toolbar">' +
      '<div class="tkt-toolbar__left">' +
      '<div class="panel-title">Inquiry &amp; Complaint Directory</div>' +
      '<div class="tkt-sub">Manage student inquiries, complaints, and feedback, and monitor the OSAS service response performance.</div>' +
      '</div>' +
      '<div class="tkt-toolbar__right">' +
      '<span class="input-group tkt-search"><span class="input-group__ico">' + OSAS.icons.icon('search', 14) + '</span>' +
      '<input class="input" id="tkt-search" placeholder="Search ticket #, student, topic…" value="' + U.esc(state.search) + '"></span>' +
      '<button type="button" class="btn btn--outline btn--sm" id="tkt-filter-btn">' + OSAS.icons.icon('filter', 12) + ' Filter Status</button>' +
      '</div></div>';
  }

  function filterBar() {
    return '<div class="tkt-filters" id="tkt-filters" style="display:none">' +
      '<div class="field"><label for="tkt-category">Concern Category</label>' +
      '<select class="input" id="tkt-category">' +
      ['All'].concat(OPTIONS.ticketCategories).map(function (cat) {
        return '<option value="' + U.esc(cat) + '"' + (cat === state.category ? ' selected' : '') + '>' + U.esc(cat) + '</option>';
      }).join('') + '</select></div>' +
      '<div class="field"><label for="tkt-status">Status</label>' +
      '<select class="input" id="tkt-status">' +
      ['All', 'Open', 'In Progress', 'Resolved', 'Closed'].map(function (s) {
        return '<option value="' + U.esc(s) + '"' + (s === state.status ? ' selected' : '') + '>' + U.esc(s) + '</option>';
      }).join('') + '</select></div>' +
      '</div>';
  }

  function tabStrip() {
    var tabs = [
      { key: 'tickets', label: 'Support Tickets' },
      { key: 'feedback', label: 'Module Feedback' },
      { key: 'survey', label: 'Orientation Survey' },
      { key: 'resolved', label: 'Resolved / Archived' }
    ];
    return '<div class="toolbar"><div class="tabs">' + tabs.map(function (tab) {
      return '<button type="button" class="tab' + (state.tab === tab.key ? ' tab--on' : '') +
        '" data-action="tab" data-id="' + tab.key + '">' + tab.label + '</button>';
    }).join('') + '</div></div>';
  }

  /* ------------------------------------------------------------ table */
  function starRating(rating) {
    var full = Math.round(rating);
    var stars = '';
    for (var i = 1; i <= 5; i++) {
      stars += '<span class="tkt-star' + (i <= full ? ' tkt-star--on' : '') + '">★</span>';
    }
    return stars;
  }

  function table(rows) {
    var page = U.paginate(rows, state.page, OSAS.CONFIG.pageSize);
    var columns = [
      {
        label: 'Ticket # &amp; Student',
        render: function (row) {
          return '<div class="tkt-code">' + U.esc(row.code) + '</div>' +
            '<div class="tkt-student">' + U.esc(row.student) + '</div>' +
            '<div class="tkt-email">' + U.esc(row.studentEmail) + '</div>';
        }
      },
      {
        label: 'Category &amp; Subject',
        render: function (row) {
          return '<div class="tkt-category">' + U.esc(row.category) + '</div>' +
            '<div class="tkt-subject">' + U.esc(U.truncate(row.subject, 80)) + '</div>';
        }
      },
      {
        label: 'Rating / Evaluation',
        render: function (row) {
          var rating = row.rating || 0;
          var label = rating >= 4.5 ? '(Excellent)' : rating >= 3.5 ? '(Good)' : rating >= 2.5 ? '(Neutral)' : '(Issue Flag)';
          return '<div class="tkt-rating">' + starRating(rating) + '</div>' +
            '<div class="tkt-rating-label">' + rating.toFixed(1) + ' / 5.0 ' + label + '</div>';
        }
      },
      {
        label: 'Submitted Date',
        render: function (row) {
          return '<div class="tkt-date">' + OSAS.fmt.shortDate(row.submittedAt) + '</div>' +
            '<div class="tkt-time">' + OSAS.fmt.time(row.submittedAt) + '</div>';
        }
      },
      { label: 'Status', render: function (row) { return C.badge(row.status); } },
      {
        label: 'Actions',
        align: 'right',
        render: function (row) {
          var pending = row.status === 'Open' || row.status === 'In Progress';
          return '<div class="cell-actions">' +
            (pending ? '<button type="button" class="btn btn--brand btn--sm" data-action="respond" data-id="' +
              U.esc(row.id) + '">Respond</button>' : '') +
            '<button type="button" class="btn btn--ghost btn--sm" data-action="view" data-id="' +
            U.esc(row.id) + '">View</button>' +
            '<button type="button" class="btn btn--ghost btn--sm" data-action="more" data-id="' +
            U.esc(row.id) + '">···</button>' +
            '</div>';
        }
      }
    ];
    var footer = C.pager(page, 'Showing ' + page.from + '–' + page.to + ' of ' + page.total + ' feedback & support entries');
    return C.dataTable({ columns: columns, rows: page.items, footer: footer });
  }

  /* ------------------------------------------------------------ actions */
  function resolve(id) {
    var ticket = OSAS.store.find('tickets', id);
    if (!ticket) { return; }
    C.confirm({
      title: 'Resolve ticket #' + ticket.code + '?',
      subtitle: U.esc(ticket.student) + ' · ' + U.esc(ticket.category),
      message: 'The ticket will be closed as resolved and the student will be notified through the portal and university email.',
      confirmLabel: 'Mark as resolved',
      confirmClass: 'btn--success',
      onConfirm: function () {
        var session = OSAS.auth.current();
        var staff = session ? session.user.firstName + ' ' + session.user.lastName : 'OSAS Support';
        ticket.status = 'Resolved';
        ticket.updatedAt = U.now();
        ticket.messages.push({
          author: staff, role: 'OSAS Support', at: U.now(),
          body: 'Your concern has been addressed and verified. We are closing this ticket as resolved — please reopen it if the issue recurs.'
        });
        OSAS.store.persist();
        OSAS.store.logActivity({
          tone: 'green', icon: 'check', title: 'New Ticket Resolved #' + ticket.code,
          text: ticket.student + ' · ' + ticket.category + ' marked as resolved by OSAS Support.'
        });
        C.toast('success', 'Ticket resolved', ticket.code + ' is now closed.');
        render();
      }
    });
  }

  function exportCsv() {
    var rows = filtered();
    U.exportCsv('beacon-support-tickets.csv', [
      { label: 'Ticket Code', value: 'code' },
      { label: 'Student', value: 'student' },
      { label: 'Email', value: 'studentEmail' },
      { label: 'Student ID', value: 'studentId' },
      { label: 'Category', value: 'category' },
      { label: 'Subject', value: 'subject' },
      { label: 'Priority', value: 'priority' },
      { label: 'Status', value: 'status' },
      { label: 'Assigned To', value: 'assignedTo' },
      { label: 'Submitted', value: function (r) { return OSAS.fmt.dateTime(r.submittedAt); } }
    ], rows);
    C.toast('success', 'Tickets exported', rows.length + ' records written to CSV.');
  }

  function render() {
    content.innerHTML = OSAS.shell.pageHead({
      title: 'Feedback & Student Support Core',
      subtitle: 'Manage student inquiries, complaints, and feedback, and monitor the OSAS service response performance.',
      actions: '<button type="button" class="btn btn--brand" data-action="new">' +
        OSAS.icons.icon('plus', 13) + ' Create Support Ticket</button>'
    }) + toolbar() + filterBar() + tabStrip() + table(filtered());
  }

  /* ---------------- one-time delegated bindings (survive re-renders) ----- */
  var FILTER_KEYS = { 'tkt-category': 'category', 'tkt-status': 'status' };

  content.addEventListener('input', function (event) {
    if (event.target.id !== 'tkt-search') { return; }
    state.search = event.target.value;
    state.page = 1;
    render();
    var box = document.getElementById('tkt-search');
    if (box) {
      box.focus();
      box.setSelectionRange(box.value.length, box.value.length);
    }
  });

  content.addEventListener('change', function (event) {
    var key = FILTER_KEYS[event.target.id];
    if (!key) { return; }
    state[key] = event.target.value;
    state.page = 1;
    render();
  });

  C.bindPager(content, function (page) {
    state.page = page;
    render();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });

  content.addEventListener('click', function (event) {
    var trigger = event.target.closest ? event.target.closest('[data-action]') : null;
    if (!trigger) { return; }
    var action = trigger.getAttribute('data-action');
    var id = trigger.getAttribute('data-id');

    if (action === 'tab') { state.tab = id; state.page = 1; render(); return; }
    if (action === 'new') {
      OSAS.forms.ticketCreate({ onSaved: function () { render(); } });
      return;
    }
    if (action === 'export') { exportCsv(); return; }
    if (action === 'view') {
      var ticket = OSAS.store.find('tickets', id);
      if (ticket) { OSAS.forms.ticketView({ ticket: ticket, onUpdated: function () { render(); } }); }
      return;
    }
    if (action === 'respond') {
      var respondTicket = OSAS.store.find('tickets', id);
      if (respondTicket) { OSAS.forms.ticketView({ ticket: respondTicket, onUpdated: function () { render(); } }); }
      return;
    }
    if (action === 'more') {
      var moreTicket = OSAS.store.find('tickets', id);
      if (moreTicket) { OSAS.forms.ticketView({ ticket: moreTicket, onUpdated: function () { render(); } }); }
      return;
    }
    if (action === 'resolve') { resolve(id); }
  });

  // Filter toggle
  content.addEventListener('click', function (event) {
    if (event.target.closest('#tkt-filter-btn')) {
      var bar = document.getElementById('tkt-filters');
      if (bar) { bar.style.display = bar.style.display === 'none' ? 'flex' : 'none'; }
    }
  });

  render();
})();
