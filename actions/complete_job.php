<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

header('Content-Type: application/json');

$user = Auth::user();
if (!$user || $user['role'] !== 'client') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

$db = getDB();
$proposalId = $_POST['proposal_id'] ?? 0;

if (!$proposalId) {
    echo json_encode(['success' => false, 'error' => 'Proposal ID is required']);
    exit;
}

try {
    $db->beginTransaction();

    // 1. Get proposal and job details
    $stmt = $db->prepare("SELECT p.*, j.client_id FROM proposals p JOIN jobs j ON p.job_id = j.id WHERE p.id = ?");
    $stmt->execute([$proposalId]);
    $proposal = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$proposal || $proposal['client_id'] != $user['id']) {
        throw new Exception("Invalid proposal or unauthorized");
    }

    // 2. Update job status to closed
    $db->prepare("UPDATE jobs SET status = 'closed' WHERE id = ?")->execute([$proposal['job_id']]);

    // 3. Update contract status to completed
    $db->prepare("UPDATE contracts SET status = 'completed', end_date = NOW() WHERE proposal_id = ? AND status = 'active'")->execute([$proposalId]);

    // 4. Reject all other non-accepted proposals for this job
    $db->prepare("UPDATE proposals SET status = 'rejected' WHERE job_id = ? AND id != ? AND status NOT IN ('accepted', 'withdrawn')")->execute([$proposal['job_id'], $proposalId]);

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Job marked as completed!']);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
