<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

header('Content-Type: application/json');

$user = Auth::user();
if (!$user || $user['role'] !== 'freelancer') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

$db = getDB();
$contractId = $_POST['contract_id'] ?? 0;
$rating = (float)($_POST['rating'] ?? 5.0);
$feedback = trim($_POST['feedback'] ?? 'Great client to work with!');

if (!$contractId) {
    echo json_encode(['success' => false, 'error' => 'Contract ID is required']);
    exit;
}

try {
    $db->beginTransaction();

    // 1. Verify contract and freelancer ownership
    $stmt = $db->prepare("SELECT * FROM contracts WHERE id = ? AND freelancer_id = ?");
    $stmt->execute([$contractId, $user['id']]);
    $contract = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$contract) {
        throw new Exception("Contract not found or unauthorized");
    }

    if ($contract['status'] === 'completed') {
        throw new Exception("Contract is already completed");
    }

    // 2. Update contract status to completed
    $db->prepare("UPDATE contracts SET status = 'completed', end_date = NOW() WHERE id = ?")->execute([$contractId]);

    // 3. Update job status to closed
    $db->prepare("UPDATE jobs SET status = 'closed' WHERE id = ?")->execute([$contract['job_id']]);

    // 4. Save review given by freelancer to client
    // Check if freelancer already reviewed client
    $check = $db->prepare("SELECT id FROM reviews WHERE contract_id = ? AND reviewer_id = ?");
    $check->execute([$contractId, $user['id']]);
    if (!$check->fetch()) {
        $revStmt = $db->prepare("
            INSERT INTO reviews (contract_id, reviewer_id, reviewee_id, rating, feedback)
            VALUES (?, ?, ?, ?, ?)
        ");
        $revStmt->execute([
            $contractId,
            $user['id'], // freelancer
            $contract['client_id'], // client
            $rating,
            $feedback
        ]);
    }

    // 5. Reject other proposals if any
    $db->prepare("UPDATE proposals SET status = 'rejected' WHERE job_id = ? AND id != ? AND status NOT IN ('accepted', 'withdrawn')")->execute([$contract['job_id'], $contract['proposal_id']]);

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Contract marked as completed successfully!']);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
