<?php
// =============================================================================
// Ashish Traders Fireworks - Review Submit API (PHP for Hostinger Shared Hosting)
// Endpoint: POST /api/review-submit.php
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

if (!$payload || empty($payload['name']) || empty($payload['review'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Name and review are required']);
    exit;
}

// Read existing reviews
$reviewsFile = $dataDir . 'reviews.json';
$existing = [];
if (file_exists($reviewsFile)) {
    $content = file_get_contents($reviewsFile);
    $decoded = json_decode($content, true);
    if (is_array($decoded)) $existing = $decoded;
}

$newRev = [
    'id'       => 'rev-' . substr(strval(time()), -6),
    'name'     => trim($payload['name']),
    'city'     => trim(isset($payload['city']) ? $payload['city'] : 'Dehradun'),
    'rating'   => intval(isset($payload['rating']) ? $payload['rating'] : 5),
    'date'     => isset($payload['date']) ? $payload['date'] : 'Festive Season 2026',
    'review'   => trim($payload['review']),
    'verified' => true
];

array_unshift($existing, $newRev);

$jsonData = json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
if (file_put_contents($reviewsFile, $jsonData) !== false) {
    echo json_encode(['success' => true, 'review' => $newRev]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save review']);
}
