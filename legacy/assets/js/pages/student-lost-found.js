/* ==========================================================================
   Student module — Lost & Found  (student/lost-found.html)
   Browse reported items, report a lost or found item, open a report and file
   an ownership claim. Hash routed:
     #/report                  report a new lost or found item
     #/item/LF-2026-014        item detail + claim form
   Only the signed-in student's own reports appear under "My Reports"; the
   student-shell.js access gate keeps staff accounts out of this page.
   ========================================================================== */
(function () {
  "use strict";

  var shell = OSAS.studentShell.mount({
    page: "lostfound",
    title: "Lost & Found",
  });
  if (!shell) {
    return;
  }

  var U = OSAS.util,
    C = OSAS.components;
  var main = shell.main;
  var OPTIONS = OSAS.store.options();

  var state = {
    q: "",
    tab: "browse",
    type: "Lost",
    attachment: "",
    claimProof: "",
    claimAttachment: "",
  };

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
      row.reporterEmail &&
      String(row.reporterEmail).toLowerCase() === String(email()).toLowerCase();
    var byId =
      studentId() &&
      row.studentId &&
      String(row.studentId) === String(studentId());
    return Boolean(byEmail || byId);
  }
  function allRows() {
    return OSAS.store.all("lostFound");
  }
  function myRows() {
    return U.sortBy(allRows().filter(mine), "postedAt", "desc");
  }
  function byCode(code) {
    return (
      allRows().filter(function (row) {
        return row.code === code;
      })[0] || null
    );
  }
  function browseRows() {
    return allRows().filter(function (row) {
      return row.status !== "Archived";
    });
  }
  function tabRows() {
    return state.tab === "mine" ? myRows() : browseRows();
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
          row.title +
          " " +
          row.category +
          " " +
          row.location +
          " " +
          row.type
        )
          .toLowerCase()
          .indexOf(q) > -1
      );
    });
  }
  function myClaim(row) {
    var list = row.claims || [];
    return (
      list.filter(function (claim) {
        return (
          String(claim.claimantEmail).toLowerCase() ===
          String(email()).toLowerCase()
        );
      })[0] || null
    );
  }
  function pendingClaims(row) {
    return (row.claims || []).filter(function (claim) {
      return claim.status === "Pending";
    });
  }
  function stamp(value) {
    return OSAS.fmt.shortDate(value) + " · " + OSAS.fmt.time(value);
  }

  /* ---------------------------------------------------------- list view */
  function stateMeta(row) {
    var claim = myClaim(row);
    if (claim && claim.status === "Pending") {
      return { label: "Claim pending", tone: "rose" };
    }
    if (claim && claim.status === "Verified") {
      return { label: "Claimed", tone: "green" };
    }
    if (claim && claim.status === "Rejected" && row.status !== "Returned") {
      return { label: "Claim rejected", tone: "gray" };
    }
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
  function cardHtml(row) {
    var meta = stateMeta(row);
    return (
      '<a class="lf-card" href="#/item/' +
      U.esc(row.code) +
      '">' +
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
      "</div>" +
      '<div class="lf-card__foot"><span class="lf-card__btn">' +
      (mine(row) ? "View report" : "View &amp; claim") +
      "</span></div></div></a>"
    );
  }
  function cardsHtml() {
    var list = filtered();
    if (!list.length) {
      if (state.q) {
        return C.emptyState(
          "No item matches &ldquo;" + U.esc(state.q) + "&rdquo;",
        );
      }
      if (state.tab === "mine") {
        return C.emptyState(
          "You have not reported any lost or found item yet.",
          '<button type="button" class="btn btn--outline btn--sm" id="lf-empty-new">Report an Item</button>',
        );
      }
      return C.emptyState("No lost or found items are posted right now.");
    }
    return list.map(cardHtml).join("");
  }
  function tabsHtml() {
    return (
      '<div class="sf-tabs">' +
      '<button type="button" class="sf-tab' +
      (state.tab === "browse" ? " is-on" : "") +
      '" id="lf-tab-browse" data-tab="browse">Browse Items</button>' +
      '<button type="button" class="sf-tab' +
      (state.tab === "mine" ? " is-on" : "") +
      '" id="lf-tab-mine" data-tab="mine">My Reports</button></div>'
    );
  }
  function listHtml() {
    return (
      '<div class="lf-page">' +
      '<button type="button" class="sf-new" id="lf-new">' +
      '<span class="sf-new__plus">+</span>Report a Lost or Found Item</button>' +
      '<div class="sf-search">' +
      '<input type="search" class="sf-search__input" id="lf-q" aria-label="Search items" ' +
      'placeholder="Search by item, category, or location&hellip;" value="' +
      U.esc(state.q) +
      '">' +
      "</div>" +
      tabsHtml() +
      '<div class="lf-grid" id="lf-list">' +
      cardsHtml() +
      "</div></div>"
    );
  }

  /* -------------------------------------------------------- report form */
  function createHtml() {
    return (
      '<div class="sf sf--create">' +
      '<h1 class="sf-create__h">Report a Lost or Found Item</h1>' +
      '<p class="sf-create__sub">Help the OSAS office return items to their owners</p>' +
      '<div class="sf-form">' +
      '<div class="sf-field"><span class="sf-label">What are you reporting? <i>*</i></span>' +
      '<div class="lf-toggle" id="lf-type">' +
      '<button type="button" class="lf-toggle__btn' +
      (state.type === "Lost" ? " is-on" : "") +
      '" id="lf-type-lost" data-type="Lost">I lost an item</button>' +
      '<button type="button" class="lf-toggle__btn' +
      (state.type === "Found" ? " is-on" : "") +
      '" id="lf-type-found" data-type="Found">I found an item</button></div></div>' +
      '<div class="sf-field"><label class="sf-label" for="lf-title">Item Name <i>*</i></label>' +
      '<input class="sf-input" id="lf-title" type="text" placeholder="e.g., Black wallet with a student ID"></div>' +
      '<div class="sf-field"><label class="sf-label" for="lf-category">Category <i>*</i></label>' +
      '<select class="sf-input" id="lf-category"><option value="">Select a category</option>' +
      OPTIONS.lostFoundCategories
        .map(function (category) {
          return (
            '<option value="' +
            U.esc(category) +
            '">' +
            U.esc(category) +
            "</option>"
          );
        })
        .join("") +
      "</select></div>" +
      '<div class="sf-field"><label class="sf-label" for="lf-location">Location (lost / found at) <i>*</i></label>' +
      '<select class="sf-input" id="lf-location"><option value="">Select a location</option>' +
      OPTIONS.lostFoundLocations
        .map(function (location) {
          return (
            '<option value="' +
            U.esc(location) +
            '">' +
            U.esc(location) +
            "</option>"
          );
        })
        .join("") +
      "</select></div>" +
      '<div class="sf-field"><label class="sf-label" for="lf-date">Date Lost / Found <i>*</i></label>' +
      '<input class="sf-input" id="lf-date" type="date" value="' +
      U.esc(U.now().slice(0, 10)) +
      '"></div>' +
      '<div class="sf-field"><label class="sf-label" for="lf-desc">Description <i>*</i></label>' +
      '<textarea class="sf-input sf-input--area" id="lf-desc" rows="5" ' +
      'placeholder="Describe the item, including identifying details only the owner would know..."></textarea></div>' +
      '<div class="sf-field"><label class="sf-label" for="lf-contact">Contact Number or Email (optional)</label>' +
      '<input class="sf-input" id="lf-contact" type="text" value="' +
      U.esc(email()) +
      '"></div>' +
      '<div class="sf-field"><span class="sf-label">Attach a Photo (Optional)</span>' +
      '<label class="sf-drop" for="lf-file">' +
      OSAS.icons.icon("upload", 17) +
      '<span class="sf-drop__text">Click to upload a PNG, JPG or PDF up to 10MB</span>' +
      '<span class="sf-drop__file" id="lf-file-name"></span></label>' +
      '<input class="sf-file" type="file" id="lf-file" accept=".png,.jpg,.jpeg,.pdf" ' +
      'aria-label="Attach a photo of the item"></div>' +
      '<div id="lf-alert"></div>' +
      '<button type="button" class="sf-submit" id="lf-submit">Post Report</button>' +
      '<button type="button" class="sf-cancel" id="lf-cancel">Cancel</button>' +
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
  function claimsHtml(row) {
    var claims = row.claims || [];
    if (!claims.length) {
      return C.emptyState("No ownership claim has been filed yet.");
    }
    return (
      '<div class="sf-thread">' +
      claims
        .map(function (claim) {
          var own =
            String(claim.claimantEmail).toLowerCase() ===
            String(email()).toLowerCase();
          var showName = mine(row) || own;
          return (
            '<div class="sf-msg' +
            (claim.status !== "Pending" ? " sf-msg--staff" : "") +
            '">' +
            '<div class="sf-msg__head"><b>' +
            U.esc(showName ? claim.claimantName : "Student claimant") +
            "</b>" +
            '<span class="sf-msg__time">' +
            stamp(claim.at) +
            "</span></div>" +
            '<p class="sf-msg__body">' +
            U.esc(
              showName
                ? claim.message
                : "A student filed a claim for this item.",
            ) +
            "</p>" +
            (showName && claim.attachment
              ? '<div class="sf-msg__file">' +
                OSAS.icons.icon("fileText", 12) +
                "<span>" +
                U.esc(claim.attachment) +
                "</span></div>"
              : "") +
            '<div class="sf-msg__head"><span class="sf-msg__time">' +
            C.badge(claim.status) +
            "</span>" +
            (claim.verifiedAt
              ? '<span class="sf-msg__time">Reviewed ' +
                OSAS.fmt.shortDate(claim.verifiedAt) +
                "</span>"
              : "") +
            "</div></div>"
          );
        })
        .join("") +
      "</div>"
    );
  }
  function claimBoxHtml(row) {
    if (row.type !== "Found" || mine(row)) {
      return "";
    }
    var existing = myClaim(row);
    if (existing) {
      return (
        '<section class="sf-sec"><h2 class="sf-sec__h">Your Claim</h2>' +
        C.alert(
          existing.status === "Pending"
            ? "info"
            : existing.status === "Verified"
              ? "success"
              : "warning",
          "Claim " + existing.status.toLowerCase(),
          existing.status === "Pending"
            ? "The OSAS office will verify your ownership details and contact you through your university email."
            : existing.status === "Verified"
              ? "Your ownership was verified. Claim the item at the OSAS office and bring your student ID."
              : "The office could not verify this claim. File a new claim with stronger proof if the item is still unclaimed.",
        ) +
        (existing.attachment
          ? '<div class="sf-msg__file">' +
            OSAS.icons.icon("fileText", 12) +
            "<span>" +
            U.esc(existing.attachment) +
            "</span></div>"
          : "") +
        "</section>"
      );
    }
    if (
      row.status === "Returned" ||
      row.status === "Archived" ||
      row.status === "Unclaimed"
    ) {
      return (
        '<section class="sf-sec"><h2 class="sf-sec__h">Ownership Claim</h2>' +
        C.alert(
          "warning",
          "This item is no longer available",
          "The item has been marked as " +
            row.status.toLowerCase() +
            " and can no longer be claimed.",
        ) +
        "</section>"
      );
    }
    return (
      '<section class="sf-sec"><h2 class="sf-sec__h">This Item Is Mine</h2>' +
      '<div class="lf-claim">' +
      '<div class="lf-claim__h">File an ownership claim</div>' +
      '<div class="sf-field"><span class="sf-label">Proof of Ownership <i>*</i></span>' +
      '<input class="sf-input" id="lf-claim-proof" type="text" ' +
      'placeholder="e.g., Student ID 22-00104 and a photo of the item"></div>' +
      '<div class="sf-field"><span class="sf-label">Describe the Item <i>*</i></span>' +
      '<textarea class="sf-input sf-input--area" id="lf-claim-msg" rows="4" ' +
      'placeholder="Mention details only the owner would know — contents, marks, engravings..."></textarea></div>' +
      '<div class="sf-field"><span class="sf-label">Photo of the Item (Optional)</span>' +
      '<label class="sf-drop" for="lf-claim-file">' +
      OSAS.icons.icon("upload", 17) +
      '<span class="sf-drop__text">Attach or take a photo — PNG or JPG up to 10MB</span>' +
      '<span class="sf-drop__file" id="lf-claim-file-name">' +
      U.esc(state.claimAttachment) +
      "</span></label>" +
      '<input class="sf-file" type="file" id="lf-claim-file" accept="image/*" ' +
      'aria-label="Attach a photo of the item"></div>' +
      '<div id="lf-claim-alert"></div>' +
      '<button type="button" class="sf-submit" id="lf-claim-submit">Submit Claim</button>' +
      "</div></section>"
    );
  }
  function detailHtml(row) {
    return (
      '<div class="sf sf--detail"><div class="sf-ticket">' +
      '<div class="sf-ticket__top"><span class="sf-code">' +
      U.esc(row.code) +
      "</span>" +
      C.badge(row.type) +
      " " +
      C.badge(row.status) +
      "</div>" +
      '<h1 class="sf-ticket__title">' +
      U.esc(row.title) +
      "</h1>" +
      '<span class="sf-ticket__cat">' +
      U.esc(row.category) +
      "</span>" +
      '<div class="sf-facts">' +
      factHtml("Date Lost / Found", OSAS.fmt.shortDate(row.dateOccurred)) +
      factHtml("Location", row.location) +
      factHtml("Reported By", mine(row) ? "You" : row.reportedBy) +
      factHtml("Custody", row.custody || "—") +
      "</div>" +
      '<section class="sf-sec"><h2 class="sf-sec__h">Description</h2>' +
      '<p class="sf-sec__p">' +
      U.esc(row.description) +
      "</p></section>" +
      (row.attachment
        ? '<section class="sf-sec"><h2 class="sf-sec__h">Attached File</h2>' +
          '<div class="sf-media"><span class="sf-media__ico">' +
          OSAS.icons.icon("fileText", 16) +
          "</span>" +
          '<span class="sf-media__meta"><b>' +
          U.esc(row.attachment) +
          "</b>" +
          "<small>Attached by " +
          U.esc(mine(row) ? "you" : row.reportedBy) +
          " on " +
          OSAS.fmt.shortDate(row.postedAt) +
          "</small></span></div></section>"
        : "") +
      (mine(row)
        ? '<section class="sf-sec"><h2 class="sf-sec__h">Claims Received</h2>' +
          claimsHtml(row) +
          "</section>"
        : "") +
      claimBoxHtml(row) +
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
    bindOnce($("lf-new"), "click", function () {
      goReport();
    });
    bindOnce($("lf-empty-new"), "click", function () {
      goReport();
    });
    bindOnce($("lf-q"), "input", function (event) {
      state.q = event.target.value;
      $("lf-list").innerHTML = cardsHtml();
      bindOnce($("lf-empty-new"), "click", function () {
        goReport();
      });
    });
    ["browse", "mine"].forEach(function (tab) {
      bindOnce($("lf-tab-" + tab), "click", function () {
        state.tab = tab;
        $("lf-tab-browse").classList.toggle("is-on", tab === "browse");
        $("lf-tab-mine").classList.toggle("is-on", tab === "mine");
        $("lf-list").innerHTML = cardsHtml();
        bindOnce($("lf-empty-new"), "click", function () {
          goReport();
        });
      });
    });
  }

  function formError(id, title, text) {
    $("lf-alert").innerHTML = C.alert("warning", title, text);
    var node = $(id);
    if (node && node.focus) {
      node.focus();
    }
  }
  function setType(type) {
    state.type = type;
    var lost = $("lf-type-lost"),
      found = $("lf-type-found");
    if (lost) {
      lost.classList.toggle("is-on", type === "Lost");
    }
    if (found) {
      found.classList.toggle("is-on", type === "Found");
    }
  }
  function submitReport() {
    var title = String(($("lf-title") || {}).value || "").trim();
    var category = String(($("lf-category") || {}).value || "").trim();
    var location = String(($("lf-location") || {}).value || "").trim();
    var date = String(($("lf-date") || {}).value || "").trim();
    var description = String(($("lf-desc") || {}).value || "").trim();
    if (!title) {
      formError(
        "lf-title",
        "Item name required",
        "Give the item a short, recognizable name.",
      );
      return;
    }
    if (!category) {
      formError(
        "lf-category",
        "Category required",
        "Choose the category that fits the item.",
      );
      return;
    }
    if (!location) {
      formError(
        "lf-location",
        "Location required",
        "Select where the item was lost or found.",
      );
      return;
    }
    if (!date) {
      formError(
        "lf-date",
        "Date required",
        "Enter the date the item was lost or found.",
      );
      return;
    }
    if (!description) {
      formError(
        "lf-desc",
        "Description required",
        "Describe the item and its identifying details.",
      );
      return;
    }
    var now = U.now();
    var code = OSAS.store.nextId("lostFound", "LF-2026", 3);
    var record = {
      id: code,
      code: code,
      type: state.type,
      title: title,
      category: category,
      description: description,
      location: location,
      dateOccurred: date + "T00:00:00",
      reportedBy: fullName(),
      reporterEmail: email(),
      studentId: studentId(),
      contact: String(($("lf-contact") || {}).value || "").trim() || email(),
      custody: state.type === "Found" ? "OSAS Office" : "—",
      status: "Open",
      attachment: state.attachment || "",
      postedAt: now,
      updatedAt: now,
      claims: [],
      returnedTo: "",
      returnedAt: "",
    };
    OSAS.store.insert("lostFound", record);
    OSAS.store.logActivity({
      tone: state.type === "Found" ? "blue" : "amber",
      icon: "search",
      title:
        state.type === "Found" ? "Found Item Reported" : "Lost Item Reported",
      text:
        fullName() +
        " reported " +
        code +
        ' — "' +
        title +
        '" (' +
        category +
        ").",
    });
    C.toast("success", "Report posted", code + " — " + title);
    state.attachment = "";
    window.location.hash = "#/item/" + code;
  }
  function wireCreate() {
    bindOnce($("lf-barback"), "click", goList);
    bindOnce($("lf-cancel"), "click", goList);
    bindOnce($("lf-submit"), "click", submitReport);
    bindOnce($("lf-type-lost"), "click", function () {
      setType("Lost");
    });
    bindOnce($("lf-type-found"), "click", function () {
      setType("Found");
    });
    bindOnce($("lf-file"), "change", function (event) {
      var file = event.target && event.target.files && event.target.files[0];
      state.attachment = file ? file.name : "";
      var label = $("lf-file-name");
      if (label) {
        label.textContent = state.attachment || "";
      }
    });
  }

  function submitClaim() {
    var row = hashRow();
    if (!row) {
      return;
    }
    var proof = String(($("lf-claim-proof") || {}).value || "").trim();
    var message = String(($("lf-claim-msg") || {}).value || "").trim();
    if (!proof || !message) {
      $("lf-claim-alert").innerHTML = C.alert(
        "warning",
        "Incomplete claim",
        "Provide proof of ownership and describe identifying details of the item.",
      );
      return;
    }
    var now = U.now();
    var claimRecord = {
      id: "CLM-" + Date.now().toString(36).toUpperCase(),
      claimantName: fullName(),
      claimantEmail: email(),
      studentId: studentId(),
      proof: proof,
      message: message,
      attachment: state.claimAttachment || "",
      at: now,
      status: "Pending",
      verifiedBy: "",
      verifiedAt: "",
    };
    OSAS.store.update("lostFound", row.id, {
      status: "Claim Pending",
      claims: (row.claims || []).concat([claimRecord]),
      updatedAt: now,
    });
    OSAS.store.logActivity({
      tone: "blue",
      icon: "search",
      title: "Lost & Found Claim Filed",
      text:
        fullName() +
        " filed a claim for " +
        row.code +
        ' — "' +
        row.title +
        '".',
    });
    C.toast(
      "success",
      "Claim submitted",
      row.code + " — the OSAS office will verify your proof.",
    );
    renderDetail(byCode(row.code) || row);
  }
  function wireDetail() {
    bindOnce($("lf-barback"), "click", goBack);
    bindOnce($("lf-claim-submit"), "click", submitClaim);
    bindOnce($("lf-claim-file"), "change", function (event) {
      var file = event.target && event.target.files && event.target.files[0];
      state.claimAttachment = file ? file.name : "";
      var label = $("lf-claim-file-name");
      if (label) {
        label.textContent = state.claimAttachment || "";
      }
    });
  }

  /* --------------------------------------------------------- bar + nav */
  function barBack(label) {
    shell.setBar({
      menu: false,
      bell: false,
      avatar: false,
      title:
        '<button type="button" class="sf-back" id="lf-barback">' +
        OSAS.icons.icon("chevronLeft", 17) +
        label +
        "</button>",
      extra: "",
    });
  }
  function goList() {
    window.location.hash = "";
  }
  function goReport() {
    window.location.hash = "#/report";
  }
  function goBack() {
    window.location.hash = "";
  }

  /* -------------------------------------------------------- live routing */
  function hashRow() {
    var match = (window.location.hash || "").match(
      /^#\/item\/([A-Za-z0-9-]+)$/,
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
    barBack("Lost & Found");
    state.attachment = "";
    state.type = "Lost";
    main.innerHTML = createHtml();
    wireCreate();
    scrollTop();
  }
  function renderDetail(row) {
    barBack("Item Details");
    state.claimAttachment = "";
    main.innerHTML = detailHtml(row);
    wireDetail();
    scrollTop();
  }

  function route() {
    var hash = window.location.hash || "";
    if (hash === "#/report") {
      renderCreate();
      return;
    }
    var match = hash.match(/^#\/item\/([A-Za-z0-9-]+)$/);
    if (match) {
      var row = byCode(match[1]);
      if (row) {
        renderDetail(row);
        return;
      }
    }
    renderList();
  }

  window.addEventListener("hashchange", route);
  route();
})();
