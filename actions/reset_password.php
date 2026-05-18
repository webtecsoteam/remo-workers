<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$token = trim($_POST['token'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (empty($token)) {
    echo json_encode(['success' => false, 'message' => 'Missing password reset token.']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long.']);
    exit;
}

if ($password !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
    exit;
}

try {
    ensureFreelancerSchema();
    $db = getDB();
    
    // Find the user by valid, non-expired token
    $stmt = $db->prepare("
        SELECT id, password_reset_expires_at FROM users 
        WHERE password_reset_token = ?
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if (!$user || strtotime($user['password_reset_expires_at']) <= time()) {
        echo json_encode(['success' => false, 'message' => 'This password reset link is invalid or has expired.']);
        exit;
    }
    
    // Hash new password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Update password and clear token
    $upStmt = $db->prepare("
        UPDATE users 
        SET password = ?, 
            password_reset_token = NULL, 
            password_reset_expires_at = NULL 
        WHERE id = ?
    ");
    $upStmt->execute([$hashedPassword, $user['id']]);
    
    echo json_encode(['success' => true, 'message' => 'Your password has been reset successfully! You can now log in.']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
