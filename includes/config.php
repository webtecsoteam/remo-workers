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
        'client_fee_monthly' => ['0.00', 'Platform fee percentage charged to clients for Monthly contracts.']
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

// Helper: Calculate Freelancer JSS, Profile Completeness, and Upwork badges dynamically
function getFreelancerStats($freelancerId) {
    $db = getDB();
    
    // 1. Fetch completed, active, cancelled, disputed contracts count
    $stmt = $db->prepare("SELECT COUNT(*) FROM contracts WHERE freelancer_id = ? AND status = 'completed'");
    $stmt->execute([$freelancerId]);
    $completedCount = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM contracts WHERE freelancer_id = ? AND status = 'active'");
    $stmt->execute([$freelancerId]);
    $activeCount = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM contracts WHERE freelancer_id = ? AND status = 'cancelled'");
    $stmt->execute([$freelancerId]);
    $cancelledCount = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM contracts WHERE freelancer_id = ? AND status = 'disputed'");
    $stmt->execute([$freelancerId]);
    $disputedCount = (int)$stmt->fetchColumn();

    // 2. Fetch total earned
    $stmt = $db->prepare("SELECT SUM(amount) FROM payments WHERE payee_id = ? AND status = 'completed' AND transaction_id NOT LIKE 'ESC-%'");
    $stmt->execute([$freelancerId]);
    $totalEarned = (float)$stmt->fetchColumn() ?: 0.0;

    // 3. Fetch user details for profile completeness
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$freelancerId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
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
            'reviews_count' => 0
        ];
    }

    // 4. Profile completeness
    $completeness = 40; // Base
    if (!empty($user['bio'])) $completeness += 20;
    $skills = !empty($user['skills']) ? json_decode($user['skills'], true) : [];
    if (!empty($skills)) $completeness += 20;
    if (!empty($user['title']) && (float)($user['hourly_rate'] ?? 0) > 0) $completeness += 10;
    if (!empty($user['avatar_url'])) $completeness += 10;
    $completeness = min(100, $completeness);

    // 5. Fetch received reviews count and average rating from reviews table
    $stmt = $db->prepare("SELECT COUNT(*), AVG(rating) FROM reviews WHERE reviewee_id = ?");
    $stmt->execute([$freelancerId]);
    $revRow = $stmt->fetch(PDO::FETCH_NUM);
    $reviewsCount = (int)$revRow[0];
    $avgRating = $revRow[1] !== null ? number_format((float)$revRow[1], 1) : '0.0';

    // 6. Job Success Score (JSS) based on client satisfaction reviews
    $totalClosed = $completedCount + $cancelledCount + $disputedCount;
    if ($reviewsCount > 0) {
        // Satisfied reviews are those with rating >= 4.0 stars
        $stmt = $db->prepare("SELECT COUNT(*) FROM reviews WHERE reviewee_id = ? AND rating >= 4.0");
        $stmt->execute([$freelancerId]);
        $satisfiedCount = (int)$stmt->fetchColumn();
        
        $jssVal = round(($satisfiedCount / $reviewsCount) * 100);
        $jssVal = max(60, min(100, $jssVal));
        $jss = $jssVal . '%';
    } else {
        // Fallback to contract status if no explicit reviews yet
        if ($totalClosed === 0) {
            $jssVal = null;
            $jss = 'N/A';
        } else {
            $jssVal = round(($completedCount / $totalClosed) * 100);
            $jssVal = max(60, min(100, $jssVal));
            $jss = $jssVal . '%';
        }
    }

    // 7. Dynamic badge
    $badge = null;
    $badge_label = '';
    if ($completeness === 100) {
        if ($jssVal === null || $totalClosed === 0) {
            if ($totalEarned < 1000) {
                $badge = 'rising_talent';
                $badge_label = 'Rising Talent';
            }
        } else {
            if ($jssVal >= 90) {
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
    }

    // 8. Rating and reviews mapping
    $rating = $reviewsCount > 0 ? $avgRating : '0.0';
    if ($reviewsCount === 0 && $completedCount > 0) {
        $rating = '5.0'; // Historical fallback
    }

    return [
        'total_earned' => $totalEarned,
        'completed_contracts' => $completedCount,
        'active_contracts' => $activeCount,
        'jss' => $jss,
        'jss_val' => $jssVal,
        'completeness' => $completeness,
        'badge' => $badge,
        'badge_label' => $badge_label,
        'rating' => $rating,
        'reviews_count' => $reviewsCount > 0 ? $reviewsCount : $completedCount
    ];
}
