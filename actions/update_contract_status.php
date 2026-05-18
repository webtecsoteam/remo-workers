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
$contractId = $_POST['contract_id'] ?? 0;
$newStatus = $_POST['status'] ?? '';

if (!$contractId || !$newStatus) {
    echo json_encode(['success' => false, 'error' => 'Contract ID and status are required']);
    exit;
}

$allowed = ['active', 'paused', 'completed', 'cancelled'];
if (!in_array($newStatus, $allowed)) {
    echo json_encode(['success' => false, 'error' => 'Invalid status']);
    exit;
}

try {
    // Verify client owns the contract
    $check = $db->prepare("SELECT id FROM contracts WHERE id = ? AND client_id = ?");
    $check->execute([$contractId, $user['id']]);
    if (!$check->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Contract not found or unauthorized']);
        exit;
    }

    $stmt = $db->prepare("UPDATE contracts SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$newStatus, $contractId]);

    echo json_encode(['success' => true, 'message' => "Contract " . ucfirst($newStatus)]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
