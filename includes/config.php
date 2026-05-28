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

$_appUrlHost = parse_url(APP_URL, PHP_URL_HOST);
define('CANONICAL_HOST', strtolower((string) ($_appUrlHost ?: 'localhost')));

// Database config
define('DB_HOST', env('DB_HOST', '127.0.0.1'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_DATABASE', env('DB_DATABASE', 'remoworkers'));
define('DB_USERNAME', env('DB_USERNAME', 'root'));
define('DB_PASSWORD', env('DB_PASSWORD', ''));

// Session (shared across www and apex when on production domain)
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    $cookieDomain = sessionCookieDomain();
    if ($cookieDomain !== null) {
        $secure = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => $cookieDomain,
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    session_start();
}

if (PHP_SAPI !== 'cli') {
    enforceCanonicalHost();
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

require_once __DIR__ . '/countries.php';

/** Encodes job ID to a short, URL-safe encrypted alphanumeric string. */
function encodeJobId($id) {
    $xor = intval($id) ^ 958273;
    $str = strval($xor);
    $encoded = base64_encode($str);
    return str_replace(['+', '/', '='], ['-', '_', ''], $encoded);
}

/** Decodes short, URL-safe encrypted alphanumeric string back to numeric job ID. */
function decodeJobId($encoded) {
    $base64 = str_replace(['-', '_'], ['+', '/'], $encoded);
    $pad = strlen($base64) % 4;
    if ($pad) {
        $base64 .= str_repeat('=', 4 - $pad);
    }
    $decoded = base64_decode($base64);
    if ($decoded === false) return 0;
    $xor = intval($decoded);
    $original = $xor ^ 958273;
    return ($original > 0 && $original < 10000000) ? $original : 0;
}

/** Encodes freelancer user ID to a short, URL-safe string (public profile links). */
function encodeFreelancerId($id) {
    $xor = intval($id) ^ 847291;
    $str = strval($xor);
    $encoded = base64_encode($str);
    return str_replace(['+', '/', '='], ['-', '_', ''], $encoded);
}

/** Decodes public freelancer profile slug back to user ID. */
function decodeFreelancerId($encoded) {
    $base64 = str_replace(['-', '_'], ['+', '/'], $encoded);
    $pad = strlen($base64) % 4;
    if ($pad) {
        $base64 .= str_repeat('=', 4 - $pad);
    }
    $decoded = base64_decode($base64);
    if ($decoded === false) return 0;
    $xor = intval($decoded);
    $original = $xor ^ 847291;
    return ($original > 0 && $original < 10000000) ? $original : 0;
}

/** Ensure freelancer-related user columns exist (safe to call repeatedly). */
function ensureFreelancerSchema() {
    static $done = false;
    if ($done) return;
    $db = getDB();
    
    try {
        $cols = $db->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
        
        if (!in_array('connects', $cols)) {
            $db->exec("ALTER TABLE users ADD COLUMN connects INT NOT NULL DEFAULT 50");
        }
        if (!in_array('email_verified_at', $cols)) {
            $db->exec("ALTER TABLE users ADD COLUMN email_verified_at TIMESTAMP NULL");
        }
        if (!in_array('email_verification_token', $cols)) {
            $db->exec("ALTER TABLE users ADD COLUMN email_verification_token VARCHAR(64) NULL");
        }
        if (!in_array('password_reset_token', $cols)) {
            $db->exec("ALTER TABLE users ADD COLUMN password_reset_token VARCHAR(64) NULL");
        }
        if (!in_array('password_reset_expires_at', $cols)) {
            $db->exec("ALTER TABLE users ADD COLUMN password_reset_expires_at TIMESTAMP NULL");
        }
        if (!in_array('last_active_at', $cols)) {
            $db->exec("ALTER TABLE users ADD COLUMN last_active_at TIMESTAMP NULL DEFAULT NULL");
        }
        if (!in_array('admin_spent_offset', $cols)) {
            $db->exec("ALTER TABLE users ADD COLUMN admin_spent_offset DECIMAL(12, 2) NOT NULL DEFAULT 0.00");
        }
        if (!in_array('admin_hires_offset', $cols)) {
            $db->exec("ALTER TABLE users ADD COLUMN admin_hires_offset INT NOT NULL DEFAULT 0");
        }
    } catch (PDOException $e) {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log("Freelancer Schema Check/Migration failed: " . $e->getMessage());
        }
    }
    
    // Self-healing reviews table initialization
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS reviews (
                id INT AUTO_INCREMENT PRIMARY KEY,
                contract_id INT NOT NULL,
                reviewer_id INT NOT NULL,
                reviewee_id INT NOT NULL,
                rating DECIMAL(3, 2) NOT NULL,
                feedback TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
                FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (reviewee_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } catch (PDOException $e) {
        // Silently continue if table already exists or has engine mismatch
    }

    // Self-healing job_invitations table initialization
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS job_invitations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                job_id INT NOT NULL,
                client_id INT NOT NULL,
                freelancer_id INT NOT NULL,
                message TEXT,
                status ENUM('pending', 'accepted', 'declined') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
                FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (freelancer_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } catch (PDOException $e) {
        // Silently continue
    }

    // Self-healing disputes table initialization
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS disputes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                contract_id INT NOT NULL,
                raised_by INT NOT NULL,
                reason TEXT NOT NULL,
                status ENUM('open', 'resolved', 'closed') DEFAULT 'open',
                resolution_notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
                FOREIGN KEY (raised_by) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } catch (PDOException $e) {
        // Silently continue
    }
    
    $done = true;
}

/** Ensure platform dynamic settings table exists and has defaults. */
function ensurePlatformSettingsTable() {
    static $initialized = false;
    if ($initialized) return;
    
    $db = getDB();
    
    // Check if table exists first using SHOW TABLES (does not cause implicit commit in MySQL)
    $stmt = $db->prepare("SHOW TABLES LIKE 'platform_settings'");
    $stmt->execute();
    $tableExists = (bool)$stmt->fetch();
    
    if (!$tableExists) {
        $sql = "CREATE TABLE IF NOT EXISTS platform_settings (
            setting_key VARCHAR(64) PRIMARY KEY,
            setting_value VARCHAR(255) NOT NULL,
            description TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $db->exec($sql);
    }
    
    // Seed default settings if they do not exist
    $defaults = [
        'freelancer_fee_fixed' => ['10.00', 'Platform fee percentage charged to freelancers for Fixed Price contracts.'],
        'freelancer_fee_hourly' => ['10.00', 'Platform fee percentage charged to freelancers for Hourly contracts.'],
        'freelancer_fee_monthly' => ['10.00', 'Platform fee percentage charged to freelancers for Monthly contracts.'],
        'client_fee_fixed' => ['0.00', 'Platform fee percentage charged to clients for Fixed Price contracts.'],
        'client_fee_hourly' => ['0.00', 'Platform fee percentage charged to clients for Hourly contracts.'],
        'client_fee_monthly' => ['0.00', 'Platform fee percentage charged to clients for Monthly contracts.'],
        'google_analytics_enabled' => ['0', 'Enable Google Analytics tracking on the public site (1 = on, 0 = off).'],
        'google_analytics_id' => ['', 'Google Analytics 4 Measurement ID (e.g. G-XXXXXXXXXX).']
    ];
    
    $checkStmt = $db->prepare("SELECT COUNT(*) FROM platform_settings WHERE setting_key = ?");
    $insertStmt = $db->prepare("INSERT INTO platform_settings (setting_key, setting_value, description) VALUES (?, ?, ?)");
    
    foreach ($defaults as $key => $info) {
        $checkStmt->execute([$key]);
        if ($checkStmt->fetchColumn() == 0) {
            $insertStmt->execute([$key, $info[0], $info[1]]);
        }
    }

    // Ensure contracts table supports 'paused' status (using SHOW COLUMNS first to avoid implicit DDL commits)
    static $statusSchemaChecked = false;
    if (!$statusSchemaChecked) {
        try {
            $stmt = $db->query("SHOW COLUMNS FROM contracts LIKE 'status'");
            $col = $stmt->fetch();
            if ($col && strpos($col['Type'], "'paused'") === false) {
                // The status ENUM doesn't support 'paused' yet, run DDL alter
                $db->exec("ALTER TABLE contracts MODIFY COLUMN status ENUM('active', 'paused', 'completed', 'cancelled', 'disputed') DEFAULT 'active'");
            }
        } catch (PDOException $e) {
            // Table or columns may not exist yet
        }
        $statusSchemaChecked = true;
    }
    
    $initialized = true;
}

/** Ensure job_categories table exists and has baseline categories. */
function ensureJobCategoriesTable(): void
{
    static $initialized = false;
    if ($initialized) {
        return;
    }

    $db = getDB();
    $db->exec("
        CREATE TABLE IF NOT EXISTS job_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(191) NOT NULL UNIQUE,
            image VARCHAR(255) NULL,
            status ENUM('active','inactive') NOT NULL DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $defaults = [
        'Accounting & Consulting',
        'Admin Support',
        'Customer Service',
        'Data Science & Analytics',
        'Design & Creative',
        'Engineering & Architecture',
        'IT & Networking',
        'Legal',
        'Sales & Marketing',
        'Translation',
        'Web, Mobile & Software Dev',
        'Writing',
    ];

    $checkStmt = $db->prepare("SELECT COUNT(*) FROM job_categories WHERE name = ?");
    $insertStmt = $db->prepare("INSERT INTO job_categories (name, status) VALUES (?, 'active')");
    foreach ($defaults as $name) {
        $checkStmt->execute([$name]);
        if ((int)$checkStmt->fetchColumn() === 0) {
            $insertStmt->execute([$name]);
        }
    }

    $initialized = true;
}

/**
 * @return list<array{id:int,name:string,image:?string,status:string}>
 */
function getJobCategories(bool $activeOnly = false): array
{
    try {
        ensureJobCategoriesTable();
        $db = getDB();
        if ($activeOnly) {
            $stmt = $db->query("SELECT id, name, image, status FROM job_categories WHERE status = 'active' ORDER BY name ASC");
        } else {
            $stmt = $db->query("SELECT id, name, image, status FROM job_categories ORDER BY name ASC");
        }
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return is_array($rows) ? $rows : [];
    } catch (Throwable $e) {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log('getJobCategories failed: ' . $e->getMessage());
        }
        return [];
    }
}

/** Get a dynamic platform setting value. */
function getPlatformSetting($key, $default = 0) {
    try {
        ensurePlatformSettingsTable();
        $db = getDB();
        $stmt = $db->prepare("SELECT setting_value FROM platform_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (float)$val : (float)$default;
    } catch (PDOException $e) {
        return (float)$default;
    }
}

/** Get a platform setting as a string (for IDs, flags, etc.). */
function getPlatformSettingString($key, $default = '') {
    try {
        ensurePlatformSettingsTable();
        $db = getDB();
        $stmt = $db->prepare("SELECT setting_value FROM platform_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (string)$val : (string)$default;
    } catch (PDOException $e) {
        return (string)$default;
    }
}

/** Cookie domain so login works on both www and apex (e.g. .remoworkers.com). */
function sessionCookieDomain(): ?string {
    $host = CANONICAL_HOST;
    if ($host === 'localhost' || $host === '127.0.0.1' || filter_var($host, FILTER_VALIDATE_IP)) {
        return null;
    }
    $bare = (strpos($host, 'www.') === 0) ? substr($host, 4) : $host;
    return '.' . $bare;
}

/** Hostnames allowed for CORS (apex + www of APP_URL domain). */
function corsAllowedOrigins(): array {
    $hosts = [];
    foreach ([CANONICAL_HOST] as $host) {
        if ($host === '' || $host === 'localhost') {
            continue;
        }
        $bare = (strpos($host, 'www.') === 0) ? substr($host, 4) : $host;
        $hosts[] = $bare;
        $hosts[] = 'www.' . $bare;
    }
    $hosts = array_unique($hosts);
    $origins = [];
    foreach ($hosts as $host) {
        $origins[] = 'https://' . $host;
        if (APP_ENV === 'development') {
            $origins[] = 'http://' . $host;
        }
    }
    return $origins;
}

/** Send CORS headers when browser Origin is www vs apex (backup if redirect is skipped). */
function applyCorsHeaders(): bool {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin === '' || !in_array($origin, corsAllowedOrigins(), true)) {
        return false;
    }
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
    header('Vary: Origin');
    return true;
}

function handleCorsPreflight(): void {
    applyCorsHeaders();
    http_response_code(204);
    exit;
}

/**
 * 301 redirect www <-> apex so the site always matches APP_URL host (fixes login CORS).
 */
function enforceCanonicalHost(): void {
    if (empty($_SERVER['HTTP_HOST'])) {
        return;
    }

    $current = strtolower((string) $_SERVER['HTTP_HOST']);
    $canonical = CANONICAL_HOST;

    if ($current === $canonical) {
        return;
    }

    if (APP_ENV === 'development' && in_array($current, ['localhost', '127.0.0.1'], true)) {
        return;
    }

    $bare = (strpos($canonical, 'www.') === 0) ? substr($canonical, 4) : $canonical;
    $wwwHost = 'www.' . $bare;
    $isPair = ($current === $bare || $current === $wwwHost);
    if (!$isPair) {
        return;
    }

    $scheme = 'http';
    if (
        (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        || (!empty($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443')
    ) {
        $scheme = 'https';
    }

    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    header('Location: ' . $scheme . '://' . $canonical . $uri, true, 301);
    exit;
}

// Build base URL from the current HTTP request (avoids www vs apex CORS mismatches).
function requestBaseUrl(): ?string {
    if (PHP_SAPI === 'cli' || empty($_SERVER['HTTP_HOST'])) {
        return null;
    }

    $scheme = 'http';
    if (
        (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        || (!empty($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443')
    ) {
        $scheme = 'https';
    }

    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $dir = str_replace('\\', '/', dirname($scriptName));
    if ($dir === '/' || $dir === '\\' || $dir === '.') {
        $dir = '';
    }

    return $scheme . '://' . $_SERVER['HTTP_HOST'] . $dir . '/';
}

/** AJAX login/register (FormData + hidden ajax=1, or legacy X-Requested-With). */
function isAjaxRequest(): bool {
    if (isset($_POST['ajax']) && (string) $_POST['ajax'] === '1') {
        return true;
    }
    $xrw = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    return strtolower($xrw) === 'xmlhttprequest';
}

// Helper: Application base URL (APP_URL host/path only — not the current PHP script folder)
/**
 * Public URL for a stored avatar only if the file exists on disk.
 */
function publicAvatarUrl(?string $avatarUrl): string
{
    if ($avatarUrl === null || trim($avatarUrl) === '') {
        return '';
    }
    $relative = ltrim(trim($avatarUrl), '/');
    $fullPath = dirname(__DIR__) . '/' . $relative;
    if (!is_file($fullPath)) {
        return '';
    }
    return baseUrl($relative);
}

function appBasePathPrefix(): string
{
    $appParts = parse_url(APP_URL);
    $pathPrefix = $appParts['path'] ?? '/';
    if ($pathPrefix === '' || $pathPrefix === '/') {
        return '/';
    }
    return '/' . trim($pathPrefix, '/') . '/';
}

function baseUrl($path = '') {
    static $base = null;
    if ($base === null) {
        $pathPrefix = appBasePathPrefix();
        $detected = requestBaseUrl();
        if ($detected !== null) {
            // Use current host/scheme (www vs apex, localhost) but APP_URL path — not the script folder.
            $parts = parse_url($detected);
            $scheme = $parts['scheme'] ?? 'http';
            $host = $parts['host'] ?? CANONICAL_HOST;
            $port = isset($parts['port']) ? ':' . $parts['port'] : '';
            $base = $scheme . '://' . $host . $port . $pathPrefix;
        } else {
            $appParts = parse_url(APP_URL);
            $scheme = $appParts['scheme'] ?? 'http';
            $base = $scheme . '://' . CANONICAL_HOST . $pathPrefix;
        }
    }
    return rtrim($base, '/') . '/' . ltrim($path, '/');
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

/**
 * @param array<string, mixed> $user
 * @param array{completed: int, active: int, cancelled: int, disputed: int, earned: float, reviews: int, satisfied: int, avg_rating: ?float} $agg
 * @return array<string, mixed>
 */
function computeFreelancerStats(array $user, array $agg): array
{
    $completedCount = (int) ($agg['completed'] ?? 0);
    $cancelledCount = (int) ($agg['cancelled'] ?? 0);
    $disputedCount = (int) ($agg['disputed'] ?? 0);
    $totalEarned = (float) ($agg['earned'] ?? 0);
    $reviewsCount = (int) ($agg['reviews'] ?? 0);
    $satisfiedCount = (int) ($agg['satisfied'] ?? 0);
    $avgRatingRaw = $agg['avg_rating'] ?? null;
    $avgRating = $avgRatingRaw !== null ? number_format((float) $avgRatingRaw, 1) : '0.0';

    $completeness = 40;
    if (!empty($user['bio'])) {
        $completeness += 20;
    }
    $skills = !empty($user['skills']) ? json_decode((string) $user['skills'], true) : [];
    if (is_array($skills) && !empty($skills)) {
        $completeness += 20;
    }
    if (!empty($user['title']) && (float) ($user['hourly_rate'] ?? 0) > 0) {
        $completeness += 10;
    }
    if (!empty($user['avatar_url'])) {
        $completeness += 10;
    }
    $completeness = min(100, $completeness);

    $totalClosed = $completedCount + $cancelledCount + $disputedCount;
    if ($reviewsCount > 0) {
        $jssVal = (int) round(($satisfiedCount / $reviewsCount) * 100);
        $jssVal = max(60, min(100, $jssVal));
        $jss = $jssVal . '%';
    } elseif ($totalClosed === 0) {
        $jssVal = null;
        $jss = 'N/A';
    } else {
        $jssVal = (int) round(($completedCount / $totalClosed) * 100);
        $jssVal = max(60, min(100, $jssVal));
        $jss = $jssVal . '%';
    }

    $badge = null;
    $badge_label = '';
    if ($completeness === 100) {
        if ($jssVal === null || $totalClosed === 0) {
            if ($totalEarned < 1000) {
                $badge = 'rising_talent';
                $badge_label = 'Rising Talent';
            }
        } elseif ($jssVal >= 90) {
            if ($totalEarned >= 10000) {
                $badge = 'expert_vetted';
                $badge_label = 'Expert Vetted';
            } elseif ($totalEarned >= 5000) {
                $badge = 'top_rated_plus';
                $badge_label = 'Top Rated Plus';
            } elseif ($totalEarned >= 1000) {
                $badge = 'top_rated';
                $badge_label = 'Top Rated';
            } else {
                $badge = 'rising_talent';
                $badge_label = 'Rising Talent';
            }
        }
    }

    $rating = $reviewsCount > 0 ? $avgRating : '0.0';
    if ($reviewsCount === 0 && $completedCount > 0) {
        $rating = '5.0';
    }

    return [
        'total_earned' => $totalEarned,
        'completed_contracts' => $completedCount,
        'active_contracts' => (int) ($agg['active'] ?? 0),
        'jss' => $jss,
        'jss_val' => $jssVal,
        'completeness' => $completeness,
        'badge' => $badge,
        'badge_label' => $badge_label,
        'rating' => $rating,
        'reviews_count' => $reviewsCount > 0 ? $reviewsCount : $completedCount,
    ];
}

/**
 * @param list<int> $freelancerIds
 * @param array<int, array<string, mixed>> $usersById
 * @return array<int, array<string, mixed>>
 */
function getFreelancerStatsBatch(array $freelancerIds, array $usersById = []): array
{
    $freelancerIds = array_values(array_unique(array_filter(array_map('intval', $freelancerIds), static fn ($id) => $id > 0)));
    if ($freelancerIds === []) {
        return [];
    }

    $empty = [
        'total_earned' => 0.0,
        'completed_contracts' => 0,
        'active_contracts' => 0,
        'jss' => 'N/A',
        'jss_val' => null,
        'completeness' => 0,
        'badge' => null,
        'badge_label' => '',
        'rating' => '0.0',
        'reviews_count' => 0,
    ];

    $contractAgg = [];
    $earned = [];
    $reviewAgg = [];

    try {
        $db = getDB();
        $placeholders = implode(',', array_fill(0, count($freelancerIds), '?'));

        $stmt = $db->prepare(
            "SELECT freelancer_id, status, COUNT(*) AS cnt
             FROM contracts
             WHERE freelancer_id IN ($placeholders)
             GROUP BY freelancer_id, status"
        );
        $stmt->execute($freelancerIds);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $fid = (int) ($row['freelancer_id'] ?? 0);
            if ($fid <= 0) {
                continue;
            }
            if (!isset($contractAgg[$fid])) {
                $contractAgg[$fid] = ['completed' => 0, 'active' => 0, 'cancelled' => 0, 'disputed' => 0];
            }
            $status = (string) ($row['status'] ?? '');
            if (isset($contractAgg[$fid][$status])) {
                $contractAgg[$fid][$status] = (int) $row['cnt'];
            }
        }

        $stmt = $db->prepare(
            "SELECT payee_id, COALESCE(SUM(amount), 0) AS earned
             FROM payments
             WHERE payee_id IN ($placeholders)
               AND status = 'completed'
               AND transaction_id NOT LIKE 'ESC-%'
             GROUP BY payee_id"
        );
        $stmt->execute($freelancerIds);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $earned[(int) $row['payee_id']] = (float) $row['earned'];
        }

        $stmt = $db->prepare(
            "SELECT reviewee_id,
                    COUNT(*) AS reviews,
                    AVG(rating) AS avg_rating,
                    SUM(CASE WHEN rating >= 4.0 THEN 1 ELSE 0 END) AS satisfied
             FROM reviews
             WHERE reviewee_id IN ($placeholders)
             GROUP BY reviewee_id"
        );
        $stmt->execute($freelancerIds);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $fid = (int) ($row['reviewee_id'] ?? 0);
            $reviewAgg[$fid] = [
                'reviews' => (int) ($row['reviews'] ?? 0),
                'avg_rating' => $row['avg_rating'] !== null ? (float) $row['avg_rating'] : null,
                'satisfied' => (int) ($row['satisfied'] ?? 0),
            ];
        }

        $missingUsers = [];
        foreach ($freelancerIds as $fid) {
            if (!isset($usersById[$fid])) {
                $missingUsers[] = $fid;
            }
        }
        if ($missingUsers !== []) {
            $ph = implode(',', array_fill(0, count($missingUsers), '?'));
            $stmt = $db->prepare("SELECT * FROM users WHERE id IN ($ph)");
            $stmt->execute($missingUsers);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $usersById[(int) $row['id']] = $row;
            }
        }
    } catch (Throwable $e) {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log('getFreelancerStatsBatch: ' . $e->getMessage());
        }
        $out = [];
        foreach ($freelancerIds as $fid) {
            $out[$fid] = $empty;
        }
        return $out;
    }

    $out = [];
    foreach ($freelancerIds as $fid) {
        $user = $usersById[$fid] ?? null;
        if (!$user) {
            $out[$fid] = $empty;
            continue;
        }
        $c = $contractAgg[$fid] ?? ['completed' => 0, 'active' => 0, 'cancelled' => 0, 'disputed' => 0];
        $r = $reviewAgg[$fid] ?? ['reviews' => 0, 'avg_rating' => null, 'satisfied' => 0];
        $out[$fid] = computeFreelancerStats($user, [
            'completed' => $c['completed'],
            'active' => $c['active'],
            'cancelled' => $c['cancelled'],
            'disputed' => $c['disputed'],
            'earned' => $earned[$fid] ?? 0.0,
            'reviews' => $r['reviews'],
            'satisfied' => $r['satisfied'],
            'avg_rating' => $r['avg_rating'],
        ]);
    }

    return $out;
}

// Helper: Calculate Freelancer JSS, Profile Completeness, and Upwork badges dynamically
function getFreelancerStats($freelancerId)
{
    $freelancerId = (int) $freelancerId;
    if ($freelancerId <= 0) {
        return [
            'total_earned' => 0.0,
            'completed_contracts' => 0,
            'active_contracts' => 0,
            'jss' => 'N/A',
            'jss_val' => null,
            'completeness' => 0,
            'badge' => null,
            'badge_label' => '',
            'rating' => '0.0',
            'reviews_count' => 0,
        ];
    }
    $batch = getFreelancerStatsBatch([$freelancerId]);
    return $batch[$freelancerId] ?? [
        'total_earned' => 0.0,
        'completed_contracts' => 0,
        'active_contracts' => 0,
        'jss' => 'N/A',
        'jss_val' => null,
        'completeness' => 0,
        'badge' => null,
        'badge_label' => '',
        'rating' => '0.0',
        'reviews_count' => 0,
    ];
}
