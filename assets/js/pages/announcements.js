/* ==========================================================================
   Module 2 — Announcement & Information Broadcast Management
   ========================================================================== */
(function () {
  'use strict';
  window.addEventListener('error', function (e) { console.error('[Global Error]', e.message, e.filename, e.lineno); });
  var C = OSAS.components, U = OSAS.util;
  var shell = OSAS.shell.mount();
  if (!shell) { console.warn('[Announcements] Shell mount failed - auth required'); return; }
  var content = shell.content;
  var OPTIONS = OSAS.store.options();
  console.log('[Announcements] Module initialized, C.modal:', typeof C.modal, 'OSAS:', !!OSAS);

  var state = { search: '', category: 'All', status: 'All', audience: 'All', page: 1 };

  function filtered() {
    var rows = OSAS.store.all('announcements').slice();
    rows = U.search(rows, state.search, ['title', 'summary', 'category', 'audience', 'code']);
    rows = U.byStatus(rows, state.status);
    if (state.category !== 'All') {
      rows = rows.filter(function (r) { return r.category === state.category; });
    }
    if (state.audience !== 'All') {
      rows = rows.filter(function (r) { return r.audience === state.audience; });
    }
    return U.sortBy(rows, 'updatedAt', 'desc');
  }

  function selectOptions(values, selected) {
    return ['<option value="All">All</option>'].concat(values.map(function (value) {
      return '<option value="' + U.esc(value) + '"' + (value === selected ? ' selected' : '') + '>' +
        U.esc(value) + '</option>';
    })).join('');
  }

  /* ------------------------------------------------------------- toolbar */
  function toolbar() {
    return '<div class="ann-toolbar">' +
      '<div class="ann-toolbar__left">' +
      '<div class="panel-title">Announcement Directory &amp; Dispatch Control</div>' +
      '<div class="ann-sub">Manage official university broadcasts, emergency alerts, and scheduled notices.</div>' +
      '</div>' +
      '<div class="ann-toolbar__right">' +
      '<span class="input-group ann-search"><span class="input-group__ico">' + OSAS.icons.icon('search', 14) + '</span>' +
      '<input class="input" id="ann-search" placeholder="Search titles, tags, authors…" value="' + U.esc(state.search) + '"></span>' +
      '<button type="button" class="btn btn--outline btn--sm" id="ann-filter-btn">' + OSAS.icons.icon('filter', 12) + ' Filter Category</button>' +
      '<button type="button" class="btn btn--ghost btn--sm" id="ann-sort-btn">Sort: Recent</button>' +
      '</div></div>';
  }

  function filterBar() {
    return '<div class="ann-filters" id="ann-filters" style="display:none">' +
      '<div class="field"><label for="ann-category">Category</label>' +
      '<select class="input" id="ann-category">' + selectOptions(OPTIONS.announcementCategories, state.category) + '</select></div>' +
      '<div class="field"><label for="ann-status">Status</label>' +
      '<select class="input" id="ann-status">' + selectOptions(OSAS.store.options().statuses, state.status) + '</select></div>' +
      '<div class="field"><label for="ann-audience">Target Audience</label>' +
      '<select class="input" id="ann-audience">' + selectOptions(OPTIONS.audiences, state.audience) + '</select></div>' +
      '</div>';
  }

  /* ------------------------------------------------------------ table */
  function table(rows) {
    var page = U.paginate(rows, state.page, OSAS.CONFIG.pageSize);
    var columns = [
      {
        label: 'Announcement Details',
        render: function (row) {
          return '<div class="ann-title">' + U.esc(row.title) + '</div>' +
            '<div class="ann-meta">' + U.esc(row.summary) + '</div>' +
            '<div class="ann-ref">Ref: ' + U.esc(row.code) + '</div>';
        }
      },
      {
        label: 'Target Audience',
        render: function (row) {
          return '<div class="ann-audience">' + U.esc(row.audience) + '</div>' +
            '<div class="ann-aud-sub">' + U.esc(row.category) + '</div>';
        }
      },
      {
        label: 'Date Published',
        render: function (row) {
          var stamp = row.status === 'Scheduled' ? row.scheduledFor : row.updatedAt;
          return '<div class="ann-date">' + OSAS.fmt.shortDate(stamp) + '</div>' +
            '<div class="ann-time">' + (row.status === 'Scheduled' ? 'Releases ' : '') + OSAS.fmt.time(stamp) + '</div>';
        }
      },
      {
        label: 'Status',
        render: function (row) { return C.badge(row.status); }
      },
      {
        label: 'Actions',
        align: 'right',
        render: function (row) {
          return '<div class="cell-actions">' +
            '<button type="button" class="btn btn--primary btn--sm" data-action="edit" data-id="' + U.esc(row.id) + '">Edit</button>' +
            (row.status === 'Draft' ? '<button type="button" class="btn btn--danger btn--sm" data-action="delete" data-id="' + U.esc(row.id) + '">Drop</button>' : '') +
            '</div>';
        }
      }
    ];
    var footer = C.pager(page, 'Showing ' + page.from + '–' + page.to + ' of ' + page.total +
      ' active announcements');
    return C.dataTable({ columns: columns, rows: page.items, footer: footer });
  }

  /* ------------------------------------------------- Compose / edit dialog */
  function announcementFormHtml(row) {
    row = row || {};
    var isEdit = !!(row && row.id);
    var attachments = row.attachments || [];
    var attachmentHtml = attachments.length
      ? '<div class="ann-attach-list">' + attachments.map(function (att) {
          return '<div class="ann-attach-item">' +
            '<span class="ann-attach-badge">PDF</span>' +
            '<span class="ann-attach-name">' + U.esc(att.name) + '</span>' +
            '<span class="ann-attach-meta">' + U.esc(att.size || '') + ' · Uploaded Complete</span>' +
            '<button type="button" class="ann-attach-remove" data-remove="' + U.esc(att.name) + '">&times;</button>' +
            '</div>';
        }).join('') + '</div>'
      : '';

    return '<div id="ann-alert"></div>' +
      '<div class="ann-form-header">Announcement Content Editor</div>' +
      '<div class="ann-form-sub">Compose official details, upload attachments, and format announcement text</div>' +
      '<div class="form-section">' +
      '<div class="section-title"><span>1.</span> Broadcast Content</div>' +
      OSAS.forms.field('Announcement Title',
        OSAS.forms.textInput('ann-title', row.title, 'Enter the announcement title'),
        { id: 'ann-title', required: true }) +
      OSAS.forms.field('Short Summary / Brief',
        '<textarea class="input" id="ann-summary" rows="2" placeholder="Appears in push notifications and preview cards…">' +
        U.esc(row.summary || '') + '</textarea>',
        { id: 'ann-summary', required: true, hint: '(Appears in push notifications and preview cards)' }) +
      OSAS.forms.field('Body Content',
        C.richText({ id: 'ann-body', value: row.body || '', placeholder: 'Write the full announcement text…' }),
        { id: 'ann-body', required: true }) +
      '</div>' +
      '<div class="form-section">' +
      '<div class="section-title"><span>2.</span> Attachments &amp; Media <span class="ann-attach-hint-inline">(PDF, PNG, JPG files up to 15MB)</span></div>' +
      '<div class="ann-upload-zone" id="ann-upload-zone">' +
      '<input type="file" id="ann-file" accept=".pdf,.png,.jpg,.jpeg" multiple style="display:none">' +
      '<div class="ann-upload-inner">' +
      '<span class="ann-upload-icon">' + OSAS.icons.icon('upload', 20) + '</span>' +
      '<span class="ann-upload-text"><b>Click to Upload</b> or drag and drop files here</span>' +
      '</div></div>' +
      attachmentHtml +
      '</div>';
  }

  function announcementModal(record, mode) {
    var isEdit = mode === 'edit';
    var row = record || {};

    try {
      return C.modal({
      size: 'lg',
      title: isEdit ? 'Edit Announcement' : 'Create Announcement',
      subtitle: isEdit
        ? 'Update the content, schedule and audience of this announcement.'
        : 'Create and publish an official university announcement for the student portal.',
      body: announcementFormHtml(row),
      footer: '<button type="button" class="btn btn--ghost" data-close>Cancel</button>' +
        '<button type="button" class="btn btn--outline" id="ann-save-draft">Add to Drafts</button>' +
        '<button type="button" class="btn btn--success" id="ann-save">' +
        (isEdit ? 'Save Changes' : 'Create new') + '</button>',
      onMount: function (el, close) {
        C.initRichText(el);
        var alertHost = el.querySelector('#ann-alert');
        var attachments = (row.attachments || []).slice();

        // --- File upload handling ---
        var fileInput = el.querySelector('#ann-file');
        var uploadZone = el.querySelector('#ann-upload-zone');

        function renderAttachments() {
          var host = el.querySelector('.ann-attach-list');
          if (!host) {
            host = document.createElement('div');
            host.className = 'ann-attach-list';
            uploadZone.parentNode.insertBefore(host, uploadZone.nextSibling);
          }
          if (!attachments.length) { host.innerHTML = ''; return; }
          host.innerHTML = attachments.map(function (att) {
            return '<div class="ann-attach-item">' +
              '<span class="ann-attach-badge">PDF</span>' +
              '<span class="ann-attach-name">' + U.esc(att.name) + '</span>' +
              '<span class="ann-attach-meta">' + U.esc(att.size || '') + ' · Uploaded Complete</span>' +
              '<button type="button" class="ann-attach-remove" data-remove="' + U.esc(att.name) + '">&times;</button>' +
              '</div>';
          }).join('');
        }
        renderAttachments();

        uploadZone.addEventListener('click', function () { fileInput.click(); });
        uploadZone.addEventListener('dragover', function (e) { e.preventDefault(); uploadZone.classList.add('ann-upload-zone--over'); });
        uploadZone.addEventListener('dragleave', function () { uploadZone.classList.remove('ann-upload-zone--over'); });
        uploadZone.addEventListener('drop', function (e) {
          e.preventDefault();
          uploadZone.classList.remove('ann-upload-zone--over');
          handleFiles(e.dataTransfer.files);
        });
        fileInput.addEventListener('change', function () { handleFiles(fileInput.files); });

        function handleFiles(files) {
          Array.prototype.forEach.call(files, function (file) {
            var ext = (file.name.split('.').pop() || '').toLowerCase();
            if (!['pdf', 'png', 'jpg', 'jpeg'].includes(ext)) {
              alertHost.innerHTML = C.alert('danger', 'Invalid file type', 'Only PDF, PNG, or JPG files are allowed.');
              return;
            }
            if (file.size > 15 * 1024 * 1024) {
              alertHost.innerHTML = C.alert('danger', 'File too large', 'Each file must be 15 MB or smaller.');
              return;
            }
            var sizeStr = file.size > 1024 * 1024
              ? (file.size / (1024 * 1024)).toFixed(1) + ' MB'
              : Math.round(file.size / 1024) + ' KB';
            attachments.push({ name: file.name, size: sizeStr });
          });
          renderAttachments();
        }

        el.addEventListener('click', function (e) {
          var btn = e.target.closest('.ann-attach-remove');
          if (!btn) { return; }
          var name = btn.getAttribute('data-remove');
          attachments = attachments.filter(function (a) { return a.name !== name; });
          renderAttachments();
        });

        function save(forcedStatus) {
          var title = OSAS.forms.value('ann-title');
          var summary = OSAS.forms.value('ann-summary');
          var bodyHtml = C.richTextValue('ann-body');
          var errors = [];

          if (!title) { errors.push('An announcement title is required.'); }
          if (!summary) { errors.push('A short summary is required.'); }
          if (!U.stripTags(bodyHtml)) { errors.push('The announcement body cannot be empty.'); }

          if (errors.length) {
            alertHost.innerHTML = C.alert('danger', 'Unable to save the announcement', errors.join(' '));
            return;
          }

          var status = forcedStatus || 'Published';
          var payload = {
            title: title, summary: summary, body: bodyHtml,
            status: status, updatedAt: U.now(),
            publishedAt: status === 'Published' ? U.now() : (row.publishedAt || ''),
            attachments: attachments
          };

          var saved;
          if (isEdit) {
            saved = OSAS.store.update('announcements', row.id, payload);
          } else {
            payload.id = OSAS.store.nextId('announcements', 'ANN-2026', 3);
            payload.code = payload.id;
            payload.views = 0;
            payload.author = 'OSAS Administration';
            payload.category = 'General';
            payload.audience = 'All Students';
            payload.priority = 'Standard Priority';
            saved = OSAS.store.insert('announcements', payload);
          }

          OSAS.store.logActivity({
            tone: 'brand', icon: 'megaphone',
            title: isEdit ? 'Announcement Updated' : 'Announcement Created',
            text: saved.code + ' — "' + saved.title + '" ' +
              (status === 'Draft' ? 'saved as draft.' : 'published to ' + (saved.audience || 'all portals') + '.')
          });
          alertHost.innerHTML = C.alert('success',
            status === 'Draft' ? 'Draft Saved!' : (isEdit ? 'Announcement Updated!' : 'Announcement Published!'),
            '<b>' + U.esc(saved.title) + '</b> — ' + (status === 'Draft'
              ? 'saved to drafts for later review.'
              : 'is now visible to ' + U.esc(saved.audience || 'all portals')) + '.');
          el.querySelector('.modal__foot').innerHTML =
            '<button type="button" class="btn btn--primary" data-close>Done</button>';
          C.toast('success', status === 'Draft' ? 'Draft saved' : 'Announcement saved', saved.title);
          render();
        }

        el.querySelector('#ann-save').addEventListener('click', function () { save(); });
        el.querySelector('#ann-save-draft').addEventListener('click', function () { save('Draft'); });
      }
    });
  } catch (err) {
    console.error('[Announcements] Modal creation error:', err);
    C.toast('danger', 'Modal Error', 'Error: ' + (err.message || err));
  }
}

  /* --------------------------------------------------- Read-only preview */
  function previewModal(row) {
    C.modal({
      size: 'lg',
      title: U.esc(row.title),
      subtitle: U.esc(row.code) + ' · ' + U.esc(row.category) + ' · ' + U.esc(row.author),
      body: C.statStrip([
        { label: 'Status', value: C.badge(row.status) },
        { label: 'Target Audience', value: U.esc(row.audience) },
        { label: 'Portal Views', value: OSAS.fmt.number(row.views) },
        { label: 'Published', value: OSAS.fmt.shortDate(row.publishedAt || row.scheduledFor || row.updatedAt) }
      ]) +
        C.alert('info', U.esc(row.priority), U.esc(row.summary)) +
        '<div class="rt"><div class="rt__area" style="max-height:none">' + (row.body || '') + '</div></div>',
      footer: '<button type="button" class="btn btn--ghost" data-close>Close</button>' +
        '<button type="button" class="btn btn--primary" id="preview-edit">Edit Announcement</button>',
      onMount: function (el, close) {
        el.querySelector('#preview-edit').addEventListener('click', function () {
          close();
          announcementModal(row, 'edit');
        });
      }
    });
  }

  function exportCsv() {
    var rows = filtered();
    U.exportCsv('beacon-announcements-' + OSAS.util.slug(OSAS.SEED_NOW.slice(0, 10)) + '.csv', [
      { label: 'Reference Code', value: 'code' },
      { label: 'Announcement Title', value: 'title' },
      { label: 'Category', value: 'category' },
      { label: 'Target Audience', value: 'audience' },
      { label: 'Status', value: 'status' },
      { label: 'Priority', value: 'priority' },
      { label: 'Date', value: function (r) { return OSAS.fmt.date(r.publishedAt || r.scheduledFor || r.updatedAt); } },
      { label: 'Portal Views', value: 'views' }
    ], rows);
    C.toast('success', 'Announcement list exported', rows.length + ' records written to CSV.');
  }

  function render() {
    content.innerHTML = OSAS.shell.pageHead({
      title: 'Announcement & Information Broadcast Management',
      subtitle: 'Create, publish, and organize university-wide announcements and information notices across the student portal.',
      actions: '<button type="button" class="btn btn--ghost" data-action="export">' +
        OSAS.icons.icon('download', 13) + ' Export List</button>' +
        '<button type="button" class="btn btn--primary" data-action="compose">' +
        OSAS.icons.icon('plus', 13) + ' Create Announcement</button>'
    }) + toolbar() + filterBar() + table(filtered());
  }

  /* ---------------- one-time delegated bindings (survive re-renders) ----- */
  var FILTER_KEYS = { 'ann-category': 'category', 'ann-status': 'status', 'ann-audience': 'audience' };

  content.addEventListener('input', function (event) {
    if (event.target.id !== 'ann-search') { return; }
    state.search = event.target.value;
    state.page = 1;
    render();
    var box = document.getElementById('ann-search');
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
    try {
      var trigger = event.target.closest ? event.target.closest('[data-action]') : null;
      if (!trigger) { return; }
      var action = trigger.getAttribute('data-action');
      var id = trigger.getAttribute('data-id');
      console.log('[Announcements] Action:', action, id);

      if (action === 'compose') { announcementModal(null, 'compose'); return; }
      if (action === 'export') { exportCsv(); return; }
      if (action === 'view') {
        var row = OSAS.store.find('announcements', id);
        if (row) { previewModal(row); }
        return;
      }
      if (action === 'edit') {
        var editRow = OSAS.store.find('announcements', id);
        if (editRow) { announcementModal(editRow, 'edit'); }
        return;
      }
      if (action === 'delete') {
        var delRow = OSAS.store.find('announcements', id);
        if (!delRow) { return; }
        C.confirm({
          title: 'Delete this draft?',
          subtitle: U.esc(delRow.title),
          alertTone: 'warning',
          alertTitle: 'The draft will be permanently removed',
          alertText: 'This action cannot be undone.',
          confirmLabel: 'Delete',
          confirmClass: 'btn--danger',
          onConfirm: function () {
            OSAS.store.remove('announcements', id);
            C.toast('info', 'Draft deleted', delRow.title);
            render();
          }
        });
        return;
      }
      if (action === 'duplicate') {
        var source = OSAS.store.find('announcements', id);
        if (!source) { return; }
        var code = OSAS.store.nextId('announcements', 'ANN-2026', 3);
        OSAS.store.insert('announcements', {
          id: code, code: code, title: source.title + ' (Copy)', summary: source.summary,
          body: source.body, category: source.category, audience: source.audience,
          priority: source.priority, status: 'Draft', views: 0, author: source.author,
          publishedAt: '', scheduledFor: '', updatedAt: U.now()
        });
        OSAS.store.logActivity({
          tone: 'info', icon: 'megaphone', title: 'Announcement Duplicated',
          text: code + ' was created as a draft copy of "' + source.title + '".'
        });
        C.toast('info', 'Draft copy created', code + ' is ready for editing.');
        render();
      }
    } catch (err) {
      console.error('[Announcements] Click handler error:', err);
      C.toast('danger', 'Error', 'Error: ' + (err.message || err));
    }
  });

  // Filter toggle
  content.addEventListener('click', function (event) {
    if (event.target.closest('#ann-filter-btn')) {
      var bar = document.getElementById('ann-filters');
      if (bar) { bar.style.display = bar.style.display === 'none' ? 'flex' : 'none'; }
    }
  });

  render();
})();
