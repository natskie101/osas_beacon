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

    /* Filter bars submit automatically when changing dropdown filters or clearing search. */
    document.querySelectorAll("form.filter-bar").forEach(function (form) {
      function submitNow() {
        form.submit();
      }

      form.querySelectorAll("select").forEach(function (control) {
        control.addEventListener("change", submitNow);
      });

      form.querySelectorAll("input").forEach(function (control) {
        var type = (control.getAttribute("type") || "text").toLowerCase();

        // Native search clear button (x)
        if (type === "search") {
          control.addEventListener("search", function () {
            if (control.value === "") {
              submitNow();
            }
          });
        }
      });
    });

    /* Retain focus on active search filter input if user just searched */
    try {
      var searchParam = new URLSearchParams(window.location.search).get("search");
      if (searchParam) {
        var searchInput = document.querySelector('form.filter-bar input[name="search"]');
        if (searchInput && document.activeElement === document.body) {
          searchInput.focus();
          var len = searchInput.value.length;
          searchInput.setSelectionRange(len, len);
        }
      }
    } catch (ignored) {
      /* Older browsers without URLSearchParams */
    }

    /* Show selected files and support dropping files onto custom upload areas. */
    function updateUploadLabel(input) {
      var dropZone = Array.prototype.slice.call(document.querySelectorAll(".upload-drop[for]")).find(function (zone) {
        return zone.getAttribute("for") === input.id;
      });
      var container = dropZone || input.closest(".field") || input.parentElement;
      var filename = container ? container.querySelector("[data-upload-filename]") : null;
      var prompts = container ? container.querySelectorAll("[data-upload-prompt]") : [];
      var file = input.files && input.files.length ? input.files[0] : null;

      if (!filename) return;

      filename.textContent = file ? file.name : "";
      filename.hidden = !file;
      prompts.forEach(function (prompt) {
        prompt.hidden = Boolean(file);
      });
    }

    document.querySelectorAll(".upload-drop__input").forEach(function (input) {
      input.addEventListener("change", function () {
        updateUploadLabel(input);
      });
    });

    document.querySelectorAll(".upload-drop[for]").forEach(function (dropZone) {
      var input = document.getElementById(dropZone.getAttribute("for") || "");
      if (!input || !input.matches(".upload-drop__input")) return;

      ["dragenter", "dragover"].forEach(function (eventName) {
        dropZone.addEventListener(eventName, function (event) {
          event.preventDefault();
          dropZone.classList.add("upload-drop--dragging");
        });
      });

      ["dragleave", "dragend"].forEach(function (eventName) {
        dropZone.addEventListener(eventName, function (event) {
          if (eventName === "dragleave" && dropZone.contains(event.relatedTarget)) return;
          dropZone.classList.remove("upload-drop--dragging");
        });
      });

      dropZone.addEventListener("drop", function (event) {
        event.preventDefault();
        dropZone.classList.remove("upload-drop--dragging");

        var files = event.dataTransfer && event.dataTransfer.files;
        if (!files || files.length === 0) return;

        var transfer = new DataTransfer();
        transfer.items.add(files[0]);
        input.files = transfer.files;
        input.dispatchEvent(new Event("change", { bubbles: true }));
      });
    });

    /* Preview a selected profile photo before submitting the profile form. */
    var profilePhotoInput = document.querySelector("[data-profile-photo-input]");
    var profilePhotoImage = document.querySelector("[data-profile-photo-image]");
    var profilePhotoInitials = document.querySelector("[data-profile-photo-initials]");
    var profilePhotoPreviewUrl = null;

    if (profilePhotoInput && profilePhotoImage) {
      profilePhotoInput.addEventListener("change", function () {
        var file = profilePhotoInput.files && profilePhotoInput.files.length ? profilePhotoInput.files[0] : null;

        if (profilePhotoPreviewUrl) {
          URL.revokeObjectURL(profilePhotoPreviewUrl);
          profilePhotoPreviewUrl = null;
        }

        if (!file || !/^image\/(jpeg|png)$/.test(file.type)) return;

        profilePhotoPreviewUrl = URL.createObjectURL(file);
        profilePhotoImage.src = profilePhotoPreviewUrl;
        profilePhotoImage.hidden = false;
        if (profilePhotoInitials) profilePhotoInitials.hidden = true;
      });
    }

    /* Toolbars marked data-editor-rich become a visual WYSIWYG editor backed by a
       hidden textarea, so the stored value stays the same cleaned HTML as before. */
    function setupRichEditor(toolbar, input) {
      var surface = document.getElementById(input.id + "-editor");

      if (!surface) return;

      var error = input.form ? input.form.querySelector("[data-editor-error]") : null;

      function sync() {
        input.value = surface.innerHTML;
      }

      function isEmpty() {
        return surface.textContent.replace(/\s+/g, "") === ""
          && !surface.querySelector("img, hr, table");
      }

      function hideError() {
        if (error && !error.hidden) error.hidden = true;
      }

      function exec(command, value) {
        surface.focus();
        document.execCommand(command, false, value || null);
        sync();
      }

      /* Build the visual surface from the stored value, then swap it in. */
      surface.innerHTML = input.value;

      /* Collapse empty markup (e.g. "<p></p>") so the placeholder can show. */
      if (isEmpty()) surface.innerHTML = "";

      surface.hidden = false;
      input.hidden = true;
      input.required = false;

      /* Chrome inserts <div> on Enter; prefer <p> so the cleaner keeps spacing. */
      try {
        document.execCommand("defaultParagraphSeparator", false, "p");
      } catch (ignored) {
        /* Older browsers ignore this command. */
      }

      toolbar.querySelectorAll("button[data-editor-command]").forEach(function (button) {
        /* Keep the text selection when the toolbar button takes focus. */
        button.addEventListener("mousedown", function (event) {
          event.preventDefault();
        });

        button.addEventListener("click", function () {
          var command = button.getAttribute("data-editor-command") || "";
          var value = button.getAttribute("data-editor-value") || "";

          if (command === "createLink") {
            var url = window.prompt("Link URL", "https://");
            if (url) exec("createLink", url);
            return;
          }

          if (command === "insertImage") {
            var image = window.prompt("Image URL", "https://");
            if (image) exec("insertImage", image);
            return;
          }

          exec(command, command === "formatBlock" && value ? "<" + value + ">" : value);
        });
      });

      surface.addEventListener("input", function () {
        sync();
        if (!isEmpty()) hideError();
      });

      if (input.form) {
        input.form.addEventListener("submit", function (event) {
          sync();

          if (isEmpty()) {
            event.preventDefault();
            event.stopImmediatePropagation();
            if (error) error.hidden = false;
            surface.focus();
          }
        });
      }
    }

    document.querySelectorAll("[data-editor-toolbar]").forEach(function (toolbar) {
      var target = document.getElementById(toolbar.getAttribute("data-target") || "");

      if (!target) return;

      if (toolbar.hasAttribute("data-editor-rich")) {
        setupRichEditor(toolbar, target);
        return;
      }

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
    var confirmBody = document.createElement("div");
    confirmBody.className = "app-confirm__body";
    var confirmIconPaths = {
      logout: '<path d="M15 4h3a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-3"/><path d="M10 8l-4 4 4 4"/><path d="M6 12h9"/>',
      trash: '<path d="M4 7h16"/><path d="M9 7V4.8h6V7"/><path d="M6.5 7l1 13h9l1-13"/><path d="M10.5 11v5M13.5 11v5"/>',
      archive: '<path d="M3 4h18v4H3z"/><path d="M5 8v12h14V8"/><path d="M10 12h4"/>',
      checkCircle: '<circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.8 2.8L16 9.5"/>',
      alert: '<path d="M12 4 2.8 20h18.4L12 4Z"/><path d="M12 10v4.5M12 17.4v.2"/>',
      shield: '<path d="M12 3l7 3v6c0 4.4-3 7.7-7 9-4-1.3-7-4.6-7-9V6l7-3Z"/><path d="M9 12l2 2 4-4"/>',
      check: '<path d="M4.5 12.5 9.5 17.5 19.5 6.5"/>',
      send: '<path d="M4 12 20 4l-6.5 16-3-6.5L4 12Z"/>',
      info: '<circle cx="12" cy="12" r="9"/><path d="M12 11v5.5M12 7.8v.2"/>'
    };

    function confirmIconSvg(name) {
      return '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        + (confirmIconPaths[name] || confirmIconPaths.shield) + '</svg>';
    }

    /* Pick an icon, tone and heading that match the action being confirmed. */
    function describeConfirmation(message, actionUrl) {
      var text = ((message || '') + ' ' + (actionUrl || '')).toLowerCase();

      if (/sign\s*out|log\s*out|logout/.test(text)) {
        return { tone: 'session', icon: 'logout', kicker: 'Session security', title: 'End your session' };
      }
      if (/delete|remove|purge/.test(text)) {
        return { tone: 'danger', icon: 'trash', kicker: 'Destructive action', title: 'Delete permanently' };
      }
      if (/archive/.test(text)) {
        return { tone: 'danger', icon: 'archive', kicker: 'Destructive action', title: 'Archive this record' };
      }
      if (/reject|decline|deny|deactivate|block/.test(text)) {
        return { tone: 'danger', icon: 'alert', kicker: 'Destructive action', title: 'Confirm this change' };
      }
      if (/verify|approve|activate|publish|restore|resolve|release|returned/.test(text)) {
        return { tone: 'success', icon: 'checkCircle', kicker: 'Verification', title: 'Confirm this action' };
      }
      if (/save|update|edit/.test(text)) {
        return { tone: 'brand', icon: 'check', kicker: 'Please confirm', title: 'Save your changes' };
      }
      if (/submit|send|post|notify|claim/.test(text)) {
        return { tone: 'brand', icon: 'send', kicker: 'Please confirm', title: 'Confirm this submission' };
      }
      return { tone: 'brand', icon: 'shield', kicker: 'Please confirm', title: 'Please confirm' };
    }

    var confirmIcon = document.createElement("span");
    confirmIcon.className = "app-confirm__icon";
    confirmIcon.setAttribute("aria-hidden", "true");
    var confirmKicker = document.createElement("span");
    confirmKicker.className = "app-confirm__kicker";
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
    confirmBody.appendChild(confirmIcon);
    confirmBody.appendChild(confirmKicker);
    confirmBody.appendChild(confirmTitle);
    confirmBody.appendChild(confirmMessage);
    confirmCard.appendChild(confirmBody);
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
        var spec = describeConfirmation(message, form.getAttribute("action") || "");
        confirmMessage.textContent = message;
        confirmTitle.textContent = submitter && submitter.getAttribute("data-confirm-title")
          ? submitter.getAttribute("data-confirm-title")
          : spec.title;
        confirmKicker.textContent = spec.kicker;
        confirmIcon.innerHTML = confirmIconSvg(spec.icon);
        var actionLabel = submitter ? submitter.textContent.trim() : "";
        confirmAccept.textContent = actionLabel && actionLabel.length <= 24 ? actionLabel : "Confirm";

        ["brand", "session", "danger", "success", "warning"].forEach(function (tone) {
          confirmDialog.classList.remove("app-confirm--" + tone);
        });
        confirmDialog.classList.add("app-confirm--" + spec.tone);
        confirmAccept.classList.remove("btn--primary", "btn--danger", "btn--success");
        confirmAccept.classList.add(spec.tone === "danger" ? "btn--danger" : (spec.tone === "success" ? "btn--success" : "btn--primary"));

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

    /* Close the account menu or notification popover when clicking outside it */
    document.addEventListener("click", function (event) {
      document.querySelectorAll("details.account[open], details.notif-menu[open]").forEach(function (details) {
        if (!details.contains(event.target)) details.removeAttribute("open");
      });

      /* Print buttons (replaces inline onclick so a strict CSP can be used) */
      if (event.target.closest("[data-print-button]")) {
        window.print();
      }
    });

    /* Keep derived access fields in sync with the selected role. */
    var roleSelect = document.querySelector("[data-role-select]");

    if (roleSelect) {
      var accessLevel = document.querySelector("[data-access-level]");
      var studentFields = document.querySelector("[data-student-fields]");
      var studentIdField = document.querySelector("[data-student-id-field]");
      var studentIdInput = document.getElementById("student_id");
      var studentAgeInput = document.getElementById("age");
      var coordinatorAgeField = document.querySelector("[data-coordinator-age-field]");
      var coordinatorAgeInput = document.getElementById("coordinator_age");
      var schoolIdSection = document.querySelector("[data-school-id-section]");
      var schoolIdInput = document.getElementById("school_id");
      var accessLevels = {
        Student: "Student — Portal Access",
        Administrator: "Administrator — Technical Support Access",
        Coordinator: "Coordinator — Coordinator Portal Access"
      };

      function syncRole() {
        var role = roleSelect.value;
        var isStudent = role === "Student";
        var isCoordinator = role === "Coordinator";

        if (accessLevel) accessLevel.value = accessLevels[role] || "";
        if (studentFields) studentFields.hidden = !isStudent;
        if (studentFields) {
          studentFields.querySelectorAll("input, select, textarea").forEach(function (field) {
            field.disabled = !isStudent;
          });
        }
        if (studentIdField) studentIdField.hidden = !isStudent;
        if (studentIdInput) studentIdInput.disabled = !isStudent;
        if (studentAgeInput) studentAgeInput.disabled = !isStudent;
        if (coordinatorAgeField) coordinatorAgeField.hidden = !isCoordinator;
        if (coordinatorAgeInput) coordinatorAgeInput.disabled = !isCoordinator;
        if (schoolIdSection) schoolIdSection.hidden = !isStudent;
        if (schoolIdInput) schoolIdInput.disabled = !isStudent;
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

    /* Student dashboard infographic rotation — advances every 5 seconds and
     * pauses on hover/focus. Reduced-motion systems still rotate; the CSS
     * swaps the slide instantly instead of sliding it. The dots below the
     * picture jump back/forward on click, like the login portal's dots. */
    var ingoCarousel = document.querySelector("[data-ingo-carousel]");

    if (ingoCarousel) {
      var ingoTrack = ingoCarousel.querySelector("[data-ingo-track]");
      var ingoSlides = ingoTrack ? ingoTrack.children.length : 0;
      var ingoIndex = 0;
      var ingoTimer = null;
      var ingoDots = ingoCarousel.querySelectorAll("[data-ingo-dot]");

      var ingoGo = function (next) {
        ingoIndex = ((next % ingoSlides) + ingoSlides) % ingoSlides;
        ingoTrack.style.transform = "translateX(-" + ingoIndex * 100 + "%)";
        ingoDots.forEach(function (dot, i) {
          dot.classList.toggle("pdot--on", i === ingoIndex);
        });
      };

      var ingoAdvance = function () {
        ingoGo(ingoIndex + 1);
      };

      var ingoStart = function () {
        if (ingoTimer === null) {
          ingoTimer = setInterval(ingoAdvance, 5000);
        }
      };

      var ingoStop = function () {
        if (ingoTimer !== null) {
          clearInterval(ingoTimer);
          ingoTimer = null;
        }
      };

      if (ingoTrack && ingoSlides > 1) {
        ingoCarousel.addEventListener("mouseenter", ingoStop);
        ingoCarousel.addEventListener("mouseleave", ingoStart);
        ingoCarousel.addEventListener("focusin", ingoStop);
        ingoCarousel.addEventListener("focusout", ingoStart);
        ingoDots.forEach(function (dot, i) {
          dot.addEventListener("click", function () {
            ingoGo(i);
            /* restart the 5s countdown after a manual jump — but only when
             * the rotation is running (not paused by hover/focus) */
            if (ingoTimer !== null) {
              ingoStop();
              ingoStart();
            }
          });
        });
        ingoStart();
      }
    }

    /* Toast notification helper */
    function showToast(message, isError) {
      var root = document.getElementById("toast-root");
      if (!root) return;

      var toast = document.createElement("div");
      toast.className = "auth-signout-toast" + (isError ? " auth-signout-toast--error" : "");
      toast.setAttribute("role", isError ? "alert" : "status");

      var text = document.createElement("span");
      text.textContent = message;

      var close = document.createElement("button");
      close.type = "button";
      close.className = "auth-signout-toast__close";
      close.innerHTML = "&times;";
      close.setAttribute("aria-label", "Dismiss notification");
      close.addEventListener("click", function () { toast.remove(); });

      toast.appendChild(text);
      toast.appendChild(close);
      root.appendChild(toast);

      window.setTimeout(function () {
        if (toast.isConnected) toast.remove();
      }, 5000);
    }

    /* Client-side File Upload Size Pre-check (Max 10 MB) */
    document.querySelectorAll('input[type="file"]').forEach(function (input) {
      input.addEventListener("change", function () {
        var file = input.files && input.files.length ? input.files[0] : null;
        if (!file) return;

        var maxBytes = 10 * 1024 * 1024; // 10MB
        if (file.size > maxBytes) {
          var sizeMb = (file.size / (1024 * 1024)).toFixed(1);
          input.value = "";
          updateUploadLabel(input);
          showToast("File is too large (" + sizeMb + " MB). Maximum allowed size is 10 MB.", true);
        }
      });
    });

    /* Prevent Accidental Double-Submits on POST forms */
    document.addEventListener("submit", function (event) {
      var form = event.target;
      if (!form || form.tagName !== "FORM") return;
      if ((form.method || "GET").toUpperCase() !== "POST") return;
      if (event.defaultPrevented) return;
      if (form.dataset.submitting === "true") {
        event.preventDefault();
        return;
      }

      // Check HTML5 validity before locking button
      if (typeof form.checkValidity === "function" && !form.checkValidity()) {
        return;
      }

      var submitter = event.submitter || form.querySelector('button[type="submit"], input[type="submit"]');
      if (!submitter) return;

      setTimeout(function () {
        form.dataset.submitting = "true";
        submitter.disabled = true;
        submitter.classList.add("is-submitting");
        var spinner = document.createElement("span");
        spinner.className = "btn-spinner";
        spinner.setAttribute("aria-hidden", "true");
        submitter.prepend(spinner);
      }, 20);
    }, true);

    window.addEventListener("pageshow", function () {
      document.querySelectorAll("form[data-submitting]").forEach(function (form) {
        delete form.dataset.submitting;
        form.querySelectorAll(".is-submitting").forEach(function (btn) {
          btn.disabled = false;
          btn.classList.remove("is-submitting");
          var spinner = btn.querySelector(".btn-spinner");
          if (spinner) spinner.remove();
        });
      });
    });

    /* Quick-Jump Command Palette (Ctrl + K) */
    var cmdPalette = document.getElementById("cmd-palette");
    var cmdInput = document.getElementById("cmd-palette-input");
    var cmdList = document.getElementById("cmd-palette-list");
    var cmdTrigger = document.getElementById("cmd-palette-trigger");
    var cmdEmpty = document.getElementById("cmd-palette-empty");

    if (cmdPalette && cmdInput && cmdList) {
      var cmdItems = Array.prototype.slice.call(cmdList.querySelectorAll(".cmd-palette__item"));
      var selectedIndex = -1;

      function getVisibleItems() {
        return cmdItems.filter(function (item) {
          return !item.hidden;
        });
      }

      function selectIndex(index) {
        var visible = getVisibleItems();
        if (visible.length === 0) {
          selectedIndex = -1;
          return;
        }
        if (index < 0) index = 0;
        if (index >= visible.length) index = visible.length - 1;
        selectedIndex = index;

        cmdItems.forEach(function (item) {
          item.classList.remove("is-selected");
        });
        visible[selectedIndex].classList.add("is-selected");
        visible[selectedIndex].scrollIntoView({ block: "nearest" });
      }

      function openPalette() {
        cmdPalette.classList.add("cmd-palette--open");
        cmdInput.value = "";
        filterPalette("");
        cmdInput.focus();
        selectIndex(0);
      }

      function closePalette() {
        cmdPalette.classList.remove("cmd-palette--open");
        if (cmdTrigger) cmdTrigger.focus();
      }

      function filterPalette(query) {
        var term = query.toLowerCase().trim();
        var matchCount = 0;

        cmdItems.forEach(function (item) {
          var title = item.getAttribute("data-cmd-title") || "";
          var badge = item.getAttribute("data-cmd-badge") || "";
          var match = !term || title.indexOf(term) !== -1 || badge.indexOf(term) !== -1;
          item.hidden = !match;
          if (match) matchCount++;
        });

        if (cmdEmpty) cmdEmpty.hidden = matchCount > 0;
        selectIndex(0);
      }

      if (cmdTrigger) {
        cmdTrigger.addEventListener("click", openPalette);
      }

      cmdPalette.addEventListener("click", function (event) {
        if (event.target === cmdPalette) closePalette();
      });

      cmdInput.addEventListener("input", function () {
        filterPalette(cmdInput.value);
      });

      cmdInput.addEventListener("keydown", function (event) {
        var visible = getVisibleItems();

        if (event.key === "Escape") {
          event.preventDefault();
          closePalette();
        } else if (event.key === "ArrowDown") {
          event.preventDefault();
          selectIndex(selectedIndex + 1);
        } else if (event.key === "ArrowUp") {
          event.preventDefault();
          selectIndex(selectedIndex - 1);
        } else if (event.key === "Enter") {
          event.preventDefault();
          if (selectedIndex >= 0 && visible[selectedIndex]) {
            window.location.href = visible[selectedIndex].href;
          }
        }
      });

      // Global shortcut: Ctrl+K or Cmd+K
      document.addEventListener("keydown", function (event) {
        var isK = event.key === "k" || event.key === "K";
        if ((event.ctrlKey || event.metaKey) && isK) {
          event.preventDefault();
          if (cmdPalette.classList.contains("cmd-palette--open")) {
            closePalette();
          } else {
            openPalette();
          }
        } else if (event.key === "Escape" && cmdPalette.classList.contains("cmd-palette--open")) {
          closePalette();
        }
      });
    }

    /* Inactivity Session Warning for Shared Campus Computers */
    var portal = document.body.getAttribute("data-portal");
    if (portal === "student" || portal === "admin") {
      var IDLE_TIMEOUT = 15 * 60 * 1000; // 15 minutes
      var COUNTDOWN_TOTAL = 120; // 2 minutes warning
      var idleTimer = null;
      var countdownTimer = null;
      var remainingSeconds = COUNTDOWN_TOTAL;

      var inactivityDialog = document.createElement("dialog");
      inactivityDialog.className = "inactivity-dialog";
      inactivityDialog.setAttribute("aria-labelledby", "inactivity-title");
      inactivityDialog.innerHTML =
        '<div class="inactivity-dialog__card">' +
          '<div class="inactivity-dialog__icon">' +
            '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>' +
          '</div>' +
          '<h2 class="inactivity-dialog__title" id="inactivity-title">Inactivity Warning</h2>' +
          '<p class="inactivity-dialog__message">For your security on shared campus computers, your portal session will sign out in <strong id="inactivity-countdown">' + COUNTDOWN_TOTAL + '</strong> seconds.</p>' +
          '<div class="inactivity-dialog__actions">' +
            '<button type="button" class="btn btn--ghost" id="inactivity-signout">Sign Out</button>' +
            '<button type="button" class="btn btn--primary" id="inactivity-extend">Stay Signed In</button>' +
          '</div>' +
        '</div>';
      document.body.appendChild(inactivityDialog);

      var countdownEl = inactivityDialog.querySelector("#inactivity-countdown");
      var extendBtn = inactivityDialog.querySelector("#inactivity-extend");
      var signoutBtn = inactivityDialog.querySelector("#inactivity-signout");

      function performSignOut() {
        var logoutForm = document.querySelector('form[action$="/logout"]');
        if (logoutForm) {
          logoutForm.submit();
        } else {
          window.location.href = "/logout";
        }
      }

      function showWarning() {
        remainingSeconds = COUNTDOWN_TOTAL;
        if (countdownEl) countdownEl.textContent = String(remainingSeconds);
        if (typeof inactivityDialog.showModal === "function") {
          inactivityDialog.showModal();
        } else {
          inactivityDialog.setAttribute("open", "");
        }
        if (extendBtn) extendBtn.focus();

        countdownTimer = setInterval(function () {
          remainingSeconds--;
          if (countdownEl) countdownEl.textContent = String(remainingSeconds);
          if (remainingSeconds <= 0) {
            clearInterval(countdownTimer);
            performSignOut();
          }
        }, 1000);
      }

      function resetIdle() {
        clearTimeout(idleTimer);
        clearInterval(countdownTimer);
        if (inactivityDialog.open) {
          if (typeof inactivityDialog.close === "function") {
            inactivityDialog.close();
          } else {
            inactivityDialog.removeAttribute("open");
          }
        }
        idleTimer = setTimeout(showWarning, IDLE_TIMEOUT);
      }

      if (extendBtn) {
        extendBtn.addEventListener("click", resetIdle);
      }
      if (signoutBtn) {
        signoutBtn.addEventListener("click", performSignOut);
      }

      var activityDebounce = null;
      ["mousemove", "mousedown", "keydown", "touchstart", "scroll"].forEach(function (evt) {
        window.addEventListener(evt, function () {
          if (inactivityDialog.open) return;
          if (activityDebounce) return;
          activityDebounce = setTimeout(function () {
            activityDebounce = null;
            resetIdle();
          }, 1000);
        }, { passive: true });
      });

      resetIdle();
    }
  });

  /* Dropdown menus, rendered inside the page.
   *
   * A native <select> popup is drawn by the browser: it ignores page CSS and
   * is not clamped to a small viewport, so on phones (and in device
   * emulation) the option list spills past the edge of the screen and is
   * hard to read. This renders the same options as an in-page list anchored
   * to the control and capped to the viewport. The real <select> stays the
   * form control — picking an option sets its value and fires a normal
   * bubbling change event, so filters keep auto-submitting and every form
   * keeps working. Without JavaScript the native select is unchanged. */
  onReady(function () {
    var controls = Array.prototype.slice.call(document.querySelectorAll("select.input"));

    if (controls.length === 0) {
      return;
    }

    var menu = document.createElement("div");
    var current = null;
    var activeIndex = -1;
    menu.className = "selectmenu";
    menu.setAttribute("role", "listbox");
    menu.hidden = true;
    document.body.appendChild(menu);

    var usable = function (select) {
      return select && !select.disabled && !select.multiple && select.offsetParent !== null;
    };

    var close = function () {
      if (current) {
        current.removeAttribute("data-selectmenu-open");
        current.removeAttribute("aria-expanded");
      }
      current = null;
      activeIndex = -1;
      menu.hidden = true;
      menu.innerHTML = "";
    };

    var highlight = function (index) {
      var items = menu.children;

      if (items.length === 0) {
        return;
      }

      activeIndex = (index + items.length) % items.length;

      for (var i = 0; i < items.length; i++) {
        items[i].classList.toggle("selectmenu__opt--active", i === activeIndex);
        items[i].setAttribute("aria-selected", i === activeIndex ? "true" : "false");
      }

      if (items[activeIndex].scrollIntoView) {
        items[activeIndex].scrollIntoView({ block: "nearest" });
      }
    };

    var choose = function (option) {
      var select = current;

      if (!select || !option) {
        return;
      }

      select.value = option.value;
      select.dispatchEvent(new Event("change", { bubbles: true }));
      close();
      select.focus({ preventScroll: true });
    };

    var position = function (select) {
      var rect = select.getBoundingClientRect();
      var width = Math.min(rect.width, window.innerWidth - 16);
      var maxHeight = Math.max(120, Math.round(window.innerHeight * 0.6));

      menu.style.width = Math.round(width) + "px";
      menu.style.left = Math.round(Math.max(8, Math.min(rect.left, window.innerWidth - width - 8))) + "px";
      menu.style.maxHeight = maxHeight + "px";

      var height = Math.min(menu.scrollHeight, maxHeight);
      var below = window.innerHeight - rect.bottom;

      if (below < height + 12 && rect.top > below) {
        menu.style.top = Math.round(Math.max(8, rect.top - height - 6)) + "px";
      } else {
        menu.style.top = Math.round(rect.bottom + 4) + "px";
      }
    };

    var open = function (select) {
      if (!usable(select)) {
        return;
      }

      if (current === select) {
        close();
        return;
      }

      close();
      current = select;
      activeIndex = -1;

      Array.prototype.slice.call(select.options).forEach(function (option, index) {
        var item = document.createElement("button");
        item.type = "button";
        item.className = "selectmenu__opt" + (option.selected ? " selectmenu__opt--on" : "");
        item.setAttribute("role", "option");
        item.setAttribute("aria-selected", option.selected ? "true" : "false");
        item.textContent = option.textContent;
        item.addEventListener("click", function () {
          choose(option);
        });
        menu.appendChild(item);

        if (option.selected) {
          activeIndex = index;
        }
      });

      menu.hidden = false;
      select.setAttribute("data-selectmenu-open", "");
      select.setAttribute("aria-expanded", "true");
      position(select);
      highlight(activeIndex < 0 ? 0 : activeIndex);
    };

    controls.forEach(function (select) {
      select.addEventListener("mousedown", function (event) {
        if (!usable(select)) {
          return;
        }

        event.preventDefault();
        select.focus({ preventScroll: true });
        open(select);
      });

      select.addEventListener("touchstart", function (event) {
        if (!usable(select)) {
          return;
        }

        event.preventDefault();
        open(select);
      }, { passive: false });

      select.addEventListener("keydown", function (event) {
        var key = event.key;

        if (key === "Escape") {
          if (current === select) {
            event.preventDefault();
            close();
          }
          return;
        }

        if (key === "ArrowDown" || key === "ArrowUp") {
          event.preventDefault();

          if (current !== select) {
            open(select);
            return;
          }

          highlight(activeIndex + (key === "ArrowDown" ? 1 : -1));
          return;
        }

        if (key === "Enter" || key === " " || key === "Spacebar") {
          event.preventDefault();

          if (current === select && activeIndex >= 0) {
            choose(select.options[activeIndex]);
          } else if (current !== select) {
            open(select);
          }
        }
      });
    });

    document.addEventListener("mousedown", function (event) {
      if (!current || event.target === current || menu.contains(event.target)) {
        return;
      }

      close();
    }, true);

    document.addEventListener("touchstart", function (event) {
      if (!current || event.target === current || menu.contains(event.target)) {
        return;
      }

      close();
    }, true);

    document.addEventListener("focusin", function (event) {
      if (!current || event.target === current || menu.contains(event.target)) {
        return;
      }

      close();
    });

    window.addEventListener("resize", function () {
      if (current) {
        close();
      }
    });

    window.addEventListener("scroll", function () {
      if (current) {
        close();
      }
    }, true);
  });
})();
