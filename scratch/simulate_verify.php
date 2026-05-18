<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

$token = "2dbeff84046e831defb27470c49c12422e15e481cde80d042495c5763c3a6711";
echo "Attempting to verify token: $token\n";

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, email_verification_token FROM users WHERE email_verification_token = ?");
    $stmt->execute([$token]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Direct Query matching token result: " . json_encode($rows) . "\n";
    
    $result = Auth::verifyEmailByToken($token);
    echo "Result: " . ($result ? "TRUE (Success)" : "FALSE (Failed)") . "\n";
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
