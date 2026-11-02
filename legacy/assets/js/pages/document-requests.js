/* ==========================================================================
   Module 12 — Document Requests Management  (document-requests.html)
   Processes certificate and document requests filed through the student
   portal: Submitted -> Under Review -> Ready for Pick-up -> Released.
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

  var TABS = [
    { key: "All", label: "All Requests" },
    { key: "Submitted", label: "Submitted" },
    { key: "Under Review", label: "Under Review" },
    { key: "Ready for Pick-up", label: "Ready for Pick-up" },
    { key: "Released", label: "Released" },
    { key: "Rejected", label: "Rejected" },
  ];

  function tabsHtml(state) {
    return (
      '<div class="toolbar"><div class="tabs">' +
      TABS.map(function (tab) {
        return (
          '<button type="button" class="tab' +
          (state.status === tab.key ? " tab--on" : "") +
          '" data-action="tab" data-id="' +
          U.esc(tab.key) +
          '">' +
          tab.label +
          "</button>"
        );
      }).join("") +
      "</div></div>"
    );
  }

  var page = OSAS.pagekit.listPage({
    content: shell.content,
    source: function () {
      return OSAS.store.all("documentRequests");
    },
    title: "Document Requests Management",
    subtitle:
      "Process certificate and document requests submitted by students through the portal.",
    actions:
      '<button type="button" class="btn btn--brand" data-action="export">' +
      OSAS.icons.icon("download", 13) +
      " Export CSV</button>",
    defaultState: {
      search: "",
      status: "All",
      documentType: "All",
      deliveryMode: "All",
      page: 1,
    },
    filters: [
      {
        key: "search",
        type: "search",
        id: "dr-search",
        label: "Search",
        placeholder: "Search code, student, document…",
      },
      {
        key: "documentType",
        id: "dr-type",
        label: "Document Type",
        values: OPTIONS.documentTypes,
      },
      {
        key: "deliveryMode",
        id: "dr-delivery",
        label: "Delivery Mode",
        values: OPTIONS.deliveryModes,
      },
    ],
    filter: function (rows, state) {
      var list = U.search(rows, state.search, [
        "code",
        "student",
        "studentEmail",
        "studentId",
        "documentType",
        "purpose",
      ]);
      if (state.status !== "All") {
        list = list.filter(function (row) {
          return row.status === state.status;
        });
      }
      if (state.documentType !== "All") {
        list = list.filter(function (row) {
          return row.documentType === state.documentType;
        });
      }
      if (state.deliveryMode !== "All") {
        list = list.filter(function (row) {
          return row.deliveryMode === state.deliveryMode;
        });
      }
      return list;
    },
    sort: { field: "requestedAt", dir: "desc" },
    stats: function () {
      var stats = OSAS.analytics.documentRequests(
        OSAS.store.all("documentRequests"),
      );
      return (
        '<div class="stats">' +
        C.statCard(
          "Submitted",
          OSAS.fmt.number(stats.submitted),
          '<span class="badge badge--pending">Awaiting review</span>' +
            '<span class="u-muted">New requests from the portal</span>',
        ) +
        C.statCard(
          "Under Review",
          OSAS.fmt.number(stats.underReview),
          '<span class="badge badge--progress">Records checking</span>' +
            '<span class="u-muted">' +
            stats.awaitingAction +
            " total in the queue</span>",
        ) +
        C.statCard(
          "Ready for Pick-up",
          OSAS.fmt.number(stats.ready),
          '<span class="badge badge--scheduled">Signed &amp; sealed</span>' +
            '<span class="u-muted">Waiting for the student to claim</span>',
        ) +
        C.statCard(
          "Released",
          OSAS.fmt.number(stats.released),
          '<span class="badge badge--completed">Completed</span>' +
            '<span class="u-muted">' +
            stats.rejected +
            " rejected &middot; " +
            stats.cancelled +
            " cancelled</span>",
        ) +
        "</div>"
      );
    },
    beforeTable: function (state) {
      return tabsHtml(state);
    },
    toolbar:
      '<div class="table-toolbar"><div class="panel-title">Document Request Queue</div>' +
      '<span class="u-muted">Open a request to move it through the release workflow</span></div>',
    footerLabel: function (info) {
      return (
        "Showing " +
        info.from +
        "–" +
        info.to +
        " of " +
        info.total +
        " document requests"
      );
    },
    empty: "No document requests match the current filters.",
    columns: function () {
      return [
        {
          label: "Request & Student",
          render: function (row) {
            return (
              '<div class="cell-title mono">' +
              U.esc(row.code) +
              "</div>" +
              '<div class="cell-sub">' +
              U.esc(row.student) +
              " &middot; " +
              U.esc(row.studentId || "No ID") +
              "</div>" +
              '<div class="cell-sub">' +
              U.esc(row.studentEmail) +
              "</div>"
            );
          },
        },
        {
          label: "Document & Purpose",
          render: function (row) {
            return (
              '<div class="cell-title">' +
              U.esc(row.documentType) +
              "</div>" +
              '<div class="cell-sub">' +
              U.esc(U.truncate(row.purpose, 74)) +
              "</div>"
            );
          },
        },
        {
          label: "Copies & Delivery",
          render: function (row) {
            return (
              '<div class="cell-title">' +
              U.esc(String(row.copies || 1)) +
              " cop" +
              (String(row.copies || 1) === "1" ? "y" : "ies") +
              " &middot; " +
              U.esc(row.deliveryMode) +
              "</div>" +
              '<div class="cell-sub">' +
              U.esc(row.program || "—") +
              "</div>"
            );
          },
        },
        {
          label: "Requested",
          render: function (row) {
            return (
              '<div class="cell-title">' +
              OSAS.fmt.shortDate(row.requestedAt) +
              "</div>" +
              '<div class="cell-sub">' +
              OSAS.fmt.time(row.requestedAt) +
              "</div>"
            );
          },
        },
        {
          label: "Priority",
          render: function (row) {
            return C.priority(row.priority);
          },
        },
        {
          label: "Status",
          render: function (row) {
            return C.badge(row.status);
          },
        },
        {
          label: "Actions",
          align: "right",
          render: function (row) {
            var open =
              row.status === "Submitted" ||
              row.status === "Under Review" ||
              row.status === "Ready for Pick-up";
            return (
              '<div class="cell-actions">' +
              (open
                ? '<button type="button" class="btn btn--brand btn--sm" data-action="process" data-id="' +
                  U.esc(row.id) +
                  '">Process</button>'
                : "") +
              '<button type="button" class="btn btn--ghost btn--sm" data-action="view" data-id="' +
              U.esc(row.id) +
              '">View</button></div>'
            );
          },
        },
      ];
    },
    onAction: function (action, id, api) {
      if (action === "export") {
        exportCsv(api.rows());
        return;
      }
      if (action === "tab") {
        api.reset({ status: id });
        return;
      }
      var request = OSAS.store.find("documentRequests", id);
      if (!request) {
        return;
      }
      if (action === "view" || action === "process") {
        OSAS.forms.documentRequestView({
          request: request,
          onUpdated: function () {
            api.render();
          },
        });
      }
    },
  });

  function exportCsv(rows) {
    U.exportCsv(
      "beacon-document-requests.csv",
      [
        { label: "Reference", value: "code" },
        { label: "Student", value: "student" },
        { label: "Student ID", value: "studentId" },
        { label: "Email", value: "studentEmail" },
        { label: "Program", value: "program" },
        { label: "Year", value: "year" },
        { label: "Document Type", value: "documentType" },
        { label: "Purpose", value: "purpose" },
        { label: "Copies", value: "copies" },
        { label: "Delivery Mode", value: "deliveryMode" },
        { label: "Priority", value: "priority" },
        { label: "Status", value: "status" },
        { label: "Processed By", value: "processedBy" },
        {
          label: "Requested",
          value: function (row) {
            return OSAS.fmt.dateTime(row.requestedAt);
          },
        },
        {
          label: "Released",
          value: function (row) {
            return row.releasedAt ? OSAS.fmt.dateTime(row.releasedAt) : "";
          },
        },
      ],
      rows,
    );
    C.toast(
      "success",
      "Document requests exported",
      rows.length + " records written to CSV.",
    );
  }

  return page;
})();
