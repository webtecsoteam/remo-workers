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
$milestoneId = isset($data['milestone_id']) ? (int)$data['milestone_id'] : 0;
$clientNote = trim((string)($data['note'] ?? ''));

if ($milestoneId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Milestone ID required']);
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

    $stmt = $db->prepare("
        SELECT m.*, c.client_id, c.freelancer_id, c.job_id, c.status AS contract_status
        FROM milestones m
        JOIN contracts c ON m.contract_id = c.id
        WHERE m.id = ? AND c.client_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$milestoneId, $user['id']]);
    $milestone = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$milestone) {
        throw new Exception('Milestone not found or unauthorized');
    }

    if ($milestone['contract_status'] === 'disputed') {
        throw new Exception('Cannot request refund while contract is disputed');
    }

    if ($milestone['status'] !== 'funded') {
        throw new Exception('Refund can only be requested for funded milestones');
    }

    $pendingReqStmt = $db->prepare("
        SELECT id FROM milestone_refund_requests
        WHERE milestone_id = ? AND status = 'pending'
        ORDER BY id DESC
        LIMIT 1
    ");
    $pendingReqStmt->execute([$milestoneId]);
    if ($pendingReqStmt->fetchColumn()) {
        throw new Exception('A refund request is already pending for this milestone');
    }

    $insertReqStmt = $db->prepare("
        INSERT INTO milestone_refund_requests
        (milestone_id, contract_id, client_id, freelancer_id, job_id, status, client_note)
        VALUES (?, ?, ?, ?, ?, 'pending', ?)
    ");
    $insertReqStmt->execute([
        $milestoneId,
        $milestone['contract_id'],
        $user['id'],
        $milestone['freelancer_id'],
        $milestone['job_id'],
        $clientNote !== '' ? $clientNote : null
    ]);

    $msgText = "AUTOMATED MESSAGE: The client has requested a refund for milestone **"
        . htmlspecialchars($milestone['description'])
        . "** ($" . number_format((float)$milestone['amount'], 2)
        . "). Please review and either accept or reject this request from your contract milestones.";

    $sendMsgStmt = $db->prepare("
        INSERT INTO messages (sender_id, receiver_id, job_id, message, is_read)
        VALUES (?, ?, ?, ?, 0)
    ");
    $sendMsgStmt->execute([
        $user['id'],
        $milestone['freelancer_id'],
        $milestone['job_id'],
        $msgText
    ]);

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Refund request sent to freelancer']);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
