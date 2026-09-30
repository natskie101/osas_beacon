/* ==========================================================================
   BEACON OSAS Portal — application shell
   Renders the numbered module sidebar, top bar and page frame, then hands the
   #page-content element to the page script.
   ========================================================================== */
OSAS.shell = (function () {
  var NAV = [
    { n: 1, label: 'Dashboard Overview', href: 'dashboard.html', page: 'dashboard', icon: 'dashboard' },
    { n: 2, label: 'Announcements', href: 'announcements.html', page: 'announcements', icon: 'megaphone' },
    { n: 3, label: 'Rules & Regulations', href: 'regulations.html', page: 'regulations', icon: 'scale' },
    { n: 4, label: 'Orientation Content', href: 'orientation.html', page: 'orientation', icon: 'graduation' },
    { n: 5, label: 'Events & Activities', href: 'events.html', page: 'events', icon: 'calendar' },
    { n: 6, label: 'Viewing & Tracking', href: 'analytics.html', page: 'analytics', icon: 'activity' },
    { n: 7, label: 'Feedback & Support', href: 'support.html', page: 'support', icon: 'headset', badge: 'tickets' },
    { n: 8, label: 'Notification Hub', href: 'broadcasts.html', page: 'broadcasts', icon: 'bell', badge: 'broadcasts' },
    { n: 9, label: 'User Management', href: 'users.html', page: 'users', icon: 'users' },
    { n: 10, label: 'Reports', href: 'reports.html', page: 'reports', icon: 'fileText' }
  ];

  var STUDENT_NAV = [
    { n: 1, label: 'Dashboard', href: 'student/dashboard.html', page: 'student-dashboard', icon: 'dashboard' },
    { n: 2, label: 'Orientation', href: 'student/orientation.html', page: 'student-orientation', icon: 'graduation' },
    { n: 3, label: 'Announcements', href: 'student/announcements.html', page: 'student-announcements', icon: 'megaphone' },
    { n: 4, label: 'Events', href: 'student/events.html', page: 'student-events', icon: 'calendar' },
    { n: 5, label: 'Feedback & Support', href: 'student/support.html', page: 'student-support', icon: 'headset', badge: 'tickets' }
  ];

  var PAGE_META = {
    dashboard: {
      title: 'Admin Dashboard Overview',
      subtitle: 'Consolidated real-time status of all OSAS portal modules, content operations and student support activity.'
    },
    announcements: {
      title: 'Announcement & Information Broadcast Management',
      subtitle: 'Create, publish, and organize university-wide announcements and information notices across the student portal.'
    },
    regulations: {
      title: 'Student Code of Conduct & Related Regulations',
      subtitle: 'Maintain and communicate university policies, the student code of conduct, and mandated regulations.'
    },
    orientation: {
      title: 'Student Orientation & Information Module',
      subtitle: 'Manage onboarding materials, orientation modules, and information guides for incoming students.'
    },
    events: {
      title: 'Events & Activities Management',
      subtitle: 'Schedule, publish and monitor university events, seminars, and student activities.'
    },
    analytics: {
      title: 'Viewing & Tracking',
      subtitle: 'Monitor portal content reach, engagement quality, and viewing activity across all broadcasting modules.'
    },
    support: {
      title: 'Feedback & Support Center',
      subtitle: 'Manage student inquiries, complaints, and feedback, and monitor the OSAS service response performance.'
    },
    broadcasts: {
      title: 'Automated Broadcasts & Student Alerts',
      subtitle: 'Configure automated broadcast alerts and reminders for university-wide announcement communications.'
    },
    users: {
      title: 'User Account & Access Management',
      subtitle: 'Manage portal access and system roles for all students and staff.'
    },
    reports: {
      title: 'Reports',
      subtitle: 'Generate and export official reports for portal activity, compliance, and support operations.'
    }
  };

  function badgeCounts() {
    return {
      tickets: OSAS.analytics.openTickets(OSAS.store.all('tickets')).length,
      broadcasts: OSAS.store.all('broadcasts').filter(function (b) {
        return b.status === 'Pending' || b.status === 'Scheduled';
      }).length
    };
  }
  function sidebarHtml(activePage, session) {
    var counts = badgeCounts();
    var items = NAV.map(function (item) {
      var badge = item.badge && counts[item.badge] ?
        '<span class="nav__badge">' + counts[item.badge] + '</span>' : '';
      return '<li class="nav__item' + (item.page === activePage ? ' nav__item--active' : '') + '">' +
        '<a href="' + item.href + '"><span class="nav__num">' + item.n + '.</span>' +
        '<span class="nav__ico">' + OSAS.icons.icon(item.icon, 15) + '</span>' +
        '<span>' + OSAS.util.esc(item.label) + '</span>' + badge + '</a></li>';
    }).join('');

    return '<aside class="sidebar" id="sidebar">' +
      '<div class="sidebar__brand">' + OSAS.icons.beacon(30) +
      '<div><div class="sidebar__wordmark">BEACON</div>' +
      '<div class="sidebar__tag">SCT OSAS Portal</div></div></div>' +
      '<nav class="nav"><div class="nav__label">Modules</div><ul>' + items + '</ul></nav>' +
      '<div class="sidebar__foot"><div class="sidebar__user">' +
      OSAS.components.avatar(session.user.firstName, session.user.lastName) +
      '<div><div class="sidebar__user-name">' +
      OSAS.util.esc(session.user.firstName + ' ' + session.user.lastName) + '</div>' +
      '<div class="sidebar__user-role">' + OSAS.util.esc(session.user.role) + '</div></div>' +
      '<button type="button" class="sidebar__signout" id="sign-out" title="Sign out">' +
      OSAS.icons.icon('logout', 14) + '</button>' +
      '</div></div></aside>' +
      '<div class="sidebar-backdrop" id="sidebar-backdrop"></div>';
  }

  function topbarHtml(meta, session) {
    var counts = badgeCounts();
    return '<header class="topbar">' +
      '<button type="button" class="icon-btn menu-btn" id="menu-btn" aria-label="Show modules">' +
      OSAS.icons.icon('menu', 15) + '</button>' +
      '<span class="topbar__mark">' + OSAS.icons.beacon(24, 2.6) + '</span>' +
      '<div><h1 class="topbar__title">' + OSAS.util.esc(meta.title) + '</h1>' +
      '<div class="topbar__sub">' + OSAS.util.esc(OSAS.store.meta().institution) + '</div></div>' +
      '<div class="topbar__actions">' +
      '<a class="icon-btn" href="broadcasts.html" title="Notification hub (' + counts.broadcasts + ' queued)">' +
      OSAS.icons.icon('bell', 15) + '</a>' +
      '<button type="button" class="icon-btn" id="print-view" title="Print current view">' +
      OSAS.icons.icon('printer', 15) + '</button>' +
      '<button type="button" class="icon-btn" id="sign-out-top" title="Sign out">' +
      OSAS.icons.icon('logout', 15) + '</button>' +
      '</div></header>';
  }

  function pageHead(options) {
    return '<div class="page-head">' +
      '<div><h2 class="page-head__title">' + OSAS.util.esc(options.title) + '</h2>' +
      '<p class="page-head__sub">' + (options.subtitle || '') + '</p></div>' +
      (options.actions ? '<div class="page-head__actions">' + options.actions + '</div>' : '') +
      '</div>';
  }

  function mount() {
    var page = document.body.getAttribute('data-page');
    var session = OSAS.auth.requireAuth();
    if (!session) { return null; }

    var meta = PAGE_META[page] || { title: 'OSAS Portal', subtitle: '' };
    var shell = document.getElementById('shell');
    shell.className = 'app';
    shell.innerHTML = sidebarHtml(page, session) +
      '<div class="main">' + topbarHtml(meta, session) +
      '<main class="content" id="page-content"></main></div>';

    var sidebar = document.getElementById('sidebar');
    var backdrop = document.getElementById('sidebar-backdrop');

    function toggleSidebar(force) {
      var open = force === undefined ? !sidebar.classList.contains('sidebar--on') : force;
      sidebar.classList.toggle('sidebar--on', open);
      backdrop.classList.toggle('sidebar-backdrop--on', open);
    }
    document.getElementById('menu-btn').addEventListener('click', function () { toggleSidebar(); });
    backdrop.addEventListener('click', function () { toggleSidebar(false); });

    function signOut() {
      OSAS.components.confirm({
        title: 'Sign out of the portal?',
        alertTone: 'warning',
        alertTitle: 'Uncommitted changes',
        alertText: 'Any form you are currently editing will be discarded when you sign out.',
        message: 'You will be returned to the portal sign-in screen.',
        confirmLabel: 'Sign out',
        onConfirm: function () {
          OSAS.auth.logout();
          window.location.href = 'index.html';
        }
      });
    }
    document.getElementById('sign-out').addEventListener('click', signOut);
    document.getElementById('sign-out-top').addEventListener('click', signOut);
    document.getElementById('print-view').addEventListener('click', function () { window.print(); });

    OSAS.components.flashFromQuery();
    return { session: session, meta: meta, content: document.getElementById('page-content') };
  }

  function resetDemoData() {
    OSAS.components.confirm({
      title: 'Restore the demo dataset?',
      alertTone: 'warning',
      alertTitle: 'This cannot be undone',
      alertText: 'All records created or edited in this browser will be replaced by the original seeded dataset.',
      confirmLabel: 'Restore dataset',
      onConfirm: function () {
        OSAS.store.reset();
        try { window.localStorage.removeItem(OSAS.CONFIG.sessionKey); } catch (err) { /* ignore */ }
        window.location.href = 'index.html?flash=' +
          encodeURIComponent('Demo dataset restored. Please sign in again.');
      }
    });
  }

  return {
    NAV: NAV, PAGE_META: PAGE_META, mount: mount, pageHead: pageHead,
    resetDemoData: resetDemoData, badgeCounts: badgeCounts
  };
})();
