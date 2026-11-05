/* ==========================================================================
   Student module — Rules and Regulation  (student/rules.html)
   Event-style rule list + inline policy detail view (hash routed, e.g.
   #/rule/RULE-118). The detail is rendered in the page body instead of a
   modal, and the shell top bar carries the back + bookmark actions.
   The shell, the student-only access gate and the navigation live in
   student-shell.js; only Published regulations are visible to students.
   ========================================================================== */
(function () {
  "use strict";

  var shell = OSAS.studentShell.mount({
    page: "rules",
    title: "Rules and Regulation",
  });
  if (!shell) {
    return;
  }

  var U = OSAS.util,
    C = OSAS.components;
  var main = shell.main;
  var user = shell.user;

  /* bookmarks + acknowledgements, kept per browser (reset with the demo data) */
  var STORE_KEY = "beacon.osas.student.rules";
  var state = { q: "", category: "All" };

  var BOOKMARK_SVG =
    '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" ' +
    'stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
    '<path d="M6 4h12v16l-6-4-6 4V4Z"/></svg>';

  function $(id) {
    return document.getElementById(id);
  }

  /* --------------------------------------------------------------- data */
  function published() {
    return OSAS.store.all("regulations").filter(function (r) {
      return r.status === "Published";
    });
  }

  function ruleCode(reg) {
    var n = parseInt(String(reg.code).split("-").pop(), 10);
    return "RULE-" + (100 + (isNaN(n) ? 0 : n));
  }

  function approver(reg) {
    return String(reg.approvalBody || "OSAS Administration").split(" — ")[0];
  }

  function categories() {
    var counts = {};
    published().forEach(function (r) {
      counts[r.category] = (counts[r.category] || 0) + 1;
    });
    return Object.keys(counts)
      .sort(function (a, b) {
        return counts[b] - counts[a] || a.localeCompare(b);
      })
      .map(function (name) {
        return { name: name, count: counts[name] };
      });
  }

  function filtered() {
    var q = state.q.trim().toLowerCase();
    return U.sortBy(published(), "updatedAt", "desc").filter(function (r) {
      if (state.category !== "All" && r.category !== state.category) {
        return false;
      }
      if (!q) {
        return true;
      }
      return (
        (r.title + " " + r.summary + " " + r.category + " " + ruleCode(r))
          .toLowerCase()
          .indexOf(q) > -1
      );
    });
  }

  function byRuleCode(code) {
    return (
      published().filter(function (row) {
        return ruleCode(row) === code;
      })[0] || null
    );
  }

  /* ------------------------------------- bookmarks / acknowledgements */
  function readMap() {
    try {
      return JSON.parse(window.localStorage.getItem(STORE_KEY)) || {};
    } catch (err) {
      return {};
    }
  }
  function writeMap(map) {
    try {
      window.localStorage.setItem(STORE_KEY, JSON.stringify(map));
    } catch (err) {
      /* ignore */
    }
  }

  function sections(html) {
    var out = [],
      re = /<h4>([\s\S]*?)<\/h4>/g,
      match,
      cursor = 0,
      current = null;
    while ((match = re.exec(html))) {
      if (current && match.index > cursor) {
        current.html += html.slice(cursor, match.index);
      }
      current = { heading: match[1], html: "" };
      out.push(current);
      cursor = match.index + match[0].length;
    }
    if (current) {
      current.html += html.slice(cursor);
    }
    if (!out.length) {
      out.push({ heading: "", html: html || "" });
    }
    return out;
  }

  /* --------------------------------------------------------- list view */
  function chipId(name) {
    return "sr-chip-" + U.slug(name);
  }

  function chipsHtml() {
    var html =
      '<button type="button" class="se-chip' +
      (state.category === "All" ? " is-on" : "") +
      '" id="sr-chip-all" data-cat="All">All Rules (' +
      published().length +
      ")</button>";
    categories().forEach(function (c) {
      html +=
        '<button type="button" class="se-chip' +
        (state.category === c.name ? " is-on" : "") +
        '" id="' +
        chipId(c.name) +
        '" data-cat="' +
        U.esc(c.name) +
        '">' +
        U.esc(c.name) +
        " (" +
        c.count +
        ")</button>";
    });
    return html;
  }

  function cardHtml(reg) {
    return (
      '<a class="se-card" href="#/rule/' +
      U.esc(ruleCode(reg)) +
      '">' +
      '<div class="se-card__top">' +
      '<span class="se-code">' +
      U.esc(ruleCode(reg)) +
      "</span>" +
      '<span class="se-pill se-pill--ongoing">In Force</span>' +
      "</div>" +
      '<h3 class="se-card__title">' +
      U.esc(reg.title) +
      "</h3>" +
      '<p class="se-card__text">' +
      U.esc(reg.summary) +
      "</p>" +
      '<div class="se-card__foot">' +
      '<div class="se-card__facts">' +
      '<span class="se-fact">' +
      OSAS.icons.icon("shield", 13) +
      "<span>" +
      U.esc(reg.category) +
      "</span></span>" +
      '<span class="se-fact">' +
      OSAS.icons.icon("calendar", 13) +
      "<span>Revised: " +
      OSAS.fmt.shortDate(reg.updatedAt) +
      "</span></span>" +
      "</div>" +
      '<span class="se-card__cta">View Policy</span>' +
      "</div>" +
      "</a>"
    );
  }

  function cardsHtml() {
    var rows = filtered();
    if (!rows.length) {
      return C.emptyState(
        state.q
          ? "No published rule matches &ldquo;" + U.esc(state.q) + "&rdquo;."
          : "No published rule matches the selected category.",
      );
    }
    return rows.map(cardHtml).join("");
  }

  function listHtml() {
    return (
      '<div class="se se--list">' +
      '<div class="se-search">' +
      '<input type="search" class="se-search__input" id="sr-q" aria-label="Search rules" ' +
      'placeholder="Search by rule title or category&hellip;" value="' +
      U.esc(state.q) +
      '">' +
      "</div>" +
      '<div class="se-chips" id="sr-chips">' +
      chipsHtml() +
      "</div>" +
      '<div class="se-list" id="sr-list">' +
      cardsHtml() +
      "</div>" +
      "</div>"
    );
  }

  /* -------------------------------------------------------- detail view */
  function factHtml(icon, label, value, wide) {
    return (
      '<div class="se-fact-box' +
      (wide ? " se-fact-box--wide" : "") +
      '">' +
      '<span class="se-fact-box__ico">' +
      OSAS.icons.icon(icon, 15) +
      "</span>" +
      '<div class="se-fact-box__body">' +
      '<span class="se-fact-box__label">' +
      label +
      "</span>" +
      "<b>" +
      U.esc(value) +
      "</b>" +
      "</div>" +
      "</div>"
    );
  }

  function sanctionsHtml(reg) {
    var tiers = reg.sanctions || [];
    if (!tiers.length) {
      return "";
    }
    return (
      '<section class="se-sec">' +
      '<h2 class="se-sec__h">Article V &mdash; Disciplinary Sanctions</h2>' +
      '<div class="sr-sanctions">' +
      tiers
        .map(function (t) {
          return (
            '<div class="sr-sanction"><b>' +
            U.esc(t.tier) +
            ":</b> " +
            U.esc(t.text) +
            "</div>"
          );
        })
        .join("") +
      "</div></section>"
    );
  }

  function ackHtml(reg, acked) {
    if (acked) {
      return (
        '<div class="sr-ack">' +
        '<div class="sr-ack__note">Policy acknowledged on ' +
        OSAS.fmt.dateTime(acked) +
        "</div></div>"
      );
    }
    return (
      '<div class="sr-ack">' +
      '<button type="button" class="sr-ack__btn" id="sr-ack">I acknowledge this policy</button>' +
      '<div class="sr-ack__note">Acknowledgement is stored in this browser for the demo.</div>' +
      "</div>"
    );
  }

  function detailHtml(reg) {
    var map = readMap();
    var acked = (map.ack || {})[reg.id];
    return (
      '<div class="se se--detail">' +
      '<div class="se-hero">' +
      '<span class="se-hero__eyebrow">' +
      U.esc(reg.category) +
      "</span>" +
      '<span class="se-hero__title">' +
      U.esc(reg.title) +
      "</span>" +
      '<span class="se-hero__org">' +
      U.esc(approver(reg)) +
      "</span>" +
      "</div>" +
      '<div class="se-head">' +
      '<span class="se-code">' +
      U.esc(ruleCode(reg)) +
      "</span>" +
      '<span class="se-open">Official student policy</span>' +
      '<span class="se-pill se-pill--ongoing">In Force</span>' +
      (acked
        ? '<span class="sr-pill sr-pill--ack">Acknowledged</span>'
        : "") +
      "</div>" +
      '<h1 class="se-title">' +
      U.esc(reg.title) +
      "</h1>" +
      '<div class="se-facts">' +
      factHtml("shield", "Category", reg.category) +
      factHtml(
        "calendar",
        "Last Revised:",
        OSAS.fmt.shortDate(reg.updatedAt) +
          " &middot; By " +
          U.esc(approver(reg)),
      ) +
      factHtml("clock", "Effective Date", reg.effectiveDate, true) +
      "</div>" +
      sections(reg.body)
        .map(function (s) {
          return (
            '<section class="se-sec">' +
            (s.heading
              ? '<h2 class="se-sec__h">' + U.esc(s.heading) + "</h2>"
              : "") +
            '<div class="se-sec__p sr-art__body">' +
            s.html +
            "</div></section>"
          );
        })
        .join("") +
      sanctionsHtml(reg) +
      ackHtml(reg, acked) +
      "</div>"
    );
  }

  /* ---------------------------------------------------------- behaviour */
  function bindOnce(node, type, fn) {
    if (!node) {
      return;
    } /* one live handler per node+event */
    node._bound = node._bound || {};
    if (node._bound[type]) {
      return;
    }
    node._bound[type] = true;
    node.addEventListener(type, fn);
  }

  function setCategory(key) {
    state.category = key;
    ["All"]
      .concat(
        categories().map(function (c) {
          return c.name;
        }),
      )
      .forEach(function (name) {
        var node = name === "All" ? $("sr-chip-all") : $(chipId(name));
        if (node) {
          node.classList.toggle("is-on", name === key);
        }
      });
    $("sr-list").innerHTML = cardsHtml();
  }

  function wireChips() {
    bindOnce($("sr-chip-all"), "click", function () {
      setCategory("All");
    });
    categories().forEach(function (c) {
      bindOnce($(chipId(c.name)), "click", function () {
        setCategory(c.name);
      });
    });
  }

  function wireList() {
    bindOnce($("sr-q"), "input", function (event) {
      state.q = event.target.value;
      $("sr-list").innerHTML = cardsHtml();
    });
    wireChips();
  }

  function toggleBookmark(reg, button) {
    var map = readMap();
    map.mark = map.mark || {};
    var now = !map.mark[reg.id];
    if (now) {
      map.mark[reg.id] = OSAS.util.now();
    } else {
      delete map.mark[reg.id];
    }
    writeMap(map);

    button.classList.toggle("is-on", now);
    button.setAttribute("aria-pressed", String(now));
    C.toast(
      "success",
      now ? "Policy bookmarked" : "Bookmark removed",
      reg.title,
    );
  }

  function acknowledge(reg) {
    var map = readMap();
    if ((map.ack || {})[reg.id]) {
      return;
    }
    map.ack = map.ack || {};
    map.ack[reg.id] = OSAS.util.now();
    writeMap(map);

    OSAS.store.logActivity({
      tone: "green",
      icon: "checkCircle",
      title: "Policy Acknowledged",
      text: reg.title + " was acknowledged by " + shell.fullName(user) + ".",
      actorId: user ? user.id : "",
      actorName: shell.fullName(user),
      targetUserId: user ? user.id : "",
      action: "policy.acknowledge",
      entity: "regulation",
      entityId: reg.id,
    });
    C.toast("success", "Policy acknowledged", reg.title);
    renderDetail(reg, true);
  }

  /* ---------------------------------------------------------- routing */
  function scrollTop() {
    if (window.scrollTo) {
      window.scrollTo(0, 0);
    }
  }

  function barFor(reg) {
    var map = readMap();
    var marked = !!(map.mark || {})[reg.id];
    shell.setBar({
      menu: false,
      bell: false,
      avatar: false,
      title:
        '<button type="button" class="se-back" id="sr-back">' +
        OSAS.icons.icon("chevronLeft", 17) +
        "Policy Details</button>",
      extra:
        '<button type="button" class="se-share sr-bookmark' +
        (marked ? " is-on" : "") +
        '" id="sr-bookmark" aria-pressed="' +
        (marked ? "true" : "false") +
        '" aria-label="Bookmark this policy">' +
        BOOKMARK_SVG +
        "</button>",
    });
  }

  function wireDetail(reg) {
    bindOnce($("sr-back"), "click", goBack);
    bindOnce($("sr-bookmark"), "click", function () {
      toggleBookmark(reg, $("sr-bookmark"));
    });
    bindOnce($("sr-ack"), "click", function () {
      acknowledge(reg);
    });
  }

  function goBack() {
    window.location.hash = "";
  }

  function renderList() {
    shell.setBar({}); /* restore title + bell + avatar */
    main.innerHTML = listHtml();
    wireList();
    scrollTop();
  }

  function renderDetail(reg, keepScroll) {
    barFor(reg);
    main.innerHTML = detailHtml(reg);
    wireDetail(reg);
    if (!keepScroll) {
      scrollTop();
    }
  }

  function route() {
    var match = (window.location.hash || "").match(
      /^#\/rule\/([A-Za-z0-9-]+)$/,
    );
    if (match) {
      var reg = byRuleCode(match[1]);
      if (reg) {
        renderDetail(reg);
        return;
      }
    }
    renderList();
  }

  window.addEventListener("hashchange", route);
  route();
})();
