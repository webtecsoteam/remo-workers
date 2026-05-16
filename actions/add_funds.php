<?php
ob_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

function json_response($data) {
    ob_end_clean();
    echo json_encode($data);
    exit;
}

$user = Auth::user();
if (!$user) {
    json_response(['success' => false, 'error' => 'Login required']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $amount = floatval($_POST['amount'] ?? 0);

    if ($amount < 50) {
        json_response(['success' => false, 'error' => 'Minimum $50 required']);
    }

    try {
        $db->beginTransaction();

        // 1. Update user balance
        $stmt = $db->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        $stmt->execute([$amount, $user['id']]);

        // 2. Insert payment record
        $stmt = $db->prepare("
            INSERT INTO payments (transaction_id, payer_id, payee_id, amount, platform_fee, currency, payment_method, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $txId = 'DEP-' . strtoupper(substr(uniqid(), -8));
        $stmt->execute([
            $txId,
            NULL, // NULL for external deposit
            $user['id'],
            $amount,
            0,
            'USD',
            $_POST['method'] ?? 'Visa',
            'completed'
        ]);

        $db->commit();
        json_response(['success' => true, 'message' => 'Funds added successfully', 'new_balance' => $user['balance'] + $amount]);

    } catch (Exception $e) {
        $db->rollBack();
        json_response(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    json_response(['success' => false, 'error' => 'Invalid request method']);
}
