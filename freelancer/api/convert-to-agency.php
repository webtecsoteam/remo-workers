<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

header('Content-Type: application/json');

$user = Auth::user();
if (!$user || ($user['role'] ?? '') !== 'freelancer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

ensureAgencySchema();
$db = getDB();

try {
    $stmt = $db->prepare("UPDATE users SET account_mode = 'agency' WHERE id = ?");
    $stmt->execute([(int)$user['id']]);
    echo json_encode(['success' => true, 'message' => 'Account converted to agency mode.']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
