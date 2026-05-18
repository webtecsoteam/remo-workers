<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

header('Content-Type: application/json');

$user = Auth::user();
if (!$user || $user['role'] !== 'freelancer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$contractId = $_GET['contract_id'] ?? null;

if (!$contractId) {
    echo json_encode(['success' => false, 'message' => 'Contract ID required']);
    exit;
}

$db = getDB();
try {
    // Verify contract belongs to freelancer
    $stmt = $db->prepare("SELECT * FROM contracts WHERE id = ? AND freelancer_id = ?");
    $stmt->execute([$contractId, $user['id']]);
    $contract = $stmt->fetch();

    if (!$contract) {
        echo json_encode(['success' => false, 'message' => 'Contract not found or unauthorized']);
        exit;
    }

    // Fetch work logs
    $wlStmt = $db->prepare("SELECT * FROM work_logs WHERE contract_id = ? ORDER BY created_at DESC");
    $wlStmt->execute([$contractId]);
    $workLogs = $wlStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    echo json_encode(['success' => true, 'work_logs' => $workLogs]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
