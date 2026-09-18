const http = require('http');
const fs = require('fs');
const path = require('path');

const PORT = 5500;
const ROOT = path.resolve(__dirname);
const UPLOAD_DIR = path.join(ROOT, 'assets', 'uploads');
const DATA_DIR = path.join(ROOT, 'data');

if (!fs.existsSync(UPLOAD_DIR)) {
  fs.mkdirSync(UPLOAD_DIR, { recursive: true });
}
if (!fs.existsSync(DATA_DIR)) {
  fs.mkdirSync(DATA_DIR, { recursive: true });
}

const MIME_TYPES = {
  '.html': 'text/html; charset=utf-8',
  '.css': 'text/css; charset=utf-8',
  '.js': 'application/javascript; charset=utf-8',
  '.json': 'application/json; charset=utf-8',
  '.jpg': 'image/jpeg',
  '.jpeg': 'image/jpeg',
  '.png': 'image/png',
  '.svg': 'image/svg+xml',
  '.webp': 'image/webp',
  '.gif': 'image/gif',
  '.mp4': 'video/mp4',
  '.mp3': 'audio/mpeg',
  '.ico': 'image/x-icon'
};

function readJsonFile(filename) {
  const filePath = path.join(DATA_DIR, filename);
  try {
    if (fs.existsSync(filePath)) {
      const raw = fs.readFileSync(filePath, 'utf8');
      return JSON.parse(raw);
    }
  } catch (err) {
    console.error(`Error reading ${filename}:`, err.message);
  }
  return null;
}

function writeJsonFile(filename, data) {
  const filePath = path.join(DATA_DIR, filename);
  fs.writeFileSync(filePath, JSON.stringify(data, null, 2), 'utf8');
}

function setCorsHeaders(res) {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE');
  res.setHeader('Access-Control-Allow-Headers', '*');
  res.setHeader('Access-Control-Private-Network', 'true');
}

const server = http.createServer((req, res) => {
  setCorsHeaders(res);
  console.log(`[${new Date().toLocaleTimeString()}] ${req.method} ${req.url}`);

  // CORS Preflight
  if (req.method === 'OPTIONS') {
    res.writeHead(204);
    return res.end();
  }

  const urlObj = new URL(req.url, `http://${req.headers.host || 'localhost:5500'}`);
  const cleanPath = urlObj.pathname.toLowerCase();

  // ==========================================================================
  // GET ALL PERSISTENT DATA: GET /api/data or /api/data.php
  // ==========================================================================
  if (req.method === 'GET' && (cleanPath === '/api/data' || cleanPath === '/api/data.php')) {
    const data = {
      products: readJsonFile('products.json'),
      brands: readJsonFile('brands.json'),
      reviews: readJsonFile('reviews.json'),
      gallery: readJsonFile('gallery.json'),
      settings: readJsonFile('settings.json')
    };
    res.writeHead(200, {
      'Content-Type': 'application/json; charset=utf-8',
      'Cache-Control': 'no-cache, no-store, must-revalidate, max-age=0',
      'Pragma': 'no-cache',
      'Expires': '0'
    });
    return res.end(JSON.stringify(data));
  }

  // ==========================================================================
  // SAVE PERSISTENT DATA: POST /api/save or /api/save.php
  // ==========================================================================
  if (req.method === 'POST' && (cleanPath === '/api/save' || cleanPath === '/api/save.php')) {
    let body = '';
    req.on('data', chunk => { body += chunk; });
    req.on('end', () => {
      try {
        const payload = JSON.parse(body);
        const allowedTypes = ['products', 'brands', 'reviews', 'gallery', 'settings'];
        if (!payload.type || !allowedTypes.includes(payload.type)) {
          res.writeHead(400, { 'Content-Type': 'application/json' });
          return res.end(JSON.stringify({ success: false, error: 'Invalid data type' }));
        }

        writeJsonFile(`${payload.type}.json`, payload.data);
        console.log(`[Admin Save] Saved ${payload.type}.json (${Array.isArray(payload.data) ? payload.data.length + ' items' : 'object'}) to disk.`);

        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ success: true, type: payload.type }));
      } catch (err) {
        console.error('Save error:', err);
        res.writeHead(500, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ success: false, error: err.message }));
      }
    });
    return;
  }

  // ==========================================================================
  // SUBMIT REVIEW ENDPOINT: POST /api/review/submit or /api/review-submit.php
  // ==========================================================================
  if (req.method === 'POST' && (cleanPath === '/api/review/submit' || cleanPath === '/api/review-submit.php')) {
    let body = '';
    req.on('data', chunk => { body += chunk; });
    req.on('end', () => {
      try {
        const payload = JSON.parse(body);
        if (!payload.name || !payload.review) {
          res.writeHead(400, { 'Content-Type': 'application/json' });
          return res.end(JSON.stringify({ success: false, error: 'Name and review are required' }));
        }

        let existing = readJsonFile('reviews.json');
        if (!Array.isArray(existing)) {
          existing = [];
        }

        const newRev = {
          id: 'rev-' + Date.now().toString().slice(-6),
          name: payload.name.trim(),
          city: (payload.city || 'Dehradun').trim(),
          rating: parseInt(payload.rating, 10) || 5,
          date: payload.date || 'Festive Season 2026',
          review: payload.review.trim(),
          verified: true
        };

        existing.unshift(newRev);
        writeJsonFile('reviews.json', existing);
        console.log(`[Review Submit] Added review from ${newRev.name} (${newRev.city})`);

        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ success: true, review: newRev }));
      } catch (err) {
        console.error('Review submit error:', err);
        res.writeHead(500, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ success: false, error: err.message }));
      }
    });
    return;
  }

  // ==========================================================================
  // BASE64 FILE UPLOAD ENDPOINT: POST /api/upload-base64 or /api/upload-base64.php
  // ==========================================================================
  if (req.method === 'POST' && (cleanPath.startsWith('/api/upload-base64') || cleanPath.startsWith('/api/upload-base64.php'))) {
    let body = '';
    req.on('data', chunk => { body += chunk; });
    req.on('end', () => {
      try {
        const payload = JSON.parse(body);
        if (!payload.base64) {
          res.writeHead(400, { 'Content-Type': 'application/json' });
          return res.end(JSON.stringify({ success: false, error: 'No base64 data provided' }));
        }

        const matches = payload.base64.match(/^data:([A-Za-z-+\/]+);base64,(.+)$/);
        let ext = '.jpg';
        let buffer;
        if (matches && matches.length === 3) {
          const mime = matches[1].toLowerCase();
          if (mime.includes('png')) ext = '.png';
          else if (mime.includes('webp')) ext = '.webp';
          else if (mime.includes('gif')) ext = '.gif';
          buffer = Buffer.from(matches[2], 'base64');
        } else {
          buffer = Buffer.from(payload.base64, 'base64');
        }

        const cleanBase = (payload.filename || 'photo').replace(/\.[^/.]+$/, "").replace(/[^a-zA-Z0-9_-]/g, '_');
        const uniqueFilename = `${Date.now()}_${cleanBase}${ext}`;
        const targetFilePath = path.join(UPLOAD_DIR, uniqueFilename);

        fs.writeFileSync(targetFilePath, buffer);
        const publicRelativePath = `assets/uploads/${uniqueFilename}`;
        console.log(`[Base64 Upload] Saved ${publicRelativePath} (${buffer.length} bytes) to disk.`);

        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({
          success: true,
          filePath: publicRelativePath,
          filename: uniqueFilename,
          size: buffer.length
        }));
      } catch (err) {
        console.error('Base64 upload error:', err);
        res.writeHead(500, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ success: false, error: err.message }));
      }
    });
    return;
  }

  // ==========================================================================
  // DIRECT FILE UPLOAD ENDPOINT: POST /api/upload or /api/upload.php?filename=xyz.jpg
  // ==========================================================================
  if (req.method === 'POST' && (cleanPath === '/api/upload' || cleanPath === '/api/upload.php')) {
    try {
      const urlObj = new URL(req.url, `http://${req.headers.host || 'localhost:5500'}`);
      let originalFilename = urlObj.searchParams.get('filename') || ('upload_' + Date.now() + '.jpg');
      
      // Clean filename
      const ext = path.extname(originalFilename).toLowerCase() || '.jpg';
      const cleanBase = path.basename(originalFilename, ext).replace(/[^a-zA-Z0-9_-]/g, '_');
      const uniqueFilename = `${Date.now()}_${cleanBase}${ext}`;
      const targetFilePath = path.join(UPLOAD_DIR, uniqueFilename);

      const writeStream = fs.createWriteStream(targetFilePath);

      req.pipe(writeStream);

      writeStream.on('finish', () => {
        const publicRelativePath = `assets/uploads/${uniqueFilename}`;
        const size = fs.statSync(targetFilePath).size;
        console.log(`[Direct Upload] Saved ${publicRelativePath} (${size} bytes) to disk.`);
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({
          success: true,
          filePath: publicRelativePath,
          filename: uniqueFilename,
          size: size
        }));
      });

      writeStream.on('error', (err) => {
        console.error('Upload stream error:', err);
        res.writeHead(500, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ success: false, error: err.message }));
      });

      return;
    } catch (err) {
      console.error('Upload handling error:', err);
      res.writeHead(500, { 'Content-Type': 'application/json' });
      return res.end(JSON.stringify({ success: false, error: err.message }));
    }
  }

  // ==========================================================================
  // STATIC FILE SERVING
  // ==========================================================================
  let reqPath = req.url.split('?')[0];
  if (reqPath === '/' || reqPath === '') reqPath = '/index.html';
  
  const safePath = path.normalize(reqPath).replace(/^(\.\.[\/\\])+/, '');
  const filePath = path.join(ROOT, safePath);

  fs.stat(filePath, (err, stats) => {
    if (err || !stats.isFile()) {
      res.writeHead(404, { 'Content-Type': 'text/plain' });
      return res.end('404 Not Found');
    }

    const ext = path.extname(filePath).toLowerCase();
    const contentType = MIME_TYPES[ext] || 'application/octet-stream';
    const totalSize = stats.size;

    // Handle range requests for video streaming
    const range = req.headers.range;
    if (range && ext === '.mp4') {
      const parts = range.replace(/bytes=/, "").split("-");
      const start = parseInt(parts[0], 10);
      const end = parts[1] ? parseInt(parts[1], 10) : totalSize - 1;
      const chunksize = (end - start) + 1;
      const file = fs.createReadStream(filePath, { start, end });

      res.writeHead(206, {
        'Content-Range': `bytes ${start}-${end}/${totalSize}`,
        'Accept-Ranges': 'bytes',
        'Content-Length': chunksize,
        'Content-Type': contentType,
        'Cache-Control': 'no-cache, no-store, must-revalidate',
        'Access-Control-Allow-Origin': '*'
      });
      return file.pipe(res);
    }

    res.writeHead(200, {
      'Content-Type': contentType,
      'Content-Length': totalSize,
      'Cache-Control': 'no-cache, no-store, must-revalidate, max-age=0',
      'Pragma': 'no-cache',
      'Expires': '0',
      'Access-Control-Allow-Origin': '*'
    });

    fs.createReadStream(filePath).pipe(res);
  });
});

server.listen(PORT, () => {
  console.log(`Node HTTP Server running at http://localhost:${PORT} with Direct File Upload (/api/upload), zero caching & video range support`);
});
