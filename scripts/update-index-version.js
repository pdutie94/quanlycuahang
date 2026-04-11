// Script: update-index-version.js
// Tự động thêm version query (?v=yyyymmddHHMM) vào các file js/css trong public/index.html
// Chạy sau mỗi lần build để cache busting cho PWA/app mobile

const fs = require('fs');
const path = require('path');

const INDEX_PATH = path.join(__dirname, '../public/index.html');
const VERSION = new Date().toISOString().replace(/[-:T.Z]/g, '').slice(0, 12); // yyyymmddHHMM

let html = fs.readFileSync(INDEX_PATH, 'utf8');

// Regex tìm các file .js, .css (không có query string)
html = html.replace(/(src|href)=("|')([^"'?]+\.(js|css))("|')/g, (match, attr, q1, file, ext, q2) => {
  // Nếu đã có ?v= thì bỏ
  let cleanFile = file.replace(/\?v=\d+/, '');
  return `${attr}=${q1}${cleanFile}?v=${VERSION}${q2}`;
});

fs.writeFileSync(INDEX_PATH, html, 'utf8');

console.log(`Updated index.html with version: ${VERSION}`);