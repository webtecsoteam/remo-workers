<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

header('Content-Type: application/json');

$user = Auth::user();
if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$contract_id = $input['contract_id'] ?? null;
$status = $input['status'] ?? null;

if (!$contract_id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("UPDATE contracts SET status = ? WHERE id = ? AND freelancer_id = ?");
    $stmt->execute([$status, $contract_id, $user['id']]);
    
    echo json_encode(['success' => true, 'message' => 'Contract updated to ' . $status]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}
