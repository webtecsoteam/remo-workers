<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

$token = $_GET['token'] ?? '';
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $user = Auth::user();
    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'Login required.']);
        exit;
    }
    if (Auth::isEmailVerified($user)) {
        echo json_encode(['success' => true, 'message' => 'Email is already verified.', 'already' => true]);
        exit;
    }
    $newToken = Auth::resendEmailVerification($user['id']);
    if (!$newToken) {
        echo json_encode(['success' => false, 'error' => 'Could not generate verification link.']);
        exit;
    }

    $verifyUrl = Auth::emailVerificationUrl($newToken);
    $subject = "Verify Your RemoWorkers Account";
    $html = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 25px; border: 1px solid #d5e0d5; border-radius: 12px; background-color: #ffffff;'>
        <div style='text-align: center; margin-bottom: 25px;'>
            <h2 style='color: #14a800; margin: 0; font-size: 24px; font-weight: 800;'>RemoWorkers</h2>
        </div>
        <div style='font-size: 15px; line-height: 1.6; color: #374151;'>
            <p>Hello " . htmlspecialchars($user['name']) . ",</p>
            <p>Thank you for joining RemoWorkers! To complete your registration and start applying to premium remote jobs, please verify your email address by clicking the secure button below:</p>
            <div style='text-align: center; margin: 35px 0;'>
                <a href='" . $verifyUrl . "' style='background-color: #14a800; color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 50px; font-weight: bold; display: inline-block; font-size: 15px; box-shadow: 0 4px 12px rgba(20,168,0,0.2);'>Verify Email Address</a>
            </div>
            <p style='font-size: 13px; color: #6b7280;'>If the button doesn't work, copy and paste this URL into your browser:<br><a href='" . $verifyUrl . "' style='color: #14a800; word-break: break-all;'>" . $verifyUrl . "</a></p>
            <hr style='border: 0; border-top: 1px solid #d5e0d5; margin: 30px 0;'>
            <p style='font-size: 11px; color: #9ca3af;'>This verification link is secure. If you did not register for a RemoWorkers account, please ignore this email.</p>
        </div>
    </div>
    ";

    require_once __DIR__ . '/../includes/classes/Mailer.php';
    if (Mailer::send($user['email'], $subject, $html)) {
        echo json_encode([
            'success' => true,
            'message' => 'Verification email sent! Please check your inbox (' . htmlspecialchars($user['email']) . ') and spam folder.',
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to deliver verification email via SMTP. Please try again later.',
        ]);
    }
    exit;
}

if ($token && Auth::verifyEmailByToken($token)) {
    $target = baseUrl('remoworkers-dashboard') . '?verified=email';
    redirect($target);
}

if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid or expired verification link.']);
    exit;
}

redirect(baseUrl('remoworkers-dashboard') . '?error=invalid_verification_link');
