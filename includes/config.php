<?php
/**
 * =============================================
 * RemoWorkers - Configuration
 * =============================================
 * Loads .env file and provides config helpers
 */

// Load .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        die('.env file not found! Please create one from .env.example');
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) continue;
        
        // Parse key=value
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            if (preg_match('/^"(.*)"$/', $value, $matches)) {
                $value = $matches[1];
            } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
                $value = $matches[1];
            }
            
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// Helper function to get env values
function env($key, $default = null) {
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    
    // Convert string booleans
    switch (strtolower($value)) {
        case 'true':  return true;
        case 'false': return false;
        case 'null':  return null;
    }
    
    return $value;
}

// Load environment
loadEnv(__DIR__ . '/../.env');

// Application config
define('APP_NAME', env('APP_NAME', 'RemoWorkers'));
define('APP_ENV', env('APP_ENV', 'production'));
define('APP_DEBUG', env('APP_DEBUG', false));
define('APP_URL', env('APP_URL', 'http://localhost'));
define('BASE_PATH', dirname(__DIR__));

// Database config
define('DB_HOST', env('DB_HOST', '127.0.0.1'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_DATABASE', env('DB_DATABASE', 'remoworkers'));
define('DB_USERNAME', env('DB_USERNAME', 'root'));
define('DB_PASSWORD', env('DB_PASSWORD', ''));

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection helper
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_DATABASE . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            if (APP_DEBUG) {
                die("Database connection failed: " . $e->getMessage());
            }
            die("Database connection failed. Please check your configuration.");
        }
    }
    return $pdo;
}

/** Ensure freelancer-related user columns exist (safe to call repeatedly). */
function ensureFreelancerSchema() {
    static $done = false;
    if ($done) return;
    $db = getDB();
    $alters = [
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS connects INT NOT NULL DEFAULT 0",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verified_at TIMESTAMP NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verification_token VARCHAR(64) NULL",
    ];
    foreach ($alters as $sql) {
        try { $db->exec($sql); } catch (PDOException $e) { /* column may already exist */ }
    }
    $done = true;
}

// Helper: Get base URL
function baseUrl($path = '') {
    return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
}

// Helper: Redirect
function redirect($url) {
    header("Location: $url");
    exit;
}

// Helper: Check if current route matches
function isRoute($route) {
    $currentRoute = $_GET['route'] ?? '';
    return $currentRoute === $route || strpos($currentRoute, $route) === 0;
}
