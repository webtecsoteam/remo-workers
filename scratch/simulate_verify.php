<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

$token = "2dbeff84046e831defb27470c49c12422e15e481cde80d042495c5763c3a6711";
echo "Attempting to verify token: $token\n";

try {
    $result = Auth::verifyEmailByToken($token);
    echo "Result: " . ($result ? "TRUE (Success)" : "FALSE (Failed)") . "\n";
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
