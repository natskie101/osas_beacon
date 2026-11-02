/* ==========================================================================
   Module 12 — User Activity & Tracking
   Read-only timeline of sign-ins, support activity and account changes.
   Coordinators see every user; the technical Administrator uses the same
   page to track student account activity.
   ========================================================================== */
(function () {
  "use strict";
  var C = OSAS.components,
    U = OSAS.util;
  var shell = OSAS.shell.mount();
  if (!shell) {
    return;
  }
  var content = shell.content;
  var OPTIONS = OSAS.store.options();
  var page = null;

  function icon(name, size) {
    return OSAS.icons.icon(name, size);
  }

  function fullName(row) {
    return row.firstName + " " + row.lastName;
  }

  function eventsFor(userId) {
    return OSAS.activity.entries(userId);
  }

  function statsHtml() {
    var users = OSAS.store.all("users");
    var students = users.filter(function (row) {
      return row.role === "Student";
    }).length;
    var staff = users.filter(function (row) {
      return row.role === "Coordinator" || row.role === "Administrator";
    }).length;
    var events = OSAS.store.all("auditLog");
    var signIns = events.filter(function (row) {
      return row.action === "auth.login";
    }).length;
    return (
      '<div class="stats">' +
      C.statCard(
        "Tracked Accounts",
        OSAS.fmt.number(users.length),
        '<span class="u-muted">' +
          students +
          " student &middot; " +
          staff +
          " staff</span>",
      ) +
      C.statCard(
        "Sign-Ins Recorded",
        OSAS.fmt.number(signIns),
        '<span class="u-muted">local audit trail</span>',
      ) +
      C.statCard(
        "Activity Events",
        OSAS.fmt.number(events.length),
        '<span class="u-muted">logins, tickets, account changes</span>',
      ) +
      "</div>"
    );
  }

  function columns() {
    return [
      {
        label: "User &amp; Role",
        render: function (row) {
          return (
            '<div class="cell-user">' +
            C.avatar(row.firstName, row.lastName) +
            '<div><div class="cell-title">' +
            U.esc(fullName(row)) +
            "</div>" +
            '<div class="cell-sub"><span class="badge badge--brand">' +
            U.esc(row.role) +
            "</span></div></div></div>"
          );
        },
      },
      {
        label: "Contact",
        render: function (row) {
          return (
            '<div class="cell-title">' +
            U.esc(row.email) +
            "</div>" +
            '<div class="cell-sub mono">' +
            U.esc(row.studentId || "No student ID") +
            "</div>"
          );
        },
      },
      {
        label: "Last Sign-In",
        render: function (row) {
          if (!row.lastLogin) {
            return '<span class="u-muted">Never signed in</span>';
          }
          return (
            '<div class="tkt-date">' +
            OSAS.fmt.shortDate(row.lastLogin) +
            "</div>" +
            '<div class="tkt-time">' +
            OSAS.fmt.time(row.lastLogin) +
            "</div>"
          );
        },
      },
      {
        label: "Account Status",
        render: function (row) {
          return C.badge(row.status);
        },
      },
      {
        label: "Recorded Activity",
        render: function (row) {
          var events = eventsFor(row.id);
          if (!events.length) {
            return '<span class="u-muted">No recorded events</span>';
          }
          return (
            '<div class="cell-title">' +
            events.length +
            " event" +
            (events.length === 1 ? "" : "s") +
            "</div>" +
            '<div class="cell-sub">' +
            U.esc(events[0].title) +
            " &middot; " +
            U.esc(OSAS.fmt.shortDate(events[0].at)) +
            "</div>"
          );
        },
      },
      {
        label: "Actions",
        align: "right",
        render: function (row) {
          return (
            '<div class="cell-actions">' +
            '<button type="button" class="btn btn--outline btn--sm" data-action="timeline" data-id="' +
            U.esc(row.id) +
            '">' +
            icon("clock", 12) +
            " View Timeline</button></div>"
          );
        },
      },
    ];
  }

  page = OSAS.pagekit.listPage({
    content: content,
    title: "User Activity & Tracking",
    subtitle: OSAS.auth.isTechnicalAdmin()
      ? "Track student sign-ins, support activity and account changes from the technical desk."
      : "Review sign-in history, support activity and account changes for every portal user.",
    resource: "users",
    defaultState: {
      search: "",
      role: "All",
      page: 1,
    },
    filters: [
      {
        type: "search",
        key: "search",
        id: "act-search",
        label: "Search Accounts",
        placeholder: "Search by name, email or student ID…",
      },
      {
        type: "select",
        key: "role",
        id: "act-role",
        label: "Role",
        values: OPTIONS.roles,
      },
    ],
    filter: function (rows, state) {
      var list = U.search(rows, state.search, [
        "firstName",
        "lastName",
        "email",
        "studentId",
      ]);
      if (state.role !== "All") {
        list = list.filter(function (row) {
          return row.role === state.role;
        });
      }
      return U.sortBy(list, "lastName", "asc");
    },
    stats: function () {
      return statsHtml();
    },
    toolbar: function () {
      return (
        '<div class="table-toolbar"><div class="panel-title">Account Activity Timeline</div>' +
        '<span class="chip">' +
        (OSAS.auth.isTechnicalAdmin()
          ? "Technical Administrator access"
          : "Coordinator access") +
        "</span></div>"
      );
    },
    columns: function () {
      return columns();
    },
    footerLabel: function (info) {
      return (
        "Showing " +
        info.from +
        "–" +
        info.to +
        " of " +
        info.total +
        " tracked accounts"
      );
    },
    empty: "No accounts match the current filters.",
    onAction: function (action, id) {
      if (action === "timeline") {
        OSAS.activity.open(id);
      }
    },
  });
})();
