<?php
// =============================================================================
// Ashish Traders Fireworks - Save Data API (PHP for Hostinger / Shared Hosting)
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

$dataDir = __DIR__ . '/../data/';
if (!is_dir($dataDir)) {
    @mkdir($dataDir, 0777, true);
}
@chmod($dataDir, 0777);

$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput, true);

if (!$payload || empty($payload['type'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid payload']);
    exit;
}

$allowedTypes = ['products', 'brands', 'reviews', 'gallery', 'settings'];
if (!in_array($payload['type'], $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid data type']);
    exit;
}

$filePath = $dataDir . $payload['type'] . '.json';
$jsonData = json_encode($payload['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

if (@file_put_contents($filePath, $jsonData) !== false) {
    @chmod($filePath, 0666);
    $count = is_array($payload['data']) ? count($payload['data']) . ' items' : 'object';
    echo json_encode(['success' => true, 'type' => $payload['type'], 'info' => "Saved {$count}"]);
} else {
    // Retry with forced permissions
    @chmod($dataDir, 0777);
    @chmod($filePath, 0666);
    if (@file_put_contents($filePath, $jsonData) !== false) {
        $count = is_array($payload['data']) ? count($payload['data']) . ' items' : 'object';
        echo json_encode(['success' => true, 'type' => $payload['type'], 'info' => "Saved {$count}"]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to write data file. Please verify write permissions on data/ folder.']);
    }
}
