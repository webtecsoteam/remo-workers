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
$contractId = isset($data['contract_id']) ? intval($data['contract_id']) : null;
$description = isset($data['description']) ? trim($data['description']) : '';
$amount = isset($data['amount']) ? floatval($data['amount']) : 0.0;

if (!$contractId) {
    echo json_encode(['success' => false, 'message' => 'Contract ID is required.']);
    exit;
}

if (empty($description)) {
    echo json_encode(['success' => false, 'message' => 'Milestone description is required.']);
    exit;
}

if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Milestone amount must be greater than $0.']);
    exit;
}

$db = getDB();
try {
    // 1. Verify contract exists and belongs to this client
    $cStmt = $db->prepare("SELECT * FROM contracts WHERE id = ? AND client_id = ?");
    $cStmt->execute([$contractId, $user['id']]);
    $contract = $cStmt->fetch();

    if (!$contract) {
        echo json_encode(['success' => false, 'message' => 'Contract not found or unauthorized.']);
        exit;
    }

    if ($contract['status'] === 'completed' || $contract['status'] === 'paused') {
        // Automatically reactivate contract when adding a new milestone
        $reactivateStmt = $db->prepare("UPDATE contracts SET status = 'active' WHERE id = ?");
        $reactivateStmt->execute([$contractId]);
        $contract['status'] = 'active'; // Update local value
    } elseif ($contract['status'] !== 'active') {
        echo json_encode(['success' => false, 'message' => 'Milestones can only be added to active, paused, or completed contracts.']);
        exit;
    }

    // 2. Insert new milestone
    $stmt = $db->prepare("
        INSERT INTO milestones (proposal_id, contract_id, description, amount, status) 
        VALUES (NULL, ?, ?, ?, 'pending')
    ");
    $stmt->execute([$contractId, $description, $amount]);
    $newMilestoneId = $db->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Milestone added successfully!',
        'milestone_id' => $newMilestoneId,
        'milestone' => [
            'id' => $newMilestoneId,
            'contract_id' => $contractId,
            'description' => $description,
            'amount' => $amount,
            'status' => 'pending'
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
