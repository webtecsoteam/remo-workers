<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

header('Content-Type: application/json');

$user = Auth::user();
if (!$user || $user['role'] !== 'freelancer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$paymentId = $data['payment_id'] ?? null;

if (!$paymentId) {
    echo json_encode(['success' => false, 'message' => 'Payment ID required']);
    exit;
}

$db = getDB();
try {
    $db->beginTransaction();

    // Verify payment belongs to freelancer and is pending
    $stmt = $db->prepare("SELECT * FROM payments WHERE id = ? AND payee_id = ? FOR UPDATE");
    $stmt->execute([$paymentId, $user['id']]);
    $payment = $stmt->fetch();

    if (!$payment) {
        throw new Exception('Payment record not found or unauthorized');
    }

    if ($payment['status'] !== 'pending') {
        throw new Exception('Payment is already cleared or completed');
    }

    // 1. Update payment status to completed
    $update = $db->prepare("UPDATE payments SET status = 'completed' WHERE id = ?");
    $update->execute([$paymentId]);

    // 2. Add amount to freelancer balance
    $amount = (float)$payment['amount'];
    // Deduct platform fee (mock fee of 10% or from row if present)
    $fee = isset($payment['platform_fee']) ? (float)$payment['platform_fee'] : ($amount * 0.10);
    $netAmount = $amount - $fee;

    $uStmt = $db->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
    $uStmt->execute([$netAmount, $user['id']]);

    // Fetch the updated balance from the database
    $balStmt = $db->prepare("SELECT balance FROM users WHERE id = ?");
    $balStmt->execute([$user['id']]);
    $newBalance = (float)$balStmt->fetchColumn();

    $db->commit();
    echo json_encode([
        'success' => true, 
        'message' => 'Funds successfully cleared and added to your available balance!',
        'new_balance' => $newBalance
    ]);
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
