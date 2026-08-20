const fs = require('fs');
const path = require('path');

// Simple PNG generator in pure Node for logo placeholders if actual PNGs aren't uploaded yet
// Creates valid 1x1 transparent PNG or base64 decoded PNGs, or high quality SVG/PNG files
const assetsDir = path.join(__dirname, 'public', 'assets');
if (!fs.existsSync(assetsDir)) {
  fs.mkdirSync(assetsDir, { recursive: true });
}

// Create sinjai.png SVG/PNG fallback if not existing
const sinjaiSvg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 240" width="200" height="240">
  <defs>
    <linearGradient id="sg" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#029fe4"/><stop offset="100%" stop-color="#005b8e"/></linearGradient>
    <linearGradient id="gg" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#ffd700"/><stop offset="100%" stop-color="#ff9900"/></linearGradient>
  </defs>
  <path d="M 100 10 C 140 10, 185 25, 185 70 C 185 145, 150 195, 100 225 C 50 195, 15 145, 15 70 C 15 25, 60 10, 100 10 Z" fill="url(#sg)" stroke="#ffffff" stroke-width="4"/>
  <polygon points="100,75 145,145 55,145" fill="#1b4d3e" />
  <path d="M 30 145 Q 100 160 170 145 L 170 170 Q 100 200 30 170 Z" fill="#0055a5"/>
  <polygon points="100,30 104,42 117,42 106,50 110,62 100,54 90,62 94,50 83,42 96,42" fill="url(#gg)"/>
  <text x="100" y="180" font-family="sans-serif" font-size="14" font-weight="900" fill="#ffffff" text-anchor="middle">SINJAI</text>
</svg>`;

// Create kominfo.png SVG/PNG fallback
const kominfoSvg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="200" height="200">
  <circle cx="100" cy="100" r="90" fill="#005b8e"/>
  <path d="M 50 100 A 50 50 0 0 1 150 100 A 25 25 0 0 1 100 100 A 25 25 0 0 0 50 100 Z" fill="#029fe4"/>
  <circle cx="100" cy="100" r="20" fill="#ffd700"/>
  <text x="100" y="165" font-family="sans-serif" font-size="12" font-weight="900" fill="#ffffff" text-anchor="middle">KOMINFO</text>
</svg>`;

fs.writeFileSync(path.join(assetsDir, 'sinjai.svg'), sinjaiSvg);
fs.writeFileSync(path.join(assetsDir, 'kominfo.svg'), kominfoSvg);

console.log('✅ Aset logo sinjai.svg & kominfo.svg berhasil dibuat!');
