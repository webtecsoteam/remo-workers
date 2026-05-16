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
    echo json_encode([
        'success' => true,
        'message' => 'Verification link ready. Open the link to verify your email.',
        'verify_url' => Auth::emailVerificationUrl($newToken),
    ]);
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
