const fs = require('fs');
const path = require('path');

const source = path.join('node_modules', 'chart.js', 'dist', 'chart.umd.js');
const destination = path.join('public', 'assets', 'js', 'chart.umd.js');

if (!fs.existsSync(source)) {
  throw new Error('Chart.js is missing. Run npm install before building Technical Admin assets.');
}

fs.mkdirSync(path.dirname(destination), { recursive: true });
fs.copyFileSync(source, destination);
