<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/referral.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    handleCorsPreflight();
}

applyCorsHeaders();
header('Content-Type: application/json');

$code = (string) ($_GET['code'] ?? '');

if (trim($code) === '') {
    echo json_encode([
        'success' => true,
        'found' => false,
        'message' => 'Enter a referral code.',
    ]);
    exit;
}

try {
    $referrer = lookupReferrerByCode($code);

    if ($referrer) {
        echo json_encode([
            'success' => true,
            'found' => true,
            'name' => $referrer['name'],
            'code' => $referrer['referral_code'],
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'found' => false,
        'message' => 'Referral code not found. You can still register without one.',
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'found' => false,
        'message' => 'Unable to verify referral code right now.',
    ]);
}
