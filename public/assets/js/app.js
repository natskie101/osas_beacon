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
    var confirmBody = document.createElement("div");
    confirmBody.className = "app-confirm__body";
    var confirmIcon = document.createElement("span");
    confirmIcon.className = "app-confirm__icon";
    confirmIcon.setAttribute("aria-hidden", "true");
    confirmIcon.innerHTML = '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4 2.8 20h18.4L12 4Z"/><path d="M12 10v4.5M12 17.4v.2"/></svg>';
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
        confirmMessage.textContent = message;
        confirmTitle.textContent = submitter && submitter.getAttribute("data-confirm-title")
          ? submitter.getAttribute("data-confirm-title")
          : "Please confirm";
        var actionLabel = submitter ? submitter.textContent.trim() : "";
        var isDangerous = /delete|remove|deactivate/i.test(message);
        confirmAccept.textContent = actionLabel && actionLabel.length <= 24 ? actionLabel : "Confirm";
        confirmDialog.classList.toggle("app-confirm--danger", isDangerous);
        confirmAccept.classList.toggle("btn--danger", isDangerous);
        confirmAccept.classList.toggle("btn--primary", !isDangerous);

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
