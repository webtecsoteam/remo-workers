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
$milestoneId = $data['milestone_id'] ?? null;

if (!$milestoneId) {
    echo json_encode(['success' => false, 'message' => 'Milestone ID required']);
    exit;
}

$db = getDB();
try {
    // Verify milestone belongs to freelancer
    $stmt = $db->prepare("
        SELECT m.*, c.status as contract_status FROM milestones m 
        JOIN contracts c ON m.contract_id = c.id 
        WHERE m.id = ? AND c.freelancer_id = ?
    ");
    $stmt->execute([$milestoneId, $user['id']]);
    $milestone = $stmt->fetch();

    if (!$milestone) {
        echo json_encode(['success' => false, 'message' => 'Milestone not found or unauthorized']);
        exit;
    }

    if ($milestone['contract_status'] === 'disputed') {
        echo json_encode(['success' => false, 'message' => 'Cannot submit work on a disputed contract.']);
        exit;
    }

    if ($milestone['status'] !== 'funded') {
        echo json_encode(['success' => false, 'message' => 'Milestone must be funded by the client before you can submit work.']);
        exit;
    }

    $update = $db->prepare("UPDATE milestones SET status = 'requested' WHERE id = ?");
    $update->execute([$milestoneId]);

    echo json_encode(['success' => true, 'message' => 'Milestone completion requested!']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
