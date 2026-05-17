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
