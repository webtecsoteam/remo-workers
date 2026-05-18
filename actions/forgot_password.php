<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Mailer.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Please enter your email address.']);
    exit;
}

try {
    ensureFreelancerSchema();
    $db = getDB();
    
    // Find the user by email
    $stmt = $db->prepare("SELECT id, name, email FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // For security, don't explicitly reveal if the email doesn't exist
        echo json_encode(['success' => true, 'message' => 'If this email is registered, you will receive a password reset link shortly.']);
        exit;
    }
    
    // Generate secure token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Save to database
    $upStmt = $db->prepare("UPDATE users SET password_reset_token = ?, password_reset_expires_at = ? WHERE id = ?");
    $upStmt->execute([$token, $expires, $user['id']]);
    
    // Build reset URL
    $resetUrl = baseUrl('reset-password?token=' . $token);
    
    $subject = "Reset Your RemoWorkers Password";
    $html = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 25px; border: 1px solid #d5e0d5; border-radius: 12px; background-color: #ffffff;'>
        <div style='text-align: center; margin-bottom: 25px;'>
            <h2 style='color: #14a800; margin: 0; font-size: 24px; font-weight: 800;'>RemoWorkers</h2>
        </div>
        <div style='font-size: 15px; line-height: 1.6; color: #374151;'>
            <p>Hello " . htmlspecialchars($user['name']) . ",</p>
            <p>We received a request to reset the password for your RemoWorkers account. Click the secure button below to set a new password:</p>
            <div style='text-align: center; margin: 35px 0;'>
                <a href='" . $resetUrl . "' style='background-color: #14a800; color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 50px; font-weight: bold; display: inline-block; font-size: 15px; box-shadow: 0 4px 12px rgba(20,168,0,0.2);'>Reset Password</a>
            </div>
            <p style='font-size: 13px; color: #6b7280;'>This link will expire in 1 hour. If the button doesn't work, copy and paste this URL into your browser:<br><a href='" . $resetUrl . "' style='color: #14a800; word-break: break-all;'>" . $resetUrl . "</a></p>
            <hr style='border: 0; border-top: 1px solid #d5e0d5; margin: 30px 0;'>
            <p style='font-size: 11px; color: #9ca3af;'>If you did not request a password reset, please ignore this email. Your password will remain secure.</p>
        </div>
    </div>
    ";
    
    if (Mailer::send($user['email'], $subject, $html)) {
        echo json_encode(['success' => true, 'message' => 'Password reset link sent! Please check your inbox and spam folder.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send recovery email. Please try again later.']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
