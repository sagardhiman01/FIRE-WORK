const http = require('http');
const fs = require('fs');
const path = require('path');

const PORT = 5500;
const ROOT = path.resolve(__dirname);
const UPLOAD_DIR = path.join(ROOT, 'assets', 'uploads');

if (!fs.existsSync(UPLOAD_DIR)) {
  fs.mkdirSync(UPLOAD_DIR, { recursive: true });
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

const server = http.createServer((req, res) => {
  // CORS Preflight
  if (req.method === 'OPTIONS') {
    res.writeHead(204, {
      'Access-Control-Allow-Origin': '*',
      'Access-Control-Allow-Methods': 'GET, POST, OPTIONS',
      'Access-Control-Allow-Headers': '*'
    });
    return res.end();
  }

  // ==========================================================================
  // DIRECT FILE UPLOAD ENDPOINT: POST /api/upload?filename=xyz.jpg
  // ==========================================================================
  if (req.method === 'POST' && req.url.startsWith('/api/upload')) {
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
        res.writeHead(200, {
          'Content-Type': 'application/json',
          'Access-Control-Allow-Origin': '*'
        });
        res.end(JSON.stringify({
          success: true,
          filePath: publicRelativePath,
          filename: uniqueFilename,
          size: fs.statSync(targetFilePath).size
        }));
      });

      writeStream.on('error', (err) => {
        console.error('Upload stream error:', err);
        res.writeHead(500, {
          'Content-Type': 'application/json',
          'Access-Control-Allow-Origin': '*'
        });
        res.end(JSON.stringify({ success: false, error: err.message }));
      });

      return;
    } catch (err) {
      console.error('Upload handling error:', err);
      res.writeHead(500, {
        'Content-Type': 'application/json',
        'Access-Control-Allow-Origin': '*'
      });
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
