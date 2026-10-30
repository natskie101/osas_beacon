/* ==========================================================================
   Module 11 — Lost & Found Management  (lost-found.html)
   Item board with ownership claims and hand-over records. Cards mirror the
   student browse board; staff get the claim count and inline actions.
   ========================================================================== */
(function () {
  "use strict";
  var C = OSAS.components,
    U = OSAS.util;
  var shell = OSAS.shell.mount();
  if (!shell) {
    return;
  }
  var OPTIONS = OSAS.store.options();

  function pendingClaims(row) {
    return (row.claims || []).filter(function (claim) {
      return claim.status === "Pending";
    });
  }

  /* Status/type pill shared by both portals: open Found items read "Found",
     open Lost items read "Lost", and closed items show their workflow state. */
  function stateMeta(row) {
    if (row.status === "Returned") {
      return { label: "Claimed", tone: "green" };
    }
    if (row.status === "Claim Pending") {
      return { label: "Claim pending", tone: "rose" };
    }
    if (row.status === "Unclaimed") {
      return { label: "Unclaimed", tone: "orange" };
    }
    if (row.status === "Archived") {
      return { label: "Archived", tone: "gray" };
    }
    return row.type === "Lost"
      ? { label: "Lost", tone: "amber" }
      : { label: "Found", tone: "blue" };
  }

  function categoryLetter(row) {
    return String(row.category || "?").charAt(0).toUpperCase();
  }

  function card(row) {
    var meta = stateMeta(row);
    var pending = pendingClaims(row).length;
    return (
      '<article class="lf-card">' +
      '<div class="lf-card__art lf-card__art--' +
      meta.tone +
      '">' +
      '<span class="lf-card__letter">' +
      U.esc(categoryLetter(row)) +
      "</span>" +
      '<span class="lf-card__state lf-card__state--' +
      meta.tone +
      '">' +
      '<span class="lf-card__state-dot"></span>' +
      U.esc(meta.label) +
      "</span></div>" +
      '<div class="lf-card__body">' +
      '<div class="lf-card__meta-top"><span class="lf-card__code">' +
      U.esc(row.code) +
      "</span>" +
      '<span class="lf-card__sep">&middot;</span>' +
      '<span class="lf-card__cat">' +
      U.esc(row.category) +
      "</span></div>" +
      '<h3 class="lf-card__title">' +
      U.esc(row.title) +
      "</h3>" +
      '<div class="lf-card__meta">' +
      '<span class="lf-card__meta-row">' +
      OSAS.icons.icon("pin", 11) +
      "<span>" +
      U.esc(row.location) +
      "</span></span>" +
      '<span class="lf-card__meta-row">' +
      OSAS.icons.icon("calendar", 11) +
      "<span>" +
      OSAS.fmt.shortDate(row.dateOccurred) +
      "</span></span>" +
      '<span class="lf-card__meta-row">' +
      OSAS.icons.icon("user", 11) +
      "<span>" +
      U.esc(row.reportedBy) +
      "</span></span>" +
      "</div>" +
      '<div class="lf-card__foot"><div class="lf-card__actions">' +
      '<button type="button" class="lf-card__btn" data-action="view" data-id="' +
      U.esc(row.id) +
      '">View</button>' +
      (pending
        ? '<button type="button" class="lf-card__btn lf-card__btn--brand" data-action="verify" data-id="' +
          U.esc(row.id) +
          '">Verify (' +
          pending +
          ")</button>"
        : "") +
      "</div></div></div></article>"
    );
  }

  var page = OSAS.pagekit.listPage({
    content: shell.content,
    source: function () {
      return OSAS.store.all("lostFound");
    },
    title: "Lost & Found Management",
    subtitle:
      "Review reported lost and found items, verify ownership claims, and record items returned to students.",
    actions:
      '<button type="button" class="btn btn--brand" data-action="export">' +
      OSAS.icons.icon("download", 13) +
      " Export CSV</button>",
    defaultState: {
      search: "",
      type: "All",
      status: "All",
      category: "All",
      page: 1,
    },
    filters: [
      {
        key: "search",
        type: "search",
        id: "lf-search",
        label: "Search",
        placeholder: "Search code, item, reporter…",
      },
      {
        key: "type",
        id: "lf-type",
        label: "Report Type",
        values: ["Lost", "Found"],
      },
      {
        key: "status",
        id: "lf-status",
        label: "Status",
        values: OPTIONS.lostFoundStatuses,
      },
      {
        key: "category",
        id: "lf-category",
        label: "Category",
        values: OPTIONS.lostFoundCategories,
      },
    ],
    filter: function (rows, state) {
      var list = U.search(rows, state.search, [
        "code",
        "title",
        "description",
        "reportedBy",
        "reporterEmail",
        "location",
        "category",
      ]);
      if (state.type !== "All") {
        list = list.filter(function (row) {
          return row.type === state.type;
        });
      }
      if (state.status !== "All") {
        list = list.filter(function (row) {
          return row.status === state.status;
        });
      }
      if (state.category !== "All") {
        list = list.filter(function (row) {
          return row.category === state.category;
        });
      }
      return list;
    },
    sort: { field: "postedAt", dir: "desc" },
    stats: function () {
      var stats = OSAS.analytics.lostFound(OSAS.store.all("lostFound"));
      return (
        '<div class="stats">' +
        C.statCard(
          "Open Reports",
          OSAS.fmt.number(stats.open),
          '<span class="badge badge--scheduled">' +
            stats.total +
            " total items</span>" +
            '<span class="u-muted">' +
            stats.lost +
            " lost &middot; " +
            stats.found +
            " found</span>",
        ) +
        C.statCard(
          "Claim Pending",
          OSAS.fmt.number(stats.claimPending),
          '<span class="badge badge--pending">' +
            stats.pendingClaims +
            " claim" +
            (stats.pendingClaims === 1 ? "" : "s") +
            " to verify</span>" +
            '<span class="u-muted">Ownership checks at the OSAS office</span>',
        ) +
        C.statCard(
          "Returned to Owner",
          OSAS.fmt.number(stats.returned),
          '<span class="badge badge--resolved">Verified hand-over</span>' +
            '<span class="u-muted">Recorded with claimant and date</span>',
        ) +
        C.statCard(
          "Unclaimed",
          OSAS.fmt.number(stats.unclaimed),
          '<span class="badge badge--warning">For disposal review</span>' +
            '<span class="u-muted">' +
            stats.archived +
            " archived reports</span>",
        ) +
        "</div>"
      );
    },
    table: false,
    beforeTable: function () {
      return (
        '<div class="lf-board-head">' +
        '<div class="panel-title">Reported Items Directory</div>' +
        '<span class="u-muted">Claims are verified at the OSAS office before an item is released</span>' +
        "</div>"
      );
    },
    afterTable: function (state, rows, info) {
      if (!rows.length) {
        return C.emptyState("No lost or found items match the current filters.");
      }
      return (
        '<div class="lf-grid">' +
        info.items.map(card).join("") +
        "</div>" +
        C.pager(
          info,
          "Showing " +
            info.from +
            "–" +
            info.to +
            " of " +
            info.total +
            " reported items",
        )
      );
    },
    onAction: function (action, id, api) {
      if (action === "export") {
        exportCsv(api.rows());
        return;
      }
      var item = OSAS.store.find("lostFound", id);
      if (!item) {
        return;
      }
      if (action === "view" || action === "verify") {
        OSAS.forms.lostFoundView({
          item: item,
          onUpdated: function () {
            api.render();
          },
        });
      }
    },
  });

  function exportCsv(rows) {
    U.exportCsv(
      "beacon-lost-and-found.csv",
      [
        { label: "Reference", value: "code" },
        { label: "Report Type", value: "type" },
        { label: "Item", value: "title" },
        { label: "Category", value: "category" },
        { label: "Reported By", value: "reportedBy" },
        { label: "Reporter Email", value: "reporterEmail" },
        { label: "Student ID", value: "studentId" },
        { label: "Location", value: "location" },
        {
          label: "Date of Incident",
          value: function (row) {
            return OSAS.fmt.dateTime(row.dateOccurred);
          },
        },
        { label: "Custody", value: "custody" },
        { label: "Status", value: "status" },
        {
          label: "Claims",
          value: function (row) {
            return (row.claims || []).length;
          },
        },
        { label: "Returned To", value: "returnedTo" },
        {
          label: "Returned At",
          value: function (row) {
            return row.returnedAt ? OSAS.fmt.dateTime(row.returnedAt) : "";
          },
        },
        {
          label: "Date Reported",
          value: function (row) {
            return OSAS.fmt.dateTime(row.postedAt);
          },
        },
      ],
      rows,
    );
    C.toast(
      "success",
      "Lost & found exported",
      rows.length + " records written to CSV.",
    );
  }

  return page;
})();
