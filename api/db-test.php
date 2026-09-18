<?php
// =============================================================================
// Ashish Traders Fireworks - Production SQL Database Diagnostic & Health Check
// URL: https://your-domain.com/api/db-test.php
// =============================================================================

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/db.php';

$report = [
    'timestamp'       => date('Y-m-d H:i:s T'),
    'php_version'     => phpversion(),
    'pdo_installed'   => class_exists('PDO'),
    'pdo_mysql'       => extension_loaded('pdo_mysql'),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'document_root'   => $_SERVER['DOCUMENT_ROOT'] ?? '',
    'filesystem_checks' => [
        'data_dir_exists'      => is_dir(__DIR__ . '/../data/'),
        'data_dir_writable'    => is_writable(__DIR__ . '/../data/'),
        'uploads_dir_exists'   => is_dir(__DIR__ . '/../assets/uploads/'),
        'uploads_dir_writable' => is_writable(__DIR__ . '/../assets/uploads/')
    ],
    'database_status' => [
        'configured'  => false,
        'connected'   => false,
        'mode'        => 'JSON File Mode (Fallback)',
        'tables'      => [],
        'counts'      => [],
        'error'       => null
    ]
];

$config = Database::getConfig();
$isConfigured = !empty($config['DB_NAME']);
$configFileExists = file_exists(__DIR__ . '/config.php');
$envFileExists = file_exists(__DIR__ . '/../.env');

$report['database_status']['config_source'] = [
    'api_config_php_exists' => $configFileExists,
    'dot_env_exists'        => $envFileExists,
    'configured_db_host'    => !empty($config['DB_HOST']) ? $config['DB_HOST'] : 'localhost',
    'configured_db_name'    => !empty($config['DB_NAME']) ? $config['DB_NAME'] : '(none)',
    'configured_db_user'    => !empty($config['DB_USER']) ? $config['DB_USER'] : '(none)',
    'has_password'          => !empty($config['DB_PASS'])
];

$pdo = Database::getConnection();

if ($pdo) {
    $report['database_status']['configured'] = true;
    $report['database_status']['connected'] = true;
    $report['database_status']['mode'] = 'MySQL / MariaDB Production SQL Database Active';

    try {
        $tables = ['products', 'brands', 'gallery', 'reviews', 'settings'];
        foreach ($tables as $tbl) {
            $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM `{$tbl}`");
            $cnt = $stmt->fetchColumn();
            $report['database_status']['tables'][$tbl] = 'EXISTS';
            $report['database_status']['counts'][$tbl] = (int)$cnt;
        }

        // Test SELECT sample
        $sampleProd = $pdo->query("SELECT id, name, price, in_stock FROM `products` LIMIT 3")->fetchAll();
        $report['database_status']['sample_products'] = $sampleProd;

        $report['success'] = true;
        $report['message'] = 'Production SQL database is connected and operational.';
    } catch (Exception $e) {
        $report['database_status']['error'] = $e->getMessage();
        $report['success'] = false;
        $report['message'] = 'Database connected but query failed: ' . $e->getMessage();
    }
} else {
    $report['database_status']['configured'] = $isConfigured;
    $report['database_status']['connected'] = false;
    $report['database_status']['error'] = Database::getLastError();
    $report['success'] = true;
    $report['message'] = $isConfigured 
        ? ('SQL configured but connection failed: ' . Database::getLastError())
        : 'No SQL credentials configured. System running safely in JSON disk storage mode.';
    
    // Check JSON files
    $dataDir = __DIR__ . '/../data/';
    foreach (['products.json', 'brands.json', 'gallery.json', 'reviews.json', 'settings.json'] as $f) {
        $exists = file_exists($dataDir . $f);
        $report['database_status']['counts'][$f] = $exists ? 'EXISTS (' . filesize($dataDir . $f) . ' bytes)' : 'MISSING';
    }
}

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
