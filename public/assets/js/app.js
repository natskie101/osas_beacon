/* ==========================================================================
   BEACON OSAS Portal — progressive enhancement for the PHP shell.
   Everything here is optional: every form and page works without JavaScript.
   ========================================================================== */
(function () {
  "use strict";

  function onReady(fn) {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", fn);
    } else {
      fn();
    }
  }

  onReady(function () {
    /* Mobile sidebar */
    var sidebar = document.getElementById("sidebar");
    var backdrop = document.getElementById("sidebar-backdrop");
    var menuButton = document.getElementById("menu-btn");
    var closeButton = document.getElementById("sidebar-close");

    function toggleSidebar(force) {
      if (!sidebar || !backdrop) return;
      var open = typeof force === "boolean" ? force : !sidebar.classList.contains("sidebar--on");
      sidebar.classList.toggle("sidebar--on", open);
      backdrop.classList.toggle("sidebar-backdrop--on", open);
    }

    if (menuButton) menuButton.addEventListener("click", function () { toggleSidebar(); });
    if (closeButton) closeButton.addEventListener("click", function () { toggleSidebar(false); });
    if (backdrop) backdrop.addEventListener("click", function () { toggleSidebar(false); });

    /* Password reveal buttons */
    document.querySelectorAll("[data-password-toggle]").forEach(function (button) {
      button.addEventListener("click", function () {
        var input = document.getElementById(button.getAttribute("data-password-toggle"));
        if (!input) return;

        var showing = input.type === "text";
        input.type = showing ? "password" : "text";
        button.setAttribute("aria-label", showing ? "Show password" : "Hide password");
      });
    });

    /* Destructive/session actions ask once before submitting */
    document.querySelectorAll("form[data-confirm]").forEach(function (form) {
      form.addEventListener("submit", function (event) {
        var message = form.getAttribute("data-confirm") || "Continue?";
        if (!window.confirm(message)) {
          event.preventDefault();
        }
      });
    });

    /* Close the account menu when clicking outside it */
    document.addEventListener("click", function (event) {
      document.querySelectorAll("details.account[open]").forEach(function (details) {
        if (!details.contains(event.target)) details.removeAttribute("open");
      });
    });

    /* Keep derived access fields in sync with the selected role. */
    var roleSelect = document.querySelector("[data-role-select]");

    if (roleSelect) {
      var accessLevel = document.querySelector("[data-access-level]");
      var accountTier = document.querySelector("[data-account-tier]");
      var studentFields = document.querySelector("[data-student-fields]");
      var accessLevels = {
        Student: "Student — Portal Access",
        Administrator: "Administrator — Technical Support Access",
        Coordinator: "Coordinator — Full System Access"
      };

      function syncRole() {
        var role = roleSelect.value;

        if (accessLevel) accessLevel.value = accessLevels[role] || "";
        if (accountTier) accountTier.value = role === "Student" ? "Student Tier" : "Admin Tier";
        if (studentFields) studentFields.hidden = role !== "Student";
      }

      roleSelect.addEventListener("change", syncRole);
      syncRole();
    }

    /* Login tagline carousel */
    var tag = document.getElementById("auth-tag");
    var dots = Array.prototype.slice.call(document.querySelectorAll("#auth-dots .pdot"));

    if (tag && dots.length) {
      var index = 0;

      function show(next) {
        index = (next + dots.length) % dots.length;
        dots.forEach(function (dot, i) {
          dot.classList.toggle("pdot--on", i === index);
        });
        tag.textContent = dots[index].getAttribute("data-tagline") || tag.textContent;
      }

      dots.forEach(function (dot, i) {
        dot.addEventListener("click", function () { show(i); });
      });

      setInterval(function () { show(index + 1); }, 7000);
    }
  });
})();
