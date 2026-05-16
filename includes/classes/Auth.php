<?php
class Auth {
    public const CONNECTS_PER_APPLICATION = 5;
    public const FREELANCER_WELCOME_CONNECTS = 5;

    public static function register($name, $email, $password, $role) {
        ensureFreelancerSchema();
        $db = getDB();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(32));
        $connects = ($role === 'freelancer') ? self::FREELANCER_WELCOME_CONNECTS : 0;

        $stmt = $db->prepare("
            INSERT INTO users (name, email, password, role, connects, email_verification_token)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$name, $email, $hashedPassword, $role, $connects, $token]);
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

    public static function user() {
        if (!isset($_SESSION['user_id'])) return null;
        ensureFreelancerSchema();
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
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
        return true;
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
