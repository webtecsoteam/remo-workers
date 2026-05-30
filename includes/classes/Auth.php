<?php
class Auth {
    public const CONNECTS_PER_APPLICATION = 5;
    public const FREELANCER_WELCOME_CONNECTS = 5;

    public static function register($name, $email, $password, $role, $country = null) {
        ensureFreelancerSchema();
        $db = getDB();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(32));
        $connects = ($role === 'freelancer') ? self::FREELANCER_WELCOME_CONNECTS : 0;

        // Auto detect country based on IP and headers if not provided
        if (empty($country)) {
            $country = 'United States'; // standard default fallback
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        }

        if (!empty($ip) && $ip !== '127.0.0.1' && $ip !== '::1') {
            try {
                $ctx = stream_context_create(['http' => ['timeout' => 1.5]]);
                $res = @file_get_contents("http://ip-api.com/json/" . $ip, false, $ctx);
                if ($res) {
                    $geo = json_decode($res, true);
                    if (!empty($geo['country'])) {
                        $country = $geo['country'];
                    }
                }
            } catch (Exception $e) {
                // ignore
            }
        } else {
            // Check language headers for localhost/dev testing
            if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                $lang = strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2));
                $lang_map = [
                    'en' => 'United States',
                    'uk' => 'United Kingdom',
                    'gb' => 'United Kingdom',
                    'ca' => 'Canada',
                    'in' => 'India',
                    'au' => 'Australia',
                    'de' => 'Germany',
                    'fr' => 'France',
                    'es' => 'Spain',
                    'it' => 'Italy',
                    'nl' => 'Netherlands',
                ];
                if (isset($lang_map[$lang])) {
                    $country = $lang_map[$lang];
                }
            }
        }
    }

        $stmt = $db->prepare("
            INSERT INTO users (name, email, password, role, connects, email_verification_token, country)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$name, $email, $hashedPassword, $role, $connects, $token, $country]);
    }

    public static function login($email, $password) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] !== 'active') {
                throw new Exception("Your account is " . $user['status'] . ". Please contact support.");
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_email'] = $user['email'];

            $db->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?")->execute([$user['id']]);

            return $user;
        }
        return false;
    }

    public static function logout() {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    public static function isActive($user = null) {
        $user = $user ?? self::user();
        return $user && ($user['status'] ?? '') === 'active';
    }

    /**
     * Block suspended (or otherwise inactive) clients from restricted actions.
     * Returns an error string, or null if the client may proceed.
     * Reads fresh status from the database so checks work even before Auth::user() clears the session.
     */
    public static function suspendedClientError($user = null) {
        if (is_array($user)) {
            $role = $user['role'] ?? '';
            $status = $user['status'] ?? '';
            $userId = $user['id'] ?? null;
        } else {
            $userId = $user ?? ($_SESSION['user_id'] ?? null);
            if (!$userId) {
                return null;
            }
            $db = getDB();
            $stmt = $db->prepare("SELECT role, status FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $row = $stmt->fetch();
            if (!$row) {
                return null;
            }
            $role = $row['role'] ?? '';
            $status = $row['status'] ?? '';
        }

        if ($role !== 'client' || $status === 'active') {
            return null;
        }
        if ($status === 'suspended') {
            return 'Your account has been suspended. You cannot perform this action. Please contact support.';
        }
        return 'Your account is ' . $status . '. You cannot perform this action. Please contact support.';
    }

    /** Minimum wallet balance (USD) required before a client can start an hourly contract. */
    public const CLIENT_MIN_HOURLY_BALANCE = 1.0;

    /**
     * Returns an error message if the client lacks the minimum balance for hourly contracts,
     * or null if they may proceed.
     */
    public static function hourlyContractBalanceError($clientId, $db = null, $forUpdate = false) {
        $db = $db ?? getDB();
        $sql = "SELECT balance FROM users WHERE id = ? AND role = 'client'";
        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }
        $stmt = $db->prepare($sql);
        $stmt->execute([(int) $clientId]);
        $balance = $stmt->fetchColumn();
        if ($balance === false) {
            return 'Client account not found.';
        }
        if ((float) $balance < self::CLIENT_MIN_HOURLY_BALANCE) {
            return 'You need at least $' . number_format(self::CLIENT_MIN_HOURLY_BALANCE, 2)
                . ' in your account balance to start an hourly contract. Please add funds first.';
        }
        return null;
    }

    public static function user() {
        if (!isset($_SESSION['user_id'])) return null;
        ensureFreelancerSchema();
        $db = getDB();
        
        // Update last_active_at at most once per minute to optimize database writes
        $db->prepare("
            UPDATE users 
            SET last_active_at = NOW() 
            WHERE id = ? AND (last_active_at IS NULL OR last_active_at < DATE_SUB(NOW(), INTERVAL 1 MINUTE))
        ")->execute([$_SESSION['user_id']]);
        
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if ($user && ($user['status'] ?? '') !== 'active') {
            self::logout();
            return null;
        }
        return $user;
    }

    public static function check() {
        return isset($_SESSION['user_id']);
    }

    public static function isEmailVerified($user = null) {
        $user = $user ?? self::user();
        if (!$user) return false;
        return !empty($user['email_verified_at']);
    }

    public static function isIdentityVerified($user = null) {
        $user = $user ?? self::user();
        if (!$user) return false;
        return !empty($user['is_verified']);
    }

    public static function canApplyToJobs($user = null) {
        $user = $user ?? self::user();
        if (!$user || ($user['role'] ?? '') !== 'freelancer') return false;
        return self::isEmailVerified($user) && self::isIdentityVerified($user);
    }

    public static function verifyEmailByToken($token) {
        ensureFreelancerSchema();
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM users WHERE email_verification_token = ? LIMIT 1");
        $stmt->execute([$token]);
        $userId = $stmt->fetchColumn();
        if (!$userId) return false;

        $upd = $db->prepare("
            UPDATE users
            SET email_verified_at = NOW(), email_verification_token = NULL
            WHERE id = ?
        ");
        $upd->execute([$userId]);

        if (file_exists(__DIR__ . '/../referral.php')) {
            require_once __DIR__ . '/../referral.php';
            referralOnReferredUserUpdated((int) $userId);
        }

        return (int) $userId;
    }

    public static function resendEmailVerification($userId) {
        ensureFreelancerSchema();
        $db = getDB();
        $token = bin2hex(random_bytes(32));
        $stmt = $db->prepare("UPDATE users SET email_verification_token = ? WHERE id = ? AND email_verified_at IS NULL");
        $stmt->execute([$token, $userId]);
        if ($stmt->rowCount() === 0) return null;
        return $token;
    }

    public static function emailVerificationUrl($token) {
        return baseUrl('verify-email?token=' . urlencode($token));
    }
}
