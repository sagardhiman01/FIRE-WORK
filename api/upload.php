<?php
// =============================================================================
// Ashish Traders Fireworks - Image Upload API (PHP for Hostinger Shared Hosting)
// Endpoint: POST /api/upload.php
// =============================================================================

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: *');
header('Content-Type: application/json; charset=utf-8');

// Handle CORS preflight
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

// Create uploads directory if it doesn't exist
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Check if file was uploaded via multipart form
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['photo'];
    $originalName = basename($file['name']);
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    
    // Allowed extensions
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    if (!in_array($ext, $allowed)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Allowed: jpg, jpeg, png, gif, webp, svg']);
        exit;
    }
    
    // Max 10MB
    if ($file['size'] > 10 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'File too large. Maximum 10MB allowed.']);
        exit;
    }
    
    $cleanBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
    $uniqueName = time() . '_' . $cleanBase . '.' . $ext;
    $targetPath = $uploadDir . $uniqueName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        echo json_encode([
            'success' => true,
            'filePath' => 'assets/uploads/' . $uniqueName,
            'filename' => $uniqueName,
            'size' => filesize($targetPath)
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to save uploaded file']);
    }
    exit;
}

// Check if raw binary was sent (like Node.js server handles)
$rawInput = file_get_contents('php://input');
if (strlen($rawInput) > 0) {
    $filename = isset($_GET['filename']) ? $_GET['filename'] : ('upload_' . time() . '.jpg');
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (empty($ext)) $ext = 'jpg';
    
    $cleanBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($filename, PATHINFO_FILENAME));
    $uniqueName = time() . '_' . $cleanBase . '.' . $ext;
    $targetPath = $uploadDir . $uniqueName;
    
    if (file_put_contents($targetPath, $rawInput) !== false) {
        echo json_encode([
            'success' => true,
            'filePath' => 'assets/uploads/' . $uniqueName,
            'filename' => $uniqueName,
            'size' => filesize($targetPath)
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to write file']);
    }
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'No file received. Send as multipart form (field: photo) or raw binary body.']);
