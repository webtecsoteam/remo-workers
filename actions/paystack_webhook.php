<?php
/**
 * =============================================
 * RemoWorkers - Paystack Webhook Handler
 * =============================================
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Paystack.php';

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

// Retrieve the request's body
$input = file_get_contents('php://input');
$event = json_decode($input);

// Paystack Signature Verification
$secretKey = env('PAYSTACK_SECRET_KEY');
if ($_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] !== hash_hmac('sha512', $input, $secretKey)) {
    // Silently exit for unauthorized requests
    exit;
}

// http_response_code(200); // Acknowledge receipt quickly

// Handle the event
switch($event->event) {
    case 'charge.success':
        handleChargeSuccess($event->data);
        break;
}

function handleChargeSuccess($data) {
    $reference = $data->reference;
    $amount = $data->amount / 100;
    
    $metadata = $data->metadata;
    // Metadata can be an object or a string depending on how it was sent
    if (is_string($metadata)) {
        $metadata = json_decode($metadata);
    }
    
    $userId = $metadata->user_id ?? null;
    if (!$userId) return;

    $db = getDB();
    
    // Check if transaction already processed (idempotency)
    $stmt = $db->prepare("SELECT id FROM payments WHERE transaction_id = ? AND status = 'completed'");
    $stmt->execute([$reference]);
    if ($stmt->fetch()) {
        // Already processed
        return;
    }

    try {
        $db->beginTransaction();

        // 1. Update User Balance
        $stmt = $db->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        $stmt->execute([$amount, $userId]);

        // 2. Record/Update Payment
        // Check if a pending record exists
        $stmt = $db->prepare("SELECT id FROM payments WHERE transaction_id = ?");
        $stmt->execute([$reference]);
        $existing = $stmt->fetch();

        if ($existing) {
            $stmt = $db->prepare("UPDATE payments SET status = 'completed', amount = ? WHERE transaction_id = ?");
            $stmt->execute([$amount, $reference]);
        } else {
            $stmt = $db->prepare("INSERT INTO payments (transaction_id, payer_id, payee_id, amount, currency, payment_method, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $reference,
                $userId,
                $userId,
                $amount,
                $data->currency,
                'paystack',
                'completed'
            ]);
        }

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        // You might want to log this error
    }
}

exit;
