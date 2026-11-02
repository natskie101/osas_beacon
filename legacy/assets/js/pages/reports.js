(function () {
  "use strict";
  window.addEventListener("error", function (e) {
    console.error("[Reports] Global Error", e.message, e.filename, e.lineno);
  });
  var C = OSAS.components,
    U = OSAS.util;
  var shell = OSAS.shell.mount();
  if (!shell) {
    console.warn("[Reports] Shell mount failed - auth required");
    return;
  }
  var content = shell.content;
  var OPTIONS = OSAS.store.options();

  var REPORT_CATEGORY = "Orientation Content";
  function orientationReports() {
    return OSAS.store.all("reports").filter(function (r) {
      return r.category === REPORT_CATEGORY;
    });
  }

  function mandatoryModules() {
    return OSAS.store.all("modules").filter(function (r) {
      return r.mandatory && r.status === "Published";
    });
  }
  function students() {
    return OSAS.store.all("users").filter(function (r) {
      return r.role === "Student";
    });
  }
  /* Mandatory module rows enriched with tracked cohort completion. */
  function mandatoryWithProgress() {
    var mods = mandatoryModules();
    var list = students();
    return mods.map(function (mod) {
      var tracked = list.filter(function (row) {
        return (row.orientationModules || []).some(function (m) {
          return m.code === mod.code;
        });
      }).length;
      var out = {};
      Object.keys(mod).forEach(function (k) {
        out[k] = mod[k];
      });
      out.tracked = tracked;
      out.ofStudents = list.length;
      out.trackedPct = list.length
        ? Math.round((tracked / list.length) * 100)
        : 0;
      return out;
    });
  }
  /* Per-student mandatory orientation progress for the report body. */
  function studentProgressRows() {
    var mods = mandatoryModules();
    return students().map(function (row) {
      var done = (row.orientationModules || []).length;
      return {
        id: row.id,
        studentId: row.studentId,
        name: row.firstName + " " + row.lastName,
        program: row.program,
        department: row.department,
        year: row.year,
        completed: done,
        total: mods.length,
        pct: row.orientationPct || 0,
        events: row.eventsAttended || 0,
        hours: row.coCurricularHrs || 0,
        status:
          (row.orientationPct || 0) >= 100
            ? "Compliant"
            : (row.orientationPct || 0) > 0
              ? "In Progress"
              : "Not Started",
      };
    });
  }
  function cohortSummary() {
    var rows = studentProgressRows();
    var mods = mandatoryModules().length || 1;
    var slots = rows.length * mods || 1;
    var done = rows.reduce(function (s, r) {
      return s + r.completed;
    }, 0);
    return {
      students: rows.length,
      modules: mandatoryModules().length,
      rate: Math.round((done / slots) * 100),
      compliant: rows.filter(function (r) {
        return r.status === "Compliant";
      }).length,
      inProgress: rows.filter(function (r) {
        return r.status === "In Progress";
      }).length,
      notStarted: rows.filter(function (r) {
        return r.status === "Not Started";
      }).length,
      events: rows.reduce(function (s, r) {
        return s + r.events;
      }, 0),
      hours: rows.reduce(function (s, r) {
        return s + r.hours;
      }, 0),
    };
  }

  /* Inline progress bar for module completion (shared styling with the
     student ledger so the two modules read identically). */
  function progressCell(pct, label) {
    var value = Math.max(0, Math.min(Number(pct) || 0, 100));
    var color = value >= 100 ? "green" : value >= 50 ? "blue" : "red";
    return (
      '<div class="trk-progress"><div class="trk-progress__label">' +
      U.esc(label || value + "%") +
      "</div>" +
      '<div class="trk-progress__bar"><div class="trk-progress__fill trk-progress__fill--' +
      color +
      '" style="width:' +
      value +
      '%"></div></div></div>'
    );
  }

  /* Mandatory Orientation Module Progress — per-module cohort completion.
     Relocated here from the Viewing & Tracking module. */
  function moduleProgressPanel() {
    var rows = mandatoryWithProgress();
    if (!rows.length) {
      return (
        '<div class="form-section">' +
        C.alert(
          "info",
          "No mandatory modules",
          "There are no published mandatory orientation modules to track.",
        ) +
        "</div>"
      );
    }
    var columns = [
      {
        label: "Mandatory Module",
        render: function (row) {
          return (
            '<div class="cell-title">' +
            U.esc(row.title) +
            "</div>" +
            '<div class="cell-sub mono">' +
            U.esc(row.code) +
            " &middot; " +
            U.esc(row.version) +
            " &middot; " +
            U.esc(row.category) +
            "</div>"
          );
        },
      },
      {
        label: "Students Completed",
        render: function (row) {
          return progressCell(
            row.trackedPct,
            row.tracked + " / " + row.ofStudents + " tracked students",
          );
        },
      },
      {
        label: "Portal Learners",
        align: "right",
        render: function (row) {
          return (
            '<div class="cell-title">' +
            OSAS.fmt.number(row.learners) +
            "</div>" +
            '<div class="cell-sub">' +
            row.completionRate +
            "% completion</div>"
          );
        },
      },
      {
        label: "Est. Duration",
        align: "right",
        render: function (row) {
          return U.esc(OSAS.fmt.duration(row.duration));
        },
      },
      {
        label: "Last Updated",
        render: function (row) {
          return OSAS.fmt.date(row.updatedAt);
        },
      },
      {
        label: "Compliance",
        render: function (row) {
          if (row.trackedPct >= 90) {
            return '<span class="badge badge--active">On Track</span>';
          }
          if (row.trackedPct >= 50) {
            return '<span class="badge badge--pending">Needs Push</span>';
          }
          return '<span class="badge badge--danger">Critical</span>';
        },
      },
      {
        label: "Actions",
        align: "right",
        render: function (row) {
          return (
            '<button type="button" class="btn btn--brand btn--sm" data-action="module-students" ' +
            'data-id="' +
            U.esc(row.code) +
            '">' +
            OSAS.icons.icon("eye", 11) +
            " View</button>"
          );
        },
      },
    ];
    return (
      '<div class="form-section">' +
      '<div class="section-title"><span>1.</span> Mandatory Orientation Module Progress</div>' +
      C.dataTable({
        columns: columns,
        rows: rows,
        empty: "No mandatory orientation modules are published yet.",
      }) +
      "</div>"
    );
  }

  /* Students who have completed a given mandatory module, newest completion
     first — the roster behind the progress panel's View action. */
  function moduleCompleters(code) {
    return students()
      .map(function (row) {
        var match = (row.orientationModules || []).filter(function (m) {
          return m.code === code;
        })[0];
        if (!match) {
          return null;
        }
        return {
          studentId: row.studentId,
          name: row.firstName + " " + row.lastName,
          program: row.program,
          year: row.year,
          date: match.date,
        };
      })
      .filter(Boolean)
      .sort(function (a, b) {
        return new Date(b.date) - new Date(a.date);
      });
  }

  /* Completion roster for one mandatory module — opened from the View action
     in the Mandatory Orientation Module Progress panel. */
  function moduleStudentsModal(code) {
    var mod = mandatoryWithProgress().filter(function (m) {
      return m.code === code;
    })[0];
    if (!mod) {
      C.toast(
        "warning",
        "Module not found",
        "That mandatory module is no longer published.",
      );
      return;
    }
    var rows = moduleCompleters(code);
    var columns = moduleRosterColumns();
    var body = rows.length
      ? C.dataTable({
          columns: columns,
          rows: rows,
          empty: "No students have completed this module yet.",
        })
      : C.alert(
          "info",
          "No completions yet",
          "No tracked student has completed this mandatory orientation module.",
        );
    C.modal({
      size: "lg",
      title: U.esc(mod.title),
      subtitle:
        U.esc(mod.code) +
        " &middot; " +
        U.esc(mod.version) +
        " &middot; " +
        U.esc(mod.category),
      body:
        C.statStrip([
          { label: "Completed", value: rows.length + " / " + mod.ofStudents },
          { label: "Completion", value: mod.trackedPct + "%" },
          {
            label: "Est. Duration",
            value: U.esc(OSAS.fmt.duration(mod.duration)),
          },
          { label: "Last Updated", value: OSAS.fmt.date(mod.updatedAt) },
        ]) +
        '<div class="form-section">' +
        '<div class="section-title"><span>1.</span> Students Who Completed This Module</div>' +
        body +
        "</div>",
      footer:
        '<button type="button" class="btn btn--ghost" data-close>Close</button>' +
        '<button type="button" class="btn btn--ghost" data-format="csv">' +
        OSAS.icons.icon("download", 12) +
        " CSV</button>" +
        '<button type="button" class="btn btn--primary" data-format="xls">' +
        OSAS.icons.icon("download", 12) +
        " Excel</button>",
      onMount: function (el) {
        /* Offer the roster as an Excel-compatible workbook or a plain CSV. */
        U.qsa("[data-format]", el).forEach(function (btn) {
          btn.addEventListener("click", function () {
            downloadModuleRoster(mod, rows, btn.getAttribute("data-format"));
          });
        });
      },
    });
  }

  /* Columns shared by the modal roster, its CSV export and its Excel export. */
  function moduleRosterColumns() {
    return [
      { label: "Student ID", value: "studentId" },
      { label: "Name", value: "name" },
      { label: "Program", value: "program" },
      { label: "Year Level", value: "year" },
      {
        label: "Date Completed",
        value: function (r) {
          return OSAS.fmt.date(r.date);
        },
      },
    ];
  }

  /* Excel-compatible workbook. Saved as .xls (an HTML table Excel opens
     natively) so the roster downloads without any external library. */
  function buildXls(mod, columns, rows) {
    function cell(value) {
      return String(value == null ? "" : value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;");
    }
    var span = columns.length;
    var head = columns
      .map(function (c) {
        return "<th>" + cell(c.label) + "</th>";
      })
      .join("");
    var body = rows
      .map(function (row) {
        return (
          "<tr>" +
          columns
            .map(function (c) {
              return (
                "<td>" +
                cell(
                  typeof c.value === "function" ? c.value(row) : row[c.value],
                ) +
                "</td>"
              );
            })
            .join("") +
          "</tr>"
        );
      })
      .join("");
    return (
      '<html xmlns:o="urn:schemas-microsoft-com:office:office" ' +
      'xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">' +
      '<head><meta charset="utf-8">' +
      "<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>" +
      "<x:Name>" +
      cell(mod.code) +
      "</x:Name>" +
      "<x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>" +
      "</x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->" +
      '</head><body><table border="1">' +
      '<tr><th colspan="' +
      span +
      '">' +
      cell(mod.title) +
      "</th></tr>" +
      '<tr><td colspan="' +
      span +
      '">' +
      cell("Module " + mod.code + " · " + mod.version + " · " + mod.category) +
      "</td></tr>" +
      '<tr><td colspan="' +
      span +
      '">' +
      cell(
        "Completed by " +
          rows.length +
          " of " +
          mod.ofStudents +
          " tracked students",
      ) +
      "</td></tr>" +
      "<tr>" +
      head +
      "</tr>" +
      body +
      "</table></body></html>"
    );
  }

  /* Export the module completion roster as Excel (.xls) or CSV (.csv). */
  function downloadModuleRoster(mod, rows, format) {
    var columns = moduleRosterColumns();
    var base = "beacon-orientation-" + U.slug(mod.code) + "-completers";
    if (format === "xls") {
      U.download(
        base + ".xls",
        buildXls(mod, columns, rows),
        "application/vnd.ms-excel",
      );
    } else {
      U.exportCsv(base + ".csv", columns, rows);
    }
    C.toast(
      "success",
      "Roster downloaded",
      base + "." + format + " (" + rows.length + " students)",
    );
  }

  console.log(
    "[Reports] Module initialized, C.modal:",
    typeof C.modal,
    "OSAS:",
    !!OSAS,
  );

  /* Global error listener for modal operations */
  window.addEventListener("modalError", function (e) {
    console.error("[Reports] Modal error:", e.detail);
  });

  /* REPORT SOURCES — each maps to a store resource with column definitions */
  var REPORT_SOURCES = {
    "User Management": {
      resource: "users",
      columns: [
        {
          label: "Full Name",
          value: function (r) {
            return r.firstName + " " + r.lastName;
          },
        },
        { label: "Role", value: "role" },
        { label: "Student ID", value: "studentId" },
        { label: "Email", value: "email" },
        { label: "Department", value: "department" },
        { label: "Program", value: "program" },
        { label: "Access Level", value: "accessLevel" },
        { label: "Status", value: "status" },
        {
          label: "Last Login",
          value: function (r) {
            return OSAS.fmt.dateTime(r.lastLogin);
          },
        },
      ],
    },
    Announcements: {
      resource: "announcements",
      columns: [
        { label: "Reference", value: "code" },
        { label: "Title", value: "title" },
        { label: "Category", value: "category" },
        { label: "Audience", value: "audience" },
        { label: "Status", value: "status" },
        {
          label: "Date",
          value: function (r) {
            return OSAS.fmt.dateTime(
              r.publishedAt || r.scheduledFor || r.updatedAt,
            );
          },
        },
        { label: "Views", value: "views" },
      ],
    },
    "Orientation Content": {
      /* Mandatory orientation modules enriched with cohort completion — the
         report body lists every required module, not the full module library. */
      resource: "modules",
      rows: mandatoryWithProgress,
      columns: [
        { label: "Reference", value: "code" },
        { label: "Mandatory Module", value: "title" },
        { label: "Category", value: "category" },
        { label: "Version", value: "version" },
        { label: "Duration (mins)", value: "duration" },
        { label: "Portal Learners", value: "learners" },
        { label: "Module Completion %", value: "completionRate" },
        {
          label: "Tracked Students",
          value: function (r) {
            return (
              r.tracked + " / " + r.ofStudents + " (" + r.trackedPct + "%)"
            );
          },
        },
        { label: "Status", value: "status" },
        {
          label: "Updated",
          value: function (r) {
            return OSAS.fmt.date(r.updatedAt);
          },
        },
      ],
    },
    "Feedback & Support": {
      resource: "tickets",
      columns: [
        { label: "Ticket Code", value: "code" },
        { label: "Student", value: "student" },
        { label: "Email", value: "studentEmail" },
        { label: "Category", value: "category" },
        { label: "Subject", value: "subject" },
        { label: "Priority", value: "priority" },
        { label: "Status", value: "status" },
        { label: "Assigned To", value: "assignedTo" },
        {
          label: "Submitted",
          value: function (r) {
            return OSAS.fmt.dateTime(r.submittedAt);
          },
        },
      ],
    },
    "Viewing & Tracking": {
      resource: "tracking",
      columns: [
        { label: "Content", value: "title" },
        { label: "Type", value: "type" },
        { label: "Views", value: "views" },
        { label: "Unique Visitors", value: "uniqueVisitors" },
        { label: "Average Time", value: "avgTime" },
        { label: "Engagement %", value: "engagement" },
        { label: "Trend %", value: "trend" },
        {
          label: "Last Viewed",
          value: function (r) {
            return OSAS.fmt.dateTime(r.lastViewed);
          },
        },
      ],
    },
    "Rules & Regulations": {
      resource: "regulations",
      columns: [
        { label: "Reference", value: "code" },
        { label: "Policy Title", value: "title" },
        { label: "Category", value: "category" },
        { label: "Effective Date", value: "effectiveDate" },
        { label: "Approval Body", value: "approvalBody" },
        { label: "Views", value: "views" },
        { label: "Status", value: "status" },
      ],
    },
    "Events & Activities": {
      resource: "events",
      columns: [
        { label: "Event Code", value: "code" },
        { label: "Event Title", value: "title" },
        { label: "Category", value: "category" },
        {
          label: "Start",
          value: function (r) {
            return OSAS.fmt.dateTime(r.startAt);
          },
        },
        { label: "Venue", value: "venue" },
        { label: "Audience", value: "audience" },
        { label: "Registered", value: "registered" },
        { label: "Status", value: "status" },
      ],
    },
  };

  /* --- Stats Helper --- */
  function statsHtml() {
    var stats = OSAS.analytics.reports(orientationReports());
    var cohort = cohortSummary();
    return (
      '<div class="stats">' +
      C.statCard(
        "Cohort Orientation Rate",
        cohort.rate + "%",
        '<span class="u-muted">' +
          cohort.modules +
          " mandatory modules &times; " +
          cohort.students +
          " students</span>",
      ) +
      C.statCard(
        "Mandatory Modules",
        cohort.modules,
        '<span class="u-muted">required for freshmen clearance</span>',
      ) +
      C.statCard(
        "Fully Compliant",
        cohort.compliant + " / " + cohort.students,
        '<span class="badge badge--completed">Cleared</span><span class="u-muted">' +
          cohort.inProgress +
          " in progress &middot; " +
          cohort.notStarted +
          " not started</span>",
      ) +
      C.statCard(
        "Orientation Reports on File",
        stats.total,
        '<span class="u-muted">' +
          OSAS.fmt.number(stats.records) +
          " records compiled</span>",
      ) +
      "</div>"
    );
  }

  /* --- Download Report ---
     Orientation reports export two sections: the mandatory module register
     with cohort completion, then the per-student orientation progress. */
  function downloadReport(id) {
    var report = OSAS.store.find("reports", id);
    if (!report) {
      return;
    }
    var source =
      REPORT_SOURCES[report.category] || REPORT_SOURCES[REPORT_CATEGORY];
    var rows =
      typeof source.rows === "function"
        ? source.rows()
        : OSAS.store.all(source.resource);
    var filename = U.slug(report.title) + ".csv";

    if (report.category === REPORT_CATEGORY) {
      exportOrientationReport(filename, source, report);
      OSAS.store.logActivity({
        tone: "green",
        icon: "download",
        title: "Report Downloaded",
        text:
          report.code +
          ' — "' +
          report.title +
          '" exported with ' +
          rows.length +
          " mandatory modules and " +
          studentProgressRows().length +
          " student records.",
      });
      return;
    }
    U.exportCsv(filename, source.columns, rows);
    OSAS.store.logActivity({
      tone: "green",
      icon: "download",
      title: "Report Downloaded",
      text:
        report.code +
        ' — "' +
        report.title +
        '" exported with ' +
        rows.length +
        " records.",
    });
    C.toast(
      "success",
      "Report downloaded",
      filename + " (" + rows.length + " records)",
    );
  }

  function exportOrientationReport(filename, source, report) {
    var cohort = cohortSummary();
    var mods = source.rows();
    var lines = [];

    /* --- header block --- */
    lines.push(
      [
        "BEACON OSAS Portal — Student Orientation & Information Module Report",
      ].join(","),
    );
    lines.push(["Reference", report.code].map(U.escapeCsv).join(","));
    lines.push(["Reporting Period", report.period].map(U.escapeCsv).join(","));
    lines.push(["Generated By", report.generatedBy].map(U.escapeCsv).join(","));
    lines.push(
      ["Generated At", OSAS.fmt.dateTime(report.generatedAt)]
        .map(U.escapeCsv)
        .join(","),
    );
    lines.push(
      ["Classification", report.classification || "Official Record"]
        .map(U.escapeCsv)
        .join(","),
    );
    lines.push("");

    /* --- section 1: cohort summary --- */
    lines.push(["1. COHORT ORIENTATION SUMMARY"].join(","));
    lines.push(["Metric", "Value"].map(U.escapeCsv).join(","));
    [
      ["Students Tracked", cohort.students],
      ["Mandatory Modules", cohort.modules],
      ["Cohort Orientation Rate", cohort.rate + "%"],
      ["Fully Compliant", cohort.compliant],
      ["In Progress", cohort.inProgress],
      ["Not Started", cohort.notStarted],
      ["Events Attended", cohort.events],
      ["Co-Curricular Hours", cohort.hours],
    ].forEach(function (pair) {
      lines.push(pair.map(U.escapeCsv).join(","));
    });
    lines.push("");

    /* --- section 2: mandatory module register --- */
    lines.push(["2. MANDATORY ORIENTATION MODULES"].join(","));
    lines.push(
      source.columns
        .map(function (c) {
          return U.escapeCsv(c.label);
        })
        .join(","),
    );
    mods.forEach(function (row) {
      lines.push(
        source.columns
          .map(function (c) {
            return U.escapeCsv(
              typeof c.value === "function" ? c.value(row) : row[c.value],
            );
          })
          .join(","),
      );
    });
    lines.push("");

    /* --- section 3: student progress --- */
    var progress = studentProgressRows();
    lines.push(["3. STUDENT ORIENTATION PROGRESS"].join(","));
    lines.push(
      [
        "Student ID",
        "Student Name",
        "Program",
        "Department",
        "Year Level",
        "Mandatory Completed",
        "Total Mandatory",
        "Orientation %",
        "Events Attended",
        "Co-Curricular Hrs",
        "Compliance Status",
      ]
        .map(U.escapeCsv)
        .join(","),
    );
    progress.forEach(function (row) {
      lines.push(
        [
          row.studentId,
          row.name,
          row.program,
          row.department,
          row.year,
          row.completed,
          row.total,
          row.pct + "%",
          row.events,
          row.hours,
          row.status,
        ]
          .map(U.escapeCsv)
          .join(","),
      );
    });

    U.download(
      filename,
      "\ufeff" + lines.join("\r\n"),
      "text/csv;charset=utf-8",
    );
    C.toast(
      "success",
      "Report downloaded",
      filename +
        " (" +
        mods.length +
        " modules, " +
        progress.length +
        " students)",
    );
  }

  /* --- Generate Report Modal --- */
  function generateReportModal() {
    C.modal({
      size: "lg",
      title: "Generate New Report",
      subtitle:
        "Compile a filtered export of OSAS records for submission or archiving.",
      body:
        '<div id="rp-alert"></div>' +
        '<div class="form-section">' +
        '<div class="section-title"><span>1.</span> Report Definition</div>' +
        OSAS.forms.field(
          "Report Title",
          OSAS.forms.textInput(
            "rp-title",
            "",
            "e.g. Orientation Module Completion Summary",
          ),
          { id: "rp-title", required: true },
        ) +
        '<div class="form-grid form-grid--2">' +
        OSAS.forms.field(
          "Report Category",
          OSAS.forms.selectInput(
            "rp-category",
            [REPORT_CATEGORY],
            REPORT_CATEGORY,
            "Select a category",
          ),
          { id: "rp-category", required: true },
        ) +
        OSAS.forms.field(
          "Reporting Period",
          OSAS.forms.selectInput(
            "rp-period",
            OPTIONS.reportPeriods,
            "September 2026",
            "Select a period",
          ),
        ) +
        "</div>" +
        '<div class="form-grid form-grid--2">' +
        OSAS.forms.field(
          "Export Format",
          OSAS.forms.selectInput(
            "rp-format",
            OPTIONS.reportFormats,
            "PDF",
            "Select a format",
          ),
        ) +
        OSAS.forms.field(
          "File Classification",
          OSAS.forms.selectInput(
            "rp-classification",
            ["Official Record", "Internal Working Copy", "For Review Only"],
            "Official Record",
            "Select a classification",
          ),
        ) +
        "</div></div>" +
        '<div class="form-section">' +
        '<div class="section-title"><span>2.</span> Inclusion Options</div>' +
        '<div class="check-stack">' +
        '<label class="check"><input type="checkbox" checked><span>Include the summary dashboard page with counts and status breakdowns.</span></label>' +
        '<label class="check"><input type="checkbox" checked><span>Include the detailed record listing from the selected module.</span></label>' +
        '<label class="check"><input type="checkbox"><span>Attach the system event log for the selected period.</span></label>' +
        "</div></div>",
      footer:
        '<button type="button" class="btn btn--ghost" data-close>Cancel</button>' +
        '<button type="button" class="btn btn--primary" id="rp-generate">Generate Report</button>',
      onMount: function (el, close) {
        var alertHost = el.querySelector("#rp-alert");
        el.querySelector("#rp-generate").addEventListener("click", function () {
          try {
            var title = OSAS.forms.value("rp-title");
            /* Orientation reports only — the category is fixed for this module */
            var categoryValue = REPORT_CATEGORY;
            if (!title) {
              alertHost.innerHTML = C.alert(
                "danger",
                "Unable to generate the report",
                "A report title is required.",
              );
              return;
            }
            var source = REPORT_SOURCES[categoryValue];
            var sourceRows = source
              ? typeof source.rows === "function"
                ? source.rows()
                : OSAS.store.all(source.resource)
              : [];
            var recordCount = sourceRows.length;
            var id = OSAS.store.nextId("reports", "REP-2026", 3);
            var session = OSAS.auth.current();
            var record = {
              id: id,
              code: id,
              title: title,
              category: categoryValue,
              period: OSAS.forms.value("rp-period") || "September 2026",
              format: OSAS.forms.value("rp-format") || "PDF",
              classification:
                OSAS.forms.value("rp-classification") || "Official Record",
              status: "Processing",
              records: 0,
              generatedAt: U.now(),
              generatedBy: session
                ? session.user.firstName + " " + session.user.lastName
                : "OSAS Administration",
              scope:
                OSAS.store.meta().campusCode +
                " — Southway College of Technology campuses",
            };
            OSAS.store.insert("reports", record);
            alertHost.innerHTML = C.alert(
              "info",
              "Report queued for processing",
              "Compiling <b>" +
                U.esc(title) +
                "</b> from " +
                OSAS.fmt.number(recordCount) +
                " source records…",
            );
            C.toast("info", "Report queued", id + " — " + title);
            close();
            page.render();

            window.setTimeout(function () {
              OSAS.store.update("reports", id, {
                status: "Completed",
                records: recordCount,
              });
              OSAS.store.logActivity({
                tone: "green",
                icon: "fileText",
                title: "Report Generated",
                text:
                  id +
                  ' — "' +
                  title +
                  '" completed with ' +
                  recordCount +
                  " records.",
              });
              C.toast(
                "success",
                "Report ready",
                id + " — " + title + " (" + recordCount + " records)",
              );
              page.render();
            }, 1200);
          } catch (err) {
            alertHost.innerHTML = C.alert(
              "danger",
              "Report generation failed",
              err.message,
            );
            console.error("[Reports] Generate error:", err);
          }
        });
      },
    });
  }

  /* --- Detail Modal --- */
  function detailModal(row) {
    row = row || {};
    var source = REPORT_SOURCES[row.category];
    var allRows = source
      ? typeof source.rows === "function"
        ? source.rows()
        : OSAS.store.all(source.resource)
      : [];
    var preview = allRows.slice(0, 6);
    var columns = source ? source.columns : [];
    var cohort = row.category === REPORT_CATEGORY ? cohortSummary() : null;

    /* Student records section — for orientation reports this carries the real
       mandatory-module progress; other categories keep the plain roster. */
    var studentColumns;
    var students;
    if (cohort) {
      studentColumns = [
        { label: "Student ID", value: "studentId" },
        { label: "Name", value: "name" },
        { label: "Program", value: "program" },
        {
          label: "Mandatory Modules",
          render: function (r) {
            return r.completed + " / " + r.total;
          },
        },
        {
          label: "Orientation Progress",
          render: function (r) {
            return progressCell(
              r.pct,
              r.pct + "% (" + r.completed + "/" + r.total + ")",
            );
          },
        },
        { label: "Events", align: "right", value: "events" },
        { label: "Co-Curricular Hrs", align: "right", value: "hours" },
        {
          label: "Status",
          render: function (r) {
            var tone =
              r.status === "Compliant"
                ? "completed"
                : r.status === "In Progress"
                  ? "processing"
                  : "draft";
            return (
              '<span class="badge badge--' +
              tone +
              '">' +
              U.esc(r.status) +
              "</span>"
            );
          },
        },
      ];
      students = studentProgressRows();
    } else {
      studentColumns = [
        { label: "Student ID", value: "studentId" },
        {
          label: "Name",
          value: function (r) {
            return (r.firstName || "") + " " + (r.lastName || "");
          },
        },
        { label: "Program", value: "program" },
        { label: "Department", value: "department" },
        { label: "Year Level", value: "year" },
        {
          label: "Date Completed",
          value: function (r) {
            return OSAS.fmt.date(r.lastLogin);
          },
        },
      ];
      students = OSAS.store.all("users").filter(function (u) {
        return u.role === "Student";
      });
    }
    var studentBody = students.length
      ? C.dataTable({
          columns: studentColumns,
          rows: students,
          empty: "No students available.",
        })
      : C.alert(
          "info",
          "No students available",
          "There are currently no student records to display.",
        );

    C.modal({
      size: "lg",
      title: U.esc(row.title),
      subtitle:
        U.esc(row.code) +
        " &middot; " +
        U.esc(row.period) +
        " &middot; " +
        U.esc(row.scope),
      body:
        C.statStrip(
          [
            { label: "Status", value: C.badge(row.status) },
            { label: "Format", value: U.esc(row.format) },
            {
              label: "Records",
              value: OSAS.fmt.number(row.records || preview.length),
            },
            { label: "Generated", value: OSAS.fmt.dateTime(row.generatedAt) },
          ].concat(
            cohort
              ? [
                  { label: "Cohort Rate", value: cohort.rate + "%" },
                  { label: "Mandatory Modules", value: cohort.modules },
                  {
                    label: "Compliant",
                    value: cohort.compliant + " / " + cohort.students,
                  },
                ]
              : [],
          ),
        ) +
        '<div class="form-section">' +
        '<div class="section-title"><span>1.</span> Report Metadata</div>' +
        '<dl class="kv">' +
        "<dt>Report Category</dt><dd>" +
        U.esc(row.category) +
        "</dd>" +
        "<dt>Reporting Period</dt><dd>" +
        U.esc(row.period) +
        "</dd>" +
        "<dt>Generated By</dt><dd>" +
        U.esc(row.generatedBy) +
        "</dd>" +
        "<dt>Classification</dt><dd>" +
        U.esc(row.classification || "Official Record") +
        "</dd>" +
        "<dt>Source Module</dt><dd>" +
        (source ? U.esc(source.resource) : "—") +
        "</dd>" +
        (cohort
          ? "<dt>Mandatory Scope</dt><dd>" +
            cohort.modules +
            " mandatory orientation modules &times; " +
            cohort.students +
            " tracked students</dd>"
          : "") +
        "</dl></div>" +
        '<div class="form-section">' +
        '<div class="section-title"><span>2.</span> ' +
        (cohort ? "Student Orientation Progress" : "Student Records") +
        "</div>" +
        studentBody +
        "</div>" +
        '<div class="form-section">' +
        '<div class="section-title"><span>3.</span> ' +
        (cohort
          ? "Mandatory Module Register (first "
          : "Record Preview (first ") +
        preview.length +
        ")</div>" +
        (preview.length
          ? C.dataTable({
              columns: columns,
              rows: preview,
              empty: "No records available for this report category.",
            })
          : C.alert(
              "info",
              "No preview available",
              "This report category has no source records to preview right now.",
            )) +
        "</div>",
      footer:
        '<button type="button" class="btn btn--ghost" data-close>Close</button>' +
        '<button type="button" class="btn btn--primary" id="rp-download">' +
        OSAS.icons.icon("download", 12) +
        " Download Report</button>",
      onMount: function (el, close) {
        try {
          el.querySelector("#rp-download").addEventListener(
            "click",
            function () {
              downloadReport(row.id);
              close();
              page.render();
            },
          );
        } catch (err) {
          console.error("[Reports] Download error:", err);
        }
      },
    });
  }

  /* Resolve the generating officer's role/department from the user directory.
     Seeded reports store a bare name; fall back gracefully when unmatched. */
  function staffFor(name) {
    var target = String(name || "")
      .trim()
      .toLowerCase();
    if (!target) {
      return null;
    }
    var found = null;
    OSAS.store.all("users").forEach(function (u) {
      if (found) {
        return;
      }
      var full = String(u.firstName + " " + u.lastName)
        .trim()
        .toLowerCase();
      if (full === target) {
        found = u;
      }
    });
    return found;
  }

  /* Report register — every column carries its supporting detail so the
     officer can read the full context without opening the row. */
  var REGISTER_COLUMNS = [
    {
      label: "Report Title",
      render: function (row) {
        row = row || {};
        return (
          '<div class="cell-title">' +
          U.esc(row.title) +
          "</div>" +
          '<div class="cell-sub mono">' +
          U.esc(row.code) +
          "</div>" +
          '<div class="cell-sub">' +
          U.esc(row.scope || OSAS.store.meta().institution) +
          "</div>"
        );
      },
    },
    {
      label: "Scope &amp; Period",
      render: function (row) {
        row = row || {};
        return (
          '<div class="cell-title">' +
          U.esc(row.period) +
          "</div>" +
          '<div class="cell-sub"><span class="chip">' +
          U.esc(row.category) +
          "</span></div>" +
          '<div class="cell-sub">' +
          U.esc(row.classification || "Official Record") +
          "</div>"
        );
      },
    },
    {
      label: "Generated By",
      render: function (row) {
        row = row || {};
        var staff = staffFor(row.generatedBy);
        var role = staff ? staff.role : "OSAS Administration";
        var dept = staff ? staff.department || staff.program || "" : "";
        return (
          '<div class="cell-title">' +
          U.esc(row.generatedBy) +
          "</div>" +
          '<div class="cell-sub">' +
          U.esc(role) +
          (dept ? " &middot; " + U.esc(dept) : "") +
          "</div>" +
          '<div class="cell-sub mono">' +
          OSAS.fmt.dateTime(row.generatedAt) +
          "</div>"
        );
      },
    },
    {
      label: "Records Compiled",
      align: "right",
      render: function (row) {
        row = row || {};
        var total = row.records || 0;
        /* Orientation reports compile modules + students, so show the split */
        if (row.category === REPORT_CATEGORY && total) {
          return (
            '<div class="cell-title">' +
            OSAS.fmt.number(total) +
            "</div>" +
            '<div class="cell-sub">' +
            mandatoryWithProgress().length +
            " modules &middot; " +
            studentProgressRows().length +
            " students</div>"
          );
        }
        return (
          '<div class="cell-title">' +
          OSAS.fmt.number(total) +
          "</div>" +
          '<div class="cell-sub">' +
          (total ? "source rows exported" : "awaiting compilation") +
          "</div>"
        );
      },
    },
    {
      label: "Format",
      render: function (row) {
        row = row || {};
        var fmt = row.format || "PDF";
        var ext =
          fmt === "CSV" ? ".csv" : fmt === "Excel (XLSX)" ? ".xlsx" : ".pdf";
        return (
          '<span class="badge badge--info">' +
          U.esc(fmt) +
          "</span>" +
          '<div class="cell-sub mono">' +
          ext +
          "</div>"
        );
      },
    },
    {
      label: "Status",
      render: function (row) {
        row = row || {};
        var note =
          row.status === "Completed"
            ? "Ready for download"
            : row.status === "Processing"
              ? "Compiling source records"
              : "Not yet submitted";
        return C.badge(row.status) + '<div class="cell-sub">' + note + "</div>";
      },
    },
    {
      label: "Actions",
      align: "right",
      render: function (row) {
        row = row || {};
        return (
          '<div class="cell-actions" style="display:flex;gap:8px;align-items:center;">' +
          '<button type="button" class="btn btn--primary btn--sm" data-action="download" data-id="' +
          U.esc(row.id) +
          '">Download</button>' +
          '<button type="button" class="btn btn--ghost btn--sm" data-action="view" data-id="' +
          U.esc(row.id) +
          '">View</button></div>'
        );
      },
    },
  ];

  /* --- Pagekit List Page --- */
  var page = OSAS.pagekit.listPage({
    content: content,
    title: "Reports",
    subtitle:
      "Generate and export official reports for the Student Orientation & Information Module.",
    actions:
      '<button type="button" class="btn btn--primary" data-action="generate">' +
      OSAS.icons.icon("plus", 13) +
      " Generate New Report</button>",
    source: orientationReports,
    defaultState: { search: "", period: "All", status: "All", page: 1 },
    filters: [
      {
        id: "rp-search",
        key: "search",
        type: "search",
        label: "Search Reports",
        placeholder: "Search by report title or reference…",
      },
      {
        id: "rp-filter-period",
        key: "period",
        type: "select",
        label: "Reporting Period",
        values: OPTIONS.reportPeriods,
      },
      {
        id: "rp-filter-status",
        key: "status",
        type: "select",
        label: "Status",
        values: ["Completed", "Processing", "Draft"],
      },
    ],
    sort: { field: "generatedAt", dir: "desc" },
    stats: statsHtml,
    /* The Generated Report Register table is hidden — this page now presents
       the Mandatory Orientation Module Progress panel below the filters.
       Remove this flag to bring the register back. */
    table: false,
    columns: function () {
      return REGISTER_COLUMNS;
    },
    beforeTable: function () {
      return moduleProgressPanel();
    },
    toolbar:
      '<div class="table-toolbar"><div class="panel-title">Generated Report Register</div>' +
      '<button type="button" class="btn btn--ghost btn--sm" data-action="export">' +
      OSAS.icons.icon("download", 11) +
      " Export Register</button></div>",
    filter: function (rows, state) {
      try {
        var next = U.search(rows, state.search, ["title", "code", "period"]);
        next = U.byStatus(next, state.status);
        if (state.period !== "All") {
          next = next.filter(function (r) {
            return r.period === state.period;
          });
        }
        return next;
      } catch (err) {
        console.error("[Reports] Filter error:", err);
        return rows;
      }
    },

    footerLabel: function (info) {
      return (
        "Showing " +
        info.from +
        "–" +
        info.to +
        " of " +
        info.total +
        " orientation reports"
      );
    },
    empty: "No reports match the current filters.",
    afterTable: function () {
      return '<div class="u-grow"></div>';
    },
    onAction: function (action, id) {
      try {
        if (action === "generate") {
          generateReportModal();
          return;
        }
        if (action === "backup") {
          U.download(
            "beacon-osas-backup-" + OSAS.SEED_NOW.slice(0, 10) + ".json",
            OSAS.store.exportBackup(),
            "application/json",
          );
          C.toast("success", "Backup downloaded", "beacon-osas-backup.json");
          return;
        }
        if (action === "restore") {
          OSAS.shell.resetDemoData();
          return;
        }
        if (action === "export") {
          U.exportCsv(
            "beacon-report-register.csv",
            [
              { label: "Reference", value: "code" },
              { label: "Report Title", value: "title" },
              { label: "Category", value: "category" },
              { label: "Period", value: "period" },
              {
                label: "Classification",
                value: function (r) {
                  return r.classification || "Official Record";
                },
              },
              { label: "Scope", value: "scope" },
              { label: "Format", value: "format" },
              { label: "Records", value: "records" },
              {
                label: "Mandatory Modules",
                value: function () {
                  return mandatoryWithProgress().length;
                },
              },
              {
                label: "Students Tracked",
                value: function () {
                  return studentProgressRows().length;
                },
              },
              { label: "Generated By", value: "generatedBy" },
              {
                label: "Generated Role",
                value: function (r) {
                  var s = staffFor(r.generatedBy);
                  return s ? s.role : "OSAS Administration";
                },
              },
              {
                label: "Generated Department",
                value: function (r) {
                  var s = staffFor(r.generatedBy);
                  return s ? s.department || s.program || "" : "";
                },
              },
              {
                label: "Generated At",
                value: function (r) {
                  return OSAS.fmt.dateTime(r.generatedAt);
                },
              },
              { label: "Status", value: "status" },
            ],
            page.rows(),
          );
          C.toast(
            "success",
            "Register exported",
            page.rows().length + " reports written to CSV.",
          );
          return;
        }
        if (action === "download") {
          downloadReport(id);
          return;
        }
        if (action === "module-students") {
          moduleStudentsModal(id);
          return;
        }
        if (action === "view") {
          var row = OSAS.store.find("reports", id);
          if (row) {
            detailModal(row);
          }
        }
      } catch (err) {
        console.error("[Reports] Action error [" + action + "]:", err);
      }
    },
  });
})();
