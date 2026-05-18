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
    $type = $metadata['type'] ?? 'deposit';

    if (!$userId) {
        die("Invalid metadata: user_id missing.");
    }

    $db = getDB();
    try {
        $db->beginTransaction();

        if ($type === 'connects') {
            $connectsAmount = intval($metadata['connects_amount'] ?? 0);

            // 1. Update User Connects
            $stmt = $db->prepare("UPDATE users SET connects = connects + ? WHERE id = ?");
            $stmt->execute([$connectsAmount, $userId]);

            // 2. Add Connects History
            $stmt = $db->prepare("
                INSERT INTO connects_history (user_id, action, description, amount) 
                VALUES (?, 'purchase', ?, ?)
            ");
            $stmt->execute([
                $userId,
                'Purchased ' . $connectsAmount . ' Connects Pack',
                $connectsAmount
            ]);

            // 3. Update Payment Status to completed
            $stmt = $db->prepare("UPDATE payments SET status = 'completed', currency = ?, amount = ?, description = ? WHERE transaction_id = ?");
            $stmt->execute([
                $data['currency'],
                $amount,
                'Purchased ' . $connectsAmount . ' Connects (Paystack completed)',
                $reference
            ]);

            $db->commit();

            // Redirect to Freelancer Connects dashboard with success
            $redirectUrl = baseUrl('remoworkers-dashboard?payment=success&connects=' . $connectsAmount);
            header("Location: $redirectUrl");
            exit;
        } else {
            // 1. Update User Balance (Client Deposit)
            $stmt = $db->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $stmt->execute([$amount, $userId]);

            // 2. Update Payment Status to completed
            $stmt = $db->prepare("UPDATE payments SET status = 'completed', currency = ?, amount = ? WHERE transaction_id = ?");
            $stmt->execute([
                $data['currency'],
                $amount,
                $reference
            ]);

            $db->commit();

            // Redirect to Client dashboard with success
            $redirectUrl = baseUrl('client/index.php?payment=success&amount=' . $amount);
            header("Location: $redirectUrl");
            exit;
        }

    } catch (Exception $e) {
        $db->rollBack();
        die("Database error: " . $e->getMessage());
    }
} else {
    // Payment failed or was cancelled
    $db = getDB();
    $stmt = $db->prepare("SELECT description FROM payments WHERE transaction_id = ?");
    $stmt->execute([$reference]);
    $desc = $stmt->fetchColumn();
    $isConnects = ($desc && stripos($desc, 'Connects') !== false);

    if ($isConnects) {
        $redirectUrl = baseUrl('remoworkers-dashboard?payment=failed');
    } else {
        $redirectUrl = baseUrl('client/index.php?payment=failed');
    }
    header("Location: $redirectUrl");
    exit;
}
