/* ==========================================================================
   Module 9 — User Account & Access Management
   Prototype build. The module opens on the Verification Queue of pending
   registrations. "Needs Review" opens the Add New User modal with the
   applicant's School ID; Verify / Reject clear the request from the queue.
   ========================================================================== */
(function () {
  'use strict';
  var C = OSAS.components, U = OSAS.util;
  var shell = OSAS.shell.mount();
  if (!shell) { return; }
  var OPTIONS = OSAS.store.options();
  var TIERS = OPTIONS.accountTiers || ['Student Tier'];
  var YEARS = OPTIONS.years || ['First Year'];
  var page = null;

  function icon(n, s) { return OSAS.icons.icon(n, s); }
  function fullName(row) { return row.firstName + ' ' + row.lastName; }
  function submittedAt(row) { return row.requestedAt || row.createdAt || ''; }
  function isQueue(state) { return state.tab !== 'directory'; }

  /* --------------------------------------------------------------- toolbar */
  function queueToolbar(state) {
    var filtering = state.tier !== 'All';
    return '<div class="table-toolbar vm-head">' +
      '<div><div class="panel-title">Verification Queue</div>' +
      '<div class="vm-sub">Review applicant identity details and uploaded documentation.</div></div>' +
      '<div class="vm-tools">' +
      '<span class="input-group vm-search"><span class="input-group__ico">' + icon('search', 14) + '</span>' +
      '<input class="input" id="vm-search" placeholder="Search name, email, or ID…" value="' +
      U.esc(state.search) + '"></span>' +
      '<button type="button" class="btn btn--ghost btn--sm" data-action="filters">' + icon('filter', 12) +
      ' Filters' + (filtering ? ' &middot; ' + U.esc(state.tier) : '') + '</button>' +
      (filtering ? '<button type="button" class="btn btn--ghost btn--sm" data-action="clear" title="Clear filter">' +
        icon('close', 12) + '</button>' : '') +
      '</div></div>' +
      (state.filtersOpen ? '<div class="vm-filters"><div class="field"><label for="vm-tier">Account Type</label>' +
        '<select class="input" id="vm-tier"><option value="All">All</option>' +
        TIERS.map(function (tier) {
          return '<option value="' + U.esc(tier) + '"' + (tier === state.tier ? ' selected' : '') + '>' +
            U.esc(tier) + '</option>';
        }).join('') + '</select></div></div>' : '');
  }

  /* ------------------------------------------------------- applicant School ID
     Stand-in card drawn from the registration record — enough to read the
     name and ID number while demoing the review flow. */
  function schoolId(row) {
    return '<svg viewBox="0 0 300 170" class="vm-id-svg" role="img" aria-label="Student ID of ' +
      U.esc(fullName(row)) + '">' +
      '<rect width="300" height="170" rx="8" fill="#eef6f0" stroke="#cfe0d4"/>' +
      '<rect width="300" height="28" rx="8" fill="#0f8a4d"/>' +
      '<text x="150" y="19" text-anchor="middle" font-size="12" font-weight="bold" fill="#fff" ' +
      'font-family="Verdana,sans-serif">STUDENT ID CARD</text>' +
      '<rect x="14" y="44" width="54" height="70" fill="#dbe4dd"/>' +
      '<circle cx="41" cy="70" r="11" fill="#c3d0c7"/>' +
      '<path d="M20 114c0-12 9-18 21-18s21 6 21 18Z" fill="#c3d0c7"/>' +
      '<text x="80" y="58" font-size="8" fill="#6b7280" font-family="Verdana,sans-serif">NAME</text>' +
      '<text x="80" y="74" font-size="12" font-weight="bold" fill="#1f2937" font-family="Verdana,sans-serif">' +
      U.esc(fullName(row)) + '</text>' +
      '<text x="80" y="94" font-size="8" fill="#6b7280" font-family="Verdana,sans-serif">STUDENT ID</text>' +
      '<text x="80" y="110" font-size="12" font-weight="bold" fill="#7a1420" font-family="Consolas,monospace">' +
      U.esc(row.studentId || 'Pending') + '</text>' +
      '<text x="80" y="130" font-size="9" fill="#3f4753" font-family="Verdana,sans-serif">' +
      U.esc(row.program || '') + '</text></svg>';
  }

  /* ------------------------------------------------- review / add new user */
  function reviewModal(id) {
    var row = OSAS.store.find('users', id);
    if (!row) { return; }
    var alertHost = null;

    C.modal({
      size: 'md',
      title: 'Add New User',
      subtitle: 'Enter member details and mobile app login credentials below.',
      body: '<div id="vm-alert"></div>' +
        '<div class="vm-context">' + C.avatar(row.firstName, row.lastName, 'amber') +
        '<div class="u-grow"><b>' + U.esc(fullName(row)) + '</b><div class="cell-sub">Registered ' +
        U.esc(OSAS.fmt.hoursAgo(submittedAt(row))) + ' &middot; ' + U.esc(OSAS.fmt.dateTime(submittedAt(row))) +
        '</div></div><span class="vm-review">Needs Review</span></div>' +
        OSAS.forms.field('ID Numbers / Student IDs',
          OSAS.forms.textInput('vm-sid', row.studentId, '26-00000')) +
        '<div class="form-grid form-grid--2">' +
        OSAS.forms.field('First Names', OSAS.forms.textInput('vm-first', row.firstName, 'Juan'), { required: true }) +
        OSAS.forms.field('Last Names', OSAS.forms.textInput('vm-last', row.lastName, 'Dela Cruz'), { required: true }) +
        '</div>' +
        OSAS.forms.field('Email Addresses',
          OSAS.forms.textInput('vm-email', row.email, 'name@student.uc.edu.ph', 'email'), { required: true }) +
        '<div class="form-grid form-grid--2">' +
        OSAS.forms.field('Passwords', OSAS.forms.passwordInput('vm-pass', 'Minimum 10 characters'), { required: true }) +
        OSAS.forms.field('Years', OSAS.forms.selectInput('vm-year', YEARS, row.year, 'Select a year')) +
        '</div>' +
        '<div class="form-grid form-grid--2">' +
        OSAS.forms.field('User Roles',
          OSAS.forms.selectInput('vm-role', OPTIONS.roles, row.role, 'Select a role'), { required: true }) +
        OSAS.forms.field('Department / Program',
          OSAS.forms.selectInput('vm-program', OPTIONS.programs, row.program, 'Select a program')) +
        '</div>' +
        OSAS.forms.field('Pictures of School ID',
          row.schoolIdImage
            ? '<div class="vm-idcard"><img src="' + row.schoolIdImage + '" alt="School ID" style="max-width:100%;max-height:220px;border-radius:7px"></div>'
            : '<div class="vm-idcard">' + schoolId(row) + '</div>'),
      footerClass: ' modal__foot--split',
      footer: '<button type="button" class="btn btn--ghost" data-close>Cancel</button>' +
        '<button type="button" class="btn btn--success" id="vm-save">Save Changes</button>',
      onMount: function (el) {
        OSAS.forms.bindPasswordToggles(el);
        alertHost = el.querySelector('#vm-alert');
        el.querySelector('#vm-save').addEventListener('click', function () {
          var role = OSAS.forms.value('vm-role');
          var sid = OSAS.forms.value('vm-sid');
          var first = OSAS.forms.value('vm-first');
          var last = OSAS.forms.value('vm-last');
          var email = OSAS.forms.value('vm-email');
          var pass = OSAS.forms.value('vm-pass');
          var problem = '';

          if (!first || !last) { problem = 'First name and last name are required.'; }
          else if (!email || !OSAS.forms.validEmail(email)) { problem = 'A valid email address is required.'; }
          else if (!role) { problem = 'Select the portal role for this account.'; }
          else if (pass.length < 10) { problem = 'The temporary password must be at least 10 characters long.'; }
          else if (role === 'Student' && !sid) { problem = 'A student ID number is required for student accounts.'; }

          if (problem) {
            alertHost.innerHTML = C.alert('danger', 'Unable to save the account', problem);
            return;
          }

          OSAS.store.update('users', row.id, {
            studentId: sid, firstName: first, lastName: last, email: email,
            username: email.split('@')[0], role: role,
            year: OSAS.forms.value('vm-year'),
            program: OSAS.forms.value('vm-program') || row.program,
            status: 'Active'
          });
          alertHost.innerHTML = C.alert('success', 'Account Verified Successfully!',
            'Portal access for <b>' + U.esc(email) + '</b> is now active — temporary password <b>' +
            U.esc(pass) + '</b>.');
          el.querySelector('.modal__foot').innerHTML =
            '<button type="button" class="btn btn--primary" data-close>Done</button>';
          OSAS.store.logActivity({
            tone: 'green', icon: 'check', title: 'Registration Request Verified',
            text: fullName(row) + ' — ' + email + ' identity confirmed and portal access granted.'
          });
          C.toast('success', 'Account verified', fullName(row) + ' can now sign in to the portal.');
          page.render();
        });
      }
    });
  }

  /* -------------------------------------------------------- queue decisions */
  function verifyRequest(id) {
    var row = OSAS.store.find('users', id);
    if (!row) { return; }
    OSAS.store.update('users', id, { status: 'Active' });
    C.toast('success', 'Request verified', fullName(row) + ' can now sign in to the portal.');
    page.render();
  }

  function rejectRequest(id) {
    var row = OSAS.store.find('users', id);
    if (!row) { return; }
    C.confirm({
      title: 'Reject this registration request?',
      subtitle: U.esc(fullName(row)) + ' &middot; ' + U.esc(row.email),
      alertTone: 'warning',
      alertTitle: 'The pending request will be discarded',
      alertText: 'The applicant is asked to register again with a valid school ID.',
      confirmLabel: 'Reject request',
      confirmClass: 'btn--danger',
      onConfirm: function () {
        OSAS.store.remove('users', id);
        C.toast('info', 'Request rejected', fullName(row) + ' was removed from the queue.');
        page.render();
      }
    });
  }

  /* ------------------------------------------------------ directory tab (kept) */
  function statsHtml() {
    var stats = OSAS.analytics.users(OSAS.store.all('users'));
    return '<div class="stats">' +
      C.statCard('Registered Students', OSAS.fmt.number(stats.registeredStudents),
        C.delta(10.5, 'vs. last academic year') + '<span class="u-muted">' + U.esc(stats.lastSync) + '</span>') +
      C.statCard('Active Student Accounts', OSAS.fmt.number(stats.activeStudentAccounts),
        '<span class="badge badge--active">Active</span><span class="u-muted">signed in at least once</span>') +
      C.statCard('Pending Activations', OSAS.fmt.number(stats.pendingActivations),
        '<span class="badge badge--pending">Awaiting</span><span class="u-muted">of which ' + stats.pending +
        ' are in this directory</span>') +
      C.statCard('Administrative Staff', OSAS.fmt.number(stats.administrativeStaff),
        '<span class="u-muted">' + stats.byRole.Administrator + ' admin &middot; ' +
        (stats.byRole.Faculty || 0) + ' faculty &middot; ' + (stats.byRole.Staff || 0) + ' staff in directory</span>') +
      '</div>';
  }

  function tabStrip(state) {
    var pending = OSAS.store.all('users').filter(function (r) { return r.status === 'Pending'; }).length;
    return '<div class="toolbar"><div class="tabs">' +
      '<button type="button" class="tab' + (isQueue(state) ? ' tab--on' : '') +
      '" data-action="tab" data-id="queue">Verification Queue (' + pending + ')</button>' +
      '<button type="button" class="tab' + (isQueue(state) ? '' : ' tab--on') +
      '" data-action="tab" data-id="directory">User Directory</button></div>' +
      '<span class="chip">' + (pending ? pending + ' request' + (pending === 1 ? '' : 's') +
        ' awaiting verification' : 'The verification queue is clear') + '</span></div>';
  }

  function directoryFilters(state) {
    return '<div class="filter-bar filter-bar--3">' +
      '<div class="field"><label for="usr-search">Search the Access Directory</label>' +
      '<span class="input-group"><span class="input-group__ico">' + icon('search', 14) + '</span>' +
      '<input class="input" id="usr-search" placeholder="Search by name, email, student ID or department…" value="' +
      U.esc(state.search) + '"></span></div>' +
      '<div class="field"><label for="usr-role">Role</label><select class="input" id="usr-role">' +
      ['All'].concat(OPTIONS.roles).map(function (r) {
        return '<option value="' + U.esc(r) + '"' + (r === state.role ? ' selected' : '') + '>' + U.esc(r) + '</option>';
      }).join('') + '</select></div>' +
      '<div class="field"><label for="usr-status">Account Status</label><select class="input" id="usr-status">' +
      ['All', 'Active', 'Pending', 'Inactive'].map(function (s) {
        return '<option value="' + s + '"' + (s === state.status ? ' selected' : '') + '>' + s + '</option>';
      }).join('') + '</select></div></div>';
  }

  function queueColumns() {
    return [
      {
        label: 'Applicant',
        render: function (row) {
          return '<div class="cell-user">' + C.avatar(row.firstName, row.lastName, 'amber') +
            '<div><div class="cell-title">' + U.esc(fullName(row)) + '</div>' +
            '<div class="cell-sub">' + U.esc(row.program || row.department || 'No program assigned') + '</div></div></div>';
        }
      },
      {
        label: 'Account Type',
        render: function (row) {
          return '<span class="vm-tier">' + U.esc(row.accountTier || 'Student Tier') + '</span>';
        }
      },
      {
        label: 'Student ID',
        render: function (row) {
          return row.studentId ? '<span class="mono">' + U.esc(row.studentId) + '</span>'
            : '<span class="u-muted">—</span>';
        }
      },
      { label: 'Email', render: function (row) { return '<div class="cell-title">' + U.esc(row.email) + '</div>'; } },
      {
        label: 'Registration Time',
        render: function (row) {
          return '<div class="vm-ago">' + U.esc(OSAS.fmt.hoursAgo(submittedAt(row))) + '</div>' +
            '<div class="vm-when">' + U.esc(OSAS.fmt.dateTime(submittedAt(row))) + '</div>';
        }
      },
      {
        label: 'Status',
        render: function (row) {
          return '<button type="button" class="vm-review" data-action="review" data-id="' + U.esc(row.id) +
            '" title="Review this request and confirm the account">Needs Review</button>';
        }
      },
      {
        label: 'Actions',
        align: 'right',
        render: function (row) {
          return '<div class="cell-actions">' +
            '<button type="button" class="btn btn--ghost btn--sm" data-action="reject" data-id="' +
            U.esc(row.id) + '">Reject</button>' +
            '<button type="button" class="btn btn--primary btn--sm" data-action="verify" data-id="' +
            U.esc(row.id) + '">Verify</button></div>';
        }
      }
    ];
  }

  function directoryColumns() {
    return [
      {
        label: 'User & Role',
        render: function (row) {
          return '<div class="cell-user">' + C.avatar(row.firstName, row.lastName) +
            '<div><div class="cell-title">' + U.esc(fullName(row)) + '</div>' +
            '<div class="cell-sub"><span class="badge badge--brand">' + U.esc(row.role) + '</span></div></div></div>';
        }
      },
      {
        label: 'UC Student ID',
        render: function (row) {
          return row.studentId ? '<span class="mono">' + U.esc(row.studentId) + '</span>'
            : '<span class="u-muted">—</span>';
        }
      },
      {
        label: 'UC Email Address',
        render: function (row) {
          return '<div class="cell-title">' + U.esc(row.email) + '</div>' +
            (row.phone ? '<div class="cell-sub">' + U.esc(row.phone) + '</div>' : '');
        }
      },
      {
        label: 'Department & Program',
        render: function (row) {
          return '<div class="cell-title">' + U.esc(row.department || '—') + '</div>' +
            '<div class="cell-sub">' + U.esc(row.program || 'No program assigned') + '</div>';
        }
      },
      { label: 'Account Status', render: function (row) { return C.badge(row.status); } },
      {
        label: 'Actions',
        align: 'right',
        render: function (row) {
          return '<div class="cell-actions">' +
            (row.status === 'Pending'
              ? '<button type="button" class="btn btn--outline btn--sm" data-action="review" data-id="' +
                U.esc(row.id) + '">Review</button>' : '') +
            '<button type="button" class="btn btn--outline btn--sm" data-action="edit" data-id="' +
            U.esc(row.id) + '">Manage</button>' +
            '<button type="button" class="btn btn--ghost btn--sm" data-action="toggle" data-id="' +
            U.esc(row.id) + '">' + (row.status === 'Active' ? 'Deactivate' : 'Activate') + '</button></div>';
        }
      }
    ];
  }

  /* ------------------------------------------------------------------ page */
  page = OSAS.pagekit.listPage({
    content: shell.content,
    title: 'User Account & Access Management',
    subtitle: 'Verify pending registrations and manage portal access and system roles.',
    actions: '<button type="button" class="btn btn--primary" data-action="new">' +
      icon('plus', 13) + ' Add New User</button>',
    resource: 'users',
    defaultState: {
      tab: 'queue', search: '', tier: 'All', filtersOpen: false,
      role: 'All', status: 'All', page: 1
    },
    filter: function (rows, state) {
      if (isQueue(state)) {
        var queue = rows.filter(function (row) { return row.status === 'Pending'; });
        queue = U.search(queue, state.search, ['firstName', 'lastName', 'email', 'studentId', 'program']);
        if (state.tier !== 'All') {
          queue = queue.filter(function (row) { return (row.accountTier || 'Student Tier') === state.tier; });
        }
        return U.sortBy(queue, 'requestedAt', 'desc');
      }
      var list = U.search(rows, state.search,
        ['firstName', 'lastName', 'email', 'studentId', 'department', 'program']);
      list = U.byStatus(list, state.status);
      if (state.role !== 'All') {
        list = list.filter(function (row) { return row.role === state.role; });
      }
      return U.sortBy(list, 'lastName', 'asc');
    },
    stats: function (state) { return isQueue(state) ? '' : statsHtml(); },
    beforeTable: function (state) {
      return tabStrip(state) + (isQueue(state) ? '' : directoryFilters(state));
    },
    toolbar: function (state) {
      return isQueue(state) ? queueToolbar(state)
        : '<div class="table-toolbar"><div class="panel-title">System Access Directory</div></div>';
    },
    columns: function (state) { return isQueue(state) ? queueColumns() : directoryColumns(); },
    footerLabel: function (info) {
      var queue = isQueue(page ? page.state : { tab: 'queue' });
      return 'Showing ' + info.from + '–' + info.to + ' of ' + info.total +
        (queue ? ' pending registration requests' : ' registered users');
    },
    empty: 'No registration requests match the current filters.',
    onAction: function (action, id) {
      if (action === 'tab') { page.state.tab = id; page.state.page = 1; page.render(); return; }
      if (action === 'filters') { page.state.filtersOpen = !page.state.filtersOpen; page.render(); return; }
      if (action === 'clear') { page.state.tier = 'All'; page.state.page = 1; page.render(); return; }
      if (action === 'review') { reviewModal(id); return; }
      if (action === 'verify') { verifyRequest(id); return; }
      if (action === 'reject') { rejectRequest(id); return; }
      if (action === 'new') {
        OSAS.forms.userForm({ onSaved: function () { page.render(); } });
        return;
      }
      if (action === 'edit') {
        OSAS.forms.userForm({ user: OSAS.store.find('users', id), onSaved: function () { page.render(); } });
        return;
      }
      if (action === 'toggle') {
        var user = OSAS.store.find('users', id);
        OSAS.store.update('users', id, { status: user.status === 'Active' ? 'Inactive' : 'Active' });
        C.toast('success', 'Account updated', fullName(user) + ' status changed.');
        page.render();
      }
    }
  });

  shell.content.addEventListener('input', function (event) {
    if (event.target.id !== 'vm-search' && event.target.id !== 'usr-search') { return; }
    page.state.search = event.target.value;
    page.state.page = 1;
    page.render();
    var box = document.getElementById(event.target.id);
    if (box) { box.focus(); box.setSelectionRange(box.value.length, box.value.length); }
  });

  shell.content.addEventListener('change', function (event) {
    var key = { 'vm-tier': 'tier', 'usr-role': 'role', 'usr-status': 'status' }[event.target.id];
    if (!key) { return; }
    page.state[key] = event.target.value || 'All';
    page.state.page = 1;
    page.render();
  });
})();
