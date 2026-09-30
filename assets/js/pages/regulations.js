/* ==========================================================================
   Module 3 — Student Code of Conduct & School Regulations
   ========================================================================== */
(function () {
  'use strict';
  window.addEventListener('error', function (e) { console.error('[Global Error]', e.message, e.filename, e.lineno); });
  var C = OSAS.components, U = OSAS.util;
  var shell = OSAS.shell.mount();
  if (!shell) { console.warn('[Regulations] Shell mount failed - auth required'); return; }
  var content = shell.content;
  var OPTIONS = OSAS.store.options();
  console.log('[Regulations] Module initialized, C.modal:', typeof C.modal, 'OSAS:', !!OSAS);

  var state = { search: '', category: 'All', status: 'All', tab: 'all', page: 1 };

  function filtered() {
    var rows = OSAS.store.all('regulations').slice();
    rows = U.search(rows, state.search, ['title', 'summary', 'category', 'referenceCode', 'code']);
    rows = U.byStatus(rows, state.status);
    if (state.category !== 'All') {
      rows = rows.filter(function (r) { return r.category === state.category; });
    }
    if (state.tab === 'academic') { rows = rows.filter(function (r) { return r.category === 'Academic Conduct'; }); }
    if (state.tab === 'campus') { rows = rows.filter(function (r) { return r.category === 'Campus Discipline'; }); }
    if (state.tab === 'orgs') { rows = rows.filter(function (r) { return r.category === 'Orgs & Activities'; }); }
    return U.sortBy(rows, 'updatedAt', 'desc');
  }

  /* ------------------------------------------------------------- toolbar */
  function toolbar() {
    return '<div class="reg-toolbar">' +
      '<div class="reg-toolbar__left">' +
      '<div class="panel-title">Policy &amp; Regulation Register</div>' +
      '<div class="reg-sub">Maintain and communicate university policies, the student code of conduct, and mandated regulations to all enrolled students.</div>' +
      '</div>' +
      '<div class="reg-toolbar__right">' +
      '<span class="input-group reg-search"><span class="input-group__ico">' + OSAS.icons.icon('search', 14) + '</span>' +
      '<input class="input" id="reg-search" placeholder="Search by rule title or code…" value="' + U.esc(state.search) + '"></span>' +
      '<span class="input-group reg-filter"><span class="input-group__ico">' + OSAS.icons.icon('filter', 14) + '</span>' +
      '<select class="input" id="reg-category">' +
      ['All'].concat(OPTIONS.regulationCategories || []).map(function (cat) {
        return '<option value="' + U.esc(cat) + '"' + (cat === state.category ? ' selected' : '') + '>' + U.esc(cat) + '</option>';
      }).join('') + '</select></span>' +
      '</div></div>';
  }

  function tabStrip() {
    var tabs = [
      { key: 'all', label: 'All Policies' },
      { key: 'academic', label: 'Academic Conduct' },
      { key: 'campus', label: 'Campus Discipline' },
      { key: 'orgs', label: 'Orgs & Activities' }
    ];
    return '<div class="toolbar"><div class="tabs">' + tabs.map(function (tab) {
      return '<button type="button" class="tab' + (state.tab === tab.key ? ' tab--on' : '') +
        '" data-action="tab" data-id="' + tab.key + '">' + tab.label + '</button>';
    }).join('') + '</div></div>';
  }

  /* ------------------------------------------------------------ table */
  function statusBadge(row) {
    var status = row.status;
    if (status === 'Published') { return '<span class="reg-status reg-status--enforced">Enforced</span>'; }
    if (status === 'Pending Review') { return '<span class="reg-status reg-status--review">Under Review</span>'; }
    if (status === 'Draft') { return '<span class="reg-status reg-status--unforced">Unforced</span>'; }
    return '<span class="reg-status reg-status--archived">Archived</span>';
  }

  function table(rows) {
    var page = U.paginate(rows, state.page, OSAS.CONFIG.pageSize);
    var columns = [
      {
        label: 'Code',
        render: function (row) {
          return '<div class="reg-code">' + U.esc(row.code) + '</div>';
        }
      },
      {
        label: 'Regulation Title &amp; Description',
        render: function (row) {
          return '<div class="reg-title">' + U.esc(row.title) + '</div>' +
            '<div class="reg-desc">' + U.esc(U.truncate(row.summary, 120)) + '</div>';
        }
      },
      { label: 'Category', render: function (row) { return '<span class="reg-cat">' + U.esc(row.category) + '</span>'; } },
      { label: 'Status', render: function (row) { return statusBadge(row); } },
      {
        label: 'Last Revised',
        render: function (row) {
          return '<div class="reg-date">' + OSAS.fmt.shortDate(row.updatedAt) + '</div>' +
            '<div class="reg-by">by ' + U.esc(row.revisedBy || 'OSAS Council') + '</div>';
        }
      },
      {
        label: 'Actions',
        align: 'right',
        render: function (row) {
          return '<div class="cell-actions">' +
            '<button type="button" class="btn btn--brand btn--sm" data-action="edit" data-id="' + U.esc(row.id) + '">Edit</button>' +
            '<button type="button" class="btn btn--ghost btn--sm" data-action="more" data-id="' + U.esc(row.id) + '">···</button>' +
            '</div>';
        }
      }
    ];
    var footer = C.pager(page, 'Page ' + page.current + ' of ' + page.pages + ' (' + page.total + ' rules total)');
    return C.dataTable({ columns: columns, rows: page.items, footer: footer });
  }

  /* -------------------------------------------------------- policy editor */
  function regulationFormHtml(row) {
    row = row || {};
    var isEdit = !!(row && row.id);
    var nextCode = OSAS.store.nextId('regulations', 'RULE-2026-', 3);
    return '<div id="reg-alert"></div>' +
      '<div class="form-section">' +
      '<div class="section-title"><span>1.</span> General Policy Identification</div>' +
      '<div class="form-grid form-grid--3">' +
      OSAS.forms.field('Policy Code / Reference',
        OSAS.forms.textInput('reg-reference', row.referenceCode || nextCode, 'RULE-2026-000'),
        { id: 'reg-reference', required: true }) +
      OSAS.forms.field('Primary Category',
        OSAS.forms.selectInput('reg-category-select', OPTIONS.regulationCategories, row.category, 'Select a category'),
        { id: 'reg-category-select', required: true }) +
      OSAS.forms.field('Offense Severity Rating',
        OSAS.forms.selectInput('reg-severity', ['Minor Offense (Level 1)', 'Major Offense (Level 2)', 'Grave Offense (Level 3)'],
          row.severity || 'Major Offense (Level 2)', 'Select severity'),
        { id: 'reg-severity', required: true }) +
      '</div>' +
      OSAS.forms.field('Full Regulation Title',
        OSAS.forms.textInput('reg-title', row.title, 'Enter the full regulation title'),
        { id: 'reg-title', required: true }) +
      OSAS.forms.field('Executive Summary',
        '<textarea class="input" id="reg-summary" rows="2" placeholder="Appears on mobile portal preview…">' +
        U.esc(row.summary || '') + '</textarea>',
        { id: 'reg-summary', required: true, hint: '(Appears on mobile portal preview)' }) +
      '</div>' +
      '<div class="form-section">' +
      '<div class="section-title"><span>2.</span> Regulation Provisions &amp; Text Editor</div>' +
      '<div class="reg-editor-label">WYSIWYG Mode</div>' +
      OSAS.forms.field('',
        C.richText({ id: 'reg-body', value: row.body || '', placeholder: 'Draft official sections, sub-clauses, and specific penal consequences.' }),
        { id: 'reg-body', required: true }) +
      '</div>';
  }

  function regulationModal(row, mode) {
    var isEdit = mode === 'edit';
    try {
      C.modal({
      size: 'lg',
      title: isEdit ? 'Edit Regulation' : 'New Regulation',
      subtitle: isEdit
        ? 'Update the provisions of this policy. A revision notice is sent to students on release.'
        : 'Register a new university policy or regulation and publish it to the student portal.',
      body: regulationFormHtml(row),
      footer: '<button type="button" class="btn btn--ghost" data-close>Cancel</button>' +
        '<button type="button" class="btn btn--outline" id="reg-save-draft">Save Draft</button>' +
        '<button type="button" class="btn btn--brand" id="reg-save">' +
        OSAS.icons.icon('plus', 12) + ' Publish Policy</button>',
      onMount: function (el, close) {
        C.initRichText(el);
        var alertHost = el.querySelector('#reg-alert');

        function save(status) {
          var title = OSAS.forms.value('reg-title');
          var categoryValue = OSAS.forms.value('reg-category-select');
          var summary = OSAS.forms.value('reg-summary');
          var bodyHtml = C.richTextValue('reg-body');
          var errors = [];
          if (!title) { errors.push('A policy title is required.'); }
          if (!categoryValue) { errors.push('Select the policy category.'); }
          if (!summary) { errors.push('A policy summary is required.'); }
          if (!U.stripTags(bodyHtml)) { errors.push('The policy text cannot be empty.'); }
          if (errors.length) {
            alertHost.innerHTML = C.alert('danger', 'Unable to save the regulation', errors.join(' '));
            return;
          }

          var payload = {
            title: title, category: categoryValue, summary: summary, body: bodyHtml,
            referenceCode: OSAS.forms.value('reg-reference') || (row.referenceCode || 'RULE-2026-NEW'),
            severity: el.querySelector('#reg-severity').value || 'Major Offense (Level 2)',
            status: status, updatedAt: U.now(),
            revisedBy: 'OSAS Council'
          };

          var saved;
          if (isEdit) {
            saved = OSAS.store.update('regulations', row.id, payload);
          } else {
            payload.id = OSAS.store.nextId('regulations', 'REG-2026', 3);
            payload.code = payload.id;
            payload.views = 0;
            saved = OSAS.store.insert('regulations', payload);
          }

          OSAS.store.logActivity({
            tone: status === 'Published' ? 'brand' : 'amber', icon: 'scale',
            title: status === 'Published' ? 'Regulation Published' : 'Regulation Saved',
            text: saved.code + ' — "' + saved.title + '" is now ' + status.toLowerCase() +
              ' (' + saved.category + ').'
          });
          alertHost.innerHTML = C.alert('success',
            status === 'Published' ? 'Regulation Published!' : 'Regulation Saved',
            '<b>' + U.esc(saved.title) + '</b> — ' + (status === 'Published'
              ? 'effective ' + U.esc(saved.effectiveDate) + ' and visible to ' + U.esc(saved.audience) + '.'
              : 'stored as ' + status.toLowerCase() + ' pending final approval.'));
          el.querySelector('.modal__foot').innerHTML =
            '<button type="button" class="btn btn--primary" data-close>Done</button>';
          C.toast('success', status === 'Published' ? 'Regulation published' : 'Regulation saved', saved.title);
          close();
          render();
        }

        el.querySelector('#reg-save').addEventListener('click', function () { save('Published'); });
        el.querySelector('#reg-save-draft').addEventListener('click', function () {
          save(isEdit ? (row.status === 'Published' ? 'Published' : 'Draft') : 'Draft');
        });
      }
    });
  } catch (err) {
    console.error('[Regulations] Modal creation error:', err);
    C.toast('danger', 'Modal Error', 'Error: ' + (err.message || err));
  }
}

  function previewModal(row) {
    C.modal({
      size: 'lg',
      title: U.esc(row.title),
      subtitle: U.esc(row.code) + ' · Register no. ' + U.esc(row.referenceCode || '—') +
        ' · ' + U.esc(row.approvalBody || ''),
      body: C.statStrip([
        { label: 'Status', value: C.badge(row.status) },
        { label: 'Category', value: U.esc(row.category) },
        { label: 'Effective Date', value: U.esc(row.effectiveDate || '—') },
        { label: 'Portal Views', value: OSAS.fmt.number(row.views) }
      ]) +
        C.alert('info', 'Policy summary', U.esc(row.summary)) +
        '<div class="rt"><div class="rt__area" style="max-height:none">' + (row.body || '') + '</div></div>' +
        C.statStrip([
          { label: 'Review Schedule', value: U.esc(row.reviewSchedule || '—') },
          { label: 'Target Audience', value: U.esc(row.audience) },
          { label: 'Last Updated', value: OSAS.fmt.shortDate(row.updatedAt) },
          { label: 'Approval Body', value: U.esc((row.approvalBody || '').split('—')[0]) }
        ]),
      footer: '<button type="button" class="btn btn--ghost" data-close>Close</button>' +
        '<button type="button" class="btn btn--outline" id="reg-print">Print Policy</button>' +
        '<button type="button" class="btn btn--primary" id="reg-edit">Edit Regulation</button>',
      onMount: function (el, close) {
        el.querySelector('#reg-print').addEventListener('click', function () { window.print(); });
        el.querySelector('#reg-edit').addEventListener('click', function () {
          close();
          regulationModal(row, 'edit');
        });
      }
    });
  }

  function exportCsv() {
    var rows = filtered();
    U.exportCsv('beacon-regulations.csv', [
      { label: 'Reference Code', value: 'code' },
      { label: 'Policy Title', value: 'title' },
      { label: 'Category', value: 'category' },
      { label: 'Audience', value: 'audience' },
      { label: 'Effective Date', value: 'effectiveDate' },
      { label: 'Approval Body', value: 'approvalBody' },
      { label: 'Status', value: 'status' },
      { label: 'Views', value: 'views' }
    ], rows);
    C.toast('success', 'Policy register exported', rows.length + ' records written to CSV.');
  }

  function render() {
    content.innerHTML = OSAS.shell.pageHead({
      title: 'Student Code of Conduct & School Regulations',
      subtitle: 'Maintain and communicate university policies, the student code of conduct, and mandated regulations to all enrolled students.',
      actions: '<button type="button" class="btn btn--ghost" data-action="export">' +
        OSAS.icons.icon('download', 13) + ' Export Policies</button>' +
        '<button type="button" class="btn btn--brand" data-action="new">' +
        OSAS.icons.icon('plus', 13) + ' Add Regulation Policy</button>'
    }) + toolbar() + tabStrip() + table(filtered());
  }

  /* ---------------- one-time delegated bindings (survive re-renders) ----- */
  content.addEventListener('input', function (event) {
    if (event.target.id !== 'reg-search') { return; }
    state.search = event.target.value;
    state.page = 1;
    render();
    var box = document.getElementById('reg-search');
    if (box) {
      box.focus();
      box.setSelectionRange(box.value.length, box.value.length);
    }
  });

  content.addEventListener('change', function (event) {
    if (event.target.id !== 'reg-category') { return; }
    state.category = event.target.value;
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
      console.log('[Regulations] Action:', action, id);

      if (action === 'tab') { state.tab = id; state.page = 1; render(); return; }
      if (action === 'new') { regulationModal(null, 'new'); return; }
      if (action === 'export') { exportCsv(); return; }
      if (action === 'view') {
        var row = OSAS.store.find('regulations', id);
        if (row) { previewModal(row); }
        return;
      }
      if (action === 'edit') {
        var editRow = OSAS.store.find('regulations', id);
        if (editRow) { regulationModal(editRow, 'edit'); }
        return;
      }
      if (action === 'more') {
        var moreRow = OSAS.store.find('regulations', id);
        if (moreRow) { previewModal(moreRow); }
        return;
      }
      if (action === 'archive') {
        var archRow = OSAS.store.find('regulations', id);
        if (!archRow) { return; }
        C.confirm({
          title: 'Archive this regulation?',
          subtitle: archRow.title,
          alertTone: 'warning', alertTitle: 'Students will lose portal access to this policy',
          alertText: 'The policy remains in the register for audit purposes and can be restored at any time.',
          confirmLabel: 'Archive policy',
          onConfirm: function () {
            OSAS.store.update('regulations', archRow.id, { status: 'Archived', updatedAt: U.now() });
            OSAS.store.logActivity({
              tone: 'amber', icon: 'scale', title: 'Regulation Archived',
              text: archRow.code + ' — "' + archRow.title + '" was archived from the student portals.'
            });
            C.toast('info', 'Regulation archived', archRow.title);
            render();
          }
        });
        return;
      }
      if (action === 'restore') {
        var restRow = OSAS.store.find('regulations', id);
        if (!restRow) { return; }
        OSAS.store.update('regulations', restRow.id, { status: 'Published', updatedAt: U.now() });
        OSAS.store.logActivity({
          tone: 'green', icon: 'scale', title: 'Regulation Restored',
          text: restRow.code + ' — "' + restRow.title + '" was restored to the active policy register.'
        });
        C.toast('success', 'Regulation restored', restRow.title);
        render();
      }
    } catch (err) {
      console.error('[Regulations] Click handler error:', err);
      C.toast('danger', 'Error', 'Error: ' + (err.message || err));
    }
  });

  render();
})();
