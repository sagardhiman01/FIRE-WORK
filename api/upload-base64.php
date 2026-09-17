<?php
// =============================================================================
// Ashish Traders Fireworks - Base64 Image Upload API (PHP for Hostinger)
// Endpoint: POST /api/upload-base64.php
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

$uploadDir = __DIR__ . '/../assets/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput, true);

if (!$payload || empty($payload['base64'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No base64 data provided']);
    exit;
}

$base64 = $payload['base64'];
$filename = isset($payload['filename']) ? $payload['filename'] : 'photo.jpg';

// Parse data URL
$ext = 'jpg';
if (preg_match('/^data:([A-Za-z-+\/]+);base64,(.+)$/', $base64, $matches)) {
    $mime = strtolower($matches[1]);
    $data = base64_decode($matches[2]);
    
    if (strpos($mime, 'png') !== false) $ext = 'png';
    elseif (strpos($mime, 'webp') !== false) $ext = 'webp';
    elseif (strpos($mime, 'gif') !== false) $ext = 'gif';
} else {
    $data = base64_decode($base64);
}

if ($data === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid base64 data']);
    exit;
}

$cleanBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($filename, PATHINFO_FILENAME));
$uniqueName = time() . '_' . $cleanBase . '.' . $ext;
$targetPath = $uploadDir . $uniqueName;

if (file_put_contents($targetPath, $data) !== false) {
    echo json_encode([
        'success' => true,
        'filePath' => 'assets/uploads/' . $uniqueName,
        'filename' => $uniqueName,
        'size' => strlen($data)
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to write file']);
}
