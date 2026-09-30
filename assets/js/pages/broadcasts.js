/* ==========================================================================
   Module 8 — Automated Broadcasts & Student Alerts (Notification Hub)
   ========================================================================== */
(function () {
  'use strict';
  var C = OSAS.components, U = OSAS.util;
  var shell = OSAS.shell.mount();
  if (!shell) { return; }
  var content = shell.content;
  var OPTIONS = OSAS.store.options();

  var state = { search: '', channel: 'All', tab: 'All', page: 1 };

  function filtered() {
    var rows = OSAS.store.all('broadcasts').slice();
    rows = U.search(rows, state.search, ['subject', 'message', 'code', 'audience']);
    if (state.channel !== 'All') {
      rows = rows.filter(function (r) { return r.channel === state.channel; });
    }
    if (state.tab === 'Sent') { rows = rows.filter(function (r) { return r.status === 'Sent'; }); }
    if (state.tab === 'Scheduled') { rows = rows.filter(function (r) { return r.status === 'Scheduled'; }); }
    if (state.tab === 'Urgent') { rows = rows.filter(function (r) { return r.priority === 'Urgent' || r.priority === 'High'; }); }
    if (state.tab === 'Draft') { rows = rows.filter(function (r) { return r.status === 'Draft' || r.status === 'Pending'; }); }
    return U.sortBy(rows, 'at', 'desc');
  }

  /* ------------------------------------------------------------- toolbar */
  function toolbar() {
    return '<div class="bc-toolbar">' +
      '<div class="bc-toolbar__left">' +
      '<div class="panel-title">Notification &amp; Broadcast Ledger</div>' +
      '<div class="bc-sub">Configure automated broadcast alerts and reminders for university-wide announcement communications.</div>' +
      '</div>' +
      '<div class="bc-toolbar__right">' +
      '<span class="input-group bc-search"><span class="input-group__ico">' + OSAS.icons.icon('search', 14) + '</span>' +
      '<input class="input" id="bc-search" placeholder="Search notification topic…" value="' + U.esc(state.search) + '"></span>' +
      '<span class="input-group bc-channel"><span class="input-group__ico">' + OSAS.icons.icon('broadcast', 14) + '</span>' +
      '<select class="input" id="bc-channel">' +
      ['All'].concat(OPTIONS.channels).map(function (ch) {
        return '<option value="' + U.esc(ch) + '"' + (ch === state.channel ? ' selected' : '') + '>' + U.esc(ch) + '</option>';
      }).join('') + '</select></span>' +
      '</div></div>';
  }

  function tabStrip() {
    var counts = OSAS.analytics.broadcasts(OSAS.store.all('broadcasts'));
    var tabs = [
      { key: 'All', label: 'All Alerts (' + counts.total + ')' },
      { key: 'Sent', label: 'Sent Logs' },
      { key: 'Scheduled', label: 'Scheduled (' + counts.scheduled + ')' },
      { key: 'Urgent', label: 'Urgent Broadcasts' }
    ];
    return '<div class="toolbar"><div class="tabs">' + tabs.map(function (tab) {
      return '<button type="button" class="tab' + (state.tab === tab.key ? ' tab--on' : '') +
        '" data-action="tab" data-id="' + tab.key + '">' + tab.label + '</button>';
    }).join('') + '</div></div>';
  }

  /* ------------------------------------------------------------ table */
  function table(rows) {
    var page = U.paginate(rows, state.page, OSAS.CONFIG.pageSize);
    var columns = [
      {
        label: 'Notification Subject &amp; Message',
        render: function (row) {
          return '<div class="bc-subject">' + U.esc(row.subject) + '</div>' +
            '<div class="bc-msg">' + U.esc(U.truncate(row.message, 120)) + '</div>';
        }
      },
      { label: 'Status', render: function (row) { return C.badge(row.status); } },
      {
        label: 'Sent / Scheduled',
        render: function (row) {
          return '<div class="bc-date">' + OSAS.fmt.shortDate(row.at) + '</div>' +
            '<div class="bc-time">' + (row.status === 'Sent' ? 'at ' : '') + OSAS.fmt.time(row.at) + '</div>';
        }
      },
      {
        label: 'Actions',
        align: 'right',
        render: function (row) {
          return '<div class="cell-actions">' +
            '<button type="button" class="btn btn--brand btn--sm" data-action="send" data-id="' + U.esc(row.id) + '">Send Notification</button>' +
            '</div>';
        }
      }
    ];
    var footer = C.pager(page, 'Showing ' + page.from + '–' + page.to + ' of ' + page.total + ' broadcast alerts');
    return C.dataTable({ columns: columns, rows: page.items, footer: footer });
  }

  /* -------------------------------------------------- compose alert modal */
  function broadcastModal() {
    C.modal({
      size: 'lg',
      title: 'Create Broadcast Alert',
      subtitle: 'Compose an automated student alert or reminder for the Notification Hub.',
      body: '<div id="bc-alert"></div>' +
        '<div class="form-section">' +
        '<div class="section-title"><span>1.</span> Message</div>' +
        OSAS.forms.field('Subject',
          OSAS.forms.textInput('bc-subject', '', 'e.g. Reminder: Mandatory Orientation Completion Deadline'),
          { id: 'bc-subject', required: true }) +
        OSAS.forms.field('Message Body',
          '<textarea class="input" id="bc-message" rows="4" placeholder="Write the alert message shown to students…"></textarea>',
          { id: 'bc-message', required: true }) +
        '<div class="form-grid form-grid--2">' +
        OSAS.forms.field('Channel', OSAS.forms.selectInput('bc-channel', OPTIONS.channels, 'Portal + Email', 'Select a channel')) +
        OSAS.forms.field('Target Audience', OSAS.forms.selectInput('bc-audience', OPTIONS.audiences, 'All Student Portals', 'Select an audience')) +
        '</div></div>' +
        '<div class="form-section">' +
        '<div class="section-title"><span>2.</span> Release</div>' +
        '<div class="form-grid form-grid--3">' +
        OSAS.forms.field('Release Mode',
          OSAS.forms.selectInput('bc-mode', ['Send Now', 'Schedule Release', 'Save as Draft'], 'Save as Draft', 'Select a mode')) +
        OSAS.forms.field('Release Date', '<input class="input" type="date" id="bc-date" value="' + OSAS.SEED_NOW.slice(0, 10) + '">') +
        OSAS.forms.field('Release Time', '<input class="input" type="time" id="bc-time" value="08:00">') +
        '</div>' +
        C.alert('info', 'Delivery estimate',
          'Portal notifications are delivered instantly; university email and SMS alerts are dispatched within 5 minutes of release.') +
        '</div>',
      footer: '<button type="button" class="btn btn--ghost" data-close>Cancel</button>' +
        '<button type="button" class="btn btn--primary" id="bc-save">Create Broadcast</button>',
      onMount: function (el, close) {
        var alertHost = el.querySelector('#bc-alert');
        el.querySelector('#bc-save').addEventListener('click', function () {
          var subject = OSAS.forms.value('bc-subject');
          var message = OSAS.forms.value('bc-message');
          if (!subject || !message) {
            alertHost.innerHTML = C.alert('danger', 'Unable to create the broadcast',
              'Both a subject and a message body are required.');
            return;
          }
          var mode = el.querySelector('#bc-mode').value;
          var status = mode === 'Send Now' ? 'Sent' : mode === 'Schedule Release' ? 'Scheduled' : 'Draft';
          var stamp = el.querySelector('#bc-date').value + 'T' +
            (el.querySelector('#bc-time').value || '08:00') + ':00';
          var audience = el.querySelector('#bc-audience').value || 'All Student Portals';
          var reach = audience === 'All Student Portals' ? 13980 : 1240;
          var id = OSAS.store.nextId('broadcasts', 'NOTIF-2026', 3);
          var record = {
            id: id, code: id, subject: subject, message: message,
            channel: el.querySelector('#bc-channel').value || 'Portal + Email',
            status: status, at: status === 'Sent' ? U.now() : stamp,
            audience: audience, reach: status === 'Sent' ? reach : 0,
            delivered: status === 'Sent' ? reach : 0,
            opened: status === 'Sent' ? Math.round(reach * 0.68) : 0
          };
          OSAS.store.insert('broadcasts', record);
          OSAS.store.logActivity({
            tone: status === 'Sent' ? 'brand' : status === 'Scheduled' ? 'blue' : 'amber',
            icon: 'broadcast',
            title: status === 'Sent' ? 'Broadcast Delivered'
              : status === 'Scheduled' ? 'Broadcast Scheduled' : 'Broadcast Drafted',
            text: id + ' — "' + subject + '" ' + (status === 'Sent'
              ? 'was delivered to ' + audience + '.'
              : 'is queued for ' + OSAS.fmt.dateTime(stamp) + '.')
          });
          alertHost.innerHTML = C.alert('success', 'Broadcast Created!',
            '<b>' + U.esc(subject) + '</b> is now <b>' + status + '</b> for ' + U.esc(audience) + '.');
          el.querySelector('.modal__foot').innerHTML =
            '<button type="button" class="btn btn--primary" data-close>Done</button>';
          C.toast('success', 'Broadcast created', id + ' — ' + status);
          close();
          render();
        });
      }
    });
  }

  function previewModal(row) {
    C.modal({
      size: 'md',
      title: U.esc(row.subject),
      subtitle: U.esc(row.code) + ' · ' + U.esc(row.channel) + ' · ' + U.esc(row.audience),
      body: C.statStrip([
        { label: 'Status', value: C.badge(row.status) },
        { label: 'Release', value: OSAS.fmt.dateTime(row.at) },
        { label: 'Delivered', value: OSAS.fmt.number(row.delivered) },
        { label: 'Opened', value: OSAS.fmt.number(row.opened) }
      ]) +
        '<div class="tile tile--tint"><span class="tile__ico">' + OSAS.icons.icon('broadcast', 15) + '</span>' +
        '<div><div class="tile__title">Message Body</div>' +
        '<div class="tile__text">' + U.esc(row.message) + '</div></div></div>' +
        (row.delivered ? C.progressBar(
          Math.round((row.opened / row.delivered) * 100),
          Math.round((row.opened / row.delivered) * 100) >= 60 ? 'green' : 'amber') +
          '<div class="hint">Open rate: ' + OSAS.fmt.number(row.opened) + ' of ' +
          OSAS.fmt.number(row.delivered) + ' delivered messages</div>' : ''),
      footer: '<button type="button" class="btn btn--ghost" data-close>Close</button>' +
        (row.status !== 'Sent'
          ? '<button type="button" class="btn btn--primary" id="bc-send">Send Notification</button>' : ''),
      onMount: function (el, close) {
        var send = el.querySelector('#bc-send');
        if (send) {
          send.addEventListener('click', function () {
            close();
            sendBroadcast(row.id);
          });
        }
      }
    });
  }

  function sendBroadcast(id) {
    var row = OSAS.store.find('broadcasts', id);
    if (!row) { return; }
    C.confirm({
      title: 'Send this notification now?',
      subtitle: row.code + ' · ' + row.subject,
      alertTone: 'info',
      alertTitle: 'Delivery channels: ' + row.channel,
      alertText: 'This alert will be delivered to ' + row.audience +
        '. The action is recorded in the system event log.',
      confirmLabel: 'Send Notification',
      confirmClass: 'btn--primary',
      onConfirm: function () {
        var reach = row.audience === 'All Student Portals' ? 13980 : 1240;
        OSAS.store.update('broadcasts', row.id, {
          status: 'Sent', at: U.now(), reach: reach, delivered: reach, opened: Math.round(reach * 0.68)
        });
        OSAS.store.logActivity({
          tone: 'brand', icon: 'send', title: 'Broadcast Delivered #' + row.code,
          text: '"' + U.truncate(row.subject, 60) + '" was delivered to ' + row.audience +
            ' through ' + row.channel + '.'
        });
        C.toast('success', 'Notification sent', row.code + ' — ' + row.subject);
        render();
      }
    });
  }

  function exportCsv() {
    var rows = filtered();
    U.exportCsv('beacon-broadcast-logs.csv', [
      { label: 'Reference', value: 'code' },
      { label: 'Subject', value: 'subject' },
      { label: 'Message', value: 'message' },
      { label: 'Channel', value: 'channel' },
      { label: 'Audience', value: 'audience' },
      { label: 'Status', value: 'status' },
      { label: 'Sent / Scheduled', value: function (r) { return OSAS.fmt.dateTime(r.at); } },
      { label: 'Delivered', value: 'delivered' },
      { label: 'Opened', value: 'opened' }
    ], rows);
    C.toast('success', 'Broadcast logs exported', rows.length + ' records written to CSV.');
  }

  function render() {
    content.innerHTML = OSAS.shell.pageHead({
      title: 'Automated Broadcasts & Student Alerts',
      subtitle: 'Configure automated broadcast alerts and reminders for university-wide announcement communications.',
      actions: '<button type="button" class="btn btn--ghost" data-action="export">' +
        OSAS.icons.icon('download', 13) + ' Export Logs</button>' +
        '<button type="button" class="btn btn--primary" data-action="new">' +
        OSAS.icons.icon('send', 13) + ' New Broadcast</button>'
    }) + toolbar() + tabStrip() + table(filtered());
  }

  /* ---------------- one-time delegated bindings (survive re-renders) ----- */
  content.addEventListener('input', function (event) {
    if (event.target.id !== 'bc-search') { return; }
    state.search = event.target.value;
    state.page = 1;
    render();
    var box = document.getElementById('bc-search');
    if (box) {
      box.focus();
      box.setSelectionRange(box.value.length, box.value.length);
    }
  });

  content.addEventListener('change', function (event) {
    if (event.target.id !== 'bc-channel') { return; }
    state.channel = event.target.value;
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
    if (action === 'new') { broadcastModal(); return; }
    if (action === 'export') { exportCsv(); return; }
    if (action === 'view') {
      var row = OSAS.store.find('broadcasts', id);
      if (row) { previewModal(row); }
      return;
    }
    if (action === 'send') { sendBroadcast(id); }
  });

  render();
})();
