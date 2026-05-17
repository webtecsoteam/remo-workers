<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

header('Content-Type: application/json');

$user = Auth::user();
if (!$user || $user['role'] !== 'client') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$milestoneId = $data['milestone_id'] ?? null;

if (!$milestoneId) {
    echo json_encode(['success' => false, 'message' => 'Milestone ID required']);
    exit;
}

$db = getDB();
try {
    $db->beginTransaction();

    // Verify milestone belongs to client
    $stmt = $db->prepare("
        SELECT m.*, c.freelancer_id, c.client_id, c.job_id 
        FROM milestones m 
        JOIN contracts c ON m.contract_id = c.id 
        WHERE m.id = ? AND c.client_id = ?
    ");
    $stmt->execute([$milestoneId, $user['id']]);
    $milestone = $stmt->fetch();

    if (!$milestone) {
        throw new Exception('Milestone not found or unauthorized');
    }

    if ($milestone['status'] === 'paid') {
        throw new Exception('Milestone already paid');
    }

    // 1. Update milestone status to paid
    $update = $db->prepare("UPDATE milestones SET status = 'paid' WHERE id = ?");
    $update->execute([$milestoneId]);

    // Update original escrow funding payment record from pending to completed
    $updatePayment = $db->prepare("
        UPDATE payments 
        SET status = 'completed' 
        WHERE payer_id = ? AND payee_id = ? AND job_id = ? AND amount = ? AND status = 'pending' AND payment_method != 'Escrow Release'
        LIMIT 1
    ");
    $updatePayment->execute([
        $user['id'],
        $milestone['freelancer_id'],
        $milestone['job_id'],
        $milestone['amount']
    ]);

    // 2. Create payment record for the freelancer with status 'pending' (security hold) and deduct fee
    $transactionId = 'TXN-' . strtoupper(uniqid());
    $amount = (float)$milestone['amount'];
    $fee = $amount * 0.10; // 10% platform fee
    $netAmount = $amount - $fee;

    $pStmt = $db->prepare("
        INSERT INTO payments (transaction_id, payer_id, payee_id, job_id, amount, platform_fee, status, payment_method) 
        VALUES (?, ?, ?, ?, ?, ?, 'pending', 'Escrow Release')
    ");
    $pStmt->execute([
        $transactionId,
        $user['id'],
        $milestone['freelancer_id'],
        $milestone['job_id'],
        $amount,
        $fee
    ]);

    // 3. Do NOT credit available balance immediately. Funds will sit in security hold (pending) 
    // until freelancer or admin clears the hold.

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Milestone approved! Payments released to security hold (Pending).']);
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
