<?php
// =============================================================================
// Ashish Traders Fireworks - Save Data API (PHP for Hostinger Shared Hosting)
// Endpoint: POST /api/save.php
// =============================================================================

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: *');
header('Content-Type: application/json; charset=utf-8');

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
    mkdir($dataDir, 0755, true);
}

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

if (file_put_contents($filePath, $jsonData) !== false) {
    $count = is_array($payload['data']) ? count($payload['data']) . ' items' : 'object';
    echo json_encode(['success' => true, 'type' => $payload['type'], 'info' => "Saved {$count}"]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to write data file']);
}
