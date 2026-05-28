<?php
ob_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';
require_once __DIR__ . '/../includes/client_blocks.php';

function json_response($data) {
    ob_end_clean();
    echo json_encode($data);
    exit;
}

$user = Auth::user();
if (!$user) {
    json_response(['success' => false, 'error' => 'Login required']);
}

$other_id = intval($_GET['with'] ?? 0);
if (!$other_id) {
    json_response(['success' => false, 'error' => 'Missing recipient ID']);
}

$db = getDB();
try {
    // Mark as read
    $stmt = $db->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ?");
    $stmt->execute([$user['id'], $other_id]);

    // Fetch messages
    $stmt = $db->prepare("
        SELECT m.*, u.name as sender_name 
        FROM messages m 
        JOIN users u ON m.sender_id = u.id 
        WHERE (m.sender_id = ? AND m.receiver_id = ?) 
           OR (m.sender_id = ? AND m.receiver_id = ?) 
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$user['id'], $other_id, $other_id, $user['id']]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $blockStatus = clientMessagingBlockStatus($db, $user, $other_id);

    json_response([
        'success' => true,
        'messages' => $messages,
        'blocked' => $blockStatus['blocked'],
        'blocked_by_me' => $blockStatus['blocked_by_me'],
    ]);
} catch (Exception $e) {
    json_response(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
