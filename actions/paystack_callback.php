<?php
/**
 * =============================================
 * RemoWorkers - Paystack Callback Action
 * =============================================
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';
require_once __DIR__ . '/../includes/classes/Paystack.php';

$reference = $_GET['reference'] ?? '';

if (empty($reference)) {
    die("No reference found. Payment verification failed.");
}

$paystack = new Paystack();
$response = $paystack->verify($reference);

if ($response['status'] && $response['data']['status'] === 'success') {
    $data = $response['data'];
    $amount = $data['amount'] / 100; // Convert back from kobo/cents
    
    $metadata = $data['metadata'];
    if (is_string($metadata)) {
        $metadata = json_decode($metadata, true);
    }
    
    $userId = $metadata['user_id'] ?? null;

    if (!$userId) {
        die("Invalid metadata: user_id missing.");
    }

    $db = getDB();
    try {
        $db->beginTransaction();

        // 1. Update User Balance
        $stmt = $db->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        $stmt->execute([$amount, $userId]);

        // 2. Update Payment Status
        $stmt = $db->prepare("UPDATE payments SET status = 'completed', currency = ?, amount = ? WHERE transaction_id = ?");
        $stmt->execute([
            $data['currency'],
            $amount,
            $reference
        ]);

        $db->commit();

        // Redirect with success
        $redirectUrl = baseUrl('client/index.php?payment=success&amount=' . $amount);
        header("Location: $redirectUrl");
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        die("Database error: " . $e->getMessage());
    }
} else {
    // Payment failed or was cancelled
    $redirectUrl = baseUrl('client/index.php?payment=failed');
    header("Location: $redirectUrl");
    exit;
}
