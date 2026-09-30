/* ==========================================================================
   BEACON OSAS Portal — core runtime
   Namespace, configuration, formatters, utilities, persistent store, auth.
   Loaded on every page before components.js / shell.js / page scripts.
   ========================================================================== */
window.OSAS = window.OSAS || {};

/* ---------------------------------------------------------------- config */
OSAS.CONFIG = {
  /* 'local'  -> browser localStorage demo database (default, zero setup)
     'api'    -> REST backend in /api (PHP + MySQL, see database/schema.sql)   */
  dataMode: 'local',
  apiBase: 'api/index.php',
  storageKey: 'beacon.osas.portal.v4',
  resetKey: 'beacon.osas.reset',
  sessionKey: 'beacon.osas.session',
  demoPassword: 'Beacon@2026',
  pageSize: 8,
  /* Prototype only: the sign-in form is pre-filled with the administrator
     account, so the portal can be demoed with a single click on Login.
     Set to false to start with a blank form. */
  autoAdmin: true
};

/* ------------------------------------------------------------- formatters */
OSAS.fmt = (function () {
  var MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  var MONTHS_LONG = ['January', 'February', 'March', 'April', 'May', 'June', 'July',
    'August', 'September', 'October', 'November', 'December'];

  function date(value) {
    if (!value) { return '—'; }
    var d = new Date(value);
    if (isNaN(d.getTime())) { return String(value); }
    return MONTHS_LONG[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear();
  }

  function shortDate(value) {
    if (!value) { return '—'; }
    var d = new Date(value);
    if (isNaN(d.getTime())) { return String(value); }
    return MONTHS[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear();
  }

  function time(value) {
    if (!value) { return '—'; }
    var d = new Date(value);
    if (isNaN(d.getTime())) { return String(value); }
    var h = d.getHours(), m = d.getMinutes(), suffix = h >= 12 ? 'PM' : 'AM';
    var hh = h % 12; if (hh === 0) { hh = 12; }
    return hh + ':' + String(m).padStart(2, '0') + ' ' + suffix;
  }

  function dateTime(value) { return value ? shortDate(value) + ' ' + time(value) : '—'; }

  function longDateTime(value) {
    return value ? MONTHS_LONG[new Date(value).getMonth()] + ' ' + new Date(value).getDate() + ', ' +
      new Date(value).getFullYear() + ' · ' + time(value) : '—';
  }

  function range(start, end) {
    if (!start) { return '—'; }
    if (!end || start === end) { return dateTime(start); }
    var a = new Date(start), b = new Date(end);
    if (a.toDateString() === b.toDateString()) { return shortDate(start) + ' ' + time(start) + ' – ' + time(end); }
    var crossesMonth = a.getMonth() !== b.getMonth();
    var tail = crossesMonth ? shortDate(end) : MONTHS[b.getMonth()] + ' ' + b.getDate() + ', ' + b.getFullYear();
    return shortDate(start) + ' – ' + tail;
  }

  function number(value) {
    if (value === null || value === undefined || value === '') { return '0'; }
    return Number(value).toLocaleString('en-US');
  }

  function hoursAgo(value) {
    if (!value) { return '—'; }
    var diff = (new Date(OSAS.SEED_NOW).getTime() - new Date(value).getTime()) / 3600000;
    if (diff <= 0.02) { return 'Just now'; }
    if (diff < 1) { return Math.max(1, Math.round(diff * 60)) + ' mins ago'; }
    if (diff < 24) { var h = Math.round(diff); return h + (h === 1 ? ' hr ago' : ' hrs ago'); }
    if (diff < 48) { return 'Yesterday'; }
    return Math.round(diff / 24) + ' days ago';
  }

  function initials(firstName, lastName) {
    var a = (firstName || '').trim().charAt(0);
    var b = (lastName || '').trim().charAt(0);
    return (a + b).toUpperCase() || 'UC';
  }

  function initialsOf(fullName) {
    var parts = String(fullName || '').trim().split(/\s+/);
    return ((parts[0] || '').charAt(0) + (parts[1] || '').charAt(0)).toUpperCase() || 'UC';
  }

  function fileSizeLabel(count) { return count === 1 ? '1 file' : count + ' files'; }
  function duration(minutes) { return minutes ? minutes + ' mins' : '—'; }
  function percent(value, digits) {
    var n = Number(value) || 0;
    return n.toFixed(digits === 1 ? 1 : 0) + '%';
  }

  return {
    MONTHS: MONTHS, MONTHS_LONG: MONTHS_LONG,
    date: date, shortDate: shortDate, time: time, dateTime: dateTime, longDateTime: longDateTime,
    range: range, number: number, hoursAgo: hoursAgo, initials: initials, initialsOf: initialsOf,
    fileSizeLabel: fileSizeLabel, duration: duration, percent: percent
  };
})();
OSAS.SEED_NOW = '2026-09-16T09:45:00';
/* ------------------------------------------------------------ utilities */
OSAS.util = (function () {
  function esc(value) {
    return String(value === null || value === undefined ? '' : value)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }
  function clone(value) { return JSON.parse(JSON.stringify(value)); }
  function uid(prefix) {
    return (prefix || 'ID') + '-' + Date.now().toString(36).toUpperCase() +
      Math.floor(Math.random() * 900 + 100);
  }
  function now() {
    var d = new Date();
    function p(v) { return String(v).padStart(2, '0'); }
    return d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate()) + 'T' +
      p(d.getHours()) + ':' + p(d.getMinutes()) + ':' + p(d.getSeconds());
  }
  function contains(haystack, needle) {
    return String(haystack || '').toLowerCase().indexOf(String(needle || '').toLowerCase()) > -1;
  }
  function search(records, term, fields) {
    if (!term) { return records; }
    return records.filter(function (row) {
      return fields.some(function (f) { return contains(row[f], term); });
    });
  }
  function byStatus(records, status, field) {
    if (!status || status === 'All') { return records; }
    return records.filter(function (row) { return row[field || 'status'] === status; });
  }
  function sortBy(records, field, direction) {
    var dir = direction === 'asc' ? 1 : -1;
    return records.slice().sort(function (a, b) {
      var x = a[field] || '', y = b[field] || '';
      if (typeof x === 'number' && typeof y === 'number') { return (x - y) * dir; }
      return String(x).localeCompare(String(y)) * dir;
    });
  }
  function paginate(records, page, size) {
    var per = size || OSAS.CONFIG.pageSize;
    var totalPages = Math.max(1, Math.ceil(records.length / per));
    var current = Math.min(Math.max(1, page || 1), totalPages);
    var start = (current - 1) * per;
    return { items: records.slice(start, start + per), page: current, pages: totalPages, total: records.length, from: records.length ? start + 1 : 0, to: Math.min(start + per, records.length) };
  }
  function escapeCsv(value) {
    var text = String(value === null || value === undefined ? '' : value);
    if (/[",\n]/.test(text)) { return '"' + text.replace(/"/g, '""') + '"'; }
    return text;
  }
  function download(filename, content, mime) {
    var blob = new Blob([content], { type: mime || 'text/plain;charset=utf-8' });
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url; a.download = filename;
    document.body.appendChild(a); a.click();
    document.body.removeChild(a);
    setTimeout(function () { URL.revokeObjectURL(url); }, 1500);
  }
  function exportCsv(filename, columns, rows) {
    var lines = [columns.map(function (c) { return escapeCsv(c.label); }).join(',')];
    rows.forEach(function (row) {
      lines.push(columns.map(function (c) { return escapeCsv(typeof c.value === 'function' ? c.value(row) : row[c.value]); }).join(','));
    });
    download(filename, '\ufeff' + lines.join('\r\n'), 'text/csv;charset=utf-8');
  }
  function debounce(fn, wait) {
    var timer = null;
    return function () {
      var args = arguments, ctx = this;
      clearTimeout(timer);
      timer = setTimeout(function () { fn.apply(ctx, args); }, wait || 220);
    };
  }
  function qs(selector, root) { return (root || document).querySelector(selector); }
  function qsa(selector, root) { return Array.prototype.slice.call((root || document).querySelectorAll(selector)); }
  function stripTags(html) { return String(html || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim(); }
  function truncate(text, length) {
    var value = stripTags(text);
    return value.length > length ? value.slice(0, length - 1).trim() + '…' : value;
  }
  function monthYear(value) {
    if (!value) { return '—'; }
    var d = new Date(value);
    return OSAS.fmt.MONTHS_LONG[d.getMonth()] + ' ' + d.getFullYear();
  }
  function clamp(value, min, max) { return Math.min(Math.max(value, min), max); }
  function slug(value) { return String(value || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''); }

  return {
    esc: esc, clone: clone, uid: uid, now: now, contains: contains, search: search,
    byStatus: byStatus, sortBy: sortBy, paginate: paginate, download: download,
    exportCsv: exportCsv, debounce: debounce, qs: qs, qsa: qsa, stripTags: stripTags,
    truncate: truncate, clamp: clamp, monthYear: monthYear, slug: slug
  };
})();
/* ------------------------------------------------------------------ icons */
OSAS.icons = (function () {
  var PATHS = {
    dashboard: '<rect x="3" y="3" width="7.5" height="7.5" rx="1.5"/><rect x="13.5" y="3" width="7.5" height="4.5" rx="1.5"/><rect x="13.5" y="10.5" width="7.5" height="10.5" rx="1.5"/><rect x="3" y="13.5" width="7.5" height="7.5" rx="1.5"/>',
    megaphone: '<path d="M3 11v2a1 1 0 0 0 1 1h2l6 4V6L6 10H4a1 1 0 0 0-1 1Z"/><path d="M16 9a4 4 0 0 1 0 6"/><path d="M18.5 6.5a8 8 0 0 1 0 11"/>',
    scale: '<path d="M12 3v18"/><path d="M7 5h10"/><path d="M5 21h14"/><path d="M6 5 3 12h6L6 5Z"/><path d="M18 5l-3 7h6l-3-7Z"/>',
    graduation: '<path d="M2.5 8.5 12 4l9.5 4.5L12 13 2.5 8.5Z"/><path d="M6 10.8V16c0 1.7 2.7 3 6 3s6-1.3 6-3v-5.2"/><path d="M21 9v5"/>',
    calendar: '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/>',
    activity: '<path d="M3 20h18"/><path d="M6 20V11M11 20V6M16 20v-6M21 20V9"/>',
    headset: '<path d="M4 14v-2a8 8 0 0 1 16 0v2"/><path d="M4 14h3v5H5.5A1.5 1.5 0 0 1 4 17.5V14Z"/><path d="M20 14h-3v5h1.5a1.5 1.5 0 0 0 1.5-1.5V14Z"/><path d="M20 19v1a2 2 0 0 1-2 2h-3"/>',
    bell: '<path d="M18 15V10a6 6 0 1 0-12 0v5l-1.5 3h15L18 15Z"/><path d="M10 21h4"/>',
    users: '<circle cx="9" cy="8" r="3.4"/><path d="M3 20c0-3.3 2.7-5.5 6-5.5s6 2.2 6 5.5"/><path d="M16 5.2a3.4 3.4 0 0 1 0 6.6"/><path d="M18.5 20c0-2.2-.7-3.9-2-5"/>',
    fileText: '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z"/><path d="M14 3v5h5"/><path d="M9 13h6M9 17h4"/>',
    search: '<circle cx="11" cy="11" r="6.5"/><path d="M16 16l4.5 4.5"/>',
    filter: '<path d="M3 5h18l-7 8v6l-4 2v-8L3 5Z"/>',
    plus: '<path d="M12 5v14M5 12h14"/>',
    close: '<path d="M6 6l12 12M18 6 6 18"/>',
    check: '<path d="M4.5 12.5 9.5 17.5 19.5 6.5"/>',
    checkCircle: '<circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.8 2.8L16 9.5"/>',
    eye: '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/>',
    eyeOff: '<path d="M3 3l18 18"/><path d="M10.6 6.1A9.9 9.9 0 0 1 12 6c6 0 9.5 6 9.5 6a17 17 0 0 1-2.9 3.5"/><path d="M6.4 8.3A16.4 16.4 0 0 0 2.5 12s3.5 6 9.5 6c1.3 0 2.5-.3 3.5-.7"/><path d="M9.9 10.1a3 3 0 0 0 4.1 4.2"/>',
    alert: '<path d="M12 4 2.8 20h18.4L12 4Z"/><path d="M12 10v4.5M12 17.4v.2"/>',
    clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7.5V12l3.5 2"/>',
    download: '<path d="M12 3v11"/><path d="M8 11l4 4 4-4"/><path d="M4 19h16"/>',
    refresh: '<path d="M20 11a8 8 0 0 0-13.5-4.6L4 9"/><path d="M4 5v4h4"/><path d="M4 13a8 8 0 0 0 13.5 4.6L20 15"/><path d="M20 19v-4h-4"/>',
    chevronLeft: '<path d="M14.5 5.5 8 12l6.5 6.5"/>',
    chevronRight: '<path d="M9.5 5.5 16 12l-6.5 6.5"/>',
    arrowRight: '<path d="M4 12h15"/><path d="M13 6l6 6-6 6"/>',
    arrowUp: '<path d="M12 20V5"/><path d="M6 11l6-6 6 6"/>',
    arrowDown: '<path d="M12 4v15"/><path d="M6 13l6 6 6-6"/>',
    send: '<path d="M4 12 20 4l-6.5 16-3-6.5L4 12Z"/>',
    shield: '<path d="M12 3l7 3v6c0 4.4-3 7.7-7 9-4-1.3-7-4.6-7-9V6l7-3Z"/><path d="M9 12l2 2 4-4"/>',
    target: '<circle cx="12" cy="12" r="8.5"/><circle cx="12" cy="12" r="4.5"/><circle cx="12" cy="12" r="1"/>',
    broadcast: '<circle cx="12" cy="12" r="2.5"/><path d="M5.5 5.5a9 9 0 0 0 0 13M18.5 18.5a9 9 0 0 0 0-13"/><path d="M8.5 8.5a5 5 0 0 0 0 7M15.5 15.5a5 5 0 0 0 0-7"/>',
    mail: '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3.5 7l8.5 6 8.5-6"/>',
    lock: '<rect x="5" y="10.5" width="14" height="10" rx="2"/><path d="M8.5 10.5V8a3.5 3.5 0 0 1 7 0v2.5"/>',
    user: '<circle cx="12" cy="8.5" r="3.6"/><path d="M4.5 20c0-3.6 3.4-6 7.5-6s7.5 2.4 7.5 6"/>',
    upload: '<path d="M12 20V9"/><path d="M8 12l4-4 4 4"/><path d="M4 4h16"/>',
    edit: '<path d="M4 20h4l10-10-4-4L4 16v4Z"/><path d="M14.5 5.5 18.5 9.5"/>',
    trash: '<path d="M4 7h16"/><path d="M9 7V4.8h6V7"/><path d="M6.5 7l1 13h9l1-13"/><path d="M10.5 11v5M13.5 11v5"/>',
    external: '<path d="M14 4h6v6"/><path d="M20 4l-8 8"/><path d="M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>',
    menu: '<path d="M4 7h16M4 12h16M4 17h16"/>',
    logout: '<path d="M15 4h3a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-3"/><path d="M10 8l-4 4 4 4"/><path d="M6 12h9"/>',
    info: '<circle cx="12" cy="12" r="9"/><path d="M12 11v5.5M12 7.8v.2"/>',
    printer: '<path d="M7 9V4h10v5"/><rect x="4" y="9" width="16" height="7" rx="1.5"/><path d="M7 14h10v6H7z"/>',
    layers: '<path d="M12 3 3 8l9 5 9-5-9-5Z"/><path d="M3 13.5 12 18.5l9-5"/>',
    pin: '<path d="M12 21s6-5.5 6-10a6 6 0 1 0-12 0c0 4.5 6 10 6 10Z"/><circle cx="12" cy="11" r="2.4"/>',
    dot: '<circle cx="12" cy="12" r="3.4"/>'
  };

  function icon(name, size, extraClass) {
    var inner = PATHS[name] || PATHS.dot;
    var px = size || 16;
    return '<svg viewBox="0 0 24 24" width="' + px + '" height="' + px + '" fill="none" stroke="currentColor" ' +
      'stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"' +
      (extraClass ? ' class="' + extraClass + '"' : '') + '>' + inner + '</svg>';
  }

  function beacon(size, strokeWidth) {
    var px = size || 40;
    return '<svg viewBox="0 0 64 64" width="' + px + '" height="' + px + '" class="beacon-mark" ' +
      'fill="none" stroke="currentColor" stroke-width="' + (strokeWidth || 2.2) + '" ' +
      'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
      '<path d="M32 4v5"/><path d="M25.5 14h13"/><path d="M27 14l1-5h8l1 5"/>' +
      '<path d="M27 14h10l4 26H23l4-26Z"/><path d="M21.5 40h21"/><path d="M18.5 46h27"/>' +
      '<path d="M32 16v24"/><path d="M24 25h16M25 34h14"/>' +
      '<path d="M20 12 12 6M44 12l8-6"/><path d="M19 20H9M45 20h10"/>' +
      '<path d="M17 54c4-2.6 7.6-2.6 11 0s7.6 2.6 11 0" stroke-opacity=".7"/>' +
      '</svg>';
  }

  return { icon: icon, beacon: beacon, PATHS: PATHS };
})();
/* ------------------------------------------------ persistent data store
   Single seam for all module data. Swap these methods for fetch() calls
   against api/index.php (see README "Server deployment") to run on MySQL.
   ---------------------------------------------------------------------- */
OSAS.store = (function () {
  var RESOURCES = ['users', 'announcements', 'regulations', 'modules', 'events',
    'tickets', 'broadcasts', 'reports', 'tracking', 'auditLog'];
  var state = null;

  function fromSeed() {
    var next = {
      version: OSAS_SEED.meta.schemaVersion,
      meta: OSAS_SEED.meta,
      metrics: OSAS_SEED.metrics,
      traffic: OSAS_SEED.traffic,
      options: OSAS_SEED.options,
      engagementNotes: OSAS_SEED.engagementNotes || []
    };
    RESOURCES.forEach(function (name) { next[name] = OSAS_SEED[name] || []; });
    return OSAS.util.clone(next);
  }

  function persist() {
    try {
      window.localStorage.setItem(OSAS.CONFIG.storageKey, JSON.stringify(state));
    } catch (err) {
      /* private mode / quota — the session keeps working in memory */
    }
  }

  function load(force) {
    if (state && !force) { return state; }
    var raw = null;
    try { raw = window.localStorage.getItem(OSAS.CONFIG.storageKey); } catch (err) { raw = null; }
    if (raw) {
      try {
        var parsed = JSON.parse(raw);
        if (parsed && parsed.version === OSAS_SEED.meta.schemaVersion && parsed.users) {
          state = parsed;
          return state;
        }
      } catch (err) { /* fall through to reseed */ }
    }
    state = fromSeed();
    persist();
    return state;
  }

  function reset() {
    state = fromSeed();
    persist();
    return state;
  }

  function all(name) { return load()[name] || []; }

  function find(name, id) {
    var rows = all(name);
    for (var i = 0; i < rows.length; i++) { if (rows[i].id === id) { return rows[i]; } }
    return null;
  }

  function nextId(name, prefix, pad) {
    var highest = 0;
    all(name).forEach(function (row) {
      var match = String(row.id || '').match(new RegExp('^' + prefix + '-(\\d+)$'));
      if (match) { highest = Math.max(highest, parseInt(match[1], 10)); }
    });
    var next = highest + 1;
    return prefix + '-' + (pad ? String(next).padStart(pad, '0') : next);
  }

  function insert(name, record) {
    load()[name].unshift(record);
    persist();
    return record;
  }

  function update(name, id, patch) {
    var row = find(name, id);
    if (!row) { return null; }
    Object.keys(patch).forEach(function (key) { row[key] = patch[key]; });
    persist();
    return row;
  }

  function remove(name, id) {
    var rows = load()[name];
    for (var i = 0; i < rows.length; i++) {
      if (rows[i].id === id) { rows.splice(i, 1); persist(); return true; }
    }
    return false;
  }

  function logActivity(entry) {
    var record = {
      id: OSAS.util.uid('LOG'),
      tone: entry.tone || 'brand',
      icon: entry.icon || 'info',
      title: entry.title,
      text: entry.text || '',
      at: entry.at || OSAS.util.now(),
      ago: entry.ago || 'Just now'
    };
    insert('auditLog', record);
    return record;
  }

  function meta() { return load().meta; }
  function metrics() { return load().metrics; }
  function traffic() { return load().traffic; }
  function options() { return load().options; }
  function notes() { return load().engagementNotes || []; }
  function exportBackup() { return JSON.stringify(load(), null, 2); }

  return {
    RESOURCES: RESOURCES, load: load, reset: reset, persist: persist,
    all: all, find: find, insert: insert, update: update, remove: remove,
    nextId: nextId, logActivity: logActivity,
    meta: meta, metrics: metrics, traffic: traffic, options: options, notes: notes,
    exportBackup: exportBackup
  };
})();
/* ------------------------------------------------------------------- auth */
OSAS.auth = (function () {
  function readSession() {
    try {
      var raw = window.sessionStorage.getItem(OSAS.CONFIG.sessionKey) ||
        window.localStorage.getItem(OSAS.CONFIG.sessionKey);
      return raw ? JSON.parse(raw) : null;
    } catch (err) { return null; }
  }

  function writeSession(session, remember) {
    var raw = JSON.stringify(session);
    try {
      window.sessionStorage.setItem(OSAS.CONFIG.sessionKey, raw);
      if (remember) { window.localStorage.setItem(OSAS.CONFIG.sessionKey, raw); }
      else { window.localStorage.removeItem(OSAS.CONFIG.sessionKey); }
    } catch (err) { /* ignore */ }
  }

  function current() {
    var session = readSession();
    if (!session) { return null; }
    var user = OSAS.store.find('users', session.userId);
    if (!user) { return null; }
    return { user: user, role: user.role, signedInAt: session.signedInAt };
  }

  function login(email, password, remember) {
    var target = String(email || '').trim().toLowerCase();
    var user = null;
    OSAS.store.all('users').forEach(function (row) {
      if (String(row.email).toLowerCase() === target) { user = row; }
    });
    if (!user) { return { ok: false, message: 'No portal account found for that university email address.' }; }
    if (user.status === 'Inactive') { return { ok: false, message: 'This account has been deactivated. Please contact OSAS Support.' }; }
    if (password !== OSAS.CONFIG.demoPassword) { return { ok: false, message: 'Incorrect password. Both the password and email address are case-sensitive.' }; }
    if (user.status === 'Pending') { user.status = 'Active'; }
    user.lastLogin = OSAS.util.now();
    OSAS.store.persist();
    writeSession({ userId: user.id, role: user.role, signedInAt: OSAS.util.now() }, !!remember);
    OSAS.store.logActivity({
      tone: 'brand', icon: 'lock', title: 'Portal Sign-In',
      text: user.firstName + ' ' + user.lastName + ' signed in to the ' + String(user.role).toLowerCase() + ' portal.'
    });
    return { ok: true, user: user };
  }

  function logout() {
    var session = current();
    try {
      window.sessionStorage.removeItem(OSAS.CONFIG.sessionKey);
      window.localStorage.removeItem(OSAS.CONFIG.sessionKey);
    } catch (err) { /* ignore */ }
    if (session) {
      OSAS.store.logActivity({
        tone: 'brand', icon: 'logout', title: 'Portal Sign-Out',
        text: session.user.firstName + ' ' + session.user.lastName + ' signed out of the portal.'
      });
    }
  }

  function requireAuth() {
    var session = current();
    if (!session) {
      var target = window.location.pathname.split('/').pop() || 'dashboard.html';
      window.location.replace('index.html?next=' + encodeURIComponent(target));
      return null;
    }
    return session;
  }

  return { current: current, login: login, logout: logout, requireAuth: requireAuth };
})();
/* ------------------------------------------------------- analytics helpers
   Derived figures for dashboard cards, tables and footers. Every module page
   reads its headline numbers from here so nothing is hard-coded twice.
   ---------------------------------------------------------------------- */
OSAS.analytics = (function () {
  function count(rows, field, value) {
    return rows.filter(function (r) { return r[field] === value; }).length;
  }
  function sum(rows, field) {
    return rows.reduce(function (acc, r) { return acc + (Number(r[field]) || 0); }, 0);
  }
  function avg(rows, field) {
    if (!rows.length) { return 0; }
    return sum(rows, field) / rows.length;
  }
  function weightedRate(rows, rateField, weightField) {
    var weight = sum(rows, weightField);
    if (!weight) { return 0; }
    var total = rows.reduce(function (acc, r) {
      return acc + (Number(r[rateField]) || 0) * (Number(r[weightField]) || 0);
    }, 0);
    return Math.round((total / weight) * 10) / 10;
  }
  function groupCount(rows, field) {
    var map = {};
    rows.forEach(function (r) {
      var key = r[field] || 'Unspecified';
      map[key] = (map[key] || 0) + 1;
    });
    return map;
  }
  function priorityRank(value) {
    var ranks = { High: 0, Medium: 1, Low: 2 };
    return ranks[value] === undefined ? 3 : ranks[value];
  }
  function openTickets(rows) {
    return rows.filter(function (r) { return r.status === 'Open' || r.status === 'In Progress'; });
  }
  /* Queue used by the dashboard "Priority Inquiries" table */
  function priorityQueue(rows) {
    return openTickets(rows).slice().sort(function (a, b) {
      var rank = priorityRank(a.priority) - priorityRank(b.priority);
      if (rank !== 0) { return rank; }
      return new Date(b.submittedAt) - new Date(a.submittedAt);
    });
  }

  /* ------------------------------------------------------------ dashboard */
  function dashboard() {
    var metrics = OSAS.store.metrics();
    var tickets = OSAS.store.all('tickets');
    var events = OSAS.store.all('events');
    var modules = OSAS.store.all('modules');
    var published = modules.filter(function (m) { return m.status === 'Published'; });
    var queue = priorityQueue(tickets);
    return {
      totalStudents: metrics.registeredStudents,
      totalStudentsGrowth: metrics.registeredStudentsGrowth,
      orientationRate: weightedRate(published, 'completionRate', 'learners'),
      orientationGrowth: metrics.orientationCompletionGrowth,
      openTickets: openTickets(tickets).length,
      highPriorityTickets: queue.filter(function (t) { return t.priority === 'High'; }).length,
      avgResponseHours: metrics.avgSupportResponseHours,
      activeEvents: count(events, 'status', 'Scheduled'),
      upcomingEvents: count(events, 'status', 'Scheduled'),
      scheduledThisWeek: events.filter(function (e) {
        var diff = (new Date(e.startAt) - new Date(OSAS.SEED_NOW)) / 86400000;
        return e.status === 'Scheduled' && diff >= 0 && diff <= 14;
      }).length,
      queue: queue,
      audit: OSAS.store.all('auditLog').slice(0, 5),
      traffic: OSAS.store.traffic(),
      metrics: metrics
    };
  }

  /* -------------------------------------------------------- module 2 / 3 */
  function content(rows, extras) {
    var publishedRows = rows.filter(function (r) { return r.status === 'Published'; });
    var stats = {
      total: rows.length,
      published: publishedRows.length,
      scheduled: count(rows, 'status', 'Scheduled'),
      drafts: count(rows, 'status', 'Draft'),
      archived: count(rows, 'status', 'Archived'),
      pending: count(rows, 'status', 'Pending Review'),
      totalViews: sum(rows, 'views'),
      publishedViews: sum(publishedRows, 'views')
    };
    if (extras) { Object.keys(extras).forEach(function (k) { stats[k] = extras[k]; }); }
    return stats;
  }

  function modules(rows) {
    var published = rows.filter(function (r) { return r.status === 'Published'; });
    var stats = content(rows);
    stats.learners = sum(rows, 'learners');
    stats.completions = sum(rows, 'completions');
    stats.completionRate = weightedRate(published, 'completionRate', 'learners');
    stats.avgDuration = Math.round(avg(published, 'duration'));
    stats.mandatory = published.filter(function (r) { return r.mandatory; }).length;
    return stats;
  }

  function events(rows) {
    var capacity = sum(rows, 'capacity');
    return {
      total: rows.length,
      upcoming: count(rows, 'status', 'Scheduled'),
      completed: count(rows, 'status', 'Completed'),
      cancelled: count(rows, 'status', 'Cancelled'),
      registrations: sum(rows, 'registered'),
      capacity: capacity,
      utilization: capacity ? Math.round((sum(rows, 'registered') / capacity) * 100) : 0
    };
  }
  function tickets(rows) {
    var open = openTickets(rows);
    return {
      total: rows.length,
      open: count(rows, 'status', 'Open'),
      inProgress: count(rows, 'status', 'In Progress'),
      resolved: count(rows, 'status', 'Resolved'),
      closed: count(rows, 'status', 'Closed'),
      awaitingAction: open.length,
      highPriority: open.filter(function (r) { return r.priority === 'High'; }).length,
      avgResponseHours: OSAS.store.metrics().avgSupportResponseHours,
      categories: groupCount(rows, 'category')
    };
  }

  function broadcasts(rows) {
    var sent = rows.filter(function (r) { return r.status === 'Sent'; });
    var delivered = sum(sent, 'delivered');
    var opened = sum(sent, 'opened');
    return {
      total: rows.length,
      sent: sent.length,
      scheduled: count(rows, 'status', 'Scheduled'),
      pending: count(rows, 'status', 'Pending'),
      drafts: count(rows, 'status', 'Draft'),
      delivered: delivered,
      opened: opened,
      openRate: delivered ? Math.round((opened / delivered) * 100) : 0
    };
  }

  function users(rows) {
    var metrics = OSAS.store.metrics();
    return {
      registeredStudents: metrics.registeredStudents,
      activeStudentAccounts: metrics.activeStudentAccounts,
      pendingActivations: metrics.pendingActivations,
      administrativeStaff: metrics.administrativeStaff,
      directoryTotal: rows.length,
      active: count(rows, 'status', 'Active'),
      pending: count(rows, 'status', 'Pending'),
      inactive: count(rows, 'status', 'Inactive'),
      byRole: groupCount(rows, 'role'),
      lastSync: metrics.lastSyncLabel
    };
  }

  function tracking(rows) {
    var metrics = OSAS.store.metrics();
    return {
      total: rows.length,
      views: sum(rows, 'views'),
      uniqueVisitors: metrics.uniqueVisitors,
      avgEngagement: Math.round(avg(rows, 'engagement') * 10) / 10,
      lowEngagement: rows.filter(function (r) { return r.engagement < 70; }).length,
      topItem: rows.slice().sort(function (a, b) { return b.views - a.views; })[0] || null,
      byType: groupCount(rows, 'type'),
      totalPortalViews: metrics.totalPortalViews
    };
  }

  function reports(rows) {
    var sorted = rows.slice().sort(function (a, b) {
      return new Date(b.generatedAt) - new Date(a.generatedAt);
    });
    return {
      total: rows.length,
      completed: count(rows, 'status', 'Completed'),
      processing: count(rows, 'status', 'Processing'),
      drafts: count(rows, 'status', 'Draft'),
      records: sum(rows, 'records'),
      latest: sorted[0] || null
    };
  }

  return {
    count: count, sum: sum, avg: avg, weightedRate: weightedRate, groupCount: groupCount,
    priorityQueue: priorityQueue, openTickets: openTickets,
    dashboard: dashboard, content: content, modules: modules, events: events,
    tickets: tickets, broadcasts: broadcasts, users: users, tracking: tracking, reports: reports
  };
})();
/* @@CORE-END@@ */
