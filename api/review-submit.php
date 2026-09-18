<?php
// =============================================================================
// Ashish Traders Fireworks - Review Submit API (SQL Database & JSON Dual-Sync)
// Endpoint: POST /api/review-submit.php
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
$payload = json_decode($rawInput, true);

if (!$payload || empty($payload['name']) || empty($payload['review'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Name and review are required']);
    exit;
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

// 1. Insert into SQL Database (if connected)
$sqlSuccess = false;
if (Database::getConnection()) {
    $sqlSuccess = Database::insertSingleReview($newRev);
}

// 2. Also save to JSON file as disk backup
$dataDir = __DIR__ . '/../data/';
if (!is_dir($dataDir)) {
    @mkdir($dataDir, 0777, true);
}
@chmod($dataDir, 0777);

$reviewsFile = $dataDir . 'reviews.json';
$existing = [];
if (file_exists($reviewsFile)) {
    $content = @file_get_contents($reviewsFile);
    if ($content !== false) {
        $decoded = json_decode($content, true);
        if (is_array($decoded)) $existing = $decoded;
    }
}
array_unshift($existing, $newRev);

$jsonData = json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
$jsonSuccess = (@file_put_contents($reviewsFile, $jsonData) !== false);

if (!$jsonSuccess) {
    @chmod($dataDir, 0777);
    @chmod($reviewsFile, 0666);
    $jsonSuccess = (@file_put_contents($reviewsFile, $jsonData) !== false);
}

if ($sqlSuccess || $jsonSuccess) {
    echo json_encode(['success' => true, 'review' => $newRev]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save review']);
}
