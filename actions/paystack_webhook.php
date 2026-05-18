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

    $type = $metadata->type ?? 'deposit';

    try {
        $db->beginTransaction();

        if ($type === 'connects') {
            $connectsAmount = intval($metadata->connects_amount ?? 0);

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

            // 3. Record/Update Payment
            $stmt = $db->prepare("SELECT id FROM payments WHERE transaction_id = ?");
            $stmt->execute([$reference]);
            $existing = $stmt->fetch();

            if ($existing) {
                $stmt = $db->prepare("UPDATE payments SET status = 'completed', amount = ?, description = ? WHERE transaction_id = ?");
                $stmt->execute([
                    $amount,
                    'Purchased ' . $connectsAmount . ' Connects (Paystack completed)',
                    $reference
                ]);
            } else {
                $stmt = $db->prepare("
                    INSERT INTO payments (transaction_id, payer_id, payee_id, amount, currency, payment_method, status, description, platform_fee) 
                    VALUES (?, ?, ?, ?, ?, 'paystack', 'completed', ?, 0.0)
                ");
                $stmt->execute([
                    $reference,
                    $userId,
                    1, // System
                    $amount,
                    $data->currency,
                    'Purchased ' . $connectsAmount . ' Connects (Paystack completed)'
                ]);
            }
        } else {
            // 1. Update User Balance (Client Deposit)
            $stmt = $db->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $stmt->execute([$amount, $userId]);

            // 2. Record/Update Payment
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
        }

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        // You might want to log this error
    }
}

exit;
