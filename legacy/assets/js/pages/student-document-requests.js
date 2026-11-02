/* ==========================================================================
   Student module — Document Requests  (student/document-requests.html)
   Request official certificates and track their release. Hash routed:
     #/new                        file a new document request
     #/request/DR-2026-012        request detail + workflow timeline
   Only requests filed by the signed-in student are ever loaded.
   ========================================================================== */
(function () {
  "use strict";

  var shell = OSAS.studentShell.mount({
    page: "documents",
    title: "Document Requests",
  });
  if (!shell) {
    return;
  }

  var U = OSAS.util,
    C = OSAS.components;
  var main = shell.main;
  var OPTIONS = OSAS.store.options();

  var state = { q: "", tab: "All", copies: 1, attachment: "" };
  var TABS = [
    "All",
    "Submitted",
    "Under Review",
    "Ready for Pick-up",
    "Released",
  ];

  function $(id) {
    return document.getElementById(id);
  }
  function fullName() {
    return shell.fullName(shell.user);
  }
  function email() {
    return (shell.user || {}).email || "";
  }
  function studentId() {
    return (shell.user || {}).studentId || "";
  }

  /* --------------------------------------------------------------- data */
  function mine(row) {
    var byEmail =
      email() &&
      row.studentEmail &&
      String(row.studentEmail).toLowerCase() === String(email()).toLowerCase();
    var byId =
      studentId() &&
      row.studentId &&
      String(row.studentId) === String(studentId());
    return Boolean(byEmail || byId);
  }
  function allRows() {
    return OSAS.store.all("documentRequests");
  }
  function myRows() {
    return U.sortBy(allRows().filter(mine), "requestedAt", "desc");
  }
  function byCode(code) {
    return (
      allRows().filter(function (row) {
        return row.code === code;
      })[0] || null
    );
  }
  function tabRows() {
    var list = myRows();
    if (state.tab === "All") {
      return list;
    }
    return list.filter(function (row) {
      return row.status === state.tab;
    });
  }
  function filtered() {
    var list = tabRows();
    var q = state.q.trim().toLowerCase();
    if (!q) {
      return list;
    }
    return list.filter(function (row) {
      return (
        (
          row.code +
          " " +
          row.documentType +
          " " +
          row.purpose +
          " " +
          row.status
        )
          .toLowerCase()
          .indexOf(q) > -1
      );
    });
  }
  function cancellable(row) {
    return row.status === "Submitted" || row.status === "Under Review";
  }

  /* ---------------------------------------------------------- list view */
  function cardHtml(row) {
    return (
      '<a class="sf-card" href="#/request/' +
      U.esc(row.code) +
      '">' +
      '<div class="sf-card__top"><span class="sf-code">' +
      U.esc(row.code) +
      "</span>" +
      C.badge(row.status) +
      "</div>" +
      '<h3 class="sf-card__title">' +
      U.esc(row.documentType) +
      "</h3>" +
      '<span class="sf-card__cat">' +
      U.esc(U.truncate(row.purpose, 90)) +
      "</span>" +
      '<div class="sf-card__foot"><div class="sf-card__meta">' +
      '<span class="sf-card__when">' +
      OSAS.icons.icon("calendar", 12) +
      "<span>" +
      OSAS.fmt.shortDate(row.requestedAt) +
      "</span></span>" +
      '<span class="sf-card__when">' +
      OSAS.icons.icon("fileText", 12) +
      "<span>" +
      U.esc(String(row.copies || 1)) +
      " " +
      (String(row.copies || 1) === "1" ? "copy" : "copies") +
      " &middot; " +
      U.esc(row.deliveryMode) +
      "</span></span>" +
      '</div><span class="sf-card__cta">View</span></div></a>'
    );
  }
  function cardsHtml() {
    var list = filtered();
    if (!list.length) {
      if (state.q) {
        return C.emptyState(
          "No request matches &ldquo;" + U.esc(state.q) + "&rdquo;",
        );
      }
      if (state.tab !== "All") {
        return C.emptyState(
          "You have no request marked as " +
            U.esc(state.tab).toLowerCase() +
            ".",
        );
      }
      return C.emptyState(
        "You have not requested a document yet.",
        '<button type="button" class="btn btn--outline btn--sm" id="dr-empty-new">Request a Document</button>',
      );
    }
    return list.map(cardHtml).join("");
  }
  function tabsHtml() {
    return (
      '<div class="sf-tabs">' +
      TABS.map(function (tab) {
        return (
          '<button type="button" class="sf-tab' +
          (state.tab === tab ? " is-on" : "") +
          '" id="dr-tab-' +
          U.slug(tab) +
          '" data-tab="' +
          U.esc(tab) +
          '">' +
          tab +
          "</button>"
        );
      }).join("") +
      "</div>"
    );
  }
  function listHtml() {
    return (
      '<div class="sf sf--list">' +
      '<button type="button" class="sf-new" id="dr-new">' +
      '<span class="sf-new__plus">+</span>Request a Document</button>' +
      '<div class="sf-search">' +
      '<input type="search" class="sf-search__input" id="dr-q" aria-label="Search requests" ' +
      'placeholder="Search by reference, document, or status&hellip;" value="' +
      U.esc(state.q) +
      '">' +
      "</div>" +
      tabsHtml() +
      '<div class="sf-list" id="dr-list">' +
      cardsHtml() +
      "</div></div>"
    );
  }

  /* ------------------------------------------------------- request form */
  function createHtml() {
    return (
      '<div class="sf sf--create">' +
      '<h1 class="sf-create__h">Request a Document</h1>' +
      '<p class="sf-create__sub">OSAS prepares the document and notifies you when it is ready</p>' +
      '<div class="sf-form">' +
      '<div class="sf-field"><label class="sf-label" for="dr-type">Document Type <i>*</i></label>' +
      '<select class="sf-input" id="dr-type"><option value="">Select a document</option>' +
      OPTIONS.documentTypes
        .map(function (type) {
          return (
            '<option value="' + U.esc(type) + '">' + U.esc(type) + "</option>"
          );
        })
        .join("") +
      "</select></div>" +
      '<div class="sf-field"><label class="sf-label" for="dr-purpose">Purpose <i>*</i></label>' +
      '<textarea class="sf-input sf-input--area" id="dr-purpose" rows="4" ' +
      'placeholder="Why do you need this document? (e.g., OJT requirement, scholarship renewal)"></textarea></div>' +
      '<div class="sf-field"><label class="sf-label" for="dr-copies">Number of Copies <i>*</i></label>' +
      '<select class="sf-input" id="dr-copies">' +
      [1, 2, 3, 4, 5]
        .map(function (n) {
          return (
            '<option value="' +
            n +
            '"' +
            (n === 1 ? " selected" : "") +
            ">" +
            n +
            "</option>"
          );
        })
        .join("") +
      "</select></div>" +
      '<div class="sf-field"><label class="sf-label" for="dr-delivery">Delivery Mode <i>*</i></label>' +
      '<select class="sf-input" id="dr-delivery">' +
      OPTIONS.deliveryModes
        .map(function (mode) {
          return (
            '<option value="' + U.esc(mode) + '">' + U.esc(mode) + "</option>"
          );
        })
        .join("") +
      "</select></div>" +
      '<div class="sf-field"><span class="sf-label">Attach a Valid ID (Optional)</span>' +
      '<label class="sf-drop" for="dr-file">' +
      OSAS.icons.icon("upload", 17) +
      '<span class="sf-drop__text">Click to upload a PNG, JPG or PDF up to 10MB</span>' +
      '<span class="sf-drop__file" id="dr-file-name"></span></label>' +
      '<input class="sf-file" type="file" id="dr-file" accept=".png,.jpg,.jpeg,.pdf" ' +
      'aria-label="Attach a valid ID"></div>' +
      '<div id="dr-alert"></div>' +
      '<button type="button" class="sf-submit" id="dr-submit">Submit Request</button>' +
      '<button type="button" class="sf-cancel" id="dr-cancel">Cancel</button>' +
      "</div></div>"
    );
  }

  /* -------------------------------------------------------- detail view */
  function factHtml(label, value) {
    return (
      '<div class="sf-fact-box"><span class="sf-fact-box__label">' +
      label +
      "</span><b>" +
      U.esc(value) +
      "</b></div>"
    );
  }
  function stepsHtml(row) {
    var rows = row.timeline || [];
    if (!rows.length) {
      return C.emptyState("No workflow activity has been recorded yet.");
    }
    return rows
      .map(function (step) {
        return (
          '<div class="sf-step">' +
          '<span class="sf-step__dot' +
          (step.status === "Rejected" || step.status === "Cancelled"
            ? " sf-step__dot--warn"
            : "") +
          '"></span>' +
          '<div class="sf-step__body">' +
          '<div class="sf-step__title">' +
          U.esc(step.status) +
          "</div>" +
          '<div class="sf-step__meta">' +
          OSAS.fmt.dateTime(step.at) +
          " &middot; " +
          U.esc(step.by || "OSAS Records") +
          "</div>" +
          '<div class="sf-step__note">' +
          U.esc(step.note) +
          "</div>" +
          "</div></div>"
        );
      })
      .join("");
  }
  function detailHtml(row) {
    return (
      '<div class="sf sf--detail"><div class="sf-ticket">' +
      '<div class="sf-ticket__top"><span class="sf-code">' +
      U.esc(row.code) +
      "</span>" +
      C.badge(row.status) +
      "</div>" +
      '<h1 class="sf-ticket__title">' +
      U.esc(row.documentType) +
      "</h1>" +
      '<span class="sf-ticket__cat">' +
      U.esc(row.purpose) +
      "</span>" +
      '<div class="sf-facts">' +
      factHtml("Date Requested", OSAS.fmt.shortDate(row.requestedAt)) +
      factHtml("Copies", String(row.copies || 1)) +
      factHtml("Delivery Mode", row.deliveryMode) +
      factHtml("Last Update", OSAS.fmt.shortDate(row.updatedAt)) +
      "</div>" +
      (row.status === "Ready for Pick-up"
        ? C.alert(
            "success",
            "Your document is ready",
            "Claim it at the OSAS office and bring your student ID. Office hours are 8:00 AM to 5:00 PM, Monday to Friday.",
          )
        : "") +
      (row.status === "Released"
        ? C.alert(
            "success",
            "Document released",
            "This request was completed on " +
              OSAS.fmt.shortDate(row.releasedAt) +
              ".",
          )
        : "") +
      (row.status === "Rejected"
        ? C.alert(
            "warning",
            "Request rejected",
            U.esc(row.remarks || "Contact the OSAS office for details."),
          )
        : "") +
      '<section class="sf-sec"><h2 class="sf-sec__h">Request Details</h2>' +
      '<div class="sf-facts">' +
      factHtml("Student", fullName()) +
      factHtml("Student ID", studentId() || "—") +
      factHtml("Program", (shell.user || {}).program || "—") +
      factHtml("Processed By", row.processedBy || "Unassigned") +
      "</div></section>" +
      (row.attachment
        ? '<section class="sf-sec"><h2 class="sf-sec__h">Attached File</h2>' +
          '<div class="sf-media"><span class="sf-media__ico">' +
          OSAS.icons.icon("fileText", 16) +
          "</span>" +
          '<span class="sf-media__meta"><b>' +
          U.esc(row.attachment) +
          "</b>" +
          "<small>Uploaded with the request on " +
          OSAS.fmt.shortDate(row.requestedAt) +
          "</small></span></div></section>"
        : "") +
      '<section class="sf-sec"><h2 class="sf-sec__h">Progress Timeline</h2>' +
      stepsHtml(row) +
      "</section>" +
      (cancellable(row)
        ? '<section class="sf-sec">' +
          '<button type="button" class="sf-cancel" id="dr-cancel-request">Cancel This Request</button>' +
          "</section>"
        : "") +
      "</div></div>"
    );
  }

  /* ---------------------------------------------------------- behaviour */
  function bindOnce(node, type, fn) {
    if (!node) {
      return;
    }
    node._bound = node._bound || {};
    if (node._bound[type]) {
      return;
    }
    node._bound[type] = true;
    node.addEventListener(type, fn);
  }
  function scrollTop() {
    if (window.scrollTo) {
      window.scrollTo(0, 0);
    }
  }

  function wireList() {
    bindOnce($("dr-new"), "click", goCreate);
    bindOnce($("dr-empty-new"), "click", goCreate);
    bindOnce($("dr-q"), "input", function (event) {
      state.q = event.target.value;
      $("dr-list").innerHTML = cardsHtml();
      bindOnce($("dr-empty-new"), "click", goCreate);
    });
    TABS.forEach(function (tab) {
      bindOnce($("dr-tab-" + U.slug(tab)), "click", function () {
        state.tab = tab;
        TABS.forEach(function (other) {
          var node = $("dr-tab-" + U.slug(other));
          if (node) {
            node.classList.toggle("is-on", other === tab);
          }
        });
        $("dr-list").innerHTML = cardsHtml();
        bindOnce($("dr-empty-new"), "click", goCreate);
      });
    });
  }

  function formError(id, title, text) {
    $("dr-alert").innerHTML = C.alert("warning", title, text);
    var node = $(id);
    if (node && node.focus) {
      node.focus();
    }
  }
  function submitRequest() {
    var type = String(($("dr-type") || {}).value || "").trim();
    var purpose = String(($("dr-purpose") || {}).value || "").trim();
    if (!type) {
      formError(
        "dr-type",
        "Document required",
        "Choose the document you need.",
      );
      return;
    }
    if (!purpose) {
      formError(
        "dr-purpose",
        "Purpose required",
        "State why you need this document.",
      );
      return;
    }
    var now = U.now();
    var code = OSAS.store.nextId("documentRequests", "DR-2026", 3);
    var record = {
      id: code,
      code: code,
      student: fullName(),
      studentId: studentId(),
      studentEmail: email(),
      program: (shell.user || {}).program || "",
      year: (shell.user || {}).year || "",
      documentType: type,
      purpose: purpose,
      copies: String(($("dr-copies") || {}).value || 1),
      deliveryMode: String(
        ($("dr-delivery") || {}).value || OPTIONS.deliveryModes[0],
      ),
      status: "Submitted",
      priority: "Medium",
      requestedAt: now,
      updatedAt: now,
      releasedAt: "",
      processedBy: "",
      remarks: "",
      attachment: state.attachment || "",
      timeline: [
        {
          at: now,
          status: "Submitted",
          by: fullName(),
          note: "Request submitted through the student portal.",
        },
      ],
    };
    OSAS.store.insert("documentRequests", record);
    OSAS.store.logActivity({
      tone: "amber",
      icon: "fileText",
      title: "Document Request Submitted",
      text: fullName() + " requested a " + type + " (" + code + ").",
    });
    C.toast("success", "Request submitted", code + " — " + type);
    state.attachment = "";
    window.location.hash = "#/request/" + code;
  }
  function wireCreate() {
    bindOnce($("dr-barback"), "click", goList);
    bindOnce($("dr-cancel"), "click", goList);
    bindOnce($("dr-submit"), "click", submitRequest);
    bindOnce($("dr-file"), "change", function (event) {
      var file = event.target && event.target.files && event.target.files[0];
      state.attachment = file ? file.name : "";
      var label = $("dr-file-name");
      if (label) {
        label.textContent = state.attachment || "";
      }
    });
  }

  function cancelRequest(row) {
    C.confirm({
      title: "Cancel request " + row.code + "?",
      message:
        "The request will be withdrawn and the OSAS records team will no longer process it.",
      confirmLabel: "Cancel request",
      confirmClass: "btn--danger",
      onConfirm: function () {
        var now = U.now();
        OSAS.store.update("documentRequests", row.id, {
          status: "Cancelled",
          updatedAt: now,
          timeline: (row.timeline || []).concat([
            {
              at: now,
              status: "Cancelled",
              by: fullName(),
              note: "Cancelled by the student through the portal.",
            },
          ]),
        });
        OSAS.store.logActivity({
          tone: "amber",
          icon: "fileText",
          title: "Document Request Cancelled",
          text:
            fullName() +
            " cancelled " +
            row.code +
            " — " +
            row.documentType +
            ".",
        });
        C.toast(
          "info",
          "Request cancelled",
          row.code + " — " + row.documentType,
        );
        renderDetail(byCode(row.code) || row);
      },
    });
  }
  function wireDetail() {
    bindOnce($("dr-barback"), "click", goBack);
    var cancel = $("dr-cancel-request");
    if (cancel) {
      bindOnce(cancel, "click", function () {
        var row = hashRow();
        if (row && cancellable(row)) {
          cancelRequest(row);
        }
      });
    }
  }

  /* --------------------------------------------------------- bar + nav */
  function barBack(label) {
    shell.setBar({
      menu: false,
      bell: false,
      avatar: false,
      title:
        '<button type="button" class="sf-back" id="dr-barback">' +
        OSAS.icons.icon("chevronLeft", 17) +
        label +
        "</button>",
      extra: "",
    });
  }
  function goList() {
    window.location.hash = "";
  }
  function goCreate() {
    window.location.hash = "#/new";
  }
  function goBack() {
    window.location.hash = "";
  }

  function hashRow() {
    var match = (window.location.hash || "").match(
      /^#\/request\/([A-Za-z0-9-]+)$/,
    );
    return match ? byCode(match[1]) : null;
  }

  /* ------------------------------------------------------------ render */
  function renderList() {
    shell.setBar({});
    main.innerHTML = listHtml();
    wireList();
    scrollTop();
  }
  function renderCreate() {
    barBack("Document Requests");
    state.attachment = "";
    main.innerHTML = createHtml();
    wireCreate();
    scrollTop();
  }
  function renderDetail(row) {
    barBack("Request Details");
    main.innerHTML = detailHtml(row);
    wireDetail();
    scrollTop();
  }

  function route() {
    var hash = window.location.hash || "";
    if (hash === "#/new") {
      renderCreate();
      return;
    }
    var match = hash.match(/^#\/request\/([A-Za-z0-9-]+)$/);
    if (match) {
      var row = byCode(match[1]);
      if (row && mine(row)) {
        renderDetail(row);
        return;
      }
    }
    renderList();
  }

  window.addEventListener("hashchange", route);
  route();
})();
