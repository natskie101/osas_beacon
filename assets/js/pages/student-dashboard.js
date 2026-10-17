/* ==========================================================================
   Student module — Student Dashboard  (student/dashboard.html)
   The shell, the student-only access gate and the navigation live in
   student-shell.js; this script only renders the dashboard feed.
   ========================================================================== */
(function () {
  'use strict';

  var shell = OSAS.studentShell.mount({ page: 'dashboard', title: 'Student Dashboard' });
  if (!shell) { return; }

  var U = OSAS.util, C = OSAS.components;
  var user = shell.user;
  var main = shell.main;

  /* ------------------------------------------------------------ identity */
  function renderIdentity() {
    var line2 = [user.program || user.department || user.role, user.year]
      .filter(Boolean).join(' • ');
    var idLabel = user.studentId ? 'Student ID' : 'Portal ID';
    var idValue = user.studentId || user.id;
    var statusKey = U.slug(user.status);

    $('sp-welcome').innerHTML =
      '<div class="sp-welcome__id">' +
        '<div class="sp-welcome__eyebrow">Welcome back</div>' +
        '<div class="sp-welcome__name">' + U.esc(shell.fullName(user)) + '</div>' +
        '<div class="sp-welcome__meta">' + U.esc(line2) + '</div>' +
      '</div>' +
      '<div class="sp-welcome__grid">' +
        '<div><div class="sp-welcome__label">' + U.esc(idLabel) + '</div>' +
          '<div class="sp-welcome__value">' + U.esc(idValue) + '</div></div>' +
        '<div><div class="sp-welcome__label">Status</div>' +
          '<div class="sp-welcome__value"><span class="sp-welcome__pill sp-welcome__pill--' +
          statusKey + '">' + U.esc(user.status) + '</span></div></div>' +
      '</div>';
  }

  /* -------------------------------------------------------- announcement */
  function renderAnnouncement() {
    var published = OSAS.store.all('announcements').filter(function (a) {
      return a.status === 'Published';
    });
    var latest = published.length ? U.sortBy(published, 'publishedAt', 'desc')[0] : null;

    if (!latest) {
      $('sp-ann-title').textContent = 'No announcement right now';
      $('sp-ann-text').textContent = 'New bulletins from the Office of Student Affairs will appear here.';
      return;
    }
    $('sp-ann-title').textContent = latest.title;
    $('sp-ann-text').textContent = latest.summary;
  }

  /* ---------------------------------------------------------- orientation */
  function renderOrientation() {
    var published = OSAS.store.all('modules').filter(function (m) {
      return m.status === 'Published';
    });
    var featured = published.length ? U.sortBy(published, 'updatedAt', 'desc')[0] : null;

    if (!featured) {
      $('sp-orient-title').textContent = 'Orientation module is being prepared';
      return;
    }
    $('sp-orient-title').textContent = featured.title;
  }

  function $(id) { return document.getElementById(id); }

  /* ---------------------------------------------------------------- init */
  main.innerHTML =
    '<section class="sp-welcome" id="sp-welcome" aria-label="Signed-in student"></section>' +
    '<div class="sp-grid">' +
      '<div class="sp-col">' +
        '<section class="sp-card" aria-label="Latest announcement">' +
          '<div class="sp-card__label">ANNOUNCEMENT</div>' +
          '<a class="sp-card__title" id="sp-ann-title" href="announcements.html">Loading announcement…</a>' +
          '<p class="sp-card__text" id="sp-ann-text"></p>' +
        '</section>' +
        '<figure class="sp-poster">' +
          '<img src="../assets/img/choose-health-poster.svg" width="640" height="400" ' +
          'alt="Choose health, not smoke — smoke-free campus advocacy poster">' +
        '</figure>' +
      '</div>' +
      '<div class="sp-col">' +
        '<section class="sp-card" aria-label="Orientation module">' +
          '<div class="sp-card__label">Orientation</div>' +
          '<a class="sp-card__title" id="sp-orient-title" href="orientation.html">Loading orientation module…</a>' +
          '<p class="sp-card__text">Please read the Orientation Module for the instructions.</p>' +
          '<a class="sp-card__link" href="orientation.html">Instructions ' +
            OSAS.icons.icon('arrowRight', 13) + '</a>' +
        '</section>' +
        '<a class="sp-card sp-card--link" href="support.html">' +
          '<div class="sp-card__label">Submit Feedback/Report</div>' +
          '<p class="sp-card__text sp-card__text--dark">Please check Feedback/Report module</p>' +
        '</a>' +
      '</div>' +
    '</div>';

  renderIdentity();
  renderAnnouncement();
  renderOrientation();
})();
