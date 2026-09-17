import sys
from http.server import ThreadingHTTPServer, SimpleHTTPRequestHandler

PORT = 5500

class RobustNoCacheHandler(SimpleHTTPRequestHandler):
    def end_headers(self):
        self.send_header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
        self.send_header('Pragma', 'no-cache')
        self.send_header('Expires', '0')
        self.send_header('Access-Control-Allow-Origin', '*')
        super().end_headers()

    def log_message(self, format, *args):
        sys.stderr.write(f"[{self.log_date_time_string()}] {format % args}\n")
        sys.stderr.flush()

if __name__ == '__main__':
    ThreadingHTTPServer.allow_reuse_address = True
    server = ThreadingHTTPServer(('0.0.0.0', PORT), RobustNoCacheHandler)
    print(f"Threading HTTP Server running on http://localhost:{PORT} with zero caching", flush=True)
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        pass
