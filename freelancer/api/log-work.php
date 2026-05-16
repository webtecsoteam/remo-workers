<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

header('Content-Type: application/json');

$user = Auth::user();
if (!$user) {
    // Mock user fallback
    $db = getDB();
    $stmt = $db->query("SELECT * FROM users WHERE id = 2");
    $user = $stmt->fetch();
}

$input = json_decode(file_get_contents('php://input'), true);
$contract_id = $input['contract_id'] ?? null;
$hours = $input['hours'] ?? null;
$amount = $input['amount'] ?? 0;
$description = $input['description'] ?? null;
$attachments = $input['attachments'] ?? null;

if (!$contract_id || (!$hours && !$description && !$amount)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO work_logs (contract_id, freelancer_id, amount, hours, description, attachments) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$contract_id, $user['id'], $amount, $hours, $description, $attachments]);
    
    echo json_encode(['success' => true, 'message' => 'Work submitted successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}
