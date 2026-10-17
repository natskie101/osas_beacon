/* Runs qa/selftest.html's inline script headlessly (no browser). */
const fs = require('fs');
const vm = require('vm');

const html = fs.readFileSync('qa/selftest.html', 'utf8');
const inline = html.match(/<script>([\s\S]*?)<\/script>/);
if (!inline) { console.error('no inline self-test script found'); process.exit(1); }

const elements = {};
function makeEl(id) {
  return {
    id, innerHTML: '', textContent: '', value: '', style: {},
    classList: { add() {}, remove() {}, toggle() {}, contains() { return false; } },
    addEventListener() {}, setAttribute() {}, getAttribute() { return null; },
    appendChild() {}, focus() {}, querySelector() { return null; }
  };
}
function store(backing) {
  return {
    getItem: k => (k in backing ? backing[k] : null),
    setItem: (k, v) => { backing[k] = String(v); },
    removeItem: k => { delete backing[k]; },
    clear: () => { Object.keys(backing).forEach(k => delete backing[k]); },
    key: i => Object.keys(backing)[i] || null
  };
}

const local = {}, session = {};
const sb = {
  console, URLSearchParams, encodeURIComponent, decodeURIComponent, setTimeout, clearTimeout,
  localStorage: store(local), sessionStorage: store(session),
  document: {
    getElementById(id) { if (!elements[id]) elements[id] = makeEl(id); return elements[id]; },
    querySelector() { return null; }, querySelectorAll() { return []; },
    addEventListener() {}, createElement() { return makeEl('dyn'); },
    body: Object.assign(makeEl('body'), {
      attrs: {},
      setAttribute(k, v) { this.attrs[k] = String(v); },
      getAttribute(k) { return k in this.attrs ? this.attrs[k] : null; }
    })
  },
  location: { search: '', pathname: '/qa/selftest.html', hash: '', href: '', replace(v) { this.href = v; } },
  matchMedia: () => ({ matches: false, addEventListener() {}, addListener() {} }),
  addEventListener() {}
};
sb.window = sb;
sb.globalThis = sb;

vm.createContext(sb);
['assets/js/seed.js', 'assets/js/core.js'].forEach(f =>
  vm.runInContext(fs.readFileSync(f, 'utf8'), sb, { filename: f }));
vm.runInContext(inline[1], sb, { filename: 'qa/selftest.html#inline' });

const out = elements.out || {};
const text = out.textContent || out.innerHTML || '';
console.log(text);
if (sb.document.body.attrs['data-summary']) {
  console.log('\nsummary: ' + sb.document.body.attrs['data-summary']);
}
const failed = (text.match(/^FAIL/gm) || []).length;
const passed = (text.match(/^PASS/gm) || []).length;
console.log('\npassed=' + passed + ' failed=' + failed);
process.exit(failed ? 1 : 0);
