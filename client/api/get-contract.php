<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

header('Content-Type: application/json');

$user = Auth::user();
if (!$user || $user['role'] !== 'client') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$contractId = $_GET['id'] ?? null;
if (!$contractId) {
    echo json_encode(['success' => false, 'message' => 'Contract ID is required']);
    exit;
}

$db = getDB();
try {
    $stmt = $db->prepare("
        SELECT c.*, j.title as job_title, u.name as freelancer_name, u.avatar_url as freelancer_avatar 
        FROM contracts c 
        JOIN jobs j ON c.job_id = j.id 
        JOIN users u ON c.freelancer_id = u.id 
        WHERE c.id = ? AND c.client_id = ?
    ");
    $stmt->execute([$contractId, $user['id']]);
    $contract = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$contract) {
        echo json_encode(['success' => false, 'message' => 'Contract not found']);
        exit;
    }

    // Fetch milestones
    $mStmt = $db->prepare("SELECT * FROM milestones WHERE contract_id = ? ORDER BY id ASC");
    $mStmt->execute([$contractId]);
    $contract['milestones'] = $mStmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($contract['milestones'])) {
        try {
            $milestoneIds = array_map(static fn($m) => (int)$m['id'], $contract['milestones']);
            $milestoneIds = array_values(array_unique(array_filter($milestoneIds)));
            if (!empty($milestoneIds)) {
                $placeholders = implode(',', array_fill(0, count($milestoneIds), '?'));
                $refundStmt = $db->prepare("
                    SELECT rr.*
                    FROM milestone_refund_requests rr
                    INNER JOIN (
                        SELECT milestone_id, MAX(id) AS latest_id
                        FROM milestone_refund_requests
                        WHERE milestone_id IN ($placeholders)
                        GROUP BY milestone_id
                    ) latest ON latest.latest_id = rr.id
                ");
                $refundStmt->execute($milestoneIds);
                $refundRows = $refundStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                $refundMap = [];
                foreach ($refundRows as $rr) {
                    $refundMap[(int)$rr['milestone_id']] = $rr;
                }
                foreach ($contract['milestones'] as &$ms) {
                    $refund = $refundMap[(int)$ms['id']] ?? null;
                    $ms['refund_request_status'] = $refund['status'] ?? null;
                    $ms['refund_requested_at'] = $refund['created_at'] ?? null;
                    $ms['refund_response_at'] = $refund['responded_at'] ?? null;
                    $ms['refund_client_note'] = $refund['client_note'] ?? null;
                    $ms['refund_freelancer_note'] = $refund['freelancer_note'] ?? null;
                }
                unset($ms);
            }
        } catch (Throwable $e) {
            // Refund table may not exist yet.
        }
    }

    // Fetch work logs
    $wlStmt = $db->prepare("SELECT * FROM work_logs WHERE contract_id = ? ORDER BY created_at DESC");
    $wlStmt->execute([$contractId]);
    $contract['work_logs'] = $wlStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    echo json_encode([
        'success' => true,
        'contract' => $contract
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
