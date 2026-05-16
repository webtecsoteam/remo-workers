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

    // 1. Update milestone status
    $update = $db->prepare("UPDATE milestones SET status = 'paid' WHERE id = ?");
    $update->execute([$milestoneId]);

    // 2. Create payment record
    $transactionId = 'TXN-' . strtoupper(uniqid());
    $pStmt = $db->prepare("
        INSERT INTO payments (transaction_id, payer_id, payee_id, job_id, amount, status, type) 
        VALUES (?, ?, ?, ?, ?, 'completed', 'milestone')
    ");
    $pStmt->execute([
        $transactionId,
        $user['id'],
        $milestone['freelancer_id'],
        $milestone['job_id'],
        $milestone['amount']
    ]);

    // 3. Update balances
    // Deduct from client
    $dStmt = $db->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
    $dStmt->execute([$milestone['amount'], $user['id']]);
    
    // Add to freelancer
    $uStmt = $db->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
    $uStmt->execute([$milestone['amount'], $milestone['freelancer_id']]);

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Milestone payment released!']);
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
