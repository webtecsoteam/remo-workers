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
$milestoneId = isset($data['milestone_id']) ? (int)$data['milestone_id'] : 0;
$decision = strtolower(trim((string)($data['decision'] ?? '')));
$freelancerNote = trim((string)($data['note'] ?? ''));

if ($milestoneId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Milestone ID required']);
    exit;
}

if (!in_array($decision, ['accept', 'reject'], true)) {
    echo json_encode(['success' => false, 'message' => 'Decision must be accept or reject']);
    exit;
}

$db = getDB();

try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS milestone_refund_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            milestone_id INT NOT NULL,
            contract_id INT NOT NULL,
            client_id INT NOT NULL,
            freelancer_id INT NOT NULL,
            job_id INT NULL,
            status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
            client_note TEXT NULL,
            freelancer_note TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            responded_at TIMESTAMP NULL DEFAULT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_mrr_milestone (milestone_id),
            INDEX idx_mrr_status (status),
            INDEX idx_mrr_client (client_id),
            INDEX idx_mrr_freelancer (freelancer_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    $db->beginTransaction();

    $milestoneStmt = $db->prepare("
        SELECT m.*, c.client_id, c.freelancer_id, c.job_id, c.status AS contract_status
        FROM milestones m
        JOIN contracts c ON m.contract_id = c.id
        WHERE m.id = ? AND c.freelancer_id = ?
        FOR UPDATE
    ");
    $milestoneStmt->execute([$milestoneId, $user['id']]);
    $milestone = $milestoneStmt->fetch(PDO::FETCH_ASSOC);

    if (!$milestone) {
        throw new Exception('Milestone not found or unauthorized');
    }

    if ($milestone['contract_status'] === 'disputed') {
        throw new Exception('Cannot process refund request while contract is disputed');
    }

    if ($milestone['status'] !== 'funded') {
        throw new Exception('Refund request can only be processed for funded milestones');
    }

    $refundStmt = $db->prepare("
        SELECT * FROM milestone_refund_requests
        WHERE milestone_id = ? AND status = 'pending'
        ORDER BY id DESC
        LIMIT 1
        FOR UPDATE
    ");
    $refundStmt->execute([$milestoneId]);
    $refundRequest = $refundStmt->fetch(PDO::FETCH_ASSOC);

    if (!$refundRequest) {
        throw new Exception('No pending refund request found for this milestone');
    }

    $newStatus = $decision === 'accept' ? 'accepted' : 'rejected';
    $updateReqStmt = $db->prepare("
        UPDATE milestone_refund_requests
        SET status = ?, freelancer_note = ?, responded_at = NOW()
        WHERE id = ?
    ");
    $updateReqStmt->execute([
        $newStatus,
        $freelancerNote !== '' ? $freelancerNote : null,
        $refundRequest['id']
    ]);

    if ($decision === 'accept') {
        $amount = (float)$milestone['amount'];

        $escrowPaymentStmt = $db->prepare("
            SELECT id, amount, platform_fee
            FROM payments
            WHERE transaction_id LIKE 'ESC-%'
              AND payer_id = ?
              AND payee_id = ?
              AND job_id = ?
              AND amount = ?
              AND status = 'pending'
            ORDER BY id DESC
            LIMIT 1
            FOR UPDATE
        ");
        $escrowPaymentStmt->execute([
            $milestone['client_id'],
            $milestone['freelancer_id'],
            $milestone['job_id'],
            $amount
        ]);
        $escrowPayment = $escrowPaymentStmt->fetch(PDO::FETCH_ASSOC);

        if (!$escrowPayment) {
            throw new Exception('Escrow payment not found or already processed');
        }

        $refundTotal = (float)$escrowPayment['amount'] + (float)($escrowPayment['platform_fee'] ?? 0);

        $refundClientStmt = $db->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        $refundClientStmt->execute([$refundTotal, $milestone['client_id']]);

        $updateEscrowStmt = $db->prepare("UPDATE payments SET status = 'refunded' WHERE id = ?");
        $updateEscrowStmt->execute([$escrowPayment['id']]);

        $resetMilestoneStmt = $db->prepare("UPDATE milestones SET status = 'pending' WHERE id = ?");
        $resetMilestoneStmt->execute([$milestoneId]);

        $msgText = "AUTOMATED MESSAGE: The freelancer accepted your refund request for milestone **"
            . htmlspecialchars($milestone['description'])
            . "**. $" . number_format($refundTotal, 2)
            . " has been returned to your wallet balance.";
    } else {
        $msgText = "AUTOMATED MESSAGE: The freelancer rejected your refund request for milestone **"
            . htmlspecialchars($milestone['description'])
            . "**. The milestone remains funded and active.";
    }

    $sendMsgStmt = $db->prepare("
        INSERT INTO messages (sender_id, receiver_id, job_id, message, is_read)
        VALUES (?, ?, ?, ?, 0)
    ");
    $sendMsgStmt->execute([
        $user['id'],
        $milestone['client_id'],
        $milestone['job_id'],
        $msgText
    ]);

    $db->commit();
    echo json_encode([
        'success' => true,
        'message' => $decision === 'accept'
            ? 'Refund accepted. Funds were returned to client wallet.'
            : 'Refund request rejected.'
    ]);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
