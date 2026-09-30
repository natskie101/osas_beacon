/* ==========================================================================
   Module 6 — Viewing & Participation Tracking Core
   ========================================================================== */
(function () {
  'use strict';
  var C = OSAS.components, U = OSAS.util;
  var shell = OSAS.shell.mount();
  if (!shell) { return; }
  var content = shell.content;
  var OPTIONS = OSAS.store.options();

  var state = { search: '', program: 'All', tab: 'all', page: 1 };

  function filtered() {
    var rows = OSAS.store.all('users').filter(function (r) { return r.role === 'Student'; });
    rows = U.search(rows, state.search, ['studentId', 'firstName', 'lastName', 'email', 'program', 'department']);
    if (state.program !== 'All') {
      rows = rows.filter(function (r) { return r.program === state.program; });
    }
    if (state.tab === 'orientation') { rows = rows.filter(function (r) { return r.orientationDone; }); }
    if (state.tab === 'atrisk') { rows = rows.filter(function (r) { return r.status === 'Inactive' || r.orientationDone === false; }); }
    return U.sortBy(rows, 'studentId', 'asc');
  }

  /* ------------------------------------------------------------- toolbar */
  function toolbar() {
    return '<div class="trk-toolbar">' +
      '<div class="trk-toolbar__left">' +
      '<div class="panel-title">Student Participation &amp; Orientation Ledger</div>' +
      '<div class="trk-sub">Monitor portal content reach, engagement quality, and viewing activity across all broadcasting modules.</div>' +
      '</div>' +
      '<div class="trk-toolbar__right">' +
      '<span class="input-group trk-search"><span class="input-group__ico">' + OSAS.icons.icon('search', 14) + '</span>' +
      '<input class="input" id="trk-search" placeholder="Search student ID, name, program…" value="' + U.esc(state.search) + '"></span>' +
      '<span class="input-group trk-program"><span class="input-group__ico">' + OSAS.icons.icon('filter', 14) + '</span>' +
      '<select class="input" id="trk-program">' +
      ['All'].concat(OPTIONS.programs || []).map(function (p) {
        return '<option value="' + U.esc(p) + '"' + (p === state.program ? ' selected' : '') + '>' + U.esc(p) + '</option>';
      }).join('') + '</select></span>' +
      '</div></div>';
  }

  function tabStrip() {
    var students = OSAS.store.all('users').filter(function (r) { return r.role === 'Student'; });
    var orientDone = students.filter(function (r) { return r.orientationDone; }).length;
    var atRisk = students.filter(function (r) { return r.status === 'Inactive' || r.orientationDone === false; }).length;
    var tabs = [
      { key: 'all', label: 'All Student Logs' },
      { key: 'orientation', label: 'Orientation Progress' },
      { key: 'atrisk', label: 'At-Risk / Incomplete' }
    ];
    return '<div class="toolbar"><div class="tabs">' + tabs.map(function (tab) {
      return '<button type="button" class="tab' + (state.tab === tab.key ? ' tab--on' : '') +
        '" data-action="tab" data-id="' + tab.key + '">' + tab.label + '</button>';
    }).join('') + '</div></div>';
  }

  /* ------------------------------------------------------------ table */
  function orientationBar(pct) {
    var color = pct >= 100 ? 'green' : pct >= 50 ? 'blue' : 'red';
    return '<div class="trk-progress">' +
      '<div class="trk-progress__label">' + pct + '% Modules (' + (pct >= 100 ? '100%' : pct + '%') + ')</div>' +
      '<div class="trk-progress__bar"><div class="trk-progress__fill trk-progress__fill--' + color + '" style="width:' + Math.min(pct, 100) + '%"></div></div>' +
      '</div>';
  }

  function table(rows) {
    var page = U.paginate(rows, state.page, OSAS.CONFIG.pageSize);
    var columns = [
      {
        label: 'Student ID &amp; Name',
        render: function (row) {
          return '<div class="trk-id">' + U.esc(row.studentId || '—') + '</div>' +
            '<div class="trk-name">' + U.esc(row.firstName + ' ' + row.lastName) + '</div>' +
            '<div class="trk-email">' + U.esc(row.email) + '</div>';
        }
      },
      {
        label: 'Program &amp; College',
        render: function (row) {
          return '<div class="trk-program">' + U.esc(row.program || '—') + '</div>' +
            '<div class="trk-college">' + U.esc(row.department || '') + '</div>';
        }
      },
      {
        label: 'Orientation Completed',
        render: function (row) {
          var pct = row.orientationPct || 0;
          return orientationBar(pct);
        }
      },
      {
        label: 'Attended Events',
        render: function (row) {
          var events = row.eventsAttended || 0;
          var hours = row.coCurricularHrs || 0;
          return '<div class="trk-events">' + events + ' Events Logged</div>' +
            '<div class="trk-hours">' + hours + ' Co-Curricular Hrs</div>';
        }
      },
      {
        label: 'Status',
        render: function (row) {
          var pct = row.orientationPct || 0;
          if (pct >= 100) { return '<span class="badge badge--active">Compliant</span>'; }
          if (pct >= 50) { return '<span class="badge badge--pending">In Progress</span>'; }
          return '<span class="badge badge--danger">Overdue</span>';
        }
      },
      {
        label: 'Actions',
        align: 'right',
        render: function (row) {
          var pct = row.orientationPct || 0;
          return '<div class="cell-actions">' +
            '<button type="button" class="btn btn--brand btn--sm" data-action="view" data-id="' + U.esc(row.id) + '">View</button>' +
            (pct < 100 ? '<button type="button" class="btn btn--outline btn--sm" data-action="notify" data-id="' + U.esc(row.id) + '">Notify</button>' : '') +
            '</div>';
        }
      }
    ];
    var footer = C.pager(page, 'Showing ' + page.from + '–' + page.to + ' of ' + page.total + ' student records');
    return C.dataTable({ columns: columns, rows: page.items, footer: footer });
  }

  /* ------------------------------------------------------------ actions */
  function notifyStudent(id) {
    var row = OSAS.store.find('users', id);
    if (!row) { return; }
    C.confirm({
      title: 'Send orientation reminder?',
      subtitle: row.firstName + ' ' + row.lastName + ' · ' + (row.studentId || 'No ID'),
      alertTone: 'info',
      alertTitle: 'Portal + Email notification',
      alertText: 'A reminder will be sent to complete the remaining orientation modules.',
      confirmLabel: 'Send Notification',
      confirmClass: 'btn--primary',
      onConfirm: function () {
        OSAS.store.logActivity({
          tone: 'blue', icon: 'send', title: 'Orientation Reminder Sent',
          text: row.firstName + ' ' + row.lastName + ' was reminded to complete orientation modules.'
        });
        C.toast('success', 'Notification sent', row.firstName + ' ' + row.lastName + ' will be notified.');
        render();
      }
    });
  }

  function viewStudent(id) {
    var row = OSAS.store.find('users', id);
    if (!row) { return; }
    var pct = row.orientationPct || 0;
    var modules = row.orientationModules || [];
    var events = row.attendedEvents || [];
    var statusLabel = pct >= 100 ? 'Compliant' : pct >= 50 ? 'In Progress' : 'Overdue';
    var statusClass = pct >= 100 ? 'active' : pct >= 50 ? 'pending' : 'danger';

    var moduleHtml = modules.length
      ? '<div class="trk-module-list">' + modules.map(function (m) {
          return '<div class="trk-module">' +
            '<span class="trk-module-check">' + OSAS.icons.icon('check', 14) + '</span>' +
            '<div class="trk-module-info">' +
            '<div class="trk-module-name">' + U.esc(m.name) + '</div>' +
            '<div class="trk-module-date">Completed: ' + U.esc(m.date) + '</div>' +
            '</div></div>';
        }).join('') + '</div>'
      : '<div class="trk-empty">No modules completed yet.</div>';

    var eventHtml = events.length
      ? '<div class="trk-event-list">' + events.map(function (e) {
          return '<div class="trk-event">' +
            '<div class="trk-event-info">' +
            '<div class="trk-event-name">' + U.esc(e.name) + '</div>' +
            '<div class="trk-event-date">' + U.esc(e.date) + '</div>' +
            '</div>' +
            '<span class="trk-event-credit">+' + U.esc(e.credit) + ' Credit Hrs</span>' +
            '</div>';
        }).join('') + '</div>'
      : '<div class="trk-empty">No events attended yet.</div>';

    C.modal({
      size: 'lg',
      title: 'View Student Profile & Tracking Logs',
      subtitle: U.esc(row.firstName + ' ' + row.lastName) + ' (ID: ' + U.esc(row.studentId || '—') + ')',
      body: '<div class="trk-profile">' +
        '<div class="trk-profile__avatar">' + C.avatar(row.firstName, row.lastName, 'brand') + '</div>' +
        '<div class="trk-profile__info">' +
        '<div class="trk-profile__name">' + U.esc(row.firstName + ' ' + row.lastName) + '</div>' +
        '<div class="trk-profile__meta">' + U.esc(row.email) + ' · ' + U.esc(row.program || '') + ' · Year Level: ' + U.esc(row.year || '—') + '</div>' +
        '</div>' +
        '<span class="badge badge--' + statusClass + '">' + statusLabel + '</span>' +
        '</div>' +
        '<div class="trk-section">' +
        '<div class="trk-section__title">Orientation Progress (' + modules.length + ' / ' + (modules.length + 2) + ' Completed)</div>' +
        orientationBar(pct) +
        moduleHtml +
        '</div>' +
        '<div class="trk-section">' +
        '<div class="trk-section__title">Attended Campus Events (' + events.length + ' Events / ' + (events.reduce(function (s, e) { return s + (parseInt(e.credit) || 0); }, 0)) + ' Hours)</div>' +
        eventHtml +
        '</div>',
      footer: '<button type="button" class="btn btn--outline" id="trk-print">Print Student Summary</button>' +
        '<button type="button" class="btn btn--brand" data-close>Close Details</button>',
      onMount: function (el, close) {
        var printBtn = el.querySelector('#trk-print');
        if (printBtn) {
          printBtn.addEventListener('click', function () {
            C.toast('info', 'Print queued', 'Student summary sent to printer.');
          });
        }
      }
    });
  }

  function exportCsv() {
    var rows = filtered();
    U.exportCsv('beacon-student-tracking.csv', [
      { label: 'Student ID', value: 'studentId' },
      { label: 'Name', value: function (r) { return r.firstName + ' ' + r.lastName; } },
      { label: 'Email', value: 'email' },
      { label: 'Program', value: 'program' },
      { label: 'Department', value: 'department' },
      { label: 'Orientation %', value: 'orientationPct' },
      { label: 'Events Attended', value: 'eventsAttended' },
      { label: 'Co-Curricular Hrs', value: 'coCurricularHrs' },
      { label: 'Status', value: 'status' }
    ], rows);
    C.toast('success', 'Tracking exported', rows.length + ' records written to CSV.');
  }

  function render() {
    content.innerHTML = OSAS.shell.pageHead({
      title: 'Viewing & Participation Tracking Core',
      subtitle: 'Monitor portal content reach, engagement quality, and viewing activity across all broadcasting modules.',
      actions: '<button type="button" class="btn btn--ghost" data-action="export">' +
        OSAS.icons.icon('download', 13) + ' Export Logs</button>'
    }) + toolbar() + tabStrip() + table(filtered());
  }

  /* ---------------- one-time delegated bindings (survive re-renders) ----- */
  content.addEventListener('input', function (event) {
    if (event.target.id !== 'trk-search') { return; }
    state.search = event.target.value;
    state.page = 1;
    render();
    var box = document.getElementById('trk-search');
    if (box) {
      box.focus();
      box.setSelectionRange(box.value.length, box.value.length);
    }
  });

  content.addEventListener('change', function (event) {
    if (event.target.id !== 'trk-program') { return; }
    state.program = event.target.value;
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
    if (action === 'export') { exportCsv(); return; }
    if (action === 'view') { viewStudent(id); return; }
    if (action === 'notify') { notifyStudent(id); }
  });

  render();
})();
