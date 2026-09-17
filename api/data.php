<?php
// =============================================================================
// Ashish Traders Fireworks - Read All Data API (PHP for Hostinger Shared Hosting)
// Endpoint: GET /api/data.php
// =============================================================================

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: *');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$dataDir = __DIR__ . '/../data/';

function readJsonFile($filename) {
    global $dataDir;
    $filePath = $dataDir . $filename;
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        $decoded = json_decode($content, true);
        if ($decoded !== null) return $decoded;
    }
    return null;
}

$data = [
    'products' => readJsonFile('products.json'),
    'brands'   => readJsonFile('brands.json'),
    'reviews'  => readJsonFile('reviews.json'),
    'gallery'  => readJsonFile('gallery.json'),
    'settings' => readJsonFile('settings.json')
];

echo json_encode($data);
