/* ==========================================================================
   Module 10 — Reports
   ========================================================================== */
(function () {
  'use strict';
  window.addEventListener('error', function (e) { console.error('[Reports] Global Error', e.message, e.filename, e.lineno); });
  var C = OSAS.components, U = OSAS.util;
  var shell = OSAS.shell.mount();
  if (!shell) { console.warn('[Reports] Shell mount failed - auth required'); return; }
  var content = shell.content;
  var OPTIONS = OSAS.store.options();
  console.log('[Reports] Module initialized, C.modal:', typeof C.modal, 'OSAS:', !!OSAS);

  /* Global error listener for modal operations */
  window.addEventListener('modalError', function (e) { console.error('[Reports] Modal error:', e.detail); });

  /* REPORT SOURCES — each maps to a store resource with column definitions */
  var REPORT_SOURCES = {
    'User Management': {
      resource: 'users',
      columns: [
        { label: 'Full Name', value: function (r) { return r.firstName + ' ' + r.lastName; } },
        { label: 'Role', value: 'role' }, { label: 'Student ID', value: 'studentId' },
        { label: 'Email', value: 'email' }, { label: 'Department', value: 'department' },
        { label: 'Program', value: 'program' }, { label: 'Access Level', value: 'accessLevel' },
        { label: 'Status', value: 'status' },
        { label: 'Last Login', value: function (r) { return OSAS.fmt.dateTime(r.lastLogin); } }
      ]
    },
    'Announcements': {
      resource: 'announcements',
      columns: [
        { label: 'Reference', value: 'code' }, { label: 'Title', value: 'title' },
        { label: 'Category', value: 'category' }, { label: 'Audience', value: 'audience' },
        { label: 'Status', value: 'status' },
        { label: 'Date', value: function (r) { return OSAS.fmt.dateTime(r.publishedAt || r.scheduledFor || r.updatedAt); } },
        { label: 'Views', value: 'views' }
      ]
    },
    'Orientation Content': {
      resource: 'modules',
      columns: [
        { label: 'Reference', value: 'code' }, { label: 'Title', value: 'title' },
        { label: 'Category', value: 'category' }, { label: 'Version', value: 'version' },
        { label: 'Duration (mins)', value: 'duration' }, { label: 'Learners', value: 'learners' },
        { label: 'Completion %', value: 'completionRate' }, { label: 'Status', value: 'status' },
        { label: 'Updated', value: function (r) { return OSAS.fmt.date(r.updatedAt); } }
      ]
    },
    'Feedback & Support': {
      resource: 'tickets',
      columns: [
        { label: 'Ticket Code', value: 'code' }, { label: 'Student', value: 'student' },
        { label: 'Email', value: 'studentEmail' }, { label: 'Category', value: 'category' },
        { label: 'Subject', value: 'subject' }, { label: 'Priority', value: 'priority' },
        { label: 'Status', value: 'status' }, { label: 'Assigned To', value: 'assignedTo' },
        { label: 'Submitted', value: function (r) { return OSAS.fmt.dateTime(r.submittedAt); } }
      ]
    },
    'Viewing & Tracking': {
      resource: 'tracking',
      columns: [
        { label: 'Content', value: 'title' }, { label: 'Type', value: 'type' },
        { label: 'Views', value: 'views' }, { label: 'Unique Visitors', value: 'uniqueVisitors' },
        { label: 'Average Time', value: 'avgTime' }, { label: 'Engagement %', value: 'engagement' },
        { label: 'Trend %', value: 'trend' },
        { label: 'Last Viewed', value: function (r) { return OSAS.fmt.dateTime(r.lastViewed); } }
      ]
    },
    'Rules & Regulations': {
      resource: 'regulations',
      columns: [
        { label: 'Reference', value: 'code' }, { label: 'Policy Title', value: 'title' },
        { label: 'Category', value: 'category' }, { label: 'Effective Date', value: 'effectiveDate' },
        { label: 'Approval Body', value: 'approvalBody' }, { label: 'Views', value: 'views' },
        { label: 'Status', value: 'status' }
      ]
    },
    'Events & Activities': {
      resource: 'events',
      columns: [
        { label: 'Event Code', value: 'code' }, { label: 'Event Title', value: 'title' },
        { label: 'Category', value: 'category' },
        { label: 'Start', value: function (r) { return OSAS.fmt.dateTime(r.startAt); } },
        { label: 'Venue', value: 'venue' }, { label: 'Audience', value: 'audience' },
        { label: 'Registered', value: 'registered' },
        { label: 'Status', value: 'status' }
      ]
    }
  };

  /* --- Stats Helper --- */
  function statsHtml() {
    var stats = OSAS.analytics.reports(OSAS.store.all('reports'));
    return '<div class="stats">' +
      C.statCard('Reports on File', stats.total,
        '<span class="u-muted">across ' + OPTIONS.reportCategories.length + ' report categories</span>') +
      C.statCard('Completed Reports', stats.completed,
        '<span class="badge badge--completed">Ready</span><span class="u-muted">available for download</span>') +
      C.statCard('Records Compiled', OSAS.fmt.number(stats.records),
        '<span class="u-muted">total rows across generated reports</span>') +
      C.statCard('In Progress', stats.processing + stats.drafts,
        '<span class="badge badge--processing">Processing</span><span class="u-muted">' + stats.drafts +
        ' draft(s) waiting</span>') + '</div>';
  }

  /* --- Download Report --- */
  function downloadReport(id) {
    var report = OSAS.store.find('reports', id);
    if (!report) { return; }
    var source = REPORT_SOURCES[report.category] || REPORT_SOURCES['Announcements'];
    var rows = OSAS.store.all(source.resource);
    var extension = report.format === 'CSV' ? 'csv' : 'csv';
    var filename = U.slug(report.title) + '.' + extension;
    U.exportCsv(filename, source.columns, rows);
    OSAS.store.logActivity({ tone: 'green', icon: 'download', title: 'Report Downloaded',
      text: report.code + ' — "' + report.title + '" exported with ' + rows.length + ' records.' });
    C.toast('success', 'Report downloaded', filename + ' (' + rows.length + ' records)');
  }

  /* --- Generate Report Modal --- */
  function generateReportModal() {
    C.modal({
      size: 'lg',
      title: 'Generate New Report',
      subtitle: 'Compile a filtered export of OSAS records for submission or archiving.',
      body: '<div id="rp-alert"></div>' +
        '<div class="form-section">' +
        '<div class="section-title"><span>1.</span> Report Definition</div>' +
        OSAS.forms.field('Report Title',
          OSAS.forms.textInput('rp-title', '', 'e.g. Orientation Module Completion Summary'),
          { id: 'rp-title', required: true }) +
        '<div class="form-grid form-grid--2">' +
        OSAS.forms.field('Report Category',
          OSAS.forms.selectInput('rp-category', OPTIONS.reportCategories, 'Feedback & Support', 'Select a category'),
          { id: 'rp-category', required: true }) +
        OSAS.forms.field('Reporting Period',
          OSAS.forms.selectInput('rp-period', OPTIONS.reportPeriods, 'September 2026', 'Select a period')) +
        '</div>' +
        '<div class="form-grid form-grid--2">' +
        OSAS.forms.field('Export Format',
          OSAS.forms.selectInput('rp-format', OPTIONS.reportFormats, 'PDF', 'Select a format')) +
        OSAS.forms.field('File Classification',
          OSAS.forms.selectInput('rp-classification',
            ['Official Record', 'Internal Working Copy', 'For Review Only'],
            'Official Record', 'Select a classification')) +
        '</div></div>' +
        '<div class="form-section">' +
        '<div class="section-title"><span>2.</span> Inclusion Options</div>' +
        '<div class="check-stack">' +
        '<label class="check"><input type="checkbox" checked><span>Include the summary dashboard page with counts and status breakdowns.</span></label>' +
        '<label class="check"><input type="checkbox" checked><span>Include the detailed record listing from the selected module.</span></label>' +
        '<label class="check"><input type="checkbox"><span>Attach the system event log for the selected period.</span></label>' +
        '</div></div>',
      footer: '<button type="button" class="btn btn--ghost" data-close>Cancel</button>' +
        '<button type="button" class="btn btn--primary" id="rp-generate">Generate Report</button>',
      onMount: function (el, close) {
        var alertHost = el.querySelector('#rp-alert');
        el.querySelector('#rp-generate').addEventListener('click', function () {
          try {
            var title = OSAS.forms.value('rp-title');
            var categoryValue = OSAS.forms.value('rp-category');
            if (!title) {
              alertHost.innerHTML = C.alert('danger', 'Unable to generate the report',
                'A report title is required.');
              return;
            }
            var source = REPORT_SOURCES[categoryValue];
            var recordCount = source ? OSAS.store.all(source.resource).length : 0;
            var id = OSAS.store.nextId('reports', 'REP-2026', 3);
            var session = OSAS.auth.current();
            var record = {
              id: id, code: id, title: title, category: categoryValue,
              period: OSAS.forms.value('rp-period') || 'September 2026',
              format: OSAS.forms.value('rp-format') || 'PDF',
              classification: OSAS.forms.value('rp-classification') || 'Official Record',
              status: 'Processing', records: 0, generatedAt: U.now(),
              generatedBy: session ? session.user.firstName + ' ' + session.user.lastName : 'OSAS Administration',
              scope: OSAS.store.meta().campusCode + ' — Southway College of Technology campuses'
            };
            OSAS.store.insert('reports', record);
            alertHost.innerHTML = C.alert('info', 'Report queued for processing',
              'Compiling <b>' + U.esc(title) + '</b> from ' + OSAS.fmt.number(recordCount) + ' source records…');
            C.toast('info', 'Report queued', id + ' — ' + title);
            close();
            page.render();

            window.setTimeout(function () {
              OSAS.store.update('reports', id, { status: 'Completed', records: recordCount });
              OSAS.store.logActivity({
                tone: 'green', icon: 'fileText', title: 'Report Generated',
                text: id + ' — "' + title + '" completed with ' + recordCount + ' records.'
              });
              C.toast('success', 'Report ready', id + ' — ' + title + ' (' + recordCount + ' records)');
              page.render();
            }, 1200);
          } catch (err) {
            alertHost.innerHTML = C.alert('danger', 'Report generation failed', err.message);
            console.error('[Reports] Generate error:', err);
          }
        });
      }
    });
  }

  /* --- Detail Modal --- */
  function detailModal(row) {
    row = row || {};
    var source = REPORT_SOURCES[row.category];
    var preview = source ? OSAS.store.all(source.resource).slice(0, 6) : [];
    var columns = source ? source.columns : [];
    C.modal({
      size: 'lg',
      title: U.esc(row.title),
      subtitle: U.esc(row.code) + ' &middot; ' + U.esc(row.period) + ' &middot; ' + U.esc(row.scope),
      body: C.statStrip([
        { label: 'Status', value: C.badge(row.status) },
        { label: 'Format', value: U.esc(row.format) },
        { label: 'Records', value: OSAS.fmt.number(row.records || preview.length) },
        { label: 'Generated', value: OSAS.fmt.dateTime(row.generatedAt) }
      ]) +
        '<div class="form-section">' +
        '<div class="section-title"><span>1.</span> Report Metadata</div>' +
        '<dl class="kv">' +
        '<dt>Report Category</dt><dd>' + U.esc(row.category) + '</dd>' +
        '<dt>Reporting Period</dt><dd>' + U.esc(row.period) + '</dd>' +
        '<dt>Generated By</dt><dd>' + U.esc(row.generatedBy) + '</dd>' +
        '<dt>Classification</dt><dd>' + U.esc(row.classification || 'Official Record') + '</dd>' +
        '<dt>Source Module</dt><dd>' + (source ? U.esc(source.resource) : '—') + '</dd>' +
        '</dl></div>' +
        '<div class="form-section">' +
        '<div class="section-title"><span>2.</span> Record Preview (first ' + preview.length + ')</div>' +
        (preview.length ? C.dataTable({ columns: columns, rows: preview, empty: 'No records available for this report category.' }) :
          C.alert('info', 'No preview available',
            'This report category has no source records to preview right now.')) +
        '</div>',
      footer: '<button type="button" class="btn btn--ghost" data-close>Close</button>' +
        '<button type="button" class="btn btn--primary" id="rp-download">' +
        OSAS.icons.icon('download', 12) + ' Download Report</button>',
      onMount: function (el, close) {
        try {
          el.querySelector('#rp-download').addEventListener('click', function () {
            downloadReport(row.id);
            close();
            page.render();
          });
        } catch (err) {
          console.error('[Reports] Download error:', err);
        }
      }
    });
  }

  /* --- Pagekit List Page --- */
  var page = OSAS.pagekit.listPage({
    content: content,
    title: 'Reports',
    subtitle: 'Generate and export official reports for portal activity, compliance, and support operations.',
    actions: '<button type="button" class="btn btn--primary" data-action="generate">' +
      OSAS.icons.icon('plus', 13) + ' Generate New Report</button>',
    resource: 'reports',
    defaultState: { search: '', category: 'All', period: 'All', status: 'All', page: 1 },
    filters: [
      { id: 'rp-search', key: 'search', type: 'search', label: 'Search Reports',
        placeholder: 'Search by report title, category or reference…' },
      { id: 'rp-filter-category', key: 'category', type: 'select', label: 'Report Category',
        values: OPTIONS.reportCategories },
      { id: 'rp-filter-period', key: 'period', type: 'select', label: 'Reporting Period',
        values: OPTIONS.reportPeriods },
      { id: 'rp-filter-status', key: 'status', type: 'select', label: 'Status',
        values: ['Completed', 'Processing', 'Draft'] }
    ],
    sort: { field: 'generatedAt', dir: 'desc' },
    stats: statsHtml,
    toolbar: '<div class="table-toolbar"><div class="panel-title">Generated Report Register</div>' +
      '<button type="button" class="btn btn--ghost btn--sm" data-action="export">' +
      OSAS.icons.icon('download', 11) + ' Export Register</button></div>',
    filter: function (rows, state) {
      try {
        var next = U.search(rows, state.search, ['title', 'category', 'code', 'period']);
        next = U.byStatus(next, state.status);
        if (state.category !== 'All') {
          next = next.filter(function (r) { return r.category === state.category; });
        }
        if (state.period !== 'All') {
          next = next.filter(function (r) { return r.period === state.period; });
        }
        return next;
      } catch (err) {
        console.error('[Reports] Filter error:', err);
        return rows;
      }
    },
    columns: function () {
      return [
        {
          label: 'Report Title',
          render: function (row) {
            row = row || {};
            return '<div class="cell-title">' + U.esc(row.title) + '</div>' +
              '<div class="cell-sub mono">' + U.esc(row.code) + ' &middot; ' + U.esc(row.scope || '') + '</div>';
          }
        },
        { label: 'Report Category', render: function (row) { row = row || {}; return '<span class="chip">' + U.esc(row.category) + '</span>'; } },
        { label: 'Reporting Period', render: function (row) { row = row || {}; return U.esc(row.period); } },
        {
          label: 'Generated By',
          render: function (row) {
            row = row || {};
            return '<div class="cell-title">' + U.esc(row.generatedBy) + '</div>' +
              '<div class="cell-sub">' + OSAS.fmt.dateTime(row.generatedAt) + '</div>';
          }
        },
        { label: 'Records', align: 'right', render: function (row) { row = row || {}; return OSAS.fmt.number(row.records || 0); } },
        {
          label: 'Format',
          render: function (row) { row = row || {}; return '<span class="badge badge--info">' + U.esc(row.format || 'PDF') + '</span>'; }
        },
        { label: 'Status', render: function (row) { row = row || {}; return C.badge(row.status); } },
        {
          label: 'Actions',
          align: 'right',
          render: function (row) {
            row = row || {};
            return '<div class="cell-actions">' +
              '<button type="button" class="btn btn--primary btn--sm" data-action="download" data-id="' + U.esc(row.id) + '">Download</button>' +
              '<button type="button" class="btn btn--ghost btn--sm" data-action="view" data-id="' + U.esc(row.id) + '">View</button></div>';
          }
        }
      ];
    },
    footerLabel: function (info) { return 'Showing ' + info.from + '–' + info.to + ' of ' + info.total + ' generated reports'; },
    empty: 'No reports match the current filters.',
    afterTable: function () {
      return '<div class="u-grow"></div>';
    },
    onAction: function (action, id) {
      try {
        if (action === 'generate') { generateReportModal(); return; }
        if (action === 'backup') {
          U.download('beacon-osas-backup-' + OSAS.SEED_NOW.slice(0, 10) + '.json',
            OSAS.store.exportBackup(), 'application/json');
          C.toast('success', 'Backup downloaded', 'beacon-osas-backup.json');
          return;
        }
        if (action === 'restore') { OSAS.shell.resetDemoData(); return; }
        if (action === 'export') {
          U.exportCsv('beacon-report-register.csv', [
            { label: 'Reference', value: 'code' },
            { label: 'Report Title', value: 'title' },
            { label: 'Category', value: 'category' },
            { label: 'Period', value: 'period' },
            { label: 'Format', value: 'format' },
            { label: 'Records', value: 'records' },
            { label: 'Generated By', value: 'generatedBy' },
            { label: 'Generated At', value: function (r) { return OSAS.fmt.dateTime(r.generatedAt); } },
            { label: 'Status', value: 'status' }
          ], page.rows());
          C.toast('success', 'Register exported', page.rows().length + ' reports written to CSV.');
          return;
        }
        if (action === 'download') { downloadReport(id); return; }
        if (action === 'view') {
          var row = OSAS.store.find('reports', id);
          if (row) { detailModal(row); }
        }
      } catch (err) {
        console.error('[Reports] Action error [' + action + ']:', err);
      }
    }
  });
})();