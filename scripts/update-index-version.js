// Script: update-index-version.js
// Tự động thêm version query (?v=yyyymmddHHMM) vào các file js/css trong public/index.html
// Chạy sau mỗi lần build để cache busting cho PWA/app mobile

const fs = require('fs');
const path = require('path');

const INDEX_PATH = path.join(__dirname, '../public/index.html');
const VERSION = new Date().toISOString().replace(/[-:T.Z]/g, '').slice(0, 12); // yyyymmddHHMM

console.log('Updating:', INDEX_PATH);
console.log('New version:', VERSION);

if (!fs.existsSync(INDEX_PATH)) {
  console.error('ERROR: File not found:', INDEX_PATH);
  process.exit(1);
}

let html = fs.readFileSync(INDEX_PATH, 'utf8');

// Tìm các file js/css hiện có
const matches = html.match(/(src|href)=["'][^"']+\.(js|css)(?:\?v=\d+)?["']/g);
console.log('Found files:', matches);

// Regex cải tiến: match cả khi đã có ?v= hoặc chưa có
// Lưu ý: (?:js|css) là non-capturing để không làm lệch group count
let replaceCount = 0;
html = html.replace(/(src|href)=("|')([^"']+\.(?:js|css))(?:\?v=\d+)?\2/g,
  (match, attr, q1, file) => {
    replaceCount++;
    console.log(`Replacing: ${file} -> ${file}?v=${VERSION}`);
    return `${attr}=${q1}${file}?v=${VERSION}${q1}`;
  }
);

console.log(`Replaced ${replaceCount} files`);

try {
  fs.writeFileSync(INDEX_PATH, html, 'utf8');
  console.log('Successfully updated index.html with version:', VERSION);
} catch (err) {
  console.error('ERROR: Failed to write file:', err);
  process.exit(1);
}