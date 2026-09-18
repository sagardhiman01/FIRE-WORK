<?php
// =============================================================================
// Ashish Traders Fireworks - Image Upload API (PHP for Hostinger / Shared Hosting)
// Endpoint: POST /api/upload.php
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
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}
@chmod($uploadDir, 0777);

// Allowed extensions
$allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

// Method 1: Multipart Form Data (accepts 'photo', 'image', 'file', or any form field)
$file = null;
if (!empty($_FILES)) {
    if (isset($_FILES['photo']) && is_array($_FILES['photo']) && isset($_FILES['photo']['error'])) {
        $file = $_FILES['photo'];
    } elseif (isset($_FILES['image']) && is_array($_FILES['image']) && isset($_FILES['image']['error'])) {
        $file = $_FILES['image'];
    } elseif (isset($_FILES['file']) && is_array($_FILES['file']) && isset($_FILES['file']['error'])) {
        $file = $_FILES['file'];
    } else {
        $first = reset($_FILES);
        if (is_array($first) && isset($first['error'])) {
            $file = $first;
        }
    }
}

if ($file && $file['error'] === UPLOAD_ERR_OK) {
    $originalName = basename($file['name']);
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (empty($ext)) $ext = 'jpg';
    
    if (!in_array($ext, $allowedExts)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Allowed: jpg, jpeg, png, gif, webp, svg']);
        exit;
    }
    
    $cleanBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
    if (empty($cleanBase)) $cleanBase = 'photo';
    $uniqueName = time() . '_' . substr(md5(uniqid()), 0, 6) . '_' . $cleanBase . '.' . $ext;
    $targetPath = $uploadDir . $uniqueName;
    
    $saved = @move_uploaded_file($file['tmp_name'], $targetPath);
    if (!$saved) {
        $saved = @copy($file['tmp_name'], $targetPath);
    }
    
    if ($saved) {
        @chmod($targetPath, 0664);
        echo json_encode([
            'success'  => true,
            'filePath' => 'assets/uploads/' . $uniqueName,
            'filename' => $uniqueName,
            'size'     => filesize($targetPath)
        ]);
        exit;
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to save uploaded file to assets/uploads. Please check folder write permissions.']);
        exit;
    }
}

// Method 2: Raw Binary / Stream Upload (URL: /api/upload.php?filename=xyz.jpg)
$rawInput = file_get_contents('php://input');
if (!empty($rawInput)) {
    $filename = isset($_GET['filename']) ? $_GET['filename'] : ('upload_' . time() . '.jpg');
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (empty($ext) || !in_array($ext, $allowedExts)) $ext = 'jpg';
    
    $cleanBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($filename, PATHINFO_FILENAME));
    if (empty($cleanBase)) $cleanBase = 'photo';
    $uniqueName = time() . '_' . substr(md5(uniqid()), 0, 6) . '_' . $cleanBase . '.' . $ext;
    $targetPath = $uploadDir . $uniqueName;
    
    if (@file_put_contents($targetPath, $rawInput) !== false) {
        @chmod($targetPath, 0664);
        echo json_encode([
            'success'  => true,
            'filePath' => 'assets/uploads/' . $uniqueName,
            'filename' => $uniqueName,
            'size'     => filesize($targetPath)
        ]);
        exit;
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to write raw file. Check folder permissions on assets/uploads.']);
        exit;
    }
}

// If upload error code exists in $_FILES, give helpful message
if ($file && $file['error'] !== UPLOAD_ERR_OK) {
    $err = $file['error'];
    $msg = 'Upload error (code ' . $err . ')';
    if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
        $msg = 'File exceeds maximum upload size allowed by server.';
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'No file received. Send multipart form or binary body.']);
