/* ==========================================================================
   BEACON OSAS Portal — application shell
   Renders the numbered module sidebar, top bar and page frame, then hands the
   #page-content element to the page script.
   ========================================================================== */
OSAS.shell = (function () {
  var NAV = [
    {
      n: 1,
      label: "Dashboard",
      href: "dashboard.html",
      page: "dashboard",
      icon: "dashboard",
    },
    {
      n: 2,
      label: "Announcements",
      href: "announcements.html",
      page: "announcements",
      icon: "megaphone",
    },
    {
      n: 3,
      label: "Rules & Regulations",
      href: "regulations.html",
      page: "regulations",
      icon: "scale",
    },
    {
      n: 4,
      label: "Orientation Content",
      href: "orientation.html",
      page: "orientation",
      icon: "graduation",
    },
    {
      n: 5,
      label: "Events & Activities",
      href: "events.html",
      page: "events",
      icon: "calendar",
    },
    {
      n: 6,
      label: "Lost & Found",
      href: "lost-found.html",
      page: "lost-found",
      icon: "search",
      badge: "lostFound",
    },
    {
      n: 7,
      label: "Document Requests",
      href: "document-requests.html",
      page: "document-requests",
      icon: "printer",
      badge: "docRequests",
    },
    {
      n: 8,
      label: "Viewing & Tracking",
      href: "analytics.html",
      page: "analytics",
      icon: "activity",
    },
    {
      n: 9,
      label: "Support & Concerns",
      href: "support.html",
      page: "support",
      icon: "headset",
      badge: "tickets",
    },
    {
      n: 10,
      label: "User Management",
      href: "users.html",
      page: "users",
      icon: "users",
    },
    {
      n: 11,
      label: "Reports",
      href: "reports.html",
      page: "reports",
      icon: "fileText",
    },
  ];

  var STUDENT_NAV = [
    {
      n: 1,
      label: "Dashboard",
      href: "student/dashboard.html",
      page: "student-dashboard",
      icon: "dashboard",
    },
    {
      n: 2,
      label: "Orientation",
      href: "student/orientation.html",
      page: "student-orientation",
      icon: "graduation",
    },
    {
      n: 3,
      label: "Announcements",
      href: "student/announcements.html",
      page: "student-announcements",
      icon: "megaphone",
    },
    {
      n: 4,
      label: "Events",
      href: "student/events.html",
      page: "student-events",
      icon: "calendar",
    },
    {
      n: 5,
      label: "Feedback & Support",
      href: "student/support.html",
      page: "student-support",
      icon: "headset",
      badge: "tickets",
    },
  ];

  var PAGE_META = {
    dashboard: {
      title: "Coordinator Dashboard",
      subtitle:
        "Consolidated real-time status of all OSAS portal modules, content operations and student support activity.",
    },
    announcements: {
      title: "Announcements",
      subtitle:
        "Create, publish, and organize school announcements and information notices across the student portal.",
    },
    regulations: {
      title: "Rules & Regulations",
      subtitle:
        "Maintain and communicate school policies, the student code of conduct, and mandated regulations.",
    },
    orientation: {
      title: "Orientation",
      subtitle:
        "Manage onboarding materials, orientation modules, and information guides for incoming students.",
    },
    events: {
      title: "Events & Activities",
      subtitle:
        "Schedule, publish and monitor university events, seminars, and student activities.",
    },
    analytics: {
      title: "Viewing & Tracking",
      subtitle:
        "Monitor portal content reach, engagement quality, and viewing activity across all broadcasting modules.",
    },
    support: {
      title: "Support & Concerns",
      subtitle:
        "Manage student inquiries, complaints, and feedback, and monitor the OSAS service response performance.",
    },
    users: {
      title: "User Account & Access Management",
      subtitle:
        "Manage portal access and system roles for all students and staff.",
    },
    reports: {
      title: "Reports",
      subtitle:
        "Generate and export official reports for portal activity, compliance, and support operations.",
    },
    "lost-found": {
      title: "Lost & Found",
      subtitle:
        "Review reported lost and found items, verify ownership claims, and record items returned to students.",
    },
    "document-requests": {
      title: "Document Requests",
      subtitle:
        "Process certificate and document requests submitted by students through the portal.",
    },
  };

  function badgeCounts() {
    return {
      tickets: OSAS.analytics.openTickets(OSAS.store.all("tickets")).length,
      broadcasts: OSAS.store.all("broadcasts").filter(function (b) {
        return b.status === "Pending" || b.status === "Scheduled";
      }).length,
      lostFound: OSAS.store.all("lostFound").filter(function (row) {
        return row.status === "Claim Pending";
      }).length,
      docRequests: OSAS.store.all("documentRequests").filter(function (row) {
        return row.status === "Submitted" || row.status === "Under Review";
      }).length,
    };
  }
  function sidebarHtml(activePage, session) {
    var counts = badgeCounts();
    var items = NAV.map(function (item) {
      var badge =
        item.badge && counts[item.badge]
          ? '<span class="nav__badge">' + counts[item.badge] + "</span>"
          : "";
      return (
        '<li class="nav__item' +
        (item.page === activePage ? " nav__item--active" : "") +
        '">' +
        '<a href="' +
        item.href +
        '"><span class="nav__num">' +
        item.n +
        ".</span>" +
        '<span class="nav__ico">' +
        OSAS.icons.icon(item.icon, 15) +
        "</span>" +
        "<span>" +
        OSAS.util.esc(item.label) +
        "</span>" +
        badge +
        "</a></li>"
      );
    }).join("");

    return (
      '<aside class="sidebar" id="sidebar">' +
      '<div class="sidebar__brand">' +
      OSAS.icons.beacon(26, 2.2) +
      '<div><div class="sidebar__wordmark">BEACON</div>' +
      '<div class="sidebar__tag">Coordinator Portal</div></div>' +
      '<button type="button" class="sidebar__close" id="sidebar-close" aria-label="Close navigation">' +
      OSAS.icons.icon("close", 16) +
      "</button></div>" +
      '<nav class="nav" aria-label="OSAS portal navigation"><ul>' +
      items +
      "</ul></nav>" +
      '<div class="sidebar__foot"><div class="sidebar__user">' +
      OSAS.components.avatar(session.user.firstName, session.user.lastName) +
      '<div><div class="sidebar__user-name">' +
      OSAS.util.esc(session.user.firstName + " " + session.user.lastName) +
      "</div>" +
      '<div class="sidebar__user-role">' +
      OSAS.util.esc(session.user.role) +
      "</div></div>" +
      "</div>" +
      '<button type="button" class="sidebar__signout" id="sign-out">' +
      OSAS.icons.icon("logout", 15) +
      "<span>Sign out</span></button>" +
      "</div></aside>" +
      '<div class="sidebar-backdrop" id="sidebar-backdrop"></div>'
    );
  }

  function topbarHtml(meta, session) {
    var counts = badgeCounts();
    var initials = OSAS.fmt.initials(
      session.user.firstName,
      session.user.lastName,
    );
    return (
      '<header class="topbar">' +
      '<button type="button" class="icon-btn menu-btn" id="menu-btn" aria-label="Show modules">' +
      OSAS.icons.icon("menu", 18) +
      "</button>" +
      '<h1 class="topbar__title">' +
      OSAS.util.esc(meta.title) +
      "</h1>" +
      '<div class="topbar__actions">' +
      '<a class="icon-btn topbar__bell" href="broadcasts.html" title="Notification hub (' +
      counts.broadcasts +
      ' queued)" aria-label="Notifications">' +
      OSAS.icons.icon("bell", 18) +
      '<span class="topbar__bell-count"' +
      (counts.broadcasts ? "" : " hidden") +
      ">" +
      counts.broadcasts +
      "</span></a>" +
      '<button type="button" class="topbar__avatar" id="top-avatar" aria-label="Account menu">' +
      OSAS.util.esc(initials) +
      "</button>" +
      "</div></header>"
    );
  }

  function openAccount(session, onSignOut) {
    var user = session.user;
    OSAS.components.modal({
      size: "sm",
      title: "Account",
      subtitle: "Signed in to the BEACON OSAS portal",
      body:
        '<div class="acct">' +
        OSAS.components.avatar(user.firstName, user.lastName) +
        '<div><div class="acct__name">' +
        OSAS.util.esc(user.firstName + " " + user.lastName) +
        "</div>" +
        '<div class="acct__role">' +
        OSAS.util.esc(user.role) +
        "</div>" +
        '<div class="acct__mail">' +
        OSAS.util.esc(user.email || "") +
        "</div></div></div>",
      footer:
        '<button type="button" class="btn btn--ghost" data-close>Close</button>' +
        '<button type="button" class="btn btn--primary" id="acct-signout">' +
        OSAS.icons.icon("logout", 14) +
        " Sign out</button>",
      onMount: function (root, close) {
        var btn = root.querySelector("#acct-signout");
        if (btn) {
          btn.addEventListener("click", function () {
            close();
            onSignOut();
          });
        }
      },
    });
  }

  function pageHead(options) {
    return (
      '<div class="page-head">' +
      '<div><h2 class="page-head__title">' +
      OSAS.util.esc(options.title) +
      "</h2>" +
      '<p class="page-head__sub">' +
      (options.subtitle || "") +
      "</p></div>" +
      (options.actions
        ? '<div class="page-head__actions">' + options.actions + "</div>"
        : "") +
      "</div>"
    );
  }

  function mount() {
    var page = document.body.getAttribute("data-page");
    var session = OSAS.auth.requireAuth();
    if (!session) {
      return null;
    }

    var meta = PAGE_META[page] || { title: "OSAS Portal", subtitle: "" };
    var shell = document.getElementById("shell");
    shell.className = "app";
    shell.innerHTML =
      sidebarHtml(page, session) +
      '<div class="main">' +
      topbarHtml(meta, session) +
      '<main class="content" id="page-content"></main>' +
      '<footer class="app-foot">' +
      "<span>BEACON OSAS Portal &middot; Office of Student Affairs and Services</span>" +
      "<span>Southway College of Technology &middot; AY 2026-2027</span>" +
      "</footer></div>";

    var sidebar = document.getElementById("sidebar");
    var backdrop = document.getElementById("sidebar-backdrop");

    function toggleSidebar(force) {
      var open =
        force === undefined
          ? !sidebar.classList.contains("sidebar--on")
          : force;
      sidebar.classList.toggle("sidebar--on", open);
      backdrop.classList.toggle("sidebar-backdrop--on", open);
    }
    document.getElementById("menu-btn").addEventListener("click", function () {
      toggleSidebar();
    });
    backdrop.addEventListener("click", function () {
      toggleSidebar(false);
    });
    document
      .getElementById("sidebar-close")
      .addEventListener("click", function () {
        toggleSidebar(false);
      });

    function signOut() {
      OSAS.components.confirm({
        title: "Sign out of the portal?",
        alertTone: "warning",
        alertTitle: "Uncommitted changes",
        alertText:
          "Any form you are currently editing will be discarded when you sign out.",
        message: "You will be returned to the portal sign-in screen.",
        confirmLabel: "Sign out",
        onConfirm: function () {
          OSAS.auth.logout();
          window.location.href = "../index.html";
        },
      });
    }
    document.getElementById("sign-out").addEventListener("click", signOut);
    document
      .getElementById("top-avatar")
      .addEventListener("click", function () {
        openAccount(session, signOut);
      });

    OSAS.components.flashFromQuery();
    return {
      session: session,
      meta: meta,
      content: document.getElementById("page-content"),
    };
  }

  function resetDemoData() {
    OSAS.components.confirm({
      title: "Restore the demo dataset?",
      alertTone: "warning",
      alertTitle: "This cannot be undone",
      alertText:
        "All records created or edited in this browser will be replaced by the original seeded dataset.",
      confirmLabel: "Restore dataset",
      onConfirm: function () {
        OSAS.store.reset();
        try {
          window.localStorage.removeItem(OSAS.CONFIG.sessionKey);
        } catch (err) {
          /* ignore */
        }
        window.location.href =
          "../index.html?flash=" +
          encodeURIComponent("Demo dataset restored. Please sign in again.");
      },
    });
  }

  return {
    NAV: NAV,
    PAGE_META: PAGE_META,
    mount: mount,
    pageHead: pageHead,
    resetDemoData: resetDemoData,
    badgeCounts: badgeCounts,
  };
})();
