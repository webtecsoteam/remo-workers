<?php
ob_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

function json_response($data) {
    ob_end_clean();
    echo json_encode($data);
    exit;
}

$user = Auth::user();
if (!$user) {
    json_response(['success' => false, 'error' => 'Login required']);
}

$db = getDB();
$userId = (int) ($user['id'] ?? 0);

try {
    $stmt = $db->prepare("
        SELECT COUNT(*) AS cnt
        FROM messages
        WHERE receiver_id = ?
          AND is_read = 0
    ");
    $stmt->execute([$userId]);
    $unreadCount = (int) $stmt->fetchColumn();

    json_response(['success' => true, 'unread_count' => $unreadCount]);
} catch (Exception $e) {
    json_response(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

