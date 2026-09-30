/* ==========================================================================
   Module 4 — Student Onboarding & Orientation Modules
   ========================================================================== */
(function () {
  'use strict';
  var C = OSAS.components, U = OSAS.util;
  var shell = OSAS.shell.mount();
  if (!shell) { return; }
  var content = shell.content;
  var OPTIONS = OSAS.store.options();

  var state = { search: '', category: 'All', status: 'All', page: 1 };

  function filtered() {
    var rows = OSAS.store.all('modules').slice();
    rows = U.search(rows, state.search, ['title', 'description', 'category', 'code']);
    rows = U.byStatus(rows, state.status);
    if (state.category !== 'All') {
      rows = rows.filter(function (r) { return r.category === state.category; });
    }
    return U.sortBy(rows, 'updatedAt', 'desc');
  }

  /* ------------------------------------------------------------- toolbar */
  function toolbar() {
    return '<div class="ori-toolbar">' +
      '<div class="ori-toolbar__left">' +
      '<div class="panel-title">Orientation Module Library</div>' +
      '<div class="ori-sub">Manage onboarding materials, orientation modules, and information guides for incoming students.</div>' +
      '</div>' +
      '<div class="ori-toolbar__right">' +
      '<span class="input-group ori-search"><span class="input-group__ico">' + OSAS.icons.icon('search', 14) + '</span>' +
      '<input class="input" id="ori-search" placeholder="Search orientation module…" value="' + U.esc(state.search) + '"></span>' +
      '<span class="input-group ori-filter"><span class="input-group__ico">' + OSAS.icons.icon('filter', 14) + '</span>' +
      '<select class="input" id="ori-category">' +
      ['All'].concat(OPTIONS.moduleCategories || []).map(function (cat) {
        return '<option value="' + U.esc(cat) + '"' + (cat === state.category ? ' selected' : '') + '>' + U.esc(cat) + '</option>';
      }).join('') + '</select></span>' +
      '</div></div>';
  }

  /* ------------------------------------------------------------ table */
  function mediaBadge(row) {
    var type = row.mediaType || 'Video';
    var duration = row.duration ? ' (' + row.duration + ' min)' : '';
    var colorClass = type.indexOf('Video') === 0 ? 'purple' : type.indexOf('Interactive') === 0 ? 'blue' : type.indexOf('Slide') === 0 ? 'teal' : 'grey';
    return '<span class="ori-media ori-media--' + colorClass + '">' + U.esc(type + duration) + '</span>';
  }

  function statusBadge(row) {
    if (row.mandatory) { return '<span class="ori-status ori-status--mandatory">Mandatory</span>'; }
    if (row.status === 'Published') { return '<span class="ori-status ori-status--recommended">Recommended</span>'; }
    return '<span class="ori-status ori-status--optional">Optional</span>';
  }

  function table(rows) {
    var page = U.paginate(rows, state.page, OSAS.CONFIG.pageSize);
    var columns = [
      {
        label: 'Module Code &amp; Title',
        render: function (row) {
          return '<div class="ori-code">' + U.esc(row.code) + '</div>' +
            '<div class="ori-title">' + U.esc(row.title) + '</div>' +
            '<div class="ori-desc">' + U.esc(U.truncate(row.description, 100)) + '</div>';
        }
      },
      {
        label: 'Media / Content Type',
        render: function (row) { return mediaBadge(row); }
      },
      {
        label: 'Completion Rate',
        render: function (row) {
          var rate = row.completionRate || 0;
          var color = rate >= 80 ? 'green' : rate >= 50 ? 'amber' : 'red';
          return '<div class="ori-progress">' +
            '<div class="ori-progress__label">' + rate + '% (' + OSAS.fmt.number(row.completions) + ' / ' + OSAS.fmt.number(row.learners) + ')</div>' +
            '<div class="ori-progress__bar"><div class="ori-progress__fill ori-progress__fill--' + color + '" style="width:' + Math.min(rate, 100) + '%"></div></div>' +
            '</div>';
        }
      },
      {
        label: 'Status',
        render: function (row) { return statusBadge(row); }
      },
      {
        label: 'Last Updated',
        render: function (row) {
          return '<div class="ori-date">' + OSAS.fmt.shortDate(row.updatedAt) + '</div>' +
            '<div class="ori-updated">by ' + U.esc(row.updatedBy || 'OSAS Director') + '</div>';
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
    var footer = C.pager(page, 'Showing ' + page.from + '–' + page.to + ' of ' + page.total + ' orientation modules');
    return C.dataTable({ columns: columns, rows: page.items, footer: footer });
  }

  /* ------------------------------------------------- module create / edit */
  function moduleFormHtml(row) {
    var isEdit = !!(row && row.id);
    var nextCode = OSAS.store.nextId('modules', 'ORIENT-', 3);
    var contentType = row.contentType || 'Video Presentation';
    var verification = row.verification || 'quiz';

    var contentTypes = ['Video Presentation', 'Document / PDF', 'Slide Deck', 'Web URL / Embed'];
    var contentTypeHtml = '<div class="ori-content-types">' + contentTypes.map(function (ct) {
      return '<button type="button" class="ori-content-type' + (ct === contentType ? ' ori-content-type--on' : '') +
        '" data-content-type="' + U.esc(ct) + '">' + U.esc(ct) + '</button>';
    }).join('') + '</div>';

    return '<div id="mod-alert"></div>' +
      '<div class="form-section">' +
      '<div class="section-title"><span>1.</span> Module Definition</div>' +
      '<div class="form-grid form-grid--2">' +
      OSAS.forms.field('Module Code',
        OSAS.forms.textInput('mod-code', row.code || nextCode, 'ORIENT-XXX'),
        { id: 'mod-code', required: true }) +
      OSAS.forms.field('Requirement Level',
        OSAS.forms.selectInput('mod-requirement', ['Mandatory (Required for Clearance)', 'Recommended', 'Optional'],
          row.requirement || 'Mandatory (Required for Clearance)', 'Select requirement level'),
        { id: 'mod-requirement', required: true }) +
      '</div>' +
      OSAS.forms.field('Module Title',
        OSAS.forms.textInput('mod-title', row.title, 'Enter the module title'),
        { id: 'mod-title', required: true }) +
      OSAS.forms.field('Summary / Overview Description',
        '<textarea class="input" id="mod-description" rows="3" placeholder="Summary shown to freshmen in the module list…">' +
        U.esc(row.description || '') + '</textarea>', { id: 'mod-description', required: true }) +
      '</div>' +
      '<div class="form-section">' +
      '<div class="section-title"><span>2.</span> Content Type &amp; Media Attachments</div>' +
      '<input type="hidden" id="mod-content-type" value="' + U.esc(contentType) + '">' +
      contentTypeHtml +
      '<div class="ori-upload-zone" id="ori-upload-zone">' +
      '<input type="file" id="mod-file" accept=".mp4,.mov,.pdf,.pptx,.docx" style="display:none">' +
      '<div class="ori-upload-inner">' +
      '<span class="ori-upload-icon">' + OSAS.icons.icon('upload', 24) + '</span>' +
      '<span class="ori-upload-text"><b>Click to upload video file</b> or drag and drop</span>' +
      '<span class="ori-upload-hint">MP4, MOV, or PDF, DOCX files up to 250MB</span>' +
      '</div></div>' +
      '</div>' +
      '<div class="form-section">' +
      '<div class="section-title"><span>3.</span> Target Audience &amp; Completion Settings</div>' +
      '<div class="field"><span class="flabel">Completion Verification Method</span>' +
      '<div class="ori-radio-group">' +
      '<label class="ori-radio' + (verification === 'quiz' ? ' ori-radio--on' : '') + '">' +
      '<input type="radio" name="mod-verification" value="quiz"' + (verification === 'quiz' ? ' checked' : '') + '>' +
      '<span>Attach Quiz Assessment (Passing score required)</span></label>' +
      '<label class="ori-radio' + (verification === 'ack' ? ' ori-radio--on' : '') + '">' +
      '<input type="radio" name="mod-verification" value="ack"' + (verification === 'ack' ? ' checked' : '') + '>' +
      '<span>Digital Acknowledgment / Sign-off only</span></label>' +
      '</div></div>' +
      '<div class="form-grid form-grid--3">' +
      OSAS.forms.field('Passing Rate (%)',
        OSAS.forms.textInput('mod-passing', row.passingRate ? String(row.passingRate) : '80', '80', 'number'),
        { id: 'mod-passing' }) +
      OSAS.forms.field('Compliance Deadline',
        '<input class="input" type="date" id="mod-deadline" value="' + U.esc(row.deadline || '2026-10-31') + '">',
        { id: 'mod-deadline' }) +
      OSAS.forms.field('Publishing Department',
        OSAS.forms.selectInput('mod-department',
          ['Campus Safety & Health Office', 'Office of Student Affairs & Services', 'Guidance & Testing Center',
           'IT Services Office', 'Registrar\'s Office'],
          row.department || 'Campus Safety & Health Office', 'Select department'),
        { id: 'mod-department' }) +
      '</div>' +
      '</div>';
  }

  function moduleModal(row, mode) {
    var isEdit = mode === 'edit';
    C.modal({
      size: 'lg',
      title: isEdit ? 'Edit Orientation Module' : 'Create New Orientation Module',
      subtitle: isEdit
        ? 'Update the module content and publication settings for incoming students.'
        : 'Publish a new learning unit, video lesson, or policy handbook for onboarding students.',
      body: moduleFormHtml(row),
      footer: '<button type="button" class="btn btn--ghost" data-close>Cancel</button>' +
        '<button type="button" class="btn btn--outline" id="mod-save-draft">Save as Draft</button>' +
        '<button type="button" class="btn btn--brand" id="mod-save">' +
        (isEdit ? 'Save Changes' : 'Publish Module') + '</button>',
      onMount: function (el, close) {
        var alertHost = el.querySelector('#mod-alert');

        // Content type selector
        var contentTypeInput = el.querySelector('#mod-content-type');
        U.qsa('.ori-content-type', el).forEach(function (btn) {
          btn.addEventListener('click', function () {
            U.qsa('.ori-content-type', el).forEach(function (b) { b.classList.remove('ori-content-type--on'); });
            btn.classList.add('ori-content-type--on');
            contentTypeInput.value = btn.getAttribute('data-content-type');
          });
        });

        // Upload zone
        var fileInput = el.querySelector('#mod-file');
        var uploadZone = el.querySelector('#ori-upload-zone');
        if (uploadZone && fileInput) {
          uploadZone.addEventListener('click', function () { fileInput.click(); });
          fileInput.addEventListener('change', function () {
            if (fileInput.files.length) {
              uploadZone.classList.add('ori-upload-zone--has-file');
              uploadZone.querySelector('.ori-upload-text').innerHTML = '<b>' + U.esc(fileInput.files[0].name) + '</b>';
            }
          });
        }

        // Verification method toggle
        U.qsa('input[name="mod-verification"]', el).forEach(function (radio) {
          radio.addEventListener('change', function () {
            U.qsa('.ori-radio', el).forEach(function (r) { r.classList.remove('ori-radio--on'); });
            radio.closest('.ori-radio').classList.add('ori-radio--on');
          });
        });

        function save(forcedStatus) {
          var code = OSAS.forms.value('mod-code');
          var title = OSAS.forms.value('mod-title');
          var description = OSAS.forms.value('mod-description');
          var errors = [];
          if (!code) { errors.push('A module code is required.'); }
          if (!title) { errors.push('A module title is required.'); }
          if (!description) { errors.push('A short description is required.'); }
          if (errors.length) {
            alertHost.innerHTML = C.alert('danger', 'Unable to save the module', errors.join(' '));
            return;
          }

          var status = forcedStatus || 'Published';
          var verificationRadio = U.qsa('input[name="mod-verification"]', el).filter(function (r) { return r.checked; })[0];
          var payload = {
            code: code,
            title: title,
            description: description,
            contentType: contentTypeInput.value,
            requirement: el.querySelector('#mod-requirement').value,
            verification: verificationRadio ? verificationRadio.value : 'quiz',
            passingRate: parseInt(OSAS.forms.value('mod-passing'), 10) || 80,
            deadline: el.querySelector('#mod-deadline').value,
            department: el.querySelector('#mod-department').value,
            status: status,
            updatedAt: U.now(),
            updatedBy: 'OSAS Director',
            mandatory: el.querySelector('#mod-requirement').value.indexOf('Mandatory') === 0
          };

          var saved;
          if (isEdit) {
            saved = OSAS.store.update('modules', row.id, payload);
          } else {
            payload.id = OSAS.store.nextId('modules', 'ORIENT-', 3);
            payload.code = payload.id;
            payload.learners = 0;
            payload.completions = 0;
            payload.completionRate = 0;
            saved = OSAS.store.insert('modules', payload);
          }

          OSAS.store.logActivity({
            tone: status === 'Published' ? 'blue' : 'amber', icon: 'graduation',
            title: status === 'Published'
              ? (isEdit ? 'Orientation Module Updated' : 'Orientation Module Published')
              : 'Orientation Module Drafted',
            text: saved.code + ' — "' + saved.title + '" (' + saved.category + ') is now ' +
              status.toLowerCase() + ', approx. ' + saved.duration + ' minutes.'
          });
          alertHost.innerHTML = C.alert('success',
            status === 'Published' ? 'Module Published Successfully!' : 'Module Saved as Draft',
            '<b>' + U.esc(saved.title) + '</b>' + (status === 'Published'
              ? ' is now available to ' + U.esc(saved.audience) + '.'
              : ' is stored as a draft and is not yet visible to students.'));
          el.querySelector('.modal__foot').innerHTML =
            '<button type="button" class="btn btn--primary" data-close>Done</button>';
          C.toast('success', status === 'Published' ? 'Module published' : 'Draft saved', saved.title);
          close();
          render();
        }

        el.querySelector('#mod-save').addEventListener('click', function () {
          save(el.querySelector('#mod-status').value || 'Published');
        });
        el.querySelector('#mod-save-draft').addEventListener('click', function () { save('Draft'); });
      }
    });
  }

  function previewModal(row) {
    C.modal({
      size: 'lg',
      title: U.esc(row.title),
      subtitle: U.esc(row.code) + ' · ' + U.esc(row.category) + ' · version ' + U.esc(row.version),
      body: C.statStrip([
        { label: 'Status', value: C.badge(row.status) },
        { label: 'Duration', value: OSAS.fmt.duration(row.duration) },
        { label: 'Learners', value: OSAS.fmt.number(row.learners) },
        { label: 'Completion', value: OSAS.fmt.percent(row.completionRate) }
      ]) +
        (row.mandatory ? C.alert('warning', 'Mandatory module',
          'Freshmen must complete this module before the enlistment period.') : '') +
        C.alert('info', 'Module description', U.esc(row.description)) +
        '<div class="rt"><div class="rt__area" style="max-height:none">' + (row.body || '') + '</div></div>' +
        C.progressBar(row.completionRate, row.completionRate >= 85 ? 'green' : 'amber'),
      footer: '<button type="button" class="btn btn--ghost" data-close>Close</button>' +
        '<button type="button" class="btn btn--primary" id="mod-edit">Edit Module</button>',
      onMount: function (el, close) {
        el.querySelector('#mod-edit').addEventListener('click', function () {
          close();
          moduleModal(row, 'edit');
        });
      }
    });
  }

  function exportCsv() {
    var rows = filtered();
    U.exportCsv('beacon-orientation-modules.csv', [
      { label: 'Module Code', value: 'code' },
      { label: 'Title', value: 'title' },
      { label: 'Category', value: 'category' },
      { label: 'Duration', value: 'duration' },
      { label: 'Status', value: 'status' },
      { label: 'Mandatory', value: function (r) { return r.mandatory ? 'Yes' : 'No'; } },
      { label: 'Completion Rate', value: 'completionRate' },
      { label: 'Learners', value: 'learners' }
    ], rows);
    C.toast('success', 'Modules exported', rows.length + ' records written to CSV.');
  }

  function render() {
    content.innerHTML = OSAS.shell.pageHead({
      title: 'Student Onboarding & Orientation Modules',
      subtitle: 'Manage onboarding materials, orientation modules, and information guides for incoming students.',
      actions: '<button type="button" class="btn btn--ghost" data-action="export">' +
        OSAS.icons.icon('download', 13) + ' Export List</button>' +
        '<button type="button" class="btn btn--brand" data-action="new">' +
        OSAS.icons.icon('plus', 13) + ' Create Orientation Module</button>'
    }) + toolbar() + table(filtered());
  }

  /* ---------------- one-time delegated bindings (survive re-renders) ----- */
  content.addEventListener('input', function (event) {
    if (event.target.id !== 'ori-search') { return; }
    state.search = event.target.value;
    state.page = 1;
    render();
    var box = document.getElementById('ori-search');
    if (box) {
      box.focus();
      box.setSelectionRange(box.value.length, box.value.length);
    }
  });

  content.addEventListener('change', function (event) {
    if (event.target.id !== 'ori-category') { return; }
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
    var trigger = event.target.closest ? event.target.closest('[data-action]') : null;
    if (!trigger) { return; }
    var action = trigger.getAttribute('data-action');
    var id = trigger.getAttribute('data-id');

    if (action === 'new') { moduleModal({ status: 'Published', duration: 15, version: 'v1.0' }, 'new'); return; }
    if (action === 'export') { exportCsv(); return; }
    if (action === 'view') {
      var row = OSAS.store.find('modules', id);
      if (row) { previewModal(row); }
      return;
    }
    if (action === 'edit') {
      var editRow = OSAS.store.find('modules', id);
      if (editRow) { moduleModal(editRow, 'edit'); }
      return;
    }
    if (action === 'more') {
      var moreRow = OSAS.store.find('modules', id);
      if (moreRow) { previewModal(moreRow); }
      return;
    }
  });

  render();
})();
