/* ==========================================================================
   Module 1 — Admin Dashboard Overview
   ========================================================================== */
(function () {
  'use strict';
  var C = OSAS.components, U = OSAS.util;
  var shell = OSAS.shell.mount();
  if (!shell) { return; }
  var content = shell.content;

  function statsHtml(data) {
    return '<div class="stats">' +
      C.statCard('Total Registered Students', OSAS.fmt.number(data.totalStudents),
        C.delta(data.totalStudentsGrowth, 'Across both campuses') +
        ' <span class="u-muted">&middot; ' + U.esc(data.metrics.lastSyncLabel) + '</span>') +
      C.statCard('Orientation Completion Rate', OSAS.fmt.percent(data.orientationRate, 1),
        C.delta(data.orientationGrowth, 'Increase Verified') +
        ' <span class="u-muted">&middot; ' + OSAS.fmt.number(data.metrics.freshmenEnrolled) + ' freshmen</span>') +
      C.statCard('Open Support Tickets', data.openTickets,
        '<span class="badge badge--high">High Priority</span>' +
        '<span class="u-muted">' + data.highPriorityTickets + ' high &middot; avg response ' +
        data.avgResponseHours + ' hrs</span>') +
      C.statCard('Active Events &amp; Activities', data.activeEvents,
        '<span class="badge badge--scheduled">Upcoming</span>' +
        '<span class="u-muted">' + data.scheduledThisWeek + ' scheduled within two weeks</span>') +
      '</div>';
  }

  function chartCard(data) {
    return '<div class="card">' +
      '<div class="card__head"><div>' +
      '<h3 class="card__title">Weekly Portal Traffic &amp; Module Engagement</h3>' +
      '<div class="card__sub">Daily admin sign-ins and orientation module completions, current week against the previous week.</div>' +
      '</div><span class="chip">Last 7 days</span></div>' +
      '<div class="chart-host" id="dash-chart"></div>' +
      C.legend([
        { label: 'Admin Logins — Current Week' },
        { label: 'Module Completions — Current Week', dashed: true }
      ]) +
      '</div>';
  }

  function auditCard(data) {
    return '<div class="card" style="display:flex;flex-direction:column">' +
      '<div class="card__head"><div>' +
      '<h3 class="card__title">Live System Audit Log</h3>' +
      '<div class="card__sub">Recent system activity across all OSAS modules (last 24 hours).</div>' +
      '</div></div>' +
      '<div class="log-list u-grow">' + data.audit.map(C.logItem).join('') + '</div>' +
      '<div class="card__foot"><button type="button" class="link" data-action="full-log">' +
      'View Full System Event Log ' + OSAS.icons.icon('arrowRight', 12) + '</button></div></div>';
  }

  function priorityCard(data) {
    var columns = [
      {
        label: 'Ticket ID &amp; Student',
        render: function (row) {
          return '<div class="cell-user">' + C.avatarOf(row.student, 'blue') +
            '<div><div class="cell-title mono">' + U.esc(row.code) + '</div>' +
            '<div class="cell-sub">' + U.esc(row.student) + ' &middot; ' + U.esc(row.studentEmail) + '</div></div></div>';
        }
      },
      {
        label: 'Category &amp; Issue Summary',
        render: function (row) {
          return '<div class="cell-title">' + U.esc(row.category) + '</div>' +
            '<div class="cell-sub">' + U.esc(U.truncate(row.subject, 78)) + '</div>';
        }
      },
      { label: 'Submitted Date', render: function (row) { return OSAS.fmt.dateTime(row.submittedAt); } },
      { label: 'Priority', render: function (row) { return C.priority(row.priority); } },
      { label: 'Status', render: function (row) { return C.badge(row.status); } },
      {
        label: 'Quick Action',
        align: 'right',
        render: function (row) {
          var resolve = (row.status === 'Open' || row.status === 'In Progress')
            ? '<button type="button" class="btn btn--primary btn--sm" data-action="resolve" data-id="' +
              U.esc(row.id) + '">Resolve</button>' : '';
          return '<div class="cell-actions">' + resolve +
            '<button type="button" class="btn btn--ghost btn--sm" data-action="view" data-id="' +
            U.esc(row.id) + '">' + (row.status === 'Resolved' ? 'View Record' : 'View') + '</button></div>';
        }
      }
    ];

    var toolbar = '<div class="table-toolbar"><div class="panel-title">Priority Inquiries &amp; Action Required Items</div>' +
      '<a class="link" href="support.html">Go to Support Center ' + OSAS.icons.icon('arrowRight', 12) + '</a></div>';

    var queue = data.queue;
    var rows = queue.slice(0, 3);
    var footer = '<div class="table-foot"><div>Showing 1–' + rows.length + ' of ' + queue.length +
      ' priority tickets &middot; Refreshed just now</div>' +
      '<div class="u-muted">Immediate attention items from Module 7: Support Center</div></div>';

    return C.dataTable({
      columns: columns, rows: rows, toolbar: toolbar, footer: footer,
      empty: 'No priority inquiries are waiting for action. All support tickets are resolved.'
    });
  }

  function fullLogModal() {
    var entries = OSAS.store.all('auditLog');
    C.modal({
      size: 'lg',
      title: 'System Event Log',
      subtitle: 'Chronological audit trail of portal activity across all ten OSAS modules.',
      body: C.statStrip([
        { label: 'Total Events', value: OSAS.fmt.number(entries.length) },
        { label: 'Today', value: OSAS.fmt.number(entries.filter(function (e) { return e.at.slice(0, 10) === OSAS.SEED_NOW.slice(0, 10); }).length) },
        { label: 'Broadcasts', value: OSAS.fmt.number(OSAS.store.all('broadcasts').length) },
        { label: 'Open Tickets', value: OSAS.fmt.number(OSAS.analytics.openTickets(OSAS.store.all('tickets')).length) }
      ]) + '<div class="log-list" style="border:1px solid var(--line);border-radius:9px;overflow:hidden">' +
        entries.map(C.logItem).join('') + '</div>',
      footer: '<button type="button" class="btn btn--ghost" data-close>Close</button>' +
        '<button type="button" class="btn btn--outline" id="log-export">' +
        OSAS.icons.icon('download', 12) + ' Export Log (CSV)</button>',
      onMount: function (el) {
        el.querySelector('#log-export').addEventListener('click', function () {
          U.exportCsv('beacon-system-event-log.csv', [
            { label: 'Timestamp', value: 'at' },
            { label: 'Event', value: 'title' },
            { label: 'Details', value: 'text' }
          ], entries);
          C.toast('success', 'Event log exported', 'beacon-system-event-log.csv');
        });
      }
    });
  }

  function render() {
    var data = OSAS.analytics.dashboard();
    content.innerHTML =
      OSAS.shell.pageHead({
        title: 'Admin Dashboard Overview',
        subtitle: 'Consolidated real-time status of all OSAS portal modules, content operations and student support activity for ' +
          U.esc(OSAS.store.meta().academicYear) + '.',
        actions: '<button type="button" class="btn btn--ghost" data-action="refresh">' +
          OSAS.icons.icon('refresh', 13) + ' Refresh Data</button>' +
          '<a class="btn btn--primary" href="reports.html">' + OSAS.icons.icon('fileText', 13) + ' Open Reports</a>'
      }) +
      statsHtml(data) +
      '<div class="cols cols--dash u-mb-14">' + chartCard(data) + auditCard(data) + '</div>' +
      priorityCard(data);

    C.chart('dash-chart');
  }

  content.addEventListener('click', function (event) {
    var trigger = event.target.closest ? event.target.closest('[data-action]') : null;
    if (!trigger) { return; }
    var action = trigger.getAttribute('data-action');
    var id = trigger.getAttribute('data-id');

    if (action === 'refresh') {
      render();
      C.toast('success', 'Dashboard refreshed', 'Live module counters were recomputed from the latest records.');
      return;
    }
    if (action === 'full-log') { fullLogModal(); return; }
    if (action === 'view') {
      var ticket = OSAS.store.find('tickets', id);
      if (ticket) { OSAS.forms.ticketView({ ticket: ticket, onUpdated: render }); }
      return;
    }
    if (action === 'resolve') {
      var target = OSAS.store.find('tickets', id);
      if (!target) { return; }
      C.confirm({
        title: 'Resolve ticket #' + target.code + '?',
        subtitle: target.student + ' &middot; ' + target.category,
        message: 'The ticket will be closed as resolved and the student will be notified through the portal and university email.',
        confirmLabel: 'Mark as resolved',
        confirmClass: 'btn--success',
        onConfirm: function () {
          var session = OSAS.auth.current();
          var staff = session ? session.user.firstName + ' ' + session.user.lastName : 'OSAS Support';
          target.status = 'Resolved';
          target.updatedAt = U.now();
          target.messages.push({
            author: staff, role: 'OSAS Support', at: U.now(),
            body: 'Your concern has been addressed and verified. We are closing this ticket as resolved — please reopen it if the issue recurs.'
          });
          OSAS.store.persist();
          OSAS.store.logActivity({
            tone: 'green', icon: 'check', title: 'New Ticket Resolved #' + target.code,
            text: target.student + ' · ' + target.category + ' marked as resolved by OSAS Support.'
          });
          render();
          C.toast('success', 'Ticket resolved', target.code + ' is now closed.');
        }
      });
    }
  });

  render();
})();
