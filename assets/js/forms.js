/* ==========================================================================
   BEACON OSAS Portal — shared modal forms
   Used by more than one module (login + User Management, dashboard + Support).
   ========================================================================== */
OSAS.forms = (function () {
  function C() { return OSAS.components; }
  function U() { return OSAS.util; }

  function field(label, controlHtml, options) {
    var opts = options || {};
    return '<div class="field">' +
      (label ? '<label for="' + (opts.id || '') + '">' + label +
        (opts.required ? '<span class="req">*</span>' : '') + '</label>' : '') +
      controlHtml +
      (opts.hint ? '<div class="hint">' + opts.hint + '</div>' : '') + '</div>';
  }

  function textInput(id, inputValue, placeholder, type) {
    return '<input class="input" id="' + id + '" name="' + id + '" type="' + (type || 'text') +
      '" value="' + U().esc(inputValue || '') + '" placeholder="' + U().esc(placeholder || '') + '">';
  }

  function passwordInput(id, placeholder) {
    return '<span class="input-group input-group--action">' +
      '<span class="input-group__ico">' + OSAS.icons.icon('lock', 14) + '</span>' +
      '<input class="input" id="' + id + '" name="' + id + '" type="password" placeholder="' +
      U().esc(placeholder) + '" autocomplete="new-password">' +
      '<button type="button" class="input-group__btn" data-toggle="' + id + '" title="Show password">' +
      OSAS.icons.icon('eye', 14) + '</button></span>';
  }

  function selectInput(id, values, selected, placeholder) {
    var options = ['<option value="">' + U().esc(placeholder || 'Select an option') + '</option>'];
    values.forEach(function (option) {
      options.push('<option value="' + U().esc(option) + '"' +
        (option === selected ? ' selected' : '') + '>' + U().esc(option) + '</option>');
    });
    return '<select class="input" id="' + id + '" name="' + id + '">' + options.join('') + '</select>';
  }

  function bindPasswordToggles(scope) {
    U().qsa('[data-toggle]', scope).forEach(function (button) {
      button.addEventListener('click', function () {
        var input = document.getElementById(button.getAttribute('data-toggle'));
        if (!input) { return; }
        var showing = input.type === 'password';
        input.type = showing ? 'text' : 'password';
        button.innerHTML = OSAS.icons.icon(showing ? 'eyeOff' : 'eye', 14);
      });
    });
  }

  function value(id) {
    var el = document.getElementById(id);
    return el ? String(el.value || '').trim() : '';
  }

  function markInvalid(id, invalid) {
    var el = document.getElementById(id);
    if (!el) { return; }
    el.style.borderColor = invalid ? 'var(--red)' : '';
    el.style.boxShadow = invalid ? '0 0 0 3px rgba(179,38,30,.10)' : '';
  }

  function validEmail(email) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email); }
  /* ---------------------------------------------------- Add / Edit User
     Frames 91 / 72 / 73 — including the success "Account Registered" state. */
  function userForm(options) {
    var opts = options || {};
    var editing = !!opts.user;
    var user = opts.user || {};
    var meta = OSAS.store.meta();
    var optionList = OSAS.store.options();

    var body =
      '<div id="user-form-alert"></div>' +
      '<div class="form-section">' +
      '<div class="section-title"><span>1.</span> Account Identity</div>' +
      '<div class="form-grid form-grid--2">' +
      field('First Name', textInput('uf-first', user.firstName, 'Juan'), { id: 'uf-first', required: true }) +
      field('Last Name', textInput('uf-last', user.lastName, 'Dela Cruz'), { id: 'uf-last', required: true }) +
      '</div>' +
      field('Email Address', textInput('uf-email', user.email, 'juandelacruz@uc.edu.ph', 'email'),
        { id: 'uf-email', required: true, hint: 'Must be an official @uc.edu.ph university email address.' }) +
      '<div class="form-grid form-grid--2">' +
      field('UC Student ID', textInput('uf-student-id', user.studentId, '26-00000'),
        { id: 'uf-student-id', hint: 'Leave blank for faculty, staff and administrator accounts.' }) +
      field('User Role', selectInput('uf-role', optionList.roles, user.role, 'Select a role'),
        { id: 'uf-role', required: true }) +
      '</div></div>' +
      '<div class="form-section">' +
      '<div class="section-title"><span>2.</span> Department &amp; Access Level</div>' +
      '<div class="form-grid form-grid--2">' +
      field('Department / Office', selectInput('uf-department', optionList.departments, user.department, 'Select a department'), { id: 'uf-department' }) +
      field('Program (students only)', selectInput('uf-program', optionList.programs, user.program, 'Select a program'), { id: 'uf-program' }) +
      '</div>' +
      '<div class="form-grid form-grid--2">' +
      field('Access Level', selectInput('uf-access', optionList.accessLevels, user.accessLevel, 'Select an access level'), { id: 'uf-access' }) +
      field('Account Status', selectInput('uf-status', ['Active', 'Pending', 'Inactive'], user.status || 'Active', 'Select status'), { id: 'uf-status' }) +
      '</div>' +
      field('Mobile Number (optional)', textInput('uf-phone', user.phone, '+63 9XX XXX XXXX'), { id: 'uf-phone' }) +
      '</div>' +
      '<div class="form-section">' +
      '<div class="section-title"><span>3.</span> School ID Verification</div>' +
      '<div class="form-grid form-grid--2">' +
      field('Upload School ID Picture',
        '<div class="id-upload" id="uf-id-upload">' +
        '<input type="file" id="uf-id-file" accept="image/png,image/jpeg,image/jpg,image/webp" style="display:none">' +
        '<div class="id-upload__preview" id="uf-id-preview">' +
        '<span class="id-upload__placeholder">' + OSAS.icons.icon('upload', 28) + '<br>Click to upload school ID</span>' +
        '</div>' +
        '<div class="id-upload__info" id="uf-id-info"></div>' +
        '</div>',
        { id: 'uf-id-file', hint: 'Upload a clear photo or scan of your school ID card (PNG, JPG, WEBP). Max 5 MB.' }) +
      '</div></div>' +
      '<div class="form-section">' +
      '<div class="section-title"><span>4.</span> Portal Credentials</div>' +
      '<div class="form-grid form-grid--2">' +
      field('Password', passwordInput('uf-password', 'Minimum 10 characters'),
        { id: 'uf-password', required: !editing }) +
      field('Confirm Password', passwordInput('uf-confirm', 'Re-type the password'),
        { id: 'uf-confirm', required: !editing }) +
      '</div>' +
      (editing ? C().alert('info', 'Leave the password fields blank to keep the current credentials.',
        'Resetting a password will invalidate the active session of ' + U().esc(user.email) + '.') : '') +
      '</div>';
    return C().modal({
      size: 'lg',
      title: editing ? 'Edit User Account' : 'Add New User',
      subtitle: editing
        ? 'Update the portal identity, role assignment and access level of this account.'
        : 'Create a university portal account for students, staff, or administrators.',
      body: body,
      footer: '<button type="button" class="btn btn--ghost" data-close>Cancel</button>' +
        '<button type="button" class="btn btn--primary" id="user-form-save">' +
        (editing ? 'Save Changes' : 'Create Account') + '</button>',
      onMount: function (el) {
        bindPasswordToggles(el);
        var alertHost = el.querySelector('#user-form-alert');

        // --- School ID upload handling ---
        var idFileInput = el.querySelector('#uf-id-file');
        var idPreview = el.querySelector('#uf-id-preview');
        var idInfo = el.querySelector('#uf-id-info');
        var schoolIdImage = user.schoolIdImage || '';

        function renderIdPreview() {
          if (schoolIdImage) {
            idPreview.innerHTML = '<img src="' + schoolIdImage + '" alt="School ID" style="max-width:100%;max-height:180px;border-radius:6px;border:1px solid var(--line)">';
            idInfo.innerHTML = '<span class="hint" style="color:var(--green)">School ID image attached</span>';
          } else {
            idPreview.innerHTML = '<span class="id-upload__placeholder">' + OSAS.icons.icon('upload', 28) + '<br>Click to upload school ID</span>';
            idInfo.innerHTML = '';
          }
        }
        renderIdPreview();

        idPreview.addEventListener('click', function () { idFileInput.click(); });
        idFileInput.addEventListener('change', function () {
          var file = idFileInput.files[0];
          if (!file) { return; }
          if (!/^image\/(png|jpeg|jpg|webp)$/.test(file.type)) {
            alertHost.innerHTML = C().alert('danger', 'Invalid file type', 'Please upload a PNG, JPG, or WEBP image.');
            return;
          }
          if (file.size > 5 * 1024 * 1024) {
            alertHost.innerHTML = C().alert('danger', 'File too large', 'The image must be 5 MB or smaller.');
            return;
          }
          var reader = new FileReader();
          reader.onload = function (e) {
            schoolIdImage = e.target.result;
            renderIdPreview();
          };
          reader.readAsDataURL(file);
        });

        function succeed(record) {
          var message = editing ? 'Account Updated Successfully!' : 'Account Registered Successfully!';
          alertHost.innerHTML = C().alert('success', message,
            (editing ? 'Portal details saved for ' : 'Portal credentials created for ') +
            '<b>' + U().esc(record.email) + '</b>' +
            (editing ? '' : ' — temporary password <b>' + meta.demoPassword + '</b>.'));
          el.querySelector('.modal__foot').innerHTML =
            '<button type="button" class="btn btn--primary" data-close>Done</button>';
          OSAS.store.logActivity({
            tone: 'brand', icon: 'users',
            title: editing ? 'User Account Updated' : 'New User Account Created',
            text: record.firstName + ' ' + record.lastName + ' (' + record.role + ') — ' + record.email
          });
          C().toast('success', message, record.email);
          if (typeof opts.onSaved === 'function') { opts.onSaved(record); }
        }

        el.querySelector('#user-form-save').addEventListener('click', function () {
          var first = value('uf-first'), last = value('uf-last'), email = value('uf-email');
          var role = value('uf-role');
          var password = value('uf-password'), confirmPassword = value('uf-confirm');
          var errors = [];

          markInvalid('uf-first', !first);
          markInvalid('uf-last', !last);
          markInvalid('uf-email', !email || !validEmail(email));
          markInvalid('uf-role', !role);

          if (!first || !last) { errors.push('First name and last name are required.'); }
          if (!email || !validEmail(email)) { errors.push('A valid university email address is required.'); }
          if (!role) { errors.push('Select the portal role for this account.'); }
          if (!editing && !password) { errors.push('A temporary password is required.'); }
          if (password && password.length < 10) { errors.push('The password must be at least 10 characters long.'); }
          if (password !== confirmPassword) { errors.push('The password confirmation does not match.'); }

          var duplicate = OSAS.store.all('users').filter(function (row) {
            return row.email.toLowerCase() === email.toLowerCase() && row.id !== user.id;
          }).length;
          if (duplicate) { errors.push('Another portal account already uses this email address.'); }

          if (errors.length) {
            alertHost.innerHTML = C().alert('danger', 'Unable to save the account', errors.join(' '));
            return;
          }

          var payload = {
            firstName: first, lastName: last, email: email, username: email.split('@')[0],
            role: role, studentId: value('uf-student-id'), department: value('uf-department'),
            program: value('uf-program'), accessLevel: value('uf-access'),
            status: value('uf-status') || 'Active', phone: value('uf-phone'),
            schoolIdImage: schoolIdImage
          };

          var record;
          if (editing) {
            record = OSAS.store.update('users', user.id, payload);
          } else {
            payload.id = OSAS.store.nextId('users', 'USR', 4);
            payload.createdAt = U().now();
            payload.lastLogin = '';
            record = OSAS.store.insert('users', payload);
          }
          succeed(record);
        });
      }
    });
  }
  /* ------------------------------------------------- Ticket detail / reply
     Frame 85 — thread, reply composer and status controls. */
  function ticketView(options) {
    var ticket = options.ticket;
    var session = OSAS.auth.current();
    var staffName = session ? session.user.firstName + ' ' + session.user.lastName : 'OSAS Support';

    function details() {
      var t = ticket;
      return C().statStrip([
        { label: 'Current Status', value: C().badge(t.status) },
        { label: 'Priority Level', value: C().priority(t.priority) },
        { label: 'Submitted', value: OSAS.fmt.shortDate(t.submittedAt) },
        { label: 'Last Activity', value: OSAS.fmt.shortDate(t.updatedAt) }
      ]) +
        '<dl class="kv">' +
        '<dt>Student Name</dt><dd>' + U().esc(t.student) + '</dd>' +
        '<dt>UC Student ID</dt><dd>' + U().esc(t.studentId || '—') + '</dd>' +
        '<dt>Program</dt><dd>' + U().esc(t.program || '—') + '</dd>' +
        '<dt>University Email</dt><dd>' + U().esc(t.studentEmail) + '</dd>' +
        '<dt>Concern Category</dt><dd>' + U().esc(t.category) + '</dd>' +
        '<dt>Assigned To</dt><dd>' + U().esc(t.assignedTo || 'Unassigned') + '</dd>' +
        '<dt>Ticket Reference</dt><dd class="mono">' + U().esc(t.code) + '</dd>' +
        '</dl>';
    }

    function body() {
      return '<div id="ticket-alert"></div>' +
        '<div class="form-section">' +
        '<div class="section-title"><span>1.</span> Ticket Summary</div>' +
        details() +
        '<div class="tile tile--tint"><span class="tile__ico">' + OSAS.icons.icon('headset', 15) + '</span>' +
        '<div><div class="tile__title">' + U().esc(ticket.subject) + '</div>' +
        '<div class="tile__text">' + U().esc(ticket.description) + '</div></div></div>' +
        '</div>' +
        '<div class="form-section">' +
        '<div class="section-title"><span>2.</span> Conversation Thread</div>' +
        '<div class="thread">' + ticket.messages.map(function (message) {
          var isStaff = message.role !== 'Student';
          return '<div class="msg' + (isStaff ? ' msg--staff' : '') + '">' +
            C().avatarOf(message.author, isStaff ? undefined : 'grey') +
            '<div class="u-grow"><div class="msg__head"><span class="msg__name">' + U().esc(message.author) +
            '</span><span class="msg__role">' + U().esc(message.role) + '</span>' +
            '<span class="msg__time">' + OSAS.fmt.dateTime(message.at) + '</span></div>' +
            '<div class="msg__body">' + U().esc(message.body) + '</div></div></div>';
        }).join('') + '</div>' +
        '<div class="field"><label for="ticket-reply">Reply to the Student</label>' +
        '<textarea class="input" id="ticket-reply" rows="3" placeholder="Type your response or the action taken…"></textarea>' +
        '<div class="u-between u-mt-8"><span class="hint">Replies are visible to the student in the portal.</span>' +
        '<button type="button" class="btn btn--outline btn--sm" id="ticket-reply-send">' +
        OSAS.icons.icon('send', 12) + ' Send Reply</button></div></div>' +
        '</div>' +
        '<div class="form-section">' +
        '<div class="section-title"><span>3.</span> Resolution Controls</div>' +
        '<div class="form-grid form-grid--3">' +
        field('Ticket Status', selectInput('ticket-status', OSAS.store.options().ticketStatuses, ticket.status, 'Select status')) +
        field('Priority', selectInput('ticket-priority', OSAS.store.options().priorities, ticket.priority, 'Select priority')) +
        field('Assigned To', selectInput('ticket-assignee',
          ['Josefina Reyes', 'Pia Santiago', 'Nathaniel Cruz', 'Andrea Villanueva'], ticket.assignedTo, 'Select staff member')) +
        '</div>' +
        C().alert('info', 'Service level reminder',
          'High priority inquiries must be acknowledged within 1 hour and resolved within 24 hours.') +
        '</div>';
    }
    return C().modal({
      size: 'lg',
      title: 'View Ticket #' + U().esc(ticket.code),
      subtitle: 'Student inquiry submitted through the Feedback &amp; Support Center',
      body: body(),
      footerClass: ' modal__foot--split',
      footer: '<button type="button" class="btn btn--ghost" data-close>Close Window</button>' +
        '<div class="btn-row">' +
        '<button type="button" class="btn btn--outline" id="ticket-resolve">Mark as Resolved</button>' +
        '<button type="button" class="btn btn--primary" id="ticket-update">Update Ticket</button></div>',
      onMount: function (el, close) {
        function alertBox(tone, title, text) {
          el.querySelector('#ticket-alert').innerHTML = C().alert(tone, title, text);
        }
        function persistTicket() {
          OSAS.store.update('tickets', ticket.id, {
            status: ticket.status, priority: ticket.priority, assignedTo: ticket.assignedTo,
            updatedAt: ticket.updatedAt, messages: ticket.messages
          });
        }
        function refresh() {
          el.querySelector('.modal__body').innerHTML = body();
          bind();
        }
        function notify() { if (typeof options.onUpdated === 'function') { options.onUpdated(ticket); } }

        function bind() {
          el.querySelector('#ticket-reply-send').addEventListener('click', function () {
            var textarea = el.querySelector('#ticket-reply');
            var message = String(textarea.value || '').trim();
            if (!message) {
              alertBox('warning', 'Nothing to send', 'Type a reply before sending it to the student.');
              return;
            }
            ticket.messages.push({ author: staffName, role: 'OSAS Support', at: U().now(), body: message });
            ticket.updatedAt = U().now();
            if (ticket.status === 'Open') { ticket.status = 'In Progress'; }
            persistTicket();
            OSAS.store.logActivity({
              tone: 'blue', icon: 'headset', title: 'Support Reply Sent',
              text: staffName + ' replied to ' + ticket.code + ' (' + ticket.student + ').'
            });
            refresh();
            C().toast('success', 'Reply sent', 'The student was notified through the portal and university email.');
            notify();
          });
        }
        bind();

        el.querySelector('#ticket-update').addEventListener('click', function () {
          ticket.status = el.querySelector('#ticket-status').value || ticket.status;
          ticket.priority = el.querySelector('#ticket-priority').value || ticket.priority;
          ticket.assignedTo = el.querySelector('#ticket-assignee').value || ticket.assignedTo;
          ticket.updatedAt = U().now();
          persistTicket();
          OSAS.store.logActivity({
            tone: ticket.status === 'Resolved' ? 'green' : 'amber', icon: 'headset',
            title: 'Ticket Updated #' + ticket.code,
            text: ticket.student + ' · ' + ticket.category + ' — now ' + ticket.status +
              ' (' + ticket.priority + ' priority), assigned to ' + ticket.assignedTo + '.'
          });
          C().toast('success', 'Ticket updated', ticket.code + ' is now marked as ' + ticket.status + '.');
          refresh();
          notify();
        });

        el.querySelector('#ticket-resolve').addEventListener('click', function () {
          ticket.status = 'Resolved';
          ticket.updatedAt = U().now();
          ticket.messages.push({
            author: staffName, role: 'OSAS Support', at: U().now(),
            body: 'Your concern has been addressed and verified. We are closing this ticket as resolved — please reopen it if the issue recurs.'
          });
          persistTicket();
          OSAS.store.logActivity({
            tone: 'green', icon: 'check', title: 'New Ticket Resolved #' + ticket.code,
            text: ticket.student + ' · ' + ticket.category + ' marked as resolved by OSAS Support.'
          });
          C().toast('success', 'Ticket resolved', ticket.code + ' was closed and the student was notified.');
          close();
          notify();
        });
      }
    });
  }
  /* ------------------------------------------------- Create support ticket
     Frame 87 — "Create New Support Ticket". */
  function ticketCreate(options) {
    var opts = options || {};
    var students = OSAS.store.all('users').filter(function (row) { return row.role === 'Student'; });
    var optionList = OSAS.store.options();
    var session = OSAS.auth.current();
    var studentOptions = students.map(function (row) {
      return row.firstName + ' ' + row.lastName + ' · ' + (row.studentId || 'No ID');
    });

    var body =
      '<div id="ticket-form-alert"></div>' +
      '<div class="form-section">' +
      '<div class="section-title"><span>1.</span> Requesting Student</div>' +
      '<div class="form-grid form-grid--2">' +
      field('Student', selectInput('tc-student', studentOptions, '', 'Select the student filing the inquiry'),
        { id: 'tc-student', required: true, hint: 'Only accounts with the Student role can file portal inquiries.' }) +
      field('Concern Category', selectInput('tc-category', optionList.ticketCategories, '', 'Select a category'),
        { id: 'tc-category', required: true }) +
      '</div></div>' +
      '<div class="form-section">' +
      '<div class="section-title"><span>2.</span> Inquiry Details</div>' +
      '<div class="form-grid form-grid--2">' +
      field('Priority', selectInput('tc-priority', optionList.priorities, 'Medium', 'Select priority'),
        { id: 'tc-priority', required: true }) +
      field('Preferred Response Channel',
        selectInput('tc-channel', ['Portal Notification', 'University Email'], 'Portal Notification', 'Select a channel')) +
      '</div>' +
      field('Subject', textInput('tc-subject', '', 'Short description of the concern'), { id: 'tc-subject', required: true }) +
      field('Detailed Description',
        '<textarea class="input" id="tc-description" rows="4" placeholder="Describe the concern, including the date and time it was encountered…"></textarea>',
        { id: 'tc-description', required: true }) +
      field('Supporting Attachment (optional)',
        '<input class="input" type="file" id="tc-attachment" accept=".pdf,.png,.jpg,.jpeg,.docx">',
        { id: 'tc-attachment', hint: 'PDF, PNG, JPG or DOCX up to 10 MB. The file name is stored with the ticket record.' }) +
      '</div>' +
      '<label class="check"><input type="checkbox" id="tc-consent" checked>' +
      '<span>I confirm that the details provided are accurate and that the student has been informed of this inquiry.</span></label>';
    return C().modal({
      size: 'lg',
      title: 'Create New Support Ticket',
      subtitle: 'Log a student inquiry, complaint or feedback item for OSAS action.',
      body: body,
      footer: '<button type="button" class="btn btn--ghost" data-close>Cancel</button>' +
        '<button type="button" class="btn btn--primary" id="ticket-form-save">Submit Ticket</button>',
      onMount: function (el) {
        var alertHost = el.querySelector('#ticket-form-alert');
        el.querySelector('#ticket-form-save').addEventListener('click', function () {
          var studentLabel = el.querySelector('#tc-student').value;
          var category = el.querySelector('#tc-category').value;
          var priorityLevel = el.querySelector('#tc-priority').value;
          var subject = String(el.querySelector('#tc-subject').value || '').trim();
          var description = String(el.querySelector('#tc-description').value || '').trim();
          var errors = [];

          if (!studentLabel) { errors.push('Select the student who filed the inquiry.'); }
          if (!category) { errors.push('Select a concern category.'); }
          if (!subject) { errors.push('A ticket subject is required.'); }
          if (!description) { errors.push('A detailed description is required.'); }
          if (!el.querySelector('#tc-consent').checked) {
            errors.push('Confirm the accuracy declaration before submitting.');
          }
          if (errors.length) {
            alertHost.innerHTML = C().alert('danger', 'Unable to submit the ticket', errors.join(' '));
            return;
          }

          var student = students.filter(function (row) {
            return (row.firstName + ' ' + row.lastName + ' · ' + (row.studentId || 'No ID')) === studentLabel;
          })[0];
          var studentName = student ? student.firstName + ' ' + student.lastName : studentLabel;
          var stamp = U().now();
          var fileInput = el.querySelector('#tc-attachment');
          var ticketId = OSAS.store.nextId('tickets', 'TCS', 3);
          var record = {
            id: ticketId, code: ticketId,
            student: studentName,
            studentEmail: student ? student.email : '',
            studentId: student ? student.studentId : '',
            program: student ? student.program : '',
            category: category,
            subject: subject,
            description: description,
            priority: priorityLevel || 'Medium',
            status: 'Open',
            submittedAt: stamp,
            updatedAt: stamp,
            assignedTo: session ? session.user.firstName + ' ' + session.user.lastName : 'OSAS Support',
            channel: el.querySelector('#tc-channel').value || 'Portal Notification',
            attachment: fileInput.files.length ? fileInput.files[0].name : '',
            messages: [{ author: studentName, role: 'Student', at: stamp, body: description }]
          };
          OSAS.store.insert('tickets', record);
          OSAS.store.logActivity({
            tone: 'amber', icon: 'headset', title: 'New Support Ticket Logged',
            text: record.code + ' · ' + studentName + ' — ' + record.category + ' (' + record.priority + ' priority).'
          });
          alertHost.innerHTML = C().alert('success', 'Ticket Created Successfully!',
            'Reference <b>' + record.code + '</b> was logged and assigned to <b>' +
            U().esc(record.assignedTo) + '</b>.');
          el.querySelector('.modal__foot').innerHTML =
            '<button type="button" class="btn btn--primary" data-close>Done</button>';
          C().toast('success', 'Ticket created', record.code + ' — ' + record.subject);
          if (typeof opts.onSaved === 'function') { opts.onSaved(record); }
        });
      }
    });
  }

  return {
    field: field, textInput: textInput, selectInput: selectInput, passwordInput: passwordInput,
    value: value, validEmail: validEmail, bindPasswordToggles: bindPasswordToggles,
    userForm: userForm, ticketView: ticketView, ticketCreate: ticketCreate
  };
})();
