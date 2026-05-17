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
    $stmt = $db->prepare("SELECT p.*, j.client_id, j.budget_type FROM proposals p JOIN jobs j ON p.job_id = j.id WHERE p.id = ?");
    $stmt->execute([$proposalId]);
    $proposal = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$proposal || $proposal['client_id'] != $user['id']) {
        throw new Exception("Invalid proposal");
    }

    // 1.1 Balance check during acceptance is removed so clients can accept first and fund milestones later
    /*
    $balanceStmt = $db->prepare("SELECT balance FROM users WHERE id = ? FOR UPDATE");
    $balanceStmt->execute([$user['id']]);
    $clientBalance = (float)$balanceStmt->fetchColumn();
    $requiredAmount = (float)$proposal['bid_amount'];

    if ($clientBalance < $requiredAmount) {
        throw new Exception("Insufficient balance to hire the freelancer. Your balance: $" . number_format($clientBalance, 2) . ", required: $" . number_format($requiredAmount, 2) . ". Please add funds to your account.");
    }
    */

    // 2. Create contract
    $cStmt = $db->prepare("INSERT INTO contracts (job_id, client_id, freelancer_id, proposal_id, amount, contract_type, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
    $cStmt->execute([
        $proposal['job_id'],
        $user['id'],
        $proposal['freelancer_id'],
        $proposal['id'],
        $proposal['bid_amount'],
        $proposal['budget_type']
    ]);
    $contractId = $db->lastInsertId();

    // 2.1 Link milestones to the contract
    $db->prepare("UPDATE milestones SET contract_id = ? WHERE proposal_id = ?")->execute([$contractId, $proposalId]);

    // 3. Update proposal status
    $db->prepare("UPDATE proposals SET status = 'accepted' WHERE id = ?")->execute([$proposalId]);

    // 4. Update job status to in_progress
    $db->prepare("UPDATE jobs SET status = 'in_progress' WHERE id = ?")->execute([$proposal['job_id']]);

    $db->commit();

    echo json_encode(['success' => true, 'message' => 'Freelancer hired successfully!']);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
