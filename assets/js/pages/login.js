/* ==========================================================================
   Page: Portal sign-in (index.html)
   ========================================================================== */
(function () {
  'use strict';
  /* Clear stale session data from old versions */
  try {
    var keys = Object.keys(localStorage).filter(function (k) { return k.startsWith('beacon.osas'); });
    keys.forEach(function (k) { localStorage.removeItem(k); });
    sessionStorage.clear();
  } catch (e) { /* ignore */ }

  var C = OSAS.components;

  /* If a session already exists, skip the sign-in screen. */
  var existing = OSAS.auth.current();
  var params = new URLSearchParams(window.location.search);
  var next = params.get('next');
  if (existing) {
    window.location.replace(next || 'dashboard.html');
    return;
  }

  if (OSAS.CONFIG.autoAdmin) {
    var admin = OSAS.store.all('users').filter(function (row) {
      return row.role === 'Administrator';
    })[0];
    if (admin) {
      /* Prototype: the administrator account is already in the form, so the
         reviewer only has to press Login. */
      document.getElementById('email').value = admin.email;
      document.getElementById('password').value = OSAS.CONFIG.demoPassword;
    }
  }

  var TAGS = [
    'Sign in to access the <strong>Information Broadcasting and Student Services System</strong> of the Office of Student Affairs.',
    'Track announcements, orientation modules, and student support inquiries in one secure portal.',
    'Official college bulletins, regulations, and automated student alerts — for every SCT student.'
  ];

  document.getElementById('auth-brand').innerHTML = OSAS.icons.beacon(96, 2) +
    '<div class="auth__wordmark">BEACON</div>' +
    '<div class="auth__wordmark-sub">Region &middot; Southern College of Technology</div>' +
    '<p class="auth__tag" id="auth-tag">' + TAGS[0] + '</p>';
  document.getElementById('auth-corner').innerHTML = OSAS.icons.beacon(22, 2.4);

  /* Tagline rotation (carousel dots under the brand panel) */
  var dots = document.getElementById('auth-dots');
  dots.innerHTML = TAGS.map(function (tag, index) {
    return '<button type="button" class="pdot' + (index === 0 ? ' pdot--on' : '') +
      '" data-tag="' + index + '" aria-label="Slide ' + (index + 1) + '"></button>';
  }).join('');
  var tagIndex = 0;
  function showTag(index) {
    tagIndex = index % TAGS.length;
    document.getElementById('auth-tag').innerHTML = TAGS[tagIndex];
    OSAS.util.qsa('.pdot', dots).forEach(function (dot, i) {
      dot.classList.toggle('pdot--on', i === tagIndex);
    });
  }
  dots.addEventListener('click', function (event) {
    var dot = event.target.closest('[data-tag]');
    if (dot) { showTag(parseInt(dot.getAttribute('data-tag'), 10)); }
  });
  window.setInterval(function () { showTag(tagIndex + 1); }, 7000);

  /* Password visibility */
  var passwordToggle = document.getElementById('password-toggle');
  passwordToggle.addEventListener('click', function () {
    var input = document.getElementById('password');
    var showing = input.type === 'password';
    input.type = showing ? 'text' : 'password';
    passwordToggle.innerHTML = OSAS.icons.icon(showing ? 'eyeOff' : 'eye', 15);
  });

  /* Sign-in */
  var alertHost = document.getElementById('auth-alert');
  document.getElementById('login-form').addEventListener('submit', function (event) {
    event.preventDefault();
    var email = document.getElementById('email').value;
    var password = document.getElementById('password').value;
    var remember = document.getElementById('remember').checked;
    alertHost.innerHTML = '';

    if (!OSAS.forms.validEmail(email) || !password) {
      alertHost.innerHTML = C.alert('danger', 'Incomplete sign-in details',
        'Enter your official university email address and password to continue.');
      return;
    }

    var result = OSAS.auth.login(email, password, remember);
    if (!result.ok) {
      alertHost.innerHTML = C.alert('danger', 'Sign-in failed', result.message);
      C.toast('error', 'Sign-in failed', result.message);
      return;
    }
    var button = document.getElementById('login-submit');
    button.disabled = true;
    button.textContent = 'Verifying account…';
    alertHost.innerHTML = C.alert('success', 'Signed in successfully',
      'Welcome back, ' + OSAS.util.esc(result.user.firstName) + '! Opening the portal dashboard…');
    window.setTimeout(function () { window.location.href = next || 'dashboard.html'; }, 650);
  });
  /* Forgot password */
  document.getElementById('forgot-password').addEventListener('click', function (event) {
    event.preventDefault();
    C.modal({
      size: 'sm',
      title: 'Reset Portal Password',
      subtitle: 'Enter your university email address and we will send a reset link.',
      body: '<div id="reset-alert"></div>' +
        OSAS.forms.field('University Email Address',
          '<span class="input-group"><span class="input-group__ico">' + OSAS.icons.icon('mail', 14) + '</span>' +
          '<input class="input" id="reset-email" type="email" placeholder="juandelacruz@uc.edu.ph"></span>', {}) +
        C.alert('info', 'Reset links expire after 15 minutes',
          'For urgent account issues, contact the OSAS Support Center at osas.support@uc.edu.ph.'),
      footer: '<button type="button" class="btn btn--ghost" data-close>Cancel</button>' +
        '<button type="button" class="btn btn--primary" id="reset-send">Send Reset Link</button>',
      onMount: function (el, close) {
        el.querySelector('#reset-send').addEventListener('click', function () {
          var email = String(el.querySelector('#reset-email').value || '').trim();
          var host = el.querySelector('#reset-alert');
          if (!OSAS.forms.validEmail(email)) {
            host.innerHTML = C.alert('danger', 'Invalid email address',
              'Enter the @uc.edu.ph email address registered to your portal account.');
            return;
          }
          host.innerHTML = C.alert('success', 'Reset link sent',
            'Instructions were sent to <b>' + OSAS.util.esc(email) + '</b>.');
          OSAS.store.logActivity({
            tone: 'amber', icon: 'lock', title: 'Password Reset Requested',
            text: 'A password reset link was requested for ' + email + '.'
          });
          C.toast('success', 'Reset link sent', email);
          window.setTimeout(close, 900);
        });
      }
    });
  });

  /* Create account — Frames 91 / 92 */
  document.getElementById('create-account').addEventListener('click', function () {
    OSAS.forms.userForm({
      onSaved: function (user) {
        document.getElementById('email').value = user.email;
        document.getElementById('password').focus();
      }
    });
  });

  /* Demo credentials helper */
  var demoUser = OSAS.store.all('users').filter(function (u) {
    return u.role === 'Administrator';
  })[0];
  var demoEmail = demoUser ? demoUser.email : 'josefina.reyes@uc.edu.ph';
  document.getElementById('auth-hint').innerHTML = OSAS.CONFIG.autoAdmin
    ? 'Administrator account is already filled in &mdash; press <b>Login</b> to open the dashboard.'
    : 'Administrator account: <b>' + OSAS.util.esc(demoEmail) +
      '</b> &middot; password <b>' + OSAS.util.esc(OSAS.CONFIG.demoPassword) + '</b>';
  document.getElementById('fill-demo').addEventListener('click', function (event) {
    event.preventDefault();
    document.getElementById('email').value = demoEmail;
    document.getElementById('password').value = OSAS.CONFIG.demoPassword;
    document.getElementById('password').type = 'password';
    passwordToggle.innerHTML = OSAS.icons.icon('eye', 15);
  });

  C.flashFromQuery();
/* @@LOGIN-END@@ */
})();
