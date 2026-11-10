const fs = require('fs');

const stylesheetPath = 'public/assets/css/technical-admin.css';
let stylesheet = fs.readFileSync(stylesheetPath, 'utf8');

const globalSelectors = [
  ['*,:after,:before{', '#technical-overview *,#technical-overview :after,#technical-overview :before{'],
  ['::backdrop{', '#technical-overview ::backdrop{'],
];

for (const [selector, scopedSelector] of globalSelectors) {
  if (!stylesheet.includes(selector)) {
    throw new Error(`Expected Tailwind selector was not found: ${selector}`);
  }
  stylesheet = stylesheet.replace(selector, scopedSelector);
}

fs.writeFileSync(stylesheetPath, stylesheet);
