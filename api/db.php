<?php
// =============================================================================
// Ashish Traders Fireworks - Production SQL Database Layer (PDO MySQL)
// Supports MySQL / MariaDB on Hostinger, cPanel, and Custom Hosting
// =============================================================================

error_reporting(0);
ini_set('display_errors', '0');

class Database {
    private static $pdo = null;
    private static $initialized = false;
    private static $lastError = null;

    public static function getLastError() {
        return self::$lastError;
    }

    public static function getConfig() {
        return self::loadConfig();
    }

    public static function getConnection() {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $config = self::loadConfig();

        // If no database name configured, return null for JSON fallback
        if (empty($config['DB_NAME'])) {
            self::$lastError = 'DB_NAME is empty. Configure api/config.php or .env';
            return null;
        }

        $host = !empty($config['DB_HOST']) ? $config['DB_HOST'] : 'localhost';
        $port = !empty($config['DB_PORT']) ? $config['DB_PORT'] : '3306';
        $dbname = $config['DB_NAME'];
        $user = !empty($config['DB_USER']) ? $config['DB_USER'] : 'root';
        $pass = isset($config['DB_PASS']) ? $config['DB_PASS'] : '';

        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            self::$pdo = new PDO($dsn, $user, $pass, $options);

            if (!self::$initialized) {
                self::ensureSchema();
                self::$initialized = true;
            }

            return self::$pdo;
        } catch (PDOException $e) {
            self::$lastError = $e->getMessage();
            error_log('[DB Error] Connection failed: ' . $e->getMessage());
            return null;
        }
    }

    private static function loadConfig() {
        $config = [
            'DB_HOST' => 'localhost',
            'DB_PORT' => '3306',
            'DB_NAME' => '',
            'DB_USER' => '',
            'DB_PASS' => ''
        ];

        // 1. Check local api/config.php file (priority for shared hosting)
        $configFile = __DIR__ . '/config.php';
        if (file_exists($configFile)) {
            $fileConfig = @include($configFile);
            if (is_array($fileConfig)) {
                return array_merge($config, $fileConfig);
            }
        }

        // 2. Check root .env file
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = @file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines) {
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line) || $line[0] === '#') continue;
                    if (strpos($line, '=') !== false) {
                        list($k, $v) = explode('=', $line, 2);
                        $k = trim($k);
                        $v = trim($v, " \t\n\r\0\x0B\"'");
                        if (isset($config[$k])) {
                            $config[$k] = $v;
                        }
                    }
                }
            }
        }

        // 3. Check environment variables (e.g. Hostinger / Docker / Server env)
        foreach (['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS'] as $key) {
            $val = getenv($key);
            if ($val === false && isset($_ENV[$key])) $val = $_ENV[$key];
            if ($val === false && isset($_SERVER[$key])) $val = $_SERVER[$key];
            if ($val !== false && $val !== '') {
                $config[$key] = $val;
            }
        }

        return $config;
    }

    public static function ensureSchema() {
        if (!self::$pdo) return;

        try {
            // Products Table
            self::$pdo->exec("
                CREATE TABLE IF NOT EXISTS `products` (
                    `id` VARCHAR(64) PRIMARY KEY,
                    `name` VARCHAR(255) NOT NULL,
                    `brand` VARCHAR(100) NOT NULL,
                    `category` VARCHAR(100) NOT NULL,
                    `price` INT NOT NULL DEFAULT 0,
                    `original_price` INT DEFAULT 0,
                    `pack_info` VARCHAR(255) DEFAULT '',
                    `image` VARCHAR(500) NOT NULL,
                    `badge` VARCHAR(100) DEFAULT '',
                    `eco_friendly` TINYINT(1) DEFAULT 1,
                    `in_stock` TINYINT(1) DEFAULT 1,
                    `description` TEXT,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            // Brands Table
            self::$pdo->exec("
                CREATE TABLE IF NOT EXISTS `brands` (
                    `id` VARCHAR(64) PRIMARY KEY,
                    `name` VARCHAR(100) NOT NULL,
                    `logo` VARCHAR(500) NOT NULL,
                    `is_image` TINYINT(1) DEFAULT 1,
                    `origin` VARCHAR(255) DEFAULT '',
                    `tagline` VARCHAR(255) DEFAULT '',
                    `reputation` TEXT,
                    `specialties` TEXT,
                    `rating` VARCHAR(100) DEFAULT '',
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            // Gallery Table
            self::$pdo->exec("
                CREATE TABLE IF NOT EXISTS `gallery` (
                    `id` VARCHAR(64) PRIMARY KEY,
                    `title` VARCHAR(255) NOT NULL,
                    `category` VARCHAR(100) NOT NULL,
                    `image` MEDIUMTEXT NOT NULL,
                    `caption` TEXT,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            // Reviews Table
            self::$pdo->exec("
                CREATE TABLE IF NOT EXISTS `reviews` (
                    `id` VARCHAR(64) PRIMARY KEY,
                    `name` VARCHAR(255) NOT NULL,
                    `city` VARCHAR(255) DEFAULT 'Dehradun',
                    `rating` INT DEFAULT 5,
                    `date` VARCHAR(100) DEFAULT '',
                    `review` TEXT NOT NULL,
                    `verified` TINYINT(1) DEFAULT 1,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            // Global Settings Table
            self::$pdo->exec("
                CREATE TABLE IF NOT EXISTS `settings` (
                    `setting_key` VARCHAR(100) PRIMARY KEY,
                    `setting_value` MEDIUMTEXT NOT NULL,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            // Seed initial data if tables are empty
            self::seedInitialData();
        } catch (Exception $e) {
            error_log('[DB Schema Error] ' . $e->getMessage());
        }
    }

    private static function seedInitialData() {
        $dataDir = __DIR__ . '/../data/';

        // Seed products
        $prodCount = self::$pdo->query("SELECT COUNT(*) FROM `products`")->fetchColumn();
        if ($prodCount == 0 && file_exists($dataDir . 'products.json')) {
            $json = json_decode(file_get_contents($dataDir . 'products.json'), true);
            if (is_array($json)) {
                self::saveProducts($json);
            }
        }

        // Seed brands
        $brandCount = self::$pdo->query("SELECT COUNT(*) FROM `brands`")->fetchColumn();
        if ($brandCount == 0 && file_exists($dataDir . 'brands.json')) {
            $json = json_decode(file_get_contents($dataDir . 'brands.json'), true);
            if (is_array($json)) {
                self::saveBrands($json);
            }
        }

        // Seed gallery
        $galCount = self::$pdo->query("SELECT COUNT(*) FROM `gallery`")->fetchColumn();
        if ($galCount == 0 && file_exists($dataDir . 'gallery.json')) {
            $json = json_decode(file_get_contents($dataDir . 'gallery.json'), true);
            if (is_array($json)) {
                self::saveGallery($json);
            }
        }

        // Seed reviews
        $revCount = self::$pdo->query("SELECT COUNT(*) FROM `reviews`")->fetchColumn();
        if ($revCount == 0 && file_exists($dataDir . 'reviews.json')) {
            $json = json_decode(file_get_contents($dataDir . 'reviews.json'), true);
            if (is_array($json)) {
                self::saveReviews($json);
            }
        }

        // Seed settings
        $setCount = self::$pdo->query("SELECT COUNT(*) FROM `settings`")->fetchColumn();
        if ($setCount == 0 && file_exists($dataDir . 'settings.json')) {
            $json = json_decode(file_get_contents($dataDir . 'settings.json'), true);
            if (is_array($json)) {
                self::saveSettings($json);
            }
        }
    }

    // =========================================================================
    // SQL SELECT QUERIES
    // =========================================================================
    public static function getAllData() {
        if (!self::getConnection()) return null;

        try {
            // Products
            $stmt = self::$pdo->query("SELECT * FROM `products`");
            $products = [];
            while ($row = $stmt->fetch()) {
                $products[] = [
                    'id'            => $row['id'],
                    'name'          => $row['name'],
                    'brand'         => $row['brand'],
                    'category'      => $row['category'],
                    'price'         => (int)$row['price'],
                    'originalPrice' => (int)$row['original_price'],
                    'packInfo'      => $row['pack_info'],
                    'image'         => $row['image'],
                    'badge'         => $row['badge'],
                    'ecoFriendly'   => (bool)$row['eco_friendly'],
                    'inStock'       => (bool)$row['in_stock'],
                    'description'   => $row['description']
                ];
            }

            // Brands
            $stmt = self::$pdo->query("SELECT * FROM `brands`");
            $brands = [];
            while ($row = $stmt->fetch()) {
                $brands[] = [
                    'id'          => $row['id'],
                    'name'        => $row['name'],
                    'logo'        => $row['logo'],
                    'isImage'     => (bool)$row['is_image'],
                    'origin'      => $row['origin'],
                    'tagline'     => $row['tagline'],
                    'reputation'  => $row['reputation'],
                    'specialties' => !empty($row['specialties']) ? json_decode($row['specialties'], true) : [],
                    'rating'      => $row['rating']
                ];
            }

            // Gallery
            $stmt = self::$pdo->query("SELECT * FROM `gallery` ORDER BY `created_at` DESC");
            $gallery = [];
            while ($row = $stmt->fetch()) {
                $gallery[] = [
                    'id'       => $row['id'],
                    'title'    => $row['title'],
                    'category' => $row['category'],
                    'image'    => $row['image'],
                    'caption'  => $row['caption']
                ];
            }

            // Reviews
            $stmt = self::$pdo->query("SELECT * FROM `reviews` ORDER BY `created_at` DESC");
            $reviews = [];
            while ($row = $stmt->fetch()) {
                $reviews[] = [
                    'id'       => $row['id'],
                    'name'     => $row['name'],
                    'city'     => $row['city'],
                    'rating'   => (int)$row['rating'],
                    'date'     => $row['date'],
                    'review'   => $row['review'],
                    'verified' => (bool)$row['verified']
                ];
            }

            // Settings
            $stmt = self::$pdo->query("SELECT * FROM `settings`");
            $settings = [];
            while ($row = $stmt->fetch()) {
                $val = json_decode($row['setting_value'], true);
                $settings[$row['setting_key']] = ($val !== null) ? $val : $row['setting_value'];
            }

            return [
                'products' => $products,
                'brands'   => $brands,
                'reviews'  => $reviews,
                'gallery'  => $gallery,
                'settings' => $settings
            ];
        } catch (Exception $e) {
            error_log('[SQL Query Error] ' . $e->getMessage());
            return null;
        }
    }

    // =========================================================================
    // SQL INSERT / UPDATE QUERIES
    // =========================================================================
    public static function saveProducts($products) {
        if (!self::getConnection() || !is_array($products)) return false;

        self::$pdo->beginTransaction();
        try {
            self::$pdo->exec("DELETE FROM `products`");
            $sql = "INSERT INTO `products` (`id`, `name`, `brand`, `category`, `price`, `original_price`, `pack_info`, `image`, `badge`, `eco_friendly`, `in_stock`, `description`) 
                    VALUES (:id, :name, :brand, :category, :price, :original_price, :pack_info, :image, :badge, :eco_friendly, :in_stock, :description)";
            $stmt = self::$pdo->prepare($sql);

            foreach ($products as $p) {
                $stmt->execute([
                    ':id'             => $p['id'],
                    ':name'           => $p['name'] ?? '',
                    ':brand'          => $p['brand'] ?? '',
                    ':category'       => $p['category'] ?? '',
                    ':price'          => (int)($p['price'] ?? 0),
                    ':original_price' => (int)($p['originalPrice'] ?? 0),
                    ':pack_info'      => $p['packInfo'] ?? '',
                    ':image'          => $p['image'] ?? '',
                    ':badge'          => $p['badge'] ?? '',
                    ':eco_friendly'   => !empty($p['ecoFriendly']) ? 1 : 0,
                    ':in_stock'       => (!isset($p['inStock']) || $p['inStock'] !== false) ? 1 : 0,
                    ':description'    => $p['description'] ?? ''
                ]);
            }
            self::$pdo->commit();
            return true;
        } catch (Exception $e) {
            self::$pdo->rollBack();
            error_log('[SQL Save Products Error] ' . $e->getMessage());
            return false;
        }
    }

    public static function saveBrands($brands) {
        if (!self::getConnection() || !is_array($brands)) return false;

        self::$pdo->beginTransaction();
        try {
            self::$pdo->exec("DELETE FROM `brands`");
            $sql = "INSERT INTO `brands` (`id`, `name`, `logo`, `is_image`, `origin`, `tagline`, `reputation`, `specialties`, `rating`) 
                    VALUES (:id, :name, :logo, :is_image, :origin, :tagline, :reputation, :specialties, :rating)";
            $stmt = self::$pdo->prepare($sql);

            foreach ($brands as $b) {
                $specialties = is_array($b['specialties'] ?? null) ? json_encode($b['specialties']) : '[]';
                $stmt->execute([
                    ':id'          => $b['id'] ?? ('brand-' . substr(md5($b['name']), 0, 8)),
                    ':name'        => $b['name'] ?? '',
                    ':logo'        => $b['logo'] ?? '',
                    ':is_image'    => !empty($b['isImage']) ? 1 : 0,
                    ':origin'      => $b['origin'] ?? '',
                    ':tagline'     => $b['tagline'] ?? '',
                    ':reputation'  => $b['reputation'] ?? '',
                    ':specialties' => $specialties,
                    ':rating'      => $b['rating'] ?? ''
                ]);
            }
            self::$pdo->commit();
            return true;
        } catch (Exception $e) {
            self::$pdo->rollBack();
            error_log('[SQL Save Brands Error] ' . $e->getMessage());
            return false;
        }
    }

    public static function saveGallery($gallery) {
        if (!self::getConnection() || !is_array($gallery)) return false;

        self::$pdo->beginTransaction();
        try {
            self::$pdo->exec("DELETE FROM `gallery`");
            $sql = "INSERT INTO `gallery` (`id`, `title`, `category`, `image`, `caption`) 
                    VALUES (:id, :title, :category, :image, :caption)";
            $stmt = self::$pdo->prepare($sql);

            foreach ($gallery as $g) {
                if (empty($g['image'])) continue;
                $stmt->execute([
                    ':id'       => $g['id'] ?? ('gal-' . time() . rand(10, 99)),
                    ':title'    => $g['title'] ?? 'Showcase Photo',
                    ':category' => $g['category'] ?? 'Warehouse & Stock',
                    ':image'    => $g['image'],
                    ':caption'  => $g['caption'] ?? ''
                ]);
            }
            self::$pdo->commit();
            return true;
        } catch (Exception $e) {
            self::$pdo->rollBack();
            error_log('[SQL Save Gallery Error] ' . $e->getMessage());
            return false;
        }
    }

    public static function saveReviews($reviews) {
        if (!self::getConnection() || !is_array($reviews)) return false;

        self::$pdo->beginTransaction();
        try {
            self::$pdo->exec("DELETE FROM `reviews`");
            $sql = "INSERT INTO `reviews` (`id`, `name`, `city`, `rating`, `date`, `review`, `verified`) 
                    VALUES (:id, :name, :city, :rating, :date, :review, :verified)";
            $stmt = self::$pdo->prepare($sql);

            foreach ($reviews as $r) {
                if (empty($r['name']) || empty($r['review'])) continue;
                $stmt->execute([
                    ':id'       => $r['id'] ?? ('rev-' . time() . rand(10, 99)),
                    ':name'     => $r['name'],
                    ':city'     => $r['city'] ?? 'Dehradun',
                    ':rating'   => (int)($r['rating'] ?? 5),
                    ':date'     => $r['date'] ?? 'Festive Season 2026',
                    ':review'   => $r['review'],
                    ':verified' => !empty($r['verified']) ? 1 : 0
                ]);
            }
            self::$pdo->commit();
            return true;
        } catch (Exception $e) {
            self::$pdo->rollBack();
            error_log('[SQL Save Reviews Error] ' . $e->getMessage());
            return false;
        }
    }

    public static function insertSingleReview($review) {
        if (!self::getConnection()) return false;

        try {
            $sql = "INSERT INTO `reviews` (`id`, `name`, `city`, `rating`, `date`, `review`, `verified`) 
                    VALUES (:id, :name, :city, :rating, :date, :review, :verified)";
            $stmt = self::$pdo->prepare($sql);
            return $stmt->execute([
                ':id'       => $review['id'],
                ':name'     => $review['name'],
                ':city'     => $review['city'] ?? 'Dehradun',
                ':rating'   => (int)($review['rating'] ?? 5),
                ':date'     => $review['date'] ?? 'Festive Season 2026',
                ':review'   => $review['review'],
                ':verified' => !empty($review['verified']) ? 1 : 0
            ]);
        } catch (Exception $e) {
            error_log('[SQL Insert Review Error] ' . $e->getMessage());
            return false;
        }
    }

    public static function saveSettings($settings) {
        if (!self::getConnection() || !is_array($settings)) return false;

        self::$pdo->beginTransaction();
        try {
            $sql = "INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES (:k, :v) 
                    ON DUPLICATE KEY UPDATE `setting_value` = :v2";
            $stmt = self::$pdo->prepare($sql);

            foreach ($settings as $key => $val) {
                $encoded = is_array($val) ? json_encode($val) : strval($val);
                $stmt->execute([
                    ':k'  => $key,
                    ':v'  => $encoded,
                    ':v2' => $encoded
                ]);
            }
            self::$pdo->commit();
            return true;
        } catch (Exception $e) {
            self::$pdo->rollBack();
            error_log('[SQL Save Settings Error] ' . $e->getMessage());
            return false;
        }
    }
}
