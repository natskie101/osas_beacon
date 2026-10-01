/* Headless run of the student shell + dashboard + rules modules (no jsdom). */
const fs = require('fs');
const vm = require('vm');

let pass = true;
function check(label, actual, expected) {
  const ok = expected === undefined ? Boolean(actual) : actual === expected;
  let shown = actual;
  if (typeof actual === 'string' && actual.length > 80) shown = actual.slice(0, 80) + '…';
  console.log((ok ? 'PASS  ' : 'FAIL  ') + label + '  ->  ' + shown);
  pass = pass && ok;
}
function count(haystack, needle) { return haystack.split(needle).length - 1; }
/* static markup check: paired tags must balance inside a rendered view */
function balanced(html) {
  return ['div', 'span', 'button', 'a', 'p', 'ul', 'li', 'h1', 'h4'].every(t =>
    count(html, '<' + t + ' ') + count(html, '<' + t + '>') === count(html, '</' + t + '>'));
}

function makeEl(id) {
  const classes = new Set();
  const listeners = {};
  return {
    id, innerHTML: '', textContent: '', value: '', hidden: false, disabled: false, style: {},
    children: [],
    classList: {
      add: c => classes.add(c),
      remove: c => classes.delete(c),
      toggle(c, on) { if (on === undefined) { classes.has(c) ? classes.delete(c) : classes.add(c); } else if (on) { classes.add(c); } else { classes.delete(c); } },
      contains: c => classes.has(c)
    },
    setAttribute(k, v) { this['attr_' + k] = String(v); },
    getAttribute(k) { return this['attr_' + k]; },
    addEventListener(type, fn) { (listeners[type] = listeners[type] || []).push(fn); },
    dispatch(type, ev) { (listeners[type] || []).slice().forEach(fn => fn.call(this, ev || { target: this, stopPropagation() {} })); },
    querySelector() { return null; },
    appendChild(child) { this.children.push(child); }, focus() {}
  };
}

function makeSandbox(userId, page, scriptList) {
  const elements = {};
  const winListeners = {};
  const local = {}, session = {};

  function store(backing) {
    const api = {
      getItem: k => (k in backing ? backing[k] : null),
      setItem: (k, v) => { backing[k] = String(v); api[k] = String(v); },
      removeItem: k => { delete backing[k]; delete api[k]; },
      clear: () => { Object.keys(backing).forEach(k => { delete backing[k]; delete api[k]; }); },
      key: i => Object.keys(backing)[i] || null
    };
    return api;
  }

  const sb = {
    console, URLSearchParams, encodeURIComponent, decodeURIComponent, setTimeout, clearTimeout,
    /* timers are stubbed: quiz/video countdowns schedule but never tick */
    setInterval: () => 1, clearInterval: () => {},
    localStorage: store(local),
    sessionStorage: store(session),
    document: {
      getElementById(id) { if (!elements[id]) elements[id] = makeEl(id); return elements[id]; },
      querySelector() { return null; },
      querySelectorAll() { return []; },
      addEventListener() {},
      createElement() { return makeEl('dynamic'); },
      body: { style: {}, appendChild() {} }
    },
    location: {
      search: '', pathname: '/student/' + page, hash: '', href: '',
      replace(v) { this.href = v; }, reload() { this.reloaded = true; }
    },
    matchMedia: () => ({ matches: false, addEventListener() {}, addListener() {} }),
    addEventListener(type, fn) { (winListeners[type] = winListeners[type] || []).push(fn); },
    _fire(type) { (winListeners[type] || []).forEach(fn => fn({})); },
    _el: id => elements[id],
    _elements: elements
  };
  sb.window = sb;
  sb.globalThis = sb;
  if (userId) {
    session['beacon.osas.session'] = JSON.stringify({ userId, role: 'x', signedInAt: '2026-09-16T09:00:00' });
  }
  const scripts = {
    'rules.html': 'assets/js/pages/student-rules.js',
    'orientation.html': 'assets/js/pages/student-orientation.js',
    'announcements.html': 'assets/js/pages/student-announcements.js',
    'events.html': 'assets/js/pages/student-events.js',
    'support.html': 'assets/js/pages/student-support.js',
    'dashboard.html': 'assets/js/pages/student-dashboard.js'
  };
  sb._load = function () {
    vm.createContext(sb);
    (scriptList || ['assets/js/seed.js', 'assets/js/core.js', 'assets/js/components.js',
      'assets/js/student-shell.js',
      scripts[page] || 'assets/js/pages/student-dashboard.js'
    ]).forEach(f => vm.runInContext(fs.readFileSync(f, 'utf8'), sb, { filename: f }));
  };
  return sb;
}

const STUDENT = 'USR-1002';   /* Ryan Dela Cruz */
const ADMIN = 'USR-1001';     /* Josefina Reyes */

console.log('\n--- rules module (student session) ---');
let sb = makeSandbox(STUDENT, 'rules.html');
sb._load();
let main = sb._el('sp-main').innerHTML;

check('list mounts', main.includes('sr--list'), true);
check('all-rules chip shows live count', /All Rules \(16\)/.test(main), true);
check('category chips show counts', main.includes('Conduct &amp; Discipline (7)'), true);
check('rule 1 rendered', main.includes('Campus ID &amp; RFID Badge Wear Protocol'), true);
check('rule 2 rendered', main.includes('Student Organization Activity Permits'), true);
check('rule 3 rendered', main.includes('Substance-Free Campus &amp; Anti-Smoking'), true);
check('rule 4 rendered', main.includes('Classroom Decorum &amp; Digital Device Usage'), true);
check('derived codes RULE-118..121',
  ['RULE-118', 'RULE-119', 'RULE-120', 'RULE-121'].every(c => main.includes(c)), true);
check('newest rule listed first',
  main.indexOf('RULE-118') < main.indexOf('RULE-119') &&
  main.indexOf('RULE-119') < main.indexOf('RULE-120') &&
  main.indexOf('RULE-120') < main.indexOf('RULE-121'), true);
check('revised date formatted', main.includes('Revised: Sep 10, 2026'), true);
check('rules nav item active',
  sb._elements['sp-shell'].innerHTML.includes('href="rules.html" aria-current="page"'), true);
check('module top bar shows the page title (mock)',
  sb._elements['sp-shell'].innerHTML.includes('sp-top__inner--titled'), true);
check('draft rule hidden', main.includes('Proposed Policy on the Use of AI Tools'), false);
check('pending rule hidden', main.includes('Campus Safety and Emergency Response Policy'), false);
check('archived rule hidden', main.includes('Guidelines on On-Campus Parking'), false);

/* --- search -------------------------------------------------------------- */
sb._el('sr-q').value = 'rfid';
sb._el('sr-q').dispatch('input', { target: sb._el('sr-q') });
let hits = sb._el('sr-list').innerHTML;
check('search "rfid" matches the badge rule', hits.includes('Campus ID &amp; RFID'), true);
check('search "rfid" hides other rules', hits.includes('Anti-Smoking'), false);

sb._el('sr-q').value = 'health';
sb._el('sr-q').dispatch('input', { target: sb._el('sr-q') });
check('search by category works', sb._el('sr-list').innerHTML.includes('Anti-Smoking'), true);

sb._el('sr-q').value = '';
sb._el('sr-q').dispatch('input', { target: sb._el('sr-q') });
check('clearing search restores 16 cards',
  count(sb._el('sr-list').innerHTML, 'class="sr-card"') === 16, true);

/* --- detail -------------------------------------------------------------- */
sb.location.hash = '#/rule/RULE-119';
sb._fire('hashchange');
main = sb._el('sp-main').innerHTML;
check('detail view mounted', main.includes('sr--detail'), true);
const bar = id => sb._el(id).innerHTML;
check('detail back link moved into the top bar', bar('sp-top-slot').includes('Policy Details'), true);
check('bookmark control moved into the top bar', bar('sp-top-extra').includes('sr-bookmark'), true);
check('page title hidden while the back link shows',
  sb._el('sp-top-title').style.display === 'none', true);
check('detail hides menu / bell / avatar (mock bar)',
  sb._el('sp-menu').style.display === 'none' &&
  sb._el('sp-bell').style.display === 'none' &&
  sb._el('sp-avatar').style.display === 'none', true);
check('detail title', main.includes('Student Organization Activity Permits'), true);
check('code + status pills', main.includes('RULE-119') && main.includes('In Force'), true);
check('category + reviser meta', main.includes('Last Revised:') &&
  main.includes('By Office of Student Affairs and Services'), true);
const arts = ['Article I', 'Article II', 'Article III', 'Article IV'].filter(a => !main.includes(a));
check('Articles I..IV parsed from the store body (missing: ' + arts.join(',') + ')',
  arts.length === 0, true);
check('sanction ladder rendered',
  main.includes('First Offense') && main.includes('Second Offense') && main.includes('Third Offense'), true);
check('acknowledge button present', main.includes('I acknowledge this policy'), true);

/* --- bookmark (before any re-render) ------------------------------------- */
sb._el('sr-bookmark').dispatch('click');
check('bookmark stored',
  JSON.parse(sb.localStorage.getItem('beacon.osas.student.rules')).mark['REG-2026-019'] !== undefined, true);
check('bookmark class toggled', sb._el('sr-bookmark').classList.contains('is-on'), true);

/* --- acknowledge --------------------------------------------------------- */
const logsBefore = sb.OSAS.store.all('auditLog').length;
sb._el('sr-ack').dispatch('click');
main = sb._el('sp-main').innerHTML;
check('acknowledged state rendered', main.includes('Policy acknowledged'), true);
check('ack stored per rule',
  JSON.parse(sb.localStorage.getItem('beacon.osas.student.rules')).ack['REG-2026-019'] !== undefined, true);
check('acknowledgement written to the audit log',
  sb.OSAS.store.all('auditLog').length > logsBefore, true);

/* --- back to list -------------------------------------------------------- */
sb._el('sr-back').dispatch('click');
sb._fire('hashchange');
check('back returns to the list', sb._el('sp-main').innerHTML.includes('sr--list'), true);
check('all 16 cards after back',
  count(sb._el('sp-main').innerHTML, 'class="sr-card"') === 16, true);
check('list bar restored (title back, bell + avatar visible)',
  sb._el('sp-top-slot').style.display === 'none' &&
  sb._el('sp-bell').style.display === '' &&
  sb._el('sp-top-title').style.display === '', true);

console.log('\n--- rules module (administrator session) ---');
sb = makeSandbox(ADMIN, 'rules.html');
sb._load();
main = sb._el('sp-main').innerHTML;
check('admin is locked out', main.includes('ACCESS RESTRICTED'), true);
check('lock message uses the page title',
  main.includes('Rules and Regulation module is for student accounts only'), true);
check('root flagged as locked', sb._el('sp-root').classList.contains('sp--locked'), true);
check('no rule data leaked to admin', main.includes('RFID Badge Wear Protocol'), false);
check('demo student escape hatch offered', main.includes('sp-demo-student'), true);

console.log('\n--- orientation module (student session) ---');
sb = makeSandbox(STUDENT, 'orientation.html');
sb._load();
main = sb._el('sp-main').innerHTML;
check('list mounts', main.includes('so--list'), true);
check('list markup balanced', balanced(main), true);
check('all 9 published modules rendered', count(main, 'class="so-card"') === 9, true);
check('newest module listed first (ORIENT-111)',
  main.indexOf('ORIENT-111') < main.indexOf('ORIENT-108'), true);
check('seed title prefix stripped', main.includes('Orientation Module \u2014 '), false);
check('featured module title', main.includes('University Vision, Mission &amp; Core Values'), true);
check('mandatory pills (7)', count(main, 'so-pill--req so-pill--req--on') === 7, true);
check('recommended pills (2)', count(main, 'class="so-pill so-pill--req"') === 2, true);
check('cohort progress row', /\d+\.\d% \(\d[\d,]*\/\d[\d,]*\)/.test(main), true);
check('video format label', main.includes('Video (12 mins) + Quiz'), true);
check('interactive PDF label', main.includes('Interactive PDF + Quiz'), true);
check('slide deck label', main.includes('Slide Deck (15 mins) + Quiz'), true);
check('web walkthrough label', main.includes('Web Walkthrough + Quiz'), true);
check('owner derived from the publishing office',
  main.includes('By OSA Director') && main.includes('By IT Center') &&
  main.includes('By Health Services') && main.includes('By Academic Affairs'), true);
check('draft module hidden', main.includes('Financial Literacy for Freshmen'), false);
check('archived module hidden', main.includes('2025 Campus Tour (Legacy)'), false);
check('filter panel lists every category',
  main.includes('Filter by category') && main.includes('All Modules') &&
  count(main, 'data-cat=') === 9, true);
check('nav marks orientation active',
  sb._elements['sp-shell'].innerHTML.includes('href="orientation.html" aria-current="page"'), true);
check('module bar shows the page title (mock)',
  sb._elements['sp-shell'].innerHTML.includes('sp-top__inner--titled'), true);

/* --- search --------------------------------------------------------------- */
sb._el('so-q').value = 'orient-107';
sb._el('so-q').dispatch('input', { target: sb._el('so-q') });
hits = sb._el('so-list').innerHTML;
check('search by module code finds one card',
  count(hits, 'class="so-card"') === 1 && hits.includes('Digital Portal Guide'), true);
check('search hides the other modules', hits.includes('Health &amp; Wellness'), false);

sb._el('so-q').value = '';
sb._el('so-q').dispatch('input', { target: sb._el('so-q') });
check('clearing search restores 9 cards',
  count(sb._el('so-list').innerHTML, 'class="so-card"') === 9, true);

/* --- detail --------------------------------------------------------------- */
sb.location.hash = '#/orient/ORIENT-111';
sb._fire('hashchange');
main = sb._el('sp-main').innerHTML;
check('detail view mounts', main.includes('so--detail'), true);
check('detail markup balanced', balanced(main), true);
check('back link moved into the top bar',
  sb._el('sp-top-slot').innerHTML.includes('Orientation Module'), true);
check('bookmark control moved into the top bar',
  sb._el('sp-top-extra').innerHTML.includes('so-bookmark'), true);
check('detail hides menu / bell / avatar (mock bar)',
  sb._el('sp-menu').style.display === 'none' &&
  sb._el('sp-bell').style.display === 'none' &&
  sb._el('sp-avatar').style.display === 'none', true);
check('page title hidden while the back link shows',
  sb._el('sp-top-title').style.display === 'none', true);
check('requirement pill', main.includes('Mandatory (Required for Clearance)'), true);
check('meta line',
  main.includes('Video + Quiz &bull; 12 mins &bull; Office of Student Affairs &amp; Services'), true);
check('compliance deadline', main.includes('Compliance Deadline:') &&
  main.includes('October 31, 2026'), true);
check('summary section', main.includes('Summary / Overview Description'), true);
check('learning objectives parsed from the body', main.includes('Learning objectives'), true);
check('the body\u2019s "how to complete" copy is not repeated',
  main.includes('How to complete this module'), false);
check('content type section', main.includes('Content Type: Video Presentation'), true);
check('video player mock', main.includes('id="so-video"') && main.includes('00:00 / 12:00'), true);
check('completion requirement box',
  main.includes('Quiz Assessment Attached') &&
  main.includes('Passing score is required to complete this module.'), true);
check('passing rate and pending status',
  main.includes('Required Passing Rate') && main.includes('Pending Quiz Attempt'), true);
check('quiz CTA', main.includes('Take Module Quiz Assessment'), true);

/* --- bookmark ------------------------------------------------------------- */
sb._el('so-bookmark').dispatch('click');
check('bookmark stored',
  JSON.parse(sb.localStorage.getItem('beacon.osas.student.orientation'))
    .marks['ORIENT-111'] !== undefined, true);
check('bookmark class toggled', sb._el('so-bookmark').classList.contains('is-on'), true);

/* --- video player ---------------------------------------------------------- */
sb._el('so-video').dispatch('click');
check('video play button starts playback',
  sb._el('so-play').classList.contains('is-playing'), true);

/* --- quiz ------------------------------------------------------------------ */
sb.location.hash = '#/orient/ORIENT-111/quiz';
sb._fire('hashchange');
main = sb._el('sp-main').innerHTML;
check('quiz view mounts', main.includes('so--quiz'), true);
check('quiz markup balanced', balanced(main), true);
check('quiz back link label', sb._el('sp-top-slot').innerHTML.includes('Module Assessment Quiz'), true);
check('bookmark hidden on the quiz bar', sb._el('sp-top-extra').innerHTML, '');
check('timer chip starts at 15:00', main.includes('15:00 Left'), true);
check('assessment pills',
  main.includes('>Assessment<') && main.includes('Passing score required: 80%'), true);
check('question counter', main.includes('Question 1 of 8'), true);
check('question progress bar', main.includes('id="so-progress-fill" style="width:12.5%"'), true);
check('mock question 1 rendered', main.includes('primary focus of the University'), true);
check('four options rendered', count(main, 'data-opt=') === 4, true);
check('submit control', main.includes('SUBMIT ANSWER'), true);

/* answer all eight questions using the bank\u2019s correct option positions */
const ANSWER_POS = [1, 3, 0, 2, 3, 1, 2, 0];
sb._el('so-opt-' + ANSWER_POS[0]).dispatch('click');
check('picking an option marks it selected',
  sb._el('so-opt-' + ANSWER_POS[0]).classList.contains('so-opt--on') &&
  sb._el('so-opt-' + ANSWER_POS[0]).getAttribute('aria-checked') === 'true', true);
for (let i = 1; i < 8; i++) {
  sb._el('so-next').dispatch('click');        /* advance, then answer */
  sb._el('so-opt-' + ANSWER_POS[i]).dispatch('click');
}
check('advanced to the last question',
  sb._el('so-quiz-meta').innerHTML.includes('Question 8 of 8'), true);
check('last question rendered',
  sb._el('so-question').innerHTML.includes('commitment to its community'), true);

const logsBeforeOrient = sb.OSAS.store.all('auditLog').length;
sb._el('so-submit').dispatch('click');
main = sb._el('sp-main').innerHTML;
check('result view rendered', main.includes('so--result'), true);
check('result markup balanced', balanced(main), true);
check('perfect score', main.includes('100.0%') && main.includes('8 of 8 correct'), true);
check('passed pill', main.includes('so-pill--done">Passed'), true);
check('answer review lists every question', count(main, 'class="so-review__row') === 8, true);
check('no retake offered after passing', main.includes('RETAKE ASSESSMENT'), false);
check('attempt stored',
  JSON.parse(sb.localStorage.getItem('beacon.osas.student.orientation'))
    .attempts['ORIENT-111'].passed === true, true);
check('assessment written to the audit log',
  sb.OSAS.store.all('auditLog').length > logsBeforeOrient, true);

sb._fire('hashchange');   /* same hash — route again */
check('re-opening the quiz route shows the stored result',
  sb._el('sp-main').innerHTML.includes('so--result'), true);

sb.location.hash = '#/orient/ORIENT-111';
sb._fire('hashchange');
main = sb._el('sp-main').innerHTML;
check('detail shows the passed status',
  main.includes('Passed \u2014 Module Cleared') && main.includes('so-pill--done'), true);
check('CTA becomes the result link', main.includes('View my assessment result'), true);

sb.location.hash = '';
sb._fire('hashchange');
check('list card shows the passed attempt',
  sb._el('sp-main').innerHTML.includes('Passed 100.0%'), true);

/* --- failed attempt on a second module ------------------------------------- */
sb.location.hash = '#/orient/ORIENT-102/quiz';
sb._fire('hashchange');
check('second module quiz mounts on question 1',
  sb._el('so-quiz-meta').innerHTML.includes('Question 1 of 8'), true);
check('second module bank loaded',
  sb._el('so-question').innerHTML.includes('Student Code of Conduct'), true);
for (let i = 0; i < 8; i++) {
  sb._el('so-opt-' + ((ANSWER_POS[i] + 1) % 4)).dispatch('click');
  if (i < 7) { sb._el('so-next').dispatch('click'); }
}
sb._el('so-submit').dispatch('click');
main = sb._el('sp-main').innerHTML;
check('failed attempt recorded', main.includes('0.0%') && main.includes('Not Passed'), true);
check('retake offered after failing', main.includes('RETAKE ASSESSMENT'), true);

sb._el('so-retake').dispatch('click');
check('retake restarts on question 1',
  sb._el('so-quiz-meta').innerHTML.includes('Question 1 of 8'), true);

/* --- back navigation ------------------------------------------------------- */
sb._el('so-barback').dispatch('click');
check('quiz back link points at the module', sb.location.hash, '#/orient/ORIENT-102');
sb._fire('hashchange');
check('quiz back opens the detail', sb._el('sp-main').innerHTML.includes('so--detail'), true);
sb._el('so-barback').dispatch('click');
check('detail back returns to the list', sb.location.hash, '');
sb._fire('hashchange');
check('list mounts again', sb._el('sp-main').innerHTML.includes('so--list'), true);
check('list bar restored (title back, bell + avatar visible)',
  sb._el('sp-top-slot').style.display === 'none' &&
  sb._el('sp-bell').style.display === '' &&
  sb._el('sp-top-title').style.display === '', true);

console.log('\n--- orientation module (administrator session) ---');
sb = makeSandbox(ADMIN, 'orientation.html');
sb._load();
main = sb._el('sp-main').innerHTML;
check('admin is locked out', main.includes('ACCESS RESTRICTED'), true);
check('lock message uses the page title',
  main.includes('The Orientation module is for student accounts only'), true);
check('root flagged as locked', sb._el('sp-root').classList.contains('sp--locked'), true);
check('no module data leaked to admin', main.includes('University Vision, Mission'), false);
check('demo student escape hatch offered', main.includes('sp-demo-student'), true);

console.log('\n--- dashboard module (refactored onto the shared shell) ---');
sb = makeSandbox(STUDENT, 'dashboard.html');
sb._load();
main = sb._el('sp-main').innerHTML;
check('feed mounts', main.includes('sp-grid'), true);
check('welcome filled', sb._el('sp-welcome').innerHTML.includes('Ryan Dela Cruz'), true);
check('student id shown', sb._el('sp-welcome').innerHTML.includes('22-00104'), true);
check('announcement headline', sb._el('sp-ann-title').textContent, 'Midterm Examination Schedule Released');
check('orientation headline', sb._el('sp-orient-title').textContent, 'University Vision, Mission & Core Values');
check('bell badge', sb._el('sp-bell-count').textContent === '2', true);
check('dashboard keeps the brand logo in the bar',
  sb._elements['sp-shell'].innerHTML.includes('sp-top__inner--titled'), false);
check('dashboard nav active',
  sb._elements['sp-shell'].innerHTML.includes('href="dashboard.html" aria-current="page"'), true);
check('footer rendered by the shell', sb._elements['sp-shell'].innerHTML.includes('sp-foot'), true);

sb = makeSandbox(ADMIN, 'dashboard.html');
sb._load();
check('admin locked out of the dashboard too',
  sb._el('sp-main').innerHTML.includes('ACCESS RESTRICTED'), true);

console.log('\n--- announcements module (student session) ---');
sb = makeSandbox(STUDENT, 'announcements.html');
sb._load();
main = sb._el('sp-main').innerHTML;
check('list mounts', main.includes('sa--list'), true);
check('13 published student bulletins rendered', count(main, 'class="sa-card"') === 13, true);
check('newest bulletin first (ANNC-2026-085)',
  main.indexOf('ANNC-2026-085') < main.indexOf('ANNC-2026-070'), true);
check('date order continues (2025-09-16 -> 2025-09-14)',
  main.indexOf('ANNC-2026-070') < main.indexOf('ANNC-2026-072') &&
  main.indexOf('ANNC-2026-072') < main.indexOf('ANNC-2026-069'), true);
check('derived codes cover the 13 published bulletins',
  ['061', '062', '063', '064', '065', '066', '067', '068', '069', '070', '071', '072', '085']
    .every(n => main.includes('ANNC-2026-' + n)), true);
check('REF prefix on an ordinary bulletin', main.includes('REF: ANNC-2026-085'), true);
check('REV prefix on the revised bulletin', main.includes('REV: ANNC-2026-068'), true);
check('PUBLISHED pill on every card', count(main, '>Published</span>') === 13, true);
check('audience mapped to All Students', main.includes('All Students &bull;'), true);
check('author shortened to OSAS Admin', main.includes('&bull; OSAS Admin'), true);
check('freshman bulletin keeps its own audience',
  main.includes('Incoming Freshmen &bull;'), true);
check('archived bulletin hidden', main.includes('Campus Safety Reminders'), false);
check('scheduled bulletins hidden',
  main.includes('Intramurals 2026 Participation') && main.includes('Portal Maintenance Advisory'), false);
check('draft bulletins hidden', main.includes('Proposed Revision of the Student Grievance'), false);
check('faculty-only bulletin hidden', main.includes('Midterm Grade Submission Reminder'), false);
check('summary line rendered', main.includes('Official university schedule for First Semester A.Y. 2026-2027'), true);
check('search box in the tools row', main.includes('Search titles, tags, authors'), true);
check('filter + sort buttons in the tools row',
  main.includes('sa-filter-btn') && main.includes('sa-sort-btn'), true);
check('filter panel lists All + 8 categories', count(main, 'data-cat=') === 9, true);
check('sort panel lists 3 orders', count(main, 'data-sort=') === 3, true);
check('nav marks announcements active',
  sb._el('sp-shell').innerHTML.includes('href="announcements.html" aria-current="page"'), true);

/* --- search + filter ----------------------------------------------------- */
sb._el('sa-q').value = 'scholarship';
sb._el('sa-q').dispatch('input', { target: sb._el('sa-q') });
check('search by title narrows to 1 card',
  count(sb._el('sa-list').innerHTML, 'class="sa-card"') === 1, true);
check('search found the scholarship bulletin',
  sb._el('sa-list').innerHTML.includes('Scholarship &amp; Financial Assistance Application'), true);

sb._el('sa-q').value = 'ANNC-2026-070';
sb._el('sa-q').dispatch('input', { target: sb._el('sa-q') });
check('search by reference code works', count(sb._el('sa-list').innerHTML, 'class="sa-card"') === 1, true);

sb._el('sa-q').value = 'zzz-no-such-bulletin';
sb._el('sa-q').dispatch('input', { target: sb._el('sa-q') });
check('empty state for a miss', sb._el('sa-list').innerHTML.includes('No published announcement'), true);

sb._el('sa-q').value = '';
sb._el('sa-q').dispatch('input', { target: sb._el('sa-q') });
check('clearing search restores 13 cards',
  count(sb._el('sa-list').innerHTML, 'class="sa-card"') === 13, true);

/* --- audience gate ------------------------------------------------------- */
const midtermRow = sb.OSAS.store.all('announcements')
  .filter(r => r.code === 'ANN-2026-025')[0];
sb.OSAS.store.update('announcements', midtermRow.id, { audience: 'Faculty & Staff' });
sb.location.hash = '';
sb._fire('hashchange');
main = sb._el('sp-main').innerHTML;
check('bulletin retargeted at faculty drops off the student feed',
  count(main, 'class="sa-card"') === 12 && !main.includes('Midterm Examination Schedule Released'), true);
sb.OSAS.store.update('announcements', midtermRow.id, { audience: 'All Student Portals' });
sb._fire('hashchange');
check('bulletin returns when retargeted at students',
  count(sb._el('sp-main').innerHTML, 'class="sa-card"') === 13, true);

/* --- detail -------------------------------------------------------------- */
sb.location.hash = '#/ann/ANNC-2026-085';
sb._fire('hashchange');
main = sb._el('sp-main').innerHTML;
check('detail view mounted', main.includes('sa--detail'), true);
check('back link moved into the top bar',
  sb._el('sp-top-slot').innerHTML.includes('Official Announcement'), true);
check('bookmark control moved into the top bar',
  sb._el('sp-top-extra').innerHTML.includes('sa-bookmark'), true);
check('page title hidden while the back link shows',
  sb._el('sp-top-title').style.display === 'none', true);
check('detail hides menu / bell / avatar (mock bar)',
  sb._el('sp-menu').style.display === 'none' &&
  sb._el('sp-bell').style.display === 'none' &&
  sb._el('sp-avatar').style.display === 'none', true);
check('reference shown', main.includes('REF: ANNC-2026-085'), true);
check('title shown', main.includes('Midterm Examination Schedule Released'), true);
check('meta line (audience · date · publisher)',
  main.includes('All Students &bull; Sep 16, 2026 &bull; Published by: OSAS Admin'), true);
check('memorandum body rendered',
  main.includes('Dear Student Body,') && main.includes('Important Reminders and Campus Protocols'), true);
check('numbered protocols parsed from the store body',
  main.includes('Student Identification:') && main.includes('Examination Permits:'), true);
check('attached documents section with 1 file',
  main.includes('Attached Documents (1)') &&
  main.includes('Midterm_Exam_Room_Assignments.pdf') && main.includes('5.2 MB'), true);
check('acknowledge button present',
  main.includes('Acknowledge &amp; Save to Calendar'), true);
check('detail markup is balanced', balanced(main), true);

/* --- attachment preview -------------------------------------------------- */
sb._el('sa-dl-0').dispatch('click');
check('attachment preview modal opens with the file name',
  sb._el('modal-root').children.some(c => c.innerHTML.includes('Midterm_Exam_Room_Assignments.pdf')), true);

/* --- bookmark ----------------------------------------------------------- */
sb._el('sa-bookmark').dispatch('click');
check('bookmark stored',
  JSON.parse(sb.localStorage.getItem('beacon.osas.student.announcements'))
    .mark['ANNC-2026-085'] !== undefined, true);
check('bookmark class toggled', sb._el('sa-bookmark').classList.contains('is-on'), true);

/* --- acknowledge --------------------------------------------------------- */
const logsBeforeAnn = sb.OSAS.store.all('auditLog').length;
sb._el('sa-ack').dispatch('click');
main = sb._el('sp-main').innerHTML;
check('acknowledged state rendered', main.includes('Acknowledged &amp; saved to your calendar'), true);
check('ack stored per bulletin',
  JSON.parse(sb.localStorage.getItem('beacon.osas.student.announcements'))
    .ack['ANNC-2026-085'] !== undefined, true);
check('acknowledgement written to the audit log',
  sb.OSAS.store.all('auditLog').length > logsBeforeAnn, true);

/* --- back to list -------------------------------------------------------- */
sb._el('sa-barback').dispatch('click');
sb._fire('hashchange');
check('back returns to the list', sb._el('sp-main').innerHTML.includes('sa--list'), true);
check('all 13 cards after back',
  count(sb._el('sp-main').innerHTML, 'class="sa-card"') === 13, true);
check('acknowledged bulletin flagged on its card',
  sb._el('sp-main').innerHTML.includes('sa-card__ack'), true);
check('list bar restored (title back, bell + avatar visible)',
  sb._el('sp-top-slot').style.display === 'none' &&
  sb._el('sp-bell').style.display === '' &&
  sb._el('sp-top-title').style.display === '', true);

console.log('\n--- announcements module (administrator session) ---');
sb = makeSandbox(ADMIN, 'announcements.html');
sb._load();
main = sb._el('sp-main').innerHTML;
check('admin is locked out', main.includes('ACCESS RESTRICTED'), true);
check('lock message uses the page title',
  main.includes('The Announcements module is for student accounts only'), true);
check('root flagged as locked', sb._el('sp-root').classList.contains('sp--locked'), true);
check('no bulletin data leaked to admin',
  main.includes('Midterm Examination Schedule Released') === false &&
  main.includes('Dear Student Body') === false, true);
check('demo student escape hatch offered', main.includes('sp-demo-student'), true);

console.log('\n--- events module (student session) ---');
sb = makeSandbox(STUDENT, 'events.html');
sb._load();
main = sb._el('sp-main').innerHTML;
check('list mounts', main.includes('se--list'), true);
check('9 student-facing events rendered', count(main, 'class="se-card"') === 9, true);
check('phase pills rendered (Ongoing / Upcoming / Completed)',
  main.includes('se-pill--ongoing">Ongoing</span>') &&
  main.includes('se-pill--upcoming">Upcoming</span>') &&
  main.includes('se-pill--completed">Completed</span>'), true);
check('in-window event flagged Ongoing first (demo clock)',
  main.indexOf('se-pill--ongoing">Ongoing') < main.indexOf('se-pill--upcoming">Upcoming'), true);
check('search box in the tools row', main.includes('Search title, venue, or code'), true);
check('phase chips with the live total',
  main.includes('All Events (9)') && main.includes('>Upcoming</button>'), true);
check('four chip controls', count(main, 'data-phase=') === 4, true);
check('card shows the reference code', main.includes('EVT-2026-011'), true);
check('date + time line matches the mock',
  main.includes('Sep 16, 2026 · 08:00 AM - 05:00 PM'), true);
check('venue line rendered', main.includes('Campus Grounds &amp; Activity Center'), true);
check('view-details button on every card', count(main, 'View Details') === 9, true);
check('code order (011 first, then 012, 013)',
  main.indexOf('EVT-2026-011') < main.indexOf('EVT-2026-012') &&
  main.indexOf('EVT-2026-012') < main.indexOf('EVT-2026-013'), true);
check('completed activities stay in the feed',
  main.includes('Anti-Bullying Awareness Campaign') &&
  main.includes('Campus Clean-Up and Tree Planting Drive'), true);
check('nav marks events active',
  sb._el('sp-shell').innerHTML.includes('href="events.html" aria-current="page"'), true);

/* --- search -------------------------------------------------------------- */
sb._el('se-q').value = 'oval';
sb._el('se-q').dispatch('input', { target: sb._el('se-q') });
check('search by venue narrows to 1 card',
  count(sb._el('se-list').innerHTML, 'class="se-card"') === 1 &&
  sb._el('se-list').innerHTML.includes('Intramurals 2026 Opening Ceremony'), true);

sb._el('se-q').value = 'EVT-2026-014';
sb._el('se-q').dispatch('input', { target: sb._el('se-q') });
check('search by code works',
  count(sb._el('se-list').innerHTML, 'class="se-card"') === 1 &&
  sb._el('se-list').innerHTML.includes('Mental Health Awareness Seminar'), true);

sb._el('se-q').value = 'zzz-no-such-event';
sb._el('se-q').dispatch('input', { target: sb._el('se-q') });
check('empty state for a miss', sb._el('se-list').innerHTML.includes('No event matches'), true);

sb._el('se-q').value = '';
sb._el('se-q').dispatch('input', { target: sb._el('se-q') });
check('clearing search restores 9 cards',
  count(sb._el('se-list').innerHTML, 'class="se-card"') === 9, true);

/* --- phase chips --------------------------------------------------------- */
sb._el('se-chip-completed').dispatch('click');
check('completed chip filters to the 2 finished events',
  count(sb._el('se-list').innerHTML, 'class="se-card"') === 2, true);
check('active chip marked', sb._el('se-chip-completed').classList.contains('is-on'), true);
sb._el('se-chip-ongoing').dispatch('click');
check('ongoing chip filters to the 1 running event',
  count(sb._el('se-list').innerHTML, 'class="se-card"') === 1 &&
  sb._el('se-list').innerHTML.includes('OSAS Student Services'), true);
sb._el('se-chip-all').dispatch('click');
check('all chip restores every event',
  count(sb._el('se-list').innerHTML, 'class="se-card"') === 9, true);

/* --- audience gate ------------------------------------------------------- */
const fairRow = sb.OSAS.store.all('events').filter(r => r.code === 'EVT-2026-011')[0];
sb.OSAS.store.update('events', fairRow.id, { audience: 'Faculty & Staff' });
sb._fire('hashchange');
main = sb._el('sp-main').innerHTML;
check('faculty-only event drops off the student feed',
  count(main, 'class="se-card"') === 8 && !main.includes('OSAS Student Services'), true);
sb.OSAS.store.update('events', fairRow.id, { audience: 'All Students' });
sb._fire('hashchange');
check('event returns when retargeted at students',
  count(sb._el('sp-main').innerHTML, 'class="se-card"') === 9, true);

/* --- detail -------------------------------------------------------------- */
sb.location.hash = '#/evt/EVT-2026-011';
sb._fire('hashchange');
main = sb._el('sp-main').innerHTML;
check('detail view mounted', main.includes('se--detail'), true);
check('back link moved into the top bar',
  sb._el('sp-top-slot').innerHTML.includes('Event Details'), true);
check('share control moved into the top bar',
  sb._el('sp-top-extra').innerHTML.includes('se-share'), true);
check('page title hidden while the back link shows',
  sb._el('sp-top-title').style.display === 'none', true);
check('detail hides menu / bell / avatar (mock bar)',
  sb._el('sp-menu').style.display === 'none' &&
  sb._el('sp-bell').style.display === 'none' &&
  sb._el('sp-avatar').style.display === 'none', true);
check('promo banner with the category eyebrow + organizing office',
  main.includes('se-hero') && main.includes('se-hero__eyebrow">Student Organizations') &&
  main.includes('Office of Student Affairs and Services'), true);
check('reference + audience + status row',
  main.includes('se-code">EVT-2026-011') && main.includes('Open to All Students') &&
  main.includes('se-pill--ongoing">Ongoing</span>'), true);
check('event title below the banner',
  main.includes('se-title">OSAS Student Services &amp; Organization Fair'), true);
check('event date box',
  main.includes('Event Date') && main.includes('September 16, 2026'), true);
check('time duration box',
  main.includes('Time Duration') && main.includes('08:00 AM - 05:00 PM'), true);
check('venue box',
  main.includes('Location / Venue') && main.includes('Campus Grounds &amp; Activity Center'), true);
check('about + objectives section',
  main.includes('About the Event &amp; Objectives') &&
  main.includes('A one-day fair where accredited organizations'), true);
check('organized by label', main.includes('Organized By'), true);
check('attached media with the poster',
  main.includes('Attached Media &amp; Promo') &&
  main.includes('osas_services_fair_banner_2026.png') &&
  main.includes('Event Poster | Banner Attached'), true);
check('detail markup is balanced', balanced(main), true);

/* --- media preview + share ----------------------------------------------- */
sb._el('se-dl-0').dispatch('click');
check('promo preview modal opens with the file name',
  sb._el('modal-root').children.some(c => c.innerHTML.includes('osas_services_fair_banner_2026.png')), true);
sb._el('se-share').dispatch('click');
check('share offers the event link',
  sb._el('toast-root').children.some(c => c.innerHTML.includes('Share this event')), true);

/* --- other detail shapes ------------------------------------------------- */
sb.location.hash = '#/evt/EVT-2026-013';
sb._fire('hashchange');
main = sb._el('sp-main').innerHTML;
check('multi-day event shows a date range', main.includes('Sep 25 – Sep 27, 2026'), true);
check('event without promo media hides that section', !main.includes('Attached Media'), true);

sb.location.hash = '#/evt/EVT-2026-012';
sb._fire('hashchange');
main = sb._el('sp-main').innerHTML;
check('freshman event keeps its own audience',
  main.includes('Open to Incoming Freshmen'), true);
check('single-day date box', main.includes('September 22, 2026'), true);
check('scheduled event reads Upcoming', main.includes('se-pill--upcoming">Upcoming</span>'), true);

/* --- back to list -------------------------------------------------------- */
sb._el('se-barback').dispatch('click');
sb._fire('hashchange');
check('back returns to the list', sb._el('sp-main').innerHTML.includes('se--list'), true);
check('all 9 cards after back',
  count(sb._el('sp-main').innerHTML, 'class="se-card"') === 9, true);
check('list bar restored (title back, bell + avatar visible)',
  sb._el('sp-top-slot').style.display === 'none' &&
  sb._el('sp-bell').style.display === '' &&
  sb._el('sp-top-title').style.display === '', true);

console.log('\n--- events module (administrator session) ---');
sb = makeSandbox(ADMIN, 'events.html');
sb._load();
main = sb._el('sp-main').innerHTML;
check('admin is locked out', main.includes('ACCESS RESTRICTED'), true);
check('lock message uses the page title',
  main.includes('The Events &amp; Activities module is for student accounts only'), true);
check('root flagged as locked', sb._el('sp-root').classList.contains('sp--locked'), true);
check('no event data leaked to admin',
  main.includes('OSAS Student Services') === false &&
  main.includes('se--detail') === false, true);
check('demo student escape hatch offered', main.includes('sp-demo-student'), true);

console.log('\n--- feedback & support module (student session) ---');
sb = makeSandbox(STUDENT, 'support.html');
sb._load();
main = sb._el('sp-main').innerHTML;
check('list mounts', main.includes('sf--list'), true);
check("only the signed-in student's tickets are listed",
  count(main, 'class="sf-card"') === 3, true);
check('newest ticket first',
  main.indexOf('TCS-2026-009') < main.indexOf('TCS-2026-008') &&
  main.indexOf('TCS-2026-008') < main.indexOf('TCS-2026-007'), true);
check("another student's ticket never listed",
  !main.includes('TCS-2026-006') && !main.includes('Cannot activate new portal account'), true);
check('create-new-ticket button in the tools row', main.includes('Create New Ticket'), true);
check('search box in the tools row',
  main.includes('Search by ticket ID, subject, or keyword'), true);
check('four ticket tabs with Support Tickets active',
  count(main, 'data-tab=') === 4 &&
  main.includes('class="sf-tab is-on" id="sf-tab-all"'), true);
check('status pills rendered (Open / In Progress / Resolved)',
  main.includes('badge badge--open') && main.includes('badge badge--progress') &&
  main.includes('badge badge--resolved'), true);
check('card meta matches the mock (date · student)',
  main.includes('Sep 16, 2026 · Ryan Dela Cruz'), true);
check('satisfaction stars per card (3 + 4 + 5)',
  count(main, 'sf-star is-on') === 12, true);
check('view button on every card', count(main, 'View') === 3, true);
check('nav marks feedback active',
  sb._el('sp-shell').innerHTML.includes('href="support.html" aria-current="page"'), true);

/* --- search -------------------------------------------------------------- */
sb._el('sf-q').value = 'quiz';
sb._el('sf-q').dispatch('input', { target: sb._el('sf-q') });
check('search by keyword narrows to 1 card',
  count(sb._el('sf-list').innerHTML, 'class="sf-card"') === 1 &&
  sb._el('sf-list').innerHTML.includes('Module 4 quiz video loading error'), true);

sb._el('sf-q').value = 'TCS-2026-007';
sb._el('sf-q').dispatch('input', { target: sb._el('sf-q') });
check('search by ticket ID works',
  count(sb._el('sf-list').innerHTML, 'class="sf-card"') === 1 &&
  sb._el('sf-list').innerHTML.includes('certificate of registration'), true);

sb._el('sf-q').value = 'zzz-no-such-ticket';
sb._el('sf-q').dispatch('input', { target: sb._el('sf-q') });
check('empty state for a miss', sb._el('sf-list').innerHTML.includes('No ticket matches'), true);

sb._el('sf-q').value = '';
sb._el('sf-q').dispatch('input', { target: sb._el('sf-q') });
check('clearing search restores 3 cards',
  count(sb._el('sf-list').innerHTML, 'class="sf-card"') === 3, true);

/* --- status tabs --------------------------------------------------------- */
sb._el('sf-tab-open').dispatch('click');
check('open tab filters to the open ticket',
  count(sb._el('sf-list').innerHTML, 'class="sf-card"') === 1 &&
  sb._el('sf-list').innerHTML.includes('TCS-2026-009'), true);
check('active tab marked', sb._el('sf-tab-open').classList.contains('is-on'), true);

sb._el('sf-tab-progress').dispatch('click');
check('in-progress tab filters to its ticket',
  count(sb._el('sf-list').innerHTML, 'class="sf-card"') === 1 &&
  sb._el('sf-list').innerHTML.includes('TCS-2026-008'), true);

sb._el('sf-tab-resolved').dispatch('click');
check('resolved tab filters to its ticket',
  count(sb._el('sf-list').innerHTML, 'class="sf-card"') === 1 &&
  sb._el('sf-list').innerHTML.includes('TCS-2026-007'), true);

sb._el('sf-tab-all').dispatch('click');
check('all tab restores every ticket',
  count(sb._el('sf-list').innerHTML, 'class="sf-card"') === 3, true);

/* --- ownership gate ------------------------------------------------------ */
sb.location.hash = '#/ticket/TCS-2026-006';
sb._fire('hashchange');
main = sb._el('sp-main').innerHTML;
check("another student's ticket cannot be opened by URL",
  main.includes('sf--list') && !main.includes('sf--detail'), true);

/* --- detail -------------------------------------------------------------- */
sb.location.hash = '#/ticket/TCS-2026-009';
sb._fire('hashchange');
main = sb._el('sp-main').innerHTML;
check('detail view mounted', main.includes('sf--detail'), true);
check('back link moved into the top bar',
  sb._el('sp-top-slot').innerHTML.includes('Ticket Details'), true);
check('page title hidden while the back link shows',
  sb._el('sp-top-title').style.display === 'none', true);
check('detail hides menu / bell / avatar (mock bar)',
  sb._el('sp-menu').style.display === 'none' &&
  sb._el('sp-bell').style.display === 'none' &&
  sb._el('sp-avatar').style.display === 'none', true);
check('reference + status + subject',
  main.includes('sf-code">TCS-2026-009') &&
  main.includes('badge badge--open">Open</span>') &&
  main.includes('sf-ticket__title">Module 4 quiz video loading error'), true);
check('category under the title', main.includes('sf-ticket__cat">LMS Access Issues'), true);
check('date submitted box',
  main.includes('Date Submitted') && main.includes('Sep 16, 2026'), true);
check('last updated box with time',
  main.includes('Last Updated') && main.includes('Sep 16, 2026 · 09:41 AM'), true);
check('rating box matches the mock',
  main.includes('Satisfaction / Evaluation Rating') &&
  main.includes('3.0 / 5.0 (Fair)'), true);
check('three filled stars on a 3/5 rating', count(main, 'sf-star is-on') === 3, true);
check('issue description section',
  main.includes('Issue Description') &&
  main.includes('The video player inside the Module 4 quiz'), true);
check('auto-response posted by support',
  main.includes('Support Responses &amp; Updates') &&
  main.includes('LMS Support System (Auto-Response)') &&
  main.includes('Ticket received by OSAS Support'), true);
check('student message in the thread',
  main.includes('so I cannot answer the last two items'), true);
check('reply box in the mock',
  main.includes('Type your message or additional details here'), true);
check('no attachment section without a file', !main.includes('Attached File'), true);
check('detail markup is balanced', balanced(main), true);

/* --- reply --------------------------------------------------------------- */
sb._el('sf-send').dispatch('click');
check('empty reply blocked with a warning',
  sb._el('sf-reply-alert').innerHTML.includes('Nothing to send'), true);

sb._el('sf-reply').value = 'I can confirm the issue also happens on the student mobile app.';
sb._el('sf-send').dispatch('click');
main = sb._el('sp-main').innerHTML;
check('reply appended to the thread',
  main.includes('I can confirm the issue also happens on the student mobile app.'), true);
check('reply toast shown',
  sb._el('toast-root').children.some(c => c.innerHTML.includes('Reply sent')), true);
check('reply stored on the ticket record',
  sb.OSAS.store.all('tickets').filter(r => r.code === 'TCS-2026-009')[0].messages.length === 3, true);

/* --- back to list -------------------------------------------------------- */
sb._el('sf-barback').dispatch('click');
sb._fire('hashchange');
check('back returns to the list', sb._el('sp-main').innerHTML.includes('sf--list'), true);
check('list bar restored (title back, bell + avatar visible)',
  sb._el('sp-top-slot').style.display === 'none' &&
  sb._el('sp-bell').style.display === '' &&
  sb._el('sp-top-title').style.display === '', true);

/* --- create ticket ------------------------------------------------------- */
sb.location.hash = '#/create';
sb._fire('hashchange');
main = sb._el('sp-main').innerHTML;
check('create view mounted', main.includes('sf--create'), true);
check('back link labelled with the module name',
  sb._el('sp-top-slot').innerHTML.includes('Feedback & Support'), true);
check('heading + subtitle match the mock',
  main.includes('sf-create__h">Create Support Ticket') &&
  main.includes('Submit a concern or suggestion to the OSAS Support Center'), true);
check('category select lists every ticket category',
  count(main, '<option') === 7, true);
check('subject placeholder matches the mock',
  main.includes('e.g., Unable to submit Module 4 quiz'), true);
check('rating widget with five stars', count(main, 'class="sf-pick"') === 5, true);
check('rating starts unset', main.includes('0.0 / 5.0 — tap a star to rate'), true);
check('description placeholder matches the mock',
  main.includes('Describe your issue or feedback in detail...'), true);
check('attachment zone matches the mock', main.includes('up to 10MB'), true);
check('submit + cancel buttons',
  main.includes('id="sf-submit"') && main.includes('id="sf-cancel"'), true);

sb._el('sf-submit').dispatch('click');
check('empty form blocked: category required',
  sb._el('sf-alert').innerHTML.includes('Category required'), true);

sb._el('sf-category').value = 'LMS Access Issues';
sb._el('sf-subject').value = 'Video buffer stuck on Module 6 quiz';
sb._el('sf-desc').value = 'The same buffering problem happens on the Module 6 quiz since this morning.';
sb._el('sf-submit').dispatch('click');
check('rating still required before submit',
  sb._el('sf-alert').innerHTML.includes('Rating required'), true);

sb._el('sf-star-4').dispatch('click');
check('star picker sets the rating line',
  sb._el('sf-rating').innerHTML.includes('4.0 / 5.0 (Good)'), true);
check('four stars filled in the picker',
  sb._el('sf-star-4').classList.contains('is-on') === true &&
  sb._el('sf-star-5').classList.contains('is-on') === false, true);

sb._el('sf-file').dispatch('change', { target: { files: [{ name: 'module6_buffer.png' }] } });
check('attachment name captured',
  sb._el('sf-file-name').textContent === 'module6_buffer.png', true);

sb._el('sf-submit').dispatch('click');
check('new ticket stored with the student identity',
  sb.OSAS.store.all('tickets').some(r => r.code === 'TCS-2026-010' &&
    r.studentId === '22-00104' && r.rating === 4 &&
    r.attachment === 'module6_buffer.png'), true);
check('auto-response queued on the new ticket',
  sb.OSAS.store.all('tickets').filter(r => r.code === 'TCS-2026-010')[0].messages.length === 2, true);
check('success toast shown',
  sb._el('toast-root').children.some(c => c.innerHTML.includes('Ticket submitted')), true);
check('lands on the new ticket detail after submit',
  sb.location.hash, '#/ticket/TCS-2026-010');

sb._fire('hashchange');
main = sb._el('sp-main').innerHTML;
check('new ticket detail rendered',
  main.includes('sf-code">TCS-2026-010') && main.includes('sf--detail'), true);
check('attachment row appears when a file was attached',
  main.includes('Attached File') && main.includes('module6_buffer.png'), true);
check('auto-response visible on the new ticket',
  main.includes('OSAS Support (Auto-Response)'), true);

sb.location.hash = '';
sb._fire('hashchange');
check('new ticket joins the list (4 cards)',
  count(sb._el('sp-main').innerHTML, 'class="sf-card"') === 4, true);

/* --- dashboard entry point ----------------------------------------------- */
sb = makeSandbox(STUDENT, 'dashboard.html');
sb._load();
check('dashboard feedback card points at the student module',
  sb._el('sp-main').innerHTML.includes('href="support.html"'), true);

console.log('\n--- feedback & support module (administrator session) ---');
sb = makeSandbox(ADMIN, 'support.html');
sb._load();
main = sb._el('sp-main').innerHTML;
check('admin is locked out', main.includes('ACCESS RESTRICTED'), true);
check('lock message uses the page title',
  main.includes('The Feedback &amp; Support module is for student accounts only'), true);
check('root flagged as locked', sb._el('sp-root').classList.contains('sp--locked'), true);
check('no ticket data leaked to admin',
  main.includes('Module 4 quiz video loading error') === false &&
  main.includes('sf--detail') === false, true);
check('demo student escape hatch offered', main.includes('sp-demo-student'), true);

console.log('\n--- login module ---');
const LOGIN_SCRIPTS = ['assets/js/seed.js', 'assets/js/core.js', 'assets/js/components.js',
  'assets/js/forms.js', 'assets/js/pages/login.js'];

/* "Remember this device": the stored session must survive a return to this page */
sb = makeSandbox(null, 'index.html', LOGIN_SCRIPTS);
sb.localStorage.setItem('beacon.osas.session',
  JSON.stringify({ userId: ADMIN, role: 'Administrator', signedInAt: '2026-09-16T09:00:00' }));
sb.localStorage.setItem('beacon.osas.portal.v13', '{"stale":true}');
sb._load();
check('remembered session skips the sign-in screen',
  sb.location.href, 'dashboard.html');
check('remembered session key survives the stale-data wipe',
  Boolean(sb.localStorage.getItem('beacon.osas.session')), true);
check('stale portal payload wiped before reseed',
  String(sb.localStorage.getItem('beacon.osas.portal.v13')).includes('"stale":true'), false);

/* fresh visit: brand panel uses the logo from the logo folder */
sb = makeSandbox(null, 'index.html', LOGIN_SCRIPTS);
sb._load();
var loginBrand = sb._el('auth-brand').innerHTML;
check('brand panel shows the logo from the logo folder',
  loginBrand.includes('auth__logo') &&
  loginBrand.includes('logo/5125ce0d-87ff-402c-a1b4-d5c370f44713.png'), true);
check('inline lighthouse svg + empty wordmark replaced',
  !loginBrand.includes('beacon-mark') && !loginBrand.includes('auth__wordmark'), true);
check('brand keeps the rule and rotating tagline',
  loginBrand.includes('auth__rule') && loginBrand.includes('auth__tag'), true);
check('administrator credentials prefilled (autoAdmin)',
  sb._el('email').value === 'josefina.reyes@uc.edu.ph' &&
  sb._el('password').value === 'Beacon@2026', true);

/* the remember-me checkbox drives session persistence on sign-in */
sb.document.getElementById('remember').checked = true;
sb._el('login-form').dispatch('submit', { preventDefault() {} });
check('sign-in with the checkbox checked stores the session',
  Boolean(sb.localStorage.getItem('beacon.osas.session')), true);

/* remember-me row styling must not be flattened by the field-label rule */
var appCss = fs.readFileSync('assets/css/app.css', 'utf8');
check('field-label rule skips the checkbox row',
  appCss.includes('label:not(.checkbox)'), true);
check('checkbox row keeps its flex layout',
  appCss.includes('.auth .checkbox{display:flex'), true);
check('remember checkbox present in the sign-in form',
  fs.readFileSync('index.html', 'utf8').includes('id="remember"'), true);

console.log('\n--- signed out ---');
sb = makeSandbox(null, 'rules.html');
sb._load();
check('bounces to login keeping the nested path',
  sb.location.href, '../index.html?next=student%2Frules.html');

sb = makeSandbox(null, 'announcements.html');
sb._load();
check('announcements page also bounces to login',
  sb.location.href, '../index.html?next=student%2Fannouncements.html');

sb = makeSandbox(null, 'events.html');
sb._load();
check('events page also bounces to login',
  sb.location.href, '../index.html?next=student%2Fevents.html');

sb = makeSandbox(null, 'support.html');
sb._load();
check('support page also bounces to login',
  sb.location.href, '../index.html?next=student%2Fsupport.html');

console.log(pass ? '\nALL CHECKS PASSED' : '\nSOME CHECKS FAILED');
process.exit(pass ? 0 : 1);
