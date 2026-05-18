<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

header('Content-Type: application/json');

$user = Auth::user();
if (!$user || $user['role'] !== 'freelancer') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$contractId = isset($data['contract_id']) ? (int)$data['contract_id'] : 0;
$description = isset($data['description']) ? trim($data['description']) : '';
$amount = isset($data['amount']) ? (float)$data['amount'] : 0.0;

if (!$contractId || !$description || $amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters. Please fill in all fields.']);
    exit;
}

$db = getDB();

// 1. Verify contract exists and belongs to this freelancer
$contractStmt = $db->prepare("SELECT * FROM contracts WHERE id = ? AND freelancer_id = ? AND status = 'active'");
$contractStmt->execute([$contractId, $user['id']]);
$contract = $contractStmt->fetch();

if (!$contract) {
    echo json_encode(['success' => false, 'error' => 'Contract not found or is not active.']);
    exit;
}

try {
    $db->beginTransaction();

    // 2. Create the direct-submit milestone with status = 'requested'
    $mStmt = $db->prepare("
        INSERT INTO milestones (proposal_id, contract_id, description, amount, status)
        VALUES (?, ?, ?, ?, 'requested')
    ");
    $mStmt->execute([
        $contract['proposal_id'],
        $contractId,
        $description,
        $amount
    ]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Work submitted successfully! Client has been notified.'
    ]);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
