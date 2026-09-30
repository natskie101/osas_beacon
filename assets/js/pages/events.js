/* ==========================================================================
   Module 5 — Events & Activities Management
   ========================================================================== */
(function () {
  'use strict';
  var C = OSAS.components, U = OSAS.util;
  var shell = OSAS.shell.mount();
  if (!shell) { return; }
  var OPTIONS = OSAS.store.options();

  function statsHtml() {
    var stats = OSAS.analytics.events(OSAS.store.all('events'));
    return '<div class="stats">' +
      C.statCard('Upcoming Events', stats.upcoming,
        '<span class="badge badge--scheduled">Scheduled</span><span class="u-muted">published to students</span>') +
      C.statCard('Completed Activities', stats.completed,
        '<span class="badge badge--completed">Completed</span><span class="u-muted">with attendance records</span>') +
      '</div>';
  }

  /* --------------------------------------------------- event create / edit
     Frames 80 (create) and 81 (edit). */
  function eventFormHtml(row) {
    var isEdit = !!(row && row.id);
    var start = row.startAt || OSAS.SEED_NOW;
    var end = row.endAt || start;
    return '<div id="ev-alert"></div>' +
      '<div class="form-section">' +
      '<div class="section-title"><span>1.</span> Event Details</div>' +
      OSAS.forms.field('Event Title',
        OSAS.forms.textInput('ev-title', row.title, 'e.g. Freshmen Orientation Assembly — Day 1'),
        { id: 'ev-title', required: true }) +
      '<div class="form-grid form-grid--2">' +
      OSAS.forms.field('Event Category',
        OSAS.forms.selectInput('ev-category', OPTIONS.eventCategories, row.category, 'Select a category'),
        { id: 'ev-category', required: true }) +
      OSAS.forms.field('Event Mode',
        OSAS.forms.selectInput('ev-mode', ['On-campus', 'Hybrid', 'Off-campus'],
          row.mode || 'On-campus', 'Select the delivery mode')) +
      '</div>' +
      OSAS.forms.field('Event Description',
        '<textarea class="input" id="ev-description" rows="3" placeholder="Describe the activity, its purpose and what students should prepare…">' +
        U.esc(row.description || '') + '</textarea>', { id: 'ev-description', required: true }) +
      '</div>' +
      '<div class="form-section">' +
      '<div class="section-title"><span>2.</span> Schedule, Venue &amp; Capacity</div>' +
      '<div class="form-grid form-grid--2">' +
      OSAS.forms.field('Start Date &amp; Time',
        '<input class="input" type="datetime-local" id="ev-start" value="' + U.esc(start.slice(0, 16)) + '">',
        { id: 'ev-start', required: true }) +
      OSAS.forms.field('End Date &amp; Time',
        '<input class="input" type="datetime-local" id="ev-end" value="' + U.esc(end.slice(0, 16)) + '">',
        { id: 'ev-end', required: true }) +
      '</div>' +
      '<div class="form-grid form-grid--2">' +
      OSAS.forms.field('Venue / Location',
        OSAS.forms.textInput('ev-venue', row.venue, 'e.g. UC-LM University Gymnasium'),
        { id: 'ev-venue', required: true }) +
      '</div>' +
      '<div class="field"><span class="flabel">Target Audience<span class="req">*</span></span>' +
      '<div class="radio-list">' + OPTIONS.audiences.map(function (audience) {
        var on = (row.audience || 'All Students') === audience;
        return '<label class="radio-pill' + (on ? ' radio-pill--on' : '') + '">' +
          '<input type="radio" name="ev-audience" value="' + U.esc(audience) + '"' + (on ? ' checked' : '') + '>' +
          U.esc(audience) + '</label>';
      }).join('') + '</div></div>' +
      '<div class="form-grid form-grid--2">' +
      OSAS.forms.field('Publication Status',
        OSAS.forms.selectInput('ev-status', ['Scheduled', 'Completed', 'Cancelled'],
          row.status || 'Scheduled', 'Select status')) +
      OSAS.forms.field('Organizing Office',
        OSAS.forms.selectInput('ev-organizer', [
          'Office of Student Affairs and Services', 'Guidance & Testing Center',
          'Sports & Athletics Office', 'Career & Placement Office', 'College Secretariat'
        ], row.organizer, 'Select the organizing office')) +
      '</div>' +
      '</div>' +
      '<div class="form-section">' +
      '<div class="section-title"><span>3.</span> Verification &amp; Monitoring</div>' +
      '<div class="check-stack">' +
      '<label class="check"><input type="checkbox" id="ev-verify"' + (isEdit ? ' checked' : '') + '>' +
      '<span>I confirm that this activity has been reviewed and approved by the Office of Student Affairs and Services.</span></label>' +
      '<label class="check"><input type="checkbox" id="ev-monitor"' + (isEdit ? ' checked' : '') + '>' +
      '<span>Enable attendance monitoring and post-event evaluation for this activity.</span></label>' +
      '</div></div>';
  }
  function eventModal(row, mode) {
    var isEdit = mode === 'edit';
    C.modal({
      size: 'lg',
      title: isEdit ? 'Edit Event / Activity' : 'Create New Event / Activity',
      subtitle: isEdit
        ? 'Update the schedule, venue and audience of this activity.'
        : 'Schedule and publish a new event or activity for the student portal.',
      body: eventFormHtml(row),
      footer: '<button type="button" class="btn btn--ghost" data-close>Cancel</button>' +
        '<button type="button" class="btn btn--outline" id="ev-save-draft">Save as Draft</button>' +
        '<button type="button" class="btn btn--primary" id="ev-save">' +
        (isEdit ? 'Update Event' : 'Schedule Event') + '</button>',
      onMount: function (el, close) {
        var alertHost = el.querySelector('#ev-alert');
        U.qsa('input[name="ev-audience"]', el).forEach(function (radio) {
          radio.addEventListener('change', function () {
            U.qsa('.radio-pill', el).forEach(function (pill) {
              pill.classList.toggle('radio-pill--on', pill.contains(radio) && radio.checked);
            });
          });
        });

        function save(forcedStatus) {
          var title = OSAS.forms.value('ev-title');
          var categoryValue = OSAS.forms.value('ev-category');
          var description = OSAS.forms.value('ev-description');
          var venueInput = OSAS.forms.value('ev-venue');
          var startValue = el.querySelector('#ev-start').value;
          var endValue = el.querySelector('#ev-end').value;
          var errors = [];

          if (!title) { errors.push('An event title is required.'); }
          if (!categoryValue) { errors.push('Select the event category.'); }
          if (!description) { errors.push('An event description is required.'); }
          if (!venueInput) { errors.push('A venue or location is required.'); }
          if (!startValue) { errors.push('A start date and time is required.'); }
          if (startValue && endValue && new Date(endValue) < new Date(startValue)) {
            errors.push('The end date cannot be earlier than the start date.');
          }
          if (!el.querySelector('#ev-verify').checked) {
            errors.push('Confirm the approval declaration before saving this activity.');
          }
          if (errors.length) {
            alertHost.innerHTML = C.alert('danger', 'Unable to save the activity', errors.join(' '));
            return;
          }

          var audienceRadio = U.qsa('input[name="ev-audience"]', el).filter(function (r) { return r.checked; })[0];
          function stamp(value) { return value && value.length === 16 ? value + ':00' : value; }
          var payload = {
            title: title, category: categoryValue, description: description,
            venue: venueInput, mode: el.querySelector('#ev-mode').value || 'On-campus',
            startAt: stamp(startValue),
            endAt: stamp(endValue || startValue),
            audience: audienceRadio ? audienceRadio.value : 'All Students',
            organizer: el.querySelector('#ev-organizer').value || 'Office of Student Affairs and Services',
            status: forcedStatus || el.querySelector('#ev-status').value || 'Scheduled',
            updatedAt: U.now(),
            verification: 'Approved by the Office of Student Affairs and Services'
          };

          var saved;
          if (isEdit) {
            saved = OSAS.store.update('events', row.id, payload);
          } else {
            payload.id = OSAS.store.nextId('events', 'EVT-2026', 3);
            payload.code = payload.id;
            payload.registered = 0;
            saved = OSAS.store.insert('events', payload);
          }

          OSAS.store.logActivity({
            tone: saved.status === 'Scheduled' ? 'blue' : 'green', icon: 'calendar',
            title: isEdit ? 'Event Updated' : 'Event Scheduled',
            text: saved.code + ' — "' + saved.title + '" is ' + saved.status.toLowerCase() +
              ' for ' + OSAS.fmt.range(saved.startAt, saved.endAt) + ' at ' + saved.venue + '.'
          });
          alertHost.innerHTML = C.alert('success',
            isEdit ? 'Event Updated Successfully!' : 'Event Scheduled!',
            '<b>' + U.esc(saved.title) + '</b> — ' + U.esc(saved.venue) + ', ' +
            OSAS.fmt.range(saved.startAt, saved.endAt) + '.');
          el.querySelector('.modal__foot').innerHTML =
            '<button type="button" class="btn btn--primary" data-close>Done</button>';
          C.toast('success', isEdit ? 'Event updated' : 'Event scheduled', saved.title);
          close();
          page.render();
        }

        el.querySelector('#ev-save').addEventListener('click', function () { save(); });
        el.querySelector('#ev-save-draft').addEventListener('click', function () { save('Draft'); });
      }
    });
  }
  function previewModal(row) {
    C.modal({
      size: 'lg',
      title: U.esc(row.title),
      subtitle: U.esc(row.code) + ' &middot; ' + U.esc(row.category) + ' &middot; ' + U.esc(row.organizer || ''),
      body: C.statStrip([
        { label: 'Status', value: C.badge(row.status) },
        { label: 'Mode', value: U.esc(row.mode || 'On-campus') },
        { label: 'Registered', value: OSAS.fmt.number(row.registered) }
      ]) +
        C.alert('info', U.esc(row.venue), U.esc(row.description)) +
        '<dl class="kv">' +
        '<dt>Schedule</dt><dd>' + OSAS.fmt.range(row.startAt, row.endAt) + '</dd>' +
        '<dt>Target Audience</dt><dd>' + U.esc(row.audience) + '</dd>' +
        '<dt>Organizing Office</dt><dd>' + U.esc(row.organizer || '—') + '</dd>' +
        '<dt>Verification</dt><dd>' + U.esc(row.verification || 'Pending approval') + '</dd>' +
        '</dl>',
      footer: '<button type="button" class="btn btn--ghost" data-close>Close</button>' +
        '<button type="button" class="btn btn--primary" id="ev-edit">Edit Event</button>',
      onMount: function (el, close) {
        el.querySelector('#ev-edit').addEventListener('click', function () {
          close();
          eventModal(row, 'edit');
        });
      }
    });
  }
  /* --------------------------------------------------------- module page */
  var page = OSAS.pagekit.listPage({
    content: shell.content,
    title: 'Events & Activities Management',
    subtitle: 'Schedule, publish and monitor university events, seminars, and student activities.',
    actions: '<button type="button" class="btn btn--ghost" data-action="export">' +
      OSAS.icons.icon('download', 13) + ' Export Schedule</button>' +
      '<button type="button" class="btn btn--primary" data-action="new">' +
      OSAS.icons.icon('plus', 13) + ' New Event</button>',
    resource: 'events',
    defaultState: { search: '', category: 'All', status: 'All', page: 1 },
    filters: [
      { id: 'ev-search', key: 'search', type: 'search', label: 'Search Activities',
        placeholder: 'Search by event title, venue or code…' },
      { id: 'ev-filter-category', key: 'category', type: 'select', label: 'Event Category',
        values: OPTIONS.eventCategories },
      { id: 'ev-filter-status', key: 'status', type: 'select', label: 'Status',
        values: ['Scheduled', 'Completed', 'Cancelled', 'Draft'] }
    ],
    sort: { field: 'startAt', dir: 'desc' },
    stats: statsHtml,
    toolbar: '<div class="table-toolbar"><div class="panel-title">Event &amp; Activity Calendar</div>' +
      '<span class="chip">' + U.esc(OSAS.store.meta().academicYear) + '</span></div>',
    filter: function (rows, state) {
      var next = U.search(rows, state.search, ['title', 'description', 'venue', 'category', 'code']);
      next = U.byStatus(next, state.status);
      if (state.category !== 'All') {
        next = next.filter(function (r) { return r.category === state.category; });
      }
      return next;
    },
    columns: function () {
      return [
        {
          label: 'Event &amp; Activity',
          render: function (row) {
            return '<div class="cell-title">' + U.esc(row.title) + '</div>' +
              '<div class="cell-sub mono">' + U.esc(row.code) + ' &middot; ' + U.esc(row.mode || 'On-campus') +
              '</div><div class="cell-sub">' + U.esc(U.truncate(row.description, 88)) + '</div>';
          }
        },
        { label: 'Category', render: function (row) { return '<span class="chip">' + U.esc(row.category) + '</span>'; } },
        {
          label: 'Schedule',
          render: function (row) {
            return '<div class="cell-strong">' + OSAS.fmt.range(row.startAt, row.endAt) + '</div>' +
              '<div class="cell-sub">' + OSAS.fmt.hoursAgo(row.startAt) + '</div>';
          }
        },
        {
          label: 'Venue',
          render: function (row) {
            return '<div class="cell-title">' + U.esc(row.venue) + '</div>' +
              '<div class="cell-sub">' + U.esc(row.audience) + '</div>';
          }
        },
        {
          label: 'Registrations',
          render: function (row) {
            return '<div class="cell-strong">' + OSAS.fmt.number(row.registered) + '</div>' +
              '<div class="cell-sub">students registered</div>';
          }
        },
        { label: 'Status', render: function (row) { return C.badge(row.status); } },
        {
          label: 'Actions',
          align: 'right',
          render: function (row) {
            return '<div class="cell-actions">' +
              '<button type="button" class="btn btn--primary btn--sm" data-action="edit" data-id="' +
              U.esc(row.id) + '">Edit</button>' +
              '<button type="button" class="btn btn--outline btn--sm" data-action="view" data-id="' +
              U.esc(row.id) + '">View</button></div>';
          }
        }
      ];
    },
    footerLabel: function (info) {
      return 'Showing ' + info.from + '–' + info.to + ' of ' + info.total + ' events & activities';
    },
    empty: 'No events match the current filters.',
    onAction: function (action, id) {
      var row = id ? OSAS.store.find('events', id) : null;
      if (action === 'new') {
        eventModal({ status: 'Scheduled', mode: 'On-campus', audience: 'All Students' }, 'new');
        return;
      }
      if (action === 'export') {
        U.exportCsv('beacon-events.csv', [
          { label: 'Event Code', value: 'code' },
          { label: 'Event Title', value: 'title' },
          { label: 'Category', value: 'category' },
          { label: 'Start', value: function (r) { return OSAS.fmt.dateTime(r.startAt); } },
          { label: 'End', value: function (r) { return OSAS.fmt.dateTime(r.endAt); } },
          { label: 'Venue', value: 'venue' },
          { label: 'Audience', value: 'audience' },
          { label: 'Registered', value: 'registered' },
          { label: 'Status', value: 'status' }
        ], page.rows());
        C.toast('success', 'Schedule exported', page.rows().length + ' activities written to CSV.');
        return;
      }
      if (!row) { return; }
      if (action === 'view') { previewModal(row); return; }
      if (action === 'edit') { eventModal(row, 'edit'); }
    }
  });
})();