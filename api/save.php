<?php
// =============================================================================
// Ashish Traders Fireworks - Save Data API (Production SQL & JSON Dual-Sync)
// Endpoint: POST /api/save.php
// =============================================================================

error_reporting(0);
ini_set('display_errors', '0');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: *');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'POST method required']);
    exit;
}

require_once __DIR__ . '/db.php';

$rawInput = file_get_contents('php://input');
// Strip UTF-8 BOM if present
$rawInput = preg_replace('/^\xEF\xBB\xBF/', '', trim($rawInput));
$payload = json_decode($rawInput, true);

if (!$payload || empty($payload['type'])) {
    http_response_code(400);
    $jsonErr = json_last_error_msg();
    echo json_encode([
        'success' => false,
        'error' => 'Invalid JSON payload' . ($jsonErr !== 'No error' ? (': ' . $jsonErr) : '')
    ]);
    exit;
}

$allowedTypes = ['products', 'brands', 'reviews', 'gallery', 'settings'];
if (!in_array($payload['type'], $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid data type: ' . htmlspecialchars($payload['type'])]);
    exit;
}

$type = $payload['type'];
$data = $payload['data'];
$sqlSuccess = false;

// 1. Save to Production SQL Database (if connected)
if (Database::getConnection()) {
    switch ($type) {
        case 'products':
            $sqlSuccess = Database::saveProducts($data);
            break;
        case 'brands':
            $sqlSuccess = Database::saveBrands($data);
            break;
        case 'gallery':
            $sqlSuccess = Database::saveGallery($data);
            break;
        case 'reviews':
            $sqlSuccess = Database::saveReviews($data);
            break;
        case 'settings':
            $sqlSuccess = Database::saveSettings($data);
            break;
    }
}

// 2. Also save to JSON file as persistent disk backup
$dataDir = __DIR__ . '/../data/';
if (!is_dir($dataDir)) {
    @mkdir($dataDir, 0777, true);
}
@chmod($dataDir, 0777);

$filePath = $dataDir . $type . '.json';
$jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
$jsonSuccess = (@file_put_contents($filePath, $jsonData) !== false);

if (!$jsonSuccess) {
    @chmod($dataDir, 0777);
    @chmod($filePath, 0666);
    $jsonSuccess = (@file_put_contents($filePath, $jsonData) !== false);
}

if ($sqlSuccess || $jsonSuccess) {
    $count = is_array($data) ? count($data) . ' items' : 'object';
    $storage = $sqlSuccess ? ($jsonSuccess ? 'SQL Database & JSON File' : 'SQL Database') : 'JSON File';
    echo json_encode([
        'success' => true,
        'type'    => $type,
        'storage' => $storage,
        'info'    => "Saved {$count} to {$storage}"
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to write data to database or file']);
}
