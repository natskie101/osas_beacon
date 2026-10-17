/* ==========================================================================
   Student module — Orientation  (student/orientation.html)
   Module list -> module detail -> assessment quiz, hash routed:
     #/orient/ORIENT-111            module detail
     #/orient/ORIENT-111/quiz       assessment quiz (or the stored result)
   Only Published modules are visible to students; student-shell.js provides
   the navigation, the top bar and the student-accounts-only gate.

   Every control derives its row from the live hash / quiz state rather than
   the row it was bound with, so a re-mounted view can never act on stale data.
   ========================================================================== */
(function () {
  'use strict';

  var shell = OSAS.studentShell.mount({ page: 'orientation', title: 'Orientation' });
  if (!shell) { return; }

  var U = OSAS.util, C = OSAS.components;
  var main = shell.main;
  var user = shell.user;

  var KEY = 'beacon.osas.student.orientation';   /* attempts + bookmarks */
  var QUIZ_SECONDS = 15 * 60;
  var state = { q: '', category: 'All', quiz: null };
  var tick = null, videoTick = null, videoPos = 0;

  var OWNERS = {
    'Office of Student Affairs & Services': 'OSA Director',
    "Registrar's Office": 'Academic Affairs',
    'Guidance & Testing Center': 'Guidance Center',
    'IT Services Office': 'IT Center',
    'Campus Safety & Health Office': 'Health Services'
  };

  var PLAY_SVG = '<svg viewBox="0 0 24 24" width="26" height="26" fill="currentColor" aria-hidden="true"><path d="M9 6.5v11l9-5.5-9-5.5Z"/></svg>';
  var PAUSE_SVG = '<svg viewBox="0 0 24 24" width="26" height="26" fill="currentColor" aria-hidden="true"><path d="M8 6h3v12H8zM13 6h3v12h-3z"/></svg>';
  var BOOKMARK_SVG = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" ' +
    'stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
    '<path d="M6 4h12v16l-6-4-6 4V4Z"/></svg>';

  function $(id) { return document.getElementById(id); }

  /* --------------------------------------------------------------- data */
  function published() {
    return OSAS.store.all('modules').filter(function (row) { return row.status === 'Published'; });
  }
  function modCode(row) {
    var n = parseInt(String(row.code).split('-').pop(), 10);
    return 'ORIENT-' + (100 + (isNaN(n) ? 0 : n));
  }
  function byCode(code) {
    return published().filter(function (row) { return modCode(row) === code; })[0] || null;
  }
  function shortTitle(title) {
    return String(title || '').replace(/^Orientation Module — /, '');
  }
  function categoryLabel(row) {
    return String(row.category || 'Orientation').replace(/^Orientation Module — /, '');
  }
  function requirement(row) {
    return row.requirement || (row.mandatory ? 'Mandatory (Required for Clearance)' : 'Recommended');
  }
  function isMandatory(row) { return requirement(row).indexOf('Mandatory') === 0; }
  function contentType(row) { return row.contentType || 'Video Presentation'; }
  function verification(row) { return row.verification || 'quiz'; }
  function passingRate(row) { return Number(row.passingRate) || 80; }
  function deadline(row) { return row.deadline || '2026-10-31'; }
  function department(row) { return row.department || 'Office of Student Affairs & Services'; }
  function minutes(row) { return Number(row.duration) || 15; }
  function norm(s) { return String(s).replace(/[\u2019\u02bc]/g, "'"); }
  function owner(row) {
    var dept = norm(department(row));
    var key = Object.keys(OWNERS).filter(function (k) { return norm(k) === dept; })[0];
    return key ? OWNERS[key] : department(row);
  }

  function formatLabel(row) {          /* progress row of a list card */
    var ct = contentType(row), suffix = verification(row) === 'ack' ? ' + Sign-off' : ' + Quiz';
    if (ct === 'Document / PDF') { return 'Interactive PDF' + suffix; }
    if (ct === 'Slide Deck') { return 'Slide Deck (' + minutes(row) + ' mins)' + suffix; }
    if (ct === 'Web URL / Embed') { return 'Web Walkthrough' + suffix; }
    return 'Video (' + minutes(row) + ' mins)' + suffix;
  }
  function formatShort(row) {          /* meta line of the detail view */
    var ct = contentType(row), suffix = verification(row) === 'ack' ? ' + Sign-off' : ' + Quiz';
    if (ct === 'Document / PDF') { return 'PDF' + suffix; }
    if (ct === 'Slide Deck') { return 'Slides' + suffix; }
    if (ct === 'Web URL / Embed') { return 'Web' + suffix; }
    return 'Video' + suffix;
  }
  function fmtClock(seconds) {
    var s = Math.max(0, Math.round(seconds));
    var m = Math.floor(s / 60), rest = s % 60;
    return (m < 10 ? '0' : '') + m + ':' + (rest < 10 ? '0' : '') + rest;
  }
  function longDate(value) {
    var s = String(value || '');
    /* Date-only values parse as UTC and land on the previous day in
       negative-offset timezones — attach a local noon to keep the date. */
    if (/^\d{4}-\d{2}-\d{2}$/.test(s)) { s += 'T12:00:00'; }
    return OSAS.fmt.date(s);
  }

  /* ------------------------------------------------ storage (per browser) */
  function read() {
    try { return JSON.parse(window.localStorage.getItem(KEY)) || {}; }
    catch (err) { return {}; }
  }
  function write(map) {
    try { window.localStorage.setItem(KEY, JSON.stringify(map)); }
    catch (err) { /* ignore */ }
  }
  function attempt(code) { return (read().attempts || {})[code]; }
  function marked(code) { return Boolean((read().marks || {})[code]); }

  /* ------------------------------------------------------- live routing */
  function hashRow() {
    var m = (window.location.hash || '').match(/^#\/orient\/([A-Za-z0-9_-]+)/);
    return m ? byCode(m[1]) : null;
  }
  function quizRow() {
    if (state.quiz) { var r = byCode(state.quiz.code); if (r) { return r; } }
    return hashRow();
  }
  function bindOnce(node, type, fn) {
    if (!node) { return; }                    /* one live handler per node+event */
    node._bound = node._bound || {};
    if (node._bound[type]) { return; }
    node._bound[type] = true;
    node.addEventListener(type, fn);
  }
  function goBack() {
    var h = window.location.hash || '';
    var m = h.match(/^#\/orient\/([A-Za-z0-9_-]+)/);
    if (/\/quiz$/.test(h) && m) { window.location.hash = '#/orient/' + m[1]; }
    else { window.location.hash = ''; }
  }

  /* ------------------------------------------------------- quiz questions */
  var BANK = {
    'ORIENT-111': [
      { q: 'What is the primary focus of the University\u2019s Vision, Mission, and Core Values declaration?',
        o: ['Developing knowledgeable, skilled, and responsible community leaders.',
          'Exclusively focusing on athletic excellence and sports development.',
          'Managing commercial campus outlets and real estate properties.',
          'Overseas employment placement and visa assistance programs.'], a: 0 },
      { q: 'Which statement best describes the University Vision?',
        o: ['The future it hopes to build and the kind of graduates it forms.',
          'The list of subjects offered for the semester.',
          'The campus construction and parking plan.',
          'The budget of the athletic department.'], a: 0 },
      { q: 'What does the University Mission ask of every student?',
        o: ['Academic excellence, integrity, and service to the community.',
          'Winning every varsity league title.',
          'Enrolling in the maximum number of units.',
          'Living off-campus for the whole year.'], a: 0 },
      { q: 'Which of the following is a University Core Value?',
        o: ['Respect for persons and honesty in every undertaking.',
          'Commercial profitability of campus outlets.',
          'Competitiveness in online games.',
          'Exclusive privilege reserved for a few.'], a: 0 },
      { q: 'Who frames the orientation modules with the Vision, Mission and Core Values?',
        o: ['The Office of Student Affairs.',
          'The campus procurement office.',
          'The grounds and maintenance team.',
          'The external auditing firm.'], a: 0 },
      { q: 'Why are the Vision, Mission and Core Values discussed during orientation?',
        o: ['To guide students\u2019 conduct and decisions throughout their stay.',
          'To fill the orientation schedule.',
          'To test the students\u2019 handwriting.',
          'To select the student council officers.'], a: 0 },
      { q: 'How should a student live out the core values on campus?',
        o: ['With integrity, respect, and service in daily campus life.',
          'Only during recognition rites.',
          'Only when a teacher is watching.',
          'Values are optional for incoming freshmen.'], a: 0 },
      { q: 'Which best matches the University\u2019s commitment to its community?',
        o: ['Formation of socially responsible community leaders.',
          'Isolation from the surrounding community.',
          'Commercial expansion of campus businesses.',
          'Political campaigning inside classrooms.'], a: 0 }
    ],
    'ORIENT-102': [
      { q: 'What is the main purpose of the Student Code of Conduct?',
        o: ['To define expected behaviour and the disciplinary process.',
          'To set tuition fees and payment schedules.',
          'To schedule campus events and activities.',
          'To rank students according to popularity.'], a: 0 },
      { q: 'Who evaluates alleged violations of the Code?',
        o: ['The Office of Student Affairs and Services, through due process.',
          'The vendors operating inside the campus.',
          'The student council alone.',
          'Nobody \u2014 reports are ignored.'], a: 0 },
      { q: 'What is a first-offense sanction most likely to be?',
        o: ['A formal written warning and a required reflection.',
          'Immediate suspension for one semester.',
          'Expulsion from the university.',
          'No sanction at all.'], a: 0 },
      { q: 'What happens on a third offense?',
        o: ['Suspension from academic privileges for one academic semester.',
          'Nothing \u2014 repeat offenses are forgotten.',
          'A verbal reminder from a classmate.',
          'Free probation for all students.'], a: 0 },
      { q: 'Which behaviour is treated as a major offense?',
        o: ['Cheating in examinations or submitting plagiarized work.',
          'Asking a question during a lecture.',
          'Joining a recognized student organization.',
          'Using the library facilities.'], a: 0 },
      { q: 'Can a student appeal a disciplinary decision?',
        o: ['Yes \u2014 through the grievance and disciplinary appeal procedure.',
          'No, decisions are absolute and final.',
          'Only by posting on social media.',
          'Only incoming freshmen may appeal.'], a: 0 },
      { q: 'What should you do first if you experience harassment?',
        o: ['Report it to the Office of Student Affairs or the Guidance Center.',
          'Post it on social media for everyone to see.',
          'Ignore it until the next school year.',
          'Wait until graduation to raise it.'], a: 0 },
      { q: 'Why must every student read the Student Code of Conduct?',
        o: ['All students are duty-bound to observe its provisions.',
          'It is optional reading for officers only.',
          'It is sold as a supplemental module.',
          'Only freshmen are required to read it.'], a: 0 }
    ]
  };

  /* Banks store the correct answer first for readability; rotate each item
     once at load so option A is never always the answer. */
  var ANSWER_POS = [1, 3, 0, 2, 3, 1, 2, 0];
  Object.keys(BANK).forEach(function (code) {
    BANK[code] = BANK[code].map(function (item, i) {
      var at = ANSWER_POS[i % ANSWER_POS.length];
      var opts = item.o.slice();
      var right = opts.splice(item.a, 1)[0];
      opts.splice(at, 0, right);
      return { q: item.q, o: opts, a: at };
    });
  });

  function makeQ(text, correct, distractors, pos) {
    var opts = distractors.slice();
    opts.splice(pos, 0, correct);
    return { q: text, o: opts, a: pos };
  }

  /* Modules without a prepared bank get a generated knowledge check built
     from the module's own record, so every quiz stays answerable. */
  function fallbackQuestions(row) {
    var n = parseInt(String(row.code).split('-').pop(), 10) || 1;
    var rate = passingRate(row);
    var dept = department(row);
    var deptPool = ['Office of Student Affairs & Services', "Registrar's Office",
      'Guidance & Testing Center', 'IT Services Office', 'Campus Safety & Health Office']
      .filter(function (d) { return norm(d) !== norm(dept); });
    var titles = published().filter(function (r) { return r.id !== row.id; })
      .map(function (r) { return shortTitle(r.title); });
    while (titles.length < 3) { titles.push('Campus Facilities & Services'); }
    var d = minutes(row);
    var mandatory = isMandatory(row);
    var at = function (i) { return (n + i) % 4; };   /* keeps the answer position varied */

    return [
      makeQ('Which orientation module are you currently completing?', shortTitle(row.title),
        [titles[0], titles[1], titles[2]], at(0)),
      makeQ('What passing rate is required to complete this module?', rate + '%',
        ['50%', '60%', '100%'], at(1)),
      makeQ('Which office publishes this orientation module?', dept,
        [deptPool[0], deptPool[1], deptPool[2]], at(2)),
      makeQ('How long is the content of this module?', d + ' mins',
        [(d + 5) + ' mins', Math.max(4, d - 4) + ' mins', (d * 2) + ' mins'], at(3)),
      makeQ('Is this module required for student clearance?',
        mandatory ? 'Yes \u2014 it is mandatory for clearance.' : 'No \u2014 it is recommended only.',
        [mandatory ? 'No \u2014 it is recommended only.' : 'Yes \u2014 it is mandatory for clearance.',
          'It depends on the college you belong to.',
          'Only graduating students must complete it.'], at(0)),
      makeQ('By when must this module be completed?', longDate(deadline(row)),
        ['December 15, 2026', 'January 30, 2027', 'There is no deadline for this module.'], at(1)),
      makeQ('What happens if you do not reach the passing rate?',
        'You may retake the assessment until you pass.',
        ['Your portal account is suspended.',
          'The module closes forever.',
          'Nothing is recorded for the module.'], at(2)),
      makeQ('Where is your completion record saved?',
        'In your BEACON student portal account.',
        ['In the campus suggestion box.',
          'It is not recorded anywhere.',
          'On paper at the registrar only.'], at(3))
    ];
  }
  function questionsFor(row) { return BANK[modCode(row)] || fallbackQuestions(row); }

  /* ---------------------------------------------------------- list view */
  function filtered() {
    var q = state.q.trim().toLowerCase();
    return U.sortBy(published(), 'updatedAt', 'desc').filter(function (row) {
      if (state.category !== 'All' && categoryLabel(row) !== state.category) { return false; }
      if (!q) { return true; }
      return (row.title + ' ' + row.description + ' ' + row.category + ' ' + modCode(row))
        .toLowerCase().indexOf(q) > -1;
    });
  }
  function categories() {
    var counts = {};
    published().forEach(function (row) {
      var key = categoryLabel(row);
      counts[key] = (counts[key] || 0) + 1;
    });
    return Object.keys(counts).sort(function (a, b) {
      return counts[b] - counts[a] || a.localeCompare(b);
    }).map(function (name) { return { name: name, count: counts[name] }; });
  }
  function filterRows() {
    var html = '<button type="button" class="so-filter__row' + (state.category === 'All' ? ' is-on' : '') +
      '" data-cat="All"><span>All Modules</span><span class="so-filter__n">' + published().length + '</span></button>';
    categories().forEach(function (c) {
      html += '<button type="button" class="so-filter__row' + (state.category === c.name ? ' is-on' : '') +
        '" data-cat="' + U.esc(c.name) + '"><span>' + U.esc(c.name) +
        '</span><span class="so-filter__n">' + c.count + '</span></button>';
    });
    return html;
  }
  function cardHtml(row) {
    var code = modCode(row);
    var att = attempt(code);
    var rate = Number(row.completionRate) || 0;
    return '<a class="so-card" href="#/orient/' + code + '">' +
      '<div class="so-card__head">' +
        '<span class="so-pill so-pill--code">' + code + '</span>' +
        '<span class="so-card__title">' + U.esc(shortTitle(row.title)) + '</span>' +
        '<span class="so-pill so-pill--req' + (isMandatory(row) ? ' so-pill--req--on' : '') + '">' +
          (isMandatory(row) ? 'Mandatory' : 'Recommended') + '</span>' +
      '</div>' +
      '<p class="so-card__text">' + U.esc(row.description) + '</p>' +
      '<div class="so-card__prog">' +
        '<span class="so-bar"><span class="so-bar__fill" style="width:' + Math.min(100, rate) + '%"></span></span>' +
        '<span class="so-card__fmt">' + U.esc(formatLabel(row)) + '</span>' +
        '<span class="so-card__pct">' + rate.toFixed(1) + '% (' +
          OSAS.fmt.number(row.completions || 0) + '/' + OSAS.fmt.number(row.learners || 0) + ')</span>' +
      '</div>' +
      '<div class="so-card__foot">' +
        '<span class="so-card__meta">' + OSAS.fmt.shortDate(row.updatedAt) +
          ' &bull; By ' + U.esc(owner(row)) + '</span>' +
        (att ? '<span class="so-pill so-pill--done">' +
          (att.passed ? 'Passed ' + att.score.toFixed(1) + '%' : 'Not passed') + '</span>' : '') +
        '<span class="so-card__chev">' + OSAS.icons.icon('chevronRight', 16) + '</span>' +
      '</div>' +
    '</a>';
  }
  function cardsHtml() {
    var rows = filtered();
    if (!rows.length) {
      return C.emptyState(state.q
        ? 'No published module matches &ldquo;' + U.esc(state.q) + '&rdquo;'
        : 'No published module matches the selected category.');
    }
    return rows.map(cardHtml).join('');
  }
  function listHtml() {
    return '<div class="so so--list">' +
      '<div class="so-tools">' +
        '<div class="so-search">' +
          '<input type="search" class="so-search__input" id="so-q" aria-label="Search orientation modules" ' +
            'placeholder="Search orientation modules&hellip;" value="' + U.esc(state.q) + '">' +
          '<button type="button" class="so-search__btn" id="so-filter-btn" aria-expanded="false" ' +
            'aria-label="Filter by category">' + OSAS.icons.icon('filter', 15) + '</button>' +
        '</div>' +
        '<div class="so-filter" id="so-filter" hidden>' +
          '<div class="so-filter__h">Filter by category</div>' + filterRows() +
        '</div>' +
      '</div>' +
      '<div class="so-list" id="so-list">' + cardsHtml() + '</div>' +
    '</div>';
  }

  /* -------------------------------------------------------- detail view */
  function sectionsOf(html) {         /* splits the stored module body on <h4> */
    var out = [], re = /<h4>([\s\S]*?)<\/h4>/g, match, cursor = 0, current = null;
    while ((match = re.exec(html))) {
      if (current && match.index > cursor) { current.html += html.slice(cursor, match.index); }
      current = { heading: match[1], html: '' };
      out.push(current);
      cursor = match.index + match[0].length;
    }
    if (current) { current.html += html.slice(cursor); }
    if (!out.length) { out.push({ heading: '', html: html || '' }); }
    return out;
  }
  function objectivesHtml(row) {
    /* the body's closing "How to complete" section is not repeated here — the
       Completion Requirement panel below already carries that instruction. */
    return sectionsOf(row.body).filter(function (s) {
      return s.heading && !/how to complete/i.test(s.heading);
    }).map(function (s) {
      return '<div class="so-art"><div class="so-art__h">' + U.esc(s.heading) +
        '</div><div class="so-art__body">' + s.html + '</div></div>';
    }).join('');
  }
  function videoHtml(row) {
    videoPos = 0;
    return '<div class="so-video" id="so-video">' +
      '<div class="so-video__screen">' +
        '<button type="button" class="so-video__play" id="so-play" aria-label="Play module video">' +
          PLAY_SVG + '</button>' +
        '<div class="so-video__track"><span class="so-video__fill" id="so-vfill" style="width:0%"></span></div>' +
        '<div class="so-video__time" id="so-vtime">00:00 / ' + fmtClock(minutes(row) * 60) + '</div>' +
      '</div>' +
    '</div>';
  }
  function reqBoxHtml(row, att) {
    var quiz = verification(row) === 'quiz';
    var status = !att ? 'Pending Quiz Attempt'
      : (att.passed ? 'Passed \u2014 Module Cleared' : 'Not Passed \u2014 Retake Allowed');
    return '<div class="so-req">' +
      '<div class="so-req__head">' +
        '<span class="so-req__dot' + (quiz ? ' so-req__dot--on' : '') + '"></span>' +
        '<div><div class="so-req__title">' +
          (quiz ? 'Quiz Assessment Attached' : 'Digital Acknowledgment / Sign-off Only') + '</div>' +
        '<div class="so-req__note">' +
          (quiz ? 'Passing score is required to complete this module.'
                : 'A recorded sign-off is required to complete this module.') + '</div></div>' +
      '</div>' +
      '<div class="so-req__row"><span>Required Passing Rate</span><b>' + passingRate(row) + '%</b></div>' +
      '<div class="so-req__row"><span>Your Current Status</span><b class="' +
        (att ? (att.passed ? 'is-ok' : 'is-bad') : '') + '">' + status + '</b></div>' +
    '</div>';
  }
  function detailHtml(row) {
    var code = modCode(row);
    var att = attempt(code);
    return '<div class="so so--detail">' +
      '<div class="so-pills">' +
        '<span class="so-pill so-pill--code">' + code + '</span>' +
        '<span class="so-pill so-pill--req so-pill--req--on">' + U.esc(requirement(row)) + '</span>' +
        (att ? '<span class="so-pill ' + (att.passed ? 'so-pill--done' : 'so-pill--fail') + '">' +
          (att.passed ? 'Passed' : 'Not Passed') + '</span>' : '') +
      '</div>' +
      '<h1 class="so-title">' + U.esc(shortTitle(row.title)) + '</h1>' +
      '<div class="so-meta">' + U.esc(formatShort(row)) + ' &bull; ' + minutes(row) +
        ' mins &bull; ' + U.esc(department(row)) + '</div>' +
      '<div class="so-deadline"><span>Compliance Deadline:</span> <b>' +
        longDate(deadline(row)) + '</b></div>' +

      '<div class="so-art">' +
        '<div class="so-art__h">Summary / Overview Description</div>' +
        '<div class="so-art__body"><p>' + U.esc(row.description) + '</p></div>' +
      '</div>' +
      objectivesHtml(row) +
      '<div class="so-art">' +
        '<div class="so-art__h">Content Type: ' + U.esc(contentType(row)) + '</div>' +
        videoHtml(row) +
      '</div>' +
      '<div class="so-art">' +
        '<div class="so-art__h">Completion Requirement</div>' +
        reqBoxHtml(row, att) +
      '</div>' +
      '<div class="so-cta">' +
        '<button type="button" class="so-cta__btn" id="so-start">' +
          (att && att.passed ? 'View my assessment result'
            : (att ? 'Retake Module Quiz Assessment' : 'Take Module Quiz Assessment')) +
        '</button>' +
      '</div>' +
    '</div>';
  }

  /* --------------------------------------------------------- quiz views */
  function questionHtml(q, chosen) {
    return '<div class="so-qlabel">QUESTION</div>' +
      '<div class="so-q">' + U.esc(q.q) + '</div>' +
      '<div class="so-opts" role="radiogroup">' + q.o.map(function (opt, i) {
        return '<button type="button" class="so-opt' + (chosen === i ? ' so-opt--on' : '') +
          '" id="so-opt-' + i + '" data-opt="' + i + '" role="radio" aria-checked="' +
          (chosen === i ? 'true' : 'false') + '">' +
          '<span class="so-opt__dot"></span>' +
          '<span class="so-opt__key">' + String.fromCharCode(65 + i) + '.</span>' +
          '<span class="so-opt__text">' + U.esc(opt) + '</span></button>';
      }).join('') + '</div>';
  }
  function quizMetaHtml(idx, total) {
    return 'Question ' + (idx + 1) + ' of ' + total + ' &nbsp;|&nbsp; Single Choice';
  }
  function quizScreenHtml(row) {
    var qs = questionsFor(row), st = state.quiz, idx = st.index;
    return '<div class="so so--quiz">' +
      '<div class="so-quiz__top"><span class="so-timer" id="so-timer">' +
        fmtClock(st.left) + ' Left</span></div>' +
      '<div class="so-pills">' +
        '<span class="so-pill so-pill--code">' + modCode(row) + '</span>' +
        '<span class="so-pill so-pill--neutral">Assessment</span>' +
        '<span class="so-pill so-pill--pass">Passing score required: ' + passingRate(row) + '%</span>' +
      '</div>' +
      '<h1 class="so-title">' + U.esc(shortTitle(row.title)) + '</h1>' +
      '<div class="so-quiz__meta" id="so-quiz-meta">' + quizMetaHtml(idx, qs.length) + '</div>' +
      '<div class="so-progress"><span id="so-progress-fill" style="width:' +
        ((idx + 1) / qs.length * 100) + '%"></span></div>' +
      '<div id="so-question">' + questionHtml(qs[idx], st.answers[idx]) + '</div>' +
      '<div class="so-nav">' +
        '<button type="button" class="so-btn so-btn--ghost" id="so-prev">' +
          '&larr;&nbsp; Prev</button>' +
        '<button type="button" class="so-btn so-btn--brand" id="so-next">' +
          'NEXT QUESTION &nbsp;&rarr;</button>' +
      '</div>' +
      '<button type="button" class="so-btn so-btn--ok" id="so-submit">SUBMIT ANSWER</button>' +
    '</div>';
  }
  function resultHtml(row, att) {
    var qs = questionsFor(row);
    return '<div class="so so--quiz so--result">' +
      '<div class="so-pills">' +
        '<span class="so-pill so-pill--code">' + modCode(row) + '</span>' +
        '<span class="so-pill so-pill--neutral">Assessment Result</span>' +
        '<span class="so-pill ' + (att.passed ? 'so-pill--done' : 'so-pill--fail') + '">' +
          (att.passed ? 'Passed' : 'Not Passed') + '</span>' +
      '</div>' +
      '<h1 class="so-title">' + U.esc(shortTitle(row.title)) + '</h1>' +
      '<div class="so-score">' +
        '<div class="so-score__ring ' + (att.passed ? 'is-ok' : 'is-bad') + '">' +
          '<b>' + att.score.toFixed(1) + '%</b><span>' + att.correct + ' of ' + att.total + ' correct</span>' +
        '</div>' +
        '<div class="so-score__meta">' +
          '<div><span>Required passing rate</span><b>' + passingRate(row) + '%</b></div>' +
          '<div><span>Completed on</span><b>' + OSAS.fmt.dateTime(att.at) + '</b></div>' +
          '<div><span>Time used</span><b>' + fmtClock(att.seconds || 0) + '</b></div>' +
          (att.auto ? '<div class="so-score__warn">Time expired \u2014 the assessment was submitted automatically.</div>' : '') +
        '</div>' +
      '</div>' +
      '<div class="so-art">' +
        '<div class="so-art__h">Answer Review</div>' +
        '<div class="so-review">' + qs.map(function (q, i) {
          var mine = att.answers ? att.answers[i] : undefined;
          var right = mine === q.a;
          return '<div class="so-review__row ' + (right ? 'is-ok' : 'is-bad') + '">' +
            '<span class="so-review__mark">' + (right ? '&#10003;' : '&#10007;') + '</span>' +
            '<div><div class="so-review__q">' + U.esc(q.q) + '</div>' +
            (right ? '' : '<div class="so-review__a">Correct answer: ' +
              String.fromCharCode(65 + q.a) + '. ' + U.esc(q.o[q.a]) + '</div>') +
            '</div></div>';
        }).join('') + '</div>' +
      '</div>' +
      '<div class="so-nav">' +
        '<button type="button" class="so-btn so-btn--ghost" id="so-back">' +
          '&larr;&nbsp; Back to module</button>' +
        (att.passed ? '' :
          '<button type="button" class="so-btn so-btn--brand" id="so-retake">RETAKE ASSESSMENT</button>') +
      '</div>' +
    '</div>';
  }

  /* ----------------------------------------------------------- behaviour */
  function stopTick() {
    if (tick) { clearInterval(tick); tick = null; }
  }
  function stopVideo() {
    if (videoTick) { clearInterval(videoTick); videoTick = null; }
    var btn = $('so-play');
    if (btn) { btn.innerHTML = PLAY_SVG; btn.classList.remove('is-playing'); }
  }
  function scrollTop() { if (window.scrollTo) { window.scrollTo(0, 0); } }

  function setCategory(key) {
    state.category = key;
    Array.prototype.forEach.call(document.querySelectorAll('[data-cat]'), function (node) {
      node.classList.toggle('is-on', node.getAttribute('data-cat') === key);
    });
    $('so-list').innerHTML = cardsHtml();
  }
  function closeFilter() {
    var panel = $('so-filter');
    if (!panel || panel.hidden) { return; }
    panel.hidden = true;
    var btn = $('so-filter-btn');
    if (btn) { btn.classList.remove('is-on'); btn.setAttribute('aria-expanded', 'false'); }
  }
  function wireList() {
    bindOnce($('so-q'), 'input', function (event) {
      state.q = event.target.value;
      $('so-list').innerHTML = cardsHtml();
    });
    bindOnce($('so-filter-btn'), 'click', function (event) {
      event.stopPropagation();
      var panel = $('so-filter');
      panel.hidden = !panel.hidden;
      this.classList.toggle('is-on', !panel.hidden);
      this.setAttribute('aria-expanded', String(!panel.hidden));
    });
    Array.prototype.forEach.call(document.querySelectorAll('[data-cat]'), function (node) {
      bindOnce(node, 'click', function () {
        setCategory(node.getAttribute('data-cat'));
        closeFilter();
      });
    });
    bindOnce($('so-filter'), 'click', function (event) { event.stopPropagation(); });
  }

  function barFor(row, mode) {
    if (mode === 'quiz') {
      shell.setBar({
        menu: false, bell: false, avatar: false,
        title: '<button type="button" class="so-back" id="so-barback">' +
          OSAS.icons.icon('chevronLeft', 17) + 'Module Assessment Quiz</button>'
      });
      return;
    }
    shell.setBar({
      menu: false, bell: false, avatar: false,
      title: '<button type="button" class="so-back" id="so-barback">' +
        OSAS.icons.icon('chevronLeft', 17) + 'Orientation Module</button>',
      extra: '<button type="button" class="so-bookmark' + (marked(modCode(row)) ? ' is-on' : '') +
        '" id="so-bookmark" aria-pressed="' + (marked(modCode(row)) ? 'true' : 'false') +
        '" aria-label="Bookmark this module">' + BOOKMARK_SVG + '</button>'
    });
  }

  function toggleBookmark() {
    var row = hashRow();
    if (!row) { return; }
    var code = modCode(row), map = read();
    map.marks = map.marks || {};
    var now = !map.marks[code];
    if (now) { map.marks[code] = OSAS.util.now(); } else { delete map.marks[code]; }
    write(map);
    var btn = $('so-bookmark');
    if (btn) {
      btn.classList.toggle('is-on', now);
      btn.setAttribute('aria-pressed', String(now));
    }
    C.toast('success', now ? 'Module bookmarked' : 'Bookmark removed', shortTitle(row.title));
  }

  function wireDetail() {
    bindOnce($('so-barback'), 'click', goBack);
    bindOnce($('so-bookmark'), 'click', toggleBookmark);
    bindOnce($('so-start'), 'click', function () {
      var r = hashRow();
      if (r) { window.location.hash = '#/orient/' + modCode(r) + '/quiz'; }
    });
    bindOnce($('so-video'), 'click', function () { toggleVideo(); });
  }

  function toggleVideo() {
    var row = hashRow();
    if (!row) { return; }
    var total = minutes(row) * 60;
    if (videoTick) { stopVideo(); return; }
    var btn = $('so-play');
    if (btn) { btn.innerHTML = PAUSE_SVG; btn.classList.add('is-playing'); }
    videoTick = setInterval(function () {
      videoPos = Math.min(total, videoPos + 1);
      var fill = $('so-vfill'), time = $('so-vtime');
      if (fill) { fill.style.width = (videoPos / total * 100) + '%'; }
      if (time) { time.textContent = fmtClock(videoPos) + ' / ' + fmtClock(total); }
      if (videoPos >= total) { stopVideo(); }
    }, 1000);
  }

  function ensureTick() {
    if (tick || !state.quiz) { return; }
    tick = setInterval(function () {
      var s = state.quiz;
      if (!s) { stopTick(); return; }
      s.left = Math.max(0, s.left - 1);
      var chip = $('so-timer');
      if (chip) { chip.textContent = fmtClock(s.left) + ' Left'; }
      if (s.left <= 0) { stopTick(); var r = quizRow(); if (r) { submitQuiz(r, true); } }
    }, 1000);
  }

  /* One live handler per control: the frame is bound on the first paint and
     afterwards only the question block is repainted. */
  function paintQuiz() {
    var row = quizRow();
    if (!row) { return; }
    main.innerHTML = quizScreenHtml(row);

    function syncOptions() {
      var s = state.quiz;
      if (!s) { return; }
      var chosen = s.answers[s.index];
      questionsFor(quizRow())[s.index].o.forEach(function (unused, i) {
        var node = $('so-opt-' + i);
        if (!node) { return; }
        var on = chosen === i;
        node.classList.toggle('so-opt--on', on);
        node.setAttribute('aria-checked', on ? 'true' : 'false');
      });
    }
    function bindOptions() {
      var s = state.quiz;
      if (!s) { return; }
      questionsFor(quizRow())[s.index].o.forEach(function (unused, i) {
        var node = $('so-opt-' + i);
        if (!node) { return; }
        bindOnce(node, 'click', function () {
          var live = state.quiz;
          if (!live) { return; }
          live.answers[live.index] = i;
          syncOptions();
        });
      });
    }
    function paintQuestion() {
      var s = state.quiz;
      if (!s) { return; }
      var qs = questionsFor(quizRow());
      $('so-question').innerHTML = questionHtml(qs[s.index], s.answers[s.index]);
      bindOptions();
      var fill = $('so-progress-fill');
      if (fill) { fill.style.width = ((s.index + 1) / qs.length * 100) + '%'; }
      var meta = $('so-quiz-meta');
      if (meta) { meta.innerHTML = quizMetaHtml(s.index, qs.length); }
      var prev = $('so-prev'), next = $('so-next');
      if (prev) { prev.disabled = s.index === 0; }
      if (next) { next.disabled = s.index === qs.length - 1; }
    }

    bindOnce($('so-prev'), 'click', function () {
      var s = state.quiz;
      if (!s || s.index === 0) { return; }
      s.index -= 1;
      paintQuestion();
      scrollTop();
    });
    bindOnce($('so-next'), 'click', function () {
      var s = state.quiz;
      if (!s || s.index >= questionsFor(quizRow()).length - 1) { return; }
      s.index += 1;
      paintQuestion();
      scrollTop();
    });
    bindOnce($('so-submit'), 'click', function () {
      var r = quizRow();
      if (r) { submitQuiz(r, false); }
    });
    bindOnce($('so-barback'), 'click', goBack);

    paintQuestion();
    ensureTick();
  }

  function submitQuiz(row, auto) {
    var st = state.quiz;
    if (!st || st.code !== modCode(row)) { return; }
    var qs = questionsFor(row);
    var answered = st.answers.filter(function (a) { return a !== undefined && a !== null; }).length;
    var missing = qs.length - answered;
    if (!auto && missing > 0) {
      C.toast('info', 'Answer every question first',
        missing + ' of ' + qs.length + ' questions are still unanswered.');
      return;
    }
    var correct = 0;
    qs.forEach(function (q, i) { if (st.answers[i] === q.a) { correct++; } });
    var score = Math.round(correct / qs.length * 1000) / 10;
    var passed = score >= passingRate(row);
    var att = {
      score: score, correct: correct, total: qs.length, passed: passed,
      at: OSAS.util.now(), seconds: QUIZ_SECONDS - st.left, auto: Boolean(auto),
      answers: st.answers.slice()
    };
    var map = read();
    map.attempts = map.attempts || {};
    map.attempts[modCode(row)] = att;
    write(map);

    OSAS.store.logActivity({
      tone: passed ? 'green' : 'amber', icon: 'graduation',
      title: passed ? 'Orientation Module Passed' : 'Orientation Module Attempted',
      text: shortTitle(row.title) + ' \u2014 ' + score + '% (' + correct + '/' + qs.length +
        ') by ' + shell.fullName(user) + '.'
    });
    C.toast(passed ? 'success' : 'error',
      passed ? 'Module completed' : 'Assessment not passed',
      shortTitle(row.title) + ' \u2014 ' + score + '%');

    stopTick();
    renderResult(row, att);
  }

  function wireResult() {
    bindOnce($('so-barback'), 'click', goBack);
    bindOnce($('so-back'), 'click', goBack);
    bindOnce($('so-retake'), 'click', function () {
      state.quiz = null;
      var r = hashRow();
      if (r) { startQuiz(r); }
    });
  }

  /* ------------------------------------------------------------ render */
  function renderList() {
    stopTick(); stopVideo();
    shell.setBar({});
    main.innerHTML = listHtml();
    wireList();
    scrollTop();
  }
  function renderDetail(row) {
    stopTick(); stopVideo();
    barFor(row, 'detail');
    main.innerHTML = detailHtml(row);
    wireDetail();
    scrollTop();
  }
  function startQuiz(row) {
    var code = modCode(row);
    if (!state.quiz || state.quiz.code !== code) {
      state.quiz = { code: code, index: 0, answers: [], left: QUIZ_SECONDS };
    }
    paintQuiz();
    scrollTop();
  }
  function renderQuiz(row) {
    var att = attempt(modCode(row));
    stopTick(); stopVideo();
    barFor(row, 'quiz');
    if (att && att.passed) { renderResult(row, att); return; }
    if (att) { state.quiz = null; }      /* earlier failed attempt — start over */
    startQuiz(row);
  }
  function renderResult(row, att) {
    stopTick(); stopVideo();
    barFor(row, 'quiz');
    main.innerHTML = resultHtml(row, att);
    wireResult();
    scrollTop();
  }

  /* ---------------------------------------------------------- routing */
  function route() {
    var match = (window.location.hash || '').match(/^#\/orient\/([A-Za-z0-9_-]+)(\/quiz)?$/);
    if (match) {
      var row = byCode(match[1]);
      if (row) {
        if (match[2]) { renderQuiz(row); } else { renderDetail(row); }
        return;
      }
    }
    renderList();
  }

  window.addEventListener('hashchange', route);
  document.addEventListener('click', closeFilter);
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') { closeFilter(); }
  });
  route();
})();
