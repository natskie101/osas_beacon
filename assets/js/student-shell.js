/* ==========================================================================
   BEACON OSAS Portal — student portal shell
   Mounts the student top bar, the navigation (slide-in drawer below 1024px,
   persistent sidebar above it) and the student-accounts-only access gate,
   then hands the <main> element to the page script.
   Loaded by every student/*.html page before its page script.
   ========================================================================== */
OSAS.studentShell = (function () {
  'use strict';

  var NAV = [
    { key: 'dashboard', label: 'Dashboard', href: 'dashboard.html' },
    { key: 'rules', label: 'Rules and Regulations', href: 'rules.html' },
    { key: 'orientation', label: 'Orientation', href: 'orientation.html' },
    { key: 'announcements', label: 'Announcements', href: 'announcements.html' },
    { key: 'events', label: 'Events and Activities', href: 'events.html' },
    { key: 'feedback', label: 'Feedback and Report', href: 'support.html' }
  ];
  var DEMO_STUDENT = 'ryan.delacruz@uc.edu.ph';

  function esc(value) { return OSAS.util.esc(value); }
  function fullName(user) { return (user.firstName || '') + ' ' + (user.lastName || ''); }
  function $(id) { return document.getElementById(id); }
  function mq() {
    return window.matchMedia ? window.matchMedia('(min-width: 1024px)') : { matches: false };
  }

  /* ------------------------------------------------------------- markup */
  function navHtml(page) {
    return NAV.map(function (item) {
      var on = item.key === page;
      return '<li><a class="sp-nav__item' + (on ? ' sp-nav__item--on' : '') + '" href="' + item.href + '"' +
        (on ? ' aria-current="page"' : '') + '>' + item.label + '</a></li>';
    }).join('');
  }

  function shellHtml(options, user) {
    return '<div class="sp" id="sp-root">' +
      '<nav class="sp-drawer" id="sp-drawer" aria-label="Student portal navigation">' +
        '<div class="sp-drawer__head">' + OSAS.icons.beacon(26, 2.2) +
          '<div><div class="sp-drawer__title">BEACON</div>' +
          '<div class="sp-drawer__sub">Student Portal</div></div>' +
          '<button type="button" class="sp-icobtn sp-drawer__close" id="sp-drawer-close" aria-label="Close navigation">' +
            OSAS.icons.icon('close', 16) + '</button>' +
        '</div>' +
        '<ul class="sp-nav">' + navHtml(options.page) + '</ul>' +
        '<div class="sp-drawer__foot">' +
          '<div class="sp-drawer__user" id="sp-drawer-user"></div>' +
          '<button type="button" class="sp-signout" id="sp-signout">' + OSAS.icons.icon('logout', 15) + 'Sign out</button>' +
        '</div>' +
      '</nav>' +
      '<div class="sp-body">' +
        '<header class="sp-top"><div class="sp-top__inner' +
          (options.page === 'dashboard' ? '' : ' sp-top__inner--titled') + '">' +
          '<div class="sp-top__side">' +
            '<button type="button" class="sp-icobtn" id="sp-menu" aria-label="Open navigation" aria-controls="sp-drawer">' +
              OSAS.icons.icon('menu', 18) + '</button>' +
            '<div class="sp-top__title" id="sp-top-title">' + esc(options.title) + '</div>' +
            '<div class="sp-top__slot" id="sp-top-slot" style="display:none"></div>' +
          '</div>' +
          '<a class="sp-brand" href="dashboard.html">' + OSAS.icons.beacon(24, 2.2) +
            '<span class="sp-brand__name">BEACON</span></a>' +
          '<div class="sp-top__side sp-top__side--end">' +
            '<span class="sp-top__extra" id="sp-top-extra" style="display:none"></span>' +
            '<button type="button" class="sp-icobtn" id="sp-bell" aria-label="Notifications">' +
              OSAS.icons.icon('bell', 18) +
              '<span class="sp-bell__count" id="sp-bell-count" hidden>0</span></button>' +
            '<button type="button" class="sp-avatar" id="sp-avatar" aria-label="Account menu">&nbsp;</button>' +
          '</div>' +
        '</div></header>' +
        '<main class="sp-main" id="sp-main"></main>' +
        '<footer class="sp-foot">' +
          '<span>BEACON OSAS Portal · Office of Student Affairs</span>' +
          '<span>Southway College of Technology · AY 2026-2027</span>' +
        '</footer>' +
      '</div>' +
      '<div class="sp-backdrop" id="sp-backdrop"></div>' +
    '</div>';
  }

  /* ------------------------------------------- restricted (non-student) */
  function renderRestricted(options, user) {
    $('sp-root').classList.add('sp--locked');
    $('sp-main').innerHTML =
      '<section class="sp-lock">' +
        '<div class="sp-card sp-lock__card">' +
          '<span class="sp-lock__ico">' + OSAS.icons.icon('lock', 20) + '</span>' +
          '<div class="sp-card__label">ACCESS RESTRICTED</div>' +
          '<h1 class="sp-lock__title">The ' + esc(options.title) +
            ' module is for student accounts only</h1>' +
          '<p class="sp-card__text">You are signed in as <strong>' + esc(fullName(user)) +
            '</strong> (' + esc(user.role) + '), so student records, policies and portal alerts ' +
            'stay hidden on this page.</p>' +
          '<div class="sp-lock__actions">' +
            '<a class="btn btn--outline" href="../dashboard.html">Back to admin dashboard</a>' +
            '<button type="button" class="btn btn--primary" id="sp-demo-student">Open with demo student account</button>' +
          '</div>' +
          '<div class="sp-lock__hint">Demo student · Ryan Dela Cruz · ' + esc(DEMO_STUDENT) +
            ' · password ' + esc(OSAS.CONFIG.demoPassword) + '</div>' +
        '</div>' +
      '</section>';

    $('sp-demo-student').addEventListener('click', function () {
      var result = OSAS.auth.login(DEMO_STUDENT, OSAS.CONFIG.demoPassword, false);
      if (!result.ok) {
        OSAS.components.toast('error', 'Could not open the student view', result.message);
        return;
      }
      window.location.reload();
    });
  }

  /* ------------------------------------------------------ account / auth */
  function signOut() {
    OSAS.auth.logout();
    window.location.replace('../index.html');
  }

  function openAccount(user) {
    OSAS.components.modal({
      size: 'sm',
      title: 'Account',
      subtitle: 'Signed in to the BEACON portal',
      body: '<div class="sp-account">' + OSAS.components.avatar(user.firstName, user.lastName) +
        '<div><div class="sp-drawer__name">' + esc(fullName(user)) + '</div>' +
        '<div class="sp-drawer__role">' + esc(user.role) + '</div>' +
        '<div class="sp-note__meta">' + esc(user.email) + '</div></div></div>',
      footer: '<button type="button" class="btn btn--ghost" data-close>Close</button>' +
        '<button type="button" class="btn btn--primary" data-signout>Sign out</button>',
      onMount: function (el, close) {
        el.querySelector('[data-signout]').addEventListener('click', function () {
          close();
          signOut();
        });
      }
    });
  }

  function confirmSignOut() {
    OSAS.components.confirm({
      title: 'Sign out?',
      message: 'You will be returned to the BEACON sign-in page.',
      confirmLabel: 'Sign out',
      onConfirm: signOut
    });
  }

  /* -------------------------------------------------------- notifications */
  function openNotifications() {
    var U = OSAS.util, C = OSAS.components;
    var rows = U.sortBy(OSAS.store.all('broadcasts'), 'at', 'desc').slice(0, 5);
    var body = '<div class="sp-notes">' + rows.map(function (b) {
      return '<div class="sp-note">' +
        '<span class="sp-note__dot sp-note__dot--' + U.slug(b.status) + '"></span>' +
        '<div><div class="sp-note__title">' + esc(b.subject) + '</div>' +
        '<div class="sp-note__text">' + esc(b.message) + '</div>' +
        '<div class="sp-note__meta">' + OSAS.fmt.dateTime(b.at) + ' · ' +
        esc(b.channel) + ' · ' + esc(b.status) + '</div></div></div>';
    }).join('') + '</div>';

    C.modal({
      size: 'md',
      title: 'Notifications',
      subtitle: 'Latest alerts delivered to your student portal account.',
      body: body,
      footer: '<button type="button" class="btn btn--ghost" data-close>Close</button>' +
        '<a class="btn btn--primary" href="../broadcasts.html">Open Notification Hub</a>'
    });
  }

  function renderBell() {
    var count = OSAS.store.all('broadcasts').filter(function (b) {
      return b.status === 'Pending' || b.status === 'Scheduled';
    }).length;
    var node = $('sp-bell-count');
    node.textContent = String(count);
    node.hidden = count === 0;
  }

  /* --------------------------------------------------------------- drawer */
  function setDrawer(open) {
    if (mq().matches) { open = false; }   /* sidebar is persistent on the web layout */
    $('sp-drawer').classList.toggle('sp-drawer--on', open);
    $('sp-backdrop').classList.toggle('sp-backdrop--on', open);
    if (!document.querySelector('.modal-backdrop')) {
      document.body.style.overflow = open ? 'hidden' : '';
    }
    if (open) { $('sp-menu').focus(); }
  }

  function wire(user, locked) {
    var media = mq();

    $('sp-menu').addEventListener('click', function () {
      setDrawer(!$('sp-drawer').classList.contains('sp-drawer--on'));
    });
    $('sp-avatar').addEventListener('click', function () {
      if (!locked && !media.matches) { setDrawer(true); return; }   /* mobile drawer */
      openAccount(user);                                            /* desktop / locked */
    });
    $('sp-backdrop').addEventListener('click', function () { setDrawer(false); });
    $('sp-drawer-close').addEventListener('click', function () { setDrawer(false); });
    $('sp-bell').addEventListener('click', function () { if (!locked) { openNotifications(); } });
    $('sp-signout').addEventListener('click', confirmSignOut);
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !document.querySelector('.modal-backdrop')) { setDrawer(false); }
    });
    if (typeof media.addEventListener === 'function') {
      media.addEventListener('change', function () { setDrawer(false); });
    } else if (typeof media.addListener === 'function') {
      media.addListener(function () { setDrawer(false); });
    }
  }

  /* ------------------------------------------------- top bar overrides
     Pages with a detail view can swap the page title for a back link and
     drop controls that do not belong there, e.g. the policy detail screen. */
  function setBar(opts) {
    opts = opts || {};
    function show(node, on) { node.style.display = on ? '' : 'none'; }
    show($('sp-menu'), opts.menu !== false);
    show($('sp-bell'), opts.bell !== false);
    show($('sp-avatar'), opts.avatar !== false);
    show($('sp-top-title'), !opts.title);
    $('sp-top-slot').innerHTML = opts.title || '';
    show($('sp-top-slot'), Boolean(opts.title));
    $('sp-top-extra').innerHTML = opts.extra || '';
    show($('sp-top-extra'), Boolean(opts.extra));
  }

  function fillIdentity(user) {
    $('sp-avatar').textContent = OSAS.fmt.initials(user.firstName, user.lastName);
    $('sp-drawer-user').innerHTML = OSAS.components.avatar(user.firstName, user.lastName) +
      '<div><div class="sp-drawer__name">' + esc(fullName(user)) + '</div>' +
      '<div class="sp-drawer__role">' + esc(user.role) + '</div></div>';
  }

  /* ---------------------------------------------------------------- mount */
  function mount(options) {
    options = options || {};
    options.page = options.page || 'dashboard';
    options.title = options.title || 'Student Portal';

    var mountPoint = document.getElementById('sp-shell');
    if (!mountPoint) { return null; }

    var session = OSAS.auth.current();
    if (!session) {
      /* keep the nested folder in the ?next= target so login returns here */
      var target = 'student/' + (window.location.pathname.split('/').pop() || 'dashboard.html');
      window.location.replace('../index.html?next=' + encodeURIComponent(target));
      return null;
    }

    var user = session.user;
    var locked = user.role !== 'Student';

    mountPoint.innerHTML = shellHtml(options, user);
    fillIdentity(user);
    renderBell();
    wire(user, locked);

    if (locked) {
      renderRestricted(options, user);
      OSAS.components.flashFromQuery();
      return null;
    }

    OSAS.components.flashFromQuery();
    return {
      root: $('sp-root'),
      main: $('sp-main'),
      user: user,
      setDrawer: setDrawer,
      setBar: setBar,
      fullName: fullName
    };
  }

  return { mount: mount, NAV: NAV, DEMO_STUDENT: DEMO_STUDENT };
})();
