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

    /* Filter bars submit automatically as the user searches or changes filters. */
    document.querySelectorAll("form.filter-bar").forEach(function (form) {
      var timer = null;

      function submitSoon() {
        window.clearTimeout(timer);
        timer = window.setTimeout(function () {
          form.submit();
        }, 350);
      }

      function submitNow() {
        window.clearTimeout(timer);
        form.submit();
      }

      form.querySelectorAll("input").forEach(function (control) {
        var type = (control.getAttribute("type") || "text").toLowerCase();

        if (["hidden", "submit", "button", "reset", "file", "checkbox", "radio"].indexOf(type) !== -1) {
          return;
        }

        control.addEventListener("input", submitSoon);
        control.addEventListener("change", submitNow);
      });

      form.querySelectorAll("select").forEach(function (control) {
        control.addEventListener("change", submitNow);
      });
    });

    /* Show the selected filename in custom upload areas. */
    document.querySelectorAll(".upload-drop__input").forEach(function (input) {
      input.addEventListener("change", function () {
        var label = input.labels && input.labels.length ? input.labels[0] : null;
        var filename = label ? label.querySelector("[data-upload-filename]") : null;
        var prompts = label ? label.querySelectorAll("[data-upload-prompt]") : [];
        var file = input.files && input.files.length ? input.files[0] : null;

        if (!filename) return;

        filename.textContent = file ? file.name : "";
        filename.hidden = !file;
        prompts.forEach(function (prompt) {
          prompt.hidden = Boolean(file);
        });
      });
    });

    /* Announcement rich-content toolbar inserts basic HTML into the body field. */
    document.querySelectorAll("[data-editor-toolbar]").forEach(function (toolbar) {
      var target = document.getElementById(toolbar.getAttribute("data-target") || "");

      if (!target) return;

      toolbar.querySelectorAll("button[data-editor-before]").forEach(function (button) {
        button.addEventListener("click", function () {
          var before = button.getAttribute("data-editor-before") || "";
          var after = button.getAttribute("data-editor-after") || "";
          var placeholder = button.getAttribute("data-editor-placeholder") || "";
          var start = target.selectionStart || 0;
          var end = target.selectionEnd || 0;
          var selected = target.value.slice(start, end) || placeholder;
          var insertion = before + selected + after;

          target.value = target.value.slice(0, start) + insertion + target.value.slice(end);
          target.focus();
          var caret = start + insertion.length;
          target.selectionStart = target.selectionEnd = caret;
        });
      });
    });

    /* Radio pills and content-type tabs reflect the selected option. */
    document.querySelectorAll(".radio-pill input[type=radio], .type-tab input[type=radio]").forEach(function (input) {
      input.addEventListener("change", function () {
        document.querySelectorAll('input[name="' + input.name + '"]').forEach(function (peer) {
          var label = peer.closest("label");

          if (!label) return;

          var modifier = label.classList.contains("type-tab") ? "type-tab--on" : "radio-pill--on";
          label.classList.toggle(modifier, peer.checked);
        });
      });
    });

    /* Orientation quiz countdown; auto-submits at expiry. */
    document.querySelectorAll("[data-quiz-countdown]").forEach(function (element) {
      var expires = new Date(element.getAttribute("data-expires") || "").getTime();

      if (!Number.isFinite(expires)) return;

      function tick() {
        var remaining = expires - Date.now();

        if (remaining <= 0) {
          element.textContent = "Time expired";
          var form = document.getElementById("quiz-form");
          if (form) form.submit();
          return;
        }

        var minutes = Math.floor(remaining / 60000);
        var seconds = Math.floor((remaining % 60000) / 1000);
        element.textContent = minutes + ":" + (seconds < 10 ? "0" : "") + seconds + " remaining";
      }

      tick();
      setInterval(tick, 1000);
    });

    /* Orientation content type: toggle between File and Link panels. */
    document.querySelectorAll("[data-content-toggle]").forEach(function (group) {
      var form = group.closest("form") || document;

      function sync(value) {
        group.querySelectorAll(".type-tab").forEach(function (label) {
          label.classList.toggle("type-tab--on", label.getAttribute("data-content-value") === value);
        });

        form.querySelectorAll("[data-content-panel]").forEach(function (panel) {
          panel.hidden = panel.getAttribute("data-content-panel") !== value;
        });
      }

      group.querySelectorAll("input[type=radio]").forEach(function (input) {
        input.addEventListener("change", function () {
          sync(input.value);
        });
      });
    });

    /* Inline quiz builder on Add Module. */
    document.querySelectorAll("[data-quiz-builder]").forEach(function (builder) {
      var form = builder.closest("form");
      var list = builder.querySelector("[data-question-list]");
      var template = document.getElementById("quiz-question-template");
      var nextIndex = 0;

      function reindex() {
        if (!list) return;

        list.querySelectorAll("[data-question]").forEach(function (block, index) {
          block.querySelectorAll("[name]").forEach(function (input) {
            input.name = input.name.replace(/questions\[\d+\]/g, "questions[" + index + "]");
          });
        });
      }

      var add = builder.querySelector("[data-add-question]");

      if (add && template && list) {
        add.addEventListener("click", function () {
          var wrapper = document.createElement("div");
          wrapper.innerHTML = template.innerHTML.replace(/__INDEX__/g, String(nextIndex)).trim();
          list.appendChild(wrapper.firstElementChild);
          nextIndex++;
          reindex();
        });

        list.addEventListener("click", function (event) {
          var remove = event.target.closest("[data-remove-question]");

          if (remove) {
            remove.closest("[data-question]").remove();
            reindex();
          }
        });
      }

      if (form) {
        var radios = form.querySelectorAll('input[name="verification_method"]');

        function sync() {
          var checked = form.querySelector('input[name="verification_method"]:checked');
          builder.hidden = !checked || checked.value !== "Quiz Assessment";
        }

        radios.forEach(function (radio) {
          radio.addEventListener("change", sync);
        });
        sync();
      }
    });

    /* Orientation quiz wizard: one question at a time. */
    document.querySelectorAll("[data-quiz-wizard]").forEach(function (form) {
      var steps = Array.prototype.slice.call(form.querySelectorAll(".or-quiz__step"));
      var current = document.querySelector("[data-quiz-current]");
      var progress = document.querySelector("[data-quiz-progress]");
      var prev = form.querySelector("[data-quiz-prev]");
      var next = form.querySelector("[data-quiz-next]");
      var index = 0;

      function render() {
        steps.forEach(function (step, i) {
          step.classList.toggle("or-quiz__step--on", i === index);
        });

        if (current) current.textContent = index + 1;
        if (progress) progress.style.width = ((index + 1) / Math.max(1, steps.length) * 100) + "%";
        if (prev) prev.disabled = index === 0;
        if (next) next.style.display = index >= steps.length - 1 ? "none" : "";
      }

      if (prev) prev.addEventListener("click", function () { if (index > 0) { index--; render(); } });
      if (next) next.addEventListener("click", function () { if (index < steps.length - 1) { index++; render(); } });

      render();
    });

    /* Lost & Found cards flip to show the attached photo. */
    document.querySelectorAll("[data-lf-card]").forEach(function (card) {
      card.querySelectorAll("[data-lf-flip]").forEach(function (button) {
        button.addEventListener("click", function (event) {
          event.preventDefault();
          event.stopPropagation();
          card.classList.toggle("is-flipped");
        });
      });
    });

    /* Confirm important actions in a portal-styled modal instead of a browser prompt. */
    var confirmDialog = document.createElement("dialog");
    confirmDialog.className = "app-confirm";
    confirmDialog.setAttribute("aria-labelledby", "app-confirm-title");

    var confirmCard = document.createElement("div");
    confirmCard.className = "app-confirm__card";
    var confirmIcon = document.createElement("span");
    confirmIcon.className = "app-confirm__icon";
    confirmIcon.setAttribute("aria-hidden", "true");
    confirmIcon.textContent = "!";
    var confirmTitle = document.createElement("h2");
    confirmTitle.className = "app-confirm__title";
    confirmTitle.id = "app-confirm-title";
    confirmTitle.textContent = "Please confirm";
    var confirmMessage = document.createElement("p");
    confirmMessage.className = "app-confirm__message";
    var confirmActions = document.createElement("div");
    confirmActions.className = "app-confirm__actions";
    var confirmCancel = document.createElement("button");
    confirmCancel.className = "btn btn--ghost";
    confirmCancel.type = "button";
    confirmCancel.textContent = "Cancel";
    var confirmAccept = document.createElement("button");
    confirmAccept.className = "btn btn--primary";
    confirmAccept.type = "button";
    confirmAccept.textContent = "Confirm";

    confirmActions.appendChild(confirmCancel);
    confirmActions.appendChild(confirmAccept);
    confirmCard.appendChild(confirmIcon);
    confirmCard.appendChild(confirmTitle);
    confirmCard.appendChild(confirmMessage);
    confirmCard.appendChild(confirmActions);
    confirmDialog.appendChild(confirmCard);
    document.body.appendChild(confirmDialog);

    var pendingConfirmation = null;
    document.querySelectorAll("form[data-confirm], form button[data-confirm], form input[data-confirm]").forEach(function (element) {
      var form = element.tagName === "FORM" ? element : element.form;
      if (!form || form.dataset.confirmHandler) return;
      form.dataset.confirmHandler = "true";

      form.addEventListener("submit", function (event) {
        if (form.dataset.confirmApproved === "true") {
          delete form.dataset.confirmApproved;
          return;
        }

        var submitter = event.submitter || null;
        var message = (submitter && submitter.getAttribute("data-confirm")) || form.getAttribute("data-confirm");
        if (!message) return;

        event.preventDefault();
        pendingConfirmation = { form: form, submitter: submitter };
        confirmMessage.textContent = message;
        confirmTitle.textContent = submitter && submitter.getAttribute("data-confirm-title")
          ? submitter.getAttribute("data-confirm-title")
          : "Please confirm";
        var actionLabel = submitter ? submitter.textContent.trim() : "";
        confirmAccept.textContent = actionLabel && actionLabel.length <= 24 ? actionLabel : "Confirm";
        confirmAccept.classList.toggle("btn--danger", /delete|remove|deactivate/i.test(message));
        confirmAccept.classList.toggle("btn--primary", !/delete|remove|deactivate/i.test(message));

        if (!confirmDialog.open) confirmDialog.showModal();
        confirmCancel.focus();
      });
    });

    confirmCancel.addEventListener("click", function () {
      pendingConfirmation = null;
      confirmDialog.close();
    });

    confirmDialog.addEventListener("click", function (event) {
      if (event.target === confirmDialog) {
        pendingConfirmation = null;
        confirmDialog.close();
      }
    });

    confirmAccept.addEventListener("click", function () {
      var confirmation = pendingConfirmation;
      pendingConfirmation = null;
      confirmDialog.close();

      if (!confirmation || !confirmation.form.isConnected) return;
      confirmation.form.dataset.confirmApproved = "true";
      if (confirmation.submitter && confirmation.submitter.form === confirmation.form) {
        confirmation.form.requestSubmit(confirmation.submitter);
      } else {
        confirmation.form.requestSubmit();
      }
      delete confirmation.form.dataset.confirmApproved;
    });

    confirmDialog.addEventListener("close", function () {
      pendingConfirmation = null;
    });

    /* Dismiss short-lived status toasts without affecting page layout. */
    document.querySelectorAll("[data-auto-dismiss-toast]").forEach(function (toast) {
      var close = toast.querySelector("[data-dismiss-toast]");
      var timer = window.setTimeout(function () {
        toast.remove();
      }, 5000);

      if (close) {
        close.addEventListener("click", function () {
          window.clearTimeout(timer);
          toast.remove();
        });
      }
    });

    /* Send a tailored orientation progress notification from tracking. */
    var notifyDialog = document.querySelector("[data-orientation-notify-modal]");

    if (notifyDialog) {
      var notifyForm = notifyDialog.querySelector("form");
      var notifyMessage = notifyDialog.querySelector("[data-notify-message]");
      var notifyRecipient = notifyDialog.querySelector("[data-notify-recipient]");
      var notifyAudience = notifyDialog.querySelector("[data-notify-audience]");
      var notifyReturn = notifyDialog.querySelector("[data-notify-return]");

      document.querySelectorAll("[data-notify-open]").forEach(function (button) {
        button.addEventListener("click", function () {
          if (!notifyForm || !notifyMessage || typeof notifyDialog.showModal !== "function") return;

          notifyForm.action = button.getAttribute("data-notify-url") || "";
          notifyMessage.value = button.getAttribute("data-notify-message") || "";
          if (notifyRecipient) notifyRecipient.textContent = button.getAttribute("data-notify-name") || "the student";
          if (notifyAudience) notifyAudience.textContent = button.getAttribute("data-notify-audience") || "in their student portal";
          if (notifyReturn) notifyReturn.value = button.getAttribute("data-notify-return") || "list";
          notifyDialog.showModal();
          notifyMessage.focus();
        });
      });

      notifyDialog.querySelectorAll("[data-notify-close]").forEach(function (button) {
        button.addEventListener("click", function () { notifyDialog.close(); });
      });

      notifyDialog.addEventListener("click", function (event) {
        if (event.target === notifyDialog) notifyDialog.close();
      });
    }

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
