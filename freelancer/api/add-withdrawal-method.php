<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

header('Content-Type: application/json');

$user = Auth::user();
if (!$user || $user['role'] !== 'freelancer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$method_type = isset($data['method_type']) ? trim($data['method_type']) : '';
$details = isset($data['details']) && is_array($data['details']) ? $data['details'] : [];

if (empty($method_type) || empty($details)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
    exit;
}

$db = getDB();

try {
    // Check if this is the first method, if so make it default
    $count = $db->prepare("SELECT COUNT(*) FROM user_withdrawal_methods WHERE user_id = ?");
    $count->execute([$user['id']]);
    $isDefault = ($count->fetchColumn() == 0) ? 1 : 0;

    $stmt = $db->prepare("
        INSERT INTO user_withdrawal_methods (user_id, method_type, details, is_default)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$user['id'], $method_type, json_encode($details), $isDefault]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
