<?php
class Auth {
    public static function register($name, $email, $password, $role) {
        $db = getDB();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$name, $email, $hashedPassword, $role]);
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
            
            // Update last login
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
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'role' => $_SESSION['user_role']
        ];
    }
    
    public static function check() {
        return isset($_SESSION['user_id']);
    }
}
